<?php
namespace App\Notifications;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
class AccountUpdated extends Notification
{
    public function __construct(public array $changes, public string $updatedBy)
    {
    }
    public function via(object $n): array
    {
        return ['mail'];
    }
    public function toMail(object $n): MailMessage
    {
        return (new MailMessage)->subject('Your account was updated')->line('An administrator updated your account. Changes: ' . implode(', ', $this->changes) . '.');
    }
}
