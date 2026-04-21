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
}
