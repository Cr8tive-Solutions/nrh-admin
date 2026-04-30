<?php

namespace App\Services\HolidayProvider;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Fetches public holidays from Calendarific (https://calendarific.com).
 *
 * Free tier: 1,000 requests/month, requires API key configured at
 * services.calendarific.key (env: CALENDARIFIC_API_KEY).
 *
 * Coverage: full Malaysian federal + state holidays, plus 230+ countries.
 *
 * Filters to "National holiday" type only — state-specific holidays
 * (e.g. Hari Hol Pahang, Sultan birthdays) are excluded so SLA timers
 * don't pause for the entire country when only one state is observing.
 * Add state holidays manually if NRH operates in a specific state.
 */
class CalendarificProvider
{
    private const BASE = 'https://calendarific.com/api/v2';

    /**
     * @return array<int, array{date: string, label: string}>
     */
    public function fetch(int $year, string $country = 'MY'): array
    {
        $key = config('services.calendarific.key');
        if (! $key) {
            throw new RuntimeException('Calendarific API key not configured. Set CALENDARIFIC_API_KEY in .env.');
        }

        $response = Http::timeout(15)
            ->retry(2, 250)
            ->acceptJson()
            ->get(self::BASE.'/holidays', [
                'api_key' => $key,
                'country' => strtolower($country),
                'year'    => $year,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException("Calendarific returned HTTP {$response->status()}");
        }

        $body = $response->json();
        $code = $body['meta']['code'] ?? null;
        if ($code !== 200) {
            $reason = $body['meta']['error_detail'] ?? $body['meta']['error_type'] ?? 'unknown error';
            throw new RuntimeException("Calendarific API error: {$reason}");
        }

        $holidays = $body['response']['holidays'] ?? [];

        return collect($holidays)
            ->filter(function ($h) {
                // Keep federal/national holidays only. State + observances skipped.
                $types = $h['type'] ?? [];
                return in_array('National holiday', $types, true);
            })
            ->map(function ($h) {
                return [
                    'date'  => $h['date']['iso'] ?? null,
                    'label' => $h['name'] ?? 'Public Holiday',
                ];
            })
            ->filter(fn ($h) => $h['date'] !== null)
            // Calendarific can return duplicates when a holiday spans multiple types — dedupe by date.
            ->unique('date')
            ->values()
            ->all();
    }
}
