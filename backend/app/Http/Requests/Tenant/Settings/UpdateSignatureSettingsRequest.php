<?php

namespace App\Http\Requests\Tenant\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSignatureSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'signature_required' => ['nullable', 'in:on'],
            'signature_type' => ['nullable', 'in:draw,type,upload'],
            'signature_terms' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
