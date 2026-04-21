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
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-xs text-gray-500 uppercase">
                    <th class="px-4 py-3 text-left font-medium">Flag</th>
                    <th class="px-4 py-3 text-left font-medium">Name</th>
                    <th class="px-4 py-3 text-left font-medium">Code</th>
                    <th class="px-4 py-3 text-left font-medium">Region</th>
                    <th class="px-4 py-3 text-left font-medium">Scopes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($countries as $country)
                <tr x-data="{ editing: false }">
                    <td class="px-4 py-2.5 text-lg">{{ $country->flag }}</td>
                    <td class="px-4 py-2.5 font-medium text-gray-900">{{ $country->name }}</td>
                    <td class="px-4 py-2.5 font-mono text-xs text-gray-500">{{ $country->code }}</td>
                    <td class="px-4 py-2.5 text-gray-500">{{ $country->region ?? '—' }}</td>
                    <td class="px-4 py-2.5 text-gray-500">{{ $country->scope_types_count }}</td>
                </tr>
                @empty
                <tr><td colspan="5" style="padding:48px 20px; text-align:center;"><div style="display:flex; flex-direction:column; align-items:center; gap:8px;"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" style="color:var(--ink-200);"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg><span style="font-size:13px; color:var(--ink-400);">No countries yet.</span></div></td></tr>
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
                    <label class="block text-sm font-medium text-gray-700 mb-1">Flag emoji</label>
                    <input type="text" name="flag" value="{{ old('flag') }}" maxlength="10"
                           placeholder="🇲🇾"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Region</label>
                <input type="text" name="region" value="{{ old('region') }}"
                       placeholder="e.g. Southeast Asia"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600">
            </div>
            <button type="submit" class="nrh-btn nrh-btn-primary">
                Add Country
            </button>
        </form>
    </div>

</div>

@endsection
