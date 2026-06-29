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
        $scopesByCountry = Country::with(['scopeTypes' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')])
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
            'country_id' => 'required|exists:countries,id',
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'turnaround' => 'nullable|string|max:100',
            'turnaround_hours' => 'nullable|integer|min:1|max:1440',
            'price' => 'required|numeric|min:0',
            'price_on_request' => 'boolean',
            'requires_signed_consent' => 'boolean',
            'required_documents' => 'nullable|array',
            'required_documents.*' => 'string|in:'.implode(',', array_keys(ScopeType::DOCUMENT_TYPES)),
            'description' => 'nullable|string',
        ]);

        $data['price_on_request'] = $request->boolean('price_on_request');
        $data['requires_signed_consent'] = $request->boolean('requires_signed_consent');
        $data['required_documents'] = $this->normaliseDocuments($request->input('required_documents', []));

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
            'country_id' => 'required|exists:countries,id',
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'turnaround' => 'nullable|string|max:100',
            'turnaround_hours' => 'nullable|integer|min:1|max:1440',
            'price' => 'required|numeric|min:0',
            'price_on_request' => 'boolean',
            'requires_signed_consent' => 'boolean',
            'required_documents' => 'nullable|array',
            'required_documents.*' => 'string|in:'.implode(',', array_keys(ScopeType::DOCUMENT_TYPES)),
            'description' => 'nullable|string',
        ]);

        $data['price_on_request'] = $request->boolean('price_on_request');
        $data['requires_signed_consent'] = $request->boolean('requires_signed_consent');
        $data['required_documents'] = $this->normaliseDocuments($request->input('required_documents', []));

        $scope->update($data);

        return redirect()->route('config.scopes.index')->with('success', 'Scope type updated.');
    }

    /**
     * Keep only valid document keys, de-duplicated and in the canonical
     * DOCUMENT_TYPES order. Returns null when nothing is required.
     */
    private function normaliseDocuments(array $input): ?array
    {
        $docs = array_values(array_filter(
            array_keys(ScopeType::DOCUMENT_TYPES),
            fn ($key) => in_array($key, $input, true)
        ));

        return $docs ?: null;
    }
}
