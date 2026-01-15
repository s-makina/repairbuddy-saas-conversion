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

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
        Route::get('/me', [\App\Http\Controllers\Api\AuthController::class, 'me']);

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
    Route::post('/tenants', [\App\Http\Controllers\Api\Admin\TenantController::class, 'store'])
        ->middleware(['throttle:auth', 'permission:admin.tenants.write']);
});

Route::prefix('{tenant}')
    ->where(['tenant' => '[A-Za-z0-9\-]+' ])
    ->middleware(['tenant'])
    ->group(function () {
        Route::prefix('app')->middleware(['auth:sanctum', 'verified', 'tenant.member'])->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\Api\App\DashboardController::class, 'show']);

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
            Route::post('/users', [\App\Http\Controllers\Api\App\UserController::class, 'store'])
                ->middleware('permission:users.manage');
            Route::put('/users/{user}', [\App\Http\Controllers\Api\App\UserController::class, 'update'])
                ->middleware('permission:users.manage');
            Route::patch('/users/{user}/role', [\App\Http\Controllers\Api\App\UserController::class, 'updateRole'])
                ->middleware('permission:users.manage');
            Route::patch('/users/{user}/status', [\App\Http\Controllers\Api\App\UserController::class, 'updateStatus'])
                ->middleware('permission:users.manage');
        });
    });
