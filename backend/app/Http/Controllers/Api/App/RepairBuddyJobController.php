<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyCaseCounter;
use App\Models\RepairBuddyCustomerDevice;
use App\Models\RepairBuddyCustomerDeviceFieldValue;
use App\Models\RepairBuddyEvent;
use App\Models\RepairBuddyDeviceFieldDefinition;
use App\Models\RepairBuddyJob;
use App\Models\RepairBuddyJobDevice;
use App\Models\RepairBuddyJobItem;
use App\Models\RepairBuddyJobStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

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
        $validated = $request->validate([
            'case_number' => ['sometimes', 'nullable', 'string', 'max:64'],
            'title' => ['required', 'string', 'max:255'],
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
            'job_devices' => ['sometimes', 'array'],
            'job_devices.*.customer_device_id' => ['required', 'integer'],
            'job_devices.*.serial' => ['sometimes', 'nullable', 'string', 'max:255'],
            'job_devices.*.notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ]);

        $statusSlug = is_string($validated['status_slug'] ?? null) && $validated['status_slug'] !== ''
            ? $validated['status_slug']
            : 'new_quote';

        $statusExists = RepairBuddyJobStatus::query()->where('slug', $statusSlug)->exists();
        if (! $statusExists) {
            return response()->json([
                'message' => 'Job status is invalid.',
            ], 422);
        }

        $requestedCaseNumber = is_string($validated['case_number'] ?? null) ? trim((string) $validated['case_number']) : '';
        $caseNumber = $requestedCaseNumber !== '' ? $requestedCaseNumber : $this->generateCaseNumber();

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
                    'notes' => array_key_exists('notes', $item) && is_string($item['notes']) ? trim((string) $item['notes']) : null,
                ];
            }

            $jobDevicesPayload = collect($jobDevicesPayload)
                ->unique('customer_device_id')
                ->values()
                ->all();
        }

        if (count($jobDevicesPayload) > 0) {
            if (! array_key_exists('customer_id', $validated) || ! is_numeric($validated['customer_id'])) {
                return response()->json([
                    'message' => 'Customer is required to attach devices.',
                ], 422);
            }

            $customerId = (int) $validated['customer_id'];
            $deviceIds = collect($jobDevicesPayload)
                ->map(fn ($d) => (int) ($d['customer_device_id'] ?? 0))
                ->filter(fn ($id) => $id > 0)
                ->values()
                ->all();

            $customerDevices = RepairBuddyCustomerDevice::query()
                ->where('customer_id', $customerId)
                ->whereIn('id', $deviceIds)
                ->get();

            if (count($customerDevices) !== count($deviceIds)) {
                return response()->json([
                    'message' => 'Customer device is invalid.',
                ], 422);
            }
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

        $job = DB::transaction(function () use ($branchId, $caseNumber, $request, $statusSlug, $tenantId, $validated, $assignedTechnicianId, $assignedTechnicianIds, $jobDevicesPayload) {
            $job = RepairBuddyJob::query()->create([
                'case_number' => $caseNumber,
                'title' => $validated['title'],
                'status_slug' => $statusSlug,
                'payment_status_slug' => $validated['payment_status_slug'] ?? null,
                'priority' => $validated['priority'] ?? null,
                'customer_id' => $validated['customer_id'] ?? null,
                'created_by' => $request->user()?->id,
                'opened_at' => now(),
                'pickup_date' => $validated['pickup_date'] ?? null,
                'delivery_date' => $validated['delivery_date'] ?? null,
                'next_service_date' => $validated['next_service_date'] ?? null,
                'case_detail' => $validated['case_detail'] ?? null,
                'assigned_technician_id' => $assignedTechnicianId,
            ]);

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
                    $notesOverride = is_string($entry['notes'] ?? null) ? trim((string) $entry['notes']) : '';

                    RepairBuddyJobDevice::query()->create([
                        'job_id' => $job->id,
                        'customer_device_id' => $cd->id,
                        'label_snapshot' => $cd->label,
                        'serial_snapshot' => $serialOverride !== '' ? $serialOverride : $cd->serial,
                        'pin_snapshot' => $cd->pin,
                        'notes_snapshot' => $notesOverride !== '' ? $notesOverride : $cd->notes,
                        'extra_fields_snapshot_json' => $extraFieldsSnapshot,
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

            return $job;
        });

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

        $prefix = is_string($branch->rb_case_prefix) ? trim($branch->rb_case_prefix) : '';
        $digits = is_numeric($branch->rb_case_digits) ? (int) $branch->rb_case_digits : 6;
        $digits = max(1, min(12, $digits));

        $issuedNumber = DB::transaction(function () {
            $counter = RepairBuddyCaseCounter::query()->lockForUpdate()->first();

            if (! $counter) {
                $counter = RepairBuddyCaseCounter::query()->create([
                    'next_number' => 1,
                ]);
                $counter = RepairBuddyCaseCounter::query()->lockForUpdate()->first();
            }

            $n = (int) ($counter->next_number ?? 1);

            $counter->forceFill([
                'next_number' => $n + 1,
            ])->save();

            return $n;
        });

        $numberPart = str_pad((string) $issuedNumber, $digits, '0', STR_PAD_LEFT);

        if ($prefix === '') {
            return $numberPart;
        }

        return $prefix.'-'.$numberPart;
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
            'messages' => [],
            'attachments' => [],
        ];
    }
}
