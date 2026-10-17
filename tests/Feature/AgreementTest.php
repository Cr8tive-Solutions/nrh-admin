<?php

use App\Models\Agreement;
use App\Models\Customer;
use Tests\Support\Fixtures;

it('creates an agreement when optional payment/SLA fields are left blank', function () {
    $admin = Fixtures::admin();
    $customer = Customer::findOrFail(Fixtures::customer());

    Fixtures::actingAs($this, $admin)
        ->post(route('customers.agreements.store', $customer), [
            'type' => 'Credit',
            'start_date' => '2026-10-17',
            'expiry_date' => '2026-10-31',
            'billing' => 'per_request',
            // sla_tat and payment intentionally omitted
        ])
        ->assertRedirect(route('customers.show', $customer))
        ->assertSessionHas('success');

    $agreement = Agreement::where('customer_id', $customer->id)->firstOrFail();
    // NOT NULL columns are satisfied with empty strings, never null.
    expect($agreement->payment)->toBe('');
    expect($agreement->sla_tat)->toBe('');
});

it('keeps staff-entered payment and SLA values verbatim', function () {
    $admin = Fixtures::admin();
    $customer = Customer::findOrFail(Fixtures::customer());

    Fixtures::actingAs($this, $admin)
        ->post(route('customers.agreements.store', $customer), [
            'type' => 'Standard',
            'start_date' => '2026-10-17',
            'expiry_date' => '2027-10-17',
            'billing' => 'monthly',
            'sla_tat' => '3 business days',
            'payment' => 'Net 30',
        ])
        ->assertRedirect(route('customers.show', $customer));

    $agreement = Agreement::where('customer_id', $customer->id)->firstOrFail();
    expect($agreement->sla_tat)->toBe('3 business days');
    expect($agreement->payment)->toBe('Net 30');
});

it('rejects an expiry date that is not after the start date', function () {
    $admin = Fixtures::admin();
    $customer = Customer::findOrFail(Fixtures::customer());

    Fixtures::actingAs($this, $admin)
        ->post(route('customers.agreements.store', $customer), [
            'type' => 'Credit',
            'start_date' => '2026-10-17',
            'expiry_date' => '2026-10-17',
            'billing' => 'monthly',
        ])
        ->assertSessionHasErrors('expiry_date');

    expect(Agreement::where('customer_id', $customer->id)->exists())->toBeFalse();
});
