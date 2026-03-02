<?php

namespace App\Http\Requests\Tenant\Expenses;

use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseRequest extends FormRequest
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
            'expense_date' => ['sometimes', 'required', 'date'],
            'category_id' => ['sometimes', 'required', 'integer', 'exists:expense_categories,id'],
            'expense_type' => ['sometimes', 'string', 'in:' . implode(',', array_keys(Expense::TYPES))],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0', 'max:999999999.99'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'payment_method' => ['sometimes', 'string', 'in:' . implode(',', array_keys(Expense::PAYMENT_METHODS))],
            'payment_status' => ['sometimes', 'string', 'in:' . implode(',', array_keys(Expense::PAYMENT_STATUSES))],
            'receipt_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'job_id' => ['sometimes', 'nullable', 'integer', 'exists:rb_jobs,id'],
            'technician_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'expense_date.required' => __('The expense date is required.'),
            'expense_date.date' => __('Please enter a valid date.'),
            'category_id.required' => __('Please select a category.'),
            'category_id.exists' => __('The selected category is invalid.'),
            'amount.required' => __('The amount is required.'),
            'amount.numeric' => __('The amount must be a number.'),
            'amount.min' => __('The amount must be at least 0.'),
            'description.max' => __('The description cannot exceed 5000 characters.'),
        ];
    }
}
