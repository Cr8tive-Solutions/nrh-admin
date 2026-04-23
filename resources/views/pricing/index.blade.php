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
    .pricing-name { font-size: 13px; color: var(--ink-900); }
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
    .pricing-save-btn {
        font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 5px;
        background: var(--emerald-700); color: #fff; border: none; cursor: pointer;
        transition: background 120ms; white-space: nowrap;
    }
    .pricing-save-btn:hover { background: var(--emerald-800); }
    .pricing-feedback { font-size: 11px; display: flex; align-items: center; gap: 4px; white-space: nowrap; }
    .pricing-feedback.saving { color: var(--ink-400); }
    .pricing-feedback.saved  { color: var(--emerald-600); font-weight: 600; }
    .pricing-feedback.error  { color: var(--danger); }
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
</style>

<div x-data="{
    customerId: '',
    customerName: '',
    loading: false,
    countries: [],
    prices: {},
    states: {},
    countryFilter: '',
    scopeSearch: '',

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
    },

    onCustomerChange(e) {
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
                this.states[scope.id] = { original: scope.custom_price ?? '', saving: false, saved: false, error: false };
            })));
        } finally { this.loading = false; }
    },

    isChanged(id) {
        return this.states[id] && String(this.prices[id]) !== String(this.states[id].original);
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

    async savePrice(scope) {
        const price = this.prices[scope.id];
        if (price === '' || price === null || price === undefined) return;
        this.states[scope.id] = { ...this.states[scope.id], saving: true, error: false };
        try {
            const res = await fetch(scope.save_url, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ price }),
            });
            if (!res.ok) throw new Error();
            const data = await res.json();
            this.prices[scope.id] = data.price;
            this.states[scope.id] = { original: data.price, saving: false, saved: true, error: false };
            setTimeout(() => { this.states[scope.id] = { ...this.states[scope.id], saved: false }; }, 2000);
        } catch {
            this.states[scope.id] = { ...this.states[scope.id], saving: false, error: true };
            setTimeout(() => { this.states[scope.id] = { ...this.states[scope.id], error: false }; }, 3000);
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
                        <div class="pricing-row">
                            <span class="pricing-name" x-text="scope.name"></span>

                            <span class="pricing-default">
                                <span x-show="scope.price_on_request">Price on request</span>
                                <span x-show="!scope.price_on_request" x-text="'Default: ' + country.currency + ' ' + scope.default_price"></span>
                            </span>

                            <div class="pricing-input-wrap">
                                <span class="pricing-prefix" x-text="country.currency"></span>
                                <input type="number" step="0.01" min="0"
                                       :value="prices[scope.id]"
                                       @input="prices[scope.id] = $event.target.value"
                                       @keydown.enter.prevent="savePrice(scope)"
                                       :placeholder="scope.price_on_request ? 'Enter price' : scope.default_price"
                                       class="pricing-input"
                                       :class="{ 'is-changed': isChanged(scope.id) }">

                                <button type="button"
                                        x-show="isChanged(scope.id) && !states[scope.id]?.saving"
                                        x-transition
                                        @click="savePrice(scope)"
                                        class="pricing-save-btn">
                                    Save
                                </button>

                                <span x-show="states[scope.id]?.saving" class="pricing-feedback saving">
                                    <svg style="width:11px;height:11px;" class="animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="32" stroke-dashoffset="12"/></svg>
                                    Saving…
                                </span>

                                <span x-show="states[scope.id]?.saved" x-transition class="pricing-feedback saved">
                                    <svg style="width:12px;height:12px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                                    Saved
                                </span>

                                <span x-show="states[scope.id]?.error" x-transition class="pricing-feedback error">Failed — try again</span>

                                <span x-show="!isChanged(scope.id) && states[scope.id]?.original && !states[scope.id]?.saved"
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

</div>
@endsection
