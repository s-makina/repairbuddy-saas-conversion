<?php

namespace App\Http\Controllers\Tenant\Statuses;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Statuses\UpsertPaymentStatusRequest;
use App\Models\Status;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentStatusController extends Controller
{
    public function save(UpsertPaymentStatusRequest $request): RedirectResponse
    {
        $tenantId = TenantContext::tenantId();
        $tenant = TenantContext::tenant();

        if (! $tenantId || ! $tenant instanceof Tenant) {
            abort(400, 'Tenant context is missing.');
        }

        $validated = $request->validated();

        $statusValue = (string) ($validated['payment_status_status'] ?? 'active');
        $isActive = $statusValue === 'active';

        $mode = (string) ($validated['form_type_status_payment'] ?? 'add');

        if ($mode === 'update') {
            $id = (int) ($validated['status_id'] ?? 0);
            if ($id <= 0) {
                return $this->redirectToSettings($tenant)
                    ->withFragment('wc_rb_payment_status')
                    ->withErrors(['status_id' => 'Payment status id is missing.'])
                    ->withInput();
            }

            $existing = Status::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('status_type', 'Payment')
                ->whereKey($id)
                ->first();

            if (! $existing) {
                return $this->redirectToSettings($tenant)
                    ->withFragment('wc_rb_payment_status')
                    ->withErrors(['status_id' => 'Payment status not found.'])
                    ->withInput();
            }

            $labelExists = Status::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('status_type', 'Payment')
                ->where('label', (string) $validated['payment_status_name'])
                ->whereKeyNot($existing->id)
                ->exists();

            if ($labelExists) {
                return $this->redirectToSettings($tenant)
                    ->withFragment('wc_rb_payment_status')
                    ->withErrors(['payment_status_name' => 'This status already exists.'])
                    ->withInput();
            }

            $existing->forceFill([
                'label' => (string) $validated['payment_status_name'],
                'is_active' => $isActive,
            ])->save();
        } else {
            $labelExists = Status::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('status_type', 'Payment')
                ->where('label', (string) $validated['payment_status_name'])
                ->exists();

            if ($labelExists) {
                return $this->redirectToSettings($tenant)
                    ->withFragment('wc_rb_payment_status')
                    ->withErrors(['payment_status_name' => 'This status already exists.'])
                    ->withInput();
            }

            Status::query()->create([
                'tenant_id' => $tenantId,
                'status_type' => 'Payment',
                'label' => (string) $validated['payment_status_name'],
                'email_enabled' => false,
                'email_template' => null,
                'sms_enabled' => false,
                'is_active' => $isActive,
            ]);
        }

        return $this->redirectToSettings($tenant)
            ->withFragment('wc_rb_payment_status')
            ->with('status', 'Payment status updated.')
            ->withInput();
    }

    public function toggle(Request $request, string $status): RedirectResponse
    {
        $tenantId = TenantContext::tenantId();
        $tenant = TenantContext::tenant();

        if (! $tenantId || ! $tenant instanceof Tenant) {
            abort(400, 'Tenant context is missing.');
        }

        $statusId = ctype_digit($status) ? (int) $status : 0;
        if ($statusId <= 0) {
            return $this->redirectToSettings($tenant)
                ->withFragment('wc_rb_payment_status')
                ->with('status', 'Payment status not found.');
        }

        $existing = Status::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status_type', 'Payment')
            ->whereKey($statusId)
            ->first();

        if (! $existing) {
            return $this->redirectToSettings($tenant)
                ->withFragment('wc_rb_payment_status')
                ->with('status', 'Payment status not found.');
        }

        $existing->forceFill([
            'is_active' => ! (bool) $existing->is_active,
        ])->save();

        return $this->redirectToSettings($tenant)
            ->withFragment('wc_rb_payment_status')
            ->with('status', 'Payment status updated.');
    }

    private function redirectToSettings(Tenant $tenant): RedirectResponse
    {
        return redirect()->to(route('tenant.dashboard', ['business' => $tenant->slug]).'?screen=settings');
    }
}
