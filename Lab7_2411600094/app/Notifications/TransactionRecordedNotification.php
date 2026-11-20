<?php

namespace App\Notifications;

use App\Models\InventoryTransaction;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Admin alert for each inventory movement recorded by staff. */
class TransactionRecordedNotification extends Notification
{
    public function __construct(public InventoryTransaction $transaction)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $t = $this->transaction;
        $staff = ($t->user?->name ?? 'Unknown') . ' staff';
        $product = $t->product?->name ?? 'Deleted product';
        $qty = abs($t->signedQuantity());
        return [
            'type' => 'staff_transaction',
            'subject' => "{$staff} recorded {$t->typeLabel()}: {$product}",
            'message' => "{$staff} recorded {$t->typeLabel()} of {$qty} unit(s) for {$product}.",
            'staff_name' => $staff,
            'product_id' => $t->product_id,
            'product_name' => $product,
            'transaction_id' => $t->id,
            'transaction_type' => $t->type,
            'quantity' => $qty,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $t = $this->transaction;
        return (new MailMessage)
            ->subject('Staff inventory transaction recorded')
            ->greeting('Inventory activity notice')
            ->line(($t->user?->name ?? 'Unknown') . ' staff recorded a ' . $t->typeLabel() . ' transaction.')
            ->line('Product: ' . ($t->product?->name ?? 'Deleted product'))
            ->line('Quantity: ' . abs($t->signedQuantity()) . ' unit(s)')
            ->line('Transaction #' . $t->id);
    }
}
