<?php

use App\Models\AdminAuditLog;
use App\Models\ScreeningRequest;
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

function seedDocument($customerId, $requestId, $candidateId): int
{
    $path = "candidate-documents/{$customerId}/REQ/candidate-{$candidateId}/nric.jpg";
    Storage::disk('client_local')->put($path, 'fake-mykad');

    return DB::table('candidate_documents')->insertGetId([
        'request_candidate_id' => $candidateId,
        'screening_request_id' => $requestId,
        'type' => 'nric',
        'file_path' => $path,
        'original_name' => 'mykad.jpg',
        'created_at' => now(), 'updated_at' => now(),
    ]);
}

function documentUrl($requestId, $candidateId, $documentId): string
{
    return route('requests.candidates.documents.download', [
        ScreeningRequest::find($requestId), $candidateId, $documentId,
    ]);
}

// ── Deactivated admin loses access immediately ───────────────────────────────

it('redirects a deactivated admin to login on the next request', function () {
    $admin = Fixtures::admin(['role' => 'operations', 'status' => 'inactive']);

    Fixtures::actingAs($this, $admin)
        ->get('/dashboard')
        ->assertRedirect(route('login'));
});

it('lets an active admin through', function () {
    $admin = Fixtures::admin(['role' => 'operations', 'status' => 'active']);

    Fixtures::actingAs($this, $admin)->get('/dashboard')->assertOk();
});

// ── Candidate document downloads are permission-gated ────────────────────────

it('forbids a viewer from downloading a candidate identity document', function () {
    Storage::fake('client_local');
    $docId = seedDocument($this->customerId, $this->requestId, $this->candidateId);
    $viewer = Fixtures::admin(['role' => 'viewer']);

    Fixtures::actingAs($this, $viewer)
        ->get(documentUrl($this->requestId, $this->candidateId, $docId))
        ->assertForbidden();
});

it('forbids finance from downloading a candidate identity document', function () {
    Storage::fake('client_local');
    $docId = seedDocument($this->customerId, $this->requestId, $this->candidateId);
    $finance = Fixtures::admin(['role' => 'finance']);

    Fixtures::actingAs($this, $finance)
        ->get(documentUrl($this->requestId, $this->candidateId, $docId))
        ->assertForbidden();
});

it('lets operations download a candidate identity document and audit-logs it', function () {
    Storage::fake('client_local');
    $docId = seedDocument($this->customerId, $this->requestId, $this->candidateId);
    $ops = Fixtures::admin(['role' => 'operations']);

    Fixtures::actingAs($this, $ops)
        ->get(documentUrl($this->requestId, $this->candidateId, $docId))
        ->assertOk();

    $log = AdminAuditLog::where('action', 'pdpa.document_downloaded')->latest('id')->first();
    expect($log)->not->toBeNull()
        ->and($log->details['document_id'])->toBe($docId)
        ->and($log->details['candidate_id'])->toBe($this->candidateId)
        ->and($log->actor_admin_id)->toBe($ops->id);
});

it('lets super_admin download regardless of explicit grants', function () {
    Storage::fake('client_local');
    $docId = seedDocument($this->customerId, $this->requestId, $this->candidateId);
    $super = Fixtures::admin(['role' => 'super_admin']);

    Fixtures::actingAs($this, $super)
        ->get(documentUrl($this->requestId, $this->candidateId, $docId))
        ->assertOk();
});
