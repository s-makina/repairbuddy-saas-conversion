<?php

namespace App\Http\Requests\Tenant\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoicesSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wcrb_add_invoice_qr_code' => ['nullable', 'boolean'],
            'wc_rb_io_thanks_msg' => ['nullable', 'string', 'max:255'],
            'wb_rb_invoice_type' => ['nullable', 'in:default,by_device,by_items'],
            'pickupdate' => ['nullable', 'in:show'],
            'deliverydate' => ['nullable', 'in:show'],
            'nextservicedate' => ['nullable', 'in:show'],
            'repair_order_type' => ['nullable', 'in:pos_type,invoice_type'],
            'business_terms' => ['nullable', 'string', 'max:2048'],
            'wc_repair_order_print_size' => ['nullable', 'in:default,a4,a5'],
            'wc_rb_cr_display_add_on_ro' => ['nullable', 'boolean'],
            'wc_rb_cr_display_add_on_ro_cu' => ['nullable', 'boolean'],
            'wc_rb_ro_thanks_msg' => ['nullable', 'string', 'max:255'],
        ];
    }
}
