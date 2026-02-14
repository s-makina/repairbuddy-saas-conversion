<?php

namespace App\Http\Requests\Tenant\Statuses;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status_name' => ['required', 'string', 'max:255'],
            'status_description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'invoice_label' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status_status' => ['sometimes', 'in:active,inactive'],
            'statusEmailMessage' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'editing_status_id' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
