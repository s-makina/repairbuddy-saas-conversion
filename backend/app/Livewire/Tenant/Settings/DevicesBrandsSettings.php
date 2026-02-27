<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\RepairBuddyDeviceFieldDefinition;
use App\Models\Tenant;
use App\Services\TenantSettings\TenantSettingsStore;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Illuminate\Support\Str;
use Livewire\Component;

class DevicesBrandsSettings extends Component
{
    public $tenant;

    /* ─── Pin Code ───────────────────────────────── */
    public bool $enable_pin_code = false;
    public bool $show_pin_in_documents = false;

    /* ─── Labels ─────────────────────────────────── */
    public string $label_note = 'Note';
    public string $label_pin = 'Pin Code / Password';
    public string $label_device = 'Device';
    public string $label_brand = 'Brand';
    public string $label_type = 'Type';
    public string $label_imei = 'ID / IMEI';

    /* ─── Pickup & Delivery ──────────────────────── */
    public bool $pickup_delivery_enabled = false;
    public string $pickup_charge = '0';
    public string $delivery_charge = '0';

    /* ─── Rental ─────────────────────────────────── */
    public bool $rental_enabled = false;
    public string $rental_per_day = '0';
    public string $rental_per_week = '0';

    /* ─── Additional Device Fields ───────────────── */
    public array $additional_fields = [];

    protected function rules(): array
    {
        return [
            'enable_pin_code'          => 'boolean',
            'show_pin_in_documents'    => 'boolean',
            'label_note'               => 'nullable|string|max:100',
            'label_pin'                => 'nullable|string|max:100',
            'label_device'             => 'nullable|string|max:100',
            'label_brand'              => 'nullable|string|max:100',
            'label_type'               => 'nullable|string|max:100',
            'label_imei'               => 'nullable|string|max:100',
            'pickup_delivery_enabled'  => 'boolean',
            'pickup_charge'            => 'nullable|numeric|min:0',
            'delivery_charge'          => 'nullable|numeric|min:0',
            'rental_enabled'           => 'boolean',
            'rental_per_day'           => 'nullable|numeric|min:0',
            'rental_per_week'          => 'nullable|numeric|min:0',
            'additional_fields'        => 'array|max:10',
            'additional_fields.*.label'           => 'required|string|max:100',
            'additional_fields.*.type'            => 'required|string|in:text,number,date,textarea,select',
            'additional_fields.*.show_in_booking' => 'boolean',
            'additional_fields.*.show_in_invoice' => 'boolean',
            'additional_fields.*.show_for_customer' => 'boolean',
        ];
    }

    protected array $validationAttributes = [
        'additional_fields.*.label' => 'field label',
    ];

