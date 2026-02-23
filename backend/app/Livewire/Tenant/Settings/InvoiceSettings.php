<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\Tenant;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Livewire\Component;

class InvoiceSettings extends Component
{
    public $tenant;

    /* ─── Print Invoice Settings ─────────────────── */
    public bool $add_qr_code = false;
    public string $invoice_footer_message = '';
    public string $invoice_type = 'by_items';
    public bool $show_pickup_date = false;
    public bool $show_delivery_date = false;
    public bool $show_next_service_date = false;
    public string $invoice_disclaimer = '';

    /* ─── Repair Order Settings ──────────────────── */
    public string $repair_order_type = 'pos';
    public string $repair_order_print_size = 'default';
    public string $repair_order_terms = '';
    public string $repair_order_footer = '';
    public bool $display_business_address = false;
    public bool $display_customer_email = false;

    /* ─── Select Options ─────────────────────────── */
    public array $invoiceTypeOptions = [];
    public array $repairOrderTypeOptions = [];
    public array $printSizeOptions = [];

    public function mount($tenant): void
    {
        $this->tenant = $tenant;
        $this->loadOptions();
        // TODO: loadSettings from TenantSettingsStore
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
        $this->invoiceTypeOptions = [
            'by_items' => 'Default (By Items)',
            'by_devices' => 'By Devices',
            'by_items_only' => 'By Items Only',
        ];

        $this->repairOrderTypeOptions = [
            'pos' => 'POS Type',
            'invoice' => 'Invoice Type',
        ];

        $this->printSizeOptions = [
            'default' => 'Default (POS)',
            'a4' => 'A4',
            'a5' => 'A5',
        ];
    }

    public function save(): void
    {
        // TODO: Wire functionality
        $this->dispatch('settings-saved', message: 'Invoice settings saved successfully.');
    }

    public function render()
    {
        return view('livewire.tenant.settings.sections.invoice-settings');
    }
}
