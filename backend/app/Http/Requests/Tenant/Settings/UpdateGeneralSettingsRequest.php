<?php

namespace App\Http\Requests\Tenant\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGeneralSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'menu_name' => ['nullable', 'string', 'max:255'],
            'wc_rb_business_name' => ['nullable', 'string', 'max:255'],
            'wc_rb_business_phone' => ['nullable', 'string', 'max:255'],
            'wc_rb_business_address' => ['nullable', 'string', 'max:255'],
            'computer_repair_logo' => ['nullable', 'string', 'max:2048'],
            'computer_repair_email' => ['nullable', 'string', 'max:255'],
            'case_number_prefix' => ['nullable', 'string', 'max:32'],
            'case_number_length' => ['nullable', 'integer', 'min:1', 'max:32'],
            'wc_job_status_cr_notice' => ['nullable', 'boolean'],
            'wcrb_attach_pdf_in_customer_emails' => ['nullable', 'boolean'],
            'wcrb_next_service_date' => ['nullable', 'boolean'],
            'wc_rb_gdpr_acceptance' => ['nullable', 'string', 'max:255'],
            'wc_rb_gdpr_acceptance_link_label' => ['nullable', 'string', 'max:255'],
            'wc_rb_gdpr_acceptance_link' => ['nullable', 'string', 'max:2048'],
            'wc_primary_country' => ['nullable', 'string', 'size:2'],
            'wc_enable_woo_products' => ['nullable', 'boolean'],
            'wcrb_disable_statuscheck_serial' => ['nullable', 'boolean'],
        ];
    }
}
