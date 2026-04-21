@extends('layouts.admin')

@section('title', 'Invoices')
@section('page-title', 'Invoices')

@section('header-actions')
    <a href="{{ route('invoices.create') }}"
       class="nrh-btn nrh-btn-primary">
        + New Invoice
    </a>
@endsection

@section('content')

<div class="bg-white rounded-lg border border-gray-200 px-4 py-3 mb-4 flex items-center gap-3">
    <form method="GET" action="{{ route('invoices.index') }}" class="flex items-center gap-3 flex-1">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search invoice no. or customer…"
               class="border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600 w-72">
        <select name="status" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600">
            <option value="">All statuses</option>
            <option value="unpaid" {{ request('status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
            <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
            <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>Overdue</option>
        </select>
        <button type="submit" class="nrh-btn nrh-btn-primary">
            Filter
        </button>
    </form>
    <div class="text-sm text-gray-500">{{ $invoices->total() }} total</div>
</div>

<div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <table class="nrh-table">
        <thead>
            <tr>
                <th>Invoice No.</th>
                <th>Customer</th>
                <th>Period</th>
                <th>Subtotal</th>
                <th>Tax (6%)</th>
                <th>Total</th>
                <th>Status</th>
                <th>Due</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $invoice)
            <tr>
                <td class="font-mono text-xs text-gray-700">{{ $invoice->number }}</td>
                <td class="font-medium text-gray-900">{{ $invoice->customer->name ?? '—' }}</td>
                <td class="text-gray-500">{{ $invoice->period }}</td>
                <td class="font-mono text-xs">{{ number_format($invoice->subtotal, 2) }}</td>
                <td class="font-mono text-xs">{{ number_format($invoice->tax, 2) }}</td>
                <td class="font-mono text-xs font-medium">MYR {{ number_format($invoice->total, 2) }}</td>
                <td class=""><span class="badge {{ $invoice->statusBadgeClass() }}">{{ $invoice->status }}</span></td>
                <td class="text-gray-500 text-xs">{{ $invoice->due_at->format('d M Y') }}</td>
                <td class="text-right">
                    <a href="{{ route('invoices.show', $invoice) }}" class="text-emerald-700 hover:text-emerald-900 text-xs font-medium">View →</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="9" style="padding:48px 20px; text-align:center;"><div style="display:flex; flex-direction:column; align-items:center; gap:8px;"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" style="color:var(--ink-200);"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg><span style="font-size:13px; color:var(--ink-400);">No invoices found.</span></div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $invoices->links() }}</div>

@endsection
