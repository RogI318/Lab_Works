<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReorderPurchaseOrdersNotification extends Notification
{
    use Queueable;

    public function __construct(public array $references)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'subject' => 'Automatic reorder drafts created',
            'message' => count($this->references) . ' draft purchase order(s) need review.',
            'references' => $this->references,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Automatic reorder drafts created')
            ->line(count($this->references) . ' draft purchase order(s) were created.')
            ->line(implode(', ', $this->references))
            ->action('Open purchase orders', route('procurement.purchase-orders.index'));
    }
}
