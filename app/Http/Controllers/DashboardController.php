<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\ScreeningRequest;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'active_requests'  => ScreeningRequest::whereIn('status', ['new', 'in_progress'])->count(),
            'flagged_cases'    => ScreeningRequest::where('status', 'flagged')->count(),
            'completed_today'  => ScreeningRequest::where('status', 'complete')->whereDate('updated_at', today())->count(),
            'total_customers'  => Customer::count(),
            'unpaid_invoices'  => Invoice::where('status', 'unpaid')->count(),
        ];

        $recentRequests = ScreeningRequest::with('customer')
            ->latest()
            ->take(10)
            ->get();

        $pendingRequests = ScreeningRequest::with('customer')
            ->where('status', 'new')
            ->latest()
            ->take(10)
            ->get();

        return view('dashboard.index', compact('stats', 'recentRequests', 'pendingRequests'));
    }
}
