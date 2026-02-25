<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('web')->group(function () {
    Route::get('/login', [\App\Http\Controllers\Web\AuthController::class, 'showLogin'])
        ->middleware('guest')
        ->name('web.login');

    Route::post('/login', [\App\Http\Controllers\Web\AuthController::class, 'login'])
        ->middleware('guest');

    Route::post('/logout', [\App\Http\Controllers\Web\AuthController::class, 'logout'])
        ->middleware('auth')
        ->name('web.logout');
});

Route::get('/email/verify/{id}/{hash}', function (Request $request, string $id, string $hash) {
    $user = User::query()->findOrFail($id);

    if (! hash_equals(sha1($user->getEmailForVerification()), (string) $hash)) {
        abort(403);
    }

    if (! $user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
        event(new Verified($user));
    }

    $frontendUrl = rtrim((string) env('FRONTEND_URL', 'http://localhost:3000'), '/');

    return redirect()->away($frontendUrl.'/verify-email?verified=1');
})->middleware(['signed'])->name('verification.verify');

// Public pages (no auth required)
Route::prefix('t/{business}')
    ->where(['business' => '[A-Za-z0-9\-]+' ])
    ->middleware(['web', 'tenant', 'branch.public'])
    ->group(function () {
        Route::get('/book', [\App\Http\Controllers\Web\TenantBookingController::class, 'show'])
            ->name('tenant.booking.show');

        Route::get('/status', [\App\Http\Controllers\Web\TenantStatusController::class, 'show'])
            ->name('tenant.status.show');

        Route::get('/my-account', [\App\Http\Controllers\Web\TenantPublicPageController::class, 'myaccount'])
            ->name('tenant.myaccount');

        Route::get('/services', [\App\Http\Controllers\Web\TenantPublicPageController::class, 'services'])
            ->name('tenant.services');

        Route::get('/parts', [\App\Http\Controllers\Web\TenantPublicPageController::class, 'parts'])
            ->name('tenant.parts');

        Route::get('/review', [\App\Http\Controllers\Web\TenantPublicPageController::class, 'review'])
            ->name('tenant.review');

        // Public signature pages (no auth required — customer facing)
        Route::get('/signature/{verification}', [\App\Http\Controllers\Web\SignatureController::class, 'signatureRequest'])
            ->name('tenant.signature.request');

        Route::post('/signature/{verification}/submit', [\App\Http\Controllers\Web\SignatureController::class, 'submitSignature'])
            ->name('tenant.signature.submit');

        Route::get('/signature/{verification}/success', [\App\Http\Controllers\Web\SignatureController::class, 'success'])
            ->name('tenant.signature.success');
    });

// ──────────────────────────────────────────────────────────────
// Customer Portal (auth required, single page with tab sections)
// ──────────────────────────────────────────────────────────────
Route::prefix('t/{business}/portal')
    ->where(['business' => '[A-Za-z0-9\-]+' ])
    ->middleware(['web', 'tenant', 'branch.public', 'auth'])
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\Web\CustomerDashboardController::class, 'portal'])
            ->name('tenant.customer.portal');

        Route::post('/account', [\App\Http\Controllers\Web\CustomerDashboardController::class, 'updateAccount'])
            ->name('tenant.customer.account.update');
    });

