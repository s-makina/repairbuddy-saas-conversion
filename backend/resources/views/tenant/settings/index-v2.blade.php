@extends('tenant.layouts.myaccount', ['title' => 'Settings'])

@section('content')
@livewire(\App\Livewire\Tenant\Settings\SettingsPage::class)
@endsection
