<?php

namespace App\Services;

use App\Models\BusinessHoliday;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Computes elapsed time within configured business hours,
 * skipping weekends and admin-managed public holidays.
 *
 * Default config: Mon–Fri, 09:00–18:00 (9 hours/day) in Asia/Kuala_Lumpur.
 */
class BusinessHours
{
    /** Cached holiday set per request, keyed by ISO date string. */
    private static ?array $holidayCache = null;

    /**
     * Business seconds elapsed from $start to $end.
     * Returns 0 when end <= start.
     */
    public static function secondsBetween(CarbonInterface $start, CarbonInterface $end): int
    {
        if ($end <= $start) {
            return 0;
        }

        $tz = config('business_hours.timezone');
        $startHour = (int) config('business_hours.start_hour');
        $endHour = (int) config('business_hours.end_hour');
        $workingDays = config('business_hours.working_days');

        $cursor = Carbon::parse($start)->setTimezone($tz);
        $endLocal = Carbon::parse($end)->setTimezone($tz);

        $totalSeconds = 0;
        // Hard cap to avoid runaway loops on bad data
        $maxIterations = 366 * 5;

        while ($cursor < $endLocal && $maxIterations-- > 0) {
            $dayStart = $cursor->copy()->setTime($startHour, 0, 0);
            $dayEnd = $cursor->copy()->setTime($endHour, 0, 0);

            $isWorkingDay = in_array($cursor->dayOfWeek, $workingDays, true)
                && ! self::isHoliday($cursor);

            if ($isWorkingDay) {
                // The slice we evaluate today is bounded by [max(cursor, dayStart), min(end, dayEnd)]
                $sliceStart = $cursor->greaterThan($dayStart) ? $cursor : $dayStart;
                $sliceEnd = $endLocal->lessThan($dayEnd) ? $endLocal : $dayEnd;

                if ($sliceEnd > $sliceStart) {
                    $totalSeconds += $sliceEnd->getTimestamp() - $sliceStart->getTimestamp();
                }
            }

            // Advance cursor to the start of the next working day.
            $cursor = $cursor->copy()->addDay()->setTime($startHour, 0, 0);
        }

        return max(0, $totalSeconds);
    }

    public static function hoursBetween(CarbonInterface $start, CarbonInterface $end): float
    {
        return round(self::secondsBetween($start, $end) / 3600, 2);
    }

    private static function isHoliday(CarbonInterface $date): bool
    {
        if (self::$holidayCache === null) {
            try {
                self::$holidayCache = BusinessHoliday::query()
                    ->pluck('date')
                    ->map(fn ($d) => Carbon::parse($d)->toDateString())
                    ->flip()
                    ->all();
            } catch (\Throwable $e) {
                // Table may not exist yet during first migration / tests.
                self::$holidayCache = [];
            }
        }
        return isset(self::$holidayCache[$date->toDateString()]);
    }

    /** For unit tests / manual cache bust between admin updates. */
    public static function flushHolidayCache(): void
    {
        self::$holidayCache = null;
    }
}
