<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\BusinessHoliday;
use App\Services\BusinessHours;
use App\Services\HolidayProvider\CalendarificProvider;
use Illuminate\Http\Request;

class BusinessHolidayController extends Controller
{
    public function index()
    {
        $holidays = BusinessHoliday::orderBy('date')->get()
            ->groupBy(fn ($h) => $h->date->format('Y'));

        return view('config.holidays.index', compact('holidays'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date'  => 'required|date|unique:business_holidays,date',
            'label' => 'required|string|max:120',
        ]);

        BusinessHoliday::create($data);
        BusinessHours::flushHolidayCache();

        return back()->with('success', 'Holiday added.');
    }

    public function destroy(BusinessHoliday $holiday)
    {
        $holiday->delete();
        BusinessHours::flushHolidayCache();

        return back()->with('success', 'Holiday removed.');
    }

    /**
     * Sync holidays from Calendarific for a given year. Existing dates are
     * preserved (skipped) so this is safe to re-run. National (federal)
     * holidays only — state holidays should be added manually.
     */
    public function syncFromApi(Request $request, CalendarificProvider $provider)
    {
        $data = $request->validate([
            'year' => 'required|integer|min:2020|max:2050',
        ]);

        try {
            $holidays = $provider->fetch($data['year'], 'MY');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to fetch from Calendarific: '.$e->getMessage());
        }

        if (empty($holidays)) {
            return back()->with('warning', "No national holidays returned for {$data['year']}. The API may not yet have data for that year.");
        }

        $created = 0;
        $skipped = 0;
        foreach ($holidays as $h) {
            if (BusinessHoliday::where('date', $h['date'])->exists()) {
                $skipped++;
                continue;
            }
            BusinessHoliday::create($h);
            $created++;
        }

        BusinessHours::flushHolidayCache();

        AdminAuditLog::record('holidays.synced', null, [
            'source'  => 'calendarific',
            'year'    => $data['year'],
            'country' => 'MY',
            'created' => $created,
            'skipped' => $skipped,
        ]);

        return back()->with('success', "Synced from Calendarific · {$created} added, {$skipped} already on file ({$data['year']}).");
    }
}
