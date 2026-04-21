@extends('layouts.admin')

@section('title', 'Scope Pricing')
@section('page-title', 'Scope Pricing')
@section('page-subtitle', 'Set per-customer custom prices for scope types')

@section('content')

{{-- Customer selector --}}
<div class="bg-white rounded-lg border border-gray-200 p-4 mb-5">
    <form method="GET" action="{{ route('pricing.index') }}" class="flex items-center gap-3">
        <label class="text-sm font-medium text-gray-700">Customer:</label>
        <select name="customer_id" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-80">
            <option value="">— Select customer —</option>
            @foreach($customers as $c)
            <option value="{{ $c->id }}" {{ $customer && $customer->id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-1.5 rounded-md transition-colors">
            Load
        </button>
    </form>
</div>

@if($customer)
<form method="POST" action="{{ route('pricing.upsert', $customer) }}">
    @csrf

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
                    <tr>
                        <input type="hidden" name="prices[{{ $loop->parent->parent->index * 1000 + $loop->index }}][scope_type_id]" value="{{ $scope->id }}">
                        <td class="px-4 py-2.5 text-gray-900 w-1/2">{{ $scope->name }}</td>
                        <td class="px-4 py-2.5 text-gray-400 text-xs w-1/4">
                            @if($scope->price_on_request)
                                <span class="italic">Price on request</span>
                            @else
                                Default: MYR {{ number_format($scope->price, 2) }}
                            @endif
                        </td>
                        <td class="px-4 py-2.5 w-1/4">
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs text-gray-500">MYR</span>
                                <input type="number" step="0.01" min="0"
                                       name="prices[{{ $loop->parent->parent->index * 1000 + $loop->index }}][price]"
                                       value="{{ $scope->custom_price !== null ? number_format($scope->custom_price, 2, '.', '') : '' }}"
                                       placeholder="{{ $scope->price_on_request ? 'Enter price' : number_format($scope->price, 2) }}"
                                       class="w-32 border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endforeach
    </div>
    @endforeach

    <div class="sticky bottom-0 bg-white border-t border-gray-200 px-0 py-3 flex justify-end">
        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-8 py-2.5 rounded-md transition-colors">
            Save All Prices
        </button>
    </div>
</form>
@else
<div class="bg-white rounded-lg border border-gray-200 p-12 text-center text-gray-400">
    Select a customer above to manage their scope pricing.
</div>
@endif

@endsection
