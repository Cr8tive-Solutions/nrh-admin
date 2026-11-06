<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Models\Admin;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use PragmaRX\Google2FA\Google2FA;
use Tests\Support\Fixtures;

it('redirects guests to login', function () {
    $this->get('/dashboard')->assertRedirect(route('login'));
});

it('logs in an active admin without 2FA, but only as far as enrolment', function () {
    $admin = Fixtures::admin([
        'email' => 'noauth@example.test',
        'password' => 'correct-horse',
        'two_factor_secret' => null,
        'two_factor_confirmed_at' => null,
    ]);

    $this->post('/login', ['email' => 'noauth@example.test', 'password' => 'correct-horse'])
        ->assertRedirect(route('dashboard'));

    expect(session('admin_id'))->toBe($admin->id);

    // 2FA is mandatory: the portal itself stays closed until they enrol.
    $this->get('/dashboard')->assertRedirect(route('account.security'));
});

it('rejects a wrong password', function () {
    Fixtures::admin(['email' => 'wrongpw@example.test', 'password' => 'correct-horse']);

    $this->post('/login', ['email' => 'wrongpw@example.test', 'password' => 'not-the-password'])
        ->assertSessionHasErrors('email');

    expect(session('admin_id'))->toBeNull();
});

it('refuses to log in an inactive admin', function () {
    Fixtures::admin([
        'email' => 'inactive@example.test',
        'password' => 'correct-horse',
        'status' => 'inactive',
    ]);

    $this->post('/login', ['email' => 'inactive@example.test', 'password' => 'correct-horse'])
        ->assertSessionHasErrors('email');

    expect(session('admin_id'))->toBeNull();
});

it('does NOT complete login when 2FA is enabled — only stashes a pending id', function () {
    $secret = (new Google2FA)->generateSecretKey();
    Fixtures::admin([
        'email' => 'twofa@example.test',
        'password' => 'correct-horse',
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
    ]);

    $this->post('/login', ['email' => 'twofa@example.test', 'password' => 'correct-horse'])
        ->assertRedirect(route('two-factor.challenge'));

    // The security-critical assertion: password alone must not grant a session.
    expect(session('admin_id'))->toBeNull()
        ->and(session('2fa.pending_admin_id'))->not->toBeNull();
});

it('completes login after a valid TOTP code', function () {
    $g = new Google2FA;
    $secret = $g->generateSecretKey();
    $admin = Fixtures::admin([
        'email' => 'totp@example.test',
        'password' => 'correct-horse',
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
    ]);

    $this->post('/login', ['email' => 'totp@example.test', 'password' => 'correct-horse']);

    $this->post('/two-factor-challenge', ['code' => $g->getCurrentOtp($secret)])
        ->assertRedirect(route('dashboard'));

    expect(session('admin_id'))->toBe($admin->id)
        ->and(session('2fa.pending_admin_id'))->toBeNull();
});

it('rejects an invalid TOTP code and grants no session', function () {
    $secret = (new Google2FA)->generateSecretKey();
    Fixtures::admin([
        'email' => 'badtotp@example.test',
        'password' => 'correct-horse',
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
    ]);

    $this->post('/login', ['email' => 'badtotp@example.test', 'password' => 'correct-horse']);

    $this->post('/two-factor-challenge', ['code' => '000000'])
        ->assertSessionHasErrors('code');

    expect(session('admin_id'))->toBeNull();
});

it('blocks the 2FA challenge when nothing is pending', function () {
    $this->get('/two-factor-challenge')->assertRedirect(route('login'));
});

it('consumes a recovery code exactly once', function () {
    $secret = (new Google2FA)->generateSecretKey();
    $admin = Fixtures::admin([
        'email' => 'recov@example.test',
        'password' => 'correct-horse',
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => ['aaaa-bbbb', 'cccc-dddd'],
    ]);

    $this->post('/login', ['email' => 'recov@example.test', 'password' => 'correct-horse']);
    $this->post('/two-factor-challenge', ['recovery_code' => 'aaaa-bbbb'])
        ->assertRedirect(route('dashboard'));

    expect(Admin::find($admin->id)->two_factor_recovery_codes)->toBe(['cccc-dddd']);

    // Re-using the same code must fail.
    $this->post('/logout');
    $this->post('/login', ['email' => 'recov@example.test', 'password' => 'correct-horse']);
    $this->post('/two-factor-challenge', ['recovery_code' => 'aaaa-bbbb'])
        ->assertSessionHasErrors('recovery_code');
});

it('clears the session on logout', function () {
    $admin = Fixtures::admin();
    Fixtures::actingAs($this, $admin)->post('/logout')->assertRedirect(route('login'));

    expect(session('admin_id'))->toBeNull();
});

it('invalidates the whole session and rotates the CSRF token on logout', function () {
    $admin = Fixtures::admin();

    Fixtures::actingAs($this, $admin)
        ->withSession(['_token' => 'pre-logout-token', 'leftover' => 'value'])
        ->post('/logout');

    expect(session()->token())->not->toBe('pre-logout-token')
        ->and(session('leftover'))->toBeNull();
});

// ── TOTP replay protection & pending-challenge limits ───────────────────────

