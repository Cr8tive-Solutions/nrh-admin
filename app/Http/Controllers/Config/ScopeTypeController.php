<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\ScopeType;
use Illuminate\Http\Request;

class ScopeTypeController extends Controller
{
    public function index()
    {
        $scopesByCountry = Country::with(['scopeTypes' => fn ($q) => $q->orderBy('id')])
            ->orderByRaw("CASE WHEN name = 'Malaysia' THEN 0 ELSE 1 END, name")
            ->get();

        return view('config.scopes.index', compact('scopesByCountry'));
    }

    public function create()
    {
        $countries = Country::orderBy('name')->get();
        return view('config.scopes.create', compact('countries'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'country_id'       => 'required|exists:countries,id',
            'name'             => 'required|string|max:255',
            'category'         => 'nullable|string|max:255',
            'turnaround'       => 'nullable|string|max:100',
            'price'            => 'required|numeric|min:0',
            'price_on_request' => 'boolean',
            'description'      => 'nullable|string',
        ]);

        $data['price_on_request'] = $request->boolean('price_on_request');

        ScopeType::create($data);

        return redirect()->route('config.scopes.index')->with('success', 'Scope type created.');
    }

    public function edit(ScopeType $scope)
    {
        $countries = Country::orderBy('name')->get();
        return view('config.scopes.edit', compact('scope', 'countries'));
    }

    public function update(Request $request, ScopeType $scope)
    {
        $data = $request->validate([
            'country_id'       => 'required|exists:countries,id',
            'name'             => 'required|string|max:255',
            'category'         => 'nullable|string|max:255',
            'turnaround'       => 'nullable|string|max:100',
            'price'            => 'required|numeric|min:0',
            'price_on_request' => 'boolean',
            'description'      => 'nullable|string',
        ]);

        $data['price_on_request'] = $request->boolean('price_on_request');

        $scope->update($data);

        return redirect()->route('config.scopes.index')->with('success', 'Scope type updated.');
    }
}
