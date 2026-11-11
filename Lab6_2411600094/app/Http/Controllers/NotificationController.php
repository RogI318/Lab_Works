<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

/** Lab 6 Part 5/6: the notification bell actions. */
class NotificationController extends Controller
{
    /** Opening an alert marks it read and jumps to the product it warns about. */
    public function show(Request $request, DatabaseNotification $notification)
    {
        abort_unless($notification->notifiable->is($request->user()), 403);

        $notification->markAsRead();

        $productId = $notification->data['product_id'] ?? null;

        return redirect($productId
            ? route('products.show', $productId)
            : route('dashboard'));
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read.');
    }
}
