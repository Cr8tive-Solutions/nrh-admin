@extends('layouts.admin')

@php $r = $registration; @endphp

@section('title', 'Registration — '.($r->company_name ?: $r->email))
@section('page-title', $r->company_name ?: $r->email)
@section('page-subtitle', 'Customer registration')

@section('header-actions')
    <a href="{{ route('registrations.index') }}" class="nrh-btn nrh-btn-ghost">← Back to registrations</a>
@endsection

@section('content')

@include('registrations._link-banner')

<div style="display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 20px; align-items: start;" class="reg-shell">
<style>@media (max-width: 1100px) { .reg-shell { grid-template-columns: 1fr !important; } }</style>

{{-- ── Main column ── --}}
<div style="display: flex; flex-direction: column; gap: 16px;">

    @if($r->isAwaitingReview())
        @if($emailTaken)
        <div class="rounded-lg px-4 py-3 text-sm" style="background: #fbeeec; color: var(--danger); border: 1px solid #f3c9c4;">
            A client portal account already exists with <strong>{{ $r->email }}</strong>, so this registration can't be approved. Cancel it, or add the person to their existing customer instead.
        </div>
        @endif
        @if($similar->isNotEmpty())
        <div class="rounded-lg px-4 py-3 text-sm" style="background: #fef3c7; color: #92400e; border: 1px solid #fde68a;">
            <strong>Possible existing customer.</strong> Check this isn't a duplicate before approving:
            @foreach($similar as $c)
                <a href="{{ route('customers.show', $c) }}" class="underline font-semibold" target="_blank">{{ $c->name }}</a>{{ $c->registration_no ? ' ('.$c->registration_no.')' : '' }}@if(! $loop->last), @endif
            @endforeach
        </div>
        @endif

        {{-- Editable review form: staff may correct details before approving. --}}
        <form method="POST" action="{{ route('registrations.approve', $r) }}" class="nrh-card"
              onsubmit="return confirm('Approve this registration? This creates the customer and activates {{ $r->email }} as its Owner, and emails them that they can sign in.');">
            @csrf
            <div class="nrh-card-head">
                <div>
                    <h3>Verify details</h3>
                    <div class="text-xs text-gray-500" style="margin-top: 2px;">As submitted by the registrant. Check against SSM / company records and correct anything before approving.</div>
                </div>
            </div>
            <div style="padding: 18px 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 14px 16px;">
                @php
                    $field = function ($name, $label, $required = true, $type = 'text') use ($r) {
                        return compact('name', 'label', 'required', 'type') + ['value' => old($name, $r->{$name})];
                    };
                @endphp
                @foreach([
                    $field('company_name', 'Company name'),
                    $field('registration_no', 'Registration no. (SSM)'),
                    $field('industry', 'Industry', false),
                    $field('contact_name', 'Contact name'),
                    $field('contact_designation', 'Designation', false),
                    $field('contact_phone', 'Contact phone'),
                ] as $f)
                <label class="block">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $f['label'] }} @if($f['required'])<span style="color: var(--danger);">*</span>@endif</span>
                    <input type="{{ $f['type'] }}" name="{{ $f['name'] }}" value="{{ $f['value'] }}" @if($f['required']) required @endif class="nrh-input" style="margin-top: 5px;">
                    @error($f['name'])<span class="text-xs" style="color: var(--danger);">{{ $message }}</span>@enderror
                </label>
                @endforeach

                <label class="block">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Country <span style="color: var(--danger);">*</span></span>
                    @php $country = old('country', $r->country); @endphp
                    <select name="country" required class="nrh-input" style="margin-top: 5px;">
                        <option value="">Select…</option>
                        @foreach($countries as $c)
                        <option value="{{ $c->name }}" @selected($country === $c->name)>{{ $c->name }}</option>
                        @endforeach
                        @if($country && ! $countries->contains('name', $country))
                        <option value="{{ $country }}" selected>{{ $country }}</option>
                        @endif
                    </select>
                    @error('country')<span class="text-xs" style="color: var(--danger);">{{ $message }}</span>@enderror
                </label>

                <label class="block">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Login email</span>
                    <input type="email" value="{{ $r->email }}" readonly class="nrh-input" style="margin-top: 5px; background: var(--paper-2); color: var(--ink-500);">
                    <span class="text-xs text-gray-400">Verified by the invitation link — not editable.</span>
                </label>

                <label class="block" style="grid-column: 1 / -1;">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Registered address <span style="color: var(--danger);">*</span></span>
                    <textarea name="address" rows="3" required class="nrh-input" style="margin-top: 5px;">{{ old('address', $r->address) }}</textarea>
                    @error('address')<span class="text-xs" style="color: var(--danger);">{{ $message }}</span>@enderror
                </label>
            </div>
            <div style="padding: 14px 20px; border-top: 1px solid var(--line); display: flex; gap: 8px; align-items: center;">
                <button type="submit" class="nrh-btn nrh-btn-primary" @disabled($emailTaken)>
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6 9 17l-5-5"/></svg>
                    Approve &amp; create customer
                </button>
                <span class="text-xs text-gray-500">Creates the customer and an active Owner login with the password they chose.</span>
            </div>
        </form>

    @else
        {{-- Read-only details --}}
        <div class="nrh-card">
            <div class="nrh-card-head"><h3>Details</h3></div>
            <dl style="padding: 18px 20px; display: grid; grid-template-columns: 180px 1fr; gap: 10px 16px; font-size: 13px;">
                @foreach([
                    'Company name' => $r->company_name,
                    'Registration no.' => $r->registration_no,
                    'Industry' => $r->industry,
                    'Country' => $r->country,
                    'Address' => $r->address,
                    'Contact name' => $r->contact_name,
                    'Designation' => $r->contact_designation,
                    'Contact phone' => $r->contact_phone,
                    'Email' => $r->email,
                ] as $label => $value)
                <dt class="text-gray-500">{{ $label }}</dt>
                <dd class="text-gray-900" style="white-space: pre-line; margin: 0;">{{ $value ?: '—' }}</dd>
                @endforeach
            </dl>
            @if($r->isOpenInvite())
            <div style="padding: 0 20px 18px;" class="text-xs text-gray-500">
                Waiting for the invitee to fill in the registration form. Fields above show anything you prefilled.
            </div>
            @endif
        </div>
    @endif
