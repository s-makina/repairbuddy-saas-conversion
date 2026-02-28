<?php

namespace App\Notifications;

use App\Models\RepairBuddyAppointment;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class AppointmentReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public RepairBuddyAppointment $appointment,
        public Tenant $tenant,
        public ?string $customSubject = null,
        public ?string $customBody = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->customSubject ?? __('Appointment Reminder');
        $businessName = $this->tenant->name ?? 'Repair Shop';
        $date = $this->appointment->appointment_date->format('F j, Y');
        $time = $this->appointment->time_slot_start->format('H:i') . ' - ' . $this->appointment->time_slot_end->format('H:i');
        $type = $this->appointment->title ?? $this->appointment->appointmentSetting?->title ?? __('Appointment');

        $frontendBase = rtrim((string) env('FRONTEND_URL', (string) env('APP_URL', '')), '/');
        $statusCheckUrl = $frontendBase . '/t/' . $this->tenant->slug . '/status';

        if ($this->appointment->job) {
            $statusCheckUrl .= '?caseNumber=' . urlencode($this->appointment->job->case_number);
        } elseif ($this->appointment->estimate) {
            $statusCheckUrl .= '?caseNumber=' . urlencode($this->appointment->estimate->case_number);
        }

        if ($this->customBody) {
            $body = str_replace([
                '{{business_name}}',
                '{{appointment_type}}',
                '{{appointment_date}}',
                '{{appointment_time}}',
                '{{status_check_link}}',
                '{{customer_name}}',
            ], [
                $businessName,
                $type,
                $date,
                $time,
                $statusCheckUrl,
                $notifiable->name ?? '',
            ], $this->customBody);

            return (new MailMessage)
                ->subject($subject)
                ->line($body);
        }

        return (new MailMessage)
            ->subject($subject)
            ->greeting(__('Hello') . ' ' . ($notifiable->name ?? '') . ',')
            ->line(__('This is a reminder about your upcoming appointment.'))
            ->line('')
            ->line('**' . __('Appointment Details') . '**')
            ->line(__('Type:') . ' ' . $type)
            ->line(__('Date:') . ' ' . $date)
            ->line(__('Time:') . ' ' . $time)
            ->line('')
            ->action(__('Check Status'), $statusCheckUrl)
            ->line('')
            ->line(__('We look forward to seeing you!'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'appointment_id' => $this->appointment->id,
            'date' => $this->appointment->appointment_date->toDateString(),
            'time' => $this->appointment->time_slot_start->format('H:i'),
        ];
    }
}
