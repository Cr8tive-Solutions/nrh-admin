<?php

namespace App\Http\Controllers\Compliance;

use App\Http\Controllers\Controller;
use App\Models\ConsentRecord;
use App\Models\RequestCandidate;
use App\Models\ScreeningRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ConsentController extends Controller
{
    /** Standard consent text version — bump when the wording changes. */
    public const CURRENT_VERSION = 'v1-2026-04';

    public const STANDARD_TEXT = <<<TXT
I, the data subject, hereby give my informed consent to NRH Intelligence Sdn. Bhd. and its
authorised processors to collect, process, verify, and disclose to the requesting client
(employer / counter-party / regulator-of-record) my personal data, including identification
documents, employment history, education records, financial standing, and any criminal,
sanctions, or adverse media records relevant to the screening services engaged.

I understand:
1. The purpose is pre-employment / due-diligence background screening.
2. NRH Intelligence will retain my data in accordance with its retention policy and the
   Personal Data Protection Act 2010 (Malaysia).
3. I may withdraw consent or request access, rectification, or erasure of my data at any
   time, subject to the lawful retention obligations of the data controller.
4. Refusing or withdrawing consent may affect the screening outcome relied on by the
   requesting client.
TXT;

    public function store(Request $request, ScreeningRequest $screeningRequest, int $candidateId)
    {
        $candidate = $screeningRequest->candidates()->findOrFail($candidateId);

        $data = $request->validate([
            'consented_at'        => 'required|date|before_or_equal:now',
            'evidence_type'       => 'required|in:digital_form,paper_signed,email,verbal_recorded',
            'evidence_file'       => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'consent_version'     => 'nullable|string|max:40',
            'notes'               => 'nullable|string|max:1000',
        ]);

        $filePath = null;
        if ($request->hasFile('evidence_file')) {
            $filePath = $request->file('evidence_file')->store(
                "consent/{$candidate->id}",
                'local'
            );
        }

        ConsentRecord::create([
            'request_candidate_id'  => $candidate->id,
            'consented_at'          => $data['consented_at'],
            'consent_version'       => $data['consent_version'] ?: self::CURRENT_VERSION,
            'consent_text_snapshot' => self::STANDARD_TEXT,
            'evidence_type'         => $data['evidence_type'],
            'evidence_file_path'    => $filePath,
            'captured_ip'           => $request->ip(),
            'captured_user_agent'   => $request->userAgent(),
            'captured_by_admin_id'  => current_admin()?->id,
            'notes'                 => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'Consent record saved.');
    }

    /** Stream a stored consent evidence file privately to authorised admins. */
    public function downloadEvidence(ConsentRecord $consent)
    {
        if (! $consent->evidence_file_path || ! Storage::disk('local')->exists($consent->evidence_file_path)) {
            abort(404);
        }

        return Storage::disk('local')->download(
            $consent->evidence_file_path,
            "consent-{$consent->id}.".pathinfo($consent->evidence_file_path, PATHINFO_EXTENSION)
        );
    }
}
