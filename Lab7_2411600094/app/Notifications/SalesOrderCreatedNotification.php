<?php

namespace App\Notifications;

use App\Models\SalesOrder;
use Illuminate\Notifications\Notification;

class SalesOrderCreatedNotification extends Notification
{
    public function __construct(public SalesOrder $order)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->order->loadMissing('createdBy');
        $creator = $this->order->createdBy?->name ?? 'Someone';

        return [
            'sales_order_id' => $this->order->id,
            'reference_number' => $this->order->reference_number,
            'message' => "{$creator} created Sales Order {$this->order->reference_number} for {$this->order->customer_name}.",
            'url' => route('procurement.sales-orders.show', $this->order->id),
        ];
    }
}
