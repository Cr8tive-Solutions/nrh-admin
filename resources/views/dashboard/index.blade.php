@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

{{-- ── HERO METRICS: Bento Grid ───────────────────────────────────────────── --}}
<section class="grid grid-cols-12 gap-5 mb-8">

    {{-- Total Cleared --}}
    <div class="col-span-12 md:col-span-4 bg-surface-container-lowest p-6 rounded-xl shadow-sm border border-outline-variant/10">
        <div class="flex justify-between items-start mb-4">
            <div class="p-2 bg-secondary-container rounded-lg">
                <span class="material-symbols-outlined text-primary" style="font-variation-settings:'FILL' 1;">analytics</span>
            </div>
            <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest">Total Cleared</span>
        </div>
        <div class="space-y-1">
            <h3 class="text-4xl font-extrabold font-display text-on-surface tracking-tighter">{{ number_format($stats['total_cleared']) }}</h3>
            <p class="text-xs text-primary flex items-center gap-1 font-semibold">
                <span class="material-symbols-outlined" style="font-size:14px; font-variation-settings:'FILL' 1;">trending_up</span>
                Screening requests completed
            </p>
        </div>
    </div>

    {{-- Active / Pending --}}
    <div class="col-span-12 md:col-span-4 bg-surface-container-lowest p-6 rounded-xl shadow-sm border border-outline-variant/10">
        <div class="flex justify-between items-start mb-4">
            <div class="p-2 bg-surface-container-highest rounded-lg">
                <span class="material-symbols-outlined text-primary">hourglass_empty</span>
            </div>
            <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest">In Progress</span>
        </div>
        <div class="space-y-1">
            <h3 class="text-4xl font-extrabold font-display text-on-surface tracking-tighter">{{ str_pad($stats['active_requests'], 2, '0', STR_PAD_LEFT) }}</h3>
            <p class="text-xs text-primary flex items-center gap-1 font-semibold {{ $stats['active_requests'] > 0 ? 'animate-pulse' : '' }}">
                <span class="material-symbols-outlined" style="font-size:14px;">schedule</span>
                Active across {{ $stats['total_customers'] }} customers
            </p>
        </div>
    </div>

    {{-- Action Required --}}
    <div class="col-span-12 md:col-span-4 p-6 rounded-xl shadow-sm border border-error/10
                {{ $stats['flagged_cases'] > 0 ? 'bg-error-container/10' : 'bg-surface-container-lowest border-outline-variant/10' }}">
        <div class="flex justify-between items-start mb-4">
            <div class="p-2 bg-error-container rounded-lg">
                <span class="material-symbols-outlined text-error" style="font-variation-settings:'FILL' 1;">flag</span>
            </div>
            <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest">Action Required</span>
        </div>
        <div class="space-y-1">
            <h3 class="text-4xl font-extrabold font-display tracking-tighter {{ $stats['flagged_cases'] > 0 ? 'text-error' : 'text-on-surface' }}">
                {{ str_pad($stats['flagged_cases'], 2, '0', STR_PAD_LEFT) }}
            </h3>
            @if($stats['flagged_cases'] > 0)
            <p class="text-xs text-error flex items-center gap-1 font-semibold">
                <span class="material-symbols-outlined" style="font-size:14px;">warning</span>
                <a href="{{ route('requests.index', ['status' => 'flagged']) }}" class="underline">Critical flags identified</a>
            </p>
            @else
            <p class="text-xs text-primary flex items-center gap-1 font-semibold">
                <span class="material-symbols-outlined" style="font-size:14px;">check_circle</span>
                All clear — no flags
            </p>
            @endif
        </div>
    </div>

</section>

