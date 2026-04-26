<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Operations
            ['key' => 'request.update',     'label' => 'Update request & candidate status', 'group' => 'Operations', 'sort' => 10],
            ['key' => 'customer.manage',    'label' => 'Create & edit customers and agreements', 'group' => 'Operations', 'sort' => 20],

            // Finance
            ['key' => 'pricing.manage',     'label' => 'Set per-customer scope pricing', 'group' => 'Finance', 'sort' => 30],
            ['key' => 'invoice.manage',     'label' => 'Create invoices & mark paid',    'group' => 'Finance', 'sort' => 40],
            ['key' => 'transaction.manage', 'label' => 'Record payments',                'group' => 'Finance', 'sort' => 50],

            // Configuration
            ['key' => 'config.scopes',      'label' => 'Manage scope types',  'group' => 'Configuration', 'sort' => 60],
            ['key' => 'config.countries',   'label' => 'Manage countries',    'group' => 'Configuration', 'sort' => 70],

            // Administration
            ['key' => 'staff.manage',       'label' => 'Manage staff accounts',         'group' => 'Administration', 'sort' => 80],
            ['key' => 'permissions.manage', 'label' => 'Edit role & user permissions',  'group' => 'Administration', 'sort' => 90],
        ];

        foreach ($permissions as $p) {
            Permission::updateOrCreate(['key' => $p['key']], $p);
        }

        $defaults = [
            'super_admin' => Permission::pluck('id')->all(),
            'operations'  => Permission::whereIn('key', ['request.update', 'customer.manage'])->pluck('id')->all(),
            'finance'     => Permission::whereIn('key', ['pricing.manage', 'invoice.manage', 'transaction.manage'])->pluck('id')->all(),
            'viewer'      => [],
        ];

        // Sync admin_role_permissions to defaults only if a role currently has none.
        // Keeps any custom changes already made through the admin UI intact.
        foreach ($defaults as $role => $permissionIds) {
            $existing = DB::table('admin_role_permissions')->where('role', $role)->count();
            if ($existing > 0) {
                continue;
            }
            foreach ($permissionIds as $pid) {
                DB::table('admin_role_permissions')->insert([
                    'role'                => $role,
                    'admin_permission_id' => $pid,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
            }
        }
    }
}
