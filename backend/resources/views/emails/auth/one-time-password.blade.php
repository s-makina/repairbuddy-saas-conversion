@extends('emails.layouts.auth-email', [
    'subject' => 'Your One-Time Password',
    'tenantName' => $tenantName ?? '99SmartX',
    'tenantLogoUrl' => $tenantLogoUrl ?? null,
])

@section('content')
    @isset($userName)
        <div class="greeting">Hello {{ $userName }},</div>
    @endisset

    <div class="message">
        {!! $introText ?? 'A new account was created for you. Use this one-time password to sign in:' !!}
    </div>

    <div class="otp-code">{{ $otpCode }}</div>

    <div class="subtext">
        This one-time password expires in {{ $expiresInMinutes }} minutes and will be invalidated after the first successful sign-in.
    </div>

    @isset($loginUrl)
        <div class="cta-container">
            <a href="{{ $loginUrl }}" class="btn btn-accent" style="color: #ffffff;">Sign In</a>
        </div>
    @endisset

    @isset($additionalMessage)
        <div class="message" style="margin-top: 16px;">
            {{ $additionalMessage }}
        </div>
    @endisset
@endsection
