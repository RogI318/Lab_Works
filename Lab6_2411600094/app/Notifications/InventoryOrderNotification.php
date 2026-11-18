<?php

namespace App\Notifications;

use App\Models\InventoryOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InventoryOrderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public InventoryOrder $order,
        public string $event,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $kind = $this->order->direction === 'purchase' ? 'Purchase Order' : 'Sales Order';
        $subject = match ($this->event) {
            'created' => "{$kind} {$this->order->order_number} created",
            'approved' => "{$kind} {$this->order->order_number} approved",
            'completed' => "{$kind} {$this->order->order_number} processed",
            'cancelled' => "{$kind} {$this->order->order_number} cancelled",
            default => "{$kind} {$this->order->order_number} updated",
        };

        return [
            'type' => 'inventory_order',
            'event' => $this->event,
            'subject' => $subject,
            'message' => $subject . ' for ' . $this->order->contact_name . '.',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'direction' => $this->order->direction,
        ];
    }
}