</div>

{{-- ── Side column ── --}}
<div style="display: flex; flex-direction: column; gap: 16px;">
    <div class="nrh-card">
        <div class="nrh-card-head"><h3>Status</h3> @include('registrations._status')</div>
        <dl style="padding: 16px 20px; display: grid; grid-template-columns: auto 1fr; gap: 8px 12px; font-size: 12px;">
            <dt class="text-gray-500">Invited</dt>
            <dd style="margin: 0;">{{ $r->created_at->format('d M Y, H:i') }}<br><span class="text-gray-400">by {{ $r->invitedBy?->name ?? '—' }}</span></dd>
            @if($r->isOpenInvite())
            <dt class="text-gray-500">Link {{ $r->isExpired() ? 'expired' : 'expires' }}</dt>
            <dd style="margin: 0;">{{ $r->expires_at->format('d M Y, H:i') }}</dd>
            <dt class="text-gray-500">Emails sent</dt>
            <dd style="margin: 0;">{{ $r->sent_count }}@if($r->last_sent_at) <span class="text-gray-400">· last {{ $r->last_sent_at->diffForHumans() }}</span>@endif</dd>
            @endif
            @if($r->submitted_at)
            <dt class="text-gray-500">Submitted</dt>
            <dd style="margin: 0;">{{ $r->submitted_at->format('d M Y, H:i') }}</dd>
            @endif
            @if($r->reviewed_at)
            <dt class="text-gray-500">{{ ucfirst($r->status) }}</dt>
            <dd style="margin: 0;">{{ $r->reviewed_at->format('d M Y, H:i') }}<br><span class="text-gray-400">by {{ $r->reviewedBy?->name ?? '—' }}</span></dd>
            @endif
            @if($r->customer)
            <dt class="text-gray-500">Customer</dt>
            <dd style="margin: 0;"><a href="{{ route('customers.show', $r->customer) }}" class="font-semibold" style="color: var(--emerald-700);">{{ $r->customer->name }} →</a></dd>
            @endif
        </dl>
        @if($r->review_note)
        <div style="padding: 0 20px 16px; font-size: 12px;">
            <div class="text-gray-500" style="margin-bottom: 4px;">Rejection reason (sent to registrant)</div>
            <div style="white-space: pre-line;">{{ $r->review_note }}</div>
        </div>
        @endif
        @if($r->invite_note)
        <div style="padding: 0 20px 16px; font-size: 12px;">
            <div class="text-gray-500" style="margin-bottom: 4px;">Internal note</div>
            <div style="white-space: pre-line;">{{ $r->invite_note }}</div>
        </div>
        @endif
    </div>

    @if($r->isOpenInvite())
    <div class="nrh-card" style="padding: 16px 20px; display: flex; flex-direction: column; gap: 8px;">
        <form method="POST" action="{{ route('registrations.resend', $r) }}"
              onsubmit="return confirm('Send a fresh link to {{ $r->email }}? The current link will stop working.');">
            @csrf
            <button type="submit" class="nrh-btn nrh-btn-primary" style="width: 100%; justify-content: center;">Resend invitation</button>
        </form>
        <form method="POST" action="{{ route('registrations.cancel', $r) }}"
              onsubmit="return confirm('Cancel this invitation? The link will stop working.');">
            @csrf
            <button type="submit" class="nrh-btn nrh-btn-ghost" style="width: 100%; justify-content: center; color: var(--danger);">Cancel invitation</button>
        </form>
    </div>
    @endif

    @if($r->isAwaitingReview())
    <div class="nrh-card" x-data="{ open: {{ $errors->has('review_note') ? 'true' : 'false' }} }">
        <div class="nrh-card-head"><h3>Reject</h3></div>
        <div style="padding: 16px 20px;">
            <button type="button" class="nrh-btn nrh-btn-ghost" style="width: 100%; justify-content: center; color: var(--danger);" x-show="!open" @click="open = true">Reject registration…</button>
            <form x-show="open" x-cloak method="POST" action="{{ route('registrations.reject', $r) }}"
                  onsubmit="return confirm('Reject this registration and email the reason to {{ $r->email }}?');">
                @csrf
                <label class="block">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Reason (emailed to registrant)</span>
                    <textarea name="review_note" rows="4" required maxlength="2000" class="nrh-input" style="margin-top: 5px;">{{ old('review_note') }}</textarea>
                    @error('review_note')<span class="text-xs" style="color: var(--danger);">{{ $message }}</span>@enderror
                </label>
                <button type="submit" class="nrh-btn" style="margin-top: 10px; width: 100%; justify-content: center; background: var(--danger); color: #fff;">Reject &amp; notify</button>
            </form>
        </div>
        <div style="padding: 0 20px 16px;">
            <form method="POST" action="{{ route('registrations.cancel', $r) }}"
                  onsubmit="return confirm('Cancel without notifying the registrant?');">
                @csrf
                <button type="submit" class="text-xs text-gray-500 underline" style="background: none; border: none; cursor: pointer; padding: 0;">Cancel silently instead</button>
            </form>
        </div>
    </div>
    @endif
</div>

</div>

@endsection
