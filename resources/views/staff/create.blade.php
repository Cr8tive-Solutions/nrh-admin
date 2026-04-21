@extends('layouts.admin')

@section('title', 'New Staff Account')
@section('page-title', 'New Staff Account')

@section('header-actions')
    <a href="{{ route('staff.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Cancel</a>
@endsection

@section('content')
<div class="bg-white rounded-lg border border-gray-200 p-6 max-w-lg">
    <form method="POST" action="{{ route('staff.store') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}" required
                   class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
            <input type="email" name="email" value="{{ old('email') }}" required
                   class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Role <span class="text-red-500">*</span></label>
            <select name="role" required
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600">
                <option value="">Select role…</option>
                <option value="super_admin" {{ old('role') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                <option value="operations" {{ old('role') === 'operations' ? 'selected' : '' }}>Operations</option>
                <option value="finance" {{ old('role') === 'finance' ? 'selected' : '' }}>Finance</option>
                <option value="viewer" {{ old('role') === 'viewer' ? 'selected' : '' }}>Viewer</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
            <input type="password" name="password" required minlength="8"
                   class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password <span class="text-red-500">*</span></label>
            <input type="password" name="password_confirmation" required
                   class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600">
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="nrh-btn nrh-btn-primary">
                Create Account
            </button>
        </div>
    </form>
</div>
@endsection
