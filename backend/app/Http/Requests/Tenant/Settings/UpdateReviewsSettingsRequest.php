<?php

namespace App\Http\Requests\Tenant\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReviewsSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'request_by_sms' => ['sometimes', 'nullable', 'in:on'],
            'request_by_email' => ['sometimes', 'nullable', 'in:on'],
            'get_feedback_page_url' => ['nullable', 'string', 'max:2048'],
            'send_request_job_status' => ['nullable', 'string', 'max:64'],
            'auto_request_interval' => ['nullable', 'in:disabled,one-notification,two-notifications'],
            'email_subject' => ['nullable', 'string', 'max:255'],
            'email_message' => ['nullable', 'string', 'max:5000'],
            'sms_message' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
