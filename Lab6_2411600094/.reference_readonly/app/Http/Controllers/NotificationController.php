<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Product;

class NotificationController extends Controller
{
    // latest() - a tiny "signature" of the bell (unread count + newest alert).
    // public/js/bell-live.js asks for it every 5 seconds and redraws the bell when it changes.
    public function latest()
    {
        $user = auth()->user();
        $newest = $user->notifications()->latest()->first();

        return response()->json([
            'signature' => $user->unreadNotifications()->count().'-'.($newest?->id),
        ]);
    }

    // read() - mark one alert as read, then open the product
    public function read(string $id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        AuditLog::record('notification_read', null, null, null, $notification->data['message'] ?? 'Alert');

        // The product may have been deleted after the alert was sent
        if (! Product::whereKey($notification->data['product_id'] ?? null)->exists()) {
            return redirect()->route('dashboard')
                ->with('success', 'Alert marked as read. That product no longer exists.');
        }

        return redirect()->route('products.show', $notification->data['product_id']);
    }

    // readAll() - mark every unread alert as read
    public function readAll()
    {
        auth()->user()->unreadNotifications->markAsRead();
        AuditLog::record('notification_read', null, null, null, 'All alerts marked as read');

        return back();
    }
}
