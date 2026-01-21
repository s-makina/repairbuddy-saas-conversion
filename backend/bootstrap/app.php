<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->throttleApi('60,1');

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'permission' => \App\Http\Middleware\EnsurePermission::class,
            'tenant' => \App\Http\Middleware\ResolveTenant::class,
            'tenant.member' => \App\Http\Middleware\EnsureTenantMember::class,
            'mfa.enforce' => \App\Http\Middleware\EnforceTenantMfa::class,
            'tenant.session' => \App\Http\Middleware\EnforceTenantSessionPolicy::class,
            'onboarding.gate' => \App\Http\Middleware\EnforceOnboardingGate::class,
            'verified' => \App\Http\Middleware\EnsureEmailVerifiedJson::class,
            'impersonation' => \App\Http\Middleware\ApplyImpersonation::class,
            'impersonation.audit' => \App\Http\Middleware\AuditImpersonatedWrites::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->reportable(function (\Throwable $e) {
            if ($e instanceof TransportExceptionInterface) {
                Log::error('mail.failed', [
                    'mailer' => config('mail.default'),
                    'message' => $e->getMessage(),
                    'exception' => get_class($e),
                ]);
            }
        });
    })->create();
