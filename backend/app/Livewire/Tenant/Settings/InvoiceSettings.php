<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\Tenant;
use App\Services\TenantSettings\TenantSettingsStore;
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

    protected function rules(): array
    {
        return [
            'add_qr_code'              => 'boolean',
            'invoice_footer_message'   => 'nullable|string|max:500',
            'invoice_type'             => 'required|string|in:by_items,by_devices,by_items_only',
            'show_pickup_date'         => 'boolean',
            'show_delivery_date'       => 'boolean',
            'show_next_service_date'   => 'boolean',
            'invoice_disclaimer'       => 'nullable|string|max:2000',
            'repair_order_type'        => 'required|string|in:pos,invoice',
            'repair_order_print_size'  => 'required|string|in:default,a4,a5',
            'repair_order_terms'       => 'nullable|string|max:500',
            'repair_order_footer'      => 'nullable|string|max:500',
            'display_business_address' => 'boolean',
            'display_customer_email'   => 'boolean',
        ];
    }

    public function mount($tenant): void
    {
        $this->tenant = $tenant;
        $this->loadOptions();
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

    private function loadOptions(): void
    {
        $this->invoiceTypeOptions = [
            'by_items'      => 'Default (By Items)',
            'by_devices'    => 'By Devices',
            'by_items_only' => 'By Items Only',
        ];

        $this->repairOrderTypeOptions = [
            'pos'     => 'POS Type',
            'invoice' => 'Invoice Type',
        ];

        $this->printSizeOptions = [
            'default' => 'Default (POS)',
            'a4'      => 'A4',
            'a5'      => 'A5',
        ];
    }

    private function loadSettings(): void
    {
        $store = new TenantSettingsStore($this->tenant);
        $settings = $store->get('invoices', []);
        if (! is_array($settings)) {
            $settings = [];
        }

        $this->add_qr_code              = (bool) ($settings['add_qr_code'] ?? false);
        $this->invoice_footer_message   = (string) ($settings['invoice_footer_message'] ?? '');
        $this->invoice_type             = (string) ($settings['invoice_type'] ?? 'by_items');
        $this->show_pickup_date         = (bool) ($settings['show_pickup_date'] ?? false);
        $this->show_delivery_date       = (bool) ($settings['show_delivery_date'] ?? false);
        $this->show_next_service_date   = (bool) ($settings['show_next_service_date'] ?? false);
        $this->invoice_disclaimer       = (string) ($settings['invoice_disclaimer'] ?? '');
        $this->repair_order_type        = (string) ($settings['repair_order_type'] ?? 'pos');
        $this->repair_order_print_size  = (string) ($settings['repair_order_print_size'] ?? 'default');
        $this->repair_order_terms       = (string) ($settings['repair_order_terms'] ?? '');
        $this->repair_order_footer      = (string) ($settings['repair_order_footer'] ?? '');
        $this->display_business_address = (bool) ($settings['display_business_address'] ?? false);
        $this->display_customer_email   = (bool) ($settings['display_customer_email'] ?? false);
    }

    public function save(): void
    {
        $this->validate();

        $store = new TenantSettingsStore($this->tenant);

        $store->merge('invoices', [
            'add_qr_code'              => $this->add_qr_code,
            'invoice_footer_message'   => $this->invoice_footer_message,
            'invoice_type'             => $this->invoice_type,
            'show_pickup_date'         => $this->show_pickup_date,
            'show_delivery_date'       => $this->show_delivery_date,
            'show_next_service_date'   => $this->show_next_service_date,
            'invoice_disclaimer'       => $this->invoice_disclaimer,
            'repair_order_type'        => $this->repair_order_type,
            'repair_order_print_size'  => $this->repair_order_print_size,
            'repair_order_terms'       => $this->repair_order_terms,
            'repair_order_footer'      => $this->repair_order_footer,
            'display_business_address' => $this->display_business_address,
            'display_customer_email'   => $this->display_customer_email,
        ]);

        $store->save();

        $this->dispatch('settings-saved', message: 'Invoice settings saved successfully.');
    }

    public function render()
    {
        return view('livewire.tenant.settings.sections.invoice-settings');
    }
}
