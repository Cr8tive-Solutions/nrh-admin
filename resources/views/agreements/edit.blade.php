@extends('layouts.admin')

@section('title', 'Edit Agreement')
@section('page-title', 'Edit Agreement')
@section('page-subtitle', $customer->name)

@section('header-actions')
    <a href="{{ route('customers.show', $customer) }}" class="text-sm text-gray-500 hover:text-gray-700">← Cancel</a>
@endsection

@section('content')
<div class="bg-white rounded-lg border border-gray-200 p-6 max-w-lg">
    <form method="POST" action="{{ route('customers.agreements.update', [$customer, $agreement]) }}" class="space-y-4">
        @csrf @method('PUT')

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Agreement Type <span class="text-red-500">*</span></label>
            <input type="text" name="type" value="{{ old('type', $agreement->type) }}" required
                   class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Start Date <span class="text-red-500">*</span></label>
                <input type="date" name="start_date" value="{{ old('start_date', $agreement->start_date->format('Y-m-d')) }}" required
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Expiry Date <span class="text-red-500">*</span></label>
                <input type="date" name="expiry_date" value="{{ old('expiry_date', $agreement->expiry_date->format('Y-m-d')) }}" required
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">SLA TAT</label>
            <input type="text" name="sla_tat" value="{{ old('sla_tat', $agreement->sla_tat) }}"
                   class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600">
        </div>

        @php
            // Tolerate legacy/free-text values: anything that isn't 'per_request' falls back to 'monthly'.
            $currentBilling = old('billing', $agreement->billing) === 'per_request' ? 'per_request' : 'monthly';
        @endphp
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Billing Mode <span class="text-red-500">*</span></label>
            <select name="billing" required
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600 bg-white">
                <option value="monthly" {{ $currentBilling === 'monthly' ? 'selected' : '' }}>Monthly invoice (credit)</option>
                <option value="per_request" {{ $currentBilling === 'per_request' ? 'selected' : '' }}>Pay per request (cash)</option>
            </select>
            <div class="mt-2 text-xs text-gray-500 leading-relaxed space-y-1">
                <p><strong class="text-gray-700">Monthly invoice</strong> — Customer is billed at month-end. Requests start processing immediately on submit.</p>
                <p><strong class="text-gray-700">Pay per request</strong> — Customer must transfer payment before each request is processed. The client portal shows them bank details and a reference; you confirm receipt by flipping the request status from "new" to "in_progress" once the transfer clears.</p>
            </div>
            @error('billing')<span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Payment Terms</label>
            <input type="text" name="payment" value="{{ old('payment', $agreement->payment) }}"
                   placeholder="e.g. Net 30"
                   class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600">
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="nrh-btn nrh-btn-primary">
                Save Changes
            </button>
        </div>
    </form>
</div>
@endsection
