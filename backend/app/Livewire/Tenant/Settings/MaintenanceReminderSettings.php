<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\Tenant;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Livewire\Component;

class MaintenanceReminderSettings extends Component
{
    public $tenant;

    /* ─── Reminders List (CRUD) ──────────────────── */
    public array $reminders = [];

    /* ─── Modal State ────────────────────────────── */
    public bool $showModal = false;
    public ?int $editingId = null;

    /* ─── Modal Fields ───────────────────────────── */
    public string $modal_name = '';
    public string $modal_interval_days = '30';
    public string $modal_description = '';
    public string $modal_email_body = '';
    public string $modal_sms_body = '';
    public string $modal_device_type_id = '';
    public string $modal_device_brand_id = '';
    public string $modal_email_enabled = 'active';
    public string $modal_sms_enabled = 'inactive';
    public string $modal_reminder_enabled = 'active';

    /* ─── Options ────────────────────────────────── */
    public array $intervalOptions = [];
    public array $deviceTypeOptions = [];
    public array $deviceBrandOptions = [];

    public function mount($tenant): void
    {
        $this->tenant = $tenant;
        $this->loadOptions();
        // TODO: Load reminders from database
        $this->reminders = [];
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

    private function loadOptions(): void
    {
        $this->intervalOptions = [
            '7'   => '7 Days',
            '30'  => '30 Days',
            '90'  => '90 Days',
            '180' => '180 Days',
            '365' => '365 Days',
        ];

        // TODO: Populate from database
        $this->deviceTypeOptions = ['' => 'All'];
        $this->deviceBrandOptions = ['' => 'All'];
    }

    public function openAddModal(): void
    {
        $this->resetModal();
        $this->showModal = true;
    }

    public function openEditModal(int $index): void
    {
        if (!isset($this->reminders[$index])) {
            return;
        }

        $r = $this->reminders[$index];
        $this->editingId = $index;
        $this->modal_name = $r['name'] ?? '';
        $this->modal_interval_days = (string) ($r['interval_days'] ?? '30');
        $this->modal_description = $r['description'] ?? '';
        $this->modal_email_body = $r['email_body'] ?? '';
        $this->modal_sms_body = $r['sms_body'] ?? '';
        $this->modal_device_type_id = (string) ($r['device_type_id'] ?? '');
        $this->modal_device_brand_id = (string) ($r['device_brand_id'] ?? '');
        $this->modal_email_enabled = $r['email_enabled'] ?? 'active';
        $this->modal_sms_enabled = $r['sms_enabled'] ?? 'inactive';
        $this->modal_reminder_enabled = $r['reminder_enabled'] ?? 'active';
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetModal();
    }

    private function resetModal(): void
    {
        $this->editingId = null;
        $this->modal_name = '';
        $this->modal_interval_days = '30';
        $this->modal_description = '';
        $this->modal_email_body = '';
        $this->modal_sms_body = '';
        $this->modal_device_type_id = '';
        $this->modal_device_brand_id = '';
        $this->modal_email_enabled = 'active';
        $this->modal_sms_enabled = 'inactive';
        $this->modal_reminder_enabled = 'active';
    }

    public function saveReminder(): void
    {
        // TODO: Wire functionality — validate & persist
        $this->closeModal();
        $this->dispatch('settings-saved', message: 'Reminder saved.');
    }

    public function deleteReminder(int $index): void
    {
        // TODO: Wire functionality — delete from DB
        if (isset($this->reminders[$index])) {
            array_splice($this->reminders, $index, 1);
        }
    }

    public function sendTestReminder(int $index): void
    {
        // TODO: Wire functionality — send test reminder to admin
        $this->dispatch('settings-saved', message: 'Test reminder sent to admin.');
    }

    public function render()
    {
        return view('livewire.tenant.settings.sections.maintenance-reminder-settings');
    }
}
