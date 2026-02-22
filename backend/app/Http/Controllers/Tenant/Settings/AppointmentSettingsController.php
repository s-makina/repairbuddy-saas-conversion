<?php

namespace App\Http\Controllers\Tenant\Settings;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyAppointmentSetting;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class AppointmentSettingsController extends Controller
{
    public function store(Request $request)
    {
        $tenantId = TenantContext::tenantId();
        $branchId = BranchContext::branchId();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_enabled' => ['sometimes', 'boolean'],
            'slot_duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'buffer_minutes' => ['required', 'integer', 'min:0', 'max:120'],
            'max_appointments_per_day' => ['required', 'integer', 'min:1', 'max:200'],
            'time_slots' => ['nullable', 'array'],
            'time_slots.*.day' => ['required', 'string', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'time_slots.*.start' => ['required', 'string', 'date_format:H:i'],
            'time_slots.*.end' => ['required', 'string', 'date_format:H:i'],
            'time_slots.*.enabled' => ['sometimes', 'boolean'],
        ]);

        RepairBuddyAppointmentSetting::create([
            'tenant_id' => $tenantId,
            'branch_id' => $branchId,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'is_enabled' => $validated['is_enabled'] ?? true,
            'slot_duration_minutes' => $validated['slot_duration_minutes'],
            'buffer_minutes' => $validated['buffer_minutes'],
            'max_appointments_per_day' => $validated['max_appointments_per_day'],
            'time_slots' => $validated['time_slots'] ?? $this->defaultTimeSlots(),
        ]);

        $business = TenantContext::tenant()?->slug ?? '';

        return redirect()
            ->route('tenant.settings.section', ['business' => $business, 'section' => 'appointments'])
            ->with('success', 'Appointment option created.');
    }

    public function update(Request $request, string $business, $settingId)
    {
        $setting = RepairBuddyAppointmentSetting::query()->whereKey((int) $settingId)->firstOrFail();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_enabled' => ['sometimes', 'boolean'],
            'slot_duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'buffer_minutes' => ['required', 'integer', 'min:0', 'max:120'],
            'max_appointments_per_day' => ['required', 'integer', 'min:1', 'max:200'],
            'time_slots' => ['nullable', 'array'],
            'time_slots.*.day' => ['required', 'string', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'time_slots.*.start' => ['required', 'string', 'date_format:H:i'],
            'time_slots.*.end' => ['required', 'string', 'date_format:H:i'],
            'time_slots.*.enabled' => ['sometimes', 'boolean'],
        ]);

        $setting->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'is_enabled' => $validated['is_enabled'] ?? $setting->is_enabled,
            'slot_duration_minutes' => $validated['slot_duration_minutes'],
            'buffer_minutes' => $validated['buffer_minutes'],
            'max_appointments_per_day' => $validated['max_appointments_per_day'],
            'time_slots' => $validated['time_slots'] ?? $setting->time_slots,
        ]);

        return redirect()
            ->route('tenant.settings.section', ['business' => $business, 'section' => 'appointments'])
            ->with('success', 'Appointment option updated.');
    }

    public function toggle(Request $request, string $business, $settingId)
    {
        $setting = RepairBuddyAppointmentSetting::query()->whereKey((int) $settingId)->firstOrFail();
        $setting->update(['is_enabled' => ! $setting->is_enabled]);

        return redirect()
            ->route('tenant.settings.section', ['business' => $business, 'section' => 'appointments'])
            ->with('success', $setting->is_enabled ? 'Appointment option enabled.' : 'Appointment option disabled.');
    }

    public function delete(Request $request, string $business, $settingId)
    {
        $setting = RepairBuddyAppointmentSetting::query()->whereKey((int) $settingId)->firstOrFail();
        $setting->delete();

        return redirect()
            ->route('tenant.settings.section', ['business' => $business, 'section' => 'appointments'])
            ->with('success', 'Appointment option deleted.');
    }

    private function defaultTimeSlots(): array
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $slots = [];
        foreach ($days as $day) {
            $slots[] = [
                'day' => $day,
                'start' => '09:00',
                'end' => '17:00',
                'enabled' => ! in_array($day, ['saturday', 'sunday']),
            ];
        }
        return $slots;
    }
}
