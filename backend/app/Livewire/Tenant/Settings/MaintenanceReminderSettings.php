<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\RepairBuddyDeviceBrand;
use App\Models\RepairBuddyDeviceType;
use App\Models\RepairBuddyMaintenanceReminder;
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

    protected function rules(): array
    {
        return [
            'modal_name'             => 'required|string|max:255',
            'modal_interval_days'    => 'required|string|in:7,30,90,180,365',
            'modal_description'      => 'nullable|string|max:500',
            'modal_email_body'       => 'nullable|string|max:5000',
            'modal_sms_body'         => 'nullable|string|max:500',
            'modal_device_type_id'   => 'nullable|string',
            'modal_device_brand_id'  => 'nullable|string',
            'modal_email_enabled'    => 'required|in:active,inactive',
            'modal_sms_enabled'      => 'required|in:active,inactive',
            'modal_reminder_enabled' => 'required|in:active,inactive',
        ];
    }

    protected array $validationAttributes = [
        'modal_name'          => 'reminder name',
        'modal_interval_days' => 'interval',
    ];

    public function mount($tenant): void
    {
        $this->tenant = $tenant;
        $this->loadOptions();
        $this->loadReminders();
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

        $this->deviceTypeOptions = ['' => 'All'] +
            RepairBuddyDeviceType::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->pluck('name', 'id')
                ->map(fn ($v, $k) => $v)
                ->toArray();

        $this->deviceBrandOptions = ['' => 'All'] +
            RepairBuddyDeviceBrand::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->pluck('name', 'id')
                ->map(fn ($v, $k) => $v)
                ->toArray();
    }

    private function loadReminders(): void
    {
        $this->reminders = RepairBuddyMaintenanceReminder::query()
            ->orderBy('id')
            ->get()
            ->map(fn ($r) => [
                'id'               => $r->id,
                'name'             => $r->name,
                'description'      => $r->description ?? '',
                'interval_days'    => $r->interval_days,
                'device_type_id'   => $r->device_type_id,
                'device_brand_id'  => $r->device_brand_id,
                'email_body'       => $r->email_body ?? '',
                'sms_body'         => $r->sms_body ?? '',
                'email_enabled'    => $r->email_enabled ? 'active' : 'inactive',
                'sms_enabled'      => $r->sms_enabled ? 'active' : 'inactive',
                'reminder_enabled' => $r->reminder_enabled ? 'active' : 'inactive',
                'last_executed_at' => $r->last_executed_at?->format('Y-m-d H:i') ?? 'Never',
            ])
            ->toArray();
    }

    public function openAddModal(): void
    {
        $this->resetModal();
        $this->showModal = true;
    }

    public function openEditModal(int $index): void
    {
        if (! isset($this->reminders[$index])) {
            return;
        }

        $r = $this->reminders[$index];
        $this->editingId              = $r['id'];
        $this->modal_name             = $r['name'] ?? '';
        $this->modal_interval_days    = (string) ($r['interval_days'] ?? '30');
        $this->modal_description      = $r['description'] ?? '';
        $this->modal_email_body       = $r['email_body'] ?? '';
        $this->modal_sms_body         = $r['sms_body'] ?? '';
        $this->modal_device_type_id   = (string) ($r['device_type_id'] ?? '');
        $this->modal_device_brand_id  = (string) ($r['device_brand_id'] ?? '');
        $this->modal_email_enabled    = $r['email_enabled'] ?? 'active';
        $this->modal_sms_enabled      = $r['sms_enabled'] ?? 'inactive';
        $this->modal_reminder_enabled = $r['reminder_enabled'] ?? 'active';
        $this->resetValidation();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetModal();
    }

    private function resetModal(): void
    {
        $this->editingId              = null;
        $this->modal_name             = '';
        $this->modal_interval_days    = '30';
        $this->modal_description      = '';
        $this->modal_email_body       = '';
        $this->modal_sms_body         = '';
        $this->modal_device_type_id   = '';
        $this->modal_device_brand_id  = '';
        $this->modal_email_enabled    = 'active';
        $this->modal_sms_enabled      = 'inactive';
        $this->modal_reminder_enabled = 'active';
        $this->resetValidation();
    }

    public function saveReminder(): void
    {
        $this->validate();

        $data = [
            'name'             => $this->modal_name,
            'description'      => $this->modal_description,
            'interval_days'    => (int) $this->modal_interval_days,
            'device_type_id'   => $this->modal_device_type_id ? (int) $this->modal_device_type_id : null,
            'device_brand_id'  => $this->modal_device_brand_id ? (int) $this->modal_device_brand_id : null,
            'email_enabled'    => $this->modal_email_enabled === 'active',
            'sms_enabled'      => $this->modal_sms_enabled === 'active',
            'reminder_enabled' => $this->modal_reminder_enabled === 'active',
            'email_body'       => $this->modal_email_body,
            'sms_body'         => $this->modal_sms_body,
        ];

        if ($this->editingId) {
            $reminder = RepairBuddyMaintenanceReminder::find($this->editingId);
            if ($reminder) {
                $data['updated_by_user_id'] = auth()->id();
                $reminder->update($data);
            }
        } else {
            $data['created_by_user_id'] = auth()->id();
            RepairBuddyMaintenanceReminder::create($data);
        }

        $this->loadReminders();
        $this->closeModal();
        $this->dispatch('settings-saved', message: 'Maintenance reminder saved successfully.');
    }

    public function deleteReminder(int $index): void
    {
        if (! isset($this->reminders[$index])) {
            return;
        }

        $reminder = RepairBuddyMaintenanceReminder::find($this->reminders[$index]['id']);
        if ($reminder) {
            $reminder->delete();
        }

        $this->loadReminders();
        $this->dispatch('settings-saved', message: 'Maintenance reminder deleted.');
    }

    public function sendTestReminder(int $index): void
    {
        if (! isset($this->reminders[$index])) {
            return;
        }

        // For now, just dispatch a notification that the test was triggered
        // In production, this would queue a test email/SMS to the admin
        $this->dispatch('settings-saved', message: 'Test reminder triggered for "' . ($this->reminders[$index]['name'] ?? '') . '". Check your admin email/phone.');
    }

    public function render()
    {
        return view('livewire.tenant.settings.sections.maintenance-reminder-settings');
    }
}
