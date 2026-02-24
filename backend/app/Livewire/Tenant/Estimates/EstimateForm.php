<?php

namespace App\Livewire\Tenant\Estimates;

use App\Livewire\Tenant\Jobs\JobForm;
use App\Models\Branch;
use App\Models\RepairBuddyCustomerDevice;
use App\Models\RepairBuddyCustomerDeviceFieldValue;
use App\Models\RepairBuddyEstimate;
use App\Models\RepairBuddyEstimateDevice;
use App\Models\RepairBuddyEstimateItem;
use App\Models\RepairBuddyEvent;
use App\Support\BranchContext;
use Illuminate\Support\Facades\DB;

class EstimateForm extends JobForm
{
    public $estimate;
    public $estimateId;
    public ?string $estimate_status = 'draft';

    /** @var string[] Valid estimate statuses */
    public static array $estimateStatuses = [
        'draft',
        'sent',
        'approved',
        'rejected',
        'expired',
    ];

    public function mount(
        $tenant = null,
        $user = null,
        $activeNav = null,
        $pageTitle = null,
        $job = null,
        $jobId = null,
        $suggestedCaseNumber = null,
        $jobStatuses = null,
        $paymentStatuses = null,
        $customers = null,
        $technicians = null,
        $branches = null,
        $customerDevices = null,
        $devices = null,
        $parts = null,
        $services = null,
        $jobItems = null,
        $jobDevices = null,
        $jobExtras = null,
        // Estimate-specific params
        $estimate = null,
        $estimateId = null,
        $estimateItems = null,
        $estimateDevices = null,
    ): void {
        $this->formMode = 'estimate';
        $this->estimate = $estimate;
        $this->estimateId = $estimateId;

        // Call parent mount, passing null for job-specific params
        parent::mount(
            tenant: $tenant,
            user: $user,
            activeNav: $activeNav,
            pageTitle: $pageTitle,
            job: null,
            jobId: null,
            suggestedCaseNumber: $suggestedCaseNumber,
            jobStatuses: $jobStatuses ?? collect(),
            paymentStatuses: $paymentStatuses ?? collect(),
            customers: $customers,
            technicians: $technicians,
            branches: $branches,
            customerDevices: $customerDevices,
            devices: $devices,
            parts: $parts,
            services: $services,
            jobItems: null,
            jobDevices: null,
            jobExtras: null,
        );

        // Now populate from estimate model if editing
        $estimateModel = $estimate instanceof RepairBuddyEstimate ? $estimate : null;

        if ($estimateModel) {
            $this->case_number = $estimateModel->case_number;
            $this->title = $estimateModel->title;
            $this->estimate_status = $estimateModel->status ?? 'draft';
            $this->customer_id = $estimateModel->customer_id;
            $this->pickup_date = $estimateModel->pickup_date ? (string) $estimateModel->pickup_date : null;
            $this->delivery_date = $estimateModel->delivery_date ? (string) $estimateModel->delivery_date : null;
            $this->case_detail = $estimateModel->case_detail;

            // Single technician for estimates
            if ($estimateModel->assigned_technician_id) {
                $this->technician_ids = [(int) $estimateModel->assigned_technician_id];
            }
        }

        // Populate devices from estimate
        $this->deviceRows = [];
        if (is_iterable($estimateDevices)) {
            foreach ($estimateDevices as $ed) {
                $brandName = '';
                $deviceModel = '';
                if ($ed->customerDevice && $ed->customerDevice->device) {
                    $brandName = $ed->customerDevice->device->brand?->name ?? '';
                    $deviceModel = $ed->customerDevice->device->model ?? '';
                }

                $this->deviceRows[] = [
                    'customer_device_id' => $ed->customer_device_id ?? null,
                    'serial' => $ed->serial_snapshot ?? null,
                    'pin' => $ed->pin_snapshot ?? null,
                    'notes' => $ed->notes_snapshot ?? null,
                    'brand_name' => $brandName,
                    'device_model' => $deviceModel,
                    'image_url' => null,
                    'additional_fields' => $ed->extra_fields_snapshot_json ?? [],
                ];
            }
        }

        // Populate items from estimate
        $this->items = [];
        if (is_iterable($estimateItems)) {
            foreach ($estimateItems as $it) {
                $this->items[] = [
                    'type' => $it->item_type ?? null,
                    'name' => $it->name_snapshot ?? null,
                    'code' => null,
                    'qty' => $it->qty ?? 1,
                    'unit_price_cents' => ($it->unit_price_amount_cents ?? 0) / 100,
                    'meta_json' => is_array($it->meta_json ?? null) ? json_encode($it->meta_json) : null,
                ];
            }
        }
    }

