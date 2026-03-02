<?php

namespace App\Http\Requests\Tenant\Expenses;

use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;

class SetExpenseStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:' . implode(',', array_keys(Expense::STATUSES))],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'status.required' => __('The status is required.'),
            'status.in' => __('Please select a valid status.'),
        ];
    }
}
