<?php

namespace App\Http\Requests\Tenant\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTimeLogSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'disable_timelog' => ['sometimes', 'nullable', 'in:on'],
            'default_tax_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'job_status_include' => ['sometimes', 'array'],
            'job_status_include.*' => ['string', 'max:64'],
            'activities' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
