{{-- Shared split-screen styles for the sign-in and two-factor pages. --}}
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
            display: flex; align-items: flex-start; justify-content: center;
            padding: max(48px, calc(50vh - 205px)) 48px 48px; background: var(--paper);
            position: relative;
        }
        .auth-right-top {
            position: absolute; top: 28px; right: 68px;
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
        .auth-input.has-error { border-color: var(--danger); background: rgba(196,69,58,0.06); }

        .field-error { font-size: 11px; color: var(--danger); margin-top: 2px; }

        .eye-btn {
            position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
            background: transparent; border: none; color: var(--ink-400); cursor: pointer;
            width: 28px; height: 28px; display: grid; place-items: center; border-radius: 4px;
        }
        .eye-btn:hover { color: var(--emerald-700); background: rgba(4,108,78,0.08); }

        .theme-toggle {
            position: absolute; top: 20px; right: 24px;
            width: 32px; height: 32px;
            display: grid; place-items: center;
            border: 1px solid var(--line);
            background: transparent;
            border-radius: 6px;
            cursor: pointer;
            color: var(--ink-500);
            transition: color 120ms, background 120ms, border-color 120ms;
            z-index: 10;
        }
        .theme-toggle:hover { border-color: var(--emerald-600); color: var(--emerald-700); background: rgba(4,108,78,0.05); }
        .theme-toggle svg { width: 15px; height: 15px; }

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
