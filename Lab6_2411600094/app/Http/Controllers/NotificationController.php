<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use App\Models\AuditLog;

/** Lab 6 Part 5/6: the notification bell actions. */
class NotificationController extends Controller
{
    public function latest(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'unread' => $user->unreadNotifications()->count(),
            'notifications' => $user->notifications()->latest()->take(15)->get()->map(fn($n) => [
                'id' => $n->id,
                'subject' => $n->data['subject'] ?? 'Inventory alert',
                'created' => $n->created_at->diffForHumans(),
                'unread' => $n->read_at === null,
                'url' => route('notifications.show', $n->id),
                'quantity' => $n->data['quantity'] ?? 1,
            ]),
        ]);
    }

    /** Opening an alert marks it read and jumps to the product it warns about. */
    public function show(Request $request, DatabaseNotification $notification)
    {
        abort_unless($notification->notifiable->is($request->user()), 403);

        $notification->markAsRead();
        AuditLog::record('notification_read', null, null, null, $notification->data['subject'] ?? 'Alert');

        $productId = $notification->data['product_id'] ?? null;

        $orderId = $notification->data['order_id'] ?? null;
        if ($orderId && $order = \App\Models\InventoryOrder::find($orderId)) {
            return redirect()->route('orders.show', $order);
        }

        // A soft-deleted product has no page to open, but the alert is still
        // worth marking read — so fall back to the dashboard instead of 404ing.
        $product = $productId ? Product::find($productId) : null;

        return redirect($product ? route('products.show', $product) : route('dashboard'));
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        AuditLog::record('notification_read', null, null, null, 'All alerts marked read');

        return back()->with('success', 'All notifications marked as read.');
    }
}
