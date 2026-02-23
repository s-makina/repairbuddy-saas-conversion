<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\Tenant;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Livewire\Component;

class SettingsPage extends Component
{
    /* ─── Layout & Page Data ─────────────────────── */
    public $tenant;
    public $user;
    public string $activeNav = 'settings';
    public string $pageTitle = 'Settings';

    /* ─── Navigation ─────────────────────────────── */
    public string $activeSection = 'dashboard';

    /* ─── Section Registry ───────────────────────── */
    public array $sections = [];

    /* ─── Flash messages ─────────────────────────── */
    public string $flashMessage = '';
    public string $flashType = 'success';

    protected $listeners = [
        'settings-saved' => 'handleSettingsSaved',
        'navigate-section' => 'switchSection',
    ];

    public function mount(): void
    {
        $this->tenant = TenantContext::tenant();
        $this->user = request()->user();

        if (! $this->tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $this->sections = $this->buildSectionRegistry();

        // Restore section from URL hash or query
        $requestedSection = request()->query('section', '');
        if ($requestedSection && array_key_exists($requestedSection, $this->sections)) {
            $this->activeSection = $requestedSection;
        }
    }

    public function hydrate(): void
    {
        // Re-set tenant context on subsequent Livewire requests
        if ($this->tenant instanceof Tenant && is_int($this->tenant->id)) {
            TenantContext::set($this->tenant);
            $branch = $this->tenant->branches()->where('is_default', true)->first();
            if ($branch) {
                BranchContext::set($branch);
            }
        }
    }

    public function switchSection(string $section): void
    {
        if (array_key_exists($section, $this->sections)) {
            $this->activeSection = $section;
            $this->flashMessage = '';
        }
    }

    public function handleSettingsSaved(string $message = 'Settings saved successfully.'): void
    {
        $this->flashMessage = $message;
        $this->flashType = 'success';
    }

    private function buildSectionRegistry(): array
    {
        return [
            // ── Dashboard ──
            'dashboard' => [
                'label' => 'Dashboard',
                'icon' => 'home',
                'group' => 'dashboard',
                'component' => 'tenant.settings.dashboard-section',
            ],

            // ── Core ──
            'general' => [
                'label' => 'General Settings',
                'icon' => 'cog-6-tooth',
                'group' => 'core',
                'component' => 'tenant.settings.general-settings',
            ],
            'currency' => [
                'label' => 'Currency',
                'icon' => 'currency-dollar',
                'group' => 'core',
                'component' => 'tenant.settings.currency-settings',
            ],
            'invoices' => [
                'label' => 'Reports & Invoices',
                'icon' => 'document-text',
                'group' => 'core',
                'component' => 'tenant.settings.invoice-settings',
            ],

            // ── Jobs & Workflow ──
            'job-status' => [
                'label' => 'Job Status',
                'icon' => 'clipboard-document-list',
                'group' => 'workflow',
                'component' => 'tenant.settings.job-status-settings',
            ],
            'payment-status' => [
                'label' => 'Payment Status',
                'icon' => 'credit-card',
                'group' => 'workflow',
                'component' => 'tenant.settings.payment-status-settings',
            ],
            'estimates' => [
                'label' => 'Estimates',
                'icon' => 'calculator',
                'group' => 'workflow',
                'component' => 'tenant.settings.estimate-settings',
            ],
            'signature' => [
                'label' => 'Signature Workflow',
                'icon' => 'pencil-square',
                'group' => 'workflow',
                'component' => 'tenant.settings.signature-settings',
            ],

            // ── Communication ──
            'sms' => [
                'label' => 'SMS',
                'icon' => 'chat-bubble-left-right',
                'group' => 'communication',
                'component' => 'tenant.settings.sms-settings',
            ],
            'reviews' => [
                'label' => 'Job Reviews',
                'icon' => 'star',
                'group' => 'communication',
                'component' => 'tenant.settings.review-settings',
            ],

            // ── Catalog ──
            'devices-brands' => [
                'label' => 'Devices & Brands',
                'icon' => 'device-phone-mobile',
                'group' => 'catalog',
                'component' => 'tenant.settings.devices-brands-settings',
            ],
            'services' => [
                'label' => 'Service Settings',
                'icon' => 'wrench-screwdriver',
                'group' => 'catalog',
                'component' => 'tenant.settings.service-settings',
            ],
            'taxes' => [
                'label' => 'Manage Taxes',
                'icon' => 'receipt-percent',
                'group' => 'catalog',
                'component' => 'tenant.settings.tax-settings',
            ],

            // ── Scheduling ──
            'bookings' => [
                'label' => 'Booking Settings',
                'icon' => 'calendar',
                'group' => 'scheduling',
                'component' => 'tenant.settings.booking-settings',
            ],
            'appointments' => [
                'label' => 'Appointments',
                'icon' => 'clock',
                'group' => 'scheduling',
                'component' => 'tenant.settings.appointment-settings',
            ],
            'maintenance-reminders' => [
                'label' => 'Maintenance Reminders',
                'icon' => 'bell-alert',
                'group' => 'scheduling',
                'component' => 'tenant.settings.maintenance-reminder-settings',
            ],

            // ── Appearance ──
            'styling' => [
                'label' => 'Styling & Labels',
                'icon' => 'paint-brush',
                'group' => 'appearance',
                'component' => 'tenant.settings.styling-settings',
            ],
            'timelog' => [
                'label' => 'Time Log Settings',
                'icon' => 'clock',
                'group' => 'appearance',
                'component' => 'tenant.settings.time-log-settings',
            ],

            // ── Account & Security ──
            'account' => [
                'label' => 'My Account Settings',
                'icon' => 'user-circle',
                'group' => 'account',
                'component' => 'tenant.settings.account-settings',
            ],
        ];
    }

    public function getSectionGroupsProperty(): array
    {
        $groups = [
            'dashboard' => ['label' => '', 'sections' => []],
            'core' => ['label' => 'Core', 'sections' => []],
            'workflow' => ['label' => 'Jobs & Workflow', 'sections' => []],
            'communication' => ['label' => 'Communication', 'sections' => []],
            'catalog' => ['label' => 'Catalog', 'sections' => []],
            'scheduling' => ['label' => 'Scheduling', 'sections' => []],
            'appearance' => ['label' => 'Appearance', 'sections' => []],
            'account' => ['label' => 'Account & Security', 'sections' => []],
        ];

        foreach ($this->sections as $key => $section) {
            $group = $section['group'] ?? 'core';
            if (isset($groups[$group])) {
                $groups[$group]['sections'][$key] = $section;
            }
        }

        return array_filter($groups, fn ($g) => ! empty($g['sections']));
    }

    public function render()
    {
        return view('livewire.tenant.settings.settings-page');
    }
}
