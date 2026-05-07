<?php

namespace App\Http\Controllers;

use App\Models\Agreement;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AgreementController extends Controller
{
    public function create(Customer $customer)
    {
        return view('agreements.create', compact('customer'));
    }

    public function store(Request $request, Customer $customer)
    {
        $customer->agreements()->create($this->validated($request));

        return redirect()->route('customers.show', $customer)->with('success', 'Agreement created.');
    }

    public function edit(Customer $customer, Agreement $agreement)
    {
        return view('agreements.edit', compact('customer', 'agreement'));
    }

    public function update(Request $request, Customer $customer, Agreement $agreement)
    {
        $agreement->update($this->validated($request));

        return redirect()->route('customers.show', $customer)->with('success', 'Agreement updated.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'type'        => 'required|string|max:100',
            'start_date'  => 'required|date',
            'expiry_date' => 'required|date|after:start_date',
            'sla_tat'     => 'nullable|string|max:100',
            'billing'     => ['required', Rule::in(['monthly', 'per_request'])],
            'payment'     => 'nullable|string|max:100',
        ]);
    }
}
