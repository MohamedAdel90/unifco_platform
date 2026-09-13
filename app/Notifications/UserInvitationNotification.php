<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(private User $user, private string $token, private $expiresAt) {}
    public function via(object $notifiable): array { return ['mail']; }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('UNIFCO Platform invitation')
            ->greeting('Welcome '.$this->user->name)
            ->line('You have been invited to UNIFCO Platform. Use the secure link below to set your password.')
            ->action('Set Password',route('invitations.accept',['token'=>$this->token]))
            ->line('This invitation expires '.$this->expiresAt->toDayDateTimeString().' and can be used only once.');
    }
}
