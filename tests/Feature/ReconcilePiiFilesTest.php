<?php

use App\Models\AdminAuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Support\Fixtures;

beforeEach(function () {
    Fixtures::seedPermissions();
    $this->customerId = Fixtures::customer();
    $this->userId = Fixtures::customerUser($this->customerId);
    $this->requestId = Fixtures::screeningRequest($this->customerId, $this->userId);
    $this->identityTypeId = Fixtures::identityType();
    $this->candidateId = Fixtures::candidate($this->requestId, $this->identityTypeId);
});

it('reports orphaned files but leaves referenced ones alone', function () {
    Storage::fake('client_local');
    Storage::fake('local');

    $referenced = "candidate-documents/{$this->customerId}/REQ/candidate-{$this->candidateId}/nric.jpg";
    Storage::disk('client_local')->put($referenced, 'x');
    DB::table('candidate_documents')->insert([
        'request_candidate_id' => $this->candidateId,
        'screening_request_id' => $this->requestId,
        'type' => 'nric', 'file_path' => $referenced, 'original_name' => 'n.jpg',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $orphan = "candidate-documents/{$this->customerId}/REQ/candidate-{$this->candidateId}/orphan.pdf";
    Storage::disk('client_local')->put($orphan, 'left-behind');

    $this->artisan('pii:reconcile-files')->assertSuccessful();

    // Dry run: nothing deleted
    Storage::disk('client_local')->assertExists($orphan);
    Storage::disk('client_local')->assertExists($referenced);
});

it('deletes orphans with --delete and keeps referenced files', function () {
    Storage::fake('client_local');
    Storage::fake('local');

    $referenced = "candidate-documents/{$this->customerId}/keep.jpg";
    Storage::disk('client_local')->put($referenced, 'x');
    DB::table('consent_records')->insert([
        'request_candidate_id' => $this->candidateId,
        'consented_at' => now(), 'consent_version' => 'v1', 'consent_text_snapshot' => 'c',
        'evidence_type' => 'digital_form', 'evidence_file_path' => $referenced,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $orphan = "candidate-documents/{$this->customerId}/gone.pdf";
    Storage::disk('client_local')->put($orphan, 'left-behind');

    $this->artisan('pii:reconcile-files --delete')->assertSuccessful();

    Storage::disk('client_local')->assertMissing($orphan);
    Storage::disk('client_local')->assertExists($referenced); // consent-referenced, kept

    expect(AdminAuditLog::where('action', 'pdpa.orphan_files_deleted')->exists())->toBeTrue();
});
