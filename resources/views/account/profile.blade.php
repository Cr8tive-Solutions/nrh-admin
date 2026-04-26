@extends('layouts.admin')

@section('title', 'My Profile')
@section('page-title', 'My Profile')
@section('page-subtitle', $admin->email)

@section('content')

@include('account._nav')

<style>
    .pf-shell { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 24px; align-items: start; }
    @media (max-width: 1100px) { .pf-shell { grid-template-columns: 1fr; } }

    .pf-section {
        background: var(--card);
        border: 1px solid var(--line);
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 14px;
    }
    .pf-section-head {
        padding: 14px 20px;
        border-bottom: 1px solid var(--line);
        display: flex; align-items: center; gap: 10px;
        background: linear-gradient(180deg, var(--paper-2), var(--card));
    }
    .pf-section-icon {
        width: 28px; height: 28px;
        border-radius: 6px;
        display: grid; place-items: center;
        background: var(--emerald-50);
        color: var(--emerald-700);
    }
    .pf-section-icon svg { width: 14px; height: 14px; }
    .pf-section-title { font-size: 13px; font-weight: 600; color: var(--ink-900); }
    .pf-section-body { padding: 22px 24px; }

    .pf-field { display: flex; flex-direction: column; gap: 5px; margin-bottom: 16px; }
    .pf-field:last-child { margin-bottom: 0; }
    .pf-label {
        font-size: 10px; text-transform: uppercase; letter-spacing: 0.14em;
        color: var(--ink-500); font-weight: 600;
    }
    .pf-input {
        padding: 10px 12px;
        border: 1px solid var(--line);
        background: var(--card);
        border-radius: 8px;
        font-family: inherit; font-size: 13px;
        color: var(--ink-900); outline: none;
        transition: border-color 120ms ease, box-shadow 120ms ease;
    }
    .pf-input:focus {
        border-color: var(--emerald-600);
        box-shadow: 0 0 0 3px rgba(5,150,105,0.10);
    }
    .pf-input.readonly {
        background: var(--paper-2);
        color: var(--ink-500);
        cursor: not-allowed;
    }

    /* Avatar uploader */
    .pf-avatar-row { display: grid; grid-template-columns: 100px 1fr; gap: 18px; align-items: center; }
    .pf-avatar-current {
        width: 100px; height: 100px;
        border-radius: 18px;
        background: linear-gradient(135deg, var(--emerald-600), var(--emerald-800));
        color: #fff;
        display: grid; place-items: center;
        font-family: 'Fraunces', serif; font-size: 36px; font-weight: 600;
        overflow: hidden;
        box-shadow: 0 8px 18px -8px rgba(4,77,57,0.4), inset 0 0 0 1px rgba(212,175,55,0.25);
    }
    .pf-avatar-current img { width: 100%; height: 100%; object-fit: cover; }
    .pf-avatar-actions { display: flex; flex-direction: column; gap: 8px; }
    .pf-avatar-buttons { display: flex; gap: 8px; align-items: center; }
    .pf-avatar-hint { font-size: 11px; color: var(--ink-500); line-height: 1.5; }
    .pf-file-btn {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 8px 14px;
        font-size: 12px; font-weight: 600;
        border-radius: 8px;
        border: 1px solid var(--line);
        background: var(--card);
        color: var(--ink-700);
        cursor: pointer;
        transition: all 120ms;
    }
    .pf-file-btn:hover { border-color: var(--emerald-600); color: var(--emerald-700); }
    .pf-file-btn svg { width: 13px; height: 13px; }

    /* Sticky action bar */
    .pf-actions {
        position: sticky; bottom: 0;
        margin: 0 -28px -28px;
        padding: 14px 28px;
        background: var(--paper);
        border-top: 1px solid var(--line);
        display: flex; align-items: center; justify-content: flex-end; gap: 8px;
        z-index: 5;
    }

    /* Identity card on the right */
    .pf-identity {
        background: var(--card);
        border: 1px solid var(--line);
        border-radius: 12px;
        overflow: hidden;
        position: sticky; top: 20px;
    }
    .pf-identity-head {
        padding: 24px 20px 16px;
        text-align: center;
    }
    .pf-id-avatar {
        width: 80px; height: 80px;
        border-radius: 18px;
        background: linear-gradient(135deg, var(--emerald-600), var(--emerald-800));
        color: #fff;
        display: grid; place-items: center;
        font-family: 'Fraunces', serif; font-size: 30px; font-weight: 600;
        margin: 0 auto 12px;
        overflow: hidden;
        box-shadow: 0 6px 14px -6px rgba(4,77,57,0.4), inset 0 0 0 1px rgba(212,175,55,0.25);
    }
    .pf-id-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .pf-id-name {
        font-family: 'Fraunces', serif; font-size: 18px; font-weight: 500;
        color: var(--ink-900); margin-bottom: 4px;
    }
    .pf-id-email {
        font-size: 12px; color: var(--ink-500); font-family: 'JetBrains Mono', monospace;
    }
    .pf-identity-meta {
        padding: 16px 20px;
        border-top: 1px solid var(--line);
        font-size: 11px; color: var(--ink-500);
    }
    .pf-identity-meta-row { display: flex; justify-content: space-between; padding: 4px 0; }
    .pf-identity-meta-row b { color: var(--ink-900); font-weight: 600; }
