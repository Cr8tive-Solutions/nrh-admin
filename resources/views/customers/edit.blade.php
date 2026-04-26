@extends('layouts.admin')

@section('title', 'Edit Customer')
@section('page-title', 'Edit Customer')
@section('page-subtitle', $customer->name)

@section('header-actions')
    <a href="{{ route('customers.show', $customer) }}" class="nrh-btn nrh-btn-ghost">← Back to profile</a>
@endsection

@section('content')

@include('customers._form', [
    'action'      => route('customers.update', $customer),
    'method'      => 'PUT',
    'customer'    => $customer,
    'countries'   => $countries,
    'submitLabel' => 'Save Changes',
    'cancelUrl'   => route('customers.show', $customer),
])

@endsection