{{-- ── CHART + SYSTEM STATUS ───────────────────────────────────────────────── --}}
<section class="grid grid-cols-12 gap-6 mb-8">

    {{-- Screening Volume Chart --}}
    <div class="col-span-12 lg:col-span-8 bg-surface-container-lowest p-8 rounded-xl shadow-sm border border-outline-variant/10">
        <div class="flex justify-between items-end mb-8">
            <div>
                <h2 class="text-2xl font-extrabold font-display tracking-tight text-on-surface">Screening Volume</h2>
                <p class="text-sm text-on-surface-variant mt-0.5">Request flow across the past 7 days.</p>
            </div>
            <div class="flex items-center gap-3 text-xs text-on-surface-variant">
                <span class="flex items-center gap-1.5">
                    <span class="inline-block w-3 h-3 rounded-sm bg-primary/80"></span>
                    Peak day
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="inline-block w-3 h-3 rounded-sm bg-surface-container-highest"></span>
                    Other days
                </span>
            </div>
        </div>

        @php
            $maxCount = max($weeklyVolume->pluck('count')->max(), 1);
            $maxHeight = 224; // px — matches design h-56
        @endphp

        <div class="relative h-64 flex items-end justify-between gap-3 px-2 border-b border-surface-container-high">
            @php $peakIdx = $weeklyVolume->search(fn($d) => $d['count'] === $maxCount); @endphp
            @foreach($weeklyVolume as $idx => $day)
            @php
                $barH = $day['count'] > 0 ? max(8, (int) round(($day['count'] / $maxCount) * $maxHeight)) : 8;
                $isPeak = $idx === $peakIdx && $day['count'] > 0;
            @endphp
            <div class="flex-1 flex flex-col items-center group">
                <div class="w-full rounded-t-lg transition-all relative cursor-default"
                     style="height: {{ $barH }}px; background-color: {{ $isPeak ? 'var(--color-primary)' : 'var(--color-surface-container-highest)' }};"
                     x-data
                     @mouseenter="$el.style.opacity='0.8'"
                     @mouseleave="$el.style.opacity='1'">
                    {{-- Tooltip --}}
                    <div class="absolute -top-8 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 transition-opacity
                                bg-inverse-surface text-white text-[10px] font-bold px-2 py-1 rounded whitespace-nowrap z-10">
                        {{ $day['count'] }} requests
                    </div>
                </div>
                <span class="text-[10px] font-bold mt-3 {{ $isPeak ? 'text-on-surface' : 'text-on-surface-variant' }} uppercase tracking-wider">
                    {{ strtoupper(substr($day['label'], 0, 3)) }}
                </span>
            </div>
            @endforeach
        </div>

        <div class="flex justify-between mt-4 text-xs text-on-surface-variant">
            <span>Total this week: <strong class="text-on-surface">{{ number_format($weeklyVolume->sum('count')) }}</strong></span>
            @if($stats['unpaid_invoices'] > 0)
            <span class="text-error font-semibold flex items-center gap-1">
                <span class="material-symbols-outlined" style="font-size:14px;">receipt</span>
                {{ $stats['unpaid_invoices'] }} unpaid invoice{{ $stats['unpaid_invoices'] > 1 ? 's' : '' }}
            </span>
            @endif
        </div>
    </div>

    {{-- System / Activity Rail --}}
    <div class="col-span-12 lg:col-span-4 bg-surface-container-low p-8 rounded-xl relative overflow-hidden border border-outline-variant/20">
        <h2 class="text-xl font-extrabold font-display mb-6 relative z-10 text-on-surface">Recent Activity</h2>

        <div class="space-y-5 relative z-10">
            @forelse($recentRequests->take(4) as $req)
            @php
                $isFlag = $req->status === 'flagged';
                $isNew  = $req->status === 'new';
            @endphp
            <div class="flex gap-4">
                <div class="relative flex-shrink-0">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center relative z-10 shadow-sm
                                {{ $isFlag ? 'bg-error' : 'bg-primary' }}">
                        <span class="material-symbols-outlined text-white" style="font-size:14px;">
                            {{ $isFlag ? 'flag' : ($isNew ? 'fiber_new' : 'task_alt') }}
                        </span>
                    </div>
                    @if(!$loop->last)
                    <div class="absolute top-8 left-1/2 -translate-x-1/2 w-px h-full bg-outline-variant/40"></div>
                    @endif
                </div>
                <div class="flex-1 pb-4">
                    <p class="text-sm font-bold text-on-surface leading-tight">
                        <a href="{{ route('requests.show', $req) }}" class="hover:text-primary transition-colors">
                            {{ $req->customer->name ?? 'Unknown' }}
                        </a>
                    </p>
                    <p class="text-[11px] text-on-surface-variant font-mono">{{ $req->reference }}</p>
                    <p class="text-[10px] text-on-surface-variant/70 mt-0.5">{{ $req->updated_at->diffForHumans() }}</p>
                </div>
            </div>
            @empty
            <p class="text-sm text-on-surface-variant">No recent activity.</p>
            @endforelse
        </div>

        {{-- Ambient glow --}}
        <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-primary/5 blur-[60px] rounded-full pointer-events-none"></div>
    </div>

</section>

