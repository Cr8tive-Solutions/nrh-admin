@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('header-actions')
<a href="{{ route('requests.index') }}" class="nrh-btn nrh-btn-ghost" style="font-size: 12px; padding: 6px 12px;">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 12h13M11 18h10"/></svg>
    All requests
</a>
@endsection

@section('content')

{{-- Page head --}}
<div style="display: flex; align-items: flex-end; justify-content: space-between; gap: 24px; padding-bottom: 4px;">
    <div>
        <h1 style="font-family: 'Fraunces', serif; font-weight: 500; font-size: 30px; letter-spacing: -0.01em; margin: 0; line-height: 1.05; color: var(--ink-900);">
            Good day, <em style="font-style: italic; color: var(--emerald-700);">{{ session('admin_name') }}.</em>
        </h1>
        <div style="margin-top: 6px; color: var(--ink-500); font-size: 13px;">
            @if($stats['flagged_cases'] > 0)
                <span style="color: var(--danger); font-weight: 600;">{{ $stats['flagged_cases'] }} {{ $stats['flagged_cases'] === 1 ? 'case requires' : 'cases require' }} your attention</span> &middot;
            @endif
            {{ $stats['active_requests'] }} active &middot; {{ $stats['total_customers'] }} customers &middot; {{ now()->format('d M Y') }}
        </div>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="{{ route('requests.index') }}" class="nrh-btn nrh-btn-ghost">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5M12 15V3"/></svg>
            Export
        </a>
        <a href="{{ route('customers.create') }}" class="nrh-btn nrh-btn-primary">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M12 5v14M5 12h14"/></svg>
            New customer
        </a>
    </div>
</div>

{{-- ── Stat strip ── --}}
<div style="display: grid; grid-template-columns: repeat(5, 1fr); border: 1px solid var(--line); border-radius: 12px; background: var(--card); overflow: hidden; box-shadow: var(--shadow-sm);">

    <div style="padding: 18px 20px; border-right: 1px solid var(--line); display: flex; flex-direction: column; gap: 8px;">
        <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.18em; color: var(--ink-500); display: flex; align-items: center; gap: 6px;">
            <span style="width: 6px; height: 6px; border-radius: 1px; background: var(--emerald-600); display: inline-block;"></span>Active requests
        </div>
        <div style="font-family: 'Fraunces', serif; font-size: 32px; font-weight: 500; letter-spacing: -0.02em; line-height: 1;">{{ $stats['active_requests'] }}</div>
        <div style="font-size: 11px; color: var(--ink-500); font-family: 'JetBrains Mono', monospace;">In progress</div>
    </div>

    <div style="padding: 18px 20px; border-right: 1px solid var(--line); display: flex; flex-direction: column; gap: 8px;">
        <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.18em; color: var(--ink-500); display: flex; align-items: center; gap: 6px;">
            <span style="width: 6px; height: 6px; border-radius: 1px; background: var(--danger); display: inline-block;"></span>Flagged
        </div>
        <div style="font-family: 'Fraunces', serif; font-size: 32px; font-weight: 500; letter-spacing: -0.02em; line-height: 1; color: {{ $stats['flagged_cases'] > 0 ? 'var(--danger)' : 'var(--ink-900)' }};">{{ $stats['flagged_cases'] }}</div>
        <div style="font-size: 11px; color: var(--ink-500); font-family: 'JetBrains Mono', monospace;">
            @if($stats['flagged_cases'] > 0)
                <a href="{{ route('requests.index', ['status' => 'flagged']) }}" style="color: var(--danger); font-weight: 600; text-decoration: none;">Review now →</a>
            @else
                All clear
            @endif
        </div>
    </div>

    <div style="padding: 18px 20px; border-right: 1px solid var(--line); display: flex; flex-direction: column; gap: 8px;">
        <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.18em; color: var(--ink-500); display: flex; align-items: center; gap: 6px;">
            <span style="width: 6px; height: 6px; border-radius: 1px; background: var(--emerald-600); display: inline-block;"></span>Completed
        </div>
        <div style="font-family: 'Fraunces', serif; font-size: 32px; font-weight: 500; letter-spacing: -0.02em; line-height: 1;">{{ $stats['total_cleared'] }}</div>
        <div style="font-size: 11px; color: var(--ink-500); font-family: 'JetBrains Mono', monospace;">All time</div>
    </div>

    <div style="padding: 18px 20px; border-right: 1px solid var(--line); display: flex; flex-direction: column; gap: 8px;">
        <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.18em; color: var(--ink-500); display: flex; align-items: center; gap: 6px;">
            <span style="width: 6px; height: 6px; border-radius: 1px; background: var(--ink-400); display: inline-block;"></span>Customers
        </div>
        <div style="font-family: 'Fraunces', serif; font-size: 32px; font-weight: 500; letter-spacing: -0.02em; line-height: 1;">{{ $stats['total_customers'] }}</div>
        <div style="font-size: 11px; color: var(--ink-500); font-family: 'JetBrains Mono', monospace;">Active accounts</div>
    </div>

    <div style="padding: 18px 20px; display: flex; flex-direction: column; gap: 8px;">
        <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.18em; color: var(--ink-500); display: flex; align-items: center; gap: 6px;">
            <span style="width: 6px; height: 6px; border-radius: 1px; background: var(--gold-500); display: inline-block;"></span>Unpaid invoices
        </div>
        <div style="font-family: 'Fraunces', serif; font-size: 32px; font-weight: 500; letter-spacing: -0.02em; line-height: 1; color: {{ $stats['unpaid_invoices'] > 0 ? 'var(--gold-700)' : 'var(--ink-900)' }};">{{ $stats['unpaid_invoices'] }}</div>
        <div style="font-size: 11px; font-family: 'JetBrains Mono', monospace;">
            @if($stats['unpaid_invoices'] > 0)
                <a href="{{ route('invoices.index') }}" style="color: var(--gold-700); font-weight: 600; text-decoration: none;">View invoices →</a>
            @else
                <span style="color: var(--ink-500);">All settled</span>
            @endif
        </div>
    </div>

