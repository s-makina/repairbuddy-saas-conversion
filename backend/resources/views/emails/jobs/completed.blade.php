@extends('emails.layouts.auth-email', [
    'subject' => 'Your Device is Ready! - ' . $caseNumber,
    'headerColor' => '#16a34a',
    'buttonColor' => '#063e70',
    'tenantName' => $tenantName ?? 'RepairBuddy',
    'tenantLogoUrl' => $tenantLogoUrl ?? null,
    'greeting' => 'Hi ' . $customerName . ',',
])

@section('content')
<div class="message">
    Your <strong>{{ $device }}</strong> {{ $service }} is complete and ready for pickup!
</div>

<div class="info-grid">
    <div class="info-row">
        <div class="info-label">Case #:</div>
        <div class="info-value"><strong>{{ $caseNumber }}</strong></div>
    </div>
    <div class="info-row">
        <div class="info-label">Completed:</div>
        <div class="info-value">{{ $completedDate }}</div>
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
</div>

<div class="info-grid" style="background: #e8f5e9;">
    <div class="info-row">
        <div class="info-label">Total:</div>
        <div class="info-value" style="font-size: 18px; color: #16a34a;"><strong>{{ $total }}</strong></div>
    </div>
</div>

<div class="message" style="font-size: 13px; background: #f9fafb; padding: 12px; border-radius: 6px; margin-top: 12px;">
    <strong>Pickup Details:</strong><br>
    📍 {{ $pickupLocation }}<br>
    🕐 {{ $pickupHours }}<br>
    📝 {{ $pickupNote }}
</div>

<div class="message" style="font-size: 12px; color: #166534; background: #e8f5e9; padding: 10px; border-radius: 6px; margin-top: 8px;">
    ✅ {{ $warrantyText }}
</div>

<div class="cta-container">
    <a href="{{ $invoiceUrl }}" class="btn btn-primary" style="color: #ffffff;">View Invoice</a>
    <a href="{{ $feedbackUrl }}" class="btn btn-accent" style="margin-left: 8px; color: #ffffff;">Leave Feedback</a>
</div>
@endsection
