<?php

namespace App\Console\Commands;

use App\Models\AdminAuditLog;
use App\Models\BusinessHoliday;
use App\Services\BusinessHours;
use App\Services\HolidayProvider\CalendarificProvider;
use Illuminate\Console\Command;

/**
 * php artisan holidays:sync --year=2026 --country=MY
 *
 * Idempotent: existing dates are skipped. Safe to schedule annually.
 */
class SyncHolidays extends Command
{
    protected $signature = 'holidays:sync
                            {--year= : Year to sync (defaults to current year)}
                            {--country=MY : ISO 3166-1 alpha-2 country code}';

    protected $description = 'Sync public holidays from Calendarific into business_holidays';

    public function handle(CalendarificProvider $provider): int
    {
        $year = (int) ($this->option('year') ?: now()->year);
        $country = strtoupper($this->option('country'));

        $this->line("Fetching {$country} holidays for {$year} from Calendarific…");

        try {
            $holidays = $provider->fetch($year, $country);
        } catch (\Throwable $e) {
            $this->error('Fetch failed: '.$e->getMessage());
            return self::FAILURE;
        }

        if (empty($holidays)) {
            $this->warn("No holidays returned. The API may not yet have data for {$year}/{$country}.");
            return self::SUCCESS;
        }

        $this->info('Returned '.count($holidays).' holidays.');

        $created = 0;
        $skipped = 0;
        foreach ($holidays as $h) {
            if (BusinessHoliday::where('date', $h['date'])->exists()) {
                $skipped++;
                continue;
            }
            BusinessHoliday::create($h);
            $created++;
            $this->line("  + {$h['date']} · {$h['label']}");
        }

        BusinessHours::flushHolidayCache();

        AdminAuditLog::record('holidays.synced', null, [
            'source'  => 'calendarific',
            'year'    => $year,
            'country' => $country,
            'created' => $created,
            'skipped' => $skipped,
            'trigger' => 'cli',
        ]);

        $this->info("Done. Added {$created}, skipped {$skipped}.");
        return self::SUCCESS;
    }
}
