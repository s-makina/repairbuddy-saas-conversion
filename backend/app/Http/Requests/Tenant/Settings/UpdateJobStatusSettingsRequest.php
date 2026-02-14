<?php

namespace App\Http\Requests\Tenant\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobStatusSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wcrb_job_status_delivered' => ['nullable', 'string', 'max:64'],
            'wcrb_job_status_cancelled' => ['nullable', 'string', 'max:64'],
        ];
    }
}
