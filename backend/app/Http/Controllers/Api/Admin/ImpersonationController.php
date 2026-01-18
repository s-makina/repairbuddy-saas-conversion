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
