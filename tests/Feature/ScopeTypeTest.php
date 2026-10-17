<?php

use App\Models\ScopeType;
use Illuminate\Support\Facades\DB;
use Tests\Support\Fixtures;

it('creates a scope when the display turnaround is left blank, deriving it from SLA hours', function () {
    $admin = Fixtures::admin();
    $countryId = Fixtures::country();

    Fixtures::actingAs($this, $admin)
        ->post(route('config.scopes.store'), [
            'country_id' => $countryId,
            'name' => 'All Employment',
            'price' => 0,
            'price_on_request' => '1',
            'turnaround_hours' => 18, // 2 working days (9h/day)
            // turnaround (display) intentionally omitted
        ])
        ->assertRedirect(route('config.scopes.index'))
        ->assertSessionHas('success');

    $scope = ScopeType::where('name', 'All Employment')->firstOrFail();
    // The NOT NULL display column is populated, never null.
    expect($scope->turnaround)->toBe('2 business days');
    expect($scope->turnaround_hours)->toBe(18);
});

it('falls back to "By arrangement" when neither turnaround field is given', function () {
    $admin = Fixtures::admin();
    $countryId = Fixtures::country();

    Fixtures::actingAs($this, $admin)
        ->post(route('config.scopes.store'), [
            'country_id' => $countryId,
            'name' => 'Bespoke Check',
            'price' => 0,
            'price_on_request' => '1',
        ])
        ->assertRedirect(route('config.scopes.index'));

    expect(ScopeType::where('name', 'Bespoke Check')->value('turnaround'))->toBe('By arrangement');
});

it('keeps the staff-entered display turnaround verbatim', function () {
    $admin = Fixtures::admin();
    $countryId = Fixtures::country();

    Fixtures::actingAs($this, $admin)
        ->post(route('config.scopes.store'), [
            'country_id' => $countryId,
            'name' => 'Credit Check',
            'price' => 120,
            'turnaround' => '3 business days',
            'turnaround_hours' => 24,
        ])
        ->assertRedirect(route('config.scopes.index'));

    expect(ScopeType::where('name', 'Credit Check')->value('turnaround'))->toBe('3 business days');
});

it('renders hours (not days) when the SLA is not a whole number of working days', function () {
    $admin = Fixtures::admin();
    $countryId = Fixtures::country();

    Fixtures::actingAs($this, $admin)
        ->post(route('config.scopes.store'), [
            'country_id' => $countryId,
            'name' => 'Rush Check',
            'price' => 50,
            'turnaround_hours' => 4,
        ])
        ->assertRedirect(route('config.scopes.index'));

    expect(ScopeType::where('name', 'Rush Check')->value('turnaround'))->toBe('4 business hours');
});
