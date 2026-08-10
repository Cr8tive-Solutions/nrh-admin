<?php

use App\Models\Admin;
use PragmaRX\Google2FA\Google2FA;
use Tests\Support\Fixtures;

it('redirects guests to login', function () {
    $this->get('/dashboard')->assertRedirect(route('login'));
});

it('logs in an active admin without 2FA', function () {
    $admin = Fixtures::admin(['email' => 'noauth@example.test', 'password' => 'correct-horse']);

    $this->post('/login', ['email' => 'noauth@example.test', 'password' => 'correct-horse'])
        ->assertRedirect(route('dashboard'));

    expect(session('admin_id'))->toBe($admin->id);
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
