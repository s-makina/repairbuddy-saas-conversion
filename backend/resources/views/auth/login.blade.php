@extends('layouts.auth', ['title' => 'Login'])

@php
  $tenantSlug = $tenantSlug ?? null;
  $tenant = $tenant ?? null;
@endphp

@section('content')
  <div class="auth-card">
    <div class="text-center">
      @if($tenant)
        <div class="tenant-brand mb-2">{{ $tenant->name }}</div>
      @endif
      <a href="/" class="brand-logo">Repair<span>Buddy</span></a>
    </div>

    <div class="auth-header text-center">
      <h1>Welcome back</h1>
      <p>Please enter your details to sign in.</p>
    </div>

    @if(session('status'))
      <div class="alert alert-success mb-4">
        {{ session('status') }}
      </div>
    @endif

    @error('auth')
      <div class="alert alert-danger mb-4">
        {{ $message }}
      </div>
    @enderror

    <form method="POST" action="{{ $tenantSlug ? route('tenant.login', ['business' => $tenantSlug]) : route('login') }}" onsubmit="handleSubmit(this)">
      @csrf

      <div class="input-group-modern">
        <label for="email" class="form-label">Email Address</label>
        <div class="input-wrapper">
          <input
            type="email"
            name="email"
            id="email"
            value="{{ old('email') }}"
            class="form-control @error('email') is-invalid @enderror"
            placeholder="name@company.com"
            autocomplete="email"
            required
          />
          <i class="bi bi-envelope"></i>
        </div>
        @error('email')
          <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
      </div>

      <div class="input-group-modern">
        <div class="d-flex justify-content-between">
          <label for="password" class="form-label">Password</label>
          <a href="{{ $tenantSlug ? route('tenant.password.request', ['business' => $tenantSlug]) : route('password.request') }}" class="forgot-password">Forgot?</a>
        </div>
        <div class="input-wrapper">
          <input
            type="password"
            name="password"
            id="password"
            class="form-control @error('password') is-invalid @enderror"
            placeholder="••••••••"
            autocomplete="current-password"
            required
          />
          <i class="bi bi-shield-lock"></i>
          <button type="button" class="password-toggle" onclick="togglePassword('password', this)">
            <i class="bi bi-eye" style="position: static; font-size: 0.9rem;"></i>
          </button>
        </div>
        @error('password')
          <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
      </div>

      <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="remember" id="remember" style="cursor: pointer;">
          <label class="form-check-label small text-muted" for="remember" style="cursor: pointer; font-weight: 500;">
            Stay logged in
          </label>
        </div>
      </div>

      <button type="submit" class="btn btn-modern">Sign In</button>
    </form>

    <div class="footer-link">
      @if($tenantSlug)
        Need an account? <a href="{{ route('tenant.register', ['business' => $tenantSlug]) }}">Register</a>
      @else
        Need a workspace? <a href="{{ route('register') }}">Get Started</a>
      @endif
    </div>
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

    function handleSubmit(form) {
      const btn = form.querySelector('button[type="submit"]');
      if (btn && !btn.disabled) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Signing in...';
      }
    }
  </script>
@endpush
@endsection
