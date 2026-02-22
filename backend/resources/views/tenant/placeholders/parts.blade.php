@extends('tenant.layouts.public', ['tenant' => $tenant ?? null, 'business' => $business ?? ''])

@section('title', 'Parts - ' . (($tenant->name ?? null) ?: config('app.name', 'RepairBuddy')))

@section('content')
  <div class="rpn-placeholder">
    <div class="rpn-placeholder-icon">
      <i class="bi bi-box-seam"></i>
    </div>
    <h1>Parts</h1>
    <p>
      View available replacement parts, accessories, and components. Check pricing and stock levels before booking your repair.
    </p>
    <span class="rpn-placeholder-badge">
      <i class="bi bi-clock"></i> Coming Soon
    </span>
  </div>
@endsection
