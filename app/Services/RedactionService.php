<?php

namespace App\Services;

use App\Models\AdminAuditLog;
use App\Models\RequestCandidate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Redacts a candidate's PII while preserving structural records for auditability.
 *
 * Used for:
 *   - Retention expiry (scheduled purge)
 *   - Erasure requests (DSAR type=erasure)
 *
 * The candidate row is NOT deleted. Names, identity numbers, mobile, remarks
 * are replaced with redaction markers, and the blind-index hash is cleared so
 * the candidate is no longer findable by IC. Findings comments are replaced
 * with a marker. Uploaded PII files — candidate documents (NRIC/passport/
 * resume) and consent evidence scans — are DELETED from disk; the consent
 * record rows are kept (proof consent was given) but their file paths cleared.
 *
 * Issued PDF report files are NOT modified — they were sent to the client and
 * remain immutable. Payment slips/receipts are financial records under a
 * separate retention policy and are out of scope for per-candidate erasure.
 */
class RedactionService
{
    public const MARKER_NAME = '[REDACTED — PII removed]';

    public const MARKER_ID = '[REDACTED]';

    public const MARKER_MOBILE = '[REDACTED]';

    /** Disks a customer/admin-uploaded PII file may live on, in lookup order. */
    private const FILE_DISKS = ['client_local', 'local'];

    /**
     * Redact a candidate. Idempotent — running twice has no further effect.
     */
    public static function redactCandidate(RequestCandidate $candidate, string $reason): void
    {
        if ($candidate->isRedacted()) {
            return;
        }

        $candidate->loadMissing('documents', 'consentRecords');

        // Gather the physical files to destroy BEFORE the transaction — the DB
        // work removes the rows/paths that point at them.
        $filesToDelete = [];
        foreach ($candidate->documents as $doc) {
            if ($doc->file_path) {
                $filesToDelete[] = $doc->file_path;
            }
        }
        foreach ($candidate->consentRecords as $consent) {
            if ($consent->evidence_file_path) {
                $filesToDelete[] = $consent->evidence_file_path;
            }
        }

        $original = [
            'id' => $candidate->id,
            'name' => $candidate->name,
            'identity_number' => self::partialMask($candidate->identity_number),
            'reason' => $reason,
            'documents' => $candidate->documents->count(),
            'consent_files' => $candidate->consentRecords->whereNotNull('evidence_file_path')->count(),
        ];

        DB::transaction(function () use ($candidate, $reason) {
            // Mask identifying fields on the candidate record and clear the
            // blind-index hash so a redacted candidate can't be found by IC.
            $maskedId = self::partialMask($candidate->identity_number);
            $candidate->update([
                'name' => self::MARKER_NAME,
                'identity_number' => $maskedId,
                'identity_number_hash' => null,
                'mobile' => $candidate->mobile ? self::MARKER_MOBILE : null,
                'remarks' => null,
                'redacted_at' => now(),
                'redacted_reason' => $reason,
            ]);

            // Cascade through scope findings — replace narrative comments and
            // structured record details with a marker. Keep status + timestamps.
            $marker = ['comment' => '[REDACTED — '.$reason.']'];
            DB::table('candidate_scope_type')
                ->where('request_candidate_id', $candidate->id)
                ->whereNotNull('findings')
                ->update(['findings' => json_encode($marker)]);

            // Delete the candidate's uploaded documents; keep consent rows as
            // proof-of-consent but null the scanned evidence file reference.
            $candidate->documents()->delete();
            $candidate->consentRecords()
                ->whereNotNull('evidence_file_path')
                ->update(['evidence_file_path' => null]);
        });

        // Physical files are deleted after the DB commit (unlink isn't
        // transactional). Best-effort; failures are recorded so an operator
        // can follow up rather than silently leaving PII on disk.
        $deleted = [];
        $failed = [];
        foreach (array_unique($filesToDelete) as $path) {
            self::deleteFromAnyDisk($path) ? $deleted[] = $path : $failed[] = $path;
        }

        AdminAuditLog::record('pdpa.candidate_redacted', null, $original + [
            'files_deleted' => $deleted,
            'files_failed' => $failed,
        ]);
    }

    /**
     * Delete a stored file from whichever cross-app disk holds it. Returns true
     * if the file is gone (deleted now, or already absent), false only if it
     * exists somewhere and could not be removed.
     */
    private static function deleteFromAnyDisk(string $path): bool
    {
        $existedSomewhere = false;
        foreach (self::FILE_DISKS as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                $existedSomewhere = true;
                if (! Storage::disk($disk)->delete($path)) {
                    return false;
                }
            }
        }

        return true; // deleted, or never present on any disk
    }

    /**
     * Mask a Malaysian-style identity number while keeping its structure detectable.
     * "880101-14-5678" → "8***-**-***8" — enough to not be PII, but recognisable as MyKAD.
     */
    public static function partialMask(?string $value): ?string
    {
        if (! $value) {
            return null;
        }
        $len = strlen($value);
        if ($len <= 4) {
            return str_repeat('*', $len);
        }

        $first = substr($value, 0, 1);
        $last = substr($value, -1);
        $middle = preg_replace('/[A-Za-z0-9]/', '*', substr($value, 1, $len - 2));

        return $first.$middle.$last;
    }
}
