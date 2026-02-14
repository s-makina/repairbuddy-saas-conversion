<?php

namespace App\Http\Requests\Tenant\MaintenanceReminders;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMaintenanceReminderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1024'],
            'interval_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'device_type_id' => ['sometimes', 'nullable', 'integer'],
            'device_brand_id' => ['sometimes', 'nullable', 'integer'],
            'email_enabled' => ['sometimes', 'nullable', 'string', 'in:active,inactive,on'],
            'sms_enabled' => ['sometimes', 'nullable', 'string', 'in:active,inactive,on'],
            'reminder_enabled' => ['sometimes', 'nullable', 'string', 'in:active,inactive,on'],
            'email_body' => ['sometimes', 'nullable', 'string'],
            'sms_body' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
