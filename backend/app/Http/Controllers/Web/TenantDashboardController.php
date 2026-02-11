<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantDashboardController extends Controller
{
    public function show(Request $request)
    {
        $tenant = TenantContext::tenant();
        $user = $request->user();

        if (! $user) {
            return redirect()->route('web.login');
        }

        $screen = is_string($request->query('screen')) && $request->query('screen') !== ''
            ? (string) $request->query('screen')
            : 'dashboard';

        if ($screen === 'jobs' || $screen === 'jobs_card') {
            $current_view = $screen === 'jobs_card' ? 'card' : 'table';
            $_page = $screen === 'jobs_card' ? 'jobs_card' : 'jobs';

            $view_label = $current_view === 'card' ? 'Table View' : 'Card View';

            $view_url = $tenant?->slug
                ? route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=' . ($current_view === 'card' ? 'jobs' : 'jobs_card')
                : '#';

            $baseDashboardUrl = $tenant?->slug
                ? route('tenant.dashboard', ['business' => $tenant->slug])
                : '#';

            $mockJobRowsTable = <<<'HTML'
<tr class="job_id_101 job_status_in_process">
    <td  class="ps-4" data-label="ID"><a href="#" target="_blank"><strong>00101</a></strong></th>
    <td data-label="Case Number/Tech"><a href="#" target="_blank">WC_ABC123</a><br><strong class="text-primary">Tech: Alex</strong></td>
    <td data-label="Customer">John Smith<br><strong>P</strong>: (555) 111-2222<br><strong>E</strong>: john@example.com</td>
    <td data-label="Devices">Dell Inspiron 15 (D-1001)</td>
    <td data-bs-toggle="tooltip" data-bs-title="P: = Pickup date D: = Delivery date N: = Next service date " data-label="Dates"><strong>P</strong>:02/10/2026<br><strong>D</strong>:02/12/2026<br><strong>N</strong>:02/15/2026</td>
    <td data-label="Total">
        <strong>$120.00</strong>
    </td>
    <td class="gap-3 p-3" data-label="Balance">
        <span class="p-2 text-success-emphasis bg-success-subtle border border-success-subtle rounded-3">$40.00</span>
    </td>
    <td data-label="Payment">
        Partial
    </td>
    <td data-label="Status">
        <div class="dropdown"><button class="btn btn-sm dropdown-toggle d-flex align-items-center btn-info" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi-gear me-2"></i>In Process</button><ul class="dropdown-menu"><li><a class="dropdown-item d-flex justify-content-between align-items-center py-2 "
                            recordid="101"
                            data-type="job_status_update"
                            data-value="new"
                            data-security=""
                            href="#" data-status="new"><div class="d-flex align-items-center"><i class="bi-plus-circle text-primary me-2"></i><span>New</span></div></a></li><li><a class="dropdown-item d-flex justify-content-between align-items-center py-2 active"
                            recordid="101"
                            data-type="job_status_update"
                            data-value="inprocess"
                            data-security=""
                            href="#" data-status="inprocess"><div class="d-flex align-items-center"><i class="bi-gear text-info me-2"></i><span>In Process</span></div><i class="bi bi-check2 text-primary ms-2"></i></a></li><li><a class="dropdown-item d-flex justify-content-between align-items-center py-2 "
                            recordid="101"
                            data-type="job_status_update"
                            data-value="delivered"
                            data-security=""
                            href="#" data-status="delivered"><div class="d-flex align-items-center"><i class="bi-check-square text-success me-2"></i><span>Delivered</span></div></a></li></ul></div>
    </td>
    <td data-label="Priority">
        <div class="dropdown"><button class="btn btn-sm dropdown-toggle d-flex align-items-center btn-warning" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi-arrow-up-circle me-2"></i><span>High</span></button><ul class="dropdown-menu shadow-sm"><li><a class="dropdown-item d-flex justify-content-between align-items-center py-2"
                                    recordid="101"
                                    data-type="update_job_priority"
                                    data-value="normal"
                                    data-security=""
                                    href="#"><div class="d-flex align-items-center"><i class="bi-circle text-secondary me-2"></i><span>Normal</span></div></a></li><li><a class="dropdown-item d-flex justify-content-between align-items-center py-2 active"
                                    recordid="101"
                                    data-type="update_job_priority"
                                    data-value="high"
                                    data-security=""
                                    href="#"><div class="d-flex align-items-center"><i class="bi-arrow-up-circle text-warning me-2"></i><span>High</span></div><i class="bi-check2 text-primary ms-2"></i></a></li><li><a class="dropdown-item d-flex justify-content-between align-items-center py-2"
                                    recordid="101"
                                    data-type="update_job_priority"
                                    data-value="urgent"
                                    data-security=""
                                    href="#"><div class="d-flex align-items-center"><i class="bi-exclamation-triangle text-danger me-2"></i><span>Urgent</span></div></a></li></ul></div>
    </td>
    
    <td data-label="Actions" class="text-end pe-4">
        <div class="dropdown">
            <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-gear me-1"></i> Actions
            </button>
            <ul class="dropdown-menu shadow-sm">
                <li>
                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#openTakePaymentModal" recordid="101" data-security="">
                        <i class="bi bi-credit-card text-success me-2"></i>Take Payment
                    </a>
                </li>
                <li><a class="dropdown-item" href="#" target="_blank">
                    <i class="bi bi-printer text-secondary me-2"></i>Print Job Invoice</a>
                </li>
                <li>
                    <a class="dropdown-item" href="#" target="_blank">
                        <i class="bi bi-file-earmark-pdf text-danger me-2"></i>Download PDF
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="#" target="_blank">
                        <i class="bi bi-envelope text-info me-2"></i>Email Customer
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#wcrbduplicatejobfront" recordid="101" data-security="">
                        <i class="bi bi-files text-warning me-2"></i>Duplicate job
                    </a>
                </li>
                <li><a class="dropdown-item" href="#" target="_blank"><i class="bi bi-pencil-square text-primary me-2"></i>Edit</a></li>
            </ul>
        </div>
    </td>
</tr>
<tr class="job_id_102 job_status_completed">
    <td  class="ps-4" data-label="ID"><a href="#" target="_blank"><strong>00102</a></strong></th>
    <td data-label="Case Number/Tech"><a href="#" target="_blank">WC_DEF456</a><br><strong class="text-primary">Tech: Sam</strong></td>
    <td data-label="Customer">Sarah Johnson<br><strong>P</strong>: (555) 333-4444<br><strong>E</strong>: sarah@example.com</td>
    <td data-label="Devices">iPhone 13 (IP-2233)</td>
    <td data-bs-toggle="tooltip" data-bs-title="P: = Pickup date D: = Delivery date N: = Next service date " data-label="Dates"><strong>P</strong>:02/09/2026<br><strong>D</strong>:02/11/2026</td>
    <td data-label="Total">
        <strong>$80.00</strong>
    </td>
    <td class="gap-3 p-3" data-label="Balance">
        <span class="p-2 text-primary-emphasis bg-primary-subtle border border-primary-subtle rounded-3">$0.00</span>
    </td>
    <td data-label="Payment">
        Paid
    </td>
    <td data-label="Status">
        <div class="dropdown"><button class="btn btn-sm dropdown-toggle d-flex align-items-center btn-success" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi-check-square me-2"></i>Delivered</button><ul class="dropdown-menu"><li><a class="dropdown-item d-flex justify-content-between align-items-center py-2 "
                            recordid="102"
                            data-type="job_status_update"
                            data-value="new"
                            data-security=""
                            href="#" data-status="new"><div class="d-flex align-items-center"><i class="bi-plus-circle text-primary me-2"></i><span>New</span></div></a></li><li><a class="dropdown-item d-flex justify-content-between align-items-center py-2 "
                            recordid="102"
                            data-type="job_status_update"
                            data-value="inprocess"
                            data-security=""
                            href="#" data-status="inprocess"><div class="d-flex align-items-center"><i class="bi-gear text-info me-2"></i><span>In Process</span></div></a></li><li><a class="dropdown-item d-flex justify-content-between align-items-center py-2 active"
                            recordid="102"
                            data-type="job_status_update"
                            data-value="delivered"
                            data-security=""
                            href="#" data-status="delivered"><div class="d-flex align-items-center"><i class="bi-check-square text-success me-2"></i><span>Delivered</span></div><i class="bi bi-check2 text-primary ms-2"></i></a></li></ul></div>
    </td>
    <td data-label="Priority">
        <div class="dropdown"><button class="btn btn-sm dropdown-toggle d-flex align-items-center btn-outline-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi-circle me-2"></i><span>Normal</span></button><ul class="dropdown-menu shadow-sm"><li><a class="dropdown-item d-flex justify-content-between align-items-center py-2 active"
                                    recordid="102"
                                    data-type="update_job_priority"
                                    data-value="normal"
                                    data-security=""
                                    href="#"><div class="d-flex align-items-center"><i class="bi-circle text-secondary me-2"></i><span>Normal</span></div><i class="bi-check2 text-primary ms-2"></i></a></li><li><a class="dropdown-item d-flex justify-content-between align-items-center py-2"
                                    recordid="102"
                                    data-type="update_job_priority"
                                    data-value="high"
                                    data-security=""
                                    href="#"><div class="d-flex align-items-center"><i class="bi-arrow-up-circle text-warning me-2"></i><span>High</span></div></a></li><li><a class="dropdown-item d-flex justify-content-between align-items-center py-2"
                                    recordid="102"
                                    data-type="update_job_priority"
                                    data-value="urgent"
                                    data-security=""
                                    href="#"><div class="d-flex align-items-center"><i class="bi-exclamation-triangle text-danger me-2"></i><span>Urgent</span></div></a></li></ul></div>
    </td>
    
    <td data-label="Actions" class="text-end pe-4">
        <div class="dropdown">
            <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-gear me-1"></i> Actions
            </button>
            <ul class="dropdown-menu shadow-sm">
                <li>
                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#openTakePaymentModal" recordid="102" data-security="">
                        <i class="bi bi-credit-card text-success me-2"></i>Take Payment
                    </a>
                </li>
                <li><a class="dropdown-item" href="#" target="_blank">
                    <i class="bi bi-printer text-secondary me-2"></i>Print Job Invoice</a>
                </li>
                <li>
                    <a class="dropdown-item" href="#" target="_blank">
                        <i class="bi bi-file-earmark-pdf text-danger me-2"></i>Download PDF
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="#" target="_blank">
                        <i class="bi bi-envelope text-info me-2"></i>Email Customer
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#wcrbduplicatejobfront" recordid="102" data-security="">
                        <i class="bi bi-files text-warning me-2"></i>Duplicate job
                    </a>
                </li>
                <li><a class="dropdown-item" href="#" target="_blank"><i class="bi bi-pencil-square text-primary me-2"></i>Edit</a></li>
            </ul>
        </div>
    </td>
</tr>
HTML;

            $mockJobRowsCard = <<<'HTML'
<div class="row g-3 p-3">
    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="card h-100 job-card border">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <strong class="text-primary">00101</strong>
                <span class="badge bg-warning">In Process</span>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-start mb-3">
                    <span class="device-icon me-3">
                        <i class="bi bi-laptop display-6 text-primary"></i>
                    </span>
                    <div>
                        <h6 class="card-title mb-1">Dell Inspiron 15</h6>
                        <p class="text-muted small mb-0">WC_ABC123</p>
                    </div>
                </div>
                <div class="job-meta">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Customer:</span>
                        <span class="fw-semibold text-truncate ms-2" style="max-width: 120px;">John Smith</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Priority:</span>
                        <span class="badge bg-danger">High</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total:</span>
                        <span class="fw-semibold">$120.00</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Due:</span>
                        <span class="fw-semibold">02/12/2026</span>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-top-0 pt-0">
                <div class="btn-group w-100">
                    <a href="#" class="btn btn-outline-primary btn-sm"><i class="bi bi-eye me-1"></i>View</a>
                    <a href="#" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
                    <a href="#" target="_blank" class="btn btn-outline-info btn-sm"><i class="bi bi-printer me-1"></i></a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="card h-100 job-card border">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <strong class="text-primary">00102</strong>
                <span class="badge bg-success">Completed</span>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-start mb-3">
                    <span class="device-icon me-3">
                        <i class="bi bi-phone display-6 text-primary"></i>
                    </span>
                    <div>
                        <h6 class="card-title mb-1">iPhone 13</h6>
                        <p class="text-muted small mb-0">WC_DEF456</p>
                    </div>
                </div>
                <div class="job-meta">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Customer:</span>
                        <span class="fw-semibold text-truncate ms-2" style="max-width: 120px;">Sarah Johnson</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Priority:</span>
                        <span class="badge bg-secondary">Normal</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total:</span>
                        <span class="fw-semibold">$80.00</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Due:</span>
                        <span class="fw-semibold">02/11/2026</span>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-top-0 pt-0">
                <div class="btn-group w-100">
                    <a href="#" class="btn btn-outline-primary btn-sm"><i class="bi bi-eye me-1"></i>View</a>
                    <a href="#" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
                    <a href="#" target="_blank" class="btn btn-outline-info btn-sm"><i class="bi bi-printer me-1"></i></a>
                </div>
            </div>
        </div>
    </div>
</div>
HTML;

            $mockPagination = <<<'HTML'
<div class="card-footer">
    <div class="d-flex justify-content-between align-items-center">
        <div class="text-muted">Showing 1 to 2 of 2 jobs</div>
        <nav><ul class="pagination mb-0">
            <li class="page-item disabled"><a class="page-link" href="#" tabindex="-1" aria-disabled="true"><i class="bi bi-chevron-left"></i></a></li>
            <li class="page-item active"><a class="page-link" href="#">1</a></li>
            <li class="page-item disabled"><a class="page-link" href="#"><i class="bi bi-chevron-right"></i></a></li>
        </ul></nav>
    </div>
</div>
HTML;

            $mockExportButtons = <<<'HTML'
<ul class="dropdown-menu">
    <li><a href="#" class="dropdown-item">
        <i class="bi bi-filetype-csv me-2"></i>CSV
    </a></li>
    <li><a href="#" class="dropdown-item">
        <i class="bi bi-filetype-pdf me-2"></i>PDF
    </a></li>
    <li><a href="#" class="dropdown-item">
        <i class="bi bi-filetype-xlsx me-2"></i>Excel
    </a></li>
</ul>
HTML;

            $mockJobStatusOptions = <<<'HTML'
<option value="new">New</option>
<option value="in_process">In Process</option>
<option value="completed">Completed</option>
HTML;

            $mockDeviceOptions = <<<'HTML'
<option value="">Devices ...</option>
<option value="1">Dell Inspiron 15</option>
<option value="2">iPhone 13</option>
HTML;

            $mockPaymentStatusOptions = <<<'HTML'
<option value="unpaid">Unpaid</option>
<option value="partial">Partial</option>
<option value="paid">Paid</option>
HTML;

            $mockPriorityOptions = <<<'HTML'
<select class="form-select" name="wc_job_priority" id="wc_job_priority">
    <option value="all">Priority (All)</option>
    <option value="normal">Normal</option>
    <option value="urgent">Urgent</option>
</select>
HTML;

            $mockDuplicateFrontBox = <<<'HTML'
<div id="wcrb-duplicate-job-front-box" class="d-none"></div>
HTML;

            $jobStatusTiles = [
                [
                    'status_slug' => 'in_process',
                    'status_name' => 'In Process',
                    'jobs_count' => 3,
                    'color' => 'bg-primary',
                    'url' => $baseDashboardUrl !== '#'
                        ? ($baseDashboardUrl . '?screen=' . $_page . '&job_status=in_process')
                        : '#',
                ],
                [
                    'status_slug' => 'completed',
                    'status_name' => 'Completed',
                    'jobs_count' => 5,
                    'color' => 'bg-success',
                    'url' => $baseDashboardUrl !== '#'
                        ? ($baseDashboardUrl . '?screen=' . $_page . '&job_status=completed')
                        : '#',
                ],
                [
                    'status_slug' => 'new',
                    'status_name' => 'New',
                    'jobs_count' => 2,
                    'color' => 'bg-warning',
                    'url' => $baseDashboardUrl !== '#'
                        ? ($baseDashboardUrl . '?screen=' . $_page . '&job_status=new')
                        : '#',
                ],
            ];

            return view('tenant.jobs', [
                'tenant' => $tenant,
                'user' => $user,
                'activeNav' => 'jobs',
                'pageTitle' => 'Jobs',
                'role' => is_string($user?->role) ? (string) $user->role : null,
                'current_view' => $current_view,
                '_page' => $_page,
                'view_label' => $view_label,
                'view_url' => $view_url,
                '_job_status' => $jobStatusTiles,
                'job_status_options_html' => $mockJobStatusOptions,
                'job_priority_options_html' => $mockPriorityOptions,
                'device_options_html' => $mockDeviceOptions,
                'payment_status_options_html' => $mockPaymentStatusOptions,
                'export_buttons_html' => $mockExportButtons,
                'license_state' => true,
                'use_store_select' => false,
                'clear_filters_url' => $baseDashboardUrl !== '#'
                    ? ($baseDashboardUrl . '?screen=' . $_page)
                    : '#',
                'duplicate_job_front_box_html' => $mockDuplicateFrontBox,
                'jobs_list' => [
                    'rows' => $current_view === 'card' ? $mockJobRowsCard : $mockJobRowsTable,
                    'pagination' => $mockPagination,
                ],
            ]);
        }

        if ($screen === 'calendar') {
            return view('tenant.calendar', [
                'tenant' => $tenant,
                'user' => $user,
                'activeNav' => 'dashboard',
                'pickup_date_label' => 'Pickup date',
                'delivery_date_label' => 'Delivery date',
                'nextservice_date_label' => 'Next service date',
                'enable_next_service' => true,
                'calendar_events_url' => $tenant?->slug
                    ? route('tenant.calendar.events', ['business' => $tenant->slug])
                    : '#',
            ]);
        }

        if ($screen === 'timelog') {
            $mockEligibleJobsWithDevicesDropdown = <<<'HTML'
<select class="form-select" id="wcrb_timelog_jobs_devices">
    <option value="">Select a job/device</option>
</select>
HTML;

            $mockNonceFieldHtml = <<<'HTML'
<input type="hidden" id="wcrb_timelog_nonce_field" name="wcrb_timelog_nonce_field" value="">
<input type="hidden" name="_wp_http_referer" value="">
HTML;

            $mockActivityTypesDropdownHtml = <<<'HTML'
<select class="form-select" id="activityType">
    <option value="">Select activity</option>
</select>
HTML;

            $mockActivityTypesDropdownManualHtml = <<<'HTML'
<select class="form-select" id="activityType_manual" name="activityType_manual">
    <option value="">Select activity</option>
</select>
HTML;

            return view('tenant.timelog', [
                'tenant' => $tenant,
                'user' => $user,
                'activeNav' => 'timelog',
                'pageTitle' => 'Time Log',
                'userRole' => is_string($user?->role) ? (string) $user->role : 'guest',
                'licenseState' => true,
                'technician_id' => $user?->id,
                'device_label' => 'device',
                'stats' => [
                    'today_hours' => 0,
                    'week_hours' => 0,
                    'billable_rate' => 0,
                    'month_earnings' => 0,
                    'month_earnings_formatted' => '$0.00',
                    'completed_jobs' => 0,
                    'avg_time_per_job' => 0,
                ],
                'eligible_jobs_with_devices_dropdown_html' => $mockEligibleJobsWithDevicesDropdown,
                'timelog_nonce_field_html' => $mockNonceFieldHtml,
                'timelog_activity_types_dropdown_html' => $mockActivityTypesDropdownHtml,
                'timelog_activity_types_dropdown_manual_html' => $mockActivityTypesDropdownManualHtml,
                'productivity_stats' => [
                    'avg_daily_hours' => 0,
                    'total_jobs_completed' => 0,
                    'efficiency_score' => 0,
                ],
                'activity_distribution' => [],
                'recent_time_logs_html' => '',
            ]);
        }

        return view('tenant.dashboard', [
            'tenant' => $tenant,
            'user' => $user,
            'activeNav' => 'dashboard',
        ]);
    }

    public function calendarEvents(Request $request): JsonResponse
    {
        $events = [
            [
                'title' => 'Job #72 - John Smith',
                'start' => now()->addDay()->setTime(10, 0)->toIso8601String(),
                'end' => now()->addDay()->setTime(11, 30)->toIso8601String(),
                'url' => '#',
                'classNames' => ['bg-primary', 'job-event'],
                'extendedProps' => [
                    'tooltip' => 'Case: WC_o2Za691770208822 | Customer: John Smith | Status: New | Date Field: pickup date',
                    'status' => 'New',
                    'type' => 'job',
                ],
            ],
            [
                'title' => 'Estimate #73 - smakina',
                'start' => now()->setTime(14, 0)->toIso8601String(),
                'end' => now()->setTime(15, 30)->toIso8601String(),
                'url' => '#',
                'classNames' => ['bg-warning', 'estimate-event'],
                'extendedProps' => [
                    'tooltip' => 'Case: WC_o2Za691770208822 | Customer: smakina | Status: Quote | Date Field: pickup date',
                    'status' => 'Quote',
                    'type' => 'estimate',
                ],
            ],
            [
                'title' => 'Job #74 - Michael Chen',
                'start' => now()->addDays(2)->setTime(11, 0)->toIso8601String(),
                'end' => now()->addDays(2)->setTime(12, 0)->toIso8601String(),
                'url' => '#',
                'classNames' => ['bg-info', 'job-event'],
                'extendedProps' => [
                    'tooltip' => 'Case: WC_o2Za691770208822 | Customer: Michael Chen | Status: In Process | Date Field: pickup date',
                    'status' => 'In Process',
                    'type' => 'job',
                ],
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $events,
        ]);
    }
}
