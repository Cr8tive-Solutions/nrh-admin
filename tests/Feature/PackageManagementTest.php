<?php

use App\Models\Customer;
use App\Models\Package;
use App\Models\ScreeningRequest;
use Illuminate\Support\Facades\DB;
use Tests\Support\Fixtures;

/**
 * Admin authoring of per-customer scope packages.
 *
 * Packages are read-only in the client portal, so this is the only surface that
 * writes `packages` / `package_scope_type`.
 */
beforeEach(function () {
    Fixtures::seedPermissions();

    $this->admin = Fixtures::admin(['role' => 'super_admin']);
    $this->customerId = Fixtures::customer();
    $this->customer = Customer::findOrFail($this->customerId);
    $this->countryId = Fixtures::country(['name' => 'Malaysia']);
    $this->scopeA = Fixtures::scopeType($this->countryId);
    $this->scopeB = Fixtures::scopeType($this->countryId);
});

it('shows the packages tab on the customer page', function () {
    $packageId = Fixtures::package($this->customerId, $this->countryId, ['name' => 'Standard Screening']);
    Fixtures::attachPackageScope($packageId, $this->scopeA);

    Fixtures::actingAs($this, $this->admin)
        ->get(route('customers.show', $this->customer))
        ->assertOk()
        ->assertSee('Standard Screening')
        ->assertSee('Scope Packages');
});

it('lists every company with its package count when none is picked', function () {
    $packageId = Fixtures::package($this->customerId, $this->countryId, ['name' => 'Standard Screening']);
    Fixtures::attachPackageScope($packageId, $this->scopeA);

    $emptyCustomerId = Fixtures::customer(['name' => 'No Packages Sdn Bhd']);

    Fixtures::actingAs($this, $this->admin)
        ->get(route('packages.index'))
        ->assertOk()
        ->assertSee($this->customer->name)
        ->assertSee('1 package')
        ->assertSee('No Packages Sdn Bhd')
        ->assertSee('No packages');

    expect($emptyCustomerId)->toBeInt();
});

it('shows one company\'s packages when picked', function () {
    $packageId = Fixtures::package($this->customerId, $this->countryId, ['name' => 'Finance Sector Pack']);
    Fixtures::attachPackageScope($packageId, $this->scopeA);

    // Another customer's package must not leak into this view.
    $otherId = Fixtures::customer();
    $otherPackage = Fixtures::package($otherId, $this->countryId, ['name' => 'Someone Elses Pack']);
    Fixtures::attachPackageScope($otherPackage, $this->scopeA);

    Fixtures::actingAs($this, $this->admin)
        ->get(route('packages.index', ['customer_id' => $this->customerId]))
        ->assertOk()
        ->assertSee('Finance Sector Pack')
        ->assertDontSee('Someone Elses Pack');
});

it('lets any admin view the packages page read-only', function () {
    $viewer = Fixtures::admin(['role' => 'viewer']);

    Fixtures::actingAs($this, $viewer)
        ->get(route('packages.index'))
        ->assertOk()
        ->assertSee('you don\'t have permission to edit packages', false);
});

it('returns to the packages page after creating from there', function () {
    Fixtures::actingAs($this, $this->admin)
        ->post(route('customers.packages.store', $this->customer), [
            'name' => 'From Packages Page',
            'country_id' => $this->countryId,
            'price' => 199.00,
            'scope_type_ids' => [$this->scopeA],
            'from' => 'packages',
        ])
        ->assertRedirect(route('packages.index', ['customer_id' => $this->customerId]));
});

it('returns to the customer page when created from the customer tab', function () {
    Fixtures::actingAs($this, $this->admin)
        ->post(route('customers.packages.store', $this->customer), [
            'name' => 'From Customer Tab',
            'country_id' => $this->countryId,
            'price' => 199.00,
            'scope_type_ids' => [$this->scopeA],
        ])
        ->assertRedirect(route('customers.show', $this->customer));
});

