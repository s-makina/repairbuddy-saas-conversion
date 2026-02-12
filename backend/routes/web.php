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

        Route::post('/settings/pages-setup', [\App\Http\Controllers\Web\TenantDashboardController::class, 'updatePagesSetup'])
            ->name('tenant.settings.pages_setup.update');

        Route::post('/settings/taxes', [\App\Http\Controllers\Web\TenantDashboardController::class, 'storeTax'])
            ->name('tenant.settings.taxes.store');

        Route::post('/settings/taxes/{tax}/active', [\App\Http\Controllers\Web\TenantDashboardController::class, 'setTaxActive'])
            ->where(['tax' => '[0-9]+' ])
            ->name('tenant.settings.taxes.active');

        Route::post('/settings/taxes/{tax}/default', [\App\Http\Controllers\Web\TenantDashboardController::class, 'setTaxDefault'])
            ->where(['tax' => '[0-9]+' ])
            ->name('tenant.settings.taxes.default');

        Route::post('/settings/taxes/settings', [\App\Http\Controllers\Web\TenantDashboardController::class, 'updateTaxSettings'])
            ->name('tenant.settings.taxes.settings');

        Route::post('/settings/devices-brands', [\App\Http\Controllers\Web\TenantDashboardController::class, 'updateDevicesBrandsSettings'])
            ->name('tenant.settings.devices_brands.update');

        Route::post('/settings/device-brands', [\App\Http\Controllers\Web\TenantDashboardController::class, 'storeDeviceBrand'])
            ->name('tenant.settings.device_brands.store');

        Route::post('/settings/device-brands/{brand}/active', [\App\Http\Controllers\Web\TenantDashboardController::class, 'setDeviceBrandActive'])
            ->where(['brand' => '[0-9]+' ])
            ->name('tenant.settings.device_brands.active');

        Route::post('/settings/device-brands/{brand}/delete', [\App\Http\Controllers\Web\TenantDashboardController::class, 'deleteDeviceBrand'])
            ->where(['brand' => '[0-9]+' ])
            ->name('tenant.settings.device_brands.delete');

        Route::post('/settings/bookings', [\App\Http\Controllers\Web\TenantDashboardController::class, 'updateBookingSettings'])
            ->name('tenant.settings.bookings.update');

        Route::post('/settings/services', [\App\Http\Controllers\Web\TenantDashboardController::class, 'updateServiceSettings'])
            ->name('tenant.settings.services.update');

        Route::post('/settings/payment-status/{slug}', [\App\Http\Controllers\Web\TenantDashboardController::class, 'updatePaymentStatusDisplay'])
            ->name('tenant.settings.payment_status.update');

        Route::post('/settings/maintenance-reminders', [\App\Http\Controllers\Web\TenantDashboardController::class, 'storeMaintenanceReminder'])
            ->name('tenant.settings.maintenance_reminders.store');

        Route::post('/settings/maintenance-reminders/{reminder}/update', [\App\Http\Controllers\Web\TenantDashboardController::class, 'updateMaintenanceReminder'])
            ->where(['reminder' => '[0-9]+' ])
            ->name('tenant.settings.maintenance_reminders.update');

        Route::post('/settings/maintenance-reminders/{reminder}/delete', [\App\Http\Controllers\Web\TenantDashboardController::class, 'deleteMaintenanceReminder'])
            ->where(['reminder' => '[0-9]+' ])
            ->name('tenant.settings.maintenance_reminders.delete');

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
