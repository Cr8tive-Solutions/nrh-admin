{{-- One-shot banner after an invite/resend: the link is only ever shown here
     (just its hash is stored), so staff can share it by hand if email fails. --}}
@if(session('invitation_url'))
<div x-data="{ open: true, copied: false, copy() { navigator.clipboard.writeText(@js(session('invitation_url'))); this.copied = true; setTimeout(() => this.copied = false, 2000); } }"
     x-show="open"
     style="background: linear-gradient(135deg, #f0fdf4, #ecfdf5); border: 1px solid #86efac; border-radius: 12px; padding: 18px 22px; margin-bottom: 16px; display: flex; align-items: center; gap: 16px;">
    <div style="width: 36px; height: 36px; border-radius: 8px; background: var(--emerald-700); color: white; display: grid; place-items: center; flex-shrink: 0;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><path d="m22 6-10 7L2 6"/></svg>
    </div>
    <div style="flex: 1; min-width: 0;">
        <div style="font-size: 13px; font-weight: 600; color: #14532d;">Registration link</div>
        <div style="font-size: 11px; color: #166534; margin-top: 2px;">If the email doesn't arrive, share this link with the invitee yourself — valid for {{ \App\Models\CustomerRegistration::INVITE_DAYS }} days. It won't be shown again.</div>
        <div style="margin-top: 10px; display: flex; gap: 8px; align-items: center;">
            <input type="text" readonly value="{{ session('invitation_url') }}"
                   onclick="this.select()"
                   style="flex: 1; min-width: 0; padding: 7px 10px; border: 1px solid #86efac; background: white; border-radius: 6px; font-family: 'JetBrains Mono', monospace; font-size: 11px; color: #14532d;">
            <button type="button" @click="copy()" class="nrh-btn nrh-btn-primary" style="font-size: 11px; padding: 6px 14px; flex-shrink: 0;">
                <span x-show="!copied">Copy link</span>
                <span x-show="copied" x-cloak>Copied ✓</span>
            </button>
        </div>
    </div>
    <button @click="open = false" style="background: none; border: none; cursor: pointer; color: #166534; opacity: 0.6; padding: 0; flex-shrink: 0;" aria-label="Dismiss">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
    </button>
</div>
@endif
