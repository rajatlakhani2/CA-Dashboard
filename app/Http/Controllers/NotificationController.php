<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function markRead($id)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $notification = $user->notifications()->findOrFail($id);
        $notification->markAsRead();

        return back();
    }

    public function markAllRead()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $user->unreadNotifications->markAsRead();
        return back()->with('success', 'All notifications marked as read.');
    }
}
