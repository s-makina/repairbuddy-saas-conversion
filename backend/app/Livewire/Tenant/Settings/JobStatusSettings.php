<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\Tenant;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Livewire\Component;

class JobStatusSettings extends Component
{
    public $tenant;

    /* ─── Status Table Data (UI-only for now) ────── */
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

    public function mount($tenant): void
    {
        $this->tenant = $tenant;
        // TODO: Load statuses from DB
        $this->statuses = [];
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

    public function openAddModal(): void
    {
        $this->resetModal();
        $this->showAddModal = true;
    }

    public function openEditModal(int $statusId): void
    {
        $this->resetModal();
        $this->editingStatusId = $statusId;
        // TODO: populate modal fields from status record
        $this->showEditModal = true;
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
    }

    public function saveStatus(): void
    {
        // TODO: Wire functionality
        $this->closeModal();
        $this->dispatch('settings-saved', message: 'Status saved successfully.');
    }

    public function saveSettings(): void
    {
        // TODO: Wire functionality
        $this->dispatch('settings-saved', message: 'Job status settings saved successfully.');
    }

    public function render()
    {
        return view('livewire.tenant.settings.sections.job-status-settings');
    }
}
