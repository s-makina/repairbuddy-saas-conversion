<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\RepairBuddyEstimate;
use App\Models\RepairBuddyEstimateDevice;
use App\Models\RepairBuddyEstimateItem;
use App\Models\RepairBuddyJob;
use App\Models\RepairBuddyJobDevice;
use App\Models\RepairBuddyJobItem;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PrintDocumentController extends Controller
{
    /* ------------------------------------------------------------------ */
    /*  JOB – HTML print view                                              */
    /* ------------------------------------------------------------------ */

    public function showJob(Request $request, string $business, $jobId)
    {
        if (! is_numeric($jobId)) {
            abort(404);
        }

        $tenant = TenantContext::tenant();
        $user   = $request->user();

        if (! $user) {
            return redirect()->route('web.login');
        }

        $branch = BranchContext::branch();

        $job = RepairBuddyJob::query()
            ->whereKey((int) $jobId)
            ->first();

        if (! $job) {
            abort(404);
        }

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

        $customer    = $job->customer;
        $technician  = $job->technicians?->first() ?? $job->assignedTechnician;
        $statusLabel = $job->status_slug ?? 'Open';
        $paymentLabel = $job->payment_status_slug ?? '';

        $docNumber = $job->case_number ?? str_pad((string) $job->job_number, 5, '0', STR_PAD_LEFT);

        $shopName    = $this->shopName($tenant, $branch);
        $shopAddress = $this->shopAddress($branch);
        $shopPhone   = $branch?->phone ?? $tenant?->contact_phone ?? '';
        $shopEmail   = $branch?->email ?? $tenant?->contact_email ?? '';
        $currencyCode = is_string($tenant?->currency) && $tenant->currency !== '' ? strtoupper((string) $tenant->currency) : 'USD';

        $warrantyLines = $this->resolveWarranty($job);

        $backUrl = route('tenant.jobs.show', ['business' => $business, 'jobId' => $job->id]);
        $pdfUrl  = route('tenant.jobs.pdf',  ['business' => $business, 'jobId' => $job->id]);

        return view('print.document-a4', compact(
            'job', 'items', 'devices', 'customer', 'technician',
            'statusLabel', 'paymentLabel', 'docNumber',
            'shopName', 'shopAddress', 'shopPhone', 'shopEmail', 'currencyCode',
            'backUrl', 'pdfUrl', 'warrantyLines',
        ) + [
            'doc'     => $job,
            'docType' => 'job',
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  JOB – PDF download                                                 */
    /* ------------------------------------------------------------------ */

    public function pdfJob(Request $request, string $business, $jobId)
    {
        if (! is_numeric($jobId)) {
            abort(404);
        }

        $tenant = TenantContext::tenant();
        $user   = $request->user();

        if (! $user) {
            return redirect()->route('web.login');
        }

        $branch = BranchContext::branch();

        $job = RepairBuddyJob::query()
            ->whereKey((int) $jobId)
            ->first();

        if (! $job) {
            abort(404);
        }

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

        $customer    = $job->customer;
        $technician  = $job->technicians?->first() ?? $job->assignedTechnician;
        $statusLabel  = $job->status_slug ?? 'Open';
        $paymentLabel = $job->payment_status_slug ?? '';
        $docNumber    = $job->case_number ?? str_pad((string) $job->job_number, 5, '0', STR_PAD_LEFT);

        $shopName    = $this->shopName($tenant, $branch);
        $shopAddress = $this->shopAddress($branch);
        $shopPhone   = $branch?->phone ?? $tenant?->contact_phone ?? '';
        $shopEmail   = $branch?->email ?? $tenant?->contact_email ?? '';
        $currencyCode = is_string($tenant?->currency) && $tenant->currency !== '' ? strtoupper((string) $tenant->currency) : 'USD';

        $warrantyLines = $this->resolveWarranty($job);

        $backUrl = route('tenant.jobs.show', ['business' => $business, 'jobId' => $job->id]);
        $pdfUrl  = '#';

        $pdf = Pdf::loadView('print.document-a4', compact(
            'job', 'items', 'devices', 'customer', 'technician',
            'statusLabel', 'paymentLabel', 'docNumber',
            'shopName', 'shopAddress', 'shopPhone', 'shopEmail', 'currencyCode',
            'backUrl', 'pdfUrl', 'warrantyLines',
        ) + [
            'doc'     => $job,
            'docType' => 'job',
        ])->setPaper('a4', 'portrait');

        return $pdf->download('job-' . $docNumber . '.pdf');
    }

    /* ------------------------------------------------------------------ */
    /*  ESTIMATE – HTML print view                                         */
    /* ------------------------------------------------------------------ */

    public function showEstimate(Request $request, string $business, $estimateId)
    {
        if (! is_numeric($estimateId)) {
            abort(404);
        }

        $tenant = TenantContext::tenant();
        $user   = $request->user();

        if (! $user) {
            return redirect()->route('web.login');
        }

        $branch = BranchContext::branch();

        $estimate = RepairBuddyEstimate::query()
            ->whereKey((int) $estimateId)
            ->first();

        if (! $estimate) {
            abort(404);
        }

        $items = RepairBuddyEstimateItem::query()
            ->with(['tax'])
            ->where('estimate_id', $estimate->id)
            ->orderBy('id')
            ->get();

        $devices = RepairBuddyEstimateDevice::query()
            ->with(['customerDevice.device'])
            ->where('estimate_id', $estimate->id)
            ->orderBy('id')
            ->get();

        $customer   = $estimate->customer;
        $technician = $estimate->assignedTechnician;
        $statusLabel = ucfirst($estimate->status ?? 'Draft');

        $docNumber = $estimate->case_number ?? ('EST-' . str_pad((string) $estimate->id, 5, '0', STR_PAD_LEFT));

        $shopName    = $this->shopName($tenant, $branch);
        $shopAddress = $this->shopAddress($branch);
        $shopPhone   = $branch?->phone ?? $tenant?->contact_phone ?? '';
        $shopEmail   = $branch?->email ?? $tenant?->contact_email ?? '';
        $currencyCode = is_string($tenant?->currency) && $tenant->currency !== '' ? strtoupper((string) $tenant->currency) : 'USD';

        $warrantyLines = [];

        $backUrl = route('tenant.estimates.show', ['business' => $business, 'estimateId' => $estimate->id]);
        $pdfUrl  = route('tenant.estimates.pdf',  ['business' => $business, 'estimateId' => $estimate->id]);

        return view('print.document-a4', compact(
            'estimate', 'items', 'devices', 'customer', 'technician',
            'statusLabel', 'docNumber',
            'shopName', 'shopAddress', 'shopPhone', 'shopEmail', 'currencyCode',
            'backUrl', 'pdfUrl', 'warrantyLines',
        ) + [
            'doc'          => $estimate,
            'docType'      => 'estimate',
            'paymentLabel' => '',
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  ESTIMATE – PDF download                                            */
    /* ------------------------------------------------------------------ */

    public function pdfEstimate(Request $request, string $business, $estimateId)
    {
        if (! is_numeric($estimateId)) {
            abort(404);
        }

        $tenant = TenantContext::tenant();
        $user   = $request->user();

        if (! $user) {
            return redirect()->route('web.login');
        }

        $branch = BranchContext::branch();

        $estimate = RepairBuddyEstimate::query()
            ->whereKey((int) $estimateId)
            ->first();

        if (! $estimate) {
            abort(404);
        }

        $items = RepairBuddyEstimateItem::query()
            ->with(['tax'])
            ->where('estimate_id', $estimate->id)
            ->orderBy('id')
            ->get();

        $devices = RepairBuddyEstimateDevice::query()
            ->with(['customerDevice.device'])
            ->where('estimate_id', $estimate->id)
            ->orderBy('id')
            ->get();

        $customer   = $estimate->customer;
        $technician = $estimate->assignedTechnician;
        $statusLabel = ucfirst($estimate->status ?? 'Draft');
        $docNumber   = $estimate->case_number ?? ('EST-' . str_pad((string) $estimate->id, 5, '0', STR_PAD_LEFT));

        $shopName    = $this->shopName($tenant, $branch);
        $shopAddress = $this->shopAddress($branch);
        $shopPhone   = $branch?->phone ?? $tenant?->contact_phone ?? '';
        $shopEmail   = $branch?->email ?? $tenant?->contact_email ?? '';
        $currencyCode = is_string($tenant?->currency) && $tenant->currency !== '' ? strtoupper((string) $tenant->currency) : 'USD';

        $warrantyLines = [];

        $backUrl = '#';
        $pdfUrl  = '#';

        $pdf = Pdf::loadView('print.document-a4', compact(
            'estimate', 'items', 'devices', 'customer', 'technician',
            'statusLabel', 'docNumber',
            'shopName', 'shopAddress', 'shopPhone', 'shopEmail', 'currencyCode',
            'backUrl', 'pdfUrl', 'warrantyLines',
        ) + [
            'doc'          => $estimate,
            'docType'      => 'estimate',
            'paymentLabel' => '',
        ])->setPaper('a4', 'portrait');

        return $pdf->download('estimate-' . $docNumber . '.pdf');
    }

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                            */
    /* ------------------------------------------------------------------ */

    private function shopName($tenant, $branch): string
    {
        if ($branch && is_string($branch->name) && $branch->name !== '') {
            return $branch->name;
        }

        return is_string($tenant?->name) ? (string) $tenant->name : 'Repair Shop';
    }

    private function shopAddress($branch): string
    {
        if (! $branch) {
            return '';
        }

        $parts = array_filter([
            $branch->address_line1 ?? null,
            $branch->address_city  ?? null,
            $branch->address_state ?? null,
            $branch->address_postal_code ?? null,
        ]);

        return implode(', ', $parts);
    }

    private function resolveWarranty(RepairBuddyJob $job): array
    {
        // Default warranty lines — extend if you store warranty data on items/job
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
