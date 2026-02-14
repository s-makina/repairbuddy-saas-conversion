<?php

namespace App\Http\Requests\Tenant\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePagesSetupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wc_rb_my_account_page_id' => ['nullable', 'string', 'max:2048'],
            'wc_rb_status_check_page_id' => ['nullable', 'string', 'max:2048'],
            'wc_rb_get_feedback_page_id' => ['nullable', 'string', 'max:2048'],
            'wc_rb_device_booking_page_id' => ['nullable', 'string', 'max:2048'],
            'wc_rb_list_services_page_id' => ['nullable', 'string', 'max:2048'],
            'wc_rb_list_parts_page_id' => ['nullable', 'string', 'max:2048'],
            'wc_rb_customer_login_page' => ['nullable', 'string', 'max:2048'],
            'wc_rb_turn_registration_on' => ['nullable', 'in:on'],
        ];
    }
}
