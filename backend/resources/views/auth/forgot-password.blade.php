@extends('layouts.auth', ['title' => 'Forgot Password'])

@php
  $tenantSlug = $tenantSlug ?? null;
@endphp

@section('content')
  <div class="auth-card">
    <div class="text-center">
      <a href="/" class="brand-logo">Repair<span>Buddy</span></a>
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

    <form method="POST" action="{{ $tenantSlug ? route('tenant.password.email', ['business' => $tenantSlug]) : route('password.email') }}">
      @csrf

      <div class="input-group-modern">
        <label for="email" class="form-label">Email Address</label>
        <div style="position: relative;">
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
          @error('email')
            <div class="invalid-feedback d-block">{{ $message }}</div>
          @enderror
        </div>
      </div>

      <button type="submit" class="btn btn-modern">Reset password</button>
    </form>

    <div class="footer-link">
      <a href="{{ $tenantSlug ? route('tenant.login', ['business' => $tenantSlug]) : route('login') }}"><i class="bi bi-arrow-left me-2"></i>Back to log in</a>
    </div>
  </div>
@endsection
