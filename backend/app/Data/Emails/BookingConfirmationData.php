<?php

namespace App\Data\Emails;

readonly class BookingConfirmationData
{
    public function __construct(
        public string $caseNumber,
        public string $jobId,
        public string $customerName,
        public string $customerDeviceLabel,
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
            jobId: $data['job_id'] ?? '',
            customerName: $data['customer_name'] ?? '',
            customerDeviceLabel: $data['customer_device_label'] ?? '',
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
            'jobId' => $this->jobId,
            'customerName' => $this->customerName,
            'customerDeviceLabel' => $this->customerDeviceLabel,
            'trackingUrl' => $this->trackingUrl,
            'tenantName' => $this->tenantName,
            'renderedBody' => $this->renderedBody,
            'subject' => $this->subject,
            'tenantLogoUrl' => $this->tenantLogoUrl,
        ];
    }
}
