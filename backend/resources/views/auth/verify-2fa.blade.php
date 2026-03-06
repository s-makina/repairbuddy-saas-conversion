@extends('layouts.auth', ['title' => '2FA Verification'])

@php
  $tenantSlug = $tenantSlug ?? null;
@endphp

@section('content')
  <div class="auth-card">
    <div class="text-center">
      <div class="mb-4">
        <i class="bi bi-shield-check" style="font-size: 3rem; color: var(--rb-blue);"></i>
      </div>
    </div>

    <div class="auth-header text-center">
      <h1>Security code</h1>
      <p>We've sent a 6-digit verification code to your email address.</p>
    </div>

    @if(session('status'))
      <div class="alert alert-success mb-4">
        {{ session('status') }}
      </div>
    @endif

    <form method="POST" action="{{ $tenantSlug ? route('tenant.2fa.verify', ['business' => $tenantSlug]) : route('2fa.verify') }}">
      @csrf

      <div class="otp-container">
        <input type="text" name="code[]" class="otp-input" maxlength="1" pattern="\d*" inputmode="numeric" required>
        <input type="text" name="code[]" class="otp-input" maxlength="1" pattern="\d*" inputmode="numeric" required>
        <input type="text" name="code[]" class="otp-input" maxlength="1" pattern="\d*" inputmode="numeric" required>
        <input type="text" name="code[]" class="otp-input" maxlength="1" pattern="\d*" inputmode="numeric" required>
        <input type="text" name="code[]" class="otp-input" maxlength="1" pattern="\d*" inputmode="numeric" required>
        <input type="text" name="code[]" class="otp-input" maxlength="1" pattern="\d*" inputmode="numeric" required>
      </div>

      @error('code')
        <div class="alert alert-danger mb-4">{{ $message }}</div>
      @enderror

      <input type="hidden" name="code" id="full-code">

      <button type="submit" class="btn btn-modern">Verify code</button>
    </form>

    <div class="footer-link">
      Didn't receive the code? <a href="{{ $tenantSlug ? route('tenant.2fa.resend', ['business' => $tenantSlug]) : route('2fa.resend') }}">Click to resend</a>
    </div>
  </div>
@push('scripts')
  <script>
    // OTP input auto-focus and navigation
    const otpInputs = document.querySelectorAll('.otp-input');
    const fullCodeInput = document.getElementById('full-code');

    otpInputs.forEach((input, index) => {
      input.addEventListener('input', (e) => {
        if (e.target.value.length === 1 && index < otpInputs.length - 1) {
          otpInputs[index + 1].focus();
        }
        updateFullCode();
      });

      input.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && e.target.value === '' && index > 0) {
          otpInputs[index - 1].focus();
        }
      });

      input.addEventListener('paste', (e) => {
        e.preventDefault();
        const pasteData = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
        pasteData.split('').forEach((char, i) => {
          if (otpInputs[i]) {
            otpInputs[i].value = char;
          }
        });
        updateFullCode();
        if (pasteData.length > 0) {
          otpInputs[Math.min(pasteData.length, otpInputs.length - 1)].focus();
        }
      });
    });

    function updateFullCode() {
      fullCodeInput.value = Array.from(otpInputs).map(i => i.value).join('');
    }
  </script>
@endpush
@endsection
