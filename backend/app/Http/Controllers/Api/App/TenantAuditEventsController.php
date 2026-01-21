<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\AuthEvent;
use App\Models\PlatformAuditLog;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TenantAuditEventsController extends Controller
{
    public function index(Request $request)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            return response()->json([
                'message' => 'Tenant is missing.',
            ], 400);
        }

        $validated = $request->validate([
            'type' => ['nullable', 'string', 'max:100'],
            'user_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $tenantId = (int) $tenant->id;
        $type = isset($validated['type']) && is_string($validated['type']) ? trim($validated['type']) : null;
        $userId = isset($validated['user_id']) ? (int) $validated['user_id'] : null;
        $from = isset($validated['from']) && is_string($validated['from']) ? Carbon::parse($validated['from'])->startOfDay() : null;
        $to = isset($validated['to']) && is_string($validated['to']) ? Carbon::parse($validated['to'])->endOfDay() : null;

        $page = (int) ($validated['page'] ?? 1);
        $perPage = (int) ($validated['per_page'] ?? 25);
        $limit = $perPage;
        $offset = ($page - 1) * $perPage;

        $authQ = AuthEvent::query()->where('tenant_id', $tenantId);
        $platQ = PlatformAuditLog::query()->where('tenant_id', $tenantId);

        if ($type) {
            $authQ->where('event_type', $type);
            $platQ->where('action', $type);
        }

        if ($userId) {
            $authQ->where('user_id', $userId);
            $platQ->where('actor_user_id', $userId);
        }

        if ($from) {
            $authQ->where('created_at', '>=', $from);
            $platQ->where('created_at', '>=', $from);
        }

        if ($to) {
            $authQ->where('created_at', '<=', $to);
            $platQ->where('created_at', '<=', $to);
        }

        $authCount = (clone $authQ)->count();
        $platCount = (clone $platQ)->count();
        $total = $authCount + $platCount;

        $auth = (clone $authQ)->orderByDesc('created_at')->limit($limit + $offset)->get();
        $plat = (clone $platQ)->orderByDesc('created_at')->limit($limit + $offset)->get();

        $items = [];

        foreach ($auth as $e) {
            $items[] = [
                'source' => 'auth',
                'id' => $e->id,
                'type' => $e->event_type,
                'user_id' => $e->user_id,
                'actor_user_id' => $e->user_id,
                'email' => $e->email,
                'created_at' => $e->created_at?->toIso8601String(),
                'ip' => $e->ip,
                'user_agent' => $e->user_agent,
                'metadata' => $e->metadata,
            ];
        }

        foreach ($plat as $e) {
            $items[] = [
                'source' => 'platform',
                'id' => $e->id,
                'type' => $e->action,
                'user_id' => $e->actor_user_id,
                'actor_user_id' => $e->actor_user_id,
                'email' => null,
                'created_at' => $e->created_at?->toIso8601String(),
                'ip' => $e->ip,
                'user_agent' => $e->user_agent,
                'metadata' => $e->metadata,
                'reason' => $e->reason,
            ];
        }

        usort($items, function ($a, $b) {
            $at = $a['created_at'] ?? '';
            $bt = $b['created_at'] ?? '';
            return $bt <=> $at;
        });

        $paged = array_slice($items, $offset, $limit);

        return response()->json([
            'events' => $paged,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil(max(1, $total) / $perPage),
            ],
        ]);
    }
}
