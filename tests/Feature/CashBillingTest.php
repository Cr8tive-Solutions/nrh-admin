<?php

use App\Models\ScreeningRequest;
use Illuminate\Support\Facades\DB;
use Tests\Support\Fixtures;

beforeEach(function () {
    Fixtures::seedPermissions();
    $this->admin = Fixtures::admin(['role' => 'super_admin']);
    $this->customerId = Fixtures::customer();
    $this->userId = Fixtures::customerUser($this->customerId);
    $this->countryId = Fixtures::country();
    $this->identityTypeId = Fixtures::identityType();
    $this->as = fn () => Fixtures::actingAs($this, $this->admin);
});

// ── SST on cash totals ───────────────────────────────────────────────────────

it('adds SST on top of the cash subtotal', function () {
    $scopeId = Fixtures::scopeType($this->countryId, ['price' => 100.00]);
    $requestId = Fixtures::screeningRequest($this->customerId, $this->userId);
    $candidateId = Fixtures::candidate($requestId, $this->identityTypeId);
    Fixtures::attachScope($candidateId, $scopeId);

    $request = ScreeningRequest::find($requestId);

    expect($request->calculateSubtotal())->toBe(100.0)
        ->and($request->calculateTax())->toBe(6.0)
        ->and($request->calculateTotal())->toBe(106.0);
});

it('books the SST-inclusive total when verifying a payment slip', function () {
    Fixtures::agreement($this->customerId, 'per_request');
    $scopeId = Fixtures::scopeType($this->countryId, ['price' => 200.00]);
    $requestId = Fixtures::screeningRequest($this->customerId, $this->userId, [
        'status' => 'new',
        'payment_slip_path' => 'payment-slips/test/slip.pdf',
        'payment_slip_uploaded_at' => now(),
    ]);
    $candidateId = Fixtures::candidate($requestId, $this->identityTypeId);
    Fixtures::attachScope($candidateId, $scopeId);

    ($this->as)()->post(route('requests.verify-payment', ScreeningRequest::find($requestId)))
        ->assertRedirect();

    $txn = DB::table('transactions')->where('customer_id', $this->customerId)->latest('id')->first();
    expect((float) $txn->amount)->toBe(212.0)
        ->and(ScreeningRequest::find($requestId)->status)->toBe('in_progress');
});

// ── Unpriced price-on-request scopes in invoicing ────────────────────────────

it('excludes unpriced price-on-request lines from the invoice preview and warns', function () {
    $pricedId = Fixtures::scopeType($this->countryId, ['price' => 100.00]);
    $porId = Fixtures::scopeType($this->countryId, [
        'name' => 'POR Scope '.Fixtures::uniq(), 'price' => 0.00, 'price_on_request' => true,
    ]);

    $requestId = Fixtures::screeningRequest($this->customerId, $this->userId, ['status' => 'complete']);
    $candidateId = Fixtures::candidate($requestId, $this->identityTypeId);
    Fixtures::attachScope($candidateId, $pricedId);
    Fixtures::attachScope($candidateId, $porId);

    $response = ($this->as)()->getJson(route('invoices.preview-items', [
        'customer_id' => $this->customerId,
        'period_start' => now()->subDay()->toDateString(),
        'period_end' => now()->addDay()->toDateString(),
    ]))->assertOk();

    $json = $response->json();

    expect($json['items'])->toHaveCount(1)
        ->and($json['items'][0]['unit_price'])->toBe('100.00')
        ->and($json['warnings'])->toHaveCount(1)
        ->and($json['warnings'][0])->toContain('price on request');
});

it('includes a price-on-request line once the customer price is set', function () {
    $porId = Fixtures::scopeType($this->countryId, [
        'price' => 0.00, 'price_on_request' => true,
    ]);
    DB::table('customer_scope_prices')->insert([
        'customer_id' => $this->customerId, 'scope_type_id' => $porId,
        'price' => 350.00, 'created_at' => now(), 'updated_at' => now(),
    ]);

    $requestId = Fixtures::screeningRequest($this->customerId, $this->userId, ['status' => 'complete']);
    $candidateId = Fixtures::candidate($requestId, $this->identityTypeId);
    Fixtures::attachScope($candidateId, $porId);

    $json = ($this->as)()->getJson(route('invoices.preview-items', [
        'customer_id' => $this->customerId,
        'period_start' => now()->subDay()->toDateString(),
        'period_end' => now()->addDay()->toDateString(),
    ]))->assertOk()->json();

    expect($json['items'])->toHaveCount(1)
        ->and($json['items'][0]['unit_price'])->toBe('350.00')
        ->and($json['warnings'])->toBeEmpty();
});

it('does not link a request whose only lines are unpriced', function () {
    $porId = Fixtures::scopeType($this->countryId, ['price' => 0.00, 'price_on_request' => true]);

    $requestId = Fixtures::screeningRequest($this->customerId, $this->userId, ['status' => 'complete']);
    $candidateId = Fixtures::candidate($requestId, $this->identityTypeId);
    Fixtures::attachScope($candidateId, $porId);

    $json = ($this->as)()->getJson(route('invoices.preview-items', [
        'customer_id' => $this->customerId,
        'period_start' => now()->subDay()->toDateString(),
        'period_end' => now()->addDay()->toDateString(),
    ]))->assertOk()->json();

    expect($json['items'])->toBeEmpty()
        ->and($json['requests'])->toBeEmpty()
        ->and($json['warnings'])->not->toBeEmpty();
});
