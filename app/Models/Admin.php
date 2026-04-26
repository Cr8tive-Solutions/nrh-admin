<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class Admin extends Model
{
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'avatar',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

    protected $casts = [
        'password'                  => 'hashed',
        'two_factor_secret'         => 'encrypted',
        'two_factor_recovery_codes' => 'encrypted:array',
        'two_factor_confirmed_at'   => 'datetime',
    ];

    /** Memoized per-request set of effective permission keys. */
    private ?array $effectiveKeysCache = null;

    /**
     * Per-user permission overrides. Pivot column `granted` (boolean):
     *   true  → force-grant this permission regardless of role
     *   false → force-revoke this permission even if the role grants it
     */
    public function permissionOverrides(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'admin_user_permissions',
            'admin_id',
            'admin_permission_id',
        )->withPivot('granted')->withTimestamps();
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function verifyPassword(string $password): bool
    {
        return Hash::check($password, $this->password);
    }

    /**
     * Resolve effective permission keys for this admin.
     * super_admin always has every permission.
     * Otherwise: role permissions ∪ overrides(granted=true) − overrides(granted=false).
     */
    public function effectivePermissionKeys(): array
    {
        if ($this->effectiveKeysCache !== null) {
            return $this->effectiveKeysCache;
        }

        if ($this->isSuperAdmin()) {
            return $this->effectiveKeysCache = Permission::pluck('key')->all();
        }

        $rolePerms = DB::table('admin_role_permissions')
            ->join('admin_permissions', 'admin_permissions.id', '=', 'admin_role_permissions.admin_permission_id')
            ->where('admin_role_permissions.role', $this->role)
            ->pluck('admin_permissions.key')
            ->all();

        $overrides = DB::table('admin_user_permissions')
            ->join('admin_permissions', 'admin_permissions.id', '=', 'admin_user_permissions.admin_permission_id')
            ->where('admin_user_permissions.admin_id', $this->id)
            ->select('admin_permissions.key', 'admin_user_permissions.granted')
            ->get();

        $set = array_flip($rolePerms);
        foreach ($overrides as $o) {
            if ($o->granted) {
                $set[$o->key] = true;
            } else {
                unset($set[$o->key]);
            }
        }

        return $this->effectiveKeysCache = array_keys($set);
    }

    public function can($abilities, $arguments = []): bool
    {
        $key = is_array($abilities) ? ($abilities[0] ?? null) : $abilities;
        if (! is_string($key)) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        return in_array($key, $this->effectivePermissionKeys(), true);
    }

    public function hasEnabledTwoFactor(): bool
    {
        return ! is_null($this->two_factor_secret) && ! is_null($this->two_factor_confirmed_at);
    }

    /**
     * Generate a fresh set of one-time recovery codes (8 × 10-char codes).
     * Caller is responsible for persisting via the encrypted cast.
     */
    public static function generateRecoveryCodes(int $count = 8): array
    {
        return collect(range(1, $count))
            ->map(fn () => Str::random(5).'-'.Str::random(5))
            ->all();
    }

    /**
     * If $code matches one of this admin's recovery codes, consume it
     * (remove from the list, persist) and return true.
     */
    public function consumeRecoveryCode(string $code): bool
    {
        $codes = $this->two_factor_recovery_codes ?? [];
        $normalized = trim($code);

        $idx = array_search($normalized, $codes, true);
        if ($idx === false) {
            return false;
        }

        unset($codes[$idx]);
        $this->two_factor_recovery_codes = array_values($codes);
        $this->save();

        return true;
    }
}
