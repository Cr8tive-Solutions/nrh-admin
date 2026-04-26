<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminAuditLog;
use App\Support\AuthSession;
use Illuminate\Http\Request;

class LoginController extends Controller
{
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
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $admin = Admin::where('email', $data['email'])->where('status', 'active')->first();

        if (! $admin || ! $admin->verifyPassword($data['password'])) {
            AdminAuditLog::record('auth.login_failed', $admin, [
                'attempted_email' => $data['email'],
                'reason'          => $admin ? 'wrong_password' : 'unknown_or_inactive_account',
            ]);
            return back()->withErrors(['email' => 'Invalid credentials or account inactive.'])->withInput();
        }

        if ($admin->hasEnabledTwoFactor()) {
            // Stash pending admin id; do NOT complete login until 2FA verified.
            session(['2fa.pending_admin_id' => $admin->id]);
            AdminAuditLog::record('auth.password_ok_2fa_pending', $admin);
            return redirect()->route('two-factor.challenge');
        }

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
}
