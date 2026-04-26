<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminAuditLog;
use App\Support\AuthSession;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorChallengeController extends Controller
{
    public function show(Request $request)
    {
        if (! session('2fa.pending_admin_id')) {
            return redirect()->route('login');
        }
        return view('auth.two-factor-challenge');
    }

    public function verify(Request $request)
    {
        $pendingId = session('2fa.pending_admin_id');
        if (! $pendingId) {
            return redirect()->route('login');
        }

        $admin = Admin::find($pendingId);
        if (! $admin || ! $admin->hasEnabledTwoFactor() || $admin->status !== 'active') {
            session()->forget('2fa.pending_admin_id');
            return redirect()->route('login')->withErrors(['code' => 'Session expired. Please sign in again.']);
        }

        $data = $request->validate([
            'code'          => 'nullable|string',
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
                throw ValidationException::withMessages(['recovery_code' => 'That recovery code is invalid or already used.']);
            }
            AdminAuditLog::record('auth.2fa_recovery_used', $admin, [
                'remaining_codes' => count($admin->two_factor_recovery_codes ?? []),
            ]);
        } else {
            $google2fa = new Google2FA();
            // window=1 accepts the previous, current, and next 30s code (clock skew).
            $valid = $google2fa->verifyKey($admin->two_factor_secret, $code, 1);
            if (! $valid) {
                AdminAuditLog::record('auth.2fa_failed', $admin);
                throw ValidationException::withMessages(['code' => 'Invalid authenticator code.']);
            }
            AdminAuditLog::record('auth.2fa_passed', $admin);
        }

        AuthSession::login($admin);
        AdminAuditLog::record('auth.login', $admin);
        return redirect()->route('dashboard');
    }
}
