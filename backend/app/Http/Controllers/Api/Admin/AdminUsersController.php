<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminUsersController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'q'        => ['nullable', 'string', 'max:255'],
            'status'   => ['nullable', 'string', 'max:20'],
            'role'     => ['nullable', 'string', 'max:100'],
            'tenant_id'=> ['nullable', 'integer'],
            'sort'     => ['nullable', 'string', 'max:50'],
            'dir'      => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $q       = is_string($validated['q'] ?? null) ? trim($validated['q']) : '';
        $status  = is_string($validated['status'] ?? null) ? $validated['status'] : null;
        $role    = is_string($validated['role'] ?? null) ? $validated['role'] : null;
        $tenantId= isset($validated['tenant_id']) ? (int) $validated['tenant_id'] : null;
        $sort    = is_string($validated['sort'] ?? null) ? $validated['sort'] : null;
        $dir     = is_string($validated['dir'] ?? null) ? $validated['dir'] : null;
        $perPage = isset($validated['per_page']) ? (int) $validated['per_page'] : 25;

        $allowedSorts = [
            'id'         => 'users.id',
            'name'       => 'users.name',
            'email'      => 'users.email',
            'created_at' => 'users.created_at',
        ];

        $query = User::query()
            ->with(['tenant:id,name,slug,status'])
            ->where('users.is_admin', false);

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('users.name', 'like', "%{$q}%")
                    ->orWhere('users.email', 'like', "%{$q}%");
            });
        }

        if ($status !== null && $status !== '' && $status !== 'all') {
            $allowedStatuses = ['active', 'inactive', 'suspended', 'pending'];
            if (in_array($status, $allowedStatuses, true)) {
                $query->where('users.status', $status);
            }
        }

        if ($role !== null && $role !== '' && $role !== 'all') {
            $query->whereHas('roleModel', function ($sub) use ($role) {
                $sub->where('name', $role);
            });
        }

        if ($tenantId !== null) {
            $query->where('users.tenant_id', $tenantId);
        }

        $sortCol = $allowedSorts[$sort ?? ''] ?? null;
        $sortDir = in_array($dir, ['asc', 'desc'], true) ? $dir : null;

        if ($sortCol && $sortDir) {
            $query->orderBy($sortCol, $sortDir)->orderBy('users.id', 'desc');
        } else {
            $query->orderBy('users.id', 'desc');
        }

        $paginator = $query->paginate($perPage)->withQueryString();

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
            ],
        ]);
    }
}
