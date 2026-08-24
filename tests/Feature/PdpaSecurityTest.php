<?php

use App\Models\AdminAuditLog;
use App\Models\RequestCandidate;
use App\Services\RedactionService;
use App\Support\Pii;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Support\Fixtures;

beforeEach(function () {
    Fixtures::seedPermissions();
    $this->customerId = Fixtures::customer();
    $this->userId = Fixtures::customerUser($this->customerId);
    $this->requestId = Fixtures::screeningRequest($this->customerId, $this->userId);
    $this->identityTypeId = Fixtures::identityType();
    Pii::flush();
});

afterEach(function () {
    config(['pii.key' => '']);
    Pii::flush();
});

function enablePii(): void
{
    config(['pii.key' => 'base64:'.base64_encode(random_bytes(32))]);
    Pii::flush();
}

// ── Item 1: redaction deletes uploaded PII files ─────────────────────────────

it('deletes candidate documents and consent evidence files on redaction', function () {
    Storage::fake('client_local');
    Storage::fake('local');

    $candidateId = Fixtures::candidate($this->requestId, $this->identityTypeId);

    $docPath = "candidate-documents/{$this->customerId}/REQ/candidate-{$candidateId}/nric.jpg";
    Storage::disk('client_local')->put($docPath, 'fake-mykad-scan');
    DB::table('candidate_documents')->insert([
        'request_candidate_id' => $candidateId,
        'screening_request_id' => $this->requestId,
        'type' => 'nric',
        'file_path' => $docPath,
        'original_name' => 'mykad.jpg',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $consentPath = "consent/{$candidateId}/signed.pdf";
    Storage::disk('local')->put($consentPath, 'fake-signed-consent');
    DB::table('consent_records')->insert([
        'request_candidate_id' => $candidateId,
        'consented_at' => now(),
        'consent_version' => 'v1-2026-04',
        'consent_text_snapshot' => 'I consent…',
        'evidence_type' => 'paper_signed',
        'evidence_file_path' => $consentPath,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    RedactionService::redactCandidate(RequestCandidate::find($candidateId), 'retention_expiry');

    // Files gone from disk
    Storage::disk('client_local')->assertMissing($docPath);
    Storage::disk('local')->assertMissing($consentPath);

    // Document rows deleted; consent row kept but evidence path cleared
    expect(DB::table('candidate_documents')->where('request_candidate_id', $candidateId)->count())->toBe(0);
    $consent = DB::table('consent_records')->where('request_candidate_id', $candidateId)->first();
    expect($consent)->not->toBeNull()
        ->and($consent->evidence_file_path)->toBeNull();

    // Candidate masked
    $c = RequestCandidate::find($candidateId);
    expect($c->name)->toBe(RedactionService::MARKER_NAME)
        ->and($c->isRedacted())->toBeTrue()
        ->and($c->identity_number_hash)->toBeNull();

    // Audit records the deletion
    $log = AdminAuditLog::where('action', 'pdpa.candidate_redacted')->latest('id')->first();
    expect($log)->not->toBeNull()
        ->and($log->details['files_deleted'])->toContain($docPath)
        ->and($log->details['files_deleted'])->toContain($consentPath);
});

it('is idempotent and safe when a candidate has no files', function () {
    $candidateId = Fixtures::candidate($this->requestId, $this->identityTypeId);
    $c = RequestCandidate::find($candidateId);

    RedactionService::redactCandidate($c, 'retention_expiry');
    RedactionService::redactCandidate($c->fresh(), 'retention_expiry'); // second call no-op

    expect(RequestCandidate::find($candidateId)->name)->toBe(RedactionService::MARKER_NAME)
        ->and(AdminAuditLog::where('action', 'pdpa.candidate_redacted')->count())->toBe(1);
});

// ── Item 2: identity_number encryption at rest ───────────────────────────────

it('stores identity_number as ciphertext and reads it back as plaintext', function () {
    enablePii();

    $candidate = RequestCandidate::create([
        'screening_request_id' => $this->requestId,
        'identity_type_id' => $this->identityTypeId,
        'name' => 'Ali bin Abu',
        'identity_number' => '880101-14-5678',
        'status' => 'new',
    ]);

    $rawStored = DB::table('request_candidates')->where('id', $candidate->id)->value('identity_number');
    expect($rawStored)->not->toBe('880101-14-5678')                  // encrypted on disk
        ->and(strlen($rawStored))->toBeGreaterThan(80);

    expect(RequestCandidate::find($candidate->id)->identity_number)->toBe('880101-14-5678'); // decrypts on read
});

it('populates a blind index that supports exact and normalised lookups', function () {
    enablePii();

    $candidate = RequestCandidate::create([
        'screening_request_id' => $this->requestId,
        'identity_type_id' => $this->identityTypeId,
        'name' => 'Ali bin Abu',
        'identity_number' => '880101-14-5678',
        'status' => 'new',
    ]);

    expect($candidate->identity_number_hash)->toBe(Pii::hash('880101-14-5678'))
        ->and($candidate->identity_number_hash)->not->toBeNull();

    expect(RequestCandidate::whereIdentityNumber('880101-14-5678')->pluck('id'))->toContain($candidate->id)
        ->and(RequestCandidate::whereIdentityNumber('88010114 5678')->pluck('id'))->toContain($candidate->id) // normalised
        ->and(RequestCandidate::whereIdentityNumber('000000-00-0000')->count())->toBe(0);
});

it('transparently reads legacy plaintext rows written before encryption', function () {
    enablePii();

    // Simulate a pre-encryption row: plaintext straight into the column.
    $id = DB::table('request_candidates')->insertGetId([
        'screening_request_id' => $this->requestId,
        'identity_type_id' => $this->identityTypeId,
        'name' => 'Legacy',
        'identity_number' => '901231-10-1234',
        'status' => 'new',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    expect(RequestCandidate::find($id)->identity_number)->toBe('901231-10-1234'); // passthrough, no crash
});

it('backfills plaintext rows into ciphertext + index', function () {
    // Write plaintext with encryption OFF, then enable and backfill.
    $candidate = RequestCandidate::create([
        'screening_request_id' => $this->requestId,
        'identity_type_id' => $this->identityTypeId,
        'name' => 'Ali bin Abu',
        'identity_number' => '880101-14-5678',
        'status' => 'new',
    ]);
    expect(DB::table('request_candidates')->where('id', $candidate->id)->value('identity_number'))->toBe('880101-14-5678');

    enablePii();
    $this->artisan('pii:backfill-identity')->assertSuccessful();

    $raw = DB::table('request_candidates')->where('id', $candidate->id)->value('identity_number');
    expect($raw)->not->toBe('880101-14-5678') // now encrypted
        ->and(RequestCandidate::find($candidate->id)->identity_number)->toBe('880101-14-5678')
        ->and(RequestCandidate::find($candidate->id)->identity_number_hash)->toBe(Pii::hash('880101-14-5678'));
});

it('stores plaintext and skips the index when encryption is disabled', function () {
    $candidate = RequestCandidate::create([
        'screening_request_id' => $this->requestId,
        'identity_type_id' => $this->identityTypeId,
        'name' => 'Ali bin Abu',
        'identity_number' => '880101-14-5678',
        'status' => 'new',
    ]);

    expect(DB::table('request_candidates')->where('id', $candidate->id)->value('identity_number'))->toBe('880101-14-5678')
        ->and($candidate->identity_number_hash)->toBeNull();
});
