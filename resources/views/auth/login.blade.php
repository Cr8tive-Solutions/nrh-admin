<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — KYRO Admin</title>
    <link rel="icon" type="image/png" href="/images/nrh-logo.png">
    <script>(function(){var t=localStorage.getItem('nrh-theme');if(t==='dark')document.documentElement.setAttribute('data-theme','dark');}());</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter+Tight:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('auth.partials.shell-styles')
</head>
<body x-data="{
    dark: document.documentElement.getAttribute('data-theme') === 'dark',
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
            <div class="al-eyebrow">Staff-only access</div>
            <h2>Operate with <em>clarity</em> and control.</h2>
            <p>The KYRO Admin Portal gives your team full visibility over every screening request, customer account, invoice, and workflow — all in one place.</p>
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
        <button class="theme-toggle" @click="toggleDark()" :title="dark ? 'Light mode' : 'Dark mode'">
            <svg x-show="!dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="5"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
            <svg x-show="dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        </button>
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
                <p class="auth-sub" style="margin-top: 8px;">Sign in to the <b>KYRO</b> admin portal. Your session is encrypted end-to-end.</p>
            </div>

            <form method="POST" action="{{ route('login') }}" style="display: flex; flex-direction: column; gap: 16px;">
                @csrf

                @if(session('error'))
                    <span class="field-error" role="alert">{{ session('error') }}</span>
                @endif

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
                <span>Session · <b>KYRO Admin</b></span>
                <span>Encrypted · <b>TLS 1.3</b></span>
            </div>
        </div>
    </div>

</div>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
