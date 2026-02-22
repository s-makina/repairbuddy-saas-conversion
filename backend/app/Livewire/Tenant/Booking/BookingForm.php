<?php

namespace App\Livewire\Tenant\Booking;

use App\Models\RepairBuddyDevice;
use App\Models\RepairBuddyDeviceBrand;
use App\Models\RepairBuddyDeviceType;
use App\Models\RepairBuddyService;
use App\Models\RepairBuddyServiceAvailabilityOverride;
use App\Models\RepairBuddyServicePriceOverride;
use App\Models\RepairBuddyServiceType;
use App\Models\Tenant;
use App\Support\RepairBuddyPublicBookingService;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class BookingForm extends Component
{
    /* ───────── Tenant context ───────── */
    public ?int $tenantId = null;
    public string $business = '';
    public string $tenantName = '';

    /* ───────── Booking config ───────── */
    public bool $turnOffOtherDeviceBrand = false;
    public bool $turnOffOtherService = false;
    public bool $turnOffServicePrice = false;
    public bool $turnOffIdImeiBooking = false;
    public string $defaultTypeId = '';
    public string $defaultBrandId = '';
    public string $defaultDeviceId = '';

    /* ───────── Wizard state ───────── */
    public int $step = 1;

    /* ───────── Step 1: Device Type ───────── */
    public array $deviceTypes = [];
    public ?int $selectedTypeId = null;

    /* ───────── Step 2: Brand ───────── */
    public array $brands = [];
    public ?int $selectedBrandId = null;

    /* ───────── Step 3: Device ───────── */
    public array $devices = [];

    /* ───────── Step 4: Services ───────── */
    public array $services = [];

    /* ───────── Device Entries ───────── */
    public array $deviceEntries = [];

    /* ───────── Step 5: Customer Info ───────── */
    public string $firstName = '';
    public string $lastName = '';
    public string $email = '';
    public string $phone = '';
    public string $company = '';
    public string $taxId = '';
    public string $addressLine1 = '';
    public string $city = '';
    public string $postalCode = '';
    public string $jobDetails = '';

    /* ───────── Submission state ───────── */
    public bool $submitted = false;
    public string $submissionCaseNumber = '';
    public string $submissionMessage = '';
    public string $errorMessage = '';

    /* ─────────── mount ─────────── */

    public function mount(?Tenant $tenant = null, string $business = '')
    {
        $this->business = $business;

        if (! $tenant) {
            $tenant = TenantContext::tenant();
        }

        if ($tenant instanceof Tenant) {
            $this->tenantId = $tenant->id;
            $this->tenantName = (string) ($tenant->name ?? '');
        }

        $this->loadBookingConfig();
        $this->loadDeviceTypes();

        // Apply defaults
        if ($this->defaultTypeId !== '') {
            $this->selectedTypeId = (int) $this->defaultTypeId;
            $this->loadBrands();

            if ($this->defaultBrandId !== '') {
                $this->selectedBrandId = (int) $this->defaultBrandId;
                $this->loadDevices();
            }
        }
    }

    /* ─────────── config ─────────── */

    protected function loadBookingConfig(): void
    {
        $tenant = TenantContext::tenant();
        if (! $tenant) {
            return;
        }

        $settings = data_get($tenant->setup_state ?? [], 'repairbuddy_settings');
        $settings = is_array($settings) ? $settings : [];
        $booking = is_array($settings['booking'] ?? null) ? $settings['booking'] : [];

        $this->turnOffOtherDeviceBrand = (bool) ($booking['turnOffOtherDeviceBrand'] ?? false);
        $this->turnOffOtherService = (bool) ($booking['turnOffOtherService'] ?? false);
        $this->turnOffServicePrice = (bool) ($booking['turnOffServicePrice'] ?? false);
        $this->turnOffIdImeiBooking = (bool) ($booking['turnOffIdImeiInBooking'] ?? false);
        $this->defaultTypeId = (string) ($booking['defaultType'] ?? '');
        $this->defaultBrandId = (string) ($booking['defaultBrand'] ?? '');
        $this->defaultDeviceId = (string) ($booking['defaultDevice'] ?? '');
    }

    /* ─────────── data loaders ─────────── */

    protected function loadDeviceTypes(): void
    {
        $types = RepairBuddyDeviceType::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(500)
            ->get();

        $this->deviceTypes = $types->map(fn (RepairBuddyDeviceType $t) => [
            'id' => $t->id,
            'name' => $t->name,
            'image_url' => $t->image_url,
        ])->toArray();
    }

    protected function loadBrands(): void
    {
        $query = RepairBuddyDeviceBrand::query()
            ->where('is_active', true)
            ->orderBy('name');

        if ($this->selectedTypeId) {
            $brandIds = RepairBuddyDevice::query()
                ->where('is_active', true)
                ->where('disable_in_booking_form', false)
                ->where('device_type_id', $this->selectedTypeId)
                ->distinct()
                ->pluck('device_brand_id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            if (count($brandIds) === 0) {
                $this->brands = [];
                return;
            }

            $query->whereIn('id', $brandIds);
        }

        if ($this->turnOffOtherDeviceBrand) {
            $query->whereRaw('LOWER(name) <> ?', ['other']);
        }

        $brands = $query->limit(500)->get();

        $this->brands = $brands->map(fn (RepairBuddyDeviceBrand $b) => [
            'id' => $b->id,
            'name' => $b->name,
            'image_url' => $b->image_url,
        ])->toArray();
    }

    protected function loadDevices(): void
    {
        $query = RepairBuddyDevice::query()
            ->where('is_active', true)
            ->where('disable_in_booking_form', false)
            ->orderBy('model');

        if ($this->selectedTypeId) {
            $query->where('device_type_id', $this->selectedTypeId);
        }

        if ($this->selectedBrandId) {
            $query->where('device_brand_id', $this->selectedBrandId);
        }

        if ($this->turnOffOtherDeviceBrand) {
            $query->where('is_other', false);
        }

        $devices = $query->limit(1000)->get();

        $this->devices = $devices->map(fn (RepairBuddyDevice $d) => [
            'id' => $d->id,
            'model' => $d->model,
            'is_other' => (bool) $d->is_other,
        ])->toArray();
    }

    protected function loadServicesForDevice(int $deviceId): array
    {
        $device = RepairBuddyDevice::query()->whereKey($deviceId)->first();
        if (! $device) {
            return [];
        }

        $contextDeviceId = (int) $device->id;
        $contextBrandId = is_numeric($device->device_brand_id) ? (int) $device->device_brand_id : null;
        $contextTypeId = is_numeric($device->device_type_id) ? (int) $device->device_type_id : null;

        $services = RepairBuddyService::query()
            ->where('is_active', true)
            ->with(['type'])
            ->orderBy('name')
            ->limit(500)
            ->get();

        $serviceIds = $services->map(fn ($s) => (int) $s->id)->values()->all();

        // Check availability overrides
        $availabilityByService = [];
        if ($contextDeviceId || $contextBrandId || $contextTypeId) {
            $rows = RepairBuddyServiceAvailabilityOverride::query()
                ->whereIn('service_id', $serviceIds)
                ->where(function ($q) use ($contextDeviceId, $contextBrandId, $contextTypeId) {
                    if ($contextDeviceId) {
                        $q->orWhere(fn ($q2) => $q2->where('scope_type', 'device')->where('scope_ref_id', $contextDeviceId));
                    }
                    if ($contextBrandId) {
                        $q->orWhere(fn ($q2) => $q2->where('scope_type', 'brand')->where('scope_ref_id', $contextBrandId));
                    }
                    if ($contextTypeId) {
                        $q->orWhere(fn ($q2) => $q2->where('scope_type', 'type')->where('scope_ref_id', $contextTypeId));
                    }
                })
                ->orderByDesc('id')
                ->limit(5000)
                ->get();

            foreach ($rows as $row) {
                $sid = is_numeric($row->service_id) ? (int) $row->service_id : 0;
                $stype = is_string($row->scope_type) ? $row->scope_type : '';
                if ($sid <= 0 || $stype === '') continue;
                if (! array_key_exists($sid, $availabilityByService)) $availabilityByService[$sid] = [];
                if (! array_key_exists($stype, $availabilityByService[$sid])) $availabilityByService[$sid][$stype] = (string) $row->status;
            }
        }

        // Price overrides
        $priceOverrides = [];
        if (! $this->turnOffServicePrice && ($contextDeviceId || $contextBrandId || $contextTypeId)) {
            $priceRows = RepairBuddyServicePriceOverride::query()
                ->whereIn('service_id', $serviceIds)
                ->where('is_active', true)
                ->where(function ($q) use ($contextDeviceId, $contextBrandId, $contextTypeId) {
                    if ($contextDeviceId) {
                        $q->orWhere(fn ($q2) => $q2->where('scope_type', 'device')->where('scope_ref_id', $contextDeviceId));
                    }
                    if ($contextBrandId) {
                        $q->orWhere(fn ($q2) => $q2->where('scope_type', 'brand')->where('scope_ref_id', $contextBrandId));
                    }
                    if ($contextTypeId) {
                        $q->orWhere(fn ($q2) => $q2->where('scope_type', 'type')->where('scope_ref_id', $contextTypeId));
                    }
                })
                ->orderByDesc('id')
                ->limit(5000)
                ->get();

            foreach ($priceRows as $row) {
                $sid = is_numeric($row->service_id) ? (int) $row->service_id : 0;
                $stype = is_string($row->scope_type) ? $row->scope_type : '';
                if ($sid <= 0 || $stype === '') continue;
                if (! array_key_exists($sid, $priceOverrides)) $priceOverrides[$sid] = [];
                if (! array_key_exists($stype, $priceOverrides[$sid])) $priceOverrides[$sid][$stype] = $row;
            }
        }

        $out = [];
        foreach ($services as $s) {
            $sid = (int) $s->id;
            $available = true;

            if (array_key_exists($sid, $availabilityByService)) {
                $rules = $availabilityByService[$sid];
                $status = null;
                if ($contextDeviceId && array_key_exists('device', $rules)) $status = $rules['device'];
                elseif ($contextBrandId && array_key_exists('brand', $rules)) $status = $rules['brand'];
                elseif ($contextTypeId && array_key_exists('type', $rules)) $status = $rules['type'];
                if (is_string($status) && $status !== '') $available = $status === 'active';
            }

            if (! $available) continue;

            $priceDisplay = null;
            if (! $this->turnOffServicePrice) {
                $override = null;
                if ($contextDeviceId && isset($priceOverrides[$sid]['device'])) $override = $priceOverrides[$sid]['device'];
                elseif ($contextBrandId && isset($priceOverrides[$sid]['brand'])) $override = $priceOverrides[$sid]['brand'];
                elseif ($contextTypeId && isset($priceOverrides[$sid]['type'])) $override = $priceOverrides[$sid]['type'];

                $cents = $override && is_numeric($override->price_amount_cents) ? (int) $override->price_amount_cents : (is_numeric($s->base_price_amount_cents) ? (int) $s->base_price_amount_cents : null);
                $currency = $override && is_string($override->price_currency) && $override->price_currency !== '' ? $override->price_currency : (is_string($s->base_price_currency) && $s->base_price_currency !== '' ? $s->base_price_currency : null);

                if ($cents !== null && $currency !== null) {
                    $priceDisplay = $currency . ' ' . number_format($cents / 100, 2);
                }
            }

            $serviceType = $s->type;

            $out[] = [
                'id' => $s->id,
                'name' => $s->name,
                'description' => $s->description,
                'price_display' => $priceDisplay,
                'service_type_name' => $serviceType instanceof RepairBuddyServiceType ? $serviceType->name : null,
            ];
        }

        return $out;
    }

    /* ─────────── wizard actions ─────────── */

    public function selectType(int $id): void
    {
        $this->selectedTypeId = $id;
        $this->selectedBrandId = null;
        $this->brands = [];
        $this->devices = [];
        $this->services = [];
        $this->loadBrands();
        $this->step = 2;
    }

    public function selectBrand(int $id): void
    {
        $this->selectedBrandId = $id;
        $this->devices = [];
        $this->services = [];
        $this->loadDevices();
        $this->step = 3;
    }

    public function selectDeviceAndAddEntry(int $deviceId): void
    {
        $device = null;
        foreach ($this->devices as $d) {
            if ((int) $d['id'] === $deviceId) {
                $device = $d;
                break;
            }
        }

        if (! $device) {
            return;
        }

        $services = $this->loadServicesForDevice($deviceId);

        $this->deviceEntries[] = [
            'device_id' => $deviceId,
            'device_label' => $device['model'],
            'serial' => '',
            'pin' => '',
            'notes' => '',
            'selectedServiceId' => null,
            'otherService' => '',
            'services' => $services,
        ];

        $this->step = 4;
    }

    public function removeDeviceEntry(int $idx): void
    {
        if (array_key_exists($idx, $this->deviceEntries)) {
            unset($this->deviceEntries[$idx]);
            $this->deviceEntries = array_values($this->deviceEntries);
        }

        if (count($this->deviceEntries) === 0) {
            $this->step = 3;
        }
    }

    public function addAnotherDevice(): void
    {
        $this->step = 1;
    }

    public function goToCustomerStep(): void
    {
        $this->errorMessage = '';

        foreach ($this->deviceEntries as $idx => $entry) {
            if (empty($entry['selectedServiceId']) && trim($entry['otherService'] ?? '') === '') {
                $this->errorMessage = 'Please select a service for each device.';
                return;
            }
        }

        if (count($this->deviceEntries) === 0) {
            $this->errorMessage = 'Please add at least one device.';
            return;
        }

        $this->step = 5;
    }

    public function goBack(int $toStep): void
    {
        if ($toStep >= 1 && $toStep <= 5) {
            $this->step = $toStep;
        }
    }

    /* ─────────── submit ─────────── */

    public function submit(): void
    {
        $this->errorMessage = '';

        $this->validate([
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'jobDetails' => ['required', 'string', 'max:5000'],
        ]);

        if (count($this->deviceEntries) === 0) {
            $this->errorMessage = 'Please add at least one device.';
            return;
        }

        // Build payload for RepairBuddyPublicBookingService
        $devicesPayload = [];
        foreach ($this->deviceEntries as $entry) {
            $svc = [];
            if (! empty($entry['selectedServiceId'])) {
                $svc[] = [
                    'service_id' => (int) $entry['selectedServiceId'],
                    'qty' => 1,
                ];
            }

            $devicesPayload[] = [
                'device_id' => (int) $entry['device_id'],
                'device_label' => (string) ($entry['device_label'] ?? ''),
                'serial' => trim((string) ($entry['serial'] ?? '')),
                'pin' => trim((string) ($entry['pin'] ?? '')),
                'notes' => trim((string) ($entry['notes'] ?? '')),
                'services' => $svc,
                'other_service' => empty($entry['selectedServiceId']) ? trim((string) ($entry['otherService'] ?? '')) : '',
            ];
        }

        $validated = [
            'jobDetails' => $this->jobDetails,
            'customer' => [
                'firstName' => $this->firstName,
                'lastName' => $this->lastName,
                'userEmail' => $this->email,
                'phone' => $this->phone,
                'company' => $this->company,
                'taxId' => $this->taxId,
                'addressLine1' => $this->addressLine1,
                'city' => $this->city,
                'postalCode' => $this->postalCode,
            ],
            'devices' => $devicesPayload,
        ];

        try {
            $svc = app(RepairBuddyPublicBookingService::class);
            $result = $svc->submit(request(), $this->business, $validated);

            $this->submitted = true;
            $this->submissionCaseNumber = (string) ($result['case_number'] ?? '');
            $this->submissionMessage = 'Your booking has been submitted successfully!';
        } catch (\Illuminate\Validation\ValidationException $e) {
            $messages = $e->validator->errors()->all();
            $this->errorMessage = implode(' ', $messages);
        } catch (\Throwable $e) {
            Log::error('booking.submit_failed', ['error' => $e->getMessage()]);
            $this->errorMessage = 'An unexpected error occurred. Please try again.';
        }
    }

    /* ─────────── render ─────────── */

    public function render()
    {
        return view('livewire.tenant.booking.booking-form');
    }
}
