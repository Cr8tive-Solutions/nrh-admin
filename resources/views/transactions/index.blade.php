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
    <table class="nrh-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Customer</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Reference</th>
                <th>Status</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $tx)
            <tr>
                <td class="text-gray-500 text-xs">{{ $tx->created_at->format('d M Y') }}</td>
                <td class="font-medium text-gray-900">
                    <a href="{{ route('customers.show', $tx->customer) }}" class="hover:text-emerald-700">{{ $tx->customer->name ?? '—' }}</a>
                </td>
                <td class="">
                    <span class="badge {{ $tx->type === 'topup' ? 'badge-green' : ($tx->type === 'adjustment' ? 'badge-blue' : 'badge-gray') }}">{{ $tx->type }}</span>
                </td>
                <td class="font-mono font-medium text-xs">MYR {{ number_format($tx->amount, 2) }}</td>
                <td class="text-gray-500">{{ $tx->method }}</td>
                <td class="font-mono text-xs text-gray-500">{{ $tx->reference ?? '—' }}</td>
                <td class=""><span class="badge badge-gray">{{ $tx->status }}</span></td>
                <td class="text-gray-400 text-xs max-w-xs truncate">{{ $tx->notes ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="8" style="padding:48px 20px; text-align:center;"><div style="display:flex; flex-direction:column; align-items:center; gap:8px;"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" style="color:var(--ink-200);"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg><span style="font-size:13px; color:var(--ink-400);">No transactions found.</span></div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $transactions->links() }}</div>

@endsection
