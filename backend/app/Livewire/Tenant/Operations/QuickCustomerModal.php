<?php

namespace App\Livewire\Tenant\Operations;

use App\Models\User;
use App\Support\TenantContext;
use Livewire\Component;

class QuickCustomerModal extends Component
{
    public $showModal = false;

    public $first_name;
    public $last_name;
    public $email;
    public $phone;
    public $company;

    protected $rules = [
        'first_name' => 'required|string|max:255',
        'last_name' => 'nullable|string|max:255',
        'email' => 'required|email|max:255|unique:users,email',
        'phone' => 'nullable|string|max:64',
        'company' => 'nullable|string|max:255',
    ];

    protected $listeners = ['openQuickCustomerModal' => 'open'];

    public function open()
    {
        $this->reset(['first_name', 'last_name', 'email', 'phone', 'company']);
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function close()
    {
        $this->showModal = false;
    }

    public function save()
    {
        $this->validate();

        $tenant = TenantContext::tenant();

        $fullName = trim($this->first_name . ' ' . ($this->last_name ?? ''));

        $customer = User::create([
            'tenant_id' => $tenant->id,
            'is_admin' => false,
            'role' => 'customer',
            'status' => 'active',
            'name' => $fullName,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => trim((string) $this->email),
            'phone' => $this->phone,
            'company' => $this->company,
            'password' => bcrypt(str()->random(48)),
        ]);

        $this->dispatch('customerCreated', customerId: $customer->id);
        $this->close();
    }

    public function render()
    {
        return view('livewire.tenant.operations.quick-customer-modal');
    }
}
