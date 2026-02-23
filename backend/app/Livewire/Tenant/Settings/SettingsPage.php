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
    public string $activeSection = 'general';

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
                'component' => null, // placeholder
            ],

            // ── Jobs & Workflow ──
            'job-status' => [
                'label' => 'Job Status',
                'icon' => 'clipboard-document-list',
                'group' => 'workflow',
                'component' => null, // placeholder
            ],
            'payment-status' => [
                'label' => 'Payment Status',
                'icon' => 'credit-card',
                'group' => 'workflow',
                'component' => null, // placeholder
            ],
            'estimates' => [
                'label' => 'Estimates',
                'icon' => 'calculator',
                'group' => 'workflow',
                'component' => null, // placeholder
            ],
            'signature' => [
                'label' => 'Signature Workflow',
                'icon' => 'pencil-square',
                'group' => 'workflow',
                'component' => null, // placeholder
            ],

            // ── Communication ──
            'sms' => [
                'label' => 'SMS',
                'icon' => 'chat-bubble-left-right',
                'group' => 'communication',
                'component' => null, // placeholder
            ],
            'reviews' => [
                'label' => 'Job Reviews',
                'icon' => 'star',
                'group' => 'communication',
                'component' => null, // placeholder
            ],

            // ── Catalog ──
            'devices-brands' => [
                'label' => 'Devices & Brands',
                'icon' => 'device-phone-mobile',
                'group' => 'catalog',
                'component' => null, // placeholder
            ],
            'services' => [
                'label' => 'Service Settings',
                'icon' => 'wrench-screwdriver',
                'group' => 'catalog',
                'component' => null, // placeholder
            ],
            'taxes' => [
                'label' => 'Manage Taxes',
                'icon' => 'receipt-percent',
                'group' => 'catalog',
                'component' => null, // placeholder
            ],

            // ── Scheduling ──
            'bookings' => [
                'label' => 'Booking Settings',
                'icon' => 'calendar',
                'group' => 'scheduling',
                'component' => null, // placeholder
            ],
            'appointments' => [
                'label' => 'Appointments',
                'icon' => 'clock',
                'group' => 'scheduling',
                'component' => null, // placeholder
            ],
            'maintenance-reminders' => [
                'label' => 'Maintenance Reminders',
                'icon' => 'bell-alert',
                'group' => 'scheduling',
                'component' => null, // placeholder
            ],

            // ── Appearance ──
            'styling' => [
                'label' => 'Styling & Labels',
                'icon' => 'paint-brush',
                'group' => 'appearance',
                'component' => null, // placeholder
            ],
            'timelog' => [
                'label' => 'Time Log Settings',
                'icon' => 'clock',
                'group' => 'appearance',
                'component' => null, // placeholder
            ],

            // ── Account & Security ──
            'account' => [
                'label' => 'My Account Settings',
                'icon' => 'user-circle',
                'group' => 'account',
                'component' => null, // placeholder
            ],
        ];
    }

    public function getSectionGroupsProperty(): array
    {
        $groups = [
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
