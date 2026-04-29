<?php

namespace App\Http\Controllers;

use App\Models\AdminAuditLog;
use App\Models\ScreeningRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RequestQueueController extends Controller
{
    public function index(Request $request)
    {
        $query = ScreeningRequest::with('customer')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'ilike', "%{$search}%")
                  ->orWhereHas('customer', fn ($c) => $c->where('name', 'ilike', "%{$search}%"));
            });
        }

        $requests = $query->paginate(25)->withQueryString();

        return view('requests.index', compact('requests'));
    }

    public function show(ScreeningRequest $screeningRequest)
    {
        $screeningRequest->load(['customer', 'customerUser', 'candidates.identityType', 'candidates.scopeTypes']);

        $candidateStats = [
            'total'       => $screeningRequest->candidates->count(),
            'new'         => $screeningRequest->candidates->where('status', 'new')->count(),
            'in_progress' => $screeningRequest->candidates->where('status', 'in_progress')->count(),
            'flagged'     => $screeningRequest->candidates->where('status', 'flagged')->count(),
            'complete'    => $screeningRequest->candidates->where('status', 'complete')->count(),
        ];

        return view('requests.show', [
            'request'        => $screeningRequest,
            'candidateStats' => $candidateStats,
        ]);
    }

    public function updateStatus(Request $request, ScreeningRequest $screeningRequest)
    {
        $data = $request->validate([
            'status' => 'required|in:new,in_progress,flagged,complete',
        ]);

        $screeningRequest->update(['status' => $data['status']]);

        return $this->saveResponse($request, 'Request status updated.', [
            'status' => $screeningRequest->status,
        ]);
    }

    public function updateCandidateStatus(Request $request, ScreeningRequest $screeningRequest, int $candidateId)
    {
        $data = $request->validate([
            'status' => 'required|in:new,in_progress,flagged,complete',
        ]);

        $candidate = $screeningRequest->candidates()->findOrFail($candidateId);
        $candidate->update(['status' => $data['status']]);

        return $this->saveResponse($request, 'Candidate status updated.', [
            'candidate_id' => $candidate->id,
            'status'       => $candidate->status,
        ]);
    }

    /**
     * Update an individual scope check's status on a candidate.
     * Auto-stamps timestamps so the BusinessHours service can compute TAT.
     */
    public function updateScopeStatus(Request $request, ScreeningRequest $screeningRequest, int $candidateId, int $scopeTypeId)
    {
        $data = $request->validate([
            'status' => 'required|in:new,in_progress,flagged,complete',
        ]);

        $candidate = $screeningRequest->candidates()->findOrFail($candidateId);

        // Read current pivot row directly so we know previous status + existing timestamps.
        $pivot = DB::table('candidate_scope_type')
            ->where('request_candidate_id', $candidate->id)
            ->where('scope_type_id', $scopeTypeId)
            ->first();

        if (! $pivot) {
            abort(404, 'Scope is not assigned to this candidate.');
        }

        $now = now();
        $newStatus = $data['status'];
        $previousStatus = $pivot->status;

        $update = ['status' => $newStatus];

        // Backfill assigned_at if missing (older rows that pre-date the migration backfill)
        if (! $pivot->assigned_at) {
            $update['assigned_at'] = $candidate->created_at ?? $now;
        }

        // Stamp started_at the first time the scope leaves "new"
        if (! $pivot->started_at && in_array($newStatus, ['in_progress', 'flagged', 'complete'], true)) {
            $update['started_at'] = $now;
        }

        // Stamp/clear completed_at on terminal transitions
        if (in_array($newStatus, ['complete', 'flagged'], true)) {
            if (! $pivot->completed_at) {
                $update['completed_at'] = $now;
            }
        } elseif ($pivot->completed_at && in_array($newStatus, ['new', 'in_progress'], true)) {
            // Reverting from terminal back to active: clear completed_at so TAT continues.
            $update['completed_at'] = null;
        }

        DB::table('candidate_scope_type')
            ->where('request_candidate_id', $candidate->id)
            ->where('scope_type_id', $scopeTypeId)
            ->update($update);

        AdminAuditLog::record('scope_status.updated', null, [
            'request_id'    => $screeningRequest->id,
            'reference'     => $screeningRequest->reference,
            'candidate_id'  => $candidate->id,
            'candidate'     => $candidate->name,
            'scope_type_id' => $scopeTypeId,
            'from'          => $previousStatus,
            'to'            => $newStatus,
        ]);

        return $this->saveResponse($request, 'Scope status updated.', [
            'candidate_id'  => $candidate->id,
            'scope_type_id' => $scopeTypeId,
            'status'        => $newStatus,
            'started_at'    => $update['started_at'] ?? $pivot->started_at,
            'completed_at'  => array_key_exists('completed_at', $update) ? $update['completed_at'] : $pivot->completed_at,
        ]);
    }

    /**
     * Save typed findings (narrative comment + structured record fields)
     * for a single scope check on a candidate. Becomes the body content
     * for that scope in the generated PDF.
     */
    public function updateScopeFindings(Request $request, ScreeningRequest $screeningRequest, int $candidateId, int $scopeTypeId)
    {
        $data = $request->validate([
            'comment' => 'nullable|string|max:8000',
            'record'  => 'nullable|array',
            'record.*' => 'nullable|string|max:1000',
        ]);

        $candidate = $screeningRequest->candidates()->findOrFail($candidateId);

        $exists = DB::table('candidate_scope_type')
            ->where('request_candidate_id', $candidate->id)
            ->where('scope_type_id', $scopeTypeId)
            ->exists();

        if (! $exists) {
            abort(404, 'Scope is not assigned to this candidate.');
        }

        $payload = [];
        if (isset($data['comment']) && trim($data['comment']) !== '') {
            $payload['comment'] = trim($data['comment']);
        }
        if (! empty($data['record'])) {
            $clean = array_filter(
                array_map(fn ($v) => is_string($v) ? trim($v) : $v, $data['record']),
                fn ($v) => $v !== null && $v !== ''
            );
            if (! empty($clean)) {
                $payload['record'] = $clean;
            }
        }

        DB::table('candidate_scope_type')
            ->where('request_candidate_id', $candidate->id)
            ->where('scope_type_id', $scopeTypeId)
            ->update(['findings' => empty($payload) ? null : json_encode($payload)]);

        AdminAuditLog::record('scope_findings.updated', null, [
            'request_id'    => $screeningRequest->id,
            'reference'     => $screeningRequest->reference,
            'candidate_id'  => $candidate->id,
            'scope_type_id' => $scopeTypeId,
            'has_comment'   => isset($payload['comment']),
            'record_keys'   => isset($payload['record']) ? array_keys($payload['record']) : [],
        ]);

        return $this->saveResponse($request, 'Findings saved.', [
            'candidate_id'  => $candidate->id,
            'scope_type_id' => $scopeTypeId,
            'has_findings'  => ! empty($payload),
        ]);
    }

    /**
     * Update report metadata stored on screening_requests.meta
     * (Research analyst, editor, PO #, basic/prelim/full completion dates).
     */
    public function updateMeta(Request $request, ScreeningRequest $screeningRequest)
    {
        $data = $request->validate([
            'analyst'           => 'nullable|string|max:255',
            'editor'            => 'nullable|string|max:255',
            'po_number'         => 'nullable|string|max:120',
            'completion_basic'  => 'nullable|date',
            'completion_prelim' => 'nullable|date',
            'completion_full'   => 'nullable|date',
        ]);

        $meta = $screeningRequest->meta ?? [];
        foreach ($data as $key => $value) {
            if ($value === null || $value === '') {
                unset($meta[$key]);
            } else {
                $meta[$key] = $value;
            }
        }

        $screeningRequest->update(['meta' => $meta]);

        return $this->saveResponse($request, 'Report metadata saved.', ['meta' => $meta]);
    }

    /**
     * Standardised save response — JSON for AJAX callers, redirect-with-flash otherwise.
     */
    private function saveResponse(Request $request, string $message, array $payload = [])
    {
        if ($request->expectsJson()) {
            return response()->json(array_merge(['ok' => true, 'message' => $message], $payload));
        }
        return back()->with('success', $message);
    }
}
