<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class Admin extends Model
{
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'avatar',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'password' => 'hashed',
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
}
