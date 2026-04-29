@extends('layouts.admin')

@section('title', 'New Scope Type')
@section('page-title', 'New Scope Type')

@section('header-actions')
    <a href="{{ route('config.scopes.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Cancel</a>
@endsection

@section('content')
<div class="bg-white rounded-lg border border-gray-200 p-6 max-w-lg"
     x-data="{
         currencyMap: {{ json_encode($countries->pluck('currency', 'id')) }},
         selectedCurrency: '{{ old('country_id') ? ($countries->firstWhere('id', old('country_id'))?->currency ?? '') : '' }}',
         onCountryChange(id) { this.selectedCurrency = this.currencyMap[id] ?? ''; }
     }">
    <form method="POST" action="{{ route('config.scopes.store') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Country <span class="text-red-500">*</span></label>
            <select name="country_id" required
                    @change="onCountryChange($event.target.value)"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600">
                <option value="">Select country…</option>
                @foreach($countries as $country)
                <option value="{{ $country->id }}" {{ old('country_id') == $country->id ? 'selected' : '' }}>
                    {{ $country->flag }} {{ $country->name }}
                </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Scope Name <span class="text-red-500">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}" required
                   class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
            <input type="text" name="category" value="{{ old('category') }}"
                   placeholder="e.g. Security & Integrity Check"
                   class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Turnaround (display)</label>
                <input type="text" name="turnaround" value="{{ old('turnaround') }}"
                       placeholder="e.g. 3 business days"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600">
                <p class="text-xs text-gray-400 mt-1">Free text shown to clients.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">SLA target (business hours)</label>
                <input type="number" name="turnaround_hours" value="{{ old('turnaround_hours') }}"
                       min="1" max="1440" placeholder="24"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600 font-mono">
                <p class="text-xs text-gray-400 mt-1">Used for TAT calculation.</p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Price<span x-show="selectedCurrency"> (<span x-text="selectedCurrency"></span>)</span> <span class="text-red-500">*</span>
                </label>
                <input type="number" name="price" value="{{ old('price', '0.00') }}" step="0.01" min="0" required
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600">
            </div>
            <div class="flex items-end pb-2">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="price_on_request" value="1" {{ old('price_on_request') ? 'checked' : '' }}
                           class="accent-emerald-700 w-4 h-4">
                    <span class="text-sm text-gray-700">Price on request</span>
                </label>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea name="description" rows="2"
                      class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600">{{ old('description') }}</textarea>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="nrh-btn nrh-btn-primary">
                Create Scope
            </button>
        </div>
    </form>
</div>
@endsection
