@extends('layouts.admin')

@section('title', 'Edit Package · '.$customer->name)
@section('page-title', 'Edit Package')
@section('page-subtitle', $customer->name.' · '.$package->name)

@section('header-actions')
    <a href="{{ $from === 'packages' ? route('packages.index', ['customer_id' => $customer->id]) : route('customers.show', $customer) }}"
       class="text-sm text-gray-500 hover:text-gray-700">← Cancel</a>
@endsection

@section('content')
    @include('packages._form', [
        'action' => route('customers.packages.update', [$customer, $package]),
        'submitLabel' => 'Save Changes',
    ])

    <div class="bg-white rounded-lg border border-red-200 p-6 max-w-2xl mt-5">
        <div class="text-sm font-semibold text-gray-900 mb-1">Delete this package</div>
        <p class="text-xs text-gray-500 leading-relaxed mb-3">
            Removes the package and its scope list. Submitted requests keep working — their scopes were copied onto
            each candidate at submission, so nothing is re-priced or re-scoped.
            @if($requestCount > 0)
                <span class="block mt-1 text-amber-700">
                    {{ $requestCount }} {{ Str::plural('request', $requestCount) }}
                    {{ $requestCount === 1 ? 'was' : 'were' }} ordered from this package. Deleting it clears that
                    attribution, so {{ $requestCount === 1 ? 'it' : 'they' }} will show as a custom selection in
                    reporting. Consider renaming it instead.
                </span>
            @endif
        </p>
        <form method="POST" action="{{ route('customers.packages.destroy', [$customer, $package]) }}"
              onsubmit="return confirm('Delete the package &quot;{{ $package->name }}&quot; for {{ $customer->name }}?@if($requestCount > 0)\n\n{{ $requestCount }} {{ Str::plural('request', $requestCount) }} will lose their package attribution.@endif');">
            @csrf
            @method('DELETE')
            @if($from === 'packages')
                <input type="hidden" name="from" value="packages">
            @endif
            <button type="submit" class="nrh-btn" style="border-color: var(--danger); color: var(--danger);">
                Delete Package
            </button>
        </form>
    </div>
@endsection
