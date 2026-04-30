@extends('layouts.admin')

@section('title', 'Business Holidays')
@section('page-title', 'Business Holidays')
@section('page-subtitle', 'Days excluded from SLA / TAT calculations')

@section('content')

<style>
    .hl-grid { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 18px; align-items: start; }
    @media (max-width: 1100px) { .hl-grid { grid-template-columns: 1fr; } }
    .hl-card { background: var(--card); border: 1px solid var(--line); border-radius: 12px; overflow: hidden; }
    .hl-card-head { padding: 12px 16px; border-bottom: 1px solid var(--line); background: linear-gradient(180deg, var(--paper-2), var(--card)); display: flex; align-items: center; gap: 8px; }
    .hl-card-head h2 { font-size: 13px; font-weight: 600; color: var(--ink-900); margin: 0; }
    .hl-row { display: grid; grid-template-columns: 110px 1fr auto; gap: 14px; align-items: center; padding: 10px 16px; border-bottom: 1px solid var(--line); }
    .hl-row:last-child { border-bottom: none; }
    .hl-row:hover { background: var(--paper-2); }
    .hl-date { font-family: 'JetBrains Mono', monospace; font-size: 12px; color: var(--ink-700); }
    .hl-label { font-size: 13px; color: var(--ink-900); }
    .hl-year-head {
        padding: 8px 16px; background: var(--paper-2);
        font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.16em;
        color: var(--ink-500); font-family: 'JetBrains Mono', monospace;
        border-bottom: 1px solid var(--line);
    }
    .hl-add-form { padding: 18px; display: flex; flex-direction: column; gap: 12px; }
    .hl-field { display: flex; flex-direction: column; gap: 5px; }
    .hl-label-text {
        font-size: 10px; text-transform: uppercase; letter-spacing: 0.14em;
        color: var(--ink-500); font-weight: 600;
    }
    .hl-input {
        padding: 10px 12px; border: 1px solid var(--line); background: var(--card);
        border-radius: 8px; font-size: 13px; color: var(--ink-900); outline: none;
        font-family: inherit;
    }
    .hl-input:focus { border-color: var(--emerald-600); box-shadow: 0 0 0 3px rgba(5,150,105,0.10); }
    .hl-empty { padding: 48px 24px; text-align: center; color: var(--ink-400); }
</style>

<div class="hl-grid">

    <div class="hl-card">
        <div class="hl-card-head">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--emerald-700);"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            <h2>Holidays</h2>
            <span style="margin-left: auto; font-size: 11px; color: var(--ink-500); font-family: 'JetBrains Mono', monospace; text-transform: uppercase; letter-spacing: 0.1em;">
                {{ $holidays->flatten()->count() }} {{ Str::plural('day', $holidays->flatten()->count()) }}
            </span>
        </div>

        @if($holidays->isEmpty())
        <div class="hl-empty">
            <p style="font-size: 13px; color: var(--ink-700); font-weight: 500;">No holidays configured</p>
            <p style="font-size: 12px; color: var(--ink-400); margin-top: 4px;">Weekends are already excluded automatically. Add public holidays here so they're skipped from TAT calculations.</p>
        </div>
        @else
            @foreach($holidays as $year => $days)
            <div class="hl-year-head">{{ $year }} · {{ $days->count() }} {{ Str::plural('day', $days->count()) }}</div>
                @foreach($days as $h)
                <div class="hl-row">
                    <div class="hl-date">{{ $h->date->format('d M') }} · {{ $h->date->format('D') }}</div>
                    <div class="hl-label">{{ $h->label }}</div>
                    <form method="POST" action="{{ route('config.holidays.destroy', $h) }}"
                          onsubmit="return confirm('Remove {{ $h->label }} ({{ $h->date->format('d M Y') }}) from the holiday list?');" style="margin: 0;">
                        @csrf @method('DELETE')
                        <button type="submit" style="font-size: 11px; color: var(--danger); background: none; border: none; cursor: pointer; padding: 0; font-weight: 600;">Remove</button>
                    </form>
                </div>
                @endforeach
            @endforeach
        @endif
    </div>

    <aside>
    <div class="hl-card" style="margin-bottom: 14px;">
        <div class="hl-card-head">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--gold-700, #b8860b);"><path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
            <h2>Sync from Calendarific</h2>
        </div>
        <form method="POST" action="{{ route('config.holidays.sync') }}" style="padding: 14px 18px;">
            @csrf
            <p style="font-size: 11px; color: var(--ink-500); margin: 0 0 10px; line-height: 1.5;">
                Fetches Malaysian federal holidays via the Calendarific API. Existing dates are kept — only new ones are added.
            </p>
            <div style="display: flex; gap: 8px;">
                <select name="year" style="flex: 1; padding: 8px 10px; border: 1px solid var(--line); border-radius: 6px; font-size: 12px; outline: none; font-family: 'JetBrains Mono', monospace;">
                    @for($y = now()->year - 1; $y <= now()->year + 2; $y++)
                    <option value="{{ $y }}" {{ $y === now()->year ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
                <button type="submit" class="nrh-btn nrh-btn-primary" style="font-size: 11px; padding: 8px 14px; white-space: nowrap;">
                    Sync
                </button>
            </div>
            <p style="font-size: 10px; color: var(--ink-400); margin: 8px 0 0;">
                Auto-syncs annually on Jan 5 at 03:00. National holidays only — state-specific holidays (Hari Hol, Sultan birthdays) excluded; add manually if needed.
            </p>
        </form>
    </div>

    <div class="hl-card">
        <div class="hl-card-head">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--emerald-700);"><path d="M12 5v14M5 12h14"/></svg>
            <h2>Add manually</h2>
        </div>
        <form method="POST" action="{{ route('config.holidays.store') }}" class="hl-add-form">
            @csrf
            <div class="hl-field">
                <label class="hl-label-text" for="date">Date</label>
                <input type="date" id="date" name="date" required value="{{ old('date') }}" class="hl-input">
                @error('date')<span style="color: var(--danger); font-size: 11px;">{{ $message }}</span>@enderror
            </div>
            <div class="hl-field">
                <label class="hl-label-text" for="label">Label</label>
                <input type="text" id="label" name="label" required maxlength="120"
                       value="{{ old('label') }}"
                       placeholder="e.g. Hari Raya Aidilfitri"
                       class="hl-input">
                @error('label')<span style="color: var(--danger); font-size: 11px;">{{ $message }}</span>@enderror
            </div>
            <button type="submit" class="nrh-btn nrh-btn-primary" style="width: 100%; padding: 10px 16px; font-size: 13px; font-weight: 600; margin-top: 4px;">
                Add Holiday
            </button>

            <div style="font-size: 11px; color: var(--ink-500); margin-top: 6px; line-height: 1.5; padding-top: 10px; border-top: 1px solid var(--line);">
                <strong>Working hours:</strong> Mon–Fri, 09:00–18:00 (9h/day)<br>
                <strong>Timezone:</strong> {{ config('business_hours.timezone') }}
            </div>
        </form>
    </div>
    </aside>

</div>

@endsection
