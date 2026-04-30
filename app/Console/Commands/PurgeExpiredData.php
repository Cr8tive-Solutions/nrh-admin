<?php

namespace App\Console\Commands;

use App\Models\AdminAuditLog;
use App\Models\RequestCandidate;
use App\Models\RetentionPolicy;
use App\Services\RedactionService;
use Illuminate\Console\Command;

/**
 * Daily purge: redacts PII on candidate records older than the configured
 * retention period. Idempotent — already-redacted rows are skipped.
 *
 * Schedule via Console kernel: $schedule->command('pdpa:purge-expired')->dailyAt('02:30');
 */
class PurgeExpiredData extends Command
{
    protected $signature = 'pdpa:purge-expired
                            {--dry-run : Show what would be redacted without applying}
                            {--limit=1000 : Maximum records to process per run}';

    protected $description = 'Redact PII on candidate records that have passed the configured retention period';

    public function handle(): int
    {
        $policy = RetentionPolicy::where('entity_type', 'candidate')->where('enabled', true)->first();

        if (! $policy) {
            $this->info('Candidate retention policy is disabled. Skipping.');
            return self::SUCCESS;
        }

        $threshold = now()->subDays($policy->retention_days);
        $limit = (int) $this->option('limit');
        $dryRun = (bool) $this->option('dry-run');

        $this->line("Retention threshold: {$threshold->toDateTimeString()} ({$policy->retention_days} days)");
        $this->line($dryRun ? 'DRY RUN — no changes will be applied.' : 'Live run.');

        $candidates = RequestCandidate::whereNull('redacted_at')
            ->where('created_at', '<', $threshold)
            ->limit($limit)
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('No candidates eligible for purge.');
            return self::SUCCESS;
        }

        $this->info("Eligible candidates: {$candidates->count()}");
        $bar = $this->output->createProgressBar($candidates->count());
        $bar->start();

        $redacted = 0;
        foreach ($candidates as $c) {
            if (! $dryRun) {
                RedactionService::redactCandidate($c, 'retention_expiry');
                $redacted++;
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();

        if (! $dryRun) {
            AdminAuditLog::record('pdpa.retention_purge_scheduled', null, [
                'threshold' => $threshold->toIso8601String(),
                'count'     => $redacted,
            ]);
            $this->info("Redacted {$redacted} candidates.");
        } else {
            $this->info("Would have redacted {$candidates->count()} candidates (dry run).");
        }

        return self::SUCCESS;
    }
}
