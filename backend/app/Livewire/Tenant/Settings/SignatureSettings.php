<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\Tenant;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Livewire\Component;

class SignatureSettings extends Component
{
    public $tenant;

    /* ─── Pickup Signature ───────────────────────── */
    public bool $pickup_enabled = false;
    public string $pickup_trigger_status = '';
    public string $pickup_after_status = '';
    public string $pickup_email_subject = '';
    public string $pickup_email_template = '';
    public string $pickup_sms_text = '';

    /* ─── Delivery Signature ─────────────────────── */
    public bool $delivery_enabled = false;
    public string $delivery_trigger_status = '';
    public string $delivery_after_status = '';
    public string $delivery_email_subject = '';
    public string $delivery_email_template = '';
    public string $delivery_sms_text = '';

    /* ─── Status options (for dropdowns) ─────────── */
    public array $statusOptions = [];

    public function mount($tenant): void
    {
        $this->tenant = $tenant;
        // TODO: Load settings & populate statusOptions from DB
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
        $this->dispatch('settings-saved', message: 'Signature workflow settings saved.');
    }

    public function render()
    {
        return view('livewire.tenant.settings.sections.signature-settings');
    }
}
