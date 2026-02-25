<?php

namespace App\Notifications;

use App\Models\RepairBuddyJob;
use App\Models\RepairBuddySignatureRequest;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SignatureRequestNotification extends Notification
{
    use Queueable;

    public function __construct(
        private string $subject,
        private string $body,
        private string $signatureUrl,
        private RepairBuddyJob $job,
        private RepairBuddySignatureRequest $signatureRequest,
        private Tenant $tenant,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $businessName = $this->tenant->name ?? 'RepairBuddy';
        $lines = array_filter(explode("\n", $this->body));

        $mail = (new MailMessage())
            ->subject($this->subject)
            ->greeting("Hello {$notifiable->name},");

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed !== '' && ! str_starts_with($trimmed, 'Hello ')) {
                $mail->line($trimmed);
            }
        }

        $mail->action('Sign Now', $this->signatureUrl)
            ->line('This link will expire in 7 days.')
            ->salutation("Thank you,\n{$businessName}");

        return $mail;
    }
}
