<?php

use App\Models\Permission;
use Illuminate\Support\Facades\DB;
use Tests\Support\Fixtures;

beforeEach(function () {
    Fixtures::seedPermissions();
});

it('lets any authenticated admin reach read-only pages', function () {
    $viewer = Fixtures::admin(['role' => 'viewer']);
    Fixtures::actingAs($this, $viewer)->get('/requests')->assertOk();
});

it('403s a viewer on a permission-gated page', function () {
    $viewer = Fixtures::admin(['role' => 'viewer']);
    Fixtures::actingAs($this, $viewer)->get('/invoices/create')->assertForbidden();
});

it('grants finance the invoice permission', function () {
    $finance = Fixtures::admin(['role' => 'finance']);
    Fixtures::actingAs($this, $finance)->get('/invoices/create')->assertOk();
});

it('denies operations the invoice permission', function () {
    $ops = Fixtures::admin(['role' => 'operations']);
    Fixtures::actingAs($this, $ops)->get('/invoices/create')->assertForbidden();
});

it('grants operations the customer permission', function () {
    $ops = Fixtures::admin(['role' => 'operations']);
    Fixtures::actingAs($this, $ops)->get('/customers/create')->assertOk();
});

it('denies finance the customer permission', function () {
    $finance = Fixtures::admin(['role' => 'finance']);
    Fixtures::actingAs($this, $finance)->get('/customers/create')->assertForbidden();
});

it('lets super_admin bypass every gate', function () {
    $super = Fixtures::admin(['role' => 'super_admin']);
    $as = Fixtures::actingAs($this, $super);

    foreach (['/invoices/create', '/customers/create', '/staff', '/permissions', '/compliance/dsar'] as $url) {
        $as->get($url)->assertOk();
    }
});

it('honours a per-user grant override', function () {
    $viewer = Fixtures::admin(['role' => 'viewer']);
    $perm = Permission::where('key', 'invoice.manage')->firstOrFail();

    DB::table('admin_user_permissions')->insert([
        'admin_id' => $viewer->id,
        'admin_permission_id' => $perm->id,
        'granted' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    Fixtures::actingAs($this, $viewer)->get('/invoices/create')->assertOk();
});

it('honours a per-user revoke override that beats the role grant', function () {
    $finance = Fixtures::admin(['role' => 'finance']);
    $perm = Permission::where('key', 'invoice.manage')->firstOrFail();

    DB::table('admin_user_permissions')->insert([
        'admin_id' => $finance->id,
        'admin_permission_id' => $perm->id,
        'granted' => false,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    Fixtures::actingAs($this, $finance)->get('/invoices/create')->assertForbidden();
});

it('resolves effective permission keys correctly per role', function () {
    expect(Fixtures::admin(['role' => 'viewer'])->effectivePermissionKeys())->toBeEmpty();

    expect(Fixtures::admin(['role' => 'finance'])->effectivePermissionKeys())
        ->toEqualCanonicalizing(['pricing.manage', 'invoice.manage', 'transaction.manage']);

    expect(Fixtures::admin(['role' => 'operations'])->effectivePermissionKeys())
        ->toEqualCanonicalizing(['request.update', 'customer.manage', 'pdpa.consent', 'pdpa.dsar']);

    // super_admin short-circuits to every key.
    expect(Fixtures::admin(['role' => 'super_admin'])->effectivePermissionKeys())
        ->toHaveCount(Permission::count());
});
