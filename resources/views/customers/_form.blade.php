{{--
    Shared form partial for customer create / edit.
    Required vars: $action (URL), $method ('POST' or 'PUT'), $customer (Customer or null), $countries, $submitLabel
--}}
@php
    $c = $customer ?? null;
    $val = fn ($field) => old($field, $c?->{$field});
    $isCreate = $c === null;
    $sendInvitationDefault = old('send_invitation', $isCreate ? '1' : null);
@endphp

<style>
    /* Form scaffolding */
    .cf-shell { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 28px; align-items: start; }
    @media (max-width: 1100px) { .cf-shell { grid-template-columns: 1fr; } }

    .cf-form { display: flex; flex-direction: column; gap: 14px; padding-bottom: 88px; }

    .cf-section {
        background: var(--card);
        border: 1px solid var(--line);
        border-radius: 12px;
        overflow: hidden;
    }
    .cf-section-head {
        padding: 14px 20px;
        border-bottom: 1px solid var(--line);
        display: flex; align-items: center; gap: 10px;
        background: linear-gradient(180deg, var(--paper-2), var(--card));
    }
    .cf-section-icon {
        width: 28px; height: 28px;
        border-radius: 6px;
        display: grid; place-items: center;
        background: var(--emerald-50);
        color: var(--emerald-700);
    }
    .cf-section-icon svg { width: 14px; height: 14px; }
    .cf-section-title {
        font-size: 13px; font-weight: 600; color: var(--ink-900);
        letter-spacing: -0.005em;
    }
    .cf-section-sub {
        font-size: 11px; color: var(--ink-500);
        margin-top: 2px;
    }
    .cf-section-body {
        padding: 18px 20px;
        display: grid; grid-template-columns: 1fr 1fr; gap: 14px 16px;
    }
    .cf-section-body .col-2 { grid-column: 1 / -1; }

    .cf-field { display: flex; flex-direction: column; gap: 5px; }
    .cf-label {
        font-size: 10px; text-transform: uppercase; letter-spacing: 0.14em;
        color: var(--ink-500); font-weight: 600;
        display: flex; align-items: center; gap: 4px;
    }
    .cf-label .req { color: var(--danger); }

    .cf-input-wrap { position: relative; }
    .cf-lead-icon {
        position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
        width: 14px; height: 14px;
        color: var(--ink-400); pointer-events: none;
    }
    .cf-input,
    .cf-textarea,
    .cf-select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid var(--line);
        background: var(--card);
        border-radius: 8px;
        font-family: inherit; font-size: 13px;
        color: var(--ink-900);
        outline: none;
        transition: border-color 120ms ease, box-shadow 120ms ease, background 120ms;
    }
    .cf-input.has-icon { padding-left: 36px; }
    .cf-input:focus, .cf-textarea:focus, .cf-select:focus {
        border-color: var(--emerald-600);
        box-shadow: 0 0 0 3px rgba(5,150,105,0.10);
    }
    .cf-input::placeholder, .cf-textarea::placeholder { color: var(--ink-400); }
    .cf-input.has-error { border-color: var(--danger); background: rgba(196,69,58,0.04); }
    .cf-textarea { resize: vertical; min-height: 80px; }
    .cf-help { font-size: 11px; color: var(--ink-400); }
    .cf-error { font-size: 11px; color: var(--danger); }

    /* Preview card */
    .cf-preview {
        position: sticky; top: 20px;
        background: var(--card);
        border: 1px solid var(--line);
        border-radius: 12px;
        overflow: hidden;
    }
    .cf-preview-head {
        padding: 12px 16px;
        background: var(--paper-2);
        border-bottom: 1px solid var(--line);
        font-size: 10px; text-transform: uppercase; letter-spacing: 0.16em;
        color: var(--ink-500); font-weight: 600;
        display: flex; align-items: center; gap: 8px;
    }
    .cf-preview-head .live-dot {
        width: 6px; height: 6px; border-radius: 50%; background: var(--emerald-600);
        box-shadow: 0 0 0 3px rgba(5,150,105,0.18);
    }
    .cf-preview-body { padding: 22px 20px; }
    .cf-preview-avatar {
        width: 60px; height: 60px;
        border-radius: 14px;
        background: linear-gradient(135deg, var(--emerald-600), var(--emerald-800));
        color: #fff;
        display: grid; place-items: center;
        font-family: 'Fraunces', serif; font-size: 22px; font-weight: 600;
        letter-spacing: 0.02em;
        margin-bottom: 14px;
        box-shadow: 0 6px 14px -6px rgba(4,77,57,0.4), inset 0 0 0 1px rgba(212,175,55,0.25);
    }
    .cf-preview-name {
        font-family: 'Fraunces', serif; font-size: 20px; font-weight: 500;
        line-height: 1.2; color: var(--ink-900);
        word-break: break-word;
    }
    .cf-preview-name em { color: var(--ink-400); font-style: italic; font-weight: 400; }
    .cf-preview-meta {
        font-family: 'JetBrains Mono', monospace;
        font-size: 10px; text-transform: uppercase; letter-spacing: 0.08em;
        color: var(--ink-400); margin-top: 6px;
    }
    .cf-preview-divider { height: 1px; background: var(--line); margin: 16px 0; }
    .cf-preview-row { display: grid; grid-template-columns: 14px 1fr; gap: 10px; align-items: start; padding: 6px 0; }
    .cf-preview-row svg { width: 13px; height: 13px; color: var(--ink-400); margin-top: 3px; }
    .cf-preview-row-text { font-size: 12px; color: var(--ink-700); line-height: 1.4; word-break: break-word; }
    .cf-preview-row-text.muted { color: var(--ink-400); font-style: italic; }

    /* Sticky action bar */
    .cf-actions {
        position: sticky; bottom: 0; left: 0; right: 0;
        margin: 0 -28px -28px;
        padding: 14px 28px;
        background: var(--paper);
        border-top: 1px solid var(--line);
        display: flex; align-items: center; justify-content: space-between;
        backdrop-filter: blur(8px);
        z-index: 5;
    }
    .cf-actions-hint {
        font-size: 11px; color: var(--ink-500);
        font-family: 'JetBrains Mono', monospace;
        text-transform: uppercase; letter-spacing: 0.08em;
    }
