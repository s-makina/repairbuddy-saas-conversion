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
            'estimates_enabled' => ['nullable', 'in:on'],
            'estimate_valid_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ];
    }
}
