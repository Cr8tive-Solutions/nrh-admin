@extends('layouts.admin')

@section('title', 'Scope Types')
@section('page-title', 'Scope Types')

@section('header-actions')
    <a href="{{ route('config.scopes.create') }}"
       class="nrh-btn nrh-btn-primary">
        + New Scope
    </a>
@endsection

@section('content')

<div x-data="{ countryFilter: '', scopeSearch: '' }">

    {{-- Filter bar --}}
    <div class="bg-white rounded-lg border border-gray-200 px-4 py-3 mb-4 flex items-center gap-3">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-gray-400 flex-shrink-0"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
        <select x-model="countryFilter"
                class="text-sm border border-gray-200 rounded-md px-3 py-1.5 bg-white text-gray-700 outline-none focus:border-emerald-500">
            <option value="">All Countries</option>
            @foreach($scopesByCountry as $country)
            <option value="{{ $country->id }}">{{ $country->flag }} {{ $country->name }}</option>
            @endforeach
        </select>
        <input type="search"
               x-model.debounce.200ms="scopeSearch"
               placeholder="Filter scopes…"
               class="text-sm border border-gray-200 rounded-md px-3 py-1.5 bg-white text-gray-700 outline-none focus:border-emerald-500 w-56">
        <button x-show="countryFilter !== '' || scopeSearch !== ''"
                x-transition
                @click="countryFilter = ''; scopeSearch = ''"
                class="text-xs text-gray-400 hover:text-gray-600 ml-1">
            Clear filters
        </button>
    </div>

    @foreach($scopesByCountry as $country)
    @php $allScopeNames = $country->scopeTypes->pluck('name')->map(fn($n) => strtolower($n))->values()->toArray(); @endphp
    <div class="bg-white rounded-lg border border-gray-200 mb-4"
         x-show="(countryFilter === '' || countryFilter === '{{ $country->id }}') &&
                 (scopeSearch === '' || {{ json_encode($allScopeNames) }}.some(n => n.includes(scopeSearch.toLowerCase())))">

        <div class="px-4 py-3 border-b border-gray-100 flex items-center gap-2">
            @if($country->flag)<span class="text-lg">{{ $country->flag }}</span>@endif
            <h2 class="text-sm font-semibold text-gray-900">{{ $country->name }}</h2>
            <span class="text-xs text-gray-400">({{ $country->scopeTypes->count() }} scopes)</span>
        </div>

        @php $grouped = $country->scopeTypes->groupBy('category'); @endphp

        @foreach($grouped as $category => $scopes)
        @php $catNames = $scopes->pluck('name')->map(fn($n) => strtolower($n))->values()->toArray(); @endphp
        <div class="border-b border-gray-50 last:border-0"
             x-show="scopeSearch === '' || {{ json_encode($catNames) }}.some(n => n.includes(scopeSearch.toLowerCase()))">
            <div class="px-4 py-2 bg-gray-50 text-xs font-medium text-gray-500 uppercase tracking-wide">
                {{ $category ?: 'Uncategorised' }}
            </div>
            <table class="nrh-table">
                <tbody>
                    @foreach($scopes as $scope)
                    <tr x-show="scopeSearch === '' || {{ json_encode(strtolower($scope->name)) }}.includes(scopeSearch.toLowerCase())">
                        <td class="text-gray-900 w-2/5">{{ $scope->name }}</td>
                        <td class="text-gray-500 text-xs w-1/5">{{ $scope->turnaround ?? '—' }}</td>
                        <td class="font-mono text-xs w-1/5">
                            @if($scope->price_on_request)
                                <span class="badge badge-yellow">Price on request</span>
                            @else
                                {{ $country->currency }} {{ number_format($scope->price, 2) }}
                            @endif
                        </td>
                        <td class="text-right w-1/5">
                            <a href="{{ route('config.scopes.edit', $scope) }}" class="text-emerald-700 hover:text-emerald-900 text-xs font-medium">Edit</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endforeach

        @if($country->scopeTypes->isEmpty())
        <div class="px-4 py-6 text-center text-gray-400 text-sm">No scope types defined.</div>
        @endif
    </div>
    @endforeach

    @if($scopesByCountry->isEmpty())
    <div class="bg-white rounded-lg border border-gray-200 p-12 text-center text-gray-400">
        No scope types yet. Add countries first, then create scopes.
    </div>
    @endif

</div>

@endsection
