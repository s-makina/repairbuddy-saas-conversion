@extends('layouts.auth', ['title' => 'Forgot Password'])

@php
  $tenantSlug = $tenantSlug ?? null;
  $tenantRoutePrefix = request()->routeIs('tenant.subdomain.*') ? 'tenant.subdomain' : 'tenant';
@endphp

@section('content')
  <div class="auth-card">
    <div class="text-center">
      <a href="/" class="brand-logo">99<span>SmartX</span></a>
    </div>

    <div class="auth-header text-center">
      <h1>Forgot password?</h1>
      <p>No worries, we'll send you reset instructions.</p>
    </div>

    @if(session('status'))
      <div class="alert alert-success mb-4">
        {{ session('status') }}
      </div>
    @endif

    <form method="POST" action="{{ $tenantSlug ? route($tenantRoutePrefix.'.password.email', ['business' => $tenantSlug]) : route('password.email') }}" onsubmit="handleSubmit(this)">
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
            placeholder="Enter your email"
            autocomplete="email"
            required
          />
          <i class="bi bi-envelope"></i>
        </div>
        @error('email')
          <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
      </div>

      <button type="submit" class="btn btn-modern">Reset password</button>
    </form>

    <div class="footer-link">
      <a href="{{ $tenantSlug ? route($tenantRoutePrefix.'.login', ['business' => $tenantSlug]) : route('login') }}"><i class="bi bi-arrow-left me-2"></i>Back to log in</a>
    </div>
  </div>
@push('scripts')
  <script>
    function handleSubmit(form) {
      const btn = form.querySelector('button[type="submit"]');
      if (btn && !btn.disabled) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Sending...';
      }
    }
  </script>
@endpush
@endsection
