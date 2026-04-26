<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-factor verification — NRH Admin</title>
    <link rel="icon" type="image/png" href="/images/nrh-logo.png">
    <script>(function(){var t=localStorage.getItem('nrh-theme');if(t==='dark')document.documentElement.setAttribute('data-theme','dark');}());</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Inter+Tight:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; min-height: 100vh; }
        body {
            font-family: 'Inter Tight', ui-sans-serif, system-ui, sans-serif;
            -webkit-font-smoothing: antialiased; font-size: 14px; line-height: 1.45;
            background: var(--paper);
            display: grid; place-items: center; padding: 32px;
        }
        .tfa-card {
            width: 100%; max-width: 420px;
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            padding: 36px;
            display: flex; flex-direction: column; gap: 22px;
        }
        .tfa-brand { display: flex; align-items: center; gap: 10px; }
        .tfa-brand img { width: 32px; height: 32px; object-fit: contain; }
        .tfa-brand-text { font-family: 'Fraunces', serif; font-weight: 600; font-size: 14px; color: var(--ink-900); }
        .tfa-brand-text em { font-style: italic; color: var(--emerald-700); }
        .tfa-kicker {
            font-family: 'JetBrains Mono', monospace; font-size: 10px;
            text-transform: uppercase; letter-spacing: 0.18em;
            color: var(--emerald-700); display: flex; align-items: center; gap: 8px;
        }
        .tfa-kicker .dot { width: 6px; height: 6px; border-radius: 50%; background: #d4af37; }
        .tfa-title {
            font-family: 'Fraunces', serif; font-size: 26px; font-weight: 500;
            line-height: 1.15; letter-spacing: -0.01em;
            margin: 0; color: var(--ink-900);
        }
        .tfa-title em { font-style: italic; color: var(--emerald-700); }
        .tfa-sub { font-size: 13px; color: var(--ink-500); margin: 0; line-height: 1.5; }

        .field { display: flex; flex-direction: column; gap: 6px; }
        .field-label {
            font-size: 10px; text-transform: uppercase; letter-spacing: 0.14em;
            color: var(--ink-500); font-weight: 600;
        }
        .tfa-input {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid var(--line);
            background: var(--card);
            border-radius: 8px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 18px; letter-spacing: 0.3em;
            color: var(--ink-900); outline: none;
            text-align: center;
            transition: border-color 120ms ease, box-shadow 120ms ease;
        }
        .tfa-input:focus { border-color: var(--emerald-600); box-shadow: 0 0 0 3px rgba(5,150,105,0.12); }
        .tfa-input.recovery { font-size: 14px; letter-spacing: 0.1em; }
        .tfa-input.has-error { border-color: var(--danger); background: rgba(196,69,58,0.06); }
        .field-error { font-size: 11px; color: var(--danger); margin-top: 2px; }

        .btn-tfa {
            width: 100%; padding: 12px 16px;
            background: var(--emerald-700); color: #fff;
            border: none; border-radius: 8px;
            font-family: inherit; font-size: 14px; font-weight: 600;
            cursor: pointer;
            box-shadow: inset 0 0 0 1px rgba(212,175,55,0.3);
            transition: background 120ms ease;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-tfa:hover { background: var(--emerald-800); }

        .toggle-link {
            font-size: 12px; color: var(--emerald-700); font-weight: 600;
            background: none; border: none; cursor: pointer; padding: 0;
            text-align: center;
        }
        .toggle-link:hover { color: var(--emerald-800); text-decoration: underline; }

        .signout-row {
            display: flex; justify-content: center; padding-top: 6px;
            border-top: 1px solid var(--line);
        }
        .signout-row form button {
            font-family: 'JetBrains Mono', monospace; font-size: 10px;
            text-transform: uppercase; letter-spacing: 0.16em;
            color: var(--ink-500); background: none; border: none; cursor: pointer;
            padding: 12px 0 0;
        }
        .signout-row form button:hover { color: var(--danger); }
    </style>
</head>
<body x-data="{ recovery: false }">

<div class="tfa-card">
    <div class="tfa-brand">
        <img src="/images/nrh-logo.png" alt="NRH">
        <span class="tfa-brand-text">NRH <em>Intelligence</em></span>
    </div>

    <div>
        <div class="tfa-kicker"><span class="dot"></span> Two-factor verification</div>
        <h1 class="tfa-title" style="margin-top: 10px;" x-show="!recovery">Enter your <em>code</em>.</h1>
        <h1 class="tfa-title" style="margin-top: 10px;" x-show="recovery" x-cloak>Use a <em>recovery</em> code.</h1>
        <p class="tfa-sub" style="margin-top: 8px;" x-show="!recovery">Open your authenticator app and enter the 6-digit code shown for NRH Admin.</p>
        <p class="tfa-sub" style="margin-top: 8px;" x-show="recovery" x-cloak>Enter one of the recovery codes you saved when enabling 2FA. Each code can only be used once.</p>
    </div>

    <form method="POST" action="{{ route('two-factor.verify') }}" style="display: flex; flex-direction: column; gap: 16px;">
        @csrf

        <div class="field" x-show="!recovery">
            <label class="field-label" for="code">Authenticator code</label>
            <input type="text" id="code" name="code" inputmode="numeric" autocomplete="one-time-code"
                   maxlength="6" pattern="[0-9]*"
                   placeholder="123 456"
                   x-bind:autofocus="!recovery"
                   class="tfa-input {{ $errors->has('code') ? 'has-error' : '' }}">
            @error('code')
                <span class="field-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="field" x-show="recovery" x-cloak>
            <label class="field-label" for="recovery_code">Recovery code</label>
            <input type="text" id="recovery_code" name="recovery_code"
                   placeholder="abcde-12345"
                   x-bind:autofocus="recovery"
                   autocomplete="one-time-code"
                   class="tfa-input recovery {{ $errors->has('recovery_code') ? 'has-error' : '' }}">
            @error('recovery_code')
                <span class="field-error">{{ $message }}</span>
            @enderror
        </div>

        <button type="submit" class="btn-tfa">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M9 12l2 2 4-4"/><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            Verify &amp; continue
        </button>

        <button type="button" class="toggle-link" @click="recovery = !recovery">
            <span x-show="!recovery">Lost your device? Use a recovery code</span>
            <span x-show="recovery" x-cloak>Use authenticator code instead</span>
        </button>
    </form>

    <div class="signout-row">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">← Cancel and sign out</button>
        </form>
    </div>
</div>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
