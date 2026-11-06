<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\AdminAuditLog;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
            'total' => $allStaff->count(),
            'active' => $allStaff->where('status', 'active')->count(),
            'inactive' => $allStaff->where('status', 'inactive')->count(),
            'with_2fa' => $allStaff->whereNotNull('two_factor_confirmed_at')->count(),
            'by_role' => $allStaff->groupBy('role')->map->count()->all(),
        ];

        return view('staff.index', compact('staff', 'stats'));
    }

    public function create()
    {
        return view('staff.create');
    }

    /**
     * Create a staff account. Only a super admin may create another super
     * admin. The account must replace its initial password at first sign-in.
     */
    public function store(Request $request)
    {
        $assignableRoles = current_admin()->isSuperAdmin()
            ? ['super_admin', 'operations', 'finance', 'viewer']
            : ['operations', 'finance', 'viewer'];

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admins,email',
            'password' => ['required', 'string', 'confirmed', Admin::passwordRule()],
            'role' => ['required', Rule::in($assignableRoles)],
        ], [
            'role.in' => 'Only a super admin can create a super admin account.',
        ]);

        $admin = Admin::create($data + ['must_change_password' => true]);

        AdminAuditLog::record('staff.created', $admin, [
            'email' => $admin->email,
            'role' => $admin->role,
        ]);

        return redirect()->route('staff.index')->with('success', 'Staff account created.');
    }

    public function toggleStatus(Admin $admin)
    {
        if ($error = $this->guardStaffChange($admin)) {
            return back()->with('error', $error);
        }

        $from = $admin->status;
        $admin->update(['status' => $from === 'active' ? 'inactive' : 'active']);

        AdminAuditLog::record('staff.status_toggled', $admin, [
            'from' => $from,
            'to' => $admin->status,
        ]);

        return back()->with('success', 'Status updated.');
    }

    /**
     * Why the current admin may not change $target's status or permissions,
     * or null if they may: nobody changes their own account here, and only a
     * super admin may change a super admin.
     */
    private function guardStaffChange(Admin $target): ?string
    {
        $current = current_admin();

        if ($target->id === $current->id) {
            return 'You cannot change your own account status or permissions.';
        }

        if ($target->isSuperAdmin() && ! $current->isSuperAdmin()) {
            return 'Only a super admin can change a super admin account.';
        }

        return null;
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
            'target_email' => $admin->email,
            'had_2fa_enabled' => $hadEnabled,
        ]);

        return back()->with('success', "Two-factor authentication reset for {$admin->name}. They will be asked to set up two-factor again right after their next sign-in.");
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
        if ($error = $this->guardStaffChange($admin)) {
            return back()->with('error', $error);
        }

        $data = $request->validate([
            'override' => 'array',
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
                    'admin_id' => $admin->id,
                    'admin_permission_id' => (int) $permissionId,
                    'granted' => $state === 'grant',
                    'created_at' => now(),
                    'updated_at' => now(),
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
            'after' => $after,
        ]);

        return redirect()->route('staff.permissions', $admin)->with('success', 'User permissions updated.');
    }
}
