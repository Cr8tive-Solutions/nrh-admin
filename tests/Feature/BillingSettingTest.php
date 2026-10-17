<?php

use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Tests\Support\Fixtures;

beforeEach(function () {
    Setting::flushMemo(); // per-process memo would otherwise leak across tests.
});

it('falls back to the config default when no setting is stored', function () {
    expect(Setting::sstRate(0.06))->toBe(0.06);
});

it('reads a stored SST rate and parses it as a float', function () {
    Setting::setValue('sst_rate', 0.08);
    expect(Setting::sstRate(0.06))->toBe(0.08);
});

it('setValue refreshes the cached value', function () {
    expect(Setting::sstRate(0.06))->toBe(0.06); // caches the default
    Setting::setValue('sst_rate', 0.10);
    expect(Setting::sstRate(0.06))->toBe(0.10); // cache was cleared on write
});

it('renders the billing settings page with the current rate as a percentage', function () {
    Setting::setValue('sst_rate', 0.08);

    Fixtures::actingAs($this, Fixtures::admin())
        ->get(route('config.billing.index'))
        ->assertOk()
        ->assertSee('Billing Settings')
        ->assertSee('value="8"', false);
});

it('updates the SST rate from a percentage and stores it as a fraction', function () {
    Fixtures::actingAs($this, Fixtures::admin())
        ->put(route('config.billing.update'), ['sst_percent' => 8])
        ->assertRedirect(route('config.billing.index'))
        ->assertSessionHas('success');

    expect(DB::table('app_settings')->where('key', 'sst_rate')->value('value'))->toBe('0.08')
        ->and(Setting::sstRate(0.06))->toBe(0.08);

    expect(DB::table('admin_audit_logs')->where('action', 'settings.sst_rate.updated')->exists())->toBeTrue();
});

it('accepts 0% (no SST)', function () {
    Fixtures::actingAs($this, Fixtures::admin())
        ->put(route('config.billing.update'), ['sst_percent' => 0])
        ->assertSessionHas('success');

    expect(Setting::sstRate(0.06))->toBe(0.0);
});

it('rejects a negative or over-100 rate', function () {
    $admin = Fixtures::admin();

    Fixtures::actingAs($this, $admin)
        ->put(route('config.billing.update'), ['sst_percent' => -5])
        ->assertSessionHasErrors('sst_percent');

    Fixtures::actingAs($this, $admin)
        ->put(route('config.billing.update'), ['sst_percent' => 150])
        ->assertSessionHasErrors('sst_percent');
});
