<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Customer;
use App\Models\Package;
use App\Models\ScopeType;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Per-customer scope packages.
 *
 * Two entry points, both backed by this controller:
 *   - /packages              — central page with a company picker (index)
 *   - /customers/{customer}  — the Packages tab on the customer detail page
 *
 * Authoring (create/update/destroy) is gated on `customer.manage`; the index is
 * read-only and open to any admin, matching Scope Pricing.
 */
class PackageController extends Controller
{
    /**
     * Pick a company to manage packages for. With no `customer_id` the page
     * lists every customer and how many packages each has, so the ones with
     * none are obvious at a glance.
     */
    public function index(Request $request)
    {
        $customers = Customer::orderBy('name')->get();

        if (! $request->filled('customer_id')) {
            return view('packages.index', [
                'customers' => $customers,
                'customer' => null,
                'packageCounts' => Package::selectRaw('customer_id, count(*) as total')
                    ->groupBy('customer_id')
                    ->pluck('total', 'customer_id'),
            ]);
        }

        $customer = Customer::findOrFail($request->input('customer_id'));
        $customer->load([
            'packages' => fn ($q) => $q->orderBy('country_id')->orderBy('name'),
            'packages.country',
            'packages.scopeTypes',
        ]);

        $scopePrices = $customer->scopePrices()->pluck('price', 'scope_type_id');

        return view('packages.index', [
            'customers' => $customers,
            'customer' => $customer,
            'packageCounts' => collect(),
            'scopePrices' => $scopePrices,
            'packageTotals' => $customer->packages->mapWithKeys(
                fn (Package $p) => [$p->id => $p->effectiveTotal($scopePrices)]
            ),
            'packageUnpriced' => $customer->packages->mapWithKeys(
                fn (Package $p) => [$p->id => $p->unpricedScopeTypes($scopePrices)->pluck('id')->all()]
            ),
        ]);
    }

    public function create(Request $request, Customer $customer)
    {
        return view('packages.create', [
            'customer' => $customer,
            'countries' => $this->countriesWithScopes(),
            'scopesByCountry' => $this->scopesByCountry($customer),
            'from' => $request->input('from'),
        ]);
    }

    public function store(Request $request, Customer $customer)
    {
        $data = $this->validated($request, $customer);

        DB::transaction(function () use ($customer, $data) {
            $package = $customer->packages()->create([
                'country_id' => $data['country_id'],
                'name' => $data['name'],
            ]);

            $package->scopeTypes()->sync($data['scope_type_ids']);
        });

        return $this->redirectBack($request, $customer, 'Package created.');
    }

    public function edit(Request $request, Customer $customer, Package $package)
    {
        $this->assertBelongsTo($customer, $package);

        $package->load('scopeTypes');

        return view('packages.edit', [
            'customer' => $customer,
            'package' => $package,
            'countries' => $this->countriesWithScopes(),
            'scopesByCountry' => $this->scopesByCountry($customer),
            // Deleting nulls these requests' package_id, losing the attribution.
            'requestCount' => $package->screeningRequests()->count(),
            'from' => $request->input('from'),
        ]);
    }

    public function update(Request $request, Customer $customer, Package $package)
    {
        $this->assertBelongsTo($customer, $package);

        $data = $this->validated($request, $customer, $package);

        DB::transaction(function () use ($package, $data) {
            $package->update([
                'country_id' => $data['country_id'],
                'name' => $data['name'],
            ]);

            $package->scopeTypes()->sync($data['scope_type_ids']);
        });

        return $this->redirectBack($request, $customer, 'Package updated.');
    }

    public function destroy(Request $request, Customer $customer, Package $package)
    {
        $this->assertBelongsTo($customer, $package);

        // package_scope_type cascades on the FK, but delete explicitly so the
        // pivot teardown is part of the same transaction as the parent row.
        DB::transaction(function () use ($package) {
            $package->scopeTypes()->detach();
            $package->delete();
        });

        return $this->redirectBack($request, $customer, 'Package deleted.');
    }

    /**
     * Return the admin to whichever listing they started from — the central
     * /packages page or the customer's Packages tab — rather than always
     * bouncing to the customer page.
     */
    private function redirectBack(Request $request, Customer $customer, string $message)
    {
        $target = $request->input('from') === 'packages'
            ? route('packages.index', ['customer_id' => $customer->id])
            : route('customers.show', $customer);

        return redirect()->to($target)->with('success', $message);
    }

    /**
     * A package belongs to exactly one customer. The hashid binding resolves
     * {package} globally, so reject a mismatched customer/package pair rather
     * than letting one customer's URL edit another's package.
     */
    private function assertBelongsTo(Customer $customer, Package $package): void
    {
        abort_unless($package->customer_id === $customer->id, 404);
    }

    /**
     * @return array{country_id: int, name: string, scope_type_ids: list<int>}
     */
    private function validated(Request $request, Customer $customer, ?Package $package = null): array
    {
        $data = $request->validate([
            'country_id' => 'required|exists:countries,id',
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('packages', 'name')
                    ->where('customer_id', $customer->id)
                    ->where('country_id', $request->input('country_id'))
                    ->ignore($package?->id),
            ],
            'scope_type_ids' => 'required|array|min:1',
            'scope_type_ids.*' => 'integer|exists:scope_types,id',
        ], [
            'name.unique' => 'This customer already has a package with that name for the selected country.',
            'scope_type_ids.required' => 'Select at least one scope for the package.',
            'scope_type_ids.min' => 'Select at least one scope for the package.',
        ]);

        $scopeIds = array_values(array_unique(array_map('intval', $data['scope_type_ids'])));

        // A package is country-scoped: the request wizard filters packages by the
        // selected country, so a scope from another country would be unreachable.
        $foreign = ScopeType::whereIn('id', $scopeIds)
            ->where('country_id', '!=', $data['country_id'])
            ->pluck('name');

        if ($foreign->isNotEmpty()) {
            throw ValidationException::withMessages([
                'scope_type_ids' => 'These scopes belong to a different country: '.$foreign->implode(', ').'.',
            ]);
        }

        return [
            'country_id' => (int) $data['country_id'],
            'name' => $data['name'],
            'scope_type_ids' => $scopeIds,
        ];
    }

    /** Only countries that actually have scopes can hold a package. */
    private function countriesWithScopes()
    {
        return Country::whereHas('scopeTypes')
            ->orderByRaw("CASE WHEN name = 'Malaysia' THEN 0 ELSE 1 END, name")
            ->get();
    }

    /**
     * Scopes grouped by country for the picker, each decorated with the price
     * this customer actually pays so the admin can see the package total.
     *
     * @return Collection<int, Collection<int, array<string, mixed>>>
     */
    private function scopesByCountry(Customer $customer)
    {
        $overrides = $customer->scopePrices()->pluck('price', 'scope_type_id');

        return ScopeType::orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('country_id')
            ->map(fn ($scopes) => $scopes->map(fn (ScopeType $scope) => [
                'id' => $scope->id,
                'name' => $scope->name,
                'category' => $scope->category,
                'turnaround' => $scope->turnaround,
                'price' => $overrides->has($scope->id)
                    ? (float) $overrides->get($scope->id)
                    : (float) $scope->price,
                'has_override' => $overrides->has($scope->id),
                // Unpriced price-on-request scopes block the client's submission.
                'unpriced' => $scope->price_on_request && ! $overrides->has($scope->id),
            ])->values());
    }
}
