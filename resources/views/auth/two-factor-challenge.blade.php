<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-factor verification — KYRO Admin</title>
    <link rel="icon" type="image/png" href="/images/nrh-logo.png">
    <script>(function(){var t=localStorage.getItem('nrh-theme');if(t==='dark')document.documentElement.setAttribute('data-theme','dark');}());</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter+Tight:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('auth.partials.shell-styles')
    <style>
        [x-cloak] { display: none !important; }

        /* Trust chain */
        .al-chain {
            display: flex; flex-direction: column; gap: 12px;
            border-top: 1px solid rgba(212,175,55,0.18);
            border-bottom: 1px solid rgba(212,175,55,0.18);
            padding: 18px 0; margin-top: 24px;
        }
        .al-chain-row { display: flex; align-items: center; gap: 12px; font-size: 12px; color: rgba(233,239,235,0.45); }
        .al-chain-row .n { font-family: 'JetBrains Mono', monospace; font-size: 10px; letter-spacing: 0.14em; color: rgba(233,239,235,0.5); width: 72px; }
        .al-chain-row .pip { width: 6px; height: 6px; border-radius: 50%; background: rgba(212,175,55,0.3); flex-shrink: 0; margin: 0 1px; }
        .al-chain-row.done { color: rgba(233,239,235,0.7); }
        .al-chain-row.done .pip { background: #d4af37; }
        .al-chain-row.done .ok { color: #10b981; margin-left: 4px; }
        .al-chain-row.current { color: #fff; font-weight: 600; }
        .al-chain-row.current .pip { width: 8px; height: 8px; margin: 0; background: #d4af37; animation: al-pulse 2s ease-in-out infinite; }
        @keyframes al-pulse {
            0%, 100% { box-shadow: 0 0 0 4px rgba(212,175,55,0.2); }
            50%      { box-shadow: 0 0 0 8px rgba(212,175,55,0.08); }
        }

        /* Code boxes */
        .otp-wrap { display: flex; gap: 8px; justify-content: space-between; }
        .otp-input {
            width: 54px; height: 64px;
            text-align: center;
            font-family: 'Fraunces', serif;
            font-size: 28px; font-weight: 500;
            border: 1px solid var(--line);
            background: var(--card);
            border-radius: 10px;
            color: var(--ink-900);
            outline: none;
            transition: all 150ms ease;
        }
        .otp-input:focus { border-color: var(--emerald-600); box-shadow: 0 0 0 3px rgba(5,150,105,0.14); transform: translateY(-1px); }
        .otp-input.filled { border-color: var(--emerald-600); background: var(--emerald-50); color: var(--emerald-800); }
        .otp-wrap.has-error .otp-input { border-color: var(--danger); }
        .otp-sep { display: grid; place-items: center; color: var(--ink-300); font-family: 'Fraunces', serif; font-size: 28px; user-select: none; padding: 0 2px; }

        .alert-error {
            display: flex; align-items: center; gap: 10px;
            background: rgba(196,69,58,0.08); border: 1px solid rgba(196,69,58,0.25); border-left: 3px solid var(--danger);
            border-radius: 6px; padding: 10px 14px;
            font-size: 13px; color: var(--danger); font-weight: 500;
        }
        .alert-error svg { width: 15px; height: 15px; flex-shrink: 0; }

        .action-row { display: flex; gap: 10px; }
        .btn-cancel {
            flex: 0 0 auto;
            padding: 13px 18px;
            border: 1px solid var(--line); border-radius: 8px;
            background: var(--card); color: var(--ink-900);
            font-family: inherit; font-size: 14px; font-weight: 600;
            cursor: pointer;
            transition: border-color 120ms ease, color 120ms ease;
        }
        .btn-cancel:hover { border-color: var(--danger); color: var(--danger); }
        .action-row .btn-auth { flex: 1; }

        .toggle-link {
            font-size: 12px; color: var(--emerald-700); font-weight: 600;
            background: none; border: none; cursor: pointer; padding: 0; font-family: inherit;
        }
        .toggle-link:hover { text-decoration: underline; }
        .help-row { display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: var(--ink-500); }

        .recovery-input {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid var(--line); border-radius: 8px;
            background: var(--card);
            font-family: 'JetBrains Mono', monospace; font-size: 16px; letter-spacing: 0.12em; text-align: center;
            color: var(--ink-900); outline: none;
        }
        .recovery-input:focus { border-color: var(--emerald-600); box-shadow: 0 0 0 3px rgba(5,150,105,0.12); }
        .recovery-input.has-error { border-color: var(--danger); background: rgba(196,69,58,0.06); }
    </style>
</head>
<body x-data="{
    dark: document.documentElement.getAttribute('data-theme') === 'dark',
    recovery: {{ $errors->has('recovery_code') ? 'true' : 'false' }},
    toggleDark() {
        this.dark = !this.dark;
        document.documentElement.setAttribute('data-theme', this.dark ? 'dark' : 'light');
        localStorage.setItem('nrh-theme', this.dark ? 'dark' : 'light');
    }
}">

<div class="auth-shell">

    {{-- ── Left panel ── --}}
    <aside class="auth-left">
        <div class="al-brand">
            <img src="/images/nrh-logo.png" alt="NRH" class="al-logo">
            <div>
                <div class="al-brand-name">KYRO</div>
                <div class="al-brand-sub">Admin Portal</div>
            </div>
        </div>

        <img src="/images/nrh-logo.png" alt="" class="al-seal">

        <div class="al-hero">
            <div class="al-eyebrow">Zero-trust verification</div>
            <h2>One more step before <em>full access</em>.</h2>
            <p>KYRO Admin can open every customer's candidates, reports and invoices. A second factor makes sure that access stays with you.</p>
            <div class="al-chain">
                <div class="al-chain-row done"><span class="pip"></span><span class="n">LAYER 01</span>Password <span class="ok">✓ verified</span></div>
                <div class="al-chain-row current"><span class="pip"></span><span class="n">LAYER 02</span>Two-factor authentication</div>
                <div class="al-chain-row"><span class="pip"></span><span class="n">LAYER 03</span>Admin workspace</div>
            </div>
        </div>

        <div class="al-foot">
            <span>NRH Intelligence Sdn. Bhd.</span>
            <div class="al-badges">
                <span class="al-badge"><span class="dot"></span> System online</span>
            </div>
        </div>
    </aside>

    {{-- ── Right panel ── --}}
    <div class="auth-right">
        <button class="theme-toggle" @click="toggleDark()" :title="dark ? 'Light mode' : 'Dark mode'">
            <svg x-show="!dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="5"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
            <svg x-show="dark" x-cloak viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        </button>
        <div class="auth-right-top">
            Internal staff access only.
        </div>

        <div class="auth-card">
            <div>
                <div class="auth-kicker">
                    <span class="step"><span class="sdot"></span>Step 2 · 2</span>
                    <span x-show="!recovery">Authenticator app</span>
                    <span x-show="recovery" x-cloak>Recovery code</span>
                </div>
                <h1 class="auth-title" style="margin-top: 14px;" x-show="!recovery">Enter your <em>6-digit</em> code.</h1>
                <h1 class="auth-title" style="margin-top: 14px;" x-show="recovery" x-cloak>Use a <em>recovery</em> code.</h1>
                <p class="auth-sub" style="margin-top: 8px;" x-show="!recovery">Open your authenticator app and enter the current code for <b>KYRO Admin</b> (or NRH Admin, if you set it up before the rename).</p>
                <p class="auth-sub" style="margin-top: 8px;" x-show="recovery" x-cloak>Enter one of the recovery codes you saved when you turned on 2FA. Each code works only once.</p>
            </div>

            @if ($errors->any())
                <div class="alert-error" role="alert">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('two-factor.verify') }}" id="otpForm" style="display: flex; flex-direction: column; gap: 16px;">
                @csrf
                <input type="hidden" name="code" id="otpCodeInput" :disabled="recovery">

                <div class="field" x-show="!recovery">
                    <div class="field-label">Verification code</div>
                    <div class="otp-wrap {{ $errors->has('code') ? 'has-error' : '' }}" id="otpWrap">
                        <input class="otp-input" maxlength="1" inputmode="numeric" autocomplete="one-time-code" aria-label="Digit 1">
                        <input class="otp-input" maxlength="1" inputmode="numeric" aria-label="Digit 2">
                        <input class="otp-input" maxlength="1" inputmode="numeric" aria-label="Digit 3">
                        <div class="otp-sep">·</div>
                        <input class="otp-input" maxlength="1" inputmode="numeric" aria-label="Digit 4">
                        <input class="otp-input" maxlength="1" inputmode="numeric" aria-label="Digit 5">
                        <input class="otp-input" maxlength="1" inputmode="numeric" aria-label="Digit 6">
                    </div>
                </div>

                <div class="field" x-show="recovery" x-cloak>
                    <label class="field-label" for="recovery_code">Recovery code</label>
                    <input type="text" id="recovery_code" name="recovery_code" :disabled="!recovery"
                           placeholder="abcde-12345" autocomplete="one-time-code"
                           class="recovery-input {{ $errors->has('recovery_code') ? 'has-error' : '' }}">
                </div>

                <div class="action-row">
                    <button type="submit" form="cancelForm" class="btn-cancel">Cancel</button>
                    <button type="submit" class="btn-auth">
                        Verify and sign in
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </button>
                </div>
            </form>

            <div class="help-row">
                <span x-show="!recovery">Lost your device?</span>
                <span x-show="recovery" x-cloak>Have your phone?</span>
                <button type="button" class="toggle-link" @click="recovery = !recovery; $nextTick(() => recovery ? document.getElementById('recovery_code').focus() : document.querySelector('#otpWrap .otp-input').focus())">
                    <span x-show="!recovery">Use a recovery code</span>
                    <span x-show="recovery" x-cloak>Use authenticator code</span>
                </button>
            </div>

            <div class="session-strip">
                <span>Session · <b>KYRO Admin</b></span>
                <span>Encrypted · <b>TLS 1.3</b></span>
            </div>
        </div>
    </div>

</div>

<form method="POST" action="{{ route('logout') }}" id="cancelForm" hidden>
    @csrf
</form>

<script>
    (function () {
        const inputs = Array.from(document.querySelectorAll('#otpWrap .otp-input'));
        const form = document.getElementById('otpForm');
        const hidden = document.getElementById('otpCodeInput');
        const code = () => inputs.map(i => i.value).join('');
        const submitIfComplete = () => {
            if (code().length === 6) { hidden.value = code(); form.requestSubmit(); }
        };

        inputs.forEach((input, i) => {
            input.addEventListener('input', (e) => {
                const digit = e.target.value.replace(/[^0-9]/g, '').slice(-1);
                e.target.value = digit;
                e.target.classList.toggle('filled', digit !== '');
                if (digit && inputs[i + 1]) { inputs[i + 1].focus(); }
                submitIfComplete();
            });
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !e.target.value && inputs[i - 1]) { inputs[i - 1].focus(); }
            });
        });

        document.addEventListener('paste', (e) => {
            if (document.activeElement && document.activeElement.id === 'recovery_code') { return; }
            const digits = (e.clipboardData.getData('text') || '').replace(/[^0-9]/g, '').slice(0, 6);
            if (!digits) { return; }
            e.preventDefault();
            digits.split('').forEach((d, k) => { if (inputs[k]) { inputs[k].value = d; inputs[k].classList.add('filled'); } });
            const next = inputs[Math.min(digits.length, inputs.length - 1)];
            if (next) { next.focus(); }
            submitIfComplete();
        });

        form.addEventListener('submit', () => { hidden.value = code(); });

        if (inputs[0] && !document.querySelector('.recovery-input.has-error')) { inputs[0].focus(); }
    })();
</script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
