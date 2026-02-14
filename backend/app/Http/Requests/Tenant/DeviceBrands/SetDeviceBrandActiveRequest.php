<?php

namespace App\Http\Requests\Tenant\DeviceBrands;

use Illuminate\Foundation\Http\FormRequest;

class SetDeviceBrandActiveRequest extends FormRequest
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
