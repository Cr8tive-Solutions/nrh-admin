@extends('layouts.admin')

@section('title', 'New Data Subject Request')
@section('page-title', 'Log New Data Subject Request')

@section('header-actions')
    <a href="{{ route('compliance.dsar.index') }}" class="nrh-btn nrh-btn-ghost">← Back</a>
@endsection

@section('content')

<form method="POST" action="{{ route('compliance.dsar.store') }}" enctype="multipart/form-data"
      style="max-width: 720px;">
    @csrf

    <div style="background: var(--card); border: 1px solid var(--line); border-radius: 12px; padding: 22px 26px; margin-bottom: 14px;">
        <div style="font-size: 13px; font-weight: 600; color: var(--ink-900); margin-bottom: 4px;">Subject</div>
        <div style="font-size: 12px; color: var(--ink-500); margin-bottom: 16px;">Who is asking for the data, and how do you know?</div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <div>
                <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.14em; color: var(--ink-500); font-weight: 600; margin-bottom: 4px;">Subject name <span style="color: var(--danger);">*</span></div>
                <input type="text" name="subject_name" required value="{{ old('subject_name') }}"
                       style="width: 100%; padding: 9px 12px; border: 1px solid var(--line); border-radius: 6px; font-size: 13px; outline: none;">
            </div>
            <div>
                <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.14em; color: var(--ink-500); font-weight: 600; margin-bottom: 4px;">Email</div>
                <input type="email" name="subject_email" value="{{ old('subject_email') }}"
                       style="width: 100%; padding: 9px 12px; border: 1px solid var(--line); border-radius: 6px; font-size: 13px; outline: none;">
            </div>
            <div>
                <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.14em; color: var(--ink-500); font-weight: 600; margin-bottom: 4px;">Identity number (NRIC / Passport)</div>
                <input type="text" name="subject_identity_number" value="{{ old('subject_identity_number') }}"
                       style="width: 100%; padding: 9px 12px; border: 1px solid var(--line); border-radius: 6px; font-size: 13px; font-family: 'JetBrains Mono', monospace; outline: none;">
            </div>
            <div>
                <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.14em; color: var(--ink-500); font-weight: 600; margin-bottom: 4px;">Relation to subject <span style="color: var(--danger);">*</span></div>
                <select name="relation" required style="width: 100%; padding: 9px 12px; border: 1px solid var(--line); border-radius: 6px; font-size: 13px; outline: none;">
                    @foreach($relations as $key => $label)
                    <option value="{{ $key }}" {{ old('relation') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div style="background: var(--card); border: 1px solid var(--line); border-radius: 12px; padding: 22px 26px; margin-bottom: 14px;">
        <div style="font-size: 13px; font-weight: 600; color: var(--ink-900); margin-bottom: 4px;">Request details</div>
        <div style="font-size: 12px; color: var(--ink-500); margin-bottom: 16px;">What are they asking for, and how did the request arrive?</div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <div>
                <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.14em; color: var(--ink-500); font-weight: 600; margin-bottom: 4px;">Request type <span style="color: var(--danger);">*</span></div>
                <select name="type" required style="width: 100%; padding: 9px 12px; border: 1px solid var(--line); border-radius: 6px; font-size: 13px; outline: none;">
                    @foreach($types as $key => $label)
                    <option value="{{ $key }}" {{ old('type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.14em; color: var(--ink-500); font-weight: 600; margin-bottom: 4px;">Received via <span style="color: var(--danger);">*</span></div>
                <select name="received_via" required style="width: 100%; padding: 9px 12px; border: 1px solid var(--line); border-radius: 6px; font-size: 13px; outline: none;">
                    @foreach(['email','post','phone','in_person'] as $v)
                    <option value="{{ $v }}" {{ old('received_via') === $v ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $v)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.14em; color: var(--ink-500); font-weight: 600; margin-bottom: 4px;">Received at <span style="color: var(--danger);">*</span></div>
                <input type="datetime-local" name="received_at" required value="{{ old('received_at', now()->format('Y-m-d\TH:i')) }}"
                       style="width: 100%; padding: 9px 12px; border: 1px solid var(--line); border-radius: 6px; font-size: 13px; font-family: 'JetBrains Mono', monospace; outline: none;">
            </div>
            <div>
                <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.14em; color: var(--ink-500); font-weight: 600; margin-bottom: 4px;">Verification document</div>
                <input type="file" name="evidence_file" accept=".pdf,.jpg,.jpeg,.png" style="font-size: 12px;">
                <p style="font-size: 10px; color: var(--ink-400); margin: 4px 0 0;">Optional. PDF / JPG / PNG, max 5 MB.</p>
            </div>
            <div style="grid-column: 1 / -1;">
                <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.14em; color: var(--ink-500); font-weight: 600; margin-bottom: 4px;">Description / what they asked for <span style="color: var(--danger);">*</span></div>
                <textarea name="description" required rows="4"
                          placeholder="Quote the email or describe the call. e.g. &quot;Asked for full copy of any data NRH holds about him under PDPA s30. Provided IC photo for verification.&quot;"
                          style="width: 100%; padding: 10px 12px; border: 1px solid var(--line); border-radius: 6px; font-size: 13px; font-family: inherit; outline: none; resize: vertical;">{{ old('description') }}</textarea>
            </div>
        </div>
    </div>

    <div style="display: flex; gap: 10px;">
        <button type="submit" class="nrh-btn nrh-btn-primary" style="padding: 11px 22px;">Log Request</button>
        <a href="{{ route('compliance.dsar.index') }}" class="nrh-btn nrh-btn-ghost">Cancel</a>
    </div>

    <p style="font-size: 11px; color: var(--ink-500); margin-top: 12px;">
        PDPA Section 30 requires response within <strong>21 working days</strong>. Due date will be auto-calculated from "received at".
    </p>
</form>

@endsection
