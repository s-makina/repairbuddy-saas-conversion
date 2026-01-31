<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Notifications\OneTimePasswordNotification;
use App\Support\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class UserController extends Controller
{
    protected function buildIndexQuery(array $validated, bool $withRelations = true)
    {
        $tenantId = TenantContext::tenantId();

        $q = is_string($validated['q'] ?? null) ? trim($validated['q']) : '';
        $role = is_string($validated['role'] ?? null) ? $validated['role'] : null;
        $status = is_string($validated['status'] ?? null) ? $validated['status'] : null;
        $sort = is_string($validated['sort'] ?? null) ? $validated['sort'] : null;
        $dir = is_string($validated['dir'] ?? null) ? strtolower($validated['dir']) : null;

        $allowedSorts = [
            'id' => 'users.id',
            'name' => 'users.name',
            'email' => 'users.email',
            'status' => 'users.status',
            'created_at' => 'users.created_at',
        ];

        $query = User::query()
            ->where('users.tenant_id', $tenantId)
            ->where('users.is_admin', false);

        if ($withRelations) {
            $query->with([
                'roleModel',
                'branches:id,code,name,is_active',
            ]);
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('users.name', 'like', "%{$q}%")
                    ->orWhere('users.email', 'like', "%{$q}%");
            });
        }

        if (is_string($role) && $role !== '' && $role !== 'all') {
            if ($role === 'none') {
                $query->whereNull('users.role_id');
            } elseif (ctype_digit($role)) {
                $query->where('users.role_id', (int) $role);
            }
        }

        if (is_string($status) && $status !== '' && $status !== 'all') {
            if (in_array($status, ['pending', 'active', 'inactive', 'suspended'], true)) {
                $query->where('users.status', $status);
            }
        }

        $sortCol = $allowedSorts[$sort ?? ''] ?? null;
        $sortDir = in_array($dir, ['asc', 'desc'], true) ? $dir : null;

        if ($sortCol && $sortDir) {
            $query->orderBy($sortCol, $sortDir);
            $query->orderBy('users.id', 'desc');
        } else {
            $query->orderBy('users.id', 'desc');
        }

        return $query;
    }

    protected function ensureTenantUser(User $user): void
    {
        $tenantId = TenantContext::tenantId();

        if ((int) $user->tenant_id !== (int) $tenantId || $user->is_admin) {
            abort(403, 'Forbidden.');
        }
    }

    public function index(Request $request, string $tenant)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:20'],
            'sort' => ['nullable', 'string', 'max:50'],
            'dir' => ['nullable', 'string', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 10);

        $query = $this->buildIndexQuery($validated, true);

        $paginator = $query->paginate($perPage);

        return response()->json([
            'users' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function export(Request $request, string $tenant)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:20'],
            'sort' => ['nullable', 'string', 'max:50'],
            'dir' => ['nullable', 'string', 'in:asc,desc'],
            'format' => ['required', 'string', 'in:csv,xlsx,pdf'],
        ]);

        $format = $validated['format'];

        $tenantId = TenantContext::tenantId();

        set_time_limit(0);

        $query = $this->buildIndexQuery($validated, false)
            ->leftJoin('roles', function ($join) use ($tenantId) {
                $join->on('roles.id', '=', 'users.role_id')
                    ->where('roles.tenant_id', '=', $tenantId);
            })
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.status',
                'users.created_at',
                'roles.name as role_name',
            ]);

        $timestamp = now()->format('Ymd_His');

        if ($format === 'csv') {
            $filename = "users_{$timestamp}.csv";

            return response()->streamDownload(function () use ($query) {
                $out = fopen('php://output', 'w');
                if ($out === false) {
                    return;
                }

                fputcsv($out, ['ID', 'Name', 'Email', 'Role', 'Status', 'Created At']);

                foreach ($query->cursor() as $row) {
                    $createdAt = isset($row->created_at) ? (string) $row->created_at : '';
                    fputcsv($out, [
                        (string) ($row->id ?? ''),
                        (string) ($row->name ?? ''),
                        (string) ($row->email ?? ''),
                        (string) ($row->role_name ?? ''),
                        (string) ($row->status ?? ''),
                        $createdAt,
                    ]);
                }

                fclose($out);
            }, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        if ($format === 'pdf') {
            $filename = "users_{$timestamp}.pdf";

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
                . '<h1>Users Export</h1>'
                . '<table><thead><tr>'
                . '<th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Created At</th>'
                . '</tr></thead><tbody>';

            foreach ($rows as $row) {
                $html .= '<tr>'
                    . '<td>' . $escape($row->id ?? '') . '</td>'
                    . '<td>' . $escape($row->name ?? '') . '</td>'
                    . '<td>' . $escape($row->email ?? '') . '</td>'
                    . '<td>' . $escape($row->role_name ?? '') . '</td>'
                    . '<td>' . $escape($row->status ?? '') . '</td>'
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

        $filename = "users_{$timestamp}.xlsx";

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

            $headers = ['ID', 'Name', 'Email', 'Role', 'Status', 'Created At'];
            $sheet->fromArray($headers, null, 'A1');

            $r = 2;
            foreach ($rows as $row) {
                $sheet->fromArray([
                    (string) ($row->id ?? ''),
                    (string) ($row->name ?? ''),
                    (string) ($row->email ?? ''),
                    (string) ($row->role_name ?? ''),
                    (string) ($row->status ?? ''),
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

    public function store(Request $request, string $tenant)
    {
        $tenantId = TenantContext::tenantId();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role_id' => ['required', 'integer'],
            'branch_ids' => ['required', 'array', 'min:1'],
            'branch_ids.*' => ['integer'],
        ]);

        $role = Role::query()->where('tenant_id', $tenantId)->where('id', $validated['role_id'])->first();

        if (! $role) {
            return response()->json([
                'message' => 'Role is invalid.',
            ], 422);
        }

        $branchIds = array_values(array_unique(array_map('intval', $validated['branch_ids'])));

        $validBranchIds = Branch::query()
            ->where('is_active', true)
            ->whereIn('id', $branchIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        sort($branchIds);
        sort($validBranchIds);

        if (count($branchIds) === 0 || $branchIds !== $validBranchIds) {
            return response()->json([
                'message' => 'Shop selection is invalid.',
            ], 422);
        }

        $oneTimePassword = Str::password(16);
        $oneTimePasswordExpiresAt = now()->addMinutes(60 * 24);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make(Str::random(72)),
            'one_time_password_hash' => Hash::make($oneTimePassword),
            'one_time_password_expires_at' => $oneTimePasswordExpiresAt,
            'one_time_password_used_at' => null,
            'tenant_id' => $tenantId,
            'role_id' => $role->id,
            'role' => null,
            'status' => 'active',
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        $sync = [];
        foreach ($validBranchIds as $id) {
            $sync[$id] = ['tenant_id' => $tenantId];
        }

        $user->branches()->sync($sync);

        try {
            $user->notify(new OneTimePasswordNotification($oneTimePassword, 60 * 24));
        } catch (\Throwable $e) {
            Log::error('user.onetime_password_notification_failed', [
                'user_id' => $user->id,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'user' => $user->load(['roleModel', 'branches:id,code,name,is_active']),
        ], 201);
    }

    public function updateShop(Request $request, string $tenant, User $user)
    {
        $tenantId = TenantContext::tenantId();
        $this->ensureTenantUser($user);

        $validated = $request->validate([
            'branch_ids' => ['required', 'array', 'min:1'],
            'branch_ids.*' => ['integer'],
        ]);

        $branchIds = array_values(array_unique(array_map('intval', $validated['branch_ids'])));

        $validBranchIds = Branch::query()
            ->where('is_active', true)
            ->whereIn('id', $branchIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        sort($branchIds);
        sort($validBranchIds);

        if (count($branchIds) === 0 || $branchIds !== $validBranchIds) {
            return response()->json([
                'message' => 'Shop selection is invalid.',
            ], 422);
        }

        $sync = [];
        foreach ($validBranchIds as $id) {
            $sync[$id] = ['tenant_id' => $tenantId];
        }

        $user->branches()->sync($sync);

        return response()->json([
            'user' => $user->load(['roleModel', 'branches:id,code,name,is_active']),
        ]);
    }

    public function updateRole(Request $request, string $tenant, User $user)
    {
        $tenantId = TenantContext::tenantId();
        $this->ensureTenantUser($user);

        $validated = $request->validate([
            'role_id' => ['required', 'integer'],
        ]);

        $role = Role::query()->where('tenant_id', $tenantId)->where('id', $validated['role_id'])->first();

        if (! $role) {
            return response()->json([
                'message' => 'Role is invalid.',
            ], 422);
        }

        $user->forceFill([
            'role_id' => $role->id,
            'role' => null,
        ])->save();

        return response()->json([
            'user' => $user->load('roleModel'),
        ]);
    }

    public function update(Request $request, string $tenant, User $user)
    {
        $tenantId = TenantContext::tenantId();
        $this->ensureTenantUser($user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'role_id' => ['nullable', 'integer'],
        ]);

        $roleId = $validated['role_id'] ?? null;

        if (! is_null($roleId)) {
            $role = Role::query()->where('tenant_id', $tenantId)->where('id', $roleId)->first();

            if (! $role) {
                return response()->json([
                    'message' => 'Role is invalid.',
                ], 422);
            }

            $user->forceFill([
                'role_id' => $role->id,
                'role' => null,
            ]);
        }

        $user->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ])->save();

        return response()->json([
            'user' => $user->load('roleModel'),
        ]);
    }

    public function updateStatus(Request $request, string $tenant, User $user)
    {
        $this->ensureTenantUser($user);

        $validated = $request->validate([
            'status' => ['required', 'in:pending,active,inactive,suspended'],
        ]);

        $user->forceFill([
            'status' => $validated['status'],
        ])->save();

        return response()->json([
            'user' => $user->load('roleModel'),
        ]);
    }

    public function sendPasswordResetLink(Request $request, string $tenant, User $user)
    {
        $this->ensureTenantUser($user);

        $status = Password::sendResetLink([
            'email' => $user->email,
        ]);

        if ($status !== Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'Failed to send reset link.',
                'status' => $status,
            ], 422);
        }

        return response()->json([
            'message' => 'Reset link sent.',
        ]);
    }
}
