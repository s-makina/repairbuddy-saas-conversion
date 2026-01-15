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

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
        Route::get('/me', [\App\Http\Controllers\Api\AuthController::class, 'me']);
    });
});

Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/tenants', [\App\Http\Controllers\Api\Admin\TenantController::class, 'index']);
    Route::post('/tenants', [\App\Http\Controllers\Api\Admin\TenantController::class, 'store'])->middleware('throttle:auth');
});

Route::prefix('app')->middleware(['auth:sanctum', 'tenant', 'tenant.member'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Api\App\DashboardController::class, 'show']);

    Route::get('/notes', [\App\Http\Controllers\Api\App\TenantNoteController::class, 'index']);
    Route::post('/notes', [\App\Http\Controllers\Api\App\TenantNoteController::class, 'store']);
});
