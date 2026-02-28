<?php

namespace App\Console\Commands;

use App\Models\RepairBuddyAppointment;
use App\Models\Tenant;
use App\Notifications\AppointmentReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders {--hours=24 : Hours before appointment to send reminder}';

    protected $description = 'Send reminder notifications for upcoming appointments';

    public function handle(): int
    {
        $hoursBefore = (int) $this->option('hours');
        $targetDate = now()->addHours($hoursBefore)->toDateString();
        $targetTimeStart = now()->addHours($hoursBefore)->format('H:i:s');
        $targetTimeEnd = now()->addHours($hoursBefore + 1)->format('H:i:s');

        $appointments = RepairBuddyAppointment::query()
            ->with(['customer', 'appointmentSetting', 'job', 'estimate'])
            ->where('appointment_date', $targetDate)
            ->where('time_slot_start', '>=', $targetTimeStart)
            ->where('time_slot_start', '<', $targetTimeEnd)
            ->whereIn('status', [RepairBuddyAppointment::STATUS_SCHEDULED, RepairBuddyAppointment::STATUS_CONFIRMED])
            ->whereNull('reminder_sent_at')
            ->limit(500)
            ->get();

        $sent = 0;
        $failed = 0;

        foreach ($appointments as $appointment) {
            $customer = $appointment->customer;
            $tenant = Tenant::query()->where('id', $appointment->tenant_id)->first();

            if (! $customer || ! $tenant) {
                $failed++;
                continue;
            }

            try {
                $customer->notify(new AppointmentReminderNotification(
                    appointment: $appointment,
                    tenant: $tenant,
                ));

                $appointment->update(['reminder_sent_at' => now()]);
                $sent++;

                $this->info("Reminder sent for appointment #{$appointment->id} to {$customer->email}");
            } catch (\Throwable $e) {
                $failed++;
                Log::error('appointments.reminder_failed', [
                    'appointment_id' => $appointment->id,
                    'customer_id' => $customer->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Reminders sent: {$sent}, Failed: {$failed}");

        return self::SUCCESS;
    }
}
