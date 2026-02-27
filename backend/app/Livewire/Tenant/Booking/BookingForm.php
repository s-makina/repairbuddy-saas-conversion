<?php

namespace App\Livewire\Tenant\Booking;

use App\Models\RepairBuddyAppointmentSetting;
use App\Models\RepairBuddyDevice;
use App\Models\RepairBuddyDeviceBrand;
use App\Models\RepairBuddyDeviceFieldDefinition;
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
    public ?Tenant $tenant = null;
    public ?int $tenantId = null;
    public string $business = '';
    public string $tenantName = '';

    /* ───────── Booking config ───────── */
    public bool $turnOffOtherDeviceBrand = false;
    public bool $turnOffOtherService = false;
    public bool $turnOffServicePrice = false;
    public bool $turnOffIdImeiBooking = false;
    public bool $enablePinCodeField = false;
    public string $defaultTypeId = '';
    public string $defaultBrandId = '';
    public string $defaultDeviceId = '';

    /* ───────── Device & Brand Labels (from settings) ───────── */
    public string $labelDevice = 'Device';
    public string $labelBrand = 'Brand';
    public string $labelType = 'Type';
    public string $labelImei = 'ID / IMEI';
    public string $labelPin = 'Pin Code / Password';
    public string $labelNote = 'Note';

    /* ───────── Pickup & Delivery (from settings) ───────── */
    public bool $pickupDeliveryEnabled = false;
    public string $pickupCharge = '0';
    public string $deliveryCharge = '0';

    /* ───────── Rental (from settings) ───────── */
    public bool $rentalEnabled = false;
    public string $rentalPerDay = '0';
    public string $rentalPerWeek = '0';

    /* ───────── Dynamic field definitions ───────── */
    public array $bookingFieldDefinitions = [];

    /* ───────── Wizard state ───────── */
    public int $step = 1;

    /* ───────── Step 1: Device Type ───────── */
    public array $deviceTypes = [];
    public ?int $selectedTypeId = null;

    /* ───────── Step 2: Brand ───────── */
    public array $brands = [];
    public ?int $selectedBrandId = null;
    public bool $isOtherBrand = false;

    /* ───────── Step 3: Device ───────── */
    public array $devices = [];
    public bool $isOtherDevice = false;
    public string $otherDeviceLabel = '';
    public string $deviceSearch = '';

    /* ───────── Step 4: Services ───────── */
    public array $services = [];

    /* ───────── Appointment Booking ───────── */
    public array $appointmentOptions = [];
    public ?int $selectedAppointmentId = null;
    public string $selectedAppointmentDate = '';
    public string $selectedTimeSlot = '';

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

    /* ───────── GDPR ───────── */
    public string $gdprLabel = '';
    public string $gdprLinkLabel = '';
    public string $gdprLinkUrl = '';
    public bool $gdprAccepted = false;

    /* ───────── Logged-in / returning customer auto-fill ───────── */
    public bool $isLoggedInCustomer = false;
    public ?int $loggedInCustomerId = null;
    public bool $isReturningCustomer = false;

    /* ─────────── mount ─────────── */

    public function mount(?Tenant $tenant = null, string $business = '')
    {
        $this->business = $business;

        if (! $tenant) {
            $tenant = TenantContext::tenant();
        }

        if ($tenant instanceof Tenant) {
            $this->tenant = $tenant;
            $this->tenantId = $tenant->id;
            $this->tenantName = (string) ($tenant->name ?? '');
        }

        $this->loadBookingConfig();
        $this->loadDeviceTypes();
        $this->prefillLoggedInCustomer();
        $this->prefillFromSession();

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

    /**
     * Pre-fill customer details if a logged-in customer is on the booking page.
     */
    protected function prefillLoggedInCustomer(): void
    {
        $user = auth()->user();

        if (! $user instanceof \App\Models\User) {
            return;
        }

        // Only auto-fill for customers within the same tenant
        if ($user->role !== 'customer' || (int) ($user->tenant_id ?? 0) !== (int) ($this->tenantId ?? 0)) {
            return;
        }

        $this->isLoggedInCustomer = true;
        $this->loggedInCustomerId = (int) $user->id;

        $this->fillFromUser($user);
    }

    /**
     * Pre-fill from session for returning guests who have previously booked.
     * Only applies if not already logged in.
     */
    protected function prefillFromSession(): void
    {
        if ($this->isLoggedInCustomer) {
            return;
        }

        $sessionKey = 'booking_customer_' . ($this->tenantId ?? 0);
        $saved = session($sessionKey);

        if (! is_array($saved) || empty($saved['email'])) {
            return;
        }

        // Verify the saved customer still exists
        $user = \App\Models\User::query()
            ->where('tenant_id', (int) ($this->tenantId ?? 0))
            ->where('email', strtolower(trim((string) $saved['email'])))
            ->where('role', 'customer')
            ->first();

        if (! $user instanceof \App\Models\User) {
            session()->forget($sessionKey);
            return;
        }

        $this->isReturningCustomer = true;
        $this->fillFromUser($user);
    }

    /**
     * Fill the customer form fields from a User model.
     */
    protected function fillFromUser(\App\Models\User $user): void
    {
        $this->firstName = (string) ($user->first_name ?? '');
        $this->lastName = (string) ($user->last_name ?? '');
        $this->email = (string) ($user->email ?? '');
        $this->phone = (string) ($user->phone ?? '');
        $this->company = (string) ($user->company ?? '');
        $this->taxId = (string) ($user->tax_id ?? '');
        $this->addressLine1 = (string) ($user->address_line1 ?? '');
        $this->city = (string) ($user->address_city ?? '');
        $this->postalCode = (string) ($user->address_postal_code ?? '');
    }

    /**
     * Save customer details to session after a successful booking
     * so returning guests don't have to re-enter their info.
     */
    protected function saveCustomerToSession(): void
    {
        $sessionKey = 'booking_customer_' . ($this->tenantId ?? 0);
        session([$sessionKey => [
            'email' => strtolower(trim($this->email)),
            'saved_at' => now()->toIso8601String(),
        ]]);
    }

    /**
     * Clear the saved customer session so a returning guest can enter new details.
     * Called from the "Not you? Change details" button.
     */
    public function clearSavedCustomer(): void
    {
        $sessionKey = 'booking_customer_' . ($this->tenantId ?? 0);
        session()->forget($sessionKey);

        $this->isReturningCustomer = false;
        $this->firstName = '';
        $this->lastName = '';
        $this->email = '';
        $this->phone = '';
        $this->company = '';
        $this->taxId = '';
        $this->addressLine1 = '';
        $this->city = '';
        $this->postalCode = '';
    }

    public function hydrate(): void
    {
        if ($this->tenant instanceof Tenant) {
            TenantContext::set($this->tenant);
            
            $branchId = is_numeric($this->tenant->default_branch_id) ? (int) $this->tenant->default_branch_id : null;
            if ($branchId) {
                $branch = \App\Models\Branch::find($branchId);
                if ($branch) {
                    \App\Support\BranchContext::set($branch);
                }
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
        $devicesBrands = is_array($settings['devicesBrands'] ?? null) ? $settings['devicesBrands'] : [];

        $this->turnOffOtherDeviceBrand = (bool) ($booking['turnOffOtherDeviceBrand'] ?? false);
        $this->turnOffOtherService = (bool) ($booking['turnOffOtherService'] ?? false);
        $this->turnOffServicePrice = (bool) ($booking['turnOffServicePrice'] ?? false);
        $this->turnOffIdImeiBooking = (bool) ($booking['turnOffIdImeiInBooking'] ?? false);
        $this->enablePinCodeField = (bool) ($devicesBrands['enablePinCodeField'] ?? false);
        $this->defaultTypeId = (string) ($booking['defaultType'] ?? '');
        $this->defaultBrandId = (string) ($booking['defaultBrand'] ?? '');
        $this->defaultDeviceId = (string) ($booking['defaultDevice'] ?? '');

        // Device & Brand labels from settings
        $labels = is_array($devicesBrands['labels'] ?? null) ? $devicesBrands['labels'] : [];
        $this->labelDevice = (string) ($labels['device'] ?? 'Device');
        $this->labelBrand = (string) ($labels['deviceBrand'] ?? 'Brand');
        $this->labelType = (string) ($labels['deviceType'] ?? 'Type');
        $this->labelImei = (string) ($labels['imei'] ?? 'ID / IMEI');
        $this->labelPin = (string) ($labels['pin'] ?? 'Pin Code / Password');
        $this->labelNote = (string) ($labels['note'] ?? 'Note');

        // Pickup & Delivery settings
        $this->pickupDeliveryEnabled = (bool) ($devicesBrands['pickupDeliveryEnabled'] ?? false);
        $this->pickupCharge = (string) ($devicesBrands['pickupCharge'] ?? '0');
        $this->deliveryCharge = (string) ($devicesBrands['deliveryCharge'] ?? '0');

        // Rental settings
        $this->rentalEnabled = (bool) ($devicesBrands['rentalEnabled'] ?? false);
        $this->rentalPerDay = (string) ($devicesBrands['rentalPerDay'] ?? '0');
        $this->rentalPerWeek = (string) ($devicesBrands['rentalPerWeek'] ?? '0');

        // GDPR
        $general = is_array($settings['general'] ?? null) ? $settings['general'] : [];
        $this->gdprLabel = (string) ($general['wc_rb_gdpr_acceptance'] ?? '');
        $this->gdprLinkLabel = (string) ($general['wc_rb_gdpr_acceptance_link_label'] ?? '');
        $this->gdprLinkUrl = (string) ($general['wc_rb_gdpr_acceptance_link'] ?? '');

        // Dynamic field definitions for booking
        $this->bookingFieldDefinitions = RepairBuddyDeviceFieldDefinition::query()
            ->where('is_active', true)
            ->where('show_in_booking', true)
            ->orderBy('id')
            ->get()
            ->map(fn (RepairBuddyDeviceFieldDefinition $d) => [
                'id' => $d->id,
                'key' => $d->key,
                'label' => $d->label,
                'type' => $d->type,
            ])
            ->toArray();

        // Load appointment options
        $this->appointmentOptions = RepairBuddyAppointmentSetting::query()
            ->where('is_enabled', true)
            ->orderBy('title')
            ->limit(50)
            ->get()
            ->map(fn (RepairBuddyAppointmentSetting $a) => [
                'id' => $a->id,
                'title' => $a->title,
                'description' => $a->description,
                'slot_duration_minutes' => $a->slot_duration_minutes,
                'buffer_minutes' => $a->buffer_minutes,
                'max_appointments_per_day' => $a->max_appointments_per_day,
                'time_slots' => is_array($a->time_slots) ? $a->time_slots : [],
            ])
            ->toArray();
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

        // Apply search filter
        $search = trim($this->deviceSearch);
        if ($search !== '') {
            $query->where('model', 'LIKE', '%' . $search . '%');
        }

        $devices = $query->limit(1000)->get();

        $this->devices = $devices->map(fn (RepairBuddyDevice $d) => [
            'id' => $d->id,
            'model' => $d->model,
            'is_other' => (bool) $d->is_other,
        ])->toArray();
    }

    /**
     * Re-filter devices when search query changes.
     */
    public function updatedDeviceSearch(): void
    {
        $this->loadDevices();
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
        $this->isOtherBrand = false;
        $this->isOtherDevice = false;
        $this->otherDeviceLabel = '';
        $this->brands = [];
        $this->devices = [];
        $this->services = [];
        $this->loadBrands();
        $this->step = 2;
    }

    public function selectBrand(int $id): void
    {
        $this->selectedBrandId = $id;
        $this->isOtherBrand = false;
        $this->isOtherDevice = false;
        $this->otherDeviceLabel = '';
        $this->devices = [];
        $this->services = [];
        $this->loadDevices();
        $this->step = 3;
    }

    /**
     * "Other" brand selected — skip device step, show custom device label input.
     * Matches plugin: dt_brand_id="brand_other" → load_other_device
     */
    public function selectOtherBrand(): void
    {
        $this->selectedBrandId = null;
        $this->isOtherBrand = true;
        $this->isOtherDevice = true;
        $this->otherDeviceLabel = '';
        $this->devices = [];
        $this->services = [];
        // Skip device step (step 3), go straight to "other device" input (step 3 with isOtherDevice=true)
        $this->step = 3;
    }

    /**
     * "Other" device selected from the device list.
     * Matches plugin: dt_device_id="load_other_device"
     */
    public function selectOtherDevice(): void
    {
        $this->isOtherDevice = true;
        $this->otherDeviceLabel = '';
    }

    /**
     * Confirm custom device label and add entry with all services (no device-specific filtering).
     * Stays on step 3 so user can add more devices.
     */
    public function confirmOtherDevice(): void
    {
        $label = trim($this->otherDeviceLabel);
        if ($label === '') {
            $this->errorMessage = 'Please enter a device name.';
            return;
        }

        $this->errorMessage = '';
        $services = $this->loadAllServices();

        // Build empty extra_fields from booking field definitions
        $extraFields = [];
        foreach ($this->bookingFieldDefinitions as $def) {
            $extraFields[] = [
                'key' => $def['key'],
                'label' => $def['label'],
                'value_text' => '',
            ];
        }

        $this->deviceEntries[] = [
            'device_id' => null,
            'device_label' => $label,
            'is_other' => true,
            'serial' => '',
            'pin' => '',
            'notes' => '',
            'extra_fields' => $extraFields,
            'selectedServiceIds' => [],
            'otherService' => '',
            'services' => $services,
        ];

        $this->isOtherDevice = false;
        $this->isOtherBrand = false;
        $this->otherDeviceLabel = '';
        // Stay on step 3 so user can add more devices
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

        // Build empty extra_fields from booking field definitions
        $extraFields = [];
        foreach ($this->bookingFieldDefinitions as $def) {
            $extraFields[] = [
                'key' => $def['key'],
                'label' => $def['label'],
                'value_text' => '',
            ];
        }

        $this->deviceEntries[] = [
            'device_id' => $deviceId,
            'device_label' => $device['model'],
            'is_other' => false,
            'serial' => '',
            'pin' => '',
            'notes' => '',
            'extra_fields' => $extraFields,
            'selectedServiceIds' => [],
            'otherService' => '',
            'services' => $services,
        ];

        // Stay on step 3 so user can add more devices
        $this->deviceSearch = '';
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

    /**
     * Toggle a service selection for a device entry (multi-select).
     */
    public function toggleService(int $deviceIdx, int $serviceId): void
    {
        if (! isset($this->deviceEntries[$deviceIdx])) {
            return;
        }

        // Clone the full array so Livewire detects the nested mutation
        $entries = $this->deviceEntries;

        $ids = $entries[$deviceIdx]['selectedServiceIds'] ?? [];
        if (! is_array($ids)) {
            $ids = [];
        }

        if (in_array($serviceId, $ids)) {
            $ids = array_values(array_filter($ids, fn ($id) => $id !== $serviceId));
        } else {
            $ids[] = $serviceId;
        }

        $entries[$deviceIdx]['selectedServiceIds'] = $ids;

        // Reassign the entire property so Livewire tracks the change in its snapshot
        $this->deviceEntries = $entries;
    }

    /**
     * Validate selected devices and proceed to service selection (step 4).
     */
    public function proceedToServices(): void
    {
        $this->errorMessage = '';

        if (count($this->deviceEntries) === 0) {
            $this->errorMessage = 'Please add at least one device.';
            return;
        }

        $this->step = 4;
    }

    /**
     * Load all active services without device-specific availability filtering.
     * Used for "Other" devices where no specific device is selected.
     */
    protected function loadAllServices(): array
    {
        $services = RepairBuddyService::query()
            ->where('is_active', true)
            ->with(['type'])
            ->orderBy('name')
            ->limit(500)
            ->get();

        $out = [];
        foreach ($services as $s) {
            $priceDisplay = null;
            if (! $this->turnOffServicePrice) {
                $cents = is_numeric($s->base_price_amount_cents) ? (int) $s->base_price_amount_cents : null;
                $currency = is_string($s->base_price_currency) && $s->base_price_currency !== '' ? $s->base_price_currency : null;
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

    public function goToCustomerStep(): void
    {
        $this->errorMessage = '';

        foreach ($this->deviceEntries as $idx => $entry) {
            $hasServices = ! empty($entry['selectedServiceIds']) && is_array($entry['selectedServiceIds']) && count($entry['selectedServiceIds']) > 0;
            if (! $hasServices && trim($entry['otherService'] ?? '') === '') {
                $this->errorMessage = 'Please select a service for each device.';
                return;
            }
        }

        if (count($this->deviceEntries) === 0) {
            $this->errorMessage = 'Please add at least one device.';
            return;
        }

        // For logged-in or returning customers, skip "Your Details" step and submit directly
        if ($this->isLoggedInCustomer || $this->isReturningCustomer) {
            $this->step = 5; // briefly set to 5 for the confirmation view
            $this->submit();
            return;
        }

        $this->step = 5;
    }

    public function goBack(int $toStep): void
    {
        if ($toStep >= 1 && $toStep <= 5) {
            // Reset Other state when navigating backward
            if ($toStep <= 2) {
                $this->isOtherDevice = false;
                $this->isOtherBrand = false;
                $this->otherDeviceLabel = '';
            }
            if ($toStep <= 3 && ! $this->isOtherBrand) {
                $this->isOtherDevice = false;
                $this->otherDeviceLabel = '';
            }
            $this->step = $toStep;
        }
    }

    /* ─────────── submit ─────────── */

    public function submit(): void
    {
        $this->errorMessage = '';

        // For logged-in/returning customers, jobDetails is optional (auto-generated if empty)
        $jobDetailsRule = ($this->isLoggedInCustomer || $this->isReturningCustomer)
            ? ['nullable', 'string', 'max:5000']
            : ['required', 'string', 'max:5000'];

        $rules = [
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'jobDetails' => $jobDetailsRule,
        ];

        $messages = [];

        // Skip GDPR requirement for logged-in/returning customers (already accepted)
        if ($this->gdprLabel && ! $this->isLoggedInCustomer && ! $this->isReturningCustomer) {
            $rules['gdprAccepted'] = ['accepted'];
            $messages['gdprAccepted.accepted'] = 'You must accept the privacy policy / terms to continue.';
        }

        $this->validate($rules, $messages);

        if (count($this->deviceEntries) === 0) {
            $this->errorMessage = 'Please add at least one device.';
            return;
        }

        // Auto-generate job details for logged-in/returning customers if left empty
        if (($this->isLoggedInCustomer || $this->isReturningCustomer) && trim($this->jobDetails) === '') {
            $deviceLabels = collect($this->deviceEntries)->pluck('device_label')->filter()->implode(', ');
            $this->jobDetails = 'Booking for: ' . ($deviceLabels ?: 'repair service');
        }

        // Build payload for RepairBuddyPublicBookingService
        $devicesPayload = [];
        foreach ($this->deviceEntries as $entry) {
            $svc = [];
            $selectedIds = is_array($entry['selectedServiceIds'] ?? null) ? $entry['selectedServiceIds'] : [];
            foreach ($selectedIds as $sid) {
                if (is_numeric($sid) && (int) $sid > 0) {
                    $svc[] = [
                        'service_id' => (int) $sid,
                        'qty' => 1,
                    ];
                }
            }

            // Build extra_fields payload
            $extraFields = [];
            if (is_array($entry['extra_fields'] ?? null)) {
                foreach ($entry['extra_fields'] as $ef) {
                    $val = is_string($ef['value_text'] ?? null) ? trim((string) $ef['value_text']) : '';
                    if ($val !== '') {
                        $extraFields[] = [
                            'key' => (string) ($ef['key'] ?? ''),
                            'label' => (string) ($ef['label'] ?? ''),
                            'value_text' => $val,
                        ];
                    }
                }
            }

            $devicesPayload[] = [
                'device_id' => ! empty($entry['device_id']) ? (int) $entry['device_id'] : null,
                'device_label' => (string) ($entry['device_label'] ?? ''),
                'serial' => trim((string) ($entry['serial'] ?? '')),
                'pin' => trim((string) ($entry['pin'] ?? '')),
                'notes' => trim((string) ($entry['notes'] ?? '')),
                'extra_fields' => $extraFields,
                'services' => $svc,
                'other_service' => empty($selectedIds) ? trim((string) ($entry['otherService'] ?? '')) : '',
            ];
        }

        $validated = [
            'jobDetails' => $this->jobDetails,
            'gdprAccepted' => $this->gdprAccepted,
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
            'appointment' => [
                'appointment_setting_id' => $this->selectedAppointmentId,
                'date' => $this->selectedAppointmentDate,
                'time_slot' => $this->selectedTimeSlot,
            ],
        ];

        try {
            $svc = app(RepairBuddyPublicBookingService::class);
            $result = $svc->submit(request(), $this->business, $validated);

            $this->submitted = true;
            $this->submissionCaseNumber = (string) ($result['case_number'] ?? '');
            $this->submissionMessage = 'Your booking has been submitted successfully!';

            // Save customer details to session for future bookings
            $this->saveCustomerToSession();
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
