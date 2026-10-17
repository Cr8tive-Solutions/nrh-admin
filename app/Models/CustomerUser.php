<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerUser extends Model
{
    /**
     * The client portal's real role lives in Spatie's `roles` /
     * `model_has_roles` tables under the `customer_user` guard. This app does
     * not ship spatie/laravel-permission, so those tables are reached directly
     * — the morph type is the shared FQCN, identical in both apps.
     */
    public const ROLE_MODEL_TYPE = 'App\Models\CustomerUser';

    public const GUARD = 'customer_user';

    /**
     * Portal roles ordered from most to least privileged. Used to populate the
     * admin role picker and to derive the coarse `role` column below.
     */
    public const PORTAL_ROLES = ['Owner', 'Admin', 'Accounts', 'HR', 'Viewer'];

    /** Portal roles that map to the legacy `role` column value 'admin'. */
    public const ADMIN_PORTAL_ROLES = ['Owner', 'Admin'];

    protected $fillable = [
        'customer_id', 'name', 'email', 'password',
        'role', 'status', 'avatar',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'password' => 'hashed',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(CustomerUserInvitation::class);
    }

    public function latestInvitation(): HasOne
    {
        return $this->hasOne(CustomerUserInvitation::class)->latestOfMany();
    }

    /**
     * The portal roles assigned to this user. Eager-loadable, so the customer
     * page can list a whole team without a query per row.
     */
    public function portalRoles(): BelongsToMany
    {
        return $this->belongsToMany(PortalRole::class, 'model_has_roles', 'model_id', 'role_id')
            ->wherePivot('model_type', self::ROLE_MODEL_TYPE)
            ->where('roles.guard_name', self::GUARD);
    }

    /**
     * The user's portal role name, or null when none has been assigned — which
     * happens for accounts created before roles existed, or provisioned outside
     * the invite flow. Such a user can log in but sees almost nothing, so the
     * admin UI surfaces it rather than guessing.
     */
    public function portalRoleName(): ?string
    {
        return $this->portalRoles->first()?->name;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Point the user at exactly one portal role, writing Spatie's pivot directly
     * (this app does not ship spatie/laravel-permission) and keeping the legacy
     * `role` column in step so both portals agree on who is an admin. Returns
     * false (and logs) when the role does not exist.
     */
    public function assignPortalRole(string $roleName): bool
    {
        $role = PortalRole::forPortal()->where('name', $roleName)->first();

        if (! $role) {
            Log::warning('Portal role missing; cannot assign', [
                'customer_user_id' => $this->id,
                'role' => $roleName,
            ]);

            return false;
        }

        DB::transaction(function () use ($role, $roleName) {
            DB::table('model_has_roles')
                ->where('model_type', self::ROLE_MODEL_TYPE)
                ->where('model_id', $this->id)
                ->delete();

            DB::table('model_has_roles')->insert([
                'role_id' => $role->id,
                'model_type' => self::ROLE_MODEL_TYPE,
                'model_id' => $this->id,
            ]);

            $this->update([
                'role' => in_array($roleName, self::ADMIN_PORTAL_ROLES, true) ? 'admin' : 'user',
            ]);
        });

        $this->unsetRelation('portalRoles');

        return true;
    }
}
