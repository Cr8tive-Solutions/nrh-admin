@extends('layouts.admin')

@section('title', 'Countries')
@section('page-title', 'Countries')

@section('content')

<div x-data="{
    search: '',
    regionFilter: '',
    showAdd: false,
    regions: {{ json_encode($countries->pluck('region')->filter()->unique()->sort()->values()) }},
}">

    {{-- ── Top bar ── --}}
    <div class="bg-white rounded-lg border border-gray-200 px-4 py-3 mb-3 flex items-center gap-3">
        {{-- Search --}}
        <div class="flex items-center gap-2 flex-1 max-w-xs border border-gray-200 rounded-md px-3 py-1.5 bg-white focus-within:border-emerald-500">
            <svg class="text-gray-400 flex-shrink-0" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <input type="search" x-model.debounce.150ms="search" placeholder="Search countries…"
                   class="w-full text-sm outline-none bg-transparent">
        </div>

        {{-- Region filter --}}
        <select x-model="regionFilter" class="text-sm border border-gray-200 rounded-md px-3 py-1.5 bg-white text-gray-700 outline-none focus:border-emerald-500">
            <option value="">All Regions</option>
            <template x-for="r in regions" :key="r">
                <option :value="r" x-text="r"></option>
            </template>
        </select>

        {{-- Clear --}}
        <button x-show="search !== '' || regionFilter !== ''"
                x-transition
                @click="search = ''; regionFilter = ''"
                class="text-xs text-gray-400 hover:text-gray-600">
            Clear
        </button>

        <div class="ml-auto flex items-center gap-3">
            {{-- Count badge --}}
            <span class="text-xs text-gray-400">
                {{ $countries->count() }} countries
            </span>

            {{-- Add button --}}
            <button @click="showAdd = !showAdd"
                    :class="showAdd ? 'bg-gray-100 text-gray-700' : 'bg-emerald-700 text-white'"
                    class="text-sm font-medium px-3 py-1.5 rounded-md transition-colors">
                <span x-show="!showAdd">+ Add Country</span>
                <span x-show="showAdd">✕ Cancel</span>
            </button>
        </div>
    </div>

    {{-- ── Add country form (collapsible) ── --}}
    <div x-show="showAdd" x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
         class="bg-white rounded-lg border border-emerald-200 p-5 mb-3">
        <h2 class="text-sm font-semibold text-gray-900 mb-4">Add Country</h2>
        <form method="POST" action="{{ route('config.countries.store') }}">
            @csrf
            <div class="grid grid-cols-5 gap-3 items-end">
                <div class="col-span-1">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Flag emoji</label>
                    <input type="text" name="flag" value="{{ old('flag') }}" maxlength="10" placeholder="🇲🇾"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600 text-center">
                </div>
                <div class="col-span-1">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="Malaysia"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Code (ISO 3) <span class="text-red-500">*</span></label>
                    <input type="text" name="code" value="{{ old('code') }}" maxlength="3" required placeholder="MYS"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600 uppercase font-mono">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Currency <span class="text-red-500">*</span></label>
                    <input type="text" name="currency" value="{{ old('currency', 'USD') }}" maxlength="3" required placeholder="USD"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600 uppercase font-mono">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Region</label>
                    <input type="text" name="region" value="{{ old('region') }}" placeholder="e.g. Asia"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600">
                </div>
            </div>
            <div class="mt-3 flex gap-2">
                <button type="submit" class="nrh-btn nrh-btn-primary text-sm">Add Country</button>
                <button type="button" @click="showAdd = false" class="text-sm text-gray-500 hover:text-gray-700 px-3 py-1.5">Cancel</button>
            </div>
        </form>
    </div>

    @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm px-4 py-2.5 rounded-lg mb-3">
        {{ session('success') }}
    </div>
    @endif

    {{-- ── Countries table ── --}}
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <table class="nrh-table">
            <thead>
                <tr>
                    <th class="w-10">Flag</th>
                    <th>Country</th>
                    <th class="w-20">Code</th>
                    <th class="w-20">Currency</th>
                    <th class="w-32">Region</th>
                    <th class="w-16 text-center">Scopes</th>
                    <th class="w-20"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($countries as $country)
                @php
                    $nameLC   = strtolower($country->name);
                    $regionLC = strtolower($country->region ?? '');
                @endphp
                <tr x-data="{ editing: false }"
                    x-show="(search === '' || '{{ $nameLC }}'.includes(search.toLowerCase())) &&
                             (regionFilter === '' || regionFilter === '{{ $country->region }}')">

                    {{-- ── View mode ── --}}
                    <template x-if="!editing">
                        <td class="text-xl text-center">{{ $country->flag ?? '🌐' }}</td>
                    </template>
                    <template x-if="!editing">
                        <td>
                            <span class="font-medium text-gray-900">{{ $country->name }}</span>
                            @if($country->name === 'Malaysia')
                            <span class="ml-1.5 text-xs font-semibold px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700">HQ</span>
                            @endif
                        </td>
                    </template>
                    <template x-if="!editing">
                        <td class="font-mono text-xs text-gray-500">{{ $country->code }}</td>
                    </template>
                    <template x-if="!editing">
                        <td>
                            <span class="font-mono text-xs font-semibold px-2 py-0.5 rounded
                                {{ $country->currency === 'MYR' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ $country->currency }}
                            </span>
                        </td>
                    </template>
                    <template x-if="!editing">
                        <td class="text-xs text-gray-500">{{ $country->region ?? '—' }}</td>
                    </template>
                    <template x-if="!editing">
                        <td class="text-center">
                            @if($country->scope_types_count > 0)
                            <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">
                                {{ $country->scope_types_count }}
                            </span>
                            @else
                            <span class="text-xs text-gray-300">—</span>
                            @endif
                        </td>
                    </template>
                    <template x-if="!editing">
                        <td class="text-right">
                            <button @click="editing = true"
                                    class="text-xs text-emerald-700 hover:text-emerald-900 font-medium px-2 py-1 rounded hover:bg-emerald-50 transition-colors">
                                Edit
                            </button>
                        </td>
                    </template>

                    {{-- ── Edit mode ── --}}
                    <template x-if="editing">
                        <td colspan="7" class="p-0">
                            <form method="POST" action="{{ route('config.countries.update', $country) }}"
                                  class="flex items-center gap-2 px-3 py-2.5 bg-emerald-50 border-y border-emerald-100">
                                @csrf @method('PUT')
                                <input type="text" name="flag" value="{{ $country->flag }}" maxlength="10" placeholder="🌐"
                                       title="Flag emoji"
                                       class="w-12 border border-gray-300 rounded px-2 py-1.5 text-sm text-center focus:outline-none focus:ring-1 focus:ring-emerald-500 bg-white">
                                <input type="text" name="name" value="{{ $country->name }}" required placeholder="Name"
                                       title="Country name"
                                       class="flex-1 border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-emerald-500 bg-white">
                                <input type="text" name="code" value="{{ $country->code }}" required maxlength="3" placeholder="MYS"
                                       title="ISO 3-char code"
                                       class="w-20 border border-gray-300 rounded px-2 py-1.5 text-sm uppercase font-mono focus:outline-none focus:ring-1 focus:ring-emerald-500 bg-white">
                                <input type="text" name="currency" value="{{ $country->currency }}" required maxlength="3" placeholder="USD"
                                       title="Currency code"
                                       class="w-20 border border-gray-300 rounded px-2 py-1.5 text-sm uppercase font-mono focus:outline-none focus:ring-1 focus:ring-emerald-500 bg-white">
                                <input type="text" name="region" value="{{ $country->region }}" placeholder="Region"
                                       title="Region"
                                       class="w-36 border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-emerald-500 bg-white">
                                <div class="flex items-center gap-2 ml-2">
                                    <button type="submit"
                                            class="text-xs font-semibold bg-emerald-700 text-white px-3 py-1.5 rounded hover:bg-emerald-800 transition-colors whitespace-nowrap">
                                        Save
                                    </button>
                                    <button type="button" @click="editing = false"
                                            class="text-xs text-gray-500 hover:text-gray-700 px-2 py-1.5">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </td>
                    </template>

                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-12 text-center text-gray-400 text-sm">
                        No countries yet. Add one above.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        {{-- No results state --}}
        <div x-show="{{ $countries->count() > 0 ? 'true' : 'false' }} && (search !== '' || regionFilter !== '')"
             x-cloak
             class="hidden">
            {{-- Handled by x-show on rows; if all hidden we show this --}}
        </div>
    </div>

    {{-- No filter match message --}}
    <div x-show="search !== '' || regionFilter !== ''"
         x-transition
         class="mt-2 text-center text-xs text-gray-400">
        Showing filtered results — <button @click="search = ''; regionFilter = ''" class="underline hover:text-gray-600">clear filters</button> to see all {{ $countries->count() }} countries.
    </div>

</div>

@endsection
