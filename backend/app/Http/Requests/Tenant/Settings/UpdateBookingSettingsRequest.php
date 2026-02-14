<?php

namespace App\Http\Requests\Tenant\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'booking_email_subject_to_customer' => ['nullable', 'string', 'max:255'],
            'booking_email_body_to_customer' => ['nullable', 'string', 'max:5000'],
            'booking_email_subject_to_admin' => ['nullable', 'string', 'max:255'],
            'booking_email_body_to_admin' => ['nullable', 'string', 'max:5000'],

            'wcrb_turn_booking_forms_to_jobs' => ['nullable', 'boolean'],
            'wcrb_turn_off_other_device_brands' => ['nullable', 'boolean'],
            'wcrb_turn_off_other_service' => ['nullable', 'boolean'],
            'wcrb_turn_off_service_price' => ['nullable', 'boolean'],
            'wcrb_turn_off_idimei_booking' => ['nullable', 'boolean'],

            'wc_booking_default_type' => ['nullable', 'integer', 'min:1'],
            'wc_booking_default_brand' => ['nullable', 'integer', 'min:1'],
            'wc_booking_default_device' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
