<?php

use App\Models\Customer;
use App\Models\CustomerUser;
use Illuminate\Support\Facades\DB;
use Tests\Support\Fixtures;

/**
 * NRH staff managing a customer's portal logins.
 *
 * The real role lives in the client portal's spatie tables (`roles` /
 * `model_has_roles`, guard `customer_user`). This app has no spatie package, so
 * it writes that pivot directly — these tests pin the contract down, including
 * keeping the legacy `customer_users.role` flag in step.
 */
beforeEach(function () {
    Fixtures::seedPermissions();

    $this->admin = Fixtures::admin(['role' => 'super_admin']);
    $this->customerId = Fixtures::customer();
    $this->customer = Customer::findOrFail($this->customerId);
    $this->userId = Fixtures::customerUser($this->customerId);
    $this->user = CustomerUser::findOrFail($this->userId);

    Fixtures::portalRoles();
});

it('invites an additional user with the chosen role', function () {
    $email = 'invitee-'.Fixtures::uniq().'@example.test';

    Fixtures::actingAs($this, $this->admin)
        ->post(route('customers.users.invite', $this->customer), [
            'name' => 'Siti Nurhaliza',
            'email' => $email,
            'portal_role' => 'HR',
        ])
        ->assertRedirect()
        ->assertSessionHas('invitation_url');

    $invited = CustomerUser::where('email', $email)->firstOrFail();

    expect($invited->customer_id)->toBe($this->customerId)
        ->and($invited->name)->toBe('Siti Nurhaliza')
        ->and($invited->portalRoleName())->toBe('HR')
        // Suspended until they accept and set a password.
        ->and($invited->status)->toBe('inactive')
        ->and($invited->role)->toBe('user');

    // A pending 14-day invitation is issued.
    $invitation = DB::table('customer_user_invitations')->where('customer_user_id', $invited->id)->first();
    expect($invitation)->not->toBeNull()
        ->and($invitation->accepted_at)->toBeNull();
});

it('maps an Owner invite to the legacy admin flag', function () {
    $email = 'owner-'.Fixtures::uniq().'@example.test';

    Fixtures::actingAs($this, $this->admin)
        ->post(route('customers.users.invite', $this->customer), [
            'name' => 'Second Owner',
            'email' => $email,
            'portal_role' => 'Owner',
        ]);

    $invited = CustomerUser::where('email', $email)->firstOrFail();

    expect($invited->portalRoleName())->toBe('Owner')
        ->and($invited->role)->toBe('admin');
});

it('invites even when the customer already has users', function () {
    // provisionPrimaryUser refuses in this situation; invite must not.
    expect($this->customer->customerUsers()->count())->toBe(1);

    $email = 'extra-'.Fixtures::uniq().'@example.test';

    Fixtures::actingAs($this, $this->admin)
        ->post(route('customers.users.invite', $this->customer), [
            'name' => 'Third Member',
            'email' => $email,
            'portal_role' => 'Viewer',
        ])
        ->assertSessionHasNoErrors();

    expect($this->customer->customerUsers()->count())->toBe(2);
});

it('rejects an email already used by any customer', function () {
    $otherCustomerId = Fixtures::customer();
    $takenId = Fixtures::customerUser($otherCustomerId);
    $taken = CustomerUser::findOrFail($takenId);

    Fixtures::actingAs($this, $this->admin)
        ->post(route('customers.users.invite', $this->customer), [
            'name' => 'Clashing Person',
            'email' => $taken->email,
            'portal_role' => 'HR',
        ])
        ->assertSessionHasErrors('email');

    // No partial account left behind.
    expect(CustomerUser::where('customer_id', $this->customerId)->count())->toBe(1);
});

it('rejects an invite with a role that is not a portal role', function () {
    Fixtures::actingAs($this, $this->admin)
        ->post(route('customers.users.invite', $this->customer), [
            'name' => 'Bad Role',
            'email' => 'badrole-'.Fixtures::uniq().'@example.test',
            'portal_role' => 'super_admin',
        ])
        ->assertSessionHasErrors('portal_role');

    expect(CustomerUser::where('customer_id', $this->customerId)->count())->toBe(1);
});

it('denies inviting without customer.manage', function () {
    $finance = Fixtures::admin(['role' => 'finance']);

    Fixtures::actingAs($this, $finance)
        ->post(route('customers.users.invite', $this->customer), [
            'name' => 'Nope',
            'email' => 'nope-'.Fixtures::uniq().'@example.test',
            'portal_role' => 'HR',
        ])
        ->assertForbidden();
});

it('assigns a portal role and keeps the legacy flag in step', function () {
    Fixtures::actingAs($this, $this->admin)
        ->patch(route('customers.users.role', [$this->customer, $this->user]), ['portal_role' => 'Owner'])
        ->assertRedirect();

    expect($this->user->fresh()->portalRoleName())->toBe('Owner')
        ->and($this->user->fresh()->role)->toBe('admin');
});