</style>

<div class="pf-shell"
     x-data="{
        name: @js($admin->name),
        avatarPreview: @js($admin->avatarUrl()),
        get initials() {
            if (!this.name) return 'A';
            return this.name.trim().split(/\s+/).slice(0, 2).map(s => s[0]).join('').toUpperCase();
        },
        onFile(e) {
            const file = e.target.files[0];
            if (!file) return;
            this.avatarPreview = URL.createObjectURL(file);
        },
     }">

    <form method="POST" action="{{ route('account.profile.update') }}" enctype="multipart/form-data" style="padding-bottom: 88px;">
        @csrf @method('PUT')

        {{-- ── Avatar section ── --}}
        <div class="pf-section">
            <div class="pf-section-head">
                <div class="pf-section-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                </div>
                <div class="pf-section-title">Profile picture</div>
            </div>
            <div class="pf-section-body">
                <div class="pf-avatar-row">
                    <div class="pf-avatar-current">
                        <template x-if="avatarPreview">
                            <img :src="avatarPreview" alt="Avatar preview">
                        </template>
                        <template x-if="!avatarPreview">
                            <span x-text="initials"></span>
                        </template>
                    </div>
                    <div class="pf-avatar-actions">
                        <div class="pf-avatar-buttons">
                            <label class="pf-file-btn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M17 8l-5-5-5 5M12 3v12"/></svg>
                                Choose image
                                <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp"
                                       @change="onFile($event)" style="display:none;">
                            </label>
                            @if($admin->avatar)
                            <button type="button" class="pf-file-btn"
                                    onclick="if (confirm('Remove avatar? You will be back to initials.')) { document.getElementById('remove-avatar-form').submit(); }"
                                    style="color: var(--danger);">
                                Remove
                            </button>
                            @endif
                        </div>
                        <div class="pf-avatar-hint">JPG, PNG or WEBP, up to 2 MB. Square images work best (200×200 or larger).</div>
                        @error('avatar')
                            <span style="color: var(--danger); font-size: 11px;">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Personal information ── --}}
        <div class="pf-section">
            <div class="pf-section-head">
                <div class="pf-section-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
                </div>
                <div class="pf-section-title">Personal information</div>
            </div>
            <div class="pf-section-body">
                <div class="pf-field">
                    <label class="pf-label" for="name">Display name <span style="color: var(--danger);">*</span></label>
                    <input type="text" id="name" name="name" required
                           x-model="name"
                           value="{{ old('name', $admin->name) }}"
                           class="pf-input">
                    @error('name')
                        <span style="color: var(--danger); font-size: 11px;">{{ $message }}</span>
                    @enderror
                </div>

                <div class="pf-field">
                    <label class="pf-label">Email address</label>
                    <input type="text" readonly value="{{ $admin->email }}" class="pf-input readonly" style="font-family: 'JetBrains Mono', monospace;">
                    <span style="font-size: 11px; color: var(--ink-400);">Email is locked. Contact a super admin if it needs to change.</span>
                </div>

                <div class="pf-field">
                    <label class="pf-label">Role</label>
                    <div>
                        <span class="badge {{ $admin->role === 'super_admin' ? 'badge-blue' : 'badge-gray' }}" style="font-size: 12px; padding: 4px 10px;">
                            {{ str_replace('_', ' ', $admin->role) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="pf-actions">
            <a href="{{ route('dashboard') }}" class="nrh-btn nrh-btn-ghost">Cancel</a>
            <button type="submit" class="nrh-btn nrh-btn-primary">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" style="margin-right:4px;"><path d="M20 6L9 17l-5-5"/></svg>
                Save Changes
            </button>
        </div>
    </form>

    {{-- ── Identity preview card ── --}}
    <aside class="pf-identity">
        <div class="pf-identity-head">
            <div class="pf-id-avatar">
                <template x-if="avatarPreview">
                    <img :src="avatarPreview" alt="Avatar">
                </template>
                <template x-if="!avatarPreview">
                    <span x-text="initials"></span>
                </template>
            </div>
            <div class="pf-id-name" x-text="name"></div>
            <div class="pf-id-email">{{ $admin->email }}</div>
        </div>
        <div class="pf-identity-meta">
            <div class="pf-identity-meta-row">
                <span>Role</span>
                <b>{{ str_replace('_', ' ', $admin->role) }}</b>
            </div>
            <div class="pf-identity-meta-row">
                <span>Member since</span>
                <b>{{ $admin->created_at->format('M Y') }}</b>
            </div>
            <div class="pf-identity-meta-row">
                <span>2FA</span>
                <b style="color: {{ $admin->hasEnabledTwoFactor() ? 'var(--emerald-700)' : 'var(--ink-400)' }};">
                    {{ $admin->hasEnabledTwoFactor() ? 'On' : 'Off' }}
                </b>
            </div>
        </div>
    </aside>
</div>

@if($admin->avatar)
<form id="remove-avatar-form" method="POST" action="{{ route('account.profile.avatar.remove') }}" style="display: none;">
    @csrf @method('DELETE')
</form>
@endif

@endsection
