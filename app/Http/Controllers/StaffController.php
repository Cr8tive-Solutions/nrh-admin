<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    public function index()
    {
        $this->requireSuperAdmin();
        $staff = Admin::orderBy('name')->get();
        return view('staff.index', compact('staff'));
    }

    public function create()
    {
        $this->requireSuperAdmin();
        return view('staff.create');
    }

    public function store(Request $request)
    {
        $this->requireSuperAdmin();

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
        $this->requireSuperAdmin();

        if ($admin->id === session('admin_id')) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $admin->update(['status' => $admin->status === 'active' ? 'inactive' : 'active']);

        return back()->with('success', 'Status updated.');
    }

    private function requireSuperAdmin(): void
    {
        if (session('admin_role') !== 'super_admin') {
            abort(403, 'Super admin access required.');
        }
    }
}
