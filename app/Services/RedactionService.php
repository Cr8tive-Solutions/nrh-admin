<?php

namespace App\Services;

use App\Models\AdminAuditLog;
use App\Models\RequestCandidate;
use Illuminate\Support\Facades\DB;

/**
 * Redacts a candidate's PII while preserving structural records for auditability.
 *
 * Used for:
 *   - Retention expiry (scheduled purge)
 *   - Erasure requests (DSAR type=erasure)
 *
 * The candidate row is NOT deleted. Names, identity numbers, mobile, remarks
 * are replaced with redaction markers. Findings comments are replaced with
 * a marker referencing the redaction reason. Issued PDF report files are
 * not modified — they were sent to the client and remain immutable.
 */
class RedactionService
{
    public const MARKER_NAME    = '[REDACTED — PII removed]';
    public const MARKER_ID      = '[REDACTED]';
    public const MARKER_MOBILE  = '[REDACTED]';

    /**
     * Redact a candidate. Idempotent — running twice has no further effect.
     */
    public static function redactCandidate(RequestCandidate $candidate, string $reason): void
    {
        if ($candidate->isRedacted()) {
            return;
        }

        $original = [
            'id'              => $candidate->id,
            'name'            => $candidate->name,
            'identity_number' => self::partialMask($candidate->identity_number),
            'reason'          => $reason,
        ];

        DB::transaction(function () use ($candidate, $reason) {
            // Mask identifying fields on the candidate record
            $maskedId = self::partialMask($candidate->identity_number);
            $candidate->update([
                'name'            => self::MARKER_NAME,
                'identity_number' => $maskedId,
                'mobile'          => $candidate->mobile ? self::MARKER_MOBILE : null,
                'remarks'         => null,
                'redacted_at'     => now(),
                'redacted_reason' => $reason,
            ]);

            // Cascade through scope findings — replace narrative comments and
            // structured record details with a marker. Keep status + timestamps.
            $marker = ['comment' => '[REDACTED — '.$reason.']'];
            DB::table('candidate_scope_type')
                ->where('request_candidate_id', $candidate->id)
                ->whereNotNull('findings')
                ->update(['findings' => json_encode($marker)]);
        });

        AdminAuditLog::record('pdpa.candidate_redacted', null, $original);
    }

    /**
     * Mask a Malaysian-style identity number while keeping its structure detectable.
     * "880101-14-5678" → "8***-**-***8" — enough to not be PII, but recognisable as MyKAD.
     */
    public static function partialMask(?string $value): ?string
    {
        if (! $value) return null;
        $len = strlen($value);
        if ($len <= 4) return str_repeat('*', $len);

        $first = substr($value, 0, 1);
        $last  = substr($value, -1);
        $middle = preg_replace('/[A-Za-z0-9]/', '*', substr($value, 1, $len - 2));
        return $first.$middle.$last;
    }
}
