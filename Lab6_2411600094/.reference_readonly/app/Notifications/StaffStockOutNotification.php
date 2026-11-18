<?php
// IDEA 2: when a STAFF member records a Stock Out, the admins get a bell alert.
// Bell only (database), no email, so no queue worker is needed.
// Created by: php artisan make:notification StaffStockOutNotification

namespace App\Notifications;

use App\Models\InventoryTransaction;
use Illuminate\Notifications\Notification;

class StaffStockOutNotification extends Notification
{
    public function __construct(public InventoryTransaction $transaction)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    // Saved in the notifications table and shown in the bell
    public function toArray(object $notifiable): array
    {
        $t = $this->transaction;
        $staff = $t->user->name ?? 'Staff';
        $product = $t->product->name ?? 'a product';
        $qty = abs($t->quantity);

        return [
            'type' => 'staff_stock_out',
            'product_id' => $t->product_id,
            'name' => $product,
            'staff_name' => $staff,
            'quantity' => $qty,
            'quantity_before' => $t->quantity_before,
            'quantity_after' => $t->quantity_after,
            'message' => "{$staff} stocked out {$qty} x {$product} ({$t->quantity_before} → {$t->quantity_after}).",
        ];
    }
}
