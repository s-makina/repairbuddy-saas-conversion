<?php

namespace App\Http\Requests\Tenant\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSignatureSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'signature_required' => ['nullable', 'in:on'],
            'signature_type' => ['nullable', 'in:draw,type,upload'],
            'signature_terms' => ['nullable', 'string', 'max:1000'],

			'pickup_enabled' => ['nullable', 'boolean'],
			'pickup_trigger_status' => ['nullable', 'string', 'max:64'],
			'pickup_email_subject' => ['nullable', 'string', 'max:200'],
			'pickup_email_template' => ['nullable', 'string', 'max:5000'],
			'pickup_sms_text' => ['nullable', 'string', 'max:500'],
			'pickup_after_status' => ['nullable', 'string', 'max:64'],

			'delivery_enabled' => ['nullable', 'boolean'],
			'delivery_trigger_status' => ['nullable', 'string', 'max:64'],
			'delivery_email_subject' => ['nullable', 'string', 'max:200'],
			'delivery_email_template' => ['nullable', 'string', 'max:5000'],
			'delivery_sms_text' => ['nullable', 'string', 'max:500'],
			'delivery_after_status' => ['nullable', 'string', 'max:64'],
        ];
    }
}
