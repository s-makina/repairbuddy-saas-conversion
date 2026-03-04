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
        $tenantName = $this->tenant->name ?? 'RepairBuddy';
        $caseNumber = $this->job->case_number ?? 'N/A';
        $signatureLabel = $this->signatureRequest->signature_label;

        return (new MailMessage())
            ->subject($this->subject)
            ->view('emails.signatures.request', [
                'tenantName' => $tenantName,
                'caseNumber' => $caseNumber,
                'signatureLabel' => $signatureLabel,
                'body' => $this->body,
                'signatureUrl' => $this->signatureUrl,
            ]);
    }
}
