@extends('layouts.admin')

@section('title', 'Edit ' . $invoice->number)
@section('page-title', 'Edit Invoice')
@section('page-subtitle', $invoice->number . ' · ' . $invoice->customer->name)

@section('header-actions')
    <a href="{{ route('invoices.show', $invoice) }}" class="nrh-btn nrh-btn-ghost" style="font-size:12px;">← Cancel</a>
@endsection

@section('content')
<div x-data="{
    items: @json($invoice->items->map(fn($i) => ['description' => $i->description, 'qty' => $i->qty, 'unit_price' => (string)$i->unit_price])->values()),
    autoRequestIds: @json($linkedRequestIds),
    autoLoading: false,
    autoError: '',
    get subtotal() { return this.items.reduce((s, i) => s + (parseFloat(i.qty) * parseFloat(i.unit_price) || 0), 0); },
    get tax()      { return this.subtotal * 0.06; },
    get total()    { return this.subtotal + this.tax; },
    addItem()      { this.items.push({ description: '', qty: 1, unit_price: '' }); },
    removeItem(i)  { if (this.items.length > 1) this.items.splice(i, 1); },
    async autoGenerate() {
        const periodStart = document.querySelector('[name=period_start]').value;
        const periodEnd   = document.querySelector('[name=period_end]').value;
        if (!periodStart || !periodEnd) { this.autoError = 'Set the date range first.'; return; }
        this.autoLoading = true; this.autoError = '';
        try {
            const url = '{{ route('invoices.preview-items') }}?customer_id={{ $invoice->customer_id }}&period_start=' + encodeURIComponent(periodStart) + '&period_end=' + encodeURIComponent(periodEnd);
            const res  = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            const json = await res.json();
            if (!res.ok) { this.autoError = json.error || 'Server error.'; return; }
            if (!json.items.length) { this.autoError = 'No uninvoiced requests found for that period.'; return; }
            this.items = json.items;
            this.autoRequestIds = json.requests.map(r => r.id);
        } catch(e) {
            this.autoError = 'Request failed.';
        } finally {
            this.autoLoading = false;
        }
    }
}" style="display:grid; grid-template-columns:1fr 280px; gap:20px; align-items:start;">

    {{-- Left column --}}
    <div style="display:flex; flex-direction:column; gap:16px;">

        {{-- Invoice details --}}
        <div class="nrh-card">
            <div class="nrh-card-head">
                <h3>Invoice Details</h3>
            </div>
            <div style="padding:20px; display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div style="grid-column:span 2;">
                    <label style="display:block; font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:0.14em; color:var(--ink-500); margin-bottom:6px;">Customer</label>
                    <div class="nrh-input" style="background:var(--paper); color:var(--ink-600); cursor:default;">{{ $invoice->customer->name }}</div>
                </div>
                <div>
                    <label style="display:block; font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:0.14em; color:var(--ink-500); margin-bottom:6px;">
                        Period From <span style="color:var(--danger);">*</span>
                    </label>
                    <input type="date" name="period_start" form="invoice-form"
                           value="{{ old('period_start', $invoice->period_date?->format('Y-m-d') ?? now()->startOfMonth()->format('Y-m-d')) }}"
                           required class="nrh-input">
                    @error('period_start')<p style="margin-top:4px;font-size:11px;color:var(--danger);">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label style="display:block; font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:0.14em; color:var(--ink-500); margin-bottom:6px;">
                        Period To <span style="color:var(--danger);">*</span>
                    </label>
                    <input type="date" name="period_end" form="invoice-form"
                           value="{{ old('period_end', $invoice->period_end?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                           required class="nrh-input">
                    @error('period_end')<p style="margin-top:4px;font-size:11px;color:var(--danger);">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label style="display:block; font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:0.14em; color:var(--ink-500); margin-bottom:6px;">
                        Issue Date <span style="color:var(--danger);">*</span>
                    </label>
                    <input type="date" name="issued_at" form="invoice-form"
                           value="{{ old('issued_at', $invoice->issued_at->format('Y-m-d')) }}" required class="nrh-input">
                </div>
                <div>
                    <label style="display:block; font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:0.14em; color:var(--ink-500); margin-bottom:6px;">
                        Due Date <span style="color:var(--danger);">*</span>
                    </label>
                    <input type="date" name="due_at" form="invoice-form"
                           value="{{ old('due_at', $invoice->due_at->format('Y-m-d')) }}" required class="nrh-input">
                </div>
            </div>
        </div>

        {{-- Line items --}}
        <div class="nrh-card">
            <div class="nrh-card-head">
                <h3>Line Items</h3>
                <div style="display:flex;gap:8px;align-items:center;">
                    <button type="button" @click="autoGenerate()" :disabled="autoLoading"
                            style="font-size:12px; font-weight:600; color:var(--emerald-700); background:rgba(4,108,78,0.06); border:1px solid rgba(4,108,78,0.2); cursor:pointer; padding:4px 10px; border-radius:4px; transition:background 120ms; display:flex;align-items:center;gap:5px;"
                            onmouseover="this.style.background='rgba(4,108,78,0.12)'"
                            onmouseout="this.style.background='rgba(4,108,78,0.06)'">
                        <svg x-show="!autoLoading" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <svg x-show="autoLoading" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:spin 1s linear infinite;"><circle cx="12" cy="12" r="10" stroke-dasharray="60" stroke-dashoffset="40"/></svg>
                        <span x-text="autoLoading ? 'Loading…' : 'Re-generate from requests'"></span>
                    </button>
                    <button type="button" @click="addItem()"
                            style="font-size:12px; font-weight:600; color:var(--emerald-700); background:none; border:none; cursor:pointer; padding:4px 8px; border-radius:4px; transition:background 120ms;"
                            onmouseover="this.style.background='rgba(4,108,78,0.08)'"
                            onmouseout="this.style.background='none'">
                        + Add item
                    </button>
                </div>
            </div>

            <div x-show="autoError" x-text="autoError"
                 style="margin:12px 20px 0; padding:8px 12px; background:#fef2f2; border:1px solid #fecaca; border-radius:6px; font-size:12px; color:#b91c1c; display:none;"></div>
            <div x-show="autoRequestIds.length > 0"
                 style="margin:12px 20px 0; padding:8px 12px; background:rgba(4,108,78,0.05); border:1px solid rgba(4,108,78,0.15); border-radius:6px; font-size:12px; color:var(--ink-500); display:none;">
                <span style="font-weight:600; color:var(--emerald-700);" x-text="autoRequestIds.length + ' request(s) linked'"></span>
                — will be saved when you update.
                <button type="button" @click="autoRequestIds=[]"
                        style="margin-left:8px; font-size:11px; color:var(--ink-400); background:none; border:none; cursor:pointer; text-decoration:underline;">unlink all</button>
            </div>

            <form id="invoice-form" method="POST" action="{{ route('invoices.update', $invoice) }}">
                @csrf @method('PUT')
                <input type="hidden" name="request_ids" :value="JSON.stringify(autoRequestIds)">
                <table class="nrh-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th style="width:80px;">Qty</th>
                            <th style="width:130px;">Unit Price</th>
                            <th style="width:120px; text-align:right;">Line Total</th>
                            <th style="width:36px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, index) in items" :key="index">
                            <tr>
                                <td style="padding:10px 20px;">
                                    <input type="text" :name="`items[${index}][description]`"
                                           x-model="item.description" placeholder="Description" required
                                           class="nrh-input" style="padding:7px 10px;">
                                </td>
                                <td style="padding:10px 8px 10px 20px;">
                                    <input type="number" :name="`items[${index}][qty]`"
                                           x-model.number="item.qty" min="1" required
                                           class="nrh-input" style="padding:7px 10px; text-align:center;">
                                </td>
                                <td style="padding:10px 8px;">
                                    <div style="display:flex; align-items:center; gap:6px;">
                                        <span style="font-size:11px; color:var(--ink-500); white-space:nowrap;">MYR</span>
                                        <input type="number" :name="`items[${index}][unit_price]`"
                                               x-model="item.unit_price" step="0.01" min="0"
                                               placeholder="0.00" required
                                               class="nrh-input" style="padding:7px 10px; text-align:right;">
                                    </div>
                                </td>
                                <td style="padding:10px 20px 10px 8px; text-align:right; font-family:'JetBrains Mono',monospace; font-size:12px; color:var(--ink-700);"
                                    x-text="'MYR ' + (item.qty * parseFloat(item.unit_price) || 0).toFixed(2)"></td>
                                <td style="padding:10px 12px 10px 0; text-align:center;">
                                    <button type="button" @click="removeItem(index)"
                                            style="width:24px; height:24px; display:grid; place-items:center; border-radius:4px; background:none; border:none; cursor:pointer; color:var(--ink-300); transition:color 120ms, background 120ms; font-size:14px;"
                                            onmouseover="this.style.color='var(--danger)';this.style.background='rgba(196,69,58,0.08)'"
                                            onmouseout="this.style.color='var(--ink-300)';this.style.background='none'">✕</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </form>
        </div>

    </div>

    {{-- Right: Summary --}}
    <div style="position:sticky; top:80px; display:flex; flex-direction:column; gap:12px;">
        <div class="nrh-card">
            <div class="nrh-card-head"><h3>Summary</h3></div>
            <div style="padding:16px 20px; display:flex; flex-direction:column; gap:10px;">
                <div style="display:flex; justify-content:space-between; align-items:center; font-size:13px;">
                    <span style="color:var(--ink-500);">Subtotal</span>
                    <span style="font-family:'JetBrains Mono',monospace; font-size:12px;" x-text="'MYR ' + subtotal.toFixed(2)"></span>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; font-size:13px;">
                    <span style="color:var(--ink-500);">SST (6%)</span>
                    <span style="font-family:'JetBrains Mono',monospace; font-size:12px;" x-text="'MYR ' + tax.toFixed(2)"></span>
                </div>
                <div style="border-top:1px solid var(--line); padding-top:10px; display:flex; justify-content:space-between; align-items:center;">
                    <span style="font-size:13px; font-weight:600; color:var(--ink-900);">Total</span>
                    <span style="font-family:'JetBrains Mono',monospace; font-size:15px; font-weight:700; color:var(--emerald-700);" x-text="'MYR ' + total.toFixed(2)"></span>
                </div>
            </div>
            <div style="padding:0 16px 16px; display:flex; flex-direction:column; gap:8px;">
                <button type="submit" form="invoice-form" class="nrh-btn nrh-btn-primary" style="width:100%; justify-content:center;">
                    Save Changes
                </button>
                <a href="{{ route('invoices.show', $invoice) }}"
                   style="display:block; text-align:center; font-size:12px; color:var(--ink-400); padding:4px 0;">Cancel</a>
            </div>
        </div>

        <div style="padding:12px 14px; border-radius:8px; background:#fef3c7; border:1px solid #fde68a; font-size:11px; color:#78350f; line-height:1.6;">
            Editing will replace all line items.<br>
            Request links will be updated to match the new selection.
        </div>
    </div>

</div>
<style>@keyframes spin { to { transform: rotate(360deg); } }</style>
@endsection
