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
    <table class="nrh-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>2FA</th>
                <th>Status</th>
                <th>Created</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($staff as $member)
            <tr>
                <td class="font-medium text-gray-900">
                    {{ $member->name }}
                    @if($member->id === session('admin_id'))
                    <span class="badge badge-blue ml-1">you</span>
                    @endif
                </td>
                <td class="text-gray-500">{{ $member->email }}</td>
                <td class="">
                    <span class="badge {{ $member->role === 'super_admin' ? 'badge-blue' : 'badge-gray' }}">
                        {{ str_replace('_', ' ', $member->role) }}
                    </span>
                </td>
                <td class="">
                    @if($member->hasEnabledTwoFactor())
                        <span class="badge badge-green">on</span>
                    @else
                        <span class="badge badge-gray">off</span>
                    @endif
                </td>
                <td class="">
                    <span class="badge {{ $member->status === 'active' ? 'badge-green' : 'badge-gray' }}">{{ $member->status }}</span>
                </td>
                <td class="text-gray-500 text-xs">{{ $member->created_at->format('d M Y') }}</td>
                <td class="text-right">
                    <a href="{{ route('staff.permissions', $member) }}" class="text-xs text-emerald-700 hover:text-emerald-900 font-medium mr-3">Permissions</a>
                    @if(current_admin()?->isSuperAdmin() && $member->id !== session('admin_id') && $member->hasEnabledTwoFactor())
                    <form method="POST" action="{{ route('staff.reset-2fa', $member) }}" class="inline mr-3"
                          onsubmit="return confirm('Reset 2FA for {{ $member->name }}? They will be able to sign in with just their password and must re-enroll. This action is logged.');">
                        @csrf @method('PATCH')
                        <button type="submit" class="text-xs text-amber-600 hover:text-amber-800 font-medium">Reset 2FA</button>
                    </form>
                    @endif
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
            <tr><td colspan="7" style="padding:48px 20px; text-align:center;"><div style="display:flex; flex-direction:column; align-items:center; gap:8px;"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" style="color:var(--ink-200);"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg><span style="font-size:13px; color:var(--ink-400);">No staff accounts.</span></div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
