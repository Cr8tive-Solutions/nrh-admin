<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerScopePrice;
use App\Models\ScopeType;
use Illuminate\Http\Request;

class ScopePricingController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::orderBy('name')->get();
        $customer = null;
        $scopesByCountry = collect();

        if ($request->filled('customer_id')) {
            $customer = Customer::findOrFail($request->customer_id);
            $existingPrices = CustomerScopePrice::where('customer_id', $customer->id)
                ->pluck('price', 'scope_type_id');

            $scopesByCountry = Country::with(['scopeTypes' => fn ($q) => $q->orderBy('category')->orderBy('name')])
                ->whereHas('scopeTypes')
                ->orderBy('name')
                ->get()
                ->map(function ($country) use ($existingPrices) {
                    $country->scopeTypes->each(function ($scope) use ($existingPrices) {
                        $scope->custom_price = $existingPrices[$scope->id] ?? null;
                    });
                    return $country;
                });
        }

        return view('pricing.index', compact('customers', 'customer', 'scopesByCountry'));
    }

    public function scopesJson(Customer $customer)
    {
        $existingPrices = CustomerScopePrice::where('customer_id', $customer->id)
            ->pluck('price', 'scope_type_id');

        $countries = Country::with(['scopeTypes' => fn ($q) => $q->orderBy('category')->orderBy('name')])
            ->whereHas('scopeTypes')
            ->orderBy('name')
            ->get()
            ->map(function ($country) use ($existingPrices, $customer) {
                $categories = $country->scopeTypes
                    ->groupBy('category')
                    ->map(fn ($scopes, $category) => [
                        'name'   => $category ?: 'Uncategorised',
                        'scopes' => $scopes->map(fn ($scope) => [
                            'id'              => $scope->id,
                            'name'            => $scope->name,
                            'price_on_request'=> $scope->price_on_request,
                            'default_price'   => $scope->price_on_request ? null : number_format($scope->price, 2),
                            'custom_price'    => isset($existingPrices[$scope->id])
                                                    ? number_format($existingPrices[$scope->id], 2, '.', '')
                                                    : null,
                            'save_url'        => route('pricing.update-one', [$customer, $scope]),
                        ])->values(),
                    ])
                    ->values();

                return [
                    'name'        => $country->name,
                    'flag'        => $country->flag,
                    'scope_count' => $country->scopeTypes->count(),
                    'categories'  => $categories,
                ];
            });

        return response()->json(['countries' => $countries]);
    }

    public function upsert(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'prices'                => 'required|array',
            'prices.*.scope_type_id'=> 'required|exists:scope_types,id',
            'prices.*.price'        => 'required|numeric|min:0',
        ]);

        foreach ($data['prices'] as $row) {
            CustomerScopePrice::updateOrCreate(
                ['customer_id' => $customer->id, 'scope_type_id' => $row['scope_type_id']],
                ['price' => $row['price']]
            );
        }

        return back()->with('success', 'Pricing updated.');
    }

    public function updateOne(Request $request, Customer $customer, ScopeType $scopeType)
    {
        $data = $request->validate([
            'price' => 'required|numeric|min:0',
        ]);

        CustomerScopePrice::updateOrCreate(
            ['customer_id' => $customer->id, 'scope_type_id' => $scopeType->id],
            ['price' => $data['price']]
        );

        return response()->json([
            'price' => number_format($data['price'], 2),
        ]);
    }
}
