<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\Tenant;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Livewire\Component;

class SmsSettings extends Component
{
    public $tenant;

    /* ─── Activation ─────────────────────────────── */
    public bool $sms_active = false;

    /* ─── Gateway ────────────────────────────────── */
    public string $sms_gateway = '';
    public string $gateway_account_sid = '';
    public string $gateway_auth_token = '';
    public string $gateway_from_number = '';

    /* ─── Statuses ───────────────────────────────── */
    public array $available_statuses = [];
    public array $included_statuses = [];

    /* ─── Test SMS ───────────────────────────────── */
    public string $test_number = '';
    public string $test_message = '';

    /* ─── Options ────────────────────────────────── */
    public array $gatewayOptions = [];

    public function mount($tenant): void
    {
        $this->tenant = $tenant;
        $this->gatewayOptions = [
            '' => '— Select Gateway —',
            'twilio' => 'Twilio',
            'vonage' => 'Vonage (Nexmo)',
            'messagebird' => 'MessageBird',
        ];
        // TODO: Load settings from TenantSettingsStore
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
        $this->dispatch('settings-saved', message: 'SMS settings saved.');
    }

    public function sendTestSms(): void
    {
        // TODO: Wire functionality
        $this->dispatch('settings-saved', message: 'Test SMS sent.');
    }

    public function render()
    {
        return view('livewire.tenant.settings.sections.sms-settings');
    }
}
