<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminAuditLog;
use App\Support\AuthSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    /** Failed sign-ins allowed per account before it is locked. */
    public const MAX_ACCOUNT_ATTEMPTS = 10;

    /** How long an account lockout lasts, in seconds. */
    public const ACCOUNT_LOCKOUT_SECONDS = 900;

    /**
     * Checked against the submitted password when no active account matches,
     * so an unknown email costs the same bcrypt work as a wrong password and
     * response timing does not reveal which emails have accounts.
     */
    private const DUMMY_PASSWORD_HASH = '$2y$12$H6HE1vwCqAiikgxmLaT.uOLpWmQXha8tW4t.RR7vOJtEOSl1ao9xy';

    public function showLogin()
    {
        if (session('admin_id')) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Per-account lockout, on top of the per-IP route throttle: stops a
        // distributed guess against one account. Counted for unknown emails
        // too, so a lockout does not reveal whether the account exists.
        $lockoutKey = self::accountLockoutKey($data['email']);
        if (RateLimiter::tooManyAttempts($lockoutKey, self::MAX_ACCOUNT_ATTEMPTS)) {
            AdminAuditLog::record('auth.login_locked_out', null, ['attempted_email' => $data['email']]);

            return back()->withErrors(['email' => 'Too many sign-in attempts. Please try again later.'])->withInput();
        }

        $admin = Admin::where('email', $data['email'])->where('status', 'active')->first();

        if (! $admin) {
            Hash::check($data['password'], self::DUMMY_PASSWORD_HASH);
        }

        if (! $admin || ! $admin->verifyPassword($data['password'])) {
            RateLimiter::hit($lockoutKey, self::ACCOUNT_LOCKOUT_SECONDS);
            AdminAuditLog::record('auth.login_failed', $admin, [
                'attempted_email' => $data['email'],
                'reason' => $admin ? 'wrong_password' : 'unknown_or_inactive_account',
            ]);

            return back()->withErrors(['email' => 'Invalid credentials or account inactive.'])->withInput();
        }

        if ($admin->hasEnabledTwoFactor()) {
            // Stash pending admin id; do NOT complete login until 2FA verified.
            session([
                '2fa.pending_admin_id' => $admin->id,
                '2fa.pending_at' => now()->getTimestamp(),
                '2fa.attempts' => 0,
            ]);
            AdminAuditLog::record('auth.password_ok_2fa_pending', $admin);

            return redirect()->route('two-factor.challenge');
        }

        RateLimiter::clear($lockoutKey);
        AuthSession::login($admin);
        AdminAuditLog::record('auth.login', $admin);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        $admin = current_admin();
        AdminAuditLog::record('auth.logout', $admin);
        AuthSession::logout();

        return redirect()->route('login');
    }

    /** Rate-limiter key for an account's failed sign-ins (case-insensitive email). */
    public static function accountLockoutKey(string $email): string
    {
        return 'admin-login:'.Str::lower(trim($email));
    }
}
