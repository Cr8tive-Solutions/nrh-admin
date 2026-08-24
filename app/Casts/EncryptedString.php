<?php

namespace App\Casts;

use App\Support\Pii;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Encrypts a string column at rest with the shared PII key (App\Support\Pii).
 * Reads transparently decrypt, falling back to legacy plaintext; writes encrypt
 * when a PII key is configured, otherwise pass through unchanged.
 */
class EncryptedString implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return Pii::decrypt($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return Pii::encrypt($value === null ? null : (string) $value);
    }
}
