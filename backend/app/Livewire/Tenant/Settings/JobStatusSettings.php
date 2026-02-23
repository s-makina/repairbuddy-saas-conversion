<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\RepairBuddyJobStatus;
use App\Models\Tenant;
use App\Services\TenantSettings\TenantSettingsStore;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Illuminate\Support\Str;
use Livewire\Component;

class JobStatusSettings extends Component
{
    public $tenant;

    /* ─── Status Table Data ──────────────────────── */
    public array $statuses = [];

    /* ─── Status Settings ────────────────────────── */
    public string $status_considered_completed = '';
    public string $status_considered_cancelled = '';

    /* ─── Modal State ────────────────────────────── */
    public bool $showAddModal = false;
    public bool $showEditModal = false;
    public ?int $editingStatusId = null;

    /* ─── Add/Edit Form ──────────────────────────── */
    public string $modal_status_name = '';
    public string $modal_status_description = '';
    public string $modal_invoice_label = 'Invoice';
    public string $modal_status_active = 'active';
    public string $modal_email_message = '';

    protected function rules(): array
    {
        return [
            'modal_status_name'        => 'required|string|max:100',
            'modal_status_description' => 'nullable|string|max:255',
            'modal_invoice_label'      => 'nullable|string|max:100',
            'modal_status_active'      => 'required|in:active,inactive',
            'modal_email_message'      => 'nullable|string|max:5000',
        ];
    }

    protected array $validationAttributes = [
        'modal_status_name'        => 'status name',
        'modal_status_description' => 'description',
        'modal_invoice_label'      => 'invoice label',
        'modal_email_message'      => 'email message',
    ];

    public function mount($tenant): void
    {
        $this->tenant = $tenant;
        $this->loadStatuses();
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

    private function loadStatuses(): void
    {
        $this->statuses = RepairBuddyJobStatus::query()
            ->orderBy('id')
            ->get()
            ->map(fn ($s) => [
                'id'            => $s->id,
                'name'          => $s->label,
                'slug'          => $s->slug,
                'description'   => $s->email_template ? Str::limit(strip_tags($s->email_template), 60) : '',
                'invoice_label' => $s->invoice_label ?? 'Invoice',
                'is_active'     => $s->is_active,
                'email_enabled' => $s->email_enabled,
            ])
            ->toArray();
    }

    private function loadSettings(): void
    {
        $store = new TenantSettingsStore($this->tenant);
        $settings = $store->get('job_status', []);
        if (! is_array($settings)) {
            $settings = [];
        }

        $this->status_considered_completed = (string) ($settings['status_considered_completed'] ?? '');
        $this->status_considered_cancelled = (string) ($settings['status_considered_cancelled'] ?? '');
    }

    public function openAddModal(): void
    {
        $this->resetModal();
        $this->showAddModal = true;
    }

    public function openEditModal(int $statusId): void
    {
        $this->resetModal();

        $status = RepairBuddyJobStatus::find($statusId);
        if (! $status) {
            return;
        }

        $this->editingStatusId         = $status->id;
        $this->modal_status_name       = $status->label;
        $this->modal_status_description = ''; // description is derived from email_template
        $this->modal_invoice_label     = $status->invoice_label ?? 'Invoice';
        $this->modal_status_active     = $status->is_active ? 'active' : 'inactive';
        $this->modal_email_message     = $status->email_template ?? '';
        $this->showEditModal           = true;
    }

    public function closeModal(): void
    {
        $this->showAddModal = false;
        $this->showEditModal = false;
        $this->resetModal();
    }

    private function resetModal(): void
    {
        $this->modal_status_name = '';
        $this->modal_status_description = '';
        $this->modal_invoice_label = 'Invoice';
        $this->modal_status_active = 'active';
        $this->modal_email_message = '';
        $this->editingStatusId = null;
        $this->resetValidation();
    }

    public function saveStatus(): void
    {
        $this->validate();

        $slug = Str::slug($this->modal_status_name, '_');

        if ($this->editingStatusId) {
            // Update existing
            $status = RepairBuddyJobStatus::find($this->editingStatusId);
            if (! $status) {
                $this->dispatch('settings-saved', message: 'Status not found.');
                return;
            }

            // Ensure slug uniqueness (excluding current record)
            $slugExists = RepairBuddyJobStatus::where('slug', $slug)
                ->where('id', '!=', $status->id)
                ->exists();

            $status->update([
                'label'          => $this->modal_status_name,
                'slug'           => $slugExists ? $status->slug : $slug,
                'invoice_label'  => $this->modal_invoice_label,
                'is_active'      => $this->modal_status_active === 'active',
                'email_enabled'  => ! empty($this->modal_email_message),
                'email_template' => $this->modal_email_message,
            ]);
        } else {
            // Ensure slug uniqueness
            $originalSlug = $slug;
            $counter = 1;
            while (RepairBuddyJobStatus::where('slug', $slug)->exists()) {
                $slug = $originalSlug.'_'.$counter;
                $counter++;
            }

            RepairBuddyJobStatus::create([
                'label'          => $this->modal_status_name,
                'slug'           => $slug,
                'invoice_label'  => $this->modal_invoice_label,
                'is_active'      => $this->modal_status_active === 'active',
                'email_enabled'  => ! empty($this->modal_email_message),
                'email_template' => $this->modal_email_message,
            ]);
        }

        $this->loadStatuses();
        $this->closeModal();
        $this->dispatch('settings-saved', message: 'Job status saved successfully.');
    }

    public function deleteStatus(int $statusId): void
    {
        $status = RepairBuddyJobStatus::find($statusId);
        if ($status) {
            $status->delete();
            $this->loadStatuses();
            $this->dispatch('settings-saved', message: 'Job status deleted.');
        }
    }

    public function saveSettings(): void
    {
        $this->validate([
            'status_considered_completed' => 'nullable|string',
            'status_considered_cancelled' => 'nullable|string',
        ]);

        $store = new TenantSettingsStore($this->tenant);

        $store->merge('job_status', [
            'status_considered_completed' => $this->status_considered_completed,
            'status_considered_cancelled' => $this->status_considered_cancelled,
        ]);

        $store->save();

        $this->dispatch('settings-saved', message: 'Job status settings saved successfully.');
    }

    public function render()
    {
        return view('livewire.tenant.settings.sections.job-status-settings');
    }
}
