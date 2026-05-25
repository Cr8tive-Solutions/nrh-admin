<?php

namespace App\Http\Controllers;

use App\Models\AdminAuditLog;
use App\Models\ReportVersion;
use App\Models\ScreeningRequest;
use App\Models\Transaction;
use App\Services\ReportSnapshot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
            'total' => $screeningRequest->candidates->count(),
            'new' => $screeningRequest->candidates->where('status', 'new')->count(),
            'in_progress' => $screeningRequest->candidates->where('status', 'in_progress')->count(),
            'flagged' => $screeningRequest->candidates->where('status', 'flagged')->count(),
            'complete' => $screeningRequest->candidates->where('status', 'complete')->count(),
        ];

        // Report versions, current snapshot hash, and per-type freshness for the Generate panel.
        $versions = ReportVersion::with('generatedBy', 'supersededBy')
            ->where('screening_request_id', $screeningRequest->id)
            ->orderBy('type')
            ->orderByDesc('version')
            ->get();

        $currentSnapshot = ReportSnapshot::build($screeningRequest);
        $currentHash = ReportSnapshot::hash($currentSnapshot);

        $latestPerType = $versions->groupBy('type')->map(fn ($g) => $g->sortByDesc('version')->first());
        $reportFreshness = collect(ReportVersion::types())->mapWithKeys(function ($type) use ($latestPerType, $currentHash) {
            $latest = $latestPerType->get($type);

            return [$type => [
                'latest' => $latest,
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
            'request' => $screeningRequest,
            'candidateStats' => $candidateStats,
            'versions' => $versions,
            'currentHash' => $currentHash,
            'reportFreshness' => $reportFreshness,
            'activeAgreement' => $activeAgreement,
            'isCashBilled' => $isCashBilled,
            'awaitingPayment' => $awaitingPayment,
            'paymentAmount' => $paymentAmount,
        ]);
    }

    /**
     * Verify a cash-billed (per-request) customer's uploaded payment slip:
     *   1. mark slip verified (payment_verified_at + payment_verified_by)
     *   2. record a transactions row referencing this request
     *   3. flip request status from 'new' to 'in_progress' so the TAT clock starts
     *   4. write an audit log entry
     *
     * Requires a slip to be present — admins should not unblock cash-billed
     * requests without a verifiable receipt on file. Idempotent: 422s if the
     * request is past 'new' or already verified. Wraps everything in a DB
     * transaction so a partial state (e.g. transaction recorded but status
     * not flipped) is impossible.
     */
    public function verifyPaymentSlip(Request $request, ScreeningRequest $screeningRequest)
    {
        $screeningRequest->load('customer.agreements');

        $agreement = $screeningRequest->customer?->agreements
            ->filter(fn ($a) => $a->expiry_date->isFuture())
            ->sortByDesc('expiry_date')
            ->first();

        if (! $agreement || ! $agreement->isPerRequest()) {
            return $this->saveResponse($request, 'Customer is not on cash billing — no payment verification needed.', [], 422);
        }

        if ($screeningRequest->status !== 'new') {
            return $this->saveResponse($request, 'Payment was already verified for this request.', [], 422);
        }

        if (! $screeningRequest->hasPaymentSlip()) {
            return $this->saveResponse($request, 'No payment slip uploaded yet — customer must upload before verification.', [], 422);
        }

        if ($screeningRequest->isPaymentVerified()) {
            return $this->saveResponse($request, 'Payment was already verified for this request.', [], 422);
        }

        $amount = $screeningRequest->calculateTotal();
        $admin = current_admin();

        DB::transaction(function () use ($screeningRequest, $amount, $admin) {
            $screeningRequest->update([
                'payment_verified_at' => now(),
                'payment_verified_by' => $admin?->id,
                'status' => 'in_progress',
            ]);

            Transaction::create([
                'customer_id' => $screeningRequest->customer_id,
                'type' => 'payment',
                'amount' => $amount,
                'reference' => $screeningRequest->reference,
                'status' => 'completed',
                'method' => 'Bank Transfer',
                'notes' => "Cash payment verified via uploaded slip for screening request {$screeningRequest->reference}.",
            ]);
        });

        AdminAuditLog::record('payment.verified', null, [
            'request_id' => $screeningRequest->id,
            'reference' => $screeningRequest->reference,
            'customer' => $screeningRequest->customer?->name,
            'amount' => $amount,
            'method' => 'Bank Transfer',
            'slip_path' => $screeningRequest->payment_slip_path,
        ]);

        return $this->saveResponse($request, 'Payment verified — request is now in progress.', [
            'status' => $screeningRequest->status,
            'amount' => $amount,
        ]);
    }

    /**
     * Stream the customer-uploaded payment slip privately to admins. The slip
     * lives on the customer portal's local disk; production must mount that
     * storage shared between the two apps.
     */
    public function downloadPaymentSlip(ScreeningRequest $screeningRequest)
    {
        if (! $screeningRequest->hasPaymentSlip()) {
            abort(404);
        }

        $disk = Storage::disk('client_local');

        if (! $disk->exists($screeningRequest->payment_slip_path)) {
            abort(404, 'Slip file is no longer available on disk.');
        }

        $extension = pathinfo($screeningRequest->payment_slip_path, PATHINFO_EXTENSION);
        $filename = "payment-slip-{$screeningRequest->reference}.{$extension}";

        return $disk->download($screeningRequest->payment_slip_path, $filename);
    }

    public function updateStatus(Request $request, ScreeningRequest $screeningRequest)
    {
        $statuses = implode(',', ScreeningRequest::STATUSES);
        $data = $request->validate([
            'status' => "required|in:{$statuses}",
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
            'status' => $screeningRequest->status,
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
            'status' => $candidate->status,
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
            'request_id' => $screeningRequest->id,
            'reference' => $screeningRequest->reference,
            'candidate_id' => $candidate->id,
            'candidate' => $candidate->name,
            'scope_type_id' => $scopeTypeId,
            'from' => $previousStatus,
            'to' => $newStatus,
        ]);

        return $this->saveResponse($request, 'Scope status updated.', [
            'candidate_id' => $candidate->id,
            'scope_type_id' => $scopeTypeId,
            'status' => $newStatus,
            'started_at' => $update['started_at'] ?? $pivot->started_at,
            'completed_at' => array_key_exists('completed_at', $update) ? $update['completed_at'] : $pivot->completed_at,
        ]);
    }

    /**
     * Save structured findings for a single scope check on a candidate.
     * Stores result_type, risk_level, narrative fields, and an array of
     * adverse records as JSON. Becomes the body content for that scope
     * in the generated PDF.
     */
    public function updateScopeFindings(Request $request, ScreeningRequest $screeningRequest, int $candidateId, int $scopeTypeId)
    {
        $data = $request->validate([
            'result_type'         => 'nullable|in:clean,record_identified,adverse,not_requested',
            'risk_level'          => 'nullable|in:high,medium,low,nil',
            'risk_status_text'    => 'nullable|string|max:500',
            'implication'         => 'nullable|string|max:500',
            'verification_method' => 'nullable|string|max:4000',
            'scope_description'   => 'nullable|string|max:4000',
            'records_json'        => 'nullable|string|max:65535',
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

        if (! empty($data['result_type'])) {
            $payload['result_type'] = $data['result_type'];
        }
        if (! empty($data['risk_level'])) {
            $payload['risk_level'] = $data['risk_level'];
        }
        foreach (['risk_status_text', 'implication', 'verification_method', 'scope_description'] as $field) {
            $v = trim($data[$field] ?? '');
            if ($v !== '') {
                $payload[$field] = $v;
            }
        }

        // Parse and normalise the records array from JSON.
        // The client sends fields as [{key, value}] pairs; we store them as {key: value}.
        if (! empty($data['records_json'])) {
            $rawRecords = json_decode($data['records_json'], true);
            if (is_array($rawRecords)) {
                $records = [];
                foreach ($rawRecords as $rec) {
                    $title = trim($rec['title'] ?? '');
                    if ($title === '') {
                        continue; // skip blank records
                    }
                    $fieldsMap = [];
                    foreach ($rec['fields'] ?? [] as $field) {
                        $k = trim($field['key'] ?? '');
                        if ($k !== '') {
                            $fieldsMap[$k] = trim($field['value'] ?? '');
                        }
                    }
                    $entry = ['title' => $title];
                    foreach (['act', 'section', 'description', 'penalty', 'verdict', 'risk_text'] as $f) {
                        $v = trim($rec[$f] ?? '');
                        if ($v !== '') {
                            $entry[$f] = $v;
                        }
                    }
                    $entry['risk_level'] = $rec['risk_level'] ?? 'high';
                    if (! empty($fieldsMap)) {
                        $entry['fields'] = $fieldsMap;
                    }
                    $records[] = $entry;
                }
                if (! empty($records)) {
                    $payload['records'] = $records;
                }
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
            'result_type'   => $payload['result_type'] ?? null,
            'risk_level'    => $payload['risk_level'] ?? null,
            'record_count'  => count($payload['records'] ?? []),
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
            'analyst' => 'nullable|string|max:255',
            'editor' => 'nullable|string|max:255',
            'po_number' => 'nullable|string|max:120',
            'completion_basic' => 'nullable|date',
            'completion_prelim' => 'nullable|date',
            'completion_full' => 'nullable|date',
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
