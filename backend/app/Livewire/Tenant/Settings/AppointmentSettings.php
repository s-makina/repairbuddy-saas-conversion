<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\Tenant;
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

    public function mount($tenant): void
    {
        $this->tenant = $tenant;
        $this->durationOptions = [
            '15' => '15 minutes',
            '30' => '30 minutes',
            '45' => '45 minutes',
            '60' => '1 hour',
            '90' => '1.5 hours',
            '120' => '2 hours',
        ];
        // TODO: Load settings from TenantSettingsStore
    }

    public function hydrate(): void
    {
        if ($this->tenant instanceof Tenant && is_int($this->tenant->id)) {
            TenantContext::set($this->tenant);
            $branch = $this->tenant->branches()->where('is_default', true)->first();
            if ($branch) { BranchContext::set($branch); }
        }
    }

    public function save(): void
    {
        // TODO: Wire functionality
        $this->dispatch('settings-saved', message: 'Appointment settings saved.');
    }

    public function render()
    {
        return view('livewire.tenant.settings.sections.appointment-settings');
    }
}
