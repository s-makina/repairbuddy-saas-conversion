@extends('emails.layouts.auth-email', [
    'subject' => 'Verify Email Address',
    'tenantName' => $tenantName ?? 'RepairBuddy',
    'tenantLogoUrl' => $tenantLogoUrl ?? null,
])

@section('content')
    @isset($userName)
        <div class="greeting">Hello {{ $userName }},</div>
    @endisset

    <div class="message">
        Please click the button below to verify your email address.
    </div>

    @isset($userEmail)
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Email:</div>
                <div class="info-value"><strong>{{ $userEmail }}</strong></div>
            </div>
        </div>
    @endisset

    <div class="cta-container">
        <a href="{{ $verificationUrl }}" class="btn btn-accent">Verify Email Address</a>
    </div>

    <div class="message" style="margin-top: 16px;">
        If you did not create an account, no further action is required.
    </div>
@endsection