Route::prefix('t/{business}')
    ->where(['business' => '[A-Za-z0-9\-]+' ])
    ->middleware(['web', 'tenant', 'branch.web', 'auth'])
    ->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Web\TenantDashboardController::class, 'show'])
            ->name('tenant.dashboard');

        Route::get('/settings', [\App\Http\Controllers\Web\TenantSettingsController::class, 'show'])
            ->name('tenant.settings');

        Route::get('/settings/v2', [\App\Http\Controllers\Web\TenantSettingsController::class, 'showV2'])
            ->name('tenant.settings.v2');

        Route::get('/settings/users', [\App\Http\Controllers\Web\Settings\UsersController::class, 'index'])
            ->middleware('permission:users.manage')
            ->name('tenant.settings.users.index');

        Route::get('/settings/shops', [\App\Http\Controllers\Web\Settings\BranchesController::class, 'index'])
            ->middleware('permission:branches.manage')
            ->name('tenant.settings.shops.index');

        Route::get('/settings/shops/new', [\App\Http\Controllers\Web\Settings\BranchesController::class, 'create'])
            ->middleware('permission:branches.manage')
            ->name('tenant.settings.shops.create');

        Route::get('/settings/shops/{branch}/edit', [\App\Http\Controllers\Web\Settings\BranchesController::class, 'edit'])
            ->where(['branch' => '[0-9]+' ])
            ->middleware('permission:branches.manage')
            ->name('tenant.settings.shops.edit');

        Route::post('/settings/shops', [\App\Http\Controllers\Web\Settings\BranchesController::class, 'store'])
            ->middleware('permission:branches.manage')
            ->name('tenant.settings.shops.store');

        Route::post('/settings/shops/{branch}/update', [\App\Http\Controllers\Web\Settings\BranchesController::class, 'update'])
            ->where(['branch' => '[0-9]+' ])
            ->middleware('permission:branches.manage')
            ->name('tenant.settings.shops.update');

        Route::post('/settings/shops/{branch}/active', [\App\Http\Controllers\Web\Settings\BranchesController::class, 'setActive'])
            ->where(['branch' => '[0-9]+' ])
            ->middleware('permission:branches.manage')
            ->name('tenant.settings.shops.active');

        Route::post('/settings/shops/{branch}/default', [\App\Http\Controllers\Web\Settings\BranchesController::class, 'setDefault'])
            ->where(['branch' => '[0-9]+' ])
            ->middleware('permission:branches.manage')
            ->name('tenant.settings.shops.default');

        Route::post('/branch/active', [\App\Http\Controllers\Web\TenantBranchController::class, 'setActive'])
            ->name('tenant.branch.active');

        Route::get('/settings/users/datatable', [\App\Http\Controllers\Web\Settings\UsersController::class, 'datatable'])
            ->middleware('permission:users.manage')
            ->name('tenant.settings.users.datatable');

        Route::get('/settings/users/new', [\App\Http\Controllers\Web\Settings\UsersController::class, 'create'])
            ->middleware('permission:users.manage')
            ->name('tenant.settings.users.create');

        Route::get('/settings/users/{user}/edit', [\App\Http\Controllers\Web\Settings\UsersController::class, 'edit'])
            ->where(['user' => '[0-9]+' ])
            ->middleware('permission:users.manage')
            ->name('tenant.settings.users.edit');

        Route::post('/settings/users', [\App\Http\Controllers\Web\Settings\UsersController::class, 'store'])
            ->middleware('permission:users.manage')
            ->name('tenant.settings.users.store');

        Route::post('/settings/users/{user}/update', [\App\Http\Controllers\Web\Settings\UsersController::class, 'update'])
            ->where(['user' => '[0-9]+' ])
            ->middleware('permission:users.manage')
            ->name('tenant.settings.users.update');

        Route::post('/settings/users/{user}/status', [\App\Http\Controllers\Web\Settings\UsersController::class, 'setStatus'])
            ->where(['user' => '[0-9]+' ])
            ->middleware('permission:users.manage')
            ->name('tenant.settings.users.status');

        Route::post('/settings/users/{user}/password-reset', [\App\Http\Controllers\Web\Settings\UsersController::class, 'sendPasswordReset'])
            ->where(['user' => '[0-9]+' ])
            ->middleware('permission:users.manage')
            ->name('tenant.settings.users.password_reset');

        Route::post('/settings/users/{user}/delete', [\App\Http\Controllers\Web\Settings\UsersController::class, 'delete'])
            ->where(['user' => '[0-9]+' ])
            ->middleware('permission:users.manage')
            ->name('tenant.settings.users.delete');

        Route::get('/technicians', [\App\Http\Controllers\Web\TechniciansController::class, 'index'])
            ->middleware('permission:technicians.view')
            ->name('tenant.technicians.index');

        Route::post('/technicians', [\App\Http\Controllers\Web\TechniciansController::class, 'store'])
            ->middleware('permission:users.manage')
            ->name('tenant.technicians.store');

        Route::get('/technicians/datatable', [\App\Http\Controllers\Web\TechniciansController::class, 'datatable'])
            ->middleware('permission:technicians.view')
            ->name('tenant.technicians.datatable');

        Route::get('/managers', [\App\Http\Controllers\Web\ManagersController::class, 'index'])
            ->middleware('permission:managers.view')
            ->name('tenant.managers.index');

        Route::get('/managers/datatable', [\App\Http\Controllers\Web\ManagersController::class, 'datatable'])
            ->middleware('permission:managers.view')
            ->name('tenant.managers.datatable');

        Route::post('/technicians/{user}/hourly-rates', [\App\Http\Controllers\Web\TechniciansController::class, 'updateHourlyRates'])
            ->where(['user' => '[0-9]+' ])
            ->middleware('permission:hourly_rates.view')
            ->name('tenant.technicians.hourly_rates.update');

        Route::get('/settings/hourly-rates', [\App\Http\Controllers\Web\Settings\HourlyRatesController::class, 'index'])
            ->middleware('permission:hourly_rates.view')
            ->name('tenant.settings.hourly_rates.index');

        Route::post('/settings/hourly-rates/{user}/update', [\App\Http\Controllers\Web\Settings\HourlyRatesController::class, 'update'])
            ->where(['user' => '[0-9]+' ])
            ->middleware('permission:hourly_rates.view')
            ->name('tenant.settings.hourly_rates.update');

        Route::get('/settings/roles', [\App\Http\Controllers\Web\Settings\RolesController::class, 'index'])
            ->middleware('permission:roles.manage')
            ->name('tenant.settings.roles.index');

        Route::get('/settings/roles/datatable', [\App\Http\Controllers\Web\Settings\RolesController::class, 'datatable'])
            ->middleware('permission:roles.manage')
            ->name('tenant.settings.roles.datatable');

        Route::get('/settings/roles/new', [\App\Http\Controllers\Web\Settings\RolesController::class, 'create'])
            ->middleware('permission:roles.manage')
            ->name('tenant.settings.roles.create');

        Route::get('/settings/roles/{role}/edit', [\App\Http\Controllers\Web\Settings\RolesController::class, 'edit'])
            ->where(['role' => '[0-9]+' ])
            ->middleware('permission:roles.manage')
            ->name('tenant.settings.roles.edit');

        Route::post('/settings/roles', [\App\Http\Controllers\Web\Settings\RolesController::class, 'store'])
            ->middleware('permission:roles.manage')
            ->name('tenant.settings.roles.store');

        Route::post('/settings/roles/{role}/update', [\App\Http\Controllers\Web\Settings\RolesController::class, 'update'])
            ->where(['role' => '[0-9]+' ])
            ->middleware('permission:roles.manage')
            ->name('tenant.settings.roles.update');

        Route::post('/settings/roles/{role}/delete', [\App\Http\Controllers\Web\Settings\RolesController::class, 'delete'])
            ->where(['role' => '[0-9]+' ])
            ->middleware('permission:roles.manage')
            ->name('tenant.settings.roles.delete');

        Route::get('/settings/roles/permissions', [\App\Http\Controllers\Web\Settings\RolesController::class, 'permissionsIndex'])
            ->middleware('permission:roles.manage')
            ->name('tenant.settings.roles.permissions.index');

        Route::get('/settings/roles/{role}/permissions', [\App\Http\Controllers\Web\Settings\RolesController::class, 'rolePermissionsShow'])
            ->where(['role' => '[0-9]+' ])
            ->middleware('permission:roles.manage')
            ->name('tenant.settings.roles.permissions.show');

        Route::post('/settings/roles/{role}/permissions/sync', [\App\Http\Controllers\Web\Settings\RolesController::class, 'rolePermissionsSync'])
            ->where(['role' => '[0-9]+' ])
            ->middleware('permission:roles.manage')
            ->name('tenant.settings.roles.permissions.sync');

        Route::get('/settings/permissions', [\App\Http\Controllers\Web\Settings\PermissionsController::class, 'index'])
            ->middleware('permission:roles.manage')
            ->name('tenant.settings.permissions.index');

        Route::get('/settings/permissions/datatable', [\App\Http\Controllers\Web\Settings\PermissionsController::class, 'datatable'])
            ->middleware('permission:roles.manage')
            ->name('tenant.settings.permissions.datatable');

        Route::get('/settings/{section}', [\App\Http\Controllers\Web\TenantSettingsController::class, 'section'])
            ->where(['section' => '[A-Za-z0-9\-]+' ])
            ->name('tenant.settings.section');

        Route::get('/operations/brands', [\App\Http\Controllers\Web\Operations\DeviceBrandOperationsController::class, 'index'])
            ->name('tenant.operations.brands.index');

        Route::get('/operations/brands/datatable', [\App\Http\Controllers\Web\Operations\DeviceBrandOperationsController::class, 'datatable'])
            ->name('tenant.operations.brands.datatable');

        Route::get('/operations/brands/new', [\App\Http\Controllers\Web\Operations\DeviceBrandOperationsController::class, 'create'])
            ->name('tenant.operations.brands.create');

        Route::get('/operations/brands/search', [\App\Http\Controllers\Web\Operations\DeviceBrandOperationsController::class, 'search'])
            ->name('tenant.operations.brands.search');

        Route::get('/operations/brands/{brand}/edit', [\App\Http\Controllers\Web\Operations\DeviceBrandOperationsController::class, 'edit'])
            ->where(['brand' => '[0-9]+' ])
            ->name('tenant.operations.brands.edit');

        Route::post('/operations/brands', [\App\Http\Controllers\Web\Operations\DeviceBrandOperationsController::class, 'store'])
            ->name('tenant.operations.brands.store');

        Route::post('/operations/brands/{brand}/update', [\App\Http\Controllers\Web\Operations\DeviceBrandOperationsController::class, 'update'])
            ->where(['brand' => '[0-9]+' ])
            ->name('tenant.operations.brands.update');

        Route::post('/operations/brands/{brand}/active', [\App\Http\Controllers\Web\Operations\DeviceBrandOperationsController::class, 'setActive'])
            ->where(['brand' => '[0-9]+' ])
            ->name('tenant.operations.brands.active');

        Route::post('/operations/brands/{brand}/delete', [\App\Http\Controllers\Web\Operations\DeviceBrandOperationsController::class, 'delete'])
            ->where(['brand' => '[0-9]+' ])
            ->name('tenant.operations.brands.delete');

        Route::get('/operations/brand-types', [\App\Http\Controllers\Web\Operations\DeviceTypeOperationsController::class, 'index'])
            ->name('tenant.operations.brand_types.index');

        Route::get('/operations/brand-types/datatable', [\App\Http\Controllers\Web\Operations\DeviceTypeOperationsController::class, 'datatable'])
            ->name('tenant.operations.brand_types.datatable');

        Route::get('/operations/brand-types/new', [\App\Http\Controllers\Web\Operations\DeviceTypeOperationsController::class, 'create'])
            ->name('tenant.operations.brand_types.create');

        Route::get('/operations/brand-types/search', [\App\Http\Controllers\Web\Operations\DeviceTypeOperationsController::class, 'search'])
            ->name('tenant.operations.brand_types.search');

        Route::get('/operations/brand-types/{type}/edit', [\App\Http\Controllers\Web\Operations\DeviceTypeOperationsController::class, 'edit'])
            ->where(['type' => '[0-9]+' ])
            ->name('tenant.operations.brand_types.edit');

        Route::post('/operations/brand-types', [\App\Http\Controllers\Web\Operations\DeviceTypeOperationsController::class, 'store'])
            ->name('tenant.operations.brand_types.store');

        Route::post('/operations/brand-types/{type}/update', [\App\Http\Controllers\Web\Operations\DeviceTypeOperationsController::class, 'update'])
            ->where(['type' => '[0-9]+' ])
            ->name('tenant.operations.brand_types.update');

        Route::post('/operations/brand-types/{type}/active', [\App\Http\Controllers\Web\Operations\DeviceTypeOperationsController::class, 'setActive'])
            ->where(['type' => '[0-9]+' ])
            ->name('tenant.operations.brand_types.active');

        Route::post('/operations/brand-types/{type}/delete', [\App\Http\Controllers\Web\Operations\DeviceTypeOperationsController::class, 'delete'])
            ->where(['type' => '[0-9]+' ])
            ->name('tenant.operations.brand_types.delete');

        Route::get('/operations/devices', [\App\Http\Controllers\Web\Operations\DeviceOperationsController::class, 'index'])
            ->name('tenant.operations.devices.index');

        Route::get('/operations/devices/datatable', [\App\Http\Controllers\Web\Operations\DeviceOperationsController::class, 'datatable'])
            ->name('tenant.operations.devices.datatable');

        Route::get('/operations/devices/new', [\App\Http\Controllers\Web\Operations\DeviceOperationsController::class, 'create'])
            ->name('tenant.operations.devices.create');

        Route::get('/operations/devices/{device}/edit', [\App\Http\Controllers\Web\Operations\DeviceOperationsController::class, 'edit'])
            ->where(['device' => '[0-9]+' ])
            ->name('tenant.operations.devices.edit');

        Route::post('/operations/devices', [\App\Http\Controllers\Web\Operations\DeviceOperationsController::class, 'store'])
            ->name('tenant.operations.devices.store');

        Route::post('/operations/devices/{device}/update', [\App\Http\Controllers\Web\Operations\DeviceOperationsController::class, 'update'])
            ->where(['device' => '[0-9]+' ])
            ->name('tenant.operations.devices.update');

        Route::post('/operations/devices/{device}/active', [\App\Http\Controllers\Web\Operations\DeviceOperationsController::class, 'setActive'])
            ->where(['device' => '[0-9]+' ])
            ->name('tenant.operations.devices.active');

        Route::post('/operations/devices/{device}/variations', [\App\Http\Controllers\Web\Operations\DeviceOperationsController::class, 'storeVariations'])
            ->where(['device' => '[0-9]+' ])
            ->name('tenant.operations.devices.variations.store');

        Route::post('/operations/devices/{device}/delete', [\App\Http\Controllers\Web\Operations\DeviceOperationsController::class, 'delete'])
            ->where(['device' => '[0-9]+' ])
            ->name('tenant.operations.devices.delete');

        Route::get('/operations/clients', [\App\Http\Controllers\Web\Operations\ClientOperationsController::class, 'index'])
            ->name('tenant.operations.clients.index');

        Route::get('/operations/clients/datatable', [\App\Http\Controllers\Web\Operations\ClientOperationsController::class, 'datatable'])
            ->name('tenant.operations.clients.datatable');

        Route::get('/operations/clients/new', [\App\Http\Controllers\Web\Operations\ClientOperationsController::class, 'create'])
            ->name('tenant.operations.clients.create');

        Route::get('/operations/clients/{client}/edit', [\App\Http\Controllers\Web\Operations\ClientOperationsController::class, 'edit'])
            ->where(['client' => '[0-9]+' ])
            ->name('tenant.operations.clients.edit');

        Route::post('/operations/clients', [\App\Http\Controllers\Web\Operations\ClientOperationsController::class, 'store'])
            ->name('tenant.operations.clients.store');

        Route::post('/operations/clients/{client}/update', [\App\Http\Controllers\Web\Operations\ClientOperationsController::class, 'update'])
            ->where(['client' => '[0-9]+' ])
            ->name('tenant.operations.clients.update');

        Route::post('/operations/clients/{client}/delete', [\App\Http\Controllers\Web\Operations\ClientOperationsController::class, 'delete'])
            ->where(['client' => '[0-9]+' ])
            ->name('tenant.operations.clients.delete');

        Route::get('/operations/service-types', [\App\Http\Controllers\Web\Operations\ServiceTypeOperationsController::class, 'index'])
            ->name('tenant.operations.service_types.index');

        Route::get('/operations/service-types/datatable', [\App\Http\Controllers\Web\Operations\ServiceTypeOperationsController::class, 'datatable'])
            ->name('tenant.operations.service_types.datatable');

        Route::get('/operations/service-types/new', [\App\Http\Controllers\Web\Operations\ServiceTypeOperationsController::class, 'create'])
            ->name('tenant.operations.service_types.create');

        Route::get('/operations/service-types/search', [\App\Http\Controllers\Web\Operations\ServiceTypeOperationsController::class, 'search'])
            ->name('tenant.operations.service_types.search');

        Route::get('/operations/service-types/{type}/edit', [\App\Http\Controllers\Web\Operations\ServiceTypeOperationsController::class, 'edit'])
            ->where(['type' => '[0-9]+' ])
            ->name('tenant.operations.service_types.edit');

        Route::post('/operations/service-types', [\App\Http\Controllers\Web\Operations\ServiceTypeOperationsController::class, 'store'])
            ->name('tenant.operations.service_types.store');

        Route::post('/operations/service-types/{type}/update', [\App\Http\Controllers\Web\Operations\ServiceTypeOperationsController::class, 'update'])
            ->where(['type' => '[0-9]+' ])
            ->name('tenant.operations.service_types.update');

        Route::post('/operations/service-types/{type}/active', [\App\Http\Controllers\Web\Operations\ServiceTypeOperationsController::class, 'setActive'])
            ->where(['type' => '[0-9]+' ])
            ->name('tenant.operations.service_types.active');

        Route::post('/operations/service-types/{type}/delete', [\App\Http\Controllers\Web\Operations\ServiceTypeOperationsController::class, 'delete'])
            ->where(['type' => '[0-9]+' ])
            ->name('tenant.operations.service_types.delete');

        Route::get('/operations/services', [\App\Http\Controllers\Web\Operations\ServiceOperationsController::class, 'index'])
            ->name('tenant.operations.services.index');

        Route::get('/operations/services/datatable', [\App\Http\Controllers\Web\Operations\ServiceOperationsController::class, 'datatable'])
            ->name('tenant.operations.services.datatable');

        Route::get('/operations/services/new', [\App\Http\Controllers\Web\Operations\ServiceOperationsController::class, 'create'])
            ->name('tenant.operations.services.create');

        Route::get('/operations/services/{service}/edit', [\App\Http\Controllers\Web\Operations\ServiceOperationsController::class, 'edit'])
            ->where(['service' => '[0-9]+' ])
            ->name('tenant.operations.services.edit');

        Route::get('/operations/services/{service}/price-overrides/section', [\App\Http\Controllers\Web\Operations\ServiceOperationsController::class, 'priceOverridesSection'])
            ->where(['service' => '[0-9]+' ])
            ->name('tenant.operations.services.price_overrides.section');

        Route::post('/operations/services', [\App\Http\Controllers\Web\Operations\ServiceOperationsController::class, 'store'])
            ->name('tenant.operations.services.store');

        Route::post('/operations/services/{service}/update', [\App\Http\Controllers\Web\Operations\ServiceOperationsController::class, 'update'])
            ->where(['service' => '[0-9]+' ])
            ->name('tenant.operations.services.update');

        Route::post('/operations/services/{service}/price-overrides', [\App\Http\Controllers\Web\Operations\ServiceOperationsController::class, 'updatePriceOverrides'])
            ->where(['service' => '[0-9]+' ])
            ->name('tenant.operations.services.price_overrides.update');

        Route::post('/operations/services/{service}/active', [\App\Http\Controllers\Web\Operations\ServiceOperationsController::class, 'setActive'])
            ->where(['service' => '[0-9]+' ])
            ->name('tenant.operations.services.active');

        Route::post('/operations/services/{service}/delete', [\App\Http\Controllers\Web\Operations\ServiceOperationsController::class, 'delete'])
            ->where(['service' => '[0-9]+' ])
            ->name('tenant.operations.services.delete');

        Route::get('/operations/parts', [\App\Http\Controllers\Web\Operations\PartOperationsController::class, 'index'])
            ->name('tenant.operations.parts.index');

        Route::get('/operations/parts/datatable', [\App\Http\Controllers\Web\Operations\PartOperationsController::class, 'datatable'])
            ->name('tenant.operations.parts.datatable');

        Route::get('/operations/parts/new', [\App\Http\Controllers\Web\Operations\PartOperationsController::class, 'create'])
            ->name('tenant.operations.parts.create');

        Route::get('/operations/parts/{part}/edit', [\App\Http\Controllers\Web\Operations\PartOperationsController::class, 'edit'])
            ->where(['part' => '[0-9]+' ])
            ->name('tenant.operations.parts.edit');

        Route::get('/operations/parts/{part}/price-overrides/section', [\App\Http\Controllers\Web\Operations\PartOperationsController::class, 'priceOverridesSection'])
            ->where(['part' => '[0-9]+' ])
            ->name('tenant.operations.parts.price_overrides.section');

        Route::post('/operations/parts', [\App\Http\Controllers\Web\Operations\PartOperationsController::class, 'store'])
            ->name('tenant.operations.parts.store');

        Route::post('/operations/parts/{part}/update', [\App\Http\Controllers\Web\Operations\PartOperationsController::class, 'update'])
            ->where(['part' => '[0-9]+' ])
            ->name('tenant.operations.parts.update');

        Route::post('/operations/parts/{part}/price-overrides', [\App\Http\Controllers\Web\Operations\PartOperationsController::class, 'updatePriceOverrides'])
            ->where(['part' => '[0-9]+' ])
            ->name('tenant.operations.parts.price_overrides.update');

        Route::post('/operations/parts/{part}/active', [\App\Http\Controllers\Web\Operations\PartOperationsController::class, 'setActive'])
            ->where(['part' => '[0-9]+' ])
            ->name('tenant.operations.parts.active');

        Route::post('/operations/parts/{part}/delete', [\App\Http\Controllers\Web\Operations\PartOperationsController::class, 'delete'])
            ->where(['part' => '[0-9]+' ])
            ->name('tenant.operations.parts.delete');

        Route::get('/operations/part-brands', [\App\Http\Controllers\Web\Operations\PartBrandOperationsController::class, 'index'])
            ->name('tenant.operations.part_brands.index');

        Route::get('/operations/part-brands/datatable', [\App\Http\Controllers\Web\Operations\PartBrandOperationsController::class, 'datatable'])
            ->name('tenant.operations.part_brands.datatable');

        Route::get('/operations/part-brands/new', [\App\Http\Controllers\Web\Operations\PartBrandOperationsController::class, 'create'])
            ->name('tenant.operations.part_brands.create');

        Route::get('/operations/part-brands/{brand}/edit', [\App\Http\Controllers\Web\Operations\PartBrandOperationsController::class, 'edit'])
            ->where(['brand' => '[0-9]+' ])
            ->name('tenant.operations.part_brands.edit');

        Route::post('/operations/part-brands', [\App\Http\Controllers\Web\Operations\PartBrandOperationsController::class, 'store'])
            ->name('tenant.operations.part_brands.store');

        Route::post('/operations/part-brands/{brand}/update', [\App\Http\Controllers\Web\Operations\PartBrandOperationsController::class, 'update'])
            ->where(['brand' => '[0-9]+' ])
            ->name('tenant.operations.part_brands.update');

        Route::post('/operations/part-brands/{brand}/active', [\App\Http\Controllers\Web\Operations\PartBrandOperationsController::class, 'setActive'])
            ->where(['brand' => '[0-9]+' ])
            ->name('tenant.operations.part_brands.active');

        Route::post('/operations/part-brands/{brand}/delete', [\App\Http\Controllers\Web\Operations\PartBrandOperationsController::class, 'delete'])
            ->where(['brand' => '[0-9]+' ])
            ->name('tenant.operations.part_brands.delete');

        Route::get('/operations/part-types', [\App\Http\Controllers\Web\Operations\PartTypeOperationsController::class, 'index'])
            ->name('tenant.operations.part_types.index');

        Route::get('/operations/part-types/datatable', [\App\Http\Controllers\Web\Operations\PartTypeOperationsController::class, 'datatable'])
            ->name('tenant.operations.part_types.datatable');

        Route::get('/operations/part-types/new', [\App\Http\Controllers\Web\Operations\PartTypeOperationsController::class, 'create'])
            ->name('tenant.operations.part_types.create');

        Route::get('/operations/part-types/{type}/edit', [\App\Http\Controllers\Web\Operations\PartTypeOperationsController::class, 'edit'])
            ->where(['type' => '[0-9]+' ])
            ->name('tenant.operations.part_types.edit');

        Route::post('/operations/part-types', [\App\Http\Controllers\Web\Operations\PartTypeOperationsController::class, 'store'])
            ->name('tenant.operations.part_types.store');

        Route::post('/operations/part-types/{type}/update', [\App\Http\Controllers\Web\Operations\PartTypeOperationsController::class, 'update'])
            ->where(['type' => '[0-9]+' ])
            ->name('tenant.operations.part_types.update');

        Route::post('/operations/part-types/{type}/active', [\App\Http\Controllers\Web\Operations\PartTypeOperationsController::class, 'setActive'])
            ->where(['type' => '[0-9]+' ])
            ->name('tenant.operations.part_types.active');

        Route::post('/operations/part-types/{type}/delete', [\App\Http\Controllers\Web\Operations\PartTypeOperationsController::class, 'delete'])
            ->where(['type' => '[0-9]+' ])
            ->name('tenant.operations.part_types.delete');

        Route::get('/profile', [\App\Http\Controllers\Web\TenantProfileController::class, 'edit'])
            ->name('tenant.profile.edit');

        Route::post('/profile', [\App\Http\Controllers\Web\TenantProfileController::class, 'update'])
            ->name('tenant.profile.update');

        Route::post('/profile/password', [\App\Http\Controllers\Web\TenantProfileController::class, 'updatePassword'])
            ->name('tenant.profile.password.update');

        Route::post('/profile/photo', [\App\Http\Controllers\Web\TenantProfileController::class, 'updatePhoto'])
            ->name('tenant.profile.photo.update');

        Route::post('/settings/general', [\App\Http\Controllers\Tenant\Settings\GeneralSettingsController::class, 'update'])
            ->name('tenant.settings.general.update');

        Route::post('/settings/currency', [\App\Http\Controllers\Tenant\Settings\CurrencySettingsController::class, 'update'])
            ->name('tenant.settings.currency.update');

        Route::post('/settings/invoices', [\App\Http\Controllers\Tenant\Settings\InvoicesSettingsController::class, 'update'])
            ->name('tenant.settings.invoices.update');

        Route::post('/settings/job-status', [\App\Http\Controllers\Tenant\Settings\JobStatusSettingsController::class, 'update'])
            ->name('tenant.settings.job_status.update');

        Route::post('/settings/job-status/statuses', [\App\Http\Controllers\Tenant\Statuses\JobStatusController::class, 'store'])
            ->name('tenant.settings.job_status.store');

        Route::post('/settings/job-status/statuses/{status}/update', [\App\Http\Controllers\Tenant\Statuses\JobStatusController::class, 'update'])
            ->where(['status' => '[0-9]+' ])
            ->name('tenant.settings.job_status.statuses.update');

        Route::post('/settings/job-status/statuses/{status}/delete', [\App\Http\Controllers\Tenant\Statuses\JobStatusController::class, 'delete'])
            ->where(['status' => '[0-9]+' ])
            ->name('tenant.settings.job_status.statuses.delete');

        Route::post('/settings/pages-setup', [\App\Http\Controllers\Tenant\Settings\PagesSetupController::class, 'update'])
            ->name('tenant.settings.pages_setup.update');

        Route::post('/settings/taxes', [\App\Http\Controllers\Tenant\Taxes\TaxController::class, 'store'])
            ->name('tenant.settings.taxes.store');

        Route::post('/settings/taxes/{tax}/update', [\App\Http\Controllers\Tenant\Taxes\TaxController::class, 'update'])
            ->where(['tax' => '[0-9]+' ])
            ->name('tenant.settings.taxes.update');

        Route::post('/settings/taxes/{tax}/active', [\App\Http\Controllers\Tenant\Taxes\TaxController::class, 'setActive'])
            ->where(['tax' => '[0-9]+' ])
            ->name('tenant.settings.taxes.active');

        Route::post('/settings/taxes/{tax}/default', [\App\Http\Controllers\Tenant\Taxes\TaxController::class, 'setDefault'])
            ->where(['tax' => '[0-9]+' ])
            ->name('tenant.settings.taxes.default');

        Route::post('/settings/taxes/settings', [\App\Http\Controllers\Tenant\Taxes\TaxSettingsController::class, 'update'])
            ->name('tenant.settings.taxes.settings');

        Route::post('/settings/devices-brands', [\App\Http\Controllers\Tenant\Settings\DevicesBrandsSettingsController::class, 'update'])
            ->name('tenant.settings.devices_brands.update');

        Route::post('/settings/device-brands', [\App\Http\Controllers\Tenant\DeviceBrands\DeviceBrandController::class, 'store'])
            ->name('tenant.settings.device_brands.store');

        Route::post('/settings/device-brands/{brand}/active', [\App\Http\Controllers\Tenant\DeviceBrands\DeviceBrandController::class, 'setActive'])
            ->where(['brand' => '[0-9]+' ])
            ->name('tenant.settings.device_brands.active');

        Route::post('/settings/device-brands/{brand}/delete', [\App\Http\Controllers\Tenant\DeviceBrands\DeviceBrandController::class, 'delete'])
            ->where(['brand' => '[0-9]+' ])
            ->name('tenant.settings.device_brands.delete');

        Route::post('/settings/bookings', [\App\Http\Controllers\Tenant\Settings\BookingSettingsController::class, 'update'])
            ->name('tenant.settings.bookings.update');

        Route::get('/settings/bookings/brands', [\App\Http\Controllers\Tenant\Settings\BookingSettingsController::class, 'brands'])
            ->name('tenant.settings.bookings.brands');

        Route::get('/settings/bookings/devices', [\App\Http\Controllers\Tenant\Settings\BookingSettingsController::class, 'devices'])
            ->name('tenant.settings.bookings.devices');

        Route::post('/settings/services', [\App\Http\Controllers\Tenant\Settings\ServiceSettingsController::class, 'update'])
            ->name('tenant.settings.services.update');

        Route::post('/settings/payment-status/{slug}', [\App\Http\Controllers\Tenant\Statuses\PaymentStatusDisplayController::class, 'update'])
            ->name('tenant.settings.payment_status.update');

        Route::post('/settings/payment-status', [\App\Http\Controllers\Tenant\Statuses\PaymentStatusController::class, 'save'])
            ->name('tenant.settings.payment_status.save');

        Route::post('/settings/payment-status/{status}/toggle', [\App\Http\Controllers\Tenant\Statuses\PaymentStatusController::class, 'toggle'])
            ->where(['status' => '[0-9]+' ])
            ->name('tenant.settings.payment_status.toggle');

        Route::post('/settings/payment-methods', [\App\Http\Controllers\Tenant\Settings\PaymentMethodsController::class, 'update'])
            ->name('tenant.settings.payment_methods.update');

        Route::post('/settings/maintenance-reminders', [\App\Http\Controllers\Tenant\MaintenanceReminders\MaintenanceReminderController::class, 'store'])
            ->name('tenant.settings.maintenance_reminders.store');

        Route::get('/settings/maintenance-reminders/logs', [\App\Http\Controllers\Tenant\MaintenanceReminders\MaintenanceReminderController::class, 'logs'])
            ->name('tenant.settings.maintenance_reminders.logs');

        Route::post('/settings/maintenance-reminders/{reminder}/update', [\App\Http\Controllers\Tenant\MaintenanceReminders\MaintenanceReminderController::class, 'update'])
            ->where(['reminder' => '[0-9]+' ])
            ->name('tenant.settings.maintenance_reminders.update');

        Route::post('/settings/maintenance-reminders/{reminder}/delete', [\App\Http\Controllers\Tenant\MaintenanceReminders\MaintenanceReminderController::class, 'delete'])
            ->where(['reminder' => '[0-9]+' ])
            ->name('tenant.settings.maintenance_reminders.delete');

        Route::post('/settings/time-log', [\App\Http\Controllers\Tenant\Settings\TimeLogSettingsController::class, 'update'])
            ->name('tenant.settings.time_log.update');

        Route::post('/settings/styling', [\App\Http\Controllers\Tenant\Settings\StylingSettingsController::class, 'update'])
            ->name('tenant.settings.styling.update');

        Route::post('/settings/reviews', [\App\Http\Controllers\Tenant\Settings\ReviewsSettingsController::class, 'update'])
            ->name('tenant.settings.reviews.update');

        Route::post('/settings/estimates', [\App\Http\Controllers\Tenant\Settings\EstimatesSettingsController::class, 'update'])
            ->name('tenant.settings.estimates.update');

        Route::post('/settings/sms', [\App\Http\Controllers\Tenant\Settings\SmsSettingsController::class, 'update'])
            ->name('tenant.settings.sms.update');

        Route::post('/settings/account', [\App\Http\Controllers\Tenant\Settings\AccountSettingsController::class, 'update'])
            ->name('tenant.settings.account.update');

        Route::post('/settings/signature', [\App\Http\Controllers\Tenant\Settings\SignatureSettingsController::class, 'update'])
            ->name('tenant.settings.signature.update');

        Route::post('/settings/appointments', [\App\Http\Controllers\Tenant\Settings\AppointmentSettingsController::class, 'store'])
            ->name('tenant.settings.appointments.store');

        Route::post('/settings/appointments/{setting}/update', [\App\Http\Controllers\Tenant\Settings\AppointmentSettingsController::class, 'update'])
            ->where(['setting' => '[0-9]+' ])
            ->name('tenant.settings.appointments.update');

        Route::post('/settings/appointments/{setting}/toggle', [\App\Http\Controllers\Tenant\Settings\AppointmentSettingsController::class, 'toggle'])
            ->where(['setting' => '[0-9]+' ])
            ->name('tenant.settings.appointments.toggle');

        Route::post('/settings/appointments/{setting}/delete', [\App\Http\Controllers\Tenant\Settings\AppointmentSettingsController::class, 'delete'])
            ->where(['setting' => '[0-9]+' ])
            ->name('tenant.settings.appointments.delete');

        Route::get('/jobs/new', [\App\Http\Controllers\Web\TenantJobController::class, 'create'])
            ->name('tenant.jobs.create');

        Route::get('/jobs/{jobId}/edit', [\App\Http\Controllers\Web\TenantJobController::class, 'edit'])
            ->where(['jobId' => '[0-9]+' ])
            ->name('tenant.jobs.edit');

        Route::get('/jobs/{jobId}', [\App\Http\Controllers\Web\TenantJobController::class, 'show'])
            ->where(['jobId' => '[0-9]+' ])
            ->name('tenant.jobs.show');

        Route::put('/jobs/{jobId}', [\App\Http\Controllers\Web\TenantJobController::class, 'update'])
            ->where(['jobId' => '[0-9]+' ])
            ->name('tenant.jobs.update');

        Route::post('/jobs/{jobId}/payments', [\App\Http\Controllers\Web\TenantJobController::class, 'storePayment'])
            ->where(['jobId' => '[0-9]+' ])
            ->name('tenant.jobs.payments.store');

        Route::post('/jobs', [\App\Http\Controllers\Web\TenantJobController::class, 'store'])
            ->name('tenant.jobs.store');

        Route::get('/jobs/datatable', [\App\Http\Controllers\Web\TenantJobController::class, 'datatable'])
            ->name('tenant.jobs.datatable');

        /* ------------------------------------------------------------ */
        /*  SIGNATURES – authenticated management pages                  */
        /* ------------------------------------------------------------ */
        Route::get('/jobs/{jobId}/signatures', [\App\Http\Controllers\Web\SignatureController::class, 'index'])
            ->where(['jobId' => '[0-9]+' ])
            ->name('tenant.signatures.index');

        Route::get('/jobs/{jobId}/signatures/create', [\App\Http\Controllers\Web\SignatureController::class, 'create'])
            ->where(['jobId' => '[0-9]+' ])
            ->name('tenant.signatures.create');

        Route::post('/jobs/{jobId}/signatures', [\App\Http\Controllers\Web\SignatureController::class, 'store'])
            ->where(['jobId' => '[0-9]+' ])
            ->name('tenant.signatures.store');

        Route::get('/jobs/{jobId}/signatures/{signatureId}', [\App\Http\Controllers\Web\SignatureController::class, 'generator'])
            ->where(['jobId' => '[0-9]+', 'signatureId' => '[0-9]+' ])
            ->name('tenant.signatures.generator');

        Route::get('/jobs/{jobId}/signatures/{signatureId}/show', [\App\Http\Controllers\Web\SignatureController::class, 'show'])
            ->where(['jobId' => '[0-9]+', 'signatureId' => '[0-9]+' ])
            ->name('tenant.signatures.show');

        Route::post('/jobs/{jobId}/signatures/{signatureId}/send', [\App\Http\Controllers\Web\SignatureController::class, 'sendEmail'])
            ->where(['jobId' => '[0-9]+', 'signatureId' => '[0-9]+' ])
            ->name('tenant.signatures.send');

        /* ------------------------------------------------------------ */
        /*  ESTIMATES – standalone pages                                 */
        /* ------------------------------------------------------------ */
        Route::get('/estimates', [\App\Http\Controllers\Web\TenantEstimateController::class, 'index'])
            ->name('tenant.estimates.index');

        Route::get('/estimates/new', [\App\Http\Controllers\Web\TenantEstimateController::class, 'create'])
            ->name('tenant.estimates.create');

        Route::get('/estimates/datatable', [\App\Http\Controllers\Web\TenantEstimateController::class, 'datatable'])
            ->name('tenant.estimates.datatable');

        Route::get('/estimates/{estimateId}', [\App\Http\Controllers\Web\TenantEstimateController::class, 'show'])
            ->where(['estimateId' => '[0-9]+' ])
            ->name('tenant.estimates.show');

        Route::get('/estimates/{estimateId}/edit', [\App\Http\Controllers\Web\TenantEstimateController::class, 'edit'])
            ->where(['estimateId' => '[0-9]+' ])
            ->name('tenant.estimates.edit');

        Route::post('/estimates', [\App\Http\Controllers\Web\TenantEstimateController::class, 'store'])
            ->name('tenant.estimates.store');

        Route::put('/estimates/{estimateId}', [\App\Http\Controllers\Web\TenantEstimateController::class, 'update'])
            ->where(['estimateId' => '[0-9]+' ])
            ->name('tenant.estimates.update');

        Route::post('/estimates/{estimateId}/approve', [\App\Http\Controllers\Web\TenantEstimateController::class, 'approve'])
            ->where(['estimateId' => '[0-9]+' ])
            ->name('tenant.estimates.approve');

        Route::post('/estimates/{estimateId}/reject', [\App\Http\Controllers\Web\TenantEstimateController::class, 'reject'])
            ->where(['estimateId' => '[0-9]+' ])
            ->name('tenant.estimates.reject');

        Route::post('/estimates/{estimateId}/convert', [\App\Http\Controllers\Web\TenantEstimateController::class, 'convert'])
            ->where(['estimateId' => '[0-9]+' ])
            ->name('tenant.estimates.convert');

        Route::post('/estimates/{estimateId}/send', [\App\Http\Controllers\Web\TenantEstimateController::class, 'send'])
            ->where(['estimateId' => '[0-9]+' ])
            ->name('tenant.estimates.send');

        Route::delete('/estimates/{estimateId}', [\App\Http\Controllers\Web\TenantEstimateController::class, 'destroy'])
            ->where(['estimateId' => '[0-9]+' ])
            ->name('tenant.estimates.destroy');

        /* ------------------------------------------------------------ */
        /*  PRINT / PDF – jobs & estimates                               */
        /* ------------------------------------------------------------ */
        Route::get('/jobs/{jobId}/print', [\App\Http\Controllers\Web\PrintDocumentController::class, 'showJob'])
            ->where(['jobId' => '[0-9]+'])
            ->name('tenant.jobs.print');

        Route::get('/jobs/{jobId}/pdf', [\App\Http\Controllers\Web\PrintDocumentController::class, 'pdfJob'])
            ->where(['jobId' => '[0-9]+'])
            ->name('tenant.jobs.pdf');

        Route::get('/estimates/{estimateId}/print', [\App\Http\Controllers\Web\PrintDocumentController::class, 'showEstimate'])
            ->where(['estimateId' => '[0-9]+'])
            ->name('tenant.estimates.print');

        Route::get('/estimates/{estimateId}/pdf', [\App\Http\Controllers\Web\PrintDocumentController::class, 'pdfEstimate'])
            ->where(['estimateId' => '[0-9]+'])
            ->name('tenant.estimates.pdf');

        Route::post('/calendar/events', [\App\Http\Controllers\Web\TenantDashboardController::class, 'calendarEvents'])
            ->name('tenant.calendar.events');

        // Legacy AJAX handler
        Route::match(['get', 'post'], '/legacy-ajax', [\App\Http\Controllers\Web\AjaxController::class, 'handle'])
            ->name('tenant.legacy-ajax');

        // ── Mockup pages (design previews) ──
        Route::get('/mockups/datatables', function (\Illuminate\Http\Request $request) {
            $tenant = $request->route('business');
            $tenantModel = \App\Models\Tenant::where('slug', $tenant)->firstOrFail();
            return view('tenant.mockups.datatables', [
                'pageTitle' => 'DataTable Designs',
                'tenant'    => $tenantModel,
            ]);
        })->name('tenant.mockups.datatables');
    });