function signInToChallenge($test, string $email): void
{
    $test->post('/login', ['email' => $email, 'password' => 'correct-horse'])
        ->assertRedirect(route('two-factor.challenge'));
}

it('refuses to accept the same TOTP code twice', function () {
    $g = new Google2FA;
    $secret = $g->generateSecretKey();
    Fixtures::admin([
        'email' => 'replay@example.test',
        'password' => 'correct-horse',
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
    ]);
    $code = $g->getCurrentOtp($secret);

    signInToChallenge($this, 'replay@example.test');
    $this->post('/two-factor-challenge', ['code' => $code])->assertRedirect(route('dashboard'));
    $this->post('/logout');

    signInToChallenge($this, 'replay@example.test');
    $this->post('/two-factor-challenge', ['code' => $code])->assertSessionHasErrors('code');

    expect(session('admin_id'))->toBeNull();
});

it('persists the time-step of the last accepted code', function () {
    $g = new Google2FA;
    $secret = $g->generateSecretKey();
    $admin = Fixtures::admin([
        'email' => 'timestep@example.test',
        'password' => 'correct-horse',
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
    ]);

    signInToChallenge($this, 'timestep@example.test');
    $this->post('/two-factor-challenge', ['code' => $g->getCurrentOtp($secret)]);

    expect(Admin::find($admin->id)->two_factor_last_used_timestep)->toBeGreaterThanOrEqual($g->getTimestamp() - 1);
});

it('discards the pending sign-in after 5 wrong codes', function () {
    $this->withoutMiddleware(ThrottleRequests::class);
    $g = new Google2FA;
    $secret = $g->generateSecretKey();
    Fixtures::admin([
        'email' => 'guess@example.test',
        'password' => 'correct-horse',
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
    ]);

    signInToChallenge($this, 'guess@example.test');

    for ($i = 1; $i < TwoFactorChallengeController::MAX_ATTEMPTS; $i++) {
        $this->post('/two-factor-challenge', ['code' => '000000'])->assertSessionHasErrors('code');
    }

    $this->post('/two-factor-challenge', ['code' => '000000'])->assertRedirect(route('login'));

    expect(session('2fa.pending_admin_id'))->toBeNull();

    // Even the right code is useless now — the password step must be redone.
    $this->post('/two-factor-challenge', ['code' => $g->getCurrentOtp($secret)])->assertRedirect(route('login'));
    expect(session('admin_id'))->toBeNull();
});

it('expires a pending sign-in after 5 minutes', function () {
    $g = new Google2FA;
    $secret = $g->generateSecretKey();
    Fixtures::admin([
        'email' => 'slow@example.test',
        'password' => 'correct-horse',
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
    ]);

    signInToChallenge($this, 'slow@example.test');

    $this->travel(6)->minutes();

    $this->post('/two-factor-challenge', ['code' => $g->getCurrentOtp($secret)])->assertRedirect(route('login'));

    expect(session('admin_id'))->toBeNull()
        ->and(session('2fa.pending_admin_id'))->toBeNull();
});

// ── Account enumeration & per-account lockout ────────────────────────────────

it('runs a password hash check even when the email has no account', function () {
    Hash::shouldReceive('check')->once()->andReturn(false);

    $this->post('/login', ['email' => 'nobody-'.Fixtures::uniq().'@example.test', 'password' => 'whatever'])
        ->assertSessionHasErrors(['email' => 'Invalid credentials or account inactive.']);
});

it('counts failed sign-ins per lower-cased email', function () {
    $email = 'Lockout-'.Fixtures::uniq().'@Example.test';

    $this->post('/login', ['email' => $email, 'password' => 'wrong']);

    expect(RateLimiter::attempts(LoginController::accountLockoutKey(strtolower($email))))->toBe(1);
});

it('locks an account after 10 failed sign-ins, even against the right password', function () {
    Fixtures::admin([
        'email' => 'locked@example.test',
        'password' => 'correct-horse',
        'two_factor_secret' => null,
        'two_factor_confirmed_at' => null,
    ]);
    $key = LoginController::accountLockoutKey('locked@example.test');

    for ($i = 0; $i < LoginController::MAX_ACCOUNT_ATTEMPTS; $i++) {
        RateLimiter::hit($key, LoginController::ACCOUNT_LOCKOUT_SECONDS);
    }

    $this->post('/login', ['email' => 'LOCKED@example.test', 'password' => 'correct-horse'])
        ->assertSessionHasErrors(['email' => 'Too many sign-in attempts. Please try again later.']);

    expect(session('admin_id'))->toBeNull();
});

it('clears the account lockout counter on a successful sign-in', function () {
    Fixtures::admin([
        'email' => 'recovering@example.test',
        'password' => 'correct-horse',
        'two_factor_secret' => null,
        'two_factor_confirmed_at' => null,
    ]);
    $key = LoginController::accountLockoutKey('recovering@example.test');
    RateLimiter::hit($key, LoginController::ACCOUNT_LOCKOUT_SECONDS);

    $this->post('/login', ['email' => 'recovering@example.test', 'password' => 'correct-horse'])
        ->assertRedirect(route('dashboard'));

    expect(RateLimiter::attempts($key))->toBe(0);
});
