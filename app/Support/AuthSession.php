<?php

namespace App\Support;

use App\Models\Admin;

class AuthSession
{
    public static function login(Admin $admin): void
    {
        session([
            'admin_id'    => $admin->id,
            'admin_name'  => $admin->name,
            'admin_role'  => $admin->role,
            'admin_email' => $admin->email,
        ]);
        session()->forget(['2fa.pending_admin_id']);
        session()->regenerate();
    }

    public static function logout(): void
    {
        session()->forget(['admin_id', 'admin_name', 'admin_role', 'admin_email', '2fa.pending_admin_id']);
        session()->regenerate();
    }
}
