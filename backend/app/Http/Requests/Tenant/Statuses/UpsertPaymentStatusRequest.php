<?php

namespace App\Http\Requests\Tenant\Statuses;

use Illuminate\Foundation\Http\FormRequest;

class UpsertPaymentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_status_name' => ['required', 'string', 'max:255'],
            'payment_status_status' => ['sometimes', 'in:active,inactive'],
            'form_type_status_payment' => ['sometimes', 'in:add,update'],
            'status_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ];
    }
}
