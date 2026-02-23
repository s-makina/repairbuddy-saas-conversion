<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\RepairBuddyAppointmentSetting;
use App\Models\Tenant;
use App\Services\TenantSettings\TenantSettingsStore;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Livewire\Component;

class AppointmentSettings extends Component
{
    public $tenant;

    /* ─── Appointment Settings ───────────────────── */
    public bool $enable_appointments = false;
    public string $default_duration = '60';
    public string $business_hours_start = '09:00';
    public string $business_hours_end = '17:00';
    public bool $allow_online_booking = false;
    public int $buffer_time = 15;
    public string $confirmation_email_subject = '';
    public string $confirmation_email_body = '';
    public string $reminder_email_subject = '';
    public string $reminder_email_body = '';
    public int $reminder_hours_before = 24;

    /* ─── Duration options ───────────────────────── */
    public array $durationOptions = [];

    protected function rules(): array
    {
        return [
            'enable_appointments'        => 'boolean',
            'default_duration'           => 'required|string|in:15,30,45,60,90,120',
            'business_hours_start'       => 'required|string|max:5',
            'business_hours_end'         => 'required|string|max:5',
            'allow_online_booking'       => 'boolean',
            'buffer_time'                => 'required|integer|min:0|max:120',
            'confirmation_email_subject' => 'nullable|string|max:255',
            'confirmation_email_body'    => 'nullable|string|max:5000',
            'reminder_email_subject'     => 'nullable|string|max:255',
            'reminder_email_body'        => 'nullable|string|max:5000',
            'reminder_hours_before'      => 'required|integer|min:1|max:168',
        ];
    }

    public function mount($tenant): void
    {
        $this->tenant = $tenant;
        $this->durationOptions = [
            '15'  => '15 minutes',
            '30'  => '30 minutes',
            '45'  => '45 minutes',
            '60'  => '1 hour',
            '90'  => '1.5 hours',
            '120' => '2 hours',
        ];
        $this->loadSettings();
    }

    public function hydrate(): void
    {
        if ($this->tenant instanceof Tenant && is_int($this->tenant->id)) {
            TenantContext::set($this->tenant);
            $branch = $this->tenant->branches()->where('is_default', true)->first();
            if ($branch) {
                BranchContext::set($branch);
            }
        }
    }

    private function loadSettings(): void
    {
        // Load from appointment settings model
        $apptSetting = RepairBuddyAppointmentSetting::first();

        if ($apptSetting) {
            $this->enable_appointments = $apptSetting->is_enabled;
            $this->default_duration    = (string) ($apptSetting->slot_duration_minutes ?? 60);
            $this->buffer_time         = $apptSetting->buffer_minutes ?? 15;

            $timeSlots = $apptSetting->time_slots ?? [];
            $this->business_hours_start = (string) ($timeSlots['start'] ?? '09:00');
            $this->business_hours_end   = (string) ($timeSlots['end'] ?? '17:00');
        }

        // Load email templates from TenantSettingsStore
        $store = new TenantSettingsStore($this->tenant);
        $settings = $store->get('appointments', []);
        if (! is_array($settings)) {
            $settings = [];
        }

        $this->allow_online_booking       = (bool) ($settings['allow_online_booking'] ?? false);
        $this->confirmation_email_subject = (string) ($settings['confirmation_email_subject'] ?? '');
        $this->confirmation_email_body    = (string) ($settings['confirmation_email_body'] ?? '');
        $this->reminder_email_subject     = (string) ($settings['reminder_email_subject'] ?? '');
        $this->reminder_email_body        = (string) ($settings['reminder_email_body'] ?? '');
        $this->reminder_hours_before      = (int) ($settings['reminder_hours_before'] ?? 24);
    }

    public function save(): void
    {
        $this->validate();

        // Save to appointment settings model (upsert)
        RepairBuddyAppointmentSetting::updateOrCreate(
            [
                'tenant_id'  => $this->tenant->id,
                'branch_id'  => BranchContext::branchId(),
            ],
            [
                'is_enabled'             => $this->enable_appointments,
                'slot_duration_minutes'  => (int) $this->default_duration,
                'buffer_minutes'         => $this->buffer_time,
                'time_slots'             => [
                    'start' => $this->business_hours_start,
                    'end'   => $this->business_hours_end,
                ],
            ]
        );

        // Save email templates to TenantSettingsStore
        $store = new TenantSettingsStore($this->tenant);

        $store->merge('appointments', [
            'allow_online_booking'       => $this->allow_online_booking,
            'confirmation_email_subject' => $this->confirmation_email_subject,
            'confirmation_email_body'    => $this->confirmation_email_body,
            'reminder_email_subject'     => $this->reminder_email_subject,
            'reminder_email_body'        => $this->reminder_email_body,
            'reminder_hours_before'      => $this->reminder_hours_before,
        ]);

        $store->save();

        $this->dispatch('settings-saved', message: 'Appointment settings saved successfully.');
    }

    public function render()
    {
        return view('livewire.tenant.settings.sections.appointment-settings');
    }
}
