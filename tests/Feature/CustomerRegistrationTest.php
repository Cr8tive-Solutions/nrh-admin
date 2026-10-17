<?php

use App\Mail\CustomerRegistrationMail;
use App\Models\AdminAuditLog;
use App\Models\Customer;
use App\Models\CustomerRegistration;
use App\Models\CustomerUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\Support\Fixtures;

/**
 * Invite-to-register onboarding, staff side: invite → (invitee submits on the
 * client portal) → approve creates the customer + an active Owner login, or
 * reject emails the reason.
 */
beforeEach(function () {
    Fixtures::seedPermissions();
    Fixtures::portalRoles();
    Mail::fake();

    $this->admin = Fixtures::admin(['role' => 'super_admin']);
});

/** A registration as the client portal leaves it after the invitee submits. */
function submittedRegistration(array $attrs = []): CustomerRegistration
{
    $registration = new CustomerRegistration(array_merge([
        'email' => 'reg-'.Fixtures::uniq().'@example.test',
        'status' => 'submitted',
        'company_name' => 'Maju Jaya Sdn Bhd',
        'registration_no' => '202301012345',
        'address' => "12 Jalan Ampang\nKuala Lumpur",
        'country' => 'Malaysia',
        'industry' => 'Logistics',
        'contact_name' => 'Aminah Yusof',
        'contact_designation' => 'HR Manager',
        'contact_phone' => '+60123456789',
        'password_hash' => Hash::make('chosen-password'),
        'submitted_at' => now(),
    ], $attrs));
    $registration->issueToken();
    $registration->save();

    return $registration;
}

function reviewPayload(CustomerRegistration $r, array $overrides = []): array
{
    return array_merge($r->only([
        'company_name', 'registration_no', 'address', 'country', 'industry',
        'contact_name', 'contact_designation', 'contact_phone',
    ]), $overrides);
}

it('invites a prospective customer by email with a hashed, expiring token', function () {
    $email = 'Prospect-'.Fixtures::uniq().'@Example.test';

    $response = Fixtures::actingAs($this, $this->admin)
        ->post(route('registrations.store'), [
            'email' => $email,
            'company_name' => 'Prospect Bhd',
            'invite_note' => 'Met at HR expo',
        ])
        ->assertRedirect()
        ->assertSessionHas('invitation_url');

    $registration = CustomerRegistration::where('email', strtolower($email))->firstOrFail();
    $url = session('invitation_url');
    $token = basename($url);

    expect($registration->status)->toBe('invited')
        ->and($registration->invited_by_admin_id)->toBe($this->admin->id)
        ->and($registration->company_name)->toBe('Prospect Bhd')
        ->and($url)->toContain('/register/')
        // Only the hash is stored.
        ->and($registration->token_hash)->toBe(hash('sha256', $token))
        ->and($registration->token_hash)->not->toBe($token)
        ->and($registration->expires_at->isFuture())->toBeTrue();

    Mail::assertSent(CustomerRegistrationMail::class, fn ($m) => $m->kind === 'invite'
        && $m->url === $url && $m->hasTo(strtolower($email)));

    $response->assertRedirect(route('registrations.show', $registration));
});

it('refuses to invite an email that already has a portal login or an open registration', function () {
    $customerId = Fixtures::customer();
    $existing = CustomerUser::findOrFail(Fixtures::customerUser($customerId));

    Fixtures::actingAs($this, $this->admin)
        ->post(route('registrations.store'), ['email' => $existing->email])
        ->assertSessionHasErrors('email');

    $open = submittedRegistration();

    Fixtures::actingAs($this, $this->admin)
        ->post(route('registrations.store'), ['email' => $open->email])
        ->assertSessionHasErrors('email');

    Mail::assertNothingSent();
});

it('approves a submission into a customer with an active Owner login using the chosen password', function () {
    $r = submittedRegistration();

    $response = Fixtures::actingAs($this, $this->admin)
        ->post(route('registrations.approve', $r), reviewPayload($r, ['company_name' => 'Maju Jaya (M) Sdn Bhd']));

    $customer = Customer::where('name', 'Maju Jaya (M) Sdn Bhd')->firstOrFail();
    $response->assertRedirect(route('customers.show', $customer));

    $user = CustomerUser::where('email', $r->email)->firstOrFail();
    $r->refresh();

    expect($customer->registration_no)->toBe('202301012345')
        ->and($customer->contact_email)->toBe($r->email)
        ->and($user->customer_id)->toBe($customer->id)
        ->and($user->status)->toBe('active')
        ->and($user->portalRoleName())->toBe('Owner')
        ->and($user->role)->toBe('admin')
        ->and(Hash::check('chosen-password', $user->getRawOriginal('password')))->toBeTrue()
        ->and($r->status)->toBe('approved')
        ->and($r->customer_id)->toBe($customer->id)
        ->and($r->customer_user_id)->toBe($user->id)
        ->and($r->reviewed_by_admin_id)->toBe($this->admin->id)
        // The staged password hash is not kept once it has been handed over.
        ->and($r->password_hash)->toBeNull()
        ->and(AdminAuditLog::where('action', 'customer_registration.approved')->exists())->toBeTrue();

    Mail::assertSent(CustomerRegistrationMail::class, fn ($m) => $m->kind === 'approved' && $m->hasTo($r->email));
});

