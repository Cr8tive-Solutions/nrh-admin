@extends('layouts.admin')

@section('title', 'Account Security')
@section('page-title', 'Account Security')
@section('page-subtitle', $admin->email)

@section('content')

<div class="max-w-2xl">

    {{-- ── Recovery codes one-shot display ── --}}
    @if($recoveryCodes)
    <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-5 mb-5">
        <div class="flex items-start gap-3">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--emerald-700); flex-shrink: 0; margin-top: 2px;"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
            <div class="flex-1">
                <h3 class="text-sm font-semibold text-emerald-900">Save these recovery codes</h3>
                <p class="text-xs text-emerald-800 mt-1">Each code can be used <strong>once</strong> to sign in if you lose access to your authenticator. Store them somewhere safe — they will not be shown again.</p>

                <div class="grid grid-cols-2 gap-2 mt-4 mb-3">
                    @foreach($recoveryCodes as $code)
                    <div class="bg-white border border-emerald-200 rounded px-3 py-2 font-mono text-sm text-gray-900 text-center select-all">{{ $code }}</div>
                    @endforeach
                </div>

                <button type="button" onclick="navigator.clipboard.writeText({{ json_encode(implode("\n", $recoveryCodes)) }}); this.textContent='Copied';"
                        class="text-xs text-emerald-700 hover:text-emerald-900 font-semibold">
                    Copy all to clipboard
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Two-factor authentication card ── --}}
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-start justify-between mb-1">
            <div>
                <h2 class="text-base font-semibold text-gray-900">Two-Factor Authentication</h2>
                <p class="text-xs text-gray-500 mt-1">Add an extra layer of security with a code from your authenticator app.</p>
            </div>
            @if($admin->hasEnabledTwoFactor())
                <span class="badge badge-green">Enabled</span>
            @elseif($pendingSetup)
                <span class="badge badge-yellow">Setup pending</span>
            @else
                <span class="badge badge-gray">Disabled</span>
            @endif
        </div>

        {{-- ── State: not enabled, no pending setup ── --}}
        @if(! $admin->hasEnabledTwoFactor() && ! $pendingSetup)
        <div class="border-t border-gray-100 mt-4 pt-4">
            <p class="text-sm text-gray-600 mb-3">
                When 2FA is enabled, you'll need a 6-digit code from an authenticator app (Google Authenticator, Authy, 1Password, etc.) every time you sign in.
            </p>
            <form method="POST" action="{{ route('account.two-factor.enable') }}">
                @csrf
                <button type="submit" class="nrh-btn nrh-btn-primary">Enable Two-Factor Authentication</button>
            </form>
        </div>
        @endif

        {{-- ── State: pending setup (secret generated, not confirmed) ── --}}
        @if($pendingSetup)
        <div class="border-t border-gray-100 mt-4 pt-4">
            <p class="text-sm text-gray-600 mb-4">
                <strong>Step 1:</strong> Scan this QR code with your authenticator app, or enter the setup key manually.
            </p>

            <div class="flex gap-6 items-start mb-5">
                <div class="bg-white border border-gray-200 rounded-lg p-3 flex-shrink-0">
                    <img src="data:image/svg+xml;base64,{{ $qrSvg }}" alt="2FA QR code" style="display: block; width: 220px; height: 220px;">
                </div>
                <div class="flex-1 text-sm">
                    <div class="text-xs text-gray-500 uppercase font-medium mb-1">Setup key</div>
                    <div class="font-mono text-sm bg-gray-50 border border-gray-200 rounded px-3 py-2 select-all break-all">{{ $admin->two_factor_secret }}</div>
                    <p class="text-xs text-gray-500 mt-2">Use this key if you can't scan the QR code.</p>
                </div>
            </div>

            <p class="text-sm text-gray-600 mb-3">
                <strong>Step 2:</strong> Enter the 6-digit code your authenticator now displays.
            </p>

            <form method="POST" action="{{ route('account.two-factor.confirm') }}" class="flex items-center gap-2 mb-4">
                @csrf
                <input type="text" name="code" inputmode="numeric" maxlength="6" pattern="[0-9]*"
                       placeholder="123456" autocomplete="one-time-code" autofocus
                       class="border border-gray-300 rounded-md px-3 py-2 text-sm font-mono w-32 text-center tracking-widest focus:outline-none focus:ring-2 focus:ring-emerald-600 {{ $errors->has('code') ? 'border-red-400 bg-red-50' : '' }}">
                <button type="submit" class="nrh-btn nrh-btn-primary">Confirm &amp; Enable</button>
            </form>
            @error('code')
                <p class="text-xs text-red-600 mb-3">{{ $message }}</p>
            @enderror

            <form method="POST" action="{{ route('account.two-factor.cancel') }}">
                @csrf @method('DELETE')
                <button type="submit" class="text-xs text-gray-500 hover:text-gray-700 underline">Cancel setup</button>
            </form>
        </div>
        @endif

        {{-- ── State: enabled ── --}}
        @if($admin->hasEnabledTwoFactor())
        <div class="border-t border-gray-100 mt-4 pt-4 text-sm text-gray-600">
            <p>2FA was enabled on <strong>{{ $admin->two_factor_confirmed_at->format('d M Y, H:i') }}</strong>.</p>
            <p class="mt-1">Recovery codes remaining: <strong>{{ count($admin->two_factor_recovery_codes ?? []) }} / 8</strong></p>
        </div>
        @endif
    </div>

    {{-- ── Recovery code regeneration & disable (only when enabled) ── --}}
    @if($admin->hasEnabledTwoFactor())
    <div class="bg-white rounded-lg border border-gray-200 p-6 mt-4">
        <h2 class="text-base font-semibold text-gray-900">Manage Recovery Codes</h2>
        <p class="text-xs text-gray-500 mt-1 mb-4">Regenerate your recovery codes. Existing codes will stop working immediately.</p>

        <form method="POST" action="{{ route('account.two-factor.regenerate-codes') }}" class="flex items-end gap-2">
            @csrf
            <div class="flex flex-col gap-1 flex-1 max-w-xs">
                <label class="text-xs font-medium text-gray-600 uppercase tracking-wide">Current password</label>
                <input type="password" name="password" required autocomplete="current-password"
                       class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600 {{ $errors->has('password') && session()->has('_old_input.regenerate_password_field_marker') ? 'border-red-400' : '' }}">
            </div>
            <button type="submit" class="nrh-btn nrh-btn-ghost">Regenerate Codes</button>
        </form>
    </div>

    <div class="bg-white rounded-lg border border-red-200 p-6 mt-4">
        <h2 class="text-base font-semibold text-red-700">Disable Two-Factor Authentication</h2>
        <p class="text-xs text-gray-500 mt-1 mb-4">Removing 2FA leaves your account protected only by your password. Re-enter your password to confirm.</p>

        <form method="POST" action="{{ route('account.two-factor.disable') }}" class="flex items-end gap-2">
            @csrf @method('DELETE')
            <div class="flex flex-col gap-1 flex-1 max-w-xs">
                <label class="text-xs font-medium text-gray-600 uppercase tracking-wide">Current password</label>
                <input type="password" name="password" required autocomplete="current-password"
                       class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600 {{ $errors->has('password') ? 'border-red-400' : '' }}">
                @error('password')
                    <span class="text-xs text-red-600">{{ $message }}</span>
                @enderror
            </div>
            <button type="submit" class="nrh-btn" style="background: #c4453a; color: white;"
                    onclick="return confirm('Disable 2FA for your account?');">
                Disable 2FA
            </button>
        </form>
    </div>
    @endif

</div>

@endsection
