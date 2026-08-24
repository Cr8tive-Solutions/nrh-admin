<?php

namespace App\Models\Concerns;

use App\Casts\EncryptedString;
use App\Support\Pii;
use Illuminate\Database\Eloquent\Builder;

/**
 * Encrypts `identity_number` at rest (shared PII key) and maintains a
 * deterministic blind-index column `identity_number_hash` for exact-match
 * lookups. No-op on data until PII_KEY is configured (see config/pii.php).
 *
 * Keep identical in nrh-admin and nrh-intelligence.
 */
trait EncryptsIdentityNumber
{
    public function initializeEncryptsIdentityNumber(): void
    {
        $this->mergeCasts(['identity_number' => EncryptedString::class]);
        // Maintained by the saving hook; fillable so redaction's update() can null it.
        $this->mergeFillable(['identity_number_hash']);
    }

    public static function bootEncryptsIdentityNumber(): void
    {
        static::saving(function ($model) {
            // Only maintain the blind index while encryption is active. When
            // disabled we don't touch the column at all, so the feature (and
            // its migration) is a true no-op until PII_KEY is set.
            if (! Pii::enabled()) {
                return;
            }

            // Recompute the blind index whenever the IC changes, UNLESS the
            // caller set the hash explicitly this save (e.g. redaction nulls it).
            if ($model->isDirty('identity_number') && ! $model->isDirty('identity_number_hash')) {
                $model->identity_number_hash = Pii::hash($model->identity_number);
            }
        });
    }

    /** Blind-index hash for a raw identity number (null when encryption is off). */
    public static function hashIdentity(?string $value): ?string
    {
        return Pii::hash($value);
    }

    /**
     * Exact-match on identity number. Uses the blind index when encryption is
     * on (the column is ciphertext and can't be compared directly), otherwise
     * a plain equality on the plaintext column.
     */
    public function scopeWhereIdentityNumber(Builder $query, ?string $value): Builder
    {
        if (Pii::enabled()) {
            return $query->where('identity_number_hash', Pii::hash($value));
        }

        return $query->where('identity_number', $value);
    }
}
