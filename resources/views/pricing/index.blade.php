@extends('layouts.admin')

@section('title', 'Scope Pricing')
@section('page-title', 'Scope Pricing')

@section('content')
<style>
    .pricing-card { background: var(--card); border: 1px solid var(--line); border-radius: 10px; overflow: hidden; }
    .pricing-country-head { padding: 12px 16px; border-bottom: 1px solid var(--line); display: flex; align-items: center; gap: 8px; }
    .pricing-country-head h2 { font-size: 13px; font-weight: 600; color: var(--ink-900); margin: 0; }
    .pricing-country-head .count { font-size: 11px; color: var(--ink-400); }
    .pricing-cat-head { padding: 7px 16px; background: var(--paper-2); font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.14em; color: var(--ink-500); border-bottom: 1px solid var(--line); }
    .pricing-row { display: grid; grid-template-columns: 2fr 1fr 1.4fr; align-items: center; padding: 10px 16px; border-bottom: 1px solid var(--line); transition: background 100ms; }
    .pricing-row:last-child { border-bottom: none; }
    .pricing-row:hover { background: rgba(4,108,78,0.03); }
    .pricing-row.is-dirty { background: rgba(16,185,129,0.04); }
    .pricing-name { font-size: 13px; color: var(--ink-900); display: flex; align-items: center; gap: 8px; }
    .pricing-default { font-size: 11px; color: var(--ink-400); font-style: italic; }
    .pricing-input-wrap { display: flex; align-items: center; gap: 8px; }
    .pricing-prefix { font-size: 11px; color: var(--ink-500); font-family: 'JetBrains Mono', monospace; }
    .pricing-input {
        width: 110px; padding: 6px 10px; text-align: right;
        border: 1px solid var(--line); background: var(--card);
        border-radius: 6px; font-size: 13px; color: var(--ink-900);
        font-family: 'JetBrains Mono', monospace;
        outline: none; transition: border-color 120ms, box-shadow 120ms, background 120ms;
    }
    .pricing-input:focus { border-color: var(--emerald-600); box-shadow: 0 0 0 3px rgba(5,150,105,0.1); }
    .pricing-input.is-changed { border-color: var(--emerald-500); background: rgba(16,185,129,0.04); }
    .pricing-dirty-dot {
        width: 7px; height: 7px; border-radius: 50%;
        background: var(--emerald-600);
        box-shadow: 0 0 0 3px rgba(5,150,105,0.18);
        flex-shrink: 0;
    }
    .pricing-badge { font-size: 10px; font-weight: 600; padding: 2px 7px; border-radius: 999px; background: rgba(4,108,78,0.08); color: var(--emerald-700); white-space: nowrap; }
    .pricing-skeleton-row { display: flex; align-items: center; gap: 12px; padding: 13px 16px; border-bottom: 1px solid var(--line); }
    .skeleton-block { border-radius: 4px; background: var(--ink-100); animation: pulse 1.4s ease-in-out infinite; }
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.45} }
    .pricing-selector-bar { background: var(--card); border: 1px solid var(--line); border-radius: 10px; padding: 14px 18px; margin-bottom: 18px; display: flex; align-items: center; gap: 12px; }
    .pricing-selector-label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.16em; color: var(--ink-500); white-space: nowrap; }
    .pricing-selector { border: 1px solid var(--line); background: var(--card); color: var(--ink-900); border-radius: 6px; padding: 7px 12px; font-size: 13px; outline: none; min-width: 260px; transition: border-color 120ms; font-family: inherit; }
    .pricing-selector:focus { border-color: var(--emerald-600); box-shadow: 0 0 0 3px rgba(5,150,105,0.1); }
    .pricing-filter-input { border: 1px solid var(--line); background: var(--card); color: var(--ink-900); border-radius: 6px; padding: 7px 12px; font-size: 13px; outline: none; min-width: 180px; transition: border-color 120ms; font-family: inherit; }
    .pricing-filter-input:focus { border-color: var(--emerald-600); box-shadow: 0 0 0 3px rgba(5,150,105,0.1); }
    .pricing-empty { background: var(--card); border: 1px solid var(--line); border-radius: 10px; padding: 56px 20px; text-align: center; }

    /* Bottom padding so floating bar never overlaps the last row */
    .pricing-page-end { height: 80px; }

    /* Floating bulk action bar — fixed to viewport, centered within the
       content area (220px sidebar offset). Always visible without scrolling. */
    .pricing-bulkbar {
        position: fixed;
        bottom: 24px;
        left: calc(50% + 110px);
        transform: translateX(-50%);
        z-index: 40;
        max-width: calc(100vw - 268px);
        width: max-content;
        min-width: 360px;
        background: var(--card);
        border: 1px solid var(--line);
        border-radius: 10px;
        padding: 10px 14px 10px 18px;
        display: flex; align-items: center; gap: 12px;
        box-shadow:
            0 18px 36px -14px rgba(0,0,0,0.28),
            0 6px 14px -6px rgba(4,77,57,0.18),
            0 0 0 1px rgba(212,175,55,0.22);
        backdrop-filter: blur(8px);
        transition: opacity 200ms ease, transform 200ms ease;
    }
    .pricing-bulkbar.idle { opacity: 0.55; }
    .pricing-bulkbar.idle:hover { opacity: 1; }
    @media (max-width: 720px) {
        .pricing-bulkbar { left: 50%; max-width: calc(100vw - 28px); min-width: 0; }
    }
    .pricing-bulk-text { font-size: 13px; color: var(--ink-700); flex: 1; }
    .pricing-bulk-text b { color: var(--ink-900); font-weight: 600; }
    .pricing-bulk-feedback {
        font-size: 12px; font-weight: 600;
        display: inline-flex; align-items: center; gap: 6px;
    }
    .pricing-bulk-feedback.saved { color: var(--emerald-700); }
    .pricing-bulk-feedback.error { color: var(--danger); }
