<?php

namespace App\Data\Emails;

readonly class JobCompletedData
{
    /**
     * @param array<int, array{label: string, value: string}> $costBreakdown
     */
    public function __construct(
        public string $caseNumber,
        public string $customerName,
        public string $device,
        public string $service,
        public array $costBreakdown,
        public string $total,
        public string $completedDate,
        public string $pickupLocation,
        public string $pickupHours,
        public string $pickupNote,
        public string $warrantyText,
        public string $invoiceUrl,
        public string $feedbackUrl,
        public string $trackingUrl,
        public string $tenantName,
        public ?string $tenantLogoUrl = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            caseNumber: $data['case_number'] ?? '',
            customerName: $data['customer_name'] ?? '',
            device: $data['device'] ?? '',
            service: $data['service'] ?? '',
            costBreakdown: $data['cost_breakdown'] ?? [],
            total: $data['total'] ?? '',
            completedDate: $data['completed_date'] ?? '',
            pickupLocation: $data['pickup_location'] ?? '',
            pickupHours: $data['pickup_hours'] ?? '',
            pickupNote: $data['pickup_note'] ?? 'Bring photo ID',
            warrantyText: $data['warranty_text'] ?? '90-Day Warranty — Covered for any issues with the repaired part.',
            invoiceUrl: $data['invoice_url'] ?? '',
            feedbackUrl: $data['feedback_url'] ?? '',
            trackingUrl: $data['tracking_url'] ?? '',
            tenantName: $data['tenant_name'] ?? 'RepairBuddy',
            tenantLogoUrl: $data['tenant_logo_url'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'caseNumber' => $this->caseNumber,
            'customerName' => $this->customerName,
            'device' => $this->device,
            'service' => $this->service,
            'costBreakdown' => $this->costBreakdown,
            'total' => $this->total,
            'completedDate' => $this->completedDate,
            'pickupLocation' => $this->pickupLocation,
            'pickupHours' => $this->pickupHours,
            'pickupNote' => $this->pickupNote,
            'warrantyText' => $this->warrantyText,
            'invoiceUrl' => $this->invoiceUrl,
            'feedbackUrl' => $this->feedbackUrl,
            'trackingUrl' => $this->trackingUrl,
            'tenantName' => $this->tenantName,
            'tenantLogoUrl' => $this->tenantLogoUrl,
        ];
    }
}
