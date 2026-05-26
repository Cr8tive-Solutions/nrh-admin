@extends('layouts.admin')

@section('title', 'Generate Monthly Invoices')
@section('page-title', 'Generate Monthly Invoices')

@section('header-actions')
    <a href="{{ route('invoices.index') }}" class="nrh-btn nrh-btn-ghost" style="font-size:12px;">← Cancel</a>
@endsection

@section('content')
<div x-data="{
    periodStart: '{{ now()->startOfMonth()->format('Y-m-d') }}',
    periodEnd: '{{ now()->format('Y-m-d') }}',
    issuedAt: '{{ now()->format('Y-m-d') }}',
    dueAt: '{{ now()->addDays(30)->format('Y-m-d') }}',
    loading: false,
    generating: false,
    error: '',
    success: '',
    customers: [],
    skipped: [],

    get selectedCount()  { return this.customers.filter(c => c.selected).length; },
    get grandTotal()     { return this.customers.filter(c => c.selected).reduce((s, c) => s + c.total, 0); },

    async preview() {
        if (!this.periodStart || !this.periodEnd) { this.error = 'Set the date range first.'; return; }
        this.loading = true; this.error = ''; this.customers = []; this.skipped = [];
        try {
            const res  = await fetch('{{ route('invoices.bulk-preview') }}?period_start=' + encodeURIComponent(this.periodStart) + '&period_end=' + encodeURIComponent(this.periodEnd), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            if (!res.ok) { this.error = json.error || 'Server error.'; return; }
            this.skipped = json.skipped || [];
            if (!json.customers.length && !this.skipped.length) { this.error = 'No uninvoiced activity found for any monthly customer in that period.'; return; }
            if (!json.customers.length && this.skipped.length) { this.error = 'All monthly customers already have an invoice for this period.'; return; }
            this.customers = json.customers.map(c => ({ ...c, selected: true, expanded: false }));
        } catch(e) {
            this.error = 'Request failed.';
        } finally {
            this.loading = false;
        }
    },

    async generate() {
        const selected = this.customers.filter(c => c.selected);
        if (!selected.length) { this.error = 'Select at least one customer.'; return; }
        if (!this.issuedAt || !this.dueAt) { this.error = 'Set issue date and due date.'; return; }
        this.generating = true; this.error = '';
        try {
            const res  = await fetch('{{ route('invoices.bulk-store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                },
                body: JSON.stringify({
                    period_start: this.periodStart,
                    period_end:   this.periodEnd,
                    issued_at:    this.issuedAt,
                    due_at:       this.dueAt,
                    customers:  selected.map(c => ({
                        customer_id:  c.customer_id,
                        items:        c.items,
                        request_ids:  c.request_ids
                    }))
                })
            });
            const json = await res.json();
            if (!res.ok) { this.error = json.message || 'Failed to create invoices.'; return; }
            window.location.href = json.redirect_url + '?success=' + json.created + '+invoice(s)+created';
        } catch(e) {
            this.error = 'Request failed.';
        } finally {
            this.generating = false;
        }
    }
}" style="display:grid; grid-template-columns:1fr 280px; gap:20px; align-items:start;">

    {{-- Left column --}}
    <div style="display:flex; flex-direction:column; gap:16px;">

        {{-- Step 1: Period + Preview --}}
        <div class="nrh-card">
            <div class="nrh-card-head"><h3>Step 1 — Select Period</h3></div>
            <div style="padding:20px; display:grid; grid-template-columns:1fr 1fr 1fr 1fr; gap:16px; align-items:end;">
                <div>
                    <label style="display:block; font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:0.14em; color:var(--ink-500); margin-bottom:6px;">
                        Period From <span style="color:var(--danger);">*</span>
                    </label>
                    <input type="date" x-model="periodStart" class="nrh-input">
                </div>
                <div>
                    <label style="display:block; font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:0.14em; color:var(--ink-500); margin-bottom:6px;">
                        Period To <span style="color:var(--danger);">*</span>
                    </label>
                    <input type="date" x-model="periodEnd" class="nrh-input">
                </div>
                <div>
                    <label style="display:block; font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:0.14em; color:var(--ink-500); margin-bottom:6px;">Issue Date</label>
                    <input type="date" x-model="issuedAt" class="nrh-input">
                </div>
                <div>
                    <label style="display:block; font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:0.14em; color:var(--ink-500); margin-bottom:6px;">Due Date</label>
                    <input type="date" x-model="dueAt" class="nrh-input">
                </div>
            </div>
            <div style="padding:0 20px 20px;">
                <button type="button" @click="preview()" :disabled="loading"
                        class="nrh-btn nrh-btn-primary" style="display:flex; align-items:center; gap:6px;">
                    <svg x-show="loading" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:spin 1s linear infinite;"><circle cx="12" cy="12" r="10" stroke-dasharray="60" stroke-dashoffset="40"/></svg>
                    <span x-text="loading ? 'Loading…' : 'Preview invoices'"></span>
                </button>
            </div>
        </div>

        {{-- Error --}}
        <div x-show="error" x-text="error" style="display:none; padding:10px 14px; background:#fef2f2; border:1px solid #fecaca; border-radius:6px; font-size:12px; color:#b91c1c;"></div>

        {{-- Step 2: Customer table --}}
        <div x-show="customers.length > 0" style="display:none;" class="nrh-card">
            <div class="nrh-card-head">
                <h3>Step 2 — Review &amp; Confirm</h3>
                <div style="display:flex; gap:10px; align-items:center; font-size:12px; color:var(--ink-500);">
                    <button type="button" @click="customers.forEach(c => c.selected = true)"
                            style="color:var(--emerald-700); background:none; border:none; cursor:pointer; font-size:12px; font-weight:600;">Select all</button>
                    <span style="color:var(--ink-200);">|</span>
                    <button type="button" @click="customers.forEach(c => c.selected = false)"
                            style="color:var(--ink-400); background:none; border:none; cursor:pointer; font-size:12px;">Deselect all</button>
                </div>
            </div>
            <table class="nrh-table">
                <thead>
                    <tr>
                        <th style="width:36px;"></th>
                        <th>Customer</th>
                        <th style="text-align:center;">Requests</th>
                        <th style="text-align:center;">Line items</th>
                        <th style="text-align:right;">Subtotal</th>
                        <th style="text-align:right;">SST</th>
                        <th style="text-align:right;">Total</th>
                        <th style="width:36px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(c, i) in customers" :key="c.customer_id">
                        <template>
                            <tr :style="!c.selected ? 'opacity:0.45' : ''">
                                <td style="padding:10px 12px; text-align:center;">
                                    <input type="checkbox" x-model="c.selected"
                                           style="width:15px; height:15px; cursor:pointer; accent-color:var(--emerald-700);">
                                </td>
                                <td style="padding:10px 20px; font-weight:600; color:var(--ink-900);" x-text="c.customer_name"></td>
                                <td style="padding:10px; text-align:center; font-size:12px; color:var(--ink-500);" x-text="c.request_ids.length"></td>
                                <td style="padding:10px; text-align:center; font-size:12px; color:var(--ink-500);" x-text="c.items.length"></td>
                                <td style="padding:10px 20px; text-align:right; font-family:'JetBrains Mono',monospace; font-size:12px;" x-text="'MYR ' + c.subtotal.toFixed(2)"></td>
                                <td style="padding:10px 20px; text-align:right; font-family:'JetBrains Mono',monospace; font-size:12px; color:var(--ink-500);" x-text="'MYR ' + c.tax.toFixed(2)"></td>
                                <td style="padding:10px 20px; text-align:right; font-family:'JetBrains Mono',monospace; font-size:13px; font-weight:700; color:var(--emerald-700);" x-text="'MYR ' + c.total.toFixed(2)"></td>
                                <td style="padding:10px 8px; text-align:center;">
                                    <button type="button" @click="c.expanded = !c.expanded"
                                            style="width:22px; height:22px; display:grid; place-items:center; background:none; border:none; cursor:pointer; color:var(--ink-400);"
                                            :title="c.expanded ? 'Collapse' : 'View line items'">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                             :style="c.expanded ? 'transform:rotate(180deg)' : ''"><path d="M6 9l6 6 6-6"/></svg>
                                    </button>
                                </td>
                            </tr>
                            {{-- Expanded line items --}}
                            <tr x-show="c.expanded" style="display:none;">
                                <td></td>
                                <td colspan="7" style="padding:0 20px 12px;">
                                    <table style="width:100%; border-collapse:collapse; font-size:11px;">
                                        <thead>
                                            <tr style="border-bottom:1px solid var(--line);">
                                                <th style="padding:4px 8px 4px 0; text-align:left; color:var(--ink-400); font-weight:600; text-transform:uppercase; letter-spacing:0.1em;">Description</th>
                                                <th style="padding:4px 8px; text-align:center; color:var(--ink-400); font-weight:600; text-transform:uppercase; letter-spacing:0.1em; width:50px;">Qty</th>
                                                <th style="padding:4px 0 4px 8px; text-align:right; color:var(--ink-400); font-weight:600; text-transform:uppercase; letter-spacing:0.1em; width:100px;">Unit Price</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="item in c.items" :key="item.description">
                                                <tr style="border-bottom:1px solid var(--line);">
                                                    <td style="padding:5px 8px 5px 0; color:var(--ink-700);" x-text="item.description"></td>
                                                    <td style="padding:5px 8px; text-align:center; color:var(--ink-500);" x-text="item.qty"></td>
                                                    <td style="padding:5px 0 5px 8px; text-align:right; font-family:'JetBrains Mono',monospace; color:var(--ink-700);" x-text="'MYR ' + parseFloat(item.unit_price).toFixed(2)"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </template>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- Skipped customers (already invoiced for this period) --}}
        <div x-show="skipped.length > 0" style="display:none;" class="nrh-card">
            <div class="nrh-card-head">
                <h3 style="display:flex;align-items:center;gap:8px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                    <span>Already invoiced — skipped</span>
                </h3>
                <span style="font-size:11px; color:var(--ink-400);" x-text="skipped.length + ' customer(s)'"></span>
            </div>
            <div style="padding:4px 0 8px;">
                <template x-for="s in skipped" :key="s.customer_id">
                    <div style="display:flex; align-items:center; justify-content:space-between; padding:8px 20px; border-bottom:1px solid var(--line);">
                        <span style="font-size:13px; color:var(--ink-700);" x-text="s.customer_name"></span>
                        <a :href="'{{ url('/invoices') }}/' + s.invoice_id"
                           style="font-size:12px; font-family:'JetBrains Mono',monospace; color:var(--emerald-700); font-weight:600;"
                           x-text="s.invoice_number"></a>
                    </div>
                </template>
            </div>
        </div>

    </div>

    {{-- Right: Summary + Generate button --}}
    <div style="position:sticky; top:80px; display:flex; flex-direction:column; gap:12px;">
        <div class="nrh-card" x-show="customers.length > 0" style="display:none;">
            <div class="nrh-card-head"><h3>Summary</h3></div>
            <div style="padding:16px 20px; display:flex; flex-direction:column; gap:10px;">
                <div style="display:flex; justify-content:space-between; align-items:center; font-size:13px;">
                    <span style="color:var(--ink-500);">Customers</span>
                    <span x-text="selectedCount + ' of ' + customers.length"></span>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; font-size:13px;">
                    <span style="color:var(--ink-500);">From</span>
                    <span x-text="periodStart" style="font-family:'JetBrains Mono',monospace; font-size:12px;"></span>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; font-size:13px;">
                    <span style="color:var(--ink-500);">To</span>
                    <span x-text="periodEnd" style="font-family:'JetBrains Mono',monospace; font-size:12px;"></span>
                </div>
                <div style="border-top:1px solid var(--line); padding-top:10px; display:flex; justify-content:space-between; align-items:center;">
                    <span style="font-size:13px; font-weight:600; color:var(--ink-900);">Grand Total</span>
                    <span style="font-family:'JetBrains Mono',monospace; font-size:15px; font-weight:700; color:var(--emerald-700);" x-text="'MYR ' + grandTotal.toFixed(2)"></span>
                </div>
            </div>
            <div style="padding:0 16px 16px; display:flex; flex-direction:column; gap:8px;">
                <button type="button" @click="generate()" :disabled="generating || selectedCount === 0"
                        class="nrh-btn nrh-btn-primary" style="width:100%; justify-content:center; gap:6px;">
                    <svg x-show="generating" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:spin 1s linear infinite;"><circle cx="12" cy="12" r="10" stroke-dasharray="60" stroke-dashoffset="40"/></svg>
                    <span x-text="generating ? 'Creating…' : 'Generate ' + selectedCount + ' Invoice(s)'"></span>
                </button>
            </div>
        </div>

        <div style="padding:12px 14px; border-radius:8px; background:rgba(4,108,78,0.05); border:1px solid rgba(4,108,78,0.12); font-size:11px; color:var(--ink-500); line-height:1.6;">
            Only monthly-billed customers are shown.<br>
            Only requests without an invoice yet are included.<br>
            Each invoice links to the matched requests.
        </div>
    </div>

</div>
<style>@keyframes spin { to { transform: rotate(360deg); } }</style>
@endsection
