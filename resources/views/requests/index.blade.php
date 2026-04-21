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
    <table class="nrh-table">
        <thead>
            <tr>
                <th>Reference</th>
                <th>Customer</th>
                <th>Type</th>
                <th>Candidates</th>
                <th>Status</th>
                <th>Submitted</th>
                <th>Updated</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($requests as $req)
            <tr>
                <td class="font-mono text-xs text-gray-700">{{ $req->reference }}</td>
                <td class="font-medium text-gray-900">{{ $req->customer->name ?? '—' }}</td>
                <td class="text-gray-500">{{ $req->type ?? '—' }}</td>
                <td class="text-gray-500">{{ $req->candidates_count ?? '—' }}</td>
                <td class="">
                    <span class="badge {{ $req->statusBadgeClass() }}">{{ str_replace('_', ' ', $req->status) }}</span>
                </td>
                <td class="text-gray-500 text-xs">{{ $req->created_at->format('d M Y') }}</td>
                <td class="text-gray-500 text-xs">{{ $req->updated_at->diffForHumans() }}</td>
                <td class="text-right">
                    <a href="{{ route('requests.show', $req) }}" class="text-emerald-700 hover:text-emerald-900 text-xs font-medium">View →</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" style="padding:48px 20px; text-align:center;"><div style="display:flex; flex-direction:column; align-items:center; gap:8px;"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" style="color:var(--ink-200);"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg><span style="font-size:13px; color:var(--ink-400);">No requests found.</span></div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $requests->links() }}
</div>

@endsection
