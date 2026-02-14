<?php

namespace App\Http\Requests\Tenant\Settings;

use App\Models\Role;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = TenantContext::tenantId();

        $roleNames = $tenantId
            ? Role::query()
                ->where('tenant_id', $tenantId)
                ->pluck('name')
                ->map(fn ($n) => (string) $n)
                ->filter(fn ($n) => trim($n) !== '')
                ->values()
                ->all()
            : [];

        $fallbackRoleNames = ['customer', 'vip_customer'];
        $allowedRoleNames = empty($roleNames)
            ? $fallbackRoleNames
            : array_values(array_unique(array_merge($fallbackRoleNames, $roleNames)));

        return [
            'customer_registration' => ['nullable', 'in:on,off'],
            'account_approval_required' => ['nullable', 'in:on,off'],
            'default_customer_role' => ['nullable', 'string', 'max:255', Rule::in($allowedRoleNames)],
        ];
    }
}
