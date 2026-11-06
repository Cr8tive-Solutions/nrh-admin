<?php

use App\Models\Admin;
use App\Models\AdminAuditLog;
use App\Support\AuthSession;
use PragmaRX\Google2FA\Google2FA;
use Tests\Support\Fixtures;

/**
 * Staff account hardening: mandatory 2FA, forced first-login password change,
 * self-service password change, and the absolute session lifetime.
 */
function unenrolledAdmin(array $attrs = []): Admin
{
    return Fixtures::admin(array_merge([
        'two_factor_secret' => null,
        'two_factor_confirmed_at' => null,
    ], $attrs));
}

// ── Mandatory 2FA ────────────────────────────────────────────────────────────

it('sends an admin without 2FA to enrolment from every page', function () {
    $admin = unenrolledAdmin(['role' => 'operations']);

    foreach (['/dashboard', '/requests', '/customers', '/guide'] as $url) {
        Fixtures::actingAs($this, $admin)->get($url)->assertRedirect(route('account.security'));
    }
});

it('lets an admin without 2FA reach the enrolment page and start enrolment', function () {
    $admin = unenrolledAdmin();

    Fixtures::actingAs($this, $admin)->get(route('account.security'))->assertOk();
    Fixtures::actingAs($this, $admin)->post(route('account.two-factor.enable'))->assertRedirect(route('account.security'));

    expect(Admin::find($admin->id)->two_factor_secret)->not->toBeNull();
});

it('lets an admin finish enrolment and then use the portal', function () {
    $admin = unenrolledAdmin();
    Fixtures::actingAs($this, $admin)->post(route('account.two-factor.enable'));

    $secret = Admin::find($admin->id)->two_factor_secret;
    Fixtures::actingAs($this, $admin)
        ->post(route('account.two-factor.confirm'), ['code' => (new Google2FA)->getCurrentOtp($secret)])
        ->assertRedirect(route('account.security'));

    Fixtures::actingAs($this, $admin)->get('/dashboard')->assertOk();
});

it('lets an admin without 2FA log out', function () {
    $admin = unenrolledAdmin();

    Fixtures::actingAs($this, $admin)->post('/logout')->assertRedirect(route('login'));

    expect(session('admin_id'))->toBeNull();
});

it('requires a current authenticator code to disable 2FA', function () {
    $admin = Fixtures::admin(['password' => 'correct-horse']);

    Fixtures::actingAs($this, $admin)
        ->delete(route('account.two-factor.disable'), ['password' => 'correct-horse'])
        ->assertSessionHasErrors('code');

    Fixtures::actingAs($this, $admin)
        ->delete(route('account.two-factor.disable'), ['password' => 'correct-horse', 'code' => '000000'])
        ->assertSessionHasErrors('code');

    expect(Admin::find($admin->id)->hasEnabledTwoFactor())->toBeTrue();
});

it('disables 2FA with password plus a valid code, then forces re-enrolment', function () {
    $admin = Fixtures::admin(['password' => 'correct-horse']);
    $code = (new Google2FA)->getCurrentOtp($admin->two_factor_secret);

    Fixtures::actingAs($this, $admin)
        ->delete(route('account.two-factor.disable'), ['password' => 'correct-horse', 'code' => $code])
        ->assertRedirect(route('account.security'));

    expect(Admin::find($admin->id)->hasEnabledTwoFactor())->toBeFalse();

    Fixtures::actingAs($this, $admin)->get('/dashboard')->assertRedirect(route('account.security'));
});

it('accepts a recovery code in place of the authenticator code when disabling 2FA', function () {
    $admin = Fixtures::admin(['password' => 'correct-horse', 'two_factor_recovery_codes' => ['aaaa-bbbb']]);

    Fixtures::actingAs($this, $admin)
        ->delete(route('account.two-factor.disable'), ['password' => 'correct-horse', 'code' => 'aaaa-bbbb'])
        ->assertRedirect(route('account.security'));

    expect(Admin::find($admin->id)->hasEnabledTwoFactor())->toBeFalse();
});

it('throttles the account security actions', function () {
    $admin = Fixtures::admin(['password' => 'correct-horse']);

    for ($i = 0; $i < 5; $i++) {
        Fixtures::actingAs($this, $admin)
            ->delete(route('account.two-factor.disable'), ['password' => 'wrong', 'code' => '000000']);
    }

    Fixtures::actingAs($this, $admin)
        ->delete(route('account.two-factor.disable'), ['password' => 'wrong', 'code' => '000000'])
        ->assertStatus(429);
});

// ── Self-service password change ─────────────────────────────────────────────

it('changes the password and audit-logs it', function () {
    $admin = Fixtures::admin(['password' => 'Old-password-1']);

    Fixtures::actingAs($this, $admin)
        ->put(route('account.password.update'), [
            'current_password' => 'Old-password-1',
            'password' => 'Brand-new-Passw0rd',
            'password_confirmation' => 'Brand-new-Passw0rd',
        ])
        ->assertRedirect(route('account.security'))
        ->assertSessionHasNoErrors();

    expect(Admin::find($admin->id)->verifyPassword('Brand-new-Passw0rd'))->toBeTrue()
        ->and(AdminAuditLog::where('action', 'auth.password_changed')->where('target_admin_id', $admin->id)->exists())->toBeTrue();
});

