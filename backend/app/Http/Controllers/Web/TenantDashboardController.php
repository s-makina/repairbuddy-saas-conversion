<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\RepairBuddyAppointment;
use App\Models\RepairBuddyCustomerDevice;
use App\Models\RepairBuddyEstimate;
use App\Models\RepairBuddyEvent;
use App\Models\RepairBuddyJob;
use App\Models\RepairBuddyPayment;
use App\Models\Role;
use App\Models\Status;
use App\Models\Tenant;
use App\Models\User;
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
            $branch = BranchContext::branch();

            if (! $tenant || ! $branch instanceof Branch) {
                abort(400, 'Tenant or branch context is missing.');
            }

            /* ── Build jobs query ── */
            $jobQuery = RepairBuddyJob::query()
                ->with(['customer', 'technicians', 'jobDevices'])
                ->where('tenant_id', (int) $tenant->id)
                ->where('branch_id', (int) $branch->id)
                ->orderByDesc('id');

            /* search */
            $searchInput = is_string($request->query('searchinput'))
                ? trim((string) $request->query('searchinput')) : '';
            if ($searchInput !== '') {
                $jobQuery->where(function ($q) use ($searchInput) {
                    $q->where('case_number', 'like', "%{$searchInput}%")
                      ->orWhere('title', 'like', "%{$searchInput}%")
                      ->orWhere('case_detail', 'like', "%{$searchInput}%")
                      ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$searchInput}%"));
                });
            }

            /* status filter */
            $statusFilter = is_string($request->query('job_status'))
                ? trim((string) $request->query('job_status')) : '';
            if ($statusFilter !== '' && $statusFilter !== 'all') {
                $jobQuery->where('status_slug', $statusFilter);
            }

            /* payment status filter */
            $paymentFilter = is_string($request->query('wc_payment_status'))
                ? trim((string) $request->query('wc_payment_status')) : '';
            if ($paymentFilter !== '' && $paymentFilter !== 'all') {
                $jobQuery->where('payment_status_slug', $paymentFilter);
            }

            /* priority filter */
            $priorityFilter = is_string($request->query('wc_job_priority'))
                ? trim((string) $request->query('wc_job_priority')) : '';
            if ($priorityFilter !== '' && $priorityFilter !== 'all') {
                $jobQuery->where('priority', $priorityFilter);
            }

            /* device filter */
            $deviceFilter = $request->query('device_post_id');
            if (is_numeric($deviceFilter) && (int) $deviceFilter > 0) {
                $jobQuery->whereHas('jobDevices', fn ($dq) => $dq->where('customer_device_id', (int) $deviceFilter));
            }

            $jobs = $jobQuery->limit(500)->get();

            /* ── Format rows for <x-ui.datatable> ── */
            $priorityBadgeMap = [
                'high'   => 'wcrb-pill--high',
                'urgent' => 'wcrb-pill--danger',
                'normal' => 'wcrb-pill--low',
            ];

            $statusBadgeMap = [
                'new'          => 'wcrb-pill--pending',
                'in_process'   => 'wcrb-pill--progress',
                'inprocess'    => 'wcrb-pill--progress',
                'completed'    => 'wcrb-pill--active',
                'delivered'    => 'wcrb-pill--active',
                'waiting_parts'=> 'wcrb-pill--warning',
            ];

            $paymentBadgeMap = [
                'unpaid'  => 'wcrb-pill--danger',
                'partial' => 'wcrb-pill--warning',
                'paid'    => 'wcrb-pill--active',
            ];

            $jobRows = [];
            foreach ($jobs as $job) {
                $num = is_numeric($job->job_number) ? (int) $job->job_number : (int) $job->id;
                $jobId = str_pad((string) $num, 5, '0', STR_PAD_LEFT);
                $caseNumber = is_string($job->case_number) ? (string) $job->case_number : '';

                $customerName = $job->customer?->name ?? '—';
                $tech = $job->technicians->first();
                $techName = $tech?->name ?? '—';

                $devices = $job->jobDevices
                    ->map(fn ($d) => is_string($d->label_snapshot ?? null) ? trim((string) $d->label_snapshot) : '')
                    ->filter()
                    ->take(3)
                    ->implode(', ') ?: '—';

                $statusSlug = is_string($job->status_slug) ? trim((string) $job->status_slug) : '';
                $statusLabel = $statusSlug !== '' ? ucwords(str_replace(['_', '-'], ' ', $statusSlug)) : '—';

                $priorityLabel = is_string($job->priority) ? ucfirst((string) $job->priority) : '—';
                $paymentLabel = is_string($job->payment_status_slug) ? ucfirst((string) $job->payment_status_slug) : '—';

                $pickup = $job->pickup_date?->format('M d, Y') ?? '';
                $delivery = $job->delivery_date?->format('M d, Y') ?? '';

                $showUrl = route('tenant.jobs.show', ['business' => $tenant->slug, 'jobId' => $job->id]);

                $actions = '<div class="d-flex justify-content-end align-items-center gap-1 flex-nowrap">'
                    . '<a href="' . e($showUrl) . '" class="btn btn-sm btn-primary" style="padding: .25rem .65rem; font-size: .78rem;" title="' . e(__('View')) . '"><i class="bi bi-eye me-1"></i>' . e(__('View')) . '</a>'
                    . '<div class="dropdown">'
                    . '<button class="btn btn-sm btn-light border" data-bs-toggle="dropdown" aria-expanded="false" title="' . e(__('More actions')) . '" style="padding: .25rem .45rem;"><i class="bi bi-three-dots" style="font-size:.75rem;"></i></button>'
                    . '<ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:.82rem; min-width: 160px;">'
                    . '<li><button class="dropdown-item py-2" type="button" onclick="openDocPreview(\'job\',' . (int) $job->id . ')"><i class="bi bi-printer me-2 text-muted"></i>' . e(__('Print / Preview')) . '</button></li>'
                    . '<li><a class="dropdown-item py-2" href="' . e(route('tenant.jobs.edit', ['business' => $tenant->slug, 'jobId' => $job->id])) . '"><i class="bi bi-pencil me-2 text-muted"></i>' . e(__('Edit Job')) . '</a></li>'
                    . '</ul>'
                    . '</div>'
                    . '</div>';

                $jobRows[] = [
                    'job_id'     => $jobId,
                    'case_number'=> $caseNumber,
                    'customer'   => $customerName,
                    'device'     => $devices,
                    'technician' => $techName,
                    'status'     => $statusLabel,
                    '_badgeClass_status' => $statusBadgeMap[strtolower($statusSlug)] ?? 'wcrb-pill--inactive',
                    'priority'   => $priorityLabel,
                    '_badgeClass_priority' => $priorityBadgeMap[strtolower($job->priority ?? '')] ?? 'wcrb-pill--inactive',
                    'payment'    => $paymentLabel,
                    '_badgeClass_payment' => $paymentBadgeMap[strtolower($job->payment_status_slug ?? '')] ?? 'wcrb-pill--inactive',
                    'pickup_date'  => $pickup,
                    'delivery_date'=> $delivery,
                    'actions'    => $actions,
                ];
            }

            /* ── Status tiles (real counts) ── */
            $statusCounts = RepairBuddyJob::query()
                ->where('tenant_id', (int) $tenant->id)
                ->where('branch_id', (int) $branch->id)
                ->selectRaw('status_slug, COUNT(*) as cnt')
                ->groupBy('status_slug')
                ->pluck('cnt', 'status_slug')
                ->toArray();

            $statusColors = [
                'new'           => 'warning',
                'in_process'    => 'primary',
                'inprocess'     => 'primary',
                'completed'     => 'success',
                'delivered'     => 'info',
                'waiting_parts' => 'secondary',
            ];

            $baseDashboardUrl = route('tenant.dashboard', ['business' => $tenant->slug]);

            $jobStatusTiles = [];
            foreach ($statusCounts as $slug => $count) {
                if ((int) $count <= 0) {
                    continue;
                }
                $jobStatusTiles[] = [
                    'status_slug' => $slug,
                    'status_name' => ucwords(str_replace(['_', '-'], ' ', (string) $slug)),
                    'jobs_count'  => $count,
                    'color'       => $statusColors[$slug] ?? 'bg-secondary',
                    'url'         => $baseDashboardUrl . '?screen=jobs&job_status=' . urlencode((string) $slug),
                ];
            }

            /* ── Look-ups for filter dropdowns ── */
            $jobStatuses = Status::query()
                ->where('status_type', 'Job')
                ->where('is_active', true)
                ->orderBy('id')
                ->get(['id', 'code', 'label']);

            $paymentStatuses = Status::query()
                ->where('status_type', 'Payment')
                ->where('is_active', true)
                ->orderBy('id')
                ->get(['id', 'code', 'label']);

            $customers = User::query()
                ->where('tenant_id', (int) $tenant->id)
                ->where('role', 'customer')
                ->orderBy('name')
                ->limit(500)
                ->get(['id', 'name']);

            $technicianRoleId = Role::query()
                ->where('tenant_id', (int) $tenant->id)
                ->where('name', 'Technician')
                ->value('id');
            $technicianRoleId = is_numeric($technicianRoleId) ? (int) $technicianRoleId : null;

            $technicians = User::query()
                ->where('tenant_id', (int) $tenant->id)
                ->where('is_admin', false)
                ->where('status', 'active')
                ->where(function ($q) use ($technicianRoleId) {
                    if ($technicianRoleId) {
                        $q->where('role_id', $technicianRoleId);
                    }
                    $q->orWhereHas('roles', fn ($rq) => $rq->where('name', 'Technician'))
                      ->orWhere('role', 'technician');
                })
                ->orderBy('name')
                ->limit(500)
                ->get(['id', 'name']);

            $devices = RepairBuddyCustomerDevice::query()
                ->where('tenant_id', (int) $tenant->id)
                ->where('branch_id', (int) $branch->id)
                ->orderByDesc('id')
                ->limit(500)
                ->get(['id', 'label', 'serial']);

            return view('tenant.jobs', [
                'tenant'          => $tenant,
                'user'            => $user,
                'activeNav'       => 'jobs',
                'pageTitle'       => 'Jobs',
                'role'            => is_string($user?->role) ? (string) $user->role : null,
                'jobRows'         => $jobRows,
                '_job_status'     => $jobStatusTiles,
                'jobStatuses'     => $jobStatuses,
                'paymentStatuses' => $paymentStatuses,
                'customers'       => $customers,
                'technicians'     => $technicians,
                'devices'         => $devices,
                'searchInput'     => $searchInput,
                'statusFilter'    => $statusFilter,
                'paymentFilter'   => $paymentFilter,
                'priorityFilter'  => $priorityFilter,
                'deviceFilter'    => $deviceFilter,
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
            if ($tenant?->slug) {
                return redirect()->route('tenant.time_log.dashboard', ['business' => $tenant->slug]);
            }
            abort(400, 'Tenant is missing.');
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

        /* ── Real Stats Calculation ── */
        $activeJobsCount = RepairBuddyJob::query()
            ->where('tenant_id', (int) $tenantId)
            ->where('branch_id', (int) $branchId)
            ->whereNotIn('status_slug', ['completed', 'delivered', 'cancelled'])
            ->count();

        $completedJobsCount = RepairBuddyJob::query()
            ->where('tenant_id', (int) $tenantId)
            ->where('branch_id', (int) $branchId)
            ->whereIn('status_slug', ['completed', 'delivered'])
            ->count();

        $pendingEstimatesCount = RepairBuddyEstimate::query()
            ->where('tenant_id', (int) $tenantId)
            ->where('branch_id', (int) $branchId)
            ->whereIn('status', ['pending', 'sent'])
            ->whereNull('converted_job_id')
            ->count();

        $totalRevenueCents = RepairBuddyPayment::query()
            ->where('tenant_id', (int) $tenantId)
            ->where('branch_id', (int) $branchId)
            ->where('payment_status', 'paid')
            ->sum('amount_cents');

        $currency = is_string($tenant?->currency) && $tenant->currency !== '' ? (string) $tenant->currency : 'USD';
        $revenueFormatted = number_format($totalRevenueCents / 100, 2) . ' ' . strtoupper($currency);

        $overviewStats = [
            'active_jobs_count' => $activeJobsCount,
            'completed_jobs_count' => $completedJobsCount,
            'pending_estimates_count' => $pendingEstimatesCount,
            'revenue_formatted' => $revenueFormatted,
        ];

        /* ── Latest Jobs ── */
        $latestJobsRaw = RepairBuddyJob::query()
            ->where('tenant_id', (int) $tenantId)
            ->where('branch_id', (int) $branchId)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $latestJobs = [];
        foreach ($latestJobsRaw as $j) {
            $statusClass = 'bg-secondary';
            if (in_array($j->status_slug, ['completed', 'delivered'])) $statusClass = 'bg-success';
            elseif (in_array($j->status_slug, ['new', 'pending'])) $statusClass = 'bg-warning text-dark';
            elseif ($j->status_slug === 'in_process' || $j->status_slug === 'inprocess') $statusClass = 'bg-primary';

            $latestJobs[] = [
                'title' => (string) $j->title,
                'display_title' => (string) $j->title,
                'edit_url' => route('tenant.jobs.show', ['business' => $tenant->slug, 'jobId' => $j->id]),
                'formatted_total' => '',
                'job_number' => (string) $j->job_number,
                'date' => $j->created_at->format('M d, Y'),
                'status_display' => ucwords(str_replace(['_', '-'], ' ', (string) $j->status_slug)),
                'status_badge_class' => $statusClass,
            ];
        }

        /* ── Latest Estimates ── */
        $latestEstimatesRaw = RepairBuddyEstimate::query()
            ->where('tenant_id', (int) $tenantId)
            ->where('branch_id', (int) $branchId)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $latestEstimates = [];
        foreach ($latestEstimatesRaw as $e) {
            $statusClass = 'bg-info';
            if ($e->status === 'approved') $statusClass = 'bg-success';
            elseif ($e->status === 'rejected') $statusClass = 'bg-danger';

            $latestEstimates[] = [
                'title' => (string) $e->title,
                'display_title' => (string) $e->title,
                'edit_url' => route('tenant.estimates.show', ['business' => $tenant->slug, 'estimateId' => $e->id]),
                'formatted_total' => '',
                'date' => $e->created_at->format('M d, Y'),
                'status_display' => ucfirst((string) $e->status),
                'status_badge_class' => $statusClass,
            ];
        }

        /* ── Recent Activity ── */
        $activitiesRaw = RepairBuddyEvent::query()
            ->with(['actor'])
            ->where('tenant_id', (int) $tenantId)
            ->where('branch_id', (int) $branchId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $activities = [];
        foreach ($activitiesRaw as $evt) {
            $payload = is_array($evt->payload_json) ? $evt->payload_json : [];

            // Generate edit URL based on entity type
            $editUrl = '#';
            if ($evt->entity_type === 'job' && $evt->entity_id) {
                $editUrl = route('tenant.jobs.show', ['business' => $tenant->slug, 'jobId' => $evt->entity_id]);
            } elseif ($evt->entity_type === 'estimate' && $evt->entity_id) {
                $editUrl = route('tenant.estimates.show', ['business' => $tenant->slug, 'estimateId' => $evt->entity_id]);
            }

            // Determine icon and color based on event type
            $activityColor = 'info';
            $activityIcon = 'bi-info-circle';
            if (str_contains($evt->event_type, 'created') || str_contains($evt->event_type, 'created')) {
                $activityColor = 'success';
                $activityIcon = 'bi-plus-circle';
            } elseif (str_contains($evt->event_type, 'updated') || str_contains($evt->event_type, 'status')) {
                $activityColor = 'warning';
                $activityIcon = 'bi-pencil';
            } elseif (str_contains($evt->event_type, 'deleted') || str_contains($evt->event_type, 'cancelled')) {
                $activityColor = 'danger';
                $activityIcon = 'bi-trash';
            } elseif (str_contains($evt->event_type, 'completed') || str_contains($evt->event_type, 'delivered')) {
                $activityColor = 'success';
                $activityIcon = 'bi-check-circle';
            }

            $activities[] = [
                'activity_color' => $activityColor,
                'activity_icon' => $activityIcon,
                'name' => (string) ($payload['title'] ?? $evt->entity_type . ' ' . $evt->event_type),
                'change_detail_html' => (string) ($payload['message'] ?? ''),
                'device_display' => (string) ($payload['device'] ?? ''),
                'formatted_job_number' => $evt->entity_type === 'job' ? (string) $evt->entity_id : ($evt->entity_id ?? ''),
                'time_ago' => $evt->created_at->diffForHumans(),
                'user_name' => $evt->actor?->name ?? 'System',
                'badge_text' => ucwords(str_replace(['_', '-'], ' ', (string) $evt->event_type)),
                'edit_url' => $editUrl,
            ];
        }

        /* ── Priority Jobs ── */
        $priorityJobsRaw = RepairBuddyJob::query()
            ->where('tenant_id', (int) $tenantId)
            ->where('branch_id', (int) $branchId)
            ->whereIn('priority', ['high', 'urgent'])
            ->whereNotIn('status_slug', ['completed', 'delivered', 'cancelled'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $priorityJobs = [];
        foreach ($priorityJobsRaw as $pj) {
            $priorityJobs[] = [
                'device_name' => (string) $pj->title,
                'device_display' => (string) $pj->title,
                'edit_url' => route('tenant.jobs.show', ['business' => $tenant->slug, 'jobId' => $pj->id]),
                'job_number' => (string) $pj->job_number,
                'priority' => (string) $pj->priority,
                'priority_label' => ucfirst((string) $pj->priority),
                'priority_badge_class' => $pj->priority === 'urgent' ? 'bg-danger' : 'bg-warning',
                'status_label' => ucwords(str_replace(['_', '-'], ' ', (string) $pj->status_slug)),
                'status_badge_class' => 'bg-secondary',
            ];
        }

        /* ── Upcoming Visits (jobs with pickup_date >= today) ── */
        $upcomingVisitsRaw = RepairBuddyJob::query()
            ->with(['customer', 'jobDevices'])
            ->where('tenant_id', (int) $tenantId)
            ->where('branch_id', (int) $branchId)
            ->where('pickup_date', '>=', today())
            ->whereNotIn('status_slug', ['completed', 'delivered', 'cancelled'])
            ->orderBy('pickup_date')
            ->limit(5)
            ->get();

        $upcomingVisits = [];
        foreach ($upcomingVisitsRaw as $uv) {
            $deviceDisplay = $uv->jobDevices->first()?->label_snapshot ?? $uv->title;
            $upcomingVisits[] = [
                'id' => $uv->id,
                'title' => (string) $uv->title,
                'device_display' => (string) $deviceDisplay,
                'customer_name' => $uv->customer?->name ?? '—',
                'customer_phone' => $uv->customer?->phone_number ?? '',
                'customer_address' => trim(sprintf('%s %s %s',
                    $uv->customer?->address ?? '',
                    $uv->customer?->city ?? '',
                    $uv->customer?->zip_code ?? ''
                )),
                'pickup_date_formatted' => $uv->pickup_date?->format('M d, Y') ?? '',
                'pickup_date_relative' => $uv->pickup_date?->isToday() ? 'Today'
                    : ($uv->pickup_date?->isTomorrow() ? 'Tomorrow' : $uv->pickup_date?->format('M d')),
                'edit_url' => route('tenant.jobs.show', ['business' => $tenant->slug, 'jobId' => $uv->id]),
                'job_number' => (string) $uv->job_number,
            ];
        }

        /* ── Chart Data ── */
        // Revenue by day (last 7 days)
        $revenueByDay = RepairBuddyPayment::query()
            ->where('tenant_id', (int) $tenantId)
            ->where('branch_id', (int) $branchId)
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subDays(7)->startOfDay())
            ->selectRaw('DATE(created_at) as date, SUM(amount_cents) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $chartLabels = [];
        $chartRevenue = [];
        $chartJobsCompleted = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartLabels[] = now()->subDays($i)->format('D');
            $dayRevenue = $revenueByDay->firstWhere('date', $date);
            $chartRevenue[] = $dayRevenue ? ($dayRevenue->total / 100) : 0;
        }

        // Jobs completed by day (last 7 days)
        $jobsCompletedByDay = RepairBuddyJob::query()
            ->where('tenant_id', (int) $tenantId)
            ->where('branch_id', (int) $branchId)
            ->whereIn('status_slug', ['completed', 'delivered'])
            ->where('closed_at', '>=', now()->subDays(7)->startOfDay())
            ->selectRaw('DATE(closed_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dayJobs = $jobsCompletedByDay->firstWhere('date', $date);
            $chartJobsCompleted[] = $dayJobs ? (int) $dayJobs->count : 0;
        }

        // Job status distribution
        $jobStatusDistribution = RepairBuddyJob::query()
            ->where('tenant_id', (int) $tenantId)
            ->where('branch_id', (int) $branchId)
            ->selectRaw('status_slug, COUNT(*) as count')
            ->groupBy('status_slug')
            ->pluck('count', 'status_slug')
            ->toArray();

        // Device types distribution (using label_snapshot from job devices)
        $deviceTypeDistribution = \App\Models\RepairBuddyJobDevice::query()
            ->whereHas('job', fn ($q) => $q->where('tenant_id', (int) $tenantId)->where('branch_id', (int) $branchId))
            ->selectRaw('label_snapshot, COUNT(*) as count')
            ->whereNotNull('label_snapshot')
            ->where('label_snapshot', '!=', '')
            ->groupBy('label_snapshot')
            ->orderByDesc('count')
            ->limit(5)
            ->pluck('count', 'label_snapshot')
            ->toArray();

        // Average repair time (days between opened_at and closed_at)
        $avgRepairTime = RepairBuddyJob::query()
            ->where('tenant_id', (int) $tenantId)
            ->where('branch_id', (int) $branchId)
            ->whereIn('status_slug', ['completed', 'delivered'])
            ->whereNotNull('opened_at')
            ->whereNotNull('closed_at')
            ->selectRaw('AVG(DATEDIFF(closed_at, opened_at)) as avg_days')
            ->value('avg_days');

        $chartData = [
            'revenue' => [
                'labels' => $chartLabels,
                'data' => $chartRevenue,
            ],
            'jobs_completed' => [
                'data' => $chartJobsCompleted,
            ],
            'job_status' => [
                'labels' => array_map(fn($s) => ucwords(str_replace(['_', '-'], ' ', $s)), array_keys($jobStatusDistribution)),
                'data' => array_values($jobStatusDistribution),
            ],
            'device_types' => [
                'labels' => array_keys($deviceTypeDistribution),
                'data' => array_values($deviceTypeDistribution),
            ],
            'performance' => [
                'avg_repair_days' => round((float) $avgRepairTime, 1),
            ],
        ];

        /* ── Quick Action URLs ── */
        $job_url = route('tenant.jobs.create', ['business' => $tenant->slug]);
        $estimate_url = route('tenant.estimates.create', ['business' => $tenant->slug]);
        $calendar_url = route('tenant.dashboard', ['business' => $tenant->slug, 'screen' => 'calendar']);
        $report_url = route('tenant.dashboard', ['business' => $tenant->slug]) . '?screen=reports';

        return view('tenant.dashboard', [
            'tenant' => $tenant,
            'user' => $user,
            'activeNav' => 'dashboard',
            'role' => (string) ($user->role ?? 'technician'),
            'overviewStats' => $overviewStats,
            'latestJobs' => $latestJobs,
            'latestEstimates' => $latestEstimates,
            'activities' => $activities,
            'priorityJobs' => $priorityJobs,
            'upcomingVisits' => $upcomingVisits,
            'chartData' => $chartData,
            'job_url' => $job_url,
            'estimate_url' => $estimate_url,
            'calendar_url' => $calendar_url,
            'report_url' => $report_url,
        ]);
    }

    public function calendarEvents(Request $request): JsonResponse
    {
        $tenant = TenantContext::tenant();
        $tenantId = TenantContext::tenantId();
        $branchId = BranchContext::branchId();
        $user = $request->user();

        if (! $tenant || ! $tenantId || ! $branchId) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant or branch context missing.',
            ], 400);
        }

        $start = $request->input('start');
        $end = $request->input('end');
        $filter = $request->input('filter', 'all');
        $dateField = $request->input('date_field', 'pickup_date');

        $startDate = is_string($start) ? \Carbon\Carbon::parse($start)->startOfDay() : now()->subMonth()->startOfDay();
        $endDate = is_string($end) ? \Carbon\Carbon::parse($end)->endOfDay() : now()->addMonth()->endOfDay();

        $events = [];
        $totalJobs = 0;
        $totalEstimates = 0;

        $statusColors = [
            'new' => 'success',
            'quote' => 'info',
            'in_process' => 'info',
            'inprocess' => 'info',
            'waiting_parts' => 'warning',
            'ready' => 'warning',
            'completed' => 'secondary',
            'delivered' => 'secondary',
            'cancelled' => 'danger',
        ];

        // Fetch Jobs
        if ($filter === 'all' || $filter === 'jobs' || $filter === 'my_assignments') {
            $jobQuery = RepairBuddyJob::query()
                ->with(['customer'])
                ->where('tenant_id', (int) $tenantId)
                ->where('branch_id', (int) $branchId);

            if ($filter === 'my_assignments' && $user) {
                $jobQuery->where(function ($q) use ($user) {
                    $q->where('assigned_technician_id', $user->id)
                        ->orWhereHas('technicians', fn ($tq) => $tq->where('users.id', $user->id));
                });
            }

            $dateColumn = match ($dateField) {
                'delivery_date' => 'delivery_date',
                'next_service_date' => 'next_service_date',
                'post_date' => 'created_at',
                default => 'pickup_date',
            };

            $jobQuery->whereNotNull($dateColumn)
                ->where($dateColumn, '>=', $startDate->toDateString())
                ->where($dateColumn, '<=', $endDate->toDateString());

            $jobs = $jobQuery->limit(500)->get();
            $totalJobs = $jobs->count();

            foreach ($jobs as $job) {
                $dateValue = $dateColumn === 'created_at' ? $job->created_at : $job->{$dateColumn};
                if (! $dateValue) {
                    continue;
                }

                $statusSlug = $job->status_slug ?? 'new';
                $color = $statusColors[$statusSlug] ?? 'primary';
                $customerName = $job->customer?->name ?? '—';

                $title = "Job #{$job->job_number} - {$customerName}";

                $tooltip = "Case: {$job->case_number} | Customer: {$customerName} | Status: " . ucfirst($statusSlug);
                if ($job->{$dateColumn}) {
                    $tooltip .= " | Date: " . $job->{$dateColumn}->format('M d, Y');
                }

                $events[] = [
                    'title' => $title,
                    'start' => $dateValue->toIso8601String(),
                    'allDay' => true,
                    'url' => route('tenant.jobs.show', ['business' => $tenant->slug, 'jobId' => $job->id]),
                    'classNames' => ["bg-{$color}", 'job-event'],
                    'extendedProps' => [
                        'tooltip' => $tooltip,
                        'status' => ucfirst($statusSlug),
                        'type' => 'job',
                        'id' => $job->id,
                    ],
                ];
            }
        }

        // Fetch Estimates
        if ($filter === 'all' || $filter === 'estimates') {
            $estimateQuery = RepairBuddyEstimate::query()
                ->with(['customer'])
                ->where('tenant_id', (int) $tenantId)
                ->where('branch_id', (int) $branchId);

            $dateColumn = match ($dateField) {
                'delivery_date' => 'delivery_date',
                'post_date' => 'created_at',
                default => 'pickup_date',
            };

            $estimateQuery->whereNotNull($dateColumn)
                ->where($dateColumn, '>=', $startDate->toDateString())
                ->where($dateColumn, '<=', $endDate->toDateString());

            $estimates = $estimateQuery->limit(500)->get();
            $totalEstimates = $estimates->count();

            foreach ($estimates as $estimate) {
                $dateValue = $dateColumn === 'created_at' ? $estimate->created_at : $estimate->{$dateColumn};
                if (! $dateValue) {
                    continue;
                }

                $statusSlug = $estimate->status ?? 'pending';
                $customerName = $estimate->customer?->name ?? '—';

                $title = "Estimate - {$customerName}";

                $tooltip = "Case: {$estimate->case_number} | Customer: {$customerName} | Status: " . ucfirst($statusSlug);

                $events[] = [
                    'title' => $title,
                    'start' => $dateValue->toIso8601String(),
                    'allDay' => true,
                    'url' => route('tenant.estimates.show', ['business' => $tenant->slug, 'estimateId' => $estimate->id]),
                    'classNames' => ['bg-warning', 'estimate-event'],
                    'extendedProps' => [
                        'tooltip' => $tooltip,
                        'status' => ucfirst($statusSlug),
                        'type' => 'estimate',
                        'id' => $estimate->id,
                    ],
                ];
            }
        }

        // Fetch Appointments
        if ($filter === 'all' || $filter === 'appointments') {
            $appointmentQuery = RepairBuddyAppointment::query()
                ->with(['customer', 'appointmentSetting'])
                ->where('tenant_id', (int) $tenantId)
                ->where('branch_id', (int) $branchId)
                ->where('appointment_date', '>=', $startDate->toDateString())
                ->where('appointment_date', '<=', $endDate->toDateString());

            $appointments = $appointmentQuery->limit(500)->get();

            foreach ($appointments as $appointment) {
                $customerName = $appointment->customer?->name ?? '—';
                $title = $appointment->title ?? ($appointment->appointmentSetting?->title ?? 'Appointment');
                $timeDisplay = $appointment->time_slot_start->format('H:i') . ' - ' . $appointment->time_slot_end->format('H:i');

                $statusColorsAppt = [
                    'scheduled' => 'info',
                    'confirmed' => 'success',
                    'completed' => 'secondary',
                    'cancelled' => 'danger',
                    'no_show' => 'danger',
                ];
                $color = $statusColorsAppt[$appointment->status] ?? 'info';

                $tooltip = "{$title} | Customer: {$customerName} | Time: {$timeDisplay} | Status: " . ucfirst($appointment->status);

                $url = '#';
                if ($appointment->job_id) {
                    $url = route('tenant.jobs.show', ['business' => $tenant->slug, 'jobId' => $appointment->job_id]);
                } elseif ($appointment->estimate_id) {
                    $url = route('tenant.estimates.show', ['business' => $tenant->slug, 'estimateId' => $appointment->estimate_id]);
                }

                $events[] = [
                    'title' => "{$title} - {$customerName}",
                    'start' => $appointment->appointment_date->format('Y-m-d') . 'T' . $appointment->time_slot_start->format('H:i:s'),
                    'end' => $appointment->appointment_date->format('Y-m-d') . 'T' . $appointment->time_slot_end->format('H:i:s'),
                    'url' => $url,
                    'classNames' => ["bg-{$color}", 'appointment-event'],
                    'extendedProps' => [
                        'tooltip' => $tooltip,
                        'status' => ucfirst($appointment->status),
                        'type' => 'appointment',
                        'id' => $appointment->id,
                    ],
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $events,
            'stats' => [
                'total_jobs' => $totalJobs,
                'total_estimates' => $totalEstimates,
            ],
        ]);
    }
}
