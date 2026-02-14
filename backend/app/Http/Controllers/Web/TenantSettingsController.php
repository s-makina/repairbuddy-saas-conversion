<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\TenantBootstrap\EnsureDefaultRepairBuddyStatuses;
use App\Support\TenantContext;
use App\ViewModels\Tenant\SettingsScreenViewModel;
use Illuminate\Http\Request;

class TenantSettingsController extends Controller
{
    public function show(Request $request)
    {
        $tenant = TenantContext::tenant();
        $tenantId = TenantContext::tenantId();

        if (! $request->user()) {
            return redirect()->route('web.login');
        }

        if (is_int($tenantId) && $tenantId > 0) {
            app(EnsureDefaultRepairBuddyStatuses::class)->ensure($tenantId);
        }

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $vm = new SettingsScreenViewModel($request, $tenant);

        return view('tenant.settings.index', $vm->toArray());
    }

    public function section(Request $request, string $business, string $section)
    {
        $tenant = TenantContext::tenant();
        $tenantId = TenantContext::tenantId();

        if (! $request->user()) {
            return redirect()->route('web.login');
        }

        if (is_int($tenantId) && $tenantId > 0) {
            app(EnsureDefaultRepairBuddyStatuses::class)->ensure($tenantId);
        }

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $section = trim((string) $section);
        $allowed = collect([
            'devices-brands' => 'tenant.settings.sections.devices-brands',
            'bookings' => 'tenant.settings.sections.bookings',
            'maintenance-reminders' => 'tenant.settings.sections.maintenance-reminders',
            'taxes' => 'tenant.settings.sections.taxes',
            'services' => 'tenant.settings.sections.services',
            'styling' => 'tenant.settings.sections.styling',
            'estimates' => 'tenant.settings.sections.estimates',
            'timelog' => 'tenant.settings.sections.timelog',
            'reviews' => 'tenant.settings.sections.reviews',
            'account' => 'tenant.settings.sections.account',
            'signature' => 'tenant.settings.sections.signature',
        ]);

        if (! $allowed->has($section)) {
            abort(404);
        }

        $vm = new SettingsScreenViewModel($request, $tenant);

        return view('tenant.settings.section', array_merge($vm->toArray(), [
            'pageTitle' => (string) (collect($vm->toArray()['extraTabs'] ?? [])
                ->first(fn ($t) => (string) ($t['view'] ?? '') === (string) $allowed->get($section))['label'] ?? 'Settings'),
            'settingsSectionView' => (string) $allowed->get($section),
        ]));
    }
}