it('refuses a wrong current password', function () {
    $admin = Fixtures::admin(['password' => 'Old-password-1']);

    Fixtures::actingAs($this, $admin)
        ->put(route('account.password.update'), [
            'current_password' => 'not-it',
            'password' => 'Brand-new-Passw0rd',
            'password_confirmation' => 'Brand-new-Passw0rd',
        ])
        ->assertSessionHasErrorsIn('updatePassword', 'current_password');

    expect(Admin::find($admin->id)->verifyPassword('Old-password-1'))->toBeTrue();
});

it('enforces the staff password policy', function (string $weak) {
    $admin = Fixtures::admin(['password' => 'Old-password-1']);

    Fixtures::actingAs($this, $admin)
        ->put(route('account.password.update'), [
            'current_password' => 'Old-password-1',
            'password' => $weak,
            'password_confirmation' => $weak,
        ])
        ->assertSessionHasErrorsIn('updatePassword', 'password');
})->with([
    'too short' => 'Short-pw1',
    'no upper case' => 'all-lower-case-1',
    'no number' => 'No-Numbers-Here',
]);

it('signs out the admin\'s other sessions when the password changes', function () {
    $admin = Fixtures::admin(['password' => 'Old-password-1']);
    $otherSession = [
        'admin_id' => $admin->id,
        'admin_logged_in_at' => now()->getTimestamp(),
        'admin_password_fp' => AuthSession::passwordFingerprint($admin),
    ];

    Fixtures::actingAs($this, $admin)->put(route('account.password.update'), [
        'current_password' => 'Old-password-1',
        'password' => 'Brand-new-Passw0rd',
        'password_confirmation' => 'Brand-new-Passw0rd',
    ]);

    // This session keeps working…
    $this->get('/dashboard')->assertOk();

    // …but one opened with the old password does not.
    app()->forgetInstance('current_admin');
    $this->flushSession();
    $this->withSession($otherSession)->get('/dashboard')->assertRedirect(route('login'));
});

// ── Forced password change for accounts created by another admin ─────────────

it('forces an admin flagged must_change_password to the password form', function () {
    $admin = Fixtures::admin(['password' => 'Initial-Passw0rd', 'must_change_password' => true]);

    Fixtures::actingAs($this, $admin)->get('/dashboard')->assertRedirect(route('account.security'));
    Fixtures::actingAs($this, $admin)->get(route('account.security'))->assertOk();

    Fixtures::actingAs($this, $admin)->put(route('account.password.update'), [
        'current_password' => 'Initial-Passw0rd',
        'password' => 'My-own-Passw0rd',
        'password_confirmation' => 'My-own-Passw0rd',
    ])->assertSessionHasNoErrors();

    expect(Admin::find($admin->id)->must_change_password)->toBeFalse();
    $this->get('/dashboard')->assertOk();
});

it('does not force existing admins to change their password', function () {
    $admin = Fixtures::admin();

    expect($admin->fresh()->must_change_password)->toBeFalse();
    Fixtures::actingAs($this, $admin)->get('/dashboard')->assertOk();
});

// ── Absolute session lifetime ────────────────────────────────────────────────

it('signs staff out 12 hours after sign-in regardless of activity', function () {
    $admin = Fixtures::admin();

    Fixtures::actingAs($this, $admin)
        ->withSession(['admin_logged_in_at' => now()->subHours(12)->subMinute()->getTimestamp()])
        ->get('/dashboard')
        ->assertRedirect(route('login'));

    expect(session('admin_id'))->toBeNull();
});

it('keeps a session alive within the absolute lifetime', function () {
    $admin = Fixtures::admin();

    Fixtures::actingAs($this, $admin)
        ->withSession(['admin_logged_in_at' => now()->subHours(11)->getTimestamp()])
        ->get('/dashboard')
        ->assertOk();
});

it('reads the absolute lifetime from config', function () {
    config(['session.absolute_lifetime_minutes' => 30]);
    $admin = Fixtures::admin();

    Fixtures::actingAs($this, $admin)
        ->withSession(['admin_logged_in_at' => now()->subMinutes(31)->getTimestamp()])
        ->get('/dashboard')
        ->assertRedirect(route('login'));
});

it('records the sign-in time at login', function () {
    $this->freezeTime();
    Fixtures::admin([
        'email' => 'lifetime@example.test',
        'password' => 'correct-horse',
        'two_factor_secret' => null,
        'two_factor_confirmed_at' => null,
    ]);

    $this->post('/login', ['email' => 'lifetime@example.test', 'password' => 'correct-horse']);

    expect(session('admin_logged_in_at'))->toBe(now()->getTimestamp());
});
