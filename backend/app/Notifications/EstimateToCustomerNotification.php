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
        $tenant = \App\Support\Context\TenantContext::tenant();
        $tenantName = $tenant?->name ?? 'RepairBuddy';

        $msg = (new MailMessage)
            ->subject($this->subject)
            ->view('emails.estimates.customer_notification', [
                'tenantName' => $tenantName,
                'caseNumber' => $this->estimate->case_number,
                'body' => $this->body,
                'approveUrl' => $this->approveUrl,
                'rejectUrl' => $this->rejectUrl,
            ]);

        if ($this->attachPdf && $this->pdfPath) {
            $msg->attach($this->pdfPath, [
                'as' => 'estimate-'.$this->estimate->case_number.'.pdf',
                'mime' => 'application/pdf',
            ]);
        }

        return $msg;
    }
}
