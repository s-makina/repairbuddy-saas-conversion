@extends('layouts.auth', ['title' => 'Create Account'])

@php
  $tenantSlug = $tenantSlug ?? null;
  $tenant = $tenant ?? null;
  $tenantRoutePrefix = request()->routeIs('tenant.subdomain.*') ? 'tenant.subdomain' : 'tenant';
@endphp

@section('content')
  <div class="auth-card wider">
    <div class="text-center">
      @if($tenant)
        <div class="tenant-brand mb-2">{{ $tenant->name }}</div>
      @endif
      <a href="/" class="brand-logo">99<span>SmartX</span></a>
    </div>

    <div class="auth-header text-center">
      <h1>Join Workshop</h1>
      <p>Create your account to start collaborating with your team.</p>
    </div>

    @if(session('status'))
      <div class="alert alert-success mb-4">
        {{ session('status') }}
      </div>
    @endif

    <form method="POST" action="{{ $tenantSlug ? route($tenantRoutePrefix.'.register', ['business' => $tenantSlug]) : route('register') }}" onsubmit="handleSubmit(this)">
      @csrf

      <div class="row">
        <div class="col-md-6">
          <div class="input-group-modern">
            <label for="first_name" class="form-label">First Name</label>
            <div class="input-wrapper">
              <input
                type="text"
                name="first_name"
                id="first_name"
                value="{{ old('first_name') }}"
                class="form-control @error('first_name') is-invalid @enderror"
                placeholder="John"
                required
              />
              <i class="bi bi-person"></i>
            </div>
            @error('first_name')
              <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
          </div>
        </div>
        <div class="col-md-6">
          <div class="input-group-modern">
            <label for="last_name" class="form-label">Last Name</label>
            <div class="input-wrapper">
              <input
                type="text"
                name="last_name"
                id="last_name"
                value="{{ old('last_name') }}"
                class="form-control @error('last_name') is-invalid @enderror"
                placeholder="Doe"
                required
              />
              <i class="bi bi-person"></i>
            </div>
            @error('last_name')
              <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
          </div>
        </div>
      </div>

      <div class="input-group-modern">
        <label for="email" class="form-label">Email Address</label>
        <div class="input-wrapper">
          <input
            type="email"
            name="email"
            id="email"
            value="{{ old('email') }}"
            class="form-control @error('email') is-invalid @enderror"
            placeholder="john@example.com"
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
        <label for="password" class="form-label">Password</label>
        <div class="input-wrapper">
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
        </div>
        @error('password')
          <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
      </div>

      <div class="input-group-modern">
        <label for="password_confirmation" class="form-label">Confirm Password</label>
        <div class="input-wrapper">
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

      <div class="form-check mb-4">
        <input class="form-check-input" type="checkbox" name="terms" id="terms" required>
        <label class="form-check-label small text-muted" for="terms">
          I agree to the <a href="#" class="text-decoration-none fw-bold" style="color: var(--rb-blue);">Terms</a> and <a href="#" class="text-decoration-none fw-bold" style="color: var(--rb-blue);">Privacy Policy</a>.
        </label>
        @error('terms')
          <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
      </div>

      <button type="submit" class="btn btn-modern">Create Account</button>
    </form>

    <div class="footer-link">
      Already have an account? <a href="{{ $tenantSlug ? route($tenantRoutePrefix.'.login', ['business' => $tenantSlug]) : route('login') }}">Log in</a>
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
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Creating account...';
      }
    }
  </script>
@endpush
@endsection
