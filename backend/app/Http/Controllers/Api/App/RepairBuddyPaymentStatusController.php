<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyPaymentStatus;
use App\Models\TenantStatusOverride;
use Illuminate\Http\Request;

class RepairBuddyPaymentStatusController extends Controller
{
    public function index(Request $request)
    {
        $overrides = TenantStatusOverride::query()
            ->where('domain', 'payment')
            ->get()
            ->keyBy('code');

        return response()->json([
            'payment_statuses' => RepairBuddyPaymentStatus::query()
                ->orderBy('id')
                ->get()
                ->map(function (RepairBuddyPaymentStatus $s) use ($overrides) {
                    $override = $overrides[$s->slug] ?? null;

                    return [
                        'id' => $s->id,
                        'slug' => $s->slug,
                        'label' => (is_string($override?->label) && $override->label !== '') ? $override->label : $s->label,
                        'is_active' => (bool) $s->is_active,
                        'color' => is_string($override?->color) ? $override->color : null,
                        'sort_order' => is_numeric($override?->sort_order) ? (int) $override->sort_order : null,
                    ];
                })
                ->sortBy(function (array $row) {
                    return is_numeric($row['sort_order'] ?? null) ? (int) $row['sort_order'] : (int) $row['id'];
                })
                ->values(),
        ]);
    }

    public function updateDisplay(Request $request, string $business, string $slug)
    {
        $validated = $request->validate([
            'label' => ['sometimes', 'nullable', 'string', 'max:255'],
            'color' => ['sometimes', 'nullable', 'string', 'max:32'],
            'sort_order' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100000'],
        ]);

        $status = RepairBuddyPaymentStatus::query()->where('slug', $slug)->first();

        if (! $status) {
            return response()->json([
                'message' => 'Payment status not found.',
            ], 404);
        }

        $override = TenantStatusOverride::query()
            ->where('domain', 'payment')
            ->where('code', $slug)
            ->first();

        if (! $override) {
            TenantStatusOverride::query()->create([
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

        return response()->json([
            'message' => 'Payment status display updated.',
        ]);
    }
}
