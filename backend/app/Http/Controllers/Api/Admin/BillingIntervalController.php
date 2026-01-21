<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BillingInterval;
use App\Support\PlatformAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BillingIntervalController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'include_inactive' => ['nullable', 'boolean'],
        ]);

        $includeInactive = (bool) ($validated['include_inactive'] ?? false);

        $q = BillingInterval::query()->orderByDesc('is_active')->orderBy('months')->orderBy('name');

        if (! $includeInactive) {
            $q->where('is_active', true);
        }

        return response()->json([
            'billing_intervals' => $q->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:64', 'unique:billing_intervals,code'],
            'name' => ['required', 'string', 'max:255'],
            'months' => ['required', 'integer', 'min:1', 'max:1200'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $code = $validated['code'] ?? Str::slug($validated['name']);
        $code = $code ?: 'interval';

        $baseCode = $code;
        $i = 1;
        while (BillingInterval::query()->where('code', $code)->exists()) {
            $code = $baseCode.'-'.$i;
            $i++;
        }

        $interval = BillingInterval::query()->create([
            'code' => $code,
            'name' => $validated['name'],
            'months' => (int) $validated['months'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        PlatformAudit::log($request, 'billing.interval.created', null, null, [
            'billing_interval_id' => $interval->id,
            'code' => $interval->code,
        ]);

        return response()->json([
            'interval' => $interval,
        ], 201);
    }

    public function update(Request $request, BillingInterval $interval)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:64', 'unique:billing_intervals,code,'.$interval->id],
            'name' => ['required', 'string', 'max:255'],
            'months' => ['required', 'integer', 'min:1', 'max:1200'],
            'is_active' => ['nullable', 'boolean'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $before = $interval->toArray();

        $interval->forceFill([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'months' => (int) $validated['months'],
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : (bool) $interval->is_active,
        ])->save();

        PlatformAudit::log($request, 'billing.interval.updated', null, $validated['reason'] ?? null, [
            'billing_interval_id' => $interval->id,
            'before' => $before,
            'after' => $interval->toArray(),
        ]);

        return response()->json([
            'interval' => $interval,
        ]);
    }

    public function setActive(Request $request, BillingInterval $interval)
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $before = $interval->toArray();

        $interval->forceFill([
            'is_active' => (bool) $validated['is_active'],
        ])->save();

        PlatformAudit::log($request, 'billing.interval.active_updated', null, $validated['reason'] ?? null, [
            'billing_interval_id' => $interval->id,
            'before' => $before,
            'after' => $interval->toArray(),
        ]);

        return response()->json([
            'interval' => $interval,
        ]);
    }
}
