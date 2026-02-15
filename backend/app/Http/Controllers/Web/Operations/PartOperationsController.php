<?php

namespace App\Http\Controllers\Web\Operations;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\RepairBuddyDevice;
use App\Models\RepairBuddyDeviceBrand;
use App\Models\RepairBuddyDeviceType;
use App\Models\RepairBuddyPart;
use App\Models\RepairBuddyPartBrand;
use App\Models\RepairBuddyPartPriceOverride;
use App\Models\RepairBuddyPartType;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class PartOperationsController extends Controller
{
    public function index(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        return view('tenant.operations.parts.index', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'operations',
            'pageTitle' => __('Parts'),
        ]);
    }

    public function datatable(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            return response()->json(['message' => 'Tenant is missing.'], 400);
        }

        $query = RepairBuddyPart::query()
            ->with(['type', 'brand'])
            ->orderByDesc('is_active')
            ->orderBy('name');

        return DataTables::eloquent($query)
            ->addColumn('type_display', fn (RepairBuddyPart $part) => (string) ($part->type?->name ?? ''))
            ->addColumn('brand_display', fn (RepairBuddyPart $part) => (string) ($part->brand?->name ?? ''))
            ->addColumn('price_display', function (RepairBuddyPart $part) {
                $amountCents = is_numeric($part->price_amount_cents) ? (int) $part->price_amount_cents : null;
                $currency = is_string($part->price_currency) && $part->price_currency !== '' ? (string) $part->price_currency : null;

                if ($amountCents === null || $currency === null) {
                    return '';
                }

                $amount = number_format($amountCents / 100, 2, '.', '');

                return e($currency) . ' ' . e($amount);
            })
            ->addColumn('status_display', function (RepairBuddyPart $part) {
                if ($part->is_active) {
                    return '<span class="wcrb-pill wcrb-pill--active">' . e(__('Active')) . '</span>';
                }

                return '<span class="wcrb-pill wcrb-pill--inactive">' . e(__('Inactive')) . '</span>';
            })
            ->addColumn('actions_display', function (RepairBuddyPart $part) use ($tenant) {
                $editUrl = route('tenant.operations.parts.edit', ['business' => $tenant->slug, 'part' => $part->id]);
                $activeUrl = route('tenant.operations.parts.active', ['business' => $tenant->slug, 'part' => $part->id]);
                $deleteUrl = route('tenant.operations.parts.delete', ['business' => $tenant->slug, 'part' => $part->id]);
                $csrf = csrf_field();
                $activeValue = $part->is_active ? '0' : '1';
                $activeLabel = $part->is_active ? __('Deactivate') : __('Activate');

                return '<div class="d-inline-flex gap-2">'
                    . '<a class="btn btn-sm btn-outline-primary" href="' . e($editUrl) . '" title="' . e(__('Edit')) . '" aria-label="' . e(__('Edit')) . '"><i class="bi bi-pencil"></i></a>'
                    . '<form method="post" action="' . e($activeUrl) . '">' . $csrf
                    . '<input type="hidden" name="is_active" value="' . e($activeValue) . '" />'
                    . '<button type="submit" class="btn btn-sm btn-outline-secondary" title="' . e($activeLabel) . '" aria-label="' . e($activeLabel) . '">'
                    . ($part->is_active ? '<i class="bi bi-toggle-off"></i>' : '<i class="bi bi-toggle-on"></i>')
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

        $recentParts = RepairBuddyPart::query()
            ->with(['type', 'brand'])
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $typeOptions = RepairBuddyPartType::query()
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name'])
            ->mapWithKeys(fn (RepairBuddyPartType $t) => [(string) $t->id => (string) $t->name])
            ->prepend((string) __('None'), '')
            ->all();

        $brandOptions = RepairBuddyPartBrand::query()
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name'])
            ->mapWithKeys(fn (RepairBuddyPartBrand $b) => [(string) $b->id => (string) $b->name])
            ->prepend((string) __('None'), '')
            ->all();

        $tenantCurrency = is_string($tenant->currency) && $tenant->currency !== '' ? strtoupper((string) $tenant->currency) : '';

        return view('tenant.operations.parts.create', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'operations',
            'pageTitle' => __('Add Part'),
            'recentParts' => $recentParts,
            'typeOptions' => $typeOptions,
            'brandOptions' => $brandOptions,
            'tenantCurrency' => $tenantCurrency,
        ]);
    }

    public function edit(Request $request, string $business, int $part)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $model = RepairBuddyPart::query()->with(['type', 'brand'])->whereKey($part)->firstOrFail();

        $typeOptions = RepairBuddyPartType::query()
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name'])
            ->mapWithKeys(fn (RepairBuddyPartType $t) => [(string) $t->id => (string) $t->name])
            ->prepend((string) __('None'), '')
            ->all();

        $brandOptions = RepairBuddyPartBrand::query()
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name'])
            ->mapWithKeys(fn (RepairBuddyPartBrand $b) => [(string) $b->id => (string) $b->name])
            ->prepend((string) __('None'), '')
            ->all();

        $tenantCurrency = is_string($tenant->currency) && $tenant->currency !== '' ? strtoupper((string) $tenant->currency) : '';

        $typesQ = is_string($request->query('types_q')) ? trim((string) $request->query('types_q')) : '';
        $brandsQ = is_string($request->query('brands_q')) ? trim((string) $request->query('brands_q')) : '';
        $devicesQ = is_string($request->query('devices_q')) ? trim((string) $request->query('devices_q')) : '';

        $deviceTypesQuery = RepairBuddyDeviceType::query()->orderBy('name');
        if ($typesQ !== '') {
            $deviceTypesQuery->where('name', 'like', '%'.$typesQ.'%');
        }
        $deviceTypes = $deviceTypesQuery
            ->paginate(5, ['id', 'name'], 'types_page')
            ->appends($request->except('types_page'));

        $deviceBrandsQuery = RepairBuddyDeviceBrand::query()->orderBy('name');
        if ($brandsQ !== '') {
            $deviceBrandsQuery->where('name', 'like', '%'.$brandsQ.'%');
        }
        $deviceBrands = $deviceBrandsQuery
            ->paginate(5, ['id', 'name'], 'brands_page')
            ->appends($request->except('brands_page'));

        $devicesQuery = RepairBuddyDevice::query()->orderBy('model');
        if ($devicesQ !== '') {
            $devicesQuery->where('model', 'like', '%'.$devicesQ.'%');
        }
        $devices = $devicesQuery
            ->paginate(5, ['id', 'model', 'device_brand_id', 'device_type_id'], 'devices_page')
            ->appends($request->except('devices_page'));

        $overrides = RepairBuddyPartPriceOverride::query()
            ->where('part_id', $model->id)
            ->whereNull('part_variant_id')
            ->whereIn('scope_type', ['type', 'brand', 'device'])
            ->orderByDesc('id')
            ->get();

        $overridesIndex = [];
        foreach ($overrides as $o) {
            $scopeType = is_string($o->scope_type) ? (string) $o->scope_type : '';
            $refId = is_numeric($o->scope_ref_id) ? (int) $o->scope_ref_id : null;
            if ($scopeType === '' || $refId === null) {
                continue;
            }

            $overridesIndex[$scopeType . ':' . $refId] = $o;
        }

        return view('tenant.operations.parts.edit', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'operations',
            'pageTitle' => __('Edit Part'),
            'part' => $model,
            'typeOptions' => $typeOptions,
            'brandOptions' => $brandOptions,
            'tenantCurrency' => $tenantCurrency,
            'deviceTypes' => $deviceTypes,
            'deviceBrands' => $deviceBrands,
            'devices' => $devices,
            'partPriceOverridesIndex' => $overridesIndex,
        ]);
    }

    public function priceOverridesSection(Request $request, string $business, int $part): Response
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'section' => ['required', 'string', 'max:32'],
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
            'page' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ]);

        $section = (string) $validated['section'];
        if (! in_array($section, ['type', 'brand', 'device'], true)) {
            abort(422, 'Section is invalid.');
        }

        $partModel = RepairBuddyPart::query()->whereKey($part)->firstOrFail();

        $q = is_string($validated['q'] ?? null) ? trim((string) $validated['q']) : '';
        $page = is_numeric($validated['page'] ?? null) ? (int) $validated['page'] : 1;

        if ($section === 'type') {
            $query = RepairBuddyDeviceType::query()->orderBy('name');
            if ($q !== '') {
                $query->where('name', 'like', '%'.$q.'%');
            }
            $paginator = $query->paginate(5, ['id', 'name'], 'page', $page);
        } elseif ($section === 'brand') {
            $query = RepairBuddyDeviceBrand::query()->orderBy('name');
            if ($q !== '') {
                $query->where('name', 'like', '%'.$q.'%');
            }
            $paginator = $query->paginate(5, ['id', 'name'], 'page', $page);
        } else {
            $query = RepairBuddyDevice::query()->orderBy('model');
            if ($q !== '') {
                $query->where('model', 'like', '%'.$q.'%');
            }
            $paginator = $query->paginate(5, ['id', 'model', 'device_brand_id', 'device_type_id'], 'page', $page);
        }

        $overrides = RepairBuddyPartPriceOverride::query()
            ->where('part_id', $partModel->id)
            ->whereNull('part_variant_id')
            ->where('scope_type', $section)
            ->orderByDesc('id')
            ->get();

        $overridesIndex = [];
        foreach ($overrides as $o) {
            $refId = is_numeric($o->scope_ref_id) ? (int) $o->scope_ref_id : null;
            if ($refId === null) {
                continue;
            }
            $overridesIndex[$section . ':' . $refId] = $o;
        }

        $html = view('tenant.operations.parts._price_overrides_section', [
            'section' => $section,
            'tenant' => $tenant,
            'part' => $partModel,
            'paginator' => $paginator,
            'partPriceOverridesIndex' => $overridesIndex,
        ])->render();

        return response($html);
    }

    public function updatePriceOverrides(Request $request, string $business, int $part): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'scope_type' => ['required', 'string', 'max:32'],
            'scope_ref_id' => ['required', 'array'],
            'scope_ref_id.*' => ['nullable'],
            'price' => ['required', 'array'],
            'price.*' => ['nullable'],
            'manufacturing_code' => ['required', 'array'],
            'manufacturing_code.*' => ['nullable', 'string', 'max:255'],
            'stock_code' => ['required', 'array'],
            'stock_code.*' => ['nullable', 'string', 'max:255'],
            'active_ref_id' => ['sometimes', 'array'],
            'active_ref_id.*' => ['nullable'],
        ]);

        $scopeType = (string) $validated['scope_type'];
        if (! in_array($scopeType, ['type', 'brand', 'device'], true)) {
            return redirect()
                ->route('tenant.operations.parts.edit', ['business' => $tenant->slug, 'part' => $part])
                ->withErrors(['scope_type' => __('Scope type is invalid.')]);
        }

        $model = RepairBuddyPart::query()->whereKey($part)->firstOrFail();

        $currency = is_string($model->price_currency) && $model->price_currency !== ''
            ? strtoupper((string) $model->price_currency)
            : (is_string($tenant->currency) && $tenant->currency !== '' ? strtoupper((string) $tenant->currency) : null);

        if ($currency === null || $currency === '') {
            return redirect()
                ->route('tenant.operations.parts.edit', ['business' => $tenant->slug, 'part' => $model->id])
                ->withErrors(['price' => __('Tenant currency is not configured.')]);
        }

        $activeRefIds = [];
        if (array_key_exists('active_ref_id', $validated) && is_array($validated['active_ref_id'])) {
            foreach ($validated['active_ref_id'] as $idRaw) {
                if (is_numeric($idRaw)) {
                    $activeRefIds[(int) $idRaw] = true;
                }
            }
        }

        $branches = Branch::query()->where('is_active', true)->orderBy('id')->get(['id']);

        $refIds = is_array($validated['scope_ref_id']) ? $validated['scope_ref_id'] : [];
        $prices = is_array($validated['price']) ? $validated['price'] : [];
        $mfgCodes = is_array($validated['manufacturing_code']) ? $validated['manufacturing_code'] : [];
        $stockCodes = is_array($validated['stock_code']) ? $validated['stock_code'] : [];

        DB::transaction(function () use ($branches, $currency, $model, $prices, $refIds, $scopeType, $tenant, $activeRefIds, $mfgCodes, $stockCodes) {
            foreach ($refIds as $idx => $refIdRaw) {
                if (! is_numeric($refIdRaw)) {
                    continue;
                }

                $refId = (int) $refIdRaw;
                $isActive = array_key_exists($refId, $activeRefIds);

                $priceRaw = array_key_exists($idx, $prices) ? $prices[$idx] : null;
                $priceCents = null;
                if ($priceRaw !== null && $priceRaw !== '') {
                    $priceCents = (int) round(((float) $priceRaw) * 100);
                }

                $mfgRaw = array_key_exists($idx, $mfgCodes) ? $mfgCodes[$idx] : null;
                $mfg = is_string($mfgRaw) ? trim((string) $mfgRaw) : null;
                if ($mfg === '') {
                    $mfg = null;
                }

                $stockRaw = array_key_exists($idx, $stockCodes) ? $stockCodes[$idx] : null;
                $stock = is_string($stockRaw) ? trim((string) $stockRaw) : null;
                if ($stock === '') {
                    $stock = null;
                }

                foreach ($branches as $branch) {
                    $branchId = (int) $branch->id;

                    RepairBuddyPartPriceOverride::query()->updateOrCreate(
                        [
                            'tenant_id' => (int) $tenant->id,
                            'branch_id' => $branchId,
                            'part_id' => (int) $model->id,
                            'part_variant_id' => null,
                            'scope_type' => $scopeType,
                            'scope_ref_id' => $refId,
                        ],
                        [
                            'price_amount_cents' => $priceCents,
                            'price_currency' => $currency,
                            'manufacturing_code' => $mfg,
                            'stock_code' => $stock,
                            'is_active' => $isActive,
                        ]
                    );
                }
            }
        });

        return redirect()
            ->route('tenant.operations.parts.edit', ['business' => $tenant->slug, 'part' => $model->id])
            ->with('status', __('Pricing updated.'));
    }

    public function store(Request $request, string $business): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'part_type_id' => ['sometimes', 'nullable'],
            'part_brand_id' => ['sometimes', 'nullable'],
            'sku' => ['sometimes', 'nullable', 'string', 'max:128'],
            'manufacturing_code' => ['required', 'string', 'max:255'],
            'stock_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'price_currency' => ['sometimes', 'nullable', 'string', 'max:8'],
            'warranty' => ['sometimes', 'nullable', 'string', 'max:255'],
            'core_features' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'capacity' => ['sometimes', 'nullable', 'string', 'max:255'],
            'installation_charges' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'installation_charges_currency' => ['sometimes', 'nullable', 'string', 'max:8'],
            'installation_message' => ['sometimes', 'nullable', 'string', 'max:255'],
            'stock' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ]);

        $name = trim((string) $validated['name']);
        $partTypeIdRaw = $validated['part_type_id'] ?? null;
        $partTypeId = is_numeric($partTypeIdRaw) ? (int) $partTypeIdRaw : null;
        $partBrandIdRaw = $validated['part_brand_id'] ?? null;
        $partBrandId = is_numeric($partBrandIdRaw) ? (int) $partBrandIdRaw : null;

        if ($partTypeId !== null && ! RepairBuddyPartType::query()->whereKey($partTypeId)->exists()) {
            return redirect()
                ->route('tenant.operations.parts.create', ['business' => $tenant->slug])
                ->withErrors(['part_type_id' => __('Part type is invalid.')])
                ->withInput();
        }

        if ($partBrandId !== null && ! RepairBuddyPartBrand::query()->whereKey($partBrandId)->exists()) {
            return redirect()
                ->route('tenant.operations.parts.create', ['business' => $tenant->slug])
                ->withErrors(['part_brand_id' => __('Part brand is invalid.')])
                ->withInput();
        }

        $priceCents = (int) round(((float) $validated['price']) * 100);

        $priceCurrency = is_string($validated['price_currency'] ?? null) && trim((string) $validated['price_currency']) !== ''
            ? strtoupper(trim((string) $validated['price_currency']))
            : null;

        if ($priceCurrency === null || $priceCurrency === '') {
            $tenantCurrency = is_string($tenant->currency) && $tenant->currency !== '' ? strtoupper((string) $tenant->currency) : '';
            if ($tenantCurrency === '') {
                return redirect()
                    ->route('tenant.operations.parts.create', ['business' => $tenant->slug])
                    ->withErrors(['price_currency' => __('Tenant currency is not configured.')])
                    ->withInput();
            }

            $priceCurrency = $tenantCurrency;
        }

        $installationCents = null;
        if (array_key_exists('installation_charges', $validated) && $validated['installation_charges'] !== null && $validated['installation_charges'] !== '') {
            $installationCents = (int) round(((float) $validated['installation_charges']) * 100);
        }

        $installationCurrency = null;
        if ($installationCents !== null) {
            $installationCurrency = is_string($validated['installation_charges_currency'] ?? null) && trim((string) $validated['installation_charges_currency']) !== ''
                ? strtoupper(trim((string) $validated['installation_charges_currency']))
                : $priceCurrency;
        }

        if (RepairBuddyPart::query()->where('name', $name)->exists()) {
            return redirect()
                ->route('tenant.operations.parts.index', ['business' => $tenant->slug])
                ->withErrors(['name' => __('Part with same name already exists.')])
                ->withInput();
        }

        RepairBuddyPart::query()->create([
            'part_type_id' => $partTypeId,
            'part_brand_id' => $partBrandId,
            'name' => $name,
            'sku' => $validated['sku'] ?? null,
            'manufacturing_code' => $validated['manufacturing_code'] ?? null,
            'stock_code' => $validated['stock_code'] ?? null,
            'price_amount_cents' => $priceCents,
            'price_currency' => $priceCurrency,
            'tax_id' => null,
            'warranty' => $validated['warranty'] ?? null,
            'core_features' => $validated['core_features'] ?? null,
            'capacity' => $validated['capacity'] ?? null,
            'installation_charges_amount_cents' => $installationCents,
            'installation_charges_currency' => $installationCurrency,
            'installation_message' => $validated['installation_message'] ?? null,
            'stock' => array_key_exists('stock', $validated) && $validated['stock'] !== null && $validated['stock'] !== '' ? (int) $validated['stock'] : null,
            'is_active' => true,
        ]);

        return redirect()
            ->route('tenant.operations.parts.index', ['business' => $tenant->slug])
            ->with('status', __('Part added.'));
    }

    public function update(Request $request, string $business, int $part): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'part_type_id' => ['sometimes', 'nullable'],
            'part_brand_id' => ['sometimes', 'nullable'],
            'sku' => ['sometimes', 'nullable', 'string', 'max:128'],
            'manufacturing_code' => ['required', 'string', 'max:255'],
            'stock_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'price_currency' => ['sometimes', 'nullable', 'string', 'max:8'],
            'warranty' => ['sometimes', 'nullable', 'string', 'max:255'],
            'core_features' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'capacity' => ['sometimes', 'nullable', 'string', 'max:255'],
            'installation_charges' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'installation_charges_currency' => ['sometimes', 'nullable', 'string', 'max:8'],
            'installation_message' => ['sometimes', 'nullable', 'string', 'max:255'],
            'stock' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ]);

        $model = RepairBuddyPart::query()->whereKey($part)->firstOrFail();

        $name = trim((string) $validated['name']);
        $partTypeIdRaw = $validated['part_type_id'] ?? null;
        $partTypeId = is_numeric($partTypeIdRaw) ? (int) $partTypeIdRaw : null;
        $partBrandIdRaw = $validated['part_brand_id'] ?? null;
        $partBrandId = is_numeric($partBrandIdRaw) ? (int) $partBrandIdRaw : null;

        if ($partTypeId !== null && ! RepairBuddyPartType::query()->whereKey($partTypeId)->exists()) {
            return redirect()
                ->route('tenant.operations.parts.edit', ['business' => $tenant->slug, 'part' => $model->id])
                ->withErrors(['part_type_id' => __('Part type is invalid.')])
                ->withInput();
        }

        if ($partBrandId !== null && ! RepairBuddyPartBrand::query()->whereKey($partBrandId)->exists()) {
            return redirect()
                ->route('tenant.operations.parts.edit', ['business' => $tenant->slug, 'part' => $model->id])
                ->withErrors(['part_brand_id' => __('Part brand is invalid.')])
                ->withInput();
        }

        $priceCents = (int) round(((float) $validated['price']) * 100);

        $priceCurrency = is_string($validated['price_currency'] ?? null) && trim((string) $validated['price_currency']) !== ''
            ? strtoupper(trim((string) $validated['price_currency']))
            : (is_string($model->price_currency) && $model->price_currency !== '' ? (string) $model->price_currency : null);

        if ($priceCurrency === null || $priceCurrency === '') {
            $tenantCurrency = is_string($tenant->currency) && $tenant->currency !== '' ? strtoupper((string) $tenant->currency) : '';
            if ($tenantCurrency === '') {
                return redirect()
                    ->route('tenant.operations.parts.edit', ['business' => $tenant->slug, 'part' => $model->id])
                    ->withErrors(['price_currency' => __('Tenant currency is not configured.')])
                    ->withInput();
            }

            $priceCurrency = $tenantCurrency;
        }

        $installationCents = $model->installation_charges_amount_cents;
        if (array_key_exists('installation_charges', $validated)) {
            if ($validated['installation_charges'] === null || $validated['installation_charges'] === '') {
                $installationCents = null;
            } else {
                $installationCents = (int) round(((float) $validated['installation_charges']) * 100);
            }
        }

        $installationCurrency = $model->installation_charges_currency;
        if ($installationCents !== null) {
            if (array_key_exists('installation_charges_currency', $validated)) {
                $installationCurrency = is_string($validated['installation_charges_currency']) && trim((string) $validated['installation_charges_currency']) !== ''
                    ? strtoupper(trim((string) $validated['installation_charges_currency']))
                    : $priceCurrency;
            }

            if (! is_string($installationCurrency) || $installationCurrency === '') {
                $installationCurrency = $priceCurrency;
            }
        } else {
            $installationCurrency = null;
        }

        if (RepairBuddyPart::query()
            ->where('name', $name)
            ->where('id', '!=', $model->id)
            ->exists()) {
            return redirect()
                ->route('tenant.operations.parts.index', ['business' => $tenant->slug])
                ->withErrors(['name' => __('Part with same name already exists.')])
                ->withInput();
        }

        $model->forceFill([
            'part_type_id' => $partTypeId,
            'part_brand_id' => $partBrandId,
            'name' => $name,
            'sku' => $validated['sku'] ?? null,
            'manufacturing_code' => $validated['manufacturing_code'] ?? null,
            'stock_code' => $validated['stock_code'] ?? null,
            'price_amount_cents' => $priceCents,
            'price_currency' => $priceCurrency,
            'tax_id' => null,
            'warranty' => $validated['warranty'] ?? null,
            'core_features' => $validated['core_features'] ?? null,
            'capacity' => $validated['capacity'] ?? null,
            'installation_charges_amount_cents' => $installationCents,
            'installation_charges_currency' => $installationCurrency,
            'installation_message' => $validated['installation_message'] ?? null,
            'stock' => array_key_exists('stock', $validated) && $validated['stock'] !== null && $validated['stock'] !== '' ? (int) $validated['stock'] : null,
        ])->save();

        return redirect()
            ->route('tenant.operations.parts.index', ['business' => $tenant->slug])
            ->with('status', __('Part updated.'));
    }

    public function setActive(Request $request, string $business, int $part): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $model = RepairBuddyPart::query()->whereKey($part)->firstOrFail();
        $model->forceFill([
            'is_active' => (bool) $validated['is_active'],
        ])->save();

        return redirect()
            ->route('tenant.operations.parts.index', ['business' => $tenant->slug])
            ->with('status', __('Part updated.'));
    }

    public function delete(Request $request, string $business, int $part): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $model = RepairBuddyPart::query()->whereKey($part)->firstOrFail();

        $model->delete();

        return redirect()
            ->route('tenant.operations.parts.index', ['business' => $tenant->slug])
            ->with('status', __('Part deleted.'));
    }
}
