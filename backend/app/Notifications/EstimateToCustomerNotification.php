<?php

namespace App\Notifications;

use App\Models\RepairBuddyEstimate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EstimateToCustomerNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly RepairBuddyEstimate $estimate,
        public readonly string $subject,
        public readonly string $body,
        public readonly ?string $approveUrl,
        public readonly ?string $rejectUrl,
        public readonly bool $attachPdf,
        public readonly ?string $pdfPath,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $msg = (new MailMessage)
            ->subject($this->subject);

        foreach (preg_split("/\r\n|\r|\n/", $this->body) as $line) {
            $trim = trim((string) $line);
            if ($trim === '') {
                $msg->line(' ');
            } else {
                $msg->line($trim);
            }
        }

        if ($this->approveUrl) {
            $msg->action('Approve estimate', $this->approveUrl);
        }

        if ($this->rejectUrl) {
            $msg->line('If you do not approve, you can reject it here:');
            $msg->action('Reject estimate', $this->rejectUrl);
        }

        if ($this->attachPdf && $this->pdfPath) {
            $msg->attach($this->pdfPath, [
                'as' => 'estimate-'.$this->estimate->case_number.'.pdf',
                'mime' => 'application/pdf',
            ]);
        }

        return $msg;
    }
}
