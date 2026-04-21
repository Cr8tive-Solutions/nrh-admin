<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function showLogin()
    {
        if (session('admin_id')) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $admin = Admin::where('email', $data['email'])->where('status', 'active')->first();

        if (! $admin || ! $admin->verifyPassword($data['password'])) {
            return back()->withErrors(['email' => 'Invalid credentials or account inactive.'])->withInput();
        }

        session([
            'admin_id'    => $admin->id,
            'admin_name'  => $admin->name,
            'admin_role'  => $admin->role,
            'admin_email' => $admin->email,
        ]);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['admin_id', 'admin_name', 'admin_role', 'admin_email']);
        return redirect()->route('login');
    }
}
