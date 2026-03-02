<?php

namespace App\Http\Requests\Tenant\Expenses;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseCategoryRequest extends FormRequest
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
            'category_name' => ['required', 'string', 'max:255'],
            'category_description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'category_type' => ['sometimes', 'nullable', 'string', 'max:50'],
            'color_code' => ['sometimes', 'nullable', 'string', 'max:20'],
            'sort_order' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
            'taxable' => ['sometimes', 'nullable', 'boolean'],
            'tax_rate' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            'parent_category_id' => ['sometimes', 'nullable', 'integer', 'exists:expense_categories,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'category_name.required' => __('The category name is required.'),
            'category_name.max' => __('The category name cannot exceed 255 characters.'),
            'tax_rate.max' => __('The tax rate cannot exceed 100%.'),
            'parent_category_id.exists' => __('The selected parent category is invalid.'),
        ];
    }
}
