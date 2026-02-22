@extends('tenant.layouts.public', ['tenant' => $tenant ?? null, 'business' => $business ?? ''])

@section('title', 'Review Your Job - ' . (($tenant->name ?? null) ?: config('app.name', 'RepairBuddy')))

@section('content')
  <div class="rpn-placeholder">
    <div class="rpn-placeholder-icon">
      <i class="bi bi-file-earmark-check"></i>
    </div>
    <h1>Review Your Job</h1>
    <p>
      View the full details of your completed or in-progress repair — including diagnostics, parts used, costs, and technician notes. Enter your case number to get started.
    </p>
    <span class="rpn-placeholder-badge">
      <i class="bi bi-clock"></i> Coming Soon
    </span>
  </div>
@endsection
