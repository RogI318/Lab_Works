<?php
namespace App\Notifications;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
class AccountDeleted extends Notification
{
    public function __construct(public string $name, public string $deletedBy)
    {
    }
    public function via(object $n): array
    {
        return ['mail'];
    }
    public function toMail(object $n): MailMessage
    {
        return (new MailMessage)->subject('Your account was deleted')->greeting('Hello ' . $this->name)->line('An administrator deleted your account.');
    }
}
