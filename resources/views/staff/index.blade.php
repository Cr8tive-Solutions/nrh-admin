@extends('layouts.admin')

@section('title', 'Staff Accounts')
@section('page-title', 'Staff Accounts')

@section('header-actions')
    <a href="{{ route('staff.create') }}"
       class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-md transition-colors">
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
                                class="text-xs {{ $member->status === 'active' ? 'text-red-500 hover:text-red-700' : 'text-green-600 hover:text-green-800' }} font-medium">
                            {{ $member->status === 'active' ? 'Deactivate' : 'Activate' }}
                        </button>
                    </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">No staff accounts.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
