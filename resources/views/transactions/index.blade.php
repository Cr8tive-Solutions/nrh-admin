@extends('layouts.admin')

@section('title', 'Transactions')
@section('page-title', 'Transactions')

@section('header-actions')
    <a href="{{ route('transactions.create') }}"
       class="nrh-btn nrh-btn-primary">
        + Record Payment
    </a>
@endsection

@section('content')

<div class="bg-white rounded-lg border border-gray-200 px-4 py-3 mb-4 flex items-center gap-3">
    <form method="GET" action="{{ route('transactions.index') }}" class="flex items-center gap-3 flex-1">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search by customer name…"
               class="border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600 w-72">
        <button type="submit" class="nrh-btn nrh-btn-primary">
            Search
        </button>
    </form>
    <div class="text-sm text-gray-500">{{ $transactions->total() }} total</div>
</div>

<div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="bg-gray-50 border-b border-gray-200 text-xs text-gray-500 uppercase">
                <th class="px-4 py-3 text-left font-medium">Date</th>
                <th class="px-4 py-3 text-left font-medium">Customer</th>
                <th class="px-4 py-3 text-left font-medium">Type</th>
                <th class="px-4 py-3 text-left font-medium">Amount</th>
                <th class="px-4 py-3 text-left font-medium">Method</th>
                <th class="px-4 py-3 text-left font-medium">Reference</th>
                <th class="px-4 py-3 text-left font-medium">Status</th>
                <th class="px-4 py-3 text-left font-medium">Notes</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($transactions as $tx)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-2.5 text-gray-500 text-xs">{{ $tx->created_at->format('d M Y') }}</td>
                <td class="px-4 py-2.5 font-medium text-gray-900">
                    <a href="{{ route('customers.show', $tx->customer) }}" class="hover:text-emerald-700">{{ $tx->customer->name ?? '—' }}</a>
                </td>
                <td class="px-4 py-2.5">
                    <span class="badge {{ $tx->type === 'topup' ? 'badge-green' : ($tx->type === 'adjustment' ? 'badge-blue' : 'badge-gray') }}">{{ $tx->type }}</span>
                </td>
                <td class="px-4 py-2.5 font-mono font-medium text-xs">MYR {{ number_format($tx->amount, 2) }}</td>
                <td class="px-4 py-2.5 text-gray-500">{{ $tx->method }}</td>
                <td class="px-4 py-2.5 font-mono text-xs text-gray-500">{{ $tx->reference ?? '—' }}</td>
                <td class="px-4 py-2.5"><span class="badge badge-gray">{{ $tx->status }}</span></td>
                <td class="px-4 py-2.5 text-gray-400 text-xs max-w-xs truncate">{{ $tx->notes ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="8" class="px-4 py-10 text-center text-gray-400">No transactions found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $transactions->links() }}</div>

@endsection
