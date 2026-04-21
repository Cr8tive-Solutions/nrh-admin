@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

{{-- Stats cards --}}
<div class="grid grid-cols-5 gap-4 mb-6">
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="text-xs text-gray-500 uppercase font-medium tracking-wide">Active Requests</div>
        <div class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['active_requests'] }}</div>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="text-xs text-gray-500 uppercase font-medium tracking-wide">Flagged Cases</div>
        <div class="text-2xl font-bold text-red-600 mt-1">{{ $stats['flagged_cases'] }}</div>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="text-xs text-gray-500 uppercase font-medium tracking-wide">Completed Today</div>
        <div class="text-2xl font-bold text-green-600 mt-1">{{ $stats['completed_today'] }}</div>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="text-xs text-gray-500 uppercase font-medium tracking-wide">Total Customers</div>
        <div class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['total_customers'] }}</div>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="text-xs text-gray-500 uppercase font-medium tracking-wide">Unpaid Invoices</div>
        <div class="text-2xl font-bold text-yellow-600 mt-1">{{ $stats['unpaid_invoices'] }}</div>
    </div>
</div>

<div class="grid grid-cols-2 gap-6">

    {{-- Pending queue --}}
    <div class="bg-white rounded-lg border border-gray-200">
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-900">Pending Queue</h2>
            <a href="{{ route('requests.index', ['status' => 'new']) }}" class="text-xs text-indigo-600 hover:text-indigo-800">View all</a>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-xs text-gray-500 uppercase">
                    <th class="px-4 py-2 text-left font-medium">Reference</th>
                    <th class="px-4 py-2 text-left font-medium">Customer</th>
                    <th class="px-4 py-2 text-left font-medium">Submitted</th>
                    <th class="px-4 py-2 text-left font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($pendingRequests as $req)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2.5 font-mono text-xs text-gray-700">{{ $req->reference }}</td>
                    <td class="px-4 py-2.5 text-gray-900">{{ $req->customer->name ?? '—' }}</td>
                    <td class="px-4 py-2.5 text-gray-500">{{ $req->created_at->diffForHumans() }}</td>
                    <td class="px-4 py-2.5">
                        <a href="{{ route('requests.show', $req) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">Open →</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400 text-xs">No pending requests</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Recent requests --}}
    <div class="bg-white rounded-lg border border-gray-200">
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-900">Recent Requests</h2>
            <a href="{{ route('requests.index') }}" class="text-xs text-indigo-600 hover:text-indigo-800">View all</a>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-xs text-gray-500 uppercase">
                    <th class="px-4 py-2 text-left font-medium">Reference</th>
                    <th class="px-4 py-2 text-left font-medium">Customer</th>
                    <th class="px-4 py-2 text-left font-medium">Status</th>
                    <th class="px-4 py-2 text-left font-medium">Updated</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($recentRequests as $req)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2.5">
                        <a href="{{ route('requests.show', $req) }}" class="font-mono text-xs text-indigo-600 hover:text-indigo-800">{{ $req->reference }}</a>
                    </td>
                    <td class="px-4 py-2.5 text-gray-900">{{ $req->customer->name ?? '—' }}</td>
                    <td class="px-4 py-2.5">
                        <span class="badge {{ $req->statusBadgeClass() }}">{{ str_replace('_', ' ', $req->status) }}</span>
                    </td>
                    <td class="px-4 py-2.5 text-gray-500">{{ $req->updated_at->diffForHumans() }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400 text-xs">No requests yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

@endsection
