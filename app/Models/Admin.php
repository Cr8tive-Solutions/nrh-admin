<?php

namespace App\Models;

use App\Traits\HasHashid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use PragmaRX\Google2FA\Google2FA;

class Admin extends Model
{
    use HasHashid;

    protected $fillable = [
        'name',
        'email',
        'password',
        'must_change_password',
        'role',
        'status',
        'avatar',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

    protected $casts = [
        'password' => 'hashed',
        'must_change_password' => 'boolean',
        'two_factor_secret' => 'encrypted',
        'two_factor_recovery_codes' => 'encrypted:array',
        'two_factor_confirmed_at' => 'datetime',
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

    /**
     * Public URL of the uploaded avatar, or null if none.
     * Avatars are stored on the 'public' disk under avatars/{filename}.
     */
    public function avatarUrl(): ?string
    {
        if (! $this->avatar) {
            return null;
        }

        return Storage::disk('public')->url($this->avatar);
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name ?? ''));
        $first = $parts[0][0] ?? '';
        $last = isset($parts[1]) ? $parts[1][0] : '';

        return strtoupper($first.$last) ?: 'A';
    }

    /**
     * Policy for every newly set staff password (self-service change and
     * accounts created by another admin). No breached-password lookup: the
     * portal makes no external calls.
     */
    public static function passwordRule(): Password
    {
        return Password::min(12)->letters()->mixedCase()->numbers();
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
     * Verify an authenticator (TOTP) code and burn its time-step.
     *
     * A ±1 step window tolerates clock drift, but a code is only accepted if
     * its step is newer than the last one used, so a code (or an older one
     * from the window) can never be replayed. The accepted step is persisted.
     */
    public function consumeTotpCode(string $code): bool
    {
        if (! $this->two_factor_secret) {
            return false;
        }

        $timestep = (new Google2FA)->verifyKeyNewer(
            $this->two_factor_secret,
            trim($code),
            (int) $this->two_factor_last_used_timestep,
            1,
        );

        if ($timestep === false) {
            return false;
        }

        $this->two_factor_last_used_timestep = $timestep;
        $this->save();

        return true;
    }

    /**
     * If $code matches one of this admin's recovery codes, consume it
     * (remove from the list, persist) and return true.
     *
     * Every stored code is compared in constant time, so response timing does
     * not reveal how much of a guess matched.
     */
    public function consumeRecoveryCode(string $code): bool
    {
        $codes = $this->two_factor_recovery_codes ?? [];
        $normalized = trim($code);

        $idx = false;
        foreach ($codes as $i => $stored) {
            if (hash_equals((string) $stored, $normalized) && $idx === false) {
                $idx = $i;
            }
        }

        if ($idx === false) {
            return false;
        }

        unset($codes[$idx]);
        $this->two_factor_recovery_codes = array_values($codes);
        $this->save();

        return true;
    }
}
