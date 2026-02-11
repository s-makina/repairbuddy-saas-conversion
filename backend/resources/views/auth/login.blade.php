@extends('layouts.auth', ['title' => 'Login'])

@section('content')
  <div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-5">
      <div class="text-center mb-4">
        <div class="fw-bold fs-3 rb-brand">RepairBuddy</div>
        <div class="text-muted">Sign in to continue</div>
      </div>

      <div class="card rb-auth-card">
        <div class="card-body p-4">
          <form method="POST" action="{{ route('web.login') }}">
            @csrf

            <div class="mb-3">
              <label class="form-label">Email</label>
              <input
                type="email"
                name="email"
                value="{{ old('email') }}"
                class="form-control @error('email') is-invalid @enderror"
                autocomplete="email"
                required
              />
              @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="mb-3">
              <label class="form-label">Password</label>
              <input
                type="password"
                name="password"
                class="form-control @error('password') is-invalid @enderror"
                autocomplete="current-password"
                required
              />
              @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="d-flex align-items-center justify-content-between mb-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" />
                <label class="form-check-label" for="remember">Remember me</label>
              </div>
            </div>

            <button type="submit" class="btn btn-rb w-100">
              Login
            </button>
          </form>
        </div>
      </div>

      <div class="text-center text-muted small mt-3">
        Tip: for now this is only for the Blade tenant dashboard.
      </div>
    </div>
  </div>
@endsection
