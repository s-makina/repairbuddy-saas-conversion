<?php

namespace App\Services\Documents;

use App\Models\Branch;
use App\Models\RepairBuddyJob;
use App\Models\RepairBuddyJobDevice;
use App\Models\RepairBuddyJobItem;
use App\Models\Tenant;
use App\Support\BranchContext;
use Barryvdh\DomPDF\Facade\Pdf;

class JobPdfService
{
    /**
     * Generate PDF content for a job invoice/repair order.
     */
    public function generatePdfContent(RepairBuddyJob $job, Tenant $tenant, ?Branch $branch = null): string
    {
        $items = RepairBuddyJobItem::query()
            ->with(['tax'])
            ->where('job_id', $job->id)
            ->orderBy('id')
            ->get();

        $devices = RepairBuddyJobDevice::query()
            ->with(['customerDevice.device'])
            ->where('job_id', $job->id)
            ->orderBy('id')
            ->get();

        $customer = $job->customer;
        $technician = $job->technicians?->first() ?? $job->assignedTechnician;
        $statusLabel = $job->status_slug ?? 'Open';
        $paymentLabel = $job->payment_status_slug ?? '';
        $docNumber = $job->case_number ?? str_pad((string) $job->job_number, 5, '0', STR_PAD_LEFT);

        $shopName = $this->shopName($tenant, $branch);
        $shopAddress = $this->shopAddress($branch);
        $shopPhone = $branch?->phone ?? $tenant?->contact_phone ?? '';
        $shopEmail = $branch?->email ?? $tenant?->contact_email ?? '';
        $currencyCode = is_string($tenant?->currency) && $tenant->currency !== '' ? strtoupper((string) $tenant->currency) : 'USD';

        $warrantyLines = $this->resolveWarranty($job);

        $backUrl = '#';
        $pdfUrl = '#';

        $pdf = Pdf::loadView('print.document-a4', compact(
            'job', 'items', 'devices', 'customer', 'technician',
            'statusLabel', 'paymentLabel', 'docNumber',
            'shopName', 'shopAddress', 'shopPhone', 'shopEmail', 'currencyCode',
            'backUrl', 'pdfUrl', 'warrantyLines',
        ) + [
            'doc' => $job,
            'docType' => 'job',
        ])->setPaper('a4', 'portrait');

        return $pdf->output();
    }

    /**
     * Generate a filename for the job PDF.
     */
    public function generateFilename(RepairBuddyJob $job): string
    {
        $docNumber = $job->case_number ?? str_pad((string) $job->job_number, 5, '0', STR_PAD_LEFT);
        return 'job-' . $docNumber . '.pdf';
    }

    private function shopName(?Tenant $tenant, ?Branch $branch): string
    {
        if ($branch && is_string($branch->name) && $branch->name !== '') {
            return $branch->name;
        }

        return is_string($tenant?->name) ? (string) $tenant->name : 'Repair Shop';
    }

    private function shopAddress(?Branch $branch): string
    {
        if (! $branch) {
            return '';
        }

        $parts = array_filter([
            $branch->address_line1 ?? null,
            $branch->address_city ?? null,
            $branch->address_state ?? null,
            $branch->address_postal_code ?? null,
        ]);

        return implode(', ', $parts);
    }

    private function resolveWarranty(RepairBuddyJob $job): array
    {
        $lines = [];

        if ($job->items) {
            foreach ($job->items as $item) {
                $meta = is_string($item->meta_json)
                    ? json_decode($item->meta_json, true)
                    : (is_array($item->meta_json) ? $item->meta_json : []);

                $warranty = $meta['warranty'] ?? null;
                if ($warranty) {
                    $lines[] = $item->name_snapshot . ': ' . $warranty;
                }
            }
        }

        if (empty($lines)) {
            $lines = ['Parts: 90 days', 'Labour: 30 days'];
        }

        return $lines;
    }
}
