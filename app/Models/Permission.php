<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $table = 'admin_permissions';

    protected $fillable = ['key', 'label', 'group', 'sort'];

    public static function roles(): array
    {
        return ['super_admin', 'operations', 'finance', 'viewer'];
    }
}
