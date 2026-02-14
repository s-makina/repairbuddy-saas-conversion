<?php

namespace App\Http\Requests\Tenant\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCurrencySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wc_cr_selected_currency' => ['nullable', 'string', 'max:8'],
            'wc_cr_currency_position' => ['nullable', 'string', 'max:32'],
            'wc_cr_thousand_separator' => ['nullable', 'string', 'max:8'],
            'wc_cr_decimal_separator' => ['nullable', 'string', 'max:8'],
            'wc_cr_number_of_decimals' => ['nullable', 'integer', 'min:0', 'max:8'],
        ];
    }
}
