@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? 'Time Logs'])

@php
    /** @var \App\Models\Tenant|null $tenant */
    /** @var \App\Models\User|null   $user */

    $logs              = $logs ?? collect();
    $summary           = is_array($summary ?? null) ? $summary : [];
    $technicians       = $technicians ?? collect();
    $activityOptions   = $activityOptions ?? collect();

    $role       = is_string($user?->role) ? (string) $user->role : null;
    $tenantSlug = is_string($tenant?->slug) ? (string) $tenant->slug : '';
    $baseUrl    = $tenantSlug ? route('tenant.time_logs.index', ['business' => $tenantSlug]) : '#';
    $currency   = strtoupper(is_string($currency ?? null) ? (string) $currency : 'USD');

    try {
        $fmt = new \NumberFormatter('en_US', \NumberFormatter::CURRENCY);
        $fmt->setTextAttribute(\NumberFormatter::CURRENCY_CODE, $currency);
        $currencySymbol = $fmt->getSymbol(\NumberFormatter::CURRENCY_SYMBOL) ?: ($currency . ' ');
    } catch (\Exception $e) {
        $currencySymbol = $currency . ' ';
    }

    $formatMoney = function ($amount) use ($currencySymbol) {
        if ($amount === null) return '—';
        return $currencySymbol . number_format((float) $amount, 2, '.', ',');
    };

    // Filters
    $filterTechnician = $filterTechnician ?? null;
    $filterJob        = $filterJob ?? null;
    $filterStatus     = $filterStatus ?? '';
    $filterActivity   = $filterActivity ?? '';
    $filterDateFrom   = $filterDateFrom ?? '';
    $filterDateTo     = $filterDateTo ?? '';
    $search           = $search ?? '';

    // Badge map
    $statusBadgeMap = [
        'pending'  => 'wcrb-pill--pending',
        'approved' => 'wcrb-pill--active',
        'rejected' => 'wcrb-pill--danger',
        'billed'   => 'wcrb-pill--info',
    ];

    // Duration formatter
    $formatDuration = function ($minutes) {
        if ($minutes === null || (int) $minutes <= 0) return '—';
        $h = floor((int) $minutes / 60);
        $m = (int) $minutes % 60;
        return $h > 0 ? sprintf('%dh %dm', $h, $m) : sprintf('%dm', $m);
    };

    // Build columns
    $columns = [
        ['key' => 'id',          'label' => '#',                  'width' => '55px',  'sortable' => true],
        ['key' => 'job',         'label' => __('Job'),            'sortable' => true,  'filter' => true],
        ['key' => 'technician',  'label' => __('Technician'),     'sortable' => true,  'filter' => true],
        ['key' => 'activity',    'label' => __('Activity'),       'width' => '120px', 'sortable' => true],
        ['key' => 'time',        'label' => __('Time'),           'width' => '150px', 'sortable' => true,  'nowrap' => true],
        ['key' => 'duration',    'label' => __('Duration'),       'width' => '90px',  'sortable' => true],
        ['key' => 'charged',     'label' => __('Charged'),        'width' => '100px', 'sortable' => true,  'align' => 'text-end'],
        ['key' => 'cost',        'label' => __('Cost'),           'width' => '100px', 'sortable' => true,  'align' => 'text-end'],
        ['key' => 'profit',      'label' => __('Profit'),         'width' => '100px', 'sortable' => true,  'align' => 'text-end'],
        ['key' => 'status',      'label' => __('Status'),         'width' => '100px', 'sortable' => true,  'badge' => true],
        ['key' => 'created_at',  'label' => __('Created'),        'width' => '110px', 'sortable' => true,  'nowrap' => true],
    ];

    // Build rows
    $rows = [];
    foreach ($logs as $tl) {
        $minutes = is_numeric($tl->total_minutes) ? (int) $tl->total_minutes : 0;
        $rate    = is_numeric($tl->hourly_rate_cents) ? (int) $tl->hourly_rate_cents : 0;
        $costC   = is_numeric($tl->hourly_cost_cents) ? (int) $tl->hourly_cost_cents : 0;

        $charged = round(($minutes * $rate) / 60);
        $costAmt = round(($minutes * $costC) / 60);
        $profit  = $charged - $costAmt;

        $jobLabel = '—';
        if ($tl->relationLoaded('job') && $tl->job) {
            $jobLabel = ($tl->job->case_number ?: 'JOB-' . $tl->job_id);
            if ($tl->job->title) {
                $jobLabel .= ' — ' . \Illuminate\Support\Str::limit($tl->job->title, 30);
            }
        }

        $techName = '—';
        if ($tl->relationLoaded('technician') && $tl->technician) {
            $techName = $tl->technician->name ?? '—';
        }

        $startTime = $tl->start_time ? $tl->start_time->format('M d, g:ia') : '—';
        $endTime   = $tl->end_time ? $tl->end_time->format('g:ia') : '—';
        $timeStr   = $startTime . ' – ' . $endTime;

        $stateKey = is_string($tl->log_state) ? strtolower(trim((string) $tl->log_state)) : 'pending';

        $rows[] = [
            'id'         => $tl->id,
            'job'        => $jobLabel,
            'technician' => $techName,
            'activity'   => ucfirst($tl->activity ?? ''),
            'time'       => $timeStr,
            'duration'   => $formatDuration($tl->total_minutes),
            'charged'    => $formatMoney($charged / 100),
            'cost'       => $formatMoney($costAmt / 100),
            'profit'     => $formatMoney($profit / 100),
            'status'     => ucfirst($stateKey),
            '_badgeClass_status' => $statusBadgeMap[$stateKey] ?? 'wcrb-pill--pending',
            'created_at' => $tl->created_at?->format('M d, Y') ?? '—',
        ];
    }
