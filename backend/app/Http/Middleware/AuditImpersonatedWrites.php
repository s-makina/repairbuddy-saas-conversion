<?php

namespace App\Http\Middleware;

use App\Support\ImpersonationContext;
use App\Support\PlatformAudit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditImpersonatedWrites
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $session = ImpersonationContext::session();

        if (! $session) {
            return $response;
        }

        $method = strtoupper((string) $request->getMethod());

        if (! in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $response;
        }

        $input = $request->except(['password', 'new_password', 'current_password', 'otp_secret', 'token']);
        $keys = array_keys(is_array($input) ? $input : []);

        PlatformAudit::logAs($request, ImpersonationContext::actor(), 'impersonation.write', ImpersonationContext::tenant(), null, [
            'impersonation_session_id' => $session->id,
            'impersonator_user_id' => ImpersonationContext::actor()?->id,
            'target_user_id' => $session->target_user_id,
            'method' => $method,
            'path' => $request->path(),
            'input_keys' => $keys,
            'status' => method_exists($response, 'getStatusCode') ? $response->getStatusCode() : null,
        ]);

        return $response;
    }
}
