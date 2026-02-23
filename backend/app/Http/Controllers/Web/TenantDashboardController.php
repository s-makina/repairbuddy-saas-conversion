<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\TenantBootstrap\EnsureDefaultRepairBuddyStatuses;
use App\Support\BranchContext;
use App\Support\TenantContext;
use App\ViewModels\Tenant\SettingsScreenViewModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantDashboardController extends Controller
{
    public function show(Request $request)
    {
        $tenant = TenantContext::tenant();
        $tenantId = TenantContext::tenantId();
        $branchId = BranchContext::branchId();
        $user = $request->user();

        if (! $user) {
            return redirect()->route('web.login');
        }

        $screen = is_string($request->query('screen')) && $request->query('screen') !== ''
            ? (string) $request->query('screen')
            : 'dashboard';

        if ($screen === 'profile') {
            if ($tenant instanceof Tenant) {
                return redirect()->route('tenant.profile.edit', ['business' => $tenant->slug]);
            }

            abort(400, 'Tenant is missing.');
        }

        if ($screen === 'settings') {
            if ($tenant?->slug) {
                return redirect()->route('tenant.settings', ['business' => $tenant->slug]);
            }

            abort(400, 'Tenant is missing.');
        }

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

            $jobShowUrl101 = $tenant?->slug ? route('tenant.jobs.show', ['business' => $tenant->slug, 'jobId' => 101]) : '#';
            $jobShowUrl102 = $tenant?->slug ? route('tenant.jobs.show', ['business' => $tenant->slug, 'jobId' => 102]) : '#';

            $mockJobRowsTable = <<<'HTML'
<tr class="job_id_101 job_status_in_process">
    <td  class="ps-4" data-label="ID"><a href="__JOB_SHOW_URL_101__" target="_blank"><strong>00101</a></strong></th>
    <td data-label="Case Number/Tech"><a href="__JOB_SHOW_URL_101__" target="_blank">WC_ABC123</a><br><strong class="text-primary">Tech: Alex</strong></td>
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
                <li><a class="dropdown-item" href="__JOB_SHOW_URL_101__" target="_blank"><i class="bi bi-pencil-square text-primary me-2"></i>Edit</a></li>
            </ul>
        </div>
    </td>
</tr>
<tr class="job_id_102 job_status_completed">
    <td  class="ps-4" data-label="ID"><a href="__JOB_SHOW_URL_102__" target="_blank"><strong>00102</a></strong></th>
    <td data-label="Case Number/Tech"><a href="__JOB_SHOW_URL_102__" target="_blank">WC_DEF456</a><br><strong class="text-primary">Tech: Sam</strong></td>
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
                <li><a class="dropdown-item" href="__JOB_SHOW_URL_102__" target="_blank"><i class="bi bi-pencil-square text-primary me-2"></i>Edit</a></li>
            </ul>
        </div>
    </td>
</tr>
HTML;

            $mockJobRowsTable = str_replace(
                ['__JOB_SHOW_URL_101__', '__JOB_SHOW_URL_102__'],
                [$jobShowUrl101, $jobShowUrl102],
                $mockJobRowsTable
            );

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
                    <a href="__JOB_SHOW_URL_101__" class="btn btn-outline-primary btn-sm"><i class="bi bi-eye me-1"></i>View</a>
                    <a href="__JOB_SHOW_URL_101__" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
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
                    <a href="__JOB_SHOW_URL_102__" class="btn btn-outline-primary btn-sm"><i class="bi bi-eye me-1"></i>View</a>
                    <a href="__JOB_SHOW_URL_102__" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
                    <a href="#" target="_blank" class="btn btn-outline-info btn-sm"><i class="bi bi-printer me-1"></i></a>
                </div>
            </div>
        </div>
    </div>
</div>
HTML;

            $mockJobRowsCard = str_replace(
                ['__JOB_SHOW_URL_101__', '__JOB_SHOW_URL_102__'],
                [$jobShowUrl101, $jobShowUrl102],
                $mockJobRowsCard
            );

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

        if ($screen === 'estimates' || $screen === 'estimates_card') {
            // Redirect to standalone estimates page
            return redirect()->route('tenant.estimates.index', ['business' => $tenant->slug]);
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

        if ($screen === 'customer-devices') {
            $mockStatsHtml = <<<'HTML'
<div class="row g-3 mb-4">
    <div class="col">
        <div class="card stats-card bg-primary text-white">
            <div class="card-body text-center p-3">
                <h6 class="card-title text-white-50 mb-1">Devices</h6>
                <h4 class="mb-0">12</h4>
                <small class="text-white-50">Total</small>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card stats-card bg-success text-white">
            <div class="card-body text-center p-3">
                <h6 class="card-title text-white-50 mb-1">Active</h6>
                <h4 class="mb-0">9</h4>
                <small class="text-white-50">In use</small>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card stats-card bg-warning text-dark">
            <div class="card-body text-center p-3">
                <h6 class="card-title mb-1">Needs Attention</h6>
                <h4 class="mb-0">2</h4>
                <small class="text-muted">Flagged</small>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card stats-card bg-secondary text-white">
            <div class="card-body text-center p-3">
                <h6 class="card-title text-white-50 mb-1">Archived</h6>
                <h4 class="mb-0">1</h4>
                <small class="text-white-50">Old</small>
            </div>
        </div>
    </div>
</div>
HTML;

            $mockFiltersHtml = <<<'HTML'
<div class="card mb-4"><div class="card-body"></div></div>
HTML;

            $mockRowsHtml = <<<'HTML'
HTML;

            $mockPaginationHtml = <<<'HTML'
HTML;

            $mockAddDeviceFormHtml = <<<'HTML'
HTML;

            $isAdminUser = (bool) ($user?->is_admin ?? false);
            $role = is_string($user?->role) ? (string) $user->role : '';
            if ($role !== '' && $role !== 'customer' && $role !== 'guest') {
                $isAdminUser = true;
            }

            return view('tenant.customer_devices', [
                'tenant' => $tenant,
                'user' => $user,
                'activeNav' => 'customer-devices',
                'pageTitle' => 'Devices',
                'is_admin_user' => $isAdminUser,
                'wc_device_label' => 'Devices',
                'sing_device_label' => 'Device',
                'wc_device_id_imei_label' => 'ID/IMEI',
                'wc_pin_code_label' => 'Pin Code/Password',
                'devices_data' => [
                    'stats' => $mockStatsHtml,
                    'filters' => $mockFiltersHtml,
                    'rows' => $mockRowsHtml,
                    'pagination' => $mockPaginationHtml,
                ],
                'add_device_form_html' => $mockAddDeviceFormHtml,
            ]);
        }

        if ($screen === 'expenses') {
            $baseDashboardUrl = $tenant?->slug
                ? route('tenant.dashboard', ['business' => $tenant->slug])
                : '#';

            return view('tenant.expenses', [
                'tenant' => $tenant,
                'user' => $user,
                'activeNav' => 'expenses',
                'pageTitle' => 'Expenses',
                'userRole' => is_string($user?->role) ? (string) $user->role : 'guest',
                'licenseState' => true,
                'search' => is_string($request->query('search')) ? (string) $request->query('search') : '',
                'category_id' => $request->query('category_id') ?? '',
                'payment_status' => is_string($request->query('payment_status')) ? (string) $request->query('payment_status') : '',
                'start_date' => is_string($request->query('start_date')) ? (string) $request->query('start_date') : '',
                'end_date' => is_string($request->query('end_date')) ? (string) $request->query('end_date') : '',
                'page' => (int) ($request->query('expenses_page') ?? 1),
                'limit' => 20,
                'offset' => 0,
                'expenses' => [],
                'total_expenses' => 0,
                'total_pages' => 0,
                'stats' => [
                    'totals' => (object) [
                        'grand_total' => 0,
                        'total_count' => 0,
                        'total_amount' => 0,
                        'total_tax' => 0,
                    ],
                ],
                'categories' => [],
                'payment_methods' => [],
                'payment_statuses' => [],
                'expense_types' => [],
                'reset_url' => $baseDashboardUrl !== '#'
                    ? ($baseDashboardUrl . '?screen=expenses')
                    : '#',
                'page_url_prev' => $baseDashboardUrl !== '#'
                    ? ($baseDashboardUrl . '?screen=expenses')
                    : '#',
                'page_url_next' => $baseDashboardUrl !== '#'
                    ? ($baseDashboardUrl . '?screen=expenses')
                    : '#',
                'page_urls' => [],
            ]);
        }

        if ($screen === 'expense_categories') {
            $baseDashboardUrl = $tenant?->slug
                ? route('tenant.dashboard', ['business' => $tenant->slug])
                : '#';

            return view('tenant.expense_categories', [
                'tenant' => $tenant,
                'user' => $user,
                'activeNav' => 'expense_categories',
                'pageTitle' => 'Expense Categories',
                'userRole' => is_string($user?->role) ? (string) $user->role : 'guest',
                'licenseState' => true,
                'categories' => [
                    (object) [
                        'category_id' => 1,
                        'category_name' => 'Parts & Supplies',
                        'category_description' => 'Consumables, screws, adhesives, cables, and small parts.',
                        'color_code' => '#3498db',
                        'is_active' => 1,
                        'taxable' => 1,
                        'tax_rate' => 15,
                    ],
                    (object) [
                        'category_id' => 2,
                        'category_name' => 'Tools',
                        'category_description' => 'Equipment and tools purchased for repairs.',
                        'color_code' => '#2ecc71',
                        'is_active' => 1,
                        'taxable' => 0,
                        'tax_rate' => 0,
                    ],
                    (object) [
                        'category_id' => 3,
                        'category_name' => 'Utilities',
                        'category_description' => 'Electricity, water, internet, and phone.',
                        'color_code' => '#f39c12',
                        'is_active' => 1,
                        'taxable' => 1,
                        'tax_rate' => 5,
                    ],
                    (object) [
                        'category_id' => 4,
                        'category_name' => 'Marketing',
                        'category_description' => '',
                        'color_code' => '#9b59b6',
                        'is_active' => 0,
                        'taxable' => 0,
                        'tax_rate' => 0,
                    ],
                ],
                'nonce' => csrf_token(),
            ]);
        }

        if ($screen === 'reviews') {
            $role = is_string($user?->role) ? (string) $user->role : 'guest';
            $userRoles = [$role];
            if (($user?->is_admin ?? false) === true) {
                $userRoles[] = 'administrator';
            }
            if ($role !== 'customer' && $role !== 'guest') {
                $userRoles[] = 'store_manager';
                $userRoles[] = 'technician';
            }
            $userRoles = array_values(array_unique(array_filter($userRoles)));

            $isAdminUser = ! empty(array_intersect(['administrator', 'store_manager', 'technician'], $userRoles));
            $baseDashboardUrl = $tenant?->slug
                ? route('tenant.dashboard', ['business' => $tenant->slug])
                : '#';

            $mockStatsHtml = <<<'HTML'
<div class="row g-3 mb-4"><div class="col"><div class="card stats-card bg-primary text-white"><div class="card-body text-center p-3"><div class="mb-2"><i class="bi bi-star-fill fs-1 opacity-75"></i></div><h6 class="card-title mb-1 text-white">Total Reviews</h6><h3 class="mb-0 text-white">0</h3></div></div></div><div class="col"><div class="card stats-card bg-success text-white"><div class="card-body text-center p-3"><div class="mb-2"><i class="bi bi-graph-up fs-1 opacity-75"></i></div><h6 class="card-title mb-1 text-white">Avg. Rating</h6><h3 class="mb-0 text-white">0/5</h3></div></div></div><div class="col"><div class="card stats-card bg-warning text-white"><div class="card-body text-center p-3"><div class="mb-2"><i class="bi bi-star-fill fs-1 opacity-75"></i><i class="bi bi-star-fill fs-1 opacity-75"></i><i class="bi bi-star-fill fs-1 opacity-75"></i><i class="bi bi-star-fill fs-1 opacity-75"></i><i class="bi bi-star-fill fs-1 opacity-75"></i></div><h6 class="card-title mb-1 text-white">5 Stars</h6><h3 class="mb-0 text-white">0</h3></div></div></div><div class="col"><div class="card stats-card bg-danger text-white"><div class="card-body text-center p-3"><div class="mb-2"><i class="bi bi-star-fill fs-1 opacity-75"></i></div><h6 class="card-title mb-1 text-white">1 Star</h6><h3 class="mb-0 text-white">0</h3></div></div></div></div>
HTML;

            $mockFiltersHtml = '';
            if ($isAdminUser) {
                $resetUrl = $baseDashboardUrl !== '#'
                    ? ($baseDashboardUrl . '?screen=reviews')
                    : '#';

                $mockFiltersHtml = '<div class="card mb-4"><div class="card-body"><form method="get" action="" class="row g-3">'
                    . '<input type="hidden" name="screen" value="reviews" />'
                    . '<div class="col-md-4"><div class="input-group">'
                    . '<span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>'
                    . '<input type="text" class="form-control border-start-0" name="review_search" id="reviewSearch" value="" placeholder="Search...">'
                    . '</div></div>'
                    . '<div class="col-md-3"><select name="rating_filter" class="form-select">'
                    . '<option value="all">All Ratings</option>'
                    . '<option value="5" >★★★★★ (5 stars)</option>'
                    . '<option value="4" >★★★★ (4 stars)</option>'
                    . '<option value="3" >★★★ (3 stars)</option>'
                    . '<option value="2" >★★ (2 stars)</option>'
                    . '<option value="1" >★ (1 stars)</option>'
                    . '</select></div>'
                    . '<div class="col-md-2"><div class="d-flex gap-2">'
                    . '<a href="' . $resetUrl . '" class="btn btn-outline-secondary" id="clearReviewFilters"><i class="bi bi-arrow-clockwise"></i></a>'
                    . '<button type="submit" class="btn btn-primary" id="applyReviewFilters"><i class="bi bi-funnel"></i> Filter</button>'
                    . '</div></div>'
                    . '</form></div></div>';
            }

            $mockRowsHtml = '';
            if ($isAdminUser) {
                $mockRowsHtml .= '<tr><td colspan="9" class="text-center py-5">'
                    . '<i class="bi bi-star display-1 text-muted"></i>'
                    . '<h4 class="text-muted mt-3">No reviews found!</h4>'
                    . '</td></tr>';
            } else {
                $mockRowsHtml .= '<tr><td colspan="8" class="text-center py-5">'
                    . '<i class="bi bi-star display-1 text-muted"></i>'
                    . '<h4 class="text-muted mt-3">No reviews found!</h4>'
                    . '</td></tr>';
            }

            return view('tenant.reviews', [
                'tenant' => $tenant,
                'user' => $user,
                'activeNav' => 'reviews',
                'pageTitle' => 'Reviews',
                'userRole' => $role,
                'is_admin_user' => $isAdminUser,
                'sing_device_label' => 'Device',
                'reviews_data' => [
                    'stats' => $mockStatsHtml,
                    'filters' => $mockFiltersHtml,
                    'rows' => $mockRowsHtml,
                    'pagination' => '',
                ],
            ]);
        }

        if ($screen === 'profile') {
            $dateTime = '';
            if ($user?->created_at) {
                $dateTime = $user->created_at->format('F j, Y');
            }

            $userRoleLabel = 'Customer';
            if (is_string($user?->role) && $user->role !== '') {
                $userRoleLabel = ucfirst($user->role);
            }

            $countryCode = is_string($user?->country) ? (string) $user->country : '';
            $countries = [];
            if (class_exists(\Symfony\Component\Intl\Countries::class)) {
                $countries = \Symfony\Component\Intl\Countries::getNames('en');
            }

            if (empty($countries) && class_exists(\ResourceBundle::class)) {
                $bundle = \ResourceBundle::create('en', 'ICUDATA-region');
                if ($bundle) {
                    foreach ($bundle as $code => $name) {
                        if (is_string($code) && preg_match('/^[A-Z]{2}$/', $code) && is_string($name) && $name !== '') {
                            $countries[$code] = $name;
                        }
                    }
                }
            }

            if (empty($countries)) {
                $countries = [
                    'US' => 'United States',
                    'GB' => 'United Kingdom',
                    'AL' => 'Albania',
                    'DZ' => 'Algeria',
                    'AS' => 'American Samoa',
                    'AD' => 'Andorra',
                    'AO' => 'Angola',
                    'AI' => 'Anguilla',
                    'AQ' => 'Antarctica',
                    'AG' => 'Antigua and Barbuda',
                    'AR' => 'Argentina',
                    'AM' => 'Armenia',
                    'AW' => 'Aruba',
                    'AU' => 'Australia',
                    'AT' => 'Austria',
                    'AZ' => 'Azerbaijan',
                    'BS' => 'Bahamas',
                    'BH' => 'Bahrain',
                    'BD' => 'Bangladesh',
                    'BB' => 'Barbados',
                    'BY' => 'Belarus',
                    'BE' => 'Belgium',
                    'BZ' => 'Belize',
                    'BJ' => 'Benin',
                    'BM' => 'Bermuda',
                    'BT' => 'Bhutan',
                    'BO' => 'Bolivia',
                    'BQ' => 'Bonaire, Sint Eustatius and Saba',
                    'BA' => 'Bosnia and Herzegovina',
                    'BW' => 'Botswana',
                    'BV' => 'Bouvet Island',
                    'BR' => 'Brazil',
                    'IO' => 'British Indian Ocean Territory',
                    'BN' => 'Brunei Darussalam',
                    'BG' => 'Bulgaria',
                    'BF' => 'Burkina Faso',
                    'BI' => 'Burundi',
                    'KH' => 'Cambodia',
                    'CM' => 'Cameroon',
                    'CA' => 'Canada',
                    'CV' => 'Cape Verde',
                    'KY' => 'Cayman Islands',
                    'CF' => 'Central African Republic',
                    'TD' => 'Chad',
                    'CL' => 'Chile',
                    'CN' => 'China',
                    'CX' => 'Christmas Island',
                    'CC' => 'Cocos (Keeling) Islands',
                    'CO' => 'Colombia',
                    'KM' => 'Comoros',
                    'CG' => 'Congo',
                    'CD' => 'Congo, Democratic Republic of the Congo',
                    'CK' => 'Cook Islands',
                    'CR' => 'Costa Rica',
                    'CI' => "Cote D'Ivoire",
                    'HR' => 'Croatia',
                    'CU' => 'Cuba',
                    'CW' => 'Curacao',
                    'CY' => 'Cyprus',
                    'CZ' => 'Czech Republic',
                    'DK' => 'Denmark',
                    'DJ' => 'Djibouti',
                    'DM' => 'Dominica',
                    'DO' => 'Dominican Republic',
                    'EC' => 'Ecuador',
                    'EG' => 'Egypt',
                    'SV' => 'El Salvador',
                    'GQ' => 'Equatorial Guinea',
                    'ER' => 'Eritrea',
                    'EE' => 'Estonia',
                    'ET' => 'Ethiopia',
                    'FK' => 'Falkland Islands (Malvinas)',
                    'FO' => 'Faroe Islands',
                    'FJ' => 'Fiji',
                    'FI' => 'Finland',
                    'FR' => 'France',
                    'GF' => 'French Guiana',
                    'PF' => 'French Polynesia',
                    'TF' => 'French Southern Territories',
                    'GA' => 'Gabon',
                    'GM' => 'Gambia',
                    'GE' => 'Georgia',
                    'DE' => 'Germany',
                    'GH' => 'Ghana',
                    'GI' => 'Gibraltar',
                    'GR' => 'Greece',
                    'GL' => 'Greenland',
                    'GD' => 'Grenada',
                    'GP' => 'Guadeloupe',
                    'GU' => 'Guam',
                    'GT' => 'Guatemala',
                    'GG' => 'Guernsey',
                    'GN' => 'Guinea',
                    'GW' => 'Guinea-Bissau',
                    'GY' => 'Guyana',
                    'HT' => 'Haiti',
                    'HM' => 'Heard Island and Mcdonald Islands',
                    'VA' => 'Holy See (Vatican City State)',
                    'HN' => 'Honduras',
                    'HK' => 'Hong Kong',
                    'HU' => 'Hungary',
                    'IS' => 'Iceland',
                    'IN' => 'India',
                    'ID' => 'Indonesia',
                    'IR' => 'Iran, Islamic Republic of',
                    'IQ' => 'Iraq',
                    'IE' => 'Ireland',
                    'IM' => 'Isle of Man',
                    'IL' => 'Israel',
                    'IT' => 'Italy',
                    'JM' => 'Jamaica',
                    'JP' => 'Japan',
                    'JE' => 'Jersey',
                    'JO' => 'Jordan',
                    'KZ' => 'Kazakhstan',
                    'KE' => 'Kenya',
                    'KI' => 'Kiribati',
                    'KP' => "Korea, Democratic People's Republic of",
                    'KR' => 'Korea, Republic of',
                    'XK' => 'Kosovo',
                    'KW' => 'Kuwait',
                    'KG' => 'Kyrgyzstan',
                    'LA' => "Lao People's Democratic Republic",
                    'LV' => 'Latvia',
                    'LB' => 'Lebanon',
                    'LS' => 'Lesotho',
                    'LR' => 'Liberia',
                    'LY' => 'Libyan Arab Jamahiriya',
                    'LI' => 'Liechtenstein',
                    'LT' => 'Lithuania',
                    'LU' => 'Luxembourg',
                    'MO' => 'Macao',
                    'MK' => 'Macedonia, the Former Yugoslav Republic of',
                    'MG' => 'Madagascar',
                    'MW' => 'Malawi',
                    'MY' => 'Malaysia',
                    'MV' => 'Maldives',
                    'ML' => 'Mali',
                    'MT' => 'Malta',
                    'MH' => 'Marshall Islands',
                    'MQ' => 'Martinique',
                    'MR' => 'Mauritania',
                    'MU' => 'Mauritius',
                    'YT' => 'Mayotte',
                    'MX' => 'Mexico',
                    'FM' => 'Micronesia, Federated States of',
                    'MD' => 'Moldova, Republic of',
                    'MC' => 'Monaco',
                    'MN' => 'Mongolia',
                    'ME' => 'Montenegro',
                    'MS' => 'Montserrat',
                    'MA' => 'Morocco',
                    'MZ' => 'Mozambique',
                    'MM' => 'Myanmar',
                    'NA' => 'Namibia',
                    'NR' => 'Nauru',
                    'NP' => 'Nepal',
                    'NL' => 'Netherlands',
                    'AN' => 'Netherlands Antilles',
                    'NC' => 'New Caledonia',
                    'NZ' => 'New Zealand',
                    'NI' => 'Nicaragua',
                    'NE' => 'Niger',
                    'NG' => 'Nigeria',
                    'NU' => 'Niue',
                    'NF' => 'Norfolk Island',
                    'MP' => 'Northern Mariana Islands',
                    'NO' => 'Norway',
                    'OM' => 'Oman',
                    'PK' => 'Pakistan',
                    'PW' => 'Palau',
                    'PS' => 'Palestinian Territory, Occupied',
                    'PA' => 'Panama',
                    'PG' => 'Papua New Guinea',
                    'PY' => 'Paraguay',
                    'PE' => 'Peru',
                    'PH' => 'Philippines',
                    'PN' => 'Pitcairn',
                    'PL' => 'Poland',
                    'PT' => 'Portugal',
                    'PR' => 'Puerto Rico',
                    'QA' => 'Qatar',
                    'RE' => 'Reunion',
                    'RO' => 'Romania',
                    'RU' => 'Russian Federation',
                    'RW' => 'Rwanda',
                    'BL' => 'Saint Barthelemy',
                    'SH' => 'Saint Helena',
                    'KN' => 'Saint Kitts and Nevis',
                    'LC' => 'Saint Lucia',
                    'MF' => 'Saint Martin',
                    'PM' => 'Saint Pierre and Miquelon',
                    'VC' => 'Saint Vincent and the Grenadines',
                    'WS' => 'Samoa',
                    'SM' => 'San Marino',
                    'ST' => 'Sao Tome and Principe',
                    'SA' => 'Saudi Arabia',
                    'SN' => 'Senegal',
                    'RS' => 'Serbia',
                    'SC' => 'Seychelles',
                    'SL' => 'Sierra Leone',
                    'SG' => 'Singapore',
                    'SX' => 'Sint Maarten',
                    'SK' => 'Slovakia',
                    'SI' => 'Slovenia',
                    'SB' => 'Solomon Islands',
                    'SO' => 'Somalia',
                    'ZA' => 'South Africa',
                    'GS' => 'South Georgia and the South Sandwich Islands',
                    'SS' => 'South Sudan',
                    'ES' => 'Spain',
                    'LK' => 'Sri Lanka',
                    'SD' => 'Sudan',
                    'SR' => 'Suriname',
                    'SJ' => 'Svalbard and Jan Mayen',
                    'SZ' => 'Swaziland',
                    'SE' => 'Sweden',
                    'CH' => 'Switzerland',
                    'SY' => 'Syrian Arab Republic',
                    'TW' => 'Taiwan, Province of China',
                    'TJ' => 'Tajikistan',
                    'TZ' => 'Tanzania, United Republic of',
                    'TH' => 'Thailand',
                    'TL' => 'Timor-Leste',
                    'TG' => 'Togo',
                    'TK' => 'Tokelau',
                    'TO' => 'Tonga',
                    'TT' => 'Trinidad and Tobago',
                    'TN' => 'Tunisia',
                    'TR' => 'Turkey',
                    'TM' => 'Turkmenistan',
                    'TC' => 'Turks and Caicos Islands',
                    'TV' => 'Tuvalu',
                    'UG' => 'Uganda',
                    'UA' => 'Ukraine',
                    'AE' => 'United Arab Emirates',
                    'GB' => 'United Kingdom',
                    'US' => 'United States',
                    'UM' => 'United States Minor Outlying Islands',
                    'UY' => 'Uruguay',
                    'UZ' => 'Uzbekistan',
                    'VU' => 'Vanuatu',
                    'VE' => 'Venezuela',
                    'VN' => 'Viet Nam',
                    'VG' => 'Virgin Islands, British',
                    'VI' => 'Virgin Islands, U.S.',
                    'WF' => 'Wallis and Futuna',
                    'EH' => 'Western Sahara',
                    'YE' => 'Yemen',
                    'ZM' => 'Zambia',
                    'ZW' => 'Zimbabwe',
                ];
            }
            ksort($countries);
            $optionsGenerated = '';
            foreach ($countries as $code => $name) {
                $selected = ($countryCode !== '' && strtoupper($code) === strtoupper($countryCode)) ? ' selected' : '';
                $optionsGenerated .= '<option value="' . e($code) . '"' . $selected . '>' . e($name) . '</option>';
            }

            return view('tenant.profile', [
                'tenant' => $tenant,
                'user' => $user,
                'activeNav' => 'profile',
                'pageTitle' => 'Profile',
                'first_name' => is_string($user?->first_name) ? (string) $user->first_name : '',
                'last_name' => is_string($user?->last_name) ? (string) $user->last_name : '',
                'user_email' => is_string($user?->email) ? (string) $user->email : '',
                'phone_number' => is_string($user?->phone_number) ? (string) $user->phone_number : '',
                'company' => is_string($user?->company) ? (string) $user->company : '',
                'billing_tax' => is_string($user?->billing_tax) ? (string) $user->billing_tax : '',
                'address' => is_string($user?->address) ? (string) $user->address : '',
                'city' => is_string($user?->city) ? (string) $user->city : '',
                'zip_code' => is_string($user?->zip_code) ? (string) $user->zip_code : '',
                'state' => is_string($user?->state) ? (string) $user->state : '',
                'country' => $countryCode,
                'optionsGenerated' => $optionsGenerated,
                'current_avatar' => '',
                '_jobs_count' => 0,
                '_estimates_count' => 0,
                'lifetime_value_formatted' => '$0.00',
                'dateTime' => $dateTime,
                'userRole' => $userRoleLabel,
                'wcrb_updateuser_nonce_post' => '',
                'wcrb_updatepassword_nonce_post' => '',
                'wcrb_profile_photo_nonce' => '',
                'wp_http_referer' => '',
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
