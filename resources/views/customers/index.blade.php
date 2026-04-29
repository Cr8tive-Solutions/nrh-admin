@extends('layouts.admin')

@section('title', 'Customers')
@section('page-title', 'Customers')

@section('header-actions')
    @allowed('customer.manage')
    <a href="{{ route('customers.create') }}"
       class="nrh-btn nrh-btn-primary">
        + New Customer
    </a>
    @endallowed
@endsection

@section('content')

{{-- Filter bar --}}
<div class="bg-white rounded-lg border border-gray-200 px-4 py-3 mb-4 flex items-center gap-3">
    <form method="GET" action="{{ route('customers.index') }}" class="flex items-center gap-3 flex-1">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search name, registration no, email…"
               class="border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600 w-72">

        <select name="invitation" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600">
            <option value="">All invitation states</option>
            <option value="pending"  {{ request('invitation') === 'pending'  ? 'selected' : '' }}>Pending</option>
            <option value="expired"  {{ request('invitation') === 'expired'  ? 'selected' : '' }}>Expired</option>
            <option value="accepted" {{ request('invitation') === 'accepted' ? 'selected' : '' }}>Accepted</option>
        </select>

        <button type="submit" class="nrh-btn nrh-btn-primary">Filter</button>
        @if(request('search') || request('invitation'))
        <a href="{{ route('customers.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
        @endif
    </form>
    <div class="text-sm text-gray-500">{{ $customers->total() }} customers</div>
</div>

<div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <table class="nrh-table">
        <thead>
            <tr>
                <th>Company</th>
                <th>Reg No.</th>
                <th>Industry</th>
                <th>Primary Contact</th>
                <th>Portal access</th>
                <th>Requests</th>
                <th>Invoices</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($customers as $customer)
            @php
                $primary = $customer->primaryUser;
                $inv     = $primary?->latestInvitation;
            @endphp
            <tr>
                <td class="">
                    <div class="font-medium text-gray-900">{{ $customer->name }}</div>
                    @if($customer->country)
                    <div class="text-xs text-gray-400">{{ $customer->country }}</div>
                    @endif
                </td>
                <td class="text-gray-500 font-mono text-xs">{{ $customer->registration_no ?? '—' }}</td>
                <td class="text-gray-500">{{ $customer->industry ?? '—' }}</td>
                <td class="">
                    <div class="text-gray-900">{{ $customer->contact_name ?? '—' }}</div>
                    @if($customer->contact_email)
                    <div class="text-xs text-gray-400">{{ $customer->contact_email }}</div>
                    @endif
                </td>
                <td>
                    @if(! $primary)
                        <span class="badge badge-gray">no user</span>
                        @if($customer->contact_email)
                            @allowed('customer.manage')
                            <form method="POST" action="{{ route('customers.provision-primary-user', $customer) }}" class="inline" style="margin-top:3px; display:block;"
                                  onsubmit="return confirm('Create a primary login account for {{ $customer->contact_name }} ({{ $customer->contact_email }}) and send a portal invitation?');">
                                @csrf
                                <button type="submit" style="font-size:11px; color:var(--emerald-700); font-weight:600; background:none; border:none; cursor:pointer; padding:0;">Provision &amp; invite →</button>
                            </form>
                            @endallowed
                        @else
                            <div class="text-xs text-gray-400" style="margin-top:3px;">add contact email to invite</div>
                        @endif
                    @elseif(! $inv)
                        <span class="badge badge-gray">no invitation</span>
                        @allowed('customer.manage')
                        <form method="POST" action="{{ route('customers.users.resend-invitation', [$customer, $primary]) }}" class="inline ml-1"
                              onsubmit="return confirm('Send a portal invitation to {{ $primary->email }}?');">
                            @csrf
                            <button type="submit" style="font-size:11px; color:var(--emerald-700); font-weight:600; background:none; border:none; cursor:pointer; padding:0; text-decoration:underline;">Send</button>
                        </form>
                        @endallowed
                    @elseif($inv->isAccepted())
                        <span class="badge badge-green" title="Accepted {{ $inv->accepted_at->format('d M Y, H:i') }}">accepted</span>
                        <div class="text-xs text-gray-400" style="margin-top:3px;">{{ $inv->accepted_at->diffForHumans() }}</div>
                    @elseif($inv->isExpired())
                        <span class="badge badge-red" title="Expired {{ $inv->expires_at->format('d M Y, H:i') }}">expired</span>
                        <div class="text-xs text-gray-400" style="margin-top:3px;">last sent {{ $inv->last_sent_at?->diffForHumans() ?? $inv->created_at->diffForHumans() }}</div>
                        @allowed('customer.manage')
                        <form method="POST" action="{{ route('customers.users.resend-invitation', [$customer, $primary]) }}" class="inline" style="margin-top:3px; display:block;"
                              onsubmit="return confirm('Send a fresh invitation to {{ $primary->email }}? The expired one will be replaced.');">
                            @csrf
                            <button type="submit" style="font-size:11px; color:var(--emerald-700); font-weight:600; background:none; border:none; cursor:pointer; padding:0;">Resend invitation →</button>
                        </form>
                        @endallowed
                    @else
                        {{-- pending --}}
                        <span class="badge badge-yellow" title="Expires {{ $inv->expires_at->format('d M Y, H:i') }}">pending</span>
                        <div class="text-xs text-gray-400" style="margin-top:3px;">sent {{ $inv->last_sent_at?->diffForHumans() ?? $inv->created_at->diffForHumans() }} · expires {{ $inv->expires_at->diffForHumans() }}</div>
                        @allowed('customer.manage')
                        <form method="POST" action="{{ route('customers.users.resend-invitation', [$customer, $primary]) }}" class="inline" style="margin-top:3px; display:block;"
                              onsubmit="return confirm('Send a fresh invitation to {{ $primary->email }}? The previous link will be revoked.');">
                            @csrf
                            <button type="submit" style="font-size:11px; color:var(--emerald-700); font-weight:600; background:none; border:none; cursor:pointer; padding:0;">Resend →</button>
                        </form>
                        @endallowed
                    @endif
                </td>
                <td class="text-gray-500">{{ $customer->screening_requests_count }}</td>
                <td class="text-gray-500">{{ $customer->invoices_count }}</td>
                <td class="text-right">
                    <a href="{{ route('customers.show', $customer) }}" class="text-emerald-700 hover:text-emerald-900 text-xs font-medium">View →</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" style="padding:48px 20px; text-align:center;"><div style="display:flex; flex-direction:column; align-items:center; gap:8px;"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" style="color:var(--ink-200);"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg><span style="font-size:13px; color:var(--ink-400);">No customers found.</span></div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $customers->links() }}</div>

@endsection
