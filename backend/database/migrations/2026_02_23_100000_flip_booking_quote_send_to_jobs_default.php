<?php

use App\Models\Tenant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

/**
 * Flip sendBookingQuoteToJobs / bookingQuoteSendToJobs to false for ALL
 * existing tenants so that public booking submissions create estimates
 * instead of jobs by default.
 */
return new class extends Migration
{
    public function up(): void
    {
        Tenant::query()->each(function (Tenant $tenant) {
            $state = $tenant->setup_state ?? [];
            if (! is_array($state)) {
                return;
            }

            $settings = $state['repairbuddy_settings'] ?? [];
            if (! is_array($settings)) {
                return;
            }

            $changed = false;

            // booking.sendBookingQuoteToJobs → false
            if (isset($settings['booking']) && is_array($settings['booking'])) {
                if (($settings['booking']['sendBookingQuoteToJobs'] ?? null) === true) {
                    $settings['booking']['sendBookingQuoteToJobs'] = false;
                    $changed = true;
                }
            }

            // estimates.bookingQuoteSendToJobs → false
            if (isset($settings['estimates']) && is_array($settings['estimates'])) {
                if (($settings['estimates']['bookingQuoteSendToJobs'] ?? null) === true) {
                    $settings['estimates']['bookingQuoteSendToJobs'] = false;
                    $changed = true;
                }
            }

            if ($changed) {
                $state['repairbuddy_settings'] = $settings;
                $tenant->forceFill(['setup_state' => $state])->save();

                Log::info('migration: flipped sendBookingQuoteToJobs to false', [
                    'tenant_id' => $tenant->id,
                    'slug'      => $tenant->slug,
                ]);
            }
        });
    }

    public function down(): void
    {
        // Intentionally left empty – reverting would re-enable "send to jobs"
        // which is the old behaviour we no longer want.
    }
};
