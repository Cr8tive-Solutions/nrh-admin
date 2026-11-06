<?php

use App\Models\AdminAuditLog;
use App\Models\Permission;
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

// ── Request page: identity masking and findings visibility ──────────────────

/** Give the beforeEach candidate a known ID, mobile and a scope with findings. */
function seedSensitiveCandidate($test): void
{
    DB::table('request_candidates')->where('id', $test->candidateId)->update([
        'identity_number' => '900101-01-1234',
        'mobile' => '+60 12-345 6789',
    ]);

    $scopeTypeId = Fixtures::scopeType(Fixtures::country());
    Fixtures::attachScope($test->candidateId, $scopeTypeId, [
        'findings' => json_encode([
            'result_type' => 'record_identified',
            'risk_level' => 'high',
            'risk_status_text' => 'SECRET-FINDING-TEXT',
        ]),
    ]);
}

function requestPage($test, $admin)
{
    return Fixtures::actingAs($test, $admin)->get(route('requests.show', ScreeningRequest::find($test->requestId)));
}

it('masks the identity and mobile numbers for staff without pdpa.documents', function () {
    seedSensitiveCandidate($this);

    foreach (['viewer', 'finance'] as $role) {
        requestPage($this, Fixtures::admin(['role' => $role]))
            ->assertOk()
            ->assertSee('******-**-1234')
            ->assertDontSee('900101-01-1234')
            ->assertSee('+** **-*** 6789')
            ->assertDontSee('+60 12-345 6789');
    }
});

it('shows full identity and mobile numbers to staff with pdpa.documents', function () {
    seedSensitiveCandidate($this);

    requestPage($this, Fixtures::admin(['role' => 'operations']))
        ->assertOk()
        ->assertSee('900101-01-1234')
        ->assertSee('+60 12-345 6789');
});

it('never emits findings to staff without request.update or report.view', function () {
    seedSensitiveCandidate($this);

    foreach (['viewer', 'finance'] as $role) {
        requestPage($this, Fixtures::admin(['role' => $role]))
            ->assertOk()
            ->assertDontSee('SECRET-FINDING-TEXT')
            ->assertDontSee('record_identified');
    }
});

it('shows findings to staff who work requests or may read reports', function () {
    seedSensitiveCandidate($this);

    requestPage($this, Fixtures::admin(['role' => 'operations']))->assertSee('SECRET-FINDING-TEXT');

    $reportReader = Fixtures::admin(['role' => 'viewer']);
    DB::table('admin_user_permissions')->insert([
        'admin_id' => $reportReader->id,
        'admin_permission_id' => Permission::where('key', 'report.view')->value('id'),
        'granted' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    requestPage($this, $reportReader)
        ->assertSee('SECRET-FINDING-TEXT')
        // report.view alone does not unmask the identity number.
        ->assertDontSee('900101-01-1234');
});
