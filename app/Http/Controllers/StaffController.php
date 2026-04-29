<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\AdminAuditLog;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $query = Admin::orderBy('name');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'ilike', "%{$s}%")
                  ->orWhere('email', 'ilike', "%{$s}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('twofa')) {
            if ($request->twofa === 'on') {
                $query->whereNotNull('two_factor_confirmed_at');
            } elseif ($request->twofa === 'off') {
                $query->whereNull('two_factor_confirmed_at');
            }
        }

        $staff = $query->get();

        $allStaff = Admin::all(['role', 'status', 'two_factor_confirmed_at']);
        $stats = [
            'total'          => $allStaff->count(),
            'active'         => $allStaff->where('status', 'active')->count(),
            'inactive'       => $allStaff->where('status', 'inactive')->count(),
            'with_2fa'       => $allStaff->whereNotNull('two_factor_confirmed_at')->count(),
            'by_role'        => $allStaff->groupBy('role')->map->count()->all(),
        ];

        return view('staff.index', compact('staff', 'stats'));
    }

    public function create()
    {
        return view('staff.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:admins,email',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'required|in:super_admin,operations,finance,viewer',
        ]);

        Admin::create($data);

        return redirect()->route('staff.index')->with('success', 'Staff account created.');
    }

    public function toggleStatus(Admin $admin)
    {
        if ($admin->id === session('admin_id')) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $admin->update(['status' => $admin->status === 'active' ? 'inactive' : 'active']);

        return back()->with('success', 'Status updated.');
    }

    /**
     * Reset another admin's 2FA — used when they've lost both their device
     * and their recovery codes. Super admin only, audit-logged.
     */
    public function resetTwoFactor(Admin $admin)
    {
        $current = current_admin();

        if (! $current || ! $current->isSuperAdmin()) {
            abort(403, 'Only super admins can reset two-factor authentication.');
        }

        if ($admin->id === $current->id) {
            return back()->with('error', 'Use the Account Security page to manage your own 2FA.');
        }

        $hadEnabled = $admin->hasEnabledTwoFactor();

        $admin->two_factor_secret = null;
        $admin->two_factor_recovery_codes = null;
        $admin->two_factor_confirmed_at = null;
        $admin->save();

        AdminAuditLog::record('two_factor.reset_by_admin', $admin, [
            'target_email'    => $admin->email,
            'had_2fa_enabled' => $hadEnabled,
        ]);

        return back()->with('success', "Two-factor authentication reset for {$admin->name}. They can now sign in with just their password and re-enroll.");
    }

    public function permissions(Admin $admin)
    {
        $permissions = Permission::orderBy('sort')->get()->groupBy('group');

        $rolePermIds = DB::table('admin_role_permissions')
            ->where('role', $admin->role)
            ->pluck('admin_permission_id')
            ->all();

        $overrides = DB::table('admin_user_permissions')
            ->where('admin_id', $admin->id)
            ->pluck('granted', 'admin_permission_id')
            ->all();

        return view('staff.permissions', compact('admin', 'permissions', 'rolePermIds', 'overrides'));
    }

    public function updatePermissions(Request $request, Admin $admin)
    {
        $data = $request->validate([
            'override'   => 'array',
            'override.*' => 'in:inherit,grant,revoke',
        ]);

        $submitted = $data['override'] ?? [];

        $before = DB::table('admin_user_permissions')
            ->where('admin_id', $admin->id)
            ->pluck('granted', 'admin_permission_id')
            ->all();

        DB::transaction(function () use ($admin, $submitted) {
            DB::table('admin_user_permissions')->where('admin_id', $admin->id)->delete();

            foreach ($submitted as $permissionId => $state) {
                if ($state === 'inherit') {
                    continue;
                }
                DB::table('admin_user_permissions')->insert([
                    'admin_id'            => $admin->id,
                    'admin_permission_id' => (int) $permissionId,
                    'granted'             => $state === 'grant',
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
            }
        });

        $after = [];
        foreach ($submitted as $permissionId => $state) {
            if ($state !== 'inherit') {
                $after[(int) $permissionId] = $state === 'grant';
            }
        }

        AdminAuditLog::record('permissions.user_overrides_updated', $admin, [
            'before' => $before,
            'after'  => $after,
        ]);

        return redirect()->route('staff.permissions', $admin)->with('success', 'User permissions updated.');
    }
}
