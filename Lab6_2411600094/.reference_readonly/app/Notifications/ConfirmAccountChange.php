<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class ConfirmAccountChange extends Notification
{
    /** @param list<string> $changes e.g. ['email address', 'password'] */
    public function __construct(private User $user, private array $changes)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute(
            'account.confirm-changes',
            now()->addHours(24),
            ['user' => $this->user->getKey(), 'token' => $this->user->pendingChangesToken()]
        );

        return (new MailMessage)
            ->subject('Please confirm changes to your account')
            ->greeting('Hello '.$this->user->name.',')
            ->line('An administrator asked to change your '.implode(' and ', $this->changes).'.')
            ->line('The change only takes effect after you confirm it. This link expires in 24 hours.')
            ->action('Confirm changes', $url)
            ->line('If you did not expect this, ignore this email and nothing will change.');
    }
}
