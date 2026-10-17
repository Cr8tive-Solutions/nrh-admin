{{--
    Shared create/edit form for a customer scope package.

    Expects: $customer, $countries, $scopesByCountry, $action, $submitLabel,
             and optionally $package (edit mode).
--}}
@php
    $package = $package ?? null;
    $from = $from ?? null;
    $selectedCountry = (int) old('country_id', $package?->country_id ?? ($countries->first()->id ?? 0));
    $selectedScopes = array_map('intval', old('scope_type_ids', $package ? $package->scopeTypes->pluck('id')->all() : []));
    $cancelUrl = $from === 'packages'
        ? route('packages.index', ['customer_id' => $customer->id])
        : route('customers.show', $customer);
@endphp

<div class="bg-white rounded-lg border border-gray-200 p-6 max-w-2xl"
     x-data="{
         countryId: {{ $selectedCountry }},
         price: '{{ old('price', $package?->price !== null ? number_format((float) $package->price, 2, '.', '') : '') }}',
         selected: {{ json_encode($selectedScopes) }},
         {{-- Cast to object so a country-id-keyed map never encodes as a JS array. --}}
         scopes: {{ json_encode((object) $scopesByCountry->all()) }},
         currencies: {{ json_encode((object) $countries->pluck('currency', 'id')->all()) }},

         get visibleScopes() { return this.scopes[this.countryId] ?? []; },
         get currency() { return this.currencies[this.countryId] ?? 'MYR'; },

         /* Scopes are country-scoped, so changing country clears the selection. */
         onCountryChange(id) {
             this.countryId = parseInt(id);
             this.selected = this.selected.filter(
                 sid => this.visibleScopes.some(s => s.id === sid)
             );
         },

         toggle(id) {
             const i = this.selected.indexOf(id);
             if (i === -1) { this.selected.push(id); } else { this.selected.splice(i, 1); }
         },

         isSelected(id) { return this.selected.includes(id); },

         get selectedScopeObjects() {
             return this.visibleScopes.filter(s => this.isSelected(s.id));
         },

         /* Mirrors Package::effectiveTotal() — null when a scope is unpriced. */
         get total() {
             const chosen = this.selectedScopeObjects;
             if (chosen.some(s => s.unpriced)) { return null; }
             return chosen.reduce((sum, s) => sum + s.price, 0);
         },

         get unpricedCount() {
             return this.selectedScopeObjects.filter(s => s.unpriced).length;
         },
     }">

    <form method="POST" action="{{ $action }}" class="space-y-5">
        @csrf
        @if($package)
            @method('PUT')
        @endif
        {{-- Carries the originating listing through so we redirect back to it. --}}
        @if(($from ?? null) === 'packages')
            <input type="hidden" name="from" value="packages">
        @endif

        @if($errors->any())
        <div class="rounded-md border border-red-200 bg-red-50 px-3 py-3">
            <ul class="text-sm text-red-700 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Package Name <span class="text-red-500">*</span>
            </label>
            <input type="text" name="name" value="{{ old('name', $package?->name) }}" required
                   maxlength="255" placeholder="e.g. Standard Screening"
                   class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600">
            <p class="text-xs text-gray-400 mt-1">Shown to {{ $customer->name }} in the request wizard.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Package Price <span class="text-red-500">*</span>
            </label>
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-500 font-mono" x-text="currency"></span>
                <input type="number" name="price" step="0.01" min="0" required
                       x-model="price" placeholder="0.00"
                       class="w-40 border border-gray-300 rounded-md px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-emerald-600">
                <button type="button" x-show="total !== null && selected.length > 0"
                        @click="price = total.toFixed(2)"
                        class="text-xs text-emerald-700 font-medium hover:underline">
                    Use scopes total (<span x-text="currency"></span> <span x-text="(total ?? 0).toFixed(2)"></span>)
                </button>
            </div>
            <p class="text-xs text-gray-400 mt-1">
                The fixed price {{ $customer->name }} pays for this bundle — this is what gets billed (before 6% SST),
                overriding the sum of the individual scope prices. Charged per candidate.
            </p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Country <span class="text-red-500">*</span>
            </label>
            <select name="country_id" required
                    @change="onCountryChange($event.target.value)"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600">
                @foreach($countries as $country)
                <option value="{{ $country->id }}" {{ $selectedCountry === $country->id ? 'selected' : '' }}>
                    {{ $country->flag }} {{ $country->name }}
                </option>
                @endforeach
            </select>
            <p class="text-xs text-gray-400 mt-1">
                A package bundles scopes from one country only — the wizard filters packages by the country the client picks.
            </p>
        </div>

        <div>
            <div class="flex items-center justify-between mb-2">
                <label class="block text-sm font-medium text-gray-700">
                    Scopes Included <span class="text-red-500">*</span>
                </label>
                <span class="text-xs text-gray-500 font-mono">
                    <span x-text="selected.length"></span> selected
                </span>
            </div>

            <template x-if="visibleScopes.length === 0">
                <p class="text-sm text-gray-500 border border-dashed border-gray-300 rounded-md px-3 py-4 text-center">
                    No scope types configured for this country yet.
                </p>
            </template>

            <div class="border border-gray-200 rounded-md divide-y divide-gray-100 max-h-96 overflow-y-auto"
                 x-show="visibleScopes.length > 0">
                <template x-for="scope in visibleScopes" :key="scope.id">
                    <label class="flex items-start gap-3 px-3 py-2.5 cursor-pointer hover:bg-gray-50">
                        <input type="checkbox" name="scope_type_ids[]" :value="scope.id"
                               :checked="isSelected(scope.id)" @change="toggle(scope.id)"
                               class="accent-emerald-700 w-4 h-4 mt-0.5 flex-shrink-0">
                        <span class="flex-1 min-w-0">
                            <span class="block text-sm text-gray-900" x-text="scope.name"></span>
                            <span class="block text-xs text-gray-500 mt-0.5">
                                <span x-show="scope.category" x-text="scope.category"></span>
                                <span x-show="scope.category && scope.turnaround"> · </span>
                                <span x-show="scope.turnaround" x-text="scope.turnaround"></span>
                            </span>
                        </span>
                        <span class="text-xs font-mono text-gray-700 flex-shrink-0 text-right">
                            <template x-if="scope.unpriced">
                                <span class="text-amber-700">On request</span>
                            </template>
                            <template x-if="!scope.unpriced">
                                <span>
                                    <span x-text="currency"></span>
                                    <span x-text="scope.price.toFixed(2)"></span>
                                    <span x-show="scope.has_override" class="text-emerald-700" title="Per-customer price override">*</span>
                                </span>
                            </template>
                        </span>
                    </label>
                </template>
            </div>

            <p class="text-xs text-gray-400 mt-1">
                <span class="text-emerald-700">*</span> customer-specific price override. Prices come from
                <a href="{{ route('pricing.index') }}" class="text-emerald-700 hover:underline">Scope Pricing</a>.
            </p>
        </div>

        {{-- Reference only: the sum of the individual scope prices. The customer
             is billed the Package Price above, not this figure. --}}
        <div class="rounded-md border border-gray-200 bg-gray-50 px-3 py-3" x-show="selected.length > 0">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-700">Scopes total <span class="text-gray-400 font-normal">(reference)</span></span>
                <span class="text-sm font-mono font-semibold text-gray-900">
                    <template x-if="total !== null">
                        <span><span x-text="currency"></span> <span x-text="total.toFixed(2)"></span></span>
                    </template>
                    <template x-if="total === null">
                        <span class="text-amber-700">—</span>
                    </template>
                </span>
            </div>
            <p class="text-xs text-gray-500 mt-2">
                Sum of the selected scopes at this customer's prices, for comparison. The bundle is billed at the
                <strong>Package Price</strong> set above regardless.
            </p>
            <p class="text-xs text-amber-700 mt-2" x-show="unpricedCount > 0">
                <span x-text="unpricedCount"></span> selected scope(s) are price-on-request with no per-scope override —
                that's fine here: the fixed package price covers them.
            </p>
        </div>

        <div class="flex gap-3 pt-1">
            <button type="submit" class="nrh-btn nrh-btn-primary" :disabled="selected.length === 0">
                {{ $submitLabel }}
            </button>
            <a href="{{ $cancelUrl }}" class="text-sm text-gray-500 hover:text-gray-700 self-center">
                Cancel
            </a>
        </div>
    </form>
</div>
