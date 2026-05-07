<?php

namespace App\Http\Controllers;

use App\Models\AdminAuditLog;
use App\Models\ReportVersion;
use App\Models\ScreeningRequest;
use App\Models\Transaction;
use App\Services\ReportSnapshot;
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
        $screeningRequest->load(['customer.agreements', 'customerUser', 'candidates.identityType', 'candidates.scopeTypes', 'candidates.latestConsent.capturedBy']);

        $candidateStats = [
            'total'       => $screeningRequest->candidates->count(),
            'new'         => $screeningRequest->candidates->where('status', 'new')->count(),
            'in_progress' => $screeningRequest->candidates->where('status', 'in_progress')->count(),
            'flagged'     => $screeningRequest->candidates->where('status', 'flagged')->count(),
            'complete'    => $screeningRequest->candidates->where('status', 'complete')->count(),
        ];

        // Report versions, current snapshot hash, and per-type freshness for the Generate panel.
        $versions = ReportVersion::with('generatedBy', 'supersededBy')
            ->where('screening_request_id', $screeningRequest->id)
            ->orderBy('type')
            ->orderByDesc('version')
            ->get();

        $currentSnapshot = ReportSnapshot::build($screeningRequest);
        $currentHash     = ReportSnapshot::hash($currentSnapshot);

        $latestPerType = $versions->groupBy('type')->map(fn ($g) => $g->sortByDesc('version')->first());
        $reportFreshness = collect(ReportVersion::types())->mapWithKeys(function ($type) use ($latestPerType, $currentHash) {
            $latest = $latestPerType->get($type);
            return [$type => [
                'latest'      => $latest,
                'has_changes' => $latest === null || $latest->content_hash !== $currentHash,
            ]];
        })->all();

        // Cash-billed customers need explicit payment confirmation before processing starts.
        $activeAgreement = $screeningRequest->customer?->agreements
            ->filter(fn ($a) => $a->expiry_date->isFuture())
            ->sortByDesc('expiry_date')
            ->first();
        $isCashBilled = $activeAgreement?->isPerRequest() ?? false;
        $awaitingPayment = $isCashBilled && $screeningRequest->status === 'new';
        $paymentAmount = $awaitingPayment ? $screeningRequest->calculateTotal() : null;

        return view('requests.show', [
            'request'         => $screeningRequest,
            'candidateStats'  => $candidateStats,
            'versions'        => $versions,
            'currentHash'     => $currentHash,
            'reportFreshness' => $reportFreshness,
            'activeAgreement' => $activeAgreement,
            'isCashBilled'    => $isCashBilled,
            'awaitingPayment' => $awaitingPayment,
            'paymentAmount'   => $paymentAmount,
        ]);
    }

    /**
     * Confirm bank transfer received for a cash-billed (per-request) customer:
     *   1. record a transactions row referencing this request
     *   2. flip request status from 'new' to 'in_progress' so the TAT clock starts
     *   3. write an audit log entry
     *
     * Idempotent: only callable while status='new' AND the customer's active
     * agreement is per_request. Wraps everything in a DB transaction.
     */
    public function confirmPayment(Request $request, ScreeningRequest $screeningRequest)
    {
        $screeningRequest->load('customer.agreements');

        $agreement = $screeningRequest->customer?->agreements
            ->filter(fn ($a) => $a->expiry_date->isFuture())
            ->sortByDesc('expiry_date')
            ->first();

        if (! $agreement || ! $agreement->isPerRequest()) {
            return $this->saveResponse($request, 'Customer is not on cash billing — no payment confirmation needed.', [], 422);
        }

        if ($screeningRequest->status !== 'new') {
            return $this->saveResponse($request, 'Payment was already confirmed for this request.', [], 422);
        }

        $amount = $screeningRequest->calculateTotal();

        DB::transaction(function () use ($screeningRequest, $amount) {
            Transaction::create([
                'customer_id' => $screeningRequest->customer_id,
                'type'        => 'payment',
                'amount'      => $amount,
                'reference'   => $screeningRequest->reference,
                'status'      => 'completed',
                'method'      => 'Bank Transfer',
                'notes'       => "Cash payment for screening request {$screeningRequest->reference}.",
            ]);

            $screeningRequest->update(['status' => 'in_progress']);
        });

        AdminAuditLog::record('payment.confirmed', null, [
            'request_id' => $screeningRequest->id,
            'reference'  => $screeningRequest->reference,
            'customer'   => $screeningRequest->customer?->name,
            'amount'     => $amount,
            'method'     => 'Bank Transfer',
        ]);

        return $this->saveResponse($request, 'Payment confirmed — request is now in progress.', [
            'status' => $screeningRequest->status,
            'amount' => $amount,
        ]);
    }

    public function updateStatus(Request $request, ScreeningRequest $screeningRequest)
    {
        $statuses = implode(',', ScreeningRequest::STATUSES);
        $data = $request->validate([
            'status'           => "required|in:{$statuses}",
            'rejection_reason' => 'nullable|string|max:2000|required_if:status,rejected',
        ], [
            'rejection_reason.required_if' => 'A reason is required when rejecting a request.',
        ]);

        $update = ['status' => $data['status']];

        // Capture / clear rejection reason in lock-step with the status flip,
        // so the client portal never shows a stale reason on a non-rejected row.
        if ($data['status'] === 'rejected') {
            $update['rejection_reason'] = $data['rejection_reason'];
        } elseif ($screeningRequest->rejection_reason !== null) {
            $update['rejection_reason'] = null;
        }

        $screeningRequest->update($update);

        return $this->saveResponse($request, 'Request status updated.', [
            'status'           => $screeningRequest->status,
            'rejection_reason' => $screeningRequest->rejection_reason,
        ]);
    }

    public function updateCandidateStatus(Request $request, ScreeningRequest $screeningRequest, int $candidateId)
    {
        // Candidate-level status uses the original four — prelim/updated/rejected
        // are request-level workflow concepts, not per-candidate.
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
    private function saveResponse(Request $request, string $message, array $payload = [], int $status = 200)
    {
        $ok = $status < 400;
        if ($request->expectsJson()) {
            return response()->json(array_merge(['ok' => $ok, 'message' => $message], $payload), $status);
        }
        return back()->with($ok ? 'success' : 'error', $message);
    }
}
