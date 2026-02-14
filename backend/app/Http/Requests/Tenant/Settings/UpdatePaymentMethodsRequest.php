<?php

namespace App\Http\Requests\Tenant\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentMethodsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $raw = $this->input('wc_rb_payment_method');

        if (! is_array($raw)) {
            return;
        }

        $normalized = [];
        foreach ($raw as $v) {
            if (! is_scalar($v)) {
                continue;
            }
            $s = trim((string) $v);
            if ($s === '') {
                continue;
            }
            $normalized[] = $s;
        }

        $this->merge([
            'wc_rb_payment_method' => $normalized,
        ]);
    }

    public function rules(): array
    {
        return [
            'wc_rb_payment_method' => ['sometimes', 'array'],
            'wc_rb_payment_method.*' => ['string', 'max:64', 'in:cash,card,bank,woocommerce'],
        ];
    }
}
