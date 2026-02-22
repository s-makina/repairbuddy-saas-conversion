@extends('tenant.layouts.booking', ['tenant' => $tenant ?? null])

@section('content')
  @livewire('tenant.job-status.status-form', ['tenant' => $tenant, 'business' => $business, 'initialCaseNumber' => $initialCaseNumber ?? ''])
@endsection

@push('page-styles')
<link rel="stylesheet" href="{{ asset('css/status.css') }}">
@endpush
