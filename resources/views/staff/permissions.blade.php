@extends('layouts.admin')

@section('title', $admin->name . ' — Permissions')
@section('page-title', $admin->name)
@section('page-subtitle', 'Per-user permission overrides')

@section('header-actions')
    <a href="{{ route('staff.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Back to Staff</a>
@endsection

@section('content')

<div class="max-w-4xl">

    <div class="bg-blue-50 border border-blue-200 text-blue-800 text-xs px-4 py-3 rounded-lg mb-4 flex items-start gap-2">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="flex-shrink-0 mt-0.5"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
        <div>
            <strong>{{ $admin->name }}</strong> inherits from role <strong>{{ str_replace('_', ' ', $admin->role) }}</strong>.
            Use this page to <strong>force grant</strong> a permission their role doesn't include, or <strong>force revoke</strong> a permission their role normally would have.
            Leave on <strong>Inherit</strong> to use the role default.
            @if($admin->isSuperAdmin())
            <br><em class="text-blue-600">Note: super admin always has every permission. Overrides have no effect.</em>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ route('staff.permissions.update', $admin) }}">
        @csrf @method('PUT')

        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-4">
            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-900">Permissions</h2>
                <button type="submit" class="nrh-btn nrh-btn-primary" {{ $admin->isSuperAdmin() ? 'disabled' : '' }}>Save Changes</button>
            </div>

            <table class="nrh-table">
                <thead>
                    <tr>
                        <th class="w-1/2">Permission</th>
                        <th class="text-center">Role default</th>
                        <th class="text-center" style="width: 30%;">Override</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($permissions as $group => $items)
                    <tr class="bg-gray-50">
                        <td colspan="3" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ $group }}</td>
                    </tr>
                    @foreach($items as $perm)
                    @php
                        $roleHas = in_array($perm->id, $rolePermIds, true);
                        $current = array_key_exists($perm->id, $overrides)
                            ? ($overrides[$perm->id] ? 'grant' : 'revoke')
                            : 'inherit';
                    @endphp
                    <tr>
                        <td>
                            <div class="font-medium text-gray-900">{{ $perm->label }}</div>
                            <div class="text-xs text-gray-400 font-mono">{{ $perm->key }}</div>
                        </td>
                        <td class="text-center">
                            @if($roleHas)
                                <span class="badge badge-green">Granted</span>
                            @else
                                <span class="badge badge-gray">Not granted</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="inline-flex items-center gap-3 text-xs">
                                <label class="inline-flex items-center gap-1 cursor-pointer">
                                    <input type="radio" name="override[{{ $perm->id }}]" value="inherit"
                                           {{ $current === 'inherit' ? 'checked' : '' }}
                                           class="accent-gray-500" {{ $admin->isSuperAdmin() ? 'disabled' : '' }}>
                                    <span class="text-gray-600">Inherit</span>
                                </label>
                                <label class="inline-flex items-center gap-1 cursor-pointer">
                                    <input type="radio" name="override[{{ $perm->id }}]" value="grant"
                                           {{ $current === 'grant' ? 'checked' : '' }}
                                           class="accent-emerald-700" {{ $admin->isSuperAdmin() ? 'disabled' : '' }}>
                                    <span class="text-emerald-700 font-medium">Grant</span>
                                </label>
                                <label class="inline-flex items-center gap-1 cursor-pointer">
                                    <input type="radio" name="override[{{ $perm->id }}]" value="revoke"
                                           {{ $current === 'revoke' ? 'checked' : '' }}
                                           class="accent-red-600" {{ $admin->isSuperAdmin() ? 'disabled' : '' }}>
                                    <span class="text-red-600 font-medium">Revoke</span>
                                </label>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                    @endforeach
                </tbody>
            </table>

            <div class="px-4 py-3 border-t border-gray-100 flex justify-end">
                <button type="submit" class="nrh-btn nrh-btn-primary" {{ $admin->isSuperAdmin() ? 'disabled' : '' }}>Save Changes</button>
            </div>
        </div>
    </form>
</div>

@endsection
