<?php

namespace App\Services\HolidayProvider;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Fetches public holidays from the free Nager.Date API.
 *
 * No API key required, no rate limits. Federal holidays only —
 * state-specific Malaysian holidays (Hari Hol Pahang, etc.) are not
 * included. If state-level coverage becomes necessary, swap this for
 * a Calendarific-backed provider implementing the same shape.
 *
 * @see https://date.nager.at/Api
 */
class NagerDateProvider
{
    private const BASE = 'https://date.nager.at/api/v3';

    /**
     * Fetch public holidays for a given year + ISO 3166-1 alpha-2 country code.
     *
     * @return array<int, array{date: string, label: string}>
     */
    public function fetch(int $year, string $country = 'MY'): array
    {
        $url = self::BASE."/PublicHolidays/{$year}/".strtoupper($country);

        $response = Http::timeout(10)
            ->retry(2, 250)
            ->acceptJson()
            ->get($url);

        if (! $response->successful()) {
            throw new RuntimeException("Nager.Date API returned HTTP {$response->status()} for {$year}/{$country}");
        }

        $data = $response->json();
        if (! is_array($data)) {
            throw new RuntimeException('Nager.Date API returned an unexpected response shape.');
        }

        return collect($data)
            ->map(fn ($h) => [
                // Nager fields: date (YYYY-MM-DD), name (English), localName, types
                'date'  => $h['date'] ?? null,
                'label' => $h['localName'] ?: $h['name'] ?? 'Public Holiday',
            ])
            ->filter(fn ($h) => $h['date'] !== null)
            ->values()
            ->all();
    }
}
