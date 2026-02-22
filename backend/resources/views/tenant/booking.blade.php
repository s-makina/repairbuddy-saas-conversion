@extends('tenant.layouts.public', ['tenant' => $tenant ?? null, 'business' => $business ?? ''])

@section('content')
  @livewire('tenant.booking.booking-form', ['tenant' => $tenant, 'business' => $business])
@endsection

@push('page-styles')
<link rel="stylesheet" href="{{ asset('css/booking.css') }}">
@endpush
