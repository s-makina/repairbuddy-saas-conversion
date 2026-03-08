@extends('emails.layouts.auth-email', [
    'subject' => 'Verify Email Address',
    'tenantName' => $tenantName ?? '99SmartX',
    'tenantLogoUrl' => $tenantLogoUrl ?? null,
])

@section('content')
    @isset($userName)
        <div class="greeting">Hello {{ $userName }},</div>
    @endisset

    <div class="message">
        Your account has been created. Please click the button below to verify your email address and activate your workspace.
    </div>

    <div class="info-grid">
        @isset($userEmail)
            <div class="info-row">
                <div class="info-label">Email:</div>
                <div class="info-value"><strong>{{ $userEmail }}</strong></div>
            </div>
        @endisset
        @if(!empty($tenantName) && $tenantName !== 'RepairBuddy')
            <div class="info-row">
                <div class="info-label">Company:</div>
                <div class="info-value"><strong>{{ $tenantName }}</strong></div>
            </div>
        @endif
        @isset($tenantSlug)
            <div class="info-row">
                <div class="info-label">Workspace ID:</div>
                <div class="info-value"><strong>{{ $tenantSlug }}</strong></div>
            </div>
        @endisset
        @isset($workspaceUrl)
            <div class="info-row">
                <div class="info-label">Workspace URL:</div>
                <div class="info-value"><a href="{{ $workspaceUrl }}" style="color: inherit; word-break: break-all;">{{ $workspaceUrl }}</a></div>
            </div>
        @endisset
    </div>

    <div class="cta-container">
        <a href="{{ $verificationUrl }}" class="btn btn-accent" style="color: #ffffff;">Verify Email Address</a>
    </div>

    <div class="message" style="margin-top: 16px;">
        If you did not create an account, no further action is required.
    </div>
@endsection
