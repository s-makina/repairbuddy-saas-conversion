@extends('emails.layouts.auth-email', [
    'subject' => 'Reset Password Notification',
    'tenantName' => $tenantName ?? '99SmartX',
    'tenantLogoUrl' => $tenantLogoUrl ?? null,
])

@section('content')
    @isset($userName)
        <div class="greeting">Hello {{ $userName }},</div>
    @endisset

    <div class="message">
        You are receiving this email because we received a password reset request for your account.
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
        <a href="{{ $resetUrl }}" class="btn btn-accent" style="color: #ffffff;">Reset Password</a>
    </div>

    <div class="subtext">
        This password reset link will expire in {{ $expireMinutes }} minutes.
    </div>

    <div class="message" style="margin-top: 16px;">
        If you did not request a password reset, no further action is required.
    </div>
@endsection
