<?php

namespace App\Http\Requests\Tenant\Statuses;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentStatusDisplayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => ['sometimes', 'nullable', 'string', 'max:255'],
            'color' => ['sometimes', 'nullable', 'string', 'max:32'],
            'sort_order' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100000'],
        ];
    }
}
