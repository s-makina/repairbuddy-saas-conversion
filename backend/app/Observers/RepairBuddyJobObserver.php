<?php

namespace App\Observers;

use App\Data\Emails\JobCompletedData;
use App\Data\Emails\JobStatusUpdateData;
use App\Mail\JobCompletedMail;
use App\Mail\JobStatusUpdateMail;
use App\Models\RepairBuddyJob;
use App\Models\RepairBuddyJobStatus;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Documents\JobPdfService;
use App\Services\TenantSettings\TenantSettingsStore;
use App\Support\TenantContext;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class RepairBuddyJobObserver
{
    /**
     * Handle the RepairBuddyJob "updated" event.
     */
    public function updated(RepairBuddyJob $job): void
    {
        // Check if status_slug changed during the last save
        if ($job->wasChanged('status_slug')) {
            $oldStatus = $job->getOriginal('status_slug');
            $newStatus = $job->status_slug;

            $this->sendStatusUpdateEmail($job, $oldStatus, $newStatus);
        }
    }

    /**
     * Send status update email to customer.
     */
    protected function sendStatusUpdateEmail(RepairBuddyJob $job, ?string $oldStatus, ?string $newStatus): void
    {
        // Skip if no customer or no email
        $customer = $job->customer;
        if (!$customer instanceof User || !is_string($customer->email) || trim($customer->email) === '') {
            return;
        }

        // Get tenant
        $tenant = TenantContext::tenant();
        if (!$tenant instanceof Tenant) {
            return;
        }

        // Check if email notifications are enabled for status changes (global toggle)
        $store = new TenantSettingsStore($tenant);
        $general = $store->get('general', []);
        $emailCustomerOnStatusChange = (bool) ($general['wc_job_status_cr_notice'] ?? false);

        if (!$emailCustomerOnStatusChange) {
            return;
        }

        // Check if this specific status has email enabled (per-status control)
        $statusRecord = RepairBuddyJobStatus::query()
            ->where('tenant_id', (int) $tenant->id)
            ->where('slug', $newStatus)
            ->first();

        if (!$statusRecord || !$statusRecord->email_enabled) {
            return;
        }

        // Check if PDF should be attached
        $attachPdf = (bool) ($general['wcrb_attach_pdf_in_customer_emails'] ?? false);

        try {
            $frontendBase = rtrim((string) env('FRONTEND_URL', (string) env('APP_URL', '')), '/');
            $trackingUrl = $frontendBase . '/t/' . $tenant->slug . '/status?caseNumber=' . urlencode((string) $job->case_number);

            // Get tenant logo
            $tenantLogoUrl = null;
            if (is_string($tenant->logo_path) && trim($tenant->logo_path) !== '') {
                $tenantLogoUrl = Storage::disk('public')->url($tenant->logo_path);
            }

            // Get device info
            $device = $this->getDeviceLabel($job);

            // Get service info
            $service = $this->getServiceLabel($job);

            // Get technician
            $technician = $job->assignedTechnician?->name ?? 'Assigned technician';

            // Determine if completed
            $completedStatuses = ['completed', 'delivered'];
            if (in_array($newStatus, $completedStatuses)) {
                // Send completion email
                $data = new JobCompletedData(
                    caseNumber: (string) $job->case_number,
                    customerName: $customer->name ?? 'Customer',
                    device: $device,
                    service: $service,
                    costBreakdown: $this->getCostBreakdown($job),
                    total: $this->getTotalCost($job),
                    completedDate: now()->format('M d, Y'),
                    pickupLocation: $this->getPickupLocation($job, $tenant),
                    pickupHours: $this->getPickupHours($tenant),
                    pickupNote: 'Bring photo ID',
                    warrantyText: '90-Day Warranty — Covered for any issues with the repaired part.',
                    invoiceUrl: $trackingUrl,
                    feedbackUrl: $trackingUrl . '&action=feedback',
                    trackingUrl: $trackingUrl,
                    tenantName: is_string($tenant->name) ? (string) $tenant->name : 'RepairBuddy',
                    tenantLogoUrl: $tenantLogoUrl,
                );

                Mail::to($customer->email)->send($this->attachPdfIfEnabled(new JobCompletedMail($data), $job, $tenant, $attachPdf));
            } else {
                // Send status update email
                $data = new JobStatusUpdateData(
                    caseNumber: (string) $job->case_number,
                    customerName: $customer->name ?? 'Customer',
                    status: $this->formatStatus($newStatus),
                    statusColor: $this->getStatusColor($newStatus),
                    device: $device,
                    service: $service,
                    technician: $technician,
                    estimatedCompletion: $job->delivery_date?->format('M d, Y') ?? 'To be determined',
                    updatedAt: now()->format('M d, H:i'),
                    note: $job->case_detail ?? '',
                    trackingUrl: $trackingUrl,
                    tenantName: is_string($tenant->name) ? (string) $tenant->name : 'RepairBuddy',
                    tenantLogoUrl: $tenantLogoUrl,
                );

                Mail::to($customer->email)->send($this->attachPdfIfEnabled(new JobStatusUpdateMail($data), $job, $tenant, $attachPdf));
            }
        } catch (\Throwable $e) {
            Log::error('job.status_email_failed', [
                'job_id' => $job->id,
                'case_number' => $job->case_number,
                'status' => $newStatus,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function getDeviceLabel(RepairBuddyJob $job): string
    {
        $device = $job->devices()->first();
        if ($device) {
            $brand = $device->deviceBrand?->label ?? '';
            $model = $device->deviceModel?->label ?? '';
            return trim($brand . ' ' . $model) ?: 'Device';
        }
        return 'Device';
    }

    protected function getServiceLabel(RepairBuddyJob $job): string
    {
        $item = $job->items()->first();
        if ($item) {
            return $item->service?->label ?? $item->title ?? 'Repair Service';
        }
        return 'Repair Service';
    }

    protected function getCostBreakdown(RepairBuddyJob $job): array
    {
        $breakdown = [];
        $items = $job->items()->get();
        foreach ($items as $item) {
            $price = (float) ($item->price ?? 0);
            $qty = (int) ($item->quantity ?? 1);
            if ($price > 0) {
                $breakdown[] = [
                    'label' => $item->title ?? $item->service?->label ?? 'Item',
                    'value' => '$' . number_format($price * $qty, 2),
                ];
            }
        }
        return $breakdown ?: [['label' => 'Service', 'value' => 'TBD']];
    }

    protected function getTotalCost(RepairBuddyJob $job): string
    {
        $total = 0;
        $items = $job->items()->get();
        foreach ($items as $item) {
            $price = (float) ($item->price ?? 0);
            $qty = (int) ($item->quantity ?? 1);
            $total += $price * $qty;
        }
        return $total > 0 ? '$' . number_format($total, 2) : 'TBD';
    }

    protected function getPickupLocation(RepairBuddyJob $job, Tenant $tenant): string
    {
        $branch = $job->branch;
        if ($branch && is_string($branch->address) && trim($branch->address) !== '') {
            return trim($branch->address);
        }
        return is_string($tenant->name) ? (string) $tenant->name : 'Our location';
    }

    protected function getPickupHours(Tenant $tenant): string
    {
        // Could be enhanced to get from tenant settings
        return 'Mon-Fri 9-6, Sat 10-4';
    }

    protected function formatStatus(?string $status): string
    {
        if (empty($status)) {
            return 'Unknown';
        }
        return ucwords(str_replace(['_', '-'], ' ', $status));
    }

    protected function getStatusColor(?string $status): string
    {
        return match ($status) {
            'in_progress', 'inprocess', 'in-process' => '#0ea5e9',
            'ready', 'ready_for_pickup', 'ready for pickup' => '#fd6742',
            'completed', 'delivered' => '#16a34a',
            default => '#0ea5e9',
        };
    }

    /**
     * Attach PDF to email if setting is enabled.
     */
    protected function attachPdfIfEnabled(object $mailable, RepairBuddyJob $job, Tenant $tenant, bool $attachPdf): object
    {
        if (! $attachPdf) {
            return $mailable;
        }

        try {
            $pdfService = new JobPdfService();
            $pdfContent = $pdfService->generatePdfContent($job, $tenant, $job->branch);
            $filename = $pdfService->generateFilename($job);

            $mailable->attachData($pdfContent, $filename, [
                'mime' => 'application/pdf',
            ]);
        } catch (\Throwable $e) {
            Log::warning('job.pdf_attachment_failed', [
                'job_id' => $job->id,
                'case_number' => $job->case_number,
                'error' => $e->getMessage(),
            ]);
        }

        return $mailable;
    }
}
