<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\RepairBuddyEvent;
use App\Models\RepairBuddyJob;
use App\Models\RepairBuddyJobAttachment;
use App\Models\RepairBuddyCustomerDevice;
use App\Models\RepairBuddyJobDevice;
use App\Models\RepairBuddyJobItem;
use App\Models\RepairBuddyJobStatus;
use App\Models\RepairBuddyPaymentStatus;
use App\Support\TenantContext;
use App\Support\BranchContext;
use App\Support\RepairBuddyCaseNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TenantJobController extends Controller
{
    public function create(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();
        $user = $request->user();

        if (! $user) {
            return redirect()->route('web.login');
        }

        $branch = BranchContext::branch();
        if (! $tenant || ! $branch instanceof Branch) {
            abort(400, 'Tenant or branch context is missing.');
        }

        $jobStatuses = RepairBuddyJobStatus::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        $paymentStatuses = RepairBuddyPaymentStatus::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        $customers = \App\Models\User::query()
            ->where('tenant_id', (int) $tenant->id)
            ->where('role', 'customer')
            ->orderBy('name')
            ->limit(500)
            ->get();

        $technicians = \App\Models\User::query()
            ->where('tenant_id', (int) $tenant->id)
            ->whereIn('role', ['technician', 'store_manager', 'administrator'])
            ->orderBy('name')
            ->limit(500)
            ->get();

        $customerDevices = RepairBuddyCustomerDevice::query()
            ->with(['customer'])
            ->orderBy('id', 'desc')
            ->limit(500)
            ->get();

        return view('tenant.job_create', [
            'tenant' => $tenant,
            'user' => $user,
            'activeNav' => 'jobs',
            'pageTitle' => 'New Job',
            'suggestedCaseNumber' => null,
            'jobStatuses' => $jobStatuses,
            'paymentStatuses' => $paymentStatuses,
            'customers' => $customers,
            'technicians' => $technicians,
            'customerDevices' => $customerDevices,
        ]);
    }

    public function store(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();
        $user = $request->user();

        if (! $user) {
            return redirect()->route('web.login');
        }

        $branch = BranchContext::branch();
        if (! $tenant || ! $branch instanceof Branch) {
            abort(400, 'Tenant or branch context is missing.');
        }

        $validated = $request->validate([
            'case_number' => ['sometimes', 'nullable', 'string', 'max:64'],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status_slug' => ['required', 'string', 'max:64'],
            'payment_status_slug' => ['sometimes', 'nullable', 'string', 'max:64'],
            'priority' => ['sometimes', 'nullable', 'string', 'max:32'],
            'customer_id' => ['sometimes', 'nullable', 'integer'],
            'pickup_date' => ['sometimes', 'nullable', 'date'],
            'delivery_date' => ['sometimes', 'nullable', 'date'],
            'next_service_date' => ['sometimes', 'nullable', 'date'],
            'case_detail' => ['sometimes', 'nullable', 'string', 'max:5000'],

            'technician_ids' => ['sometimes', 'array'],
            'technician_ids.*' => ['integer'],

            'customer_device_ids' => ['sometimes', 'array'],
            'customer_device_ids.*' => ['integer'],

            'item_type' => ['sometimes', 'array'],
            'item_type.*' => ['sometimes', 'nullable', 'string', 'max:32'],
            'item_name' => ['sometimes', 'array'],
            'item_name.*' => ['sometimes', 'nullable', 'string', 'max:255'],
            'item_qty' => ['sometimes', 'array'],
            'item_qty.*' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:9999'],
            'item_unit_price_cents' => ['sometimes', 'array'],
            'item_unit_price_cents.*' => ['sometimes', 'nullable', 'integer', 'min:-1000000000', 'max:1000000000'],
        ]);

        $settings = data_get($tenant->setup_state ?? [], 'repairbuddy_settings');
        $general = is_array($settings) ? (array) data_get($settings, 'general', []) : [];

        $caseNumber = is_string($validated['case_number'] ?? null) ? trim((string) $validated['case_number']) : '';
        if ($caseNumber === '') {
            $caseService = new RepairBuddyCaseNumberService();
            $caseNumber = DB::transaction(function () use ($caseService, $tenant, $branch, $general) {
                return $caseService->nextCaseNumber($tenant, $branch, $general);
            });
        }

        $title = is_string($validated['title'] ?? null) ? trim((string) $validated['title']) : '';
        if ($title === '') {
            $title = $caseNumber;
        }

        $job = DB::transaction(function () use ($validated, $tenant, $branch, $user, $caseNumber, $title) {
            $job = RepairBuddyJob::query()->create([
                'tenant_id' => (int) $tenant->id,
                'branch_id' => (int) $branch->id,
                'case_number' => $caseNumber,
                'title' => $title,
                'status_slug' => (string) $validated['status_slug'],
                'payment_status_slug' => is_string($validated['payment_status_slug'] ?? null) ? (string) $validated['payment_status_slug'] : null,
                'priority' => is_string($validated['priority'] ?? null) && $validated['priority'] !== '' ? (string) $validated['priority'] : 'normal',
                'customer_id' => array_key_exists('customer_id', $validated) && is_numeric($validated['customer_id']) ? (int) $validated['customer_id'] : null,
                'created_by' => $user->id,
                'opened_at' => now(),
                'pickup_date' => $validated['pickup_date'] ?? null,
                'delivery_date' => $validated['delivery_date'] ?? null,
                'next_service_date' => $validated['next_service_date'] ?? null,
                'case_detail' => $validated['case_detail'] ?? null,
            ]);

            $techIds = array_key_exists('technician_ids', $validated) && is_array($validated['technician_ids'])
                ? array_values(array_unique(array_map('intval', $validated['technician_ids'])))
                : [];

            if (count($techIds) > 0) {
                $job->technicians()->sync($techIds);
            }

            $deviceIds = array_key_exists('customer_device_ids', $validated) && is_array($validated['customer_device_ids'])
                ? array_values(array_unique(array_map('intval', $validated['customer_device_ids'])))
                : [];

            if (count($deviceIds) > 0) {
                $devices = RepairBuddyCustomerDevice::query()->whereIn('id', $deviceIds)->get()->keyBy('id');
                foreach ($deviceIds as $cdId) {
                    $cd = $devices->get($cdId);
                    if (! $cd) {
                        continue;
                    }

                    RepairBuddyJobDevice::query()->create([
                        'job_id' => $job->id,
                        'customer_device_id' => $cd->id,
                        'label_snapshot' => $cd->label,
                        'serial_snapshot' => $cd->serial,
                        'pin_snapshot' => $cd->pin,
                        'notes_snapshot' => $cd->notes,
                    ]);
                }
            }

            $types = is_array($validated['item_type'] ?? null) ? $validated['item_type'] : [];
            $names = is_array($validated['item_name'] ?? null) ? $validated['item_name'] : [];
            $qtys = is_array($validated['item_qty'] ?? null) ? $validated['item_qty'] : [];
            $prices = is_array($validated['item_unit_price_cents'] ?? null) ? $validated['item_unit_price_cents'] : [];

            $max = max(count($types), count($names), count($qtys), count($prices));
            for ($i = 0; $i < $max; $i++) {
                $t = is_string($types[$i] ?? null) ? trim((string) $types[$i]) : '';
                $n = is_string($names[$i] ?? null) ? trim((string) $names[$i]) : '';
                if ($t === '' || $n === '') {
                    continue;
                }
                if (! in_array($t, ['service', 'part', 'fee', 'discount'], true)) {
                    continue;
                }

                $q = is_numeric($qtys[$i] ?? null) ? (int) $qtys[$i] : 1;
                $p = is_numeric($prices[$i] ?? null) ? (int) $prices[$i] : 0;

                RepairBuddyJobItem::query()->create([
                    'job_id' => $job->id,
                    'item_type' => $t,
                    'ref_id' => null,
                    'name_snapshot' => $n,
                    'qty' => $q,
                    'unit_price_amount_cents' => $p,
                    'unit_price_currency' => is_string($tenant->currency ?? null) ? (string) $tenant->currency : null,
                    'tax_id' => null,
                    'meta_json' => null,
                ]);
            }

            RepairBuddyEvent::query()->create([
                'actor_user_id' => $user->id,
                'entity_type' => 'job',
                'entity_id' => $job->id,
                'visibility' => 'private',
                'event_type' => 'job.created',
                'payload_json' => [
                    'title' => 'Job created',
                    'message' => 'Job created.',
                ],
            ]);

            return $job;
        });

        return redirect()->route('tenant.jobs.show', ['business' => $tenant->slug, 'jobId' => $job->id]);
    }

    public function show(Request $request, string $business, $jobId)
    {
        if (! is_numeric($jobId)) {
            abort(404);
        }

        $tenant = TenantContext::tenant();
        $user = $request->user();

        if (! $user) {
            return redirect()->route('web.login');
        }

        $job = RepairBuddyJob::query()
            ->with(['customer', 'technicians', 'jobDevices'])
            ->whereKey((int) $jobId)
            ->first();

        if (! $job) {
            abort(404);
        }

        $items = RepairBuddyJobItem::query()
            ->with(['tax'])
            ->where('job_id', $job->id)
            ->orderBy('id', 'asc')
            ->get();

        $devices = RepairBuddyJobDevice::query()
            ->where('job_id', $job->id)
            ->orderBy('id', 'desc')
            ->get();

        $attachments = RepairBuddyJobAttachment::query()
            ->where('job_id', $job->id)
            ->orderBy('id', 'desc')
            ->get();

        $events = RepairBuddyEvent::query()
            ->with(['actor'])
            ->where('entity_type', 'job')
            ->where('entity_id', $job->id)
            ->orderBy('created_at', 'desc')
            ->limit(200)
            ->get();

        $currency = is_string($tenant?->currency) && $tenant?->currency !== '' ? strtoupper((string) $tenant->currency) : 'USD';

        $itemsSubtotalCents = 0;
        foreach ($items as $item) {
            $qty = is_numeric($item->qty) ? (int) $item->qty : 1;
            $unit = is_numeric($item->unit_price_amount_cents) ? (int) $item->unit_price_amount_cents : 0;
            $itemsSubtotalCents += ($qty * $unit);
        }

        $totals = [
            'items_subtotal_cents' => $itemsSubtotalCents,
            'tax_total_cents' => null,
            'grand_total_cents' => $itemsSubtotalCents,
            'paid_total_cents' => null,
            'balance_cents' => null,
            'currency' => $currency,
        ];

        return view('tenant.job_show', [
            'tenant' => $tenant,
            'user' => $user,
            'activeNav' => 'jobs',
            'pageTitle' => 'Job ' . $job->case_number,
            'job' => $job,
            'jobItems' => $items,
            'jobDevices' => $devices,
            'jobAttachments' => $attachments,
            'jobEvents' => $events,
            'totals' => $totals,
        ]);
    }
}
