<?php

namespace App\Livewire\Tenant\Jobs;

use App\Actions\RepairBuddy\UpsertRepairBuddyJob;
use App\Models\Branch;
use App\Models\RepairBuddyJob;
use App\Support\BranchContext;
use Illuminate\Http\UploadedFile;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class JobForm extends Component
{
    use WithFileUploads;

    public $tenant;
    public $user;
    public $activeNav;
    public $pageTitle;
    public $job;
    public $jobId;
    public $suggestedCaseNumber;
    public $jobStatuses;
    public $paymentStatuses;
    public $customers;
    public $technicians;
    public $branches;
    public $customerDevices;
    public $devices;
    public $parts;
    public $services;
    public $jobItems;
    public $jobDevices;
    public $jobExtras;

    public bool $enablePinCodeField = false;

    public ?string $case_number = null;
    public ?string $title = null;
    public ?string $status_slug = null;
    public ?string $payment_status_slug = null;
    public ?string $prices_inclu_exclu = null;
    public ?string $priority = null;
    public bool $can_review_it = true;
    public $customer_id = null;
    public ?string $pickup_date = null;
    public ?string $delivery_date = null;
    public ?string $next_service_date = null;
    public ?string $case_detail = null;
    public ?string $wc_order_note = null;

    public $job_file = null;

    /** @var array<int,int|string> */
    public array $technician_ids = [];

    /** @var array<int,array{customer_device_id:mixed,serial:mixed,pin:mixed,notes:mixed}> */
    public array $deviceRows = [];

    /** @var array<int,array{occurred_at:mixed,label:mixed,data_text:mixed,description:mixed,visibility:mixed}> */
    public array $extras = [];

    /** @var array<int,\Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null> */
    public array $extra_item_files = [];

    /** @var array<int,array{type:mixed,name:mixed,qty:mixed,unit_price_cents:mixed,meta_json:mixed}> */
    public array $items = [];

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
    ): void {
        $this->tenant = $tenant;
        $this->user = $user;
        $this->activeNav = $activeNav;
        $this->pageTitle = $pageTitle;
        $this->job = $job;
        $this->jobId = $jobId;
        $this->suggestedCaseNumber = $suggestedCaseNumber;
        $this->jobStatuses = $jobStatuses;
        $this->paymentStatuses = $paymentStatuses;
        $this->customers = $customers;
        $this->technicians = $technicians;
        $this->branches = $branches;
        $this->customerDevices = $customerDevices;
        $this->devices = $devices;
        $this->parts = $parts;
        $this->services = $services;
        $this->jobItems = $jobItems;
        $this->jobDevices = $jobDevices;
        $this->jobExtras = $jobExtras;

        $settings = data_get($this->tenant?->setup_state ?? [], 'repairbuddy_settings');
        $devicesBrandsSettings = is_array($settings) ? (array) data_get($settings, 'devicesBrands', []) : [];
        $this->enablePinCodeField = (bool) ($devicesBrandsSettings['enablePinCodeField'] ?? false);

        $jobModel = $job instanceof RepairBuddyJob ? $job : null;

        $this->case_number = $jobModel?->case_number;
        $this->title = $jobModel?->title;
        $this->status_slug = is_string($jobModel?->status_slug) ? (string) $jobModel?->status_slug : null;
        $this->payment_status_slug = is_string($jobModel?->payment_status_slug) ? (string) $jobModel?->payment_status_slug : null;
        $this->prices_inclu_exclu = is_string($jobModel?->prices_inclu_exclu) ? (string) $jobModel?->prices_inclu_exclu : null;
        $this->priority = is_string($jobModel?->priority) ? (string) $jobModel?->priority : null;
        $this->can_review_it = $jobModel ? (bool) $jobModel->can_review_it : true;
        $this->customer_id = $jobModel?->customer_id;
        $this->pickup_date = $jobModel?->pickup_date ? (string) $jobModel?->pickup_date : null;
        $this->delivery_date = $jobModel?->delivery_date ? (string) $jobModel?->delivery_date : null;
        $this->next_service_date = $jobModel?->next_service_date ? (string) $jobModel?->next_service_date : null;
        $this->case_detail = $jobModel?->case_detail;

        $this->technician_ids = [];
        if ($jobModel && $jobModel->relationLoaded('technicians')) {
            $this->technician_ids = $jobModel->technicians->pluck('id')->map(fn ($v) => (int) $v)->values()->all();
        }

        $this->deviceRows = [];
        if (is_iterable($jobDevices)) {
            foreach ($jobDevices as $jd) {
                $this->deviceRows[] = [
                    'customer_device_id' => $jd->customer_device_id ?? null,
                    'serial' => $jd->serial_snapshot ?? null,
                    'pin' => $jd->pin_snapshot ?? null,
                    'notes' => $jd->notes_snapshot ?? null,
                ];
            }
        }

        $this->extras = [];
        $this->extra_item_files = [];

        if (is_iterable($jobExtras)) {
            foreach ($jobExtras as $ex) {
                $this->extras[] = [
                    'occurred_at' => $ex->occurred_at ?? null,
                    'label' => $ex->label ?? null,
                    'data_text' => $ex->data_text ?? null,
                    'description' => $ex->description ?? null,
                    'visibility' => $ex->visibility ?? 'private',
                ];
                $this->extra_item_files[] = null;
            }
        }

        $this->items = [];
        if (is_iterable($jobItems)) {
            foreach ($jobItems as $it) {
                $this->items[] = [
                    'type' => $it->item_type ?? null,
                    'name' => $it->name_snapshot ?? null,
                    'qty' => $it->qty ?? 1,
                    'unit_price_cents' => $it->unit_price_amount_cents ?? 0,
                    'meta_json' => is_array($it->meta_json ?? null) ? json_encode($it->meta_json) : null,
                ];
            }
        }
    }

    protected function rules(): array
    {
        return [
            'case_number' => ['nullable', 'string', 'max:64'],
            'title' => ['nullable', 'string', 'max:255'],
            'status_slug' => ['nullable', 'string', 'max:64'],
            'payment_status_slug' => ['nullable', 'string', 'max:64'],
            'prices_inclu_exclu' => ['nullable', 'string', 'in:inclusive,exclusive'],
            'priority' => ['nullable', 'string', 'max:32'],
            'can_review_it' => ['boolean'],
            'customer_id' => ['nullable', 'integer'],
            'pickup_date' => ['nullable', 'date'],
            'delivery_date' => ['nullable', 'date'],
            'next_service_date' => ['nullable', 'date'],
            'case_detail' => ['nullable', 'string', 'max:5000'],
            'wc_order_note' => ['nullable', 'string', 'max:5000'],

            'job_file' => ['nullable', 'file', 'max:20480'],

            'technician_ids' => ['array'],
            'technician_ids.*' => ['integer'],

            'deviceRows' => ['array'],
            'deviceRows.*.customer_device_id' => ['nullable', 'integer'],
            'deviceRows.*.serial' => ['nullable', 'string', 'max:255'],
            'deviceRows.*.pin' => ['nullable', 'string', 'max:255'],
            'deviceRows.*.notes' => ['nullable', 'string', 'max:5000'],

            'items' => ['array'],
            'items.*.type' => ['nullable', 'string', 'max:32'],
            'items.*.name' => ['nullable', 'string', 'max:255'],
            'items.*.code' => ['nullable', 'string', 'max:64'],
            'items.*.qty' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'items.*.unit_price_cents' => ['nullable', 'integer', 'min:-1000000000', 'max:1000000000'],
            'items.*.meta_json' => ['nullable', 'string', 'max:2000'],

            'extras' => ['array'],
            'extras.*.occurred_at' => ['nullable', 'date'],
            'extras.*.label' => ['nullable', 'string', 'max:255'],
            'extras.*.data_text' => ['nullable', 'string', 'max:5000'],
            'extras.*.description' => ['nullable', 'string', 'max:5000'],
            'extras.*.visibility' => ['nullable', 'string', 'in:public,private'],

            'extra_item_files' => ['array'],
            'extra_item_files.*' => ['nullable', 'file', 'max:20480'],
        ];
    }

    public function addDevice(): void
    {
        $this->deviceRows[] = [
            'customer_device_id' => null,
            'serial' => null,
            'pin' => null,
            'notes' => null,
        ];
    }

    public function removeDevice(int $idx): void
    {
        if (! array_key_exists($idx, $this->deviceRows)) {
            return;
        }
        unset($this->deviceRows[$idx]);
        $this->deviceRows = array_values($this->deviceRows);
    }

    public function addExtra(): void
    {
        $this->extras[] = [
            'occurred_at' => null,
            'label' => null,
            'data_text' => null,
            'description' => null,
            'visibility' => 'private',
        ];
        $this->extra_item_files[] = null;
    }

    public function removeExtra(int $idx): void
    {
        if (array_key_exists($idx, $this->extras)) {
            unset($this->extras[$idx]);
            $this->extras = array_values($this->extras);
        }
        if (array_key_exists($idx, $this->extra_item_files)) {
            unset($this->extra_item_files[$idx]);
            $this->extra_item_files = array_values($this->extra_item_files);
        }
    }

    public function addItem(): void
    {
        $this->items[] = [
            'type' => 'part',
            'name' => null,
            'code' => null,
            'qty' => 1,
            'unit_price_cents' => 0,
            'meta_json' => null,
        ];
    }

    public function addPart(): void
    {
        $this->items[] = [
            'type' => 'part',
            'name' => null,
            'code' => null,
            'qty' => 1,
            'unit_price_cents' => 0,
            'meta_json' => null,
        ];
    }

    public function addService(): void
    {
        $this->items[] = [
            'type' => 'service',
            'name' => null,
            'code' => null,
            'qty' => 1,
            'unit_price_cents' => 0,
            'meta_json' => null,
        ];
    }

    public function addOtherItem(): void
    {
        $this->items[] = [
            'type' => 'fee',
            'name' => null,
            'code' => null,
            'qty' => 1,
            'unit_price_cents' => 0,
            'meta_json' => null,
        ];
    }

    public function removeItem(int $idx): void
    {
        if (! array_key_exists($idx, $this->items)) {
            return;
        }
        unset($this->items[$idx]);
        $this->items = array_values($this->items);
    }

    public function save()
    {
        $this->validate();

        if (count($this->extra_item_files) < count($this->extras)) {
            $missing = count($this->extras) - count($this->extra_item_files);
            for ($i = 0; $i < $missing; $i++) {
                $this->extra_item_files[] = null;
            }
        }

        $validated = [
            'case_number' => $this->case_number,
            'title' => $this->title,
            'status_slug' => $this->status_slug,
            'payment_status_slug' => $this->payment_status_slug,
            'prices_inclu_exclu' => $this->prices_inclu_exclu,
            'priority' => $this->priority,
            'can_review_it' => $this->can_review_it,
            'customer_id' => is_numeric($this->customer_id) ? (int) $this->customer_id : null,
            'pickup_date' => $this->pickup_date,
            'delivery_date' => $this->delivery_date,
            'next_service_date' => $this->next_service_date,
            'case_detail' => $this->case_detail,
            'wc_order_note' => $this->wc_order_note,

            'technician_ids' => array_values(array_unique(array_map('intval', $this->technician_ids))),

            'job_device_customer_device_id' => array_map(fn ($r) => $r['customer_device_id'] ?? null, $this->deviceRows),
            'job_device_serial' => array_map(fn ($r) => $r['serial'] ?? null, $this->deviceRows),
            'job_device_pin' => array_map(fn ($r) => $r['pin'] ?? null, $this->deviceRows),
            'job_device_notes' => array_map(fn ($r) => $r['notes'] ?? null, $this->deviceRows),

            'item_type' => array_map(fn ($r) => $r['type'] ?? null, $this->items),
            'item_name' => array_map(fn ($r) => $r['name'] ?? null, $this->items),
            'item_code' => array_map(fn ($r) => $r['code'] ?? null, $this->items),
            'item_qty' => array_map(fn ($r) => $r['qty'] ?? null, $this->items),
            'item_unit_price_cents' => array_map(fn ($r) => $r['unit_price_cents'] ?? null, $this->items),
            'item_meta_json' => array_map(fn ($r) => $r['meta_json'] ?? null, $this->items),

            'extra_item_occurred_at' => array_map(fn ($r) => $r['occurred_at'] ?? null, $this->extras),
            'extra_item_label' => array_map(fn ($r) => $r['label'] ?? null, $this->extras),
            'extra_item_data_text' => array_map(fn ($r) => $r['data_text'] ?? null, $this->extras),
            'extra_item_description' => array_map(fn ($r) => $r['description'] ?? null, $this->extras),
            'extra_item_visibility' => array_map(fn ($r) => $r['visibility'] ?? null, $this->extras),
        ];

        $action = new UpsertRepairBuddyJob();

        if (is_numeric($this->jobId) && (int) $this->jobId > 0 && $this->job instanceof RepairBuddyJob) {
            $jobFile = $this->job_file instanceof UploadedFile ? $this->job_file : null;
            $extraFiles = is_array($this->extra_item_files) ? $this->extra_item_files : [];
            $job = $action->update($this->tenant, $this->user, $this->job, $validated, $jobFile, $extraFiles);
        } else {
            $branch = BranchContext::branch();
            if (! $branch instanceof Branch) {
                abort(400, 'Tenant or branch context is missing.');
            }
            $jobFile = $this->job_file instanceof UploadedFile ? $this->job_file : null;
            $extraFiles = is_array($this->extra_item_files) ? $this->extra_item_files : [];
            $job = $action->create($this->tenant, $branch, $this->user, $validated, $jobFile, $extraFiles);
        }

        return redirect()->route('tenant.jobs.show', ['business' => $this->tenant->slug, 'jobId' => $job->id]);
    }

    public function render()
    {
        return view('livewire.tenant.jobs.job-form');
    }
}
