<?php

use App\Models\ScreeningRequest;
use Illuminate\Support\Facades\DB;
use Tests\Support\Fixtures;

beforeEach(function () {
    Fixtures::seedPermissions();
    $this->admin = Fixtures::admin(['role' => 'super_admin']);

    $this->customerId = Fixtures::customer();
    $this->userId = Fixtures::customerUser($this->customerId);
    Fixtures::agreement($this->customerId, 'monthly');

    $this->countryId = Fixtures::country();
    $this->identityTypeId = Fixtures::identityType();
    $this->scopeTypeId = Fixtures::scopeType($this->countryId);

    $this->requestId = Fixtures::screeningRequest($this->customerId, $this->userId);
    $this->candidateId = Fixtures::candidate($this->requestId, $this->identityTypeId);
    Fixtures::attachScope($this->candidateId, $this->scopeTypeId);

    $this->req = ScreeningRequest::find($this->requestId);
    $this->as = fn () => Fixtures::actingAs($this, $this->admin);
});

it('accepts every canonical status', function () {
    foreach (ScreeningRequest::STATUSES as $status) {
        $payload = ['status' => $status];
        if ($status === 'rejected') {
            $payload['rejection_reason'] = 'Incomplete documents.';
        }

        ($this->as)()
            ->patchJson(route('requests.status', $this->req), $payload)
            ->assertOk();

        expect(ScreeningRequest::find($this->requestId)->status)->toBe($status);
    }
});

it('rejects a status outside the canonical set', function () {
    // 'prelim' and 'flagged' are NOT request statuses — they belong to
    // ReportVersion.type and candidate/scope status respectively.
    foreach (['prelim', 'flagged', 'bogus'] as $status) {
        ($this->as)()
            ->patchJson(route('requests.status', $this->req), ['status' => $status])
            ->assertStatus(422);
    }

    expect(ScreeningRequest::find($this->requestId)->status)->toBe('new');
});

it('requires a reason when rejecting', function () {
    ($this->as)()
        ->patchJson(route('requests.status', $this->req), ['status' => 'rejected'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('rejection_reason');
});

it('stores then clears the rejection reason as status moves', function () {
    ($this->as)()->patchJson(route('requests.status', $this->req), [
        'status' => 'rejected', 'rejection_reason' => 'Missing consent form.',
    ])->assertOk();

    expect(ScreeningRequest::find($this->requestId)->rejection_reason)->toBe('Missing consent form.');

    // Moving off 'rejected' must clear the reason so the client portal never
    // shows a stale reason on a non-rejected row.
    ($this->as)()->patchJson(route('requests.status', $this->req), ['status' => 'in_progress'])->assertOk();

    expect(ScreeningRequest::find($this->requestId)->rejection_reason)->toBeNull();
});

it('updates candidate status', function () {
    ($this->as)()->patchJson(
        route('requests.candidates.status', [$this->req, $this->candidateId]),
        ['status' => 'flagged']
    )->assertOk();

    expect(DB::table('request_candidates')->find($this->candidateId)->status)->toBe('flagged');
});

it('stamps started_at when a scope leaves new', function () {
    ($this->as)()->patchJson(
        route('requests.scope.status', [$this->req, $this->candidateId, $this->scopeTypeId]),
        ['status' => 'in_progress']
    )->assertOk();

    $pivot = DB::table('candidate_scope_type')
        ->where('request_candidate_id', $this->candidateId)->first();

    expect($pivot->status)->toBe('in_progress')
        ->and($pivot->started_at)->not->toBeNull()
        ->and($pivot->completed_at)->toBeNull();
});

it('stamps completed_at on a terminal status and clears it on revert', function () {
    $url = route('requests.scope.status', [$this->req, $this->candidateId, $this->scopeTypeId]);

    ($this->as)()->patchJson($url, ['status' => 'complete'])->assertOk();
    $pivot = DB::table('candidate_scope_type')->where('request_candidate_id', $this->candidateId)->first();
    expect($pivot->completed_at)->not->toBeNull();

    // Reverting to an active status must restart the TAT clock.
    ($this->as)()->patchJson($url, ['status' => 'in_progress'])->assertOk();
    $pivot = DB::table('candidate_scope_type')->where('request_candidate_id', $this->candidateId)->first();
    expect($pivot->completed_at)->toBeNull();
});

it('404s when the scope is not assigned to the candidate', function () {
    $otherScope = Fixtures::scopeType($this->countryId);

    ($this->as)()->patchJson(
        route('requests.scope.status', [$this->req, $this->candidateId, $otherScope]),
        ['status' => 'complete']
    )->assertNotFound();
});

it('saves structured scope findings as json', function () {
    ($this->as)()->patchJson(
        route('requests.scope.findings', [$this->req, $this->candidateId, $this->scopeTypeId]),
        [
            'result_type' => 'record_identified',
            'risk_level' => 'high',
            'risk_status_text' => 'High Risk - conviction recorded.',
            'records_json' => json_encode([[
                'title' => 'AKTA DADAH BERBAHAYA 1952',
                'act' => 'Dangerous Drugs Act 1952',
                'verdict' => 'CONVICTED',
                'risk_level' => 'high',
                'fields' => [['key' => 'Place Of Offence', 'value' => 'Kuala Lumpur']],
            ]]),
        ]
    )->assertOk();

    $findings = json_decode(
        DB::table('candidate_scope_type')->where('request_candidate_id', $this->candidateId)->value('findings'),
        true
    );

    expect($findings['result_type'])->toBe('record_identified')
        ->and($findings['risk_level'])->toBe('high')
        ->and($findings['records'])->toHaveCount(1)
        ->and($findings['records'][0]['title'])->toBe('AKTA DADAH BERBAHAYA 1952')
        // [{key,value}] pairs are normalised into a {key: value} map on save.
        ->and($findings['records'][0]['fields'])->toBe(['Place Of Offence' => 'Kuala Lumpur']);
});

it('drops blank records instead of storing them', function () {
    ($this->as)()->patchJson(
        route('requests.scope.findings', [$this->req, $this->candidateId, $this->scopeTypeId]),
        ['result_type' => 'clean', 'records_json' => json_encode([['title' => '   ']])]
    )->assertOk();

    $findings = json_decode(
        DB::table('candidate_scope_type')->where('request_candidate_id', $this->candidateId)->value('findings'),
        true
    );

    expect($findings)->not->toHaveKey('records');
});

it('blocks a viewer from changing status', function () {
    $viewer = Fixtures::admin(['role' => 'viewer']);

    Fixtures::actingAs($this, $viewer)
        ->patchJson(route('requests.status', $this->req), ['status' => 'complete'])
        ->assertForbidden();

    expect(ScreeningRequest::find($this->requestId)->status)->toBe('new');
});
