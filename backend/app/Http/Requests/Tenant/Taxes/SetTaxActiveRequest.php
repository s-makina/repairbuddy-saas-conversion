<?php

namespace App\Http\Requests\Tenant\Taxes;

use Illuminate\Foundation\Http\FormRequest;

class SetTaxActiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_active' => ['required', 'boolean'],
        ];
    }
}
