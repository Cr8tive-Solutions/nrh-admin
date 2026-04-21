@extends('layouts.admin')

@section('title', 'New Invoice')
@section('page-title', 'New Invoice')

@section('header-actions')
    <a href="{{ route('invoices.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Cancel</a>
@endsection

@section('content')
<div x-data="{
    items: [{ description: '', qty: 1, unit_price: '' }],
    get subtotal() { return this.items.reduce((s, i) => s + (parseFloat(i.qty) * parseFloat(i.unit_price) || 0), 0); },
    get tax()      { return this.subtotal * 0.06; },
    get total()    { return this.subtotal + this.tax; },
    addItem()      { this.items.push({ description: '', qty: 1, unit_price: '' }); },
    removeItem(i)  { if (this.items.length > 1) this.items.splice(i, 1); }
}">

<div class="grid grid-cols-3 gap-6">

    <div class="col-span-2 space-y-4">

        {{-- Header --}}
        <div class="bg-white rounded-lg border border-gray-200 p-5">
            <h2 class="text-sm font-semibold text-gray-900 mb-4">Invoice Details</h2>
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Customer <span class="text-red-500">*</span></label>
                    <select name="customer_id" form="invoice-form" required
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Select customer…</option>
                        @foreach($customers as $c)
                        <option value="{{ $c->id }}" {{ old('customer_id', request('customer_id')) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Period <span class="text-red-500">*</span></label>
                    <input type="text" name="period" form="invoice-form" value="{{ old('period') }}"
                           placeholder="e.g. April 2026" required
                           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Issue Date <span class="text-red-500">*</span></label>
                    <input type="date" name="issued_at" form="invoice-form" value="{{ old('issued_at', now()->format('Y-m-d')) }}" required
                           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Due Date <span class="text-red-500">*</span></label>
                    <input type="date" name="due_at" form="invoice-form" value="{{ old('due_at', now()->addDays(30)->format('Y-m-d')) }}" required
                           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        {{-- Line items --}}
        <div class="bg-white rounded-lg border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-gray-900">Line Items</h2>
                <button type="button" @click="addItem()" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                    + Add Item
                </button>
            </div>

            <form id="invoice-form" method="POST" action="{{ route('invoices.store') }}">
                @csrf

                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs text-gray-500 uppercase border-b border-gray-100">
                            <th class="pb-2 text-left font-medium">Description</th>
                            <th class="pb-2 text-left font-medium w-20">Qty</th>
                            <th class="pb-2 text-left font-medium w-28">Unit Price</th>
                            <th class="pb-2 text-right font-medium w-28">Total</th>
                            <th class="pb-2 w-8"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, index) in items" :key="index">
                            <tr class="border-b border-gray-50">
                                <td class="py-2 pr-2">
                                    <input type="text" :name="`items[${index}][description]`" x-model="item.description"
                                           placeholder="Description" required
                                           class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                </td>
                                <td class="py-2 pr-2">
                                    <input type="number" :name="`items[${index}][qty]`" x-model.number="item.qty"
                                           min="1" required
                                           class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500 text-center">
                                </td>
                                <td class="py-2 pr-2">
                                    <input type="number" :name="`items[${index}][unit_price]`" x-model="item.unit_price"
                                           step="0.01" min="0" placeholder="0.00" required
                                           class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500 text-right">
                                </td>
                                <td class="py-2 pr-2 text-right font-mono text-xs" x-text="(item.qty * parseFloat(item.unit_price) || 0).toFixed(2)"></td>
                                <td class="py-2">
                                    <button type="button" @click="removeItem(index)" class="text-gray-300 hover:text-red-500 transition-colors">✕</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </form>
        </div>

    </div>

    {{-- Right: Summary --}}
    <div class="space-y-4">
        <div class="bg-white rounded-lg border border-gray-200 p-5">
            <h2 class="text-sm font-semibold text-gray-900 mb-4">Summary</h2>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Subtotal</span>
                    <span class="font-mono" x-text="'MYR ' + subtotal.toFixed(2)"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">SST (6%)</span>
                    <span class="font-mono" x-text="'MYR ' + tax.toFixed(2)"></span>
                </div>
                <div class="border-t border-gray-200 pt-2 flex justify-between font-semibold">
                    <span>Total</span>
                    <span class="font-mono text-base" x-text="'MYR ' + total.toFixed(2)"></span>
                </div>
            </div>

            <button type="submit" form="invoice-form"
                    class="mt-5 w-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium py-2.5 rounded-md transition-colors">
                Create Invoice
            </button>
        </div>
    </div>

</div>
</div>
@endsection
