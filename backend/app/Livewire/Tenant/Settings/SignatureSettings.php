<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\RepairBuddyJobStatus;
use App\Models\Tenant;
use App\Services\TenantSettings\TenantSettingsStore;
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

    protected function rules(): array
    {
        return [
            'pickup_enabled'           => 'boolean',
            'pickup_trigger_status'    => 'nullable|string|max:100',
            'pickup_after_status'      => 'nullable|string|max:100',
            'pickup_email_subject'     => 'nullable|string|max:255',
            'pickup_email_template'    => 'nullable|string|max:5000',
            'pickup_sms_text'          => 'nullable|string|max:500',
            'delivery_enabled'         => 'boolean',
            'delivery_trigger_status'  => 'nullable|string|max:100',
            'delivery_after_status'    => 'nullable|string|max:100',
            'delivery_email_subject'   => 'nullable|string|max:255',
            'delivery_email_template'  => 'nullable|string|max:5000',
            'delivery_sms_text'        => 'nullable|string|max:500',
        ];
    }

    public function mount($tenant): void
    {
        $this->tenant = $tenant;
        $this->loadStatusOptions();
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

    private function loadStatusOptions(): void
    {
        $this->statusOptions = ['' => '— Select Status —'] +
            RepairBuddyJobStatus::query()
                ->where('is_active', true)
                ->orderBy('id')
                ->pluck('label', 'slug')
                ->toArray();
    }

    private function loadSettings(): void
    {
        $store = new TenantSettingsStore($this->tenant);
        $settings = $store->get('signature', []);
        if (! is_array($settings)) {
            $settings = [];
        }

        $this->pickup_enabled          = (bool) ($settings['pickup_enabled'] ?? false);
        $this->pickup_trigger_status   = (string) ($settings['pickup_trigger_status'] ?? '');
        $this->pickup_after_status     = (string) ($settings['pickup_after_status'] ?? '');
        $this->pickup_email_subject    = (string) ($settings['pickup_email_subject'] ?? '');
        $this->pickup_email_template   = (string) ($settings['pickup_email_template'] ?? '');
        $this->pickup_sms_text         = (string) ($settings['pickup_sms_text'] ?? '');
        $this->delivery_enabled        = (bool) ($settings['delivery_enabled'] ?? false);
        $this->delivery_trigger_status = (string) ($settings['delivery_trigger_status'] ?? '');
        $this->delivery_after_status   = (string) ($settings['delivery_after_status'] ?? '');
        $this->delivery_email_subject  = (string) ($settings['delivery_email_subject'] ?? '');
        $this->delivery_email_template = (string) ($settings['delivery_email_template'] ?? '');
        $this->delivery_sms_text       = (string) ($settings['delivery_sms_text'] ?? '');
    }

    public function save(): void
    {
        $this->validate();

        $store = new TenantSettingsStore($this->tenant);

        $store->merge('signature', [
            'pickup_enabled'          => $this->pickup_enabled,
            'pickup_trigger_status'   => $this->pickup_trigger_status,
            'pickup_after_status'     => $this->pickup_after_status,
            'pickup_email_subject'    => $this->pickup_email_subject,
            'pickup_email_template'   => $this->pickup_email_template,
            'pickup_sms_text'         => $this->pickup_sms_text,
            'delivery_enabled'        => $this->delivery_enabled,
            'delivery_trigger_status' => $this->delivery_trigger_status,
            'delivery_after_status'   => $this->delivery_after_status,
            'delivery_email_subject'  => $this->delivery_email_subject,
            'delivery_email_template' => $this->delivery_email_template,
            'delivery_sms_text'       => $this->delivery_sms_text,
        ]);

        $store->save();

        $this->dispatch('settings-saved', message: 'Signature workflow settings saved successfully.');
    }

    public function render()
    {
        return view('livewire.tenant.settings.sections.signature-settings');
    }
}
