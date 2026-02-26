<?php

namespace App\Http\Controllers\Web;

use App\Actions\RepairBuddy\UpsertRepairBuddyJob;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\RepairBuddyCustomerDevice;
use App\Models\RepairBuddyJobAttachment;
use App\Models\RepairBuddyCustomerDeviceFieldValue;
use App\Models\RepairBuddyDeviceFieldDefinition;
use App\Models\RepairBuddyDevice;
use App\Models\RepairBuddyJob;
use App\Models\RepairBuddyJobDevice;
use App\Models\RepairBuddyPayment;
use App\Models\RepairBuddyPaymentStatus;
use App\Models\RepairBuddyTax;
use App\Services\TenantSettings\TenantSettingsStore;
use App\Models\RepairBuddyJobExtraItem;
use App\Models\RepairBuddyJobCounter;
use App\Models\RepairBuddyJobItem;
use App\Models\RepairBuddyPart;
use App\Models\RepairBuddyService;
use App\Models\RepairBuddyEvent;
use App\Models\RepairBuddyTimeLog;
use App\Models\Role;
use App\Models\Status;
use App\Models\Tenant;
use App\Models\User;
use App\Services\RepairBuddyCaseNumberService;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class TenantJobController extends Controller
{
    public function datatable(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $branch = BranchContext::branch();
        if (! $tenant || ! $branch instanceof Branch) {
            return response()->json(['message' => 'Tenant or branch context is missing.'], 400);
        }

        $query = RepairBuddyJob::query()
            ->with(['customer', 'technicians', 'jobDevices'])
            ->where('tenant_id', (int) $tenant->id)
            ->where('branch_id', (int) $branch->id);

        return DataTables::eloquent($query)
            ->addColumn('job_id_display', function (RepairBuddyJob $job) {
                $num = is_numeric($job->job_number) ? (int) $job->job_number : (int) $job->id;
                return str_pad((string) $num, 5, '0', STR_PAD_LEFT);
            })
            ->addColumn('case_number_display', function (RepairBuddyJob $job) {
                return is_string($job->case_number) ? (string) $job->case_number : '';
            })
            ->addColumn('tech_display', function (RepairBuddyJob $job) {
                $tech = $job->technicians->first();
                return $tech?->name ?? '';
            })
            ->addColumn('customer_display', function (RepairBuddyJob $job) {
                $c = $job->customer;
                if (! $c) {
                    return '';
                }

                $name = is_string($c->name ?? null) ? (string) $c->name : '';
                $phone = is_string($c->phone ?? null) ? trim((string) $c->phone) : '';
                $email = is_string($c->email ?? null) ? trim((string) $c->email) : '';

                $parts = [];
                if ($name !== '') {
                    $parts[] = e($name);
                }
                if ($phone !== '') {
                    $parts[] = '<strong>P</strong>: ' . e($phone);
                }
                if ($email !== '') {
                    $parts[] = '<strong>E</strong>: ' . e($email);
                }

                return implode('<br>', $parts);
            })
            ->addColumn('devices_display', function (RepairBuddyJob $job) {
                $labels = [];
                foreach ($job->jobDevices as $d) {
                    $label = is_string($d->label_snapshot ?? null) ? trim((string) $d->label_snapshot) : '';
                    if ($label !== '') {
                        $labels[] = $label;
                    }
                }
                return e(implode(', ', array_slice($labels, 0, 3)));
            })
            ->addColumn('dates_display', function (RepairBuddyJob $job) {
                $lines = [];
                if ($job->pickup_date) {
                    $lines[] = '<strong>P</strong>:' . e($job->pickup_date->format('m/d/Y'));
                }
                if ($job->delivery_date) {
                    $lines[] = '<strong>D</strong>:' . e($job->delivery_date->format('m/d/Y'));
                }
                if ($job->next_service_date) {
                    $lines[] = '<strong>N</strong>:' . e($job->next_service_date->format('m/d/Y'));
                }
                return implode('<br>', $lines);
            })
            ->addColumn('total_display', function (RepairBuddyJob $job) {
                return '';
            })
            ->addColumn('balance_display', function (RepairBuddyJob $job) {
                return '';
            })
            ->addColumn('payment_display', function (RepairBuddyJob $job) {
                return is_string($job->payment_status_slug) ? e((string) $job->payment_status_slug) : '';
            })
            ->addColumn('status_display', function (RepairBuddyJob $job) {
                $slug = is_string($job->status_slug) ? trim((string) $job->status_slug) : '';
                if ($slug === '') {
                    return '';
                }

                $label = strtoupper(str_replace(['-', '_'], ' ', $slug));
                return '<span class="wcrb-pill wcrb-pill--active">' . e($label) . '</span>';
            })
            ->addColumn('priority_display', function (RepairBuddyJob $job) {
                return is_string($job->priority) ? e((string) $job->priority) : '';
            })
            ->addColumn('actions_display', function (RepairBuddyJob $job) use ($tenant) {
                if (! $tenant?->slug) {
                    return '';
                }
                $url      = route('tenant.jobs.show', ['business' => $tenant->slug, 'jobId' => $job->id]);
                $viewBtn  = '<a class="btn btn-outline-primary btn-sm" href="' . e($url) . '" title="' . e(__('View')) . '" aria-label="' . e(__('View')) . '"><i class="bi bi-eye"></i></a>';
                $printBtn = '<button type="button" class="btn btn-outline-secondary btn-sm ms-1" title="' . e(__('Preview / Print')) . '" onclick="openDocPreview(\'job\',' . (int) $job->id . ')"><i class="bi bi-printer"></i></button>';
                return $viewBtn . $printBtn;
            })
            ->filter(function ($query) use ($request) {
                $search = $request->input('search.value');
                $search = is_string($search) ? trim($search) : '';
                if ($search === '') {
                    return;
                }

                $query->where(function ($q) use ($search) {
                    $q->where('case_number', 'like', '%' . $search . '%')
                        ->orWhere('title', 'like', '%' . $search . '%')
                        ->orWhere('case_detail', 'like', '%' . $search . '%');
                });
            })
            ->rawColumns([
                'customer_display',
                'dates_display',
                'status_display',
                'actions_display',
            ])
            ->toJson();
    }

    private function nextJobNumber(int $tenantId, int $branchId): int
    {
        $row = RepairBuddyJobCounter::query()
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->lockForUpdate()
            ->first();

        if (! $row) {
            $row = RepairBuddyJobCounter::query()->create([
                'tenant_id' => $tenantId,
                'branch_id' => $branchId,
                'next_number' => 2,
            ]);

            return 1;
        }

        $next = is_numeric($row->next_number) ? (int) $row->next_number : 1;
        if ($next < 1) {
            $next = 1;
        }

        $row->forceFill([
            'next_number' => $next + 1,
        ])->save();

        return $next;
    }

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

        $jobStatuses = Status::query()
            ->where('status_type', 'Job')
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        $paymentStatuses = Status::query()
            ->where('status_type', 'Payment')
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        $customers = \App\Models\User::query()
            ->where('tenant_id', (int) $tenant->id)
            ->where('role', 'customer')
            ->orderBy('name')
            ->limit(500)
            ->get();

        $technicianRoleId = Role::query()
            ->where('tenant_id', (int) $tenant->id)
            ->where('name', 'Technician')
            ->value('id');

        $technicianRoleId = is_numeric($technicianRoleId) ? (int) $technicianRoleId : null;

        $technicians = \App\Models\User::query()
            ->where('tenant_id', (int) $tenant->id)
            ->where('is_admin', false)
            ->where('status', 'active')
            ->where(function ($q) use ($technicianRoleId) {
                if ($technicianRoleId) {
                    $q->where('role_id', $technicianRoleId);
                }

                $q->orWhereHas('roles', fn ($rq) => $rq->where('name', 'Technician'))
                    ->orWhere('role', 'technician');
            })
            ->orderBy('name')
            ->limit(500)
            ->get();

        $customerDevices = RepairBuddyCustomerDevice::query()
            ->with(['customer'])
            ->where('tenant_id', (int) $tenant->id)
            ->where('branch_id', (int) $branch->id)
            ->orderBy('id', 'desc')
            ->limit(500)
            ->get();

        $devices = RepairBuddyDevice::query()
            ->with(['type', 'brand', 'parent'])
            ->where('tenant_id', (int) $tenant->id)
            ->where('branch_id', (int) $branch->id)
            ->where('is_active', true)
            ->orderBy('model')
            ->limit(2000)
            ->get();

        $parts = RepairBuddyPart::query()
            ->where('tenant_id', (int) $tenant->id)
            ->where('branch_id', (int) $branch->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(1000)
            ->get(['id', 'name', 'sku']);

        $services = RepairBuddyService::query()
            ->where('tenant_id', (int) $tenant->id)
            ->where('branch_id', (int) $branch->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(1000)
            ->get(['id', 'name', 'service_code']);

        $branches = Branch::query()
            ->where('tenant_id', (int) $tenant->id)
            ->orderBy('code')
            ->orderBy('name')
            ->limit(200)
            ->get();

        return view('tenant.job_create', [
            'tenant' => $tenant,
            'user' => $user,
            'activeNav' => 'jobs',
            'pageTitle' => 'New Job',
            'job' => null,
            'jobId' => null,
            'suggestedCaseNumber' => null,
            'jobStatuses' => $jobStatuses,
            'paymentStatuses' => $paymentStatuses,
            'customers' => $customers,
            'technicians' => $technicians,
            'branches' => $branches,
            'customerDevices' => $customerDevices,
            'devices' => $devices,
            'parts' => $parts,
            'services' => $services,
            'jobItems' => [],
            'jobDevices' => [],
        ]);
    }

    public function edit(Request $request, string $business, $jobId)
    {
        if (! is_numeric($jobId)) {
            abort(404);
        }

        $tenant = TenantContext::tenant();
        $user = $request->user();

        if (! $user) {
            return redirect()->route('web.login');
        }

        $branch = BranchContext::branch();
        if (! $tenant || ! $branch instanceof Branch) {
            abort(400, 'Tenant or branch context is missing.');
        }

        $job = RepairBuddyJob::query()
            ->with(['technicians'])
            ->whereKey((int) $jobId)
            ->first();

        if (! $job) {
            abort(404);
        }

        $jobStatuses = Status::query()
            ->where('status_type', 'Job')
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        $paymentStatuses = Status::query()
            ->where('status_type', 'Payment')
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        $customers = \App\Models\User::query()
            ->where('tenant_id', (int) $tenant->id)
            ->where('role', 'customer')
            ->orderBy('name')
            ->limit(500)
            ->get();

        $technicianRoleId = Role::query()
            ->where('tenant_id', (int) $tenant->id)
            ->where('name', 'Technician')
            ->value('id');
        $technicianRoleId = is_numeric($technicianRoleId) ? (int) $technicianRoleId : null;

        $technicians = \App\Models\User::query()
            ->where('tenant_id', (int) $tenant->id)
            ->where('is_admin', false)
            ->where('status', 'active')
            ->where(function ($q) use ($technicianRoleId) {
                if ($technicianRoleId) {
                    $q->where('role_id', $technicianRoleId);
                }

                $q->orWhereHas('roles', fn ($rq) => $rq->where('name', 'Technician'))
                    ->orWhere('role', 'technician');
            })
            ->orderBy('name')
            ->limit(500)
            ->get();

        $customerDevices = RepairBuddyCustomerDevice::query()
            ->with(['customer'])
            ->where('tenant_id', (int) $tenant->id)
            ->where('branch_id', (int) $branch->id)
            ->orderBy('id', 'desc')
            ->limit(500)
            ->get();

        $devices = RepairBuddyDevice::query()
            ->with(['type', 'brand', 'parent'])
            ->where('tenant_id', (int) $tenant->id)
            ->where('branch_id', (int) $branch->id)
            ->where('is_active', true)
            ->orderBy('model')
            ->limit(2000)
            ->get();

        $parts = RepairBuddyPart::query()
            ->where('tenant_id', (int) $tenant->id)
            ->where('branch_id', (int) $branch->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(1000)
            ->get(['id', 'name', 'sku']);

        $services = RepairBuddyService::query()
            ->where('tenant_id', (int) $tenant->id)
            ->where('branch_id', (int) $branch->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(1000)
            ->get(['id', 'name', 'service_code']);

        $branches = Branch::query()
            ->where('tenant_id', (int) $tenant->id)
            ->orderBy('code')
            ->orderBy('name')
            ->limit(200)
            ->get();

        $jobDevices = RepairBuddyJobDevice::query()
            ->where('job_id', $job->id)
            ->orderBy('id', 'desc')
            ->get();

        $jobItems = RepairBuddyJobItem::query()
            ->where('job_id', $job->id)
            ->orderBy('id', 'asc')
            ->get();

        $jobExtras = RepairBuddyJobExtraItem::query()
            ->where('job_id', $job->id)
            ->orderBy('id', 'asc')
            ->get();

        return view('tenant.job_create', [
            'tenant' => $tenant,
            'user' => $user,
            'activeNav' => 'jobs',
            'pageTitle' => 'Edit Job ' . $job->case_number,
            'job' => $job,
            'jobId' => $job->id,
            'suggestedCaseNumber' => null,
            'jobStatuses' => $jobStatuses,
            'paymentStatuses' => $paymentStatuses,
            'customers' => $customers,
            'technicians' => $technicians,
            'branches' => $branches,
            'customerDevices' => $customerDevices,
            'devices' => $devices,
            'parts' => $parts,
            'services' => $services,
            'jobItems' => $jobItems,
            'jobDevices' => $jobDevices,
            'jobExtras' => $jobExtras,
        ]);
    }

    public function update(Request $request, string $business, $jobId)
    {
        if (! is_numeric($jobId)) {
            abort(404);
        }

        $tenant = TenantContext::tenant();
        $user = $request->user();

        if (! $user) {
            return redirect()->route('web.login');
        }

        $branch = BranchContext::branch();
        if (! $tenant || ! $branch instanceof Branch) {
            abort(400, 'Tenant or branch context is missing.');
        }

        $job = RepairBuddyJob::query()->whereKey((int) $jobId)->first();
        if (! $job) {
            abort(404);
        }

        $validated = $request->validate([
            'case_number' => ['sometimes', 'nullable', 'string', 'max:64'],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status_slug' => ['sometimes', 'nullable', 'string', 'max:64'],
            'payment_status_slug' => ['sometimes', 'nullable', 'string', 'max:64'],
            'prices_inclu_exclu' => ['sometimes', 'nullable', 'string', 'in:inclusive,exclusive'],
            'priority' => ['sometimes', 'nullable', 'string', 'max:32'],
            'can_review_it' => ['sometimes', 'nullable', 'boolean'],
            'customer_id' => ['sometimes', 'nullable', 'integer'],
            'pickup_date' => ['sometimes', 'nullable', 'date'],
            'delivery_date' => ['sometimes', 'nullable', 'date'],
            'next_service_date' => ['sometimes', 'nullable', 'date'],
            'case_detail' => ['sometimes', 'nullable', 'string', 'max:5000'],

            'technician_ids' => ['sometimes', 'array'],
            'technician_ids.*' => ['integer'],

            'job_device_customer_device_id' => ['sometimes', 'array'],
            'job_device_customer_device_id.*' => ['sometimes', 'nullable', 'integer'],
            'job_device_serial' => ['sometimes', 'array'],
            'job_device_serial.*' => ['sometimes', 'nullable', 'string', 'max:255'],
            'job_device_pin' => ['sometimes', 'array'],
            'job_device_pin.*' => ['sometimes', 'nullable', 'string', 'max:255'],
            'job_device_notes' => ['sometimes', 'array'],
            'job_device_notes.*' => ['sometimes', 'nullable', 'string', 'max:5000'],

            'item_type' => ['sometimes', 'array'],
            'item_type.*' => ['sometimes', 'nullable', 'string', 'max:32'],
            'item_name' => ['sometimes', 'array'],
            'item_name.*' => ['sometimes', 'nullable', 'string', 'max:255'],
            'item_qty' => ['sometimes', 'array'],
            'item_qty.*' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:9999'],
            'item_unit_price_cents' => ['sometimes', 'array'],
            'item_unit_price_cents.*' => ['sometimes', 'nullable', 'integer', 'min:-1000000000', 'max:1000000000'],
            'item_meta_json' => ['sometimes', 'array'],
            'item_meta_json.*' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        $job = (new UpsertRepairBuddyJob())->update($tenant, $user, $job, $validated);

        return redirect()->route('tenant.jobs.show', ['business' => $tenant->slug, 'jobId' => $job->id]);
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
            'status_slug' => ['sometimes', 'nullable', 'string', 'max:64'],
            'payment_status_slug' => ['sometimes', 'nullable', 'string', 'max:64'],
            'prices_inclu_exclu' => ['sometimes', 'nullable', 'string', 'in:inclusive,exclusive'],
            'priority' => ['sometimes', 'nullable', 'string', 'max:32'],
            'can_review_it' => ['sometimes', 'nullable', 'boolean'],
            'customer_id' => ['sometimes', 'nullable', 'integer'],
            'pickup_date' => ['sometimes', 'nullable', 'date'],
            'delivery_date' => ['sometimes', 'nullable', 'date'],
            'next_service_date' => ['sometimes', 'nullable', 'date'],
            'case_detail' => ['sometimes', 'nullable', 'string', 'max:5000'],

            'wc_order_note' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'job_file' => ['sometimes', 'nullable', 'file', 'max:20480'],

            'technician_ids' => ['sometimes', 'array'],
            'technician_ids.*' => ['integer'],

            'job_device_customer_device_id' => ['sometimes', 'array'],
            'job_device_customer_device_id.*' => ['sometimes', 'nullable', 'integer'],
            'job_device_serial' => ['sometimes', 'array'],
            'job_device_serial.*' => ['sometimes', 'nullable', 'string', 'max:255'],
            'job_device_pin' => ['sometimes', 'array'],
            'job_device_pin.*' => ['sometimes', 'nullable', 'string', 'max:255'],
            'job_device_notes' => ['sometimes', 'array'],
            'job_device_notes.*' => ['sometimes', 'nullable', 'string', 'max:5000'],

            'item_type' => ['sometimes', 'array'],
            'item_type.*' => ['sometimes', 'nullable', 'string', 'max:32'],
            'item_name' => ['sometimes', 'array'],
            'item_name.*' => ['sometimes', 'nullable', 'string', 'max:255'],
            'item_qty' => ['sometimes', 'array'],
            'item_qty.*' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:9999'],
            'item_unit_price_cents' => ['sometimes', 'array'],
            'item_unit_price_cents.*' => ['sometimes', 'nullable', 'integer', 'min:-1000000000', 'max:1000000000'],

            'extra_item_occurred_at' => ['sometimes', 'array'],
            'extra_item_occurred_at.*' => ['sometimes', 'nullable', 'date'],
            'extra_item_label' => ['sometimes', 'array'],
            'extra_item_label.*' => ['sometimes', 'nullable', 'string', 'max:255'],
            'extra_item_data_text' => ['sometimes', 'array'],
            'extra_item_data_text.*' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'extra_item_description' => ['sometimes', 'array'],
            'extra_item_description.*' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'extra_item_visibility' => ['sometimes', 'array'],
            'extra_item_visibility.*' => ['sometimes', 'nullable', 'string', 'in:public,private'],
            'extra_item_file' => ['sometimes', 'array'],
            'extra_item_file.*' => ['sometimes', 'nullable', 'file', 'max:20480'],
        ]);

        $jobFile = $request->file('job_file');
        $extraItemFiles = $request->file('extra_item_file');
        $extraItemFiles = is_array($extraItemFiles) ? $extraItemFiles : [];

        $job = (new UpsertRepairBuddyJob())->create($tenant, $branch, $user, $validated, $jobFile, $extraItemFiles);

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
            ->with(['customer', 'technicians', 'jobDevices', 'signatureRequests'])
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
            ->with(['customerDevice.device.brand', 'customerDevice.device.type'])
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

        /* ── Tax settings from tenant ── */
        $store = new TenantSettingsStore($tenant);
        $taxSettings = $store->get('taxes', []);
        $taxEnabled = (bool) ($taxSettings['enableTaxes'] ?? false);
        $pricesMode = is_string($job->prices_inclu_exclu) && $job->prices_inclu_exclu !== ''
            ? $job->prices_inclu_exclu
            : (is_string($taxSettings['invoiceAmounts'] ?? null) ? (string) $taxSettings['invoiceAmounts'] : 'exclusive');

        /* ── Categorize items and compute per-category totals ── */
        $categories = ['service' => [], 'part' => [], 'fee' => [], 'discount' => []];
        foreach ($items as $item) {
            $type = is_string($item->item_type) ? strtolower((string) $item->item_type) : 'fee';
            if (! array_key_exists($type, $categories)) {
                $categories['fee'][] = $item; // unknown types go to fees/extras
            } else {
                $categories[$type][] = $item;
            }
        }

        $computeCategoryTotals = function (array $categoryItems, string $mode, bool $taxOn) {
            $sub = 0;
            $tax = 0;
            foreach ($categoryItems as $item) {
                $qty  = is_numeric($item->qty) ? max(1, (int) $item->qty) : 1;
                $unit = is_numeric($item->unit_price_amount_cents) ? (int) $item->unit_price_amount_cents : 0;
                $line = $qty * $unit;
                $sub += $line;

                if ($taxOn && $item->relationLoaded('tax') && $item->tax) {
                    $rate = (float) ($item->tax->rate ?? 0);
                    if ($rate > 0) {
                        if ($mode === 'inclusive') {
                            // Tax is embedded in the price: tax = line - line / (1 + rate/100)
                            $tax += (int) round($line - ($line / (1 + ($rate / 100))));
                        } else {
                            // Tax is on top: tax = line * rate / 100
                            $tax += (int) round($line * ($rate / 100.0));
                        }
                    }
                }
            }
            return ['subtotal' => $sub, 'tax' => $tax, 'total' => $sub + $tax];
        };

        $serviceTotals  = $computeCategoryTotals($categories['service'], $pricesMode, $taxEnabled);
        $partTotals     = $computeCategoryTotals($categories['part'], $pricesMode, $taxEnabled);
        $feeTotals      = $computeCategoryTotals($categories['fee'], $pricesMode, $taxEnabled);
        $discountTotals = $computeCategoryTotals($categories['discount'], $pricesMode, false); // discounts never taxed

        // For inclusive: subtotal = sum of all line items (tax already inside), grand total = subtotal
        // For exclusive: subtotal = sum of all line items, grand total = subtotal + total tax
        $itemsSubtotalCents = $serviceTotals['subtotal'] + $partTotals['subtotal'] + $feeTotals['subtotal'];
        $discountCents      = $discountTotals['subtotal'];
        $taxCents           = $serviceTotals['tax'] + $partTotals['tax'] + $feeTotals['tax'];

        if ($pricesMode === 'inclusive') {
            // Inclusive: prices already contain tax, grand total = items - discounts
            $grandTotalCents = $itemsSubtotalCents - $discountCents;
        } else {
            // Exclusive: tax added on top
            $grandTotalCents = $itemsSubtotalCents + $taxCents - $discountCents;
        }

        // Tax info for display
        $taxName = null;
        $taxRate = null;
        if ($taxEnabled) {
            $firstTaxedItem = $items->first(fn ($i) => $i->tax !== null);
            if ($firstTaxedItem && $firstTaxedItem->tax) {
                $taxName = $firstTaxedItem->tax->name;
                $taxRate = (float) $firstTaxedItem->tax->rate;
            }
        }

        $payments = RepairBuddyPayment::query()
            ->with(['receiver'])
            ->where('job_id', $job->id)
            ->orderBy('paid_at', 'desc')
            ->get();

        $paidCents = $payments->sum('amount_cents');

        $totals = [
            'currency'             => $currency,
            'subtotal_cents'       => $itemsSubtotalCents - $discountCents,
            'items_subtotal_cents' => $itemsSubtotalCents,
            'discount_cents'       => $discountCents,
            'tax_cents'            => $taxCents,
            'tax_total_cents'      => $taxCents,
            'tax_name'             => $taxName,
            'tax_rate'             => $taxRate,
            'tax_mode'             => $pricesMode,
            'total_cents'          => $grandTotalCents,
            'grand_total_cents'    => $grandTotalCents,
            'paid_cents'           => $paidCents,
            'paid_total_cents'     => $paidCents,
            'balance_cents'        => $grandTotalCents - $paidCents,
            // Per-category breakdowns
            'services'  => $serviceTotals,
            'parts'     => $partTotals,
            'fees'      => $feeTotals,
            'discounts' => $discountTotals,
        ];

        $paymentStatuses = RepairBuddyPaymentStatus::query()
            ->where('is_active', true)
            ->orderBy('label')
            ->get();

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
            'jobTimelogs' => RepairBuddyTimeLog::query()
                ->with(['technician:id,name,email'])
                ->where('job_id', $job->id)
                ->orderByDesc('start_time')
                ->limit(200)
                ->get(),
            'jobPayments' => $payments,
            'jobExpenses' => collect(),
            'jobFeedback' => collect(),
            'paymentStatuses' => $paymentStatuses,
        ]);
    }

    /* ---------------------------------------------------------------- */
    /*  Store a manual payment against a job                            */
    /* ---------------------------------------------------------------- */
    public function storePayment(Request $request, string $business, $jobId)
    {
        if (! is_numeric($jobId)) {
            abort(404);
        }

        $tenant = TenantContext::tenant();
        $user   = $request->user();

        if (! $user) {
            return redirect()->route('web.login');
        }

        $job = RepairBuddyJob::query()->whereKey((int) $jobId)->firstOrFail();

        $validated = $request->validate([
            'method'         => 'required|string|max:60',
            'payment_status' => 'required|string|max:60',
            'amount'         => 'required|numeric|min:0.01',
            'transaction_id' => 'nullable|string|max:200',
            'notes'          => 'nullable|string|max:2000',
            'paid_at'        => 'nullable|date',
        ]);

        $amountCents = (int) round($validated['amount'] * 100);

        $currency = is_string($tenant?->currency) && $tenant->currency !== ''
            ? strtoupper((string) $tenant->currency) : 'USD';

        RepairBuddyPayment::create([
            'job_id'         => $job->id,
            'received_by'    => $user->id,
            'method'         => $validated['method'],
            'payment_status' => $validated['payment_status'],
            'transaction_id' => $validated['transaction_id'] ?? null,
            'amount_cents'   => $amountCents,
            'currency'       => $currency,
            'notes'          => $validated['notes'] ?? null,
            'paid_at'        => $validated['paid_at'] ?? now(),
        ]);

        // Log the event
        RepairBuddyEvent::create([
            'tenant_id'    => $tenant->id,
            'entity_type'  => 'job',
            'entity_id'    => $job->id,
            'event_type'   => 'payment_added',
            'actor_id'     => $user->id,
            'payload_json' => [
                'title'   => 'Payment recorded',
                'message' => "Payment of {$currency} " . number_format($validated['amount'], 2) . " via {$validated['method']}.",
            ],
        ]);

        $tenantSlug = is_string($tenant?->slug) ? (string) $tenant->slug : '';

        return redirect()
            ->route('tenant.jobs.show', ['business' => $tenantSlug, 'jobId' => $job->id])
            ->with('success', __('Payment recorded successfully.'));
    }
}
