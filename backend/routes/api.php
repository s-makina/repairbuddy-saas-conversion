<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
    ]);
});

Route::prefix('public')->group(function () {
    Route::get('/billing/plans', [\App\Http\Controllers\Api\Public\BillingPlansController::class, 'index']);
});

Route::prefix('t/{business}')
    ->where(['business' => '[A-Za-z0-9\-]+' ])
    ->middleware(['tenant', 'branch.public'])
    ->group(function () {
        Route::get('/services', [\App\Http\Controllers\Api\Public\RepairBuddyServicesController::class, 'index']);
        Route::get('/parts', [\App\Http\Controllers\Api\Public\RepairBuddyPartsController::class, 'index']);

        Route::prefix('status')->group(function () {
            Route::post('/lookup', [\App\Http\Controllers\Api\Public\RepairBuddyStatusController::class, 'lookup']);
            Route::post('/{caseNumber}/message', [\App\Http\Controllers\Api\Public\RepairBuddyStatusController::class, 'message'])
                ->where(['caseNumber' => '[A-Za-z0-9\-_]+' ]);
        });

        Route::prefix('portal')->group(function () {
            Route::get('/tickets', [\App\Http\Controllers\Api\Public\RepairBuddyPortalTicketsController::class, 'index']);
            Route::get('/tickets/{jobId}', [\App\Http\Controllers\Api\Public\RepairBuddyPortalTicketsController::class, 'show'])
                ->whereNumber('jobId');
            Route::get('/devices', [\App\Http\Controllers\Api\Public\RepairBuddyPortalDevicesController::class, 'index']);
            Route::get('/job-devices', [\App\Http\Controllers\Api\Public\RepairBuddyPortalJobDevicesController::class, 'index']);
            Route::get('/estimates', [\App\Http\Controllers\Api\Public\RepairBuddyPortalEstimatesController::class, 'index']);
            Route::get('/estimates/{estimateId}', [\App\Http\Controllers\Api\Public\RepairBuddyPortalEstimatesController::class, 'show'])
                ->whereNumber('estimateId');
        });

        Route::prefix('estimates')->group(function () {
            Route::get('/{caseNumber}/approve', [\App\Http\Controllers\Api\Public\RepairBuddyEstimateActionsController::class, 'approve'])
                ->where(['caseNumber' => '[A-Za-z0-9\-_]+' ]);
            Route::get('/{caseNumber}/reject', [\App\Http\Controllers\Api\Public\RepairBuddyEstimateActionsController::class, 'reject'])
                ->where(['caseNumber' => '[A-Za-z0-9\-_]+' ]);
        });
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
        Route::post('/password/change', [\App\Http\Controllers\Api\AuthController::class, 'changePassword'])->middleware('impersonation');

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
});

