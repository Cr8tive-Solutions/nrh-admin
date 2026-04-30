@extends('layouts.admin')

@section('title', 'Retention Policies')
@section('page-title', 'Retention Policies')
@section('page-subtitle', 'How long PDPA-relevant data stays before redaction')

@section('content')

<style>
    .ret-section { background: var(--card); border: 1px solid var(--line); border-radius: 12px; overflow: hidden; margin-bottom: 14px; }
    .ret-section-head {
        padding: 12px 18px; border-bottom: 1px solid var(--line);
        background: linear-gradient(180deg, var(--paper-2), var(--card));
        font-size: 13px; font-weight: 600; color: var(--ink-900);
    }
    .ret-policy-row {
        padding: 16px 20px; border-bottom: 1px solid var(--line);
        display: grid; grid-template-columns: 1.4fr auto 100px 80px;
        gap: 18px; align-items: center;
    }
    .ret-policy-row:last-child { border-bottom: none; }
    .ret-input {
        width: 90px; padding: 7px 10px;
        border: 1px solid var(--line); border-radius: 6px;
        font-size: 13px; font-family: 'JetBrains Mono', monospace; text-align: right;
        outline: none;
    }
    .ret-input:focus { border-color: var(--emerald-600); box-shadow: 0 0 0 2px rgba(5,150,105,0.10); }
</style>

<form method="POST" action="{{ route('compliance.retention.update') }}">
    @csrf @method('PUT')
    <div class="ret-section">
        <div class="ret-section-head">Retention durations</div>
        @foreach($policies as $i => $p)
        <div class="ret-policy-row">
            <input type="hidden" name="policies[{{ $i }}][id]" value="{{ $p->id }}">
            <div>
                <div style="font-size: 13px; font-weight: 600; color: var(--ink-900); text-transform: capitalize;">{{ str_replace('_', ' ', $p->entity_type) }}</div>
                <div style="font-size: 11px; color: var(--ink-500); margin-top: 4px; line-height: 1.5;">{{ $p->description }}</div>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <input type="number" name="policies[{{ $i }}][retention_days]"
                       value="{{ $p->retention_days }}" min="30" max="36500"
                       class="ret-input">
                <span style="font-size: 11px; color: var(--ink-500); font-family: 'JetBrains Mono', monospace; text-transform: uppercase;">days</span>
            </div>
            <div style="font-size: 11px; color: var(--ink-500); font-family: 'JetBrains Mono', monospace; text-align: right;">
                ≈ {{ round($p->retention_days / 365, 1) }} years
            </div>
            <div style="text-align: center;">
                <label style="cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600;">
                    <input type="checkbox" name="policies[{{ $i }}][enabled]" value="1" {{ $p->enabled ? 'checked' : '' }} class="accent-emerald-700" style="transform: scale(1.1);">
                    Enabled
                </label>
            </div>
        </div>
        @endforeach
        <div style="padding: 14px 20px; background: var(--paper-2); display: flex; justify-content: flex-end; gap: 10px;">
            <button type="submit" class="nrh-btn nrh-btn-primary" style="font-size: 12px; padding: 8px 18px;">Save policies</button>
        </div>
    </div>
</form>

<div class="ret-section">
    <div class="ret-section-head" style="display: flex; align-items: center; gap: 10px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--gold-700, #b8860b);"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0zM12 9v4M12 17h.01"/></svg>
        <span>Manual purge</span>
    </div>
    <div style="padding: 18px 22px;">
        @if($threshold)
        <p style="font-size: 13px; color: var(--ink-700); line-height: 1.6;">
            With the current candidate retention of <strong>{{ $policies->firstWhere('entity_type', 'candidate')->retention_days }} days</strong>,
            the cutoff is <strong style="font-family: 'JetBrains Mono', monospace;">{{ $threshold->format('d M Y') }}</strong>.
            <br>
            <strong>{{ number_format($eligibleForPurge) }}</strong> candidate {{ Str::plural('record', $eligibleForPurge) }}
            currently {{ $eligibleForPurge === 1 ? 'matches' : 'match' }} the purge criteria.
        </p>
        <p style="font-size: 12px; color: var(--ink-500); margin: 10px 0 0; line-height: 1.5;">
            The scheduled job runs daily at <strong>02:30 Asia/Kuala_Lumpur</strong>. You can also trigger it manually:
        </p>
        @if($eligibleForPurge > 0)
        <form method="POST" action="{{ route('compliance.retention.purge-now') }}"
              onsubmit="return confirm('Redact PII on {{ $eligibleForPurge }} candidate(s) past retention now? This is irreversible. Issued PDF reports remain unchanged.');"
              style="margin-top: 14px;">
            @csrf
            <button type="submit" class="nrh-btn" style="background: var(--danger); color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 12px; cursor: pointer;">
                Run purge now ({{ $eligibleForPurge }} {{ Str::plural('record', $eligibleForPurge) }})
            </button>
        </form>
        @endif
        @else
        <p style="font-size: 13px; color: var(--ink-500); font-style: italic;">Candidate retention is disabled. Re-enable it above to schedule purges.</p>
        @endif
    </div>
</div>

<div class="ret-section">
    <div class="ret-section-head">What gets redacted</div>
    <div style="padding: 14px 20px; font-size: 12px; color: var(--ink-700); line-height: 1.7;">
        When a candidate passes the retention threshold (or an erasure DSAR is executed), the following fields are replaced with redaction markers:
        <ul style="margin: 6px 0 0; padding-left: 22px;">
            <li>Candidate name → <code style="background: var(--paper-2); padding: 1px 4px; border-radius: 3px;">{{ \App\Services\RedactionService::MARKER_NAME }}</code></li>
            <li>Identity number → first &amp; last char preserved, middle masked (e.g. <code style="background: var(--paper-2); padding: 1px 4px; border-radius: 3px; font-family: 'JetBrains Mono', monospace;">8***-**-***8</code>)</li>
            <li>Mobile, remarks → cleared</li>
            <li>All scope finding comments &amp; record details → replaced with redaction marker referencing the reason</li>
        </ul>
        <p style="margin-top: 10px;">
            <strong>What is preserved:</strong> the candidate row itself, scope statuses, timestamps, audit trail, and all <strong>previously-issued PDF reports</strong> (immutable on disk — they were already delivered to the client).
        </p>
    </div>
</div>

@endsection
