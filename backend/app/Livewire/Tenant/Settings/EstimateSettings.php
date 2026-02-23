<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\Tenant;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Livewire\Component;

class EstimateSettings extends Component
{
    public $tenant;

    /* ─── General ────────────────────────────────── */
    public bool $estimates_enabled = true;
    public bool $booking_forms_to_jobs = false;
    public int $estimate_valid_days = 30;

    /* ─── Email to Customer ──────────────────────── */
    public string $email_subject_customer = '';
    public string $email_body_customer = '';

    /* ─── Approve email to Admin ─────────────────── */
    public string $approve_email_subject = '';
    public string $approve_email_body = '';

    /* ─── Reject email to Admin ──────────────────── */
    public string $reject_email_subject = '';
    public string $reject_email_body = '';

    public function mount($tenant): void
    {
        $this->tenant = $tenant;
        // TODO: Load from TenantSettingsStore
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
        $this->dispatch('settings-saved', message: 'Estimate settings saved.');
    }

    public function render()
    {
        return view('livewire.tenant.settings.sections.estimate-settings');
    }
}