it('maps a non-privileged role to the legacy user flag', function () {
    Fixtures::actingAs($this, $this->admin)
        ->patch(route('customers.users.role', [$this->customer, $this->user]), ['portal_role' => 'HR']);

    expect($this->user->fresh()->portalRoleName())->toBe('HR')
        ->and($this->user->fresh()->role)->toBe('user');
});

it('replaces rather than stacks roles', function () {
    $as = Fixtures::actingAs($this, $this->admin);

    $as->patch(route('customers.users.role', [$this->customer, $this->user]), ['portal_role' => 'Owner']);
    $as->patch(route('customers.users.role', [$this->customer, $this->user]), ['portal_role' => 'Viewer']);

    $assigned = DB::table('model_has_roles')
        ->where('model_type', CustomerUser::ROLE_MODEL_TYPE)
        ->where('model_id', $this->userId)
        ->count();

    expect($assigned)->toBe(1)
        ->and($this->user->fresh()->portalRoleName())->toBe('Viewer');
});

it('rejects a role that is not a portal role', function () {
    Fixtures::actingAs($this, $this->admin)
        ->patch(route('customers.users.role', [$this->customer, $this->user]), ['portal_role' => 'super_admin'])
        ->assertSessionHasErrors('portal_role');
});

it('suspends and restores portal access', function () {
    $as = Fixtures::actingAs($this, $this->admin);

    // Viewer, so the last-active-Owner guard does not apply.
    $as->patch(route('customers.users.role', [$this->customer, $this->user]), ['portal_role' => 'Viewer']);

    $as->patch(route('customers.users.status', [$this->customer, $this->user]), ['status' => 'inactive']);
    expect($this->user->fresh()->status)->toBe('inactive');

    $as->patch(route('customers.users.status', [$this->customer, $this->user]), ['status' => 'active']);
    expect($this->user->fresh()->status)->toBe('active');
});

it('refuses to suspend the only active owner', function () {
    $as = Fixtures::actingAs($this, $this->admin);
    $as->patch(route('customers.users.role', [$this->customer, $this->user]), ['portal_role' => 'Owner']);

    $as->patch(route('customers.users.status', [$this->customer, $this->user]), ['status' => 'inactive'])
        ->assertSessionHas('error');

    expect($this->user->fresh()->status)->toBe('active');
});

it('allows suspending an owner once another active owner exists', function () {
    $as = Fixtures::actingAs($this, $this->admin);
    $as->patch(route('customers.users.role', [$this->customer, $this->user]), ['portal_role' => 'Owner']);

    $secondId = Fixtures::customerUser($this->customerId);
    $second = CustomerUser::findOrFail($secondId);
    $as->patch(route('customers.users.role', [$this->customer, $second]), ['portal_role' => 'Owner']);

    $as->patch(route('customers.users.status', [$this->customer, $this->user]), ['status' => 'inactive'])
        ->assertSessionHasNoErrors();

    expect($this->user->fresh()->status)->toBe('inactive');
});

it('404s on a user belonging to another customer', function () {
    $otherCustomerId = Fixtures::customer();
    $foreignId = Fixtures::customerUser($otherCustomerId);
    $foreign = CustomerUser::findOrFail($foreignId);

    Fixtures::actingAs($this, $this->admin)
        ->patch(route('customers.users.role', [$this->customer, $foreign]), ['portal_role' => 'HR'])
        ->assertNotFound();
});

it('denies a role without customer.manage', function () {
    $finance = Fixtures::admin(['role' => 'finance']);

    Fixtures::actingAs($this, $finance)
        ->patch(route('customers.users.role', [$this->customer, $this->user]), ['portal_role' => 'HR'])
        ->assertForbidden();
});

it('shows the real portal role on the customer page', function () {
    Fixtures::actingAs($this, $this->admin)
        ->patch(route('customers.users.role', [$this->customer, $this->user]), ['portal_role' => 'Accounts']);

    Fixtures::actingAs($this, $this->admin)
        ->get(route('customers.show', $this->customer))
        ->assertOk()
        ->assertSee('Accounts');
});

it('gives a provisioned primary user the Owner role', function () {
    // provisionPrimaryUser requires a customer with no users yet.
    $freshId = Fixtures::customer(['contact_name' => 'Jane Owner', 'contact_email' => 'jane-'.Fixtures::uniq().'@example.test']);
    $fresh = Customer::findOrFail($freshId);

    Fixtures::actingAs($this, $this->admin)
        ->post(route('customers.provision-primary-user', $fresh))
        ->assertRedirect();

    $provisioned = CustomerUser::where('customer_id', $freshId)->firstOrFail();

    // Without this the account could log in but every client route is
    // permission-gated, so they would see almost nothing.
    expect($provisioned->portalRoleName())->toBe('Owner');
});
