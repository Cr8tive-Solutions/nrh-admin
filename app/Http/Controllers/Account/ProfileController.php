<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function show()
    {
        return view('account.profile', ['admin' => current_admin()]);
    }

    public function update(Request $request)
    {
        $admin = current_admin();

        $data = $request->validate([
            'name'   => 'required|string|max:255',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048', // 2 MB
        ]);

        $admin->name = $data['name'];

        if ($request->hasFile('avatar')) {
            // Delete previous avatar (if any) before storing the new one.
            if ($admin->avatar) {
                Storage::disk('public')->delete($admin->avatar);
            }

            $file = $request->file('avatar');
            $ext = $file->getClientOriginalExtension();
            $filename = "avatars/admin-{$admin->id}-".Str::random(12).".{$ext}";
            $file->storeAs('', $filename, 'public');

            $admin->avatar = $filename;
        }

        $admin->save();

        // Keep the cached session name fresh so the topbar updates immediately.
        if (session('admin_id') === $admin->id) {
            session(['admin_name' => $admin->name]);
        }

        return redirect()->route('account.profile')->with('success', 'Profile updated.');
    }

    public function removeAvatar()
    {
        $admin = current_admin();

        if ($admin->avatar) {
            Storage::disk('public')->delete($admin->avatar);
            $admin->avatar = null;
            $admin->save();
        }

        return redirect()->route('account.profile')->with('success', 'Avatar removed.');
    }
}
