@extends('layouts.admin')

@section('title', 'Staff Accounts')
@section('page-title', 'Staff Accounts')
@section('page-subtitle', 'Internal admin user accounts')

@section('header-actions')
    <a href="{{ route('staff.create') }}" class="nrh-btn nrh-btn-primary">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" style="margin-right:4px;"><path d="M12 5v14M5 12h14"/></svg>
        New Staff
    </a>
@endsection

@section('content')

@php
    $roleColors = [
        'super_admin' => ['bg' => 'rgba(59,130,246,0.10)', 'text' => '#1d4ed8', 'dot' => '#3b82f6'],
        'operations'  => ['bg' => 'rgba(245,158,11,0.10)', 'text' => '#b45309', 'dot' => '#f59e0b'],
        'finance'     => ['bg' => 'var(--emerald-50)',     'text' => 'var(--emerald-700)', 'dot' => 'var(--emerald-600)'],
        'viewer'      => ['bg' => 'var(--ink-100)',        'text' => 'var(--ink-500)', 'dot' => 'var(--ink-400)'],
    ];
    $twoFaPct = $stats['total'] > 0 ? round(($stats['with_2fa'] / $stats['total']) * 100) : 0;
@endphp

<style>
    /* Hero */
    .sf-hero {
        background: var(--card);
        border: 1px solid var(--line);
        border-radius: 14px;
        padding: 22px 26px;
        margin-bottom: 18px;
        display: grid; grid-template-columns: 1fr auto; gap: 24px; align-items: center;
        position: relative; overflow: hidden;
    }
    .sf-hero::before {
        content: ""; position: absolute; right: -120px; top: -120px;
        width: 320px; height: 320px; border-radius: 50%;
        background: radial-gradient(circle, rgba(212,175,55,0.06), transparent 60%);
        pointer-events: none;
    }
    .sf-hero-stats { display: flex; align-items: stretch; gap: 0; }
    .sf-stat { padding: 4px 18px; border-left: 1px solid var(--line); text-align: center; min-width: 80px; }
    .sf-stat:first-child { border-left: none; padding-left: 0; }
    .sf-stat-value {
        font-family: 'Fraunces', serif; font-size: 24px; font-weight: 500;
        line-height: 1; color: var(--ink-900);
    }
    .sf-stat-value.green { color: var(--emerald-700); }
    .sf-stat-value.warn { color: var(--gold-700, #b8860b); }
    .sf-stat-label {
        font-size: 9px; text-transform: uppercase; letter-spacing: 0.16em;
        color: var(--ink-500); margin-top: 6px;
        font-family: 'JetBrains Mono', monospace;
    }

    /* Role distribution mini-bars */
    .sf-role-grid {
        display: grid; grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px; align-items: end;
        position: relative; z-index: 1;
    }
    .sf-role-cell { display: flex; flex-direction: column; gap: 6px; }
    .sf-role-name {
        font-size: 10px; text-transform: uppercase; letter-spacing: 0.14em;
        color: var(--ink-500); font-weight: 600;
        font-family: 'JetBrains Mono', monospace;
        display: flex; align-items: center; gap: 5px;
    }
    .sf-role-name .dot { width: 6px; height: 6px; border-radius: 50%; }
    .sf-role-bar { width: 100%; height: 4px; border-radius: 99px; background: var(--ink-100); overflow: hidden; }
    .sf-role-bar-fill { height: 100%; border-radius: 99px; }
    .sf-role-count {
        font-family: 'Fraunces', serif; font-size: 18px; font-weight: 500;
        color: var(--ink-900); line-height: 1;
    }
    .sf-role-count .label-inline {
        font-family: 'JetBrains Mono', monospace; font-size: 10px;
        color: var(--ink-400); margin-left: 4px; font-weight: 400;
    }

    /* Filter bar */
    .sf-filterbar {
        background: var(--card); border: 1px solid var(--line);
        border-radius: 12px; padding: 12px 14px; margin-bottom: 14px;
        display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
    }
    .sf-filterbar input,
    .sf-filterbar select {
        border: 1px solid var(--line); border-radius: 8px;
        padding: 7px 10px; font-size: 13px;
        background: var(--card); color: var(--ink-900); outline: none;
        font-family: inherit;
        transition: border-color 120ms;
    }
    .sf-filterbar input:focus,
    .sf-filterbar select:focus { border-color: var(--emerald-600); box-shadow: 0 0 0 2px rgba(5,150,105,0.10); }
    .sf-filterbar input { min-width: 240px; }

    /* Table card */
    .sf-table-card {
        background: var(--card);
        border: 1px solid var(--line);
        border-radius: 12px;
        overflow: hidden;
    }

    /* Row */
    .sf-row {
        display: grid;
        grid-template-columns: 44px 1fr 130px 80px 100px 110px auto;
        gap: 14px; align-items: center;
        padding: 12px 18px;
        border-bottom: 1px solid var(--line);
        transition: background 100ms;
    }
    .sf-row:last-child { border-bottom: none; }
    .sf-row:hover { background: var(--paper-2); }
    .sf-row.is-self { background: linear-gradient(90deg, rgba(5,150,105,0.04), transparent 30%); }

    .sf-head {
        background: var(--paper-2);
        border-bottom: 1px solid var(--line);
    }
    .sf-head .sf-row {
        padding: 10px 18px;
        border-bottom: none;
        font-size: 9px; text-transform: uppercase; letter-spacing: 0.16em;
        color: var(--ink-500); font-weight: 600;
        font-family: 'JetBrains Mono', monospace;
    }
    .sf-head .sf-row:hover { background: var(--paper-2); }

    .sf-avatar {
        width: 40px; height: 40px; border-radius: 10px;
        background: linear-gradient(135deg, var(--emerald-600), var(--emerald-800));
        color: #fff; display: grid; place-items: center;
        font-family: 'Fraunces', serif; font-size: 13px; font-weight: 600;
        flex-shrink: 0;
        box-shadow: 0 4px 8px -4px rgba(4,77,57,0.4), inset 0 0 0 1px rgba(212,175,55,0.2);
        overflow: hidden;
    }
    .sf-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .sf-avatar.inactive { background: linear-gradient(135deg, var(--ink-400), var(--ink-500)); opacity: 0.85; }

    .sf-name {
        display: flex; align-items: center; gap: 8px;
        font-size: 13px; font-weight: 600; color: var(--ink-900);
    }
    .sf-name-you {
        font-size: 9px; text-transform: uppercase; letter-spacing: 0.12em;
        background: var(--emerald-700); color: white;
        padding: 2px 7px; border-radius: 99px; font-weight: 700;
    }
    .sf-email {
        font-size: 11px; color: var(--ink-500); margin-top: 2px;
        font-family: 'JetBrains Mono', monospace;
    }

    .sf-role-pill {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 3px 10px; border-radius: 99px;
        font-size: 11px; font-weight: 600;
        text-transform: capitalize;
    }
    .sf-role-pill .dot { width: 5px; height: 5px; border-radius: 50%; }

    .sf-2fa {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 11px; font-weight: 600;
    }
    .sf-2fa.on { color: var(--emerald-700); }
    .sf-2fa.off { color: var(--ink-400); }
    .sf-2fa svg { width: 13px; height: 13px; }

    .sf-status-dot {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: 11px; font-weight: 600;
        text-transform: capitalize;
    }
    .sf-status-dot .dot { width: 7px; height: 7px; border-radius: 50%; }
    .sf-status-dot.active { color: var(--emerald-700); }
    .sf-status-dot.active .dot { background: var(--emerald-600); box-shadow: 0 0 0 3px rgba(5,150,105,0.18); }
    .sf-status-dot.inactive { color: var(--ink-400); }
    .sf-status-dot.inactive .dot { background: var(--ink-400); }

    .sf-time {
        font-size: 11px; color: var(--ink-500);
        font-family: 'JetBrains Mono', monospace;
    }

    .sf-actions {
        display: flex; gap: 6px; align-items: center; justify-content: flex-end;
    }
    .sf-action-btn {
        font-size: 11px; font-weight: 600;
        padding: 5px 10px; border-radius: 6px;
        background: transparent; border: 1px solid var(--line);
        color: var(--ink-700); cursor: pointer;
        text-decoration: none;
        transition: all 120ms;
        display: inline-flex; align-items: center; gap: 4px;
        font-family: inherit;
    }
    .sf-action-btn:hover { border-color: var(--emerald-600); color: var(--emerald-700); background: rgba(5,150,105,0.04); }
    .sf-action-btn.warn:hover { border-color: #d97706; color: #b45309; background: rgba(245,158,11,0.08); }
    .sf-action-btn.danger:hover { border-color: var(--danger); color: var(--danger); background: rgba(196,69,58,0.06); }
    .sf-action-btn svg { width: 11px; height: 11px; }

    /* Empty */
    .sf-empty {
        padding: 56px 24px; text-align: center; color: var(--ink-400);
    }
    .sf-empty-icon {
        width: 48px; height: 48px; border-radius: 50%;
        background: var(--paper-2); display: inline-grid; place-items: center;
        color: var(--ink-300); margin-bottom: 12px;
    }
    .sf-empty-icon svg { width: 20px; height: 20px; }

    @media (max-width: 1100px) {
        .sf-row { grid-template-columns: 40px 1fr auto; }
        .sf-role-cell, .sf-2fa-cell, .sf-status-cell, .sf-time-cell { display: none; }
    }
</style>

{{-- ── Hero ── --}}
<div class="sf-hero">
    <div style="position: relative; z-index: 1; min-width: 0;">
        <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.18em; color: var(--ink-500); font-weight: 600; font-family: 'JetBrains Mono', monospace; margin-bottom: 8px;">Role distribution</div>
        <div class="sf-role-grid">
            @foreach(['super_admin' => 'Super Admin', 'operations' => 'Operations', 'finance' => 'Finance', 'viewer' => 'Viewer'] as $key => $label)
            @php
                $count = $stats['by_role'][$key] ?? 0;
                $pct = $stats['total'] > 0 ? ($count / $stats['total']) * 100 : 0;
                $cfg = $roleColors[$key];
            @endphp
            <div class="sf-role-cell">
                <div class="sf-role-name">
                    <span class="dot" style="background: {{ $cfg['dot'] }};"></span>
                    {{ $label }}
                </div>
                <div class="sf-role-count">{{ $count }}<span class="label-inline">/ {{ $stats['total'] }}</span></div>
                <div class="sf-role-bar"><div class="sf-role-bar-fill" style="width: {{ $pct }}%; background: {{ $cfg['dot'] }};"></div></div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="sf-hero-stats" style="position: relative; z-index: 1;">
        <div class="sf-stat">
            <div class="sf-stat-value">{{ $stats['total'] }}</div>
            <div class="sf-stat-label">Total</div>
        </div>
        <div class="sf-stat">
            <div class="sf-stat-value green">{{ $stats['active'] }}</div>
            <div class="sf-stat-label">Active</div>
        </div>
        @if($stats['inactive'] > 0)
        <div class="sf-stat">
            <div class="sf-stat-value">{{ $stats['inactive'] }}</div>
            <div class="sf-stat-label">Inactive</div>
        </div>
        @endif
        <div class="sf-stat">
            <div class="sf-stat-value {{ $twoFaPct >= 80 ? 'green' : ($twoFaPct >= 50 ? '' : 'warn') }}">{{ $twoFaPct }}%</div>
            <div class="sf-stat-label">2FA on</div>
        </div>
    </div>
</div>

{{-- ── Filter bar ── --}}
<form method="GET" action="{{ route('staff.index') }}" class="sf-filterbar">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="color:var(--ink-400); flex-shrink:0;"><circle cx="11" cy="11" r="7"/><path d="m20 20-3-3"/></svg>
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or email…">

    <select name="role">
        <option value="">All roles</option>
        @foreach(['super_admin' => 'Super Admin', 'operations' => 'Operations', 'finance' => 'Finance', 'viewer' => 'Viewer'] as $key => $label)
        <option value="{{ $key }}" {{ request('role') === $key ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>

    <select name="status">
        <option value="">All statuses</option>
        <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Active</option>
        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
    </select>

    <select name="twofa">
        <option value="">All 2FA</option>
        <option value="on"  {{ request('twofa') === 'on'  ? 'selected' : '' }}>2FA on</option>
        <option value="off" {{ request('twofa') === 'off' ? 'selected' : '' }}>2FA off</option>
    </select>

    <button type="submit" class="nrh-btn nrh-btn-primary" style="font-size: 12px; padding: 6px 14px;">Filter</button>
    @if(request()->anyFilled(['search','role','status','twofa']))
    <a href="{{ route('staff.index') }}" style="font-size: 12px; color: var(--ink-500); text-decoration: none;">Clear</a>
    @endif
    <span style="margin-left: auto; font-size: 11px; color: var(--ink-500); font-family: 'JetBrains Mono', monospace; text-transform: uppercase; letter-spacing: 0.1em;">
        {{ $staff->count() }} {{ Str::plural('result', $staff->count()) }}
    </span>
</form>

{{-- ── Table ── --}}
<div class="sf-table-card">
    <div class="sf-head">
        <div class="sf-row">
            <div></div>
            <div>Name</div>
            <div>Role</div>
            <div>2FA</div>
            <div>Status</div>
            <div>Joined</div>
            <div style="text-align: right;">Actions</div>
        </div>
    </div>

    @forelse($staff as $member)
    @php
        $isSelf  = $member->id === session('admin_id');
        $cfg     = $roleColors[$member->role] ?? $roleColors['viewer'];
        $avatar  = $member->avatarUrl();
    @endphp
    <div class="sf-row {{ $isSelf ? 'is-self' : '' }}">
        <div class="sf-avatar {{ $member->status === 'inactive' ? 'inactive' : '' }}">
            @if($avatar)
                <img src="{{ $avatar }}" alt="">
            @else
                {{ $member->initials() }}
            @endif
        </div>

        <div style="min-width: 0;">
            <div class="sf-name">
                <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $member->name }}</span>
                @if($isSelf)<span class="sf-name-you">You</span>@endif
            </div>
            <div class="sf-email">{{ $member->email }}</div>
        </div>

        <div class="sf-role-cell">
            <span class="sf-role-pill" style="background: {{ $cfg['bg'] }}; color: {{ $cfg['text'] }};">
                <span class="dot" style="background: {{ $cfg['dot'] }};"></span>
                {{ str_replace('_', ' ', $member->role) }}
            </span>
        </div>

        <div class="sf-2fa-cell">
            @if($member->hasEnabledTwoFactor())
                <span class="sf-2fa on" title="Enabled {{ $member->two_factor_confirmed_at->format('d M Y') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
                    On
                </span>
            @else
                <span class="sf-2fa off">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    Off
                </span>
            @endif
        </div>

        <div class="sf-status-cell">
            <span class="sf-status-dot {{ $member->status }}">
                <span class="dot"></span>
                {{ $member->status }}
            </span>
        </div>

        <div class="sf-time-cell sf-time">{{ $member->created_at->format('d M Y') }}</div>

        <div class="sf-actions">
            <a href="{{ route('staff.permissions', $member) }}" class="sf-action-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
                Permissions
            </a>

            @if(current_admin()?->isSuperAdmin() && ! $isSelf && $member->hasEnabledTwoFactor())
            <form method="POST" action="{{ route('staff.reset-2fa', $member) }}" class="inline"
                  onsubmit="return confirm('Reset 2FA for {{ $member->name }}? They will be able to sign in with just their password and must re-enroll. This action is logged.');">
                @csrf @method('PATCH')
                <button type="submit" class="sf-action-btn warn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9c-2.6 0-5 1.1-6.7 2.9L3 8"/><path d="M3 3v5h5"/></svg>
                    Reset 2FA
                </button>
            </form>
            @endif

            @if(! $isSelf)
            <form method="POST" action="{{ route('staff.toggle', $member) }}" class="inline">
                @csrf @method('PATCH')
                <button type="submit" class="sf-action-btn {{ $member->status === 'active' ? 'danger' : '' }}">
                    @if($member->status === 'active')
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M4.93 4.93l14.14 14.14"/></svg>
                        Deactivate
                    @else
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        Activate
                    @endif
                </button>
            </form>
            @endif
        </div>
    </div>
    @empty
    <div class="sf-empty">
        <div class="sf-empty-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
        </div>
        <div style="font-size: 13px; color: var(--ink-700); font-weight: 500;">
            @if(request()->anyFilled(['search','role','status','twofa']))
                No staff match your filters
            @else
                No staff accounts yet
            @endif
        </div>
        <div style="font-size: 12px; color: var(--ink-400); margin-top: 4px;">
            @if(request()->anyFilled(['search','role','status','twofa']))
                Try clearing filters or adjusting your search.
            @else
                Click "+ New Staff" to create the first one.
            @endif
        </div>
    </div>
    @endforelse
</div>

@endsection
