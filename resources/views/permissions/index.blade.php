@extends('layouts.admin')

@section('title', 'Roles & Permissions')
@section('page-title', 'Roles & Permissions')

@section('content')

<form method="POST" action="{{ route('permissions.update') }}">
    @csrf @method('PUT')

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-4">
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Role Matrix</h2>
                <p class="text-xs text-gray-500 mt-0.5">Tick a box to grant a role access to that permission. Super admin has every permission and cannot be edited.</p>
            </div>
            <button type="submit" class="nrh-btn nrh-btn-primary">Save Changes</button>
        </div>

        <table class="nrh-table">
            <thead>
                <tr>
                    <th class="w-1/2">Permission</th>
                    @foreach($roles as $role)
                    <th class="text-center" style="width: 12%;">
                        <span class="badge {{ $role === 'super_admin' ? 'badge-green' : 'badge-gray' }}">{{ str_replace('_', ' ', $role) }}</span>
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($permissions as $group => $items)
                <tr class="bg-gray-50">
                    <td colspan="{{ count($roles) + 1 }}" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                        {{ $group }}
                    </td>
                </tr>
                @foreach($items as $perm)
                <tr>
                    <td>
                        <div class="font-medium text-gray-900">{{ $perm->label }}</div>
                        <div class="text-xs text-gray-400 font-mono">{{ $perm->key }}</div>
                    </td>
                    @foreach($roles as $role)
                    <td class="text-center">
                        @if($role === 'super_admin')
                            <input type="checkbox" checked disabled class="accent-emerald-700 cursor-not-allowed opacity-60">
                        @else
                            <input type="checkbox"
                                   name="matrix[{{ $role }}][]"
                                   value="{{ $perm->id }}"
                                   {{ in_array($perm->id, $matrix[$role] ?? [], true) ? 'checked' : '' }}
                                   class="accent-emerald-700 cursor-pointer">
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endforeach
                @endforeach
            </tbody>
        </table>

        <div class="px-4 py-3 border-t border-gray-100 flex justify-end">
            <button type="submit" class="nrh-btn nrh-btn-primary">Save Changes</button>
        </div>
    </div>
</form>

<div class="text-xs text-gray-500 mt-3">
    Per-user overrides (force-grant or force-revoke for a specific staff member) live on each staff member's <strong>Permissions</strong> tab.
</div>

@endsection
