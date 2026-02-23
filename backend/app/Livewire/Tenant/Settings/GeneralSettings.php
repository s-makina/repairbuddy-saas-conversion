<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\Tenant;
use App\Services\TenantSettings\TenantSettingsStore;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Livewire\Component;

class GeneralSettings extends Component
{
    public $tenant;

    /* ─── Form Fields ────────────────────────────── */
    public string $business_name = '';
    public string $business_phone = '';
    public string $business_address = '';
    public string $logo_url = '';
    public string $email = '';
    public string $case_number_prefix = 'WC_';
    public int $case_number_length = 6;
    public string $default_country = '';

    /* ─── Toggles ────────────────────────────────── */
    public bool $email_customer_on_status_change = false;
    public bool $attach_pdf = false;
    public bool $next_service_date = false;
    public bool $use_woo_products = false;
    public bool $disable_status_check_serial = false;

    /* ─── GDPR / Compliance ──────────────────────── */
    public string $gdpr_acceptance = '';
    public string $gdpr_link_label = 'Privacy policy';
    public string $gdpr_link_url = '';

    /* ─── Select Options ─────────────────────────── */
    public array $countries = [];

    protected function rules(): array
    {
        return [
            'business_name' => 'required|string|max:255',
            'business_phone' => 'nullable|string|max:50',
            'business_address' => 'nullable|string|max:500',
            'logo_url' => 'nullable|url|max:500',
            'email' => 'nullable|email|max:255',
            'case_number_prefix' => 'nullable|string|max:20',
            'case_number_length' => 'required|integer|min:1|max:20',
            'default_country' => 'nullable|string|max:5',
            'gdpr_acceptance' => 'nullable|string|max:1000',
            'gdpr_link_label' => 'nullable|string|max:100',
            'gdpr_link_url' => 'nullable|string|max:500',
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
            $branch = $this->tenant->branches()->where('is_default', true)->first();
            if ($branch) {
                BranchContext::set($branch);
            }
        }
    }

    private function loadSettings(): void
    {
        $store = new TenantSettingsStore($this->tenant);
        $general = $store->get('general', []);
        if (! is_array($general)) {
            $general = [];
        }

        $this->business_name = (string) ($general['wc_rb_business_name'] ?? ($this->tenant->name ?? ''));
        $this->business_phone = (string) ($general['wc_rb_business_phone'] ?? ($this->tenant->contact_phone ?? ''));
        $this->business_address = (string) ($general['wc_rb_business_address'] ?? '');
        $this->logo_url = (string) ($general['computer_repair_logo'] ?? (is_string($this->tenant->logo_url) ? $this->tenant->logo_url : ''));
        $this->email = (string) ($general['computer_repair_email'] ?? ($this->tenant->contact_email ?? ''));
        $this->case_number_prefix = (string) ($general['case_number_prefix'] ?? 'WC_');
        $this->case_number_length = (int) ($general['case_number_length'] ?? 6);
        $this->default_country = (string) ($general['wc_primary_country'] ?? '');

        $this->email_customer_on_status_change = (bool) ($general['wc_job_status_cr_notice'] ?? false);
        $this->attach_pdf = (bool) ($general['wcrb_attach_pdf_in_customer_emails'] ?? false);
        $this->next_service_date = (bool) ($general['wcrb_next_service_date'] ?? false);
        $this->use_woo_products = (bool) ($general['wc_enable_woo_products'] ?? false);
        $this->disable_status_check_serial = (bool) ($general['wcrb_disable_statuscheck_serial'] ?? false);

        $this->gdpr_acceptance = (string) ($general['wc_rb_gdpr_acceptance'] ?? 'I understand that I will be contacted by a representative regarding this request and I agree to the privacy policy.');
        $this->gdpr_link_label = (string) ($general['wc_rb_gdpr_acceptance_link_label'] ?? 'Privacy policy');
        $this->gdpr_link_url = (string) ($general['wc_rb_gdpr_acceptance_link'] ?? '');

        // Load country options
        $this->countries = $this->getCountryOptions();
    }

    public function save(): void
    {
        $this->validate();

        $store = new TenantSettingsStore($this->tenant);

        $store->merge('general', [
            'wc_rb_business_name' => $this->business_name,
            'wc_rb_business_phone' => $this->business_phone,
            'wc_rb_business_address' => $this->business_address,
            'computer_repair_logo' => $this->logo_url,
            'computer_repair_email' => $this->email,
            'case_number_prefix' => $this->case_number_prefix,
            'case_number_length' => $this->case_number_length,
            'wc_primary_country' => $this->default_country,
            'wc_job_status_cr_notice' => $this->email_customer_on_status_change,
            'wcrb_attach_pdf_in_customer_emails' => $this->attach_pdf,
            'wcrb_next_service_date' => $this->next_service_date,
            'wc_enable_woo_products' => $this->use_woo_products,
            'wcrb_disable_statuscheck_serial' => $this->disable_status_check_serial,
            'wc_rb_gdpr_acceptance' => $this->gdpr_acceptance,
            'wc_rb_gdpr_acceptance_link_label' => $this->gdpr_link_label,
            'wc_rb_gdpr_acceptance_link' => $this->gdpr_link_url,
        ]);

        // Also update tenant model fields
        $this->tenant->name = $this->business_name;
        if ($this->email) {
            $this->tenant->contact_email = $this->email;
        }
        if ($this->business_phone) {
            $this->tenant->contact_phone = $this->business_phone;
        }

        $store->save();

        $this->dispatch('settings-saved', message: 'General settings saved successfully.');
    }

    private function getCountryOptions(): array
    {
        return [
            '' => 'Select Country',
            'AF' => 'Afghanistan', 'AL' => 'Albania', 'DZ' => 'Algeria', 'AR' => 'Argentina',
            'AU' => 'Australia', 'AT' => 'Austria', 'BE' => 'Belgium', 'BR' => 'Brazil',
            'CA' => 'Canada', 'CL' => 'Chile', 'CN' => 'China', 'CO' => 'Colombia',
            'HR' => 'Croatia', 'CZ' => 'Czech Republic', 'DK' => 'Denmark', 'EG' => 'Egypt',
            'FI' => 'Finland', 'FR' => 'France', 'DE' => 'Germany', 'GR' => 'Greece',
            'HK' => 'Hong Kong', 'HU' => 'Hungary', 'IN' => 'India', 'ID' => 'Indonesia',
            'IE' => 'Ireland', 'IL' => 'Israel', 'IT' => 'Italy', 'JP' => 'Japan',
            'KR' => 'South Korea', 'LT' => 'Lithuania', 'MY' => 'Malaysia', 'MX' => 'Mexico',
            'NL' => 'Netherlands', 'NZ' => 'New Zealand', 'NO' => 'Norway', 'PK' => 'Pakistan',
            'PE' => 'Peru', 'PH' => 'Philippines', 'PL' => 'Poland', 'PT' => 'Portugal',
            'RO' => 'Romania', 'RU' => 'Russia', 'SA' => 'Saudi Arabia', 'SG' => 'Singapore',
            'ZA' => 'South Africa', 'ES' => 'Spain', 'SE' => 'Sweden', 'CH' => 'Switzerland',
            'TW' => 'Taiwan', 'TH' => 'Thailand', 'TR' => 'Turkey', 'UA' => 'Ukraine',
            'AE' => 'United Arab Emirates', 'GB' => 'United Kingdom', 'US' => 'United States',
            'VN' => 'Vietnam',
        ];
    }

    public function render()
    {
        return view('livewire.tenant.settings.sections.general-settings');
    }
}
