<?php

use App\Models\ReportVersion;
use App\Models\ScreeningRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Support\Fixtures;

beforeEach(function () {
    Storage::fake('local');

    Fixtures::seedPermissions();
    $this->admin = Fixtures::admin(['role' => 'super_admin']);

    $this->customerId = Fixtures::customer();
    $this->userId = Fixtures::customerUser($this->customerId);
    Fixtures::agreement($this->customerId, 'monthly');

    $countryId = Fixtures::country();
    $identityTypeId = Fixtures::identityType();
    $this->scopeTypeId = Fixtures::scopeType($countryId);

    $this->requestId = Fixtures::screeningRequest($this->customerId, $this->userId, ['status' => 'in_progress']);
    $this->candidateId = Fixtures::candidate($this->requestId, $identityTypeId);
    Fixtures::attachScope($this->candidateId, $this->scopeTypeId, ['status' => 'complete', 'completed_at' => now()]);

    $this->req = ScreeningRequest::find($this->requestId);
    $this->as = fn () => Fixtures::actingAs($this, $this->admin);
});

it('generates a full report as version 1 and flips status to complete', function () {
    ($this->as)()->post(route('requests.report.generate', $this->req), ['type' => 'full'])
        ->assertRedirect();

    $version = ReportVersion::where('screening_request_id', $this->requestId)->first();

    expect($version)->not->toBeNull()
        ->and($version->type)->toBe('full')
        ->and($version->version)->toBe(1)
        ->and($version->content_hash)->not->toBeEmpty()
        ->and($version->file_sha256)->not->toBeEmpty()
        ->and($version->generated_by_admin_id)->toBe($this->admin->id);

    // A full report moves the request forward.
    expect(ScreeningRequest::find($this->requestId)->status)->toBe('complete');

    Storage::disk('local')->assertExists($version->file_path);
});

/**
 * BUG (documented, not endorsed): the "no changes since last issue" guard is
 * defeated by generate()'s own side effect.
 *
 * ReportSnapshot::build() includes `request.status` in the hashed snapshot, and
 * issuing a `full` report mutates that status (in_progress -> complete ->
 * updated). So each of the first three clicks produces a genuinely different
 * content_hash and slips past the guard:
 *
 *   attempt 1  status in_progress  -> v1 created, status becomes complete
 *   attempt 2  status complete     -> v2 created, status becomes updated
 *   attempt 3  status updated      -> v3 created, status stays updated
 *   attempt 4  status updated      -> BLOCKED (hash finally stable)
 *
 * Business rule #7 in CLAUDE.md says re-issuing with no data change is blocked;
 * in practice an admin double-clicking Generate mints 2 extra immutable
 * "official" report versions. Likely fix: exclude the workflow status (and the
 * auto-filled completion_* meta dates) from the hashed snapshot.
 *
 * This test pins the CURRENT behaviour. When fixed, attempt 2 should be blocked
 * and the version count should stay at 1.
 */
it('lets an unchanged full report be re-issued twice before the guard bites', function () {
    ($this->as)()->post(route('requests.report.generate', $this->req), ['type' => 'full']);

    // Attempts 2 and 3 slip through only because the status flip changed the hash.
    ($this->as)()->post(route('requests.report.generate', $this->req), ['type' => 'full']);
    ($this->as)()->post(route('requests.report.generate', $this->req), ['type' => 'full']);

    expect(ReportVersion::where('screening_request_id', $this->requestId)->count())->toBe(3);

    // Now the status has settled on 'updated', so the hash is finally stable
    // and the guard fires as intended.
    ($this->as)()->post(route('requests.report.generate', $this->req), ['type' => 'full'])
        ->assertSessionHasErrors('type');

    expect(ReportVersion::where('screening_request_id', $this->requestId)->count())->toBe(3);
});

