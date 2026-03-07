<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImpersonationSession;
use App\Models\Tenant;
use App\Models\User;
use App\Support\PlatformAudit;
use Illuminate\Http\Request;

class ImpersonationController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'q'        => ['nullable', 'string', 'max:255'],
            'status'   => ['nullable', 'string', 'in:active,completed,terminated,all'],
            'from'     => ['nullable', 'date'],
            'to'       => ['nullable', 'date'],
            'sort'     => ['nullable', 'string', 'max:50'],
            'dir'      => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $q       = is_string($validated['q'] ?? null) ? trim($validated['q']) : '';
        $status  = is_string($validated['status'] ?? null) ? $validated['status'] : null;
        $from    = is_string($validated['from'] ?? null) ? $validated['from'] : null;
        $to      = is_string($validated['to'] ?? null) ? $validated['to'] : null;
        $sort    = is_string($validated['sort'] ?? null) ? $validated['sort'] : null;
        $dir     = is_string($validated['dir'] ?? null) ? $validated['dir'] : null;
        $perPage = isset($validated['per_page']) ? (int) $validated['per_page'] : 25;

        $allowedSorts = [
            'id'         => 'impersonation_sessions.id',
            'started_at' => 'impersonation_sessions.started_at',
            'ended_at'   => 'impersonation_sessions.ended_at',
        ];

        $query = ImpersonationSession::query()
            ->with([
                'actor:id,name,email,is_admin,admin_role',
                'targetUser:id,name,email,tenant_id',
                'tenant:id,name,slug',
            ]);

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('impersonation_sessions.reason', 'like', "%{$q}%")
                    ->orWhere('impersonation_sessions.reference_id', 'like', "%{$q}%")
                    ->orWhereHas('actor', function ($actorQ) use ($q) {
                        $actorQ->where('name', 'like', "%{$q}%")
                               ->orWhere('email', 'like', "%{$q}%");
                    })
                    ->orWhereHas('targetUser', function ($targetQ) use ($q) {
                        $targetQ->where('name', 'like', "%{$q}%")
                                ->orWhere('email', 'like', "%{$q}%");
                    });
            });
        }

        if ($status !== null && $status !== '' && $status !== 'all') {
            if ($status === 'active') {
                $query->whereNull('impersonation_sessions.ended_at')
                      ->where('impersonation_sessions.expires_at', '>', now());
            } elseif ($status === 'completed') {
                $query->whereNotNull('impersonation_sessions.ended_at');
            } elseif ($status === 'terminated') {
                $query->whereNull('impersonation_sessions.ended_at')
                      ->where('impersonation_sessions.expires_at', '<=', now());
            }
        }

        if ($from !== null) {
            $query->whereDate('impersonation_sessions.started_at', '>=', $from);
        }

        if ($to !== null) {
            $query->whereDate('impersonation_sessions.started_at', '<=', $to);
        }

        $sortCol = $allowedSorts[$sort ?? ''] ?? null;
        $sortDir = in_array($dir, ['asc', 'desc'], true) ? $dir : null;

        if ($sortCol && $sortDir) {
            $query->orderBy($sortCol, $sortDir)->orderBy('impersonation_sessions.id', 'desc');
        } else {
            $query->orderBy('impersonation_sessions.id', 'desc');
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

    public function store(Request $request)
    {
        $actor = $request->user();

        if (! $actor instanceof User || ! $actor->is_admin) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        $validated = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'target_user_id' => ['required', 'integer', 'exists:users,id'],
            'reason' => ['required', 'string', 'max:255'],
            'reference_id' => ['required', 'string', 'max:255'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:240'],
        ]);

        $tenant = Tenant::query()->findOrFail($validated['tenant_id']);
        $target = User::query()->findOrFail($validated['target_user_id']);

        if ((int) $target->tenant_id !== (int) $tenant->id || $target->is_admin) {
            return response()->json([
                'message' => 'Target user must be a tenant user within the selected tenant.',
            ], 422);
        }

        if (! $target->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Target user email is not verified.',
            ], 422);
        }

        $minutes = (int) ($validated['duration_minutes'] ?? 60);

        $session = ImpersonationSession::query()->create([
            'actor_user_id' => $actor->id,
            'tenant_id' => $tenant->id,
            'target_user_id' => $target->id,
            'reason' => $validated['reason'],
            'reference_id' => $validated['reference_id'],
            'started_at' => now(),
            'expires_at' => now()->addMinutes($minutes),
            'metadata' => [
                'duration_minutes' => $minutes,
            ],
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        PlatformAudit::log($request, 'impersonation.started', $tenant, $validated['reason'], [
            'impersonation_session_id' => $session->id,
            'reference_id' => $validated['reference_id'],
            'target_user_id' => $target->id,
            'expires_at' => $session->expires_at?->toIso8601String(),
        ]);

        return response()->json([
            'session' => $session->load(['tenant', 'targetUser']),
        ], 201);
    }

    public function stop(Request $request, ImpersonationSession $session)
    {
        $actor = $request->user();

        if (! $actor instanceof User || ! $actor->is_admin) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $adminRole = (string) ($actor->admin_role ?? '');
        if ($adminRole === '') {
            $adminRole = 'platform_admin';
        }

        if ((int) $session->actor_user_id !== (int) $actor->id && $adminRole !== 'platform_admin') {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        if (! $session->ended_at) {
            $session->forceFill([
                'ended_at' => now(),
            ])->save();

            $session->loadMissing('tenant');

            PlatformAudit::log($request, 'impersonation.ended', $session->tenant, $validated['reason'] ?? null, [
                'impersonation_session_id' => $session->id,
            ]);
        }

        return response()->json([
            'session' => $session->fresh(),
        ]);
    }
}
