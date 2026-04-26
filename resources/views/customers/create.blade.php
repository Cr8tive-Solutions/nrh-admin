@extends('layouts.admin')

@section('title', 'New Customer')
@section('page-title', 'New Customer')
@section('page-subtitle', 'Add a new corporate client account')

@section('header-actions')
    <a href="{{ route('customers.index') }}" class="nrh-btn nrh-btn-ghost">← Back to customers</a>
@endsection

@section('content')

@include('customers._form', [
    'action'      => route('customers.store'),
    'method'      => 'POST',
    'customer'    => null,
    'countries'   => $countries,
    'submitLabel' => 'Create Customer',
    'cancelUrl'   => route('customers.index'),
])

@endsection
