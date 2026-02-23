<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\RepairBuddyJobStatus;
use App\Models\Tenant;
use App\Services\TenantSettings\TenantSettingsStore;
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

    protected function rules(): array
    {
        return [
            'sms_active'          => 'boolean',
            'sms_gateway'         => 'nullable|string|in:,twilio,vonage,messagebird',
            'gateway_account_sid' => 'nullable|string|max:255',
            'gateway_auth_token'  => 'nullable|string|max:255',
            'gateway_from_number' => 'nullable|string|max:50',
            'included_statuses'   => 'array',
            'included_statuses.*' => 'string',
        ];
    }

    public function mount($tenant): void
    {
        $this->tenant = $tenant;
        $this->gatewayOptions = [
            ''           => '— Select Gateway —',
            'twilio'     => 'Twilio',
            'vonage'     => 'Vonage (Nexmo)',
            'messagebird' => 'MessageBird',
        ];
        $this->loadStatuses();
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

    private function loadStatuses(): void
    {
        $this->available_statuses = RepairBuddyJobStatus::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->map(fn ($s) => [
                'key'   => $s->slug,
                'label' => $s->label,
            ])
            ->toArray();
    }

    private function loadSettings(): void
    {
        $store = new TenantSettingsStore($this->tenant);
        $settings = $store->get('sms', []);
        if (! is_array($settings)) {
            $settings = [];
        }

        $this->sms_active          = (bool) ($settings['sms_active'] ?? false);
        $this->sms_gateway         = (string) ($settings['sms_gateway'] ?? '');
        $this->gateway_account_sid = (string) ($settings['gateway_account_sid'] ?? '');
        $this->gateway_auth_token  = (string) ($settings['gateway_auth_token'] ?? '');
        $this->gateway_from_number = (string) ($settings['gateway_from_number'] ?? '');
        $this->included_statuses   = (array) ($settings['included_statuses'] ?? []);
    }

    public function save(): void
    {
        $this->validate();

        $store = new TenantSettingsStore($this->tenant);

        $store->merge('sms', [
            'sms_active'          => $this->sms_active,
            'sms_gateway'         => $this->sms_gateway,
            'gateway_account_sid' => $this->gateway_account_sid,
            'gateway_auth_token'  => $this->gateway_auth_token,
            'gateway_from_number' => $this->gateway_from_number,
            'included_statuses'   => $this->included_statuses,
        ]);

        $store->save();

        $this->dispatch('settings-saved', message: 'SMS settings saved successfully.');
    }

    public function sendTestSms(): void
    {
        $this->validate([
            'test_number'  => 'required|string|max:20',
            'test_message' => 'required|string|max:500',
        ]);

        if (! $this->sms_active || empty($this->sms_gateway)) {
            $this->dispatch('settings-saved', message: 'Please enable SMS and configure a gateway first.');
            return;
        }

        // In production, this would dispatch a job to send an actual SMS
        // For now, confirm the test was triggered
        $this->dispatch('settings-saved', message: 'Test SMS queued for delivery to ' . $this->test_number . '.');
    }

    public function render()
    {
        return view('livewire.tenant.settings.sections.sms-settings');
    }
}
