<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OneTimePasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $oneTimePassword,
        protected int $expiresInMinutes = 1440,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = rtrim((string) env('FRONTEND_URL', 'http://localhost:3000'), '/');

        return (new MailMessage)
            ->subject('Your one-time password')
            ->greeting('Welcome!')
            ->line('A new account was created for you.')
            ->line('Use this one-time password to sign in:')
            ->line($this->oneTimePassword)
            ->line('This one-time password expires in '.$this->expiresInMinutes.' minutes and will be invalidated after the first successful sign-in.')
            ->action('Sign in', $frontendUrl.'/login')
            ->line('After signing in, please set a new password using the "Forgot password" link on the sign-in page, or from your Profile page.');
    }
}
