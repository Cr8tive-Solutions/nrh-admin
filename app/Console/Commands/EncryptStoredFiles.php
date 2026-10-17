<?php

namespace App\Console\Commands;

use App\Models\AdminAuditLog;
use App\Support\EncryptedFilesystemAdapter;
use App\Support\Pii;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Encrypts the plaintext backlog on both private disks after PII_KEY is set:
 * this app's `local` (report PDFs, consent/DSAR evidence) and `client_local`
 * (the client portal's slips, receipts, candidate documents, consent evidence).
 * New writes are already sealed by EncryptedFilesystemAdapter; reads accept
 * both forms, so this can run at any time. Already-encrypted files are skipped,
 * so it is safe to re-run.
 *
 * Dry run by default; --apply rewrites each file in place (temp file + atomic
 * rename, after verifying the ciphertext decrypts back to the original bytes).
 */
class EncryptStoredFiles extends Command
{
    protected $signature = 'pii:encrypt-files {--apply : Encrypt the files instead of only listing them}';

    protected $description = 'Encrypt plaintext files on the private storage disks (requires PII_KEY)';

    private const DISKS = ['local', 'client_local'];

    public function handle(): int
    {
        if (! Pii::enabled()) {
            $this->error('PII_KEY is not set — nothing can be encrypted.');

            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $plain = 0;
        $encrypted = 0;
        $failed = 0;

        foreach (self::DISKS as $diskName) {
            $disk = Storage::disk($diskName);

            foreach ($disk->allFiles() as $path) {
                if (str_starts_with(basename($path), '.')) {
                    continue; // .gitignore and friends
                }

                $full = $disk->path($path);
                $bytes = file_get_contents($full);

                if ($bytes === false) {
                    $this->error("  unreadable: {$diskName}:{$path}");
                    $failed++;

                    continue;
                }

                if (EncryptedFilesystemAdapter::isEncrypted($bytes)) {
                    continue;
                }

                $plain++;

                if (! $apply) {
                    $this->line("  {$diskName}:{$path}");

                    continue;
                }

                try {
                    $this->encryptInPlace($full, $bytes);
                    $encrypted++;
                } catch (Throwable $e) {
                    $this->error("  failed: {$diskName}:{$path} — {$e->getMessage()}");
                    $failed++;
                }
            }
        }

        if (! $apply) {
            $this->info("{$plain} plaintext file(s) found. Re-run with --apply to encrypt them.");

            return $failed ? self::FAILURE : self::SUCCESS;
        }

        AdminAuditLog::record('storage.files_encrypted', null, [
            'encrypted' => $encrypted,
            'failed' => $failed,
        ]);

        $this->info("Encrypted {$encrypted} file(s); {$failed} failure(s).");

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function encryptInPlace(string $full, string $bytes): void
    {
        $sealed = EncryptedFilesystemAdapter::encrypt($bytes);

        if (EncryptedFilesystemAdapter::decrypt($sealed) !== $bytes) {
            throw new \RuntimeException('round-trip verification failed');
        }

        $tmp = $full.'.enc-tmp';
        if (file_put_contents($tmp, $sealed, LOCK_EX) !== strlen($sealed)) {
            @unlink($tmp);
            throw new \RuntimeException('could not write temp file');
        }

        @chmod($tmp, fileperms($full) & 0777);

        if (! rename($tmp, $full)) {
            @unlink($tmp);
            throw new \RuntimeException('could not replace original');
        }
    }
}
