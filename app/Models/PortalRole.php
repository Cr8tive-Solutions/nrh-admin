<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A client-portal role (Owner, Admin, Accounts, HR, Viewer).
 *
 * These rows belong to the client portal, which manages them through
 * spatie/laravel-permission. This app does not ship that package, so it reads
 * the `roles` table directly — deliberately named PortalRole to keep it clearly
 * distinct from `Permission`, which backs *this* portal's own admin RBAC.
 *
 * Read-write for role *assignment* only: never create, rename or delete rows
 * here. The permission sets attached to each role are owned by the client
 * portal's RolesSeeder.
 */
class PortalRole extends Model
{
    protected $table = 'roles';

    public $timestamps = true;

    protected $fillable = [];

    /** Only roles for the client portal's guard — never this app's own. */
    public function scopeForPortal(Builder $query): Builder
    {
        return $query->where('guard_name', CustomerUser::GUARD);
    }
}
