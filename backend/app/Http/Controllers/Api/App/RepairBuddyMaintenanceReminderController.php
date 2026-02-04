<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyMaintenanceReminder;
use App\Models\RepairBuddyMaintenanceReminderLog;
use App\Models\RepairBuddyDeviceBrand;
use App\Models\RepairBuddyDeviceType;
use App\Models\Tenant;
use App\Support\PlatformAudit;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class RepairBuddyMaintenanceReminderController extends Controller
{
    public function index(Request $request, string $business)
    {
        $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $q = is_string($request->input('q')) ? trim((string) $request->input('q')) : '';

        $query = RepairBuddyMaintenanceReminder::query()->with(['deviceType', 'deviceBrand'])->orderByDesc('id');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('id', $q);
            });
        }

        $reminders = $query->limit(500)->get();

        return response()->json([
            'reminders' => $reminders->map(fn (RepairBuddyMaintenanceReminder $r) => $this->serializeReminder($r))->values(),
        ]);
    }

    public function store(Request $request, string $business)
    {
        $validated = $this->validatePayload($request);

        $tenant = TenantContext::tenant();
        if (! $tenant instanceof Tenant) {
            return response()->json(['message' => 'Tenant is missing.'], 400);
        }

        $reminder = RepairBuddyMaintenanceReminder::query()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'interval_days' => (int) $validated['interval_days'],
            'device_type_id' => $validated['device_type_id'] ?? null,
            'device_brand_id' => $validated['device_brand_id'] ?? null,
            'email_enabled' => (bool) ($validated['email_enabled'] ?? false),
            'sms_enabled' => (bool) ($validated['sms_enabled'] ?? false),
            'reminder_enabled' => (bool) ($validated['reminder_enabled'] ?? true),
            'email_body' => $validated['email_body'] ?? null,
            'sms_body' => $validated['sms_body'] ?? null,
            'created_by_user_id' => $request->user()?->id,
            'updated_by_user_id' => $request->user()?->id,
        ]);

        PlatformAudit::log($request, 'repairbuddy.maintenance_reminders.created', $tenant, null, [
            'reminder_id' => $reminder->id,
        ]);

        return response()->json([
            'reminder' => $this->serializeReminder($reminder->fresh(['deviceType', 'deviceBrand'])),
        ], 201);
    }

    public function update(Request $request, string $business, int $id)
    {
        $validated = $this->validatePayload($request, isUpdate: true);

        $tenant = TenantContext::tenant();
        if (! $tenant instanceof Tenant) {
            return response()->json(['message' => 'Tenant is missing.'], 400);
        }

        $reminder = RepairBuddyMaintenanceReminder::query()->whereKey($id)->first();
        if (! $reminder) {
            return response()->json(['message' => 'Reminder not found.'], 404);
        }

        $before = $reminder->toArray();

        $reminder->forceFill([
            'name' => array_key_exists('name', $validated) ? $validated['name'] : $reminder->name,
            'description' => array_key_exists('description', $validated) ? $validated['description'] : $reminder->description,
            'interval_days' => array_key_exists('interval_days', $validated) ? (int) $validated['interval_days'] : $reminder->interval_days,
            'device_type_id' => array_key_exists('device_type_id', $validated) ? $validated['device_type_id'] : $reminder->device_type_id,
            'device_brand_id' => array_key_exists('device_brand_id', $validated) ? $validated['device_brand_id'] : $reminder->device_brand_id,
            'email_enabled' => array_key_exists('email_enabled', $validated) ? (bool) $validated['email_enabled'] : $reminder->email_enabled,
            'sms_enabled' => array_key_exists('sms_enabled', $validated) ? (bool) $validated['sms_enabled'] : $reminder->sms_enabled,
            'reminder_enabled' => array_key_exists('reminder_enabled', $validated) ? (bool) $validated['reminder_enabled'] : $reminder->reminder_enabled,
            'email_body' => array_key_exists('email_body', $validated) ? $validated['email_body'] : $reminder->email_body,
            'sms_body' => array_key_exists('sms_body', $validated) ? $validated['sms_body'] : $reminder->sms_body,
            'updated_by_user_id' => $request->user()?->id,
        ])->save();

        PlatformAudit::log($request, 'repairbuddy.maintenance_reminders.updated', $tenant, null, [
            'reminder_id' => $reminder->id,
            'before' => $before,
            'after' => $reminder->toArray(),
        ]);

        return response()->json([
            'reminder' => $this->serializeReminder($reminder->fresh(['deviceType', 'deviceBrand'])),
        ]);
    }

    public function destroy(Request $request, string $business, int $id)
    {
        $tenant = TenantContext::tenant();
        if (! $tenant instanceof Tenant) {
            return response()->json(['message' => 'Tenant is missing.'], 400);
        }

        $reminder = RepairBuddyMaintenanceReminder::query()->whereKey($id)->first();
        if (! $reminder) {
            return response()->json(['message' => 'Reminder not found.'], 404);
        }

        $snapshot = $reminder->toArray();
        $reminder->delete();

        PlatformAudit::log($request, 'repairbuddy.maintenance_reminders.deleted', $tenant, null, [
            'before' => $snapshot,
        ]);

        return response()->json(['ok' => true]);
    }

    public function test(Request $request, string $business, int $id)
    {
        $validated = $request->validate([
            'email' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:64'],
        ]);

        $tenant = TenantContext::tenant();
        if (! $tenant instanceof Tenant) {
            return response()->json(['message' => 'Tenant is missing.'], 400);
        }

        $reminder = RepairBuddyMaintenanceReminder::query()->whereKey($id)->first();
        if (! $reminder) {
            return response()->json(['message' => 'Reminder not found.'], 404);
        }

        $email = is_string($validated['email'] ?? null) ? trim((string) $validated['email']) : '';
        $phone = is_string($validated['phone'] ?? null) ? trim((string) $validated['phone']) : '';

        if ($email === '' && $phone === '') {
            return response()->json([
                'message' => 'Email or phone is required.',
            ], 422);
        }

        $unsubscribeUrl = URL::temporarySignedRoute('public.maintenance-reminders.unsubscribe', now()->addDays(7), [
            'business' => $business,
            'jobId' => 0,
        ]);

        $vars = [
            '{{customer_name}}' => 'John Doe',
            '{{device_name}}' => 'TEST Device',
            '{{unsubscribe_device}}' => $unsubscribeUrl,
        ];

        $emailStatus = 'skipped';
        $smsStatus = 'skipped';

        if ($email !== '') {
            $emailStatus = 'sent';
            RepairBuddyMaintenanceReminderLog::query()->create([
                'reminder_id' => $reminder->id,
                'job_id' => null,
                'customer_id' => $request->user()?->id,
                'channel' => 'email',
                'to_address' => $email,
                'status' => $emailStatus,
                'error_message' => null,
            ]);
        }

        if ($phone !== '') {
            $smsStatus = 'failed';
            RepairBuddyMaintenanceReminderLog::query()->create([
                'reminder_id' => $reminder->id,
                'job_id' => null,
                'customer_id' => $request->user()?->id,
                'channel' => 'sms',
                'to_address' => $phone,
                'status' => $smsStatus,
                'error_message' => 'SMS provider is not configured.',
            ]);
        }

        PlatformAudit::log($request, 'repairbuddy.maintenance_reminders.test_sent', $tenant, null, [
            'reminder_id' => $reminder->id,
            'email' => $email !== '' ? $email : null,
            'phone' => $phone !== '' ? $phone : null,
        ]);

        $emailBody = is_string($reminder->email_body) ? (string) $reminder->email_body : '';
        $smsBody = is_string($reminder->sms_body) ? (string) $reminder->sms_body : '';

        $renderedEmail = strtr($emailBody, $vars);
        $renderedSms = strtr($smsBody, $vars);

        return response()->json([
            'ok' => true,
            'unsubscribe_url' => $unsubscribeUrl,
            'preview' => [
                'email' => $renderedEmail,
                'sms' => $renderedSms,
            ],
            'status' => [
                'email' => $emailStatus,
                'sms' => $smsStatus,
            ],
        ]);
    }

    private function validatePayload(Request $request, bool $isUpdate = false): array
    {
        $rules = [
            'name' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1024'],
            'interval_days' => [$isUpdate ? 'sometimes' : 'required', 'integer', 'min:1', 'max:3650'],
            'device_type_id' => ['sometimes', 'nullable', 'integer'],
            'device_brand_id' => ['sometimes', 'nullable', 'integer'],
            'email_enabled' => ['sometimes', 'boolean'],
            'sms_enabled' => ['sometimes', 'boolean'],
            'reminder_enabled' => ['sometimes', 'boolean'],
            'email_body' => ['sometimes', 'nullable', 'string'],
            'sms_body' => ['sometimes', 'nullable', 'string'],
        ];

        $validated = $request->validate($rules);

        if (array_key_exists('device_type_id', $validated) && $validated['device_type_id'] !== null) {
            $typeId = (int) $validated['device_type_id'];
            if ($typeId > 0 && ! RepairBuddyDeviceType::query()->whereKey($typeId)->exists()) {
                abort(response()->json(['message' => 'Device type is invalid.'], 422));
            }
            $validated['device_type_id'] = $typeId > 0 ? $typeId : null;
        }

        if (array_key_exists('device_brand_id', $validated) && $validated['device_brand_id'] !== null) {
            $brandId = (int) $validated['device_brand_id'];
            if ($brandId > 0 && ! RepairBuddyDeviceBrand::query()->whereKey($brandId)->exists()) {
                abort(response()->json(['message' => 'Device brand is invalid.'], 422));
            }
            $validated['device_brand_id'] = $brandId > 0 ? $brandId : null;
        }

        $emailEnabled = array_key_exists('email_enabled', $validated) ? (bool) $validated['email_enabled'] : null;
        $smsEnabled = array_key_exists('sms_enabled', $validated) ? (bool) $validated['sms_enabled'] : null;

        if ($emailEnabled === true) {
            if (! array_key_exists('email_body', $validated) || ! is_string($validated['email_body']) || trim((string) $validated['email_body']) === '') {
                abort(response()->json(['message' => 'Email body is required when email is enabled.'], 422));
            }
        }

        if ($smsEnabled === true) {
            if (! array_key_exists('sms_body', $validated) || ! is_string($validated['sms_body']) || trim((string) $validated['sms_body']) === '') {
                abort(response()->json(['message' => 'SMS body is required when SMS is enabled.'], 422));
            }
        }

        return $validated;
    }

    private function serializeReminder(RepairBuddyMaintenanceReminder $r): array
    {
        return [
            'id' => $r->id,
            'name' => $r->name,
            'description' => $r->description,
            'interval_days' => (int) $r->interval_days,
            'device_type_id' => $r->device_type_id,
            'device_brand_id' => $r->device_brand_id,
            'device_type_name' => $r->relationLoaded('deviceType') ? $r->deviceType?->name : null,
            'device_brand_name' => $r->relationLoaded('deviceBrand') ? $r->deviceBrand?->name : null,
            'email_enabled' => (bool) $r->email_enabled,
            'sms_enabled' => (bool) $r->sms_enabled,
            'reminder_enabled' => (bool) $r->reminder_enabled,
            'email_body' => $r->email_body,
            'sms_body' => $r->sms_body,
            'last_executed_at' => $r->last_executed_at,
            'created_at' => $r->created_at,
            'updated_at' => $r->updated_at,
        ];
    }
}
