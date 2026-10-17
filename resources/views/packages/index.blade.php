@extends('layouts.admin')

@section('title', 'Packages')
@section('page-title', 'Packages')
@section('page-subtitle', 'Scope bundles offered to each company')

@section('content')
<style>
    .pk-bar {
        background: var(--card); border: 1px solid var(--line); border-radius: 10px;
        padding: 14px 18px; margin-bottom: 18px;
        display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
    }
    .pk-bar-label {
        font-size: 10px; font-weight: 600; text-transform: uppercase;
        letter-spacing: 0.16em; color: var(--ink-500); white-space: nowrap;
    }
    .pk-select {
        border: 1px solid var(--line); background: var(--card); color: var(--ink-900);
        border-radius: 6px; padding: 7px 12px; font-size: 13px; outline: none;
        min-width: 280px; font-family: inherit; transition: border-color 120ms;
    }
    .pk-select:focus { border-color: var(--emerald-600); box-shadow: 0 0 0 3px rgba(5,150,105,0.1); }

    .pk-card { background: var(--card); border: 1px solid var(--line); border-radius: 10px; overflow: hidden; }
    .pk-card-head {
        padding: 12px 18px; border-bottom: 1px solid var(--line);
        display: flex; align-items: center; gap: 10px;
    }
    .pk-card-title { font-size: 13px; font-weight: 600; color: var(--ink-900); }

    .pk-row { padding: 14px 18px; border-bottom: 1px solid var(--line); }
    .pk-row:last-child { border-bottom: none; }
    .pk-row-head { display: flex; align-items: baseline; gap: 10px; margin-bottom: 9px; }
    .pk-name { font-size: 14px; font-weight: 600; color: var(--ink-900); }
    .pk-meta {
        font-size: 11px; color: var(--ink-500);
        font-family: 'JetBrains Mono', monospace;
        text-transform: uppercase; letter-spacing: 0.08em;
    }
    .pk-total {
        margin-left: auto; font-size: 12px; font-weight: 600; color: var(--ink-900);
        font-family: 'JetBrains Mono', monospace; white-space: nowrap;
    }
    .pk-pills { display: flex; flex-wrap: wrap; gap: 6px; }
    .pk-pill {
        display: inline-flex; align-items: center; padding: 3px 9px; border-radius: 99px;
        font-size: 11px; background: var(--paper-2); color: var(--ink-700);
        border: 1px solid var(--line);
    }
    .pk-pill.unpriced {
        background: rgba(212,175,55,0.10); color: var(--gold-700, #b8860b);
        border-color: rgba(212,175,55,0.35);
    }

    /* Overview table (no company selected) */
    .pk-ov-row {
        display: grid; grid-template-columns: 1fr auto auto;
        align-items: center; gap: 14px;
        padding: 10px 18px; border-bottom: 1px solid var(--line);
        text-decoration: none; transition: background 100ms;
    }
    .pk-ov-row:last-child { border-bottom: none; }
    .pk-ov-row:hover { background: rgba(4,108,78,0.03); }
    .pk-ov-name { font-size: 13px; color: var(--ink-900); }
    .pk-ov-count {
        font-size: 11px; font-weight: 600; padding: 2px 9px; border-radius: 999px;
        background: rgba(4,108,78,0.08); color: var(--emerald-700); white-space: nowrap;
    }
    .pk-ov-count.none { background: var(--ink-100); color: var(--ink-500); }
    .pk-ov-go { font-size: 11px; color: var(--emerald-700); font-weight: 600; }

    .pk-empty { padding: 52px 20px; text-align: center; }
    .pk-empty-title { font-size: 13px; color: var(--ink-700); font-weight: 500; }
    .pk-empty-sub { font-size: 12px; color: var(--ink-400); margin-top: 4px; }
</style>

@php $canEdit = admin_can('customer.manage'); @endphp

@unless($canEdit)
<div class="bg-amber-50 border border-amber-200 text-amber-800 text-xs px-4 py-2.5 rounded-lg mb-3 flex items-center gap-2">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
    Read-only — you don't have permission to edit packages.
</div>
@endunless

{{-- Company picker. Navigates on change; the page is fully server-rendered. --}}
<div class="pk-bar">
    <span class="pk-bar-label">Company</span>
    <select class="pk-select" onchange="if (this.value) { location.href = '{{ route('packages.index') }}?customer_id=' + this.value } else { location.href = '{{ route('packages.index') }}' }">
        <option value="">Select a company…</option>
        @foreach($customers as $c)
            <option value="{{ $c->id }}" {{ $customer && $customer->id === $c->id ? 'selected' : '' }}>
                {{ $c->name }}
            </option>
        @endforeach
    </select>

    @if($customer)
        <a href="{{ route('customers.show', $customer) }}" class="pk-ov-go" style="text-decoration: none;">
            Open company page →
        </a>
        @if($canEdit)
        <a href="{{ route('customers.packages.create', ['customer' => $customer, 'from' => 'packages']) }}"
           class="nrh-btn nrh-btn-primary" style="margin-left: auto; font-size: 11px; padding: 6px 13px;">
            + New Package
        </a>
        @endif
    @endif
</div>

@if(! $customer)
    {{-- Overview: every company with its package count, so gaps are obvious. --}}
    <div class="pk-card">
        <div class="pk-card-head">
            <div class="pk-card-title">All Companies</div>
            <span style="margin-left: auto; font-size: 11px; color: var(--ink-500); font-family: 'JetBrains Mono', monospace; text-transform: uppercase; letter-spacing: 0.1em;">
                {{ $customers->count() }} {{ Str::plural('company', $customers->count()) }}
            </span>
        </div>

        @forelse($customers as $c)
            @php $count = (int) ($packageCounts[$c->id] ?? 0); @endphp
            <a class="pk-ov-row" href="{{ route('packages.index', ['customer_id' => $c->id]) }}">
                <span class="pk-ov-name">{{ $c->name }}</span>
                <span class="pk-ov-count {{ $count === 0 ? 'none' : '' }}">
                    {{ $count === 0 ? 'No packages' : $count.' '.Str::plural('package', $count) }}
                </span>
                <span class="pk-ov-go">Manage →</span>
            </a>
        @empty
            <div class="pk-empty">
                <div class="pk-empty-title">No companies yet</div>
                <div class="pk-empty-sub">Create a customer first, then bundle their scopes into packages.</div>
            </div>
        @endforelse
    </div>
@else
    <div class="pk-card">
        <div class="pk-card-head">
            <div class="pk-card-title">{{ $customer->name }}</div>
            <span style="margin-left: auto; font-size: 11px; color: var(--ink-500); font-family: 'JetBrains Mono', monospace; text-transform: uppercase; letter-spacing: 0.1em;">
                {{ $customer->packages->count() }} {{ Str::plural('package', $customer->packages->count()) }}
            </span>
        </div>

        @forelse($customer->packages as $package)
            @php
                $unpricedIds = $packageUnpriced[$package->id] ?? [];
                $total = $packageTotals[$package->id] ?? null;
                $currency = $package->country?->currency ?? 'MYR';
            @endphp
            <div class="pk-row">
                <div class="pk-row-head">
                    <div class="pk-name">{{ $package->name }}</div>
                    <div class="pk-meta">
                        {{ $package->country?->flag }} {{ $package->country?->name ?? '—' }}
                        · {{ $package->scopeTypes->count() }} {{ Str::plural('scope', $package->scopeTypes->count()) }}
                    </div>
                    <div class="pk-total">
                        @if($total === null)
                            <span style="color: var(--gold-700, #b8860b);">Not quotable</span>
                        @else
                            {{ $currency }} {{ number_format($total, 2) }}
                        @endif
                        @if($canEdit)
                        <a href="{{ route('customers.packages.edit', ['customer' => $customer, 'package' => $package, 'from' => 'packages']) }}"
                           style="margin-left: 10px; font-size: 11px; color: var(--emerald-700); font-weight: 600; text-decoration: none;">Edit →</a>
                        @endif
                    </div>
                </div>

                @if($package->scopeTypes->isEmpty())
                    <div style="font-size: 12px; color: var(--danger); font-style: italic;">
                        No scopes — the client portal shows this package as empty. Edit it to add scopes, or delete it.
                    </div>
                @else
                    <div class="pk-pills">
                        @foreach($package->scopeTypes as $scope)
                            @php $isUnpriced = in_array($scope->id, $unpricedIds, true); @endphp
                            <span class="pk-pill {{ $isUnpriced ? 'unpriced' : '' }}"
                                  title="{{ $isUnpriced
                                      ? 'Price on request with no override for this customer'
                                      : $currency.' '.number_format((float) ($scopePrices[$scope->id] ?? $scope->price), 2) }}">
                                {{ $scope->name }}
                            </span>
                        @endforeach
                    </div>
                @endif

                @if(count($unpricedIds) > 0)
                <div style="font-size: 11px; color: var(--gold-700, #b8860b); margin-top: 8px; line-height: 1.5;">
                    {{ count($unpricedIds) }} {{ Str::plural('scope', count($unpricedIds)) }} priced on request with no
                    override for this customer — the client portal will reject any request using this package.
                    @allowed('pricing.manage')
                    <a href="{{ route('pricing.index', ['customer_id' => $customer->id]) }}"
                       style="color: var(--emerald-700); font-weight: 600; text-decoration: none;">Set pricing →</a>
                    @endallowed
                </div>
                @endif
            </div>
        @empty
            <div class="pk-empty">
                <div class="pk-empty-title">No packages for {{ $customer->name }}</div>
                <div class="pk-empty-sub">
                    Bundle the scopes this company orders most often so their team can add them in one click.
                </div>
                @if($canEdit)
                <a href="{{ route('customers.packages.create', ['customer' => $customer, 'from' => 'packages']) }}"
                   class="nrh-btn nrh-btn-primary" style="margin-top: 16px; font-size: 11px; padding: 6px 13px;">
                    + New Package
                </a>
                @endif
            </div>
        @endforelse
    </div>
@endif
@endsection
