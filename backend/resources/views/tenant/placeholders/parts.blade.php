@extends('tenant.layouts.public', ['tenant' => $tenant ?? null, 'business' => $business ?? ''])

@section('title', 'Parts & Components - ' . (($tenant->name ?? null) ?: config('app.name', 'RepairBuddy')))

@section('content')
  @livewire('tenant.public-pages.part-list', ['tenant' => $tenant, 'business' => $business])
@endsection
