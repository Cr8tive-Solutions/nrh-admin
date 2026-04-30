<?php

namespace App\Http\Controllers\Compliance;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\DataSubjectRequest;
use App\Models\RequestCandidate;
use App\Services\RedactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DataSubjectRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = DataSubjectRequest::with('candidate.screeningRequest', 'handler')
            ->orderBy('received_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('reference', 'ilike', "%{$s}%")
                  ->orWhere('subject_name', 'ilike', "%{$s}%")
                  ->orWhere('subject_email', 'ilike', "%{$s}%");
            });
        }

        $requests = $query->paginate(25)->withQueryString();

        $stats = [
            'received'  => DataSubjectRequest::where('status', 'received')->count(),
            'verifying' => DataSubjectRequest::where('status', 'verifying_identity')->count(),
            'in_progress' => DataSubjectRequest::where('status', 'in_progress')->count(),
            'overdue'   => DataSubjectRequest::whereIn('status', ['received', 'verifying_identity', 'in_progress'])
                            ->where('due_at', '<', now())->count(),
        ];

        return view('compliance.dsar.index', compact('requests', 'stats'));
    }

    public function create()
    {
        return view('compliance.dsar.create', [
            'types'     => DataSubjectRequest::types(),
            'relations' => DataSubjectRequest::relations(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject_name'            => 'required|string|max:255',
            'subject_email'           => 'nullable|email|max:255',
            'subject_identity_number' => 'nullable|string|max:60',
            'relation'                => 'required|in:'.implode(',', array_keys(DataSubjectRequest::relations())),
            'type'                    => 'required|in:'.implode(',', array_keys(DataSubjectRequest::types())),
            'received_via'            => 'required|in:email,post,phone,in_person',
            'received_at'             => 'required|date|before_or_equal:now',
            'description'             => 'required|string|max:4000',
            'evidence_file'           => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'request_candidate_id'    => 'nullable|integer|exists:request_candidates,id',
        ]);

        $filePath = null;
        if ($request->hasFile('evidence_file')) {
            $filePath = $request->file('evidence_file')->store('dsar', 'local');
        }

        $dsar = DataSubjectRequest::create([
            'reference'              => DataSubjectRequest::nextReference(),
            'subject_name'           => $data['subject_name'],
            'subject_email'          => $data['subject_email'] ?? null,
            'subject_identity_number'=> $data['subject_identity_number'] ?? null,
            'relation'               => $data['relation'],
            'type'                   => $data['type'],
            'received_via'           => $data['received_via'],
            'received_at'            => $data['received_at'],
            // PDPA-aligned: respond within 21 working days
            'due_at'                 => \Carbon\Carbon::parse($data['received_at'])->addWeekdays(21),
            'description'            => $data['description'],
            'evidence_file_path'     => $filePath,
            'request_candidate_id'   => $data['request_candidate_id'] ?? null,
            'status'                 => 'received',
            'handled_by_admin_id'    => current_admin()?->id,
        ]);

        return redirect()->route('compliance.dsar.show', $dsar)
            ->with('success', "Data subject request {$dsar->reference} logged.");
    }

    public function show(DataSubjectRequest $dsar)
    {
        $dsar->load('candidate.screeningRequest.customer', 'handler');

        // Find candidates that may match this subject (by name / IC) for linking
        $matchedCandidates = collect();
        if ($dsar->subject_name && ! $dsar->candidate) {
            $matchedCandidates = RequestCandidate::with('screeningRequest.customer')
                ->whereNull('redacted_at')
                ->where(function ($q) use ($dsar) {
                    $q->where('name', 'ilike', "%{$dsar->subject_name}%");
                    if ($dsar->subject_identity_number) {
                        $q->orWhere('identity_number', $dsar->subject_identity_number);
                    }
                })
                ->limit(20)
                ->get();
        }

        return view('compliance.dsar.show', compact('dsar', 'matchedCandidates'));
    }

    /**
     * Mark identity as verified (admin manually checked the requestor's IC etc.)
     */
    public function verify(DataSubjectRequest $dsar)
    {
        if ($dsar->status !== 'received') {
            return back()->with('error', 'Can only verify a request in "received" status.');
        }

        $dsar->update([
            'status'      => 'verifying_identity',
            'verified_at' => null,
        ]);
        // Followed by confirmIdentity below. Two-step so the admin can pause.

        return back()->with('success', 'Moved to identity verification.');
    }

    public function confirmIdentity(DataSubjectRequest $dsar)
    {
        $dsar->update([
            'status'      => 'in_progress',
            'verified_at' => now(),
        ]);

        return back()->with('success', 'Identity confirmed; request is in progress.');
    }

    public function linkCandidate(Request $request, DataSubjectRequest $dsar)
    {
        $data = $request->validate([
            'request_candidate_id' => 'required|integer|exists:request_candidates,id',
        ]);
        $dsar->update(['request_candidate_id' => $data['request_candidate_id']]);

        return back()->with('success', 'Candidate linked.');
    }

    public function complete(Request $request, DataSubjectRequest $dsar)
    {
        $data = $request->validate([
            'outcome' => 'required|string|max:4000',
        ]);

        if (! in_array($dsar->status, ['in_progress', 'verifying_identity'], true)) {
            return back()->with('error', 'Can only complete an in-progress request.');
        }

        $dsar->update([
            'status'       => 'completed',
            'outcome'      => $data['outcome'],
            'completed_at' => now(),
        ]);

        AdminAuditLog::record('pdpa.dsar_completed', null, [
            'dsar_id'  => $dsar->id,
            'reference'=> $dsar->reference,
            'type'     => $dsar->type,
        ]);

        return back()->with('success', "{$dsar->reference} completed.");
    }

    public function reject(Request $request, DataSubjectRequest $dsar)
    {
        $data = $request->validate([
            'outcome' => 'required|string|max:4000',
        ]);

        $dsar->update([
            'status'       => 'rejected',
            'outcome'      => $data['outcome'],
            'completed_at' => now(),
        ]);

        AdminAuditLog::record('pdpa.dsar_rejected', null, [
            'dsar_id'   => $dsar->id,
            'reference' => $dsar->reference,
            'reason'    => $data['outcome'],
        ]);

        return back()->with('success', "{$dsar->reference} rejected with reason.");
    }

    /**
     * Execute the erasure for an erasure-type DSAR after explicit confirmation.
     * Calls RedactionService to redact the linked candidate.
     */
    public function executeErasure(Request $request, DataSubjectRequest $dsar)
    {
        $request->validate([
            'confirm' => 'required|in:I understand this is irreversible',
        ]);

        if ($dsar->type !== 'erasure') {
            throw ValidationException::withMessages(['type' => 'Only erasure-type requests can trigger redaction.']);
        }

        if (! $dsar->candidate) {
            throw ValidationException::withMessages(['candidate' => 'Link a candidate to this request first.']);
        }

        if ($dsar->status !== 'in_progress') {
            throw ValidationException::withMessages(['status' => 'Identity must be verified (status: in_progress) before redacting.']);
        }

        RedactionService::redactCandidate($dsar->candidate, "erasure_request_{$dsar->reference}");

        $dsar->update([
            'status'       => 'completed',
            'outcome'      => "Candidate PII redacted under erasure request {$dsar->reference} on ".now()->format('d M Y, H:i').". Issued report PDFs are immutable and were not modified.",
            'completed_at' => now(),
        ]);

        AdminAuditLog::record('pdpa.erasure_executed', null, [
            'dsar_id'      => $dsar->id,
            'reference'    => $dsar->reference,
            'candidate_id' => $dsar->candidate->id,
        ]);

        return redirect()->route('compliance.dsar.show', $dsar)
            ->with('success', "Erasure executed. Candidate PII has been redacted.");
    }

    public function downloadEvidence(DataSubjectRequest $dsar)
    {
        if (! $dsar->evidence_file_path || ! Storage::disk('local')->exists($dsar->evidence_file_path)) {
            abort(404);
        }
        return Storage::disk('local')->download(
            $dsar->evidence_file_path,
            "{$dsar->reference}-evidence.".pathinfo($dsar->evidence_file_path, PATHINFO_EXTENSION)
        );
    }
}