</div>

{{-- ── Two-column grid ── --}}
<div style="display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start;">

    {{-- Screening volume chart --}}
    <div class="nrh-card">
        <div class="nrh-card-head">
            <h3>Screening volume</h3>
            <span style="font-size: 10px; font-family: 'JetBrains Mono', monospace; color: var(--ink-400); letter-spacing: 0.1em; text-transform: uppercase;">Last 7 days</span>
        </div>
        <div style="padding: 20px 20px 16px;">
            @php
                $maxCount = max($weeklyVolume->pluck('count')->max(), 1);
                $maxH = 140;
                $peakIdx = $weeklyVolume->search(fn($d) => $d['count'] === $maxCount);
            @endphp

            <div style="display: flex; align-items: flex-end; justify-content: space-between; gap: 8px; height: {{ $maxH + 28 }}px; border-bottom: 1px solid var(--line); padding-bottom: 4px;">
                @foreach($weeklyVolume as $idx => $day)
                @php
                    $barH = $day['count'] > 0 ? max(6, (int) round(($day['count'] / $maxCount) * $maxH)) : 6;
                    $isPeak = $idx === $peakIdx && $day['count'] > 0;
                @endphp
                <div style="flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px;">
                    <div style="font-family: 'JetBrains Mono', monospace; font-size: 10px; color: var(--ink-500);">{{ $day['count'] ?: '' }}</div>
                    <div style="width: 100%; height: {{ $barH }}px; border-radius: 4px 4px 0 0;
                                background: {{ $isPeak ? 'var(--emerald-700)' : 'var(--emerald-100)' }};
                                transition: opacity 120ms ease;"
                         title="{{ $day['count'] }} requests on {{ $day['label'] }}"></div>
                </div>
                @endforeach
            </div>
            <div style="display: flex; justify-content: space-between; margin-top: 8px;">
                @foreach($weeklyVolume as $day)
                <div style="flex: 1; text-align: center; font-size: 10px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--ink-500); font-family: 'JetBrains Mono', monospace;">
                    {{ strtoupper(substr($day['label'], 0, 3)) }}
                </div>
                @endforeach
            </div>
            <div style="display: flex; justify-content: space-between; margin-top: 14px; font-size: 12px; color: var(--ink-500); border-top: 1px solid var(--line); padding-top: 12px;">
                <span>Total this week: <strong style="color: var(--ink-900);">{{ number_format($weeklyVolume->sum('count')) }}</strong></span>
                <span>Peak: <strong style="color: var(--emerald-700);">{{ $weeklyVolume->max('count') }}</strong> requests/day</span>
            </div>
        </div>
    </div>

    {{-- Side column --}}
    <div style="display: flex; flex-direction: column; gap: 20px;">

        {{-- Recent activity --}}
        <div class="nrh-card">
            <div class="nrh-card-head">
                <h3>Activity</h3>
                <a href="{{ route('requests.index') }}" style="font-size: 11px; color: var(--emerald-700); font-weight: 600; text-decoration: none;">View all</a>
            </div>
            <div style="display: flex; flex-direction: column;">
                @forelse($recentRequests->take(5) as $req)
                @php
                    $isFlag = $req->status === 'flagged';
                    $isNew  = $req->status === 'new';
                    $isDone = $req->status === 'complete';
                    $iconBg = $isFlag ? '#fbeeec' : ($isDone ? 'var(--emerald-50)' : 'var(--ink-100)');
                    $iconColor = $isFlag ? 'var(--danger)' : ($isDone ? 'var(--emerald-700)' : 'var(--ink-500)');
                @endphp
                <div style="padding: 12px 18px; border-bottom: 1px solid var(--line); display: grid; grid-template-columns: 24px 1fr; gap: 12px; font-size: 12px; color: var(--ink-700);">
                    <div style="width: 24px; height: 24px; border-radius: 50%; background: {{ $iconBg }}; color: {{ $iconColor }}; display: grid; place-items: center; flex-shrink: 0;">
                        @if($isFlag)
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
                        @elseif($isDone)
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg>
                        @else
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                        @endif
                    </div>
                    <div>
                        <div><a href="{{ route('requests.show', $req) }}" style="font-weight: 600; color: var(--ink-900); text-decoration: none;">{{ $req->customer->name ?? 'Unknown' }}</a> &mdash; {{ str_replace('_', ' ', $req->status) }}</div>
                        <div style="font-family: 'JetBrains Mono', monospace; font-size: 10px; color: var(--ink-400); margin-top: 3px; text-transform: uppercase; letter-spacing: 0.05em;">{{ $req->reference }} &middot; {{ $req->updated_at->diffForHumans() }}</div>
                    </div>
                </div>
                @empty
                <div style="padding: 24px 18px; font-size: 13px; color: var(--ink-500); text-align: center;">No recent activity.</div>
                @endforelse
            </div>
        </div>

    </div>
