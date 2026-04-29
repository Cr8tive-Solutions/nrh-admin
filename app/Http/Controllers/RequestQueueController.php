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

        return back()->with('success', 'Request status updated.');
    }

    public function updateCandidateStatus(Request $request, ScreeningRequest $screeningRequest, int $candidateId)
    {
        $data = $request->validate([
            'status' => 'required|in:new,in_progress,flagged,complete',
        ]);

        $screeningRequest->candidates()->findOrFail($candidateId)->update(['status' => $data['status']]);

        return back()->with('success', 'Candidate status updated.');
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

        return back()->with('success', 'Scope status updated.');
    }
}
