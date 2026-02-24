@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? 'New Estimate'])

@section('content')
@livewire(\App\Livewire\Tenant\Estimates\EstimateForm::class, [
    'tenant' => $tenant ?? null,
    'user' => $user ?? null,
    'activeNav' => $activeNav ?? null,
    'pageTitle' => $pageTitle ?? null,
    'estimate' => $estimate ?? null,
    'estimateId' => $estimateId ?? null,
    'customers' => $customers ?? null,
    'technicians' => $technicians ?? null,
    'customerDevices' => $customerDevices ?? null,
    'devices' => $devices ?? null,
    'parts' => $parts ?? null,
    'services' => $services ?? null,
    'estimateItems' => $estimateItems ?? [],
    'estimateDevices' => $estimateDevices ?? [],
])
@endsection
