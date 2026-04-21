<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with('customer')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('customer', fn ($c) => $c->where('name', 'ilike', "%{$search}%"));
        }

        $transactions = $query->paginate(25)->withQueryString();

        return view('transactions.index', compact('transactions'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        return view('transactions.create', compact('customers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'type'        => 'required|in:topup,payment,adjustment',
            'amount'      => 'required|numeric|min:0.01',
            'method'      => 'required|string|max:100',
            'reference'   => 'nullable|string|max:255',
            'notes'       => 'nullable|string',
            'status'      => 'required|string|max:50',
        ]);

        Transaction::create($data);

        return redirect()->route('transactions.index')->with('success', 'Transaction recorded.');
    }
}
