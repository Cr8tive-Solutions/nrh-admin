@extends('layouts.admin')

@section('title', $customer->name)
@section('page-title', $customer->name)
@section('page-subtitle', $customer->registration_no ?? '')

@section('header-actions')
    @allowed('customer.manage')
    <a href="{{ route('customers.edit', $customer) }}" class="nrh-btn nrh-btn-ghost">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        Edit
    </a>
    @endallowed
    @allowed('pricing.manage')
    <a href="{{ route('pricing.index', ['customer_id' => $customer->id]) }}" class="nrh-btn nrh-btn-primary">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px;"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        Manage Pricing
    </a>
    @endallowed
@endsection

@section('content')

<style>
    /* ── Hero panel ── */
    .ch-hero {
        background: var(--card);
        border: 1px solid var(--line);
        border-radius: 14px;
        padding: 24px 28px;
        margin-bottom: 18px;
        display: grid;
        grid-template-columns: 80px 1fr auto;
        gap: 20px;
        align-items: center;
        position: relative;
        overflow: hidden;
    }
    .ch-hero::before {
        content: ""; position: absolute; right: -120px; top: -120px;
        width: 320px; height: 320px; border-radius: 50%;
        background: radial-gradient(circle, rgba(212,175,55,0.08), transparent 60%);
        pointer-events: none;
    }
    .ch-avatar {
        width: 80px; height: 80px;
        border-radius: 18px;
        background: linear-gradient(135deg, var(--emerald-600), var(--emerald-800));
        color: #fff;
        display: grid; place-items: center;
        font-family: 'Fraunces', serif; font-size: 30px; font-weight: 600;
        letter-spacing: 0.02em;
        box-shadow: 0 10px 22px -10px rgba(4,77,57,0.45), inset 0 0 0 1px rgba(212,175,55,0.3);
    }
    .ch-name {
        font-family: 'Fraunces', serif; font-size: 28px; font-weight: 500;
        line-height: 1.1; letter-spacing: -0.015em;
        color: var(--ink-900); margin: 0;
    }
    .ch-meta {
        display: flex; gap: 14px; align-items: center;
        margin-top: 8px;
        font-size: 12px; color: var(--ink-500);
    }
    .ch-meta-item { display: inline-flex; align-items: center; gap: 5px; }
    .ch-meta-item svg { width: 12px; height: 12px; }
    .ch-meta-sep { width: 3px; height: 3px; border-radius: 50%; background: var(--ink-300, var(--ink-400)); opacity: 0.5; }
    .ch-stats {
        display: flex; gap: 0; align-items: stretch;
    }
    .ch-stat {
        padding: 6px 18px;
        border-left: 1px solid var(--line);
        text-align: center;
        min-width: 80px;
    }
    .ch-stat:first-child { border-left: none; }
    .ch-stat-value {
        font-family: 'Fraunces', serif; font-size: 22px; font-weight: 500;
        line-height: 1; color: var(--ink-900);
    }
    .ch-stat-value.danger { color: var(--danger); }
    .ch-stat-value.warn { color: var(--gold-700, #b8860b); }
    .ch-stat-label {
        font-size: 9px; text-transform: uppercase; letter-spacing: 0.16em;
        color: var(--ink-500); margin-top: 6px;
        font-family: 'JetBrains Mono', monospace;
    }

    /* ── Tab nav ── */
    .ch-tabs {
        display: inline-flex; gap: 2px;
        background: var(--paper-2);
        border: 1px solid var(--line);
        padding: 4px;
        border-radius: 10px;
        margin-bottom: 18px;
    }
    .ch-tab {
        padding: 7px 14px;
        border-radius: 7px;
        font-size: 12px; font-weight: 600;
        color: var(--ink-500);
        background: transparent;
        border: none;
        cursor: pointer;
        transition: all 120ms ease;
        display: inline-flex; align-items: center; gap: 6px;
        font-family: inherit;
    }
    .ch-tab svg { width: 13px; height: 13px; }
    .ch-tab:hover { color: var(--ink-900); }
    .ch-tab.active {
        background: var(--card);
        color: var(--emerald-700);
        box-shadow: 0 1px 2px rgba(0,0,0,0.06), inset 0 0 0 1px var(--line);
    }
    .ch-tab .count {
        font-family: 'JetBrains Mono', monospace; font-size: 10px;
        background: var(--ink-100);
        color: var(--ink-500);
        padding: 1px 6px; border-radius: 99px;
    }
    .ch-tab.active .count {
        background: var(--emerald-50);
        color: var(--emerald-700);
    }

    /* ── Section card (matches form) ── */
    .ch-section {
        background: var(--card);
        border: 1px solid var(--line);
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 14px;
    }
    .ch-section-head {
        padding: 12px 18px;
        border-bottom: 1px solid var(--line);
        display: flex; align-items: center; gap: 10px;
        background: linear-gradient(180deg, var(--paper-2), var(--card));
    }
    .ch-section-icon {
        width: 26px; height: 26px;
        border-radius: 6px;
        display: grid; place-items: center;
        background: var(--emerald-50);
        color: var(--emerald-700);
    }
    .ch-section-icon svg { width: 13px; height: 13px; }
    .ch-section-title { font-size: 13px; font-weight: 600; color: var(--ink-900); }
    .ch-section-action { margin-left: auto; }

    .ch-info-grid {
        padding: 18px 22px;
        display: grid; grid-template-columns: repeat(2, 1fr);
        gap: 16px 28px;
    }
    .ch-info-grid .col-2 { grid-column: 1 / -1; }
    .ch-info-label {
        font-size: 9px; text-transform: uppercase; letter-spacing: 0.16em;
        color: var(--ink-500); font-weight: 600;
        font-family: 'JetBrains Mono', monospace;
    }
    .ch-info-value {
        margin-top: 4px;
        font-size: 13px;
        color: var(--ink-900);
        line-height: 1.5;
    }
    .ch-info-value.muted { color: var(--ink-400); font-style: italic; }
    .ch-info-value.mono { font-family: 'JetBrains Mono', monospace; font-size: 12px; }

    /* ── Empty state ── */
    .ch-empty {
        padding: 48px 24px;
        text-align: center;
        color: var(--ink-400);
    }
    .ch-empty-icon {
        width: 44px; height: 44px;
        border-radius: 50%;
        background: var(--paper-2);
        display: inline-grid; place-items: center;
        color: var(--ink-300);
        margin-bottom: 10px;
    }
    .ch-empty-icon svg { width: 18px; height: 18px; }
    .ch-empty-title { font-size: 13px; color: var(--ink-700); font-weight: 500; }
    .ch-empty-sub { font-size: 12px; color: var(--ink-400); margin-top: 4px; }

    /* ── Agreement cards ── */
    .ch-agreement {
        padding: 18px 22px;
        border-bottom: 1px solid var(--line);
    }
    .ch-agreement:last-child { border-bottom: none; }
    .ch-agreement-head {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 12px;
    }
    .ch-agreement-type { font-size: 14px; font-weight: 600; color: var(--ink-900); }
    .ch-agreement-grid {
        display: grid; grid-template-columns: repeat(5, 1fr); gap: 18px;
    }
    .ch-expiry-bar {
        height: 4px; border-radius: 99px; background: var(--ink-100);
        margin-top: 12px; overflow: hidden;
    }
    .ch-expiry-fill { height: 100%; border-radius: 99px; transition: width 200ms; }
</style>

<div x-data="{ tab: '{{ session('invitation_url') ? 'team' : 'info' }}' }">

{{-- ── Invitation link banner (one-shot, after create or resend) ── --}}
@if(session('invitation_url'))
<div x-data="{ open: true, copied: false, copy() { navigator.clipboard.writeText(@js(session('invitation_url'))); this.copied = true; setTimeout(() => this.copied = false, 2000); } }"
     x-show="open"
     style="background: linear-gradient(135deg, #f0fdf4, #ecfdf5); border: 1px solid #86efac; border-radius: 12px; padding: 18px 22px; margin-bottom: 16px; display: flex; align-items: center; gap: 16px;">
    <div style="width: 36px; height: 36px; border-radius: 8px; background: var(--emerald-700); color: white; display: grid; place-items: center; flex-shrink: 0;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><path d="m22 6-10 7L2 6"/></svg>
    </div>
    <div style="flex: 1;">
        <div style="font-size: 13px; font-weight: 600; color: #14532d;">Portal invitation sent</div>
        <div style="font-size: 11px; color: #166534; margin-top: 2px;">Email delivered. If it doesn't arrive, share this one-time link manually — valid for 14 days.</div>
        <div style="margin-top: 10px; display: flex; gap: 8px; align-items: center;">
            <input type="text" readonly value="{{ session('invitation_url') }}"
                   onclick="this.select()"
                   style="flex: 1; padding: 7px 10px; border: 1px solid #86efac; background: white; border-radius: 6px; font-family: 'JetBrains Mono', monospace; font-size: 11px; color: #14532d;">
            <button type="button" @click="copy()" class="nrh-btn nrh-btn-primary" style="font-size: 11px; padding: 6px 14px; flex-shrink: 0;">
                <span x-show="!copied">Copy link</span>
                <span x-show="copied" x-cloak>Copied ✓</span>
            </button>
        </div>
    </div>
    <button @click="open = false" style="background: none; border: none; cursor: pointer; color: #166534; opacity: 0.6; padding: 0; flex-shrink: 0;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
    </button>
</div>
@endif

{{-- ── Hero panel ── --}}
<div class="ch-hero">
    <div class="ch-avatar">{{ strtoupper(substr($customer->name, 0, 2)) }}</div>

    <div style="position: relative; z-index: 1;">
        <h1 class="ch-name">{{ $customer->name }}</h1>
        <div class="ch-meta">
            @if($customer->registration_no)
            <span class="ch-meta-item" style="font-family: 'JetBrains Mono', monospace;">{{ $customer->registration_no }}</span>
            <span class="ch-meta-sep"></span>
            @endif
            @if($customer->industry)
            <span class="ch-meta-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4"/></svg>
                {{ $customer->industry }}
            </span>
            <span class="ch-meta-sep"></span>
            @endif
            @if($customer->country)
            <span class="ch-meta-item">
                {{ $countryFlag }} {{ $customer->country }}
            </span>
            @endif
            @if($activeAgreement)
            <span class="ch-meta-sep"></span>
            <span class="ch-meta-item">
                @if($activeAgreement->isExpiringSoonCritical())
                    <span class="badge badge-red">Agreement expires in {{ $activeAgreement->days_left }}d</span>
                @elseif($activeAgreement->isExpiringSoon())
                    <span class="badge badge-yellow">Agreement expires in {{ $activeAgreement->days_left }}d</span>
                @else
                    <span class="badge badge-green">Agreement active · {{ $activeAgreement->days_left }}d left</span>
                @endif
            </span>
            @endif
        </div>
    </div>

    <div class="ch-stats" style="position: relative; z-index: 1;">
        <div class="ch-stat">
            <div class="ch-stat-value">{{ $stats['requests_total'] }}</div>
            <div class="ch-stat-label">Requests</div>
        </div>
        <div class="ch-stat">
            <div class="ch-stat-value {{ $stats['requests_active'] > 0 ? '' : '' }}">{{ $stats['requests_active'] }}</div>
            <div class="ch-stat-label">Active</div>
        </div>
        @if($stats['requests_flagged'] > 0)
        <div class="ch-stat">
            <div class="ch-stat-value danger">{{ $stats['requests_flagged'] }}</div>
            <div class="ch-stat-label">Flagged</div>
        </div>
        @endif
        <div class="ch-stat">
            <div class="ch-stat-value {{ $stats['invoices_unpaid'] > 0 ? 'warn' : '' }}">{{ $stats['invoices_unpaid'] }}</div>
            <div class="ch-stat-label">Unpaid</div>
        </div>
        <div class="ch-stat">
            <div class="ch-stat-value">MYR {{ number_format($customer->balance, 2) }}</div>
            <div class="ch-stat-label">Balance</div>
        </div>
    </div>
</div>

{{-- ── Tabs ── --}}
<div class="ch-tabs">
    <button @click="tab = 'info'" :class="tab === 'info' ? 'active' : ''" class="ch-tab">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4"/></svg>
        Company Info
    </button>
    <button @click="tab = 'agreement'" :class="tab === 'agreement' ? 'active' : ''" class="ch-tab">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
        Agreement
        <span class="count">{{ $customer->agreements->count() }}</span>
    </button>
    <button @click="tab = 'team'" :class="tab === 'team' ? 'active' : ''" class="ch-tab">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
        Team
        <span class="count">{{ $stats['team_members'] }}</span>
    </button>
    <button @click="tab = 'requests'" :class="tab === 'requests' ? 'active' : ''" class="ch-tab">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        Requests
        <span class="count">{{ $stats['requests_total'] }}</span>
    </button>
    <button @click="tab = 'invoices'" :class="tab === 'invoices' ? 'active' : ''" class="ch-tab">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h4"/></svg>
        Invoices
        <span class="count">{{ $customer->invoices->count() }}</span>
    </button>
    <button @click="tab = 'transactions'" :class="tab === 'transactions' ? 'active' : ''" class="ch-tab">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        Transactions
        <span class="count">{{ $customer->transactions->count() }}</span>
    </button>
</div>

{{-- ── Company Info ── --}}
<div x-show="tab === 'info'" x-cloak>
    <div class="ch-section">
        <div class="ch-section-head">
            <div class="ch-section-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4"/></svg>
            </div>
            <div class="ch-section-title">Company Details</div>
        </div>
        <div class="ch-info-grid">
            <div>
                <div class="ch-info-label">Company Name</div>
                <div class="ch-info-value" style="font-weight: 500;">{{ $customer->name }}</div>
            </div>
            <div>
                <div class="ch-info-label">Registration No.</div>
                <div class="ch-info-value mono {{ $customer->registration_no ? '' : 'muted' }}">{{ $customer->registration_no ?? '—' }}</div>
            </div>
            <div>
                <div class="ch-info-label">Industry</div>
                <div class="ch-info-value {{ $customer->industry ? '' : 'muted' }}">{{ $customer->industry ?? 'Not set' }}</div>
            </div>
            <div>
                <div class="ch-info-label">Country</div>
                <div class="ch-info-value {{ $customer->country ? '' : 'muted' }}">
                    @if($customer->country)
                        {{ $countryFlag }} {{ $customer->country }}
                    @else
                        Not set
                    @endif
                </div>
            </div>
            <div class="col-2">
                <div class="ch-info-label">Office Address</div>
                <div class="ch-info-value {{ $customer->address ? '' : 'muted' }}" style="white-space: pre-line;">{{ $customer->address ?? 'No address on file' }}</div>
            </div>
            <div>
                <div class="ch-info-label">Account Balance</div>
                <div class="ch-info-value mono" style="font-weight: 500;">MYR {{ number_format($customer->balance, 2) }}</div>
            </div>
            <div>
                <div class="ch-info-label">Customer Since</div>
                <div class="ch-info-value">{{ $customer->created_at->format('d M Y') }} <span style="color: var(--ink-400); font-size: 11px;">({{ $customer->created_at->diffForHumans() }})</span></div>
            </div>
        </div>
    </div>

    <div class="ch-section">
        <div class="ch-section-head">
            <div class="ch-section-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <div class="ch-section-title">Primary Contact</div>
        </div>
        <div class="ch-info-grid">
            <div>
                <div class="ch-info-label">Name</div>
                <div class="ch-info-value {{ $customer->contact_name ? '' : 'muted' }}">{{ $customer->contact_name ?? 'Not set' }}</div>
            </div>
            <div>
                <div class="ch-info-label">Email</div>
                <div class="ch-info-value {{ $customer->contact_email ? '' : 'muted' }}">
                    @if($customer->contact_email)
                        <a href="mailto:{{ $customer->contact_email }}" style="color: var(--emerald-700); text-decoration: none;">{{ $customer->contact_email }}</a>
                    @else
                        Not set
                    @endif
                </div>
            </div>
            <div>
                <div class="ch-info-label">Phone</div>
                <div class="ch-info-value {{ $customer->contact_phone ? '' : 'muted' }}">{{ $customer->contact_phone ?? 'Not set' }}</div>
            </div>
        </div>
    </div>
</div>

{{-- ── Agreement ── --}}
<div x-show="tab === 'agreement'" x-cloak>
    <div class="ch-section">
        <div class="ch-section-head">
            <div class="ch-section-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
            </div>
            <div class="ch-section-title">Service Agreements</div>
            @allowed('customer.manage')
            <a href="{{ route('customers.agreements.create', $customer) }}" class="nrh-btn nrh-btn-primary ch-section-action" style="font-size: 11px; padding: 5px 12px;">
                + New Agreement
            </a>
            @endallowed
        </div>

        @forelse($customer->agreements as $agreement)
        @php
            $totalDays = max(1, $agreement->start_date->diffInDays($agreement->expiry_date));
            $daysLeft = $agreement->days_left;
            $pctRemaining = max(0, min(100, ($daysLeft / $totalDays) * 100));
            $expiryColor = $agreement->isExpiringSoonCritical()
                ? 'var(--danger)'
                : ($agreement->isExpiringSoon() ? 'var(--gold-700, #b8860b)' : 'var(--emerald-600)');
        @endphp
        <div class="ch-agreement">
            <div class="ch-agreement-head">
                <div>
                    <div class="ch-agreement-type">{{ $agreement->type }}</div>
                    <div style="font-size: 11px; color: var(--ink-500); margin-top: 2px; font-family: 'JetBrains Mono', monospace; text-transform: uppercase; letter-spacing: 0.08em;">
                        {{ $agreement->start_date->format('d M Y') }} → {{ $agreement->expiry_date->format('d M Y') }}
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    @if($agreement->isExpiringSoonCritical())
                        <span class="badge badge-red">Expires in {{ $daysLeft }}d</span>
                    @elseif($agreement->isExpiringSoon())
                        <span class="badge badge-yellow">Expires in {{ $daysLeft }}d</span>
                    @else
                        <span class="badge badge-green">{{ $daysLeft }}d remaining</span>
                    @endif
                    @allowed('customer.manage')
                    <a href="{{ route('customers.agreements.edit', [$customer, $agreement]) }}" style="font-size: 11px; color: var(--emerald-700); font-weight: 600; text-decoration: none;">Edit →</a>
                    @endallowed
                </div>
            </div>

            <div class="ch-agreement-grid">
                <div>
                    <div class="ch-info-label">Start</div>
                    <div class="ch-info-value mono">{{ $agreement->start_date->format('d M Y') }}</div>
                </div>
                <div>
                    <div class="ch-info-label">Expiry</div>
                    <div class="ch-info-value mono">{{ $agreement->expiry_date->format('d M Y') }}</div>
                </div>
                <div>
                    <div class="ch-info-label">SLA TAT</div>
                    <div class="ch-info-value">{{ $agreement->sla_tat ?? '—' }}</div>
                </div>
                <div>
                    <div class="ch-info-label">Billing</div>
                    <div class="ch-info-value">{{ $agreement->billing ?? '—' }}</div>
                </div>
                <div>
                    <div class="ch-info-label">Payment</div>
                    <div class="ch-info-value">{{ $agreement->payment ?? '—' }}</div>
                </div>
            </div>

            <div class="ch-expiry-bar">
                <div class="ch-expiry-fill" style="width: {{ $pctRemaining }}%; background: {{ $expiryColor }};"></div>
            </div>
        </div>
        @empty
        <div class="ch-empty">
            <div class="ch-empty-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
            </div>
            <div class="ch-empty-title">No agreements yet</div>
            <div class="ch-empty-sub">Create one to define SLA, billing, and payment terms.</div>
        </div>
        @endforelse
    </div>
</div>

{{-- ── Team ── --}}
<div x-show="tab === 'team'" x-cloak>
    <div class="ch-section">
        <div class="ch-section-head">
            <div class="ch-section-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
            </div>
            <div class="ch-section-title">Client Portal Users</div>
            <span style="margin-left: auto; font-size: 11px; color: var(--ink-500); font-family: 'JetBrains Mono', monospace; text-transform: uppercase; letter-spacing: 0.1em;">
                {{ $stats['team_members'] }} {{ Str::plural('member', $stats['team_members']) }}
            </span>
        </div>

        @if($customer->customerUsers->isEmpty())
        <div class="ch-empty">
            <div class="ch-empty-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
            </div>
            <div class="ch-empty-title">No team members</div>
            <div class="ch-empty-sub">
                @if($customer->contact_email)
                    Provision the primary user from the contact details on file and send them an invitation.
                @else
                    Add a contact email on the company profile to enable invitations, or wait for users to self-register on the client portal.
                @endif
            </div>
            @if($customer->contact_email)
                @allowed('customer.manage')
                <form method="POST" action="{{ route('customers.provision-primary-user', $customer) }}" style="margin-top: 16px;"
                      onsubmit="return confirm('Create a primary login account for {{ $customer->contact_name }} ({{ $customer->contact_email }}) and send a portal invitation?');">
                    @csrf
                    <button type="submit" class="nrh-btn nrh-btn-primary">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" style="margin-right:4px;"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                        Provision &amp; send invitation
                    </button>
                </form>
                @endallowed
            @endif
        </div>
        @else
        <table class="nrh-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Invitation</th>
                    @allowed('customer.manage')
                    <th></th>
                    @endallowed
                </tr>
            </thead>
            <tbody>
                @foreach($customer->customerUsers as $user)
                @php $inv = $user->latestInvitation; @endphp
                <tr>
                    <td style="font-weight: 500; color: var(--ink-900);">{{ $user->name }}</td>
                    <td style="color: var(--ink-500);">{{ $user->email }}</td>
                    <td>
                        <span class="badge {{ $user->role === 'admin' ? 'badge-blue' : 'badge-gray' }}">{{ $user->role }}</span>
                    </td>
                    <td>
                        <span class="badge {{ $user->status === 'active' ? 'badge-green' : 'badge-gray' }}">{{ $user->status }}</span>
                    </td>
                    <td>
                        @if(!$inv)
                            <span class="badge badge-gray">no invitation</span>
                        @elseif($inv->isAccepted())
                            <span class="badge badge-green" title="Accepted {{ $inv->accepted_at->format('d M Y') }}">accepted</span>
                        @elseif($inv->isExpired())
                            <span class="badge badge-red" title="Expired {{ $inv->expires_at->format('d M Y') }}">expired</span>
                        @else
                            <span class="badge badge-yellow" title="Expires {{ $inv->expires_at->format('d M Y, H:i') }}">pending · {{ $inv->expires_at->diffForHumans() }}</span>
                        @endif
                    </td>
                    @allowed('customer.manage')
                    <td style="text-align: right;">
                        @if($inv && !$inv->isAccepted())
                        <form method="POST" action="{{ route('customers.users.resend-invitation', [$customer, $user]) }}" class="inline"
                              onsubmit="return confirm('Send a fresh invitation to {{ $user->email }}? Any previous invitation will be revoked.');">
                            @csrf
                            <button type="submit" style="font-size: 11px; color: var(--emerald-700); font-weight: 600; background: none; border: none; cursor: pointer; padding: 0;">
                                Resend invitation →
                            </button>
                        </form>
                        @endif
                    </td>
                    @endallowed
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

{{-- ── Requests ── --}}
<div x-show="tab === 'requests'" x-cloak>
    <div class="ch-section">
        <div class="ch-section-head">
            <div class="ch-section-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            </div>
            <div class="ch-section-title">Recent Screening Requests</div>
            <span style="margin-left: auto; font-size: 11px; color: var(--ink-500); font-family: 'JetBrains Mono', monospace; text-transform: uppercase; letter-spacing: 0.1em;">
                Showing {{ $recentRequests->count() }} of {{ $stats['requests_total'] }}
            </span>
        </div>

        @if($recentRequests->isEmpty())
        <div class="ch-empty">
            <div class="ch-empty-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            </div>
            <div class="ch-empty-title">No requests yet</div>
            <div class="ch-empty-sub">Customers submit screening requests via the client portal.</div>
        </div>
        @else
        <table class="nrh-table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Type</th>
                    <th>Candidates</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentRequests as $req)
                <tr>
                    <td style="font-family: 'JetBrains Mono', monospace; font-size: 12px; color: var(--ink-700);">{{ $req->reference }}</td>
                    <td style="color: var(--ink-500);">{{ $req->type ?? '—' }}</td>
                    <td style="color: var(--ink-500);">{{ $req->candidates->count() }}</td>
                    <td><span class="badge {{ $req->statusBadgeClass() }}">{{ str_replace('_', ' ', $req->status) }}</span></td>
                    <td style="color: var(--ink-500); font-size: 12px;">{{ $req->created_at->format('d M Y') }}</td>
                    <td style="text-align: right;"><a href="{{ route('requests.show', $req) }}" style="color: var(--emerald-700); font-size: 11px; font-weight: 600; text-decoration: none;">View →</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

{{-- ── Invoices ── --}}
<div x-show="tab === 'invoices'" x-cloak>
    <div class="ch-section">
        <div class="ch-section-head">
            <div class="ch-section-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h4"/></svg>
            </div>
            <div class="ch-section-title">Invoices</div>
            @allowed('invoice.manage')
            <a href="{{ route('invoices.create', ['customer_id' => $customer->id]) }}" class="nrh-btn nrh-btn-primary ch-section-action" style="font-size: 11px; padding: 5px 12px;">
                + New Invoice
            </a>
            @endallowed
        </div>

        @if($customer->invoices->isEmpty())
        <div class="ch-empty">
            <div class="ch-empty-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h4"/></svg>
            </div>
            <div class="ch-empty-title">No invoices yet</div>
            <div class="ch-empty-sub">Create the first invoice from the button above.</div>
        </div>
        @else
        <table class="nrh-table">
            <thead>
                <tr>
                    <th>Number</th>
                    <th>Period</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Due</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($customer->invoices as $invoice)
                <tr>
                    <td style="font-family: 'JetBrains Mono', monospace; font-size: 12px;">{{ $invoice->number }}</td>
                    <td style="color: var(--ink-500);">{{ $invoice->period }}</td>
                    <td style="font-weight: 500;">MYR {{ number_format($invoice->total, 2) }}</td>
                    <td><span class="badge {{ $invoice->statusBadgeClass() }}">{{ $invoice->status }}</span></td>
                    <td style="color: var(--ink-500); font-size: 12px;">{{ $invoice->due_at->format('d M Y') }}</td>
                    <td style="text-align: right;"><a href="{{ route('invoices.show', $invoice) }}" style="color: var(--emerald-700); font-size: 11px; font-weight: 600; text-decoration: none;">View →</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

{{-- ── Transactions ── --}}
<div x-show="tab === 'transactions'" x-cloak>
    <div class="ch-section">
        <div class="ch-section-head">
            <div class="ch-section-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <div class="ch-section-title">Transactions</div>
            @allowed('transaction.manage')
            <a href="{{ route('transactions.create', ['customer_id' => $customer->id]) }}" class="nrh-btn nrh-btn-primary ch-section-action" style="font-size: 11px; padding: 5px 12px;">
                + Record Payment
            </a>
            @endallowed
        </div>

        @if($customer->transactions->isEmpty())
        <div class="ch-empty">
            <div class="ch-empty-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <div class="ch-empty-title">No transactions yet</div>
            <div class="ch-empty-sub">Record payments and adjustments here.</div>
        </div>
        @else
        <table class="nrh-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Reference</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($customer->transactions as $tx)
                <tr>
                    <td style="color: var(--ink-500); font-size: 12px;">{{ $tx->created_at->format('d M Y') }}</td>
                    <td style="color: var(--ink-700);">
                        <span class="badge {{ $tx->type === 'topup' ? 'badge-green' : ($tx->type === 'adjustment' ? 'badge-blue' : 'badge-gray') }}">{{ $tx->type }}</span>
                    </td>
                    <td style="font-weight: 500; font-family: 'JetBrains Mono', monospace;">MYR {{ number_format($tx->amount, 2) }}</td>
                    <td style="color: var(--ink-500);">{{ $tx->method }}</td>
                    <td style="font-family: 'JetBrains Mono', monospace; font-size: 11px; color: var(--ink-500);">{{ $tx->reference ?? '—' }}</td>
                    <td><span class="badge badge-gray">{{ $tx->status }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

</div>

@endsection
