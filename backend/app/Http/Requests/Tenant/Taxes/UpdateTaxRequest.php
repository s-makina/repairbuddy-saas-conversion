<?php

namespace App\Http\Requests\Tenant\Taxes;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'edit_tax_id' => ['nullable', 'integer'],
            'edit_tax_name' => ['required', 'string', 'max:255'],
            'edit_tax_description' => ['nullable', 'string', 'max:2000'],
            'edit_tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'edit_tax_status' => ['nullable', 'in:active,inactive'],
        ];
    }
}
