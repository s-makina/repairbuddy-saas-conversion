<?php

namespace App\Http\Requests\Tenant\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEstimatesSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estimates_enabled' => ['sometimes', 'nullable', 'boolean'],
            'estimate_valid_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'wcrb_turn_booking_forms_to_jobs' => ['sometimes', 'nullable', 'boolean'],
            'estimate_email_subject_to_customer' => ['sometimes', 'nullable', 'string', 'max:255'],
            'estimate_email_body_to_customer' => ['sometimes', 'nullable', 'string'],
            'estimate_approve_email_subject_to_admin' => ['sometimes', 'nullable', 'string', 'max:255'],
            'estimate_approve_email_body_to_admin' => ['sometimes', 'nullable', 'string'],
            'estimate_reject_email_subject_to_admin' => ['sometimes', 'nullable', 'string', 'max:255'],
            'estimate_reject_email_body_to_admin' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
