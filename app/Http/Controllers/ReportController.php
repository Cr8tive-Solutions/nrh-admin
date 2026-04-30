<?php

namespace App\Http\Controllers;

use App\Models\AdminAuditLog;
use App\Models\ReportVersion;
use App\Models\ScreeningRequest;
use App\Services\ReportSnapshot;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    /**
     * Live preview / on-the-fly download.
     * Renders from current state, never persists a version.
     */
    public function preview(Request $request, ScreeningRequest $screeningRequest)
    {
        $screeningRequest->load(['customer', 'customerUser', 'candidates.identityType', 'candidates.scopeTypes', 'candidates.latestConsent']);

        $pdf = $this->renderPdf($screeningRequest, [
            'reportType'    => null,
            'reportVersion' => null,
            'reportHash'    => null,
        ]);

        $filename = 'NRH-Report-Preview-'.$screeningRequest->reference.'.pdf';
        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    /**
     * Create a persisted version of the report (Basic / Prelim / Full).
     * Refuses if the content_hash matches the latest version of the same type
     * (no changes since last issue) unless explicitly superseding.
     */
    public function generate(Request $request, ScreeningRequest $screeningRequest)
    {
        $data = $request->validate([
            'type'              => 'required|in:basic,prelim,full',
            'supersedes_id'     => 'nullable|integer|exists:report_versions,id',
            'supersede_reason'  => 'nullable|string|max:1000|required_with:supersedes_id',
        ]);

        $screeningRequest->load(['customer', 'customerUser', 'candidates.identityType', 'candidates.scopeTypes', 'candidates.latestConsent']);

        $type         = $data['type'];
        $supersedesId = $data['supersedes_id'] ?? null;

        // Auto-fill completion_* date BEFORE the snapshot, so the hash is stable
        // across subsequent generate clicks (otherwise the post-snapshot fill would
        // make every later snapshot look "changed").
        $metaKey = match ($type) {
            'basic'  => 'completion_basic',
            'prelim' => 'completion_prelim',
            'full'   => 'completion_full',
        };
        $meta = $screeningRequest->meta ?? [];
        $completionAutofilled = false;
        if (empty($meta[$metaKey])) {
            $meta[$metaKey] = now()->format('Y-m-d');
            $screeningRequest->update(['meta' => $meta]);
            $screeningRequest->refresh();
            $completionAutofilled = true;
        }

        $snapshot     = ReportSnapshot::build($screeningRequest);
        $contentHash  = ReportSnapshot::hash($snapshot);

        $latest = ReportVersion::where('screening_request_id', $screeningRequest->id)
            ->where('type', $type)
            ->orderByDesc('version')
            ->first();

        // Hash check: if nothing has changed since the last version of this type
        // and the user isn't explicitly superseding it, reject the duplicate.
        if ($latest && $latest->content_hash === $contentHash && ! $supersedesId) {
            // Roll back the autofill if we did it just for this attempt — keep state pristine.
            if ($completionAutofilled) {
                $rollback = $screeningRequest->meta ?? [];
                unset($rollback[$metaKey]);
                $screeningRequest->update(['meta' => $rollback]);
            }
            throw ValidationException::withMessages([
                'type' => "No changes since {$latest->label()} (issued {$latest->generated_at->diffForHumans()}). Edit findings or metadata first, or use Supersede to issue a corrected version.",
            ]);
        }

        // Validate supersedes_id belongs to same request + same type
        if ($supersedesId) {
            $sup = ReportVersion::find($supersedesId);
            if (! $sup || $sup->screening_request_id !== $screeningRequest->id || $sup->type !== $type) {
                throw ValidationException::withMessages([
                    'supersedes_id' => 'Superseded version must be from the same request and the same type.',
                ]);
            }
        }

        $newVersion = ($latest?->version ?? 0) + 1;

        // Pre-create the model so its id can be stamped into the PDF.
        $version = new ReportVersion([
            'screening_request_id'  => $screeningRequest->id,
            'type'                  => $type,
            'version'               => $newVersion,
            'generated_at'          => now(),
            'generated_by_admin_id' => current_admin()?->id,
            'content_hash'          => $contentHash,
            'snapshot'              => $snapshot,
            'supersedes_id'         => $supersedesId,
            'supersede_reason'      => $data['supersede_reason'] ?? null,
            'file_path'             => '',         // filled below
            'file_sha256'           => '',         // filled below
        ]);

        // Render PDF with the version label + (placeholder) hash on the cover.
        // Reorder: hash is computed from final file bytes, so placeholder for now.
        $pdfBytes = $this->renderPdf($screeningRequest, [
            'reportType'    => $type,
            'reportVersion' => $newVersion,
            'reportHash'    => null, // first render — see below
        ]);
        $fileSha256 = hash('sha256', $pdfBytes);

        // Re-render with the actual file hash embedded so it's traceable on the artifact itself.
        $pdfBytes = $this->renderPdf($screeningRequest, [
            'reportType'    => $type,
            'reportVersion' => $newVersion,
            'reportHash'    => substr($fileSha256, 0, 8),
        ]);
        // file_sha256 reflects the final bytes that get persisted/downloaded.
        $fileSha256 = hash('sha256', $pdfBytes);

        $relPath = "reports/{$screeningRequest->id}/{$type}-v{$newVersion}.pdf";
        Storage::disk('local')->put($relPath, $pdfBytes);

        $version->file_path   = $relPath;
        $version->file_sha256 = $fileSha256;

        DB::transaction(function () use ($version, $supersedesId) {
            $version->save();

            if ($supersedesId) {
                AdminAuditLog::record('report.version_superseded', null, [
                    'request_id'         => $version->screening_request_id,
                    'type'               => $version->type,
                    'new_version'        => $version->version,
                    'superseded_version' => $supersedesId,
                    'reason'             => $version->supersede_reason,
                ]);
            }

            AdminAuditLog::record('report.version_issued', null, [
                'request_id'    => $version->screening_request_id,
                'type'          => $version->type,
                'version'       => $version->version,
                'file_sha256'   => $version->file_sha256,
                'content_hash'  => $version->content_hash,
                'supersedes_id' => $supersedesId,
            ]);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'ok'         => true,
                'message'    => "{$version->label()} issued.",
                'version_id' => $version->id,
                'label'      => $version->label(),
                'short_hash' => $version->shortHash(),
            ]);
        }

        return redirect()->route('requests.show', $screeningRequest)
            ->with('success', "{$version->label()} issued.");
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
        $filename = 'NRH-Report-'.$screeningRequest->reference.'-'.ucfirst($version->type).'-v'.$version->version.'.pdf';

        return response($bytes, 200, [
            'Content-Type'        => 'application/pdf',
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
        return response($bytes, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$version->label().'.pdf"',
        ]);
    }

    /**
     * Render the screening report HTML to PDF bytes.
     */
    private function renderPdf(ScreeningRequest $screeningRequest, array $stamps = []): string
    {
        $logoPath = public_path('images/nrh-logo.png');
        $logoSrc = file_exists($logoPath)
            ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath))
            : '';

        $meta = $screeningRequest->meta ?? [];
        $fmtDate = fn ($key) => ! empty($meta[$key])
            ? \Carbon\Carbon::parse($meta[$key])->format('d F Y')
            : null;

        $data = [
            'request'           => $screeningRequest,
            'reference'         => $screeningRequest->reference,
            'customer'          => $screeningRequest->customer,
            'candidates'        => $screeningRequest->candidates,
            'logoSrc'           => $logoSrc,
            'completionBasic'   => $fmtDate('completion_basic'),
            'completionPrelim'  => $fmtDate('completion_prelim'),
            'completionFull'    => $fmtDate('completion_full')
                ?? ($screeningRequest->status === 'complete' ? $screeningRequest->updated_at->format('d F Y') : null),
            'reportType'        => $stamps['reportType'] ?? null,
            'reportVersion'     => $stamps['reportVersion'] ?? null,
            'reportHash'        => $stamps['reportHash'] ?? null,
        ];

        $pdf = Pdf::loadView('reports.screening', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled'         => true,
                'defaultFont'          => 'DejaVu Sans',
            ]);

        $dompdf = $pdf->getDomPDF();
        $dompdf->render();
        $canvas = $dompdf->getCanvas();
        $fontMetrics = $dompdf->getFontMetrics();
        $font = $fontMetrics->getFont('DejaVu Sans', 'bold');
        $canvas->page_text(
            $canvas->get_width() - 115,
            $canvas->get_height() - 38,
            'Page {PAGE_NUM} of {PAGE_COUNT}',
            $font,
            9,
            [0, 0, 0]
        );

        return $dompdf->output();
    }
}
