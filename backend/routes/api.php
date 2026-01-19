<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
    ]);
});

Route::prefix('auth')->group(function () {
    Route::post('/register', [\App\Http\Controllers\Api\AuthController::class, 'register'])->middleware('throttle:auth');
    Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login'])->middleware('throttle:auth');
    Route::post('/login/otp', [\App\Http\Controllers\Api\AuthController::class, 'loginOtp'])->middleware('throttle:auth');
    Route::post('/email/resend', [\App\Http\Controllers\Api\AuthController::class, 'resendVerificationEmail'])->middleware('throttle:auth');
    Route::post('/password/email', [\App\Http\Controllers\Api\AuthController::class, 'sendResetLinkEmail'])->middleware('throttle:auth');
    Route::post('/password/reset', [\App\Http\Controllers\Api\AuthController::class, 'resetPassword'])->middleware('throttle:auth');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
        Route::get('/me', [\App\Http\Controllers\Api\AuthController::class, 'me'])->middleware('impersonation');

        Route::middleware('verified')->group(function () {
            Route::post('/otp/setup', [\App\Http\Controllers\Api\AuthController::class, 'otpSetup']);
            Route::post('/otp/confirm', [\App\Http\Controllers\Api\AuthController::class, 'otpConfirm']);
            Route::post('/otp/disable', [\App\Http\Controllers\Api\AuthController::class, 'otpDisable']);
        });
    });
});

Route::prefix('admin')->middleware(['auth:sanctum', 'verified', 'admin'])->group(function () {
    Route::get('/tenants', [\App\Http\Controllers\Api\Admin\TenantController::class, 'index'])
        ->middleware('permission:admin.tenants.read');
    Route::get('/tenants/stats', [\App\Http\Controllers\Api\Admin\TenantController::class, 'stats'])
        ->middleware('permission:admin.tenants.read');
    Route::get('/tenants/export', [\App\Http\Controllers\Api\Admin\TenantController::class, 'export'])
        ->middleware('permission:admin.tenants.read');
    Route::get('/tenants/{tenant}', [\App\Http\Controllers\Api\Admin\TenantController::class, 'show'])
        ->whereNumber('tenant')
        ->middleware('permission:admin.tenants.read');
    Route::get('/tenants/{tenant}/entitlements', [\App\Http\Controllers\Api\Admin\TenantController::class, 'entitlements'])
        ->whereNumber('tenant')
        ->middleware('permission:admin.tenants.read');
    Route::get('/tenants/{tenant}/audit', [\App\Http\Controllers\Api\Admin\TenantController::class, 'audit'])
        ->whereNumber('tenant')
        ->middleware('permission:admin.tenants.read');
    Route::post('/tenants', [\App\Http\Controllers\Api\Admin\TenantController::class, 'store'])
        ->middleware(['throttle:auth', 'permission:admin.tenants.write']);

    Route::patch('/tenants/{tenant}/suspend', [\App\Http\Controllers\Api\Admin\TenantController::class, 'suspend'])
        ->whereNumber('tenant')
        ->middleware('permission:admin.tenants.write');
    Route::patch('/tenants/{tenant}/unsuspend', [\App\Http\Controllers\Api\Admin\TenantController::class, 'unsuspend'])
        ->whereNumber('tenant')
        ->middleware('permission:admin.tenants.write');
    Route::patch('/tenants/{tenant}/close', [\App\Http\Controllers\Api\Admin\TenantController::class, 'close'])
        ->whereNumber('tenant')
        ->middleware('permission:admin.tenants.write');
    Route::post('/tenants/{tenant}/owner/reset-password', [\App\Http\Controllers\Api\Admin\TenantController::class, 'resetOwnerPassword'])
        ->whereNumber('tenant')
        ->middleware('permission:admin.tenants.write');

    Route::put('/tenants/{tenant}/plan', [\App\Http\Controllers\Api\Admin\TenantController::class, 'setPlan'])
        ->whereNumber('tenant')
        ->middleware('permission:admin.tenants.write');
    Route::put('/tenants/{tenant}/entitlements', [\App\Http\Controllers\Api\Admin\TenantController::class, 'setEntitlementOverrides'])
        ->whereNumber('tenant')
        ->middleware('permission:admin.tenants.write');

    Route::get('/plans', [\App\Http\Controllers\Api\Admin\PlanController::class, 'index'])
        ->middleware('permission:admin.plans.read');
    Route::post('/plans', [\App\Http\Controllers\Api\Admin\PlanController::class, 'store'])
        ->middleware(['throttle:auth', 'permission:admin.plans.write']);
    Route::put('/plans/{plan}', [\App\Http\Controllers\Api\Admin\PlanController::class, 'update'])
        ->whereNumber('plan')
        ->middleware('permission:admin.plans.write');
    Route::delete('/plans/{plan}', [\App\Http\Controllers\Api\Admin\PlanController::class, 'destroy'])
        ->whereNumber('plan')
        ->middleware('permission:admin.plans.write');

    Route::post('/impersonation', [\App\Http\Controllers\Api\Admin\ImpersonationController::class, 'store'])
        ->middleware('permission:admin.impersonation.start');
    Route::post('/impersonation/{session}/stop', [\App\Http\Controllers\Api\Admin\ImpersonationController::class, 'stop'])
        ->whereNumber('session')
        ->middleware('permission:admin.impersonation.stop');

    Route::get('/tenants/{tenant}/diagnostics', [\App\Http\Controllers\Api\Admin\TenantDiagnosticsController::class, 'show'])
        ->whereNumber('tenant')
        ->middleware('permission:admin.diagnostics.read');
});

