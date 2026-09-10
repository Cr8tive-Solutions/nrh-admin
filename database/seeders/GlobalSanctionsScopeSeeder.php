<?php

namespace Database\Seeders;

use App\Models\ScopeType;
use Illuminate\Database\Seeder;

/**
 * Refreshes the description of the consolidated "Global Sanctions - Global Risk"
 * scope in every country so it lists all seven sanctions bodies covered.
 *
 * Split out of DataReportScopeSeeder so the description can be re-applied without
 * re-running that seeder's destructive consolidation (it deletes merged-away scopes).
 * Idempotent — safe to run on any environment.
 */
class GlobalSanctionsScopeSeeder extends Seeder
{
    public function run(): void
    {
        $updated = ScopeType::whereRaw("name ILIKE '%GLOBAL SANCTION%'")
            ->update(['description' => DataReportScopeSeeder::GLOBAL_SANCTIONS_DESCRIPTION]);

        $this->command?->info("Updated {$updated} Global Sanctions scope(s).");
    }
}
