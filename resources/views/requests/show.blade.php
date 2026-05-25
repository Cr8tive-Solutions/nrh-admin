@extends('layouts.admin')

@section('title', $request->reference)
@section('page-title', $request->reference)
@section('page-subtitle', $request->customer->name ?? '')

@section('header-actions')
    <a href="{{ route('requests.index') }}" class="nrh-btn nrh-btn-ghost">← Back to Queue</a>
    <a href="{{ route('requests.report.preview', $request) }}" target="_blank" class="nrh-btn nrh-btn-ghost">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        Preview Report
    </a>
@endsection

@section('content')

@php
    // Map status keys to display config — colours mirror the client portal so customers see consistent badges.
    $statusMap = [
        'new'         => ['label' => 'New',          'color' => 'var(--ink-500)',           'bg' => 'var(--ink-100)',     'icon' => 'circle'],
        'in_progress' => ['label' => 'In progress',  'color' => 'var(--gold-700, #b8860b)', 'bg' => '#fef3c7',            'icon' => 'clock'],
        'rejected'    => ['label' => 'Rejected',     'color' => 'var(--danger)',            'bg' => '#fbeeec',            'icon' => 'x'],
        'complete'    => ['label' => 'Complete',     'color' => 'var(--emerald-700)',       'bg' => 'var(--emerald-50)',  'icon' => 'check'],
        'updated'     => ['label' => 'Updated',      'color' => 'var(--emerald-700)',       'bg' => 'var(--emerald-50)',  'icon' => 'refresh'],
    ];
    $cur = $statusMap[$request->status] ?? $statusMap['new'];
    $progressOrder = ['new', 'in_progress', 'complete'];
    $currentIndex = array_search($request->status, $progressOrder);
    if ($currentIndex === false) {
        // Side-tracks (rejected / updated) — don't anchor the rail forward.
        $currentIndex = $request->status === 'updated' ? 2 : 0;
    }
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

    /* Structured findings editor */
    .rq-findings-sublabel {
        display: block; font-size: 9px; text-transform: uppercase; letter-spacing: 0.12em;
        color: var(--ink-400); font-weight: 600; font-family: 'JetBrains Mono', monospace;
        margin-bottom: 3px; margin-top: 10px;
    }
    .rq-findings-sublabel:first-child { margin-top: 0; }
    .rq-findings-select, .rq-findings-input {
        width: 100%; padding: 5px 8px; border: 1px solid var(--line);
        background: var(--card); border-radius: 5px;
        font-size: 11px; font-family: inherit; color: var(--ink-900); outline: none; box-sizing: border-box;
    }
    .rq-findings-select:focus, .rq-findings-input:focus { border-color: var(--emerald-600); }
    .rq-findings-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
    .rq-findings-section {
        border-top: 1px solid var(--line); margin-top: 14px; padding-top: 12px;
    }
    .rq-findings-section-head { display: flex; align-items: center; justify-content: space-between; }
    .rq-rec-card {
        border: 1px solid var(--line); border-radius: 7px;
        padding: 10px 12px; margin-top: 8px; background: var(--paper-2);
    }
    .rq-rec-card-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px; }
    .rq-rec-label { font-size: 10px; font-weight: 700; color: var(--ink-700); font-family: 'JetBrains Mono', monospace; }
    .rq-rec-rm-btn {
        font-size: 10px; padding: 2px 8px; border-radius: 4px;
        border: 1px solid var(--line); background: transparent;
        color: var(--ink-500); cursor: pointer; font-family: inherit;
    }
    .rq-rec-rm-btn:hover { border-color: var(--danger); color: var(--danger); }
    .rq-add-btn {
        font-size: 10px; padding: 3px 9px; border-radius: 4px;
        border: 1px dashed var(--emerald-500); background: transparent;
        color: var(--emerald-700); cursor: pointer; font-family: inherit; font-weight: 600;
    }
    .rq-add-btn:hover { background: rgba(5,150,105,0.06); }

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

    /* AJAX inline feedback indicator next to selects */
    .ajax-state {
        display: inline-flex; align-items: center;
        font-size: 11px; font-weight: 600;
        min-width: 16px; padding: 0 4px;
        border-radius: 4px;
        white-space: nowrap;
    }
    .ajax-state.saving { color: var(--ink-400); }
    .ajax-state.saved  { color: var(--emerald-700); }
    .ajax-state.error  { color: var(--danger); font-weight: 500; }

    /* Generate report panel */
    .rq-gen-grid { padding: 14px 18px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
    .rq-gen-btn {
        background: var(--card); border: 1px solid var(--line);
        border-radius: 8px; padding: 10px 8px;
        cursor: pointer; font-family: inherit;
        display: flex; flex-direction: column; align-items: center; gap: 4px;
        transition: all 120ms;
    }
    .rq-gen-btn:hover:not([disabled]) { border-color: var(--emerald-600); background: rgba(5,150,105,0.04); }
    .rq-gen-btn[disabled] { opacity: 0.45; cursor: not-allowed; }
    .rq-gen-btn.is-saving {
        opacity: 1 !important;
        border-color: var(--emerald-600);
        background: linear-gradient(135deg, rgba(5,150,105,0.08), rgba(212,175,55,0.06));
        box-shadow: inset 0 0 0 1px var(--emerald-600);
    }
    .rq-gen-spinner {
        width: 16px; height: 16px;
        border: 2px solid var(--ink-100);
        border-top-color: var(--emerald-700);
        border-radius: 50%;
        animation: rq-spin 0.7s linear infinite;
    }
    @keyframes rq-spin {
        from { transform: rotate(0deg); }
        to   { transform: rotate(360deg); }
    }
    .rq-gen-btn-label {
        font-size: 11px; font-weight: 600;
        text-transform: uppercase; letter-spacing: 0.1em;
        color: var(--ink-900);
    }
    .rq-gen-btn-sub {
        font-size: 9px; color: var(--ink-500);
        font-family: 'JetBrains Mono', monospace;
        text-transform: uppercase; letter-spacing: 0.08em;
    }

    /* Versions list */
    .rq-version-row {
        display: grid; grid-template-columns: 1fr auto auto; gap: 10px; align-items: center;
        padding: 10px 14px;
        border-bottom: 1px solid var(--line);
        font-size: 12px;
    }
    .rq-version-row:last-child { border-bottom: none; }
    .rq-version-row.is-superseded .rq-version-label { text-decoration: line-through; color: var(--ink-500); }
    .rq-version-label { font-weight: 600; color: var(--ink-900); }
    .rq-version-meta {
        font-size: 10px; color: var(--ink-500);
        font-family: 'JetBrains Mono', monospace;
        margin-top: 2px;
    }
    .rq-superseded-badge {
        display: inline-block; padding: 1px 7px; border-radius: 99px;
        background: rgba(196,69,58,0.08); color: var(--danger);
        font-size: 9px; font-weight: 600; text-transform: uppercase;
        letter-spacing: 0.08em; margin-left: 6px;
    }
    .rq-version-actions { display: flex; gap: 4px; }
    .rq-version-action-btn {
        font-size: 10px; padding: 3px 7px;
        border-radius: 4px; background: transparent;
        border: 1px solid var(--line); color: var(--ink-500);
        cursor: pointer; text-decoration: none;
        font-family: inherit;
    }
    .rq-version-action-btn:hover { border-color: var(--emerald-600); color: var(--emerald-700); }

    /* Supersede modal */
    .rq-modal-backdrop {
        position: fixed; inset: 0; background: rgba(0,0,0,0.4);
        z-index: 100; display: grid; place-items: center;
        backdrop-filter: blur(4px);
    }
    .rq-modal {
        background: var(--card); border: 1px solid var(--line);
        border-radius: 12px; max-width: 460px; width: 90%;
        box-shadow: 0 20px 40px -12px rgba(0,0,0,0.3);
    }
    .rq-modal-head { padding: 16px 20px; border-bottom: 1px solid var(--line); }
    .rq-modal-head h3 { margin: 0; font-size: 14px; font-weight: 600; color: var(--ink-900); }
    .rq-modal-head p { margin: 4px 0 0; font-size: 12px; color: var(--ink-500); }
    .rq-modal-body { padding: 16px 20px; }
    .rq-modal-textarea {
        width: 100%; min-height: 80px; padding: 9px 11px;
        border: 1px solid var(--line); border-radius: 6px;
        font-size: 12px; font-family: inherit; outline: none; resize: vertical;
    }
    .rq-modal-textarea:focus { border-color: var(--emerald-600); box-shadow: 0 0 0 2px rgba(5,150,105,0.10); }
    .rq-modal-foot { padding: 12px 20px; border-top: 1px solid var(--line); display: flex; gap: 8px; justify-content: flex-end; background: var(--paper-2); border-radius: 0 0 12px 12px; }

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

{{-- ── Cash-billing payment banner ── --}}
@if($awaitingPayment)
@php
    $hasSlip = $request->hasPaymentSlip();
@endphp
<div style="background: linear-gradient(135deg, rgba(212,175,55,0.10), rgba(212,175,55,0.04));
            border: 1px solid rgba(212,175,55,0.40);
            border-radius: 14px; padding: 18px 22px; margin-bottom: 14px;
            display: grid; grid-template-columns: auto 1fr auto; gap: 18px; align-items: center;">
    <div style="width: 40px; height: 40px; border-radius: 10px;
                background: var(--gold-700, #b8860b); color: white;
                display: grid; place-items: center; flex-shrink: 0;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
    </div>
    <div>
        <div style="font-size: 13px; font-weight: 600; color: var(--gold-700, #b8860b); display: flex; align-items: center; gap: 8px;">
            Awaiting payment · Cash customer
            <span style="font-size: 10px; padding: 2px 8px; border-radius: 99px; background: var(--gold-700, #b8860b); color: white; font-family: 'JetBrains Mono', monospace; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 600;">Per request</span>
        </div>
        <div style="font-size: 12px; color: var(--ink-700); margin-top: 4px; line-height: 1.55;">
            Expected amount: <strong style="font-family: 'JetBrains Mono', monospace; color: var(--ink-900);">MYR {{ number_format($paymentAmount, 2) }}</strong>
            · reference <strong style="font-family: 'JetBrains Mono', monospace; color: var(--ink-900);">{{ $request->reference }}</strong>.
            @if($hasSlip)
                Customer uploaded a slip on {{ $request->payment_slip_uploaded_at?->format('d M Y · H:i') }} — review and verify below.
            @else
                Waiting for the customer to upload their bank-transfer slip. Verification is blocked until they do.
            @endif
        </div>
    </div>
    @allowed('transaction.manage')
    <form method="POST" action="{{ route('requests.verify-payment', $request) }}"
          x-data="ajaxForm({ onSuccess: () => { window.location.reload(); } })"
          @submit.prevent="submit($event)" style="margin: 0;">
        @csrf
        <button type="submit"
                @if(!$hasSlip) disabled @endif
                title="{{ $hasSlip ? 'Verify the customer\'s slip and start processing' : 'Customer must upload their slip first' }}"
                style="background: var(--gold-700, #b8860b); color: white;
                       border: none; border-radius: 8px;
                       padding: 10px 18px; font-size: 12px; font-weight: 600;
                       text-transform: uppercase; letter-spacing: 0.08em;
                       cursor: {{ $hasSlip ? 'pointer' : 'not-allowed' }}; display: inline-flex; align-items: center; gap: 7px;
                       white-space: nowrap; font-family: inherit;
                       box-shadow: 0 4px 10px -4px rgba(184,134,11,0.45);
                       opacity: {{ $hasSlip ? '1' : '0.5' }};"
                :disabled="state === 'saving' || {{ $hasSlip ? 'false' : 'true' }}"
                :style="state === 'saving' ? 'opacity: 0.6; cursor: wait;' : ''"
                onmouseover="if(!this.disabled) this.style.background='#9a7209'"
                onmouseout="this.style.background='var(--gold-700, #b8860b)'">
            <template x-if="state === 'saving'">
                <span style="width: 12px; height: 12px; border: 2px solid rgba(255,255,255,0.4); border-top-color: white; border-radius: 50%; display: inline-block; animation: rq-spin 0.7s linear infinite;"></span>
            </template>
            <template x-if="state !== 'saving'">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6 9 17l-5-5"/></svg>
            </template>
            <span x-show="state !== 'saving'">Verify payment</span>
            <span x-show="state === 'saving'" x-cloak>Verifying…</span>
        </button>
    </form>
    @endallowed
</div>

{{-- Slip viewer — sits between banner and rest of page when a slip exists --}}
@if($hasSlip)
<div style="background: var(--card, #fff); border: 1px solid var(--line, #e5e7eb);
            border-radius: 12px; padding: 14px 18px; margin-bottom: 14px;
            display: flex; align-items: center; gap: 14px;">
    <div style="width: 32px; height: 32px; border-radius: 8px;
                background: rgba(212,175,55,0.12); color: var(--gold-700, #b8860b);
                display: grid; place-items: center; flex-shrink: 0;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
    </div>
    <div style="flex: 1;">
        <div style="font-size: 12px; font-weight: 600; color: var(--ink-900);">Payment slip uploaded</div>
        <div style="font-size: 11px; color: var(--ink-600); margin-top: 2px;">
            {{ $request->payment_slip_uploaded_at?->format('d M Y · H:i') }}
            · file: <span style="font-family: 'JetBrains Mono', monospace;">{{ basename($request->payment_slip_path) }}</span>
        </div>
    </div>
    <a href="{{ route('requests.payment-slip.download', $request) }}"
       style="background: var(--ink-900); color: white; border-radius: 8px;
              padding: 8px 14px; font-size: 11px; font-weight: 600;
              text-decoration: none; text-transform: uppercase; letter-spacing: 0.08em;
              display: inline-flex; align-items: center; gap: 6px;">
        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5M12 15V3"/></svg>
        View slip
    </a>
</div>
@endif
@elseif($isCashBilled)
{{-- Already in progress / past stage — quiet info chip so ops know billing terms at a glance. --}}
<div style="background: rgba(212,175,55,0.05); border: 1px solid rgba(212,175,55,0.25);
            border-radius: 10px; padding: 8px 14px; margin-bottom: 14px;
            font-size: 11px; color: var(--gold-700, #b8860b); font-weight: 500;
            display: flex; align-items: center; gap: 8px;">
    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/></svg>
    Cash customer · payment confirmed for this request
</div>
@endif

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
            $isDone = $i < $currentIndex;
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
        <div class="rq-cand" x-data="{ open: false, consentOpen: false }" :class="open ? 'is-open' : ''">
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

                @php $consent = $candidate->latestConsent; @endphp
                <div style="margin-top: 6px; display: flex; align-items: center; gap: 6px;">
                    @if($consent)
                        <span style="font-size: 10px; color: var(--emerald-700); font-weight: 600; display: inline-flex; align-items: center; gap: 4px;" title="Consent on file: {{ $consent->consented_at->format('d M Y') }} via {{ \App\Models\ConsentRecord::evidenceTypes()[$consent->evidence_type] ?? $consent->evidence_type }}">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
                            Consent · {{ $consent->consented_at->format('d M Y') }}
                        </span>
                    @else
                        <span style="font-size: 10px; color: var(--gold-700, #b8860b); font-weight: 600; display: inline-flex; align-items: center; gap: 4px;" title="No consent record on file — required by PDPA">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                            Consent missing
                        </span>
                    @endif
                    @allowed('pdpa.consent')
                    <button type="button" @click="consentOpen = !consentOpen"
                            style="font-size: 10px; padding: 2px 7px; border-radius: 4px;
                                   background: transparent; border: 1px dashed var(--line);
                                   color: var(--ink-500); cursor: pointer; font-family: inherit;">
                        <span x-show="!consentOpen">{{ $consent ? 'Update' : 'Add consent' }}</span>
                        <span x-show="consentOpen" x-cloak>Cancel</span>
                    </button>
                    @endallowed
                </div>
            </div>
            <div class="rq-cand-actions">
                @allowed('request.update')
                <form method="POST" action="{{ route('requests.candidates.status', [$request, $candidate->id]) }}"
                      x-data="ajaxForm()" @submit.prevent="submit($event)">
                    @csrf @method('PATCH')
                    <select name="status" @change="$el.form.requestSubmit()" class="rq-cand-status-select">
                        @foreach(['new', 'in_progress', 'flagged', 'complete'] as $s)
                        <option value="{{ $s }}" {{ $candidate->status === $s ? 'selected' : '' }}>{{ str_replace('_', ' ', ucfirst($s)) }}</option>
                        @endforeach
                    </select>
                    <span class="ajax-state" x-show="state" x-cloak
                          :class="{ 'saving': state==='saving', 'saved': state==='saved', 'error': state==='error' }">
                        <span x-show="state === 'saving'">·</span>
                        <span x-show="state === 'saved'">✓</span>
                        <span x-show="state === 'error'" x-text="message"></span>
                    </span>
                </form>
                @endallowed
                <button type="button" @click="open = !open" :class="open ? 'open' : ''" class="rq-cand-toggle">
                    {{ $candidate->scopeTypes->count() }} {{ Str::plural('scope', $candidate->scopeTypes->count()) }}
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                </button>
            </div>

            @allowed('pdpa.consent')
            <div x-show="consentOpen" x-cloak
                 style="grid-column: 1 / -1; padding-top: 14px; margin-top: 12px; border-top: 1px dashed var(--gold-500, #d4af37); padding: 14px; background: rgba(212,175,55,0.04); border-radius: 8px;">
                <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.16em; color: var(--gold-700, #b8860b); font-weight: 600; margin-bottom: 8px; font-family: 'JetBrains Mono', monospace;">
                    Record consent — PDPA evidence
                </div>
                <form method="POST" action="{{ route('compliance.consent.store', [$request, $candidate->id]) }}"
                      enctype="multipart/form-data"
                      x-data="ajaxForm({ onSuccess: () => { window.location.reload(); } })"
                      @submit.prevent="submit($event)">
                    @csrf
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div>
                            <div style="font-size: 9px; text-transform: uppercase; letter-spacing: 0.14em; color: var(--ink-500); font-weight: 600; margin-bottom: 4px;">Consented at <span style="color: var(--danger);">*</span></div>
                            <input type="datetime-local" name="consented_at" required value="{{ now()->format('Y-m-d\TH:i') }}"
                                   style="width: 100%; padding: 6px 9px; border: 1px solid var(--line); border-radius: 6px; font-size: 12px; font-family: 'JetBrains Mono', monospace; outline: none;">
                        </div>
                        <div>
                            <div style="font-size: 9px; text-transform: uppercase; letter-spacing: 0.14em; color: var(--ink-500); font-weight: 600; margin-bottom: 4px;">Evidence type <span style="color: var(--danger);">*</span></div>
                            <select name="evidence_type" required style="width: 100%; padding: 6px 9px; border: 1px solid var(--line); border-radius: 6px; font-size: 12px; outline: none;">
                                @foreach(\App\Models\ConsentRecord::evidenceTypes() as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div style="grid-column: 1 / -1;">
                            <div style="font-size: 9px; text-transform: uppercase; letter-spacing: 0.14em; color: var(--ink-500); font-weight: 600; margin-bottom: 4px;">Evidence file (optional)</div>
                            <input type="file" name="evidence_file" accept=".pdf,.jpg,.jpeg,.png"
                                   style="width: 100%; font-size: 11px; padding: 4px 0;">
                            <p style="font-size: 9px; color: var(--ink-400); margin: 2px 0 0;">PDF / JPG / PNG, max 5 MB. Stored privately, accessible only to admins with pdpa.consent permission.</p>
                        </div>
                        <div style="grid-column: 1 / -1;">
                            <div style="font-size: 9px; text-transform: uppercase; letter-spacing: 0.14em; color: var(--ink-500); font-weight: 600; margin-bottom: 4px;">Notes</div>
                            <textarea name="notes" rows="2" placeholder="Internal note about this consent capture (optional)"
                                      style="width: 100%; padding: 7px 10px; border: 1px solid var(--line); border-radius: 6px; font-size: 12px; font-family: inherit; outline: none; resize: vertical;"></textarea>
                        </div>
                    </div>
                    <div style="margin-top: 10px; display: flex; gap: 8px; align-items: center;">
                        <button type="submit" class="nrh-btn nrh-btn-primary"
                                style="font-size: 11px; padding: 6px 14px;"
                                :disabled="state === 'saving'">
                            <span x-show="state !== 'saving'">Save consent record</span>
                            <span x-show="state === 'saving'" x-cloak>Saving…</span>
                        </button>
                        <span x-show="state === 'error'" x-cloak x-text="message"
                              style="font-size: 11px; color: var(--danger); font-weight: 500;"></span>
                    </div>
                </form>

                @if($consent)
                <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--line); font-size: 11px; color: var(--ink-500);">
                    <strong>Latest record:</strong> {{ $consent->consented_at->format('d M Y, H:i') }}
                    via {{ \App\Models\ConsentRecord::evidenceTypes()[$consent->evidence_type] ?? $consent->evidence_type }}
                    · captured by {{ $consent->capturedBy?->name ?? '—' }}
                    @if($consent->evidence_file_path)
                    · <a href="{{ route('compliance.consent.evidence', $consent) }}" style="color: var(--emerald-700); font-weight: 600;">
                        {{ $consent->evidence_type === 'paper_signed' ? 'View signed consent form →' : 'View file →' }}
                    </a>
                    @endif
                </div>
                @endif
            </div>
            @endallowed

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
                        if ($request->isTatPaused()) {
                            // Request was rejected — TAT clock should not run.
                            $tatLabel = 'Paused';
                            $tatClass = 'no-target';
                        } elseif (! $pivot->assigned_at) {
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
                        $existingResultType         = $findings['result_type'] ?? 'clean';
                        $existingRiskLevel          = $findings['risk_level'] ?? 'nil';
                        $existingRiskStatusText     = $findings['risk_status_text'] ?? '';
                        $existingImplication        = $findings['implication'] ?? '';
                        $existingVerificationMethod = $findings['verification_method'] ?? '';
                        $existingScopeDescription   = $findings['scope_description'] ?? '';
                        // Convert records: fields {k:v} map → [{key,value}] array for Alpine
                        $existingRecords = array_map(function ($rec) {
                            $fa = [];
                            foreach ($rec['fields'] ?? [] as $k => $v) { $fa[] = ['key' => $k, 'value' => $v]; }
                            return array_merge($rec, ['fields' => $fa]);
                        }, $findings['records'] ?? []);
                        $hasFindings = !empty($findings['result_type']) || !empty($findings['records']) || !empty($findings['comment']);
                    @endphp
                    <div class="rq-scope-wrap {{ $hasFindings ? 'has-findings' : '' }}"
                         x-data="{
                            open: false,
                            result_type: @js($existingResultType),
                            risk_level: @js($existingRiskLevel),
                            risk_status_text: @js($existingRiskStatusText),
                            implication: @js($existingImplication),
                            verification_method: @js($existingVerificationMethod),
                            scope_description: @js($existingScopeDescription),
                            records: @js($existingRecords),
                            addRecord() {
                                this.records.push({ title: '', act: '', section: '', description: '', penalty: '', fields: [], verdict: 'CONVICTED', risk_level: 'high', risk_text: '' });
                            },
                            removeRecord(idx) { this.records.splice(idx, 1); },
                            addField(idx) { this.records[idx].fields.push({ key: '', value: '' }); },
                            removeField(idx, fIdx) { this.records[idx].fields.splice(fIdx, 1); },
                            onResultTypeChange() {
                                const isClean = this.result_type === 'clean' || this.result_type === 'not_requested';
                                if (isClean && this.risk_level !== 'nil') this.risk_level = 'nil';
                                if (!isClean && this.risk_level === 'nil') this.risk_level = 'high';
                            }
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
                                <form method="POST" action="{{ route('requests.scope.status', [$request, $candidate->id, $scope->id]) }}"
                                      style="margin: 0; display:inline-flex; align-items:center; gap:4px;"
                                      x-data="ajaxForm()" @submit.prevent="submit($event)">
                                    @csrf @method('PATCH')
                                    <select name="status" @change="$el.form.requestSubmit()" class="rq-scope-select">
                                        @foreach(['new', 'in_progress', 'flagged', 'complete'] as $s)
                                        <option value="{{ $s }}" {{ $sStatus === $s ? 'selected' : '' }}>{{ str_replace('_', ' ', ucfirst($s)) }}</option>
                                        @endforeach
                                    </select>
                                    <span class="ajax-state" x-show="state" x-cloak
                                          :class="{ 'saving': state==='saving', 'saved': state==='saved', 'error': state==='error' }">
                                        <span x-show="state === 'saving'">·</span>
                                        <span x-show="state === 'saved'">✓</span>
                                        <span x-show="state === 'error'" x-text="message"></span>
                                    </span>
                                </form>
                            </span>
                            @else
                            <span style="font-size: 11px; color: var(--ink-500); padding: 3px 7px;">{{ str_replace('_', ' ', $sStatus) }}</span>
                            @endallowed
                        </div>

                        @allowed('request.update')
                        <div x-show="open" x-cloak class="rq-findings-panel">
                            <form method="POST" action="{{ route('requests.scope.findings', [$request, $candidate->id, $scope->id]) }}"
                                  x-data="ajaxForm()" @submit.prevent="submit($event)">
                                @csrf @method('PATCH')

                                {{-- ── Assessment ─────────────────────────────────── --}}
                                <div class="rq-findings-label">Assessment</div>
                                <div class="rq-findings-grid-2">
                                    <div>
                                        <span class="rq-findings-sublabel" style="margin-top:0;">Result Type</span>
                                        <select name="result_type" x-model="result_type" @change="onResultTypeChange()" class="rq-findings-select">
                                            <option value="clean">Clean</option>
                                            <option value="record_identified">Record Identified</option>
                                            <option value="adverse">Adverse</option>
                                            <option value="not_requested">Not Requested</option>
                                        </select>
                                    </div>
                                    <div>
                                        <span class="rq-findings-sublabel" style="margin-top:0;">Risk Level</span>
                                        <select name="risk_level" x-model="risk_level" class="rq-findings-select">
                                            <option value="nil">Nil</option>
                                            <option value="low">Low</option>
                                            <option value="medium">Medium</option>
                                            <option value="high">High</option>
                                        </select>
                                    </div>
                                </div>

                                <span class="rq-findings-sublabel">Risk Status Text</span>
                                <input type="text" name="risk_status_text" x-model="risk_status_text"
                                       placeholder="e.g. Low Risk – Candidate cleared all checks."
                                       class="rq-findings-input">

                                <span class="rq-findings-sublabel">Implication</span>
                                <input type="text" name="implication" x-model="implication"
                                       placeholder="e.g. No Issues Found"
                                       class="rq-findings-input">

                                <span class="rq-findings-sublabel">Verification Method</span>
                                <textarea name="verification_method" x-model="verification_method"
                                          placeholder="e.g. Verification was conducted against official government databases…"
                                          class="rq-findings-textarea" style="min-height:56px;"></textarea>

                                <span class="rq-findings-sublabel">Scope Description</span>
                                <textarea name="scope_description" x-model="scope_description"
                                          placeholder="e.g. Screening conducted against the Royal Malaysia Police criminal records database…"
                                          class="rq-findings-textarea" style="min-height:56px;"></textarea>

                                {{-- ── Adverse Records (only when applicable) ──────── --}}
                                <div class="rq-findings-section"
                                     x-show="result_type === 'record_identified' || result_type === 'adverse'">
                                    <div class="rq-findings-section-head">
                                        <div class="rq-findings-label" style="margin:0;">Adverse Records</div>
                                        <button type="button" @click="addRecord()" class="rq-add-btn">+ Add Record</button>
                                    </div>

                                    <template x-if="records.length === 0">
                                        <p style="font-size:11px;color:var(--ink-400);font-style:italic;margin:8px 0 0;">No records added yet.</p>
                                    </template>

                                    <template x-for="(rec, recIdx) in records" :key="recIdx">
                                        <div class="rq-rec-card">
                                            <div class="rq-rec-card-head">
                                                <span class="rq-rec-label" x-text="`Record ${recIdx + 1}`"></span>
                                                <button type="button" @click="removeRecord(recIdx)" class="rq-rec-rm-btn">× Remove</button>
                                            </div>

                                            <div class="rq-findings-grid-2">
                                                <div>
                                                    <span class="rq-findings-sublabel">Title (Malay law name)</span>
                                                    <input type="text" x-model="rec.title"
                                                           placeholder="e.g. AKTA DADAH BERBAHAYA 1952"
                                                           class="rq-findings-input">
                                                </div>
                                                <div>
                                                    <span class="rq-findings-sublabel">Act (English name)</span>
                                                    <input type="text" x-model="rec.act"
                                                           placeholder="e.g. Dangerous Drugs Act 1952"
                                                           class="rq-findings-input">
                                                </div>
                                                <div>
                                                    <span class="rq-findings-sublabel">Section</span>
                                                    <input type="text" x-model="rec.section"
                                                           placeholder="e.g. Section 15(1)(A)"
                                                           class="rq-findings-input">
                                                </div>
                                                <div>
                                                    <span class="rq-findings-sublabel">Verdict</span>
                                                    <select x-model="rec.verdict" class="rq-findings-select">
                                                        <option value="CONVICTED">Convicted</option>
                                                        <option value="ACQUITTED">Acquitted</option>
                                                        <option value="DISCHARGED">Discharged</option>
                                                        <option value="PENDING">Pending</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <span class="rq-findings-sublabel">Description</span>
                                            <textarea x-model="rec.description"
                                                      placeholder="Offence description text"
                                                      class="rq-findings-textarea" style="min-height:48px;"></textarea>

                                            <div class="rq-findings-grid-2">
                                                <div>
                                                    <span class="rq-findings-sublabel">Penalty</span>
                                                    <input type="text" x-model="rec.penalty"
                                                           placeholder="e.g. Fine up to RM5,000 or 2 years"
                                                           class="rq-findings-input">
                                                </div>
                                                <div>
                                                    <span class="rq-findings-sublabel">Record Risk Level</span>
                                                    <select x-model="rec.risk_level" class="rq-findings-select">
                                                        <option value="high">High</option>
                                                        <option value="medium">Medium</option>
                                                        <option value="low">Low</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <span class="rq-findings-sublabel">Risk Text</span>
                                            <input type="text" x-model="rec.risk_text"
                                                   placeholder="e.g. High – Criminal conviction recorded"
                                                   class="rq-findings-input">

                                            {{-- Additional fields (Place of Offence, Date, etc.) --}}
                                            <div style="margin-top:10px;">
                                                <div style="display:flex;align-items:center;justify-content:space-between;">
                                                    <span class="rq-findings-sublabel" style="margin:0;">Additional Fields</span>
                                                    <button type="button" @click="addField(recIdx)" class="rq-add-btn">+ Field</button>
                                                </div>
                                                <template x-for="(field, fIdx) in rec.fields" :key="fIdx">
                                                    <div class="rq-record-row" style="margin-top:4px;">
                                                        <input type="text" x-model="field.key" placeholder="Field name" class="rq-record-input">
                                                        <input type="text" x-model="field.value" placeholder="Value" class="rq-record-input">
                                                        <button type="button" @click="removeField(recIdx, fIdx)" class="rq-record-rm-btn" title="Remove">×</button>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                {{-- Serialise records array as JSON for submission --}}
                                <input type="hidden" name="records_json" :value="JSON.stringify(records)">

                                {{-- ── Actions ─────────────────────────────────────── --}}
                                <div class="rq-findings-actions">
                                    <button type="submit" class="nrh-btn nrh-btn-primary" style="font-size: 11px; padding: 6px 14px;"
                                            :disabled="state === 'saving'">
                                        <span x-show="state !== 'saving'">Save findings</span>
                                        <span x-show="state === 'saving'" x-cloak>Saving…</span>
                                    </button>
                                    <button type="button" @click="open = false" style="font-size: 11px; color: var(--ink-500); background: none; border: none; cursor: pointer; padding: 6px 10px;">Cancel</button>
                                    <span class="ajax-state" x-show="state === 'saved' || state === 'error'" x-cloak
                                          :class="{ 'saved': state==='saved', 'error': state==='error' }"
                                          style="font-size: 11px; font-weight: 600;">
                                        <span x-show="state === 'saved'">✓ Saved</span>
                                        <span x-show="state === 'error'" x-text="message"></span>
                                    </span>
                                    @if($hasFindings)
                                    <span style="margin-left: auto; font-size: 10px; color: var(--ink-400); font-style: italic;" x-show="state !== 'saved' && state !== 'error'">Findings on file.</span>
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
                <div x-data="{ chosen: '{{ $request->status }}', current: '{{ $request->status }}', rejectionReason: @js($request->rejection_reason ?? '') }">
                <form method="POST" action="{{ route('requests.status', $request) }}"
                      x-data="ajaxForm({ onSuccess: () => { current = chosen; } })"
                      @submit.prevent="submit($event)">
                    @csrf @method('PATCH')
                    <div class="rq-status-options">
                        @foreach($statusMap as $key => $cfg)
                        <label class="rq-status-opt" :class="chosen === '{{ $key }}' ? 'checked' : ''">
                            <input type="radio" name="status" value="{{ $key }}"
                                   x-model="chosen"
                                   {{ $request->status === $key ? 'checked' : '' }}>
                            <span class="rq-status-opt-dot" style="background: {{ $cfg['color'] }};
                                {{ $cfg['color'] === '#ffffff' ? 'border: 1px solid var(--ink-300);' : '' }}"></span>
                            <span class="rq-status-opt-label">{{ $cfg['label'] }}</span>
                            @if($key === $request->status)
                            <span style="font-size: 10px; color: var(--ink-400); font-family: 'JetBrains Mono', monospace; text-transform: uppercase; letter-spacing: 0.08em;">current</span>
                            @endif
                        </label>
                        @endforeach
                    </div>

                    {{-- Rejection reason — required when chosen is 'rejected'. Persisted on screening_requests.rejection_reason and surfaced on the client portal. --}}
                    <div x-show="chosen === 'rejected'" x-cloak x-transition
                         style="margin-top: 14px; padding: 12px 14px; border-radius: 8px;
                                background: #fbeeec; border: 1px solid rgba(196,69,58,0.25);">
                        <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.16em;
                                    color: var(--danger); font-weight: 600; font-family: 'JetBrains Mono', monospace;
                                    margin-bottom: 6px; display: flex; align-items: center; gap: 6px;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>
                            Reason for rejection <span style="color: var(--danger);">*</span>
                        </div>
                        <textarea name="rejection_reason" x-model="rejectionReason" rows="3" maxlength="2000"
                                  placeholder="e.g. Candidate identification document is illegible. Please re-upload a clearer scan."
                                  style="width: 100%; padding: 8px 10px; border: 1px solid rgba(196,69,58,0.30);
                                         background: var(--card); border-radius: 6px; font-family: inherit;
                                         font-size: 12px; color: var(--ink-900); outline: none; resize: vertical;"
                                  :required="chosen === 'rejected'"></textarea>
                        <p style="font-size: 10px; color: var(--ink-500); margin: 6px 0 0; line-height: 1.5;">
                            The customer will see this reason on the client portal under their request detail.
                            Be specific so they know how to remedy and resubmit.
                        </p>
                    </div>

                    {{-- If a reason was previously set but admin is moving away from 'rejected', show a quiet note that it'll be cleared. --}}
                    @if($request->rejection_reason)
                    <div x-show="current === 'rejected' && chosen !== 'rejected'" x-cloak
                         style="margin-top: 10px; padding: 8px 12px; border-radius: 6px;
                                background: var(--paper-2); border: 1px dashed var(--ink-300, var(--line));
                                font-size: 11px; color: var(--ink-500); line-height: 1.55;">
                        Saving will clear the previous rejection reason on this request.
                    </div>
                    @endif

                    <button type="submit" class="nrh-btn nrh-btn-primary"
                            style="display: block; width: 100%; padding: 12px 18px; margin-top: 16px; font-size: 13px; font-weight: 600;"
                            :disabled="chosen === current || state === 'saving' || (chosen === 'rejected' && !rejectionReason.trim())"
                            :class="(chosen === current || state === 'saving' || (chosen === 'rejected' && !rejectionReason.trim())) ? 'opacity-40 cursor-not-allowed' : ''">
                        <span x-show="state !== 'saving'">Update Status</span>
                        <span x-show="state === 'saving'" x-cloak>Saving…</span>
                    </button>
                    <div x-show="state === 'saved' || state === 'error'" x-cloak
                         style="text-align: center; margin-top: 8px; font-size: 11px; font-weight: 600;"
                         :style="state === 'saved' ? 'color: var(--emerald-700);' : 'color: var(--danger);'">
                        <span x-show="state === 'saved'">✓ Status updated</span>
                        <span x-show="state === 'error'" x-text="message"></span>
                    </div>
                </form>

                {{-- Show currently-saved rejection reason (read-only) when status is rejected. --}}
                @if($request->status === 'rejected' && $request->rejection_reason)
                <div style="margin-top: 14px; padding: 12px 14px; border-radius: 8px;
                            background: #fbeeec; border: 1px solid rgba(196,69,58,0.20);">
                    <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.16em;
                                color: var(--danger); font-weight: 600; font-family: 'JetBrains Mono', monospace; margin-bottom: 5px;">
                        Visible to customer
                    </div>
                    <div style="font-size: 12px; color: var(--ink-900); line-height: 1.55; white-space: pre-line;">{{ $request->rejection_reason }}</div>
                </div>
                @endif
                </div>
                @else
                <p style="font-size: 12px; color: var(--ink-400); font-style: italic; margin: 0;">Read-only — you don't have permission to update request status.</p>
                @endallowed
            </div>
        </div>

        {{-- Generate Report --}}
        <div class="rq-section"
             x-data="{
                supersedeOpen: false,
                supersedeVersion: null,
                supersedeType: '',
                supersedeReason: '',
                openSupersede(version) {
                    this.supersedeVersion = version.id;
                    this.supersedeType = version.type;
                    this.supersedeReason = '';
                    this.supersedeOpen = true;
                }
             }">
            <div class="rq-section-head">
                <div class="rq-section-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                </div>
                <div class="rq-section-title">Generate Report</div>
            </div>

            @allowed('request.update')
            @php
                $fPrelim = $reportFreshness['prelim'];
                $fFull   = $reportFreshness['full'];
                $latestPrelim = $fPrelim['latest'];
                $latestFull   = $fFull['latest'];
            @endphp
            <div class="rq-gen-grid">
                {{-- PRELIM --}}
                <form method="POST" action="{{ route('requests.report.generate', $request) }}"
                      x-data="ajaxForm({ onSuccess: () => { window.location.reload(); } })"
                      @submit.prevent="submit($event)">
                    @csrf
                    <input type="hidden" name="type" value="prelim">
                    @php $prelimDisabled = $latestPrelim && ! $fPrelim['has_changes']; @endphp
                    <button type="submit" class="rq-gen-btn"
                            :class="{ 'is-saving': state === 'saving' }"
                            {{ $prelimDisabled ? 'disabled' : '' }}
                            :disabled="state === 'saving'"
                            title="{{ $prelimDisabled
                                ? 'No changes since '.$latestPrelim->label().' · '.$latestPrelim->generated_at->diffForHumans()
                                : ($latestPrelim ? 'Will issue Prelim v'.($latestPrelim->version + 1) : 'Will issue Prelim v1') }}">
                        <template x-if="state !== 'saving'">
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">
                                <span class="rq-gen-btn-label">Prelim</span>
                                <span class="rq-gen-btn-sub">
                                    @if($latestPrelim) @if($fPrelim['has_changes']) Issue v{{ $latestPrelim->version + 1 }} @else On v{{ $latestPrelim->version }} @endif
                                    @else Issue v1 @endif
                                </span>
                            </div>
                        </template>
                        <template x-if="state === 'saving'">
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 4px 0;">
                                <div class="rq-gen-spinner"></div>
                                <span class="rq-gen-btn-sub" style="color: var(--emerald-700);">Generating…</span>
                            </div>
                        </template>
                    </button>
                </form>

                {{-- FULL --}}
                <form method="POST" action="{{ route('requests.report.generate', $request) }}"
                      x-data="ajaxForm({ onSuccess: () => { window.location.reload(); } })"
                      @submit.prevent="submit($event)">
                    @csrf
                    <input type="hidden" name="type" value="full">
                    @php $fullDisabled = $latestFull && ! $fFull['has_changes']; @endphp
                    <button type="submit" class="rq-gen-btn"
                            :class="{ 'is-saving': state === 'saving' }"
                            {{ $fullDisabled ? 'disabled' : '' }}
                            :disabled="state === 'saving'"
                            title="{{ $fullDisabled
                                ? 'No changes since '.$latestFull->label().' · '.$latestFull->generated_at->diffForHumans()
                                : ($latestFull ? 'Will issue Full v'.($latestFull->version + 1) : 'Will issue Full v1') }}">
                        <template x-if="state !== 'saving'">
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">
                                <span class="rq-gen-btn-label">Full</span>
                                <span class="rq-gen-btn-sub">
                                    @if($latestFull) @if($fFull['has_changes']) Issue v{{ $latestFull->version + 1 }} @else On v{{ $latestFull->version }} @endif
                                    @else Issue v1 @endif
                                </span>
                            </div>
                        </template>
                        <template x-if="state === 'saving'">
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 4px 0;">
                                <div class="rq-gen-spinner"></div>
                                <span class="rq-gen-btn-sub" style="color: var(--emerald-700);">Generating…</span>
                            </div>
                        </template>
                    </button>
                </form>

                {{-- UPDATED — supersedes the current full report --}}
                @php $updatedDisabled = ! $latestFull; @endphp
                <button type="button" class="rq-gen-btn"
                        {{ $updatedDisabled ? 'disabled' : '' }}
                        title="{{ $updatedDisabled
                            ? 'Issue a Full report first before issuing an Updated version'
                            : 'Supersede '.$latestFull->label().' with a corrected version' }}"
                        @if(! $updatedDisabled)
                        @click="openSupersede({ id: {{ $latestFull->id }}, type: 'full', label: '{{ $latestFull->label() }}' })"
                        @endif>
                    <div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">
                        <span class="rq-gen-btn-label">Updated</span>
                        <span class="rq-gen-btn-sub">
                            @if($latestFull) Supersede v{{ $latestFull->version }} @else No full yet @endif
                        </span>
                    </div>
                </button>
            </div>

            @if($versions->count())
            <div style="border-top: 1px solid var(--line);">
                @foreach($versions as $v)
                @php $isSuperseded = $v->isSuperseded(); @endphp
                <div class="rq-version-row {{ $isSuperseded ? 'is-superseded' : '' }}">
                    <div>
                        <div class="rq-version-label">
                            {{ $v->label() }}
                            @if($isSuperseded)
                                <span class="rq-superseded-badge" title="Superseded by {{ $v->supersededBy->first()?->label() }}">superseded</span>
                            @endif
                        </div>
                        <div class="rq-version-meta">
                            {{ $v->generated_at->format('d M Y, H:i') }} · {{ $v->generatedBy?->name ?? 'Deleted admin' }} · SHA {{ $v->shortHash() }}
                        </div>
                        @if($v->supersedes_id && $v->supersede_reason)
                        <div style="font-size: 10px; color: var(--ink-500); font-style: italic; margin-top: 3px;">
                            Reason: {{ $v->supersede_reason }}
                        </div>
                        @endif
                    </div>
                    <div class="rq-version-actions">
                        <a href="{{ route('requests.report.view', [$request, $v]) }}" target="_blank" class="rq-version-action-btn" title="Open in new tab">View</a>
                        <a href="{{ route('requests.report.download', [$request, $v]) }}" class="rq-version-action-btn" title="Download exact bytes">Download</a>
                    </div>
                    @if(! $isSuperseded)
                    <button type="button" class="rq-version-action-btn"
                            @click="openSupersede({ id: {{ $v->id }}, type: '{{ $v->type }}', label: '{{ $v->label() }}' })"
                            title="Issue a corrected version that supersedes this one">
                        Supersede…
                    </button>
                    @else
                    <span></span>
                    @endif
                </div>
                @endforeach
            </div>
            @else
            <div style="padding: 24px 18px; text-align: center; font-size: 12px; color: var(--ink-400); font-style: italic; border-top: 1px solid var(--line);">
                No reports issued yet.
            </div>
            @endif

            {{-- Supersede modal --}}
            <div x-show="supersedeOpen" x-cloak class="rq-modal-backdrop" @click.self="supersedeOpen = false">
                <div class="rq-modal">
                    <div class="rq-modal-head">
                        <h3>Supersede &amp; reissue</h3>
                        <p>Issues a new version of the same type, marked as superseding the chosen one. The old version stays in the audit history.</p>
                    </div>
                    <form method="POST" action="{{ route('requests.report.generate', $request) }}"
                          x-data="ajaxForm({ onSuccess: () => { window.location.reload(); } })"
                          @submit.prevent="submit($event)">
                        @csrf
                        <input type="hidden" name="type" :value="supersedeType">
                        <input type="hidden" name="supersedes_id" :value="supersedeVersion">
                        <div class="rq-modal-body">
                            <div style="font-size: 11px; color: var(--ink-500); font-weight: 600; text-transform: uppercase; letter-spacing: 0.14em; margin-bottom: 5px; font-family: 'JetBrains Mono', monospace;">Reason for supersede <span style="color: var(--danger);">*</span></div>
                            <textarea name="supersede_reason" required maxlength="1000"
                                      x-model="supersedeReason"
                                      placeholder="e.g. Corrected analyst attribution. Updated PO number from XYZ to ABC."
                                      class="rq-modal-textarea"></textarea>
                            <p style="font-size: 10px; color: var(--ink-400); margin: 6px 0 0;">
                                This reason is recorded in the audit log and shown next to the new version on this page.
                            </p>
                        </div>
                        <div class="rq-modal-foot">
                            <button type="button" @click="supersedeOpen = false"
                                    style="font-size: 12px; color: var(--ink-500); background: none; border: none; cursor: pointer; padding: 6px 12px;">Cancel</button>
                            <button type="submit" class="nrh-btn nrh-btn-primary"
                                    :disabled="state === 'saving' || !supersedeReason.trim()"
                                    :class="(state === 'saving' || !supersedeReason.trim()) ? 'opacity-40 cursor-not-allowed' : ''"
                                    style="font-size: 12px; padding: 7px 14px; display: inline-flex; align-items: center; gap: 6px;">
                                <span x-show="state === 'saving'" x-cloak class="rq-gen-spinner"
                                      style="width:12px; height:12px; border-width:1.5px; border-top-color:#fff; border-color:rgba(255,255,255,0.3);"></span>
                                <span x-show="state !== 'saving'">Issue superseding version</span>
                                <span x-show="state === 'saving'" x-cloak>Generating…</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            @else
            <p style="padding: 18px; font-size: 12px; color: var(--ink-400); font-style: italic; margin: 0;">Read-only — you don't have permission to issue reports.</p>
            @endallowed
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
            <form method="POST" action="{{ route('requests.meta', $request) }}"
                  x-data="ajaxForm()" @submit.prevent="submit($event)">
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
                        <div style="font-size: 9px; text-transform: uppercase; letter-spacing: 0.16em; color: var(--ink-500); font-weight: 600; font-family: 'JetBrains Mono', monospace; margin-bottom: 4px;">Prelim completion</div>
                        <input type="date" name="completion_prelim" value="{{ data_get($request->meta, 'completion_prelim') }}" class="rq-meta-input mono">
                    </div>
                    <div>
                        <div style="font-size: 9px; text-transform: uppercase; letter-spacing: 0.16em; color: var(--ink-500); font-weight: 600; font-family: 'JetBrains Mono', monospace; margin-bottom: 4px;">Full completion</div>
                        <input type="date" name="completion_full" value="{{ data_get($request->meta, 'completion_full') }}" class="rq-meta-input mono">
                    </div>
                    <div class="col-2">
                        <button type="submit" class="nrh-btn nrh-btn-primary"
                                style="width: 100%; padding: 9px 16px; font-size: 12px; font-weight: 600; margin-top: 4px;"
                                :disabled="state === 'saving'">
                            <span x-show="state !== 'saving'">Save metadata</span>
                            <span x-show="state === 'saving'" x-cloak>Saving…</span>
                        </button>
                        <div x-show="state === 'saved' || state === 'error'" x-cloak
                             style="text-align: center; margin-top: 6px; font-size: 11px; font-weight: 600;"
                             :style="state === 'saved' ? 'color: var(--emerald-700);' : 'color: var(--danger);'">
                            <span x-show="state === 'saved'">✓ Metadata saved</span>
                            <span x-show="state === 'error'" x-text="message"></span>
                        </div>
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
