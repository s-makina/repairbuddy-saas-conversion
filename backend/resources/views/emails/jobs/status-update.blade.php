@extends('emails.layouts.auth-email', [
    'subject' => 'Status Update: ' . $status . ' - ' . $caseNumber,
    'headerColor' => $statusColor ?? '#0ea5e9',
    'buttonColor' => '#fd6742',
    'tenantName' => $tenantName ?? 'RepairBuddy',
    'tenantLogoUrl' => $tenantLogoUrl ?? null,
    'greeting' => 'Hi ' . $customerName . ',',
])

@section('content')
<div class="message">
    Your repair status has been updated to <strong style="color: {{ $statusColor ?? '#0ea5e9' }};">{{ $status }}</strong>.
</div>

<div class="info-grid">
    <div class="info-row">
        <div class="info-label">Case #:</div>
        <div class="info-value"><strong>{{ $caseNumber }}</strong></div>
    </div>
    <div class="info-row">
        <div class="info-label">Updated:</div>
        <div class="info-value">{{ $updatedAt }}</div>
    </div>
</div>

<div class="info-grid" style="margin-top: 12px;">
    <div class="info-row">
        <div class="info-label">Device:</div>
        <div class="info-value">{{ $device }}</div>
    </div>
    <div class="info-row">
        <div class="info-label">Service:</div>
        <div class="info-value">{{ $service }}</div>
    </div>
    <div class="info-row">
        <div class="info-label">Technician:</div>
        <div class="info-value">{{ $technician }}</div>
    </div>
    <div class="info-row">
        <div class="info-label">Est. Completion:</div>
        <div class="info-value">{{ $estimatedCompletion }}</div>
    </div>
</div>

@if($note)
<div class="message" style="font-size: 13px; background: #fff8f5; padding: 12px; border-radius: 6px; margin-top: 12px; border-left: 3px solid #fd6742;">
    <strong>Note from technician:</strong><br>
    {{ $note }}
</div>
@endif

<div class="cta-container">
    <a href="{{ $trackingUrl }}" class="btn btn-accent" style="color: #ffffff;">Track Your Repair →</a>
</div>
@endsection
