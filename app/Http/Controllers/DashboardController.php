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
            'total_cleared' => ScreeningRequest::where('status', 'complete')->count(),
            'active_requests' => ScreeningRequest::whereIn('status', ['new', 'in_progress'])->count(),
            'flagged_cases' => ScreeningRequest::whereHas('candidates', fn ($q) => $q->where('status', 'flagged'))->count(),
            'total_customers' => Customer::count(),
            'unpaid_invoices' => Invoice::where('status', 'unpaid')->count(),
        ];

        $expiringAgreements = Agreement::with('customer')
            ->whereDate('expiry_date', '<=', now()->inDisplayTz()->addDays(60))
            ->whereDate('expiry_date', '>=', now()->inDisplayTz())
            ->orderBy('expiry_date')
            ->take(8)
            ->get();

        $flaggedRequests = ScreeningRequest::with(['customer', 'candidates'])
            ->whereHas('candidates', fn ($q) => $q->where('status', 'flagged'))
            ->latest()
            ->take(5)
            ->get();

        $recentRequests = ScreeningRequest::with(['customer', 'candidates'])
            ->latest()
            ->take(8)
            ->get();

        // Weekly volume — last 7 local days. created_at is UTC, so shift it before taking the date.
        $localDay = "(created_at AT TIME ZONE 'UTC' AT TIME ZONE ?)::date";
        $displayTimezone = config('app.display_timezone');
        $rawVolume = ScreeningRequest::selectRaw("{$localDay} as date, COUNT(*) as count", [$displayTimezone])
            ->where('created_at', '>=', now()->inDisplayTz()->subDays(6)->startOfDay()->utc())
            // By position: the same expression with a second binding would not match the select.
            ->groupByRaw('1')
            ->orderByRaw('1')
            ->get()
            ->keyBy('date');

        $weeklyVolume = collect();
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->inDisplayTz()->subDays($i);
            $date = $day->format('Y-m-d');
            $weeklyVolume->push([
                'label' => $day->format('D'),
                'count' => (int) ($rawVolume[$date]->count ?? 0),
            ]);
        }

        return view('dashboard.index', compact('stats', 'flaggedRequests', 'recentRequests', 'weeklyVolume', 'expiringAgreements'));
    }
}
