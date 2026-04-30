@extends('layouts.admin')

@section('title', 'Data Subject Requests')
@section('page-title', 'Data Subject Requests')
@section('page-subtitle', 'PDPA Section 30 — access, erasure, rectification, portability')

@section('header-actions')
    <a href="{{ route('compliance.dsar.create') }}" class="nrh-btn nrh-btn-primary">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" style="margin-right:4px;"><path d="M12 5v14M5 12h14"/></svg>
        Log New Request
    </a>
@endsection

@section('content')

@php
    $statusColors = [
        'received'           => ['bg' => 'var(--ink-100)', 'text' => 'var(--ink-700)'],
        'verifying_identity' => ['bg' => '#fef3c7', 'text' => '#b45309'],
        'in_progress'        => ['bg' => 'rgba(59,130,246,0.10)', 'text' => '#1d4ed8'],
        'completed'          => ['bg' => 'var(--emerald-50)', 'text' => 'var(--emerald-700)'],
        'rejected'           => ['bg' => '#fbeeec', 'text' => 'var(--danger)'],
    ];
@endphp

<style>
    .dsar-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 18px; }
    .dsar-stat-card {
        background: var(--card); border: 1px solid var(--line);
        border-radius: 10px; padding: 14px 16px;
    }
    .dsar-stat-value {
        font-family: 'Fraunces', serif; font-size: 24px; font-weight: 500;
        line-height: 1; color: var(--ink-900);
    }
    .dsar-stat-value.warn { color: var(--gold-700, #b8860b); }
    .dsar-stat-value.danger { color: var(--danger); }
    .dsar-stat-label {
        font-size: 9px; text-transform: uppercase; letter-spacing: 0.16em;
        color: var(--ink-500); margin-top: 6px; font-weight: 600;
        font-family: 'JetBrains Mono', monospace;
    }
    .dsar-row {
        display: grid; grid-template-columns: 130px 1fr 130px 120px 110px auto;
        gap: 14px; align-items: center;
        padding: 12px 18px; border-bottom: 1px solid var(--line);
    }
    .dsar-row:last-child { border-bottom: none; }
    .dsar-row:hover { background: var(--paper-2); }
    .dsar-row.is-overdue { background: rgba(196,69,58,0.04); }
    .dsar-ref { font-family: 'JetBrains Mono', monospace; font-size: 12px; font-weight: 600; color: var(--ink-900); }
    .dsar-pill {
        display: inline-flex; padding: 2px 9px; border-radius: 99px;
        font-size: 10px; font-weight: 600; text-transform: capitalize;
    }
</style>

<div class="dsar-stats">
    <div class="dsar-stat-card">
        <div class="dsar-stat-value">{{ $stats['received'] }}</div>
        <div class="dsar-stat-label">Received</div>
    </div>
    <div class="dsar-stat-card">
        <div class="dsar-stat-value warn">{{ $stats['verifying'] }}</div>
        <div class="dsar-stat-label">Verifying ID</div>
    </div>
    <div class="dsar-stat-card">
        <div class="dsar-stat-value">{{ $stats['in_progress'] }}</div>
        <div class="dsar-stat-label">In progress</div>
    </div>
    <div class="dsar-stat-card">
        <div class="dsar-stat-value {{ $stats['overdue'] > 0 ? 'danger' : '' }}">{{ $stats['overdue'] }}</div>
        <div class="dsar-stat-label">Overdue</div>
    </div>
</div>

<form method="GET" action="{{ route('compliance.dsar.index') }}"
      style="background: var(--card); border: 1px solid var(--line); border-radius: 10px; padding: 12px 14px; margin-bottom: 14px; display: flex; gap: 10px; align-items: center;">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Reference / name / email…"
           style="border: 1px solid var(--line); border-radius: 8px; padding: 7px 10px; font-size: 13px; min-width: 240px; outline: none;">
    <select name="status" style="border: 1px solid var(--line); border-radius: 8px; padding: 7px 10px; font-size: 13px; outline: none;">
        <option value="">All statuses</option>
        @foreach(\App\Models\DataSubjectRequest::statuses() as $key => $label)
        <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
    <select name="type" style="border: 1px solid var(--line); border-radius: 8px; padding: 7px 10px; font-size: 13px; outline: none;">
        <option value="">All types</option>
        @foreach(\App\Models\DataSubjectRequest::types() as $key => $label)
        <option value="{{ $key }}" {{ request('type') === $key ? 'selected' : '' }}>{{ ucfirst($key) }}</option>
        @endforeach
    </select>
    <button type="submit" class="nrh-btn nrh-btn-primary" style="font-size: 12px; padding: 7px 14px;">Filter</button>
    @if(request()->anyFilled(['search','status','type']))
    <a href="{{ route('compliance.dsar.index') }}" style="font-size: 12px; color: var(--ink-500); text-decoration: none;">Clear</a>
    @endif
    <span style="margin-left: auto; font-size: 11px; color: var(--ink-500); font-family: 'JetBrains Mono', monospace; text-transform: uppercase; letter-spacing: 0.1em;">
        {{ $requests->total() }} total
    </span>
</form>

<div style="background: var(--card); border: 1px solid var(--line); border-radius: 12px; overflow: hidden;">
    @forelse($requests as $r)
    <div class="dsar-row {{ $r->isOverdue() ? 'is-overdue' : '' }}">
        <div>
            <div class="dsar-ref">{{ $r->reference }}</div>
            <div style="font-size: 10px; color: var(--ink-400); font-family: 'JetBrains Mono', monospace; text-transform: uppercase; letter-spacing: 0.06em; margin-top: 2px;">{{ $r->received_at->format('d M Y') }}</div>
        </div>
        <div>
            <div style="font-weight: 500; color: var(--ink-900);">{{ $r->subject_name }}</div>
            <div style="font-size: 11px; color: var(--ink-500);">
                @if($r->subject_email){{ $r->subject_email }}@endif
                @if($r->candidate)
                · linked: <a href="{{ route('requests.show', $r->candidate->screeningRequest) }}" style="color: var(--emerald-700); text-decoration: none;">{{ $r->candidate->screeningRequest->reference }}</a>
                @endif
            </div>
        </div>
        <div>
            <span class="dsar-pill" style="background: rgba(212,175,55,0.10); color: var(--gold-700, #b8860b);">{{ str_replace('_', ' ', $r->type) }}</span>
        </div>
        <div>
            @php $sc = $statusColors[$r->status] ?? $statusColors['received']; @endphp
            <span class="dsar-pill" style="background: {{ $sc['bg'] }}; color: {{ $sc['text'] }};">{{ str_replace('_', ' ', $r->status) }}</span>
        </div>
        <div>
            @if($r->due_at)
                @if($r->isOverdue())
                <span style="font-size: 11px; font-weight: 600; color: var(--danger);">{{ abs($r->daysToRespond()) }}d overdue</span>
                @elseif(in_array($r->status, ['completed','rejected']))
                <span style="font-size: 11px; color: var(--ink-400); font-family: 'JetBrains Mono', monospace;">closed</span>
                @else
                <span style="font-size: 11px; color: var(--ink-500); font-family: 'JetBrains Mono', monospace;">{{ $r->daysToRespond() }}d</span>
                @endif
            @endif
        </div>
        <div style="text-align: right;">
            <a href="{{ route('compliance.dsar.show', $r) }}" style="font-size: 11px; color: var(--emerald-700); font-weight: 600; text-decoration: none;">Open →</a>
        </div>
    </div>
    @empty
    <div style="padding: 56px 24px; text-align: center; color: var(--ink-400);">
        <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" style="color: var(--ink-300); margin: 0 auto 12px; display: block;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h4"/></svg>
        <div style="font-size: 13px; color: var(--ink-700); font-weight: 500;">No data subject requests</div>
        <div style="font-size: 12px; color: var(--ink-400); margin-top: 4px;">When a candidate emails asking for their data or to be erased, log it here.</div>
    </div>
    @endforelse
</div>

<div style="margin-top: 14px;">{{ $requests->links() }}</div>

@endsection
