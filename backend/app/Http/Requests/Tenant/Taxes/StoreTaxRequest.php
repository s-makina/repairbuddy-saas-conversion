<?php

namespace App\Http\Requests\Tenant\Taxes;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tax_name' => ['required', 'string', 'max:255'],
            'tax_description' => ['nullable', 'string', 'max:2000'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'tax_status' => ['nullable', 'in:active,inactive'],
        ];
    }
}
