@extends('tenant.layouts.public', ['tenant' => $tenant ?? null, 'business' => $business ?? ''])

@section('title', 'Repair Status - ' . (($tenant->name ?? null) ?: config('app.name', 'RepairBuddy')))

@section('content')
  @livewire('tenant.job-status.status-form', ['tenant' => $tenant, 'business' => $business, 'initialCaseNumber' => $initialCaseNumber ?? ''])
@endsection

@push('page-styles')
<link rel="stylesheet" href="{{ asset('css/status.css') }}">
@endpush
