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
        $tenant = \App\Support\TenantContext::tenant();
        $tenantName = $tenant?->name ?? 'RepairBuddy';

        $this->estimate->load(['items.tax']);

        $subtotalCents = 0;
        $taxTotalCents = 0;
        $itemsData = [];

        foreach ($this->estimate->items as $item) {
            $itemSubtotal = $item->qty * $item->unit_price_amount_cents;
            $itemTax = 0;

            if ($item->tax) {
                $itemTax = (int) round($itemSubtotal * ((float) $item->tax->rate / 100));
            }

            $subtotalCents += $itemSubtotal;
            $taxTotalCents += $itemTax;

            $itemsData[] = [
                'name' => $item->name_snapshot,
                'qty' => $item->qty,
                'unit_price' => $item->unit_price_amount_cents / 100,
                'total' => $itemSubtotal / 100,
                'currency' => $item->unit_price_currency,
            ];
        }

        $grandTotalCents = $subtotalCents + $taxTotalCents;

        $msg = (new MailMessage)
            ->subject($this->subject)
            ->view('emails.estimates.customer_notification', [
                'tenantName' => $tenantName,
                'caseNumber' => $this->estimate->case_number,
                'body' => $this->body,
                'approveUrl' => $this->approveUrl,
                'rejectUrl' => $this->rejectUrl,
                'items' => $itemsData,
                'subtotal' => $subtotalCents / 100,
                'taxTotal' => $taxTotalCents / 100,
                'grandTotal' => $grandTotalCents / 100,
                'currency' => $this->estimate->items->first()?->unit_price_currency ?? 'USD',
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
