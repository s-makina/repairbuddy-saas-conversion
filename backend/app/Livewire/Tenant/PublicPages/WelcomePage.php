<?php

namespace App\Livewire\Tenant\PublicPages;

use App\Models\Branch;
use App\Models\RepairBuddyAppointmentSetting;
use App\Models\RepairBuddyService;
use App\Models\Tenant;
use App\Services\TenantSettings\TenantSettingsStore;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Livewire\Component;

class WelcomePage extends Component
{
    /* ───────── Tenant context ───────── */
    public ?Tenant $tenant = null;
    public ?int $tenantId = null;
    public string $business = '';
    public string $tenantSlug = '';

    /* ───────── Hero Settings ───────── */
    public string $heroTitle = '';
    public string $heroSubtitle = '';
    public string $heroBadge = '';
    public array $heroStats = [];

    /* ───────── Styling ───────── */
    public string $primaryColor = '#063e70';
    public string $secondaryColor = '#fd6742';

    /* ───────── Services ───────── */
    public array $services = [];

    /* ───────── Business Hours ───────── */
    public array $businessHours = [];

    /* ───────── Contact Info ───────── */
    public string $contactPhone = '';
    public string $contactEmail = '';
    public string $contactAddress = '';

    /* ─────────── mount ─────────── */

    public function mount(?Tenant $tenant = null, string $business = '')
    {
        $this->business = $business;
        $this->tenantSlug = $business;

        if (! $tenant) {
            $tenant = TenantContext::tenant();
        }

        if ($tenant instanceof Tenant) {
            $this->tenant = $tenant;
            $this->tenantId = $tenant->id;

            // Set branch context if available
            $branchId = is_numeric($tenant->default_branch_id) ? (int) $tenant->default_branch_id : null;
            if ($branchId) {
                $branch = Branch::find($branchId);
                if ($branch) {
                    BranchContext::set($branch);
                }
            }
        }

        $this->loadSettings();
        $this->loadServices();
        $this->loadBusinessHours();
        $this->loadContactInfo();
    }

    public function hydrate(): void
    {
        if ($this->tenant instanceof Tenant) {
            TenantContext::set($this->tenant);

            $branchId = is_numeric($this->tenant->default_branch_id) ? (int) $this->tenant->default_branch_id : null;
            if ($branchId) {
                $branch = Branch::find($branchId);
                if ($branch) {
                    BranchContext::set($branch);
                }
            }
        }
    }

    /* ─────────── Load Settings ─────────── */

    private function loadSettings(): void
    {
        if (! $this->tenant) {
            return;
        }

        $store = new TenantSettingsStore($this->tenant);

        // Styling settings
        $styling = $store->get('styling', []);
        if (! is_array($styling)) {
            $styling = [];
        }

        $this->primaryColor = (string) ($styling['primary_color'] ?? $this->tenant->brand_color ?? '#063e70');
        $this->secondaryColor = (string) ($styling['secondary_color'] ?? '#fd6742');

        // Hero settings with defaults
        $businessName = $this->tenant->name ?? 'Our Shop';
        $this->heroTitle = (string) ($styling['hero_title'] ?? "Expert Repairs, <span>{$businessName}</span>");
        $this->heroSubtitle = (string) ($styling['hero_subtitle'] ?? 'From cracked screens to water damage — get fast, reliable repairs from our certified technicians. Book online in seconds.');
        $this->heroBadge = (string) ($styling['hero_badge_text'] ?? 'Now accepting online bookings');

        // Parse hero stats JSON
        $statsJson = (string) ($styling['hero_stats'] ?? '');
        if ($statsJson !== '') {
            $decoded = json_decode($statsJson, true);
            if (is_array($decoded)) {
                $this->heroStats = $decoded;
            }
        }
    }

    /* ─────────── Load Services ─────────── */

