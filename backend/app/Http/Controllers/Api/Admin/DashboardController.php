<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function kpis(Request $request)
    {
        $tenantRows = Tenant::query()
            ->select(['status', DB::raw('count(*) as c')])
            ->groupBy('status')
            ->get();

        $tenantByStatus = [
            'trial' => 0,
            'active' => 0,
            'past_due' => 0,
            'suspended' => 0,
            'closed' => 0,
        ];

        foreach ($tenantRows as $row) {
            $status = (string) ($row->status ?? '');
            $count = (int) ($row->c ?? 0);
            if (array_key_exists($status, $tenantByStatus)) {
                $tenantByStatus[$status] = $count;
            }
        }

        $userTotal = (int) User::query()->count();
        $adminUserTotal = (int) User::query()->where('is_admin', true)->count();

        $subRows = TenantSubscription::query()
            ->withoutGlobalScope(TenantScope::class)
            ->select(['status', DB::raw('count(*) as c')])
            ->groupBy('status')
            ->get();

        $subsByStatus = [
            'trial' => 0,
            'active' => 0,
            'past_due' => 0,
            'canceled' => 0,
        ];

        foreach ($subRows as $row) {
            $status = (string) ($row->status ?? '');
            $count = (int) ($row->c ?? 0);
            if (array_key_exists($status, $subsByStatus)) {
                $subsByStatus[$status] = $count;
            }
        }

        $activeSubTotal = (int) (($subsByStatus['trial'] ?? 0) + ($subsByStatus['active'] ?? 0) + ($subsByStatus['past_due'] ?? 0));

        $now = now();
        $from30d = $now->copy()->subDays(30);
        $fromYtd = $now->copy()->startOfYear();

        $paid30Rows = Invoice::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('status', 'paid')
            ->where('paid_at', '>=', $from30d)
            ->select(['currency', DB::raw('sum(total_cents) as total_cents')])
            ->groupBy('currency')
            ->get();

        $paidYtdRows = Invoice::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('status', 'paid')
            ->where('paid_at', '>=', $fromYtd)
            ->select(['currency', DB::raw('sum(total_cents) as total_cents')])
            ->groupBy('currency')
            ->get();

        $paidLast30dByCurrency = [];
        foreach ($paid30Rows as $row) {
            $currency = strtoupper((string) ($row->currency ?? ''));
            if ($currency !== '') {
                $paidLast30dByCurrency[$currency] = (int) ($row->total_cents ?? 0);
            }
        }

        $paidYtdByCurrency = [];
        foreach ($paidYtdRows as $row) {
            $currency = strtoupper((string) ($row->currency ?? ''));
            if ($currency !== '') {
                $paidYtdByCurrency[$currency] = (int) ($row->total_cents ?? 0);
            }
        }

        $mrrRows = DB::table('tenant_subscriptions as ts')
            ->join('billing_prices as bp', 'bp.id', '=', 'ts.billing_price_id')
            ->whereIn('ts.status', ['trial', 'active', 'past_due'])
            ->select([
                'ts.currency',
                DB::raw("sum(case when bp.interval = 'month' then bp.amount_cents when bp.interval = 'year' then round(bp.amount_cents / 12) else 0 end) as mrr_cents"),
            ])
            ->groupBy('ts.currency')
            ->get();

        $mrrByCurrency = [];
        foreach ($mrrRows as $row) {
            $currency = strtoupper((string) ($row->currency ?? ''));
            if ($currency !== '') {
                $mrrByCurrency[$currency] = (int) ($row->mrr_cents ?? 0);
            }
        }

        return response()->json([
            'generated_at' => $now->toISOString(),
            'tenants' => [
                'total' => array_sum($tenantByStatus),
                'by_status' => $tenantByStatus,
            ],
            'users' => [
                'total' => $userTotal,
                'admins' => $adminUserTotal,
            ],
            'subscriptions' => [
                'active_total' => $activeSubTotal,
                'by_status' => $subsByStatus,
            ],
            'revenue' => [
                'paid_last_30d_by_currency' => $paidLast30dByCurrency,
                'paid_ytd_by_currency' => $paidYtdByCurrency,
            ],
            'mrr_by_currency' => $mrrByCurrency,
        ]);
    }
}
