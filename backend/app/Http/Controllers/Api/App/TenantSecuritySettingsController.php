<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantSecuritySetting;
use App\Support\PlatformAudit;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class TenantSecuritySettingsController extends Controller
{
    private function defaults(): array
    {
        return [
            'mfa_required_roles' => [],
            'mfa_grace_period_days' => 7,
            'mfa_enforce_after' => null,
            'session_idle_timeout_minutes' => 60,
            'session_max_lifetime_days' => 30,
            'lockout_max_attempts' => 10,
            'lockout_duration_minutes' => 15,
        ];
    }

    public function show(Request $request)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            return response()->json([
                'message' => 'Tenant is missing.',
            ], 400);
        }

        $settings = TenantSecuritySetting::query()->where('tenant_id', $tenant->id)->first();

        $payload = array_merge($this->defaults(), $settings ? $settings->toArray() : []);

        return response()->json([
            'settings' => $payload,
        ]);
    }

    public function update(Request $request)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            return response()->json([
                'message' => 'Tenant is missing.',
            ], 400);
        }

        $tenantId = (int) $tenant->id;

        $validated = $request->validate([
            'mfa_required_roles' => ['nullable', 'array'],
            'mfa_required_roles.*' => ['integer'],
            'mfa_grace_period_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'session_idle_timeout_minutes' => ['nullable', 'integer', 'min:5', 'max:1440'],
            'session_max_lifetime_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'lockout_max_attempts' => ['nullable', 'integer', 'min:1', 'max:100'],
            'lockout_duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
        ]);

        $settings = TenantSecuritySetting::query()->firstOrNew([
            'tenant_id' => $tenantId,
        ]);

        $before = array_merge($this->defaults(), $settings->exists ? $settings->toArray() : []);

        $roleIds = array_values(array_unique(array_filter(array_map(function ($v) {
            return is_numeric($v) ? (int) $v : null;
        }, $validated['mfa_required_roles'] ?? []), function ($v) {
            return is_int($v) && $v > 0;
        })));

        if (count($roleIds) > 0) {
            $validRoleCount = Role::query()->where('tenant_id', $tenantId)->whereIn('id', $roleIds)->count();
            if ($validRoleCount !== count($roleIds)) {
                return response()->json([
                    'message' => 'One or more selected roles are invalid.',
                ], 422);
            }
        }

        $defaults = $this->defaults();

        $newGrace = array_key_exists('mfa_grace_period_days', $validated)
            ? (int) $validated['mfa_grace_period_days']
            : (int) ($settings->mfa_grace_period_days ?? $defaults['mfa_grace_period_days']);

        $previousRoles = is_array($settings->mfa_required_roles) ? $settings->mfa_required_roles : [];
        $previousRoles = array_values(array_unique(array_filter(array_map(function ($v) {
            return is_numeric($v) ? (int) $v : null;
        }, $previousRoles), function ($v) {
            return is_int($v) && $v > 0;
        })));

        $isPreviousRequiring = count($previousRoles) > 0;
        $isNewRequiring = count($roleIds) > 0;

        $settings->forceFill([
            'mfa_required_roles' => $roleIds,
            'mfa_grace_period_days' => $newGrace,
            'session_idle_timeout_minutes' => array_key_exists('session_idle_timeout_minutes', $validated)
                ? (int) $validated['session_idle_timeout_minutes']
                : (int) ($settings->session_idle_timeout_minutes ?? $defaults['session_idle_timeout_minutes']),
            'session_max_lifetime_days' => array_key_exists('session_max_lifetime_days', $validated)
                ? (int) $validated['session_max_lifetime_days']
                : (int) ($settings->session_max_lifetime_days ?? $defaults['session_max_lifetime_days']),
            'lockout_max_attempts' => array_key_exists('lockout_max_attempts', $validated)
                ? (int) $validated['lockout_max_attempts']
                : (int) ($settings->lockout_max_attempts ?? $defaults['lockout_max_attempts']),
            'lockout_duration_minutes' => array_key_exists('lockout_duration_minutes', $validated)
                ? (int) $validated['lockout_duration_minutes']
                : (int) ($settings->lockout_duration_minutes ?? $defaults['lockout_duration_minutes']),
        ]);

        if (! $isNewRequiring) {
            $settings->mfa_enforce_after = null;
        } else {
            $candidate = now()->addDays($newGrace);

            if (! $isPreviousRequiring || ! $settings->mfa_enforce_after) {
                $settings->mfa_enforce_after = $candidate;
            } else {
                if ($settings->mfa_enforce_after->lte(now())) {
                    $settings->mfa_enforce_after = $settings->mfa_enforce_after;
                } else {
                    $settings->mfa_enforce_after = $settings->mfa_enforce_after->gte($candidate) ? $settings->mfa_enforce_after : $candidate;
                }
            }
        }

        $settings->save();

        $after = array_merge($this->defaults(), $settings->fresh()->toArray());

        PlatformAudit::log($request, 'tenant.security_settings.updated', $tenant, null, [
            'before' => $before,
            'after' => $after,
        ]);

        return response()->json([
            'settings' => $after,
        ]);
    }
}
