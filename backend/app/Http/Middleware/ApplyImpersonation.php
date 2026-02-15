<?php

namespace App\Http\Middleware;

use App\Models\ImpersonationSession;
use App\Models\User;
use App\Support\ImpersonationContext;
use App\Support\PlatformAudit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ApplyImpersonation
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('X-RB-Impersonation');

        if (! is_string($header) || trim($header) === '') {
            return $next($request);
        }

        $actor = $request->user();

        if (! $actor instanceof User || ! $actor->is_admin) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        if (! $actor->can('admin.impersonation.start')) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        $id = trim($header);
        if (! ctype_digit($id)) {
            return response()->json([
                'message' => 'Invalid impersonation session.',
            ], 422);
        }

        $session = ImpersonationSession::query()
            ->whereKey((int) $id)
            ->where('actor_user_id', $actor->id)
            ->whereNull('ended_at')
            ->first();

        if (! $session) {
            return response()->json([
                'message' => 'Impersonation session is not active.',
            ], 403);
        }

        if ($session->expires_at && $session->expires_at->lte(now())) {
            $session->forceFill([
                'ended_at' => now(),
            ])->save();

            $session->loadMissing('tenant');

            PlatformAudit::logAs($request, $actor, 'impersonation.ended', $session->tenant, 'expired', [
                'impersonation_session_id' => $session->id,
                'reference_id' => $session->reference_id,
            ]);

            return response()->json([
                'message' => 'Impersonation session expired.',
            ], 403);
        }

        $session->loadMissing(['tenant', 'targetUser']);

        $target = $session->targetUser;
        $tenant = $session->tenant;

        if (! $tenant || ! $target instanceof User) {
            return response()->json([
                'message' => 'Impersonation target is invalid.',
            ], 422);
        }

        if ((int) $target->tenant_id !== (int) $tenant->id || $target->is_admin) {
            return response()->json([
                'message' => 'Impersonation target is invalid.',
            ], 422);
        }

        $request->attributes->set('impersonator_user', $actor);
        $request->attributes->set('impersonation_session', $session);

        ImpersonationContext::set($session, $actor, $target, $tenant);

        $request->setUserResolver(function () use ($target) {
            return $target;
        });
        Auth::setUser($target);

        $response = $next($request);

        ImpersonationContext::clear();

        return $response;
    }
}
