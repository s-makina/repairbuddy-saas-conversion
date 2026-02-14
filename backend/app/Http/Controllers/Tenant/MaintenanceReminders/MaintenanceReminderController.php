<?php

namespace App\Http\Controllers\Tenant\MaintenanceReminders;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\MaintenanceReminders\StoreMaintenanceReminderRequest;
use App\Http\Requests\Tenant\MaintenanceReminders\UpdateMaintenanceReminderRequest;
use App\Models\RepairBuddyDeviceBrand;
use App\Models\RepairBuddyDeviceType;
use App\Models\RepairBuddyMaintenanceReminder;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MaintenanceReminderController extends Controller
{
    public function store(StoreMaintenanceReminderRequest $request): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validated();

        $emailEnabledValue = is_string($validated['email_enabled'] ?? null) ? (string) $validated['email_enabled'] : '';
        $smsEnabledValue = is_string($validated['sms_enabled'] ?? null) ? (string) $validated['sms_enabled'] : '';
        $reminderEnabledValue = is_string($validated['reminder_enabled'] ?? null) ? (string) $validated['reminder_enabled'] : '';

        $emailEnabled = in_array($emailEnabledValue, ['active', 'on'], true);
        $smsEnabled = in_array($smsEnabledValue, ['active', 'on'], true);
        $reminderEnabled = in_array($reminderEnabledValue, ['active', 'on'], true);

        if ($emailEnabled && (! is_string($validated['email_body'] ?? null) || trim((string) $validated['email_body']) === '')) {
            return $this->redirect($tenant)
                ->withErrors(['email_body' => 'Email body is required when email is enabled.'])
                ->withInput();
        }

        if ($smsEnabled && (! is_string($validated['sms_body'] ?? null) || trim((string) $validated['sms_body']) === '')) {
            return $this->redirect($tenant)
                ->withErrors(['sms_body' => 'SMS body is required when SMS is enabled.'])
                ->withInput();
        }

        $typeId = array_key_exists('device_type_id', $validated) ? (int) ($validated['device_type_id'] ?? 0) : 0;
        $brandId = array_key_exists('device_brand_id', $validated) ? (int) ($validated['device_brand_id'] ?? 0) : 0;
        $typeId = $typeId > 0 ? $typeId : null;
        $brandId = $brandId > 0 ? $brandId : null;

        if ($typeId !== null && ! RepairBuddyDeviceType::query()->whereKey($typeId)->exists()) {
            return $this->redirect($tenant)
                ->withErrors(['device_type_id' => 'Device type is invalid.'])
                ->withInput();
        }

        if ($brandId !== null && ! RepairBuddyDeviceBrand::query()->whereKey($brandId)->exists()) {
            return $this->redirect($tenant)
                ->withErrors(['device_brand_id' => 'Device brand is invalid.'])
                ->withInput();
        }

        RepairBuddyMaintenanceReminder::query()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'interval_days' => (int) $validated['interval_days'],
            'device_type_id' => $typeId,
            'device_brand_id' => $brandId,
            'email_enabled' => $emailEnabled,
            'sms_enabled' => $smsEnabled,
            'reminder_enabled' => $reminderEnabled,
            'email_body' => is_string($validated['email_body'] ?? null) ? (string) $validated['email_body'] : null,
            'sms_body' => is_string($validated['sms_body'] ?? null) ? (string) $validated['sms_body'] : null,
            'created_by_user_id' => $request->user()?->id,
            'updated_by_user_id' => $request->user()?->id,
        ]);

        return $this->redirect($tenant)
            ->with('status', 'Maintenance reminder added.')
            ->withInput();
    }

    public function update(UpdateMaintenanceReminderRequest $request, int $reminder): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $model = RepairBuddyMaintenanceReminder::query()->whereKey($reminder)->first();
        if (! $model) {
            return $this->redirect($tenant)
                ->with('status', 'Reminder not found.')
                ->withInput();
        }

        $validated = $request->validated();

        $emailEnabledValue = array_key_exists('email_enabled', $validated) && is_string($validated['email_enabled'] ?? null)
            ? (string) $validated['email_enabled']
            : '';
        $smsEnabledValue = array_key_exists('sms_enabled', $validated) && is_string($validated['sms_enabled'] ?? null)
            ? (string) $validated['sms_enabled']
            : '';
        $reminderEnabledValue = array_key_exists('reminder_enabled', $validated) && is_string($validated['reminder_enabled'] ?? null)
            ? (string) $validated['reminder_enabled']
            : '';

        $emailEnabled = in_array($emailEnabledValue, ['active', 'on'], true);
        $smsEnabled = in_array($smsEnabledValue, ['active', 'on'], true);
        $reminderEnabled = in_array($reminderEnabledValue, ['active', 'on'], true);

        if ($emailEnabled && (! is_string($validated['email_body'] ?? null) || trim((string) $validated['email_body']) === '')) {
            return $this->redirect($tenant)
                ->withErrors(['email_body' => 'Email body is required when email is enabled.'])
                ->withInput();
        }

        if ($smsEnabled && (! is_string($validated['sms_body'] ?? null) || trim((string) $validated['sms_body']) === '')) {
            return $this->redirect($tenant)
                ->withErrors(['sms_body' => 'SMS body is required when SMS is enabled.'])
                ->withInput();
        }

        $typeId = array_key_exists('device_type_id', $validated) ? (int) ($validated['device_type_id'] ?? 0) : 0;
        $brandId = array_key_exists('device_brand_id', $validated) ? (int) ($validated['device_brand_id'] ?? 0) : 0;
        $typeId = $typeId > 0 ? $typeId : null;
        $brandId = $brandId > 0 ? $brandId : null;

        if ($typeId !== null && ! RepairBuddyDeviceType::query()->whereKey($typeId)->exists()) {
            return $this->redirect($tenant)
                ->withErrors(['device_type_id' => 'Device type is invalid.'])
                ->withInput();
        }

        if ($brandId !== null && ! RepairBuddyDeviceBrand::query()->whereKey($brandId)->exists()) {
            return $this->redirect($tenant)
                ->withErrors(['device_brand_id' => 'Device brand is invalid.'])
                ->withInput();
        }

        $model->forceFill([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'interval_days' => (int) $validated['interval_days'],
            'device_type_id' => $typeId,
            'device_brand_id' => $brandId,
            'email_enabled' => $emailEnabled,
            'sms_enabled' => $smsEnabled,
            'reminder_enabled' => $reminderEnabled,
            'email_body' => is_string($validated['email_body'] ?? null) ? (string) $validated['email_body'] : null,
            'sms_body' => is_string($validated['sms_body'] ?? null) ? (string) $validated['sms_body'] : null,
            'updated_by_user_id' => $request->user()?->id,
        ])->save();

        return $this->redirect($tenant)
            ->with('status', 'Maintenance reminder updated.')
            ->withInput();
    }

    public function delete(Request $request, int $reminder): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $model = RepairBuddyMaintenanceReminder::query()->whereKey($reminder)->first();
        if ($model) {
            $model->delete();
        }

        return $this->redirect($tenant)
            ->with('status', 'Maintenance reminder deleted.')
            ->withInput();
    }

    private function redirect(Tenant $tenant): RedirectResponse
    {
        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]).'?screen=settings')
            ->withFragment('wc_rb_maintenance_reminder');
    }
}
