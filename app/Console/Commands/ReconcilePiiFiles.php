<?php

namespace App\Console\Commands;

use App\Models\AdminAuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Finds candidate PII files under the candidate-documents tree that no live DB
 * row references — orphans left by a cascade-deleted candidate/request or by a
 * unlink that failed during redaction. Reports them by default; --delete
 * removes them (audit-logged). Safe to run repeatedly.
 */
class ReconcilePiiFiles extends Command
{
    protected $signature = 'pii:reconcile-files {--delete : Delete orphaned files instead of only listing them}';

    protected $description = 'Report (or delete) candidate PII files on disk with no referencing DB row';

    /** Disks candidate PII uploads may live on. */
    private const DISKS = ['client_local', 'local'];

    private const PREFIX = 'candidate-documents';

    public function handle(): int
    {
        // Every path any live row points at — a file matching one of these is NOT an orphan.
        $referenced = DB::table('candidate_documents')->whereNotNull('file_path')->pluck('file_path')
            ->merge(DB::table('consent_records')->whereNotNull('evidence_file_path')->pluck('evidence_file_path'))
            ->flip();

        $delete = (bool) $this->option('delete');
        $orphans = [];
        $deleted = 0;

        foreach (self::DISKS as $diskName) {
            $disk = Storage::disk($diskName);
            foreach ($disk->allFiles(self::PREFIX) as $path) {
                if ($referenced->has($path)) {
                    continue;
                }

                $orphans[] = "{$diskName}:{$path}";
                if ($delete && $disk->delete($path)) {
                    $deleted++;
                }
            }
        }

        if (empty($orphans)) {
            $this->info('No orphaned candidate PII files found.');

            return self::SUCCESS;
        }

        $this->warn(count($orphans).' orphaned file(s) found:');
        foreach ($orphans as $o) {
            $this->line("  {$o}");
        }

        if ($delete) {
            AdminAuditLog::record('pdpa.orphan_files_deleted', null, [
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
