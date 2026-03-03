<?php

namespace App\Mail;

use App\Data\Emails\JobCompletedData;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class JobCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public readonly JobCompletedData $data,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Device is Ready! - ' . $this->data->caseNumber,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.jobs.completed',
            with: [
                'caseNumber' => $this->data->caseNumber,
                'customerName' => $this->data->customerName,
                'device' => $this->data->device,
                'service' => $this->data->service,
                'costBreakdown' => $this->data->costBreakdown,
                'total' => $this->data->total,
                'completedDate' => $this->data->completedDate,
                'pickupLocation' => $this->data->pickupLocation,
                'pickupHours' => $this->data->pickupHours,
                'pickupNote' => $this->data->pickupNote,
                'warrantyText' => $this->data->warrantyText,
                'invoiceUrl' => $this->data->invoiceUrl,
                'feedbackUrl' => $this->data->feedbackUrl,
                'trackingUrl' => $this->data->trackingUrl,
                'tenantName' => $this->data->tenantName,
                'tenantLogoUrl' => $this->data->tenantLogoUrl,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
