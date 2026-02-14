<?php

namespace App\Http\Requests\Tenant\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSmsSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sms_settings_form' => ['nullable', 'in:1'],
            'wc_rb_job_status_include_present' => ['nullable', 'in:1'],

            'wc_rb_sms_active' => ['nullable', 'in:YES,on'],
            'wc_rb_sms_gateway' => ['nullable', 'string', 'max:64'],
            'sms_gateway_account_sid' => ['nullable', 'string', 'max:255'],
            'sms_gateway_auth_token' => ['nullable', 'string', 'max:255'],
            'sms_gateway_from_number' => ['nullable', 'string', 'max:64'],
            'wc_rb_job_status_include' => ['nullable', 'array'],
            'wc_rb_job_status_include.*' => ['string', 'max:64'],

            'sms_test' => ['nullable', 'in:1'],
            'sms_test_number' => ['nullable', 'string', 'max:64', 'required_if:sms_test,1'],
            'sms_test_message' => ['nullable', 'string', 'max:1024', 'required_if:sms_test,1'],
        ];
    }
}
