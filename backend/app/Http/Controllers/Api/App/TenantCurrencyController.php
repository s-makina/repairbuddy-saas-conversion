<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantCurrency;
use App\Support\PlatformAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TenantCurrencyController extends Controller
{
    public function index(Request $request, string $business)
    {
        $tenant = $this->tenant();

        $currencies = TenantCurrency::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get(['code', 'symbol', 'name', 'is_active', 'is_default']);

        if ($currencies->count() === 0) {
            $seedCode = is_string($tenant->currency) && trim($tenant->currency) !== '' ? strtoupper(trim($tenant->currency)) : 'USD';

            $seed = TenantCurrency::query()->create([
                'tenant_id' => $tenant->id,
                'code' => $seedCode,
                'symbol' => null,
                'name' => $seedCode,
                'is_active' => true,
                'is_default' => true,
                'sort_order' => 0,
            ]);

            $currencies = collect([$seed]);
        }

        $defaultCode = $currencies->firstWhere('is_default', true)?->code;
        $defaultCode = is_string($defaultCode) && $defaultCode !== '' ? strtoupper($defaultCode) : null;

        $activeCode = $defaultCode;
        if (! $activeCode) {
            $activeCode = is_string($tenant->currency) && trim($tenant->currency) !== '' ? strtoupper(trim($tenant->currency)) : null;
        }
        if (! $activeCode) {
            $activeCode = $currencies->first()?->code;
        }

        return response()->json([
            'currencies' => $currencies,
            'active_currency' => $activeCode,
        ]);
    }

    public function update(Request $request, string $business)
    {
        $tenant = $this->tenant();

        $validated = $request->validate([
            'currencies' => ['required', 'array', 'min:1'],
            'currencies.*.code' => ['required', 'string', 'size:3'],
            'currencies.*.symbol' => ['nullable', 'string', 'max:10'],
            'currencies.*.name' => ['required', 'string', 'max:255'],
            'currencies.*.is_active' => ['sometimes', 'boolean'],
            'currencies.*.is_default' => ['sometimes', 'boolean'],
        ]);

        $before = TenantCurrency::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('code')
            ->get(['code', 'symbol', 'name', 'is_active', 'is_default'])
            ->toArray();

        $rows = is_array($validated['currencies'] ?? null) ? $validated['currencies'] : [];

        $normalized = [];
        foreach ($rows as $r) {
            if (! is_array($r)) {
                continue;
            }

            $code = strtoupper((string) ($r['code'] ?? ''));
            if (! preg_match('/^[A-Z]{3}$/', $code)) {
                continue;
            }

            $name = trim((string) ($r['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $symbol = array_key_exists('symbol', $r) ? (string) $r['symbol'] : null;
            $symbol = $symbol !== null ? trim($symbol) : null;
            $symbol = $symbol === '' ? null : $symbol;

            $isActive = array_key_exists('is_active', $r) ? (bool) $r['is_active'] : true;
            $isDefault = array_key_exists('is_default', $r) ? (bool) $r['is_default'] : false;

            $normalized[$code] = [
                'tenant_id' => $tenant->id,
                'code' => $code,
                'symbol' => $symbol,
                'name' => $name,
                'is_active' => $isActive,
                'is_default' => $isDefault,
            ];
        }

        $normalized = array_values($normalized);

        if (count($normalized) === 0) {
            return response()->json([
                'message' => 'At least one valid currency is required.',
            ], 422);
        }

        DB::transaction(function () use ($tenant, $normalized) {
            $codes = [];

            foreach ($normalized as $i => $r) {
                $codes[] = $r['code'];

                TenantCurrency::query()->updateOrCreate(
                    ['tenant_id' => $tenant->id, 'code' => $r['code']],
                    [
                        'symbol' => $r['symbol'],
                        'name' => $r['name'],
                        'is_active' => $r['is_active'],
                        'is_default' => $r['is_default'],
                        'sort_order' => $i,
                    ],
                );
            }

            TenantCurrency::query()
                ->where('tenant_id', $tenant->id)
                ->whereNotIn('code', $codes)
                ->delete();

            $defaultCode = null;
            foreach ($normalized as $r) {
                if ($r['is_default'] && $r['is_active']) {
                    $defaultCode = $r['code'];
                    break;
                }
            }

            if (! $defaultCode) {
                foreach ($normalized as $r) {
                    if ($r['is_active']) {
                        $defaultCode = $r['code'];
                        break;
                    }
                }
            }

            if (! $defaultCode) {
                $defaultCode = $normalized[0]['code'];
            }

            TenantCurrency::query()->where('tenant_id', $tenant->id)->update(['is_default' => false]);
            TenantCurrency::query()->where('tenant_id', $tenant->id)->where('code', $defaultCode)->update(['is_default' => true]);

            $tenant->forceFill([
                'currency' => $defaultCode,
            ])->save();
        });

        PlatformAudit::log($request, 'tenant.currencies.updated', $tenant, null, [
            'before' => $before,
            'after' => TenantCurrency::query()
                ->where('tenant_id', $tenant->id)
                ->orderBy('code')
                ->get(['code', 'symbol', 'name', 'is_active', 'is_default'])
                ->toArray(),
        ]);

        return $this->index($request, $business);
    }
}
