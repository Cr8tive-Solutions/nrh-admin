@extends('layouts.admin')

@section('title', $invoice->number)
@section('page-title', $invoice->number)
@section('page-subtitle', $invoice->customer->name ?? '')

@section('header-actions')
    <a href="{{ route('invoices.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Back</a>
    @if($invoice->status === 'unpaid' || $invoice->status === 'overdue')
    <form method="POST" action="{{ route('invoices.paid', $invoice) }}">
        @csrf @method('PATCH')
        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-4 py-2 rounded-md transition-colors">
            Mark as Paid
        </button>
    </form>
    @endif
@endsection

@section('content')

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
            <tbody class="divide-y divide-gray-50">
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
            </div>
        </div>

    </div>
</div>

@endsection
