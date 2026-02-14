<?php

namespace App\Http\Requests\Tenant\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_registration' => ['nullable', 'in:on'],
            'account_approval_required' => ['nullable', 'in:on'],
            'default_customer_role' => ['nullable', 'in:customer,vip_customer'],
        ];
    }
}
