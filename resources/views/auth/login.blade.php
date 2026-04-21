<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — NRH Admin</title>
    <link rel="icon" type="image/png" href="/images/nrh-logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter+Tight:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: 'Inter Tight', ui-sans-serif, system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
            font-size: 14px;
            line-height: 1.45;
        }

        .auth-shell {
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            min-height: 100vh;
        }

        /* ── Left panel ── */
        .auth-left {
            background:
                radial-gradient(1200px 600px at 20% 0%, rgba(212,175,55,0.09), transparent 60%),
                radial-gradient(900px 600px at 100% 100%, rgba(5,150,105,0.14), transparent 60%),
                linear-gradient(170deg, #023527 0%, #044d39 60%, #011d15 100%);
            color: #e9efeb;
            padding: 48px 56px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }
        .auth-left::before {
            content: "";
            position: absolute; inset: 0;
            background-image:
                linear-gradient(rgba(212,175,55,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(212,175,55,0.04) 1px, transparent 1px);
            background-size: 48px 48px;
            pointer-events: none;
            mask-image: linear-gradient(180deg, transparent, black 20%, black 80%, transparent);
        }
        .auth-left::after {
            content: "";
            position: absolute; right: -160px; bottom: -160px;
            width: 480px; height: 480px;
            border-radius: 50%;
            border: 1px solid rgba(212,175,55,0.25);
            box-shadow:
                inset 0 0 0 20px rgba(212,175,55,0.04),
                inset 0 0 0 21px rgba(212,175,55,0.16),
                inset 0 0 0 60px transparent,
                inset 0 0 0 61px rgba(212,175,55,0.07);
            pointer-events: none;
        }

        /* Brand in left panel */
        .al-brand {
            display: flex; align-items: center; gap: 12px;
            position: relative; z-index: 2;
        }
        .al-logo { width: 44px; height: 44px; object-fit: contain; flex-shrink: 0; filter: drop-shadow(0 2px 6px rgba(0,0,0,0.5)); }
        .al-brand-name { font-family: 'Fraunces', serif; font-weight: 600; font-size: 18px; color: #f5ecd1; letter-spacing: 0.01em; }
        .al-brand-name em { font-style: italic; color: #d4af37; }
        .al-brand-sub { font-size: 9px; text-transform: uppercase; letter-spacing: 0.2em; color: rgba(212,175,55,0.6); margin-top: 3px; }

        /* Seal (decorative) */
        .al-seal {
            position: absolute; right: 52px; top: 44px;
            width: 110px; height: 110px;
            z-index: 2; opacity: 0.18;
            object-fit: contain;
            filter: brightness(1.4) saturate(0.3);
        }

        /* Hero copy */
        .al-hero { position: relative; z-index: 2; max-width: 500px; }
        .al-eyebrow {
            font-family: 'JetBrains Mono', monospace;
            font-size: 10px; text-transform: uppercase; letter-spacing: 0.22em;
            color: #d4af37; margin-bottom: 20px;
            display: flex; align-items: center; gap: 12px;
        }
        .al-eyebrow::before { content: ""; width: 24px; height: 1px; background: #d4af37; flex-shrink: 0; }
        .al-hero h2 {
            font-family: 'Fraunces', serif;
            font-size: 46px; font-weight: 400;
            line-height: 1.05; letter-spacing: -0.015em;
            color: #fff; margin: 0 0 18px;
            text-wrap: pretty;
        }
        .al-hero h2 em { font-style: italic; color: #d4af37; font-weight: 500; }
        .al-hero p {
            font-size: 14px; line-height: 1.6;
            color: rgba(233,239,235,0.65);
            max-width: 420px; margin: 0;
        }

        /* Stats strip */
        .al-stats {
            display: grid; grid-template-columns: repeat(3, 1fr);
            border-top: 1px solid rgba(212,175,55,0.18);
            border-bottom: 1px solid rgba(212,175,55,0.18);
            padding: 18px 0; margin-top: 24px;
            position: relative; z-index: 2;
        }
        .al-stat { padding: 0 16px; border-right: 1px solid rgba(212,175,55,0.12); }
        .al-stat:first-child { padding-left: 0; }
        .al-stat:last-child { border-right: none; }
        .al-stat .v {
            font-family: 'Fraunces', serif; font-size: 24px; font-weight: 500;
            color: #fff; letter-spacing: -0.01em; font-feature-settings: "tnum";
        }
        .al-stat .v em { font-style: normal; color: #d4af37; font-size: 16px; margin-left: 2px; }
        .al-stat .l {
            font-size: 10px; text-transform: uppercase; letter-spacing: 0.14em;
            color: rgba(233,239,235,0.5); margin-top: 4px;
        }

        /* Footer badges */
        .al-foot {
            position: relative; z-index: 2;
            display: flex; justify-content: space-between; align-items: center;
            font-family: 'JetBrains Mono', monospace;
            font-size: 9px; letter-spacing: 0.14em; text-transform: uppercase;
            color: rgba(233,239,235,0.45);
        }
        .al-badges { display: flex; gap: 10px; }
        .al-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 8px;
            border: 1px solid rgba(212,175,55,0.22);
            border-radius: 3px; color: #d4af37;
        }
        .al-badge .dot {
            width: 5px; height: 5px; border-radius: 50%;
            background: #10b981; box-shadow: 0 0 0 2px rgba(16,185,129,0.2);
        }

        /* ── Right panel ── */
        .auth-right {
            display: flex; align-items: center; justify-content: center;
            padding: 48px; background: var(--paper);
            position: relative;
        }
        .auth-right-top {
            position: absolute; top: 28px; right: 32px;
            font-size: 12px; color: var(--ink-500);
        }
        .auth-right-top span { color: var(--emerald-700); font-weight: 600; }

        .auth-card {
            width: 100%; max-width: 400px;
            display: flex; flex-direction: column; gap: 24px;
        }

        .auth-kicker {
            font-family: 'JetBrains Mono', monospace;
            font-size: 10px; text-transform: uppercase; letter-spacing: 0.2em;
            color: var(--ink-500);
            display: flex; align-items: center; gap: 10px;
        }
        .auth-kicker .step {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 3px 8px; border-radius: 999px;
            background: var(--emerald-50); color: var(--emerald-800); font-weight: 600;
        }
        .auth-kicker .step .sdot { width: 5px; height: 5px; border-radius: 50%; background: #d4af37; }

        .auth-title {
            font-family: 'Fraunces', serif;
            font-size: 32px; font-weight: 500;
            line-height: 1.1; letter-spacing: -0.015em;
            margin: 0; color: var(--ink-900);
        }
        .auth-title em { font-style: italic; color: var(--emerald-700); }
        .auth-sub { font-size: 13px; color: var(--ink-500); margin: 0; line-height: 1.5; }
        .auth-sub b { color: var(--ink-900); font-weight: 600; }

        .field { display: flex; flex-direction: column; gap: 6px; }
        .field-label {
            font-size: 10px; text-transform: uppercase; letter-spacing: 0.14em;
            color: var(--ink-500); font-weight: 600;
            display: flex; align-items: center; justify-content: space-between;
        }
        .input-wrap { position: relative; }
        .input-wrap .lead-icon {
            position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
            width: 16px; height: 16px; color: var(--ink-400); pointer-events: none;
        }
        .auth-input {
            width: 100%;
            padding: 12px 14px 12px 40px;
            border: 1px solid var(--line);
            background: var(--card);
            border-radius: 8px;
            font-family: inherit; font-size: 14px;
            color: var(--ink-900); outline: none;
            transition: border-color 120ms ease, box-shadow 120ms ease;
        }
        .auth-input:focus { border-color: var(--emerald-600); box-shadow: 0 0 0 3px rgba(5,150,105,0.12); }
        .auth-input::placeholder { color: var(--ink-400); }
        .auth-input.has-error { border-color: var(--danger); background: #fbeeec; }

        .field-error { font-size: 11px; color: var(--danger); margin-top: 2px; }

        .eye-btn {
            position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
            background: transparent; border: none; color: var(--ink-400); cursor: pointer;
            width: 28px; height: 28px; display: grid; place-items: center; border-radius: 4px;
        }
        .eye-btn:hover { color: var(--emerald-700); background: var(--emerald-50); }

        .btn-auth {
            width: 100%;
            padding: 13px 16px;
            background: var(--emerald-700); color: #fff;
            border: none;
            border-radius: 8px;
            font-family: inherit; font-size: 14px; font-weight: 600;
            cursor: pointer;
            box-shadow: inset 0 0 0 1px rgba(212,175,55,0.3), 0 1px 0 rgba(4,77,57,0.3);
            transition: background 120ms ease, transform 120ms ease;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-auth:hover { background: var(--emerald-800); }
        .btn-auth:active { transform: translateY(1px); }

        .divider {
            display: flex; align-items: center; gap: 14px;
            color: var(--ink-400); font-size: 10px; text-transform: uppercase; letter-spacing: 0.18em;
        }
        .divider::before, .divider::after { content: ""; flex: 1; height: 1px; background: var(--line); }

        .session-strip {
            display: grid; grid-template-columns: 1fr 1fr;
            border: 1px solid var(--line); border-radius: 6px;
            background: var(--paper-2);
            padding: 10px 14px;
            font-size: 11px; font-family: 'JetBrains Mono', monospace;
            color: var(--ink-500); text-transform: uppercase; letter-spacing: 0.1em;
        }
        .session-strip b { color: var(--ink-900); font-weight: 600; }

        @media (max-width: 960px) {
            .auth-shell { grid-template-columns: 1fr; }
            .auth-left { display: none; }
        }
    </style>
</head>
<body>

<div class="auth-shell">

    {{-- ── Left panel ── --}}
    <aside class="auth-left">
        <div class="al-brand">
            <img src="/images/nrh-logo.png" alt="NRH" class="al-logo">
            <div>
                <div class="al-brand-name">NRH <em>Intelligence</em></div>
                <div class="al-brand-sub">Admin Portal</div>
            </div>
        </div>

        <img src="/images/nrh-logo.png" alt="" class="al-seal">

        <div class="al-hero">
            <div class="al-eyebrow">Staff-only access</div>
            <h2>Operate with <em>clarity</em> and control.</h2>
            <p>The NRH Admin Portal gives your team full visibility over every screening request, customer account, invoice, and workflow — all in one place.</p>
            <div class="al-stats">
                <div class="al-stat">
                    <div class="v">1.2<em>M</em></div>
                    <div class="l">Screenings</div>
                </div>
                <div class="al-stat">
                    <div class="v">184</div>
                    <div class="l">Jurisdictions</div>
                </div>
                <div class="al-stat">
                    <div class="v">99.4<em>%</em></div>
                    <div class="l">On-time SLA</div>
                </div>
            </div>
        </div>

        <div class="al-foot">
            <span>NRH Intelligence Sdn. Bhd.</span>
            <div class="al-badges">
                <span class="al-badge"><span class="dot"></span> System online</span>
                <span class="al-badge">SOC 2 Type II</span>
            </div>
        </div>
    </aside>

    {{-- ── Right panel ── --}}
    <div class="auth-right">
        <div class="auth-right-top">
            Internal staff access only.
        </div>

        <div class="auth-card">
            <div>
                <div class="auth-kicker">
                    <span class="step"><span class="sdot"></span>Secure sign-in</span>
                    <span>TLS 1.3</span>
                </div>
                <h1 class="auth-title" style="margin-top: 14px;">Welcome back, <em>admin.</em></h1>
                <p class="auth-sub" style="margin-top: 8px;">Sign in to the <b>NRH Intelligence</b> admin portal. Your session is encrypted end-to-end.</p>
            </div>

            <form method="POST" action="{{ route('login') }}" style="display: flex; flex-direction: column; gap: 16px;">
                @csrf

                <div class="field">
                    <label class="field-label" for="email">Email address</label>
                    <div class="input-wrap">
                        <svg class="lead-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><path d="m22 6-10 7L2 6"/></svg>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                               autocomplete="email"
                               placeholder="admin@nrhintelligence.com"
                               class="auth-input {{ $errors->has('email') ? 'has-error' : '' }}">
                    </div>
                    @error('email')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="field">
                    <label class="field-label" for="password">Password</label>
                    <div class="input-wrap" x-data="{ show: false }">
                        <svg class="lead-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <input :type="show ? 'text' : 'password'" id="password" name="password" required
                               autocomplete="current-password"
                               placeholder="••••••••"
                               class="auth-input" style="padding-right: 42px;">
                        <button type="button" class="eye-btn" @click="show = !show">
                            <svg x-show="!show" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg x-show="show" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </button>
                    </div>
                    @error('password')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="btn-auth">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="M10 17l5-5-5-5M15 12H3"/></svg>
                    Sign in to Admin Portal
                </button>
            </form>

            <div class="session-strip">
                <span>Session · <b>NRH Admin</b></span>
                <span>Encrypted · <b>TLS 1.3</b></span>
            </div>
        </div>
    </div>

</div>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
