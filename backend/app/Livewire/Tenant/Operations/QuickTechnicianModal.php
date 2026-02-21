<?php

namespace App\Livewire\Tenant\Operations;

use App\Models\User;
use App\Support\TenantContext;
use Livewire\Component;

class QuickTechnicianModal extends Component
{
    public $showModal = false;
    public $tenant;

    public $first_name;
    public $last_name;
    public $email;
    public $phone;
    public $company;
    public $tax_id;
    public $address_line1;
    public $address_line2;
    public $address_city;
    public $address_state;
    public $address_postal_code;
    public $address_country;
    public $address_country_code;
    public $currency;

    protected $rules = [
        'first_name' => 'required|string|max:255',
        'last_name' => 'nullable|string|max:255',
        'email' => 'required|email|max:255|unique:users,email',
        'phone' => 'nullable|string|max:64',
        'company' => 'nullable|string|max:255',
        'tax_id' => 'nullable|string|max:64',
        'address_line1' => 'nullable|string|max:255',
        'address_line2' => 'nullable|string|max:255',
        'address_city' => 'nullable|string|max:255',
        'address_state' => 'nullable|string|max:255',
        'address_postal_code' => 'nullable|string|max:64',
        'address_country' => 'nullable|string|max:255',
        'address_country_code' => 'nullable|string|size:2',
        'currency' => 'nullable|string|size:3',
    ];

    protected $listeners = ['openQuickTechnicianModal' => 'open'];

    public function mount($tenant = null)
    {
        $this->tenant = $tenant;
    }

    public function hydrate(): void
    {
        if ($this->tenant instanceof \App\Models\Tenant) {
            \App\Support\TenantContext::set($this->tenant);
        }
    }

    public function open()
    {
        $this->reset([
            'first_name', 'last_name', 'email', 'phone', 'company', 'tax_id',
            'address_line1', 'address_line2', 'address_city', 'address_state',
            'address_postal_code', 'address_country', 'address_country_code', 'currency'
        ]);
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function close()
    {
        $this->showModal = false;
        // The browser event is now dispatched directly in save() if needed,
        // but let's keep this clean for general closing.
    }

    public function save()
    {
        $this->validate();

        $tenant_id = $this->tenant ? $this->tenant->id : TenantContext::tenant()?->id;

        $fullName = trim($this->first_name . ' ' . ($this->last_name ?? ''));

        $role_id = \App\Models\Role::query()
            ->where('tenant_id', $tenant_id)
            ->where('name', 'Technician')
            ->value('id');

        $technician = User::create([
            'tenant_id' => $tenant_id,
            'is_admin' => false,
            'role' => 'technician',
            'role_id' => $role_id,
            'status' => 'active',
            'name' => $fullName,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => trim((string) $this->email),
            'phone' => $this->phone,
            'company' => $this->company,
            'tax_id' => $this->tax_id,
            'address_line1' => $this->address_line1,
            'address_line2' => $this->address_line2,
            'address_city' => $this->address_city,
            'address_state' => $this->address_state,
            'address_postal_code' => $this->address_postal_code,
            'address_country' => $this->address_country,
            'address_country_code' => $this->address_country_code ? strtoupper($this->address_country_code) : null,
            'currency' => $this->currency ? strtoupper($this->currency) : null,
            'password' => bcrypt(str()->random(48)),
        ]);

        $this->dispatch('technicianCreated', technicianId: $technician->id);
        $this->dispatch('close-technician-modal'); // Explicitly tell Alpine to close it
        $this->close();
    }

    public function render()
    {
        return view('livewire.tenant.operations.quick-technician-modal');
    }
}
