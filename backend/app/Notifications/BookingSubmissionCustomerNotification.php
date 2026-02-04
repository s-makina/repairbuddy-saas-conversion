<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingSubmissionCustomerNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $subject,
        public readonly string $body,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $msg = (new MailMessage)->subject($this->subject);

        foreach (preg_split("/\r\n|\r|\n/", $this->body) as $line) {
            $trim = trim((string) $line);
            if ($trim === '') {
                $msg->line(' ');
            } else {
                $msg->line($trim);
            }
        }

        return $msg;
    }
}
