<?php

use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\Support\Fixtures;

/**
 * Regression cover for the two hashid routing bugs fixed on 2026-08-05.
 *
 * Bug A: leftover ->whereNumber() on HasHashid-bound params made every edit /
 *        verify / evidence route 404, because a hashid is alphanumeric.
 * Bug B: bare /customers/{customer} and /invoices/{invoice} were declared
 *        BEFORE the literal routes, so /customers/create, /invoices/create and
 *        the whole bulk-invoice flow resolved to the show route and then 404'd
 *        when hdecode('create') failed.
 */

/** Resolve a URI to its route name without dispatching the request. */
function matchRouteName(string $method, string $uri): string
{
    try {
        return Route::getRoutes()->match(Request::create('/'.ltrim($uri, '/'), $method))->getName() ?? '(unnamed)';
    } catch (Throwable) {
        return 'NO MATCH';
    }
}

it('does not let the wildcard show route swallow literal routes', function (string $uri, string $expected) {
    expect(matchRouteName('GET', $uri))->toBe($expected);
})->with([
    'customers/create' => ['customers/create', 'customers.create'],
    'invoices/create' => ['invoices/create', 'invoices.create'],
    'invoices/bulk-generate' => ['invoices/bulk-generate', 'invoices.bulk-generate'],
    'invoices/bulk-preview' => ['invoices/bulk-preview', 'invoices.bulk-preview'],
    'invoices/preview-items' => ['invoices/preview-items', 'invoices.preview-items'],
    'config/scopes/create' => ['config/scopes/create', 'config.scopes.create'],
    'compliance/dsar/create' => ['compliance/dsar/create', 'compliance.dsar.create'],
    'staff/create' => ['staff/create', 'staff.create'],
]);

it('matches hashid-bound routes that whereNumber used to break', function (string $method, string $tmpl, string $expected) {
    $h = hid(1);
    expect(matchRouteName($method, str_replace('{h}', $h, $tmpl)))->toBe($expected);
})->with([
    ['GET', 'customers/{h}/edit', 'customers.edit'],
    ['PUT', 'customers/{h}', 'customers.update'],
    ['POST', 'customers/{h}/provision-primary-user', 'customers.provision-primary-user'],
    ['GET', 'invoices/{h}/edit', 'invoices.edit'],
    ['PATCH', 'invoices/{h}/paid', 'invoices.paid'],
    ['GET', 'requests/{h}/report/versions/{h}/download', 'requests.report.download'],
    ['GET', 'requests/{h}/report/versions/{h}/view', 'requests.report.view'],
    ['POST', 'payment-receipts/{h}/verify', 'payment-receipts.verify'],
    ['GET', 'payment-receipts/{h}/file', 'payment-receipts.file'],
    ['GET', 'compliance/consent/{h}/evidence', 'compliance.consent.evidence'],
    ['GET', 'compliance/dsar/{h}', 'compliance.dsar.show'],
    ['PATCH', 'compliance/dsar/{h}/verify', 'compliance.dsar.verify'],
]);

it('has no literal route shadowed by an earlier wildcard', function () {
    $shadowed = [];

    foreach (Route::getRoutes() as $route) {
        $uri = $route->uri();
        if (str_contains($uri, '{')) {
            continue; // only literal-segment routes can be shadowed
        }
        foreach ($route->methods() as $method) {
            if (in_array($method, ['HEAD', 'OPTIONS'], true)) {
                continue;
            }
            try {
                $matched = Route::getRoutes()->match(Request::create('/'.$uri, $method));
            } catch (Throwable) {
                // URIs that can't form a request (e.g. the "/" root) aren't shadowable.
                continue;
            }
            if ($matched->uri() !== $uri) {
                $shadowed[] = "$method /$uri -> {$matched->uri()}";
            }
        }
    }

    expect($shadowed)->toBeEmpty();
});

it('round-trips a real model through its hashid route key', function () {
    $customerId = Fixtures::customer();
    $customer = Customer::findOrFail($customerId);

    $key = $customer->getRouteKey();

    expect($key)->not->toBe((string) $customer->id)
        ->and($key)->toMatch('/^[A-Za-z0-9]+$/')
        ->and((new Customer)->resolveRouteBinding($key)->id)->toBe($customer->id);
});

it('404s on a hashid that decodes to nothing', function () {
    expect(fn () => (new Invoice)->resolveRouteBinding('create'))
        ->toThrow(NotFoundHttpException::class);
});

it('never exposes a raw integer id in a generated url', function () {
    $customerId = Fixtures::customer();
    $customer = Customer::findOrFail($customerId);

    expect(route('customers.show', $customer))->not->toContain("/customers/{$customer->id}");
});
