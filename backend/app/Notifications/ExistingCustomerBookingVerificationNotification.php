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
        protected ?string $tenantLogoUrl = null,
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
        $shopName = $this->tenantName ?: 'our repair shop';

        $userName = $notifiable->first_name ?? $notifiable->name ?? null;

        return (new MailMessage)
            ->subject('Verify Your Account — ' . $shopName)
            ->view('emails.auth.existing-customer-verification', [
                'otpCode' => $this->oneTimePassword,
                'userName' => $userName,
                'userEmail' => $notifiable->email,
                'shopName' => $shopName,
                'portalUrl' => $portalUrl,
                'tenantName' => $this->tenantName ?: 'RepairBuddy',
                'tenantLogoUrl' => $this->tenantLogoUrl,
            ]);
    }
}