</style>

<div class="cf-shell"
     x-data="{
        name: @js($val('name') ?? ''),
        registration_no: @js($val('registration_no') ?? ''),
        industry: @js($val('industry') ?? ''),
        country: @js($val('country') ?? ''),
        address: @js($val('address') ?? ''),
        contact_name: @js($val('contact_name') ?? ''),
        contact_email: @js($val('contact_email') ?? ''),
        contact_phone: @js($val('contact_phone') ?? ''),
        get initials() {
            if (!this.name) return '?';
            return this.name.trim().split(/\s+/).slice(0, 2).map(s => s[0]).join('').toUpperCase();
        },
        get countryFlag() {
            const c = @js($countries->mapWithKeys(fn ($c) => [$c->name => $c->flag])->all());
            return c[this.country] ?? '';
        },
     }">

    <form method="POST" action="{{ $action }}" class="cf-form">
        @csrf
        @if($method !== 'POST')
            @method($method)
        @endif

        @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 text-xs px-4 py-3 rounded-lg flex items-start gap-2">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0; margin-top:2px;"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
            <div>
                <strong>Some fields need attention:</strong>
                <ul class="mt-1 list-disc list-inside">
                    @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        {{-- ── Company Information ── --}}
        <div class="cf-section">
            <div class="cf-section-head">
                <div class="cf-section-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4"/><path d="M9 9h.01M9 13h.01M9 17h.01"/></svg>
                </div>
                <div>
                    <div class="cf-section-title">Company Information</div>
                    <div class="cf-section-sub">Legal entity and core details</div>
                </div>
            </div>
            <div class="cf-section-body">
                <div class="cf-field col-2">
                    <label class="cf-label" for="name">Company name <span class="req">*</span></label>
                    <div class="cf-input-wrap">
                        <svg class="cf-lead-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4"/></svg>
                        <input type="text" id="name" name="name" required
                               x-model="name"
                               value="{{ $val('name') }}"
                               placeholder="Acme Sdn. Bhd."
                               class="cf-input has-icon {{ $errors->has('name') ? 'has-error' : '' }}">
                    </div>
                    @error('name')<span class="cf-error">{{ $message }}</span>@enderror
                </div>

                <div class="cf-field">
                    <label class="cf-label" for="registration_no">Registration No.</label>
                    <input type="text" id="registration_no" name="registration_no"
                           x-model="registration_no"
                           value="{{ $val('registration_no') }}"
                           placeholder="123456-A"
                           class="cf-input" style="font-family: 'JetBrains Mono', monospace;">
                </div>

                <div class="cf-field">
                    <label class="cf-label" for="industry">Industry</label>
                    <input type="text" id="industry" name="industry"
                           list="industry-options"
                           x-model="industry"
                           value="{{ $val('industry') }}"
                           placeholder="Banking &amp; Finance"
                           class="cf-input">
                    <datalist id="industry-options">
                        <option value="Banking &amp; Finance"></option>
                        <option value="Insurance"></option>
                        <option value="Capital Markets"></option>
                        <option value="Fintech"></option>
                        <option value="Legal Services"></option>
                        <option value="Professional Services"></option>
                        <option value="Real Estate"></option>
                        <option value="Healthcare"></option>
                        <option value="Manufacturing"></option>
                        <option value="Government"></option>
                        <option value="Education"></option>
                    </datalist>
                </div>
            </div>
        </div>

        {{-- ── Office Address ── --}}
        <div class="cf-section">
            <div class="cf-section-head">
                <div class="cf-section-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 1 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                </div>
                <div>
                    <div class="cf-section-title">Office Address</div>
                    <div class="cf-section-sub">Where the company is registered</div>
                </div>
            </div>
            <div class="cf-section-body">
                <div class="cf-field col-2">
                    <label class="cf-label" for="country">Country</label>
                    <select id="country" name="country" x-model="country" class="cf-select">
                        <option value="">— Select country —</option>
                        @foreach($countries as $cn)
                        <option value="{{ $cn->name }}" {{ $val('country') === $cn->name ? 'selected' : '' }}>{{ $cn->flag }} {{ $cn->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="cf-field col-2">
                    <label class="cf-label" for="address">Street address</label>
                    <textarea id="address" name="address" rows="3"
                              x-model="address"
                              placeholder="Suite 12-3, Level 12, Menara KLK&#10;1 Jalan PJU 7/6, Mutiara Damansara&#10;47800 Petaling Jaya"
                              class="cf-textarea">{{ $val('address') }}</textarea>
                </div>
            </div>
        </div>

        {{-- ── Primary Contact ── --}}
        <div class="cf-section">
            <div class="cf-section-head">
                <div class="cf-section-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
                <div>
                    <div class="cf-section-title">Primary Contact</div>
                    <div class="cf-section-sub">Whom we coordinate with for screening requests</div>
                </div>
            </div>
            <div class="cf-section-body">
                <div class="cf-field">
                    <label class="cf-label" for="contact_name">Full name</label>
                    <div class="cf-input-wrap">
                        <svg class="cf-lead-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
                        <input type="text" id="contact_name" name="contact_name"
                               x-model="contact_name"
                               value="{{ $val('contact_name') }}"
                               placeholder="Tan Wei Ling"
                               class="cf-input has-icon">
                    </div>
                </div>

                <div class="cf-field">
                    <label class="cf-label" for="contact_email">Email</label>
                    <div class="cf-input-wrap">
                        <svg class="cf-lead-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><path d="m22 6-10 7L2 6"/></svg>
                        <input type="email" id="contact_email" name="contact_email"
                               x-model="contact_email"
                               value="{{ $val('contact_email') }}"
                               placeholder="ops@acme.com.my"
                               class="cf-input has-icon {{ $errors->has('contact_email') ? 'has-error' : '' }}">
                    </div>
                    @error('contact_email')<span class="cf-error">{{ $message }}</span>@enderror
                </div>

                <div class="cf-field col-2">
                    <label class="cf-label" for="contact_phone">Phone</label>
                    <div class="cf-input-wrap">
                        <svg class="cf-lead-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        <input type="text" id="contact_phone" name="contact_phone"
                               x-model="contact_phone"
                               value="{{ $val('contact_phone') }}"
                               placeholder="+60 3-2345 6789"
                               class="cf-input has-icon">
                    </div>
                </div>

                @if($isCreate)
                <div class="col-2" x-show="contact_email" x-cloak style="margin-top:4px;">
                    <label style="display:flex; align-items:flex-start; gap:10px; padding:14px 16px; border:1px solid var(--line); border-radius:8px; background:var(--paper-2); cursor:pointer;"
                           :style="$el.querySelector('input[type=checkbox]').checked ? 'border-color: var(--emerald-600); background: rgba(5,150,105,0.04);' : ''">
                        <input type="checkbox" name="send_invitation" value="1"
                               {{ $sendInvitationDefault ? 'checked' : '' }}
                               class="accent-emerald-700" style="margin-top:2px; flex-shrink:0;">
                        <div style="flex:1;">
                            <div style="font-size:13px; font-weight:600; color:var(--ink-900);">Send portal invitation to <span x-text="contact_email" style="color:var(--emerald-700); font-family: 'JetBrains Mono', monospace; font-size:12px;"></span></div>
                            <div style="font-size:11px; color:var(--ink-500); margin-top:4px; line-height:1.5;">
                                Creates a primary login account on the client portal and emails an invitation link valid for 14 days.
                                The recipient sets their own password.
                            </div>
                        </div>
                    </label>
                </div>
                @endif
            </div>
        </div>

        {{-- ── Sticky action bar ── --}}
        <div class="cf-actions">
            <span class="cf-actions-hint">
                <span class="req" style="color: var(--danger);">*</span> Required fields · changes are audit-logged
            </span>
            <div class="flex gap-2">
                <a href="{{ $cancelUrl }}" class="nrh-btn nrh-btn-ghost">Cancel</a>
                <button type="submit" class="nrh-btn nrh-btn-primary">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" style="margin-right:4px;"><path d="M20 6L9 17l-5-5"/></svg>
                    {{ $submitLabel }}
                </button>
            </div>
        </div>
    </form>

    {{-- ── Live preview card ── --}}
    <aside class="cf-preview">
        <div class="cf-preview-head">
            <span class="live-dot"></span> Live preview
        </div>
        <div class="cf-preview-body">
            <div class="cf-preview-avatar" x-text="initials"></div>
            <div class="cf-preview-name" x-text="name || ''"></div>
            <div class="cf-preview-name" x-show="!name" x-cloak><em>Unnamed customer</em></div>
            <div class="cf-preview-meta">
                <span x-show="registration_no" x-text="registration_no"></span>
                <span x-show="!registration_no && !industry" x-cloak>—</span>
                <span x-show="industry" style="color: var(--ink-500);"> · <span x-text="industry"></span></span>
            </div>

            <div class="cf-preview-divider"></div>

            <div class="cf-preview-row">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 1 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <div class="cf-preview-row-text" :class="{ 'muted': !country && !address }">
                    <span x-show="country" x-html="countryFlag + ' ' + country"></span>
                    <div x-show="address" x-text="address" style="white-space: pre-wrap; margin-top: 2px; color: var(--ink-500);"></div>
                    <span x-show="!country && !address" x-cloak>No address yet</span>
                </div>
            </div>

            <div class="cf-preview-row">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
                <div class="cf-preview-row-text" :class="{ 'muted': !contact_name }">
                    <span x-show="contact_name" x-text="contact_name"></span>
                    <span x-show="!contact_name" x-cloak>No contact yet</span>
                </div>
            </div>

            <div class="cf-preview-row" x-show="contact_email">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><path d="m22 6-10 7L2 6"/></svg>
                <div class="cf-preview-row-text" x-text="contact_email"></div>
            </div>

            <div class="cf-preview-row" x-show="contact_phone">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                <div class="cf-preview-row-text" x-text="contact_phone"></div>
            </div>
        </div>
    </aside>
</div>