</div>

{{-- ── Flagged / Recent requests table ── --}}
@if($flaggedRequests->count())
<div class="nrh-card">
    <div class="nrh-card-head" style="background: #fbeeec;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <h3 style="color: var(--danger);">Action required</h3>
            <span style="background: var(--danger); color: #fff; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 600; letter-spacing: 0.05em; font-family: 'JetBrains Mono', monospace;">{{ $flaggedRequests->count() }} FLAGGED</span>
        </div>
        <a href="{{ route('requests.index', ['status' => 'flagged']) }}" style="font-size: 11px; color: var(--danger); font-weight: 600; text-decoration: none;">View all →</a>
    </div>
    <table class="nrh-table">
        <thead>
            <tr>
                <th>Customer</th>
                <th style="width: 160px;">Reference</th>
                <th style="width: 120px;">Flagged</th>
                <th style="width: 100px;">Candidates</th>
                <th style="width: 100px;"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($flaggedRequests as $req)
            <tr onclick="window.location='{{ route('requests.show', $req) }}'">
                <td>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 30px; height: 30px; border-radius: 50%; background: #fbeeec; color: var(--danger); display: grid; place-items: center; font-size: 11px; font-weight: 600; font-family: 'JetBrains Mono', monospace; flex-shrink: 0;">
                            {{ strtoupper(substr($req->customer->name ?? 'X', 0, 2)) }}
                        </div>
                        <div>
                            <div style="font-weight: 600; font-size: 13px; line-height: 1.2;">{{ $req->customer->name ?? '—' }}</div>
                            <div style="font-size: 11px; color: var(--ink-500);">{{ $req->type ?? 'Screening' }}</div>
                        </div>
                    </div>
                </td>
                <td style="font-family: 'JetBrains Mono', monospace; font-size: 12px; color: var(--ink-700);">{{ $req->reference }}</td>
                <td style="font-size: 12px; color: var(--ink-500);">{{ $req->updated_at->format('d M Y') }}</td>
                <td>
                    <span class="nrh-pill nrh-pill-flagged">{{ $req->candidates->count() }} {{ Str::plural('candidate', $req->candidates->count()) }}</span>
                </td>
                <td>
                    <a href="{{ route('requests.show', $req) }}" class="nrh-btn nrh-btn-primary" style="font-size: 11px; padding: 5px 12px;">Review</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@else

