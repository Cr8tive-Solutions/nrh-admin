@extends('layouts.admin')

@section('title', 'Registrations')
@section('page-title', 'Registrations')
@section('page-subtitle', 'Invite new customers to register, then verify and approve them')

@section('header-actions')
    <a href="{{ route('customers.index') }}" class="nrh-btn nrh-btn-ghost">Customers</a>
@endsection

@section('content')

@include('registrations._link-banner')

{{-- ── Invite form ── --}}
<div x-data="{ open: {{ $errors->any() ? 'true' : 'false' }} }" class="nrh-card" style="margin-bottom: 16px;">
    <div class="nrh-card-head">
        <div>
            <h3>Invite a new customer</h3>
            <div class="text-xs text-gray-500" style="margin-top: 2px;">
                They get an email link to enter their company and contact details and choose a password. Nothing is created until you approve.
            </div>
        </div>
        <button type="button" class="nrh-btn nrh-btn-primary" @click="open = !open" x-show="!open">+ Invite to register</button>
    </div>

    <form x-show="open" x-cloak method="POST" action="{{ route('registrations.store') }}" style="padding: 18px 20px;">
        @csrf
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px 16px;">
            <label class="block">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Email <span style="color: var(--danger);">*</span></span>
                <input type="email" name="email" value="{{ old('email') }}" required maxlength="255" class="nrh-input" style="margin-top: 5px;" placeholder="hr@company.com">
                @error('email')<span class="text-xs" style="color: var(--danger);">{{ $message }}</span>@enderror
            </label>
            <label class="block">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Contact name</span>
                <input type="text" name="contact_name" value="{{ old('contact_name') }}" maxlength="255" class="nrh-input" style="margin-top: 5px;" placeholder="Optional — prefills the form">
                @error('contact_name')<span class="text-xs" style="color: var(--danger);">{{ $message }}</span>@enderror
            </label>
            <label class="block">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Company name</span>
                <input type="text" name="company_name" value="{{ old('company_name') }}" maxlength="255" class="nrh-input" style="margin-top: 5px;" placeholder="Optional — prefills the form">
                @error('company_name')<span class="text-xs" style="color: var(--danger);">{{ $message }}</span>@enderror
            </label>
        </div>
        <label class="block" style="margin-top: 14px;">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Internal note</span>
            <textarea name="invite_note" rows="2" maxlength="2000" class="nrh-input" style="margin-top: 5px;" placeholder="Staff only — e.g. lead source, agreed terms">{{ old('invite_note') }}</textarea>
        </label>
        <div style="margin-top: 14px; display: flex; gap: 8px;">
            <button type="submit" class="nrh-btn nrh-btn-primary">Send invitation</button>
            <button type="button" class="nrh-btn nrh-btn-ghost" @click="open = false">Cancel</button>
        </div>
    </form>
</div>

{{-- ── Status tabs + search ── --}}
<div class="bg-white rounded-lg border border-gray-200 px-4 py-3 mb-4 flex items-center gap-3 flex-wrap">
    <div class="flex items-center gap-1 flex-wrap">
        @foreach([
            'submitted' => 'Awaiting review',
            'invited'   => 'Invited',
            'expired'   => 'Expired',
            'approved'  => 'Approved',
            'rejected'  => 'Rejected',
            'cancelled' => 'Cancelled',
            'all'       => 'All',
        ] as $key => $label)
        <a href="{{ route('registrations.index', array_filter(['status' => $key, 'search' => request('search')])) }}"
           class="nrh-btn {{ $status === $key ? 'nrh-btn-primary' : 'nrh-btn-ghost' }}" style="padding: 5px 11px; font-size: 12px;">
            {{ $label }} <span style="opacity: 0.7; font-weight: 500;">{{ $counts[$key] ?? 0 }}</span>
        </a>
        @endforeach
    </div>
    <form method="GET" action="{{ route('registrations.index') }}" class="flex items-center gap-2 ml-auto">
        <input type="hidden" name="status" value="{{ $status }}">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search email, company, reg no…"
               class="border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600 w-64">
        <button type="submit" class="nrh-btn nrh-btn-ghost" style="padding: 6px 12px;">Search</button>
    </form>
</div>

<div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <table class="nrh-table">
        <thead>
            <tr>
                <th>Company</th>
                <th>Contact</th>
                <th>Status</th>
                <th>Invited</th>
                <th>Submitted</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($registrations as $registration)
            <tr onclick="window.location='{{ route('registrations.show', $registration) }}'">
                <td>
                    <div class="font-medium text-gray-900">{{ $registration->company_name ?: '—' }}</div>
                    @if($registration->registration_no)
                    <div class="text-xs text-gray-400 font-mono">{{ $registration->registration_no }}</div>
                    @endif
                </td>
                <td>
                    <div class="text-gray-900">{{ $registration->contact_name ?: '—' }}</div>
                    <div class="text-xs text-gray-400">{{ $registration->email }}</div>
                </td>
                <td>@include('registrations._status')</td>
                <td>
                    <div class="text-gray-700">{{ $registration->created_at->format('d M Y') }}</div>
                    <div class="text-xs text-gray-400">by {{ $registration->invitedBy?->name ?? '—' }}</div>
                </td>
                <td class="text-gray-700">{{ $registration->submitted_at?->format('d M Y, H:i') ?? '—' }}</td>
                <td class="text-right">
                    <a href="{{ route('registrations.show', $registration) }}" class="text-sm font-semibold" style="color: var(--emerald-700);">
                        {{ $registration->isAwaitingReview() ? 'Review →' : 'View →' }}
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center text-gray-400" style="padding: 40px;">No registrations here.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $registrations->links() }}</div>

@endsection
