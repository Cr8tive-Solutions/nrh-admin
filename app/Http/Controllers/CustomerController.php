<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::withCount(['screeningRequests', 'invoices'])->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('registration_no', 'ilike', "%{$search}%")
                  ->orWhere('contact_email', 'ilike', "%{$search}%");
            });
        }

        $customers = $query->paginate(25)->withQueryString();

        return view('customers.index', compact('customers'));
    }

    public function show(Customer $customer)
    {
        $customer->load(['agreements', 'customerUsers', 'invoices' => fn ($q) => $q->latest()->take(10), 'transactions' => fn ($q) => $q->latest()->take(10)]);
        $recentRequests = $customer->screeningRequests()->with('candidates')->latest()->take(10)->get();

        return view('customers.show', compact('customer', 'recentRequests'));
    }

    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'registration_no'=> 'nullable|string|max:100',
            'address'        => 'nullable|string',
            'country'        => 'nullable|string|max:100',
            'industry'       => 'nullable|string|max:100',
            'contact_name'   => 'nullable|string|max:255',
            'contact_email'  => 'nullable|email|max:255',
            'contact_phone'  => 'nullable|string|max:50',
        ]);

        $customer->update($data);

        return redirect()->route('customers.show', $customer)->with('success', 'Customer updated.');
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'registration_no'=> 'nullable|string|max:100',
            'address'        => 'nullable|string',
            'country'        => 'nullable|string|max:100',
            'industry'       => 'nullable|string|max:100',
            'contact_name'   => 'nullable|string|max:255',
            'contact_email'  => 'nullable|email|max:255',
            'contact_phone'  => 'nullable|string|max:50',
        ]);

        $customer = Customer::create($data);

        return redirect()->route('customers.show', $customer)->with('success', 'Customer created.');
    }
}
