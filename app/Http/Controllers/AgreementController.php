<?php

namespace App\Http\Controllers;

use App\Models\Agreement;
use App\Models\Customer;
use Illuminate\Http\Request;

class AgreementController extends Controller
{
    public function create(Customer $customer)
    {
        return view('agreements.create', compact('customer'));
    }

    public function store(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'type'        => 'required|string|max:100',
            'start_date'  => 'required|date',
            'expiry_date' => 'required|date|after:start_date',
            'sla_tat'     => 'nullable|string|max:100',
            'billing'     => 'nullable|string|max:100',
            'payment'     => 'nullable|string|max:100',
        ]);

        $customer->agreements()->create($data);

        return redirect()->route('customers.show', $customer)->with('success', 'Agreement created.');
    }

    public function edit(Customer $customer, Agreement $agreement)
    {
        return view('agreements.edit', compact('customer', 'agreement'));
    }

    public function update(Request $request, Customer $customer, Agreement $agreement)
    {
        $data = $request->validate([
            'type'        => 'required|string|max:100',
            'start_date'  => 'required|date',
            'expiry_date' => 'required|date|after:start_date',
            'sla_tat'     => 'nullable|string|max:100',
            'billing'     => 'nullable|string|max:100',
            'payment'     => 'nullable|string|max:100',
        ]);

        $agreement->update($data);

        return redirect()->route('customers.show', $customer)->with('success', 'Agreement updated.');
    }
}
