@extends('layouts.admin')

@section('title', 'Billing Settings')
@section('page-title', 'Billing Settings')
@section('page-subtitle', 'Tax applied to invoices and cash requests')

@section('content')
<div class="bg-white rounded-lg border border-gray-200 p-6 max-w-lg">

    @if (session('success'))
        <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('config.billing.update') }}" class="space-y-4">
        @csrf @method('PUT')

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">SST rate (%) <span class="text-red-500">*</span></label>
            <div class="relative max-w-[160px]">
                <input type="number" name="sst_percent" value="{{ old('sst_percent', $sstPercent) }}"
                       step="0.01" min="0" max="100" required
                       class="w-full border border-gray-300 rounded-md px-3 py-2 pr-8 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-emerald-600">
                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">%</span>
            </div>
            @error('sst_percent')<span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>@enderror
            <p class="text-xs text-gray-500 mt-2 leading-relaxed">
                Malaysian Sales &amp; Service Tax applied to monthly invoice subtotals and to per-request cash totals,
                in both the admin and client portals. Enter <b>0</b> to charge no SST. The standard rate is <b>6%</b>.
                Changes affect invoices and totals generated from now on — previously issued invoices are unchanged.
            </p>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="nrh-btn nrh-btn-primary">Save SST rate</button>
        </div>
    </form>
</div>
@endsection
