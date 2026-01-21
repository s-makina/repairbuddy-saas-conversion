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
            Route::post('/otp/setup', [\App\Http\Controllers\Api\AuthController::class, 'otpSetup'])
                ->middleware(['impersonation', 'impersonation.audit']);
            Route::post('/otp/confirm', [\App\Http\Controllers\Api\AuthController::class, 'otpConfirm'])
                ->middleware(['impersonation', 'impersonation.audit']);
            Route::post('/otp/disable', [\App\Http\Controllers\Api\AuthController::class, 'otpDisable'])
                ->middleware(['impersonation', 'impersonation.audit']);
            Route::patch('/me', [\App\Http\Controllers\Api\AuthController::class, 'updateMe'])->middleware('impersonation');
            Route::post('/me/avatar', [\App\Http\Controllers\Api\AuthController::class, 'updateAvatar'])->middleware('impersonation');
            Route::delete('/me/avatar', [\App\Http\Controllers\Api\AuthController::class, 'deleteAvatar'])->middleware('impersonation');
        });
    });
});

Route::prefix('admin')->middleware(['auth:sanctum', 'verified', 'admin'])->group(function () {
    Route::get('/dashboard/kpis', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'kpis'])
        ->middleware('permission:admin.access');

    Route::get('/dashboard/sales-last-12-months', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'salesLast12Months'])
        ->middleware('permission:admin.access');

    Route::get('/settings', [\App\Http\Controllers\Api\Admin\SettingsController::class, 'show'])
        ->middleware('permission:admin.access');

    Route::patch('/settings', [\App\Http\Controllers\Api\Admin\SettingsController::class, 'update'])
        ->middleware('permission:admin.access');

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

    Route::get('/billing/catalog', [\App\Http\Controllers\Api\Admin\BillingCatalogController::class, 'catalog'])
        ->middleware('permission:admin.billing.read');

    Route::get('/billing/intervals', [\App\Http\Controllers\Api\Admin\BillingIntervalController::class, 'index'])
        ->middleware('permission:admin.billing.read');

    Route::post('/billing/intervals', [\App\Http\Controllers\Api\Admin\BillingIntervalController::class, 'store'])
        ->middleware(['throttle:auth', 'permission:admin.billing.write']);

    Route::put('/billing/intervals/{interval}', [\App\Http\Controllers\Api\Admin\BillingIntervalController::class, 'update'])
        ->whereNumber('interval')
        ->middleware('permission:admin.billing.write');

    Route::patch('/billing/intervals/{interval}/active', [\App\Http\Controllers\Api\Admin\BillingIntervalController::class, 'setActive'])
        ->whereNumber('interval')
        ->middleware('permission:admin.billing.write');

    Route::post('/billing/plans', [\App\Http\Controllers\Api\Admin\BillingPlanController::class, 'store'])
        ->middleware(['throttle:auth', 'permission:admin.billing.write']);

    Route::put('/billing/plans/{plan}', [\App\Http\Controllers\Api\Admin\BillingPlanController::class, 'update'])
        ->whereNumber('plan')
        ->middleware('permission:admin.billing.write');

    Route::post('/billing/plans/{plan}/versions/draft-from-active', [\App\Http\Controllers\Api\Admin\BillingPlanVersionController::class, 'createDraftFromActive'])
        ->whereNumber('plan')
        ->middleware('permission:admin.billing.write');
    Route::post('/billing/versions/{version}/validate', [\App\Http\Controllers\Api\Admin\BillingPlanVersionController::class, 'validateDraft'])
        ->whereNumber('version')
        ->middleware('permission:admin.billing.write');
    Route::post('/billing/versions/{version}/entitlements/sync', [\App\Http\Controllers\Api\Admin\BillingPlanVersionController::class, 'syncEntitlements'])
        ->whereNumber('version')
        ->middleware('permission:admin.billing.write');

    Route::post('/billing/versions/{version}/prices', [\App\Http\Controllers\Api\Admin\BillingPlanVersionController::class, 'pricesStore'])
        ->whereNumber('version')
        ->middleware('permission:admin.billing.write');
    Route::patch('/billing/prices/{price}', [\App\Http\Controllers\Api\Admin\BillingPlanVersionController::class, 'pricesUpdate'])
        ->whereNumber('price')
        ->middleware('permission:admin.billing.write');
    Route::delete('/billing/prices/{price}', [\App\Http\Controllers\Api\Admin\BillingPlanVersionController::class, 'pricesDelete'])
        ->whereNumber('price')
        ->middleware('permission:admin.billing.write');

    Route::post('/billing/versions/{version}/activate', [\App\Http\Controllers\Api\Admin\BillingPlanVersionController::class, 'activate'])
        ->whereNumber('version')
        ->middleware('permission:admin.billing.write');
    Route::post('/billing/versions/{version}/retire', [\App\Http\Controllers\Api\Admin\BillingPlanVersionController::class, 'retire'])
        ->whereNumber('version')
        ->middleware('permission:admin.billing.write');

    Route::post('/billing/entitlement-definitions', [\App\Http\Controllers\Api\Admin\BillingEntitlementDefinitionController::class, 'store'])
        ->middleware(['throttle:auth', 'permission:admin.billing.write']);
    Route::put('/billing/entitlement-definitions/{definition}', [\App\Http\Controllers\Api\Admin\BillingEntitlementDefinitionController::class, 'update'])
        ->whereNumber('definition')
        ->middleware('permission:admin.billing.write');
    Route::delete('/billing/entitlement-definitions/{definition}', [\App\Http\Controllers\Api\Admin\BillingEntitlementDefinitionController::class, 'destroy'])
        ->whereNumber('definition')
        ->middleware('permission:admin.billing.write');

    Route::post('/impersonation', [\App\Http\Controllers\Api\Admin\ImpersonationController::class, 'store'])
        ->middleware('permission:admin.impersonation.start');
    Route::post('/impersonation/{session}/stop', [\App\Http\Controllers\Api\Admin\ImpersonationController::class, 'stop'])
        ->whereNumber('session')
        ->middleware('permission:admin.impersonation.stop');

    Route::get('/businesses', [\App\Http\Controllers\Api\Admin\TenantController::class, 'index'])
        ->middleware('permission:admin.tenants.read');
    Route::get('/businesses/stats', [\App\Http\Controllers\Api\Admin\TenantController::class, 'stats'])
        ->middleware('permission:admin.tenants.read');
    Route::get('/businesses/export', [\App\Http\Controllers\Api\Admin\TenantController::class, 'export'])
        ->middleware('permission:admin.tenants.read');
    Route::get('/businesses/{tenant}', [\App\Http\Controllers\Api\Admin\TenantController::class, 'show'])
        ->whereNumber('tenant')
        ->middleware('permission:admin.tenants.read');
    Route::get('/businesses/{tenant}/entitlements', [\App\Http\Controllers\Api\Admin\TenantController::class, 'entitlements'])
        ->whereNumber('tenant')
        ->middleware('permission:admin.tenants.read');
    Route::get('/businesses/{tenant}/audit', [\App\Http\Controllers\Api\Admin\TenantController::class, 'audit'])
        ->whereNumber('tenant')
        ->middleware('permission:admin.tenants.read');
    Route::post('/businesses', [\App\Http\Controllers\Api\Admin\TenantController::class, 'store'])
        ->middleware(['throttle:auth', 'permission:admin.tenants.write']);

    Route::patch('/businesses/{tenant}/suspend', [\App\Http\Controllers\Api\Admin\TenantController::class, 'suspend'])
        ->whereNumber('tenant')
        ->middleware('permission:admin.tenants.write');
    Route::patch('/businesses/{tenant}/unsuspend', [\App\Http\Controllers\Api\Admin\TenantController::class, 'unsuspend'])
        ->whereNumber('tenant')
        ->middleware('permission:admin.tenants.write');
    Route::patch('/businesses/{tenant}/close', [\App\Http\Controllers\Api\Admin\TenantController::class, 'close'])
        ->whereNumber('tenant')
        ->middleware('permission:admin.tenants.write');
    Route::post('/businesses/{tenant}/owner/reset-password', [\App\Http\Controllers\Api\Admin\TenantController::class, 'resetOwnerPassword'])
        ->whereNumber('tenant')
        ->middleware('permission:admin.tenants.write');

    Route::put('/businesses/{tenant}/plan', [\App\Http\Controllers\Api\Admin\TenantController::class, 'setPlan'])
        ->whereNumber('tenant')
        ->middleware('permission:admin.tenants.write');
    Route::put('/businesses/{tenant}/entitlements', [\App\Http\Controllers\Api\Admin\TenantController::class, 'setEntitlementOverrides'])
        ->whereNumber('tenant')
        ->middleware('permission:admin.tenants.write');

    Route::get('/businesses/{tenant}/diagnostics', [\App\Http\Controllers\Api\Admin\TenantDiagnosticsController::class, 'show'])
        ->whereNumber('tenant')
        ->middleware('permission:admin.diagnostics.read');

    Route::get('/businesses/{tenant}/subscriptions', [\App\Http\Controllers\Api\Admin\BillingController::class, 'subscriptionsIndex'])
        ->whereNumber('tenant')
        ->middleware('permission:admin.billing.read');
    Route::post('/businesses/{tenant}/subscriptions', [\App\Http\Controllers\Api\Admin\BillingController::class, 'subscriptionsAssign'])
        ->whereNumber('tenant')
        ->middleware('permission:admin.billing.write');
    Route::post('/businesses/{tenant}/subscriptions/{subscription}/cancel', [\App\Http\Controllers\Api\Admin\BillingController::class, 'subscriptionsCancel'])
        ->whereNumber('tenant')
        ->whereNumber('subscription')
        ->middleware('permission:admin.billing.write');

    Route::get('/businesses/{tenant}/invoices', [\App\Http\Controllers\Api\Admin\BillingController::class, 'invoicesIndex'])
        ->whereNumber('tenant')
        ->middleware('permission:admin.billing.read');
    Route::get('/businesses/{tenant}/invoices/{invoice}', [\App\Http\Controllers\Api\Admin\BillingController::class, 'invoicesShow'])
        ->whereNumber('tenant')
        ->whereNumber('invoice')
        ->middleware('permission:admin.billing.read');
    Route::post('/businesses/{tenant}/invoices', [\App\Http\Controllers\Api\Admin\BillingController::class, 'invoicesCreateFromSubscription'])
        ->whereNumber('tenant')
        ->middleware('permission:admin.billing.write');
    Route::post('/businesses/{tenant}/invoices/{invoice}/issue', [\App\Http\Controllers\Api\Admin\BillingController::class, 'invoicesIssue'])
        ->whereNumber('tenant')
        ->whereNumber('invoice')
        ->middleware('permission:admin.billing.write');
    Route::post('/businesses/{tenant}/invoices/{invoice}/paid', [\App\Http\Controllers\Api\Admin\BillingController::class, 'invoicesMarkPaid'])
        ->whereNumber('tenant')
        ->whereNumber('invoice')
        ->middleware('permission:admin.billing.write');
    Route::get('/businesses/{tenant}/invoices/{invoice}/pdf', [\App\Http\Controllers\Api\Admin\BillingController::class, 'invoicesDownloadPdf'])
        ->whereNumber('tenant')
        ->whereNumber('invoice')
        ->middleware('permission:admin.billing.read');
});

 Route::prefix('{business}')
    ->where(['business' => '[A-Za-z0-9\-]+' ])
    ->middleware(['tenant'])
    ->group(function () {
        Route::prefix('app')->middleware(['auth:sanctum', 'impersonation', 'verified', 'impersonation.audit', 'tenant.member', 'tenant.session', 'mfa.enforce'])->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\Api\App\DashboardController::class, 'show']);

            Route::get('/gate', [\App\Http\Controllers\Api\App\GateController::class, 'show']);

            Route::get('/security-status', [\App\Http\Controllers\Api\App\SecurityStatusController::class, 'show']);

            Route::get('/security-settings', [\App\Http\Controllers\Api\App\TenantSecuritySettingsController::class, 'show'])
                ->middleware('permission:security.manage');
            Route::put('/security-settings', [\App\Http\Controllers\Api\App\TenantSecuritySettingsController::class, 'update'])
                ->middleware(['throttle:auth', 'permission:security.manage']);

            Route::post('/security/force-logout', [\App\Http\Controllers\Api\App\TenantSecurityActionsController::class, 'forceLogout'])
                ->middleware(['throttle:auth', 'permission:security.manage']);

            Route::get('/security/compliance', [\App\Http\Controllers\Api\App\TenantSecurityComplianceController::class, 'show'])
                ->middleware('permission:security.manage');

            Route::get('/audit-events', [\App\Http\Controllers\Api\App\TenantAuditEventsController::class, 'index'])
                ->middleware('permission:security.manage');

            Route::get('/setup', [\App\Http\Controllers\Api\App\SetupController::class, 'show']);
            Route::patch('/setup', [\App\Http\Controllers\Api\App\SetupController::class, 'update']);
            Route::post('/setup/complete', [\App\Http\Controllers\Api\App\SetupController::class, 'complete']);

            Route::get('/settings', [\App\Http\Controllers\Api\App\SettingsController::class, 'show'])
                ->middleware('permission:settings.manage');
            Route::patch('/settings', [\App\Http\Controllers\Api\App\SettingsController::class, 'update'])
                ->middleware('permission:settings.manage');

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
