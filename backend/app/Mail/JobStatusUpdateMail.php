<?php

namespace App\Mail;

use App\Data\Emails\JobStatusUpdateData;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class JobStatusUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public readonly JobStatusUpdateData $data,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Status Update: ' . $this->data->status . ' - ' . $this->data->caseNumber,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.jobs.status-update',
            with: [
                'caseNumber' => $this->data->caseNumber,
                'customerName' => $this->data->customerName,
                'status' => $this->data->status,
                'statusColor' => $this->data->getStatusHexColor(),
                'device' => $this->data->device,
                'service' => $this->data->service,
                'technician' => $this->data->technician,
                'estimatedCompletion' => $this->data->estimatedCompletion,
                'updatedAt' => $this->data->updatedAt,
                'note' => $this->data->note,
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
