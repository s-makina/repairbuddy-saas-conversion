<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyJob;
use App\Models\RepairBuddyTimeLog;
use App\Services\TenantSettings\TenantSettingsStore;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TenantTechTimeLogController extends Controller
{
    /**
     * Technician time-log dashboard.
     *
     * Populates all the data the `tenant.timelog` blade expects.
     */
    public function dashboard(Request $request, string $business)
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
        $techId   = (int) $user->id;

        $currency = is_string($tenant->currency) && $tenant->currency !== ''
            ? strtoupper((string) $tenant->currency) : 'USD';

        /* ─── Settings ─────────────────────────────────────── */
        $store = new TenantSettingsStore($tenant);
        $timelogSettings = $store->get('time_log', []);
        if (! is_array($timelogSettings)) {
            $timelogSettings = [];
        }

        $activitiesRaw = (string) ($timelogSettings['activities'] ?? '');
        $activityTypes = array_values(array_filter(
            array_map('trim', explode("\n", $activitiesRaw)),
            fn ($v) => $v !== ''
        ));
        if (empty($activityTypes)) {
            $activityTypes = ['Diagnosis', 'Repair', 'Testing', 'Cleaning', 'Consultation', 'Other'];
        }

        $disabled = (bool) ($timelogSettings['disable_timelog'] ?? false);
        $includedStatuses = (array) ($timelogSettings['included_statuses'] ?? []);

        /* ─── Time Boundaries ──────────────────────────────── */
        $now        = Carbon::now();
        $todayStart = $now->copy()->startOfDay();
        $todayEnd   = $now->copy()->endOfDay();
        $weekStart  = $now->copy()->startOfWeek();
        $weekEnd    = $now->copy()->endOfWeek();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd   = $now->copy()->endOfMonth();

        /* ─── Stats ────────────────────────────────────────── */
        $todayLogs = RepairBuddyTimeLog::query()
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->where('technician_id', $techId)
            ->whereBetween('start_time', [$todayStart, $todayEnd])
            ->get();

        $weekLogs = RepairBuddyTimeLog::query()
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->where('technician_id', $techId)
            ->whereBetween('start_time', [$weekStart, $weekEnd])
            ->get();

        $monthLogs = RepairBuddyTimeLog::query()
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->where('technician_id', $techId)
            ->whereBetween('start_time', [$monthStart, $monthEnd])
            ->get();

        $todayMinutes = $todayLogs->sum('total_minutes');
        $weekMinutes  = $weekLogs->sum('total_minutes');

        $weekBillableMinutes = $weekLogs->where('is_billable', true)->sum('total_minutes');
        $billableRate = $weekMinutes > 0
            ? round(($weekBillableMinutes / $weekMinutes) * 100, 0)
            : 0;

        $monthEarningsCents = $monthLogs->where('is_billable', true)->sum(function ($log) {
            $mins = is_numeric($log->total_minutes) ? (int) $log->total_minutes : 0;
            $rate = is_numeric($log->hourly_rate_cents) ? (int) $log->hourly_rate_cents : 0;
            return round(($mins * $rate) / 60);
        });

        $completedJobs = RepairBuddyJob::query()
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->whereHas('technicians', fn ($q) => $q->where('users.id', $techId))
            ->where('status_slug', 'completed')
            ->whereBetween('updated_at', [$monthStart, $monthEnd])
            ->count();

        $avgTimePerJob = 0;
        if ($completedJobs > 0) {
            $totalJobMinutes = $monthLogs->sum('total_minutes');
            $avgTimePerJob = round($totalJobMinutes / $completedJobs / 60, 1);
        }

        $stats = [
            'today_hours'            => round($todayMinutes / 60, 1),
            'week_hours'             => round($weekMinutes / 60, 1),
            'billable_rate'          => $billableRate,
            'month_earnings'         => number_format($monthEarningsCents / 100, 2),
            'month_earnings_formatted' => $currency . ' ' . number_format($monthEarningsCents / 100, 2),
            'completed_jobs'         => $completedJobs,
            'avg_time_per_job'       => $avgTimePerJob,
        ];

        /* ─── Productivity Stats ───────────────────────────── */
        $workDaysThisWeek = max(1, min(7, (int) $now->dayOfWeek ?: 7));
        $avgDailyHours = round(($weekMinutes / 60) / $workDaysThisWeek, 1);

        $weekJobIds = $weekLogs->pluck('job_id')->unique()->filter();
        $completedJobsThisWeek = RepairBuddyJob::query()
            ->whereIn('id', $weekJobIds)
            ->where('status_slug', 'completed')
            ->count();

        $efficiencyScore = $weekMinutes > 0
            ? min(100, round(($weekBillableMinutes / $weekMinutes) * 100, 0))
            : 0;

        $productivityStats = [
            'avg_daily_hours'       => $avgDailyHours,
            'total_jobs_completed'  => $completedJobsThisWeek,
            'efficiency_score'      => $efficiencyScore,
        ];

        /* ─── Activity Distribution ────────────────────────── */
        $activityDist = [];
        $totalWeekMins = $weekLogs->sum('total_minutes');
        if ($totalWeekMins > 0) {
            $groupedByActivity = $weekLogs->groupBy(function ($log) {
                $act = is_string($log->activity) ? strtolower(trim($log->activity)) : 'other';
                return preg_replace('/[^a-z0-9]/', '_', $act);
            });
            foreach ($groupedByActivity as $key => $logs) {
                $activityDist[$key] = round(($logs->sum('total_minutes') / $totalWeekMins) * 100, 0);
            }
        }

        /* ─── Weekly Chart Data ───────────────────────────── */
        $weeklyChartData = [
            'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            'data' => array_fill(0, 7, 0),
        ];

        foreach ($weekLogs as $log) {
            if ($log->start_time) {
                $dayOfWeek = (int) $log->start_time->dayOfWeekIso - 1; // 0=Mon, 6=Sun
                $minutes = is_numeric($log->total_minutes) ? (int) $log->total_minutes : 0;
                $weeklyChartData['data'][$dayOfWeek] += round($minutes / 60, 1);
            }
        }

        /* ─── Eligible Jobs Dropdown ──────────────────────── */
        $jobQuery = RepairBuddyJob::query()
            ->with(['jobDevices'])
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branchId);

        if (! empty($includedStatuses)) {
            $jobQuery->whereIn('status_slug', $includedStatuses);
        } else {
            $jobQuery->whereNotIn('status_slug', ['completed', 'cancelled', 'delivered']);
        }

        // For technicians, only show their assigned jobs
        $role = is_string($user->role) ? strtolower($user->role) : '';
        if ($role === 'technician') {
            $jobQuery->whereHas('technicians', fn ($q) => $q->where('user_id', $techId));
        }

        $eligibleJobs = $jobQuery->orderByDesc('id')->limit(200)->get();

        $jobDropdownHtml = '<select name="job_device" id="timeLogJobDeviceSelect" class="form-select" required>'
            . '<option value="">' . __('Select a Job / Device') . '</option>';

        $selectedValue = is_string($request->query('job')) ? trim((string) $request->query('job')) : '';

        foreach ($eligibleJobs as $ej) {
            $case = $ej->case_number ?: 'JOB-' . $ej->id;
            $title = $ej->title ? ' — ' . \Illuminate\Support\Str::limit($ej->title, 40) : '';

            if ($ej->jobDevices && $ej->jobDevices->count() > 0) {
                $jobDropdownHtml .= '<optgroup label="' . e($case . $title) . '">';
                foreach ($ej->jobDevices as $idx => $dev) {
                    $devLabel = $dev->label_snapshot ?: 'Device ' . ($idx + 1);
                    $val = $ej->id . '|' . ($dev->customer_device_id ?? '') . '|' . ($dev->serial_snapshot ?? '') . '|' . $idx;
                    $selected = ($val === $selectedValue) ? ' selected' : '';
                    $jobDropdownHtml .= '<option value="' . e($val) . '"' . $selected . '>' . e($devLabel) . '</option>';
                }
                $jobDropdownHtml .= '</optgroup>';
            } else {
                $val = $ej->id . '|||0';
                $selected = ($val === $selectedValue) ? ' selected' : '';
                $jobDropdownHtml .= '<option value="' . e($val) . '"' . $selected . '>' . e($case . $title) . '</option>';
            }
        }

        $jobDropdownHtml .= '</select>';

        /* ─── Activity Type Dropdowns ─────────────────────── */
        $buildActivityDropdown = function (string $id, string $name) use ($activityTypes): string {
            $html = '<select class="form-select" id="' . e($id) . '" name="' . e($name) . '">';
            foreach ($activityTypes as $type) {
                $html .= '<option value="' . e(strtolower($type)) . '">' . e($type) . '</option>';
            }
            $html .= '</select>';
            return $html;
        };

        $activityDropdownHtml       = $buildActivityDropdown('activityType', 'timelog_activity_type');
        $activityDropdownManualHtml = $buildActivityDropdown('activityTypeManual', 'timelog_activity_type');

        /* ─── Recent Time Logs HTML ───────────────────────── */
        $recentLogs = RepairBuddyTimeLog::query()
            ->with(['job:id,case_number,title'])
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->where('technician_id', $techId)
            ->orderByDesc('start_time')
            ->limit(50)
            ->get();

        $recentHtml = '';
        if ($recentLogs->isEmpty()) {
            $recentHtml = '<tr><td colspan="5" class="text-center text-muted py-4">'
                . __('No time logs recorded yet.')
                . '</td></tr>';
        } else {
            foreach ($recentLogs as $rl) {
                $jobCase = $rl->job ? ($rl->job->case_number ?: 'JOB-' . $rl->job_id) : '—';
                $activity = ucfirst($rl->activity ?? '');
                $start = $rl->start_time ? $rl->start_time->format('M d, g:ia') : '—';
                $end = $rl->end_time ? $rl->end_time->format('g:ia') : '—';
                $time = $start . ' – ' . $end;

                $mins = is_numeric($rl->total_minutes) ? (int) $rl->total_minutes : 0;
                $dh = floor($mins / 60);
                $dm = $mins % 60;
                $dur = $dh > 0 ? sprintf('%dh %dm', $dh, $dm) : sprintf('%dm', $dm);

                $rateCents = is_numeric($rl->hourly_rate_cents) ? (int) $rl->hourly_rate_cents : 0;
                $amount = round(($mins * $rateCents) / 60);
                $amountFmt = $currency . ' ' . number_format($amount / 100, 2);

                $actColor = 'secondary';
                $actLower = strtolower($rl->activity ?? '');
                $colorMap = [
                    'repair' => 'primary', 'diagnostic' => 'info', 'diagnosis' => 'info',
                    'testing' => 'success', 'test' => 'success', 'cleaning' => 'warning',
                    'consultation' => 'secondary', 'other' => 'secondary',
                ];
                foreach ($colorMap as $k => $v) {
                    if (str_contains($actLower, $k)) { $actColor = $v; break; }
                }

                $recentHtml .= '<tr>'
                    . '<td class="ps-4"><strong>' . e($jobCase) . '</strong></td>'
                    . '<td><span class="badge bg-' . $actColor . '">' . e($activity) . '</span></td>'
                    . '<td>' . e($time) . '</td>'
                    . '<td>' . e($dur) . '</td>'
                    . '<td class="text-end pe-4"><strong>' . e($amountFmt) . '</strong></td>'
                    . '</tr>';
            }
        }

        /* ─── Selected job parsing (from query string) ───── */
        $selectedValue = is_string($request->query('job')) ? trim((string) $request->query('job')) : '';
        $jobId = null;
        $deviceId = '';
        $deviceSerial = '';
        $deviceIndex = 0;
        $deviceLabel = '';
        $formaJobId = null;
        $displayName = '';

        if ($selectedValue !== '') {
            $parts = explode('|', $selectedValue);
            $jobId = is_numeric($parts[0] ?? null) ? (int) $parts[0] : null;
            $deviceId = $parts[1] ?? '';
            $deviceSerial = $parts[2] ?? '';
            $deviceIndex = is_numeric($parts[3] ?? null) ? (int) $parts[3] : 0;

            if ($jobId) {
                $selectedJob = $eligibleJobs->firstWhere('id', $jobId);
                if ($selectedJob) {
                    $formaJobId = str_pad((string) $jobId, 5, '0', STR_PAD_LEFT);
                    $displayName = $selectedJob->case_number ?: 'JOB-' . $jobId;
                    if ($selectedJob->title) {
                        $displayName .= ' — ' . $selectedJob->title;
                    }

                    if ($selectedJob->jobDevices && $selectedJob->jobDevices->count() > 0) {
                        $dev = $selectedJob->jobDevices->get($deviceIndex);
                        if ($dev) {
                            $deviceLabel = $dev->label_snapshot ?: 'Device ' . ($deviceIndex + 1);
                        }
                    }
                }
            }
        }

        /* ─── Running Timer (for restoration after page load) ─── */
        $runningTimer = RepairBuddyTimeLog::query()
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->where('technician_id', $techId)
            ->where('log_state', 'running')
            ->first();

        $runningTimerData = null;
        if ($runningTimer) {
            $runningTimerData = [
                'id' => $runningTimer->id,
                'job_id' => $runningTimer->job_id,
                'start_time' => $runningTimer->start_time?->toIso8601String(),
                'activity' => $runningTimer->activity,
                'work_description' => $runningTimer->work_description,
                'is_billable' => $runningTimer->is_billable,
                'device_id' => $runningTimer->device_id,
                'device_serial' => $runningTimer->device_serial,
                'device_index' => $runningTimer->device_index,
            ];
        }

        return view('tenant.timelog', [
            'tenant'     => $tenant,
            'user'       => $user,
            'activeNav'  => 'time-logs',
            'pageTitle'  => 'Time Log',

            // Role & license
            'userRole'      => $role,
            'licenseState'  => true,

            // Stats
            'stats'               => $stats,
            'productivity_stats'  => $productivityStats,
            'activity_distribution' => $activityDist,
            'activity_types'      => $activityTypes,
            'weekly_chart_data'   => $weeklyChartData,

            // Job/device selection
            'eligible_jobs_with_devices_dropdown_html' => $jobDropdownHtml,
            'selected_value'  => $selectedValue,
            'job_id'          => $jobId,
            'device_id'       => $deviceId,
            'device_serial'   => $deviceSerial,
            'device_index'    => $deviceIndex,
            'device_label'    => $deviceLabel,
            'forma_job_id'    => $formaJobId,
            'display_name'    => $displayName,
            'technician_id'   => $techId,

            // Dropdowns
            'timelog_activity_types_dropdown_html'        => $activityDropdownHtml,
            'timelog_activity_types_dropdown_manual_html' => $activityDropdownManualHtml,
            'timelog_nonce_field_html'                    => '', // not needed in SaaS (CSRF via meta tag)

            // Recent logs
            'recent_time_logs_html' => $recentHtml,

            // Running timer (for restoration)
            'running_timer' => $runningTimerData,
        ]);
    }

    /**
     * Start a running timer.
     */
    public function startTimer(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();
        $user = $request->user();
        $branch = BranchContext::branch();

        if (! $tenant || ! $branch || ! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'job_id' => 'required|integer|exists:repairbuddy_jobs,id',
            'start_time' => 'required|date',
            'activity' => 'required|string',
            'work_description' => 'required|string',
            'is_billable' => 'boolean',
            'device_id' => 'nullable|string',
            'device_serial' => 'nullable|string',
            'device_index' => 'nullable|integer',
        ]);

        // Check technician assignment
        $job = RepairBuddyJob::where('tenant_id', $tenant->id)
            ->where('id', $validated['job_id'])
            ->first();

        if (! $job) {
            return response()->json(['message' => 'Job not found.'], 404);
        }

        $assignedTechIds = $job->technicians()->pluck('users.id')->toArray();
        if (! in_array($user->id, $assignedTechIds) && ! $user->hasRole(['admin', 'manager'])) {
            return response()->json(['message' => 'You are not assigned to this job.'], 403);
        }

        // Create running time log
        $timeLog = RepairBuddyTimeLog::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'job_id' => $validated['job_id'],
            'technician_id' => $user->id,
            'start_time' => $validated['start_time'],
            'activity' => $validated['activity'],
            'work_description' => $validated['work_description'],
            'is_billable' => $validated['is_billable'] ?? true,
            'device_id' => $validated['device_id'] ?? null,
            'device_serial' => $validated['device_serial'] ?? null,
            'device_index' => $validated['device_index'] ?? 0,
            'time_type' => 'timer',
            'log_state' => 'running',
            'hourly_rate_cents' => $user->tech_hourly_rate_cents ?? 0,
        ]);

        return response()->json(['time_log' => $timeLog], 201);
    }

    /**
     * Pause a running timer.
     */
    public function pauseTimer(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();
        $user = $request->user();

        $validated = $request->validate([
            'time_log_id' => 'required|integer',
        ]);

        $timeLog = RepairBuddyTimeLog::where('tenant_id', $tenant->id)
            ->where('id', $validated['time_log_id'])
            ->where('technician_id', $user->id)
            ->where('log_state', 'running')
            ->first();

        if (! $timeLog) {
            return response()->json(['message' => 'Running timer not found.'], 404);
        }

        $timeLog->update(['log_state' => 'paused']);

        return response()->json(['time_log' => $timeLog]);
    }

    /**
     * Stop and complete a timer.
     */
    public function stopTimer(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();
        $user = $request->user();

        $validated = $request->validate([
            'time_log_id' => 'required|integer',
            'end_time' => 'required|date',
            'total_minutes' => 'required|integer',
        ]);

        $timeLog = RepairBuddyTimeLog::where('tenant_id', $tenant->id)
            ->where('id', $validated['time_log_id'])
            ->where('technician_id', $user->id)
            ->whereIn('log_state', ['running', 'paused'])
            ->first();

        if (! $timeLog) {
            return response()->json(['message' => 'Active timer not found.'], 404);
        }

        $timeLog->update([
            'end_time' => $validated['end_time'],
            'total_minutes' => $validated['total_minutes'],
            'log_state' => 'pending',
        ]);

        return response()->json(['time_log' => $timeLog]);
    }

    /**
     * Save a manual time entry.
     */
    public function saveEntry(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();
        $user = $request->user();
        $branch = BranchContext::branch();

        if (! $tenant || ! $branch || ! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'job_id' => 'required|integer|exists:repairbuddy_jobs,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'total_minutes' => 'required|integer|min:1',
            'activity' => 'required|string',
            'work_description' => 'required|string',
            'is_billable' => 'boolean',
            'device_id' => 'nullable|string',
            'device_serial' => 'nullable|string',
            'device_index' => 'nullable|integer',
        ]);

        // Check technician assignment
        $job = RepairBuddyJob::where('tenant_id', $tenant->id)
            ->where('id', $validated['job_id'])
            ->first();

        if (! $job) {
            return response()->json(['message' => 'Job not found.'], 404);
        }

        $assignedTechIds = $job->technicians()->pluck('users.id')->toArray();
        if (! in_array($user->id, $assignedTechIds) && ! $user->hasRole(['admin', 'manager'])) {
            return response()->json(['message' => 'You are not assigned to this job.'], 403);
        }

        $timeLog = RepairBuddyTimeLog::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'job_id' => $validated['job_id'],
            'technician_id' => $user->id,
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'total_minutes' => $validated['total_minutes'],
            'activity' => $validated['activity'],
            'work_description' => $validated['work_description'],
            'is_billable' => $validated['is_billable'] ?? true,
            'device_id' => $validated['device_id'] ?? null,
            'device_serial' => $validated['device_serial'] ?? null,
            'device_index' => $validated['device_index'] ?? 0,
            'time_type' => 'manual',
            'log_state' => 'pending',
            'hourly_rate_cents' => $user->tech_hourly_rate_cents ?? 0,
        ]);

        return response()->json(['time_log' => $timeLog], 201);
    }
}