<div class="nrh-card">
    <div class="nrh-card-head">
        <div style="display: flex; align-items: center; gap: 12px;">
            <h3>Recent requests</h3>
            <span style="background: var(--emerald-50); color: var(--emerald-800); padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 600; letter-spacing: 0.05em; font-family: 'JetBrains Mono', monospace;">{{ $recentRequests->count() }} RECENT</span>
        </div>
        <a href="{{ route('requests.index') }}" style="font-size: 11px; color: var(--emerald-700); font-weight: 600; text-decoration: none;">View all →</a>
    </div>
    <table class="nrh-table">
        <thead>
            <tr>
                <th>Customer</th>
                <th style="width: 160px;">Reference</th>
                <th style="width: 120px;">Submitted</th>
                <th style="width: 120px;">Status</th>
                <th style="width: 80px;"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($recentRequests as $req)
            <tr onclick="window.location='{{ route('requests.show', $req) }}'">
                <td>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 30px; height: 30px; border-radius: 50%; background: var(--emerald-50); color: var(--emerald-800); display: grid; place-items: center; font-size: 11px; font-weight: 600; font-family: 'JetBrains Mono', monospace; flex-shrink: 0;">
                            {{ strtoupper(substr($req->customer->name ?? 'X', 0, 2)) }}
                        </div>
                        <div>
                            <div style="font-weight: 600; font-size: 13px; line-height: 1.2;">{{ $req->customer->name ?? '—' }}</div>
                            <div style="font-size: 11px; color: var(--ink-500);">{{ $req->type ?? 'Screening' }}</div>
                        </div>
                    </div>
                </td>
                <td style="font-family: 'JetBrains Mono', monospace; font-size: 12px; color: var(--ink-700);">{{ $req->reference }}</td>
                <td style="font-size: 12px; color: var(--ink-500);">{{ $req->created_at->format('d M Y') }}</td>
                <td>
                    @php
                        $sc = match($req->status) {
                            'new'         => 'nrh-pill-new',
                            'in_progress' => 'nrh-pill-progress',
                            'flagged'     => 'nrh-pill-flagged',
                            'complete'    => 'nrh-pill-complete',
                            default       => 'nrh-pill-new',
                        };
                    @endphp
                    <span class="nrh-pill {{ $sc }}">{{ str_replace('_', ' ', $req->status) }}</span>
                </td>
                <td>
                    <a href="{{ route('requests.show', $req) }}" class="nrh-btn nrh-btn-ghost" style="font-size: 11px; padding: 5px 12px;">View</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align: center; color: var(--ink-500); padding: 32px;">No requests yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@endif

@endsection
