<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminAuditLog;
use App\Support\AuthSession;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

class SecurityController extends Controller
{
    public function show(Request $request)
    {
        $admin = current_admin();

        // Pending setup: secret saved but not yet confirmed.
        $pendingSetup = $admin->two_factor_secret && ! $admin->two_factor_confirmed_at;
        $qrSvg = null;

        if ($pendingSetup) {
            $google2fa = new Google2FA;
            $otpauthUrl = $google2fa->getQRCodeUrl(
                config('app.name', 'KYRO Admin'),
                $admin->email,
                $admin->two_factor_secret,
            );

            $renderer = new ImageRenderer(new RendererStyle(220, 1), new SvgImageBackEnd);
            $writer = new Writer($renderer);
            $qrSvg = base64_encode($writer->writeString($otpauthUrl));
        }

        $recoveryCodes = $request->session()->pull('two_factor.recovery_codes');

        return view('account.security', compact('admin', 'pendingSetup', 'qrSvg', 'recoveryCodes'));
    }

    /**
     * Begin setup: generate and save secret (unconfirmed).
     */
    public function enable(Request $request)
    {
        $admin = current_admin();

        if ($admin->hasEnabledTwoFactor()) {
            return back()->withErrors(['enable' => 'Two-factor authentication is already enabled.']);
        }

        $google2fa = new Google2FA;
        $admin->two_factor_secret = $google2fa->generateSecretKey();
        $admin->two_factor_confirmed_at = null;
        $admin->two_factor_last_used_timestep = null;
        $admin->save();

        AdminAuditLog::record('two_factor.setup_started', $admin);

        return redirect()->route('account.security');
    }

    /**
     * Confirm setup: verify a code from the user's authenticator,
     * then mark confirmed and generate recovery codes.
     */
    public function confirm(Request $request)
    {
        $admin = current_admin();

        if (! $admin->two_factor_secret || $admin->two_factor_confirmed_at) {
            return back()->withErrors(['code' => 'Setup is not in progress.']);
        }

        $data = $request->validate([
            'code' => 'required|string',
        ]);

        if (! $admin->consumeTotpCode($data['code'])) {
            throw ValidationException::withMessages(['code' => 'Invalid code. Try the next one your app displays.']);
        }

        $codes = Admin::generateRecoveryCodes();
        $admin->two_factor_recovery_codes = $codes;
        $admin->two_factor_confirmed_at = now();
        $admin->save();

        AdminAuditLog::record('two_factor.enabled', $admin);

        // One-shot flash so codes show on the next page render only.
        $request->session()->flash('two_factor.recovery_codes', $codes);

        return redirect()->route('account.security')->with('success', 'Two-factor authentication enabled.');
    }

    /**
     * Cancel an in-progress setup before it's confirmed.
     */
    public function cancelSetup(Request $request)
    {
        $admin = current_admin();
        if ($admin->two_factor_confirmed_at) {
            return back();
        }
        $admin->two_factor_secret = null;
        $admin->save();
        AdminAuditLog::record('two_factor.setup_cancelled', $admin);

        return redirect()->route('account.security');
    }

    /**
     * Disable 2FA — requires the current password and a current authenticator
     * code (or an unused recovery code). 2FA is mandatory for staff, so this is
     * a reset: AdminAuth sends the admin straight back to enrolment.
     */
    public function disable(Request $request)
    {
        $admin = current_admin();

        if (! $admin->hasEnabledTwoFactor()) {
            return back();
        }

        $data = $request->validate([
            'password' => 'required|string',
            'code' => 'required|string',
        ], [
            'code.required' => 'Enter a code from your authenticator app or a recovery code.',
        ]);

        if (! $admin->verifyPassword($data['password'])) {
            AdminAuditLog::record('two_factor.disable_password_failed', $admin);
            throw ValidationException::withMessages(['password' => 'Incorrect password.']);
        }

        $code = trim($data['code']);
        if (! $admin->consumeTotpCode($code) && ! $admin->consumeRecoveryCode($code)) {
            AdminAuditLog::record('two_factor.disable_code_failed', $admin);
            throw ValidationException::withMessages(['code' => 'Invalid authenticator or recovery code.']);
        }

        $admin->two_factor_secret = null;
        $admin->two_factor_recovery_codes = null;
        $admin->two_factor_confirmed_at = null;
        $admin->two_factor_last_used_timestep = null;
        $admin->save();

        AdminAuditLog::record('two_factor.disabled', $admin);

        return redirect()->route('account.security')
            ->with('success', 'Two-factor authentication has been reset. Set it up again with your new device to continue.');
    }

    /**
     * Regenerate recovery codes — requires current password.
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        $admin = current_admin();

        if (! $admin->hasEnabledTwoFactor()) {
            return back();
        }

        $data = $request->validate([
            'password' => 'required|string',
        ]);

        if (! $admin->verifyPassword($data['password'])) {
            throw ValidationException::withMessages(['password' => 'Incorrect password.']);
        }

        $codes = Admin::generateRecoveryCodes();
        $admin->two_factor_recovery_codes = $codes;
        $admin->save();

        AdminAuditLog::record('two_factor.recovery_codes_regenerated', $admin);

        $request->session()->flash('two_factor.recovery_codes', $codes);

        return redirect()->route('account.security')->with('success', 'New recovery codes generated.');
    }

    /**
     * Self-service password change. On success every other session of this
     * admin is signed out (AdminAuth checks the password fingerprint) and this
     * session's id is rotated. Also clears a pending forced change.
     */
    public function updatePassword(Request $request)
    {
        $admin = current_admin();

        $data = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', 'different:current_password', Admin::passwordRule()],
        ], [
            'password.different' => 'Choose a password different from your current one.',
        ]);

        if (! $admin->verifyPassword($data['current_password'])) {
            AdminAuditLog::record('auth.password_change_failed', $admin);
            throw ValidationException::withMessages(['current_password' => 'Incorrect password.'])
                ->errorBag('updatePassword');
        }

        $admin->password = $data['password'];
        $admin->must_change_password = false;
        $admin->save();

        AuthSession::refreshAfterPasswordChange($admin);

        AdminAuditLog::record('auth.password_changed', $admin);

        return redirect()->route('account.security')
            ->with('success', 'Password changed. Any other signed-in sessions have been signed out.');
    }
}
