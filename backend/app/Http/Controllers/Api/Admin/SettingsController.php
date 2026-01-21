<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformCurrency;
use App\Support\PlatformAudit;
use App\Support\PlatformSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    public function show(Request $request)
    {
        $supportedCurrencies = PlatformSettings::getArray('supported_currencies', []);

        $currencies = PlatformCurrency::query()
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get(['code', 'symbol', 'name', 'is_active']);

        $supportedFromDb = $currencies
            ->where('is_active', true)
            ->pluck('code')
            ->map(fn ($c) => strtoupper((string) $c))
            ->filter(fn ($c) => preg_match('/^[A-Z]{3}$/', $c))
            ->values()
            ->all();

        $supportedLegacy = array_values(array_filter(array_map(function ($c) {
            $c = strtoupper((string) $c);

            return preg_match('/^[A-Z]{3}$/', $c) ? $c : null;
        }, is_array($supportedCurrencies) ? $supportedCurrencies : [])));

        $supported = count($supportedFromDb) ? $supportedFromDb : $supportedLegacy;

        return response()->json([
            'app' => [
                'name' => (string) config('app.name'),
                'env' => (string) config('app.env'),
                'url' => (string) config('app.url'),
                'debug' => (bool) config('app.debug'),
            ],
            'tenancy' => [
                'resolution' => (string) config('tenancy.resolution'),
                'route_param' => (string) config('tenancy.route_param'),
                'header' => (string) config('tenancy.header'),
            ],
            'billing' => [
                'seller_country' => (string) config('billing.seller_country'),
            ],
            'currencies' => $currencies,
            'supported_currencies' => $supported,
            'mail' => [
                'default' => (string) config('mail.default'),
                'from_address' => (string) (config('mail.from.address') ?? ''),
                'from_name' => (string) (config('mail.from.name') ?? ''),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'currencies' => ['sometimes', 'array'],
            'currencies.*.code' => ['required_with:currencies', 'string', 'size:3'],
            'currencies.*.symbol' => ['nullable', 'string', 'max:10'],
            'currencies.*.name' => ['required_with:currencies', 'string', 'max:255'],
            'currencies.*.is_active' => ['sometimes', 'boolean'],
            'supported_currencies' => ['sometimes', 'array'],
            'supported_currencies.*' => ['required', 'string', 'size:3'],
        ]);

        $before = [
            'supported_currencies' => PlatformSettings::getArray('supported_currencies', []),
            'currencies' => PlatformCurrency::query()->orderBy('code')->get(['code', 'symbol', 'name', 'is_active'])->toArray(),
        ];

        DB::transaction(function () use ($validated) {
            if (array_key_exists('currencies', $validated)) {
                $rows = is_array($validated['currencies']) ? $validated['currencies'] : [];

                $normalized = [];
                foreach ($rows as $r) {
                    if (! is_array($r)) {
                        continue;
                    }

                    $code = strtoupper((string) ($r['code'] ?? ''));
                    if (! preg_match('/^[A-Z]{3}$/', $code)) {
                        continue;
                    }

                    $name = (string) ($r['name'] ?? '');
                    if ($name === '') {
                        continue;
                    }

                    $symbol = isset($r['symbol']) ? (string) $r['symbol'] : null;
                    $symbol = $symbol === '' ? null : $symbol;
                    $isActive = array_key_exists('is_active', $r) ? (bool) $r['is_active'] : true;

                    $normalized[] = [
                        'code' => $code,
                        'symbol' => $symbol,
                        'name' => $name,
                        'is_active' => $isActive,
                    ];
                }

                $codes = array_values(array_unique(array_map(fn ($r) => $r['code'], $normalized)));

                foreach ($normalized as $i => $r) {
                    PlatformCurrency::query()->updateOrCreate(
                        ['code' => $r['code']],
                        [
                            'symbol' => $r['symbol'],
                            'name' => $r['name'],
                            'is_active' => $r['is_active'],
                            'sort_order' => $i,
                        ],
                    );
                }

                PlatformCurrency::query()->whereNotIn('code', $codes)->delete();

                $activeCodes = PlatformCurrency::query()
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('code')
                    ->pluck('code')
                    ->map(fn ($c) => strtoupper((string) $c))
                    ->filter(fn ($c) => preg_match('/^[A-Z]{3}$/', $c))
                    ->values()
                    ->all();

                PlatformSettings::setArray('supported_currencies', $activeCodes);
            } elseif (array_key_exists('supported_currencies', $validated)) {
                $list = array_map(function ($c) {
                    return strtoupper((string) $c);
                }, $validated['supported_currencies'] ?? []);

                $list = array_values(array_unique(array_values(array_filter($list, function ($c) {
                    return preg_match('/^[A-Z]{3}$/', (string) $c);
                }))));

                PlatformSettings::setArray('supported_currencies', $list);

                foreach ($list as $i => $code) {
                    PlatformCurrency::query()->updateOrCreate(
                        ['code' => $code],
                        [
                            'symbol' => null,
                            'name' => $code,
                            'is_active' => true,
                            'sort_order' => $i,
                        ],
                    );
                }
            }
        });

        PlatformAudit::log($request, 'platform.settings.updated', null, null, [
            'before' => $before,
            'after' => [
                'supported_currencies' => PlatformSettings::getArray('supported_currencies', []),
                'currencies' => PlatformCurrency::query()->orderBy('code')->get(['code', 'symbol', 'name', 'is_active'])->toArray(),
            ],
        ]);

        return $this->show($request);
    }
}
