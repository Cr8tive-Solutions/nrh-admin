@extends('layouts.admin')

@section('title', 'New Invoice')
@section('page-title', 'New Invoice')

@section('header-actions')
    <a href="{{ route('invoices.index') }}" class="nrh-btn nrh-btn-ghost" style="font-size:12px;">← Cancel</a>
@endsection

@section('content')
<div x-data="{
    items: [{ description: '', qty: 1, unit_price: '' }],
    get subtotal() { return this.items.reduce((s, i) => s + (parseFloat(i.qty) * parseFloat(i.unit_price) || 0), 0); },
    get tax()      { return this.subtotal * 0.06; },
    get total()    { return this.subtotal + this.tax; },
    addItem()      { this.items.push({ description: '', qty: 1, unit_price: '' }); },
    removeItem(i)  { if (this.items.length > 1) this.items.splice(i, 1); }
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
                    <label style="display:block; font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:0.14em; color:var(--ink-500); margin-bottom:6px;">
                        Customer <span style="color:var(--danger);">*</span>
                    </label>
                    <select name="customer_id" form="invoice-form" required class="nrh-input">
                        <option value="">Select customer…</option>
                        @foreach($customers as $c)
                        <option value="{{ $c->id }}" {{ old('customer_id', request('customer_id')) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block; font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:0.14em; color:var(--ink-500); margin-bottom:6px;">
                        Period <span style="color:var(--danger);">*</span>
                    </label>
                    <input type="text" name="period" form="invoice-form" value="{{ old('period') }}"
                           placeholder="e.g. April 2026" required class="nrh-input">
                </div>
                <div>
                    <label style="display:block; font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:0.14em; color:var(--ink-500); margin-bottom:6px;">
                        Issue Date <span style="color:var(--danger);">*</span>
                    </label>
                    <input type="date" name="issued_at" form="invoice-form"
                           value="{{ old('issued_at', now()->format('Y-m-d')) }}" required class="nrh-input">
                </div>
                <div>
                    <label style="display:block; font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:0.14em; color:var(--ink-500); margin-bottom:6px;">
                        Due Date <span style="color:var(--danger);">*</span>
                    </label>
                    <input type="date" name="due_at" form="invoice-form"
                           value="{{ old('due_at', now()->addDays(30)->format('Y-m-d')) }}" required class="nrh-input">
                </div>
            </div>
        </div>

        {{-- Line items --}}
        <div class="nrh-card">
            <div class="nrh-card-head">
                <h3>Line Items</h3>
                <button type="button" @click="addItem()"
                        style="font-size:12px; font-weight:600; color:var(--emerald-700); background:none; border:none; cursor:pointer; padding:4px 8px; border-radius:4px; transition:background 120ms;"
                        onmouseover="this.style.background='rgba(4,108,78,0.08)'"
                        onmouseout="this.style.background='none'">
                    + Add item
                </button>
            </div>

            <form id="invoice-form" method="POST" action="{{ route('invoices.store') }}">
                @csrf
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
            <div class="nrh-card-head">
                <h3>Summary</h3>
            </div>
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
            <div style="padding:0 16px 16px;">
                <button type="submit" form="invoice-form" class="nrh-btn nrh-btn-primary" style="width:100%; justify-content:center;">
                    Create Invoice
                </button>
            </div>
        </div>

        <div style="padding:12px 14px; border-radius:8px; background:rgba(4,108,78,0.05); border:1px solid rgba(4,108,78,0.12); font-size:11px; color:var(--ink-500); line-height:1.6;">
            Invoice number will be auto-generated.<br>
            SST at 6% applied to subtotal.
        </div>
    </div>

</div>
@endsection
