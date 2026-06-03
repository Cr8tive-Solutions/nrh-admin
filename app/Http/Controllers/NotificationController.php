<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $admin = current_admin();
        $notifications = AdminNotification::where('admin_id', $admin->id)
            ->latest('created_at')
            ->paginate(30);
        $unreadCount = AdminNotification::where('admin_id', $admin->id)
            ->whereNull('read_at')
            ->count();

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    public function markRead(AdminNotification $notification)
    {
        abort_if($notification->admin_id !== current_admin()->id, 403);
        $notification->markRead();

        return back();
    }

    public function markAllRead()
    {
        AdminNotification::where('admin_id', current_admin()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back();
    }
}
