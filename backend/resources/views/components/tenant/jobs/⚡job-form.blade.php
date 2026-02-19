<?php

use Livewire\Component;

new class extends Component
{
    public $tenant;
    public $user;
    public $activeNav;
    public $pageTitle;
    public $job;
    public $jobId;
    public $suggestedCaseNumber;
    public $jobStatuses;
    public $paymentStatuses;
    public $customers;
    public $technicians;
    public $branches;
    public $customerDevices;
    public $devices;
    public $parts;
    public $services;
    public $jobItems;
    public $jobDevices;

    public function mount(
        $tenant = null,
        $user = null,
        $activeNav = null,
        $pageTitle = null,
        $job = null,
        $jobId = null,
        $suggestedCaseNumber = null,
        $jobStatuses = null,
        $paymentStatuses = null,
        $customers = null,
        $technicians = null,
        $branches = null,
        $customerDevices = null,
        $devices = null,
        $parts = null,
        $services = null,
        $jobItems = null,
        $jobDevices = null,
    ): void {
        $this->tenant = $tenant;
        $this->user = $user;
        $this->activeNav = $activeNav;
        $this->pageTitle = $pageTitle;
        $this->job = $job;
        $this->jobId = $jobId;
        $this->suggestedCaseNumber = $suggestedCaseNumber;
        $this->jobStatuses = $jobStatuses;
        $this->paymentStatuses = $paymentStatuses;
        $this->customers = $customers;
        $this->technicians = $technicians;
        $this->branches = $branches;
        $this->customerDevices = $customerDevices;
        $this->devices = $devices;
        $this->parts = $parts;
        $this->services = $services;
        $this->jobItems = $jobItems;
        $this->jobDevices = $jobDevices;
    }
};

?>

<div>
    {{-- Simplicity is the essence of happiness. - Cedric Bledsoe --}}
    @include('tenant.partials.job_create_inner', [
        'tenant' => $tenant,
        'user' => $user,
        'activeNav' => $activeNav,
        'pageTitle' => $pageTitle,
        'job' => $job,
        'jobId' => $jobId,
        'suggestedCaseNumber' => $suggestedCaseNumber,
        'jobStatuses' => $jobStatuses,
        'paymentStatuses' => $paymentStatuses,
        'customers' => $customers,
        'technicians' => $technicians,
        'branches' => $branches,
        'customerDevices' => $customerDevices,
        'devices' => $devices,
        'parts' => $parts,
        'services' => $services,
        'jobItems' => $jobItems,
        'jobDevices' => $jobDevices,
    ])
</div>