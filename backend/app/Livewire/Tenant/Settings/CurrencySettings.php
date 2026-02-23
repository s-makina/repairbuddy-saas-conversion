<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\Tenant;
use App\Services\TenantSettings\TenantSettingsStore;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Livewire\Component;

class CurrencySettings extends Component
{
    public $tenant;

    /* ─── Form Fields ────────────────────────────── */
    public string $currency = '';
    public string $currency_position = 'left';
    public string $thousand_separator = ',';
    public string $decimal_separator = '.';
    public int $number_of_decimals = 0;

    /* ─── Options ────────────────────────────────── */
    public array $currencyOptions = [];
    public array $positionOptions = [];

    protected function rules(): array
    {
        return [
            'currency' => 'required|string|max:10',
            'currency_position' => 'required|in:left,right,left_space,right_space',
            'thousand_separator' => 'nullable|string|max:5',
            'decimal_separator' => 'nullable|string|max:5',
            'number_of_decimals' => 'required|integer|min:0|max:8',
        ];
    }

    public function mount($tenant): void
    {
        $this->tenant = $tenant;
        $this->loadOptions();
        $this->loadSettings();
    }

    public function hydrate(): void
    {
        if ($this->tenant instanceof Tenant && is_int($this->tenant->id)) {
            TenantContext::set($this->tenant);
            $branch = $this->tenant->branches()->where('is_default', true)->first();
            if ($branch) {
                BranchContext::set($branch);
            }
        }
    }

    private function loadOptions(): void
    {
        $this->currencyOptions = [
            'USD' => 'US Dollar ($)', 'EUR' => 'Euro (€)', 'GBP' => 'British Pound (£)',
            'AUD' => 'Australian Dollar (A$)', 'CAD' => 'Canadian Dollar (C$)',
            'JPY' => 'Japanese Yen (¥)', 'CHF' => 'Swiss Franc (CHF)',
            'SEK' => 'Swedish Krona (kr)', 'NOK' => 'Norwegian Krone (kr)',
            'DKK' => 'Danish Krone (kr)', 'NZD' => 'New Zealand Dollar (NZ$)',
            'SGD' => 'Singapore Dollar (S$)', 'HKD' => 'Hong Kong Dollar (HK$)',
            'KRW' => 'South Korean Won (₩)', 'INR' => 'Indian Rupee (₹)',
            'BRL' => 'Brazilian Real (R$)', 'MXN' => 'Mexican Peso (MX$)',
            'ZAR' => 'South African Rand (R)', 'PLN' => 'Polish Zloty (zł)',
            'CZK' => 'Czech Koruna (Kč)', 'HUF' => 'Hungarian Forint (Ft)',
            'RON' => 'Romanian Leu (lei)', 'TRY' => 'Turkish Lira (₺)',
            'PHP' => 'Philippine Peso (₱)', 'THB' => 'Thai Baht (฿)',
            'MYR' => 'Malaysian Ringgit (RM)', 'IDR' => 'Indonesian Rupiah (Rp)',
            'AED' => 'UAE Dirham (AED)', 'SAR' => 'Saudi Riyal (SAR)',
            'ILS' => 'Israeli Shekel (₪)', 'TWD' => 'Taiwan Dollar (NT$)',
            'PKR' => 'Pakistani Rupee (₨)', 'EGP' => 'Egyptian Pound (E£)',
            'NGN' => 'Nigerian Naira (₦)', 'CLP' => 'Chilean Peso (CLP)',
            'COP' => 'Colombian Peso (COP)', 'PEN' => 'Peruvian Sol (S/.)',
        ];

        $this->positionOptions = [
            'left' => 'Left ($99.99)',
            'right' => 'Right (99.99$)',
            'left_space' => 'Left with space ($ 99.99)',
            'right_space' => 'Right with space (99.99 $)',
        ];
    }

    private function loadSettings(): void
    {
        $store = new TenantSettingsStore($this->tenant);
        $settings = $store->get('currency', []);
        if (! is_array($settings)) {
            $settings = [];
        }

        $this->currency = (string) ($settings['wc_cr_selected_currency'] ?? ($this->tenant->currency ?? 'USD'));
        $this->currency_position = (string) ($settings['wc_cr_currency_position'] ?? 'left');
        $this->thousand_separator = (string) ($settings['wc_cr_thousand_separator'] ?? ',');
        $this->decimal_separator = (string) ($settings['wc_cr_decimal_separator'] ?? '.');
        $this->number_of_decimals = (int) ($settings['wc_cr_number_of_decimals'] ?? 0);
    }

    public function save(): void
    {
        $this->validate();

        $store = new TenantSettingsStore($this->tenant);

        $store->merge('currency', [
            'wc_cr_selected_currency' => $this->currency,
            'wc_cr_currency_position' => $this->currency_position,
            'wc_cr_thousand_separator' => $this->thousand_separator,
            'wc_cr_decimal_separator' => $this->decimal_separator,
            'wc_cr_number_of_decimals' => $this->number_of_decimals,
        ]);

        // Update tenant currency
        $this->tenant->currency = $this->currency;

        $store->save();

        $this->dispatch('settings-saved', message: 'Currency settings saved successfully.');
    }

    public function render()
    {
        return view('livewire.tenant.settings.sections.currency-settings');
    }
}
