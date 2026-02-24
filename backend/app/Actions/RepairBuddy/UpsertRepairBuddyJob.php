<?php

namespace App\Actions\RepairBuddy;

use App\Models\Branch;
use App\Models\RepairBuddyCustomerDevice;
use App\Models\RepairBuddyCustomerDeviceFieldValue;
use App\Models\RepairBuddyDeviceFieldDefinition;
use App\Models\RepairBuddyEvent;
use App\Models\RepairBuddyJob;
use App\Models\RepairBuddyJobAttachment;
use App\Models\RepairBuddyJobCounter;
use App\Models\RepairBuddyJobDevice;
use App\Models\RepairBuddyJobExtraItem;
use App\Models\RepairBuddyJobItem;
use App\Models\RepairBuddyTax;
use App\Models\Status;
use App\Models\Tenant;
use App\Models\User;
use App\Services\RepairBuddyCaseNumberService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UpsertRepairBuddyJob
{
    public function create(
        Tenant $tenant,
        Branch $branch,
        User $actor,
        array $validated,
        ?UploadedFile $jobFile,
        array $extraItemFiles,
    ): RepairBuddyJob {
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

        return DB::transaction(function () use ($tenant, $branch, $actor, $validated, $jobFile, $extraItemFiles, $caseNumber, $title, $statusSlug, $paymentStatusSlug, $priority, $pricesIncluExclu, $canReviewIt) {
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
                'created_by' => $actor->id,
                'opened_at' => now(),
                'pickup_date' => $validated['pickup_date'] ?? null,
                'delivery_date' => $validated['delivery_date'] ?? null,
                'next_service_date' => $validated['next_service_date'] ?? null,
                'case_detail' => $validated['case_detail'] ?? null,
            ]);

            $this->syncTechnicians($job, $tenant, $branch, $validated);
            $this->replaceJobDevices($job, $validated);
            $this->replaceJobItemsCreateSimple($job, $tenant, $validated);

            RepairBuddyEvent::query()->create([
                'actor_user_id' => $actor->id,
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
                    'actor_user_id' => $actor->id,
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

            if ($jobFile instanceof UploadedFile) {
                $this->attachJobFile($job, $actor, $jobFile);
            }

            $this->createExtraItems($job, $actor, $validated, $extraItemFiles);

            return $job;
        });
    }

    public function update(
        Tenant $tenant,
        User $actor,
        RepairBuddyJob $job,
        array $validated,
        ?UploadedFile $jobFile = null,
        array $extraItemFiles = [],
    ): RepairBuddyJob {
        return DB::transaction(function () use ($tenant, $actor, $job, $validated, $jobFile, $extraItemFiles) {
            $caseNumber = is_string($validated['case_number'] ?? null) ? trim((string) $validated['case_number']) : '';
            $title = is_string($validated['title'] ?? null) ? trim((string) $validated['title']) : '';
            if ($caseNumber === '') {
                $caseNumber = (string) ($job->case_number ?? '');
            }
            if ($title === '') {
                $title = $caseNumber !== '' ? $caseNumber : (string) ($job->title ?? '');
            }

            $job->forceFill([
                'case_number' => $caseNumber,
                'title' => $title,
                'status_slug' => $validated['status_slug'] ?? $job->status_slug,
                'payment_status_slug' => $validated['payment_status_slug'] ?? $job->payment_status_slug,
                'prices_inclu_exclu' => $validated['prices_inclu_exclu'] ?? $job->prices_inclu_exclu,
                'priority' => $validated['priority'] ?? $job->priority,
                'can_review_it' => array_key_exists('can_review_it', $validated) ? (bool) $validated['can_review_it'] : (bool) $job->can_review_it,
                'customer_id' => array_key_exists('customer_id', $validated) && is_numeric($validated['customer_id']) ? (int) $validated['customer_id'] : null,
                'pickup_date' => $validated['pickup_date'] ?? null,
                'delivery_date' => $validated['delivery_date'] ?? null,
                'next_service_date' => $validated['next_service_date'] ?? null,
                'case_detail' => $validated['case_detail'] ?? null,
            ])->save();

            $branchId = (int) ($job->branch_id ?? 0);
            $branch = Branch::query()->whereKey($branchId)->first();
            if ($branch) {
                $this->syncTechnicians($job, $tenant, $branch, $validated);
            } else {
                $this->syncTechnicians($job, $tenant, null, $validated);
            }

            RepairBuddyJobDevice::query()->where('job_id', $job->id)->delete();
            $this->replaceJobDevices($job, $validated);

            RepairBuddyJobItem::query()->where('job_id', $job->id)->delete();
            $this->replaceJobItemsWithMeta($job, $tenant, $validated);

            if ($jobFile instanceof UploadedFile) {
                $this->attachJobFile($job, $actor, $jobFile);
            }

            RepairBuddyJobExtraItem::query()->where('job_id', $job->id)->delete();
            $this->createExtraItems($job, $actor, $validated, is_array($extraItemFiles) ? $extraItemFiles : []);

            RepairBuddyEvent::query()->create([
                'actor_user_id' => $actor->id,
                'entity_type' => 'job',
                'entity_id' => $job->id,
                'visibility' => 'private',
                'event_type' => 'job.updated',
                'payload_json' => [
                    'title' => 'Job updated',
                    'message' => 'Job updated.',
                ],
            ]);

            return $job;
        });
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

    private function syncTechnicians(RepairBuddyJob $job, Tenant $tenant, ?Branch $branch, array $validated): void
    {
        $techIds = array_key_exists('technician_ids', $validated) && is_array($validated['technician_ids'])
            ? array_values(array_unique(array_map('intval', $validated['technician_ids'])))
            : [];

        $branchId = $branch ? (int) $branch->id : (int) ($job->branch_id ?? 0);

        $sync = [];
        foreach ($techIds as $id) {
            if ($id <= 0) {
                continue;
            }
            $sync[$id] = [
                'tenant_id' => (int) $tenant->id,
                'branch_id' => $branchId,
            ];
        }
        $job->technicians()->sync($sync);
    }

    private function replaceJobDevices(RepairBuddyJob $job, array $validated): void
    {
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

        if (count($customerDeviceIds) === 0) {
            return;
        }

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

    private function replaceJobItemsCreateSimple(RepairBuddyJob $job, Tenant $tenant, array $validated): void
    {
        $defaultTaxId = $this->resolveDefaultTaxId($tenant);

        $types = is_array($validated['item_type'] ?? null) ? $validated['item_type'] : [];
        $names = is_array($validated['item_name'] ?? null) ? $validated['item_name'] : [];
        $codes = is_array($validated['item_code'] ?? null) ? $validated['item_code'] : [];
        $qtys = is_array($validated['item_qty'] ?? null) ? $validated['item_qty'] : [];
        $prices = is_array($validated['item_unit_price_cents'] ?? null) ? $validated['item_unit_price_cents'] : [];

        $max = max(count($types), count($names), count($codes), count($qtys), count($prices));
        for ($i = 0; $i < $max; $i++) {
            $t = is_string($types[$i] ?? null) ? trim((string) $types[$i]) : '';
            $n = is_string($names[$i] ?? null) ? trim((string) $names[$i]) : '';
            if ($t === '' || $n === '') {
                continue;
            }
            if (! in_array($t, ['service', 'part', 'fee', 'discount'], true)) {
                continue;
            }

            $c = is_string($codes[$i] ?? null) ? trim((string) $codes[$i]) : '';
            $q = is_numeric($qtys[$i] ?? null) ? (int) $qtys[$i] : 1;
            $p = is_numeric($prices[$i] ?? null) ? (int) $prices[$i] : 0;

            $metaJson = $c !== '' ? ['code' => $c] : null;

            // Discounts don't get taxed
            $itemTaxId = ($t === 'discount') ? null : $defaultTaxId;

            RepairBuddyJobItem::query()->create([
                'job_id' => $job->id,
                'item_type' => $t,
                'ref_id' => null,
                'name_snapshot' => $n,
                'qty' => $q,
                'unit_price_amount_cents' => $p,
                'unit_price_currency' => is_string($tenant->currency ?? null) ? (string) $tenant->currency : null,
                'tax_id' => $itemTaxId,
                'meta_json' => $metaJson,
            ]);
        }
    }

    private function replaceJobItemsWithMeta(RepairBuddyJob $job, Tenant $tenant, array $validated): void
    {
        $defaultTaxId = $this->resolveDefaultTaxId($tenant);

        $types = is_array($validated['item_type'] ?? null) ? $validated['item_type'] : [];
        $names = is_array($validated['item_name'] ?? null) ? $validated['item_name'] : [];
        $codes = is_array($validated['item_code'] ?? null) ? $validated['item_code'] : [];
        $qtys = is_array($validated['item_qty'] ?? null) ? $validated['item_qty'] : [];
        $prices = is_array($validated['item_unit_price_cents'] ?? null) ? $validated['item_unit_price_cents'] : [];
        $metas = is_array($validated['item_meta_json'] ?? null) ? $validated['item_meta_json'] : [];

        $max = max(count($types), count($names), count($codes), count($qtys), count($prices), count($metas));
        for ($i = 0; $i < $max; $i++) {
            $t = is_string($types[$i] ?? null) ? trim((string) $types[$i]) : '';
            $n = is_string($names[$i] ?? null) ? trim((string) $names[$i]) : '';
            if ($t === '' || $n === '') {
                continue;
            }
            if (! in_array($t, ['service', 'part', 'fee', 'discount'], true)) {
                continue;
            }

            $c = is_string($codes[$i] ?? null) ? trim((string) $codes[$i]) : '';
            $q = is_numeric($qtys[$i] ?? null) ? (int) $qtys[$i] : 1;
            $p = is_numeric($prices[$i] ?? null) ? (int) $prices[$i] : 0;

            $metaDecoded = null;
            $rawMeta = is_string($metas[$i] ?? null) ? trim((string) $metas[$i]) : '';
            if ($rawMeta !== '') {
                $maybe = json_decode($rawMeta, true);
                if (is_array($maybe)) {
                    $metaDecoded = $maybe;
                }
            }

            // Merge code into meta_json if present
            if ($c !== '') {
                $metaDecoded = is_array($metaDecoded) ? $metaDecoded : [];
                $metaDecoded['code'] = $c;
            }

            RepairBuddyJobItem::query()->create([
                'job_id' => $job->id,
                'item_type' => $t,
                'ref_id' => null,
                'name_snapshot' => $n,
                'qty' => $q,
                'unit_price_amount_cents' => $p,
                'unit_price_currency' => is_string($tenant->currency ?? null) ? (string) $tenant->currency : null,
                'tax_id' => ($t === 'discount') ? null : $defaultTaxId,
                'meta_json' => $metaDecoded,
            ]);
        }
    }

    /**
     * Resolve the default tax ID from tenant settings.
     */
    private function resolveDefaultTaxId(Tenant $tenant): ?int
    {
        $settings = data_get($tenant->setup_state ?? [], 'repairbuddy_settings');
        $taxSettings = is_array($settings) ? (array) data_get($settings, 'taxes', []) : [];

        $taxEnabled = (bool) ($taxSettings['enableTaxes'] ?? false);
        if (! $taxEnabled) {
            return null;
        }

        $rawId = $taxSettings['defaultTaxId'] ?? null;
        $taxId = is_numeric($rawId) ? (int) $rawId : null;

        if ($taxId) {
            $exists = RepairBuddyTax::query()
                ->whereKey($taxId)
                ->where('is_active', true)
                ->exists();
            if ($exists) {
                return $taxId;
            }
        }

        // Fallback: first active default tax
        $tax = RepairBuddyTax::query()
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();

        if (! $tax) {
            $tax = RepairBuddyTax::query()
                ->where('is_active', true)
                ->orderBy('id')
                ->first();
        }

        return $tax?->id;
    }

    private function attachJobFile(RepairBuddyJob $job, User $actor, UploadedFile $uploaded): void
    {
        $disk = 'public';
        $path = $uploaded->store('rb/jobs/'.$job->id.'/attachments', $disk);
        $url = Storage::disk($disk)->url($path);

        $attachment = RepairBuddyJobAttachment::query()->create([
            'job_id' => $job->id,
            'uploader_user_id' => $actor->id,
            'visibility' => 'public',
            'original_filename' => $uploaded->getClientOriginalName(),
            'mime_type' => $uploaded->getClientMimeType(),
            'size_bytes' => $uploaded->getSize() ?? 0,
            'storage_disk' => $disk,
            'storage_path' => $path,
            'url' => $url,
        ]);

        RepairBuddyEvent::query()->create([
            'actor_user_id' => $actor->id,
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

    private function createExtraItems(RepairBuddyJob $job, User $actor, array $validated, array $files): void
    {
        $occurredAts = is_array($validated['extra_item_occurred_at'] ?? null) ? $validated['extra_item_occurred_at'] : [];
        $labels = is_array($validated['extra_item_label'] ?? null) ? $validated['extra_item_label'] : [];
        $dataTexts = is_array($validated['extra_item_data_text'] ?? null) ? $validated['extra_item_data_text'] : [];
        $descriptions = is_array($validated['extra_item_description'] ?? null) ? $validated['extra_item_description'] : [];
        $visibilities = is_array($validated['extra_item_visibility'] ?? null) ? $validated['extra_item_visibility'] : [];

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
                    'uploader_user_id' => $actor->id,
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
    }
}
