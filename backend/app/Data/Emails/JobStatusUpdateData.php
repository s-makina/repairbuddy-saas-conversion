<?php

namespace App\Data\Emails;

readonly class JobStatusUpdateData
{
    public function __construct(
        public string $caseNumber,
        public string $customerName,
        public string $status,
        public string $statusColor, // 'in-progress' => #0ea5e9, 'ready' => #fd6742, 'completed' => #16a34a
        public string $device,
        public string $service,
        public string $technician,
        public string $estimatedCompletion,
        public string $updatedAt,
        public string $note,
        public string $trackingUrl,
        public string $tenantName,
        public ?string $tenantLogoUrl = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            caseNumber: $data['case_number'] ?? '',
            customerName: $data['customer_name'] ?? '',
            status: $data['status'] ?? 'In Progress',
            statusColor: $data['status_color'] ?? '#0ea5e9',
            device: $data['device'] ?? '',
            service: $data['service'] ?? '',
            technician: $data['technician'] ?? '',
            estimatedCompletion: $data['estimated_completion'] ?? '',
            updatedAt: $data['updated_at'] ?? '',
            note: $data['note'] ?? '',
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
            'status' => $this->status,
            'statusColor' => $this->statusColor,
            'device' => $this->device,
            'service' => $this->service,
            'technician' => $this->technician,
            'estimatedCompletion' => $this->estimatedCompletion,
            'updatedAt' => $this->updatedAt,
            'note' => $this->note,
            'trackingUrl' => $this->trackingUrl,
            'tenantName' => $this->tenantName,
            'tenantLogoUrl' => $this->tenantLogoUrl,
        ];
    }

    /**
     * Get the CSS class for the status badge based on status.
     */
    public function getStatusBadgeClass(): string
    {
        return match (strtolower($this->status)) {
            'in progress', 'in_progress', 'inprocess' => 'status-in-progress',
            'ready', 'ready for pickup' => 'status-ready',
            'completed', 'complete' => 'status-completed',
            default => 'status-in-progress',
        };
    }

    /**
     * Get the hex color for the status.
     */
    public function getStatusHexColor(): string
    {
        return match (strtolower($this->status)) {
            'in progress', 'in_progress', 'inprocess' => '#0ea5e9',
            'ready', 'ready for pickup' => '#fd6742',
            'completed', 'complete' => '#16a34a',
            default => '#0ea5e9',
        };
    }
}
