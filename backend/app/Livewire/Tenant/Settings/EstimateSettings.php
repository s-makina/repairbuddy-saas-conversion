<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\Tenant;
use App\Services\TenantSettings\TenantSettingsStore;
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

    protected function rules(): array
    {
        return [
            'estimates_enabled'      => 'boolean',
            'booking_forms_to_jobs'  => 'boolean',
            'estimate_valid_days'    => 'required|integer|min:1|max:365',
            'email_subject_customer' => 'nullable|string|max:255',
            'email_body_customer'    => 'nullable|string|max:5000',
            'approve_email_subject'  => 'nullable|string|max:255',
            'approve_email_body'     => 'nullable|string|max:5000',
            'reject_email_subject'   => 'nullable|string|max:255',
            'reject_email_body'      => 'nullable|string|max:5000',
        ];
    }

    public function mount($tenant): void
    {
        $this->tenant = $tenant;
        $this->loadSettings();
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

    private function loadSettings(): void
    {
        $store = new TenantSettingsStore($this->tenant);
        $settings = $store->get('estimates', []);
        if (! is_array($settings)) {
            $settings = [];
        }

        $this->estimates_enabled      = (bool) ($settings['estimates_enabled'] ?? true);
        $this->booking_forms_to_jobs  = (bool) ($settings['booking_forms_to_jobs'] ?? false);
        $this->estimate_valid_days    = (int) ($settings['estimate_valid_days'] ?? 30);
        $this->email_subject_customer = (string) ($settings['email_subject_customer'] ?? '');
        $this->email_body_customer    = (string) ($settings['email_body_customer'] ?? '');
        $this->approve_email_subject  = (string) ($settings['approve_email_subject'] ?? '');
        $this->approve_email_body     = (string) ($settings['approve_email_body'] ?? '');
        $this->reject_email_subject   = (string) ($settings['reject_email_subject'] ?? '');
        $this->reject_email_body      = (string) ($settings['reject_email_body'] ?? '');
    }

    public function save(): void
    {
        $this->validate();

        $store = new TenantSettingsStore($this->tenant);

        $store->merge('estimates', [
            'estimates_enabled'      => $this->estimates_enabled,
            'booking_forms_to_jobs'  => $this->booking_forms_to_jobs,
            'estimate_valid_days'    => $this->estimate_valid_days,
            'email_subject_customer' => $this->email_subject_customer,
            'email_body_customer'    => $this->email_body_customer,
            'approve_email_subject'  => $this->approve_email_subject,
            'approve_email_body'     => $this->approve_email_body,
            'reject_email_subject'   => $this->reject_email_subject,
            'reject_email_body'      => $this->reject_email_body,
        ]);

        $store->save();

        $this->dispatch('settings-saved', message: 'Estimate settings saved successfully.');
    }

    public function render()
    {
        return view('livewire.tenant.settings.sections.estimate-settings');
    }
}