{{-- ── ACTION REQUIRED TABLE ───────────────────────────────────────────────── --}}
@if($flaggedRequests->count())
<section class="space-y-4">
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-extrabold font-display tracking-tight text-on-surface">Action Required</h2>
        <a href="{{ route('requests.index', ['status' => 'flagged']) }}"
           class="text-sm font-bold text-primary flex items-center gap-1.5 hover:underline">
            View All <span class="material-symbols-outlined" style="font-size:16px;">arrow_forward</span>
        </a>
    </div>

    <div class="bg-surface-container-lowest rounded-xl overflow-hidden shadow-sm border border-outline-variant/10">
        {{-- Table header --}}
        <div class="grid grid-cols-12 px-6 py-3.5 bg-surface-container-high/40">
            <div class="col-span-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Customer / Reference</div>
            <div class="col-span-2 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Ref ID</div>
            <div class="col-span-2 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Flagged</div>
            <div class="col-span-2 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Candidates</div>
            <div class="col-span-2 text-right text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Action</div>
        </div>

        <div class="divide-y divide-surface-container-high/50">
            @foreach($flaggedRequests as $req)
            @php
                $initial = strtoupper(substr($req->customer->name ?? 'X', 0, 2));
                $isEven  = $loop->even;
            @endphp
            <div class="grid grid-cols-12 px-6 py-5 transition-colors
                        {{ $isEven ? 'bg-surface-container-low/40 hover:bg-surface-container-high' : 'bg-surface-container-lowest hover:bg-surface-container-low' }}">
                {{-- Customer --}}
                <div class="col-span-4 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-surface-container flex items-center justify-center font-display font-bold text-primary text-sm flex-shrink-0">
                        {{ $initial }}
                    </div>
                    <div>
                        <p class="text-sm font-bold text-on-surface leading-tight">{{ $req->customer->name ?? '—' }}</p>
                        <p class="text-[11px] text-on-surface-variant">{{ $req->type ?? 'Screening Request' }}</p>
                    </div>
                </div>
                {{-- Ref --}}
                <div class="col-span-2 flex items-center font-mono text-sm font-medium text-on-surface">
                    {{ $req->reference }}
                </div>
                {{-- Date --}}
                <div class="col-span-2 flex items-center text-sm text-on-surface-variant">
                    {{ $req->updated_at->format('d M Y') }}
                </div>
                {{-- Candidates --}}
                <div class="col-span-2 flex items-center">
                    <span class="px-2.5 py-1 rounded bg-error-container text-error text-[10px] font-extrabold uppercase tracking-tight">
                        {{ $req->candidates->count() }} {{ Str::plural('candidate', $req->candidates->count()) }}
                    </span>
                </div>
                {{-- Action --}}
                <div class="col-span-2 flex items-center justify-end gap-2">
                    <a href="{{ route('requests.show', $req) }}"
                       class="px-4 py-1.5 bg-primary text-on-primary text-xs font-bold rounded-lg shadow-sm hover:scale-105 transition-transform active:scale-95">
                        Review
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

@else
{{-- No flags — show recent requests instead --}}
<section class="space-y-4">
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-extrabold font-display tracking-tight text-on-surface">Recent Requests</h2>
        <a href="{{ route('requests.index') }}"
           class="text-sm font-bold text-primary flex items-center gap-1.5 hover:underline">
            View All <span class="material-symbols-outlined" style="font-size:16px;">arrow_forward</span>
        </a>
    </div>

    <div class="bg-surface-container-lowest rounded-xl overflow-hidden shadow-sm border border-outline-variant/10">
        <div class="grid grid-cols-12 px-6 py-3.5 bg-surface-container-high/40">
            <div class="col-span-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Customer / Reference</div>
            <div class="col-span-2 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Ref ID</div>
            <div class="col-span-2 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Submitted</div>
            <div class="col-span-2 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Status</div>
            <div class="col-span-2 text-right text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Action</div>
        </div>

        <div class="divide-y divide-surface-container-high/50">
            @forelse($recentRequests as $req)
            @php $initial = strtoupper(substr($req->customer->name ?? 'X', 0, 2)); @endphp
            <div class="grid grid-cols-12 px-6 py-4 transition-colors
                        {{ $loop->even ? 'bg-surface-container-low/40 hover:bg-surface-container-high' : 'bg-surface-container-lowest hover:bg-surface-container-low' }}">
                <div class="col-span-4 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-surface-container flex items-center justify-center font-display font-bold text-primary text-xs flex-shrink-0">{{ $initial }}</div>
                    <div>
                        <p class="text-sm font-bold text-on-surface leading-tight">{{ $req->customer->name ?? '—' }}</p>
                        <p class="text-[11px] text-on-surface-variant">{{ $req->type ?? 'Screening' }}</p>
                    </div>
                </div>
                <div class="col-span-2 flex items-center font-mono text-sm font-medium text-on-surface">{{ $req->reference }}</div>
                <div class="col-span-2 flex items-center text-sm text-on-surface-variant">{{ $req->created_at->format('d M Y') }}</div>
                <div class="col-span-2 flex items-center">
                    <span class="badge {{ $req->statusBadgeClass() }}">{{ str_replace('_', ' ', $req->status) }}</span>
                </div>
                <div class="col-span-2 flex items-center justify-end">
                    <a href="{{ route('requests.show', $req) }}"
                       class="px-4 py-1.5 bg-surface-container-highest text-on-surface text-xs font-bold rounded-lg hover:bg-white transition-all border border-outline-variant/30">
                        View
                    </a>
                </div>
            </div>
            @empty
            <div class="px-6 py-12 text-center text-on-surface-variant">No requests yet.</div>
            @endforelse
        </div>
    </div>
</section>
@endif

@endsection
