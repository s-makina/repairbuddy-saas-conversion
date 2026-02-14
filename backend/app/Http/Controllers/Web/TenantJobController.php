<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\RepairBuddyEvent;
use App\Models\RepairBuddyJob;
use App\Models\RepairBuddyJobAttachment;
use App\Models\RepairBuddyCustomerDevice;
use App\Models\RepairBuddyCustomerDeviceFieldValue;
use App\Models\RepairBuddyDeviceFieldDefinition;
use App\Models\RepairBuddyJobDevice;
use App\Models\RepairBuddyJobCounter;
use App\Models\RepairBuddyJobExtraItem;
use App\Models\RepairBuddyJobItem;
use App\Models\RepairBuddyPaymentStatus;
use App\Models\Status;
use App\Support\TenantContext;
use App\Support\BranchContext;
use App\Support\RepairBuddyCaseNumberService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TenantJobController extends Controller
{
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

        $paymentStatuses = RepairBuddyPaymentStatus::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        $customers = \App\Models\User::query()
            ->where('tenant_id', (int) $tenant->id)
            ->where('role', 'customer')
            ->orderBy('name')
            ->limit(500)
            ->get();

        $technicians = \App\Models\User::query()
            ->where('tenant_id', (int) $tenant->id)
            ->whereIn('role', ['technician', 'store_manager', 'administrator'])
            ->orderBy('name')
            ->limit(500)
            ->get();

        $customerDevices = RepairBuddyCustomerDevice::query()
            ->with(['customer'])
            ->orderBy('id', 'desc')
            ->limit(500)
            ->get();

        return view('tenant.job_create', [
            'tenant' => $tenant,
            'user' => $user,
            'activeNav' => 'jobs',
            'pageTitle' => 'New Job',
            'suggestedCaseNumber' => null,
            'jobStatuses' => $jobStatuses,
            'paymentStatuses' => $paymentStatuses,
            'customers' => $customers,
            'technicians' => $technicians,
            'customerDevices' => $customerDevices,
        ]);
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

        $settings = data_get($tenant->setup_state ?? [], 'repairbuddy_settings');
        $general = is_array($settings) ? (array) data_get($settings, 'general', []) : [];

        $caseNumber = is_string($validated['case_number'] ?? null) ? trim((string) $validated['case_number']) : '';
        if ($caseNumber === '') {
            $caseService = new RepairBuddyCaseNumberService();
            $caseNumber = DB::transaction(function () use ($caseService, $tenant, $branch, $general) {
                return $caseService->nextCaseNumber($tenant, $branch, $general);
            });
        }

        $title = is_string($validated['title'] ?? null) ? trim((string) $validated['title']) : '';
        if ($title === '') {
            $title = $caseNumber;
        }

        $taxes = is_array($settings) ? (array) data_get($settings, 'taxes', []) : [];
        $tenantInvoiceAmounts = is_string(data_get($taxes, 'invoiceAmounts')) ? trim((string) data_get($taxes, 'invoiceAmounts')) : '';

        $statusSlug = is_string($validated['status_slug'] ?? null) && trim((string) $validated['status_slug']) !== ''
            ? trim((string) $validated['status_slug'])
            : 'neworder';

        $statusExists = Status::query()
            ->where('status_type', 'Job')
            ->where('code', $statusSlug)
            ->exists();
        if (! $statusExists && $statusSlug === 'new') {
            $statusSlug = 'neworder';
            $statusExists = Status::query()
                ->where('status_type', 'Job')
                ->where('code', $statusSlug)
                ->exists();
        }
        if (! $statusExists) {
            $first = Status::query()
                ->where('status_type', 'Job')
                ->orderBy('id')
                ->value('code');
            $first = is_string($first) ? trim((string) $first) : '';
            $statusSlug = $first !== '' ? $first : 'neworder';
        }

        $paymentStatusSlug = is_string($validated['payment_status_slug'] ?? null) && trim((string) $validated['payment_status_slug']) !== ''
            ? trim((string) $validated['payment_status_slug'])
            : 'nostatus';

        $priority = is_string($validated['priority'] ?? null) && trim((string) $validated['priority']) !== ''
            ? trim((string) $validated['priority'])
            : 'normal';

        $pricesIncluExclu = is_string($validated['prices_inclu_exclu'] ?? null) && trim((string) $validated['prices_inclu_exclu']) !== ''
            ? trim((string) $validated['prices_inclu_exclu'])
            : ($tenantInvoiceAmounts === 'inclusive' || $tenantInvoiceAmounts === 'exclusive' ? $tenantInvoiceAmounts : null);

        $canReviewIt = array_key_exists('can_review_it', $validated) ? (bool) $validated['can_review_it'] : true;

        $job = DB::transaction(function () use ($request, $validated, $tenant, $branch, $user, $caseNumber, $title, $statusSlug, $paymentStatusSlug, $priority, $pricesIncluExclu, $canReviewIt) {
            $jobNumber = $this->nextJobNumber((int) $tenant->id, (int) $branch->id);

            $job = RepairBuddyJob::query()->create([
                'tenant_id' => (int) $tenant->id,
                'branch_id' => (int) $branch->id,
                'job_number' => $jobNumber,
                'case_number' => $caseNumber,
                'title' => $title,
                'status_slug' => $statusSlug,
                'payment_status_slug' => $paymentStatusSlug,
                'prices_inclu_exclu' => $pricesIncluExclu,
                'priority' => $priority,
                'can_review_it' => $canReviewIt,
                'customer_id' => array_key_exists('customer_id', $validated) && is_numeric($validated['customer_id']) ? (int) $validated['customer_id'] : null,
                'created_by' => $user->id,
                'opened_at' => now(),
                'pickup_date' => $validated['pickup_date'] ?? null,
                'delivery_date' => $validated['delivery_date'] ?? null,
                'next_service_date' => $validated['next_service_date'] ?? null,
                'case_detail' => $validated['case_detail'] ?? null,
            ]);

            $techIds = array_key_exists('technician_ids', $validated) && is_array($validated['technician_ids'])
                ? array_values(array_unique(array_map('intval', $validated['technician_ids'])))
                : [];

            if (count($techIds) > 0) {
                $job->technicians()->sync($techIds);
            }

            $deviceIds = is_array($validated['job_device_customer_device_id'] ?? null) ? $validated['job_device_customer_device_id'] : [];
            $serials = is_array($validated['job_device_serial'] ?? null) ? $validated['job_device_serial'] : [];
            $pins = is_array($validated['job_device_pin'] ?? null) ? $validated['job_device_pin'] : [];
            $notes = is_array($validated['job_device_notes'] ?? null) ? $validated['job_device_notes'] : [];

            $rows = max(count($deviceIds), count($serials), count($pins), count($notes));
            $customerDeviceIds = [];
            for ($i = 0; $i < $rows; $i++) {
                $id = is_numeric($deviceIds[$i] ?? null) ? (int) $deviceIds[$i] : 0;
                if ($id > 0) {
                    $customerDeviceIds[] = $id;
                }
            }
            $customerDeviceIds = array_values(array_unique($customerDeviceIds));

            if (count($customerDeviceIds) > 0) {
                $devices = RepairBuddyCustomerDevice::query()->whereIn('id', $customerDeviceIds)->get()->keyBy('id');
                if (count($devices) !== count($customerDeviceIds)) {
                    throw ValidationException::withMessages([
                        'job_device_customer_device_id' => ['Customer device is invalid.'],
                    ]);
                }

                $definitions = RepairBuddyDeviceFieldDefinition::query()
                    ->where('is_active', true)
                    ->orderBy('id', 'asc')
                    ->get();

                for ($i = 0; $i < $rows; $i++) {
                    $id = is_numeric($deviceIds[$i] ?? null) ? (int) $deviceIds[$i] : 0;
                    if ($id <= 0) {
                        continue;
                    }
                    $cd = $devices->get($id);
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

                    $serialOverride = is_string($serials[$i] ?? null) ? trim((string) $serials[$i]) : '';
                    $pinOverride = is_string($pins[$i] ?? null) ? trim((string) $pins[$i]) : '';
                    $notesOverride = is_string($notes[$i] ?? null) ? trim((string) $notes[$i]) : '';

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

            $types = is_array($validated['item_type'] ?? null) ? $validated['item_type'] : [];
            $names = is_array($validated['item_name'] ?? null) ? $validated['item_name'] : [];
            $qtys = is_array($validated['item_qty'] ?? null) ? $validated['item_qty'] : [];
            $prices = is_array($validated['item_unit_price_cents'] ?? null) ? $validated['item_unit_price_cents'] : [];

            $max = max(count($types), count($names), count($qtys), count($prices));
            for ($i = 0; $i < $max; $i++) {
                $t = is_string($types[$i] ?? null) ? trim((string) $types[$i]) : '';
                $n = is_string($names[$i] ?? null) ? trim((string) $names[$i]) : '';
                if ($t === '' || $n === '') {
                    continue;
                }
                if (! in_array($t, ['service', 'part', 'fee', 'discount'], true)) {
                    continue;
                }

                $q = is_numeric($qtys[$i] ?? null) ? (int) $qtys[$i] : 1;
                $p = is_numeric($prices[$i] ?? null) ? (int) $prices[$i] : 0;

                RepairBuddyJobItem::query()->create([
                    'job_id' => $job->id,
                    'item_type' => $t,
                    'ref_id' => null,
                    'name_snapshot' => $n,
                    'qty' => $q,
                    'unit_price_amount_cents' => $p,
                    'unit_price_currency' => is_string($tenant->currency ?? null) ? (string) $tenant->currency : null,
                    'tax_id' => null,
                    'meta_json' => null,
                ]);
            }

            RepairBuddyEvent::query()->create([
                'actor_user_id' => $user->id,
                'entity_type' => 'job',
                'entity_id' => $job->id,
                'visibility' => 'private',
                'event_type' => 'job.created',
                'payload_json' => [
                    'title' => 'Job created',
                    'message' => 'Job created.',
                ],
            ]);

            $orderNote = is_string($validated['wc_order_note'] ?? null) ? trim((string) $validated['wc_order_note']) : '';
            if ($orderNote !== '') {
                RepairBuddyEvent::query()->create([
                    'actor_user_id' => $user->id,
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
                    'uploader_user_id' => $user->id,
                    'visibility' => 'public',
                    'original_filename' => $uploaded->getClientOriginalName(),
                    'mime_type' => $uploaded->getClientMimeType(),
                    'size_bytes' => $uploaded->getSize() ?? 0,
                    'storage_disk' => $disk,
                    'storage_path' => $path,
                    'url' => $url,
                ]);

                RepairBuddyEvent::query()->create([
                    'actor_user_id' => $user->id,
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

            $occurredAts = is_array($validated['extra_item_occurred_at'] ?? null) ? $validated['extra_item_occurred_at'] : [];
            $labels = is_array($validated['extra_item_label'] ?? null) ? $validated['extra_item_label'] : [];
            $dataTexts = is_array($validated['extra_item_data_text'] ?? null) ? $validated['extra_item_data_text'] : [];
            $descriptions = is_array($validated['extra_item_description'] ?? null) ? $validated['extra_item_description'] : [];
            $visibilities = is_array($validated['extra_item_visibility'] ?? null) ? $validated['extra_item_visibility'] : [];
            $files = $request->file('extra_item_file');
            $files = is_array($files) ? $files : [];

            $count = max(count($labels), count($dataTexts), count($descriptions), count($visibilities), count($occurredAts), count($files));
            for ($i = 0; $i < $count; $i++) {
                $label = is_string($labels[$i] ?? null) ? trim((string) $labels[$i]) : '';
                if ($label === '') {
                    continue;
                }

                $visibility = is_string($visibilities[$i] ?? null) ? trim((string) $visibilities[$i]) : 'private';
                if (! in_array($visibility, ['public', 'private'], true)) {
                    $visibility = 'private';
                }

                $meta = null;
                $file = $files[$i] ?? null;
                if ($file instanceof UploadedFile) {
                    $disk = 'public';
                    $path = $file->store('rb/jobs/'.$job->id.'/attachments', $disk);
                    $url = Storage::disk($disk)->url($path);

                    $attachment = RepairBuddyJobAttachment::query()->create([
                        'job_id' => $job->id,
                        'uploader_user_id' => $user->id,
                        'visibility' => $visibility,
                        'original_filename' => $file->getClientOriginalName(),
                        'mime_type' => $file->getClientMimeType(),
                        'size_bytes' => $file->getSize() ?? 0,
                        'storage_disk' => $disk,
                        'storage_path' => $path,
                        'url' => $url,
                    ]);

                    $meta = [
                        'attachment_id' => $attachment->id,
                        'url' => $url,
                        'filename' => $attachment->original_filename,
                    ];
                }

                RepairBuddyJobExtraItem::query()->create([
                    'job_id' => $job->id,
                    'occurred_at' => array_key_exists($i, $occurredAts) ? $occurredAts[$i] : null,
                    'label' => $label,
                    'data_text' => is_string($dataTexts[$i] ?? null) ? trim((string) $dataTexts[$i]) : null,
                    'description' => is_string($descriptions[$i] ?? null) ? trim((string) $descriptions[$i]) : null,
                    'item_type' => $meta ? 'file' : 'text',
                    'visibility' => $visibility,
                    'meta_json' => $meta,
                ]);
            }

            return $job;
        });

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
            ->with(['customer', 'technicians', 'jobDevices'])
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

        $itemsSubtotalCents = 0;
        foreach ($items as $item) {
            $qty = is_numeric($item->qty) ? (int) $item->qty : 1;
            $unit = is_numeric($item->unit_price_amount_cents) ? (int) $item->unit_price_amount_cents : 0;
            $itemsSubtotalCents += ($qty * $unit);
        }

        $totals = [
            'items_subtotal_cents' => $itemsSubtotalCents,
            'tax_total_cents' => null,
            'grand_total_cents' => $itemsSubtotalCents,
            'paid_total_cents' => null,
            'balance_cents' => null,
            'currency' => $currency,
        ];

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
        ]);
    }
}
