<?php

namespace App\Http\Controllers\Tenant\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Settings\UpdateAccountSettingsRequest;
use App\Models\Tenant;
use App\Services\TenantSettings\TenantSettingsStore;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;

class AccountSettingsController extends Controller
{
    public function update(UpdateAccountSettingsRequest $request): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validated();

        $store = new TenantSettingsStore($tenant);

        $account = $store->get('account', []);
        if (! is_array($account)) {
            $account = [];
        }

        $account['customerRegistration'] = (string) ($validated['customer_registration'] ?? 'off') === 'on';
        $account['accountApprovalRequired'] = (string) ($validated['account_approval_required'] ?? 'off') === 'on';
        $account['defaultCustomerRole'] = $validated['default_customer_role'] ?? 'customer';

        $store->set('account', $account);
        $tenant->save();

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]).'?screen=settings')
            ->withFragment('wc_rb_manage_account')
            ->with('status', 'Account settings updated.')
            ->withInput();
    }
}
