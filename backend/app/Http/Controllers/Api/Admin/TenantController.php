<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\PlatformAuditLog;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Models\TenantSubscription;
use App\Support\EntitlementsService;
use App\Support\PlatformAudit;
use App\Support\Permissions;
use App\Support\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class TenantController extends Controller
{
    protected function buildIndexQuery(array $validated)
    {
        $q = is_string($validated['q'] ?? null) ? trim($validated['q']) : '';
        $status = is_string($validated['status'] ?? null) ? $validated['status'] : null;
        $sort = is_string($validated['sort'] ?? null) ? $validated['sort'] : null;
        $dir = is_string($validated['dir'] ?? null) ? strtolower($validated['dir']) : null;

        $allowedSorts = [
            'id' => 'tenants.id',
            'name' => 'tenants.name',
            'slug' => 'tenants.slug',
            'status' => 'tenants.status',
            'contact_email' => 'tenants.contact_email',
            'created_at' => 'tenants.created_at',
        ];

        $query = Tenant::query();

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('tenants.name', 'like', "%{$q}%")
                    ->orWhere('tenants.slug', 'like', "%{$q}%")
                    ->orWhere('tenants.contact_email', 'like', "%{$q}%");
            });
        }

        if (is_string($status) && $status !== '' && $status !== 'all') {
            $allowedStatuses = ['trial', 'active', 'past_due', 'suspended', 'closed'];

            if (in_array($status, $allowedStatuses, true)) {
                $query->where('tenants.status', $status);
            }
        }

        $sortCol = $allowedSorts[$sort ?? ''] ?? null;
        $sortDir = in_array($dir, ['asc', 'desc'], true) ? $dir : null;

        if ($sortCol && $sortDir) {
            $query->orderBy($sortCol, $sortDir);
            $query->orderBy('tenants.id', 'desc');
        } else {
            $query->orderBy('tenants.id', 'desc');
        }

        return $query;
    }

    public function stats(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:20'],
        ]);

        $query = $this->buildIndexQuery($validated);

        $rows = $query
            ->select(['tenants.status', DB::raw('count(*) as c')])
            ->groupBy('tenants.status')
            ->get();

        $byStatus = [
            'trial' => 0,
            'active' => 0,
            'past_due' => 0,
            'suspended' => 0,
            'closed' => 0,
        ];

        foreach ($rows as $row) {
            $status = (string) ($row->status ?? '');
            $count = (int) ($row->c ?? 0);
            if (array_key_exists($status, $byStatus)) {
                $byStatus[$status] = $count;
            }
        }

        return response()->json([
            'total' => array_sum($byStatus),
            'by_status' => $byStatus,
        ]);
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:20'],
            'sort' => ['nullable', 'string', 'max:50'],
            'dir' => ['nullable', 'string', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 10);

        $query = $this->buildIndexQuery($validated);

        $paginator = $query->paginate($perPage);

        $tenantItems = $paginator->items();
        $tenantIds = [];
        foreach ($tenantItems as $t) {
            $id = is_array($t) ? (int) ($t['id'] ?? 0) : (int) ($t->id ?? 0);
            if ($id > 0) {
                $tenantIds[] = $id;
            }
        }

        $snapshots = [];

        if (count($tenantIds) > 0) {
            $latestSubscriptionIds = DB::table('tenant_subscriptions')
                ->whereIn('tenant_id', $tenantIds)
                ->select(['tenant_id', DB::raw('max(id) as id')])
                ->groupBy('tenant_id');

            $subs = DB::table('tenant_subscriptions as ts')
                ->joinSub($latestSubscriptionIds, 'latest', function ($join) {
                    $join->on('latest.id', '=', 'ts.id');
                })
                ->leftJoin('billing_prices as bp', 'bp.id', '=', 'ts.billing_price_id')
                ->leftJoin('billing_intervals as bi', 'bi.id', '=', 'bp.billing_interval_id')
                ->leftJoin('billing_plan_versions as bpv', 'bpv.id', '=', 'ts.billing_plan_version_id')
                ->leftJoin('billing_plans as bpl', 'bpl.id', '=', 'bpv.billing_plan_id')
                ->select([
                    'ts.tenant_id',
                    'ts.status as subscription_status',
                    'ts.currency as subscription_currency',
                    'ts.current_period_end as subscription_current_period_end',
                    'ts.cancel_at_period_end as subscription_cancel_at_period_end',
                    'bp.amount_cents as price_amount_cents',
                    'bp.interval as price_interval',
                    'bi.code as price_interval_code',
                    'bi.months as price_interval_months',
                    'bpl.name as plan_name',
                ])
                ->get();

            foreach ($subs as $row) {
                $tenantId = (int) ($row->tenant_id ?? 0);
                if ($tenantId <= 0) {
                    continue;
                }

                $amountCents = isset($row->price_amount_cents) ? (int) $row->price_amount_cents : null;
                $interval = isset($row->price_interval_code) && $row->price_interval_code ? strtolower((string) $row->price_interval_code) : (isset($row->price_interval) ? strtolower((string) $row->price_interval) : null);
                $months = isset($row->price_interval_months) ? (int) $row->price_interval_months : null;
                $subStatus = isset($row->subscription_status) ? (string) $row->subscription_status : null;

                $mrrCents = null;
                if (in_array($subStatus, ['trial', 'active', 'past_due'], true) && $amountCents !== null && $interval) {
                    if ($months !== null && $months > 0) {
                        $mrrCents = (int) round($amountCents / $months, 0);
                    } elseif ($interval === 'month') {
                        $mrrCents = $amountCents;
                    } elseif ($interval === 'year') {
                        $mrrCents = (int) round($amountCents / 12, 0);
                    } else {
                        $mrrCents = 0;
                    }
                }

                $snapshots[$tenantId] = array_merge($snapshots[$tenantId] ?? [], [
                    'subscription_status' => $subStatus,
                    'subscription_currency' => isset($row->subscription_currency) ? (string) $row->subscription_currency : null,
                    'subscription_current_period_end' => $row->subscription_current_period_end ?? null,
                    'subscription_cancel_at_period_end' => (bool) ($row->subscription_cancel_at_period_end ?? false),
                    'plan_name' => isset($row->plan_name) ? (string) $row->plan_name : null,
                    'price_amount_cents' => $amountCents,
                    'price_interval' => $interval,
                    'mrr_cents' => $mrrCents,
                ]);
            }

            $outstandingRows = DB::table('invoices')
                ->whereIn('tenant_id', $tenantIds)
                ->where('status', 'issued')
                ->select([
                    'tenant_id',
                    DB::raw('count(*) as c'),
                    DB::raw('sum(total_cents) as total_cents'),
                ])
                ->groupBy('tenant_id')
                ->get();

            foreach ($outstandingRows as $row) {
                $tenantId = (int) ($row->tenant_id ?? 0);
                if ($tenantId <= 0) {
                    continue;
                }

                $snapshots[$tenantId] = array_merge($snapshots[$tenantId] ?? [], [
                    'outstanding_invoices_count' => (int) ($row->c ?? 0),
                    'outstanding_balance_cents' => (int) ($row->total_cents ?? 0),
                ]);
            }

            $latestInvoiceIds = DB::table('invoices')
                ->whereIn('tenant_id', $tenantIds)
                ->select(['tenant_id', DB::raw('max(id) as id')])
                ->groupBy('tenant_id');

            $latestInvoices = DB::table('invoices as inv')
                ->joinSub($latestInvoiceIds, 'latest_inv', function ($join) {
                    $join->on('latest_inv.id', '=', 'inv.id');
                })
                ->select([
                    'inv.tenant_id',
                    'inv.id',
                    'inv.invoice_number',
                    'inv.status',
                    'inv.currency',
                    'inv.total_cents',
                    'inv.issued_at',
                    'inv.paid_at',
                ])
                ->get();

            foreach ($latestInvoices as $row) {
                $tenantId = (int) ($row->tenant_id ?? 0);
                if ($tenantId <= 0) {
                    continue;
                }

                $snapshots[$tenantId] = array_merge($snapshots[$tenantId] ?? [], [
                    'last_invoice' => [
                        'id' => (int) ($row->id ?? 0),
                        'invoice_number' => isset($row->invoice_number) ? (string) $row->invoice_number : null,
                        'status' => isset($row->status) ? (string) $row->status : null,
                        'currency' => isset($row->currency) ? (string) $row->currency : null,
                        'total_cents' => isset($row->total_cents) ? (int) $row->total_cents : null,
                        'issued_at' => $row->issued_at ?? null,
                        'paid_at' => $row->paid_at ?? null,
                    ],
                ]);
            }
        }

        $tenantsOut = [];
        foreach ($tenantItems as $t) {
            $tenantId = is_array($t) ? (int) ($t['id'] ?? 0) : (int) ($t->id ?? 0);
            $base = is_array($t) ? $t : $t->toArray();
            $base['billing_snapshot'] = $tenantId > 0 ? ($snapshots[$tenantId] ?? null) : null;
            $tenantsOut[] = $base;
        }

        return response()->json([
            'tenants' => $tenantsOut,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function export(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:20'],
            'sort' => ['nullable', 'string', 'max:50'],
            'dir' => ['nullable', 'string', 'in:asc,desc'],
            'format' => ['required', 'string', 'in:csv,xlsx,pdf'],
        ]);

        $format = $validated['format'];

        set_time_limit(0);

        $query = $this->buildIndexQuery($validated)->select([
            'tenants.id',
            'tenants.name',
            'tenants.slug',
            'tenants.status',
            'tenants.contact_email',
            'tenants.created_at',
        ]);

        $timestamp = now()->format('Ymd_His');

        if ($format === 'csv') {
            $filename = "tenants_{$timestamp}.csv";

            return response()->streamDownload(function () use ($query) {
                $out = fopen('php://output', 'w');
                if ($out === false) {
                    return;
                }

                fputcsv($out, ['ID', 'Name', 'Slug', 'Status', 'Contact Email', 'Created At']);

                foreach ($query->cursor() as $row) {
                    $createdAt = isset($row->created_at) ? (string) $row->created_at : '';
                    fputcsv($out, [
                        (string) ($row->id ?? ''),
                        (string) ($row->name ?? ''),
                        (string) ($row->slug ?? ''),
                        (string) ($row->status ?? ''),
                        (string) ($row->contact_email ?? ''),
                        $createdAt,
                    ]);
                }

                fclose($out);
            }, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        if ($format === 'pdf') {
            $filename = "tenants_{$timestamp}.pdf";

            $maxRows = 2000;
            $rows = $query->limit($maxRows + 1)->get();

            if ($rows->count() > $maxRows) {
                return response()->json([
                    'message' => 'Too many rows for PDF export. Please narrow your filters.',
                ], 422);
            }

            $escape = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $html = '<!doctype html><html><head><meta charset="utf-8">'
                . '<style>'
                . 'body{font-family:DejaVu Sans, sans-serif; font-size:12px; color:#111;}'
                . 'h1{font-size:16px; margin:0 0 10px 0;}'
                . 'table{width:100%; border-collapse:collapse;}'
                . 'th,td{border:1px solid #ddd; padding:6px; vertical-align:top;}'
                . 'th{background:#f5f5f5; font-weight:700;}'
                . '</style>'
                . '</head><body>'
                . '<h1>Tenants Export</h1>'
                . '<table><thead><tr>'
                . '<th>ID</th><th>Name</th><th>Slug</th><th>Status</th><th>Contact Email</th><th>Created At</th>'
                . '</tr></thead><tbody>';

            foreach ($rows as $row) {
                $html .= '<tr>'
                    . '<td>' . $escape($row->id ?? '') . '</td>'
                    . '<td>' . $escape($row->name ?? '') . '</td>'
                    . '<td>' . $escape($row->slug ?? '') . '</td>'
                    . '<td>' . $escape($row->status ?? '') . '</td>'
                    . '<td>' . $escape($row->contact_email ?? '') . '</td>'
                    . '<td>' . $escape($row->created_at ?? '') . '</td>'
                    . '</tr>';
            }

            $html .= '</tbody></table></body></html>';

            $pdf = Pdf::loadHTML($html)->setPaper('a4', 'landscape');

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, $filename, [
                'Content-Type' => 'application/pdf',
            ]);
        }

        if (! class_exists(Spreadsheet::class) || ! class_exists(Xlsx::class)) {
            return response()->json([
                'message' => 'XLSX export is not available because PhpSpreadsheet is not installed on the server.',
            ], 501);
        }

        $filename = "tenants_{$timestamp}.xlsx";

        $maxRows = 50000;
        $rows = [];
        $count = 0;
        foreach ($query->cursor() as $row) {
            $count++;
            if ($count > $maxRows) {
                return response()->json([
                    'message' => 'Too many rows for XLSX export. Please narrow your filters.',
                ], 422);
            }
            $rows[] = $row;
        }

        return response()->streamDownload(function () use ($rows) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $headers = ['ID', 'Name', 'Slug', 'Status', 'Contact Email', 'Created At'];
            $sheet->fromArray($headers, null, 'A1');

            $r = 2;
            foreach ($rows as $row) {
                $sheet->fromArray([
                    (string) ($row->id ?? ''),
                    (string) ($row->name ?? ''),
                    (string) ($row->slug ?? ''),
                    (string) ($row->status ?? ''),
                    (string) ($row->contact_email ?? ''),
                    isset($row->created_at) ? (string) $row->created_at : '',
                ], null, 'A'.$r);
                $r++;
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:64', 'unique:tenants,slug'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'owner_password' => ['required', 'string', 'min:8'],
        ]);

        $ownerPermissions = [
            'app.access',
            'dashboard.view',
            'appointments.view',
            'jobs.view',
            'estimates.view',
            'services.view',
            'devices.view',
            'device_brands.view',
            'device_types.view',
            'parts.view',
            'payments.view',
            'reports.view',
            'expenses.view',
            'expense_categories.view',
            'clients.view',
            'customer_devices.view',
            'technicians.view',
            'managers.view',
            'job_reviews.view',
            'time_logs.view',
            'hourly_rates.view',
            'reminder_logs.view',
            'print_screen.view',
            'security.manage',
            'profile.manage',
            'settings.manage',
            'users.manage',
            'roles.manage',
            'branches.manage',
        ];

        $memberPermissions = [
            'app.access',
            'dashboard.view',
            'jobs.view',
            'appointments.view',
            'estimates.view',
            'clients.view',
            'customer_devices.view',
            'profile.manage',
            'security.manage',
        ];

        $technicianPermissions = [
            'app.access',
            'dashboard.view',
            'jobs.view',
            'appointments.view',
            'estimates.view',
            'clients.view',
            'customer_devices.view',
            'profile.manage',
            'security.manage',
        ];

        [$tenant, $owner] = DB::transaction(function () use ($validated, $ownerPermissions, $memberPermissions, $technicianPermissions) {
            $slug = $validated['slug'] ?? Str::slug($validated['name']);
            $slug = $slug ?: 'tenant';

            $baseSlug = $slug;
            $i = 1;

            while (Tenant::query()->where('slug', $slug)->exists()) {
                $slug = $baseSlug.'-'.$i;
                $i++;
            }

            $tenant = Tenant::query()->create([
                'name' => $validated['name'],
                'slug' => $slug,
                'status' => 'active',
                'contact_email' => $validated['contact_email'] ?? $validated['owner_email'],
                'activated_at' => now(),
            ]);

            foreach (Permissions::all() as $permName) {
                Permission::query()->firstOrCreate([
                    'name' => $permName,
                ]);
            }

            $ownerRole = Role::query()->firstOrCreate([
                'tenant_id' => $tenant->id,
                'name' => 'Owner',
            ]);

            $memberRole = Role::query()->firstOrCreate([
                'tenant_id' => $tenant->id,
                'name' => 'Member',
            ]);

            $technicianRole = Role::query()->firstOrCreate([
                'tenant_id' => $tenant->id,
                'name' => 'Technician',
            ]);

            $permissionIdsByName = Permission::query()->pluck('id', 'name')->all();

            $ownerRole->permissions()->sync(array_values(array_filter(array_map(function (string $name) use ($permissionIdsByName) {
                return $permissionIdsByName[$name] ?? null;
            }, $ownerPermissions))));

            $memberRole->permissions()->sync(array_values(array_filter(array_map(function (string $name) use ($permissionIdsByName) {
                return $permissionIdsByName[$name] ?? null;
            }, $memberPermissions))));

            $technicianRole->permissions()->sync(array_values(array_filter(array_map(function (string $name) use ($permissionIdsByName) {
                return $permissionIdsByName[$name] ?? null;
            }, $technicianPermissions))));

            $owner = User::query()->create([
                'name' => $validated['owner_name'],
                'email' => $validated['owner_email'],
                'password' => Hash::make($validated['owner_password']),
                'tenant_id' => $tenant->id,
                'role' => 'owner',
                'role_id' => $ownerRole->id,
                'is_admin' => false,
            ]);

            return [$tenant, $owner];
        });

        PlatformAudit::log($request, 'tenant.created', $tenant, null, [
            'owner_user_id' => $owner->id,
        ]);

        return response()->json([
            'tenant' => $tenant,
            'owner' => $owner,
        ], 201);
    }

    public function show(Request $request, Tenant $tenant)
    {
        return response()->json([
            'tenant' => $tenant->load('plan'),
            'owner' => User::query()
                ->where('tenant_id', $tenant->id)
                ->where('role', 'owner')
                ->orderBy('id')
                ->first(),
        ]);
    }

    public function entitlements(Request $request, Tenant $tenant)
    {
        $overrides = is_array($tenant->entitlement_overrides) ? $tenant->entitlement_overrides : [];

        TenantContext::set($tenant);

        $subscription = TenantSubscription::query()
            ->with(['planVersion.plan', 'planVersion.entitlements.definition', 'price'])
            ->orderByDesc('id')
            ->first();

        $planEntitlements = [];
        if ($subscription?->planVersion) {
            foreach ($subscription->planVersion->entitlements as $row) {
                $code = (string) ($row->definition?->code ?? '');
                if ($code === '') {
                    continue;
                }
                $planEntitlements[$code] = $row->value_json;
            }
        }

        $effective = (new EntitlementsService())->resolveForTenant($tenant);

        TenantContext::set(null);

        return response()->json([
            'tenant' => $tenant,
            'subscription' => $subscription,
            'plan' => $subscription?->planVersion?->plan,
            'plan_version' => $subscription?->planVersion,
            'plan_entitlements' => $planEntitlements,
            'entitlement_overrides' => $overrides,
            'effective_entitlements' => $effective,
        ]);
    }

    public function audit(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
            'action' => ['nullable', 'string', 'max:255'],
            'actor_user_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $limit = (int) ($validated['limit'] ?? 50);
        $action = is_string($validated['action'] ?? null) ? trim((string) $validated['action']) : '';
        $actorUserId = isset($validated['actor_user_id']) ? (int) $validated['actor_user_id'] : null;

        $query = PlatformAuditLog::query()
            ->where('tenant_id', $tenant->id);

        if ($action !== '') {
            $query->where('action', $action);
        }

        if ($actorUserId && $actorUserId > 0) {
            $query->where('actor_user_id', $actorUserId);
        }

        $logs = $query
            ->with('actor')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return response()->json([
            'tenant' => $tenant,
            'audit' => $logs,
        ]);
    }

    public function suspend(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        if ($tenant->status === 'closed') {
            return response()->json([
                'message' => 'Tenant is closed.',
            ], 422);
        }

        if ($tenant->status !== 'suspended') {
            $tenant->forceFill([
                'status' => 'suspended',
                'suspended_at' => now(),
                'suspension_reason' => $validated['reason'] ?? null,
            ])->save();

            PlatformAudit::log($request, 'tenant.suspended', $tenant, $validated['reason'] ?? null);
        }

        return response()->json([
            'tenant' => $tenant->fresh(),
        ]);
    }

    public function unsuspend(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        if ($tenant->status === 'closed') {
            return response()->json([
                'message' => 'Tenant is closed.',
            ], 422);
        }

        if ($tenant->status === 'suspended') {
            $tenant->forceFill([
                'status' => 'active',
                'suspended_at' => null,
                'suspension_reason' => null,
                'activated_at' => $tenant->activated_at ?? now(),
            ])->save();

            PlatformAudit::log($request, 'tenant.unsuspended', $tenant, $validated['reason'] ?? null);
        }

        return response()->json([
            'tenant' => $tenant->fresh(),
        ]);
    }

    public function close(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
            'data_retention_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ]);

        if ($tenant->status !== 'closed') {
            $tenant->forceFill([
                'status' => 'closed',
                'closed_at' => now(),
                'closed_reason' => $validated['reason'] ?? null,
                'data_retention_days' => $validated['data_retention_days'] ?? $tenant->data_retention_days,
            ])->save();

            PlatformAudit::log($request, 'tenant.closed', $tenant, $validated['reason'] ?? null, [
                'data_retention_days' => $validated['data_retention_days'] ?? null,
            ]);
        }

        return response()->json([
            'tenant' => $tenant->fresh(),
        ]);
    }

    public function resetOwnerPassword(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:12'],
        ]);

        $owner = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('role', 'owner')
            ->orderBy('id')
            ->first();

        if (! $owner) {
            return response()->json([
                'message' => 'Owner user not found.',
            ], 404);
        }

        $newPassword = is_string($validated['password'] ?? null) && $validated['password'] !== ''
            ? $validated['password']
            : Str::password(16);

        $owner->forceFill([
            'password' => Hash::make($newPassword),
        ])->save();

        PlatformAudit::log($request, 'tenant.owner_password_reset', $tenant, $validated['reason'] ?? null, [
            'owner_user_id' => $owner->id,
        ]);

        return response()->json([
            'owner_user_id' => $owner->id,
            'password' => $newPassword,
        ]);
    }

    public function setPlan(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'plan_id' => ['nullable', 'integer', 'exists:plans,id'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $before = [
            'plan_id' => $tenant->plan_id,
        ];

        $planId = $validated['plan_id'] ?? null;
        $plan = $planId ? Plan::query()->find($planId) : null;

        $tenant->forceFill([
            'plan_id' => $plan?->id,
        ])->save();

        PlatformAudit::log($request, 'tenant.plan_set', $tenant, $validated['reason'] ?? null, [
            'before' => $before,
            'after' => [
                'plan_id' => $tenant->plan_id,
            ],
        ]);

        return response()->json([
            'tenant' => $tenant->fresh()->load('plan'),
        ]);
    }

    public function setEntitlementOverrides(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'entitlement_overrides' => ['nullable', 'array'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $before = [
            'entitlement_overrides' => $tenant->entitlement_overrides,
        ];

        $tenant->forceFill([
            'entitlement_overrides' => $validated['entitlement_overrides'] ?? null,
        ])->save();

        PlatformAudit::log($request, 'tenant.entitlement_overrides_set', $tenant, $validated['reason'] ?? null, [
            'before' => $before,
            'after' => [
                'entitlement_overrides' => $tenant->entitlement_overrides,
            ],
        ]);

        return response()->json([
            'tenant' => $tenant->fresh()->load('plan'),
        ]);
    }
}