</style>

@php $canEdit = admin_can('pricing.manage'); @endphp

@unless($canEdit)
<div class="bg-amber-50 border border-amber-200 text-amber-800 text-xs px-4 py-2.5 rounded-lg mb-3 flex items-center gap-2">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
    Read-only — you don't have permission to edit pricing.
</div>
@endunless

<div x-data="{
    canEdit: {{ $canEdit ? 'true' : 'false' }},
    customerId: '',
    customerName: '',
    loading: false,
    countries: [],
    prices: {},
    states: {},
    countryFilter: '',
    scopeSearch: '',
    saving: false,
    saveStatus: '',
    saveMessage: '',

    init() {
        const params = new URLSearchParams(window.location.search);
        const cid = params.get('customer_id');
        if (cid) {
            this.customerId = cid;
            this.$nextTick(() => {
                const opt = this.$refs.customerSelect.querySelector(`option[value='${cid}']`);
                if (opt) { this.customerName = opt.textContent.trim(); this.$refs.customerSelect.value = cid; }
                this.loadScopes(cid);
            });
        }

        // Warn before unload if there are unsaved changes
        window.addEventListener('beforeunload', (e) => {
            if (this.dirtyCount > 0) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    },

    onCustomerChange(e) {
        if (this.dirtyCount > 0 && !confirm('You have unsaved price changes. Switch customer and discard them?')) {
            e.target.value = this.customerId;
            return;
        }
        this.customerId = e.target.value;
        this.customerName = e.target.options[e.target.selectedIndex].text;
        this.countries = []; this.prices = {}; this.states = {};
        this.countryFilter = ''; this.scopeSearch = '';
        if (this.customerId) this.loadScopes(this.customerId);
    },

    async loadScopes(customerId) {
        this.loading = true;
        try {
            const res = await fetch(`/pricing/${customerId}/scopes`, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            this.countries = data.countries;
            data.countries.forEach(c => c.categories.forEach(cat => cat.scopes.forEach(scope => {
                this.prices[scope.id] = scope.custom_price ?? '';
                this.states[scope.id] = { original: scope.custom_price ?? '' };
            })));
        } finally { this.loading = false; }
    },

    isChanged(id) {
        if (!this.states[id]) return false;
        const v = this.prices[id];
        // empty string in either direction is treated as no-change unless original was non-empty
        return String(v ?? '') !== String(this.states[id].original ?? '');
    },

    get dirtyIds() {
        return Object.keys(this.prices).filter(id => this.isChanged(id));
    },

    get dirtyCount() {
        return this.dirtyIds.length;
    },

    get customCount() {
        return Object.values(this.states).filter(s => s.original !== '' && s.original !== null).length;
    },

    get filteredCountries() {
        const search = this.scopeSearch.toLowerCase().trim();
        return this.countries
            .filter(c => !this.countryFilter || c.name === this.countryFilter)
            .map(c => ({
                ...c,
                categories: c.categories
                    .map(cat => ({
                        ...cat,
                        scopes: search ? cat.scopes.filter(s => s.name.toLowerCase().includes(search)) : cat.scopes
                    }))
                    .filter(cat => cat.scopes.length > 0)
            }))
            .filter(c => c.categories.length > 0);
    },

    discardChanges() {
        if (this.dirtyCount === 0) return;
        if (!confirm(`Discard ${this.dirtyCount} unsaved ${this.dirtyCount === 1 ? 'change' : 'changes'}?`)) return;
        this.dirtyIds.forEach(id => {
            this.prices[id] = this.states[id].original;
        });
    },

    async saveAll() {
        const ids = this.dirtyIds;
        if (ids.length === 0 || this.saving) return;

        // Build payload — skip empty values (treat empty as 'no custom price')
        const payload = ids
            .filter(id => this.prices[id] !== '' && this.prices[id] !== null && this.prices[id] !== undefined)
            .map(id => ({ scope_type_id: Number(id), price: this.prices[id] }));

        if (payload.length === 0) {
            this.saveStatus = 'error';
            this.saveMessage = 'No valid prices to save (empty fields are skipped).';
            setTimeout(() => { this.saveStatus = ''; }, 3000);
            return;
        }

        this.saving = true;
        this.saveStatus = '';
        try {
            const res = await fetch(`/pricing/${this.customerId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ prices: payload }),
            });
            if (!res.ok) {
                const errBody = await res.json().catch(() => ({}));
                throw new Error(errBody.message || 'Save failed');
            }
            const data = await res.json();
            // Mark each saved scope's original to the persisted value
            Object.entries(data.saved || {}).forEach(([scopeId, savedPrice]) => {
                if (this.states[scopeId]) {
                    this.states[scopeId].original = savedPrice;
                    this.prices[scopeId] = savedPrice;
                }
            });
            this.saveStatus = 'saved';
            this.saveMessage = data.message || 'Pricing saved.';
            setTimeout(() => { this.saveStatus = ''; }, 3000);
        } catch (e) {
            this.saveStatus = 'error';
            this.saveMessage = e.message || 'Failed to save. Try again.';
            setTimeout(() => { this.saveStatus = ''; }, 4000);
        } finally {
            this.saving = false;
        }
    }
}">

    {{-- ── Customer selector bar ── --}}
    <div class="pricing-selector-bar">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="color:var(--ink-400); flex-shrink:0;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        <span class="pricing-selector-label">Customer</span>

        <select x-ref="customerSelect" @change="onCustomerChange($event)" class="pricing-selector">
            <option value="">— Select a customer —</option>
            @foreach($customers as $c)
            <option value="{{ $c->id }}">{{ $c->name }}</option>
            @endforeach
        </select>

        <div x-show="countries.length > 0 && !loading" x-transition style="margin-left:auto; display:flex; align-items:center; gap:6px;">
            <span style="font-size:12px; color:var(--ink-500);">
                <span style="font-weight:700; color:var(--emerald-700);" x-text="customCount"></span>
                custom <span x-text="customCount === 1 ? 'price' : 'prices'"></span> set
            </span>
        </div>

        <div x-show="loading" style="margin-left:auto; display:flex; align-items:center; gap:6px;">
            <svg class="animate-spin" style="width:13px;height:13px;color:var(--ink-400);" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="32" stroke-dashoffset="12"/></svg>
            <span style="font-size:12px; color:var(--ink-400);">Loading…</span>
        </div>
    </div>

    {{-- ── Filter bar (shown once data is loaded) ── --}}
    <div x-show="countries.length > 0 && !loading" x-transition class="pricing-selector-bar" style="margin-top:-10px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--ink-400); flex-shrink:0;"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
        <span class="pricing-selector-label">Filter</span>

        <select x-model="countryFilter" class="pricing-filter-input">
            <option value="">All Countries</option>
            <template x-for="c in countries" :key="c.name">
                <option :value="c.name" x-text="(c.flag ? c.flag + ' ' : '') + c.name"></option>
            </template>
        </select>

        <input type="search"
               x-model.debounce.200ms="scopeSearch"
               placeholder="Filter scopes…"
               class="pricing-filter-input">

        <button x-show="countryFilter !== '' || scopeSearch !== ''"
                x-transition
                @click="countryFilter = ''; scopeSearch = ''"
                style="font-size:11px; color:var(--ink-400); background:none; border:none; cursor:pointer; padding:0 4px;">
            Clear filters
        </button>
    </div>

    {{-- ── Country cards (rendered via Alpine x-for) ── --}}
    <template x-for="country in filteredCountries" :key="country.name">
        <div class="pricing-card" style="margin-bottom:12px;">
            <div class="pricing-country-head">
                <span style="font-size:18px;" x-text="country.flag"></span>
                <h2 x-text="country.name"></h2>
                <span class="count" x-text="'(' + country.scope_count + ' scopes)'"></span>
            </div>

            <template x-for="cat in country.categories" :key="cat.name">
                <div>
                    <div class="pricing-cat-head" x-text="cat.name"></div>

                    <template x-for="scope in cat.scopes" :key="scope.id">
                        <div class="pricing-row" :class="{ 'is-dirty': isChanged(scope.id) }">
                            <span class="pricing-name">
                                <span x-show="isChanged(scope.id)" class="pricing-dirty-dot" title="Unsaved change"></span>
                                <span x-text="scope.name"></span>
                            </span>

                            <span class="pricing-default">
                                <span x-show="scope.price_on_request">Price on request</span>
                                <span x-show="!scope.price_on_request" x-text="'Default: ' + country.currency + ' ' + scope.default_price"></span>
                            </span>

                            <div class="pricing-input-wrap">
                                <span class="pricing-prefix" x-text="country.currency"></span>
                                <input type="number" step="0.01" min="0"
                                       :value="prices[scope.id]"
                                       @input="prices[scope.id] = $event.target.value"
                                       @keydown.enter.prevent="canEdit && saveAll()"
                                       :readonly="!canEdit"
                                       :placeholder="scope.price_on_request ? 'Enter price' : scope.default_price"
                                       class="pricing-input"
                                       :class="{ 'is-changed': isChanged(scope.id) }">

                                <span x-show="!isChanged(scope.id) && states[scope.id]?.original"
                                      class="pricing-badge">Custom</span>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </template>

    {{-- ── No results after filtering ── --}}
    <div x-show="!loading && customerId && countries.length > 0 && filteredCountries.length === 0" x-transition class="pricing-empty">
        <p style="font-size:14px; color:var(--ink-500); margin:0 0 4px;">No scopes match your filters</p>
        <p style="font-size:12px; color:var(--ink-400); margin:0 0 12px;">Try adjusting the country or scope filter above.</p>
        <button @click="countryFilter = ''; scopeSearch = ''" style="font-size:12px; color:var(--emerald-700); background:none; border:none; cursor:pointer; font-weight:600;">Clear filters</button>
    </div>

    {{-- ── Skeleton loader ── --}}
    <template x-if="loading">
        <div class="pricing-card">
            @for($i = 0; $i < 8; $i++)
            <div class="pricing-skeleton-row">
                <div class="skeleton-block" style="height:12px; width:{{ [38,28,45,32,40,25,36,42][$i] }}%;"></div>
                <div class="skeleton-block" style="height:12px; width:14%; margin-left:auto;"></div>
                <div class="skeleton-block" style="height:28px; width:110px; margin-left:16px;"></div>
            </div>
            @endfor
        </div>
    </template>

    {{-- ── Empty state ── --}}
    <div x-show="!loading && !customerId" class="pricing-empty">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="color:var(--ink-200); margin:0 auto 10px; display:block;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
        <p style="font-size:14px; color:var(--ink-500); margin:0 0 4px;">No customer selected</p>
        <p style="font-size:12px; color:var(--ink-400); margin:0;">Select a customer above to manage their scope pricing.</p>
    </div>

    {{-- Spacer so the floating bar never sits on top of the last row --}}
    <div x-show="canEdit && customerId && countries.length > 0" class="pricing-page-end"></div>

    {{-- ── Floating bulk action bar ── --}}
    <div x-show="canEdit && customerId && countries.length > 0" x-cloak x-transition
         class="pricing-bulkbar"
         :class="{ 'idle': dirtyCount === 0 && saveStatus !== 'saved' }">
        <span class="pricing-bulk-text">
            <template x-if="dirtyCount > 0">
                <span><b x-text="dirtyCount"></b> unsaved <span x-text="dirtyCount === 1 ? 'change' : 'changes'"></span></span>
            </template>
            <template x-if="dirtyCount === 0 && saveStatus !== 'saved' && saveStatus !== 'error'">
                <span>No unsaved changes.</span>
            </template>
            <template x-if="saveStatus === 'saved' && dirtyCount === 0">
                <span class="pricing-bulk-feedback saved">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                    <span x-text="saveMessage"></span>
                </span>
            </template>
            <template x-if="saveStatus === 'error'">
                <span class="pricing-bulk-feedback error">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                    <span x-text="saveMessage"></span>
                </span>
            </template>
        </span>

        <button type="button" class="nrh-btn nrh-btn-ghost"
                x-show="dirtyCount > 0"
                @click="discardChanges()"
                :disabled="saving"
                style="font-size: 12px; padding: 7px 14px;">
            Discard
        </button>

        <button type="button" class="nrh-btn nrh-btn-primary"
                @click="saveAll()"
                :disabled="dirtyCount === 0 || saving"
                :class="{ 'opacity-40 cursor-not-allowed': dirtyCount === 0 || saving }"
                style="font-size: 13px; padding: 9px 18px; min-width: 140px;">
            <template x-if="!saving">
                <span style="display: inline-flex; align-items: center; gap: 6px;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Save All
                    <span x-show="dirtyCount > 0" x-text="`(${dirtyCount})`" style="font-family: 'JetBrains Mono', monospace; opacity: 0.85;"></span>
                </span>
            </template>
            <template x-if="saving">
                <span style="display: inline-flex; align-items: center; gap: 6px;">
                    <svg class="animate-spin" style="width:13px;height:13px;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="32" stroke-dashoffset="12"/></svg>
                    Saving…
                </span>
            </template>
        </button>
    </div>

</div>
@endsection
