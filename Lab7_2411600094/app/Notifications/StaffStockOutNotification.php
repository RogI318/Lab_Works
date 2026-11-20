<?php
namespace App\Notifications;
use App\Models\InventoryTransaction;
use Illuminate\Notifications\Notification;
class StaffStockOutNotification extends Notification
{
    public function __construct(public InventoryTransaction $transaction)
    {
    }
    public function via(object $n): array
    {
        return ['database'];
    }
    public function toDatabase(object $n): array
    {
        $t = $this->transaction;
        return ['type' => 'staff_stock_out', 'subject' => 'Staff stock out: ' . ($t->product?->name ?? 'Product'), 'message' => ($t->user?->name ?? 'Staff') . ' recorded ' . $t->typeLabel() . ' of ' . abs($t->quantity), 'product_id' => $t->product_id, 'quantity' => $t->product?->quantity];
    }
}
