<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyCaseCounter;
use App\Models\RepairBuddyCustomerDevice;
use App\Models\RepairBuddyCustomerDeviceFieldValue;
use App\Models\RepairBuddyDevice;
use App\Models\RepairBuddyEvent;
use App\Models\RepairBuddyDeviceFieldDefinition;
use App\Models\RepairBuddyJob;
use App\Models\RepairBuddyJobAttachment;
use App\Models\RepairBuddyJobDevice;
use App\Models\RepairBuddyJobItem;
use App\Models\RepairBuddyJobStatus;
use App\Models\User;
use App\Notifications\OneTimePasswordNotification;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RepairBuddyJobController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
            'limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $q = is_string($validated['q'] ?? null) ? trim((string) $validated['q']) : '';
        $limit = is_int($validated['limit'] ?? null) ? (int) $validated['limit'] : 100;

        $query = RepairBuddyJob::query()
            ->with(['customer', 'technicians'])
            ->orderBy('id', 'desc');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('case_number', 'like', "%{$q}%")
                    ->orWhere('title', 'like', "%{$q}%")
                    ->orWhere('id', $q);
            });
        }

        $jobs = $query->limit($limit)->get();

        return response()->json([
            'jobs' => $jobs->map(fn (RepairBuddyJob $j) => $this->serializeJob($j, includeTimeline: false)),
        ]);
    }

    public function show(Request $request, string $business, $jobId)
    {
        if (! is_numeric($jobId)) {
            return response()->json([
                'message' => 'Job not found.',
            ], 404);
        }

        $job = RepairBuddyJob::query()
            ->with(['customer', 'technicians'])
            ->whereKey((int) $jobId)
            ->first();

        if (! $job) {
            return response()->json([
                'message' => 'Job not found.',
            ], 404);
        }

        return response()->json([
            'job' => $this->serializeJob($job, includeTimeline: true),
        ]);
    }

    public function store(Request $request, string $business)
    {
        // Support JSON bodies and multipart bodies (FormData) by allowing a JSON payload wrapper.
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
            'status_slug' => ['sometimes', 'nullable', 'string', 'max:64'],
            'payment_status_slug' => ['sometimes', 'nullable', 'string', 'max:64'],
            'priority' => ['sometimes', 'nullable', 'string', 'max:32'],
            'customer_id' => ['sometimes', 'nullable', 'integer'],
            'plugin_device_post_id' => ['sometimes', 'nullable', 'integer'],
            'plugin_device_id_text' => ['sometimes', 'nullable', 'string', 'max:255'],
            'pickup_date' => ['sometimes', 'nullable', 'date'],
            'delivery_date' => ['sometimes', 'nullable', 'date'],
            'next_service_date' => ['sometimes', 'nullable', 'date'],
            'case_detail' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'assigned_technician_id' => ['sometimes', 'nullable', 'integer'],
            'assigned_technician_ids' => ['sometimes', 'array'],
            'assigned_technician_ids.*' => ['integer'],
            'job_devices' => ['sometimes', 'array'],
            'job_devices.*.customer_device_id' => ['required', 'integer'],
            'job_devices.*.serial' => ['sometimes', 'nullable', 'string', 'max:255'],
            'job_devices.*.pin' => ['sometimes', 'nullable', 'string', 'max:255'],
            'job_devices.*.notes' => ['sometimes', 'nullable', 'string', 'max:5000'],

            // Plugin WP-admin parity: multiple devices added as an array of catalog devices.
            'devices' => ['sometimes', 'array'],
            'devices.*.device_id' => ['required', 'integer'],
            'devices.*.serial' => ['sometimes', 'nullable', 'string', 'max:255'],
            'devices.*.pin' => ['sometimes', 'nullable', 'string', 'max:255'],
            'devices.*.notes' => ['sometimes', 'nullable', 'string', 'max:5000'],

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

            // Plugin parity fields (stored as public-facing history/events + attachment storage).
            'wc_order_note' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'wc_job_file' => ['sometimes', 'nullable', 'integer'],
        ]);

        $statusSlug = is_string($validated['status_slug'] ?? null) && $validated['status_slug'] !== ''
            ? $validated['status_slug']
            : 'neworder';

        $statusExists = RepairBuddyJobStatus::query()->where('slug', $statusSlug)->exists();
        if (! $statusExists && $statusSlug === 'neworder') {
            $statusSlug = 'new';
            $statusExists = RepairBuddyJobStatus::query()->where('slug', $statusSlug)->exists();
        }

        if (! $statusExists) {
            $statusSlug = RepairBuddyJobStatus::query()->orderBy('id')->value('slug');
            $statusSlug = is_string($statusSlug) && $statusSlug !== '' ? $statusSlug : null;
        }

        if (! is_string($statusSlug) || $statusSlug === '') {
            return response()->json([
                'message' => 'Job status is invalid.',
            ], 422);
        }

        $requestedCaseNumber = is_string($validated['case_number'] ?? null) ? trim((string) $validated['case_number']) : '';
        $caseNumber = $requestedCaseNumber !== '' ? $requestedCaseNumber : $this->generateCaseNumber();

        $title = is_string($validated['title'] ?? null) ? trim((string) $validated['title']) : '';
        if ($title === '') {
            $title = $caseNumber;
        }

        $customerId = array_key_exists('customer_id', $validated) && is_numeric($validated['customer_id'])
            ? (int) $validated['customer_id']
            : null;

        $pluginDevicePostId = array_key_exists('plugin_device_post_id', $validated) && is_numeric($validated['plugin_device_post_id'])
            ? (int) $validated['plugin_device_post_id']
            : null;
        $pluginDeviceIdText = array_key_exists('plugin_device_id_text', $validated) && is_string($validated['plugin_device_id_text'])
            ? trim((string) $validated['plugin_device_id_text'])
            : null;

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

        $shouldCreateCustomer = array_key_exists('customer_create', $validated) && is_array($validated['customer_create']);

        // Admin create-job parity: customer/device/details are optional at creation time.

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

        $assignedTechnicianIds = [];
        if (array_key_exists('assigned_technician_ids', $validated) && is_array($validated['assigned_technician_ids'])) {
            $assignedTechnicianIds = array_values(array_unique(array_map('intval', $validated['assigned_technician_ids'])));
        }

        $jobDevicesPayload = [];
        if (array_key_exists('job_devices', $validated) && is_array($validated['job_devices'])) {
            foreach ($validated['job_devices'] as $item) {
                if (! is_array($item)) {
                    continue;
                }

                if (! array_key_exists('customer_device_id', $item) || ! is_numeric($item['customer_device_id'])) {
                    continue;
                }

                $jobDevicesPayload[] = [
                    'customer_device_id' => (int) $item['customer_device_id'],
                    'serial' => array_key_exists('serial', $item) && is_string($item['serial']) ? trim((string) $item['serial']) : null,
                    'pin' => array_key_exists('pin', $item) && is_string($item['pin']) ? trim((string) $item['pin']) : null,
                    'notes' => array_key_exists('notes', $item) && is_string($item['notes']) ? trim((string) $item['notes']) : null,
                ];
            }

            $jobDevicesPayload = collect($jobDevicesPayload)
                ->unique('customer_device_id')
                ->values()
                ->all();
        }

        if (count($jobDevicesPayload) > 0 || count($devicesPayload) > 0) {
            if (! $customerId && ! $shouldCreateCustomer) {
                return response()->json([
                    'message' => 'Customer is required to attach devices.',
                ], 422);
            }

            // Defer validation until after customer_create is processed
            $deviceIds = collect($jobDevicesPayload)
                ->map(fn ($d) => (int) ($d['customer_device_id'] ?? 0))
                ->filter(fn ($id) => $id > 0)
                ->values()
                ->all();
        }

        if (count($assignedTechnicianIds) > 0) {
            $validTechnicianIds = User::query()
                ->where('tenant_id', $this->tenantId())
                ->where('is_admin', false)
                ->whereIn('id', $assignedTechnicianIds)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            sort($assignedTechnicianIds);
            sort($validTechnicianIds);

            if ($assignedTechnicianIds !== $validTechnicianIds) {
                return response()->json([
                    'message' => 'Assigned technicians are invalid.',
                ], 422);
            }
        }

        $caseNumberExists = RepairBuddyJob::query()->where('case_number', $caseNumber)->exists();
        if ($caseNumberExists) {
            return response()->json([
                'message' => 'Case number is already in use.',
            ], 422);
        }

        $tenantId = $this->tenantId();
        $branchId = $this->branchId();

        $createdCustomer = null;
        $createdCustomerOneTimePassword = null;

        $job = DB::transaction(function () use ($branchId, $caseNumber, $request, $statusSlug, $tenantId, $validated, $assignedTechnicianId, $assignedTechnicianIds, $jobDevicesPayload, $devicesPayload, $customerId, $shouldCreateCustomer, $pluginDevicePostId, $pluginDeviceIdText, &$createdCustomer, &$createdCustomerOneTimePassword, $title) {
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

            $job = RepairBuddyJob::query()->create([
                'case_number' => $caseNumber,
                'title' => $title,
                'status_slug' => $statusSlug,
                'payment_status_slug' => $validated['payment_status_slug'] ?? null,
                'priority' => $validated['priority'] ?? null,
                'customer_id' => $customerId,
                'created_by' => $request->user()?->id,
                'opened_at' => now(),
                'pickup_date' => $validated['pickup_date'] ?? null,
                'delivery_date' => $validated['delivery_date'] ?? null,
                'next_service_date' => $validated['next_service_date'] ?? null,
                'case_detail' => $validated['case_detail'] ?? null,
                'assigned_technician_id' => $assignedTechnicianId,
                'plugin_device_post_id' => $pluginDevicePostId,
                'plugin_device_id_text' => $pluginDeviceIdText,
            ]);

            // If the legacy single-device fields are empty, set them from the first device entry
            // (helps keep older UI/reporting in sync while we support true multi-device).
            if ((! $pluginDevicePostId || $pluginDevicePostId <= 0) && count($devicesPayload) > 0) {
                $first = $devicesPayload[0];
                $firstDeviceId = is_numeric($first['device_id'] ?? null) ? (int) $first['device_id'] : null;
                $firstSerial = is_string($first['serial'] ?? null) ? trim((string) $first['serial']) : '';

                $job->forceFill([
                    'plugin_device_post_id' => $firstDeviceId,
                    'plugin_device_id_text' => $firstSerial !== '' ? $firstSerial : null,
                ])->save();
            }

            if (count($assignedTechnicianIds) > 0) {
                $sync = [];
                foreach ($assignedTechnicianIds as $id) {
                    $sync[$id] = [
                        'tenant_id' => $tenantId,
                        'branch_id' => $branchId,
                    ];
                }

                $job->technicians()->sync($sync);
            }

            if (count($jobDevicesPayload) > 0) {
                $deviceIds = collect($jobDevicesPayload)
                    ->map(fn ($d) => (int) ($d['customer_device_id'] ?? 0))
                    ->filter(fn ($id) => $id > 0)
                    ->values()
                    ->all();

                $customerDevices = RepairBuddyCustomerDevice::query()
                    ->where('customer_id', (int) $job->customer_id)
                    ->whereIn('id', $deviceIds)
                    ->get()
                    ->keyBy('id');

                if (count($customerDevices) !== count($deviceIds)) {
                    throw ValidationException::withMessages([
                        'job_devices' => ['Customer device is invalid.'],
                    ]);
                }

                $definitions = RepairBuddyDeviceFieldDefinition::query()
                    ->where('is_active', true)
                    ->orderBy('id', 'asc')
                    ->get();

                foreach ($jobDevicesPayload as $entry) {
                    $customerDeviceId = (int) $entry['customer_device_id'];
                    $cd = $customerDevices->get($customerDeviceId);
                    if (! $cd) {
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

                    RepairBuddyJobDevice::query()->create([
                        'job_id' => $job->id,
                        'customer_device_id' => $cd->id,
                        'label_snapshot' => $cd->label,
                        'serial_snapshot' => $serialOverride !== '' ? $serialOverride : $cd->serial,
                        'pin_snapshot' => $pinOverride !== '' ? $pinOverride : $cd->pin,
                        'notes_snapshot' => $notesOverride !== '' ? $notesOverride : $cd->notes,
                        'extra_fields_snapshot_json' => $extraFieldsSnapshot,
                    ]);
                }
            }

            // Admin parity: allow adding multiple devices based on the catalog device list.
            // We create rb_customer_devices entries (per customer) and attach them to the job.
            if (count($devicesPayload) > 0) {
                if (! $job->customer_id) {
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
                        'customer_id' => (int) $job->customer_id,
                        'device_id' => $device->id,
                        'label' => $device->model,
                        'serial' => $serial !== '' ? $serial : null,
                        'pin' => $pin !== '' ? $pin : null,
                        'notes' => $notes !== '' ? $notes : null,
                    ]);

                    RepairBuddyJobDevice::query()->create([
                        'job_id' => $job->id,
                        'customer_device_id' => $customerDevice->id,
                        'label_snapshot' => $customerDevice->label,
                        'serial_snapshot' => $customerDevice->serial,
                        'pin_snapshot' => $customerDevice->pin,
                        'notes_snapshot' => $customerDevice->notes,
                        'extra_fields_snapshot_json' => [],
                    ]);
                }
            }

            RepairBuddyEvent::query()->create([
                'actor_user_id' => $request->user()?->id,
                'entity_type' => 'job',
                'entity_id' => $job->id,
                'visibility' => 'private',
                'event_type' => 'job.created',
                'payload_json' => [
                    'title' => 'Job created',
                    'case_number' => $job->case_number,
                ],
            ]);

            $orderNote = is_string($validated['wc_order_note'] ?? null) ? trim((string) $validated['wc_order_note']) : '';
            if ($orderNote !== '') {
                RepairBuddyEvent::query()->create([
                    'actor_user_id' => $request->user()?->id,
                    'entity_type' => 'job',
                    'entity_id' => $job->id,
                    'visibility' => 'public',
                    'event_type' => 'order.note',
                    'payload_json' => [
                        'title' => 'Order note',
                        'message' => $orderNote,
                    ],
                ]);
            }

            $uploaded = $request->file('job_file');
            if ($uploaded instanceof UploadedFile) {
                $disk = 'public';
                $path = $uploaded->store('rb/jobs/'.$job->id.'/attachments', $disk);
                $url = Storage::disk($disk)->url($path);

                $attachment = RepairBuddyJobAttachment::query()->create([
                    'job_id' => $job->id,
                    'uploader_user_id' => $request->user()?->id,
                    'visibility' => 'public',
                    'original_filename' => $uploaded->getClientOriginalName(),
                    'mime_type' => $uploaded->getClientMimeType(),
                    'size_bytes' => $uploaded->getSize() ?? 0,
                    'storage_disk' => $disk,
                    'storage_path' => $path,
                    'url' => $url,
                ]);

                RepairBuddyEvent::query()->create([
                    'actor_user_id' => $request->user()?->id,
                    'entity_type' => 'job',
                    'entity_id' => $job->id,
                    'visibility' => 'public',
                    'event_type' => 'job.attachment',
                    'payload_json' => [
                        'title' => 'File attachment',
                        'attachment_id' => $attachment->id,
                        'url' => $url,
                        'filename' => $attachment->original_filename,
                    ],
                ]);
            }

            return $job;
        });

        if ($createdCustomer instanceof User && is_string($createdCustomerOneTimePassword) && $createdCustomerOneTimePassword !== '') {
            try {
                $createdCustomer->notify(new OneTimePasswordNotification($createdCustomerOneTimePassword, 60 * 24));
            } catch (\Throwable $e) {
                Log::error('customer.onetime_password_notification_failed', [
                    'user_id' => $createdCustomer->id,
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $job->load(['customer', 'technicians']);

        return response()->json([
            'job' => $this->serializeJob($job, includeTimeline: true),
        ], 201);
    }

    public function update(Request $request, string $business, $jobId)
    {
        if (! is_numeric($jobId)) {
            return response()->json([
                'message' => 'Job not found.',
            ], 404);
        }

        $job = RepairBuddyJob::query()->whereKey((int) $jobId)->first();

        if (! $job) {
            return response()->json([
                'message' => 'Job not found.',
            ], 404);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'status_slug' => ['sometimes', 'nullable', 'string', 'max:64'],
            'payment_status_slug' => ['sometimes', 'nullable', 'string', 'max:64'],
            'priority' => ['sometimes', 'nullable', 'string', 'max:32'],
            'customer_id' => ['sometimes', 'nullable', 'integer'],
            'pickup_date' => ['sometimes', 'nullable', 'date'],
            'delivery_date' => ['sometimes', 'nullable', 'date'],
            'next_service_date' => ['sometimes', 'nullable', 'date'],
            'case_detail' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'assigned_technician_id' => ['sometimes', 'nullable', 'integer'],
            'assigned_technician_ids' => ['sometimes', 'array'],
            'assigned_technician_ids.*' => ['integer'],
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

        if (array_key_exists('status_slug', $validated) && is_string($validated['status_slug']) && $validated['status_slug'] !== '') {
            $statusExists = RepairBuddyJobStatus::query()->where('slug', $validated['status_slug'])->exists();
            if (! $statusExists) {
                return response()->json([
                    'message' => 'Job status is invalid.',
                ], 422);
            }
        }

        if (array_key_exists('assigned_technician_ids', $validated) && is_array($validated['assigned_technician_ids'])) {
            $assignedTechnicianIds = array_values(array_unique(array_map('intval', $validated['assigned_technician_ids'])));

            if (count($assignedTechnicianIds) > 0) {
                $validTechnicianIds = User::query()
                    ->where('tenant_id', $this->tenantId())
                    ->where('is_admin', false)
                    ->whereIn('id', $assignedTechnicianIds)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                sort($assignedTechnicianIds);
                sort($validTechnicianIds);

                if ($assignedTechnicianIds !== $validTechnicianIds) {
                    return response()->json([
                        'message' => 'Assigned technicians are invalid.',
                    ], 422);
                }
            }

            $sync = [];
            foreach ($assignedTechnicianIds as $id) {
                $sync[$id] = [
                    'tenant_id' => $this->tenantId(),
                    'branch_id' => $this->branchId(),
                ];
            }

            $job->technicians()->sync($sync);
        }

        $job->forceFill([
            'title' => array_key_exists('title', $validated) ? $validated['title'] : $job->title,
            'status_slug' => array_key_exists('status_slug', $validated) ? ($validated['status_slug'] ?: null) : $job->status_slug,
            'payment_status_slug' => array_key_exists('payment_status_slug', $validated) ? $validated['payment_status_slug'] : $job->payment_status_slug,
            'priority' => array_key_exists('priority', $validated) ? $validated['priority'] : $job->priority,
            'customer_id' => array_key_exists('customer_id', $validated) ? $validated['customer_id'] : $job->customer_id,
            'pickup_date' => array_key_exists('pickup_date', $validated) ? $validated['pickup_date'] : $job->pickup_date,
            'delivery_date' => array_key_exists('delivery_date', $validated) ? $validated['delivery_date'] : $job->delivery_date,
            'next_service_date' => array_key_exists('next_service_date', $validated) ? $validated['next_service_date'] : $job->next_service_date,
            'case_detail' => array_key_exists('case_detail', $validated) ? $validated['case_detail'] : $job->case_detail,
            'assigned_technician_id' => array_key_exists('assigned_technician_id', $validated) ? $validated['assigned_technician_id'] : $job->assigned_technician_id,
        ])->save();

        return response()->json([
            'job' => $this->serializeJob($job->fresh()->load(['technicians'])),
        ]);
    }

    private function generateCaseNumber(): string
    {
        $branch = $this->branch();

        $prefix = is_string($branch->rb_case_prefix) ? trim((string) $branch->rb_case_prefix) : '';
        if ($prefix === '') {
            $prefix = 'WC_';
        }

        $length = is_numeric($branch->rb_case_digits) ? (int) $branch->rb_case_digits : 6;
        $length = max(1, min(32, $length));

        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);

        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }

        return $prefix.$randomString.time();
    }

    private function serializeJob(RepairBuddyJob $job, bool $includeTimeline = false): array
    {
        $customer = $job->customer;
        $technicians = $job->relationLoaded('technicians') ? $job->technicians : null;
        if ($customer instanceof User && (int) $customer->tenant_id !== (int) $this->tenantId()) {
            $customer = null;
        }

        $timeline = [];
        if ($includeTimeline) {
            $events = RepairBuddyEvent::query()
                ->where('entity_type', 'job')
                ->where('entity_id', $job->id)
                ->orderBy('created_at', 'desc')
                ->limit(200)
                ->get();

            $timeline = $events->map(function (RepairBuddyEvent $e) {
                $payload = is_array($e->payload_json) ? $e->payload_json : [];
                $title = is_string($payload['title'] ?? null) ? $payload['title'] : null;
                if (! $title) {
                    $title = match ((string) $e->event_type) {
                        'job.created' => 'Job created',
                        'note' => 'Internal note',
                        default => (string) $e->event_type,
                    };
                }

                $message = is_string($payload['message'] ?? null) ? $payload['message'] : null;

                return [
                    'id' => (string) $e->id,
                    'title' => $title,
                    'type' => (string) $e->event_type,
                    'message' => $message,
                    'created_at' => $e->created_at,
                ];
            })->all();
        }

        $attachments = RepairBuddyJobAttachment::query()
            ->where('job_id', $job->id)
            ->orderBy('id', 'asc')
            ->limit(200)
            ->get();

        $publicEvents = RepairBuddyEvent::query()
            ->where('entity_type', 'job')
            ->where('entity_id', $job->id)
            ->where('visibility', 'public')
            ->orderBy('created_at', 'asc')
            ->limit(500)
            ->get();

        $items = RepairBuddyJobItem::query()
            ->where('job_id', $job->id)
            ->with('tax')
            ->orderBy('id', 'asc')
            ->limit(5000)
            ->get();

        $subtotalCents = 0;
        $taxCents = 0;
        $currency = (string) ($this->tenant()->currency ?? 'USD');

        foreach ($items as $item) {
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
        }

        $serializedItems = $items->map(function (RepairBuddyJobItem $i) {
            $tax = null;
            if ($i->tax) {
                $tax = [
                    'id' => $i->tax->id,
                    'name' => $i->tax->name,
                    'rate' => $i->tax->rate,
                    'is_default' => (bool) $i->tax->is_default,
                ];
            }

            return [
                'id' => $i->id,
                'job_id' => $i->job_id,
                'item_type' => $i->item_type,
                'ref_id' => $i->ref_id,
                'name' => $i->name_snapshot,
                'qty' => $i->qty,
                'unit_price' => [
                    'currency' => $i->unit_price_currency,
                    'amount_cents' => (int) $i->unit_price_amount_cents,
                ],
                'tax' => $tax,
                'meta' => is_array($i->meta_json) ? $i->meta_json : null,
                'created_at' => $i->created_at,
            ];
        })->all();

        return [
            'id' => $job->id,
            'case_number' => $job->case_number,
            'plugin_device_post_id' => $job->plugin_device_post_id,
            'plugin_device_id_text' => $job->plugin_device_id_text,
            'title' => $job->title,
            'status' => $job->status_slug,
            'payment_status' => $job->payment_status_slug,
            'priority' => $job->priority,
            'customer_id' => $job->customer_id,
            'pickup_date' => $job->pickup_date,
            'delivery_date' => $job->delivery_date,
            'next_service_date' => $job->next_service_date,
            'case_detail' => $job->case_detail,
            'assigned_technician_id' => $job->assigned_technician_id,
            'assigned_technician_ids' => $technicians ? $technicians->map(fn (User $u) => $u->id)->values() : [],
            'assigned_technicians' => $technicians
                ? $technicians->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])->values()
                : [],
            'customer' => $customer instanceof User ? [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'company' => $customer->company,
            ] : null,
            'created_at' => $job->created_at,
            'updated_at' => $job->updated_at,
            'timeline' => $timeline,
            'items' => $serializedItems,
            'totals' => [
                'currency' => $currency,
                'subtotal_cents' => $subtotalCents,
                'tax_cents' => $taxCents,
                'total_cents' => $subtotalCents + $taxCents,
            ],
            'messages' => $publicEvents->map(function (RepairBuddyEvent $e) {
                $payload = is_array($e->payload_json) ? $e->payload_json : [];
                $message = is_string($payload['message'] ?? null) ? $payload['message'] : null;

                return [
                    'id' => (string) $e->id,
                    'author' => $e->actor_user_id ? 'staff' : 'customer',
                    'body' => $message ?? '',
                    'created_at' => $e->created_at,
                ];
            })->filter(function (array $row) {
                return is_string($row['body']) && trim($row['body']) !== '';
            })->values()->all(),
            'attachments' => $attachments->map(function (RepairBuddyJobAttachment $a) {
                return [
                    'id' => (string) $a->id,
                    'job_id' => (string) $a->job_id,
                    'filename' => $a->original_filename,
                    'mime_type' => $a->mime_type,
                    'size_bytes' => (int) $a->size_bytes,
                    'url' => $a->url,
                    'created_at' => $a->created_at,
                ];
            })->values()->all(),
        ];
    }
}
