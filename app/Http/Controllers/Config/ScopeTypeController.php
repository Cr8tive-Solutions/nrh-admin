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
        $data['turnaround'] = $this->resolveTurnaround($data['turnaround'] ?? null, $data['turnaround_hours'] ?? null);

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
        $data['turnaround'] = $this->resolveTurnaround($data['turnaround'] ?? null, $data['turnaround_hours'] ?? null);

        $scope->update($data);

        return redirect()->route('config.scopes.index')->with('success', 'Scope type updated.');
    }

    /**
     * The client-facing `turnaround` string is a NOT NULL column, but the form
     * leaves it optional (the SLA is driven by `turnaround_hours`). When staff
     * leave the display text blank, derive a sensible one from the SLA hours so
     * the insert never violates the constraint — and clients still see a value.
     */
    private function resolveTurnaround(?string $turnaround, ?int $hours): string
    {
        $turnaround = trim((string) $turnaround);

        if ($turnaround !== '') {
            return $turnaround;
        }

        if ($hours) {
            // config/business_hours.php: a working day is 09:00–18:00 = 9 hours.
            $perDay = 9;

            return $hours % $perDay === 0 && $hours >= $perDay
                ? ($d = $hours / $perDay).' business '.($d === 1 ? 'day' : 'days')
                : $hours.' business '.($hours === 1 ? 'hour' : 'hours');
        }

        return 'By arrangement';
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
