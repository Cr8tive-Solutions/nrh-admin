@extends('layouts.admin')

@section('title', 'Customers')
@section('page-title', 'Customers')

@section('header-actions')
    <a href="{{ route('customers.create') }}"
       class="nrh-btn nrh-btn-primary">
        + New Customer
    </a>
@endsection

@section('content')

{{-- Search --}}
<div class="bg-white rounded-lg border border-gray-200 px-4 py-3 mb-4 flex items-center gap-3">
    <form method="GET" action="{{ route('customers.index') }}" class="flex items-center gap-3 flex-1">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search name, registration no, email…"
               class="border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600 w-80">
        <button type="submit" class="nrh-btn nrh-btn-primary">
            Search
        </button>
        @if(request('search'))
        <a href="{{ route('customers.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
        @endif
    </form>
    <div class="text-sm text-gray-500">{{ $customers->total() }} customers</div>
</div>

<div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="bg-gray-50 border-b border-gray-200 text-xs text-gray-500 uppercase">
                <th class="px-4 py-3 text-left font-medium">Company</th>
                <th class="px-4 py-3 text-left font-medium">Reg No.</th>
                <th class="px-4 py-3 text-left font-medium">Industry</th>
                <th class="px-4 py-3 text-left font-medium">Contact</th>
                <th class="px-4 py-3 text-left font-medium">Requests</th>
                <th class="px-4 py-3 text-left font-medium">Invoices</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($customers as $customer)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-2.5">
                    <div class="font-medium text-gray-900">{{ $customer->name }}</div>
                    @if($customer->country)
                    <div class="text-xs text-gray-400">{{ $customer->country }}</div>
                    @endif
                </td>
                <td class="px-4 py-2.5 text-gray-500 font-mono text-xs">{{ $customer->registration_no ?? '—' }}</td>
                <td class="px-4 py-2.5 text-gray-500">{{ $customer->industry ?? '—' }}</td>
                <td class="px-4 py-2.5">
                    <div class="text-gray-900">{{ $customer->contact_name ?? '—' }}</div>
                    @if($customer->contact_email)
                    <div class="text-xs text-gray-400">{{ $customer->contact_email }}</div>
                    @endif
                </td>
                <td class="px-4 py-2.5 text-gray-500">{{ $customer->screening_requests_count }}</td>
                <td class="px-4 py-2.5 text-gray-500">{{ $customer->invoices_count }}</td>
                <td class="px-4 py-2.5 text-right">
                    <a href="{{ route('customers.show', $customer) }}" class="text-emerald-700 hover:text-emerald-900 text-xs font-medium">View →</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" style="padding:48px 20px; text-align:center;"><div style="display:flex; flex-direction:column; align-items:center; gap:8px;"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" style="color:var(--ink-200);"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg><span style="font-size:13px; color:var(--ink-400);">No customers found.</span></div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $customers->links() }}</div>

@endsection
