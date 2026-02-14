<?php

namespace App\Http\Requests\Tenant\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wc_service_sidebar_description' => ['nullable', 'string', 'max:1000'],
            'wc_booking_on_service_page_status' => ['nullable', 'in:on'],
            'wc_service_booking_heading' => ['nullable', 'string', 'max:255'],
            'wc_service_booking_form' => ['nullable', 'in:with_type,without_type,warranty_booking'],
        ];
    }
}
