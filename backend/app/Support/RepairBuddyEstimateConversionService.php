<?php

namespace App\Support;

use App\Support\BranchContext;
use App\Models\RepairBuddyCustomerDevice;
use App\Models\RepairBuddyEstimate;
use App\Models\RepairBuddyEvent;
use App\Models\RepairBuddyJob;
use App\Models\RepairBuddyJobDevice;
use App\Models\RepairBuddyJobItem;
use App\Models\RepairBuddyJobStatus;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class RepairBuddyEstimateConversionService
{
    public function convertToJob(RepairBuddyEstimate $estimate, ?int $actorUserId = null): RepairBuddyJob
    {
        return DB::transaction(function () use ($estimate, $actorUserId) {
            $estimate = $estimate->fresh();

            if (! $estimate instanceof RepairBuddyEstimate) {
                throw new \RuntimeException('Estimate not found.');
            }

            if (is_numeric($estimate->converted_job_id) && (int) $estimate->converted_job_id > 0) {
                $existing = RepairBuddyJob::query()->whereKey((int) $estimate->converted_job_id)->first();
                if ($existing) {
                    return $existing;
                }

                $estimate->forceFill(['converted_job_id' => null])->save();
            }

            $estimate->loadMissing(['items', 'devices']);

            $statusSlug = $this->resolveInitialJobStatusSlug();
            $caseNumber = $this->generateCaseNumber();

            $job = RepairBuddyJob::query()->create([
                'case_number' => $caseNumber,
                'title' => is_string($estimate->title) && trim((string) $estimate->title) !== '' ? (string) $estimate->title : $caseNumber,
                'status_slug' => $statusSlug,
                'payment_status_slug' => null,
                'priority' => null,
                'customer_id' => $estimate->customer_id,
                'created_by' => $actorUserId,
                'opened_at' => now(),
                'pickup_date' => $estimate->pickup_date,
                'delivery_date' => $estimate->delivery_date,
                'next_service_date' => null,
                'case_detail' => $estimate->case_detail,
                'assigned_technician_id' => $estimate->assigned_technician_id,
                'plugin_device_post_id' => null,
                'plugin_device_id_text' => null,
            ]);

            $firstCustomerDeviceId = collect($estimate->devices)
                ->map(fn ($d) => is_numeric($d->customer_device_id ?? null) ? (int) $d->customer_device_id : null)
                ->filter(fn ($id) => is_int($id) && $id > 0)
                ->first();

            if (is_int($firstCustomerDeviceId) && $firstCustomerDeviceId > 0) {
                $cd = RepairBuddyCustomerDevice::query()->whereKey($firstCustomerDeviceId)->first();
                if ($cd && is_numeric($cd->device_id)) {
                    $job->forceFill([
                        'plugin_device_post_id' => (int) $cd->device_id,
                        'plugin_device_id_text' => is_string($cd->serial) && trim((string) $cd->serial) !== '' ? trim((string) $cd->serial) : null,
                    ])->save();
                }
            }

            foreach ($estimate->devices as $d) {
                $customerDeviceId = is_numeric($d->customer_device_id ?? null) ? (int) $d->customer_device_id : null;
                if (! $customerDeviceId || $customerDeviceId <= 0) {
                    continue;
                }

                RepairBuddyJobDevice::query()->create([
                    'job_id' => $job->id,
                    'customer_device_id' => $customerDeviceId,
                    'label_snapshot' => (string) ($d->label_snapshot ?? ''),
                    'serial_snapshot' => $d->serial_snapshot,
                    'pin_snapshot' => $d->pin_snapshot,
                    'notes_snapshot' => $d->notes_snapshot,
                    'extra_fields_snapshot_json' => is_array($d->extra_fields_snapshot_json) ? $d->extra_fields_snapshot_json : [],
                ]);
            }

            foreach ($estimate->items as $item) {
                RepairBuddyJobItem::query()->create([
                    'job_id' => $job->id,
                    'item_type' => (string) $item->item_type,
                    'ref_id' => $item->ref_id,
                    'name_snapshot' => (string) $item->name_snapshot,
                    'qty' => $item->qty,
                    'unit_price_amount_cents' => $item->unit_price_amount_cents,
                    'unit_price_currency' => $item->unit_price_currency,
                    'tax_id' => $item->tax_id,
                    'meta_json' => is_array($item->meta_json) ? $item->meta_json : null,
                ]);
            }

            $estimate->forceFill([
                'status' => 'approved',
                'approved_at' => $estimate->approved_at ?: now(),
                'rejected_at' => null,
                'converted_job_id' => $job->id,
            ])->save();

            RepairBuddyEvent::query()->create([
                'actor_user_id' => $actorUserId,
                'entity_type' => 'job',
                'entity_id' => $job->id,
                'visibility' => 'private',
                'event_type' => 'job.created',
                'payload_json' => [
                    'title' => 'Job created from estimate',
                    'estimate_id' => (int) $estimate->id,
                    'estimate_case_number' => (string) $estimate->case_number,
                    'case_number' => (string) $job->case_number,
                ],
            ]);

            RepairBuddyEvent::query()->create([
                'actor_user_id' => $actorUserId,
                'entity_type' => 'estimate',
                'entity_id' => $estimate->id,
                'visibility' => 'private',
                'event_type' => 'estimate.converted_to_job',
                'payload_json' => [
                    'title' => 'Estimate converted to job',
                    'job_id' => (int) $job->id,
                    'job_case_number' => (string) $job->case_number,
                ],
            ]);

            return $job;
        });
    }

    protected function resolveInitialJobStatusSlug(): string
    {
        $preferred = ['neworder', 'new'];

        foreach ($preferred as $slug) {
            $exists = RepairBuddyJobStatus::query()->where('slug', $slug)->exists();
            if ($exists) {
                return $slug;
            }
        }

        $first = RepairBuddyJobStatus::query()->orderBy('id')->value('slug');
        if (is_string($first) && trim((string) $first) !== '') {
            return trim((string) $first);
        }

        return 'neworder';
    }

    protected function generateCaseNumber(): string
    {
        $branch = BranchContext::branch();
        $tenant = TenantContext::tenant();

        if (! $branch) {
            throw new \RuntimeException('Branch context is missing.');
        }

        if (! $tenant) {
            throw new \RuntimeException('Tenant context is missing.');
        }

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

        $caseNumber = '';

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $randomString = '';
            for ($i = 0; $i < $length; $i++) {
                $randomString .= $characters[random_int(0, $charactersLength - 1)];
            }

            $caseNumber = $prefix.$randomString.time();

            $exists = RepairBuddyJob::query()->where('case_number', $caseNumber)->exists();
            if (! $exists) {
                return $caseNumber;
            }
        }

        if ($caseNumber === '') {
            $caseNumber = $prefix.time();
        }

        return $caseNumber;
    }
}
