<?php

namespace App\Support;

use App\Models\Admin;

class AuthSession
{
    public static function login(Admin $admin): void
    {
        session([
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'admin_role' => $admin->role,
            'admin_email' => $admin->email,
            'admin_logged_in_at' => now()->getTimestamp(),
            'admin_password_fp' => self::passwordFingerprint($admin),
        ]);
        session()->forget(['2fa.pending_admin_id', '2fa.pending_at', '2fa.attempts']);
        session()->regenerate();
    }

    /**
     * End the session entirely: drop every key, issue a new session id and a
     * new CSRF token, so nothing from the signed-in session can be reused.
     */
    public static function logout(): void
    {
        session()->invalidate();
        session()->regenerateToken();
    }

    /**
     * A fingerprint of the admin's current password hash, stored in the session
     * at sign-in. AdminAuth compares it on every request, so a password change
     * ends every other session whatever the session driver.
     */
    public static function passwordFingerprint(Admin $admin): string
    {
        return hash_hmac('sha256', (string) $admin->getRawOriginal('password'), (string) config('app.key'));
    }

    /**
     * Re-pin the current session to the admin's new password and rotate its id:
     * this session stays signed in while every other one fails the check.
     */
    public static function refreshAfterPasswordChange(Admin $admin): void
    {
        session(['admin_password_fp' => self::passwordFingerprint($admin)]);
        session()->regenerate(true);
    }
}
