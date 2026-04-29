@extends('layouts.admin')

@section('title', $request->reference)
@section('page-title', $request->reference)
@section('page-subtitle', $request->customer->name ?? '')

@section('header-actions')
    <a href="{{ route('requests.index') }}" class="nrh-btn nrh-btn-ghost">← Back to Queue</a>
    <a href="{{ route('requests.report', ['screeningRequest' => $request, 'inline' => 1]) }}" target="_blank" class="nrh-btn nrh-btn-ghost">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        Preview Report
    </a>
    <a href="{{ route('requests.report', $request) }}" class="nrh-btn nrh-btn-primary">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" style="margin-right:4px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5M12 15V3"/></svg>
        Download PDF
    </a>
@endsection

@section('content')

@php
    // Map status keys to display config
    $statusMap = [
        'new'         => ['label' => 'New',          'color' => 'var(--ink-500)',     'bg' => 'var(--ink-100)',   'icon' => 'circle'],
        'in_progress' => ['label' => 'In progress',  'color' => 'var(--gold-700, #b8860b)', 'bg' => '#fef3c7',  'icon' => 'clock'],
        'flagged'     => ['label' => 'Flagged',      'color' => 'var(--danger)',      'bg' => '#fbeeec',          'icon' => 'flag'],
        'complete'    => ['label' => 'Complete',     'color' => 'var(--emerald-700)', 'bg' => 'var(--emerald-50)','icon' => 'check'],
    ];
    $cur = $statusMap[$request->status];
    $progressOrder = ['new', 'in_progress', 'flagged', 'complete'];
    $currentIndex = array_search($request->status, $progressOrder);
@endphp

