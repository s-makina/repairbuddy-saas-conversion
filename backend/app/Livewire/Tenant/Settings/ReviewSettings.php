<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\RepairBuddyJobStatus;
use App\Models\Tenant;
use App\Services\TenantSettings\TenantSettingsStore;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Livewire\Component;

class ReviewSettings extends Component
{
    public $tenant;

    /* ─── Review Request Settings ────────────────── */
    public bool $request_by_sms = false;
    public bool $request_by_email = false;
    public string $send_request_job_status = '';
    public string $auto_request_interval = 'disabled';
    public string $email_subject = '';
    public string $email_message = '';
    public string $sms_message = '';

    /* ─── Options ────────────────────────────────── */
    public array $statusOptions = [];
    public array $intervalOptions = [];

    protected function rules(): array
    {
        return [
            'request_by_sms'          => 'boolean',
            'request_by_email'        => 'boolean',
            'send_request_job_status' => 'nullable|string|max:100',
            'auto_request_interval'   => 'required|string|in:disabled,1_notification,2_notifications',
            'email_subject'           => 'nullable|string|max:255',
            'email_message'           => 'nullable|string|max:5000',
            'sms_message'             => 'nullable|string|max:500',
        ];
    }

    public function mount($tenant): void
    {
        $this->tenant = $tenant;
        $this->intervalOptions = [
            'disabled'         => 'Disabled',
            '1_notification'   => '1 Notification (after 24 hours)',
            '2_notifications'  => '2 Notifications (after 24h & 48h)',
        ];
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
        $settings = $store->get('reviews', []);
        if (! is_array($settings)) {
            $settings = [];
        }

        $this->request_by_sms          = (bool) ($settings['request_by_sms'] ?? false);
        $this->request_by_email        = (bool) ($settings['request_by_email'] ?? false);
        $this->send_request_job_status = (string) ($settings['send_request_job_status'] ?? '');
        $this->auto_request_interval   = (string) ($settings['auto_request_interval'] ?? 'disabled');
        $this->email_subject           = (string) ($settings['email_subject'] ?? '');
        $this->email_message           = (string) ($settings['email_message'] ?? '');
        $this->sms_message             = (string) ($settings['sms_message'] ?? '');
    }

    public function save(): void
    {
        $this->validate();

        $store = new TenantSettingsStore($this->tenant);

        $store->merge('reviews', [
            'request_by_sms'          => $this->request_by_sms,
            'request_by_email'        => $this->request_by_email,
            'send_request_job_status' => $this->send_request_job_status,
            'auto_request_interval'   => $this->auto_request_interval,
            'email_subject'           => $this->email_subject,
            'email_message'           => $this->email_message,
            'sms_message'             => $this->sms_message,
        ]);

        $store->save();

        $this->dispatch('settings-saved', message: 'Review settings saved successfully.');
    }

    public function render()
    {
        return view('livewire.tenant.settings.sections.review-settings');
    }
}