it('creates a package with its scopes', function () {
    Fixtures::actingAs($this, $this->admin)
        ->post(route('customers.packages.store', $this->customer), [
            'name' => 'Finance Sector Pack',
            'country_id' => $this->countryId,
            'price' => 199.00,
            'scope_type_ids' => [$this->scopeA, $this->scopeB],
        ])
        ->assertRedirect(route('customers.show', $this->customer));

    $package = Package::where('customer_id', $this->customerId)->firstOrFail();

    expect($package->name)->toBe('Finance Sector Pack')
        ->and($package->country_id)->toBe($this->countryId)
        ->and((float) $package->price)->toBe(199.00)
        ->and($package->scopeTypes->pluck('id')->sort()->values()->all())
        ->toBe(collect([$this->scopeA, $this->scopeB])->sort()->values()->all());
});

it('requires a package price', function () {
    Fixtures::actingAs($this, $this->admin)
        ->post(route('customers.packages.store', $this->customer), [
            'name' => 'No Price Pack',
            'country_id' => $this->countryId,
            'scope_type_ids' => [$this->scopeA],
        ])
        ->assertSessionHasErrors('price');

    expect(Package::where('customer_id', $this->customerId)->count())->toBe(0);
});

it('requires at least one scope', function () {
    Fixtures::actingAs($this, $this->admin)
        ->post(route('customers.packages.store', $this->customer), [
            'name' => 'Empty Pack',
            'country_id' => $this->countryId,
            'price' => 199.00,
        ])
        ->assertSessionHasErrors('scope_type_ids');

    expect(Package::where('customer_id', $this->customerId)->count())->toBe(0);
});

it('rejects a duplicate package name for the same customer and country', function () {
    Fixtures::package($this->customerId, $this->countryId, ['name' => 'Standard Screening']);

    Fixtures::actingAs($this, $this->admin)
        ->post(route('customers.packages.store', $this->customer), [
            'name' => 'Standard Screening',
            'country_id' => $this->countryId,
            'price' => 199.00,
            'scope_type_ids' => [$this->scopeA],
        ])
        ->assertSessionHasErrors('name');

    expect(Package::where('customer_id', $this->customerId)->count())->toBe(1);
});

it('allows the same package name in a different country', function () {
    Fixtures::package($this->customerId, $this->countryId, ['name' => 'Standard Screening']);

    $singapore = Fixtures::country(['name' => 'Singapore']);
    $sgScope = Fixtures::scopeType($singapore);

    Fixtures::actingAs($this, $this->admin)
        ->post(route('customers.packages.store', $this->customer), [
            'name' => 'Standard Screening',
            'country_id' => $singapore,
            'price' => 199.00,
            'scope_type_ids' => [$sgScope],
        ])
        ->assertSessionHasNoErrors();

    expect(Package::where('customer_id', $this->customerId)->count())->toBe(2);
});

it('allows another customer to reuse a package name', function () {
    Fixtures::package($this->customerId, $this->countryId, ['name' => 'Standard Screening']);

    $otherId = Fixtures::customer();
    $other = Customer::findOrFail($otherId);

    Fixtures::actingAs($this, $this->admin)
        ->post(route('customers.packages.store', $other), [
            'name' => 'Standard Screening',
            'country_id' => $this->countryId,
            'price' => 199.00,
            'scope_type_ids' => [$this->scopeA],
        ])
        ->assertSessionHasNoErrors();

    expect(Package::where('customer_id', $otherId)->count())->toBe(1);
});

it('rejects scopes belonging to another country', function () {
    $singapore = Fixtures::country(['name' => 'Singapore']);
    $sgScope = Fixtures::scopeType($singapore, ['name' => 'SG Criminal Check']);

    Fixtures::actingAs($this, $this->admin)
        ->post(route('customers.packages.store', $this->customer), [
            'name' => 'Mixed Pack',
            'country_id' => $this->countryId,
            'price' => 199.00,
            'scope_type_ids' => [$this->scopeA, $sgScope],
        ])
        ->assertSessionHasErrors('scope_type_ids');

    expect(Package::where('customer_id', $this->customerId)->count())->toBe(0);
});

