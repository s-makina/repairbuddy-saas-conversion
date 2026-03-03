<?php

namespace App\Data\Emails;

readonly class BookingConfirmationData
{
    public function __construct(
        public string $caseNumber,
        public string $customerName,
        public string $trackingUrl,
        public string $tenantName,
        public string $renderedBody,
        public string $subject,
        public ?string $tenantLogoUrl = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            caseNumber: $data['case_number'] ?? '',
            customerName: $data['customer_name'] ?? '',
            trackingUrl: $data['tracking_url'] ?? '',
            tenantName: $data['tenant_name'] ?? 'RepairBuddy',
            renderedBody: $data['rendered_body'] ?? '',
            subject: $data['subject'] ?? 'Booking Confirmed',
            tenantLogoUrl: $data['tenant_logo_url'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'caseNumber' => $this->caseNumber,
            'customerName' => $this->customerName,
            'trackingUrl' => $this->trackingUrl,
            'tenantName' => $this->tenantName,
            'renderedBody' => $this->renderedBody,
            'subject' => $this->subject,
            'tenantLogoUrl' => $this->tenantLogoUrl,
        ];
    }
}
