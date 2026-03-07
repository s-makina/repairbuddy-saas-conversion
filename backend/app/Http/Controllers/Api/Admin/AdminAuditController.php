<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformAuditLog;
use Illuminate\Http\Request;

class AdminAuditController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'q'         => ['nullable', 'string', 'max:255'],
            'action'    => ['nullable', 'string', 'max:100'],
            'tenant_id' => ['nullable', 'integer'],
            'from'      => ['nullable', 'date'],
            'to'        => ['nullable', 'date'],
            'sort'      => ['nullable', 'string', 'max:50'],
            'dir'       => ['nullable', 'string', 'in:asc,desc'],
            'per_page'  => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $q        = is_string($validated['q'] ?? null) ? trim($validated['q']) : '';
        $action   = is_string($validated['action'] ?? null) ? $validated['action'] : null;
        $tenantId = isset($validated['tenant_id']) ? (int) $validated['tenant_id'] : null;
        $from     = is_string($validated['from'] ?? null) ? $validated['from'] : null;
        $to       = is_string($validated['to'] ?? null) ? $validated['to'] : null;
        $sort     = is_string($validated['sort'] ?? null) ? $validated['sort'] : null;
        $dir      = is_string($validated['dir'] ?? null) ? $validated['dir'] : null;
        $perPage  = isset($validated['per_page']) ? (int) $validated['per_page'] : 50;

        $allowedSorts = [
            'id'         => 'platform_audit_logs.id',
            'action'     => 'platform_audit_logs.action',
            'created_at' => 'platform_audit_logs.created_at',
        ];

        $query = PlatformAuditLog::query()
            ->with(['actor:id,name,email,is_admin', 'tenant:id,name,slug']);

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('platform_audit_logs.action', 'like', "%{$q}%")
                    ->orWhere('platform_audit_logs.reason', 'like', "%{$q}%")
                    ->orWhere('platform_audit_logs.ip', 'like', "%{$q}%")
                    ->orWhereHas('actor', function ($actorQ) use ($q) {
                        $actorQ->where('name', 'like', "%{$q}%")
                               ->orWhere('email', 'like', "%{$q}%");
                    });
            });
        }

        if ($action !== null && $action !== '' && $action !== 'all') {
            $query->where('platform_audit_logs.action', 'like', "{$action}%");
        }

        if ($tenantId !== null) {
            $query->where('platform_audit_logs.tenant_id', $tenantId);
        }

        if ($from !== null) {
            $query->whereDate('platform_audit_logs.created_at', '>=', $from);
        }

        if ($to !== null) {
            $query->whereDate('platform_audit_logs.created_at', '<=', $to);
        }

        $sortCol = $allowedSorts[$sort ?? ''] ?? null;
        $sortDir = in_array($dir, ['asc', 'desc'], true) ? $dir : null;

        if ($sortCol && $sortDir) {
            $query->orderBy($sortCol, $sortDir)->orderBy('platform_audit_logs.id', 'desc');
        } else {
            $query->orderBy('platform_audit_logs.id', 'desc');
        }

        $paginator = $query->paginate($perPage)->withQueryString();

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
            ],
        ]);
    }
}
