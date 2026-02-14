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

        Route::post('/calendar/events', [\App\Http\Controllers\Web\TenantDashboardController::class, 'calendarEvents'])
            ->name('tenant.calendar.events');
    });