it('blocks an unchanged prelim re-issue immediately', function () {
    // prelim does NOT flip request status, so its hash is stable and the
    // dedup guard behaves as documented.
    ($this->as)()->post(route('requests.report.generate', $this->req), ['type' => 'prelim']);

    ($this->as)()->post(route('requests.report.generate', $this->req), ['type' => 'prelim'])
        ->assertSessionHasErrors('type');

    expect(ReportVersion::where('screening_request_id', $this->requestId)->count())->toBe(1);
});

it('allows a new version once the underlying data changes', function () {
    ($this->as)()->post(route('requests.report.generate', $this->req), ['type' => 'full']);
    $first = ReportVersion::where('screening_request_id', $this->requestId)->first();

    // Mutate a finding so the snapshot hash changes.
    DB::table('candidate_scope_type')
        ->where('request_candidate_id', $this->candidateId)
        ->update(['findings' => json_encode(['result_type' => 'clean', 'risk_level' => 'low'])]);

    ($this->as)()->post(route('requests.report.generate', $this->req), ['type' => 'full']);

    $versions = ReportVersion::where('screening_request_id', $this->requestId)
        ->orderBy('version')->get();

    expect($versions)->toHaveCount(2)
        ->and($versions[1]->version)->toBe(2)
        ->and($versions[1]->content_hash)->not->toBe($first->content_hash);
});

it('does not move the request status for a prelim report', function () {
    ($this->as)()->post(route('requests.report.generate', $this->req), ['type' => 'prelim']);

    expect(ReportVersion::where('screening_request_id', $this->requestId)->first()->type)->toBe('prelim')
        // Only 'full' advances the workflow — prelim/basic are bookkeeping artefacts.
        ->and(ScreeningRequest::find($this->requestId)->status)->toBe('in_progress');
});

it('flips complete to updated when a full report is re-issued', function () {
    ($this->as)()->post(route('requests.report.generate', $this->req), ['type' => 'full']);
    expect(ScreeningRequest::find($this->requestId)->status)->toBe('complete');

    DB::table('candidate_scope_type')
        ->where('request_candidate_id', $this->candidateId)
        ->update(['findings' => json_encode(['result_type' => 'clean'])]);

    ($this->as)()->post(route('requests.report.generate', $this->req), ['type' => 'full']);
    expect(ScreeningRequest::find($this->requestId)->status)->toBe('updated');
});

it('numbers prelim and full versions independently', function () {
    ($this->as)()->post(route('requests.report.generate', $this->req), ['type' => 'prelim']);
    ($this->as)()->post(route('requests.report.generate', $this->req), ['type' => 'full']);

    $byType = ReportVersion::where('screening_request_id', $this->requestId)->get()->keyBy('type');

    expect($byType['prelim']->version)->toBe(1)
        ->and($byType['full']->version)->toBe(1);
});

it('serves a live preview without persisting a version', function () {
    ($this->as)()->get(route('requests.report.preview', $this->req))->assertOk();

    expect(ReportVersion::where('screening_request_id', $this->requestId)->count())->toBe(0);
});

it('lets a viewer preview but not generate', function () {
    $viewer = Fixtures::admin(['role' => 'viewer']);

    Fixtures::actingAs($this, $viewer)->get(route('requests.report.preview', $this->req))->assertOk();
    Fixtures::actingAs($this, $viewer)
        ->post(route('requests.report.generate', $this->req), ['type' => 'full'])
        ->assertForbidden();

    expect(ReportVersion::where('screening_request_id', $this->requestId)->count())->toBe(0);
});

it('re-downloads the exact stored bytes of a version', function () {
    ($this->as)()->post(route('requests.report.generate', $this->req), ['type' => 'full']);
    $version = ReportVersion::where('screening_request_id', $this->requestId)->firstOrFail();

    $response = ($this->as)()->get(route('requests.report.download', [$this->req, $version]));
    $response->assertOk()->assertHeader('Content-Type', 'application/pdf');

    // download() returns the raw bytes, not a streamed response.
    expect(hash('sha256', $response->getContent()))->toBe($version->file_sha256);
});
