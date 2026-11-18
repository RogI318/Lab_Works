<?php
// Email sent to a user when an admin edits their account
// Created by: php artisan make:notification AccountUpdated

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountUpdated extends Notification
{
    // $changes = names of what changed, e.g. ['name', 'email'] (never the password itself)
    public function __construct(public array $changes, public string $updatedBy)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your account was updated')
            ->greeting('Hello!')
            ->line("An administrator ({$this->updatedBy}) updated your account.")
            ->line('What changed: ' . implode(', ', $this->changes) . '.')
            ->line('If you did not expect this change, please contact your administrator.');
    }
}
