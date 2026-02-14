<?php

namespace App\Http\Requests\Tenant\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStylingSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'delivery_date_label' => ['nullable', 'string', 'max:255'],
            'pickup_date_label' => ['nullable', 'string', 'max:255'],
            'nextservice_date_label' => ['nullable', 'string', 'max:255'],
            'casenumber_label' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'secondary_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ];
    }
}
