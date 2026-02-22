@extends('tenant.layouts.public', ['tenant' => $tenant ?? null, 'business' => $business ?? ''])

@section('title', 'Our Services - ' . (($tenant->name ?? null) ?: config('app.name', 'RepairBuddy')))

@section('content')
  <div class="rpn-placeholder">
    <div class="rpn-placeholder-icon">
      <i class="bi bi-tools"></i>
    </div>
    <h1>Our Services</h1>
    <p>
      Browse the full range of repair and maintenance services we offer — from screen replacements and battery swaps to motherboard diagnostics and data recovery.
    </p>
    <span class="rpn-placeholder-badge">
      <i class="bi bi-clock"></i> Coming Soon
    </span>
  </div>
@endsection
