<?php
// Email sent to a user when an admin deletes their account
// Created by: php artisan make:notification AccountDeleted

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountDeleted extends Notification
{
    public function __construct(public string $name, public string $deletedBy)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your account was deleted')
            ->greeting("Hello {$this->name},")
            ->line("Your account was deleted by an administrator ({$this->deletedBy}).")
            ->line('You can no longer log in with this account.')
            ->line('If you think this was a mistake, please contact your administrator.');
    }
}
