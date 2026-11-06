<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminAuditLog;
use App\Support\AuthSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class TwoFactorChallengeController extends Controller
{
    /** Wrong codes allowed per pending sign-in before it is thrown away. */
    public const MAX_ATTEMPTS = 5;

    /** How long a password-verified sign-in waits for its code, in seconds. */
    public const PENDING_TTL_SECONDS = 300;

    public function show(Request $request)
    {
        if (! session('2fa.pending_admin_id')) {
            return redirect()->route('login');
        }

        if ($this->pendingHasExpired()) {
            return $this->abandon('Your sign-in timed out. Please sign in again.');
        }

        return view('auth.two-factor-challenge');
    }

    public function verify(Request $request)
    {
        $pendingId = session('2fa.pending_admin_id');
        if (! $pendingId) {
            return redirect()->route('login');
        }

        if ($this->pendingHasExpired()) {
            return $this->abandon('Your sign-in timed out. Please sign in again.');
        }

        $admin = Admin::find($pendingId);
        if (! $admin || ! $admin->hasEnabledTwoFactor() || $admin->status !== 'active') {
            return $this->abandon('Session expired. Please sign in again.');
        }

        $data = $request->validate([
            'code' => 'nullable|string',
            'recovery_code' => 'nullable|string',
        ]);

        $code = trim((string) ($data['code'] ?? ''));
        $recovery = trim((string) ($data['recovery_code'] ?? ''));

        if ($code === '' && $recovery === '') {
            throw ValidationException::withMessages(['code' => 'Enter your authenticator code or a recovery code.']);
        }

        if ($recovery !== '') {
            if (! $admin->consumeRecoveryCode($recovery)) {
                AdminAuditLog::record('auth.2fa_recovery_failed', $admin);

                return $this->failedAttempt($admin, 'recovery_code', 'That recovery code is invalid or already used.');
            }
            AdminAuditLog::record('auth.2fa_recovery_used', $admin, [
                'remaining_codes' => count($admin->two_factor_recovery_codes ?? []),
            ]);
        } else {
            // Accepts the previous, current and next 30s step (clock skew), but
            // never a step at or before the last code used — no replays.
            if (! $admin->consumeTotpCode($code)) {
                AdminAuditLog::record('auth.2fa_failed', $admin);

                return $this->failedAttempt($admin, 'code', 'Invalid authenticator code.');
            }
            AdminAuditLog::record('auth.2fa_passed', $admin);
        }

        RateLimiter::clear(LoginController::accountLockoutKey($admin->email));
        AuthSession::login($admin);
        AdminAuditLog::record('auth.login', $admin);

        return redirect()->route('dashboard');
    }

    private function pendingHasExpired(): bool
    {
        $pendingAt = session('2fa.pending_at');

        return ! is_int($pendingAt) || now()->getTimestamp() - $pendingAt > self::PENDING_TTL_SECONDS;
    }

    /**
     * Count a wrong code against this pending sign-in. After MAX_ATTEMPTS the
     * pending state is discarded (and counts toward the account lockout), so
     * guessing further means getting through the password step again.
     */
    private function failedAttempt(Admin $admin, string $field, string $message): RedirectResponse
    {
        $attempts = (int) session('2fa.attempts', 0) + 1;

        if ($attempts >= self::MAX_ATTEMPTS) {
            RateLimiter::hit(LoginController::accountLockoutKey($admin->email), LoginController::ACCOUNT_LOCKOUT_SECONDS);
            AdminAuditLog::record('auth.2fa_attempts_exceeded', $admin);

            return $this->abandon('Too many invalid codes. Please sign in again.');
        }

        session(['2fa.attempts' => $attempts]);

        throw ValidationException::withMessages([$field => $message]);
    }

    private function abandon(string $message): RedirectResponse
    {
        session()->forget(['2fa.pending_admin_id', '2fa.pending_at', '2fa.attempts']);

        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