it('syncs the scope list on update', function () {
    $packageId = Fixtures::package($this->customerId, $this->countryId);
    Fixtures::attachPackageScope($packageId, $this->scopeA);
    $package = Package::findOrFail($packageId);

    Fixtures::actingAs($this, $this->admin)
        ->put(route('customers.packages.update', [$this->customer, $package]), [
            'name' => 'Renamed Pack',
            'country_id' => $this->countryId,
            'price' => 199.00,
            'scope_type_ids' => [$this->scopeB],
        ])
        ->assertRedirect(route('customers.show', $this->customer));

    $package->refresh()->load('scopeTypes');

    expect($package->name)->toBe('Renamed Pack')
        ->and($package->scopeTypes->pluck('id')->all())->toBe([$this->scopeB]);
});

it('deletes a package and its pivot rows', function () {
    $packageId = Fixtures::package($this->customerId, $this->countryId);
    Fixtures::attachPackageScope($packageId, $this->scopeA);
    $package = Package::findOrFail($packageId);

    Fixtures::actingAs($this, $this->admin)
        ->delete(route('customers.packages.destroy', [$this->customer, $package]))
        ->assertRedirect(route('customers.show', $this->customer));

    expect(Package::find($packageId))->toBeNull()
        ->and(DB::table('package_scope_type')->where('package_id', $packageId)->count())->toBe(0);
});

it('keeps requests when their package is deleted, clearing only the attribution', function () {
    $packageId = Fixtures::package($this->customerId, $this->countryId);
    Fixtures::attachPackageScope($packageId, $this->scopeA);
    $package = Package::findOrFail($packageId);

    $userId = Fixtures::customerUser($this->customerId);
    $requestId = Fixtures::screeningRequest($this->customerId, $userId, ['package_id' => $packageId]);

    Fixtures::actingAs($this, $this->admin)
        ->delete(route('customers.packages.destroy', [$this->customer, $package]))
        ->assertRedirect(route('customers.show', $this->customer));

    // nullOnDelete, not cascade — the request must survive.
    $request = DB::table('screening_requests')->where('id', $requestId)->first();

    expect($request)->not->toBeNull()
        ->and($request->package_id)->toBeNull();
});

it('warns on the edit page when requests reference the package', function () {
    $packageId = Fixtures::package($this->customerId, $this->countryId);
    Fixtures::attachPackageScope($packageId, $this->scopeA);
    $package = Package::findOrFail($packageId);

    $userId = Fixtures::customerUser($this->customerId);
    Fixtures::screeningRequest($this->customerId, $userId, ['package_id' => $packageId]);

    Fixtures::actingAs($this, $this->admin)
        ->get(route('customers.packages.edit', [$this->customer, $package]))
        ->assertOk()
        ->assertSee('lose their package attribution', false);
});

it('shows the package on the request detail page', function () {
    $packageId = Fixtures::package($this->customerId, $this->countryId, ['name' => 'Finance Sector Pack']);
    Fixtures::attachPackageScope($packageId, $this->scopeA);

    $userId = Fixtures::customerUser($this->customerId);
    $requestId = Fixtures::screeningRequest($this->customerId, $userId, ['package_id' => $packageId]);
    $request = ScreeningRequest::findOrFail($requestId);

    Fixtures::actingAs($this, $this->admin)
        ->get(route('requests.show', $request))
        ->assertOk()
        ->assertSee('Finance Sector Pack');
});

it('404s when the package belongs to a different customer', function () {
    $otherId = Fixtures::customer();
    $packageId = Fixtures::package($otherId, $this->countryId);
    $package = Package::findOrFail($packageId);

    Fixtures::actingAs($this, $this->admin)
        ->get(route('customers.packages.edit', [$this->customer, $package]))
        ->assertNotFound();
});

