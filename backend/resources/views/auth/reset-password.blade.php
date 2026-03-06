@extends('layouts.auth', ['title' => 'Reset Password'])

@php
  $tenantSlug = $tenantSlug ?? null;
@endphp

@section('content')
  <div class="auth-card">
    <div class="text-center">
      <a href="/" class="brand-logo">Repair<span>Buddy</span></a>
    </div>

    <div class="auth-header text-center">
      <h1>Set new password</h1>
      <p>Your new password must be different from previously used passwords.</p>
    </div>

    @if(session('status'))
      <div class="alert alert-success mb-4">
        {{ session('status') }}
      </div>
    @endif

    <form method="POST" action="{{ $tenantSlug ? route('tenant.password.update', ['business' => $tenantSlug]) : route('password.update') }}">
      @csrf
      <input type="hidden" name="token" value="{{ $token ?? request('token') }}">
      <input type="hidden" name="email" value="{{ $email ?? old('email') ?? request('email') }}">

      <div class="input-group-modern">
        <label for="password" class="form-label">Password</label>
        <div style="position: relative;">
          <input
            type="password"
            name="password"
            id="password"
            class="form-control @error('password') is-invalid @enderror"
            placeholder="••••••••"
            autocomplete="new-password"
            required
          />
          <i class="bi bi-lock"></i>
          <button type="button" class="password-toggle" onclick="togglePassword('password', this)">
            <i class="bi bi-eye" style="position: static; font-size: 0.9rem;"></i>
          </button>
          @error('password')
            <div class="invalid-feedback d-block">{{ $message }}</div>
          @enderror
        </div>
      </div>

      <div class="input-group-modern">
        <label for="password_confirmation" class="form-label">Confirm Password</label>
        <div style="position: relative;">
          <input
            type="password"
            name="password_confirmation"
            id="password_confirmation"
            class="form-control"
            placeholder="••••••••"
            autocomplete="new-password"
            required
          />
          <i class="bi bi-shield-check"></i>
        </div>
      </div>

      <button type="submit" class="btn btn-modern">Reset password</button>
    </form>
  </div>
@push('scripts')
  <script>
    function togglePassword(inputId, button) {
      const input = document.getElementById(inputId);
      const icon = button.querySelector('i');
      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
      } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
      }
    }
  </script>
@endpush
@endsection