<style>
    /* ── Hero ── */
    .rq-hero {
        background: var(--card);
        border: 1px solid var(--line);
        border-radius: 14px;
        padding: 22px 26px;
        margin-bottom: 18px;
        position: relative; overflow: hidden;
    }
    .rq-hero::before {
        content: ""; position: absolute; right: -120px; top: -120px;
        width: 320px; height: 320px; border-radius: 50%;
        background: radial-gradient(circle, rgba(212,175,55,0.06), transparent 60%);
        pointer-events: none;
    }
    .rq-hero-top {
        display: grid; grid-template-columns: 1fr auto; gap: 20px; align-items: center;
        position: relative; z-index: 1;
    }
    .rq-ref {
        font-family: 'JetBrains Mono', monospace;
        font-size: 12px; color: var(--ink-500); letter-spacing: 0.06em;
        text-transform: uppercase; margin-bottom: 4px;
    }
    .rq-title {
        font-family: 'Fraunces', serif; font-size: 28px; font-weight: 500;
        line-height: 1.1; letter-spacing: -0.015em;
        color: var(--ink-900); margin: 0;
    }
    .rq-title em { font-style: italic; color: var(--emerald-700); }
    .rq-meta {
        display: flex; gap: 14px; align-items: center; flex-wrap: wrap;
        margin-top: 8px;
        font-size: 12px; color: var(--ink-500);
    }
    .rq-meta-item { display: inline-flex; align-items: center; gap: 5px; }
    .rq-meta-item svg { width: 12px; height: 12px; }
    .rq-meta-sep { width: 3px; height: 3px; border-radius: 50%; background: var(--ink-400); opacity: 0.5; }

    .rq-status-pill {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 8px 14px;
        border-radius: 999px;
        font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em;
        background: {{ $cur['bg'] }};
        color: {{ $cur['color'] }};
    }
    .rq-status-pill .dot {
        width: 8px; height: 8px; border-radius: 50%;
        background: {{ $cur['color'] }};
        box-shadow: 0 0 0 4px rgba(0,0,0,0.04);
    }
    .rq-status-pill .dot.pulse { animation: rq-pulse 1.6s ease-in-out infinite; }
    @keyframes rq-pulse {
        0%, 100% { box-shadow: 0 0 0 0 currentColor; }
        50%      { box-shadow: 0 0 0 6px rgba(0,0,0,0); }
    }

    /* ── Workflow rail ── */
    .rq-rail {
        margin-top: 18px; padding-top: 18px;
        border-top: 1px solid var(--line);
        display: grid; grid-template-columns: repeat(4, 1fr); gap: 4px;
        position: relative; z-index: 1;
    }
    .rq-step {
        display: flex; flex-direction: column; align-items: flex-start;
        padding: 10px 12px;
        border-radius: 8px;
        border: 1px solid transparent;
        position: relative;
    }
    .rq-step + .rq-step::before {
        content: ""; position: absolute; left: -8px; top: 50%; transform: translateY(-50%);
        width: 12px; height: 1px; background: var(--ink-300, var(--line));
    }
    .rq-step-label {
        font-size: 10px; text-transform: uppercase; letter-spacing: 0.16em;
        font-family: 'JetBrains Mono', monospace; font-weight: 600;
        color: var(--ink-400);
    }
    .rq-step-bar { width: 100%; height: 3px; border-radius: 99px; background: var(--ink-100); margin-top: 8px; overflow: hidden; }
    .rq-step-bar-fill { height: 100%; border-radius: 99px; }
    .rq-step.active .rq-step-label { color: var(--ink-900); }
    .rq-step.done .rq-step-label { color: var(--emerald-700); }

    /* ── Stat strip ── */
    .rq-stats {
        display: flex; align-items: stretch; gap: 0;
    }
    .rq-stat {
        padding: 0 16px;
        border-left: 1px solid var(--line);
        text-align: center; min-width: 70px;
    }
    .rq-stat:first-child { border-left: none; padding-left: 0; }
    .rq-stat-value {
        font-family: 'Fraunces', serif; font-size: 22px; font-weight: 500;
        line-height: 1; color: var(--ink-900);
    }
    .rq-stat-value.danger { color: var(--danger); }
    .rq-stat-value.gold { color: var(--gold-700, #b8860b); }
    .rq-stat-value.green { color: var(--emerald-700); }
    .rq-stat-label {
        font-size: 9px; text-transform: uppercase; letter-spacing: 0.16em;
        color: var(--ink-500); margin-top: 6px;
        font-family: 'JetBrains Mono', monospace;
    }

    /* ── Section ── */
    .rq-section {
        background: var(--card);
        border: 1px solid var(--line);
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 14px;
    }
    .rq-section-head {
        padding: 14px 18px;
        border-bottom: 1px solid var(--line);
        display: flex; align-items: center; gap: 10px;
        background: linear-gradient(180deg, var(--paper-2), var(--card));
    }
    .rq-section-icon {
        width: 26px; height: 26px;
        border-radius: 6px;
        display: grid; place-items: center;
        background: var(--emerald-50);
        color: var(--emerald-700);
    }
    .rq-section-icon svg { width: 13px; height: 13px; }
    .rq-section-title { font-size: 13px; font-weight: 600; color: var(--ink-900); }

    /* ── Candidate row ── */
    .rq-cand {
        padding: 16px 18px;
        border-bottom: 1px solid var(--line);
        display: grid; grid-template-columns: 40px 1fr auto; gap: 14px; align-items: center;
        transition: background 100ms;
    }
    .rq-cand:last-child { border-bottom: none; }
    .rq-cand:hover { background: var(--paper-2); }
    .rq-cand-avatar {
        width: 40px; height: 40px; border-radius: 10px;
        background: linear-gradient(135deg, var(--emerald-600), var(--emerald-800));
        color: #fff; display: grid; place-items: center;
        font-family: 'Fraunces', serif; font-size: 13px; font-weight: 600;
        flex-shrink: 0;
    }
    .rq-cand-avatar.flagged { background: linear-gradient(135deg, #ef4444, #b91c1c); }
    .rq-cand-avatar.complete { background: linear-gradient(135deg, var(--emerald-500, #10b981), var(--emerald-700)); }
    .rq-cand-avatar.in_progress { background: linear-gradient(135deg, #f59e0b, #b8860b); }
    .rq-cand-name { font-size: 13px; font-weight: 600; color: var(--ink-900); }
    .rq-cand-meta {
        display: flex; gap: 10px; flex-wrap: wrap; margin-top: 3px;
        font-size: 11px; color: var(--ink-500);
    }
    .rq-cand-meta b { color: var(--ink-700); font-weight: 500; }
    .rq-cand-actions { display: flex; gap: 8px; align-items: center; }
    .rq-cand-status-select {
        font-size: 11px; padding: 5px 9px;
        border: 1px solid var(--line); background: var(--card);
        border-radius: 6px; color: var(--ink-700);
        cursor: pointer; outline: none;
    }
    .rq-cand-status-select:focus { border-color: var(--emerald-600); box-shadow: 0 0 0 2px rgba(5,150,105,0.10); }
    .rq-cand-toggle {
        font-size: 11px; padding: 5px 9px;
        background: transparent; border: 1px solid var(--line);
        border-radius: 6px; color: var(--ink-500); cursor: pointer;
        display: inline-flex; align-items: center; gap: 4px;
    }
    .rq-cand-toggle:hover { color: var(--emerald-700); border-color: var(--emerald-600); }
    .rq-cand-toggle svg { width: 11px; height: 11px; transition: transform 150ms; }
    .rq-cand-toggle.open svg { transform: rotate(180deg); }

    /* Scopes panel */
    .rq-scopes {
        grid-column: 1 / -1;
        padding-top: 14px; margin-top: 12px;
        border-top: 1px dashed var(--line);
        display: flex; flex-direction: column; gap: 4px;
    }
    .rq-scope-row {
        display: grid; grid-template-columns: 12px 1fr auto auto; gap: 12px; align-items: center;
        padding: 6px 8px; border-radius: 6px;
        font-size: 12px; color: var(--ink-700);
        transition: background 100ms;
    }
    .rq-scope-row:hover { background: var(--paper-2); }
    .rq-scope-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--ink-400); }
    .rq-scope-dot.complete { background: var(--emerald-600); }
    .rq-scope-dot.flagged { background: var(--danger); box-shadow: 0 0 0 2px rgba(239,68,68,0.15); }
    .rq-scope-dot.in_progress { background: #f59e0b; }
    .rq-scope-dot.new { background: var(--ink-400); }

    .rq-scope-tat {
        font-family: 'JetBrains Mono', monospace; font-size: 10px;
        padding: 2px 8px; border-radius: 99px; white-space: nowrap;
        display: inline-flex; align-items: center; gap: 4px;
    }
    .rq-scope-tat.running { background: var(--ink-100); color: var(--ink-500); }
    .rq-scope-tat.warning { background: #fef3c7; color: #b45309; }
    .rq-scope-tat.over    { background: #fbeeec; color: var(--danger); font-weight: 600; }
    .rq-scope-tat.within  { background: var(--emerald-50); color: var(--emerald-700); font-weight: 600; }
    .rq-scope-tat.no-target { background: transparent; color: var(--ink-400); font-style: italic; }

    .rq-scope-select {
        font-size: 11px; padding: 3px 7px;
        border: 1px solid var(--line); background: var(--card);
        border-radius: 5px; color: var(--ink-700);
        cursor: pointer; outline: none;
        font-family: inherit;
    }
    .rq-scope-select:focus { border-color: var(--emerald-600); box-shadow: 0 0 0 2px rgba(5,150,105,0.10); }
    .rq-scope-select.readonly { cursor: default; appearance: none; background: transparent; border-color: transparent; }

    /* Findings editor */
    .rq-scope-wrap { border-radius: 6px; }
    .rq-scope-wrap.has-findings .rq-scope-row { border-left: 2px solid var(--emerald-600); padding-left: 6px; }
    .rq-scope-findings-toggle {
        font-size: 10px; padding: 3px 7px; border-radius: 4px;
        background: transparent; border: 1px dashed var(--ink-300, var(--line));
        color: var(--ink-500); cursor: pointer; font-family: inherit;
        white-space: nowrap;
    }
    .rq-scope-findings-toggle:hover { color: var(--emerald-700); border-color: var(--emerald-600); border-style: solid; }
    .rq-scope-findings-toggle.has { color: var(--emerald-700); border-color: var(--emerald-600); border-style: solid; background: rgba(5,150,105,0.05); }

    .rq-findings-panel {
        margin: 4px 8px 8px 18px;
        padding: 12px 14px;
        background: var(--paper-2);
        border: 1px solid var(--line);
        border-radius: 8px;
    }
    .rq-findings-label {
        font-size: 9px; text-transform: uppercase; letter-spacing: 0.16em;
        color: var(--ink-500); font-weight: 600;
        font-family: 'JetBrains Mono', monospace;
        margin-bottom: 5px;
    }
    .rq-findings-textarea {
        width: 100%; min-height: 90px; padding: 8px 10px;
        border: 1px solid var(--line); background: var(--card);
        border-radius: 6px; font-family: inherit; font-size: 12px;
        color: var(--ink-900); outline: none; resize: vertical;
    }
    .rq-findings-textarea:focus { border-color: var(--emerald-600); box-shadow: 0 0 0 2px rgba(5,150,105,0.10); }
    .rq-findings-actions { display: flex; gap: 8px; margin-top: 10px; align-items: center; }
    .rq-record-row {
        display: grid; grid-template-columns: 140px 1fr 22px;
        gap: 6px; margin-top: 6px;
    }
    .rq-record-input {
        padding: 5px 8px; border: 1px solid var(--line);
        background: var(--card); border-radius: 5px;
        font-size: 11px; font-family: inherit; color: var(--ink-900); outline: none;
    }
    .rq-record-input:focus { border-color: var(--emerald-600); }
    .rq-record-rm-btn {
        background: transparent; border: none; cursor: pointer;
        color: var(--ink-400); font-size: 14px; padding: 0;
    }
    .rq-record-rm-btn:hover { color: var(--danger); }

    /* Meta editor */
    .rq-meta-grid {
        display: grid; grid-template-columns: 1fr 1fr; gap: 10px 12px;
        padding: 14px 18px;
    }
    .rq-meta-grid .col-2 { grid-column: 1 / -1; }
    .rq-meta-input {
        width: 100%; padding: 7px 9px;
        border: 1px solid var(--line); background: var(--card);
        border-radius: 6px; font-size: 12px; font-family: inherit;
        color: var(--ink-900); outline: none;
    }
    .rq-meta-input:focus { border-color: var(--emerald-600); box-shadow: 0 0 0 2px rgba(5,150,105,0.10); }
    .rq-meta-input.mono { font-family: 'JetBrains Mono', monospace; }

    /* ── Layout ── */
    .rq-layout { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 24px; align-items: start; }
    @media (max-width: 1100px) { .rq-layout { grid-template-columns: 1fr; } }

    /* Status update side panel */
    .rq-status-form { padding: 18px; }
    .rq-status-options {
        display: flex; flex-direction: column; gap: 6px;
    }
    .rq-status-opt {
        display: flex; align-items: center; gap: 12px;
        padding: 10px 12px;
        border: 1px solid var(--line);
        border-radius: 8px;
        cursor: pointer;
        transition: all 120ms;
    }
    .rq-status-opt:hover { border-color: var(--emerald-600); background: rgba(5,150,105,0.03); }
    .rq-status-opt input[type=radio] { accent-color: var(--emerald-700); flex-shrink: 0; }
    .rq-status-opt-dot {
        width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
    }
    .rq-status-opt-label { font-size: 13px; font-weight: 500; color: var(--ink-900); flex: 1; }
    .rq-status-opt.checked { border-color: var(--emerald-600); background: rgba(5,150,105,0.05); box-shadow: inset 0 0 0 1px var(--emerald-600); }

    .rq-empty {
        padding: 48px 24px; text-align: center; color: var(--ink-400);
    }
    .rq-empty-icon {
        width: 44px; height: 44px; border-radius: 50%;
        background: var(--paper-2); display: inline-grid; place-items: center;
        color: var(--ink-300); margin-bottom: 10px;
    }
    .rq-empty-icon svg { width: 18px; height: 18px; }
</style>

{{-- ── Hero ── --}}
<div class="rq-hero">
    <div class="rq-hero-top">
        <div>
            <div class="rq-ref">{{ $request->reference }}</div>
            <h1 class="rq-title">
                @if($request->customer)
                    <a href="{{ route('customers.show', $request->customer) }}" style="color: inherit; text-decoration: none; border-bottom: 2px solid transparent;" onmouseover="this.style.borderColor='var(--emerald-600)'" onmouseout="this.style.borderColor='transparent'">{{ $request->customer->name }}</a>
                @else
                    Unknown customer
                @endif
            </h1>
            <div class="rq-meta">
                @if($request->type)
                <span class="rq-meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 12h8M8 8h8M8 16h5"/></svg>
                    <b style="color: var(--ink-700); font-weight: 500;">{{ ucfirst($request->type) }}</b>
                </span>
                <span class="rq-meta-sep"></span>
                @endif
                <span class="rq-meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
                    Submitted by {{ $request->customerUser->name ?? '—' }}
                </span>
                <span class="rq-meta-sep"></span>
                <span class="rq-meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    {{ $request->created_at->format('d M Y, H:i') }} · {{ $request->created_at->diffForHumans() }}
                </span>
            </div>
        </div>

        <div style="display: flex; gap: 24px; align-items: center;">
            <div class="rq-status-pill">
                <span class="dot {{ in_array($request->status, ['new','in_progress']) ? 'pulse' : '' }}"></span>
                {{ $cur['label'] }}
            </div>

            <div class="rq-stats">
                <div class="rq-stat">
                    <div class="rq-stat-value">{{ $candidateStats['total'] }}</div>
                    <div class="rq-stat-label">Candidates</div>
                </div>
                @if($candidateStats['flagged'] > 0)
                <div class="rq-stat">
                    <div class="rq-stat-value danger">{{ $candidateStats['flagged'] }}</div>
                    <div class="rq-stat-label">Flagged</div>
                </div>
                @endif
                @if($candidateStats['in_progress'] > 0)
                <div class="rq-stat">
                    <div class="rq-stat-value gold">{{ $candidateStats['in_progress'] }}</div>
                    <div class="rq-stat-label">In progress</div>
                </div>
                @endif
                @if($candidateStats['complete'] > 0)
                <div class="rq-stat">
                    <div class="rq-stat-value green">{{ $candidateStats['complete'] }}</div>
                    <div class="rq-stat-label">Complete</div>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Workflow rail --}}
    <div class="rq-rail">
        @foreach($progressOrder as $i => $stepKey)
        @php
            $stepCfg = $statusMap[$stepKey];
            $isCurrent = $stepKey === $request->status;
            $isDone = $i < $currentIndex && $request->status !== 'flagged';
            $cls = $isCurrent ? 'active' : ($isDone ? 'done' : '');
            $fill = $isCurrent ? '100%' : ($isDone ? '100%' : '0%');
            $fillColor = $isCurrent ? $stepCfg['color'] : ($isDone ? 'var(--emerald-600)' : 'var(--ink-200, var(--ink-300))');
        @endphp
        <div class="rq-step {{ $cls }}">
            <div class="rq-step-label">{{ $stepCfg['label'] }}</div>
            <div class="rq-step-bar">
                <div class="rq-step-bar-fill" style="width: {{ $fill }}; background: {{ $fillColor }};"></div>
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- ── Main grid ── --}}
<div class="rq-layout">

    {{-- ── Candidates ── --}}
    <div class="rq-section" x-data="{ allOpen: false }">
        <div class="rq-section-head">
            <div class="rq-section-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
            </div>
            <div class="rq-section-title">Candidates</div>
            <span style="margin-left: auto; font-size: 11px; color: var(--ink-500); font-family: 'JetBrains Mono', monospace; text-transform: uppercase; letter-spacing: 0.1em;">
                {{ $candidateStats['total'] }} {{ Str::plural('candidate', $candidateStats['total']) }}
            </span>
        </div>

        @forelse($request->candidates as $candidate)
        @php
            $statusCfg = $statusMap[$candidate->status] ?? $statusMap['new'];
        @endphp
        <div class="rq-cand" x-data="{ open: false }" :class="open ? 'is-open' : ''">
            <div class="rq-cand-avatar {{ $candidate->status }}">
                {{ strtoupper(substr($candidate->name, 0, 2)) }}
            </div>
            <div>
                <div class="rq-cand-name">{{ $candidate->name }}</div>
                <div class="rq-cand-meta">
                    @if($candidate->identityType)
                    <span><b>{{ $candidate->identityType->name }}:</b> <span style="font-family: 'JetBrains Mono', monospace;">{{ $candidate->identity_number }}</span></span>
                    @else
                    <span style="font-family: 'JetBrains Mono', monospace;">{{ $candidate->identity_number }}</span>
                    @endif
                    @if($candidate->mobile)
                    <span>📱 {{ $candidate->mobile }}</span>
                    @endif
                    <span style="background: {{ $statusCfg['bg'] }}; color: {{ $statusCfg['color'] }}; padding: 1px 8px; border-radius: 99px; font-weight: 600; font-size: 10px; text-transform: uppercase; letter-spacing: 0.08em;">{{ $statusCfg['label'] }}</span>
                </div>
                @if($candidate->remarks)
                <div style="font-size: 11px; color: var(--ink-500); margin-top: 4px; font-style: italic;">{{ $candidate->remarks }}</div>
                @endif
            </div>
            <div class="rq-cand-actions">
                @allowed('request.update')
                <form method="POST" action="{{ route('requests.candidates.status', [$request, $candidate->id]) }}">
                    @csrf @method('PATCH')
                    <select name="status" onchange="this.form.submit()" class="rq-cand-status-select">
                        @foreach(['new', 'in_progress', 'flagged', 'complete'] as $s)
                        <option value="{{ $s }}" {{ $candidate->status === $s ? 'selected' : '' }}>{{ str_replace('_', ' ', ucfirst($s)) }}</option>
                        @endforeach
                    </select>
                </form>
                @endallowed
                <button type="button" @click="open = !open" :class="open ? 'open' : ''" class="rq-cand-toggle">
                    {{ $candidate->scopeTypes->count() }} {{ Str::plural('scope', $candidate->scopeTypes->count()) }}
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                </button>
            </div>

            <div x-show="open" x-cloak class="rq-scopes">
                @if($candidate->scopeTypes->count())
                    @foreach($candidate->scopeTypes as $scope)
                    @php
                        $pivot = $scope->pivot;
                        $sStatus = $pivot->status ?: 'new';
                        $target = $scope->turnaround_hours;
                        $tatHours = $pivot->tatHours();
                        $isRunning = $pivot->isRunning();
                        $slaState = $pivot->slaState($target);

                        // Build TAT label
                        if (! $pivot->assigned_at) {
                            $tatLabel = '—';
                            $tatClass = 'no-target';
                        } elseif ($isRunning) {
                            $tatLabel = '⏱ '.($tatHours < 1 ? round($tatHours * 60).'m' : $tatHours.'h').' running';
                            if (! $target) {
                                $tatClass = 'running';
                            } elseif ($tatHours > $target) {
                                $tatClass = 'over';
                                $tatLabel = '⚠ '.$tatHours.'h · '.round($tatHours - $target, 1).'h over';
                            } elseif ($tatHours / max(1, $target) > 0.75) {
                                $tatClass = 'warning';
                            } else {
                                $tatClass = 'running';
                            }
                        } else {
                            // Done state
                            if (! $target) {
                                $tatClass = 'within';
                                $tatLabel = '✓ '.$tatHours.'h';
                            } elseif ($slaState === 'over') {
                                $tatClass = 'over';
                                $tatLabel = '⚠ '.$tatHours.'h · '.round($tatHours - $target, 1).'h over SLA';
                            } else {
                                $tatClass = 'within';
                                $tatLabel = '✓ '.$tatHours.'h';
                            }
                        }
                    @endphp
                    @php
                        $findings = $pivot->findings ?? [];
                        $existingComment = $findings['comment'] ?? '';
                        $existingRecord  = $findings['record'] ?? [];
                        $hasFindings     = !empty($existingComment) || !empty($existingRecord);
                    @endphp
                    <div class="rq-scope-wrap {{ $hasFindings ? 'has-findings' : '' }}"
                         x-data="{
                            open: false,
                            comment: @js($existingComment),
                            record: @js(array_map(fn($k, $v) => ['key' => $k, 'value' => $v], array_keys($existingRecord), array_values($existingRecord))),
                            addRow() { this.record.push({ key: '', value: '' }); }
                         }">
                        <div class="rq-scope-row">
                            <span class="rq-scope-dot {{ $sStatus }}" title="Status: {{ str_replace('_', ' ', $sStatus) }}"></span>
                            <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                {{ $scope->name }}
                                @if($target)
                                <span style="color: var(--ink-400); font-size: 10px; font-family: 'JetBrains Mono', monospace; margin-left: 6px;">SLA {{ $target }}h</span>
                                @endif
                            </span>
                            <span class="rq-scope-tat {{ $tatClass }}" title="TAT (business hours)">{{ $tatLabel }}</span>
                            @allowed('request.update')
                            <span style="display:inline-flex; gap:6px; align-items:center;">
                                <button type="button" @click="open = !open" class="rq-scope-findings-toggle {{ $hasFindings ? 'has' : '' }}" title="Edit findings for this scope">
                                    <span x-show="!open">✎ Findings{{ $hasFindings ? ' ✓' : '' }}</span>
                                    <span x-show="open" x-cloak>Hide</span>
                                </button>
                                <form method="POST" action="{{ route('requests.scope.status', [$request, $candidate->id, $scope->id]) }}" style="margin: 0;">
                                    @csrf @method('PATCH')
                                    <select name="status" onchange="this.form.submit()" class="rq-scope-select">
                                        @foreach(['new', 'in_progress', 'flagged', 'complete'] as $s)
                                        <option value="{{ $s }}" {{ $sStatus === $s ? 'selected' : '' }}>{{ str_replace('_', ' ', ucfirst($s)) }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </span>
                            @else
                            <span style="font-size: 11px; color: var(--ink-500); padding: 3px 7px;">{{ str_replace('_', ' ', $sStatus) }}</span>
                            @endallowed
                        </div>

                        @allowed('request.update')
                        <div x-show="open" x-cloak class="rq-findings-panel">
                            <form method="POST" action="{{ route('requests.scope.findings', [$request, $candidate->id, $scope->id]) }}">
                                @csrf @method('PATCH')

                                <div class="rq-findings-label">Comment / narrative</div>
                                <textarea name="comment" x-model="comment"
                                          placeholder="e.g. NRH Intelligence's search has been completed. No adverse findings against the candidate's name and identity number."
                                          class="rq-findings-textarea"></textarea>

                                <div class="rq-findings-label" style="margin-top:12px;">Record details (optional)</div>
                                <p style="font-size: 10px; color: var(--ink-400); margin: -2px 0 4px;">
                                    Structured key/value pairs that appear under the scope's "Record" sub-table in the report. e.g. Name / Date / Court / Amount.
                                </p>
                                <template x-for="(row, idx) in record" :key="idx">
                                    <div class="rq-record-row">
                                        <input type="text" :name="`record_keys[]`" x-model="row.key"
                                               placeholder="Field name" class="rq-record-input">
                                        <input type="text" :name="`record_values[]`" x-model="row.value"
                                               placeholder="Value" class="rq-record-input">
                                        <button type="button" @click="record.splice(idx, 1)" class="rq-record-rm-btn" title="Remove">×</button>
                                    </div>
                                </template>

                                {{-- Hidden inputs that flatten to record[$key] = $value at submit --}}
                                <template x-for="row in record" :key="row.key + '|' + row.value">
                                    <input type="hidden" :name="row.key ? `record[${row.key}]` : ''" :value="row.value">
                                </template>

                                <button type="button" @click="addRow()"
                                        style="font-size: 10px; padding: 4px 9px; border-radius: 4px; border: 1px dashed var(--line); background: transparent; color: var(--emerald-700); cursor: pointer; font-weight: 600; margin-top: 6px;">
                                    + Add field
                                </button>

                                <div class="rq-findings-actions">
                                    <button type="submit" class="nrh-btn nrh-btn-primary" style="font-size: 11px; padding: 6px 14px;">Save findings</button>
                                    <button type="button" @click="open = false" style="font-size: 11px; color: var(--ink-500); background: none; border: none; cursor: pointer; padding: 6px 10px;">Cancel</button>
                                    @if($hasFindings)
                                    <span style="margin-left: auto; font-size: 10px; color: var(--ink-400); font-style: italic;">Findings on file — last edited via this form.</span>
                                    @endif
                                </div>
                            </form>
                        </div>
                        @endallowed
                    </div>
                    @endforeach
                @else
                    <p style="font-size: 11px; color: var(--ink-400); font-style: italic;">No scope checks assigned.</p>
                @endif
            </div>
        </div>
        @empty
        <div class="rq-empty">
            <div class="rq-empty-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
            </div>
            <div style="font-size: 13px; color: var(--ink-700); font-weight: 500;">No candidates submitted</div>
        </div>
        @endforelse
    </div>

    {{-- ── Side panel ── --}}
    <div>
        {{-- Status update --}}
        <div class="rq-section">
            <div class="rq-section-head">
                <div class="rq-section-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
                </div>
                <div class="rq-section-title">Update request status</div>
            </div>
            <div class="rq-status-form">
                @allowed('request.update')
                <form method="POST" action="{{ route('requests.status', $request) }}" x-data="{ chosen: '{{ $request->status }}' }">
                    @csrf @method('PATCH')
                    <div class="rq-status-options">
                        @foreach($statusMap as $key => $cfg)
                        <label class="rq-status-opt" :class="chosen === '{{ $key }}' ? 'checked' : ''">
                            <input type="radio" name="status" value="{{ $key }}"
                                   x-model="chosen"
                                   {{ $request->status === $key ? 'checked' : '' }}>
                            <span class="rq-status-opt-dot" style="background: {{ $cfg['color'] }};"></span>
                            <span class="rq-status-opt-label">{{ $cfg['label'] }}</span>
                            @if($key === $request->status)
                            <span style="font-size: 10px; color: var(--ink-400); font-family: 'JetBrains Mono', monospace; text-transform: uppercase; letter-spacing: 0.08em;">current</span>
                            @endif
                        </label>
                        @endforeach
                    </div>
                    <button type="submit" class="nrh-btn nrh-btn-primary"
                            style="display: block; width: 100%; padding: 12px 18px; margin-top: 16px; font-size: 13px; font-weight: 600;"
                            :disabled="chosen === '{{ $request->status }}'"
                            :class="chosen === '{{ $request->status }}' ? 'opacity-40 cursor-not-allowed' : ''">
                        Update Status
                    </button>
                </form>
                @else
                <p style="font-size: 12px; color: var(--ink-400); font-style: italic; margin: 0;">Read-only — you don't have permission to update request status.</p>
                @endallowed
            </div>
        </div>

        {{-- Report metadata (used in PDF cover) --}}
        @allowed('request.update')
        <div class="rq-section">
            <div class="rq-section-head">
                <div class="rq-section-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h4"/></svg>
                </div>
                <div class="rq-section-title">Report metadata</div>
            </div>
            <form method="POST" action="{{ route('requests.meta', $request) }}">
                @csrf @method('PATCH')
                <div class="rq-meta-grid">
                    <div class="col-2">
                        <div style="font-size: 9px; text-transform: uppercase; letter-spacing: 0.16em; color: var(--ink-500); font-weight: 600; font-family: 'JetBrains Mono', monospace; margin-bottom: 4px;">Research analyst</div>
                        <input type="text" name="analyst" value="{{ data_get($request->meta, 'analyst') }}" placeholder="Analyst name" class="rq-meta-input">
                    </div>
                    <div class="col-2">
                        <div style="font-size: 9px; text-transform: uppercase; letter-spacing: 0.16em; color: var(--ink-500); font-weight: 600; font-family: 'JetBrains Mono', monospace; margin-bottom: 4px;">Editor</div>
                        <input type="text" name="editor" value="{{ data_get($request->meta, 'editor') }}" placeholder="Editor name" class="rq-meta-input">
                    </div>
                    <div class="col-2">
                        <div style="font-size: 9px; text-transform: uppercase; letter-spacing: 0.16em; color: var(--ink-500); font-weight: 600; font-family: 'JetBrains Mono', monospace; margin-bottom: 4px;">Purchase order</div>
                        <input type="text" name="po_number" value="{{ data_get($request->meta, 'po_number') }}" placeholder="PO #" class="rq-meta-input mono">
                    </div>
                    <div>
                        <div style="font-size: 9px; text-transform: uppercase; letter-spacing: 0.16em; color: var(--ink-500); font-weight: 600; font-family: 'JetBrains Mono', monospace; margin-bottom: 4px;">Basic completion</div>
                        <input type="date" name="completion_basic" value="{{ data_get($request->meta, 'completion_basic') }}" class="rq-meta-input mono">
                    </div>
                    <div>
                        <div style="font-size: 9px; text-transform: uppercase; letter-spacing: 0.16em; color: var(--ink-500); font-weight: 600; font-family: 'JetBrains Mono', monospace; margin-bottom: 4px;">Prelim completion</div>
                        <input type="date" name="completion_prelim" value="{{ data_get($request->meta, 'completion_prelim') }}" class="rq-meta-input mono">
                    </div>
                    <div class="col-2">
                        <div style="font-size: 9px; text-transform: uppercase; letter-spacing: 0.16em; color: var(--ink-500); font-weight: 600; font-family: 'JetBrains Mono', monospace; margin-bottom: 4px;">Full completion</div>
                        <input type="date" name="completion_full" value="{{ data_get($request->meta, 'completion_full') }}" class="rq-meta-input mono">
                    </div>
                    <div class="col-2">
                        <button type="submit" class="nrh-btn nrh-btn-primary" style="width: 100%; padding: 9px 16px; font-size: 12px; font-weight: 600; margin-top: 4px;">Save metadata</button>
                    </div>
                </div>
            </form>
        </div>
        @endallowed

        {{-- Activity --}}
        <div class="rq-section">
            <div class="rq-section-head">
                <div class="rq-section-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                </div>
                <div class="rq-section-title">Activity</div>
            </div>
            <div style="padding: 14px 18px; font-size: 12px; color: var(--ink-700);">
                <div style="display: grid; grid-template-columns: 14px 1fr; gap: 10px; padding: 6px 0;">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: var(--emerald-600); margin-top: 4px;"></span>
                    <div>
                        <div style="font-weight: 500;">Request submitted</div>
                        <div style="color: var(--ink-400); font-size: 11px; margin-top: 2px;">{{ $request->created_at->format('d M Y, H:i') }} · {{ $request->created_at->diffForHumans() }}</div>
                    </div>
                </div>
                @if($request->updated_at->ne($request->created_at))
                <div style="display: grid; grid-template-columns: 14px 1fr; gap: 10px; padding: 6px 0;">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: var(--gold-500, #d4af37); margin-top: 4px;"></span>
                    <div>
                        <div style="font-weight: 500;">Last updated</div>
                        <div style="color: var(--ink-400); font-size: 11px; margin-top: 2px;">{{ $request->updated_at->format('d M Y, H:i') }} · {{ $request->updated_at->diffForHumans() }}</div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Meta (if any) --}}
        @if($request->meta)
        <div class="rq-section">
            <div class="rq-section-head">
                <div class="rq-section-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                </div>
                <div class="rq-section-title">Request metadata</div>
            </div>
            <pre style="padding: 14px 18px; font-size: 11px; color: var(--ink-700); margin: 0; background: var(--paper-2); overflow-x: auto; font-family: 'JetBrains Mono', monospace;">{{ json_encode($request->meta, JSON_PRETTY_PRINT) }}</pre>
        </div>
        @endif
    </div>
</div>

@endsection
