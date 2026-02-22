@extends('tenant.layouts.public', ['tenant' => $tenant ?? null, 'business' => $business ?? ''])

@section('title', 'My Account - ' . (($tenant->name ?? null) ?: config('app.name', 'RepairBuddy')))

@section('content')
  @livewire('tenant.public-pages.my-account', ['tenant' => $tenant, 'business' => $business])
@endsection
