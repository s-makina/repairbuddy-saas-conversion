@extends('tenant.layouts.public', ['tenant' => $tenant ?? null, 'business' => $business ?? ''])

@section('title', 'Review Your Job - ' . (($tenant->name ?? null) ?: config('app.name', 'RepairBuddy')))

@section('content')
  @livewire('tenant.public-pages.review-job', ['tenant' => $tenant, 'business' => $business])
@endsection
