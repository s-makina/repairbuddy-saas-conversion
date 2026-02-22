@extends('tenant.layouts.booking', ['tenant' => $tenant ?? null])

@section('content')
  @livewire('tenant.booking.booking-form', ['tenant' => $tenant, 'business' => $business])
@endsection
