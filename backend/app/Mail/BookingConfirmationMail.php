<?php

namespace App\Mail;

use App\Data\Emails\BookingConfirmationData;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public readonly BookingConfirmationData $data,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->data->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.bookings.confirmation',
            with: [
                'caseNumber' => $this->data->caseNumber,
                'customerName' => $this->data->customerName,
                'trackingUrl' => $this->data->trackingUrl,
                'tenantName' => $this->data->tenantName,
                'tenantLogoUrl' => $this->data->tenantLogoUrl,
                'renderedBody' => $this->data->renderedBody,
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
