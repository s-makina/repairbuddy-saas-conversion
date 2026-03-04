<?php

namespace App\Notifications;

use App\Models\RepairBuddyEstimate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EstimateRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly RepairBuddyEstimate $estimate,
        public readonly ?string $rejectionReason = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tenant = \App\Support\TenantContext::tenant();
        $tenantName = $tenant?->name ?? 'RepairBuddy';
        $tenantEmail = $tenant?->email ?? null;

        $subject = "Estimate {$this->estimate->case_number} - Update";

        $msg = (new MailMessage)
            ->subject($subject)
            ->view('emails.estimates.rejected_notification', [
                'tenantName' => $tenantName,
                'tenantEmail' => $tenantEmail,
                'caseNumber' => $this->estimate->case_number,
                'estimateTitle' => $this->estimate->title,
                'rejectionReason' => $this->rejectionReason,
            ]);

        return $msg;
    }
}
