<?php

namespace App\Support;

use App\Models\PlatformAuditLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;

class PlatformAudit
{
    public static function log(Request $request, string $action, ?Tenant $tenant = null, ?string $reason = null, array $metadata = []): PlatformAuditLog
    {
        $user = $request->user();

        return self::logAs($request, $user instanceof User ? $user : null, $action, $tenant, $reason, $metadata);
    }

    public static function logAs(Request $request, ?User $actor, string $action, ?Tenant $tenant = null, ?string $reason = null, array $metadata = []): PlatformAuditLog
    {
        $user = $actor;

        return PlatformAuditLog::query()->create([
            'actor_user_id' => $user instanceof User ? $user->id : null,
            'tenant_id' => $tenant?->id,
            'action' => $action,
            'reason' => $reason,
            'metadata' => $metadata,
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);
    }
}
