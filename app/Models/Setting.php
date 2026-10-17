<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Shared key/value settings (app_settings table), editable from the admin
 * portal and read by both portals. Keep this model identical across both repos.
 *
 * Deliberately NOT cached across requests: the two portals are separate apps
 * with separate cache stores, so a cached value in one could not be invalidated
 * when the other writes. Each read is a single indexed query, memoised only for
 * the lifetime of the current process so repeated reads in one request are free.
 */
class Setting extends Model
{
    protected $table = 'app_settings';

    protected $fillable = ['key', 'value'];

    /** @var array<string, string|null> per-process memo */
    private static array $memo = [];

    /**
     * Read a setting. Returns $default when the key is unset, the table is
     * missing (fresh DB / mid-migration), or the DB is unreachable — so this is
     * safe to call from a service provider's boot().
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$memo)) {
            return self::$memo[$key] ?? $default;
        }

        try {
            $value = static::query()->where('key', $key)->value('value');
        } catch (\Throwable $e) {
            // Table absent or DB down — don't memoise, so a later call can retry.
            return $default;
        }

        self::$memo[$key] = $value;

        return $value ?? $default;
    }

    /** Write a setting and refresh the per-process memo. */
    public static function setValue(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        self::$memo[$key] = (string) $value;
    }

    /** Clear the per-process memo (used between tests). */
    public static function flushMemo(): void
    {
        self::$memo = [];
    }

    /**
     * SST rate as a decimal fraction (e.g. 0.06). Falls back to the passed
     * default (the config/billing.php value) when unset.
     */
    public static function sstRate(float $default): float
    {
        $value = self::getValue('sst_rate', $default);

        return is_numeric($value) ? (float) $value : $default;
    }
}
