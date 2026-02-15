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

Route::prefix('t/{business}')
    ->where(['business' => '[A-Za-z0-9\-]+' ])
    ->middleware(['web', 'tenant', 'branch.web', 'auth'])
    ->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Web\TenantDashboardController::class, 'show'])
            ->name('tenant.dashboard');

        Route::get('/settings', [\App\Http\Controllers\Web\TenantSettingsController::class, 'show'])
            ->name('tenant.settings');

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

        Route::post('/operations/services', [\App\Http\Controllers\Web\Operations\ServiceOperationsController::class, 'store'])
            ->name('tenant.operations.services.store');

        Route::post('/operations/services/{service}/update', [\App\Http\Controllers\Web\Operations\ServiceOperationsController::class, 'update'])
            ->where(['service' => '[0-9]+' ])
            ->name('tenant.operations.services.update');

        Route::post('/operations/services/{service}/active', [\App\Http\Controllers\Web\Operations\ServiceOperationsController::class, 'setActive'])
            ->where(['service' => '[0-9]+' ])
            ->name('tenant.operations.services.active');

        Route::post('/operations/services/{service}/delete', [\App\Http\Controllers\Web\Operations\ServiceOperationsController::class, 'delete'])
            ->where(['service' => '[0-9]+' ])
            ->name('tenant.operations.services.delete');

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

        Route::get('/jobs/new', [\App\Http\Controllers\Web\TenantJobController::class, 'create'])
            ->name('tenant.jobs.create');

        Route::get('/jobs/{jobId}', [\App\Http\Controllers\Web\TenantJobController::class, 'show'])
            ->where(['jobId' => '[0-9]+' ])
            ->name('tenant.jobs.show');

        Route::post('/jobs', [\App\Http\Controllers\Web\TenantJobController::class, 'store'])
            ->name('tenant.jobs.store');

        Route::get('/jobs/datatable', [\App\Http\Controllers\Web\TenantJobController::class, 'datatable'])
            ->name('tenant.jobs.datatable');

        Route::post('/calendar/events', [\App\Http\Controllers\Web\TenantDashboardController::class, 'calendarEvents'])
            ->name('tenant.calendar.events');
    });