@endphp

@section('content')
<div class="container-fluid p-3">

    {{-- ═══════ Summary Cards ═══════ --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg">
            <div class="card stats-card bg-primary text-white">
                <div class="card-body py-3 px-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="card-title mb-1" style="font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; opacity: .85;">{{ __('Total Logs') }}</div>
                            <h4 class="mb-0">{{ $summary['total_logs'] ?? 0 }}</h4>
                        </div>
                        <div style="font-size: 1.5rem; opacity: .4;"><i class="bi bi-clock-history"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg">
            <div class="card stats-card bg-success text-white">
                <div class="card-body py-3 px-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="card-title mb-1" style="font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; opacity: .85;">{{ __('Total Hours') }}</div>
                            <h4 class="mb-0">{{ $summary['total_hours'] ?? 0 }}h</h4>
                        </div>
                        <div style="font-size: 1.5rem; opacity: .4;"><i class="bi bi-hourglass-split"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg">
            <div class="card stats-card bg-info text-white">
                <div class="card-body py-3 px-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="card-title mb-1" style="font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; opacity: .85;">{{ __('Total Charged') }}</div>
                            <h4 class="mb-0">{{ $formatMoney($summary['total_charged'] ?? 0) }}</h4>
                        </div>
                        <div style="font-size: 1.5rem; opacity: .4;"><i class="bi bi-currency-dollar"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg">
            <div class="card stats-card bg-warning text-dark">
                <div class="card-body py-3 px-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="card-title mb-1" style="font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; opacity: .85;">{{ __('Total Cost') }}</div>
                            <h4 class="mb-0">{{ $formatMoney($summary['total_cost'] ?? 0) }}</h4>
                        </div>
                        <div style="font-size: 1.5rem; opacity: .4;"><i class="bi bi-cash-stack"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg">
            <div class="card stats-card bg-dark text-white">
                <div class="card-body py-3 px-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="card-title mb-1" style="font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; opacity: .85;">{{ __('Total Profit') }}</div>
                            <h4 class="mb-0">{{ $formatMoney($summary['total_profit'] ?? 0) }}</h4>
                        </div>
                        <div style="font-size: 1.5rem; opacity: .4;"><i class="bi bi-graph-up-arrow"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════ Flash Messages ═══════ --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ═══════ DataTable ═══════ --}}
    <x-ui.datatable
        tableId="timeLogsTable"
        :title="__('Time Logs')"
        :columns="$columns"
        :rows="$rows"
        :searchable="true"
        :paginate="true"
        :perPage="25"
        :perPageOptions="[10, 25, 50, 100]"
        :exportable="true"
        :filterable="true"
        :emptyMessage="__('No time logs found.')"
    >
        <x-slot:filters>
            <form method="GET" action="{{ $baseUrl }}">
                <div class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label" style="font-size: 0.75rem;">{{ __('Search') }}</label>
                        <input type="text" class="form-control form-control-sm" name="q"
                               value="{{ $search }}" placeholder="{{ __('Activity, job, tech…') }}">
                    </div>

                    @if (in_array($role, ['administrator', 'store_manager']) || ($user && $user->is_admin))
                    <div class="col-md-2">
                        <label class="form-label" style="font-size: 0.75rem;">{{ __('Technician') }}</label>
                        <select class="form-select form-select-sm" name="technician_id">
                            <option value="">{{ __('All Technicians') }}</option>
                            @foreach ($technicians as $t)
                                <option value="{{ $t->id }}" {{ (string)$filterTechnician === (string)$t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="col-md-1">
                        <label class="form-label" style="font-size: 0.75rem;">{{ __('Job ID') }}</label>
                        <input type="number" class="form-control form-control-sm" name="job_id"
                               value="{{ $filterJob }}" placeholder="#">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label" style="font-size: 0.75rem;">{{ __('Status') }}</label>
                        <select class="form-select form-select-sm" name="status">
                            <option value="">{{ __('All') }}</option>
                            @foreach (['pending', 'approved', 'rejected', 'billed'] as $st)
                                <option value="{{ $st }}" {{ $filterStatus === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label" style="font-size: 0.75rem;">{{ __('Activity') }}</label>
                        <select class="form-select form-select-sm" name="activity">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($activityOptions as $act)
                                <option value="{{ $act }}" {{ $filterActivity === $act ? 'selected' : '' }}>{{ ucfirst($act) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-1">
                        <label class="form-label" style="font-size: 0.75rem;">{{ __('From') }}</label>
                        <input type="date" class="form-control form-control-sm" name="date_from" value="{{ $filterDateFrom }}">
                    </div>

                    <div class="col-md-1">
                        <label class="form-label" style="font-size: 0.75rem;">{{ __('To') }}</label>
                        <input type="date" class="form-control form-control-sm" name="date_to" value="{{ $filterDateTo }}">
                    </div>

                    <div class="col-auto d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i>{{ __('Apply') }}</button>
                        <a href="{{ $baseUrl }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-clockwise"></i></a>
                    </div>
                </div>
            </form>
        </x-slot:filters>
    </x-ui.datatable>

    {{-- ═══════ Server-side Pagination ═══════ --}}
    @if ($logs instanceof \Illuminate\Pagination\LengthAwarePaginator && $logs->hasPages())
    <div class="d-flex justify-content-center mt-3">
        {{ $logs->links() }}
    </div>
    @endif

</div>
@endsection

@push('page-styles')
<style>
    .wcrb-pill--pending { color: #92400e; background: rgba(245,158,11,.10); border-color: rgba(245,158,11,.25); }
    .wcrb-pill--danger  { color: #991b1b; background: rgba(239,68,68,.10);  border-color: rgba(239,68,68,.25); }
    .wcrb-pill--info    { color: #1e40af; background: rgba(59,130,246,.10);  border-color: rgba(59,130,246,.25); }

    [data-bs-theme="dark"] .wcrb-pill--pending { background: rgba(245,158,11,.20); }
    [data-bs-theme="dark"] .wcrb-pill--danger  { background: rgba(239,68,68,.20); }
    [data-bs-theme="dark"] .wcrb-pill--info    { background: rgba(59,130,246,.20); }
</style>
@endpush
