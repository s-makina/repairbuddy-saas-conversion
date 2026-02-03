<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyCustomerDevice;
use App\Models\RepairBuddyCustomerDeviceFieldValue;
use App\Models\RepairBuddyDevice;
use App\Models\RepairBuddyDeviceFieldDefinition;
use App\Models\RepairBuddyEstimate;
use App\Models\RepairBuddyEstimateDevice;
use App\Models\RepairBuddyEstimateItem;
use App\Models\RepairBuddyEstimateToken;
use App\Models\RepairBuddyEvent;
use App\Models\User;
use App\Notifications\EstimateToCustomerNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RepairBuddyEstimateController extends Controller
{
    public function index(Request $request, string $business)
    {
        $validated = $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
            'limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $q = is_string($validated['q'] ?? null) ? trim((string) $validated['q']) : '';
        $limit = is_int($validated['limit'] ?? null) ? (int) $validated['limit'] : 100;

        $query = RepairBuddyEstimate::query()
            ->with(['customer'])
            ->orderBy('id', 'desc');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('case_number', 'like', "%{$q}%")
                    ->orWhere('title', 'like', "%{$q}%")
                    ->orWhere('id', $q);
            });
        }

        $estimates = $query->limit($limit)->get();

        return response()->json([
            'estimates' => $estimates->map(fn (RepairBuddyEstimate $e) => $this->serializeEstimate($e, includeItems: false, includeDevices: false)),
        ]);
    }

    public function show(Request $request, string $business, $estimateId)
    {
        if (! is_numeric($estimateId)) {
            return response()->json([
                'message' => 'Estimate not found.',
            ], 404);
        }

        $estimate = RepairBuddyEstimate::query()
            ->with(['customer'])
            ->whereKey((int) $estimateId)
            ->first();

        if (! $estimate) {
            return response()->json([
                'message' => 'Estimate not found.',
            ], 404);
        }

        return response()->json([
            'estimate' => $this->serializeEstimate($estimate, includeItems: true, includeDevices: true),
        ]);
    }

    public function store(Request $request, string $business)
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
            'case_number' => ['sometimes', 'nullable', 'string', 'max:64'],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'nullable', 'string', 'max:32'],

            'customer_id' => ['sometimes', 'nullable', 'integer'],
            'pickup_date' => ['sometimes', 'nullable', 'date'],
            'delivery_date' => ['sometimes', 'nullable', 'date'],
            'case_detail' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'assigned_technician_id' => ['sometimes', 'nullable', 'integer'],

            'customer_create' => ['sometimes', 'array'],
            'customer_create.name' => ['required_with:customer_create', 'string', 'max:255'],
            'customer_create.email' => ['required_with:customer_create', 'email', 'max:255'],
            'customer_create.phone' => ['sometimes', 'nullable', 'string', 'max:64'],
            'customer_create.company' => ['sometimes', 'nullable', 'string', 'max:255'],
            'customer_create.tax_id' => ['sometimes', 'nullable', 'string', 'max:64'],
            'customer_create.address_line1' => ['sometimes', 'nullable', 'string', 'max:255'],
            'customer_create.address_line2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'customer_create.address_city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'customer_create.address_state' => ['sometimes', 'nullable', 'string', 'max:255'],
            'customer_create.address_postal_code' => ['sometimes', 'nullable', 'string', 'max:64'],
            'customer_create.address_country' => ['sometimes', 'nullable', 'string', 'size:2'],

            'estimate_devices' => ['sometimes', 'array'],
            'estimate_devices.*.customer_device_id' => ['required', 'integer'],
            'estimate_devices.*.serial' => ['sometimes', 'nullable', 'string', 'max:255'],
            'estimate_devices.*.pin' => ['sometimes', 'nullable', 'string', 'max:255'],
            'estimate_devices.*.notes' => ['sometimes', 'nullable', 'string', 'max:5000'],

            'devices' => ['sometimes', 'array'],
            'devices.*.device_id' => ['required', 'integer'],
            'devices.*.serial' => ['sometimes', 'nullable', 'string', 'max:255'],
            'devices.*.pin' => ['sometimes', 'nullable', 'string', 'max:255'],
            'devices.*.notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ]);

        $requestedCaseNumber = is_string($validated['case_number'] ?? null) ? trim((string) $validated['case_number']) : '';
        $caseNumber = $requestedCaseNumber !== '' ? $requestedCaseNumber : $this->generateCaseNumber();

        $caseNumberExists = RepairBuddyEstimate::query()->where('case_number', $caseNumber)->exists();
        if ($caseNumberExists) {
            return response()->json([
                'message' => 'Case number is already in use.',
            ], 422);
        }

        $title = is_string($validated['title'] ?? null) ? trim((string) $validated['title']) : '';
        if ($title === '') {
            $title = $caseNumber;
        }

        $status = is_string($validated['status'] ?? null) && $validated['status'] !== '' ? (string) $validated['status'] : 'pending';
        if (! in_array($status, ['pending', 'approved', 'rejected'], true)) {
            return response()->json([
                'message' => 'Estimate status is invalid.',
            ], 422);
        }

        $customerId = array_key_exists('customer_id', $validated) && is_numeric($validated['customer_id'])
            ? (int) $validated['customer_id']
            : null;

        $shouldCreateCustomer = array_key_exists('customer_create', $validated) && is_array($validated['customer_create']);

        $assignedTechnicianId = array_key_exists('assigned_technician_id', $validated) && is_numeric($validated['assigned_technician_id'])
            ? (int) $validated['assigned_technician_id']
            : null;

        if ($assignedTechnicianId) {
            $technicianExists = User::query()
                ->where('tenant_id', $this->tenantId())
                ->where('is_admin', false)
                ->whereKey($assignedTechnicianId)
                ->exists();

            if (! $technicianExists) {
                return response()->json([
                    'message' => 'Assigned technician is invalid.',
                ], 422);
            }
        }

        $estimateDevicesPayload = [];
        if (array_key_exists('estimate_devices', $validated) && is_array($validated['estimate_devices'])) {
            foreach ($validated['estimate_devices'] as $item) {
                if (! is_array($item)) {
                    continue;
                }

                if (! array_key_exists('customer_device_id', $item) || ! is_numeric($item['customer_device_id'])) {
                    continue;
                }

                $estimateDevicesPayload[] = [
                    'customer_device_id' => (int) $item['customer_device_id'],
                    'serial' => array_key_exists('serial', $item) && is_string($item['serial']) ? trim((string) $item['serial']) : null,
                    'pin' => array_key_exists('pin', $item) && is_string($item['pin']) ? trim((string) $item['pin']) : null,
                    'notes' => array_key_exists('notes', $item) && is_string($item['notes']) ? trim((string) $item['notes']) : null,
                ];
            }

            $estimateDevicesPayload = collect($estimateDevicesPayload)
                ->unique('customer_device_id')
                ->values()
                ->all();
        }

        $devicesPayload = [];
        if (array_key_exists('devices', $validated) && is_array($validated['devices'])) {
            foreach ($validated['devices'] as $entry) {
                if (! is_array($entry)) {
                    continue;
                }

                if (! array_key_exists('device_id', $entry) || ! is_numeric($entry['device_id'])) {
                    continue;
                }

                $devicesPayload[] = [
                    'device_id' => (int) $entry['device_id'],
                    'serial' => array_key_exists('serial', $entry) && is_string($entry['serial']) ? trim((string) $entry['serial']) : null,
                    'pin' => array_key_exists('pin', $entry) && is_string($entry['pin']) ? trim((string) $entry['pin']) : null,
                    'notes' => array_key_exists('notes', $entry) && is_string($entry['notes']) ? trim((string) $entry['notes']) : null,
                ];
            }

            $devicesPayload = collect($devicesPayload)
                ->values()
                ->all();
        }

        if (count($estimateDevicesPayload) > 0 || count($devicesPayload) > 0) {
            if (! $customerId && ! $shouldCreateCustomer) {
                return response()->json([
                    'message' => 'Customer is required to attach devices.',
                ], 422);
            }
        }

        $tenantId = $this->tenantId();
        $branchId = $this->branchId();

        $createdCustomer = null;
        $createdCustomerOneTimePassword = null;

        $estimate = DB::transaction(function () use ($assignedTechnicianId, $branchId, $caseNumber, $customerId, $devicesPayload, $estimateDevicesPayload, $request, $shouldCreateCustomer, $status, $tenantId, $title, $validated, &$createdCustomer, &$createdCustomerOneTimePassword) {
            if ($shouldCreateCustomer) {
                $cc = $validated['customer_create'];

                $email = trim((string) ($cc['email'] ?? ''));
                if ($email === '') {
                    throw ValidationException::withMessages([
                        'customer_create.email' => ['Email is required.'],
                    ]);
                }

                $emailExists = User::query()->where('email', $email)->exists();
                if ($emailExists) {
                    throw ValidationException::withMessages([
                        'customer_create.email' => ['Email is already in use.'],
                    ]);
                }

                $oneTimePassword = Str::password(16);
                $oneTimePasswordExpiresAt = now()->addMinutes(60 * 24);

                $customer = User::query()->create([
                    'tenant_id' => $tenantId,
                    'is_admin' => false,
                    'role' => 'customer',
                    'role_id' => null,
                    'status' => 'active',

                    'name' => trim((string) ($cc['name'] ?? '')),
                    'email' => $email,
                    'phone' => array_key_exists('phone', $cc) && is_string($cc['phone']) ? trim((string) $cc['phone']) : null,
                    'company' => array_key_exists('company', $cc) && is_string($cc['company']) ? trim((string) $cc['company']) : null,
                    'tax_id' => array_key_exists('tax_id', $cc) && is_string($cc['tax_id']) ? trim((string) $cc['tax_id']) : null,
                    'address_line1' => array_key_exists('address_line1', $cc) && is_string($cc['address_line1']) ? trim((string) $cc['address_line1']) : null,
                    'address_line2' => array_key_exists('address_line2', $cc) && is_string($cc['address_line2']) ? trim((string) $cc['address_line2']) : null,
                    'address_city' => array_key_exists('address_city', $cc) && is_string($cc['address_city']) ? trim((string) $cc['address_city']) : null,
                    'address_state' => array_key_exists('address_state', $cc) && is_string($cc['address_state']) ? trim((string) $cc['address_state']) : null,
                    'address_postal_code' => array_key_exists('address_postal_code', $cc) && is_string($cc['address_postal_code']) ? trim((string) $cc['address_postal_code']) : null,
                    'address_country' => array_key_exists('address_country', $cc) && is_string($cc['address_country']) ? strtoupper(trim((string) $cc['address_country'])) : null,

                    'password' => Hash::make(Str::random(72)),
                    'must_change_password' => true,
                    'one_time_password_hash' => Hash::make($oneTimePassword),
                    'one_time_password_expires_at' => $oneTimePasswordExpiresAt,
                    'one_time_password_used_at' => null,
                    'email_verified_at' => now(),
                ]);

                $customerId = (int) $customer->id;

                $createdCustomer = $customer;
                $createdCustomerOneTimePassword = $oneTimePassword;
            }

            $estimate = RepairBuddyEstimate::query()->create([
                'case_number' => $caseNumber,
                'title' => $title,
                'status' => $status,
                'customer_id' => $customerId,
                'created_by' => $request->user()?->id,
                'pickup_date' => $validated['pickup_date'] ?? null,
                'delivery_date' => $validated['delivery_date'] ?? null,
                'case_detail' => $validated['case_detail'] ?? null,
                'assigned_technician_id' => $assignedTechnicianId,
                'approved_at' => $status === 'approved' ? now() : null,
                'rejected_at' => $status === 'rejected' ? now() : null,
            ]);

            if (count($estimateDevicesPayload) > 0) {
                $this->attachCustomerDevicesFromPayload($estimate, $estimateDevicesPayload);
            }

            if (count($devicesPayload) > 0) {
                $this->attachCatalogDevicesAsCustomerDevices($estimate, $devicesPayload);
            }

            RepairBuddyEvent::query()->create([
                'actor_user_id' => $request->user()?->id,
                'entity_type' => 'estimate',
                'entity_id' => $estimate->id,
                'visibility' => 'private',
                'event_type' => 'estimate.created',
                'payload_json' => [
                    'title' => 'Estimate created',
                    'case_number' => $estimate->case_number,
                ],
            ]);

            return $estimate;
        });

        if ($createdCustomer instanceof User && is_string($createdCustomerOneTimePassword) && $createdCustomerOneTimePassword !== '') {
            try {
                $createdCustomer->notify(new \App\Notifications\OneTimePasswordNotification($createdCustomerOneTimePassword, 60 * 24));
            } catch (\Throwable $e) {
                Log::error('customer.onetime_password_notification_failed', [
                    'user_id' => $createdCustomer->id,
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $estimate->load(['customer']);

        return response()->json([
            'estimate' => $this->serializeEstimate($estimate, includeItems: true, includeDevices: true),
        ], 201);
    }

    public function update(Request $request, string $business, $estimateId)
    {
        if (! is_numeric($estimateId)) {
            return response()->json([
                'message' => 'Estimate not found.',
            ], 404);
        }

        $estimate = RepairBuddyEstimate::query()->whereKey((int) $estimateId)->first();

        if (! $estimate) {
            return response()->json([
                'message' => 'Estimate not found.',
            ], 404);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'status' => ['sometimes', 'nullable', 'string', 'max:32'],
            'customer_id' => ['sometimes', 'nullable', 'integer'],
            'pickup_date' => ['sometimes', 'nullable', 'date'],
            'delivery_date' => ['sometimes', 'nullable', 'date'],
            'case_detail' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'assigned_technician_id' => ['sometimes', 'nullable', 'integer'],
        ]);

        if (array_key_exists('assigned_technician_id', $validated) && is_numeric($validated['assigned_technician_id'])) {
            $assignedTechnicianId = (int) $validated['assigned_technician_id'];
            $technicianExists = User::query()
                ->where('tenant_id', $this->tenantId())
                ->where('is_admin', false)
                ->whereKey($assignedTechnicianId)
                ->exists();

            if (! $technicianExists) {
                return response()->json([
                    'message' => 'Assigned technician is invalid.',
                ], 422);
            }
        }

        if (array_key_exists('status', $validated) && is_string($validated['status']) && $validated['status'] !== '') {
            if (! in_array((string) $validated['status'], ['pending', 'approved', 'rejected'], true)) {
                return response()->json([
                    'message' => 'Estimate status is invalid.',
                ], 422);
            }
        }

        $estimate->forceFill([
            'title' => array_key_exists('title', $validated) ? $validated['title'] : $estimate->title,
            'status' => array_key_exists('status', $validated) && $validated['status'] ? (string) $validated['status'] : $estimate->status,
            'customer_id' => array_key_exists('customer_id', $validated) ? $validated['customer_id'] : $estimate->customer_id,
            'pickup_date' => array_key_exists('pickup_date', $validated) ? $validated['pickup_date'] : $estimate->pickup_date,
            'delivery_date' => array_key_exists('delivery_date', $validated) ? $validated['delivery_date'] : $estimate->delivery_date,
            'case_detail' => array_key_exists('case_detail', $validated) ? $validated['case_detail'] : $estimate->case_detail,
            'assigned_technician_id' => array_key_exists('assigned_technician_id', $validated) ? $validated['assigned_technician_id'] : $estimate->assigned_technician_id,
        ]);

        if (array_key_exists('status', $validated) && is_string($validated['status']) && $validated['status'] !== '') {
            $next = (string) $validated['status'];
            if ($next === 'approved' && $estimate->approved_at === null) {
                $estimate->approved_at = now();
                $estimate->rejected_at = null;
            }
            if ($next === 'rejected' && $estimate->rejected_at === null) {
                $estimate->rejected_at = now();
                $estimate->approved_at = null;
            }
            if ($next === 'pending') {
                $estimate->approved_at = null;
                $estimate->rejected_at = null;
            }
        }

        $estimate->save();

        return response()->json([
            'estimate' => $this->serializeEstimate($estimate->fresh(['customer']), includeItems: true, includeDevices: true),
        ]);
    }

    public function send(Request $request, string $business, $estimateId)
    {
        if (! is_numeric($estimateId)) {
            return response()->json([
                'message' => 'Estimate not found.',
            ], 404);
        }

        $estimate = RepairBuddyEstimate::query()->with(['customer'])->whereKey((int) $estimateId)->first();

        if (! $estimate) {
            return response()->json([
                'message' => 'Estimate not found.',
            ], 404);
        }

        $customer = $estimate->customer;
        if (! $customer instanceof User || ! is_string($customer->email) || trim((string) $customer->email) === '') {
            return response()->json([
                'message' => 'Customer email is required to send estimate.',
            ], 422);
        }

        $tenant = $this->tenant();
        $settings = data_get($tenant->setup_state ?? [], 'repairbuddy_settings');
        if (! is_array($settings)) {
            $settings = [];
        }

        $general = is_array($settings['general'] ?? null) ? $settings['general'] : [];
        $estimatesSettings = is_array($settings['estimates'] ?? null) ? $settings['estimates'] : [];

        $disableEstimates = (bool) ($estimatesSettings['disableEstimates'] ?? false);
        if ($disableEstimates) {
            return response()->json([
                'message' => 'Estimates are disabled in settings.',
            ], 422);
        }

        $attachPdf = (bool) ($general['attachPdf'] ?? false);

        $subject = is_string($estimatesSettings['customerEmailSubject'] ?? null) ? (string) $estimatesSettings['customerEmailSubject'] : '';
        $body = is_string($estimatesSettings['customerEmailBody'] ?? null) ? (string) $estimatesSettings['customerEmailBody'] : '';

        if (trim($subject) === '') {
            $subject = 'Your estimate is ready';
        }

        if (trim($body) === '') {
            $body = "Hello,\n\nYour estimate is ready.\n\nCase: {case_number}\n";
        }

        $subject = $this->renderTemplate($subject, $estimate);
        $body = $this->renderTemplate($body, $estimate);

        $tokenApprove = $this->createToken($estimate, 'approve');
        $tokenReject = $this->createToken($estimate, 'reject');

        $frontendBase = rtrim((string) env('FRONTEND_URL', (string) env('APP_URL', '')), '/');

        $approveUrl = $frontendBase.'/t/'.$business.'/status?caseNumber='.urlencode((string) $estimate->case_number).'&estimateAction=approve&token='.urlencode($tokenApprove);
        $rejectUrl = $frontendBase.'/t/'.$business.'/status?caseNumber='.urlencode((string) $estimate->case_number).'&estimateAction=reject&token='.urlencode($tokenReject);

        $pdfPath = null;
        if ($attachPdf) {
            try {
                $pdfPath = $this->generatePdfToTempPath($estimate);
            } catch (\Throwable $e) {
                Log::error('estimate.pdf_generation_failed', [
                    'estimate_id' => $estimate->id,
                    'error' => $e->getMessage(),
                ]);
                $pdfPath = null;
            }
        }

        try {
            $customer->notify(new EstimateToCustomerNotification(
                estimate: $estimate,
                subject: $subject,
                body: $body,
                approveUrl: $approveUrl,
                rejectUrl: $rejectUrl,
                attachPdf: $attachPdf,
                pdfPath: $pdfPath,
            ));
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to send email.',
            ], 500);
        }

        $estimate->forceFill([
            'sent_at' => now(),
        ])->save();

        RepairBuddyEvent::query()->create([
            'actor_user_id' => $request->user()?->id,
            'entity_type' => 'estimate',
            'entity_id' => $estimate->id,
            'visibility' => 'private',
            'event_type' => 'estimate.sent',
            'payload_json' => [
                'title' => 'Estimate sent',
                'to' => $customer->email,
            ],
        ]);

        return response()->json([
            'message' => 'Sent.',
            'estimate' => $this->serializeEstimate($estimate->fresh(['customer']), includeItems: true, includeDevices: true),
        ]);
    }

    private function attachCustomerDevicesFromPayload(RepairBuddyEstimate $estimate, array $estimateDevicesPayload): void
    {
        if (! $estimate->customer_id) {
            throw ValidationException::withMessages([
                'estimate_devices' => ['Customer is required to attach devices.'],
            ]);
        }

        $deviceIds = collect($estimateDevicesPayload)
            ->map(fn ($d) => (int) ($d['customer_device_id'] ?? 0))
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();

        $customerDevices = RepairBuddyCustomerDevice::query()
            ->where('customer_id', (int) $estimate->customer_id)
            ->whereIn('id', $deviceIds)
            ->get()
            ->keyBy('id');

        if (count($customerDevices) !== count($deviceIds)) {
            throw ValidationException::withMessages([
                'estimate_devices' => ['Customer device is invalid.'],
            ]);
        }

        $definitions = RepairBuddyDeviceFieldDefinition::query()
            ->where('is_active', true)
            ->orderBy('id', 'asc')
            ->get();

        foreach ($estimateDevicesPayload as $entry) {
            $customerDeviceId = (int) ($entry['customer_device_id'] ?? 0);
            $cd = $customerDevices->get($customerDeviceId);
            if (! $cd) {
                continue;
            }

            $existing = RepairBuddyEstimateDevice::query()
                ->where('estimate_id', $estimate->id)
                ->where('customer_device_id', $cd->id)
                ->first();
            if ($existing) {
                continue;
            }

            $values = RepairBuddyCustomerDeviceFieldValue::query()
                ->where('customer_device_id', $cd->id)
                ->get()
                ->keyBy('field_definition_id');

            $extraFieldsSnapshot = [];
            foreach ($definitions as $def) {
                $value = $values->get($def->id);
                if (! $value) {
                    continue;
                }
                $rawText = is_string($value->value_text) ? trim((string) $value->value_text) : '';
                if ($rawText === '') {
                    continue;
                }
                $extraFieldsSnapshot[] = [
                    'key' => $def->key,
                    'label' => $def->label,
                    'type' => $def->type,
                    'show_in_booking' => (bool) $def->show_in_booking,
                    'show_in_invoice' => (bool) $def->show_in_invoice,
                    'show_in_portal' => (bool) $def->show_in_portal,
                    'value_text' => $rawText,
                ];
            }

            $serialOverride = is_string($entry['serial'] ?? null) ? trim((string) $entry['serial']) : '';
            $pinOverride = is_string($entry['pin'] ?? null) ? trim((string) $entry['pin']) : '';
            $notesOverride = is_string($entry['notes'] ?? null) ? trim((string) $entry['notes']) : '';

            RepairBuddyEstimateDevice::query()->create([
                'estimate_id' => $estimate->id,
                'customer_device_id' => $cd->id,
                'label_snapshot' => $cd->label,
                'serial_snapshot' => $serialOverride !== '' ? $serialOverride : $cd->serial,
                'pin_snapshot' => $pinOverride !== '' ? $pinOverride : $cd->pin,
                'notes_snapshot' => $notesOverride !== '' ? $notesOverride : $cd->notes,
                'extra_fields_snapshot_json' => $extraFieldsSnapshot,
            ]);
        }
    }

    private function attachCatalogDevicesAsCustomerDevices(RepairBuddyEstimate $estimate, array $devicesPayload): void
    {
        if (! $estimate->customer_id) {
            throw ValidationException::withMessages([
                'devices' => ['Customer is required to attach devices.'],
            ]);
        }

        $deviceIds = collect($devicesPayload)
            ->map(fn ($d) => (int) ($d['device_id'] ?? 0))
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();

        $devices = RepairBuddyDevice::query()
            ->whereIn('id', $deviceIds)
            ->get()
            ->keyBy('id');

        if (count($devices) !== count(array_unique($deviceIds))) {
            throw ValidationException::withMessages([
                'devices' => ['Device is invalid.'],
            ]);
        }

        foreach ($devicesPayload as $entry) {
            $deviceId = (int) ($entry['device_id'] ?? 0);
            $device = $devices->get($deviceId);
            if (! $device) {
                continue;
            }

            $serial = is_string($entry['serial'] ?? null) ? trim((string) $entry['serial']) : '';
            $pin = is_string($entry['pin'] ?? null) ? trim((string) $entry['pin']) : '';
            $notes = is_string($entry['notes'] ?? null) ? trim((string) $entry['notes']) : '';

            $customerDevice = RepairBuddyCustomerDevice::query()->create([
                'customer_id' => (int) $estimate->customer_id,
                'device_id' => $device->id,
                'label' => $device->model,
                'serial' => $serial !== '' ? $serial : null,
                'pin' => $pin !== '' ? $pin : null,
                'notes' => $notes !== '' ? $notes : null,
            ]);

            $existing = RepairBuddyEstimateDevice::query()
                ->where('estimate_id', $estimate->id)
                ->where('customer_device_id', $customerDevice->id)
                ->first();
            if ($existing) {
                continue;
            }

            RepairBuddyEstimateDevice::query()->create([
                'estimate_id' => $estimate->id,
                'customer_device_id' => $customerDevice->id,
                'label_snapshot' => $customerDevice->label,
                'serial_snapshot' => $customerDevice->serial,
                'pin_snapshot' => $customerDevice->pin,
                'notes_snapshot' => $customerDevice->notes,
                'extra_fields_snapshot_json' => [],
            ]);
        }
    }

    private function generateCaseNumber(): string
    {
        $branch = $this->branch();
        $tenant = $this->tenant();

        $settings = data_get($tenant->setup_state ?? [], 'repairbuddy_settings');
        if (! is_array($settings)) {
            $settings = [];
        }

        $general = [];
        if (array_key_exists('general', $settings) && is_array($settings['general'])) {
            $general = $settings['general'];
        }

        $prefix = is_string($general['caseNumberPrefix'] ?? null) ? trim((string) $general['caseNumberPrefix']) : '';
        if ($prefix === '') {
            $prefix = is_string($branch->rb_case_prefix) ? trim((string) $branch->rb_case_prefix) : '';
        }
        if ($prefix === '') {
            $prefix = 'RB';
        }

        $length = is_numeric($general['caseNumberLength'] ?? null) ? (int) $general['caseNumberLength'] : 0;
        if ($length <= 0) {
            $length = is_numeric($branch->rb_case_digits) ? (int) $branch->rb_case_digits : 6;
        }
        $length = max(1, min(32, $length));

        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);

        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }

        return $prefix.$randomString.time();
    }

    private function serializeEstimate(RepairBuddyEstimate $estimate, bool $includeItems, bool $includeDevices): array
    {
        $customer = $estimate->customer;
        if ($customer instanceof User && (int) $customer->tenant_id !== (int) $this->tenantId()) {
            $customer = null;
        }

        $items = [];
        $subtotalCents = 0;
        $taxCents = 0;
        $currency = (string) ($this->tenant()->currency ?? 'USD');

        if ($includeItems) {
            $loaded = $estimate->relationLoaded('items') ? $estimate->items : $estimate->items()->with('tax')->orderBy('id', 'asc')->limit(5000)->get();

            foreach ($loaded as $item) {
                $qty = is_numeric($item->qty) ? (int) $item->qty : 0;
                $unit = is_numeric($item->unit_price_amount_cents) ? (int) $item->unit_price_amount_cents : 0;
                $lineSubtotal = $qty * $unit;

                $rate = $item->tax ? (float) $item->tax->rate : 0.0;
                $lineTax = (int) round($lineSubtotal * ($rate / 100.0));

                $subtotalCents += $lineSubtotal;
                $taxCents += $lineTax;

                if (is_string($item->unit_price_currency) && $item->unit_price_currency !== '') {
                    $currency = (string) $item->unit_price_currency;
                }

                $tax = null;
                if ($item->tax) {
                    $tax = [
                        'id' => $item->tax->id,
                        'name' => $item->tax->name,
                        'rate' => $item->tax->rate,
                        'is_default' => (bool) $item->tax->is_default,
                    ];
                }

                $items[] = [
                    'id' => $item->id,
                    'estimate_id' => $item->estimate_id,
                    'item_type' => $item->item_type,
                    'ref_id' => $item->ref_id,
                    'name' => $item->name_snapshot,
                    'qty' => $item->qty,
                    'unit_price' => [
                        'currency' => $item->unit_price_currency,
                        'amount_cents' => (int) $item->unit_price_amount_cents,
                    ],
                    'tax' => $tax,
                    'meta' => is_array($item->meta_json) ? $item->meta_json : null,
                    'created_at' => $item->created_at,
                ];
            }
        }

        $devices = [];
        if ($includeDevices) {
            $loadedDevices = $estimate->relationLoaded('devices') ? $estimate->devices : $estimate->devices()->orderBy('id', 'asc')->limit(200)->get();
            $devices = $loadedDevices->map(function ($d) {
                return [
                    'id' => $d->id,
                    'estimate_id' => $d->estimate_id,
                    'customer_device_id' => $d->customer_device_id,
                    'label' => $d->label_snapshot,
                    'serial' => $d->serial_snapshot,
                    'pin' => $d->pin_snapshot,
                    'notes' => $d->notes_snapshot,
                    'extra_fields' => is_array($d->extra_fields_snapshot_json) ? $d->extra_fields_snapshot_json : [],
                    'created_at' => $d->created_at,
                ];
            })->values()->all();
        }

        return [
            'id' => $estimate->id,
            'case_number' => $estimate->case_number,
            'title' => $estimate->title,
            'status' => $estimate->status,
            'customer_id' => $estimate->customer_id,
            'pickup_date' => $estimate->pickup_date,
            'delivery_date' => $estimate->delivery_date,
            'case_detail' => $estimate->case_detail,
            'assigned_technician_id' => $estimate->assigned_technician_id,
            'sent_at' => $estimate->sent_at,
            'approved_at' => $estimate->approved_at,
            'rejected_at' => $estimate->rejected_at,
            'converted_job_id' => $estimate->converted_job_id,
            'customer' => $customer instanceof User ? [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'company' => $customer->company,
            ] : null,
            'created_at' => $estimate->created_at,
            'updated_at' => $estimate->updated_at,
            'devices' => $devices,
            'items' => $items,
            'totals' => [
                'currency' => $currency,
                'subtotal_cents' => $subtotalCents,
                'tax_cents' => $taxCents,
                'total_cents' => $subtotalCents + $taxCents,
            ],
        ];
    }

    private function renderTemplate(string $template, RepairBuddyEstimate $estimate): string
    {
        $tenant = $this->tenant();

        $pairs = [
            '{case_number}' => (string) $estimate->case_number,
            '{estimate_id}' => (string) $estimate->id,
            '{business_name}' => is_string($tenant->name) ? (string) $tenant->name : '',
        ];

        return str_replace(array_keys($pairs), array_values($pairs), $template);
    }

    private function createToken(RepairBuddyEstimate $estimate, string $purpose): string
    {
        $token = Str::random(64);
        $hash = hash('sha256', $token);

        RepairBuddyEstimateToken::query()->create([
            'estimate_id' => $estimate->id,
            'purpose' => $purpose,
            'token_hash' => $hash,
            'expires_at' => now()->addDays(30),
            'used_at' => null,
        ]);

        return $token;
    }

    private function generatePdfToTempPath(RepairBuddyEstimate $estimate): string
    {
        $estimate = $estimate->fresh()->load(['customer', 'items.tax']);

        $escape = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $itemsHtml = '';
        $currency = (string) ($this->tenant()->currency ?? 'USD');
        $subtotal = 0;
        $tax = 0;

        foreach ($estimate->items as $item) {
            $qty = is_numeric($item->qty) ? (int) $item->qty : 0;
            $unit = is_numeric($item->unit_price_amount_cents) ? (int) $item->unit_price_amount_cents : 0;
            $lineSubtotal = $qty * $unit;
            $rate = $item->tax ? (float) $item->tax->rate : 0.0;
            $lineTax = (int) round($lineSubtotal * ($rate / 100.0));

            $subtotal += $lineSubtotal;
            $tax += $lineTax;

            if (is_string($item->unit_price_currency) && $item->unit_price_currency !== '') {
                $currency = (string) $item->unit_price_currency;
            }

            $itemsHtml .= '<tr>'
                . '<td>' . $escape($item->name_snapshot) . '</td>'
                . '<td style="text-align:right">' . $escape($qty) . '</td>'
                . '<td style="text-align:right">' . $escape($unit) . '</td>'
                . '<td style="text-align:right">' . $escape($lineSubtotal) . '</td>'
                . '</tr>';
        }

        $html = '<!doctype html><html><head><meta charset="utf-8">'
            . '<style>'
            . 'body{font-family:DejaVu Sans, sans-serif; font-size:12px; color:#111;}'
            . 'h1{font-size:16px; margin:0 0 10px 0;}'
            . 'table{width:100%; border-collapse:collapse; margin-top:10px;}'
            . 'th,td{border:1px solid #ddd; padding:6px; vertical-align:top;}'
            . 'th{background:#f5f5f5; font-weight:700;}'
            . '</style>'
            . '</head><body>'
            . '<h1>Estimate ' . $escape($estimate->case_number) . '</h1>'
            . '<div>Customer: ' . $escape($estimate->customer?->name ?? '') . '</div>'
            . '<table><thead><tr><th>Item</th><th style="text-align:right">Qty</th><th style="text-align:right">Unit (cents)</th><th style="text-align:right">Line (cents)</th></tr></thead><tbody>'
            . $itemsHtml
            . '</tbody></table>'
            . '<table style="margin-top:10px"><tbody>'
            . '<tr><td style="text-align:right">Subtotal</td><td style="text-align:right">' . $escape($subtotal) . ' ' . $escape($currency) . '</td></tr>'
            . '<tr><td style="text-align:right">Tax</td><td style="text-align:right">' . $escape($tax) . ' ' . $escape($currency) . '</td></tr>'
            . '<tr><td style="text-align:right"><strong>Total</strong></td><td style="text-align:right"><strong>' . $escape($subtotal + $tax) . ' ' . $escape($currency) . '</strong></td></tr>'
            . '</tbody></table>'
            . '</body></html>';

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');

        $disk = 'local';
        $dir = 'rb/tmp';
        Storage::disk($disk)->makeDirectory($dir);

        $rel = $dir.'/estimate-'.$estimate->id.'-'.Str::random(8).'.pdf';
        Storage::disk($disk)->put($rel, $pdf->output());

        return storage_path('app/'.$rel);
    }
}
