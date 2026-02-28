@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? 'Appointments'])

@section('content')
@livewire(\App\Livewire\Tenant\Appointments\AppointmentsList::class)
@endsection