    private function loadServices(): void
    {
        if (! $this->tenantId) {
            return;
        }

        $services = RepairBuddyService::query()
            ->with('type')
            ->where('tenant_id', $this->tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(12)
            ->get();

        $this->services = $services->map(fn ($service) => [
            'id' => $service->id,
            'name' => $service->name,
            'description' => $service->description ?? '',
            'time_required' => $service->time_required ?? '',
            'warranty' => $service->warranty ?? '',
            'base_price_amount' => $service->base_price_amount,
            'base_price_currency' => $service->base_price_currency ?? $this->tenant->currency ?? 'USD',
            'type_name' => $service->type?->name ?? null,
        ])->toArray();
    }

    /* ─────────── Load Business Hours ─────────── */

    private function loadBusinessHours(): void
    {
        if (! $this->tenantId) {
            return;
        }

        $branchId = BranchContext::branchId();

        $settings = RepairBuddyAppointmentSetting::query()
            ->where('tenant_id', $this->tenantId)
            ->where('branch_id', $branchId)
            ->where('is_enabled', true)
            ->first();

        if (! $settings || ! is_array($settings->time_slots)) {
            // Default hours if not configured
            $this->businessHours = [
                ['day' => 'Monday', 'open' => '09:00', 'close' => '18:00', 'closed' => false],
                ['day' => 'Tuesday', 'open' => '09:00', 'close' => '18:00', 'closed' => false],
                ['day' => 'Wednesday', 'open' => '09:00', 'close' => '18:00', 'closed' => false],
                ['day' => 'Thursday', 'open' => '09:00', 'close' => '18:00', 'closed' => false],
                ['day' => 'Friday', 'open' => '09:00', 'close' => '18:00', 'closed' => false],
                ['day' => 'Saturday', 'open' => '10:00', 'close' => '15:00', 'closed' => false],
                ['day' => 'Sunday', 'open' => null, 'close' => null, 'closed' => true],
            ];
            return;
        }

        // Parse time_slots array (format: [{day, start, end, enabled}])
        $slots = $settings->time_slots;
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $dayLabels = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        $this->businessHours = [];

        // Check if slots is indexed by day or is an array of slot objects
        $isIndexedByDay = isset($slots['monday']) || isset($slots['Monday']);

        if ($isIndexedByDay) {
            // Format: {monday: {start, end, enabled}, ...}
            foreach ($days as $i => $day) {
                $dayLower = strtolower($day);
                $slot = $slots[$dayLower] ?? $slots[$day] ?? null;

                if (is_array($slot)) {
                    $enabled = $slot['enabled'] ?? true;
                    $this->businessHours[] = [
                        'day' => $dayLabels[$i],
                        'open' => $enabled ? ($slot['start'] ?? '09:00') : null,
                        'close' => $enabled ? ($slot['end'] ?? '17:00') : null,
                        'closed' => ! $enabled,
                    ];
                } else {
                    $this->businessHours[] = [
                        'day' => $dayLabels[$i],
                        'open' => null,
                        'close' => null,
                        'closed' => true,
                    ];
                }
            }
        } else {
            // Format: [{day, start, end, enabled}, ...]
            $slotByDay = [];
            foreach ($slots as $slot) {
                if (isset($slot['day'])) {
                    $slotByDay[strtolower($slot['day'])] = $slot;
                }
            }

            foreach ($days as $i => $day) {
                $slot = $slotByDay[$day] ?? null;

                if ($slot && ($slot['enabled'] ?? true)) {
                    $this->businessHours[] = [
                        'day' => $dayLabels[$i],
                        'open' => $slot['start'] ?? '09:00',
                        'close' => $slot['end'] ?? '17:00',
                        'closed' => false,
                    ];
                } else {
                    $this->businessHours[] = [
                        'day' => $dayLabels[$i],
                        'open' => null,
                        'close' => null,
                        'closed' => true,
                    ];
                }
            }
        }
    }

    /* ─────────── Load Contact Info ─────────── */

    private function loadContactInfo(): void
    {
        if (! $this->tenant) {
            return;
        }

        $store = new TenantSettingsStore($this->tenant);
        $general = $store->get('general', []);
        if (! is_array($general)) {
            $general = [];
        }

        // Phone
        $this->contactPhone = (string) ($general['wc_rb_business_phone'] ?? $this->tenant->contact_phone ?? '');

        // Email
        $this->contactEmail = (string) ($general['computer_repair_email'] ?? $this->tenant->contact_email ?? '');

        // Address - prefer branch address, fallback to general settings
        $branch = $this->tenant->defaultBranch;
        if ($branch) {
            $parts = array_filter([
                $branch->address_line1,
                $branch->address_line2,
                $branch->address_city,
                $branch->address_state,
                $branch->address_postal_code,
            ]);
            $this->contactAddress = implode(', ', $parts);
        }

        if ($this->contactAddress === '') {
            $this->contactAddress = (string) ($general['wc_rb_business_address'] ?? '');
        }
    }

    /* ─────────── Helpers ─────────── */

    public function formatPrice(?int $cents, string $currency = 'USD'): string
    {
        if ($cents === null) {
            return '';
        }

        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'CAD' => 'CA$',
            'AUD' => 'A$',
            'NZD' => 'NZ$',
            'CHF' => 'CHF ',
            'JPY' => '¥',
            'CNY' => '¥',
            'INR' => '₹',
            'MXN' => 'MX$',
            'BRL' => 'R$',
            'ZAR' => 'R ',
            'SGD' => 'S$',
            'HKD' => 'HK$',
            'KRW' => '₩',
            'TRY' => '₺',
            'RUB' => '₽',
            'PLN' => 'zł ',
            'SEK' => 'kr ',
            'NOK' => 'kr ',
            'DKK' => 'kr ',
            'PHP' => '₱',
            'IDR' => 'Rp ',
            'THB' => '฿',
            'MYR' => 'RM ',
            'VND' => '₫',
        ];

        $symbol = $symbols[$currency] ?? $currency . ' ';
        $amount = $cents / 100;

        return $symbol . number_format($amount, 0);
    }

    public function formatTime(?string $time): string
    {
        if (! $time) {
            return '';
        }

        $parts = explode(':', $time);
        $hour = (int) ($parts[0] ?? 0);
        $minute = (int) ($parts[1] ?? 0);

        $period = $hour >= 12 ? 'PM' : 'AM';
        $displayHour = $hour > 12 ? $hour - 12 : ($hour === 0 ? 12 : $hour);

        return sprintf('%d:%02d %s', $displayHour, $minute, $period);
    }

    /* ─────────── Render ─────────── */

    public function render()
    {
        return view('livewire.tenant.public-pages.welcome-page', [
            'hasServices' => count($this->services) > 0,
            'hasHours' => count($this->businessHours) > 0,
            'hasStats' => count($this->heroStats) > 0,
        ]);
    }
}
