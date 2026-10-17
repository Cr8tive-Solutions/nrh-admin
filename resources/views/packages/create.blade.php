@extends('layouts.admin')

@section('title', 'New Package · '.$customer->name)
@section('page-title', 'New Package')
@section('page-subtitle', $customer->name)

@section('header-actions')
    <a href="{{ $from === 'packages' ? route('packages.index', ['customer_id' => $customer->id]) : route('customers.show', $customer) }}"
       class="text-sm text-gray-500 hover:text-gray-700">← Cancel</a>
@endsection

@section('content')
    @include('packages._form', [
        'action' => route('customers.packages.store', $customer),
        'submitLabel' => 'Create Package',
    ])
@endsection
