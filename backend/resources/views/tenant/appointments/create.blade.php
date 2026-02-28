@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? 'Create Appointment'])

@section('content')
@livewire(\App\Livewire\Tenant\Appointments\AppointmentForm::class, [
    'tenant' => $tenant ?? null,
    'user' => $user ?? null,
])
@endsection
