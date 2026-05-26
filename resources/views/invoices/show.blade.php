@extends('layouts.admin')

@section('title', $invoice->number)
@section('page-title', $invoice->number)
@section('page-subtitle', $invoice->customer->name ?? '')

@section('header-actions')
    <a href="{{ route('invoices.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Back</a>
    @allowed('invoice.manage')
    @if($invoice->status === 'unpaid')
    <a href="{{ route('invoices.edit', $invoice) }}" class="nrh-btn nrh-btn-ghost" style="font-size:12px;">
        Edit
    </a>
    @endif
    @if($invoice->status === 'unpaid' || $invoice->status === 'overdue')
    <form method="POST" action="{{ route('invoices.paid', $invoice) }}">
        @csrf @method('PATCH')
        <button type="submit" class="nrh-btn nrh-btn-primary">
            Mark as Paid
        </button>
    </form>
    @endif
    @endallowed
@endsection

@section('content')

@if(session('error'))
<div class="max-w-3xl mb-3 px-4 py-2.5 rounded-md text-sm" style="background:#fbeeec; color:var(--danger); border:1px solid rgba(196,69,58,0.20);">
    {{ session('error') }}
</div>
@endif

<div class="max-w-3xl">
    <div class="bg-white rounded-lg border border-gray-200 p-6">

        {{-- Header --}}
        <div class="flex justify-between mb-6">
            <div>
                <div class="text-xl font-bold text-gray-900">{{ $invoice->number }}</div>
                <div class="text-gray-500 text-sm mt-1">{{ $invoice->period }}</div>
            </div>
            <div class="text-right">
                <span class="badge {{ $invoice->statusBadgeClass() }} text-sm">{{ strtoupper($invoice->status) }}</span>
                <div class="text-xs text-gray-500 mt-2">Issued: {{ $invoice->issued_at->format('d M Y') }}</div>
                <div class="text-xs text-gray-500">Due: {{ $invoice->due_at->format('d M Y') }}</div>
            </div>
        </div>

        {{-- Customer --}}
        <div class="border-t border-gray-100 pt-4 mb-5">
            <div class="text-xs text-gray-500 uppercase font-medium mb-1">Billed to</div>
            <div class="font-medium text-gray-900">{{ $invoice->customer->name ?? '—' }}</div>
            @if($invoice->customer?->registration_no)
            <div class="text-sm text-gray-500">{{ $invoice->customer->registration_no }}</div>
            @endif
            @if($invoice->customer?->contact_email)
            <div class="text-sm text-gray-500">{{ $invoice->customer->contact_email }}</div>
            @endif
        </div>

        {{-- Line items --}}
        <table class="w-full text-sm mb-5">
            <thead>
                <tr class="border-y border-gray-200 text-xs text-gray-500 uppercase">
                    <th class="py-2 text-left font-medium">Description</th>
                    <th class="py-2 text-right font-medium w-16">Qty</th>
                    <th class="py-2 text-right font-medium w-28">Unit Price</th>
                    <th class="py-2 text-right font-medium w-28">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td class="py-2.5 text-gray-900">{{ $item->description }}</td>
                    <td class="py-2.5 text-right text-gray-500">{{ $item->qty }}</td>
                    <td class="py-2.5 text-right font-mono">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="py-2.5 text-right font-mono">{{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals --}}
        <div class="flex justify-end">
            <div class="w-56 space-y-1.5 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Subtotal</span>
                    <span class="font-mono">MYR {{ number_format($invoice->subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">SST (6%)</span>
                    <span class="font-mono">MYR {{ number_format($invoice->tax, 2) }}</span>
                </div>
                <div class="flex justify-between font-bold border-t border-gray-200 pt-2 text-base">
                    <span>Total</span>
                    <span class="font-mono">MYR {{ number_format($invoice->total, 2) }}</span>
                </div>
                @php $verifiedTotal = $invoice->verifiedReceiptsTotal(); @endphp
                @if($verifiedTotal > 0)
                <div class="flex justify-between text-xs text-emerald-700 pt-1">
                    <span>Verified payments</span>
                    <span class="font-mono">MYR {{ number_format($verifiedTotal, 2) }}</span>
                </div>
                @endif
            </div>
        </div>

    </div>

    {{-- ── Linked screening requests ── --}}
    @if($invoice->screeningRequests->isNotEmpty())
    <div class="bg-white rounded-lg border border-gray-200 p-6 mt-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-emerald-700"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                Linked screening requests
            </h3>
            <span class="text-xs text-gray-400 font-mono uppercase tracking-wider">
                {{ $invoice->screeningRequests->count() }} {{ Str::plural('request', $invoice->screeningRequests->count()) }}
            </span>
        </div>
        <p class="text-xs text-gray-500 mb-3">
            These requests are gated on this invoice — they unblock to <span class="font-mono">in_progress</span> when the invoice flips to paid.
        </p>
        <table class="w-full text-sm">
            <tbody>
                @foreach($invoice->screeningRequests as $req)
                <tr class="border-t border-gray-100">
                    <td class="py-2 font-mono text-xs text-gray-700">{{ $req->reference }}</td>
                    <td class="py-2 text-xs text-gray-500">{{ $req->type ?? '—' }}</td>
                    <td class="py-2 text-right">
                        <span class="badge {{ $req->statusBadgeClass() }}">{{ str_replace('_', ' ', $req->status) }}</span>
                    </td>
                    <td class="py-2 text-right w-16">
                        <a href="{{ route('requests.show', $req) }}" class="text-emerald-700 text-xs font-medium">View →</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- ── Payment receipts panel ── --}}
    <div class="bg-white rounded-lg border border-gray-200 p-6 mt-4"
         x-data="{ rejecting: null, verifying: null }">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-gold-700" style="color: var(--gold-700, #b8860b);"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
                Payment receipts
            </h3>
            <span class="text-xs text-gray-400 font-mono uppercase tracking-wider">
                {{ $invoice->receipts->count() }} {{ Str::plural('receipt', $invoice->receipts->count()) }}
            </span>
        </div>

        @if($invoice->receipts->isEmpty())
        <div class="text-center py-8 text-gray-400 text-sm">
            <p class="font-medium text-gray-500">No receipts uploaded yet</p>
            <p class="text-xs mt-1">When the customer uploads a receipt against this invoice, it'll appear here for verification.</p>
        </div>
        @else
        <div class="space-y-3">
            @foreach($invoice->receipts as $receipt)
            <div class="border border-gray-200 rounded-lg overflow-hidden"
                 :class="{
                    'border-emerald-200 bg-emerald-50/30': '{{ $receipt->status }}' === 'verified',
                    'border-rose-200 bg-rose-50/30': '{{ $receipt->status }}' === 'rejected',
                    'border-amber-200': '{{ $receipt->status }}' === 'pending'
                 }">
                <div class="px-4 py-3 flex items-center gap-3 flex-wrap">
                    <div class="flex-shrink-0 w-9 h-9 rounded-md bg-gray-100 grid place-items-center text-gray-500">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-gray-900 truncate">{{ $receipt->file_name }}</div>
                        <div class="text-xs text-gray-500 mt-0.5 flex items-center gap-3 flex-wrap">
                            @if($receipt->amount_claimed)
                            <span class="font-mono">MYR {{ number_format($receipt->amount_claimed, 2) }}</span>
                            @endif
                            @if($receipt->paid_on)
                            <span>Paid {{ $receipt->paid_on->format('d M Y') }}</span>
                            @endif
                            @if($receipt->reference)
                            <span class="font-mono">Ref: {{ $receipt->reference }}</span>
                            @endif
                            @if($receipt->uploadedBy)
                            <span>by {{ $receipt->uploadedBy->name }}</span>
                            @endif
                            <span>{{ $receipt->created_at->diffForHumans() }}</span>
                        </div>
                        @if($receipt->notes)
                        <div class="text-xs text-gray-500 mt-1 italic">"{{ $receipt->notes }}"</div>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <span class="badge {{ $receipt->statusBadgeClass() }}">{{ ucfirst($receipt->status) }}</span>
                        <a href="{{ route('payment-receipts.file', $receipt) }}" target="_blank"
                           class="text-xs text-emerald-700 font-medium hover:text-emerald-900">View file →</a>
                    </div>
                </div>

                @if($receipt->status === 'pending')
                @allowed('transaction.manage')
                <div class="border-t border-amber-200 bg-amber-50/50 px-4 py-3 flex flex-wrap gap-2 items-center">
                    {{-- Verify --}}
                    <button type="button"
                            @click="verifying = (verifying === {{ $receipt->id }} ? null : {{ $receipt->id }}); rejecting = null"
                            class="text-xs font-semibold px-3 py-1.5 rounded-md text-white bg-emerald-700 hover:bg-emerald-800">
                        ✓ Verify
                    </button>
                    {{-- Reject --}}
                    <button type="button"
                            @click="rejecting = (rejecting === {{ $receipt->id }} ? null : {{ $receipt->id }}); verifying = null"
                            class="text-xs font-semibold px-3 py-1.5 rounded-md text-rose-700 bg-white border border-rose-200 hover:bg-rose-50">
                        ✕ Reject
                    </button>
                    <span class="text-xs text-gray-500 ml-auto">
                        Verifying creates a transaction row and may flip the invoice to paid.
                    </span>
                </div>

                {{-- Inline verify form --}}
                <div x-show="verifying === {{ $receipt->id }}" x-cloak x-transition
                     class="border-t border-emerald-200 bg-emerald-50/40 px-4 py-3">
                    <form method="POST" action="{{ route('payment-receipts.verify', $receipt) }}" class="space-y-2">
                        @csrf
                        <label class="block text-xs font-semibold text-emerald-800 uppercase tracking-wider">Verification note (optional, internal)</label>
                        <textarea name="verification_note" rows="2" maxlength="1000"
                                  placeholder="e.g. Confirmed via Maybank statement, ref MBB-2026-0512-NRH."
                                  class="w-full text-xs px-3 py-2 border border-emerald-200 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-600 bg-white"></textarea>
                        @php
                            $effectiveAmount = $receipt->amount_claimed !== null ? (float) $receipt->amount_claimed : (float) $invoice->total;
                            $coverageAfter   = $invoice->verifiedReceiptsTotal() + $effectiveAmount;
                            $willFlipPaid    = $coverageAfter + 0.005 >= (float) $invoice->total;
                        @endphp
                        <p class="text-xs text-gray-600">
                            Will record a payment of <strong class="font-mono">MYR {{ number_format($effectiveAmount, 2) }}</strong>.
                            @if($willFlipPaid && $invoice->status !== 'paid')
                                <span class="text-emerald-700 font-medium">Invoice will flip to paid</span>
                                @if($invoice->screeningRequests->where('status', 'new')->count())
                                    and {{ $invoice->screeningRequests->where('status', 'new')->count() }} pending {{ Str::plural('request', $invoice->screeningRequests->where('status', 'new')->count()) }} will move to in_progress.
                                @else
                                    .
                                @endif
                            @elseif($willFlipPaid)
                                Invoice is already paid.
                            @else
                                Coverage after: <strong class="font-mono">MYR {{ number_format($coverageAfter, 2) }}</strong> of {{ number_format($invoice->total, 2) }}.
                            @endif
                        </p>
                        <div class="flex gap-2">
                            <button type="submit" class="text-xs font-semibold px-3 py-1.5 rounded-md text-white bg-emerald-700 hover:bg-emerald-800">Confirm verify</button>
                            <button type="button" @click="verifying = null" class="text-xs px-3 py-1.5 text-gray-500">Cancel</button>
                        </div>
                    </form>
                </div>

                {{-- Inline reject form --}}
                <div x-show="rejecting === {{ $receipt->id }}" x-cloak x-transition
                     class="border-t border-rose-200 bg-rose-50/40 px-4 py-3">
                    <form method="POST" action="{{ route('payment-receipts.reject', $receipt) }}" class="space-y-2">
                        @csrf
                        <label class="block text-xs font-semibold text-rose-800 uppercase tracking-wider">Reason for rejection <span class="text-rose-600">*</span></label>
                        <textarea name="verification_note" required rows="2" maxlength="1000"
                                  placeholder="e.g. Receipt is illegible, please reupload a clearer scan."
                                  class="w-full text-xs px-3 py-2 border border-rose-200 rounded-md focus:outline-none focus:ring-2 focus:ring-rose-500 bg-white"></textarea>
                        <p class="text-xs text-gray-500">Internal note — the customer sees a generic "rejected" status, not this text.</p>
                        <div class="flex gap-2">
                            <button type="submit" class="text-xs font-semibold px-3 py-1.5 rounded-md text-white bg-rose-700 hover:bg-rose-800">Confirm reject</button>
                            <button type="button" @click="rejecting = null" class="text-xs px-3 py-1.5 text-gray-500">Cancel</button>
                        </div>
                    </form>
                </div>
                @endallowed
                @elseif($receipt->status === 'verified' || $receipt->status === 'rejected')
                <div class="border-t {{ $receipt->status === 'verified' ? 'border-emerald-200 bg-emerald-50/40' : 'border-rose-200 bg-rose-50/40' }} px-4 py-2.5 text-xs text-gray-600">
                    <span class="font-semibold {{ $receipt->status === 'verified' ? 'text-emerald-700' : 'text-rose-700' }}">{{ ucfirst($receipt->status) }}</span>
                    by {{ $receipt->verifiedBy?->name ?? 'Deleted admin' }}
                    on {{ $receipt->verified_at?->format('d M Y, H:i') }}
                    @if($receipt->verification_note)
                        — <span class="italic">{{ $receipt->verification_note }}</span>
                    @endif
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>

</div>

@endsection
