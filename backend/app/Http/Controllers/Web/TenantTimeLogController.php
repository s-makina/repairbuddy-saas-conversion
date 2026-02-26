<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyTimeLog;
use App\Models\User;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class TenantTimeLogController extends Controller
{
    public function index(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();
        $user   = $request->user();

        if (! $user) {
            return redirect()->route('web.login');
        }

        $branch = BranchContext::branch();

        if (! $tenant || ! $branch) {
            abort(400, 'Tenant or branch context is missing.');
        }

        $tenantId = (int) $tenant->id;
        $branchId = (int) $branch->id;

        /* ---------- filters ---------- */
        $filterTechnician = $request->query('technician_id');
        $filterTechnician = is_numeric($filterTechnician) && (int) $filterTechnician > 0 ? (int) $filterTechnician : null;

        $filterJob = $request->query('job_id');
        $filterJob = is_numeric($filterJob) && (int) $filterJob > 0 ? (int) $filterJob : null;

        $filterStatus = is_string($request->query('status')) ? trim((string) $request->query('status')) : '';
        if ($filterStatus !== '' && ! in_array($filterStatus, ['pending', 'approved', 'rejected', 'billed'], true)) {
            $filterStatus = '';
        }

        $filterActivity = is_string($request->query('activity')) ? trim((string) $request->query('activity')) : '';

        $filterDateFrom = is_string($request->query('date_from')) ? trim((string) $request->query('date_from')) : '';
        $filterDateTo   = is_string($request->query('date_to')) ? trim((string) $request->query('date_to')) : '';

        $search = is_string($request->query('q')) ? trim((string) $request->query('q')) : '';

        /* ---------- build query ---------- */
        $query = RepairBuddyTimeLog::query()
            ->with([
                'job:id,case_number,title,status_slug',
                'technician:id,name,email',
            ])
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branchId);

        if ($filterTechnician) {
            $query->where('technician_id', $filterTechnician);
        }

        if ($filterJob) {
            $query->where('job_id', $filterJob);
        }

        if ($filterStatus !== '') {
            $query->where('log_state', $filterStatus);
        }

        if ($filterActivity !== '') {
            $query->where('activity', $filterActivity);
        }

        if ($filterDateFrom !== '') {
            $query->whereDate('start_time', '>=', $filterDateFrom);
        }

        if ($filterDateTo !== '') {
            $query->whereDate('start_time', '<=', $filterDateTo);
        }

        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $sub->where('activity', 'like', "%{$search}%")
                    ->orWhere('work_description', 'like', "%{$search}%")
                    ->orWhereHas('job', function ($job) use ($search) {
                        $job->where('case_number', 'like', "%{$search}%")
                            ->orWhere('title', 'like', "%{$search}%");
                    })
                    ->orWhereHas('technician', function ($t) use ($search) {
                        $t->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        /* ---------- summary ---------- */
        $summaryRow = (clone $query)
            ->selectRaw('COUNT(*) as total_logs')
            ->selectRaw('COALESCE(SUM(total_minutes), 0) as total_minutes')
            ->selectRaw('AVG(hourly_rate_cents) as avg_rate_cents')
            ->selectRaw('AVG(hourly_cost_cents) as avg_cost_cents')
            ->selectRaw('COALESCE(SUM((COALESCE(total_minutes,0) * COALESCE(hourly_rate_cents,0)) / 60.0), 0) as total_amount_cents')
            ->selectRaw('COALESCE(SUM((COALESCE(total_minutes,0) * COALESCE(hourly_cost_cents,0)) / 60.0), 0) as total_cost_cents')
            ->first();

        $summary = [
            'total_logs'    => is_numeric($summaryRow?->total_logs) ? (int) $summaryRow->total_logs : 0,
            'total_minutes' => is_numeric($summaryRow?->total_minutes) ? (int) $summaryRow->total_minutes : 0,
            'total_hours'   => is_numeric($summaryRow?->total_minutes) ? round((int) $summaryRow->total_minutes / 60, 1) : 0,
            'avg_rate'      => is_numeric($summaryRow?->avg_rate_cents) ? round((float) $summaryRow->avg_rate_cents / 100, 2) : 0,
            'avg_cost'      => is_numeric($summaryRow?->avg_cost_cents) ? round((float) $summaryRow->avg_cost_cents / 100, 2) : 0,
            'total_charged' => is_numeric($summaryRow?->total_amount_cents) ? round((float) $summaryRow->total_amount_cents / 100, 2) : 0,
            'total_cost'    => is_numeric($summaryRow?->total_cost_cents) ? round((float) $summaryRow->total_cost_cents / 100, 2) : 0,
            'total_profit'  => is_numeric($summaryRow?->total_amount_cents) && is_numeric($summaryRow?->total_cost_cents)
                ? round(((float) $summaryRow->total_amount_cents - (float) $summaryRow->total_cost_cents) / 100, 2)
                : 0,
        ];

        /* ---------- paginated logs ---------- */
        $perPage = 25;
        $logs = $query->orderByDesc('start_time')->orderByDesc('id')->paginate($perPage)->withQueryString();

        /* ---------- filter options ---------- */
        $technicians = User::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('role', ['technician', 'administrator', 'store_manager'])
            ->orderBy('name')
            ->limit(300)
            ->get(['id', 'name']);

        $activityOptions = RepairBuddyTimeLog::query()
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->distinct()
            ->pluck('activity')
            ->filter(fn ($v) => is_string($v) && trim($v) !== '')
            ->sort()
            ->values();

        $currency = is_string($tenant->currency) && $tenant->currency !== ''
            ? strtoupper((string) $tenant->currency) : 'USD';

        return view('tenant.time-logs.index', [
            'tenant'           => $tenant,
            'user'             => $user,
            'activeNav'        => 'time-logs',
            'pageTitle'        => 'Time Logs',
            'logs'             => $logs,
            'summary'          => $summary,
            'technicians'      => $technicians,
            'activityOptions'  => $activityOptions,
            'currency'         => $currency,
            'filterTechnician' => $filterTechnician,
            'filterJob'        => $filterJob,
            'filterStatus'     => $filterStatus,
            'filterActivity'   => $filterActivity,
            'filterDateFrom'   => $filterDateFrom,
            'filterDateTo'     => $filterDateTo,
            'search'           => $search,
        ]);
    }
}
