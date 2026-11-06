<?php

use App\Models\Customer;
use Tests\Support\Fixtures;

/**
 * The customer create/edit form: country must be a known country name, and
 * the live preview renders it as text (it once used x-html — stored XSS).
 */
beforeEach(function () {
    Fixtures::seedPermissions();
    Fixtures::country(['name' => 'Malaysia', 'flag' => '🇲🇾']);

    $this->admin = Fixtures::admin(['role' => 'super_admin']);
});

it('refuses to create a customer with an unknown country', function () {
    Fixtures::actingAs($this, $this->admin)
        ->post(route('customers.store'), [
            'name' => 'XSS Probe Sdn Bhd',
            'country' => '<img src=x onerror=alert(1)>',
        ])
        ->assertSessionHasErrors('country');

    expect(Customer::where('name', 'XSS Probe Sdn Bhd')->exists())->toBeFalse();
});

it('creates a customer with a known country', function () {
    Fixtures::actingAs($this, $this->admin)
        ->post(route('customers.store'), [
            'name' => 'Known Country Sdn Bhd',
            'country' => 'Malaysia',
            'contact_name' => 'Contact Person',
            'contact_email' => Fixtures::uniq('contact').'@example.test',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Customer::where('name', 'Known Country Sdn Bhd')->value('country'))->toBe('Malaysia');
});

it('refuses to update a customer to an unknown country', function () {
    $customer = Customer::findOrFail(Fixtures::customer(['country' => 'Malaysia']));

    Fixtures::actingAs($this, $this->admin)
        ->put(route('customers.update', $customer), [
            'name' => $customer->name,
            'country' => '"><script>alert(1)</script>',
        ])
        ->assertSessionHasErrors('country');

    expect($customer->fresh()->country)->toBe('Malaysia');
});

it('renders the country preview as text, never as HTML', function () {
    $customer = Customer::findOrFail(Fixtures::customer(['country' => 'Malaysia']));

    Fixtures::actingAs($this, $this->admin)
        ->get(route('customers.edit', $customer))
        ->assertOk()
        ->assertDontSee('x-html', false)
        ->assertSee('<span x-text="country"></span>', false);
});
