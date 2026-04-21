@extends('layouts.admin')

@section('title', 'Invoices')
@section('page-title', 'Invoices')

@section('header-actions')
    <a href="{{ route('invoices.create') }}"
       class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-md transition-colors">
        + New Invoice
    </a>
@endsection

@section('content')

<div class="bg-white rounded-lg border border-gray-200 px-4 py-3 mb-4 flex items-center gap-3">
    <form method="GET" action="{{ route('invoices.index') }}" class="flex items-center gap-3 flex-1">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search invoice no. or customer…"
               class="border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-72">
        <select name="status" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All statuses</option>
            <option value="unpaid" {{ request('status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
            <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
            <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>Overdue</option>
        </select>
        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-1.5 rounded-md transition-colors">
            Filter
        </button>
    </form>
    <div class="text-sm text-gray-500">{{ $invoices->total() }} total</div>
</div>

<div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="bg-gray-50 border-b border-gray-200 text-xs text-gray-500 uppercase">
                <th class="px-4 py-3 text-left font-medium">Invoice No.</th>
                <th class="px-4 py-3 text-left font-medium">Customer</th>
                <th class="px-4 py-3 text-left font-medium">Period</th>
                <th class="px-4 py-3 text-left font-medium">Subtotal</th>
                <th class="px-4 py-3 text-left font-medium">Tax (6%)</th>
                <th class="px-4 py-3 text-left font-medium">Total</th>
                <th class="px-4 py-3 text-left font-medium">Status</th>
                <th class="px-4 py-3 text-left font-medium">Due</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($invoices as $invoice)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-2.5 font-mono text-xs text-gray-700">{{ $invoice->number }}</td>
                <td class="px-4 py-2.5 font-medium text-gray-900">{{ $invoice->customer->name ?? '—' }}</td>
                <td class="px-4 py-2.5 text-gray-500">{{ $invoice->period }}</td>
                <td class="px-4 py-2.5 font-mono text-xs">{{ number_format($invoice->subtotal, 2) }}</td>
                <td class="px-4 py-2.5 font-mono text-xs">{{ number_format($invoice->tax, 2) }}</td>
                <td class="px-4 py-2.5 font-mono text-xs font-medium">MYR {{ number_format($invoice->total, 2) }}</td>
                <td class="px-4 py-2.5"><span class="badge {{ $invoice->statusBadgeClass() }}">{{ $invoice->status }}</span></td>
                <td class="px-4 py-2.5 text-gray-500 text-xs">{{ $invoice->due_at->format('d M Y') }}</td>
                <td class="px-4 py-2.5 text-right">
                    <a href="{{ route('invoices.show', $invoice) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">View →</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="9" class="px-4 py-10 text-center text-gray-400">No invoices found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $invoices->links() }}</div>

@endsection
