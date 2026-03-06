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
        protected ?string $tenantName = null,
        protected ?string $tenantLogoUrl = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = rtrim((string) env('FRONTEND_URL', 'http://localhost:3000'), '/');

        $userName = $notifiable->first_name ?? $notifiable->name ?? null;

        return (new MailMessage)
            ->subject('Your one-time password')
            ->view('emails.auth.one-time-password', [
                'otpCode' => $this->oneTimePassword,
                'expiresInMinutes' => $this->expiresInMinutes,
                'userName' => $userName,
                'loginUrl' => $frontendUrl.'/login',
                'introText' => 'A new account was created for you. Use this one-time password to sign in:',
                'additionalMessage' => 'After signing in, please set a new password using the "Forgot password" link on the sign-in page, or from your Profile page.',
                'tenantName' => $this->tenantName ?? 'RepairBuddy',
                'tenantLogoUrl' => $this->tenantLogoUrl,
            ]);
    }
}
