<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;

/**
 * Shared-key encryption + blind index for sensitive columns (candidate
 * identity_number). Keyed off config('pii.key'), NOT APP_KEY, so both portals
 * — which have different APP_KEYs but one database — can read each other's
 * ciphertext. See config/pii.php.
 *
 * Keep this class identical in nrh-admin and nrh-intelligence.
 */
class Pii
{
    private static ?Encrypter $encrypter = null;

    private static ?string $rawKey = null;

    /** Encryption is active only when a PII key is configured. */
    public static function enabled(): bool
    {
        return self::rawKey() !== null;
    }

    private static function rawKey(): ?string
    {
        if (self::$rawKey !== null) {
            return self::$rawKey ?: null;
        }

        $configured = (string) config('pii.key', '');
        if ($configured === '') {
            self::$rawKey = '';

            return null;
        }

        $key = str_starts_with($configured, 'base64:')
            ? base64_decode(substr($configured, 7))
            : $configured;

        self::$rawKey = $key;

        return $key;
    }

    private static function encrypter(): Encrypter
    {
        if (self::$encrypter === null) {
            self::$encrypter = new Encrypter(self::rawKey(), 'AES-256-CBC');
        }

        return self::$encrypter;
    }

    /** Encrypt a value for storage. Returns the raw value unchanged when disabled. */
    public static function encrypt(?string $value): ?string
    {
        if ($value === null || $value === '' || ! self::enabled()) {
            return $value;
        }

        return self::encrypter()->encryptString($value);
    }

    /**
     * Decrypt a stored value. Transparently returns legacy plaintext that was
     * written before encryption was enabled (decryption fails → passthrough),
     * so activation needs no big-bang migration.
     */
    public static function decrypt(?string $value): ?string
    {
        if ($value === null || $value === '' || ! self::enabled()) {
            return $value;
        }

        try {
            return self::encrypter()->decryptString($value);
        } catch (DecryptException) {
            return $value; // legacy plaintext
        }
    }

    /** Normalise an identity number for hashing: alphanumerics only, uppercased. */
    public static function normalizeIdentity(?string $value): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $value));
    }

    /**
     * Deterministic blind index for exact-match lookups on an encrypted column.
     * Returns null when disabled or the value is empty.
     */
    public static function hash(?string $value): ?string
    {
        if ($value === null || ! self::enabled()) {
            return null;
        }

        $normalized = self::normalizeIdentity($value);
        if ($normalized === '') {
            return null;
        }

        return hash_hmac('sha256', $normalized, self::rawKey());
    }

    /** Test seam: forget memoised key/encrypter after config changes. */
    public static function flush(): void
    {
        self::$encrypter = null;
        self::$rawKey = null;
    }
}
