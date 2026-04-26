@extends('layouts.admin')

@section('title', $customer->name)
@section('page-title', $customer->name)
@section('page-subtitle', $customer->registration_no ?? '')

@section('header-actions')
    @allowed('customer.manage')
    <a href="{{ route('customers.edit', $customer) }}"
       class="nrh-btn nrh-btn-ghost">
        Edit
    </a>
    @endallowed
    @allowed('pricing.manage')
    <a href="{{ route('pricing.index', ['customer_id' => $customer->id]) }}"
       class="nrh-btn nrh-btn-primary">
        Manage Pricing
    </a>
    @endallowed
@endsection

@section('content')

<div x-data="{ tab: 'info' }">

{{-- Tabs --}}
<div class="flex gap-1 border-b border-gray-200 mb-5">
    @foreach(['info' => 'Company Info', 'agreement' => 'Agreement', 'team' => 'Team', 'requests' => 'Requests', 'invoices' => 'Invoices', 'transactions' => 'Transactions'] as $key => $label)
    <button @click="tab = '{{ $key }}'"
            :class="tab === '{{ $key }}' ? 'border-emerald-700 text-emerald-700' : 'border-transparent text-gray-500 hover:text-gray-700'"
            class="text-sm font-medium border-b-2 -mb-px transition-colors">
        {{ $label }}
    </button>
    @endforeach
</div>

