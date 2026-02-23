<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\RepairBuddyPaymentStatus;
use App\Models\Tenant;
use App\Services\TenantSettings\TenantSettingsStore;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Illuminate\Support\Str;
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
    public string $modal_description = '';
    public string $modal_active = 'active';

    protected function rules(): array
    {
        return [
            'modal_name'        => 'required|string|max:100',
            'modal_description' => 'nullable|string|max:255',
            'modal_active'      => 'required|in:active,inactive',
        ];
    }

    protected array $validationAttributes = [
        'modal_name' => 'status name',
    ];

    public function mount($tenant): void
    {
        $this->tenant = $tenant;
        $this->loadPaymentStatuses();
        $this->loadPaymentMethods();
    }

    public function hydrate(): void
    {
        if ($this->tenant instanceof Tenant && is_int($this->tenant->id)) {
            TenantContext::set($this->tenant);
            $branch = $this->tenant->defaultBranch;
            if ($branch) {
                BranchContext::set($branch);
            }
        }
    }

    private function loadPaymentStatuses(): void
    {
        $this->paymentStatuses = RepairBuddyPaymentStatus::query()
            ->orderBy('id')
            ->get()
            ->map(fn ($s) => [
                'id'          => $s->id,
                'name'        => $s->label,
                'slug'        => $s->slug,
                'description' => $s->description ?? '',
                'is_active'   => $s->is_active,
            ])
            ->toArray();
    }

    private function loadPaymentMethods(): void
    {
        $store = new TenantSettingsStore($this->tenant);
        $settings = $store->get('payment_methods', []);
        if (! is_array($settings)) {
            $settings = [];
        }

        $this->method_cash          = (bool) ($settings['cash'] ?? true);
        $this->method_card          = (bool) ($settings['card'] ?? false);
        $this->method_bank_transfer = (bool) ($settings['bank_transfer'] ?? false);
    }

    public function openAddModal(): void
    {
        $this->editingId = null;
        $this->modal_name = '';
        $this->modal_description = '';
        $this->modal_active = 'active';
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $status = RepairBuddyPaymentStatus::find($id);
        if (! $status) {
            return;
        }

        $this->editingId        = $status->id;
        $this->modal_name       = $status->label;
        $this->modal_description = $status->description ?? '';
        $this->modal_active     = $status->is_active ? 'active' : 'inactive';
        $this->resetValidation();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->editingId = null;
        $this->modal_name = '';
        $this->modal_description = '';
        $this->modal_active = 'active';
        $this->resetValidation();
    }

    public function savePaymentStatus(): void
    {
        $this->validate();

        $slug = Str::slug($this->modal_name, '_');

        if ($this->editingId) {
            $status = RepairBuddyPaymentStatus::find($this->editingId);
            if (! $status) {
                $this->dispatch('settings-saved', message: 'Payment status not found.');
                return;
            }

            // Ensure slug uniqueness (excluding current record)
            $slugExists = RepairBuddyPaymentStatus::where('slug', $slug)
                ->where('id', '!=', $status->id)
                ->exists();

            $status->update([
                'label'       => $this->modal_name,
                'slug'        => $slugExists ? $status->slug : $slug,
                'description' => $this->modal_description,
                'is_active'   => $this->modal_active === 'active',
            ]);
        } else {
            // Ensure slug uniqueness
            $originalSlug = $slug;
            $counter = 1;
            while (RepairBuddyPaymentStatus::where('slug', $slug)->exists()) {
                $slug = $originalSlug.'_'.$counter;
                $counter++;
            }

            RepairBuddyPaymentStatus::create([
                'label'       => $this->modal_name,
                'slug'        => $slug,
                'description' => $this->modal_description,
                'is_active'   => $this->modal_active === 'active',
            ]);
        }

        $this->loadPaymentStatuses();
        $this->closeModal();
        $this->dispatch('settings-saved', message: 'Payment status saved successfully.');
    }

    public function deletePaymentStatus(int $id): void
    {
        $status = RepairBuddyPaymentStatus::find($id);
        if ($status) {
            $status->delete();
            $this->loadPaymentStatuses();
            $this->dispatch('settings-saved', message: 'Payment status deleted.');
        }
    }

    public function saveMethods(): void
    {
        $store = new TenantSettingsStore($this->tenant);

        $store->merge('payment_methods', [
            'cash'          => $this->method_cash,
            'card'          => $this->method_card,
            'bank_transfer' => $this->method_bank_transfer,
        ]);

        $store->save();

        $this->dispatch('settings-saved', message: 'Payment methods updated successfully.');
    }

    public function render()
    {
        return view('livewire.tenant.settings.sections.payment-status-settings');
    }
}
