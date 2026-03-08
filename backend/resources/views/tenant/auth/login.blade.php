@extends('layouts.tenant-auth', ['title' => 'Sign In'])

@section('content')
    <div class="auth-card">
        <div class="auth-header">
            <h1>Welcome back</h1>
            <p>Please enter your details to sign in.</p>
        </div>

        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @error('auth')
            <div class="alert alert-danger">{{ $message }}</div>
        @enderror

        <form method="POST" action="{{ route('tenant.subdomain.login', ['business' => $tenantSlug]) }}" onsubmit="handleSubmit(this)">
            @csrf

            <div class="input-group">
                <label class="form-label">Email Address</label>
                <div class="input-wrap">
                    <input type="email" name="email" class="form-input @error('email') is-invalid @enderror" placeholder="name@company.com" value="{{ old('email') }}" autocomplete="email" required />
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="input-group">
                <div class="label-row">
                    <label class="form-label" style="margin-bottom:0">Password</label>
                    <a href="{{ route('tenant.subdomain.password.request', ['business' => $tenantSlug]) }}" class="forgot-link">Forgot?</a>
                </div>
                <div class="input-wrap">
                    <input type="password" name="password" id="password" class="form-input @error('password') is-invalid @enderror" placeholder="••••••••" autocomplete="current-password" required />
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <button type="button" class="pw-toggle" onclick="togglePw()">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="checkbox-row">
                <input type="checkbox" name="remember" id="remember" />
                <label for="remember">Stay logged in</label>
            </div>

            <button type="submit" class="btn-submit">Sign In</button>
        </form>

        <div class="footer-link">
            Need an account? <a href="{{ route('tenant.subdomain.register', ['business' => $tenantSlug]) }}">Register</a>
        </div>
    </div>
@push('scripts')
    <script>
        function togglePw() {
            const i = document.getElementById('password');
            if (i) i.type = i.type === 'password' ? 'text' : 'password';
        }
        function handleSubmit(form) {
            const btn = form.querySelector('button[type="submit"]');
            if (btn && !btn.disabled) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner"></span> Signing in...';
            }
        }
    </script>
@endpush
@endsection
