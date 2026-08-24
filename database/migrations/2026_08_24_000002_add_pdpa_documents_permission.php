<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds the `pdpa.documents` permission (view candidate IC/passport uploads) and
 * grants it to the operations role. Runs on existing installs, where the
 * PermissionSeeder's "only seed a role with no permissions" guard would
 * otherwise skip already-populated roles. super_admin bypasses all checks, so
 * it needs no explicit grant. Idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        $id = DB::table('admin_permissions')->where('key', 'pdpa.documents')->value('id');

        if (! $id) {
            $id = DB::table('admin_permissions')->insertGetId([
                'key' => 'pdpa.documents',
                'label' => 'View candidate identity documents (IC/passport)',
                'group' => 'Compliance',
                'sort' => 130,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $granted = DB::table('admin_role_permissions')
            ->where('role', 'operations')
            ->where('admin_permission_id', $id)
            ->exists();

        if (! $granted) {
            DB::table('admin_role_permissions')->insert([
                'role' => 'operations',
                'admin_permission_id' => $id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $id = DB::table('admin_permissions')->where('key', 'pdpa.documents')->value('id');
        if ($id) {
            DB::table('admin_role_permissions')->where('admin_permission_id', $id)->delete();
            DB::table('admin_user_permissions')->where('admin_permission_id', $id)->delete();
            DB::table('admin_permissions')->where('id', $id)->delete();
        }
    }
};
