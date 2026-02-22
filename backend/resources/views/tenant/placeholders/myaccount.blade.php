@extends('tenant.layouts.public', ['tenant' => $tenant ?? null, 'business' => $business ?? ''])

@section('title', 'My Account - ' . (($tenant->name ?? null) ?: config('app.name', 'RepairBuddy')))

@section('content')
  <div class="rpn-placeholder">
    <div class="rpn-placeholder-icon">
      <i class="bi bi-person-circle"></i>
    </div>
    <h1>My Account</h1>
    <p>
      Access your repair history, manage your profile, and keep track of all your devices and past service records — all in one place.
    </p>
    <span class="rpn-placeholder-badge">
      <i class="bi bi-clock"></i> Coming Soon
    </span>
  </div>
@endsection
