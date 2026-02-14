<?php

namespace App\Http\Requests\Tenant\Taxes;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaxSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wc_use_taxes' => ['nullable', 'in:on'],
            'wc_primary_tax' => ['nullable', 'integer', 'min:1'],
            'wc_prices_inclu_exclu' => ['required', 'in:exclusive,inclusive'],
        ];
    }
}
