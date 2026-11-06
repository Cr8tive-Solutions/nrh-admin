<?php

use App\Models\Admin;
use App\Models\AdminAuditLog;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;
use Tests\Support\Fixtures;

/**
 * Staff administration guard rails: only a super admin touches super admin
 * accounts or the super_admin role, and nobody edits their own access.
 */
beforeEach(function () {
    Fixtures::seedPermissions();
});

/** An operations admin granted staff.manage + permissions.manage by override. */
function staffManager(): Admin
{
    $admin = Fixtures::admin(['role' => 'operations']);

    foreach (['staff.manage', 'permissions.manage'] as $key) {
        DB::table('admin_user_permissions')->insert([
            'admin_id' => $admin->id,
            'admin_permission_id' => Permission::where('key', $key)->value('id'),
            'granted' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    return $admin;
}

function newStaffPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'New Staffer',
        'email' => Fixtures::uniq('staff').'@example.test',
        'password' => 'Initial-Passw0rd',
        'password_confirmation' => 'Initial-Passw0rd',
        'role' => 'operations',
    ], $overrides);
}

// ── Creating staff ───────────────────────────────────────────────────────────

it('creates a staff account that must change its password, and audit-logs it', function () {
    $payload = newStaffPayload();

    Fixtures::actingAs($this, Fixtures::admin())
        ->post(route('staff.store'), $payload)
        ->assertRedirect(route('staff.index'));

    $created = Admin::where('email', $payload['email'])->firstOrFail();

    expect($created->must_change_password)->toBeTrue()
        ->and(AdminAuditLog::where('action', 'staff.created')->where('target_admin_id', $created->id)->exists())->toBeTrue();
});

it('enforces the password policy on new staff accounts', function () {
    Fixtures::actingAs($this, Fixtures::admin())
        ->post(route('staff.store'), newStaffPayload(['password' => 'short1A', 'password_confirmation' => 'short1A']))
        ->assertSessionHasErrors('password');
});

it('lets only a super admin create a super admin', function () {
    $payload = newStaffPayload(['role' => 'super_admin']);

    Fixtures::actingAs($this, staffManager())
        ->post(route('staff.store'), $payload)
        ->assertSessionHasErrors('role');
    expect(Admin::where('email', $payload['email'])->exists())->toBeFalse();

    Fixtures::actingAs($this, Fixtures::admin(['role' => 'super_admin']))
        ->post(route('staff.store'), $payload)
        ->assertSessionHasNoErrors();
    expect(Admin::where('email', $payload['email'])->value('role'))->toBe('super_admin');
});

// ── Activating / deactivating ────────────────────────────────────────────────

it('audit-logs a status toggle', function () {
    $target = Fixtures::admin(['role' => 'viewer']);

    Fixtures::actingAs($this, Fixtures::admin())->patch(route('staff.toggle', $target));

    $log = AdminAuditLog::where('action', 'staff.status_toggled')->where('target_admin_id', $target->id)->firstOrFail();
    expect($target->fresh()->status)->toBe('inactive')
        ->and($log->details)->toMatchArray(['from' => 'active', 'to' => 'inactive']);
});

it('stops a non-super admin from deactivating a super admin', function () {
    $super = Fixtures::admin(['role' => 'super_admin']);

    Fixtures::actingAs($this, staffManager())->patch(route('staff.toggle', $super))->assertSessionHas('error');

    expect($super->fresh()->status)->toBe('active');
});

it('stops anyone from toggling their own status', function () {
    $super = Fixtures::admin(['role' => 'super_admin']);

    Fixtures::actingAs($this, $super)->patch(route('staff.toggle', $super))->assertSessionHas('error');

    expect($super->fresh()->status)->toBe('active');
});

// ── Per-user permission overrides ────────────────────────────────────────────

it('stops an admin from editing their own permission overrides', function () {
    $manager = staffManager();
    $retentionId = Permission::where('key', 'pdpa.retention')->value('id');

    Fixtures::actingAs($this, $manager)
        ->put(route('staff.permissions.update', $manager), ['override' => [$retentionId => 'grant']])
        ->assertSessionHas('error');

    expect(DB::table('admin_user_permissions')->where('admin_id', $manager->id)->where('admin_permission_id', $retentionId)->exists())->toBeFalse();
});

it('stops a non-super admin from editing a super admin\'s overrides', function () {
    $super = Fixtures::admin(['role' => 'super_admin']);
    $perm = Permission::where('key', 'pdpa.retention')->value('id');

    Fixtures::actingAs($this, staffManager())
        ->put(route('staff.permissions.update', $super), ['override' => [$perm => 'revoke']])
        ->assertSessionHas('error');

    expect(DB::table('admin_user_permissions')->where('admin_id', $super->id)->exists())->toBeFalse();
});

it('still lets a staff manager edit another non-super admin\'s overrides', function () {
    $viewer = Fixtures::admin(['role' => 'viewer']);
    $perm = Permission::where('key', 'invoice.manage')->value('id');

    Fixtures::actingAs($this, staffManager())
        ->put(route('staff.permissions.update', $viewer), ['override' => [$perm => 'grant']])
        ->assertSessionMissing('error');

    expect($viewer->fresh()->can('invoice.manage'))->toBeTrue();
});

// ── Role matrix ──────────────────────────────────────────────────────────────

it('stops an admin from changing their own role\'s permissions in the matrix', function () {
    $manager = staffManager();
    $matrix = DB::table('admin_role_permissions')->get()->groupBy('role')
        ->map(fn ($rows) => $rows->pluck('admin_permission_id')->all())->all();
    $matrix['operations'][] = Permission::where('key', 'pdpa.retention')->value('id');

    Fixtures::actingAs($this, $manager)
        ->put(route('permissions.update'), ['matrix' => $matrix])
        ->assertSessionHas('error');

    expect(DB::table('admin_role_permissions')
        ->where('role', 'operations')
        ->where('admin_permission_id', Permission::where('key', 'pdpa.retention')->value('id'))
        ->exists())->toBeFalse();
});

it('lets an admin change other roles in the matrix', function () {
    $manager = staffManager();
    $matrix = DB::table('admin_role_permissions')->get()->groupBy('role')
        ->map(fn ($rows) => $rows->pluck('admin_permission_id')->all())->all();
    $matrix['viewer'] = [Permission::where('key', 'report.view')->value('id')];

    Fixtures::actingAs($this, $manager)
        ->put(route('permissions.update'), ['matrix' => $matrix])
        ->assertSessionMissing('error');

    expect(Fixtures::admin(['role' => 'viewer'])->can('report.view'))->toBeTrue();
});
