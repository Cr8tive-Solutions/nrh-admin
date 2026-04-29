<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'NRH Admin') — NRH Intelligence</title>
    <link rel="icon" type="image/png" href="/images/nrh-logo.png">
    <script>(function(){var t=localStorage.getItem('nrh-theme');if(t==='dark')document.documentElement.setAttribute('data-theme','dark');}());</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter+Tight:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { background: var(--paper); color: var(--ink-900); font-family: 'Inter Tight', ui-sans-serif, system-ui, sans-serif; -webkit-font-smoothing: antialiased; }

        /* ── Shell ── */
        .admin-shell { display: grid; grid-template-columns: 220px 1fr; min-height: 100vh; }

        /* ── Dark Sidebar ── */
        .admin-sidebar {
            background: linear-gradient(180deg, var(--emerald-900) 0%, #011d15 100%);
            border-right: 1px solid rgba(212,175,55,0.14);
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,0.1) transparent;
        }
        .admin-sidebar::-webkit-scrollbar { width: 4px; }
        .admin-sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 2px; }

        .sb-inner { padding: 20px 14px; display: flex; flex-direction: column; gap: 20px; flex: 1; }

        /* Brand */
        .sb-brand { display: flex; gap: 10px; align-items: center; padding-bottom: 18px; border-bottom: 1px solid rgba(212,175,55,0.14); }
        .sb-logo { width: 40px; height: 40px; flex-shrink: 0; object-fit: contain; filter: drop-shadow(0 1px 3px rgba(0,0,0,0.4)); }
        .sb-brand-text { line-height: 1.15; }
        .sb-brand-name { font-family: 'Fraunces', serif; font-weight: 600; font-size: 15px; color: #f5ecd1; letter-spacing: 0.01em; }
        .sb-brand-name em { font-style: italic; color: var(--gold-400); }
        .sb-brand-sub { font-size: 9px; text-transform: uppercase; letter-spacing: 0.2em; color: rgba(212,175,55,0.55); margin-top: 3px; }

        /* Nav */
        .sb-nav { flex: 1; display: flex; flex-direction: column; gap: 2px; }
        .sb-section-label {
            font-size: 9px; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.2em;
            color: rgba(212,175,55,0.45);
            padding: 0 10px 8px; margin-top: 12px;
        }
        .sb-section-label:first-child { margin-top: 0; }
        .sb-link {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 10px;
            border-radius: 6px;
            font-size: 13px; font-weight: 500;
            color: rgba(255,255,255,0.55);
            text-decoration: none;
            position: relative;
            transition: background 120ms ease, color 120ms ease;
            cursor: pointer;
        }
        .sb-link:hover { background: rgba(255,255,255,0.07); color: rgba(255,255,255,0.9); }
        .sb-link.active { background: rgba(255,255,255,0.1); color: #fff; }
        .sb-link.active::before {
            content: "";
            position: absolute; left: 0; top: 8px; bottom: 8px;
            width: 2px;
            background: var(--gold-500);
            border-radius: 1px;
        }
        .sb-link svg { width: 15px; height: 15px; flex-shrink: 0; opacity: 0.85; }
        .sb-link.active svg { opacity: 1; }
        .sb-count {
            margin-left: auto;
            font-size: 10px;
            font-family: 'JetBrains Mono', monospace;
            color: rgba(255,255,255,0.4);
        }
        .sb-count.alert { color: var(--gold-400); }

        /* Footer */
        .sb-footer {
            padding-top: 14px;
            border-top: 1px solid rgba(212,175,55,0.14);
            display: flex; flex-direction: column; gap: 8px;
        }
        .sb-user {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 10px;
            border-radius: 6px;
        }
        .sb-avatar {
            width: 28px; height: 28px; border-radius: 50%;
            background: var(--emerald-700);
            color: var(--gold-400);
            display: grid; place-items: center;
            font-size: 11px; font-weight: 600;
            font-family: 'JetBrains Mono', monospace;
            box-shadow: inset 0 0 0 1px rgba(212,175,55,0.4);
            flex-shrink: 0;
        }
        .sb-user-meta { display: flex; flex-direction: column; line-height: 1.2; min-width: 0; }
        .sb-user-name { font-size: 12px; font-weight: 600; color: rgba(255,255,255,0.9); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sb-user-role { font-size: 9px; color: rgba(212,175,55,0.6); text-transform: uppercase; letter-spacing: 0.14em; margin-top: 2px; }
        .sb-signout {
            display: flex; align-items: center; gap: 8px;
            padding: 7px 10px;
            border-radius: 6px;
            font-size: 12px; font-weight: 500;
            color: rgba(255,255,255,0.4);
            background: transparent;
            border: none;
            cursor: pointer;
            width: 100%;
            text-align: left;
            transition: color 120ms ease, background 120ms ease;
        }
        .sb-signout:hover { color: rgba(255,255,255,0.7); background: rgba(255,255,255,0.06); }
        .sb-signout svg { width: 14px; height: 14px; flex-shrink: 0; }

        /* ── Main area ── */
        .admin-main { display: flex; flex-direction: column; min-width: 0; }

        /* Topbar */
        .admin-topbar {
            display: flex; align-items: center; gap: 16px;
            padding: 12px 28px;
            border-bottom: 1px solid var(--line);
            background: var(--paper);
            position: sticky; top: 0; z-index: 30;
        }
        .topbar-crumbs {
            font-size: 11px; color: var(--ink-500);
            display: flex; align-items: center; gap: 6px;
            font-family: 'JetBrains Mono', monospace;
            text-transform: uppercase; letter-spacing: 0.1em;
            white-space: nowrap;
        }
        .topbar-crumbs b { color: var(--ink-900); font-weight: 600; }
        .topbar-crumbs .sep { color: var(--ink-300); }
        .topbar-search {
            margin-left: auto;
            position: relative;
            width: 300px;
        }
        .topbar-search input {
            width: 100%;
            padding: 8px 10px 8px 32px;
            border: 1px solid var(--line);
            background: var(--card);
            border-radius: 6px;
            font-size: 13px; color: var(--ink-900);
            outline: none;
            font-family: inherit;
            transition: border-color 120ms ease, box-shadow 120ms ease;
        }
        .topbar-search input::placeholder { color: var(--ink-400); }
        .topbar-search input:focus { border-color: var(--emerald-600); box-shadow: 0 0 0 3px rgba(5,150,105,0.1); }
        .topbar-search svg { position: absolute; left: 9px; top: 50%; transform: translateY(-50%); width: 14px; height: 14px; color: var(--ink-400); }
        .topbar-actions { display: flex; align-items: center; gap: 6px; }
        .topbar-icon-btn {
            width: 32px; height: 32px;
            display: grid; place-items: center;
            border-radius: 6px;
            border: 1px solid transparent;
            background: transparent;
            cursor: pointer;
            color: var(--ink-700);
            position: relative;
        }
        .topbar-icon-btn:hover { border-color: var(--line); background: var(--card); }
        .topbar-icon-btn svg { width: 16px; height: 16px; }
        .topbar-icon-btn .badge-dot {
            position: absolute; top: 5px; right: 5px;
            width: 6px; height: 6px; border-radius: 50%;
            background: var(--gold-500);
        }
        .topbar-divider { width: 1px; height: 20px; background: var(--line); margin: 0 2px; }
        .topbar-user { display: flex; align-items: center; gap: 8px; padding: 4px 6px; border-radius: 6px; cursor: default; }
        .topbar-avatar {
            width: 28px; height: 28px; border-radius: 50%;
            background: var(--emerald-700);
            color: var(--gold-400);
            display: grid; place-items: center;
            font-size: 11px; font-weight: 600;
            font-family: 'JetBrains Mono', monospace;
            box-shadow: inset 0 0 0 1px rgba(212,175,55,0.35);
        }
        .topbar-user-meta { display: flex; flex-direction: column; line-height: 1.2; }
        .topbar-user-name { font-size: 12px; font-weight: 600; color: var(--ink-900); }
        .topbar-user-role { font-size: 10px; color: var(--ink-500); text-transform: uppercase; letter-spacing: 0.1em; }

        /* Content wrapper */
        .admin-content { flex: 1; overflow-y: auto; padding: 28px; display: flex; flex-direction: column; gap: 20px; }

        /* Flash */
        .flash-success {
            display: flex; align-items: center; gap: 10px;
            padding: 12px 16px;
            background: var(--emerald-50);
            border: 1px solid rgba(5,150,105,0.2);
            border-left: 3px solid var(--emerald-600);
            border-radius: 6px;
            font-size: 13px; color: var(--emerald-800);
        }
        .flash-error {
            display: flex; align-items: center; gap: 10px;
            padding: 12px 16px;
            background: rgba(196,69,58,0.08);
            border: 1px solid rgba(196,69,58,0.2);
            border-left: 3px solid var(--danger);
            border-radius: 6px;
            font-size: 13px; color: var(--danger);
        }
        .flash-error ul { list-style: disc inside; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 2px; }
    </style>
</head>
<body x-data="{
    dark: document.documentElement.getAttribute('data-theme') === 'dark',
    toggleDark() {
        this.dark = !this.dark;
        document.documentElement.setAttribute('data-theme', this.dark ? 'dark' : 'light');
        localStorage.setItem('nrh-theme', this.dark ? 'dark' : 'light');
    }
}">

<div class="admin-shell">

    {{-- ===================== SIDEBAR ===================== --}}
    <aside class="admin-sidebar">
        <div class="sb-inner">

            {{-- Brand --}}
            <div class="sb-brand">
                <img src="/images/nrh-logo.png" alt="NRH" class="sb-logo">
                <div class="sb-brand-text">
                    <div class="sb-brand-name">NRH <em>Intelligence</em></div>
                    <div class="sb-brand-sub">Admin Portal</div>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="sb-nav">

                <div class="sb-section-label">Operations</div>

                <a href="{{ route('dashboard') }}"
                   class="sb-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                    Dashboard
                </a>

                <a href="{{ route('requests.index') }}"
                   class="sb-link {{ request()->routeIs('requests.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 5H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-4"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/></svg>
                    Request Queue
                    @if(isset($pendingCount) && $pendingCount > 0)
                    <span class="sb-count alert">{{ $pendingCount }}</span>
                    @endif
                </a>

                <a href="{{ route('customers.index') }}"
                   class="sb-link {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
                    Customers
                </a>

                <div class="sb-section-label">Finance</div>

                <a href="{{ route('pricing.index') }}"
                   class="sb-link {{ request()->routeIs('pricing.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M12 6v.5M12 17.5V18M9 9a3 3 0 0 1 6 0c0 2-3 2.5-3 5"/></svg>
                    Scope Pricing
                </a>

                <a href="{{ route('invoices.index') }}"
                   class="sb-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h4"/></svg>
                    Invoices
                </a>

                <a href="{{ route('transactions.index') }}"
                   class="sb-link {{ request()->routeIs('transactions.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    Transactions
                </a>

                @if(admin_can('config.scopes') || admin_can('config.countries'))
                <div class="sb-section-label">Configuration</div>
                @endif

                @allowed('config.scopes')
                <a href="{{ route('config.scopes.index') }}"
                   class="sb-link {{ request()->routeIs('config.scopes.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12l2 2 4-4"/></svg>
                    Scope Types
                </a>
                @endallowed

                @allowed('config.countries')
                <a href="{{ route('config.countries.index') }}"
                   class="sb-link {{ request()->routeIs('config.countries.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                    Countries
                </a>
                @endallowed

                @allowed('config.scopes')
                <a href="{{ route('config.holidays.index') }}"
                   class="sb-link {{ request()->routeIs('config.holidays.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    Holidays
                </a>
                @endallowed

                @if(admin_can('staff.manage') || admin_can('permissions.manage'))
                <div class="sb-section-label">Admin</div>
                @endif

                @allowed('staff.manage')
                <a href="{{ route('staff.index') }}"
                   class="sb-link {{ request()->routeIs('staff.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
                    Staff Accounts
                </a>

                <a href="{{ route('audit.index') }}"
                   class="sb-link {{ request()->routeIs('audit.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 13l2 2 4-4"/></svg>
                    Audit Log
                </a>
                @endallowed

                @allowed('permissions.manage')
                <a href="{{ route('permissions.index') }}"
                   class="sb-link {{ request()->routeIs('permissions.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
                    Roles &amp; Permissions
                </a>
                @endallowed

            </nav>

            {{-- Footer --}}
            @php $me = current_admin(); $myAvatar = $me?->avatarUrl(); @endphp
            <div class="sb-footer">
                <a href="{{ route('account.profile') }}" class="sb-user" style="text-decoration: none; color: inherit;">
                    <div class="sb-avatar" style="overflow: hidden;">
                        @if($myAvatar)
                            <img src="{{ $myAvatar }}" alt="" style="width:100%; height:100%; object-fit:cover;">
                        @else
                            {{ strtoupper(substr(session('admin_name', 'A'), 0, 2)) }}
                        @endif
                    </div>
                    <div class="sb-user-meta">
                        <span class="sb-user-name">{{ session('admin_name') }}</span>
                        <span class="sb-user-role">{{ str_replace('_', ' ', session('admin_role')) }}</span>
                    </div>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="sb-signout">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                        Sign out
                    </button>
                </form>
            </div>

        </div>
    </aside>

    {{-- ===================== MAIN ===================== --}}
    <div class="admin-main">

        {{-- Topbar --}}
        <header class="admin-topbar">
            <div class="topbar-crumbs">
                <span>NRH</span>
                <span class="sep">/</span>
                <span>Admin</span>
                <span class="sep">/</span>
                <b>@yield('page-title', 'Dashboard')</b>
            </div>

            <div class="topbar-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3-3"/></svg>
                <input placeholder="Search requests, customers…">
            </div>

            <div class="topbar-actions">
                @yield('header-actions')

                <button class="topbar-icon-btn" @click="toggleDark()" :title="dark ? 'Switch to light mode' : 'Switch to dark mode'">
                    <svg x-show="!dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="5"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                    <svg x-show="dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                </button>

                <button class="topbar-icon-btn" title="Notifications">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2a7 7 0 0 0-7 7v4l-2 3h18l-2-3V9a7 7 0 0 0-7-7z"/><path d="M9 19a3 3 0 0 0 6 0"/></svg>
                    @if(isset($stats) && ($stats['flagged_cases'] ?? 0) > 0)
                    <span class="badge-dot"></span>
                    @endif
                </button>

                <div class="topbar-divider"></div>

                <a href="{{ route('account.profile') }}" class="topbar-user" style="text-decoration: none; color: inherit;" title="My Profile">
                    <div class="topbar-avatar" style="overflow: hidden;">
                        @if($myAvatar)
                            <img src="{{ $myAvatar }}" alt="" style="width:100%; height:100%; object-fit:cover;">
                        @else
                            {{ strtoupper(substr(session('admin_name', 'A'), 0, 2)) }}
                        @endif
                    </div>
                    <div class="topbar-user-meta">
                        <span class="topbar-user-name">{{ session('admin_name') }}</span>
                        <span class="topbar-user-role">{{ ucfirst(str_replace('_', ' ', session('admin_role'))) }}</span>
                    </div>
                </a>
            </div>
        </header>

        {{-- Flash messages --}}
        @if(session('success'))
        <div class="flash-success mx-7 mt-5" x-data="{ show: true }" x-show="show">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg>
            <span>{{ session('success') }}</span>
            <button @click="show = false" style="margin-left:auto; background:none; border:none; cursor:pointer; color:inherit; opacity:0.6; line-height:1;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>
        @endif

        @if(session('error'))
        <div class="flash-error mx-7 mt-5" x-data="{ show: true }" x-show="show">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
            <span>{{ session('error') }}</span>
            <button @click="show = false" style="margin-left:auto; background:none; border:none; cursor:pointer; color:inherit; opacity:0.6; line-height:1;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>
        @endif

        @if(session('warning'))
        <div class="mx-7 mt-5" x-data="{ show: true }" x-show="show"
             style="background:#fefce8; border:1px solid #fde68a; color:#854d0e; padding:10px 14px; border-radius:8px; display:flex; align-items:center; gap:10px; font-size:13px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0zM12 9v4M12 17h.01"/></svg>
            <span>{{ session('warning') }}</span>
            <button @click="show = false" style="margin-left:auto; background:none; border:none; cursor:pointer; color:inherit; opacity:0.6; line-height:1;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>
        @endif

        @if($errors->any())
        <div class="flash-error mx-7 mt-5">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- Page content --}}
        <div class="admin-content">
            @yield('content')
        </div>

    </div>

</div>

{{-- Flagged cases FAB --}}
@if(isset($stats) && ($stats['flagged_cases'] ?? 0) > 0)
<a href="{{ route('requests.index', ['status' => 'flagged']) }}"
   style="position: fixed; bottom: 24px; right: 24px; z-index: 50;
          display: inline-flex; align-items: center; gap: 8px;
          padding: 10px 16px;
          background: var(--danger); color: #fff;
          border-radius: 999px;
          font-size: 13px; font-weight: 600;
          box-shadow: 0 8px 24px rgba(196,69,58,0.4);
          text-decoration: none;
          transition: transform 120ms ease, box-shadow 120ms ease;">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
    {{ $stats['flagged_cases'] }} flagged {{ $stats['flagged_cases'] === 1 ? 'case' : 'cases' }}
</a>
@endif

</body>
</html>
