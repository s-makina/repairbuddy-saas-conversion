<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\Tenant;
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

    public function mount($tenant): void
    {
        $this->tenant = $tenant;
        $this->intervalOptions = [
            'disabled' => 'Disabled',
            '1_notification' => '1 Notification (after 24 hours)',
            '2_notifications' => '2 Notifications (after 24h & 48h)',
        ];
        // TODO: Load settings & statusOptions from DB
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
        $this->dispatch('settings-saved', message: 'Review settings saved.');
    }

    public function render()
    {
        return view('livewire.tenant.settings.sections.review-settings');
    }
}
