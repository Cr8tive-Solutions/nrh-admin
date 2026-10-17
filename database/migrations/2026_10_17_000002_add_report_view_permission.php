<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds the `report.view` permission (preview, view and download screening
 * report PDFs) and grants it to the operations role. Reports were previously
 * open to every admin, viewer included. Runs on existing installs, where the
 * PermissionSeeder's "only seed a role with no permissions" guard would
 * otherwise skip already-populated roles. super_admin bypasses all checks, so
 * it needs no explicit grant. Idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        $id = DB::table('admin_permissions')->where('key', 'report.view')->value('id');

        if (! $id) {
            $id = DB::table('admin_permissions')->insertGetId([
                'key' => 'report.view',
                'label' => 'Preview, view & download screening reports',
                'group' => 'Operations',
                'sort' => 15,
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
        $id = DB::table('admin_permissions')->where('key', 'report.view')->value('id');
        if ($id) {
            DB::table('admin_role_permissions')->where('admin_permission_id', $id)->delete();
            DB::table('admin_user_permissions')->where('admin_permission_id', $id)->delete();
            DB::table('admin_permissions')->where('id', $id)->delete();
        }
    }
};
