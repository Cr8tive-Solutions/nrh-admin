@extends('layouts.admin')

@section('title', 'Staff Accounts')
@section('page-title', 'Staff Accounts')

@section('header-actions')
    <a href="{{ route('staff.create') }}"
       class="nrh-btn nrh-btn-primary">
        + New Staff
    </a>
@endsection

@section('content')

<div class="bg-white rounded-lg border border-gray-200 overflow-hidden max-w-3xl">
    <table class="w-full text-sm">
        <thead>
            <tr class="bg-gray-50 border-b border-gray-200 text-xs text-gray-500 uppercase">
                <th class="px-4 py-3 text-left font-medium">Name</th>
                <th class="px-4 py-3 text-left font-medium">Email</th>
                <th class="px-4 py-3 text-left font-medium">Role</th>
                <th class="px-4 py-3 text-left font-medium">Status</th>
                <th class="px-4 py-3 text-left font-medium">Created</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($staff as $member)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-2.5 font-medium text-gray-900">
                    {{ $member->name }}
                    @if($member->id === session('admin_id'))
                    <span class="badge badge-blue ml-1">you</span>
                    @endif
                </td>
                <td class="px-4 py-2.5 text-gray-500">{{ $member->email }}</td>
                <td class="px-4 py-2.5">
                    <span class="badge {{ $member->role === 'super_admin' ? 'badge-blue' : 'badge-gray' }}">
                        {{ str_replace('_', ' ', $member->role) }}
                    </span>
                </td>
                <td class="px-4 py-2.5">
                    <span class="badge {{ $member->status === 'active' ? 'badge-green' : 'badge-gray' }}">{{ $member->status }}</span>
                </td>
                <td class="px-4 py-2.5 text-gray-500 text-xs">{{ $member->created_at->format('d M Y') }}</td>
                <td class="px-4 py-2.5 text-right">
                    @if($member->id !== session('admin_id'))
                    <form method="POST" action="{{ route('staff.toggle', $member) }}" class="inline">
                        @csrf @method('PATCH')
                        <button type="submit"
                                class="text-xs {{ $member->status === 'active' ? 'text-error hover:text-error-dim' : 'text-emerald-700 hover:text-emerald-900' }} font-medium">
                            {{ $member->status === 'active' ? 'Deactivate' : 'Activate' }}
                        </button>
                    </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="6" style="padding:48px 20px; text-align:center;"><div style="display:flex; flex-direction:column; align-items:center; gap:8px;"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" style="color:var(--ink-200);"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg><span style="font-size:13px; color:var(--ink-400);">No staff accounts.</span></div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
