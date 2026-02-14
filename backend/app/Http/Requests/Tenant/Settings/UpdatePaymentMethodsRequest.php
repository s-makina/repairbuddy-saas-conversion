<?php

namespace App\Http\Requests\Tenant\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentMethodsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wc_rb_payment_method' => ['sometimes', 'array'],
            'wc_rb_payment_method.*' => ['string', 'max:64'],
        ];
    }
}
