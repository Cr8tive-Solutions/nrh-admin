@extends('layouts.admin')

@section('title', 'Countries')
@section('page-title', 'Countries')

@section('content')

<div class="grid grid-cols-2 gap-6">

    {{-- Existing countries --}}
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-900">Configured Countries</h2>
        </div>
        <table class="nrh-table">
            <thead>
                <tr>
                    <th>Flag</th>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Currency</th>
                    <th>Region</th>
                    <th>Scopes</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($countries as $country)
                <tr x-data="{ editing: false }">

                    {{-- View mode --}}
                    <td x-show="!editing" class="text-lg">{{ $country->flag }}</td>
                    <td x-show="!editing" class="font-medium text-gray-900">{{ $country->name }}</td>
                    <td x-show="!editing" class="font-mono text-xs text-gray-500">{{ $country->code }}</td>
                    <td x-show="!editing" class="font-mono text-xs font-semibold text-gray-700">{{ $country->currency }}</td>
                    <td x-show="!editing" class="text-gray-500">{{ $country->region ?? '—' }}</td>
                    <td x-show="!editing" class="text-gray-500">{{ $country->scope_types_count }}</td>
                    <td x-show="!editing" class="text-right">
                        <button @click="editing = true" class="text-emerald-700 hover:text-emerald-900 text-xs font-medium">Edit</button>
                    </td>

                    {{-- Edit mode --}}
                    <td x-show="editing" colspan="7" class="p-0">
                        <form method="POST" action="{{ route('config.countries.update', $country) }}" class="flex items-center gap-2 px-3 py-2 bg-emerald-50">
                            @csrf @method('PUT')
                            <input type="text" name="flag" value="{{ $country->flag }}" placeholder="🇲🇾" maxlength="10"
                                   class="w-12 border border-gray-300 rounded px-2 py-1 text-sm text-center focus:outline-none focus:ring-1 focus:ring-emerald-600">
                            <input type="text" name="name" value="{{ $country->name }}" required placeholder="Name"
                                   class="flex-1 border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-emerald-600">
                            <input type="text" name="code" value="{{ $country->code }}" required maxlength="3" placeholder="MYS"
                                   class="w-16 border border-gray-300 rounded px-2 py-1 text-sm uppercase font-mono focus:outline-none focus:ring-1 focus:ring-emerald-600">
                            <input type="text" name="currency" value="{{ $country->currency }}" required maxlength="3" placeholder="MYR"
                                   class="w-16 border border-gray-300 rounded px-2 py-1 text-sm uppercase font-mono focus:outline-none focus:ring-1 focus:ring-emerald-600">
                            <input type="text" name="region" value="{{ $country->region }}" placeholder="Region"
                                   class="w-32 border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-emerald-600">
                            <button type="submit" class="nrh-btn nrh-btn-primary text-xs py-1 px-3">Save</button>
                            <button type="button" @click="editing = false" class="text-xs text-gray-400 hover:text-gray-600">Cancel</button>
                        </form>
                    </td>

                </tr>
                @empty
                <tr><td colspan="7" style="padding:48px 20px; text-align:center;"><div style="display:flex; flex-direction:column; align-items:center; gap:8px;"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" style="color:var(--ink-200);"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg><span style="font-size:13px; color:var(--ink-400);">No countries yet.</span></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Add country --}}
    <div class="bg-white rounded-lg border border-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-900 mb-4">Add Country</h2>
        <form method="POST" action="{{ route('config.countries.store') }}" class="space-y-3">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Code (3-char) <span class="text-red-500">*</span></label>
                    <input type="text" name="code" value="{{ old('code') }}" maxlength="3" required
                           placeholder="MYS"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600 uppercase">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Currency (3-char) <span class="text-red-500">*</span></label>
                    <input type="text" name="currency" value="{{ old('currency', 'USD') }}" maxlength="3" required
                           placeholder="USD"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600 uppercase">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Flag emoji</label>
                    <input type="text" name="flag" value="{{ old('flag') }}" maxlength="10"
                           placeholder="🇲🇾"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Region</label>
                    <input type="text" name="region" value="{{ old('region') }}"
                           placeholder="e.g. Southeast Asia"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600">
                </div>
            </div>
            <button type="submit" class="nrh-btn nrh-btn-primary">
                Add Country
            </button>
        </form>
    </div>

</div>

@endsection
