<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExistingCustomerBookingVerificationNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $oneTimePassword,
        protected string $business,
        protected string $tenantName = '',
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appUrl = rtrim((string) env('APP_URL', 'http://localhost'), '/');
        $portalUrl = $appUrl . '/t/' . $this->business . '/portal';
        $bookingUrl = $appUrl . '/t/' . $this->business . '/book';
        $shopName = $this->tenantName ?: 'our repair shop';

        return (new MailMessage)
            ->subject('Verify Your Account — ' . $shopName)
            ->greeting('Hello ' . ($notifiable->first_name ?? $notifiable->name ?? '') . '!')
            ->line('Someone (hopefully you) tried to book a repair at **' . $shopName . '** using your email address.')
            ->line('You already have an account with us. Please log in to your Customer Portal to book another repair.')
            ->line('**Your login details:**')
            ->line('**Email:** ' . $notifiable->email)
            ->line('**One-Time Password:** ' . $this->oneTimePassword)
            ->line('This one-time password expires in 24 hours and will be invalidated after the first successful sign-in.')
            ->action('Log In & Book Repair', $portalUrl)
            ->line('After logging in, you can book a new repair directly from your portal without entering your details again.')
            ->line('If you did not attempt to book a repair, you can safely ignore this email.');
    }
}
