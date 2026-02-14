<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\Status;
use App\Models\TenantStatusOverride;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class RepairBuddyJobStatusController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = TenantContext::tenantId();

        if (! $tenantId) {
            return response()->json([
                'message' => 'Tenant context is missing.',
            ], 422);
        }

        $overrides = TenantStatusOverride::query()
            ->where('tenant_id', $tenantId)
            ->where('domain', 'job')
            ->get()
            ->keyBy('code');

        return response()->json([
            'job_statuses' => Status::query()
                ->where('status_type', 'Job')
                ->orderBy('id')
                ->get()
                ->map(function (Status $s) use ($overrides) {
                    $code = is_string($s->code) ? trim((string) $s->code) : '';
                    $override = $code !== '' ? ($overrides[$code] ?? null) : null;

                    return [
                        'id' => $s->id,
                        'slug' => $code,
                        'label' => (is_string($override?->label) && $override->label !== '') ? $override->label : $s->label,
                        'invoice_label' => $s->invoice_label,
                        'email_enabled' => (bool) $s->email_enabled,
                        'sms_enabled' => (bool) $s->sms_enabled,
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
        $tenantId = TenantContext::tenantId();

        if (! $tenantId) {
            return response()->json([
                'message' => 'Tenant context is missing.',
            ], 422);
        }

        $validated = $request->validate([
            'label' => ['sometimes', 'nullable', 'string', 'max:255'],
            'color' => ['sometimes', 'nullable', 'string', 'max:32'],
            'sort_order' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100000'],
        ]);

        $status = Status::query()
            ->where('status_type', 'Job')
            ->where('code', $slug)
            ->first();

        if (! $status) {
            return response()->json([
                'message' => 'Job status not found.',
            ], 404);
        }

        $override = TenantStatusOverride::query()
            ->where('tenant_id', $tenantId)
            ->where('domain', 'job')
            ->where('code', $slug)
            ->first();

        if (! $override) {
            $override = TenantStatusOverride::query()->create([
                'tenant_id' => $tenantId,
                'domain' => 'job',
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
            'message' => 'Job status display updated.',
        ]);
    }
}