it('denies a role without customer.manage', function () {
    $finance = Fixtures::admin(['role' => 'finance']);

    Fixtures::actingAs($this, $finance)
        ->get(route('customers.packages.create', $this->customer))
        ->assertForbidden();
});

it('totals a package at the customer effective prices', function () {
    $packageId = Fixtures::package($this->customerId, $this->countryId);
    Fixtures::attachPackageScope($packageId, $this->scopeA); // default 100.00
    Fixtures::attachPackageScope($packageId, $this->scopeB);
    Fixtures::customerScopePrice($this->customerId, $this->scopeB, 25.50);

    $package = Package::with('scopeTypes')->findOrFail($packageId);

    expect($package->effectiveTotal())->toBe(125.50)
        ->and($package->unpricedScopeTypes())->toBeEmpty();
});

it('reports no total when a scope is price-on-request with no override', function () {
    $onRequest = Fixtures::scopeType($this->countryId, ['price' => 0, 'price_on_request' => true]);

    $packageId = Fixtures::package($this->customerId, $this->countryId);
    Fixtures::attachPackageScope($packageId, $this->scopeA);
    Fixtures::attachPackageScope($packageId, $onRequest);

    $package = Package::with('scopeTypes')->findOrFail($packageId);

    expect($package->effectiveTotal())->toBeNull()
        ->and($package->unpricedScopeTypes()->pluck('id')->all())->toBe([$onRequest]);
});

it('counts a price-on-request scope with an override as priced', function () {
    $onRequest = Fixtures::scopeType($this->countryId, ['price' => 0, 'price_on_request' => true]);
    Fixtures::customerScopePrice($this->customerId, $onRequest, 400.00);

    $packageId = Fixtures::package($this->customerId, $this->countryId);
    Fixtures::attachPackageScope($packageId, $onRequest);

    $package = Package::with('scopeTypes')->findOrFail($packageId);

    expect($package->effectiveTotal())->toBe(400.00)
        ->and($package->unpricedScopeTypes())->toBeEmpty();
});

it('bills a package-attributed request at the bundle price per candidate', function () {
    // Package priced at 250, cheaper than its two 100.00 scopes (200) × ... — the
    // point is the fixed bundle price wins over the per-scope sum.
    $packageId = Fixtures::package($this->customerId, $this->countryId, ['price' => 250.00]);
    Fixtures::attachPackageScope($packageId, $this->scopeA);
    Fixtures::attachPackageScope($packageId, $this->scopeB);

    $identityTypeId = Fixtures::identityType();
    $userId = Fixtures::customerUser($this->customerId);
    $requestId = Fixtures::screeningRequest($this->customerId, $userId, ['package_id' => $packageId]);

    // Two candidates, each carrying the full package.
    foreach (range(1, 2) as $_) {
        $candidateId = Fixtures::candidate($requestId, $identityTypeId);
        Fixtures::attachScope($candidateId, $this->scopeA);
        Fixtures::attachScope($candidateId, $this->scopeB);
    }

    $request = ScreeningRequest::findOrFail($requestId);

    // 250 bundle × 2 candidates = 500, not 4 scope-rows × 100 = 400.
    expect($request->calculateSubtotal())->toBe(500.00)
        ->and($request->calculateTotal())->toBe(round(500.00 * 1.06, 2));
});

it('falls back to per-scope billing for an ad-hoc (no package) request', function () {
    $identityTypeId = Fixtures::identityType();
    $userId = Fixtures::customerUser($this->customerId);
    $requestId = Fixtures::screeningRequest($this->customerId, $userId); // no package_id

    $candidateId = Fixtures::candidate($requestId, $identityTypeId);
    Fixtures::attachScope($candidateId, $this->scopeA); // 100.00
    Fixtures::attachScope($candidateId, $this->scopeB); // 100.00

    expect(ScreeningRequest::findOrFail($requestId)->calculateSubtotal())->toBe(200.00);
});
