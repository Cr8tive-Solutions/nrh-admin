<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    public function index()
    {
        $countries = Country::withCount('scopeTypes')->orderBy('name')->get();
        return view('config.countries.index', compact('countries'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'code'     => 'required|string|size:3|unique:countries,code',
            'currency' => 'required|string|size:3',
            'flag'     => 'nullable|string|max:10',
            'region'   => 'nullable|string|max:100',
        ]);

        $data['currency'] = strtoupper($data['currency']);

        Country::create($data);

        return back()->with('success', 'Country added.');
    }

    public function update(Request $request, Country $country)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'code'     => 'required|string|size:3|unique:countries,code,' . $country->id,
            'currency' => 'required|string|size:3',
            'flag'     => 'nullable|string|max:10',
            'region'   => 'nullable|string|max:100',
        ]);

        $data['currency'] = strtoupper($data['currency']);

        $country->update($data);

        return back()->with('success', 'Country updated.');
    }
}
