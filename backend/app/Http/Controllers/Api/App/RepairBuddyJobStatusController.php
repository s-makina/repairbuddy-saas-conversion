<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyJobStatus;
use App\Models\TenantStatusOverride;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class RepairBuddyJobStatusController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = TenantContext::tenantId();
        $branchId = BranchContext::branchId();

        if (! $tenantId || ! $branchId) {
            return response()->json([
                'message' => 'Tenant or branch context is missing.',
            ], 400);
        }

        $overrides = TenantStatusOverride::query()
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->where('domain', 'job')
            ->get()
            ->keyBy('code');

        return response()->json([
            'job_statuses' => RepairBuddyJobStatus::query()
                ->orderBy('id')
                ->get()
                ->map(function (RepairBuddyJobStatus $s) use ($overrides) {
                    $override = $overrides[$s->slug] ?? null;

                    return [
                        'id' => $s->id,
                        'slug' => $s->slug,
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
        $branchId = BranchContext::branchId();

        if (! $tenantId || ! $branchId) {
            return response()->json([
                'message' => 'Tenant or branch context is missing.',
            ], 400);
        }

        $validated = $request->validate([
            'label' => ['sometimes', 'nullable', 'string', 'max:255'],
            'color' => ['sometimes', 'nullable', 'string', 'max:32'],
            'sort_order' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100000'],
        ]);

        $status = RepairBuddyJobStatus::query()->where('slug', $slug)->first();

        if (! $status) {
            return response()->json([
                'message' => 'Job status not found.',
            ], 404);
        }

        $override = TenantStatusOverride::query()
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->where('domain', 'job')
            ->where('code', $slug)
            ->first();

        if (! $override) {
            $override = TenantStatusOverride::query()->create([
                'tenant_id' => $tenantId,
                'branch_id' => $branchId,
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
