<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\PlatformAudit;
use App\Support\Permissions;
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

        return response()->json([
            'tenants' => $paginator->items(),
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

        [$tenant, $owner] = DB::transaction(function () use ($validated, $ownerPermissions, $memberPermissions) {
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

            $permissionIdsByName = Permission::query()->pluck('id', 'name')->all();

            $ownerRole->permissions()->sync(array_values(array_filter(array_map(function (string $name) use ($permissionIdsByName) {
                return $permissionIdsByName[$name] ?? null;
            }, $ownerPermissions))));

            $memberRole->permissions()->sync(array_values(array_filter(array_map(function (string $name) use ($permissionIdsByName) {
                return $permissionIdsByName[$name] ?? null;
            }, $memberPermissions))));

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
            'tenant' => $tenant,
            'owner' => User::query()
                ->where('tenant_id', $tenant->id)
                ->where('role', 'owner')
                ->orderBy('id')
                ->first(),
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
}
