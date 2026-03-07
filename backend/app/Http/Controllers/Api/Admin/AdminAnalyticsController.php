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

class AdminAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'months' => ['nullable', 'integer', 'min:3', 'max:24'],
        ]);

        $months = (int) ($validated['months'] ?? 12);
        $now    = now();

        // ── Tenant registrations per month ──────────────────────────────────
        $tenantRows = DB::table('tenants')
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count")
            ->where('created_at', '>=', $now->copy()->subMonths($months)->startOfMonth())
            ->groupByRaw("DATE_FORMAT(created_at, '%Y-%m')")
            ->orderBy('month')
            ->get();

        $tenantsByMonth = [];
        foreach ($tenantRows as $row) {
            $tenantsByMonth[$row->month] = (int) $row->count;
        }

        // ── User (non-admin) registrations per month ─────────────────────────
        $userRows = DB::table('users')
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count")
            ->where('is_admin', false)
            ->where('created_at', '>=', $now->copy()->subMonths($months)->startOfMonth())
            ->groupByRaw("DATE_FORMAT(created_at, '%Y-%m')")
            ->orderBy('month')
            ->get();

        $usersByMonth = [];
        foreach ($userRows as $row) {
            $usersByMonth[$row->month] = (int) $row->count;
        }

        // ── Revenue (paid invoices) per month ─────────────────────────────────
        $revenueRows = DB::table('invoices')
            ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as month, currency, SUM(total_cents) as total_cents")
            ->where('status', 'paid')
            ->where('paid_at', '>=', $now->copy()->subMonths($months)->startOfMonth())
            ->groupByRaw("DATE_FORMAT(paid_at, '%Y-%m'), currency")
            ->orderBy('month')
            ->get();

        $revenueByMonth = [];
        foreach ($revenueRows as $row) {
            $month    = $row->month;
            $currency = strtoupper((string) ($row->currency ?? 'USD'));
            if (! isset($revenueByMonth[$month])) {
                $revenueByMonth[$month] = [];
            }
            $revenueByMonth[$month][$currency] = (int) $row->total_cents;
        }

        // ── Revenue breakdown by billing plan ─────────────────────────────────
        $planRevRows = DB::table('invoices as i')
            ->join('tenant_subscriptions as ts', 'ts.id', '=', 'i.subscription_id')
            ->join('billing_plan_versions as bpv', 'bpv.id', '=', 'ts.billing_plan_version_id')
            ->join('billing_plans as bp', 'bp.id', '=', 'bpv.billing_plan_id')
            ->selectRaw("bp.name as plan_name, i.currency, SUM(i.total_cents) as total_cents, COUNT(i.id) as invoice_count")
            ->where('i.status', 'paid')
            ->where('i.paid_at', '>=', $now->copy()->subMonths($months)->startOfMonth())
            ->groupByRaw("bp.name, i.currency")
            ->orderByRaw("SUM(i.total_cents) DESC")
            ->get();

        $revenueByPlan = [];
        foreach ($planRevRows as $row) {
            $revenueByPlan[] = [
                'plan_name'     => (string) $row->plan_name,
                'currency'      => strtoupper((string) ($row->currency ?? 'USD')),
                'total_cents'   => (int) $row->total_cents,
                'invoice_count' => (int) $row->invoice_count,
            ];
        }

        // ── Subscription status snapshot ──────────────────────────────────────
        $subStatusRows = TenantSubscription::query()
            ->withoutGlobalScope(TenantScope::class)
            ->select(['status', DB::raw('count(*) as c')])
            ->groupBy('status')
            ->get();

        $subsByStatus = ['trial' => 0, 'active' => 0, 'past_due' => 0, 'canceled' => 0];
        foreach ($subStatusRows as $row) {
            $s = (string) ($row->status ?? '');
            if (array_key_exists($s, $subsByStatus)) {
                $subsByStatus[$s] = (int) ($row->c ?? 0);
            }
        }

        // ── Tenant status snapshot ────────────────────────────────────────────
        $tenantStatusRows = Tenant::query()
            ->select(['status', DB::raw('count(*) as c')])
            ->groupBy('status')
            ->get();

        $tenantsByStatus = ['trial' => 0, 'active' => 0, 'past_due' => 0, 'suspended' => 0, 'closed' => 0];
        foreach ($tenantStatusRows as $row) {
            $s = (string) ($row->status ?? '');
            if (array_key_exists($s, $tenantsByStatus)) {
                $tenantsByStatus[$s] = (int) ($row->c ?? 0);
            }
        }

        // ── MRR trend per month (from paid invoices as proxy) ─────────────────
        // We use actual paid revenue per month as the MRR indicator.
        // Group by month+currency, pick only primary USD or first currency.
        $mrrTrendRows = DB::table('invoices')
            ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as month, SUM(total_cents) as total_cents")
            ->where('status', 'paid')
            ->where('currency', 'USD')
            ->where('paid_at', '>=', $now->copy()->subMonths($months)->startOfMonth())
            ->groupByRaw("DATE_FORMAT(paid_at, '%Y-%m')")
            ->orderBy('month')
            ->get();

        $mrrByMonth = [];
        foreach ($mrrTrendRows as $row) {
            $mrrByMonth[$row->month] = (int) $row->total_cents;
        }

        // ── Build calendar buckets for the requested period ───────────────────
        $buckets = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $buckets[] = $now->copy()->subMonths($i)->format('Y-m');
        }

        $tenantTrend  = [];
        $userTrend    = [];
        $mrrTrend     = [];
        $revTrend     = [];

        foreach ($buckets as $bucket) {
            $tenantTrend[] = ['month' => $bucket, 'count' => $tenantsByMonth[$bucket] ?? 0];
            $userTrend[]   = ['month' => $bucket, 'count' => $usersByMonth[$bucket] ?? 0];
            $mrrTrend[]    = ['month' => $bucket, 'cents' => $mrrByMonth[$bucket] ?? 0];
            $revTrend[]    = ['month' => $bucket, 'by_currency' => $revenueByMonth[$bucket] ?? (object) []];
        }

        return response()->json([
            'months'               => $months,
            'tenant_trend'         => $tenantTrend,
            'user_trend'           => $userTrend,
            'mrr_trend'            => $mrrTrend,
            'revenue_trend'        => $revTrend,
            'revenue_by_plan'      => $revenueByPlan,
            'subscriptions_snapshot' => $subsByStatus,
            'tenants_snapshot'     => $tenantsByStatus,
            'totals'               => [
                'tenants' => (int) Tenant::query()->count(),
                'users'   => (int) User::query()->where('is_admin', false)->count(),
            ],
        ]);
    }
}
