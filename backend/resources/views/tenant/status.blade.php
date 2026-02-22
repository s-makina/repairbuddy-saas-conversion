@extends('tenant.layouts.public', ['tenant' => $tenant ?? null, 'business' => $business ?? ''])

@section('title', 'Repair Status - ' . (($tenant->name ?? null) ?: config('app.name', 'RepairBuddy')))

@section('content')
  <section class="rbs-page-shell">
    <div class="rbs-page-head">
      <p class="rbs-page-kicker">RepairBuddy Status Portal</p>
      <h1 class="rbs-page-title">Check repair progress in seconds</h1>
      <p class="rbs-page-subtitle">Use your case number to view timeline updates, repair details, and send a message to the shop.</p>
      <div class="rbs-page-chips" role="list" aria-label="Status page highlights">
        <span class="rbs-page-chip" role="listitem"><i class="bi bi-shield-check"></i> Secure lookup</span>
        <span class="rbs-page-chip" role="listitem"><i class="bi bi-clock-history"></i> Live timeline</span>
        <span class="rbs-page-chip" role="listitem"><i class="bi bi-chat-dots"></i> Direct messaging</span>
      </div>
    </div>

    <div class="rbs-page-content">
      @livewire('tenant.job-status.status-form', ['tenant' => $tenant, 'business' => $business, 'initialCaseNumber' => $initialCaseNumber ?? ''])
    </div>
  </section>
@endsection

@push('page-styles')
<link rel="stylesheet" href="{{ asset('css/status.css') }}">
@endpush