    public function save()
    {
        // Use basic validation (skip job-specific fields like status_slug, payment_status_slug)
        $this->validate([
            'case_number' => ['nullable', 'string', 'max:64'],
            'title' => ['nullable', 'string', 'max:255'],
            'customer_id' => ['nullable', 'integer'],
            'pickup_date' => ['nullable', 'date'],
            'delivery_date' => ['nullable', 'date'],
            'case_detail' => ['nullable', 'string', 'max:5000'],
            'wc_order_note' => ['nullable', 'string', 'max:5000'],
            'estimate_status' => ['nullable', 'string', 'in:draft,sent,approved,rejected,expired'],
            'technician_ids' => ['array'],
            'technician_ids.*' => ['integer'],
            'deviceRows' => ['array'],
            'items' => ['array'],
        ]);

        $branch = BranchContext::branch();
        if (! $branch instanceof Branch) {
            abort(400, 'Tenant or branch context is missing.');
        }

        // Process new devices (create CustomerDevice records if needed)
        foreach ($this->deviceRows as $idx => $row) {
            if (! empty($row['customer_device_id'])) {
                continue;
            }
            if (! $this->customer_id) {
                continue;
            }

            $cd = RepairBuddyCustomerDevice::create([
                'tenant_id' => (int) $this->tenant->id,
                'branch_id' => (int) $branch->id,
                'customer_id' => (int) $this->customer_id,
                'device_id' => (int) ($row['device_id'] ?? 0),
                'label' => ($row['brand_name'] ?? '') . ' ' . ($row['device_model'] ?? ''),
                'serial' => $row['serial'] ?? null,
                'pin' => $row['pin'] ?? null,
                'notes' => $row['notes'] ?? null,
            ]);

            $this->deviceRows[$idx]['customer_device_id'] = $cd->id;

            // Save additional fields
            if (! empty($row['additional_fields'])) {
                foreach ($this->fieldDefinitions as $def) {
                    $val = $row['additional_fields'][$def->key] ?? '';
                    if ($val !== '') {
                        RepairBuddyCustomerDeviceFieldValue::create([
                            'tenant_id' => (int) $this->tenant->id,
                            'branch_id' => (int) $branch->id,
                            'customer_device_id' => $cd->id,
                            'field_definition_id' => $def->id,
                            'value_text' => $val,
                        ]);
                    }
                }
            }
        }

        $caseNumber = $this->case_number;
        if (! $caseNumber || trim($caseNumber) === '') {
            $caseNumber = $this->generateEstimateCaseNumber($this->tenant, $branch);
        }

        $title = $this->title;
        if (! $title || trim($title) === '') {
            $title = $caseNumber;
        }

        $currency = is_string($this->tenant->currency) && $this->tenant->currency !== ''
            ? strtoupper((string) $this->tenant->currency) : 'USD';

        // Single technician – take first from array
        $assignedTechId = ! empty($this->technician_ids) ? (int) $this->technician_ids[0] : null;

        $estimate = DB::transaction(function () use ($caseNumber, $title, $branch, $currency, $assignedTechId) {
            if ($this->estimateId && $this->estimate instanceof RepairBuddyEstimate) {
                // UPDATE existing
                $estimate = $this->estimate;
                $estimate->forceFill([
                    'case_number' => $caseNumber,
                    'title' => $title,
                    'status' => $this->estimate_status ?? 'draft',
                    'customer_id' => is_numeric($this->customer_id) ? (int) $this->customer_id : null,
                    'assigned_technician_id' => $assignedTechId,
                    'pickup_date' => $this->pickup_date,
                    'delivery_date' => $this->delivery_date,
                    'case_detail' => $this->case_detail,
                ])->save();

                // Rebuild devices
                RepairBuddyEstimateDevice::query()->where('estimate_id', $estimate->id)->delete();
                // Rebuild items
                RepairBuddyEstimateItem::query()->where('estimate_id', $estimate->id)->delete();

                $eventType = 'estimate.updated';
                $eventTitle = 'Estimate updated';
            } else {
                // CREATE new
                $estimate = RepairBuddyEstimate::query()->create([
                    'tenant_id' => (int) $this->tenant->id,
                    'branch_id' => (int) $branch->id,
                    'case_number' => $caseNumber,
                    'title' => $title,
                    'status' => $this->estimate_status ?? 'draft',
                    'customer_id' => is_numeric($this->customer_id) ? (int) $this->customer_id : null,
                    'created_by' => $this->user->id,
                    'assigned_technician_id' => $assignedTechId,
                    'pickup_date' => $this->pickup_date,
                    'delivery_date' => $this->delivery_date,
                    'case_detail' => $this->case_detail,
                ]);

                $eventType = 'estimate.created';
                $eventTitle = 'Estimate created';
            }

            // Save devices
            foreach ($this->deviceRows as $row) {
                $cdId = $row['customer_device_id'] ?? null;
                if (! is_numeric($cdId) || (int) $cdId <= 0) {
                    continue;
                }

                $cd = RepairBuddyCustomerDevice::query()->whereKey((int) $cdId)->first();

                RepairBuddyEstimateDevice::query()->create([
                    'estimate_id' => $estimate->id,
                    'customer_device_id' => (int) $cdId,
                    'label_snapshot' => $cd?->label ?? '',
                    'serial_snapshot' => ($row['serial'] ?? null) ?: ($cd?->serial),
                    'pin_snapshot' => $row['pin'] ?? null,
                    'notes_snapshot' => $row['notes'] ?? null,
                    'extra_fields_snapshot_json' => $row['additional_fields'] ?? [],
                ]);
            }

            // Save items – unit_price_cents is in dollars in the Livewire property, convert to cents
            foreach ($this->items as $item) {
                $name = $item['name'] ?? '';
                if (! is_string($name) || trim($name) === '') {
                    continue;
                }

                $priceCents = (int) round(($item['unit_price_cents'] ?? 0) * 100);

                RepairBuddyEstimateItem::query()->create([
                    'estimate_id' => $estimate->id,
                    'item_type' => is_string($item['type'] ?? null) ? (string) $item['type'] : 'other',
                    'ref_id' => null,
                    'name_snapshot' => trim($name),
                    'qty' => is_numeric($item['qty'] ?? null) ? (int) $item['qty'] : 1,
                    'unit_price_amount_cents' => $priceCents,
                    'unit_price_currency' => $currency,
                    'tax_id' => null,
                    'meta_json' => null,
                ]);
            }

            // Audit event
            RepairBuddyEvent::query()->create([
                'actor_user_id' => $this->user->id,
                'entity_type' => 'estimate',
                'entity_id' => $estimate->id,
                'visibility' => 'private',
                'event_type' => $eventType,
                'payload_json' => [
                    'title' => $eventTitle,
                    'case_number' => $caseNumber,
                ],
            ]);

            return $estimate;
        });

        return redirect()->route('tenant.estimates.show', [
            'business' => $this->tenant->slug,
            'estimateId' => $estimate->id,
        ]);
    }

    protected function generateEstimateCaseNumber($tenant, $branch): string
    {
        $prefix = 'EST-';
        $lastEstimate = RepairBuddyEstimate::query()
            ->where('tenant_id', (int) $tenant->id)
            ->where('branch_id', (int) $branch->id)
            ->orderByDesc('id')
            ->value('case_number');

        if ($lastEstimate && preg_match('/(\d+)$/', $lastEstimate, $m)) {
            $next = (int) $m[1] + 1;
        } else {
            $next = 1001;
        }

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function render()
    {
        return view('livewire.tenant.jobs.job-form');
    }
}