it('will not approve when the email already has a portal login', function () {
    $r = submittedRegistration();
    Fixtures::customerUser(Fixtures::customer(), ['email' => $r->email]);

    Fixtures::actingAs($this, $this->admin)
        ->post(route('registrations.approve', $r), reviewPayload($r))
        ->assertSessionHas('error');

    expect($r->fresh()->status)->toBe('submitted')
        ->and(Customer::where('name', $r->company_name)->where('registration_no', $r->registration_no)->exists())->toBeFalse();
});

it('only approves submitted registrations', function () {
    $r = submittedRegistration(['status' => 'invited']);

    Fixtures::actingAs($this, $this->admin)
        ->post(route('registrations.approve', $r), reviewPayload($r))
        ->assertSessionHas('warning');

    expect(CustomerUser::where('email', $r->email)->exists())->toBeFalse();
});

it('rejects with a reason that is emailed to the registrant', function () {
    $r = submittedRegistration();

    Fixtures::actingAs($this, $this->admin)
        ->post(route('registrations.reject', $r), [])
        ->assertSessionHasErrors('review_note');

    Fixtures::actingAs($this, $this->admin)
        ->post(route('registrations.reject', $r), ['review_note' => 'Registration number does not match SSM.'])
        ->assertSessionHas('success');

    $r->refresh();
    expect($r->status)->toBe('rejected')
        ->and($r->review_note)->toBe('Registration number does not match SSM.')
        ->and($r->password_hash)->toBeNull();

    Mail::assertSent(CustomerRegistrationMail::class, fn ($m) => $m->kind === 'rejected' && $m->hasTo($r->email));
});

it('resend replaces the link so the old token stops matching', function () {
    $r = submittedRegistration(['status' => 'invited', 'password_hash' => null, 'submitted_at' => null]);
    $oldHash = $r->token_hash;

    Fixtures::actingAs($this, $this->admin)
        ->post(route('registrations.resend', $r))
        ->assertSessionHas('invitation_url');

    $r->refresh();
    expect($r->token_hash)->not->toBe($oldHash)
        ->and($r->token_hash)->toBe(hash('sha256', basename(session('invitation_url'))))
        ->and($r->sent_count)->toBe(2);
});

it('lists and shows registrations', function () {
    $r = submittedRegistration();

    Fixtures::actingAs($this, $this->admin)
        ->get(route('registrations.index'))
        ->assertOk()
        ->assertSee($r->company_name);

    Fixtures::actingAs($this, $this->admin)
        ->get(route('registrations.show', $r))
        ->assertOk()
        ->assertSee('Approve &amp; create customer', false);
});

it('is restricted to staff with customer.manage', function () {
    $viewer = Fixtures::admin(['role' => 'viewer']);
    $r = submittedRegistration();

    Fixtures::actingAs($this, $viewer)->get(route('registrations.index'))->assertForbidden();
    Fixtures::actingAs($this, $viewer)->post(route('registrations.store'), ['email' => 'x@example.test'])->assertForbidden();
    Fixtures::actingAs($this, $viewer)->post(route('registrations.approve', $r), reviewPayload($r))->assertForbidden();

    expect($r->fresh()->status)->toBe('submitted');
});

it('renders all three emails', function () {
    $r = submittedRegistration(['review_note' => 'Details <b>did not</b> match.']);

    $invite = (new CustomerRegistrationMail($r, 'invite', 'https://portal.test/register/abc'))->render();
    $approved = (new CustomerRegistrationMail($r, 'approved', 'https://portal.test/login'))->render();
    $rejected = (new CustomerRegistrationMail($r, 'rejected'))->render();

    expect($invite)->toContain('https://portal.test/register/abc')->toContain('Register now')
        ->and($approved)->toContain('https://portal.test/login')->toContain('Maju Jaya Sdn Bhd')
        // Staff-entered reason is escaped, not injected as HTML.
        ->and($rejected)->toContain('Details &lt;b&gt;did not&lt;/b&gt; match.');
});
