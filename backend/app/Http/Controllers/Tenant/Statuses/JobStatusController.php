<?php

namespace App\Http\Controllers\Tenant\Statuses;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Statuses\StoreJobStatusRequest;
use App\Http\Requests\Tenant\Statuses\UpdateJobStatusRequest;
use App\Models\RepairBuddyJob;
use App\Models\Status;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JobStatusController extends Controller
{
    private function generateUniqueCode(string $label, int $tenantId, string $statusType, ?int $ignoreId = null): string
    {
        $slugBase = Str::of($label)
            ->trim()
            ->lower()
            ->replace(' ', '_')
            ->replace('-', '_')
            ->replaceMatches('/[^a-z0-9_]/', '')
            ->toString();

        if ($slugBase === '') {
            return '';
        }

        $code = $slugBase;
        $suffix = 2;
        while (Status::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status_type', $statusType)
            ->where('code', $code)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists()) {
            $code = $slugBase.'_'.$suffix;
            $suffix++;
            if ($suffix > 200) {
                return '';
            }
        }

        return $code;
    }

    public function store(StoreJobStatusRequest $request): RedirectResponse
    {
        $tenant = TenantContext::tenant();
        $tenantId = TenantContext::tenantId();

        if (! $tenant instanceof Tenant || ! $tenantId) {
            return back()->withErrors(['status_name' => 'Tenant context is missing.'])->withInput();
        }

        $validated = $request->validated();

        $slugBase = Str::of((string) $validated['status_name'])
            ->trim()
            ->lower()
            ->replace(' ', '_')
            ->replace('-', '_')
            ->replaceMatches('/[^a-z0-9_]/', '')
            ->toString();

        if ($slugBase === '') {
            return back()->withErrors(['status_name' => 'Status name is invalid.'])->withInput();
        }

        $code = $slugBase;
        $suffix = 2;
        while (Status::query()->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status_type', 'Job')
            ->where('code', $code)
            ->exists()) {
            $code = $slugBase.'_'.$suffix;
            $suffix++;
            if ($suffix > 200) {
                return back()->withErrors(['status_name' => 'Unable to generate a unique status code.'])->withInput();
            }
        }

        $emailTemplate = array_key_exists('statusEmailMessage', $validated) ? $validated['statusEmailMessage'] : null;
        if (is_string($emailTemplate)) {
            $emailTemplate = trim($emailTemplate);
            if ($emailTemplate === '') {
                $emailTemplate = null;
            }
        }

        $label = trim((string) $validated['status_name']);

        $labelExists = Status::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status_type', 'Job')
            ->where('label', $label)
            ->exists();

        if ($labelExists) {
            return back()->withErrors(['status_name' => 'This status already exists.'])->withInput();
        }

        $isActive = (string) ($validated['status_status'] ?? 'active') === 'active';
        $invoiceLabel = array_key_exists('invoice_label', $validated) ? $validated['invoice_label'] : null;
        $description = array_key_exists('status_description', $validated) ? $validated['status_description'] : null;

        DB::transaction(function () use ($tenantId, $code, $label, $description, $invoiceLabel, $isActive, $emailTemplate) {
            Status::query()->create([
                'tenant_id' => $tenantId,
                'status_type' => 'Job',
                'code' => $code,
                'label' => $label,
                'description' => $description,
                'invoice_label' => $invoiceLabel,
                'is_active' => $isActive,
                'email_enabled' => $emailTemplate !== null,
                'email_template' => $emailTemplate,
                'sms_enabled' => false,
            ]);
        });

        return $this->redirectToSettings($tenant)
            ->withFragment('panel3')
            ->with('status', 'Job status created.');
    }

    public function update(UpdateJobStatusRequest $request, $status): RedirectResponse
    {
        $tenant = TenantContext::tenant();
        $tenantId = TenantContext::tenantId();

        $validated = $request->validated();

        $statusId = (int) $status;
        if ($statusId <= 0) {
            $fallbackId = (int) ($validated['editing_status_id'] ?? 0);
            if ($fallbackId > 0) {
                $statusId = $fallbackId;
            }
        }

        if ($statusId <= 0) {
            return back()->withErrors(['status_name' => 'Job status id is missing.'])->withInput();
        }

        if (! $tenant instanceof Tenant || ! $tenantId) {
            return back()->withErrors(['status_name' => 'Tenant context is missing.'])->withInput();
        }

        $jobStatus = Status::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status_type', 'Job')
            ->whereKey($statusId)
            ->first();

        if (! $jobStatus) {
            return back()->withErrors(['status_name' => 'Job status not found.'])->withInput();
        }

        $emailTemplate = array_key_exists('statusEmailMessage', $validated) ? $validated['statusEmailMessage'] : null;
        if (is_string($emailTemplate)) {
            $emailTemplate = trim($emailTemplate);
            if ($emailTemplate === '') {
                $emailTemplate = null;
            }
        }

        $label = trim((string) $validated['status_name']);
        $description = array_key_exists('status_description', $validated) ? $validated['status_description'] : null;
        $invoiceLabel = array_key_exists('invoice_label', $validated) ? $validated['invoice_label'] : null;
        $isActive = (string) ($validated['status_status'] ?? ($jobStatus->is_active ? 'active' : 'inactive')) === 'active';

        $code = is_string($jobStatus->code) ? trim((string) $jobStatus->code) : '';
        if ($code === '') {
            $generated = $this->generateUniqueCode((string) $jobStatus->label, (int) $tenantId, 'Job', (int) $jobStatus->id);
            if ($generated === '') {
                return back()->withErrors(['status_name' => 'Status code is missing.'])->withInput();
            }

            $jobStatus->forceFill([
                'tenant_id' => $tenantId,
                'code' => $generated,
            ])->save();

            $code = $generated;
        }

        $labelExists = Status::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status_type', 'Job')
            ->where('label', $label)
            ->whereKeyNot($jobStatus->id)
            ->exists();

        if ($labelExists) {
            return back()->withErrors(['status_name' => 'This status already exists.'])->withInput();
        }

        DB::transaction(function () use ($jobStatus, $tenantId, $code, $label, $description, $invoiceLabel, $isActive, $emailTemplate) {
            $jobStatus->forceFill([
                'tenant_id' => $tenantId,
                'label' => $label,
                'description' => $description,
                'invoice_label' => $invoiceLabel,
                'is_active' => $isActive,
                'email_enabled' => $emailTemplate !== null,
                'email_template' => $emailTemplate,
            ])->save();
        });

        return $this->redirectToSettings($tenant)
            ->withFragment('panel3')
            ->with('status', 'Job status updated.');
    }

    public function delete(Request $request, $status): RedirectResponse
    {
        $tenant = TenantContext::tenant();
        $tenantId = TenantContext::tenantId();

        $statusId = (int) $status;
        if ($statusId <= 0) {
            return back()->withErrors(['status_name' => 'Job status id is missing.']);
        }

        if (! $tenant instanceof Tenant || ! $tenantId) {
            return back()->withErrors(['status_name' => 'Tenant context is missing.']);
        }

        $jobStatus = Status::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status_type', 'Job')
            ->whereKey($statusId)
            ->first();

        if (! $jobStatus) {
            return back()->withErrors(['status_name' => 'Job status not found.']);
        }

        $code = is_string($jobStatus->code) ? trim((string) $jobStatus->code) : '';
        if ($code === '') {
            $generated = $this->generateUniqueCode((string) $jobStatus->label, (int) $tenantId, 'Job', (int) $jobStatus->id);
            if ($generated === '') {
                return back()->withErrors(['status_name' => 'Status code is missing.']);
            }

            $jobStatus->forceFill([
                'tenant_id' => $tenantId,
                'code' => $generated,
            ])->save();

            $code = $generated;
        }

        $inUseByJobs = RepairBuddyJob::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status_slug', $code)
            ->exists();

        if ($inUseByJobs) {
            return back()->withErrors(['status_name' => 'Cannot delete a status that is used by existing jobs.']);
        }

        $state = is_array($tenant->setup_state) ? $tenant->setup_state : [];
        $repairBuddySettings = $state['repairbuddy_settings'] ?? [];
        if (! is_array($repairBuddySettings)) {
            $repairBuddySettings = [];
        }
        $jobStatusSettings = $repairBuddySettings['jobStatus'] ?? [];
        if (! is_array($jobStatusSettings)) {
            $jobStatusSettings = [];
        }

        $blockedBySettings = in_array($code, [
            (string) ($jobStatusSettings['wcrb_job_status_delivered'] ?? ''),
            (string) ($jobStatusSettings['wcrb_job_status_cancelled'] ?? ''),
        ], true);

        if ($blockedBySettings) {
            return back()->withErrors(['status_name' => 'Cannot delete a status that is selected in status settings.']);
        }

        DB::transaction(function () use ($jobStatus, $tenantId, $code) {
            $jobStatus->delete();
        });

        return $this->redirectToSettings($tenant)
            ->withFragment('panel3')
            ->with('status', 'Job status deleted.');
    }

    private function redirectToSettings(Tenant $tenant): RedirectResponse
    {
        return redirect()->to(route('tenant.dashboard', ['business' => $tenant->slug]).'?screen=settings');
    }
}
