<?php

namespace App\Http\Controllers\Tenant\Statuses;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Statuses\UpdatePaymentStatusDisplayRequest;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;

class PaymentStatusDisplayController extends Controller
{
    public function update(UpdatePaymentStatusDisplayRequest $request, string $slug): RedirectResponse
    {
        $tenantId = TenantContext::tenantId();
        $tenant = TenantContext::tenant();

        if (! $tenantId || ! $tenant instanceof Tenant) {
            abort(400, 'Tenant context is missing.');
        }

        $validated = $request->validated();

        $override = \App\Models\TenantStatusOverride::query()
            ->where('tenant_id', $tenantId)
            ->where('domain', 'payment')
            ->where('code', $slug)
            ->first();

        if (! $override) {
            \App\Models\TenantStatusOverride::query()->create([
                'tenant_id' => $tenantId,
                'domain' => 'payment',
                'code' => $slug,
                'label' => $validated['label'] ?? null,
                'color' => $validated['color'] ?? null,
                'sort_order' => $validated['sort_order'] ?? null,
            ]);
        } else {
            $override->forceFill([
                'label' => array_key_exists('label', $validated) ? $validated['label'] : $override->label,
                'color' => array_key_exists('color', $validated) ? $validated['color'] : $override->color,
                'sort_order' => array_key_exists('sort_order', $validated) ? $validated['sort_order'] : $override->sort_order,
            ])->save();
        }

        return redirect()
            ->to(route('tenant.dashboard', ['business' => $tenant->slug]).'?screen=settings')
            ->withFragment('wc_rb_payment_status')
            ->with('status', 'Payment status updated.')
            ->withInput();
    }
}
