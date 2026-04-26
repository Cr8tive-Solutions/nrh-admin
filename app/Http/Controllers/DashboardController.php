<?php

namespace App\Http\Controllers;

use App\Models\Agreement;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\ScreeningRequest;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_cleared'   => ScreeningRequest::where('status', 'complete')->count(),
            'active_requests' => ScreeningRequest::whereIn('status', ['new', 'in_progress'])->count(),
            'flagged_cases'   => ScreeningRequest::where('status', 'flagged')->count(),
            'total_customers' => Customer::count(),
            'unpaid_invoices' => Invoice::where('status', 'unpaid')->count(),
        ];

        $expiringAgreements = Agreement::with('customer')
            ->whereDate('expiry_date', '<=', now()->addDays(60))
            ->whereDate('expiry_date', '>=', now())
            ->orderBy('expiry_date')
            ->take(8)
            ->get();

        $flaggedRequests = ScreeningRequest::with(['customer', 'candidates'])
            ->where('status', 'flagged')
            ->latest()
            ->take(5)
            ->get();

        $recentRequests = ScreeningRequest::with('customer')
            ->latest()
            ->take(8)
            ->get();

        // Weekly volume — last 7 days (PostgreSQL)
        $rawVolume = ScreeningRequest::selectRaw("(created_at::date) as date, COUNT(*) as count")
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupByRaw("created_at::date")
            ->orderByRaw("created_at::date")
            ->get()
            ->keyBy('date');

        $weeklyVolume = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date  = now()->subDays($i)->format('Y-m-d');
            $weeklyVolume->push([
                'label' => now()->subDays($i)->format('D'),
                'count' => (int) ($rawVolume[$date]->count ?? 0),
            ]);
        }

        return view('dashboard.index', compact('stats', 'flaggedRequests', 'recentRequests', 'weeklyVolume', 'expiringAgreements'));
    }
}
