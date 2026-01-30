<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class TechnicianController extends Controller
{
    public function index(Request $request, string $tenant)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:20'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $tenantId = TenantContext::tenantId();

        $roleId = Role::query()
            ->where('tenant_id', $tenantId)
            ->where('name', 'Technician')
            ->value('id');

        $roleId = is_numeric($roleId) ? (int) $roleId : null;

        if (! $roleId) {
            return response()->json([
                'users' => [],
                'meta' => [
                    'current_page' => 1,
                    'per_page' => (int) ($validated['per_page'] ?? 10),
                    'total' => 0,
                    'last_page' => 1,
                ],
            ]);
        }

        $q = is_string($validated['q'] ?? null) ? trim($validated['q']) : '';
        $status = is_string($validated['status'] ?? null) ? $validated['status'] : null;
        $perPage = (int) ($validated['per_page'] ?? 10);

        $query = User::query()
            ->where('users.tenant_id', $tenantId)
            ->where('users.is_admin', false)
            ->where('users.role_id', $roleId);

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('users.name', 'like', "%{$q}%")
                    ->orWhere('users.email', 'like', "%{$q}%");
            });
        }

        if (is_string($status) && $status !== '' && $status !== 'all') {
            if (in_array($status, ['pending', 'active', 'inactive', 'suspended'], true)) {
                $query->where('users.status', $status);
            }
        }

        $query->orderBy('users.id', 'desc');

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
}
