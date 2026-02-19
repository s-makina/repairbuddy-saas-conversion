@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? 'New Job'])

@section('content')
@livewire(\App\Livewire\Tenant\Jobs\JobForm::class, [
    'tenant' => $tenant ?? null,
    'user' => $user ?? null,
    'activeNav' => $activeNav ?? null,
    'pageTitle' => $pageTitle ?? null,
    'job' => $job ?? null,
    'jobId' => $jobId ?? null,
    'suggestedCaseNumber' => $suggestedCaseNumber ?? null,
    'jobStatuses' => $jobStatuses ?? null,
    'paymentStatuses' => $paymentStatuses ?? null,
    'customers' => $customers ?? null,
    'technicians' => $technicians ?? null,
    'branches' => $branches ?? null,
    'customerDevices' => $customerDevices ?? null,
    'devices' => $devices ?? null,
    'parts' => $parts ?? null,
    'services' => $services ?? null,
    'jobItems' => $jobItems ?? null,
    'jobDevices' => $jobDevices ?? [],
    'jobExtras' => $jobExtras ?? [],
])

@endsection
