<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Lab 6 Part 6: low stock alert. Queued (database driver) so the HTTP
 * request that drained the stock never waits on email delivery; the
 * database channel feeds the navbar bell, the mail channel reaches admins
 * even when they are not logged in.
 */
class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** A failed e-mail (bad SMTP login, no internet) is retried, never fatal. */
    public int $tries = 3;

    public function __construct(public Product $product)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * The bell (database channel) must appear the moment stock drops, so it
     * runs on the `sync` connection. Only the e-mail goes through the queue
     * (php artisan queue:work), exactly as Part 6 of the docx describes.
     * Without this, a queued database notification stays in the jobs table
     * until a worker runs and the bell looks "broken".
     */
    public function viaConnections(): array
    {
        return [
            'database' => 'sync',
            'mail'     => config('queue.default'),
        ];
    }

    /** Suggested reorder amount: bring the item back up to 2x its threshold. */
    private function suggestedUnits(): int
    {
        return max(0, $this->product->reorder_level * 2 - $this->product->quantity);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'subject'        => ($this->product->quantity <= 0 ? 'Out of stock: ' : 'Low stock: ')
                                . $this->product->name,
            'message'        => sprintf(
                '%s (%s) is at %d unit(s), reorder level is %d. Suggested action: reorder %d unit(s).',
                $this->product->name,
                $this->product->sku,
                $this->product->quantity,
                $this->product->reorder_level,
                $this->suggestedUnits()
            ),
            'product_id'     => $this->product->id,
            'product_name'   => $this->product->name,
            'sku'            => $this->product->sku,
            'quantity'       => $this->product->quantity,
            'reorder_level'  => $this->product->reorder_level,
            'suggested_units' => $this->suggestedUnits(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Low Stock Alert: {$this->product->name}")
            ->greeting("Hello {$notifiable->name},")
            ->line(sprintf(
                '"%s" (SKU %s) is down to %d unit(s) — its reorder level is %d.',
                $this->product->name,
                $this->product->sku,
                $this->product->quantity,
                $this->product->reorder_level
            ))
            ->line("Suggested action: reorder {$this->suggestedUnits()} unit(s).")
            ->action('Open Product', route('products.show', $this->product))
            ->line('This alert fires automatically when a stock movement crosses the reorder level.');
    }
}
