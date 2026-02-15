<?php

namespace App\Http\Controllers\Web\Operations;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyJobItem;
use App\Models\RepairBuddyService;
use App\Models\RepairBuddyServicePriceOverride;
use App\Models\RepairBuddyServiceType;
use App\Models\RepairBuddyTax;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class ServiceOperationsController extends Controller
{
    public function index(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        return view('tenant.operations.services.index', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'operations',
            'pageTitle' => __('Services'),
        ]);
    }

    public function datatable(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            return response()->json(['message' => 'Tenant is missing.'], 400);
        }

        $query = RepairBuddyService::query()
            ->with(['type', 'tax'])
            ->orderByDesc('is_active')
            ->orderBy('name');

        return DataTables::eloquent($query)
            ->addColumn('type_display', function (RepairBuddyService $service) {
                return (string) ($service->type?->name ?? '');
            })
            ->addColumn('base_price_display', function (RepairBuddyService $service) {
                $amountCents = is_numeric($service->base_price_amount_cents) ? (int) $service->base_price_amount_cents : null;
                $currency = is_string($service->base_price_currency) && $service->base_price_currency !== ''
                    ? (string) $service->base_price_currency
                    : null;

                if ($amountCents === null || $currency === null) {
                    return '';
                }

                $amount = number_format($amountCents / 100, 2, '.', '');
                return e($currency) . ' ' . e($amount);
            })
            ->addColumn('tax_display', function (RepairBuddyService $service) {
                $tax = $service->tax;
                if (! $tax instanceof RepairBuddyTax) {
                    return '';
                }

                $rateUi = rtrim(rtrim((string) $tax->rate, '0'), '.');
                return e((string) $tax->name) . ' (' . e($rateUi) . '%)';
            })
            ->addColumn('status_display', function (RepairBuddyService $service) {
                if ($service->is_active) {
                    return '<span class="wcrb-pill wcrb-pill--active">' . e(__('Active')) . '</span>';
                }

                return '<span class="wcrb-pill wcrb-pill--inactive">' . e(__('Inactive')) . '</span>';
            })
            ->addColumn('actions_display', function (RepairBuddyService $service) use ($tenant) {
                $editUrl = route('tenant.operations.services.edit', ['business' => $tenant->slug, 'service' => $service->id]);
                $activeUrl = route('tenant.operations.services.active', ['business' => $tenant->slug, 'service' => $service->id]);
                $deleteUrl = route('tenant.operations.services.delete', ['business' => $tenant->slug, 'service' => $service->id]);
                $csrf = csrf_field();
                $activeValue = $service->is_active ? '0' : '1';
                $activeLabel = $service->is_active ? __('Deactivate') : __('Activate');

                return '<div class="d-inline-flex gap-2">'
                    . '<a class="btn btn-sm btn-outline-primary" href="' . e($editUrl) . '" title="' . e(__('Edit')) . '" aria-label="' . e(__('Edit')) . '"><i class="bi bi-pencil"></i></a>'
                    . '<form method="post" action="' . e($activeUrl) . '">' . $csrf
                    . '<input type="hidden" name="is_active" value="' . e($activeValue) . '" />'
                    . '<button type="submit" class="btn btn-sm btn-outline-secondary" title="' . e($activeLabel) . '" aria-label="' . e($activeLabel) . '">'
                    . ($service->is_active ? '<i class="bi bi-toggle-off"></i>' : '<i class="bi bi-toggle-on"></i>')
                    . '</button>'
                    . '</form>'
                    . '<form method="post" action="' . e($deleteUrl) . '">' . $csrf
                    . '<button type="submit" class="btn btn-sm btn-outline-danger" title="' . e(__('Delete')) . '" aria-label="' . e(__('Delete')) . '"><i class="bi bi-trash"></i></button>'
                    . '</form>'
                    . '</div>';
            })
            ->rawColumns(['status_display', 'actions_display'])
            ->toJson();
    }

    public function create(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $recentServices = RepairBuddyService::query()
            ->with(['type'])
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $typeOptions = RepairBuddyServiceType::query()
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name'])
            ->mapWithKeys(fn (RepairBuddyServiceType $t) => [(string) $t->id => (string) $t->name])
            ->prepend((string) __('None'), '')
            ->all();

        $taxOptions = RepairBuddyTax::query()
            ->orderByDesc('is_default')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->limit(500)
            ->get()
            ->mapWithKeys(function (RepairBuddyTax $t) {
                $rateUi = rtrim(rtrim((string) $t->rate, '0'), '.');
                return [(string) $t->id => (string) $t->name . ' (' . $rateUi . '%)'];
            })
            ->prepend((string) __('Select tax'), '')
            ->all();

        $tenantCurrency = is_string($tenant->currency) && $tenant->currency !== '' ? strtoupper((string) $tenant->currency) : '';

        return view('tenant.operations.services.create', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'operations',
            'pageTitle' => __('Add Service'),
            'recentServices' => $recentServices,
            'typeOptions' => $typeOptions,
            'taxOptions' => $taxOptions,
            'tenantCurrency' => $tenantCurrency,
        ]);
    }

    public function edit(Request $request, string $business, int $service)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $model = RepairBuddyService::query()->with(['type', 'tax'])->whereKey($service)->firstOrFail();

        $typeOptions = RepairBuddyServiceType::query()
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name'])
            ->mapWithKeys(fn (RepairBuddyServiceType $t) => [(string) $t->id => (string) $t->name])
            ->prepend((string) __('None'), '')
            ->all();

        $taxOptions = RepairBuddyTax::query()
            ->orderByDesc('is_default')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->limit(500)
            ->get()
            ->mapWithKeys(function (RepairBuddyTax $t) {
                $rateUi = rtrim(rtrim((string) $t->rate, '0'), '.');
                return [(string) $t->id => (string) $t->name . ' (' . $rateUi . '%)'];
            })
            ->prepend((string) __('Select tax'), '')
            ->all();

        $tenantCurrency = is_string($tenant->currency) && $tenant->currency !== '' ? strtoupper((string) $tenant->currency) : '';

        return view('tenant.operations.services.edit', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'operations',
            'pageTitle' => __('Edit Service'),
            'service' => $model,
            'typeOptions' => $typeOptions,
            'taxOptions' => $taxOptions,
            'tenantCurrency' => $tenantCurrency,
        ]);
    }

    public function store(Request $request, string $business): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'service_type_id' => ['sometimes', 'nullable'],
            'service_code' => ['sometimes', 'nullable', 'string', 'max:128'],
            'time_required' => ['sometimes', 'nullable', 'string', 'max:128'],
            'warranty' => ['sometimes', 'nullable', 'string', 'max:255'],
            'pick_up_delivery_available' => ['sometimes', 'nullable', 'boolean'],
            'laptop_rental_available' => ['sometimes', 'nullable', 'boolean'],
            'base_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'base_price_currency' => ['sometimes', 'nullable', 'string', 'max:8'],
            'tax_id' => ['sometimes', 'nullable'],
        ]);

        $name = trim((string) $validated['name']);
        $serviceTypeIdRaw = $validated['service_type_id'] ?? null;
        $serviceTypeId = is_numeric($serviceTypeIdRaw) ? (int) $serviceTypeIdRaw : null;
        $taxIdRaw = $validated['tax_id'] ?? null;
        $taxId = is_numeric($taxIdRaw) ? (int) $taxIdRaw : null;

        if ($serviceTypeId !== null && ! RepairBuddyServiceType::query()->whereKey($serviceTypeId)->exists()) {
            return redirect()
                ->route('tenant.operations.services.create', ['business' => $tenant->slug])
                ->withErrors(['service_type_id' => __('Service type is invalid.')])
                ->withInput();
        }

        if ($taxId !== null && ! RepairBuddyTax::query()->whereKey($taxId)->exists()) {
            return redirect()
                ->route('tenant.operations.services.create', ['business' => $tenant->slug])
                ->withErrors(['tax_id' => __('Tax is invalid.')])
                ->withInput();
        }

        $basePriceCents = null;
        if (array_key_exists('base_price', $validated) && $validated['base_price'] !== null && $validated['base_price'] !== '') {
            $basePriceCents = (int) round(((float) $validated['base_price']) * 100);
        }

        $baseCurrency = array_key_exists('base_price_currency', $validated) && is_string($validated['base_price_currency']) && trim((string) $validated['base_price_currency']) !== ''
            ? strtoupper(trim((string) $validated['base_price_currency']))
            : null;

        if ($basePriceCents !== null && ($baseCurrency === null || $baseCurrency === '')) {
            $tenantCurrency = is_string($tenant->currency) && $tenant->currency !== '' ? strtoupper((string) $tenant->currency) : '';
            if ($tenantCurrency === '') {
                return redirect()
                    ->route('tenant.operations.services.create', ['business' => $tenant->slug])
                    ->withErrors(['base_price_currency' => __('Tenant currency is not configured.')])
                    ->withInput();
            }
            $baseCurrency = $tenantCurrency;
        }

        if ($basePriceCents === null) {
            $baseCurrency = null;
        }

        if (RepairBuddyService::query()->where('name', $name)->exists()) {
            return redirect()
                ->route('tenant.operations.services.index', ['business' => $tenant->slug])
                ->withErrors(['name' => __('Service already exists.')])
                ->withInput();
        }

        RepairBuddyService::query()->create([
            'name' => $name,
            'description' => is_string($validated['description'] ?? null) ? $validated['description'] : null,
            'service_type_id' => $serviceTypeId,
            'service_code' => $validated['service_code'] ?? null,
            'time_required' => $validated['time_required'] ?? null,
            'warranty' => $validated['warranty'] ?? null,
            'pick_up_delivery_available' => (bool) ($validated['pick_up_delivery_available'] ?? false),
            'laptop_rental_available' => (bool) ($validated['laptop_rental_available'] ?? false),
            'base_price_amount_cents' => $basePriceCents,
            'base_price_currency' => $baseCurrency,
            'tax_id' => $taxId,
            'is_active' => true,
        ]);

        return redirect()
            ->route('tenant.operations.services.index', ['business' => $tenant->slug])
            ->with('status', __('Service added.'));
    }

    public function update(Request $request, string $business, int $service): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'service_type_id' => ['sometimes', 'nullable'],
            'service_code' => ['sometimes', 'nullable', 'string', 'max:128'],
            'time_required' => ['sometimes', 'nullable', 'string', 'max:128'],
            'warranty' => ['sometimes', 'nullable', 'string', 'max:255'],
            'pick_up_delivery_available' => ['sometimes', 'nullable', 'boolean'],
            'laptop_rental_available' => ['sometimes', 'nullable', 'boolean'],
            'base_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'base_price_currency' => ['sometimes', 'nullable', 'string', 'max:8'],
            'tax_id' => ['sometimes', 'nullable'],
        ]);

        $model = RepairBuddyService::query()->whereKey($service)->firstOrFail();

        $name = trim((string) $validated['name']);
        $serviceTypeIdRaw = $validated['service_type_id'] ?? null;
        $serviceTypeId = is_numeric($serviceTypeIdRaw) ? (int) $serviceTypeIdRaw : null;
        $taxIdRaw = $validated['tax_id'] ?? null;
        $taxId = is_numeric($taxIdRaw) ? (int) $taxIdRaw : null;

        if ($serviceTypeId !== null && ! RepairBuddyServiceType::query()->whereKey($serviceTypeId)->exists()) {
            return redirect()
                ->route('tenant.operations.services.edit', ['business' => $tenant->slug, 'service' => $model->id])
                ->withErrors(['service_type_id' => __('Service type is invalid.')])
                ->withInput();
        }

        if ($taxId !== null && ! RepairBuddyTax::query()->whereKey($taxId)->exists()) {
            return redirect()
                ->route('tenant.operations.services.edit', ['business' => $tenant->slug, 'service' => $model->id])
                ->withErrors(['tax_id' => __('Tax is invalid.')])
                ->withInput();
        }

        $basePriceCents = $model->base_price_amount_cents;
        if (array_key_exists('base_price', $validated)) {
            if ($validated['base_price'] === null || $validated['base_price'] === '') {
                $basePriceCents = null;
            } else {
                $basePriceCents = (int) round(((float) $validated['base_price']) * 100);
            }
        }

        $baseCurrency = $model->base_price_currency;
        if (array_key_exists('base_price_currency', $validated)) {
            $baseCurrency = is_string($validated['base_price_currency']) && trim((string) $validated['base_price_currency']) !== ''
                ? strtoupper(trim((string) $validated['base_price_currency']))
                : null;
        }

        if ($basePriceCents !== null && (! is_string($baseCurrency) || $baseCurrency === '')) {
            $tenantCurrency = is_string($tenant->currency) && $tenant->currency !== '' ? strtoupper((string) $tenant->currency) : '';
            if ($tenantCurrency === '') {
                return redirect()
                    ->route('tenant.operations.services.edit', ['business' => $tenant->slug, 'service' => $model->id])
                    ->withErrors(['base_price_currency' => __('Tenant currency is not configured.')])
                    ->withInput();
            }
            $baseCurrency = $tenantCurrency;
        }

        if ($basePriceCents === null) {
            $baseCurrency = null;
        }

        if (RepairBuddyService::query()
            ->where('name', $name)
            ->where('id', '!=', $model->id)
            ->exists()) {
            return redirect()
                ->route('tenant.operations.services.index', ['business' => $tenant->slug])
                ->withErrors(['name' => __('Service already exists.')])
                ->withInput();
        }

        $model->forceFill([
            'name' => $name,
            'description' => array_key_exists('description', $validated) ? ($validated['description'] ?? null) : $model->description,
            'service_type_id' => $serviceTypeId,
            'service_code' => array_key_exists('service_code', $validated) ? ($validated['service_code'] ?? null) : $model->service_code,
            'time_required' => array_key_exists('time_required', $validated) ? ($validated['time_required'] ?? null) : $model->time_required,
            'warranty' => array_key_exists('warranty', $validated) ? ($validated['warranty'] ?? null) : $model->warranty,
            'pick_up_delivery_available' => (bool) ($validated['pick_up_delivery_available'] ?? false),
            'laptop_rental_available' => (bool) ($validated['laptop_rental_available'] ?? false),
            'base_price_amount_cents' => $basePriceCents,
            'base_price_currency' => $baseCurrency,
            'tax_id' => $taxId,
        ])->save();

        return redirect()
            ->route('tenant.operations.services.index', ['business' => $tenant->slug])
            ->with('status', __('Service updated.'));
    }

    public function setActive(Request $request, string $business, int $service): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $model = RepairBuddyService::query()->whereKey($service)->firstOrFail();
        $model->forceFill([
            'is_active' => (bool) $validated['is_active'],
        ])->save();

        return redirect()
            ->route('tenant.operations.services.index', ['business' => $tenant->slug])
            ->with('status', __('Service updated.'));
    }

    public function delete(Request $request, string $business, int $service): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $model = RepairBuddyService::query()->whereKey($service)->firstOrFail();

        $inUseByOverrides = RepairBuddyServicePriceOverride::query()->where('service_id', $model->id)->exists();
        $inUseByJobItems = RepairBuddyJobItem::query()
            ->where('item_type', 'service')
            ->where('ref_id', $model->id)
            ->exists();

        if ($inUseByOverrides || $inUseByJobItems) {
            return redirect()
                ->route('tenant.operations.services.index', ['business' => $tenant->slug])
                ->withErrors(['service' => __('Service is in use and cannot be deleted.')]);
        }

        $model->delete();

        return redirect()
            ->route('tenant.operations.services.index', ['business' => $tenant->slug])
            ->with('status', __('Service deleted.'));
    }
}
