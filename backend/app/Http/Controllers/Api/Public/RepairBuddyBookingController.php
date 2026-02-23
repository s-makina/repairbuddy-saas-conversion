<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyBranch;
use App\Models\RepairBuddyCaseCounter;
use App\Models\RepairBuddyCustomerDeviceFieldValue;
use App\Models\RepairBuddyDeviceFieldDefinition;
use App\Models\RepairBuddyDeviceType;
use App\Models\RepairBuddyEstimate;
use App\Models\RepairBuddyEstimateDevice;
use App\Models\RepairBuddyEstimateItem;
use App\Models\RepairBuddyEstimateAttachment;
use App\Models\RepairBuddyEvent;
use App\Models\RepairBuddyJob;
use App\Models\Status;
use App\Models\RepairBuddyPublicBookingForm;
use App\Models\RepairBuddyService;
use App\Models\RepairBuddyServicePrice;
use App\Models\Tenant;
use App\Models\RepairBuddyServiceAvailabilityOverride;
use App\Models\RepairBuddyServicePriceOverride;
use App\Models\RepairBuddyServiceType;
use App\Models\User;
use App\Notifications\BookingSubmissionAdminNotification;
use App\Notifications\BookingSubmissionCustomerNotification;
use App\Notifications\OneTimePasswordNotification;
use App\Support\RepairBuddyPublicBookingService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RepairBuddyBookingController extends Controller
{
    public function config(Request $request, string $business)
    {
        $tenant = $this->tenant();

        $settings = data_get($tenant->setup_state ?? [], 'repairbuddy_settings');
        if (! is_array($settings)) {
            $settings = [];
        }

        $general = is_array($settings['general'] ?? null) ? $settings['general'] : [];
        $booking = is_array($settings['booking'] ?? null) ? $settings['booking'] : [];
        $myAccount = is_array($settings['myAccount'] ?? null) ? $settings['myAccount'] : [];
        $devicesBrands = is_array($settings['devicesBrands'] ?? null) ? $settings['devicesBrands'] : [];
        $estimates = is_array($settings['estimates'] ?? null) ? $settings['estimates'] : [];

        return response()->json([
            'disabled' => (bool) ($myAccount['disableBooking'] ?? false),
            'general' => [
                'gdprAcceptanceText' => $general['gdprAcceptanceText'] ?? null,
                'gdprLinkLabel' => $general['gdprLinkLabel'] ?? null,
                'gdprLinkUrl' => $general['gdprLinkUrl'] ?? null,
                'defaultCountry' => $general['defaultCountry'] ?? null,
            ],
            'devicesBrands' => [
                'enablePinCodeField' => (bool) ($devicesBrands['enablePinCodeField'] ?? false),
                'labels' => is_array($devicesBrands['labels'] ?? null) ? $devicesBrands['labels'] : null,
                'additionalDeviceFields' => is_array($devicesBrands['additionalDeviceFields'] ?? null) ? $devicesBrands['additionalDeviceFields'] : [],
            ],
            'booking' => [
                'publicBookingMode' => $booking['publicBookingMode'] ?? 'ungrouped',
                'publicBookingUiStyle' => $booking['publicBookingUiStyle'] ?? 'wizard',
                'sendBookingQuoteToJobs' => (bool) ($booking['sendBookingQuoteToJobs'] ?? ($estimates['bookingQuoteSendToJobs'] ?? false)),
                'customerCreationEmailBehavior' => $booking['customerCreationEmailBehavior'] ?? 'send_login_credentials',
                'turnOffOtherDeviceBrand' => (bool) ($booking['turnOffOtherDeviceBrand'] ?? false),
                'turnOffOtherService' => (bool) ($booking['turnOffOtherService'] ?? false),
                'turnOffServicePrice' => (bool) ($booking['turnOffServicePrice'] ?? false),
                'turnOffIdImeiInBooking' => (bool) ($booking['turnOffIdImeiInBooking'] ?? false),
                'defaultType' => $booking['defaultType'] ?? '',
                'defaultBrand' => $booking['defaultBrand'] ?? '',
                'defaultDevice' => $booking['defaultDevice'] ?? '',
            ],
        ]);
    }

    public function deviceTypes(Request $request, string $business)
    {
        $types = RepairBuddyDeviceType::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(500)
            ->get();

        return response()->json([
            'device_types' => $types->map(fn (RepairBuddyDeviceType $t) => [
                'id' => $t->id,
                'parent_id' => $t->parent_id,
                'name' => $t->name,
                'description' => $t->description,
                'image_url' => $t->image_url,
            ]),
        ]);
    }

    public function brands(Request $request, string $business)
    {
        $validated = $request->validate([
            'typeId' => ['sometimes', 'nullable', 'integer'],
        ]);

        $typeId = array_key_exists('typeId', $validated) && is_numeric($validated['typeId']) ? (int) $validated['typeId'] : null;

        $settings = data_get($this->tenant()->setup_state ?? [], 'repairbuddy_settings');
        $settings = is_array($settings) ? $settings : [];
        $booking = is_array($settings['booking'] ?? null) ? $settings['booking'] : [];
        $turnOffOtherDeviceBrand = (bool) ($booking['turnOffOtherDeviceBrand'] ?? false);

        $query = RepairBuddyDeviceBrand::query()
            ->where('is_active', true)
            ->orderBy('name');

        if ($typeId) {
            $brandIds = RepairBuddyDevice::query()
                ->where('is_active', true)
                ->where('disable_in_booking_form', false)
                ->where('device_type_id', $typeId)
                ->distinct()
                ->pluck('device_brand_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $brandIds = array_values(array_unique(array_filter($brandIds, fn ($id) => $id > 0)));

            if (count($brandIds) === 0) {
                return response()->json([
                    'brands' => [],
                ]);
            }

            $query->whereIn('id', $brandIds);
        }

        if ($turnOffOtherDeviceBrand) {
            $query->whereRaw('LOWER(name) <> ?', ['other']);
        }

        $brands = $query->limit(500)->get();

        return response()->json([
            'brands' => $brands->map(fn (RepairBuddyDeviceBrand $b) => [
                'id' => $b->id,
                'name' => $b->name,
                'image_url' => $b->image_url,
            ]),
        ]);
    }

    public function devices(Request $request, string $business)
    {
        $validated = $request->validate([
            'typeId' => ['sometimes', 'nullable', 'integer'],
            'brandId' => ['sometimes', 'nullable', 'integer'],
        ]);

        $typeId = array_key_exists('typeId', $validated) && is_numeric($validated['typeId']) ? (int) $validated['typeId'] : null;
        $brandId = array_key_exists('brandId', $validated) && is_numeric($validated['brandId']) ? (int) $validated['brandId'] : null;

        $settings = data_get($this->tenant()->setup_state ?? [], 'repairbuddy_settings');
        $settings = is_array($settings) ? $settings : [];
        $booking = is_array($settings['booking'] ?? null) ? $settings['booking'] : [];
        $turnOffOtherDeviceBrand = (bool) ($booking['turnOffOtherDeviceBrand'] ?? false);

        $query = RepairBuddyDevice::query()
            ->where('is_active', true)
            ->where('disable_in_booking_form', false)
            ->orderBy('model');

        if ($typeId) {
            $query->where('device_type_id', $typeId);
        }

        if ($brandId) {
            $query->where('device_brand_id', $brandId);
        }

        if ($turnOffOtherDeviceBrand) {
            $query->where('is_other', false);
        }

        $devices = $query->limit(1000)->get();

        return response()->json([
            'devices' => $devices->map(fn (RepairBuddyDevice $d) => [
                'id' => $d->id,
                'model' => $d->model,
                'device_type_id' => $d->device_type_id,
                'device_brand_id' => $d->device_brand_id,
                'parent_device_id' => $d->parent_device_id,
                'is_other' => (bool) $d->is_other,
            ]),
        ]);
    }

    public function services(Request $request, string $business)
    {
        $validated = $request->validate([
            'deviceId' => ['sometimes', 'nullable', 'integer'],
            'mode' => ['sometimes', 'nullable', 'string', 'in:ungrouped,grouped,warranty'],
            'limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        $deviceId = array_key_exists('deviceId', $validated) && is_numeric($validated['deviceId']) ? (int) $validated['deviceId'] : null;
        $limit = is_numeric($validated['limit'] ?? null) ? (int) $validated['limit'] : 500;

        $settings = data_get($this->tenant()->setup_state ?? [], 'repairbuddy_settings');
        $settings = is_array($settings) ? $settings : [];
        $booking = is_array($settings['booking'] ?? null) ? $settings['booking'] : [];

        $mode = is_string($validated['mode'] ?? null) && $validated['mode'] !== ''
            ? (string) $validated['mode']
            : (is_string($booking['publicBookingMode'] ?? null) ? (string) $booking['publicBookingMode'] : 'ungrouped');

        $turnOffServicePrice = (bool) ($booking['turnOffServicePrice'] ?? false);

        $contextDevice = null;
        $contextDeviceId = null;
        $contextBrandId = null;
        $contextTypeId = null;

        if ($deviceId) {
            $contextDevice = RepairBuddyDevice::query()->whereKey($deviceId)->first();
            if ($contextDevice) {
                $contextDeviceId = (int) $contextDevice->id;
                $contextBrandId = is_numeric($contextDevice->device_brand_id) ? (int) $contextDevice->device_brand_id : null;
                $contextTypeId = is_numeric($contextDevice->device_type_id) ? (int) $contextDevice->device_type_id : null;
            }
        }

        $services = RepairBuddyService::query()
            ->where('is_active', true)
            ->with(['type'])
            ->orderBy('name')
            ->limit($limit)
            ->get();

        $serviceIds = $services->map(fn (RepairBuddyService $s) => (int) $s->id)->values()->all();

        $availabilityByService = [];
        if ($contextDeviceId || $contextBrandId || $contextTypeId) {
            $availabilityRows = RepairBuddyServiceAvailabilityOverride::query()
                ->whereIn('service_id', $serviceIds)
                ->where(function ($q) use ($contextDeviceId, $contextBrandId, $contextTypeId) {
                    if ($contextDeviceId) {
                        $q->orWhere(function ($q2) use ($contextDeviceId) {
                            $q2->where('scope_type', 'device')->where('scope_ref_id', $contextDeviceId);
                        });
                    }
                    if ($contextBrandId) {
                        $q->orWhere(function ($q2) use ($contextBrandId) {
                            $q2->where('scope_type', 'brand')->where('scope_ref_id', $contextBrandId);
                        });
                    }
                    if ($contextTypeId) {
                        $q->orWhere(function ($q2) use ($contextTypeId) {
                            $q2->where('scope_type', 'type')->where('scope_ref_id', $contextTypeId);
                        });
                    }
                })
                ->orderByDesc('id')
                ->limit(5000)
                ->get();

            foreach ($availabilityRows as $row) {
                $sid = is_numeric($row->service_id) ? (int) $row->service_id : 0;
                $stype = is_string($row->scope_type) ? (string) $row->scope_type : '';
                if ($sid <= 0 || $stype === '') {
                    continue;
                }

                if (! array_key_exists($sid, $availabilityByService)) {
                    $availabilityByService[$sid] = [];
                }

                if (! array_key_exists($stype, $availabilityByService[$sid])) {
                    $availabilityByService[$sid][$stype] = (string) $row->status;
                }
            }
        }

        $priceOverrides = [];
        if ($contextDeviceId || $contextBrandId || $contextTypeId) {
            $priceRows = RepairBuddyServicePriceOverride::query()
                ->whereIn('service_id', $serviceIds)
                ->where('is_active', true)
                ->where(function ($q) use ($contextDeviceId, $contextBrandId, $contextTypeId) {
                    if ($contextDeviceId) {
                        $q->orWhere(function ($q2) use ($contextDeviceId) {
                            $q2->where('scope_type', 'device')->where('scope_ref_id', $contextDeviceId);
                        });
                    }
                    if ($contextBrandId) {
                        $q->orWhere(function ($q2) use ($contextBrandId) {
                            $q2->where('scope_type', 'brand')->where('scope_ref_id', $contextBrandId);
                        });
                    }
                    if ($contextTypeId) {
                        $q->orWhere(function ($q2) use ($contextTypeId) {
                            $q2->where('scope_type', 'type')->where('scope_ref_id', $contextTypeId);
                        });
                    }
                })
                ->orderByDesc('id')
                ->limit(5000)
                ->get();

            foreach ($priceRows as $row) {
                $sid = is_numeric($row->service_id) ? (int) $row->service_id : 0;
                $stype = is_string($row->scope_type) ? (string) $row->scope_type : '';
                if ($sid <= 0 || $stype === '') {
                    continue;
                }

                if (! array_key_exists($sid, $priceOverrides)) {
                    $priceOverrides[$sid] = [];
                }

                if (! array_key_exists($stype, $priceOverrides[$sid])) {
                    $priceOverrides[$sid][$stype] = $row;
                }
            }
        }

        $serialize = function (RepairBuddyService $s) use ($availabilityByService, $contextDeviceId, $contextBrandId, $contextTypeId, $priceOverrides, $turnOffServicePrice) {
            $sid = (int) $s->id;

            $available = true;
            if (! $s->is_active) {
                $available = false;
            }

            $status = null;
            if ($available && array_key_exists($sid, $availabilityByService)) {
                $rules = $availabilityByService[$sid];
                if ($contextDeviceId && array_key_exists('device', $rules)) {
                    $status = $rules['device'];
                } elseif ($contextBrandId && array_key_exists('brand', $rules)) {
                    $status = $rules['brand'];
                } elseif ($contextTypeId && array_key_exists('type', $rules)) {
                    $status = $rules['type'];
                }

                if (is_string($status) && $status !== '') {
                    $available = $status === 'active';
                }
            }

            $resolved = null;
            $resolvedTaxId = is_numeric($s->tax_id) ? (int) $s->tax_id : null;

            if (! $turnOffServicePrice) {
                $override = null;

                if ($contextDeviceId && array_key_exists($sid, $priceOverrides) && array_key_exists('device', $priceOverrides[$sid])) {
                    $override = $priceOverrides[$sid]['device'];
                } elseif ($contextBrandId && array_key_exists($sid, $priceOverrides) && array_key_exists('brand', $priceOverrides[$sid])) {
                    $override = $priceOverrides[$sid]['brand'];
                } elseif ($contextTypeId && array_key_exists($sid, $priceOverrides) && array_key_exists('type', $priceOverrides[$sid])) {
                    $override = $priceOverrides[$sid]['type'];
                }

                $resolvedCents = $override && is_numeric($override->price_amount_cents)
                    ? (int) $override->price_amount_cents
                    : (is_numeric($s->base_price_amount_cents) ? (int) $s->base_price_amount_cents : null);

                $resolvedCurrency = $override && is_string($override->price_currency) && $override->price_currency !== ''
                    ? (string) $override->price_currency
                    : (is_string($s->base_price_currency) && $s->base_price_currency !== '' ? (string) $s->base_price_currency : null);

                if ($override && is_numeric($override->tax_id)) {
                    $resolvedTaxId = (int) $override->tax_id;
                }

                if ($resolvedCents !== null && $resolvedCurrency !== null && $resolvedCurrency !== '') {
                    $resolved = [
                        'currency' => $resolvedCurrency,
                        'amount_cents' => $resolvedCents,
                    ];
                }
            }

            $serviceType = $s->type;

            return [
                'id' => $s->id,
                'name' => $s->name,
                'description' => $s->description,
                'service_type' => $serviceType instanceof RepairBuddyServiceType ? [
                    'id' => $serviceType->id,
                    'name' => $serviceType->name,
                ] : null,
                'is_active' => $available,
                'price' => $resolved,
                'tax_id' => $resolvedTaxId,
            ];
        };

        if ($mode === 'grouped' || $mode === 'warranty') {
            $grouped = [];
            foreach ($services as $s) {
                $type = $s->type;
                $typeId = $type instanceof RepairBuddyServiceType ? (int) $type->id : 0;

                if (! array_key_exists($typeId, $grouped)) {
                    $grouped[$typeId] = [
                        'service_type' => $type instanceof RepairBuddyServiceType ? [
                            'id' => $type->id,
                            'name' => $type->name,
                        ] : null,
                        'services' => [],
                    ];
                }

                $grouped[$typeId]['services'][] = $serialize($s);
            }

            return response()->json([
                'mode' => $mode,
                'groups' => array_values($grouped),
            ]);
        }

        return response()->json([
            'mode' => $mode,
            'services' => $services->map(fn (RepairBuddyService $s) => $serialize($s)),
        ]);
    }

    public function submit(Request $request, string $business)
    {
        if (is_string($request->input('payload_json')) && trim((string) $request->input('payload_json')) !== '') {
            $decoded = json_decode((string) $request->input('payload_json'), true);
            if (! is_array($decoded)) {
                return response()->json([
                    'message' => 'Invalid payload_json.',
                ], 422);
            }
            $request->merge($decoded);
        }

        $validated = $request->validate([
            'mode' => ['sometimes', 'nullable', 'string', 'in:ungrouped,grouped,warranty'],
            'gdprAccepted' => ['sometimes', 'nullable', 'boolean'],
            'jobDetails' => ['required', 'string', 'max:5000'],

            'warranty' => ['sometimes', 'array'],
            'warranty.dateOfPurchase' => ['sometimes', 'nullable', 'date'],

            'customer' => ['required', 'array'],
            'customer.firstName' => ['required', 'string', 'max:255'],
            'customer.lastName' => ['required', 'string', 'max:255'],
            'customer.userEmail' => ['required', 'email', 'max:255'],
            'customer.phone' => ['sometimes', 'nullable', 'string', 'max:64'],
            'customer.company' => ['sometimes', 'nullable', 'string', 'max:255'],
            'customer.taxId' => ['sometimes', 'nullable', 'string', 'max:64'],
            'customer.addressLine1' => ['sometimes', 'nullable', 'string', 'max:255'],
            'customer.addressLine2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'customer.city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'customer.state' => ['sometimes', 'nullable', 'string', 'max:255'],
            'customer.postalCode' => ['sometimes', 'nullable', 'string', 'max:64'],
            'customer.country' => ['sometimes', 'nullable', 'string', 'size:2'],

            'devices' => ['required', 'array', 'min:1', 'max:10'],
            'devices.*.device_id' => ['sometimes', 'nullable', 'integer'],
            'devices.*.device_label' => ['sometimes', 'nullable', 'string', 'max:255'],
            'devices.*.serial' => ['sometimes', 'nullable', 'string', 'max:255'],
            'devices.*.pin' => ['sometimes', 'nullable', 'string', 'max:255'],
            'devices.*.notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'devices.*.extra_fields' => ['sometimes', 'array', 'max:50'],
            'devices.*.extra_fields.*.key' => ['sometimes', 'nullable', 'string', 'max:255'],
            'devices.*.extra_fields.*.label' => ['sometimes', 'nullable', 'string', 'max:255'],
            'devices.*.extra_fields.*.value_text' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'devices.*.services' => ['sometimes', 'array', 'max:50'],
            'devices.*.services.*.service_id' => ['required_with:devices.*.services', 'integer'],
            'devices.*.services.*.qty' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:9999'],
            'devices.*.other_service' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $svc = app(RepairBuddyPublicBookingService::class);
        $payload = $svc->submit($request, $business, $validated);

        return response()->json($payload, 201);
    }

    protected function renderBookingTemplate(string $template, array $pairs): string
    {
        return str_replace(array_keys($pairs), array_values($pairs), $template);
    }

    protected function resolveInitialJobStatusSlug(): string
    {
        $preferred = ['neworder', 'new'];

        foreach ($preferred as $slug) {
            $exists = Status::query()
                ->where('status_type', 'Job')
                ->where('code', $slug)
                ->exists();
            if ($exists) {
                return $slug;
            }
        }

        $first = Status::query()
            ->where('status_type', 'Job')
            ->orderBy('id')
            ->value('code');
        if (is_string($first) && trim((string) $first) !== '') {
            return trim((string) $first);
        }

        return 'neworder';
    }

    protected function generateCaseNumber(string $tenantSlug, string $branchCode, array $generalSettings): string
    {
        $tenantSlug = trim($tenantSlug);
        $branchCode = trim($branchCode);

        $prefix = is_string($generalSettings['caseNumberPrefix'] ?? null) ? trim((string) $generalSettings['caseNumberPrefix']) : '';
        if ($prefix === '') {
            $prefix = 'RB';
        }

        $digits = is_numeric($generalSettings['caseNumberLength'] ?? null) ? (int) $generalSettings['caseNumberLength'] : 0;
        if ($digits <= 0) {
            $digits = is_numeric($this->branch()->rb_case_digits) ? (int) $this->branch()->rb_case_digits : 6;
        }
        $digits = max(1, min(32, $digits));

        $counter = RepairBuddyCaseCounter::query()->lockForUpdate()->first();
        if (! $counter) {
            try {
                RepairBuddyCaseCounter::query()->create([
                    'next_number' => 1,
                ]);
            } catch (QueryException $e) {
                // ignore duplicate key and re-select
            }

            $counter = RepairBuddyCaseCounter::query()->lockForUpdate()->first();
        }

        if (! $counter) {
            throw new \RuntimeException('Case counter not found.');
        }

        $next = is_numeric($counter?->next_number) ? (int) $counter->next_number : 1;
        $counter->forceFill([
            'next_number' => $next + 1,
        ])->save();

        $num = str_pad((string) $next, $digits, '0', STR_PAD_LEFT);

        $parts = array_values(array_filter([
            $prefix,
            $tenantSlug,
            $branchCode,
            $num,
        ], fn ($p) => is_string($p) && trim($p) !== ''));

        $case = implode('-', $parts);

        if (strlen($case) > 64) {
            $case = implode('-', array_values(array_filter([$prefix, $branchCode, $num])));
        }

        return $case;
    }

    protected function attachDevicesAndItemsToJob(RepairBuddyJob $job, array $devicesPayload): void
    {
        foreach ($devicesPayload as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $deviceId = array_key_exists('device_id', $entry) && is_numeric($entry['device_id']) ? (int) $entry['device_id'] : null;
            $label = is_string($entry['device_label'] ?? null) ? trim((string) $entry['device_label']) : '';

            $device = null;
            if ($deviceId) {
                $device = RepairBuddyDevice::query()->whereKey($deviceId)->first();
                if (! $device) {
                    throw ValidationException::withMessages([
                        'devices' => ['Device is invalid.'],
                    ]);
                }
                $label = (string) $device->model;
            }

            if ($label === '') {
                throw ValidationException::withMessages([
                    'devices' => ['Device label is required.'],
                ]);
            }

            $serial = is_string($entry['serial'] ?? null) ? trim((string) $entry['serial']) : null;
            $pin = is_string($entry['pin'] ?? null) ? trim((string) $entry['pin']) : null;
            $notes = is_string($entry['notes'] ?? null) ? trim((string) $entry['notes']) : null;

            $cd = RepairBuddyCustomerDevice::query()->create([
                'customer_id' => (int) $job->customer_id,
                'device_id' => $device?->id,
                'label' => $label,
                'serial' => $serial !== '' ? $serial : null,
                'pin' => $pin !== '' ? $pin : null,
                'notes' => $notes !== '' ? $notes : null,
            ]);

            RepairBuddyJobDevice::query()->create([
                'job_id' => $job->id,
                'customer_device_id' => $cd->id,
                'label_snapshot' => $cd->label,
                'serial_snapshot' => $cd->serial,
                'pin_snapshot' => $cd->pin,
                'notes_snapshot' => $cd->notes,
                'extra_fields_snapshot_json' => [],
            ]);

            $serviceEntries = is_array($entry['services'] ?? null) ? $entry['services'] : [];
            $otherService = is_string($entry['other_service'] ?? null) ? trim((string) $entry['other_service']) : '';

            if (count($serviceEntries) === 0 && $otherService === '') {
                throw ValidationException::withMessages([
                    'devices' => ['Service selection is required for each device.'],
                ]);
            }

            foreach ($serviceEntries as $svcEntry) {
                if (! is_array($svcEntry) || ! array_key_exists('service_id', $svcEntry) || ! is_numeric($svcEntry['service_id'])) {
                    continue;
                }

                $serviceId = (int) $svcEntry['service_id'];
                $qty = array_key_exists('qty', $svcEntry) && is_numeric($svcEntry['qty']) ? (int) $svcEntry['qty'] : 1;
                $qty = max(1, min(9999, $qty));

                $service = RepairBuddyService::query()->whereKey($serviceId)->first();
                if (! $service || ! $service->is_active) {
                    throw ValidationException::withMessages([
                        'devices' => ['Service is invalid.'],
                    ]);
                }

                $contextBrandId = $device && is_numeric($device->device_brand_id) ? (int) $device->device_brand_id : null;
                $contextTypeId = $device && is_numeric($device->device_type_id) ? (int) $device->device_type_id : null;

                if (! $this->isServiceAvailableForContext($service->id, $device?->id, $contextBrandId, $contextTypeId)) {
                    throw ValidationException::withMessages([
                        'devices' => ['A selected service is not available for the selected device.'],
                    ]);
                }

                [$unitCents, $currency, $taxId] = $this->resolveServiceUnitPriceAndTax($service, $device?->id, $contextBrandId, $contextTypeId);

                RepairBuddyJobItem::query()->create([
                    'job_id' => $job->id,
                    'item_type' => 'service',
                    'ref_id' => $service->id,
                    'name_snapshot' => (string) $service->name,
                    'qty' => $qty,
                    'unit_price_amount_cents' => $unitCents,
                    'unit_price_currency' => $currency,
                    'tax_id' => $taxId,
                    'meta_json' => [
                        'device_id' => $device?->id,
                    ],
                ]);
            }

            if ($otherService !== '') {
                RepairBuddyJobItem::query()->create([
                    'job_id' => $job->id,
                    'item_type' => 'fee',
                    'ref_id' => null,
                    'name_snapshot' => $otherService,
                    'qty' => 1,
                    'unit_price_amount_cents' => 0,
                    'unit_price_currency' => (string) ($this->tenant()->currency ?? 'USD'),
                    'tax_id' => null,
                    'meta_json' => [
                        'device_id' => $device?->id,
                        'other_service' => true,
                    ],
                ]);
            }
        }
    }

    protected function attachDevicesAndItemsToEstimate(RepairBuddyEstimate $estimate, array $devicesPayload): void
    {
        foreach ($devicesPayload as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $deviceId = array_key_exists('device_id', $entry) && is_numeric($entry['device_id']) ? (int) $entry['device_id'] : null;
            $label = is_string($entry['device_label'] ?? null) ? trim((string) $entry['device_label']) : '';

            $device = null;
            if ($deviceId) {
                $device = RepairBuddyDevice::query()->whereKey($deviceId)->first();
                if (! $device) {
                    throw ValidationException::withMessages([
                        'devices' => ['Device is invalid.'],
                    ]);
                }
                $label = (string) $device->model;
            }

            if ($label === '') {
                throw ValidationException::withMessages([
                    'devices' => ['Device label is required.'],
                ]);
            }

            $serial = is_string($entry['serial'] ?? null) ? trim((string) $entry['serial']) : null;
            $pin = is_string($entry['pin'] ?? null) ? trim((string) $entry['pin']) : null;
            $notes = is_string($entry['notes'] ?? null) ? trim((string) $entry['notes']) : null;

            $cd = RepairBuddyCustomerDevice::query()->create([
                'customer_id' => (int) $estimate->customer_id,
                'device_id' => $device?->id,
                'label' => $label,
                'serial' => $serial !== '' ? $serial : null,
                'pin' => $pin !== '' ? $pin : null,
                'notes' => $notes !== '' ? $notes : null,
            ]);

            RepairBuddyEstimateDevice::query()->create([
                'estimate_id' => $estimate->id,
                'customer_device_id' => $cd->id,
                'label_snapshot' => $cd->label,
                'serial_snapshot' => $cd->serial,
                'pin_snapshot' => $cd->pin,
                'notes_snapshot' => $cd->notes,
                'extra_fields_snapshot_json' => [],
            ]);

            $serviceEntries = is_array($entry['services'] ?? null) ? $entry['services'] : [];
            $otherService = is_string($entry['other_service'] ?? null) ? trim((string) $entry['other_service']) : '';

            if (count($serviceEntries) === 0 && $otherService === '') {
                throw ValidationException::withMessages([
                    'devices' => ['Service selection is required for each device.'],
                ]);
            }

            foreach ($serviceEntries as $svcEntry) {
                if (! is_array($svcEntry) || ! array_key_exists('service_id', $svcEntry) || ! is_numeric($svcEntry['service_id'])) {
                    continue;
                }

                $serviceId = (int) $svcEntry['service_id'];
                $qty = array_key_exists('qty', $svcEntry) && is_numeric($svcEntry['qty']) ? (int) $svcEntry['qty'] : 1;
                $qty = max(1, min(9999, $qty));

                $service = RepairBuddyService::query()->whereKey($serviceId)->first();
                if (! $service || ! $service->is_active) {
                    throw ValidationException::withMessages([
                        'devices' => ['Service is invalid.'],
                    ]);
                }

                $contextBrandId = $device && is_numeric($device->device_brand_id) ? (int) $device->device_brand_id : null;
                $contextTypeId = $device && is_numeric($device->device_type_id) ? (int) $device->device_type_id : null;

                if (! $this->isServiceAvailableForContext($service->id, $device?->id, $contextBrandId, $contextTypeId)) {
                    throw ValidationException::withMessages([
                        'devices' => ['A selected service is not available for the selected device.'],
                    ]);
                }

                [$unitCents, $currency, $taxId] = $this->resolveServiceUnitPriceAndTax($service, $device?->id, $contextBrandId, $contextTypeId);

                RepairBuddyEstimateItem::query()->create([
                    'estimate_id' => $estimate->id,
                    'item_type' => 'service',
                    'ref_id' => $service->id,
                    'name_snapshot' => (string) $service->name,
                    'qty' => $qty,
                    'unit_price_amount_cents' => $unitCents,
                    'unit_price_currency' => $currency,
                    'tax_id' => $taxId,
                    'meta_json' => [
                        'device_id' => $device?->id,
                    ],
                ]);
            }

            if ($otherService !== '') {
                RepairBuddyEstimateItem::query()->create([
                    'estimate_id' => $estimate->id,
                    'item_type' => 'fee',
                    'ref_id' => null,
                    'name_snapshot' => $otherService,
                    'qty' => 1,
                    'unit_price_amount_cents' => 0,
                    'unit_price_currency' => (string) ($this->tenant()->currency ?? 'USD'),
                    'tax_id' => null,
                    'meta_json' => [
                        'device_id' => $device?->id,
                        'other_service' => true,
                    ],
                ]);
            }
        }
    }

    protected function isServiceAvailableForContext(int $serviceId, ?int $deviceId, ?int $brandId, ?int $typeId): bool
    {
        if ($deviceId) {
            $row = RepairBuddyServiceAvailabilityOverride::query()
                ->where('service_id', $serviceId)
                ->where('scope_type', 'device')
                ->where('scope_ref_id', $deviceId)
                ->orderByDesc('id')
                ->first();
            if ($row) {
                return (string) $row->status === 'active';
            }
        }

        if ($brandId) {
            $row = RepairBuddyServiceAvailabilityOverride::query()
                ->where('service_id', $serviceId)
                ->where('scope_type', 'brand')
                ->where('scope_ref_id', $brandId)
                ->orderByDesc('id')
                ->first();
            if ($row) {
                return (string) $row->status === 'active';
            }
        }

        if ($typeId) {
            $row = RepairBuddyServiceAvailabilityOverride::query()
                ->where('service_id', $serviceId)
                ->where('scope_type', 'type')
                ->where('scope_ref_id', $typeId)
                ->orderByDesc('id')
                ->first();
            if ($row) {
                return (string) $row->status === 'active';
            }
        }

        return true;
    }

    protected function resolveServiceUnitPriceAndTax(RepairBuddyService $service, ?int $deviceId, ?int $brandId, ?int $typeId): array
    {
        $override = null;

        if ($deviceId) {
            $override = RepairBuddyServicePriceOverride::query()
                ->where('service_id', $service->id)
                ->where('scope_type', 'device')
                ->where('scope_ref_id', $deviceId)
                ->where('is_active', true)
                ->orderByDesc('id')
                ->first();
        }

        if (! $override && $brandId) {
            $override = RepairBuddyServicePriceOverride::query()
                ->where('service_id', $service->id)
                ->where('scope_type', 'brand')
                ->where('scope_ref_id', $brandId)
                ->where('is_active', true)
                ->orderByDesc('id')
                ->first();
        }

        if (! $override && $typeId) {
            $override = RepairBuddyServicePriceOverride::query()
                ->where('service_id', $service->id)
                ->where('scope_type', 'type')
                ->where('scope_ref_id', $typeId)
                ->where('is_active', true)
                ->orderByDesc('id')
                ->first();
        }

        $unitCents = $override && is_numeric($override->price_amount_cents)
            ? (int) $override->price_amount_cents
            : (is_numeric($service->base_price_amount_cents) ? (int) $service->base_price_amount_cents : 0);

        $currency = $override && is_string($override->price_currency) && $override->price_currency !== ''
            ? strtoupper((string) $override->price_currency)
            : (is_string($service->base_price_currency) && $service->base_price_currency !== '' ? strtoupper((string) $service->base_price_currency) : (string) ($this->tenant()->currency ?? 'USD'));

        $taxId = $override && is_numeric($override->tax_id)
            ? (int) $override->tax_id
            : (is_numeric($service->tax_id) ? (int) $service->tax_id : null);

        return [$unitCents, $currency, $taxId];
    }

    protected function attachUploadsToJob(RepairBuddyJob $job, mixed $uploaded): void
    {
        $files = [];

        if ($uploaded instanceof UploadedFile) {
            $files = [$uploaded];
        } elseif (is_array($uploaded)) {
            $files = array_values(array_filter($uploaded, fn ($f) => $f instanceof UploadedFile));
        }

        $files = array_slice($files, 0, 5);

        foreach ($files as $file) {
            $disk = 'public';
            $path = $file->store('rb/jobs/'.$job->id.'/attachments', $disk);
            $url = Storage::disk($disk)->url($path);

            RepairBuddyJobAttachment::query()->create([
                'job_id' => $job->id,
                'uploader_user_id' => null,
                'visibility' => 'public',
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size_bytes' => $file->getSize() ?? 0,
                'storage_disk' => $disk,
                'storage_path' => $path,
                'url' => $url,
            ]);
        }
    }

    protected function attachUploadsToEstimate(RepairBuddyEstimate $estimate, mixed $uploaded): void
    {
        $files = [];

        if ($uploaded instanceof UploadedFile) {
            $files = [$uploaded];
        } elseif (is_array($uploaded)) {
            $files = array_values(array_filter($uploaded, fn ($f) => $f instanceof UploadedFile));
        }

        $files = array_slice($files, 0, 5);

        foreach ($files as $file) {
            $disk = 'public';
            $path = $file->store('rb/estimates/'.$estimate->id.'/attachments', $disk);
            $url = Storage::disk($disk)->url($path);

            RepairBuddyEstimateAttachment::query()->create([
                'estimate_id' => $estimate->id,
                'uploader_user_id' => null,
                'visibility' => 'public',
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size_bytes' => $file->getSize() ?? 0,
                'storage_disk' => $disk,
                'storage_path' => $path,
                'url' => $url,
            ]);
        }
    }
}
