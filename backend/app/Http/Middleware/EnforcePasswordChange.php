<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $session = $request->attributes->get('impersonation_session');
        if ($session) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user instanceof User || $user->is_admin) {
            return $next($request);
        }

        if (! $user->must_change_password) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Password change required.',
            'code' => 'password_change_required',
        ], 428);
    }
}
