<?php
// LAB 6, PART 6: Low stock notification (queued, database + mail)
// Created by: php artisan make:notification LowStockNotification
// app/Notifications/LowStockNotification.php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Product $product)
    {
    }

    // Channels: bell (database) and email
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    // Email version of the alert
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Low stock: {$this->product->name}")
            ->line("{$this->product->name} (SKU {$this->product->sku}) has {$this->product->quantity} left. The reorder level is {$this->product->reorder_level}.")
            ->line($this->suggestedAction())
            ->action('View product', route('products.show', $this->product));
    }

    // Saved in the notifications table and shown in the bell
    public function toArray(object $notifiable): array
    {
        return [
            'product_id' => $this->product->id,
            'name' => $this->product->name,
            'sku' => $this->product->sku,
            'quantity' => $this->product->quantity,
            'reorder_level' => $this->product->reorder_level,
            'suggested_action' => $this->suggestedAction(),
            'message' => "{$this->product->name} is low: {$this->product->quantity} left (reorder at {$this->product->reorder_level}).",
        ];
    }

    // Short plain-English advice
    private function suggestedAction(): string
    {
        $supplier = $this->product->supplier ?: 'your supplier';

        if ($this->product->isOutOfStock()) {
            return "Out of stock. Reorder from {$supplier} right away.";
        }

        return "Reorder from {$supplier} before stock runs out.";
    }
}
