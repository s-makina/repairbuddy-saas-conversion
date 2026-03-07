<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformCurrency;
use App\Support\PlatformAudit;
use Illuminate\Http\Request;

class AdminCurrenciesController extends Controller
{
    public function index(Request $request)
    {
        $currencies = PlatformCurrency::query()
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();

        return response()->json([
            'currencies' => $currencies,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'       => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/', 'unique:platform_currencies,code'],
            'symbol'     => ['required', 'string', 'max:10'],
            'name'       => ['required', 'string', 'max:100'],
            'is_active'  => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $currency = PlatformCurrency::query()->create([
            'code'       => strtoupper($validated['code']),
            'symbol'     => $validated['symbol'],
            'name'       => $validated['name'],
            'is_active'  => $validated['is_active'] ?? true,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        PlatformAudit::log($request, 'currency.created', null, null, [
            'currency_id' => $currency->id,
            'code'        => $currency->code,
        ]);

        return response()->json(['currency' => $currency], 201);
    }

    public function update(Request $request, PlatformCurrency $currency)
    {
        $validated = $request->validate([
            'symbol'     => ['required', 'string', 'max:10'],
            'name'       => ['required', 'string', 'max:100'],
            'is_active'  => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $currency->update([
            'symbol'     => $validated['symbol'],
            'name'       => $validated['name'],
            'is_active'  => $validated['is_active'] ?? $currency->is_active,
            'sort_order' => $validated['sort_order'] ?? $currency->sort_order,
        ]);

        PlatformAudit::log($request, 'currency.updated', null, null, [
            'currency_id' => $currency->id,
            'code'        => $currency->code,
        ]);

        return response()->json(['currency' => $currency->fresh()]);
    }

    public function destroy(Request $request, PlatformCurrency $currency)
    {
        PlatformAudit::log($request, 'currency.deleted', null, null, [
            'currency_id' => $currency->id,
            'code'        => $currency->code,
        ]);

        $currency->delete();

        return response()->json(['deleted' => true]);
    }

    public function setActive(Request $request, PlatformCurrency $currency)
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $currency->update(['is_active' => $validated['is_active']]);

        PlatformAudit::log($request, $validated['is_active'] ? 'currency.activated' : 'currency.deactivated', null, null, [
            'currency_id' => $currency->id,
            'code'        => $currency->code,
        ]);

        return response()->json(['currency' => $currency->fresh()]);
    }
}
