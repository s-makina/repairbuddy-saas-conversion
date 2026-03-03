<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingSubmissionAdminNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $subject,
        public readonly string $body,
        public readonly string $jobId,
        public readonly string $caseNumber,
        public readonly string $customerName,
        public readonly ?string $customerDeviceLabel = null,
        public readonly ?string $jobUrl = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject)
            ->view('emails.bookings.admin_notification', [
                'body' => $this->body,
                'jobId' => $this->jobId,
                'caseNumber' => $this->caseNumber,
                'customerName' => $this->customerName,
                'customerDeviceLabel' => $this->customerDeviceLabel,
                'jobUrl' => $this->jobUrl,
            ]);
    }
}
