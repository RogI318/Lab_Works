<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\ReorderPurchaseOrdersNotification;
use App\Services\ReorderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Throwable;

class ReorderController extends Controller
{
    public function generate(Request $request, ReorderService $service)
    {
        $result = $service->generatePurchaseOrders($request->user());

        if ($result['orders']->isNotEmpty()) {
            try {
                Notification::send(
                    User::admins()->get(),
                    new ReorderPurchaseOrdersNotification($result['orders']->pluck('reference_number')->all())
                );
            } catch (Throwable $exception) {
                report($exception);

                return redirect()->route('procurement.dashboard')
                    ->with('success', count($result['orders']) . ' automatic draft purchase order(s) created.')
                    ->with('warning', 'The draft orders were created, but the notification could not be sent.');
            }
        }

        return redirect()->route('procurement.dashboard')
            ->with('success', count($result['orders']) . ' automatic draft purchase order(s) created.');
    }
}
