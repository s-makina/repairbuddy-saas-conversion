<?php

namespace App\Livewire\Tenant\Jobs;

use App\Actions\RepairBuddy\UpsertRepairBuddyJob;
use App\Models\Branch;
use App\Models\RepairBuddyJob;
use App\Support\BranchContext;
use App\Models\RepairBuddyCustomerDevice;
use App\Models\RepairBuddyCustomerDeviceFieldValue;
use App\Models\RepairBuddyDeviceBrand;
use App\Models\RepairBuddyDeviceFieldDefinition;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use App\Services\TenantSettings\TenantSettingsStore;

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
    public bool $tax_enabled = false;
    public ?string $default_tax_mode = null;
    public ?int $default_tax_id = null;
    public string $currency_code = 'USD';
    public string $currency_symbol = '$';

    /** 'job' or 'estimate' — drives conditional UI in the shared blade */
    public string $formMode = 'job';

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

    public string $customer_search = '';
    public string $technician_search = '';

    public $job_file = null;

    /** @var array<int,int|string> */
    public array $technician_ids = [];

    // Step 3 Parts properties
    public $part_search = '';
    public $selected_part_id = null;
    public $selected_part_name = '';

    public $service_search = '';
    public $selected_service_id = null;
    public $selected_service_name = '';

    public $selected_device_link_index = null;

    public $device_search = '';
    public $selected_device_id = null;
    public $selected_device_name = '';
    public $selected_device_image = null;
    public $device_serial = '';
    public $device_pin = '';
    public $device_note = '';
    public array $additional_fields = [];

    public $fieldDefinitions = [];

    /** @var array<int,array{customer_device_id:mixed,serial:mixed,pin:mixed,notes:mixed,brand_id:mixed,device_id:mixed,brand_name:string,device_model:string,additional_fields:array}> */
    public array $deviceRows = [];

    public $editingDeviceIndex = null;

    /** @var array<int,array{occurred_at:mixed,label:mixed,data_text:mixed,description:mixed,visibility:mixed}> */
    public array $extras = [];

    /** @var array<int,\Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null> */
    public array $extra_item_files = [];

    protected $listeners = [
        'customerCreated' => 'handleCustomerCreated',
        'technicianCreated' => 'handleTechnicianCreated',
        'partCreated' => 'handlePartCreated',
        'serviceCreated' => 'handleServiceCreated',
    ];

    // Job Extra Modal & Form State
    public bool $showExtraModal = false;
    public ?int $editingExtraIndex = null;
    public ?string $extra_occurred_at = null;
    public ?string $extra_label = null;
    public ?string $extra_data_text = null;
    public string $extra_visibility = 'public';
    public ?string $extra_description = null;
    public $extra_temp_file = null;

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

        $this->fieldDefinitions = RepairBuddyDeviceFieldDefinition::query()
            ->where('tenant_id', (int) $this->tenant->id)
            ->where('is_active', true)
            ->where('show_in_booking', true)
            ->get();

        foreach ($this->fieldDefinitions as $def) {
            $this->additional_fields[$def->key] = '';
        }

        $settings = data_get($this->tenant?->setup_state ?? [], 'repairbuddy_settings');
        $devicesBrandsSettings = is_array($settings) ? (array) data_get($settings, 'devicesBrands', []) : [];
        $this->enablePinCodeField = (bool) ($devicesBrandsSettings['enablePinCodeField'] ?? false);

        $store = new TenantSettingsStore($this->tenant);
        $taxSettings = $store->get('taxes', []);
        $this->tax_enabled = (bool) ($taxSettings['enableTaxes'] ?? false);
        $this->default_tax_mode = is_string($taxSettings['invoiceAmounts'] ?? null)
            ? (string) $taxSettings['invoiceAmounts']
            : null;
        $rawDefaultTaxId = $taxSettings['defaultTaxId'] ?? null;
        $this->default_tax_id = is_numeric($rawDefaultTaxId) ? (int) $rawDefaultTaxId : null;

        $this->currency_code = $this->tenant->currency ?? 'USD';
        try {
            $fmt = new \NumberFormatter('en_US', \NumberFormatter::CURRENCY);
            $this->currency_symbol = $fmt->getSymbol(\NumberFormatter::CURRENCY_SYMBOL) ?: '$';
            // Actually, NumberFormatter needs the currency set to get the symbol for THAT currency
            $fmt->setTextAttribute(\NumberFormatter::CURRENCY_CODE, $this->currency_code);
            $this->currency_symbol = $fmt->getSymbol(\NumberFormatter::CURRENCY_SYMBOL) ?: ($this->currency_code . ' ');
        } catch (\Exception $e) {
            $this->currency_symbol = ($this->currency_code . ' ');
        }

        $jobModel = $job instanceof RepairBuddyJob ? $job : null;

        $this->case_number = $jobModel?->case_number;
        $this->title = $jobModel?->title;
        $this->status_slug = is_string($jobModel?->status_slug) ? (string) $jobModel?->status_slug : null;
        $this->payment_status_slug = is_string($jobModel?->payment_status_slug) ? (string) $jobModel?->payment_status_slug : null;
        $this->prices_inclu_exclu = is_string($jobModel?->prices_inclu_exclu)
            ? (string) $jobModel->prices_inclu_exclu
            : $this->default_tax_mode;
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
                $brandName = '';
                $deviceModel = '';
                if ($jd->customerDevice && $jd->customerDevice->device) {
                    $brandName = $jd->customerDevice->device->brand?->name ?? '';
                    $deviceModel = $jd->customerDevice->device->model ?? '';
                }

                $this->deviceRows[] = [
                    'customer_device_id' => $jd->customer_device_id ?? null,
                    'serial' => $jd->serial_snapshot ?? null,
                    'pin' => $jd->pin_snapshot ?? null,
                    'notes' => $jd->notes_snapshot ?? null,
                    'brand_name' => $brandName,
                    'device_model' => $deviceModel,
                    'additional_fields' => $jd->extra_fields_snapshot_json ?? [],
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

    public function hydrate(): void
    {
        if ($this->tenant instanceof \App\Models\Tenant) {
            \App\Support\TenantContext::set($this->tenant);
            
            // Re-set branch context from session or default to first branch
            $branchId = session('active_branch_id');
            $branch = $branchId ? \App\Models\Branch::find($branchId) : $this->tenant->branches->first();
            if ($branch) {
                \App\Support\BranchContext::set($branch);
            }

            $this->currency_code = $this->tenant->currency ?? 'USD';
            try {
                $fmt = new \NumberFormatter('en_US', \NumberFormatter::CURRENCY);
                $fmt->setTextAttribute(\NumberFormatter::CURRENCY_CODE, $this->currency_code);
                $this->currency_symbol = $fmt->getSymbol(\NumberFormatter::CURRENCY_SYMBOL) ?: ($this->currency_code . ' ');
            } catch (\Exception $e) {
                $this->currency_symbol = ($this->currency_code . ' ');
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

    public function getFilteredDevicesProperty()
    {
        $search = $this->device_search;
        if (strlen($search) < 2) {
            return collect();
        }

        return \App\Models\RepairBuddyDevice::query()
            ->with(['brand'])
            ->where('is_active', true)
            ->where(function ($q) use ($search) {
                $q->where('model', 'like', "%{$search}%")
                  ->orWhereHas('brand', function ($bq) use ($search) {
                      $bq->where('name', 'like', "%{$search}%");
                  });
            })
            ->limit(30)
            ->get()
            ->groupBy(function ($d) {
                return $d->brand?->name ?? 'Other';
            });
    }

    public function selectDevice($id, $name): void
    {
        $this->selected_device_id = $id;
        $this->selected_device_name = $name;
        
        $device = \App\Models\RepairBuddyDevice::find($id);
        $this->selected_device_image = $device?->image_url;
        
        $this->device_search = ''; // Clear search after selection
    }

    public function getFilteredPartsProperty()
    {
        $search = trim($this->part_search);
        if (strlen($search) < 2) {
            return collect();
        }

        // We use withoutGlobalScopes to avoid issues with BranchScope 
        // if the context isn't fully set during the Livewire lifecycle
        return \App\Models\RepairBuddyPart::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $this->tenant->id)
            ->where('is_active', true)
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('manufacturing_code', 'like', "%{$search}%")
                  ->orWhere('stock_code', 'like', "%{$search}%");
            })
            ->limit(20)
            ->get();
    }

    public function selectPart($id, $name): void
    {
        $this->selected_part_id = $id;
        $this->selected_part_name = $name;
        $this->part_search = ''; // This will trigger the wire:model.live update and hide dropdown
    }

    public function getFilteredServicesProperty()
    {
        $search = trim($this->service_search);
        if (strlen($search) < 2) {
            return collect();
        }

        return \App\Models\RepairBuddyService::query()
            ->withoutGlobalScopes([\App\Models\Scopes\BranchScope::class])
            ->where('tenant_id', $this->tenant->id)
            ->where('is_active', true)
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('service_code', 'like', "%{$search}%");
            })
            ->limit(20)
            ->get();
    }

    public function selectService($id, $name): void
    {
        $this->selected_service_id = $id;
        $this->selected_service_name = $name;
        $this->service_search = '';
    }

    public function addDeviceToTable(): void
    {
        $this->validate([
            'selected_device_id' => ['required', 'integer'],
            'device_serial' => ['nullable', 'string', 'max:255'],
            'device_pin' => ['nullable', 'string', 'max:255'],
            'device_note' => ['nullable', 'string', 'max:5000'],
        ]);

        $device = \App\Models\RepairBuddyDevice::with('brand')->find($this->selected_device_id);

        $rowData = [
            'customer_device_id' => null,
            'brand_id' => $device?->device_brand_id,
            'device_id' => $device?->id,
            'brand_name' => $device?->brand?->name ?? '',
            'device_model' => $device?->model ?? '',
            'image_url' => $device?->image_url,
            'serial' => $this->device_serial,
            'pin' => $this->device_pin,
            'notes' => $this->device_note,
            'additional_fields' => $this->additional_fields,
        ];

        if ($this->editingDeviceIndex !== null && isset($this->deviceRows[$this->editingDeviceIndex])) {
            $this->deviceRows[$this->editingDeviceIndex] = $rowData;
        } else {
            $this->deviceRows[] = $rowData;
        }

        // Reset form
        $this->cancelEditDevice();
    }

    public function editDevice(int $idx): void
    {
        if (! array_key_exists($idx, $this->deviceRows)) {
            return;
        }

        $row = $this->deviceRows[$idx];
        $this->editingDeviceIndex = $idx;
        
        $this->selected_device_id = $row['device_id'];
        $this->selected_device_name = ($row['brand_name'] ?? '') . ' ' . ($row['device_model'] ?? '');
        $this->selected_device_image = $row['image_url'] ?? null;
        
        $this->device_serial = $row['serial'] ?? '';
        $this->device_pin = $row['pin'] ?? '';
        $this->device_note = $row['notes'] ?? '';
        $this->additional_fields = $row['additional_fields'] ?? [];
        
        $this->device_search = '';
    }

    public function cancelEditDevice(): void
    {
        $this->editingDeviceIndex = null;
        $this->selected_device_id = null;
        $this->selected_device_name = '';
        $this->selected_device_image = null;
        $this->device_search = '';
        $this->device_serial = '';
        $this->device_pin = '';
        $this->device_note = '';
        
        foreach ($this->additional_fields as $k => $v) {
            $this->additional_fields[$k] = '';
        }
    }

    public function addDevice(): void
    {
        // Legacy add device - we probably don't need this anymore but let's keep it for compatibility if needed
        $this->deviceRows[] = [
            'customer_device_id' => null,
            'serial' => null,
            'pin' => null,
            'notes' => null,
            'brand_name' => '',
            'device_model' => '',
            'additional_fields' => [],
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

    public function openExtraModal(?int $idx = null): void
    {
        $this->editingExtraIndex = $idx;
        if ($idx !== null && isset($this->extras[$idx])) {
            $extra = $this->extras[$idx];
            $this->extra_occurred_at = $extra['occurred_at'] ?? null;
            $this->extra_label = $extra['label'] ?? null;
            $this->extra_data_text = $extra['data_text'] ?? null;
            $this->extra_description = $extra['description'] ?? null;
            $this->extra_visibility = $extra['visibility'] ?? 'public';
        } else {
            $this->resetExtraForm();
            $this->extra_occurred_at = now()->format('Y-m-d');
        }
        $this->showExtraModal = true;
    }

    public function closeExtraModal(): void
    {
        $this->showExtraModal = false;
        $this->resetExtraForm();
    }

    protected function resetExtraForm(): void
    {
        $this->editingExtraIndex = null;
        $this->extra_occurred_at = null;
        $this->extra_label = null;
        $this->extra_data_text = null;
        $this->extra_description = null;
        $this->extra_visibility = 'public';
        $this->extra_temp_file = null;
    }

    public function saveExtra(): void
    {
        $this->validate([
            'extra_label' => ['required', 'string', 'max:255'],
            'extra_occurred_at' => ['nullable', 'date'],
            'extra_visibility' => ['required', 'in:public,private'],
            'extra_temp_file' => ['nullable', 'file', 'max:10240'], // 10MB
        ]);

        $extraData = [
            'occurred_at' => $this->extra_occurred_at,
            'label' => $this->extra_label,
            'data_text' => $this->extra_data_text,
            'description' => $this->extra_description,
            'visibility' => $this->extra_visibility,
        ];

        if ($this->editingExtraIndex !== null && isset($this->extras[$this->editingExtraIndex])) {
            $this->extras[$this->editingExtraIndex] = $extraData;
            if ($this->extra_temp_file) {
                $this->extra_item_files[$this->editingExtraIndex] = $this->extra_temp_file;
            }
        } else {
            $this->extras[] = $extraData;
            $this->extra_item_files[] = $this->extra_temp_file;
        }

        $this->closeExtraModal();
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
        $this->validate([
            'selected_part_id' => ['required', 'integer'],
            'selected_device_link_index' => ['required', 'integer'],
        ]);

        $part = \App\Models\RepairBuddyPart::find($this->selected_part_id);
        if (!$part) return;

        $deviceRow = $this->deviceRows[$this->selected_device_link_index] ?? null;
        
        $this->items[] = [
            'type' => 'part',
            'part_id' => $part->id,
            'name' => $part->name,
            'code' => $part->manufacturing_code ?? $part->stock_code,
            'capacity' => $part->capacity,
            'device_info' => $deviceRow ? ($deviceRow['brand_name'] . ' ' . $deviceRow['device_model']) : '--',
            'device_row_index' => $this->selected_device_link_index,
            'qty' => 1,
            'unit_price_cents' => $part->price_amount_cents / 100,
            'meta_json' => null,
        ];

        // Reset
        $this->selected_part_id = null;
        $this->selected_part_name = '';
        $this->part_search = '';
        $this->selected_device_link_index = null;
    }

    public function addCustomPart(): void
    {
        $this->validate([
            'selected_device_link_index' => ['required', 'integer'],
        ]);

        $deviceRow = $this->deviceRows[$this->selected_device_link_index] ?? null;

        $this->items[] = [
            'type' => 'part',
            'part_id' => null,
            'name' => $this->part_search ?: __('Custom Part'),
            'code' => null,
            'capacity' => null,
            'device_info' => $deviceRow ? ($deviceRow['brand_name'] . ' ' . $deviceRow['device_model']) : '--',
            'device_row_index' => $this->selected_device_link_index,
            'qty' => 1,
            'unit_price_cents' => 0,
            'meta_json' => null,
        ];

        // Reset
        $this->part_search = '';
        $this->selected_device_link_index = null;
    }

    public function addService(): void
    {
        $deviceRow = $this->selected_device_link_index !== null ? ($this->deviceRows[$this->selected_device_link_index] ?? null) : null;

        if ($this->selected_service_id) {
            $service = \App\Models\RepairBuddyService::withoutGlobalScopes([\App\Models\Scopes\BranchScope::class])
                ->find($this->selected_service_id);
            
            if ($service) {
                $this->items[] = [
                    'type' => 'service',
                    'name' => $service->name,
                    'code' => $service->service_code,
                    'qty' => 1,
                    'unit_price_cents' => $service->base_price_amount_cents / 100,
                    'device_info' => $deviceRow ? ($deviceRow['brand_name'] . ' ' . $deviceRow['device_model']) : '--',
                    'device_row_index' => $this->selected_device_link_index,
                    'meta_json' => null,
                ];
            }
        } else {
            $this->items[] = [
                'type' => 'service',
                'name' => $this->service_search ?: null,
                'code' => null,
                'qty' => 1,
                'unit_price_cents' => 0,
                'device_info' => $deviceRow ? ($deviceRow['brand_name'] . ' ' . $deviceRow['device_model']) : '--',
                'device_row_index' => $this->selected_device_link_index,
                'meta_json' => null,
            ];
        }

        // Reset
        $this->selected_service_id = null;
        $this->selected_service_name = '';
        $this->service_search = '';
        $this->selected_device_link_index = null;
    }

    public function addOtherItem(): void
    {
        $deviceRow = $this->selected_device_link_index !== null ? ($this->deviceRows[$this->selected_device_link_index] ?? null) : null;

        $this->items[] = [
            'type' => 'fee',
            'name' => null,
            'code' => null,
            'qty' => 1,
            'unit_price_cents' => 0,
            'device_info' => $deviceRow ? ($deviceRow['brand_name'] . ' ' . $deviceRow['device_model']) : '--',
            'device_row_index' => $this->selected_device_link_index,
            'meta_json' => null,
        ];

        // Reset
        $this->selected_device_link_index = null;
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

        // Process newly added devices (create CustomerDevice records)
        foreach ($this->deviceRows as $idx => $row) {
            if (! empty($row['customer_device_id'])) {
                continue;
            }

            if (! $this->customer_id) {
                // Should be caught by validation, but safety first
                continue;
            }

            $branch = BranchContext::branch();

            $cd = RepairBuddyCustomerDevice::create([
                'tenant_id' => (int) $this->tenant->id,
                'branch_id' => $branch ? (int) $branch->id : null,
                'customer_id' => (int) $this->customer_id,
                'device_id' => (int) $row['device_id'],
                'label' => $row['brand_name'] . ' ' . $row['device_model'],
                'serial' => $row['serial'],
                'pin' => $row['pin'],
                'notes' => $row['notes'],
            ]);

            $this->deviceRows[$idx]['customer_device_id'] = $cd->id;

            // Save additional fields
            if (! empty($row['additional_fields'])) {
                foreach ($this->fieldDefinitions as $def) {
                    $val = $row['additional_fields'][$def->key] ?? '';
                    if ($val !== '') {
                        RepairBuddyCustomerDeviceFieldValue::create([
                            'tenant_id' => (int) $this->tenant->id,
                            'branch_id' => $branch ? (int) $branch->id : null,
                            'customer_device_id' => $cd->id,
                            'field_definition_id' => $def->id,
                            'value_text' => $val,
                        ]);
                    }
                }
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
            'item_unit_price_cents' => array_map(fn ($r) => (int) round(($r['unit_price_cents'] ?? 0) * 100), $this->items),
            'item_meta_json' => array_map(fn ($r) => $r['meta_json'] ?? null, $this->items),

            'extra_item_occurred_at' => array_map(fn ($r) => $r['occurred_at'] ?? null, $this->extras),
            'extra_item_label' => array_map(fn ($r) => $r['label'] ?? null, $this->extras),
            'extra_item_data_text' => array_map(fn ($r) => $r['data_text'] ?? null, $this->extras),
            'extra_item_description' => array_map(fn ($r) => $r['description'] ?? null, $this->extras),
            'extra_item_visibility' => array_map(fn ($r) => $r['visibility'] ?? null, $this->extras),
        ];

        $action = new UpsertRepairBuddyJob();

        if (is_numeric($this->jobId) && (int) $this->jobId > 0 && $this->job instanceof RepairBuddyJob) {
            $jobFile = $this->job_file instanceof \Illuminate\Http\UploadedFile ? $this->job_file : null;
            $extraFiles = is_array($this->extra_item_files) ? $this->extra_item_files : [];
            $job = $action->update($this->tenant, $this->user, $this->job, $validated, $jobFile, $extraFiles);
        } else {
            $branch = BranchContext::branch();
            if (! $branch instanceof Branch) {
                abort(400, 'Tenant or branch context is missing.');
            }
            $jobFile = $this->job_file instanceof \Illuminate\Http\UploadedFile ? $this->job_file : null;
            $extraFiles = is_array($this->extra_item_files) ? $this->extra_item_files : [];
            $job = $action->create($this->tenant, $branch, $this->user, $validated, $jobFile, $extraFiles);
        }

        return redirect()->route('tenant.jobs.show', ['business' => $this->tenant->slug, 'jobId' => $job->id]);
    }

    public function getFilteredCustomersProperty()
    {
        $search = $this->customer_search;
        $query = \App\Models\User::query()
            ->where('tenant_id', (int) $this->tenant->id)
            ->where('role', 'customer');

        if (strlen($search) >= 2) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('name')
            ->limit(strlen($search) >= 2 ? 10 : 5)
            ->get();
    }

    public function getFilteredTechniciansProperty()
    {
        $search = $this->technician_search;
        $query = \App\Models\User::query()
            ->where('tenant_id', (int) $this->tenant->id)
            ->where('is_admin', false)
            ->where('status', 'active')
            ->where(function ($q) {
                // The legacy `role` column is NULL for staff/technicians created via
                // UserController (role = null), but 'customer' for customer accounts.
                // SQL's `!=` operator excludes NULLs, so we must explicit-or IS NULL.
                $q->whereNull('role')
                  ->orWhere('role', '!=', 'customer');
            });

        if (!empty($this->technician_ids)) {
            $query->whereNotIn('id', $this->technician_ids);
        }

        if (strlen($search) >= 2) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('name')
            ->limit(strlen($search) >= 2 ? 10 : 5)
            ->get();
    }

    public function selectCustomer($id)
    {
        $this->customer_id = $id;
        $this->customer_search = '';
    }

    public function selectTechnician($id)
    {
        if (!in_array($id, $this->technician_ids)) {
            $this->technician_ids[] = (int) $id;
        }
        $this->technician_search = '';
    }

    public function removeTechnician($id)
    {
        $this->technician_ids = array_values(array_filter($this->technician_ids, fn($tid) => $tid != $id));
    }

    public function getSelectedCustomerProperty()
    {
        if (!$this->customer_id) {
            return null;
        }

        $customer = collect($this->customers)->firstWhere('id', $this->customer_id);
        if ($customer) {
            return $customer;
        }

        return \App\Models\User::where('tenant_id', $this->tenant->id)->find($this->customer_id);
    }

    public function getSelectedTechniciansProperty()
    {
        if (empty($this->technician_ids)) {
            return collect();
        }

        $cached = collect($this->technicians)->whereIn('id', $this->technician_ids);
        if ($cached->count() === count($this->technician_ids)) {
            return $cached;
        }

        return \App\Models\User::where('tenant_id', $this->tenant->id)
            ->whereIn('id', $this->technician_ids)
            ->get();
    }

    public function getDefaultTaxRateProperty(): float
    {
        if (!$this->default_tax_id) {
            return 0;
        }

        return (float) (\App\Models\RepairBuddyTax::query()
            ->where('tenant_id', (int) $this->tenant->id)
            ->where('is_active', true)
            ->whereKey($this->default_tax_id)
            ->value('rate') ?? 0);
    }

    public function getDefaultTaxInfoProperty(): ?array
    {
        if (!$this->default_tax_id) {
            return null;
        }

        $tax = \App\Models\RepairBuddyTax::query()
            ->where('tenant_id', (int) $this->tenant->id)
            ->where('is_active', true)
            ->whereKey($this->default_tax_id)
            ->first(['name', 'rate']);

        return $tax ? ['name' => $tax->name, 'rate' => (float) $tax->rate] : null;
    }

    public function getPartsTotalProperty(): float
    {
        return (float) collect($this->items)->where('type', 'part')->sum(fn($i) => ($i['qty'] ?? 1) * ($i['unit_price_cents'] ?? 0));
    }

    public function getServicesTotalProperty(): float
    {
        return (float) collect($this->items)->where('type', 'service')->sum(fn($i) => ($i['qty'] ?? 1) * ($i['unit_price_cents'] ?? 0));
    }

    public function getExtrasTotalProperty(): float
    {
        // 'fee' type in Step 3 corresponds to 'Extras' in the summary
        return (float) collect($this->items)->where('type', 'fee')->sum(fn($i) => ($i['qty'] ?? 1) * ($i['unit_price_cents'] ?? 0));
    }

    protected function calculateTax(float $subtotal, string $type): float
    {
        if (!$this->tax_enabled) {
            return 0;
        }

        $rate = $this->default_tax_rate;
        if ($rate <= 0) {
            return 0;
        }

        if ($this->prices_inclu_exclu === 'inclusive') {
            return $subtotal - ($subtotal / (1 + ($rate / 100)));
        }

        return $subtotal * ($rate / 100);
    }

    public function getPartsTaxProperty(): float
    {
        return $this->calculateTax($this->parts_total, 'part');
    }

    public function getServicesTaxProperty(): float
    {
        return $this->calculateTax($this->services_total, 'service');
    }

    public function getExtrasTaxProperty(): float
    {
        return $this->calculateTax($this->extras_total, 'fee');
    }

    public function getGrandTotalAmountProperty(): float
    {
        $subtotal = $this->parts_total + $this->services_total + $this->extras_total;
        
        if ($this->prices_inclu_exclu === 'inclusive' || !$this->tax_enabled) {
            return $subtotal;
        }

        return $subtotal + $this->parts_tax + $this->services_tax + $this->extras_tax;
    }

    public function getReceivedProperty(): float
    {
        // Placeholder as per request "Received (0)"
        return 0;
    }

    public function getBalanceProperty(): float
    {
        return $this->grand_total_amount - $this->received;
    }

    public function getJobExpensesProperty(): float
    {
        // Placeholder as per request "Job Expenses $0"
        return 0;
    }

    public function render()
    {
        return view('livewire.tenant.jobs.job-form');
    }

    public function handleCustomerCreated($customerId)
    {
        $this->customer_id = $customerId;
        
        // Refresh the customer dropdown/list if needed
        // Assuming the parent component or mount logic handles fetching the customer object
        $customer = \App\Models\User::find($customerId);
        if ($customer) {
            $this->customer_search = $customer->name;
            $this->dispatch('customer-selected', id: $customer->id, name: $customer->name);
        }

        $this->dispatch('toast', message: __('Customer created and selected.'), type: 'success');
    }

    public function handleTechnicianCreated($technicianId)
    {
        if (!in_array($technicianId, $this->technician_ids)) {
            $this->technician_ids[] = (int) $technicianId;
        }

        $this->dispatch('toast', message: __('Technician created and added.'), type: 'success');
    }

    public function handlePartCreated($partId)
    {
        $part = \App\Models\RepairBuddyPart::find($partId);
        if ($part) {
            $this->selectPart($part->id, $part->name);
        }

        $this->dispatch('toast', message: __('Part created and selected.'), type: 'success');
    }

    public function handleServiceCreated($serviceId)
    {
        $service = \App\Models\RepairBuddyService::find($serviceId);
        if ($service) {
            $this->selectService($service->id, $service->name);
        }

        $this->dispatch('toast', message: __('Service created and selected.'), type: 'success');
    }
}