    public function mount($tenant): void
    {
        $this->tenant = $tenant;
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

    private function loadSettings(): void
    {
        $store = new TenantSettingsStore($this->tenant);
        $settings = $store->get('devicesBrands', []);
        if (! is_array($settings)) {
            $settings = [];
        }

        // Read with both camelCase (new) and snake_case (legacy) keys for backwards compatibility
        $this->enable_pin_code         = (bool) ($settings['enablePinCodeField'] ?? $settings['enable_pin_code'] ?? false);
        $this->show_pin_in_documents   = (bool) ($settings['showPinCodeInDocuments'] ?? $settings['show_pin_in_documents'] ?? false);
        $this->label_note              = (string) ($settings['labels']['note'] ?? $settings['label_note'] ?? 'Note');
        $this->label_pin               = (string) ($settings['labels']['pin'] ?? $settings['label_pin'] ?? 'Pin Code / Password');
        $this->label_device            = (string) ($settings['labels']['device'] ?? $settings['label_device'] ?? 'Device');
        $this->label_brand             = (string) ($settings['labels']['deviceBrand'] ?? $settings['label_brand'] ?? 'Brand');
        $this->label_type              = (string) ($settings['labels']['deviceType'] ?? $settings['label_type'] ?? 'Type');
        $this->label_imei              = (string) ($settings['labels']['imei'] ?? $settings['label_imei'] ?? 'ID / IMEI');
        $this->pickup_delivery_enabled = (bool) ($settings['pickupDeliveryEnabled'] ?? $settings['pickup_delivery_enabled'] ?? false);
        $this->pickup_charge           = (string) ($settings['pickupCharge'] ?? $settings['pickup_charge'] ?? '0');
        $this->delivery_charge         = (string) ($settings['deliveryCharge'] ?? $settings['delivery_charge'] ?? '0');
        $this->rental_enabled          = (bool) ($settings['rentalEnabled'] ?? $settings['rental_enabled'] ?? false);
        $this->rental_per_day          = (string) ($settings['rentalPerDay'] ?? $settings['rental_per_day'] ?? '0');
        $this->rental_per_week         = (string) ($settings['rentalPerWeek'] ?? $settings['rental_per_week'] ?? '0');

        // Load additional fields from DB
        $this->additional_fields = RepairBuddyDeviceFieldDefinition::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->map(fn ($f) => [
                'id'               => $f->id,
                'label'            => $f->label,
                'type'             => $f->type ?? 'text',
                'show_in_booking'  => $f->show_in_booking,
                'show_in_invoice'  => $f->show_in_invoice,
                'show_for_customer' => $f->show_in_portal,
            ])
            ->toArray();
    }

    public function addField(): void
    {
        if (count($this->additional_fields) < 10) {
            $this->additional_fields[] = [
                'id'               => null,
                'label'            => '',
                'type'             => 'text',
                'show_in_booking'  => true,
                'show_in_invoice'  => true,
                'show_for_customer' => true,
            ];
        }
    }

    public function removeField(int $index): void
    {
        if (isset($this->additional_fields[$index])) {
            $field = $this->additional_fields[$index];

            // If persisted, soft-delete (mark inactive)
            if (! empty($field['id'])) {
                $model = RepairBuddyDeviceFieldDefinition::find($field['id']);
                if ($model) {
                    $model->update(['is_active' => false]);
                }
            }

            unset($this->additional_fields[$index]);
            $this->additional_fields = array_values($this->additional_fields);
        }
    }

    public function save(): void
    {
        $this->validate();

        // Save key-value settings
        $store = new TenantSettingsStore($this->tenant);

        $store->merge('devicesBrands', [
            'enablePinCodeField'      => $this->enable_pin_code,
            'showPinCodeInDocuments'  => $this->show_pin_in_documents,
            'labels'                  => [
                'note'        => $this->label_note,
                'pin'         => $this->label_pin,
                'device'      => $this->label_device,
                'deviceBrand' => $this->label_brand,
                'deviceType'  => $this->label_type,
                'imei'        => $this->label_imei,
            ],
            'pickupDeliveryEnabled'   => $this->pickup_delivery_enabled,
            'pickupCharge'            => $this->pickup_charge,
            'deliveryCharge'          => $this->delivery_charge,
            'rentalEnabled'           => $this->rental_enabled,
            'rentalPerDay'            => $this->rental_per_day,
            'rentalPerWeek'           => $this->rental_per_week,
        ]);

        $store->save();

        // Sync additional fields to DB
        $existingIds = [];
        foreach ($this->additional_fields as $field) {
            $key = Str::slug($field['label'], '_');

            if (! empty($field['id'])) {
                // Update existing
                $model = RepairBuddyDeviceFieldDefinition::find($field['id']);
                if ($model) {
                    $model->update([
                        'label'           => $field['label'],
                        'key'             => $key,
                        'type'            => $field['type'],
                        'show_in_booking' => $field['show_in_booking'] ?? true,
                        'show_in_invoice' => $field['show_in_invoice'] ?? true,
                        'show_in_portal'  => $field['show_for_customer'] ?? true,
                        'is_active'       => true,
                    ]);
                    $existingIds[] = $model->id;
                }
            } else {
                // Create new
                $model = RepairBuddyDeviceFieldDefinition::create([
                    'label'           => $field['label'],
                    'key'             => $key,
                    'type'            => $field['type'],
                    'show_in_booking' => $field['show_in_booking'] ?? true,
                    'show_in_invoice' => $field['show_in_invoice'] ?? true,
                    'show_in_portal'  => $field['show_for_customer'] ?? true,
                    'is_active'       => true,
                ]);
                $existingIds[] = $model->id;
            }
        }

        // Reload to get fresh IDs
        $this->additional_fields = RepairBuddyDeviceFieldDefinition::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->map(fn ($f) => [
                'id'               => $f->id,
                'label'            => $f->label,
                'type'             => $f->type ?? 'text',
                'show_in_booking'  => $f->show_in_booking,
                'show_in_invoice'  => $f->show_in_invoice,
                'show_for_customer' => $f->show_in_portal,
            ])
            ->toArray();

        $this->dispatch('settings-saved', message: 'Devices & Brands settings saved successfully.');
    }

    public function render()
    {
        return view('livewire.tenant.settings.sections.devices-brands-settings');
    }
}
