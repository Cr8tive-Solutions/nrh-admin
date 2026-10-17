<?php

namespace App\Http\Controllers;

use App\Models\AdminAuditLog;
use App\Models\RequestCandidate;
use App\Models\ReportVersion;
use App\Models\ScreeningRequest;
use App\Services\ReportSnapshot;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    /**
     * Live preview / on-the-fly download for one candidate.
     * Renders from current state, never persists a version.
     */
    public function preview(Request $request, ScreeningRequest $screeningRequest, RequestCandidate $candidate)
    {
        $this->assertCandidate($screeningRequest, $candidate);
        $screeningRequest->load(['customer', 'customerUser', 'candidates.identityType', 'candidates.scopeTypes', 'candidates.latestConsent']);

        $pdf = $this->renderPdf($screeningRequest, $candidate, [
            'reportType' => null,
            'reportVersion' => null,
            'reportHash' => null,
        ]);

        $filename = 'NRH-Report-Preview-'.$screeningRequest->reference.'-'.$this->candidateSlug($candidate).'.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    /**
     * Create a persisted version of one candidate's report (Prelim / Full).
     * Refuses if the content_hash matches that candidate's latest version of
     * the same type (no changes since last issue) unless explicitly superseding.
     */
    public function generate(Request $request, ScreeningRequest $screeningRequest, RequestCandidate $candidate)
    {
        $this->assertCandidate($screeningRequest, $candidate);

        $data = $request->validate([
            'type' => 'required|in:prelim,full',
            'supersedes_id' => 'nullable|integer|exists:report_versions,id',
            'supersede_reason' => 'nullable|string|max:1000|required_with:supersedes_id',
        ]);

        $screeningRequest->load(['customer', 'customerUser', 'candidates.identityType', 'candidates.scopeTypes', 'candidates.latestConsent']);

        $type = $data['type'];
        $supersedesId = $data['supersedes_id'] ?? null;

        // Auto-fill completion_* date BEFORE the snapshot, so the hash is stable
        // across subsequent generate clicks. Request-level meta, shared on the cover.
        $metaKey = match ($type) {
            'prelim' => 'completion_prelim',
            'full' => 'completion_full',
            default => null,
        };
        $meta = $screeningRequest->meta ?? [];
        $completionAutofilled = false;
        if ($metaKey && empty($meta[$metaKey])) {
            $meta[$metaKey] = now()->format('Y-m-d');
            $screeningRequest->update(['meta' => $meta]);
            $screeningRequest->refresh();
            $completionAutofilled = true;
        }

        $snapshot = ReportSnapshot::build($screeningRequest, $candidate);
        $contentHash = ReportSnapshot::hash($snapshot);

        $latest = ReportVersion::where('request_candidate_id', $candidate->id)
            ->where('type', $type)
            ->orderByDesc('version')
            ->first();

        // Hash check: nothing changed for this candidate since the last version.
        if ($latest && $latest->content_hash === $contentHash && ! $supersedesId) {
            if ($completionAutofilled) {
                $rollback = $screeningRequest->meta ?? [];
                unset($rollback[$metaKey]);
                $screeningRequest->update(['meta' => $rollback]);
            }
            throw ValidationException::withMessages([
                'type' => "No changes to {$candidate->name} since {$latest->label()} (issued {$latest->generated_at->diffForHumans()}). Edit findings or metadata first, or use Supersede to issue a corrected version.",
            ]);
        }

        if ($supersedesId) {
            $sup = ReportVersion::find($supersedesId);
            if (! $sup || $sup->request_candidate_id !== $candidate->id || $sup->type !== $type) {
                throw ValidationException::withMessages([
                    'supersedes_id' => 'Superseded version must be from the same candidate and the same type.',
                ]);
            }
        }

        $newVersion = ($latest?->version ?? 0) + 1;

        $version = new ReportVersion([
            'screening_request_id' => $screeningRequest->id,
            'request_candidate_id' => $candidate->id,
            'type' => $type,
            'version' => $newVersion,
            'generated_at' => now(),
            'generated_by_admin_id' => current_admin()?->id,
            'content_hash' => $contentHash,
            'snapshot' => $snapshot,
            'supersedes_id' => $supersedesId,
            'supersede_reason' => $data['supersede_reason'] ?? null,
            'file_path' => '',
            'file_sha256' => '',
        ]);

        // First render (placeholder hash), then re-render with the file hash embedded.
        $pdfBytes = $this->renderPdf($screeningRequest, $candidate, [
            'reportType' => $type, 'reportVersion' => $newVersion, 'reportHash' => null,
        ]);
        $fileSha256 = hash('sha256', $pdfBytes);
        $pdfBytes = $this->renderPdf($screeningRequest, $candidate, [
            'reportType' => $type, 'reportVersion' => $newVersion, 'reportHash' => substr($fileSha256, 0, 8),
        ]);
        $fileSha256 = hash('sha256', $pdfBytes);

        $relPath = "reports/{$screeningRequest->id}/candidate-{$candidate->id}/{$type}-v{$newVersion}.pdf";
        Storage::disk('local')->put($relPath, $pdfBytes);

        $version->file_path = $relPath;
        $version->file_sha256 = $fileSha256;

        $previousStatus = $screeningRequest->status;

        DB::transaction(function () use ($version, $supersedesId, $screeningRequest, $candidate, $type, $previousStatus, &$statusFlip) {
            $version->save();

            $statusFlip = null;
            if ($type === 'full') {
                // The request completes once EVERY candidate has at least one
                // full report. Re-issuing a full after completion marks it updated.
                if ($this->allCandidatesHaveFullReport($screeningRequest)) {
                    $statusFlip = in_array($previousStatus, ['complete', 'updated'], true) ? 'updated' : 'complete';
                }
                if ($statusFlip && $statusFlip !== $previousStatus) {
                    $screeningRequest->update(['status' => $statusFlip]);
                }
            }

            if ($supersedesId) {
                AdminAuditLog::record('report.version_superseded', null, [
                    'request_id' => $version->screening_request_id,
                    'candidate_id' => $candidate->id,
                    'type' => $version->type,
                    'new_version' => $version->version,
                    'superseded_version' => $supersedesId,
                    'reason' => $version->supersede_reason,
                ]);
            }

            AdminAuditLog::record('report.version_issued', null, [
                'request_id' => $version->screening_request_id,
                'candidate_id' => $candidate->id,
                'type' => $version->type,
                'version' => $version->version,
                'file_sha256' => $version->file_sha256,
                'content_hash' => $version->content_hash,
                'supersedes_id' => $supersedesId,
                'status_flip' => $statusFlip && $statusFlip !== $previousStatus
                    ? ['from' => $previousStatus, 'to' => $statusFlip] : null,
            ]);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => "{$version->label()} issued for {$candidate->name}.",
                'version_id' => $version->id,
                'label' => $version->label(),
                'short_hash' => $version->shortHash(),
            ]);
        }

        return redirect()->route('requests.show', $screeningRequest)
            ->with('success', "{$version->label()} issued for {$candidate->name}.");
    }

    /**
     * Re-download a previously persisted version (immutable).
     */
    public function download(ScreeningRequest $screeningRequest, ReportVersion $version)
    {
        if ($version->screening_request_id !== $screeningRequest->id) {
            abort(404);
        }
        if (! Storage::disk('local')->exists($version->file_path)) {
            abort(410, 'This report file is no longer available on disk.');
        }

        $bytes = Storage::disk('local')->get($version->file_path);
        $candidateSlug = $version->requestCandidate ? '-'.$this->candidateSlug($version->requestCandidate) : '';
        $filename = 'NRH-Report-'.$screeningRequest->reference.$candidateSlug.'-'.ucfirst($version->type).'-v'.$version->version.'.pdf';

        AdminAuditLog::record('report.downloaded', null, [
            'request_id' => $screeningRequest->id,
            'candidate_id' => $version->request_candidate_id,
            'version_id' => $version->id,
            'type' => $version->type,
            'version' => $version->version,
        ]);

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function view(ScreeningRequest $screeningRequest, ReportVersion $version)
    {
        if ($version->screening_request_id !== $screeningRequest->id) {
            abort(404);
        }
        if (! Storage::disk('local')->exists($version->file_path)) {
            abort(410);
        }
        $bytes = Storage::disk('local')->get($version->file_path);

        AdminAuditLog::record('report.viewed', null, [
            'request_id' => $screeningRequest->id,
            'candidate_id' => $version->request_candidate_id,
            'version_id' => $version->id,
            'type' => $version->type,
            'version' => $version->version,
        ]);

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$version->label().'.pdf"',
        ]);
    }

    /** True when every candidate in the request has at least one full report. */
    private function allCandidatesHaveFullReport(ScreeningRequest $screeningRequest): bool
    {
        $candidateIds = $screeningRequest->candidates->pluck('id');
        if ($candidateIds->isEmpty()) {
            return false;
        }

        $withFull = ReportVersion::where('type', 'full')
            ->whereIn('request_candidate_id', $candidateIds)
            ->distinct()
            ->pluck('request_candidate_id');

        return $candidateIds->diff($withFull)->isEmpty();
    }

    private function assertCandidate(ScreeningRequest $screeningRequest, RequestCandidate $candidate): void
    {
        abort_unless($candidate->screening_request_id === $screeningRequest->id, 404);
    }

    private function candidateSlug(RequestCandidate $candidate): string
    {
        $name = preg_replace('/[^A-Za-z0-9]+/', '-', (string) $candidate->name);

        return trim($name, '-') ?: ('candidate-'.$candidate->id);
    }

    /**
     * Render the screening report HTML to PDF bytes for a single candidate.
     */
    private function renderPdf(ScreeningRequest $screeningRequest, RequestCandidate $candidate, array $stamps = []): string
    {
        $logoPath = public_path('images/nrh-logo.png');
        $logoSrc = file_exists($logoPath)
            ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath))
            : '';

        $meta = $screeningRequest->meta ?? [];
        $fmtDate = fn ($key) => ! empty($meta[$key])
            ? Carbon::parse($meta[$key])->format('d F Y')
            : null;

        // Only this candidate goes into the report — the template loops over the
        // collection it's given, so a one-element collection yields an individual report.
        $only = $screeningRequest->candidates->where('id', $candidate->id)->values();

        $data = [
            'request' => $screeningRequest,
            'reference' => $screeningRequest->reference,
            'customer' => $screeningRequest->customer,
            'candidates' => $only,
            'logoSrc' => $logoSrc,
            'completionBasic' => $fmtDate('completion_basic'),
            'completionPrelim' => $fmtDate('completion_prelim'),
            'completionFull' => $fmtDate('completion_full')
                ?? ($screeningRequest->status === 'complete' ? $screeningRequest->updated_at->format('d F Y') : null),
            'reportType' => $stamps['reportType'] ?? null,
            'reportVersion' => $stamps['reportVersion'] ?? null,
            'reportHash' => $stamps['reportHash'] ?? null,
        ];

        $pdf = Pdf::loadView('reports.screening', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => true,
                'defaultFont' => 'Courier Prime',
                'fontDir' => storage_path('fonts'),
                'fontCache' => storage_path('fonts'),
            ]);

        $dompdf = $pdf->getDomPDF();
        $dompdf->render();
        $canvas = $dompdf->getCanvas();
        $fontMetrics = $dompdf->getFontMetrics();
        $font = $fontMetrics->getFont('Oswald', 'normal');
        $canvas->page_text(
            $canvas->get_width() - 115,
            $canvas->get_height() - 24,
            'Page {PAGE_NUM} of {PAGE_COUNT}',
            $font,
            8,
            [0.4, 0.4, 0.4]
        );

        return $dompdf->output();
    }
}
