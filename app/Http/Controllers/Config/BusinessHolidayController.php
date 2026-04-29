<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Models\BusinessHoliday;
use App\Services\BusinessHours;
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
}