Route::prefix('{business}')
    ->where(['business' => '[A-Za-z0-9\-]+' ])
    ->middleware(['tenant'])
    ->group(function () {
        Route::prefix('app')
            ->middleware(['auth:sanctum', 'impersonation', 'verified', 'password.change', 'impersonation.audit', 'tenant.member', 'tenant.session', 'mfa.enforce', 'onboarding.gate'])
            ->group(function () {
                Route::get('/gate', [\App\Http\Controllers\Api\App\GateController::class, 'show']);

                Route::get('/billing/plans', [\App\Http\Controllers\Api\App\BillingOnboardingController::class, 'plans']);
                Route::post('/billing/subscribe', [\App\Http\Controllers\Api\App\BillingOnboardingController::class, 'subscribe']);
                Route::get('/billing/checkout', [\App\Http\Controllers\Api\App\BillingOnboardingController::class, 'checkout']);
                Route::post('/billing/checkout/confirm', [\App\Http\Controllers\Api\App\BillingOnboardingController::class, 'confirmCheckout']);

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

            Route::get('/branches', [\App\Http\Controllers\Api\App\BranchController::class, 'index']);
            Route::get('/branches/current', [\App\Http\Controllers\Api\App\BranchController::class, 'current']);
            Route::post('/branches/active', [\App\Http\Controllers\Api\App\BranchController::class, 'setActive']);

            Route::post('/branches', [\App\Http\Controllers\Api\App\BranchController::class, 'store'])
                ->middleware('permission:branches.manage');
            Route::get('/branches/{branch}', [\App\Http\Controllers\Api\App\BranchController::class, 'show'])
                ->whereNumber('branch')
                ->middleware('permission:branches.manage');
            Route::put('/branches/{branch}', [\App\Http\Controllers\Api\App\BranchController::class, 'update'])
                ->whereNumber('branch')
                ->middleware('permission:branches.manage');
            Route::post('/branches/{branch}/assign-users', [\App\Http\Controllers\Api\App\BranchController::class, 'assignUsers'])
                ->whereNumber('branch')
                ->middleware('permission:branches.manage');
            Route::get('/branches/{branch}/users', [\App\Http\Controllers\Api\App\BranchController::class, 'users'])
                ->whereNumber('branch')
                ->middleware('permission:branches.manage');
            Route::post('/branches/{branch}/default', [\App\Http\Controllers\Api\App\BranchController::class, 'setDefault'])
                ->whereNumber('branch')
                ->middleware('permission:branches.manage');

            Route::middleware(['branch.active'])->group(function () {
                Route::get('/dashboard', [\App\Http\Controllers\Api\App\DashboardController::class, 'show']);

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
                Route::patch('/users/{user}/shop', [\App\Http\Controllers\Api\App\UserController::class, 'updateShop'])
                    ->whereNumber('user')
                    ->middleware('permission:users.manage');
                Route::patch('/users/{user}/role', [\App\Http\Controllers\Api\App\UserController::class, 'updateRole'])
                    ->middleware('permission:users.manage');
                Route::patch('/users/{user}/status', [\App\Http\Controllers\Api\App\UserController::class, 'updateStatus'])
                    ->middleware('permission:users.manage');
                Route::post('/users/{user}/reset-password', [\App\Http\Controllers\Api\App\UserController::class, 'sendPasswordResetLink'])
                    ->middleware('permission:users.manage');

                Route::get('/technicians', [\App\Http\Controllers\Api\App\TechnicianController::class, 'index'])
                    ->middleware('permission:technicians.view');

                Route::post('/technicians', [\App\Http\Controllers\Api\App\TechnicianController::class, 'store'])
                    ->middleware('permission:users.manage');

                Route::prefix('clients')->middleware('permission:clients.view')->group(function () {
                    Route::get('/', [\App\Http\Controllers\Api\App\ClientController::class, 'index']);
                    Route::post('/', [\App\Http\Controllers\Api\App\ClientController::class, 'store']);
                    Route::get('/{client}', [\App\Http\Controllers\Api\App\ClientController::class, 'show'])->whereNumber('client');
                    Route::put('/{client}', [\App\Http\Controllers\Api\App\ClientController::class, 'update'])->whereNumber('client');
                    Route::delete('/{client}', [\App\Http\Controllers\Api\App\ClientController::class, 'destroy'])->whereNumber('client');
                    Route::get('/{client}/jobs', [\App\Http\Controllers\Api\App\ClientController::class, 'jobs'])->whereNumber('client');
                });

                Route::prefix('repairbuddy')->group(function () {
                    Route::get('/settings', [\App\Http\Controllers\Api\App\RepairBuddySettingsController::class, 'show'])
                        ->middleware('permission:settings.manage');
                    Route::patch('/settings', [\App\Http\Controllers\Api\App\RepairBuddySettingsController::class, 'update'])
                        ->middleware(['throttle:auth', 'permission:settings.manage']);

                    Route::get('/device-types', [\App\Http\Controllers\Api\App\RepairBuddyDeviceTypeController::class, 'index'])
                        ->middleware('permission:device_types.view');
                    Route::post('/device-types', [\App\Http\Controllers\Api\App\RepairBuddyDeviceTypeController::class, 'store'])
                        ->middleware('permission:device_types.manage');

                    Route::patch('/device-types/{typeId}', [\App\Http\Controllers\Api\App\RepairBuddyDeviceTypeController::class, 'update'])
                        ->middleware('permission:device_types.manage')
                        ->whereNumber('typeId');

                    Route::post('/device-types/{typeId}/image', [\App\Http\Controllers\Api\App\RepairBuddyDeviceTypeController::class, 'uploadImage'])
                        ->middleware('permission:device_types.manage')
                        ->whereNumber('typeId');

                    Route::delete('/device-types/{typeId}/image', [\App\Http\Controllers\Api\App\RepairBuddyDeviceTypeController::class, 'deleteImage'])
                        ->middleware('permission:device_types.manage')
                        ->whereNumber('typeId');

                    Route::delete('/device-types/{typeId}', [\App\Http\Controllers\Api\App\RepairBuddyDeviceTypeController::class, 'destroy'])
                        ->middleware('permission:device_types.manage')
                        ->whereNumber('typeId');

                    Route::get('/device-brands', [\App\Http\Controllers\Api\App\RepairBuddyDeviceBrandController::class, 'index'])
                        ->middleware('permission:device_brands.view');
                    Route::post('/device-brands', [\App\Http\Controllers\Api\App\RepairBuddyDeviceBrandController::class, 'store'])
                        ->middleware('permission:device_brands.manage');

                    Route::patch('/device-brands/{brandId}', [\App\Http\Controllers\Api\App\RepairBuddyDeviceBrandController::class, 'update'])
                        ->middleware('permission:device_brands.manage')
                        ->whereNumber('brandId');

                    Route::post('/device-brands/{brandId}/image', [\App\Http\Controllers\Api\App\RepairBuddyDeviceBrandController::class, 'uploadImage'])
                        ->middleware('permission:device_brands.manage')
                        ->whereNumber('brandId');

                    Route::delete('/device-brands/{brandId}/image', [\App\Http\Controllers\Api\App\RepairBuddyDeviceBrandController::class, 'deleteImage'])
                        ->middleware('permission:device_brands.manage')
                        ->whereNumber('brandId');

                    Route::delete('/device-brands/{brandId}', [\App\Http\Controllers\Api\App\RepairBuddyDeviceBrandController::class, 'destroy'])
                        ->middleware('permission:device_brands.manage')
                        ->whereNumber('brandId');

                    Route::get('/devices', [\App\Http\Controllers\Api\App\RepairBuddyDeviceController::class, 'index'])
                        ->middleware('permission:devices.view');
                    Route::post('/devices', [\App\Http\Controllers\Api\App\RepairBuddyDeviceController::class, 'store'])
                        ->middleware('permission:devices.manage');

                    Route::patch('/devices/{deviceId}', [\App\Http\Controllers\Api\App\RepairBuddyDeviceController::class, 'update'])
                        ->middleware('permission:devices.manage')
                        ->whereNumber('deviceId');

                    Route::delete('/devices/{deviceId}', [\App\Http\Controllers\Api\App\RepairBuddyDeviceController::class, 'destroy'])
                        ->middleware('permission:devices.manage')
                        ->whereNumber('deviceId');

                    Route::post('/devices/{deviceId}/variations', [\App\Http\Controllers\Api\App\RepairBuddyDeviceController::class, 'createVariations'])
                        ->middleware('permission:devices.manage')
                        ->whereNumber('deviceId');

                    Route::get('/part-types', [\App\Http\Controllers\Api\App\RepairBuddyPartTypeController::class, 'index'])
                        ->middleware('permission:parts.view');
                    Route::post('/part-types', [\App\Http\Controllers\Api\App\RepairBuddyPartTypeController::class, 'store'])
                        ->middleware('permission:parts.manage');

                    Route::patch('/part-types/{typeId}', [\App\Http\Controllers\Api\App\RepairBuddyPartTypeController::class, 'update'])
                        ->middleware('permission:parts.manage')
                        ->whereNumber('typeId');

                    Route::post('/part-types/{typeId}/image', [\App\Http\Controllers\Api\App\RepairBuddyPartTypeController::class, 'uploadImage'])
                        ->middleware('permission:parts.manage')
                        ->whereNumber('typeId');

                    Route::delete('/part-types/{typeId}/image', [\App\Http\Controllers\Api\App\RepairBuddyPartTypeController::class, 'deleteImage'])
                        ->middleware('permission:parts.manage')
                        ->whereNumber('typeId');

                    Route::delete('/part-types/{typeId}', [\App\Http\Controllers\Api\App\RepairBuddyPartTypeController::class, 'destroy'])
                        ->middleware('permission:parts.manage')
                        ->whereNumber('typeId');

                    Route::get('/part-brands', [\App\Http\Controllers\Api\App\RepairBuddyPartBrandController::class, 'index'])
                        ->middleware('permission:parts.view');
                    Route::post('/part-brands', [\App\Http\Controllers\Api\App\RepairBuddyPartBrandController::class, 'store'])
                        ->middleware('permission:parts.manage');

                    Route::patch('/part-brands/{brandId}', [\App\Http\Controllers\Api\App\RepairBuddyPartBrandController::class, 'update'])
                        ->middleware('permission:parts.manage')
                        ->whereNumber('brandId');

                    Route::post('/part-brands/{brandId}/image', [\App\Http\Controllers\Api\App\RepairBuddyPartBrandController::class, 'uploadImage'])
                        ->middleware('permission:parts.manage')
                        ->whereNumber('brandId');

                    Route::delete('/part-brands/{brandId}/image', [\App\Http\Controllers\Api\App\RepairBuddyPartBrandController::class, 'deleteImage'])
                        ->middleware('permission:parts.manage')
                        ->whereNumber('brandId');

                    Route::delete('/part-brands/{brandId}', [\App\Http\Controllers\Api\App\RepairBuddyPartBrandController::class, 'destroy'])
                        ->middleware('permission:parts.manage')
                        ->whereNumber('brandId');

                    Route::get('/parts', [\App\Http\Controllers\Api\App\RepairBuddyPartController::class, 'index'])
                        ->middleware('permission:parts.view');
                    Route::post('/parts', [\App\Http\Controllers\Api\App\RepairBuddyPartController::class, 'store'])
                        ->middleware('permission:parts.manage');

                    Route::get('/parts/{partId}', [\App\Http\Controllers\Api\App\RepairBuddyPartController::class, 'show'])
                        ->middleware('permission:parts.view')
                        ->whereNumber('partId');

                    Route::patch('/parts/{partId}', [\App\Http\Controllers\Api\App\RepairBuddyPartController::class, 'update'])
                        ->middleware('permission:parts.manage')
                        ->whereNumber('partId');

                    Route::delete('/parts/{partId}', [\App\Http\Controllers\Api\App\RepairBuddyPartController::class, 'destroy'])
                        ->middleware('permission:parts.manage')
                        ->whereNumber('partId');

                    Route::post('/parts/resolve-price', [\App\Http\Controllers\Api\App\RepairBuddyPartController::class, 'resolvePrice'])
                        ->middleware('permission:parts.view');

                    Route::get('/part-variants', [\App\Http\Controllers\Api\App\RepairBuddyPartVariantController::class, 'index'])
                        ->middleware('permission:parts.view');
                    Route::post('/part-variants', [\App\Http\Controllers\Api\App\RepairBuddyPartVariantController::class, 'store'])
                        ->middleware('permission:parts.manage');
                    Route::patch('/part-variants/{variantId}', [\App\Http\Controllers\Api\App\RepairBuddyPartVariantController::class, 'update'])
                        ->middleware('permission:parts.manage')
                        ->whereNumber('variantId');
                    Route::delete('/part-variants/{variantId}', [\App\Http\Controllers\Api\App\RepairBuddyPartVariantController::class, 'destroy'])
                        ->middleware('permission:parts.manage')
                        ->whereNumber('variantId');

                    Route::get('/part-price-overrides', [\App\Http\Controllers\Api\App\RepairBuddyPartPriceOverrideController::class, 'index'])
                        ->middleware('permission:parts.view');
                    Route::post('/part-price-overrides', [\App\Http\Controllers\Api\App\RepairBuddyPartPriceOverrideController::class, 'store'])
                        ->middleware('permission:parts.manage');
                    Route::patch('/part-price-overrides/{overrideId}', [\App\Http\Controllers\Api\App\RepairBuddyPartPriceOverrideController::class, 'update'])
                        ->middleware('permission:parts.manage')
                        ->whereNumber('overrideId');
                    Route::delete('/part-price-overrides/{overrideId}', [\App\Http\Controllers\Api\App\RepairBuddyPartPriceOverrideController::class, 'destroy'])
                        ->middleware('permission:parts.manage')
                        ->whereNumber('overrideId');
                });

                Route::prefix('repairbuddy')->middleware('permission:jobs.view')->group(function () {
                    Route::get('/job-statuses', [\App\Http\Controllers\Api\App\RepairBuddyJobStatusController::class, 'index']);
                    Route::patch('/job-statuses/{slug}', [\App\Http\Controllers\Api\App\RepairBuddyJobStatusController::class, 'updateDisplay'])
                        ->middleware(['throttle:auth', 'permission:settings.manage'])
                        ->where(['slug' => '[A-Za-z0-9\-_]+' ]);
                    Route::get('/payment-statuses', [\App\Http\Controllers\Api\App\RepairBuddyPaymentStatusController::class, 'index']);
                    Route::patch('/payment-statuses/{slug}', [\App\Http\Controllers\Api\App\RepairBuddyPaymentStatusController::class, 'updateDisplay'])
                        ->middleware(['throttle:auth', 'permission:settings.manage'])
                        ->where(['slug' => '[A-Za-z0-9\-_]+' ]);

                    Route::get('/taxes', [\App\Http\Controllers\Api\App\RepairBuddyTaxController::class, 'index']);
                    Route::post('/taxes', [\App\Http\Controllers\Api\App\RepairBuddyTaxController::class, 'store']);

                    Route::get('/jobs', [\App\Http\Controllers\Api\App\RepairBuddyJobController::class, 'index']);
                    Route::post('/jobs', [\App\Http\Controllers\Api\App\RepairBuddyJobController::class, 'store']);
                    Route::get('/jobs/{jobId}', [\App\Http\Controllers\Api\App\RepairBuddyJobController::class, 'show'])->whereNumber('jobId');
                    Route::patch('/jobs/{jobId}', [\App\Http\Controllers\Api\App\RepairBuddyJobController::class, 'update'])->whereNumber('jobId');

                    Route::post('/jobs/{jobId}/items', [\App\Http\Controllers\Api\App\RepairBuddyJobItemController::class, 'store'])->whereNumber('jobId');
                    Route::delete('/jobs/{jobId}/items/{itemId}', [\App\Http\Controllers\Api\App\RepairBuddyJobItemController::class, 'destroy'])
                        ->whereNumber('jobId')
                        ->whereNumber('itemId');

                    Route::get('/jobs/{jobId}/events', [\App\Http\Controllers\Api\App\RepairBuddyJobEventController::class, 'index'])->whereNumber('jobId');
                    Route::post('/jobs/{jobId}/events', [\App\Http\Controllers\Api\App\RepairBuddyJobEventController::class, 'store'])->whereNumber('jobId');

                    Route::get('/jobs/{jobId}/devices', [\App\Http\Controllers\Api\App\RepairBuddyJobDeviceController::class, 'index'])->whereNumber('jobId');
                    Route::post('/jobs/{jobId}/devices', [\App\Http\Controllers\Api\App\RepairBuddyJobDeviceController::class, 'store'])->whereNumber('jobId');
                    Route::delete('/jobs/{jobId}/devices/{jobDeviceId}', [\App\Http\Controllers\Api\App\RepairBuddyJobDeviceController::class, 'destroy'])
                        ->whereNumber('jobId')
                        ->whereNumber('jobDeviceId');
                });

                Route::prefix('repairbuddy')->middleware('permission:estimates.view')->group(function () {
                    Route::get('/estimates', [\App\Http\Controllers\Api\App\RepairBuddyEstimateController::class, 'index']);
                    Route::get('/estimates/{estimateId}', [\App\Http\Controllers\Api\App\RepairBuddyEstimateController::class, 'show'])->whereNumber('estimateId');
                });

                Route::prefix('repairbuddy')->middleware('permission:estimates.manage')->group(function () {
                    Route::post('/estimates', [\App\Http\Controllers\Api\App\RepairBuddyEstimateController::class, 'store']);
                    Route::patch('/estimates/{estimateId}', [\App\Http\Controllers\Api\App\RepairBuddyEstimateController::class, 'update'])->whereNumber('estimateId');
                    Route::post('/estimates/{estimateId}/send', [\App\Http\Controllers\Api\App\RepairBuddyEstimateController::class, 'send'])->whereNumber('estimateId');

                    Route::post('/estimates/{estimateId}/items', [\App\Http\Controllers\Api\App\RepairBuddyEstimateItemController::class, 'store'])->whereNumber('estimateId');
                    Route::delete('/estimates/{estimateId}/items/{itemId}', [\App\Http\Controllers\Api\App\RepairBuddyEstimateItemController::class, 'destroy'])
                        ->whereNumber('estimateId')
                        ->whereNumber('itemId');

                    Route::get('/estimates/{estimateId}/devices', [\App\Http\Controllers\Api\App\RepairBuddyEstimateDeviceController::class, 'index'])->whereNumber('estimateId');
                    Route::post('/estimates/{estimateId}/devices', [\App\Http\Controllers\Api\App\RepairBuddyEstimateDeviceController::class, 'store'])->whereNumber('estimateId');
                    Route::delete('/estimates/{estimateId}/devices/{estimateDeviceId}', [\App\Http\Controllers\Api\App\RepairBuddyEstimateDeviceController::class, 'destroy'])
                        ->whereNumber('estimateId')
                        ->whereNumber('estimateDeviceId');
                });

                Route::prefix('repairbuddy')->middleware('permission:services.view')->group(function () {
                    Route::get('/services', [\App\Http\Controllers\Api\App\RepairBuddyServiceController::class, 'index']);
                    Route::post('/services/resolve-price', [\App\Http\Controllers\Api\App\RepairBuddyServicePricingController::class, 'resolvePrice']);
                });

                Route::prefix('repairbuddy')->middleware('permission:service_types.view')->group(function () {
                    Route::get('/service-types', [\App\Http\Controllers\Api\App\RepairBuddyServiceTypeController::class, 'index']);
                });

                Route::prefix('repairbuddy')->middleware('permission:services.manage')->group(function () {
                    Route::post('/services', [\App\Http\Controllers\Api\App\RepairBuddyServiceController::class, 'store']);
                    Route::patch('/services/{serviceId}', [\App\Http\Controllers\Api\App\RepairBuddyServiceController::class, 'update'])->whereNumber('serviceId');
                    Route::delete('/services/{serviceId}', [\App\Http\Controllers\Api\App\RepairBuddyServiceController::class, 'destroy'])->whereNumber('serviceId');
                });

                Route::prefix('repairbuddy')->middleware('permission:service_types.manage')->group(function () {
                    Route::post('/service-types', [\App\Http\Controllers\Api\App\RepairBuddyServiceTypeController::class, 'store']);
                    Route::patch('/service-types/{typeId}', [\App\Http\Controllers\Api\App\RepairBuddyServiceTypeController::class, 'update'])->whereNumber('typeId');
                    Route::delete('/service-types/{typeId}', [\App\Http\Controllers\Api\App\RepairBuddyServiceTypeController::class, 'destroy'])->whereNumber('typeId');
                });

                Route::prefix('repairbuddy')->middleware('permission:settings.manage')->group(function () {
                    Route::get('/device-field-definitions', [\App\Http\Controllers\Api\App\RepairBuddyDeviceFieldDefinitionController::class, 'index']);
                    Route::post('/device-field-definitions', [\App\Http\Controllers\Api\App\RepairBuddyDeviceFieldDefinitionController::class, 'store']);
                    Route::patch('/device-field-definitions/{definitionId}', [\App\Http\Controllers\Api\App\RepairBuddyDeviceFieldDefinitionController::class, 'update'])
                        ->whereNumber('definitionId');
                    Route::delete('/device-field-definitions/{definitionId}', [\App\Http\Controllers\Api\App\RepairBuddyDeviceFieldDefinitionController::class, 'destroy'])
                        ->whereNumber('definitionId');

                    Route::get('/service-price-overrides', [\App\Http\Controllers\Api\App\RepairBuddyServicePriceOverrideController::class, 'index']);
                    Route::post('/service-price-overrides', [\App\Http\Controllers\Api\App\RepairBuddyServicePriceOverrideController::class, 'store']);
                    Route::patch('/service-price-overrides/{overrideId}', [\App\Http\Controllers\Api\App\RepairBuddyServicePriceOverrideController::class, 'update'])
                        ->whereNumber('overrideId');
                    Route::delete('/service-price-overrides/{overrideId}', [\App\Http\Controllers\Api\App\RepairBuddyServicePriceOverrideController::class, 'destroy'])
                        ->whereNumber('overrideId');
                });

                Route::prefix('repairbuddy')->middleware('permission:customer_devices.view')->group(function () {
                    Route::get('/customer-devices', [\App\Http\Controllers\Api\App\RepairBuddyCustomerDeviceController::class, 'index']);
                });

                Route::prefix('repairbuddy')->middleware('permission:customer_devices.manage')->group(function () {
                    Route::post('/customer-devices', [\App\Http\Controllers\Api\App\RepairBuddyCustomerDeviceController::class, 'store']);

                    Route::patch('/customer-devices/{customerDeviceId}', [\App\Http\Controllers\Api\App\RepairBuddyCustomerDeviceController::class, 'update'])
                        ->whereNumber('customerDeviceId');

                    Route::delete('/customer-devices/{customerDeviceId}', [\App\Http\Controllers\Api\App\RepairBuddyCustomerDeviceController::class, 'destroy'])
                        ->whereNumber('customerDeviceId');

                    Route::get('/customer-devices/{customerDeviceId}/extra-fields', [\App\Http\Controllers\Api\App\RepairBuddyCustomerDeviceExtraFieldsController::class, 'index'])
                        ->whereNumber('customerDeviceId');
                    Route::put('/customer-devices/{customerDeviceId}/extra-fields', [\App\Http\Controllers\Api\App\RepairBuddyCustomerDeviceExtraFieldsController::class, 'update'])
                        ->whereNumber('customerDeviceId');
                });
            });
        });
    });
