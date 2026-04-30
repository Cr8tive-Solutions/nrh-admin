@extends('layouts.admin')

@section('title', $dsar->reference)
@section('page-title', $dsar->reference)
@section('page-subtitle', $dsar->subject_name . ' · ' . str_replace('_', ' ', ucfirst($dsar->type)))

@section('header-actions')
    <a href="{{ route('compliance.dsar.index') }}" class="nrh-btn nrh-btn-ghost">← Back to DSAR list</a>
@endsection

@section('content')

@php
    $statusColors = [
        'received'           => ['bg' => 'var(--ink-100)', 'text' => 'var(--ink-700)'],
        'verifying_identity' => ['bg' => '#fef3c7', 'text' => '#b45309'],
        'in_progress'        => ['bg' => 'rgba(59,130,246,0.10)', 'text' => '#1d4ed8'],
        'completed'          => ['bg' => 'var(--emerald-50)', 'text' => 'var(--emerald-700)'],
        'rejected'           => ['bg' => '#fbeeec', 'text' => 'var(--danger)'],
    ];
    $sc = $statusColors[$dsar->status];
@endphp

<style>
    .dsar-grid { display: grid; grid-template-columns: 1fr 320px; gap: 18px; align-items: start; }
    @media (max-width: 1100px) { .dsar-grid { grid-template-columns: 1fr; } }
    .dsar-section {
        background: var(--card); border: 1px solid var(--line);
        border-radius: 12px; overflow: hidden; margin-bottom: 14px;
    }
    .dsar-section-head {
        padding: 12px 18px; border-bottom: 1px solid var(--line);
        background: linear-gradient(180deg, var(--paper-2), var(--card));
        font-size: 13px; font-weight: 600; color: var(--ink-900);
    }
    .dsar-info-grid {
        padding: 16px 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 14px 20px;
    }
    .dsar-info-grid .col-2 { grid-column: 1 / -1; }
    .dsar-info-label {
        font-size: 9px; text-transform: uppercase; letter-spacing: 0.16em;
        color: var(--ink-500); font-weight: 600; font-family: 'JetBrains Mono', monospace;
    }
    .dsar-info-value { margin-top: 4px; font-size: 13px; color: var(--ink-900); line-height: 1.5; }
    .dsar-info-value.muted { color: var(--ink-400); font-style: italic; }
    .dsar-info-value.mono  { font-family: 'JetBrains Mono', monospace; font-size: 12px; }

    .dsar-actions {
        padding: 16px 18px; display: flex; flex-direction: column; gap: 10px;
    }
    .dsar-action-form {
        background: var(--paper-2); border: 1px solid var(--line);
        border-radius: 8px; padding: 12px; display: flex; flex-direction: column; gap: 8px;
    }
    .dsar-action-form textarea {
        width: 100%; padding: 8px 10px; border: 1px solid var(--line);
        border-radius: 6px; font-size: 12px; font-family: inherit; outline: none; resize: vertical; min-height: 70px;
    }
</style>

