<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformAuditLog;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ActivityFeedController extends Controller
{
    /**
     * GET /api/admin/activity-feed
     *
     * Returns paginated activity-feed events (backed by platform_audit_logs)
     * plus KPI summary and sidebar widget data.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'q'         => ['nullable', 'string', 'max:255'],
            'type'      => ['nullable', 'string', 'max:100'],
            'tenant_id' => ['nullable', 'integer'],
            'range'     => ['nullable', 'string', 'in:today,7d,30d,90d,all'],
            'tab'       => ['nullable', 'string', 'in:all,signups,billing,alerts'],
            'sort'      => ['nullable', 'string', 'max:50'],
            'dir'       => ['nullable', 'string', 'in:asc,desc'],
            'page'      => ['nullable', 'integer', 'min:1'],
            'per_page'  => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $q        = is_string($validated['q'] ?? null) ? trim($validated['q']) : '';
        $type     = is_string($validated['type'] ?? null) ? $validated['type'] : null;
        $tenantId = isset($validated['tenant_id']) ? (int) $validated['tenant_id'] : null;
        $range    = is_string($validated['range'] ?? null) ? $validated['range'] : 'today';
        $tab      = is_string($validated['tab'] ?? null) ? $validated['tab'] : 'all';
        $perPage  = isset($validated['per_page']) ? (int) $validated['per_page'] : 20;

        // Determine date range
        $fromDate = $this->resolveFromDate($range);

        // Build base query
        $query = PlatformAuditLog::query()
            ->with(['actor:id,name,email,is_admin', 'tenant:id,name,slug,status']);

        if ($fromDate) {
            $query->where('platform_audit_logs.created_at', '>=', $fromDate);
        }

        // Search
        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('platform_audit_logs.action', 'like', "%{$q}%")
                    ->orWhere('platform_audit_logs.reason', 'like', "%{$q}%")
                    ->orWhere('platform_audit_logs.ip', 'like', "%{$q}%")
                    ->orWhereHas('actor', function ($actorQ) use ($q) {
                        $actorQ->where('name', 'like', "%{$q}%")
                               ->orWhere('email', 'like', "%{$q}%");
                    })
                    ->orWhereHas('tenant', function ($tenantQ) use ($q) {
                        $tenantQ->where('name', 'like', "%{$q}%");
                    });
            });
        }

        // Type filter
        if ($type !== null && $type !== '' && $type !== 'all') {
            $query->where('platform_audit_logs.action', 'like', "{$type}%");
        }

        // Tenant filter
        if ($tenantId !== null) {
            $query->where('platform_audit_logs.tenant_id', $tenantId);
        }

        // Tab filter
        $this->applyTabFilter($query, $tab);

        // Ordering
        $query->orderBy('platform_audit_logs.created_at', 'desc')
              ->orderBy('platform_audit_logs.id', 'desc');

        $paginator = $query->paginate($perPage)->withQueryString();

        // KPI summary (always for today)
        $todayStart = Carbon::today();
        $kpis = $this->buildKpis($todayStart);

        // Tab counts (within same date range)
        $tabCounts = $this->buildTabCounts($fromDate);

        // Recent signups widget
        $recentSignups = $this->getRecentSignups();

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
            ],
            'kpis'           => $kpis,
            'tab_counts'     => $tabCounts,
            'recent_signups' => $recentSignups,
        ]);
    }

    /**
     * GET /api/admin/activity-feed/unread-count
     *
     * Returns the total count of audit-log events recorded today (since midnight)
     * plus a breakdown of notable event types — used for the sidebar badge.
     */
    public function unreadCount()
    {
        $todayStart = Carbon::today();

        $total = PlatformAuditLog::query()
            ->where('created_at', '>=', $todayStart)
            ->count();

        $alerts = PlatformAuditLog::query()
            ->where('created_at', '>=', $todayStart)
            ->where(function ($q) {
                $q->where('action', 'like', 'auth.failed%')
                  ->orWhere('action', 'like', 'tenant.suspended%')
                  ->orWhere('action', 'like', 'tenant.closed%')
                  ->orWhere('action', 'like', 'billing.payment_failed%')
                  ->orWhere('action', 'like', 'platform.error%');
            })
            ->count();

        return response()->json([
            'count'  => $total,
            'alerts' => $alerts,
        ]);
    }

    private function resolveFromDate(string $range): ?Carbon
    {
        return match ($range) {
            'today' => Carbon::today(),
            '7d'    => Carbon::now()->subDays(7),
            '30d'   => Carbon::now()->subDays(30),
            '90d'   => Carbon::now()->subDays(90),
            'all'   => null,
            default => Carbon::today(),
        };
    }

    private function applyTabFilter($query, string $tab): void
    {
        match ($tab) {
            'signups' => $query->where(function ($q) {
                $q->where('action', 'like', 'tenant.created%')
                  ->orWhere('action', 'like', 'auth.register%');
            }),
            'billing' => $query->where(function ($q) {
                $q->where('action', 'like', 'billing.%')
                  ->orWhere('action', 'like', 'tenant.plan%')
                  ->orWhere('action', 'like', 'currency.%');
            }),
            'alerts' => $query->where(function ($q) {
                $q->where('action', 'like', 'auth.failed%')
                  ->orWhere('action', 'like', 'tenant.suspended%')
                  ->orWhere('action', 'like', 'tenant.closed%')
                  ->orWhere('action', 'like', 'billing.payment_failed%')
                  ->orWhere('action', 'like', 'platform.error%');
            }),
            default => null,
        };
    }

    private function buildKpis(Carbon $todayStart): array
    {
        // Events today
        $eventsToday = PlatformAuditLog::where('created_at', '>=', $todayStart)->count();

        // New signups today
        $signupsToday = PlatformAuditLog::where('created_at', '>=', $todayStart)
            ->where(function ($q) {
                $q->where('action', 'like', 'tenant.created%')
                  ->orWhere('action', 'like', 'auth.register%');
            })
            ->count();

        // Plan changes today
        $planChangesToday = PlatformAuditLog::where('created_at', '>=', $todayStart)
            ->where(function ($q) {
                $q->where('action', 'like', 'tenant.plan%')
                  ->orWhere('action', 'like', 'billing.subscription%');
            })
            ->count();

        // Alerts today
        $alertsToday = PlatformAuditLog::where('created_at', '>=', $todayStart)
            ->where(function ($q) {
                $q->where('action', 'like', 'auth.failed%')
                  ->orWhere('action', 'like', 'tenant.suspended%')
                  ->orWhere('action', 'like', 'tenant.closed%')
                  ->orWhere('action', 'like', 'billing.payment_failed%')
                  ->orWhere('action', 'like', 'platform.error%');
            })
            ->count();

        return [
            'events_today'      => $eventsToday,
            'signups_today'     => $signupsToday,
            'plan_changes_today' => $planChangesToday,
            'alerts_today'      => $alertsToday,
        ];
    }

    private function buildTabCounts(?Carbon $fromDate): array
    {
        $base = PlatformAuditLog::query();
        if ($fromDate) {
            $base->where('created_at', '>=', $fromDate);
        }

        $all = (clone $base)->count();

        $signups = (clone $base)->where(function ($q) {
            $q->where('action', 'like', 'tenant.created%')
              ->orWhere('action', 'like', 'auth.register%');
        })->count();

        $billing = (clone $base)->where(function ($q) {
            $q->where('action', 'like', 'billing.%')
              ->orWhere('action', 'like', 'tenant.plan%')
              ->orWhere('action', 'like', 'currency.%');
        })->count();

        $alerts = (clone $base)->where(function ($q) {
            $q->where('action', 'like', 'auth.failed%')
              ->orWhere('action', 'like', 'tenant.suspended%')
              ->orWhere('action', 'like', 'tenant.closed%')
              ->orWhere('action', 'like', 'billing.payment_failed%')
              ->orWhere('action', 'like', 'platform.error%');
        })->count();

        return [
            'all'     => $all,
            'signups' => $signups,
            'billing' => $billing,
            'alerts'  => $alerts,
        ];
    }

    private function getRecentSignups(): array
    {
        $logs = PlatformAuditLog::query()
            ->with(['tenant:id,name,slug,status,plan_id', 'tenant.plan:id,name'])
            ->where(function ($q) {
                $q->where('action', 'like', 'tenant.created%')
                  ->orWhere('action', 'like', 'auth.register%');
            })
            ->whereNotNull('tenant_id')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return $logs->map(function (PlatformAuditLog $log) {
            return [
                'tenant_id'   => $log->tenant_id,
                'tenant_name' => $log->tenant?->name ?? 'Unknown',
                'tenant_slug' => $log->tenant?->slug ?? '',
                'plan_name'   => $log->tenant?->plan?->name ?? null,
                'created_at'  => $log->created_at?->toISOString(),
            ];
        })->values()->toArray();
    }
}
