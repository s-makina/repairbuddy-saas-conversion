<?php

namespace App\Http\Controllers\Tenant\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Settings\UpdateSmsSettingsRequest;
use App\Models\Status;
use App\Models\Tenant;
use App\Services\TenantSettings\TenantSettingsStore;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;

class SmsSettingsController extends Controller
{
    public function update(UpdateSmsSettingsRequest $request): RedirectResponse
    {
        $tenantId = TenantContext::tenantId();
        $tenant = TenantContext::tenant();

        if (! $tenantId || ! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validated();

        $isSettingsForm = array_key_exists('sms_settings_form', $validated);
        $isTestForm = array_key_exists('sms_test', $validated);

        $store = new TenantSettingsStore($tenant);

        $sms = $store->get('sms', []);
        if (! is_array($sms)) {
            $sms = [];
        }

        if ($isSettingsForm) {
            $isActive = $request->has('wc_rb_sms_active');
            $sms['activateSmsForSelectiveStatuses'] = $isActive;
            $sms['enabled'] = $isActive;

            $sms['gateway'] = (string) ($validated['wc_rb_sms_gateway'] ?? '');
            $sms['gatewayAccountSid'] = $validated['sms_gateway_account_sid'] ?? null;
            $sms['gatewayAuthToken'] = $validated['sms_gateway_auth_token'] ?? null;
            $sms['gatewayFromNumber'] = $validated['sms_gateway_from_number'] ?? null;

            $allowedCodes = Status::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('status_type', 'Job')
                ->pluck('code')
                ->map(fn ($v) => is_string($v) ? trim($v) : '')
                ->filter(fn ($v) => $v !== '')
                ->unique()
                ->values()
                ->all();
            $allowedSet = array_fill_keys($allowedCodes, true);

            $includes = $validated['wc_rb_job_status_include'] ?? null;
            if (is_array($includes)) {
                $sms['sendWhenStatusChangedToIds'] = collect($includes)
                    ->filter(fn ($v) => is_string($v) && trim($v) !== '' && isset($allowedSet[trim($v)]))
                    ->map(fn ($v) => trim((string) $v))
                    ->unique()
                    ->values()
                    ->all();
            } elseif (array_key_exists('wc_rb_job_status_include_present', $validated)) {
                $sms['sendWhenStatusChangedToIds'] = [];
            }
        }

        if ($isTestForm) {
            $sms['testNumber'] = $validated['sms_test_number'] ?? null;
            $sms['testMessage'] = $validated['sms_test_message'] ?? null;
        }

        if (($sms['gateway'] ?? null) === 'custom') {
            $sms['apiKey'] = $sms['gatewayAccountSid'] ?? null;
            $sms['senderId'] = $sms['gatewayFromNumber'] ?? null;
        }

        $store->set('sms', $sms);
        $tenant->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]).'?screen=settings')
            ->withFragment('wc_rb_page_sms_IDENTIFIER')
            ->with('status', 'SMS settings updated.')
            ->withInput();
    }
}
