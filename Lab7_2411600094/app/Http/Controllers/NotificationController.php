<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Route;

/** Lab 6 Part 5/6: the notification bell actions. */
class NotificationController extends Controller
{
    /** Opening an alert marks it read and jumps to whatever it warns about. */
    public function show(Request $request, DatabaseNotification $notification)
    {
        abort_unless($notification->notifiable->is($request->user()), 403);

        $notification->markAsRead();

        // Part 3: a notification may name its own screen (the reorder summary
        // points at the draft orders it raised). It stores the route NAME, so
        // the link is built here, in the request, and works no matter which
        // host or sub-folder the app is served from. Otherwise fall back to the
        // product the Lab 6 low-stock bell is about.
        $route = $notification->data['route'] ?? null;

        if (is_string($route) && $route !== '' && Route::has($route)) {
            return redirect()->route($route, $notification->data['route_params'] ?? []);
        }

        $productId = $notification->data['product_id'] ?? null;

        // A soft-deleted product has no page to open, but the alert is still
        // worth marking read — so fall back to the dashboard instead of 404ing.
        $product = $productId ? Product::find($productId) : null;

        return redirect($product ? route('products.show', $product) : route('dashboard'));
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read.');
    }
}
