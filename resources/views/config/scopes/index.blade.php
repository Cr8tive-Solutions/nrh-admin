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

@foreach($scopesByCountry as $country)
<div class="bg-white rounded-lg border border-gray-200 mb-4">
    <div class="px-4 py-3 border-b border-gray-100 flex items-center gap-2">
        @if($country->flag)<span class="text-lg">{{ $country->flag }}</span>@endif
        <h2 class="text-sm font-semibold text-gray-900">{{ $country->name }}</h2>
        <span class="text-xs text-gray-400">({{ $country->scopeTypes->count() }} scopes)</span>
    </div>

    @php $grouped = $country->scopeTypes->groupBy('category'); @endphp

    @foreach($grouped as $category => $scopes)
    <div class="border-b border-gray-50 last:border-0">
        <div class="px-4 py-2 bg-gray-50 text-xs font-medium text-gray-500 uppercase tracking-wide">
            {{ $category ?: 'Uncategorised' }}
        </div>
        <table class="w-full text-sm">
            <tbody class="divide-y divide-gray-50">
                @foreach($scopes as $scope)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2.5 text-gray-900 w-2/5">{{ $scope->name }}</td>
                    <td class="px-4 py-2.5 text-gray-500 text-xs w-1/5">{{ $scope->turnaround ?? '—' }}</td>
                    <td class="px-4 py-2.5 font-mono text-xs w-1/5">
                        @if($scope->price_on_request)
                            <span class="badge badge-yellow">Price on request</span>
                        @else
                            MYR {{ number_format($scope->price, 2) }}
                        @endif
                    </td>
                    <td class="px-4 py-2.5 text-right w-1/5">
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

@endsection
