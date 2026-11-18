<?php
namespace App\Notifications;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
class ConfirmAccountChange extends Notification
{
    public function __construct(private User $user, private array $changes)
    {
    }
    public function via(object $n): array
    {
        return ['mail'];
    }
    public function toMail(object $n): MailMessage
    {
        $url = URL::temporarySignedRoute('account.confirm-changes', now()->addHours(24), ['user' => $this->user->id, 'token' => $this->user->pendingChangesToken()]);
        return (new MailMessage)->subject('Confirm account changes')->line('Confirm your ' . implode(' and ', $this->changes) . ' change.')->action('Confirm changes', $url);
    }
}
