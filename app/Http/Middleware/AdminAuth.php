<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use App\Support\AuthSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    /**
     * Routes an admin may still reach while their account is not yet secured
     * (no confirmed 2FA, or an initial password awaiting change): the account
     * security page and the actions on it. Logout sits outside this middleware.
     */
    private const ACCOUNT_SETUP_ROUTES = [
        'account.security',
        'account.password.update',
        'account.two-factor.enable',
        'account.two-factor.confirm',
        'account.two-factor.cancel',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! session('admin_id')) {
            return redirect()->route('login');
        }

        // Re-validate the account on every request: a deactivated (or deleted)
        // admin must lose access immediately, not merely at their next login.
        $admin = current_admin();
        if (! $admin || $admin->status !== 'active') {
            AuthSession::logout();

            return redirect()->route('login')
                ->with('error', 'Your account is no longer active. Please contact an administrator.');
        }

        if ($this->sessionHasExpired() || ! $this->sessionMatchesPassword($admin)) {
            AuthSession::logout();

            return redirect()->route('login')
                ->with('error', 'Your session has expired. Please sign in again.');
        }

        if (! $request->routeIs(...self::ACCOUNT_SETUP_ROUTES)) {
            if ($admin->must_change_password) {
                return redirect()->route('account.security')
                    ->with('warning', 'Choose a new password before continuing.');
            }

            if (! $admin->hasEnabledTwoFactor()) {
                return redirect()->route('account.security')
                    ->with('warning', 'Two-factor authentication is required for every staff account. Set it up to continue.');
            }
        }

        return $next($request);
    }

    /**
     * Absolute lifetime: a staff session ends a fixed time after sign-in no
     * matter how active it has been. A session without a sign-in time predates
     * this check and is treated as expired.
     */
    private function sessionHasExpired(): bool
    {
        $loggedInAt = session('admin_logged_in_at');
        $lifetimeSeconds = (int) config('session.absolute_lifetime_minutes', 720) * 60;

        return ! is_int($loggedInAt) || now()->getTimestamp() - $loggedInAt > $lifetimeSeconds;
    }

    /**
     * The session was opened with the admin's current password; once the
     * password changes, every session opened with the old one is rejected.
     */
    private function sessionMatchesPassword(Admin $admin): bool
    {
        $fingerprint = session('admin_password_fp');

        return is_string($fingerprint) && hash_equals(AuthSession::passwordFingerprint($admin), $fingerprint);
    }
}