{{-- Company Info --}}
<div x-show="tab === 'info'" x-cloak>
    <div class="bg-white rounded-lg border border-gray-200 p-6 max-w-2xl">
        <div class="grid grid-cols-2 gap-x-8 gap-y-4 text-sm">
            <div>
                <div class="text-xs text-gray-500 uppercase font-medium">Company Name</div>
                <div class="mt-1 font-medium text-gray-900">{{ $customer->name }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500 uppercase font-medium">Registration No.</div>
                <div class="mt-1 font-mono">{{ $customer->registration_no ?? '—' }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500 uppercase font-medium">Industry</div>
                <div class="mt-1">{{ $customer->industry ?? '—' }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500 uppercase font-medium">Country</div>
                <div class="mt-1">{{ $customer->country ?? '—' }}</div>
            </div>
            <div class="col-span-2">
                <div class="text-xs text-gray-500 uppercase font-medium">Address</div>
                <div class="mt-1 text-gray-700">{{ $customer->address ?? '—' }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500 uppercase font-medium">Contact Name</div>
                <div class="mt-1">{{ $customer->contact_name ?? '—' }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500 uppercase font-medium">Contact Email</div>
                <div class="mt-1">{{ $customer->contact_email ?? '—' }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500 uppercase font-medium">Contact Phone</div>
                <div class="mt-1">{{ $customer->contact_phone ?? '—' }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500 uppercase font-medium">Balance</div>
                <div class="mt-1 font-mono">MYR {{ number_format($customer->balance, 2) }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Agreement --}}
<div x-show="tab === 'agreement'" x-cloak>
    @allowed('customer.manage')
    <div class="mb-4 flex justify-end">
        <a href="{{ route('customers.agreements.create', $customer) }}"
           class="nrh-btn nrh-btn-primary">
            + New Agreement
        </a>
    </div>
    @endallowed
    @forelse($customer->agreements as $agreement)
    <div class="bg-white rounded-lg border border-gray-200 p-5 max-w-2xl mb-3">
        <div class="flex items-start justify-between mb-3">
            <div class="font-medium text-gray-900">{{ $agreement->type }}</div>
            <div class="flex items-center gap-2">
                @if($agreement->isExpiringSoonCritical())
                    <span class="badge badge-red">Expiring in {{ $agreement->days_left }}d</span>
                @elseif($agreement->isExpiringSoon())
                    <span class="badge badge-yellow">Expiring in {{ $agreement->days_left }}d</span>
                @else
                    <span class="badge badge-green">{{ $agreement->days_left }}d remaining</span>
                @endif
                @allowed('customer.manage')
                <a href="{{ route('customers.agreements.edit', [$customer, $agreement]) }}" class="text-xs text-emerald-700 hover:text-emerald-900">Edit</a>
                @endallowed
            </div>
        </div>
        <div class="grid grid-cols-3 gap-4 text-sm">
            <div>
                <div class="text-xs text-gray-500 uppercase font-medium">Start Date</div>
                <div class="mt-1">{{ $agreement->start_date->format('d M Y') }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500 uppercase font-medium">Expiry Date</div>
                <div class="mt-1">{{ $agreement->expiry_date->format('d M Y') }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500 uppercase font-medium">SLA TAT</div>
                <div class="mt-1">{{ $agreement->sla_tat ?? '—' }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500 uppercase font-medium">Billing</div>
                <div class="mt-1">{{ $agreement->billing ?? '—' }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500 uppercase font-medium">Payment</div>
                <div class="mt-1">{{ $agreement->payment ?? '—' }}</div>
            </div>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-lg border border-gray-200 p-8 text-center text-gray-400 max-w-2xl">No agreements.</div>
    @endforelse
</div>

{{-- Team --}}
<div x-show="tab === 'team'" x-cloak>
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden max-w-2xl">
        <table class="nrh-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customer->customerUsers as $user)
                <tr>
                    <td class="font-medium text-gray-900">{{ $user->name }}</td>
                    <td class="text-gray-500">{{ $user->email }}</td>
                    <td class="text-gray-500">{{ $user->role }}</td>
                    <td class="">
                        <span class="badge {{ $user->status === 'active' ? 'badge-green' : 'badge-gray' }}">{{ $user->status }}</span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">No team members.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Requests --}}
<div x-show="tab === 'requests'" x-cloak>
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <table class="nrh-table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Type</th>
                    <th>Candidates</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentRequests as $req)
                <tr>
                    <td class="font-mono text-xs">{{ $req->reference }}</td>
                    <td class="text-gray-500">{{ $req->type ?? '—' }}</td>
                    <td class="text-gray-500">{{ $req->candidates->count() }}</td>
                    <td class=""><span class="badge {{ $req->statusBadgeClass() }}">{{ str_replace('_', ' ', $req->status) }}</span></td>
                    <td class="text-gray-500 text-xs">{{ $req->created_at->format('d M Y') }}</td>
                    <td class="text-right"><a href="{{ route('requests.show', $req) }}" class="text-emerald-700 hover:text-emerald-900 text-xs">View →</a></td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No requests.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Invoices --}}
<div x-show="tab === 'invoices'" x-cloak>
    @allowed('invoice.manage')
    <div class="mb-3 flex justify-end">
        <a href="{{ route('invoices.create', ['customer_id' => $customer->id]) }}"
           class="nrh-btn nrh-btn-primary">
            + New Invoice
        </a>
    </div>
    @endallowed
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <table class="nrh-table">
            <thead>
                <tr>
                    <th>Number</th>
                    <th>Period</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Due</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($customer->invoices as $invoice)
                <tr>
                    <td class="font-mono text-xs">{{ $invoice->number }}</td>
                    <td class="text-gray-500">{{ $invoice->period }}</td>
                    <td class="font-medium">MYR {{ number_format($invoice->total, 2) }}</td>
                    <td class=""><span class="badge {{ $invoice->statusBadgeClass() }}">{{ $invoice->status }}</span></td>
                    <td class="text-gray-500 text-xs">{{ $invoice->due_at->format('d M Y') }}</td>
                    <td class="text-right"><a href="{{ route('invoices.show', $invoice) }}" class="text-emerald-700 hover:text-emerald-900 text-xs">View →</a></td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No invoices.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Transactions --}}
<div x-show="tab === 'transactions'" x-cloak>
    @allowed('transaction.manage')
    <div class="mb-3 flex justify-end">
        <a href="{{ route('transactions.create', ['customer_id' => $customer->id]) }}"
           class="nrh-btn nrh-btn-primary">
            + Record Payment
        </a>
    </div>
    @endallowed
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <table class="nrh-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Reference</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customer->transactions as $tx)
                <tr>
                    <td class="text-gray-500 text-xs">{{ $tx->created_at->format('d M Y') }}</td>
                    <td class="text-gray-700">{{ $tx->type }}</td>
                    <td class="font-medium font-mono">MYR {{ number_format($tx->amount, 2) }}</td>
                    <td class="text-gray-500">{{ $tx->method }}</td>
                    <td class="font-mono text-xs text-gray-500">{{ $tx->reference ?? '—' }}</td>
                    <td class=""><span class="badge badge-gray">{{ $tx->status }}</span></td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No transactions.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

</div>
@endsection
