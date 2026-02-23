<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\Tenant;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Livewire\Component;

class PaymentStatusSettings extends Component
{
    public $tenant;

    /* ─── Payment Status Table ───────────────────── */
    public array $paymentStatuses = [];

    /* ─── Payment Methods ────────────────────────── */
    public bool $method_cash = true;
    public bool $method_card = false;
    public bool $method_bank_transfer = false;

    /* ─── Modal State ────────────────────────────── */
    public bool $showModal = false;
    public ?int $editingId = null;
    public string $modal_name = '';
    public string $modal_active = 'active';

    public function mount($tenant): void
    {
        $this->tenant = $tenant;
        // TODO: Load payment statuses and methods from DB
    }

    public function hydrate(): void
    {
        if ($this->tenant instanceof Tenant && is_int($this->tenant->id)) {
            TenantContext::set($this->tenant);
            $branch = $this->tenant->branches()->where('is_default', true)->first();
            if ($branch) { BranchContext::set($branch); }
        }
    }

    public function openAddModal(): void
    {
        $this->editingId = null;
        $this->modal_name = '';
        $this->modal_active = 'active';
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $this->editingId = $id;
        // TODO: load from record
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->editingId = null;
        $this->modal_name = '';
        $this->modal_active = 'active';
    }

    public function savePaymentStatus(): void
    {
        // TODO: Wire functionality
        $this->closeModal();
        $this->dispatch('settings-saved', message: 'Payment status saved.');
    }

    public function saveMethods(): void
    {
        // TODO: Wire functionality
        $this->dispatch('settings-saved', message: 'Payment methods updated.');
    }

    public function render()
    {
        return view('livewire.tenant.settings.sections.payment-status-settings');
    }
}
