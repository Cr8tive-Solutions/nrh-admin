<?php

namespace App\Console\Commands;

use App\Models\AdminAuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Finds stored files that no live DB row references — orphans left by a
 * cascade-deleted candidate/request, a nulled evidence path, or an unlink that
 * failed during redaction. Reports them by default; --delete removes them
 * (audit-logged). Safe to run repeatedly.
 *
 * Covers candidate PII (documents, consent evidence) and finance/compliance
 * artefacts (DSAR evidence, payment slips, invoice receipts). Report PDFs
 * (reports/ prefix) are intentionally NOT swept — they are immutable records.
 */
class ReconcilePiiFiles extends Command
{
    protected $signature = 'pii:reconcile-files {--delete : Delete orphaned files instead of only listing them}';

    protected $description = 'Report (or delete) stored PII/finance files with no referencing DB row';

    /** Disks uploads may live on (client- or admin-written). */
    private const DISKS = ['client_local', 'local'];

    /** Storage prefixes to sweep. reports/ is deliberately excluded. */
    private const PREFIXES = ['candidate-documents', 'consent', 'dsar', 'payment-slips', 'receipts'];

    public function handle(): int
    {
        // Every path any live row points at, across all file-bearing columns.
        // A scanned file matching one of these is NOT an orphan.
        $referenced = collect()
            ->merge(DB::table('candidate_documents')->whereNotNull('file_path')->pluck('file_path'))
            ->merge(DB::table('consent_records')->whereNotNull('evidence_file_path')->pluck('evidence_file_path'))
            ->merge(DB::table('data_subject_requests')->whereNotNull('evidence_file_path')->pluck('evidence_file_path'))
            ->merge(DB::table('screening_requests')->whereNotNull('payment_slip_path')->pluck('payment_slip_path'))
            ->merge(DB::table('invoice_payment_receipts')->whereNotNull('file_path')->pluck('file_path'))
            ->flip();

        $delete = (bool) $this->option('delete');
        $orphans = [];
        $deleted = 0;

        foreach (self::DISKS as $diskName) {
            $disk = Storage::disk($diskName);
            foreach (self::PREFIXES as $prefix) {
                foreach ($disk->allFiles($prefix) as $path) {
                    if ($referenced->has($path)) {
                        continue;
                    }

                    $orphans[] = "{$diskName}:{$path}";
                    if ($delete && $disk->delete($path)) {
                        $deleted++;
                    }
                }
            }
        }

        if (empty($orphans)) {
            $this->info('No orphaned files found.');

            return self::SUCCESS;
        }

        $this->warn(count($orphans).' orphaned file(s) found:');
        foreach ($orphans as $o) {
            $this->line("  {$o}");
        }

        if ($delete) {
            AdminAuditLog::record('storage.orphan_files_deleted', null, [
                'count' => $deleted,
                'files' => $orphans,
            ]);
            $this->info("Deleted {$deleted} orphaned file(s).");
        } else {
            $this->comment('Run again with --delete to remove them.');
        }

        return self::SUCCESS;
    }
}
