<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermissionsController extends Controller
{
    public function index()
    {
        $permissions = Permission::orderBy('sort')->get()->groupBy('group');
        $roles = Permission::roles();

        $matrix = DB::table('admin_role_permissions')
            ->get(['role', 'admin_permission_id'])
            ->groupBy('role')
            ->map(fn ($rows) => $rows->pluck('admin_permission_id')->all())
            ->toArray();

        return view('permissions.index', compact('permissions', 'roles', 'matrix'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'matrix'           => 'array',
            'matrix.*'         => 'array',
            'matrix.*.*'       => 'integer|exists:admin_permissions,id',
        ]);

        $allRoles = Permission::roles();
        $submitted = $data['matrix'] ?? [];

        DB::transaction(function () use ($allRoles, $submitted) {
            foreach ($allRoles as $role) {
                if ($role === 'super_admin') {
                    // super_admin always implicitly has every permission — never editable here.
                    continue;
                }

                DB::table('admin_role_permissions')->where('role', $role)->delete();

                $ids = $submitted[$role] ?? [];
                foreach (array_unique($ids) as $pid) {
                    DB::table('admin_role_permissions')->insert([
                        'role'                => $role,
                        'admin_permission_id' => (int) $pid,
                        'created_at'          => now(),
                        'updated_at'          => now(),
                    ]);
                }
            }
        });

        return redirect()->route('permissions.index')->with('success', 'Role permissions updated.');
    }
}