Route::prefix('{tenant}')
    ->where(['tenant' => '[A-Za-z0-9\-]+' ])
    ->middleware(['tenant'])
    ->group(function () {
        Route::prefix('app')->middleware(['auth:sanctum', 'impersonation', 'verified', 'impersonation.audit', 'tenant.member'])->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\Api\App\DashboardController::class, 'show']);

            Route::get('/entitlements', [\App\Http\Controllers\Api\App\EntitlementController::class, 'index']);

            Route::get('/notes', [\App\Http\Controllers\Api\App\TenantNoteController::class, 'index']);
            Route::post('/notes', [\App\Http\Controllers\Api\App\TenantNoteController::class, 'store']);

            Route::get('/permissions', [\App\Http\Controllers\Api\App\PermissionController::class, 'index'])
                ->middleware('permission:roles.manage');

            Route::get('/roles', [\App\Http\Controllers\Api\App\RoleController::class, 'index'])
                ->middleware('permission:roles.manage');
            Route::post('/roles', [\App\Http\Controllers\Api\App\RoleController::class, 'store'])
                ->middleware('permission:roles.manage');
            Route::put('/roles/{role}', [\App\Http\Controllers\Api\App\RoleController::class, 'update'])
                ->middleware('permission:roles.manage');
            Route::delete('/roles/{role}', [\App\Http\Controllers\Api\App\RoleController::class, 'destroy'])
                ->middleware('permission:roles.manage');

            Route::get('/users', [\App\Http\Controllers\Api\App\UserController::class, 'index'])
                ->middleware('permission:users.manage');
            Route::get('/users/export', [\App\Http\Controllers\Api\App\UserController::class, 'export'])
                ->middleware('permission:users.manage');
            Route::post('/users', [\App\Http\Controllers\Api\App\UserController::class, 'store'])
                ->middleware('permission:users.manage');
            Route::put('/users/{user}', [\App\Http\Controllers\Api\App\UserController::class, 'update'])
                ->middleware('permission:users.manage');
            Route::patch('/users/{user}/role', [\App\Http\Controllers\Api\App\UserController::class, 'updateRole'])
                ->middleware('permission:users.manage');
            Route::patch('/users/{user}/status', [\App\Http\Controllers\Api\App\UserController::class, 'updateStatus'])
                ->middleware('permission:users.manage');
            Route::post('/users/{user}/reset-password', [\App\Http\Controllers\Api\App\UserController::class, 'sendPasswordResetLink'])
                ->middleware('permission:users.manage');
        });
    });
