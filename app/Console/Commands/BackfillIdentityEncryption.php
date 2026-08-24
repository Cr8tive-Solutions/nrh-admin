<?php

namespace App\Console\Commands;

use App\Models\RequestCandidate;
use App\Support\Pii;
use Illuminate\Console\Command;

/**
 * One-shot backfill after enabling PII encryption: encrypt any plaintext
 * identity_number rows and populate the blind-index hash. Idempotent — the
 * cast decrypts already-encrypted rows on read, so re-running re-encrypts the
 * same plaintext and recomputes the same hash. Run once in both apps' shared
 * DB (either app is fine — same database) after setting PII_KEY.
 */
class BackfillIdentityEncryption extends Command
{
    protected $signature = 'pii:backfill-identity {--dry-run : Report counts without writing} {--chunk=500}';

    protected $description = 'Encrypt existing candidate identity numbers and populate the blind index';

    public function handle(): int
    {
        if (! Pii::enabled()) {
            $this->error('PII_KEY is not configured — set it in .env first (config/pii.php). Aborting.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $processed = 0;
        $changed = 0;

        RequestCandidate::query()
            ->whereNotNull('identity_number')
            ->orderBy('id')
            ->chunkById((int) $this->option('chunk'), function ($candidates) use (&$processed, &$changed, $dryRun) {
                foreach ($candidates as $candidate) {
                    $processed++;

                    $rawStored = $candidate->getRawOriginal('identity_number');
                    $plain = $candidate->identity_number; // cast-decrypted (or legacy plaintext)
                    $wantHash = Pii::hash($plain);

                    // Already encrypted (raw differs from plaintext) AND hash present → skip.
                    $alreadyEncrypted = $rawStored !== null && $rawStored !== $plain;
                    if ($alreadyEncrypted && $candidate->identity_number_hash === $wantHash) {
                        continue;
                    }

                    $changed++;
                    if (! $dryRun) {
                        // Re-assigning through the cast encrypts. saveQuietly()
                        // skips events (no audit spam / hook), so set the hash here.
                        $candidate->identity_number = $plain;
                        $candidate->identity_number_hash = $wantHash;
                        $candidate->saveQuietly();
                    }
                }
            });

        $verb = $dryRun ? 'would be' : 'were';
        $this->info("Scanned {$processed} candidate(s); {$changed} {$verb} encrypted/indexed.");

        return self::SUCCESS;
    }
}
