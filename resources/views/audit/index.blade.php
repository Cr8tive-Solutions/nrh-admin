@extends('layouts.admin')

@section('title', 'Audit Log')
@section('page-title', 'Audit Log')
@section('page-subtitle', 'Sensitive admin actions, most recent first')

@section('content')

<div class="bg-white rounded-lg border border-gray-200 px-4 py-3 mb-4 flex items-center gap-3">
    <form method="GET" action="{{ route('audit.index') }}" class="flex items-center gap-3 flex-1">
        <select name="action" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600">
            <option value="">All actions</option>
            @foreach($actions as $a)
            <option value="{{ $a }}" {{ request('action') === $a ? 'selected' : '' }}>{{ $a }}</option>
            @endforeach
        </select>
        <button type="submit" class="nrh-btn nrh-btn-primary">Filter</button>
        @if(request('action'))
        <a href="{{ route('audit.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
        @endif
    </form>
    <div class="text-sm text-gray-500">{{ $logs->total() }} entries</div>
</div>

<div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <table class="nrh-table">
        <thead>
            <tr>
                <th>When</th>
                <th>Actor</th>
                <th>Action</th>
                <th>Target</th>
                <th>IP</th>
                <th>Details</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
            <tr>
                <td class="text-xs text-gray-500 whitespace-nowrap">
                    {{ $log->created_at->format('d M Y, H:i:s') }}
                    <div class="text-gray-400">{{ $log->created_at->diffForHumans() }}</div>
                </td>
                <td>
                    <div class="font-medium text-gray-900 text-sm">{{ $log->actor->name ?? 'system / deleted' }}</div>
                    @if($log->actor)
                    <div class="text-xs text-gray-400">{{ $log->actor->email }}</div>
                    @endif
                </td>
                <td>
                    <span class="badge badge-blue font-mono text-xs">{{ $log->action }}</span>
                </td>
                <td>
                    @if($log->target)
                    <div class="font-medium text-gray-900 text-sm">{{ $log->target->name }}</div>
                    <div class="text-xs text-gray-400">{{ $log->target->email }}</div>
                    @else
                    <span class="text-gray-400 text-xs">—</span>
                    @endif
                </td>
                <td class="text-gray-500 text-xs font-mono">{{ $log->ip_address ?? '—' }}</td>
                <td class="text-xs">
                    @if($log->details)
                    <details>
                        <summary class="text-emerald-700 hover:text-emerald-900 cursor-pointer">View</summary>
                        <pre class="text-xs text-gray-600 bg-gray-50 rounded p-2 mt-1 overflow-x-auto">{{ json_encode($log->details, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </details>
                    @else
                    <span class="text-gray-400">—</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="6" style="padding:48px 20px; text-align:center;"><div style="display:flex; flex-direction:column; align-items:center; gap:8px;"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" style="color:var(--ink-200);"><circle cx="12" cy="12" r="10"/></svg><span style="font-size:13px; color:var(--ink-400);">No audit entries yet.</span></div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $logs->links() }}</div>

@endsection
