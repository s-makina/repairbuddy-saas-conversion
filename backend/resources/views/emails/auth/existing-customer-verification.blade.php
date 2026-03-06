@extends('emails.layouts.auth-email', [
    'subject' => 'Verify Your Account — ' . ($tenantName ?? 'RepairBuddy'),
    'tenantName' => $tenantName ?? 'RepairBuddy',
    'tenantLogoUrl' => $tenantLogoUrl ?? null,
])

@section('content')
    @isset($userName)
        <div class="greeting">Hello {{ $userName }},</div>
    @endisset

    <div class="message">
        Someone (hopefully you) tried to book a repair at <strong>{{ $shopName }}</strong> using your email address.
    </div>

    <div class="message">
        You already have an account with us. Please log in to your Customer Portal to book another repair.
    </div>

    <div class="info-grid">
        <div class="info-row">
            <div class="info-label">Email:</div>
            <div class="info-value"><strong>{{ $userEmail }}</strong></div>
        </div>
    </div>

    <div class="message">
        <strong>Your One-Time Password:</strong>
    </div>

    <div class="otp-code">{{ $otpCode }}</div>

    <div class="subtext">
        This one-time password expires in 24 hours and will be invalidated after the first successful sign-in.
    </div>

    @isset($portalUrl)
        <div class="cta-container">
            <a href="{{ $portalUrl }}" class="btn btn-accent">Log In & Book Repair</a>
        </div>
    @endisset

    <div class="message" style="margin-top: 16px;">
        After logging in, you can book a new repair directly from your portal without entering your details again.
    </div>

    <div class="subtext">
        If you did not attempt to book a repair, you can safely ignore this email.
    </div>
@endsection
