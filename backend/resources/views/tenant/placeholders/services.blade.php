@extends('tenant.layouts.public', ['tenant' => $tenant ?? null, 'business' => $business ?? ''])

@section('title', 'Our Services - ' . (($tenant->name ?? null) ?: config('app.name', 'RepairBuddy')))

@section('content')
  @livewire('tenant.public-pages.service-list', ['tenant' => $tenant, 'business' => $business])
@endsection
