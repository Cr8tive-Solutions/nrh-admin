@extends('layouts.admin')

@section('title', 'Request Queue')
@section('page-title', 'Request Queue')

@section('header-actions')
    <a href="{{ route('requests.index') }}" class="text-xs text-gray-500 hover:text-gray-700">Clear filters</a>
@endsection

@section('content')

{{-- Filters --}}
<div class="bg-white rounded-lg border border-gray-200 px-4 py-3 mb-4 flex items-center gap-4">
    <form method="GET" action="{{ route('requests.index') }}" class="flex items-center gap-3 flex-1">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search reference or customer…"
               class="border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600 w-72">

        <select name="status" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600">
            <option value="">All statuses</option>
            <option value="new" {{ request('status') === 'new' ? 'selected' : '' }}>New</option>
            <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
            <option value="flagged" {{ request('status') === 'flagged' ? 'selected' : '' }}>Flagged</option>
            <option value="complete" {{ request('status') === 'complete' ? 'selected' : '' }}>Complete</option>
        </select>

        <button type="submit" class="nrh-btn nrh-btn-primary">
            Filter
        </button>
    </form>
    <div class="text-sm text-gray-500">{{ $requests->total() }} total</div>
</div>

{{-- Table --}}
<div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="bg-gray-50 border-b border-gray-200 text-xs text-gray-500 uppercase">
                <th class="px-4 py-3 text-left font-medium">Reference</th>
                <th class="px-4 py-3 text-left font-medium">Customer</th>
                <th class="px-4 py-3 text-left font-medium">Type</th>
                <th class="px-4 py-3 text-left font-medium">Candidates</th>
                <th class="px-4 py-3 text-left font-medium">Status</th>
                <th class="px-4 py-3 text-left font-medium">Submitted</th>
                <th class="px-4 py-3 text-left font-medium">Updated</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($requests as $req)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-2.5 font-mono text-xs text-gray-700">{{ $req->reference }}</td>
                <td class="px-4 py-2.5 font-medium text-gray-900">{{ $req->customer->name ?? '—' }}</td>
                <td class="px-4 py-2.5 text-gray-500">{{ $req->type ?? '—' }}</td>
                <td class="px-4 py-2.5 text-gray-500">{{ $req->candidates_count ?? '—' }}</td>
                <td class="px-4 py-2.5">
                    <span class="badge {{ $req->statusBadgeClass() }}">{{ str_replace('_', ' ', $req->status) }}</span>
                </td>
                <td class="px-4 py-2.5 text-gray-500 text-xs">{{ $req->created_at->format('d M Y') }}</td>
                <td class="px-4 py-2.5 text-gray-500 text-xs">{{ $req->updated_at->diffForHumans() }}</td>
                <td class="px-4 py-2.5 text-right">
                    <a href="{{ route('requests.show', $req) }}" class="text-emerald-700 hover:text-emerald-900 text-xs font-medium">View →</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="px-4 py-10 text-center text-gray-400">No requests found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $requests->links() }}
</div>

@endsection