<div class="dsar-grid">
    <div>
        {{-- Subject info --}}
        <div class="dsar-section">
            <div class="dsar-section-head">Subject &amp; request</div>
            <div class="dsar-info-grid">
                <div>
                    <div class="dsar-info-label">Subject name</div>
                    <div class="dsar-info-value">{{ $dsar->subject_name }}</div>
                </div>
                <div>
                    <div class="dsar-info-label">Email</div>
                    <div class="dsar-info-value {{ $dsar->subject_email ? '' : 'muted' }}">{{ $dsar->subject_email ?? '—' }}</div>
                </div>
                <div>
                    <div class="dsar-info-label">Identity number</div>
                    <div class="dsar-info-value mono {{ $dsar->subject_identity_number ? '' : 'muted' }}">{{ $dsar->subject_identity_number ?? 'Not provided' }}</div>
                </div>
                <div>
                    <div class="dsar-info-label">Relation</div>
                    <div class="dsar-info-value">{{ \App\Models\DataSubjectRequest::relations()[$dsar->relation] ?? $dsar->relation }}</div>
                </div>
                <div>
                    <div class="dsar-info-label">Request type</div>
                    <div class="dsar-info-value">{{ \App\Models\DataSubjectRequest::types()[$dsar->type] ?? $dsar->type }}</div>
                </div>
                <div>
                    <div class="dsar-info-label">Received</div>
                    <div class="dsar-info-value">{{ $dsar->received_at->format('d M Y, H:i') }} · via {{ str_replace('_', ' ', $dsar->received_via) }}</div>
                </div>
                <div class="col-2">
                    <div class="dsar-info-label">Description</div>
                    <div class="dsar-info-value" style="white-space: pre-wrap;">{{ $dsar->description }}</div>
                </div>
                @if($dsar->evidence_file_path)
                <div class="col-2">
                    <div class="dsar-info-label">Verification document</div>
                    <div class="dsar-info-value">
                        <a href="{{ route('compliance.dsar.evidence', $dsar) }}" style="color: var(--emerald-700); font-weight: 600; text-decoration: none;">Download evidence file →</a>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Linked candidate / matching --}}
        @if($dsar->candidate)
        <div class="dsar-section">
            <div class="dsar-section-head">Linked candidate</div>
            <div class="dsar-info-grid">
                <div>
                    <div class="dsar-info-label">Candidate</div>
                    <div class="dsar-info-value">
                        @if($dsar->candidate->isRedacted())
                            <em style="color: var(--ink-400);">[REDACTED — already erased]</em>
                        @else
                            {{ $dsar->candidate->name }}
                        @endif
                    </div>
                </div>
                <div>
                    <div class="dsar-info-label">Identity number</div>
                    <div class="dsar-info-value mono">{{ $dsar->candidate->identity_number }}</div>
                </div>
                <div class="col-2">
                    <div class="dsar-info-label">Linked request</div>
                    <div class="dsar-info-value">
                        <a href="{{ route('requests.show', $dsar->candidate->screeningRequest) }}" style="color: var(--emerald-700); font-weight: 600; text-decoration: none;">{{ $dsar->candidate->screeningRequest->reference }}</a>
                        — {{ $dsar->candidate->screeningRequest->customer->name ?? '—' }}
                    </div>
                </div>
            </div>
        </div>
        @elseif($matchedCandidates->count())
        <div class="dsar-section">
            <div class="dsar-section-head">Possible candidate matches</div>
            <div style="padding: 8px 0;">
                @foreach($matchedCandidates as $c)
                <form method="POST" action="{{ route('compliance.dsar.link-candidate', $dsar) }}"
                      style="padding: 10px 18px; border-bottom: 1px solid var(--line); display: grid; grid-template-columns: 1fr auto; gap: 14px; align-items: center; margin: 0;">
                    @csrf @method('PATCH')
                    <div>
                        <div style="font-size: 13px; font-weight: 500; color: var(--ink-900);">{{ $c->name }}</div>
                        <div style="font-size: 11px; color: var(--ink-500); font-family: 'JetBrains Mono', monospace;">{{ $c->identity_number }} · {{ $c->screeningRequest->reference }} · {{ $c->screeningRequest->customer->name ?? '—' }}</div>
                    </div>
                    <input type="hidden" name="request_candidate_id" value="{{ $c->id }}">
                    <button type="submit" class="nrh-btn nrh-btn-ghost" style="font-size: 11px; padding: 5px 12px;">Link to this DSAR</button>
                </form>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Outcome --}}
        @if($dsar->outcome)
        <div class="dsar-section">
            <div class="dsar-section-head">Outcome</div>
            <div style="padding: 16px 20px; font-size: 13px; color: var(--ink-700); white-space: pre-wrap; line-height: 1.5;">{{ $dsar->outcome }}</div>
            @if($dsar->completed_at)
            <div style="padding: 0 20px 14px; font-size: 11px; color: var(--ink-500);">
                Completed {{ $dsar->completed_at->format('d M Y, H:i') }}
                @if($dsar->handler) by {{ $dsar->handler->name }} @endif
            </div>
            @endif
        </div>
        @endif
    </div>

    <aside>
        {{-- Status & timeline card --}}
        <div class="dsar-section">
            <div class="dsar-section-head">Status</div>
            <div class="dsar-actions">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span class="dsar-pill" style="background: {{ $sc['bg'] }}; color: {{ $sc['text'] }}; padding: 4px 12px; border-radius: 99px; font-weight: 600; font-size: 11px;">
                        {{ str_replace('_', ' ', $dsar->status) }}
                    </span>
                </div>

                <div style="font-size: 11px; color: var(--ink-500); border-top: 1px solid var(--line); padding-top: 10px;">
                    <div style="margin-bottom: 4px;"><strong>Received:</strong> {{ $dsar->received_at->format('d M Y, H:i') }}</div>
                    @if($dsar->verified_at)
                    <div style="margin-bottom: 4px;"><strong>ID verified:</strong> {{ $dsar->verified_at->format('d M Y, H:i') }}</div>
                    @endif
                    @if($dsar->due_at)
                    <div style="margin-bottom: 4px;">
                        <strong>Due:</strong> {{ $dsar->due_at->format('d M Y') }}
                        @if($dsar->isOverdue())
                            <span style="color: var(--danger); font-weight: 600;">· {{ abs($dsar->daysToRespond()) }}d overdue</span>
                        @elseif(in_array($dsar->status, ['received','verifying_identity','in_progress']))
                            <span style="color: var(--ink-400);">· {{ $dsar->daysToRespond() }}d remaining</span>
                        @endif
                    </div>
                    @endif
                    @if($dsar->completed_at)
                    <div><strong>Closed:</strong> {{ $dsar->completed_at->format('d M Y, H:i') }}</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Action buttons by current status --}}
        @if(! in_array($dsar->status, ['completed','rejected']))
        <div class="dsar-section">
            <div class="dsar-section-head">Actions</div>
            <div class="dsar-actions">
                @if($dsar->status === 'received')
                <form method="POST" action="{{ route('compliance.dsar.verify', $dsar) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="nrh-btn nrh-btn-primary" style="width: 100%; font-size: 12px; padding: 8px;">Move to identity verification →</button>
                </form>
                @endif

                @if($dsar->status === 'verifying_identity')
                <form method="POST" action="{{ route('compliance.dsar.confirm-identity', $dsar) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="nrh-btn nrh-btn-primary" style="width: 100%; font-size: 12px; padding: 8px;">Confirm identity verified →</button>
                </form>
                @endif

                @if($dsar->status === 'in_progress' && $dsar->type === 'erasure' && $dsar->candidate && ! $dsar->candidate->isRedacted())
                <form method="POST" action="{{ route('compliance.dsar.execute-erasure', $dsar) }}"
                      onsubmit="return confirm('Execute erasure for {{ $dsar->candidate->name }}? This redacts PII across the candidate record, all scope findings, and is IRREVERSIBLE. Issued PDF reports remain unchanged.');">
                    @csrf
                    <input type="hidden" name="confirm" value="I understand this is irreversible">
                    <button type="submit" class="nrh-btn" style="width: 100%; font-size: 12px; padding: 9px; background: var(--danger); color: #fff; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                        ⚠ Execute erasure (irreversible)
                    </button>
                    <p style="font-size: 10px; color: var(--ink-500); margin: 6px 0 0; line-height: 1.5;">
                        This will redact the candidate's name, identity number, mobile, remarks, and all scope findings. The PDF reports already issued to clients will not be modified.
                    </p>
                </form>
                @endif

                <div class="dsar-action-form">
                    <form method="POST" action="{{ route('compliance.dsar.complete', $dsar) }}">
                        @csrf @method('PATCH')
                        <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.14em; color: var(--ink-500); font-weight: 600;">Complete with outcome</div>
                        <textarea name="outcome" required placeholder="What was done? e.g. &quot;Provided full copy of held data via secure email on 28 Apr 2026.&quot;"></textarea>
                        <button type="submit" class="nrh-btn nrh-btn-primary" style="font-size: 11px; padding: 6px 12px;">Mark completed</button>
                    </form>
                </div>

                <div class="dsar-action-form">
                    <form method="POST" action="{{ route('compliance.dsar.reject', $dsar) }}">
                        @csrf @method('PATCH')
                        <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.14em; color: var(--ink-500); font-weight: 600;">Reject with reason</div>
                        <textarea name="outcome" required placeholder="Why is this request being rejected? e.g. &quot;Identity could not be verified after multiple requests.&quot;"></textarea>
                        <button type="submit" class="nrh-btn" style="font-size: 11px; padding: 6px 12px; background: transparent; border: 1px solid var(--danger); color: var(--danger);">Reject</button>
                    </form>
                </div>
            </div>
        </div>
        @endif
    </aside>
</div>

@endsection
