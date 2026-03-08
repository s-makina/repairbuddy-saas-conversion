@extends('layouts.tenant-auth', ['title' => 'Forgot Password'])

@section('content')
    <div class="auth-card">
        <div class="lock-icon">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
            </svg>
        </div>
        <div class="auth-header">
            <h1>Forgot your password?</h1>
            <p>No worries! Enter the email address associated with your account and we'll send you a reset link.</p>
        </div>

        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('tenant.password.email', ['business' => $tenantSlug]) }}" onsubmit="handleSubmit(this)">
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

            <button type="submit" class="btn-submit">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Send Reset Link
            </button>
        </form>

        <div class="footer-link">
            Remember your password? <a href="{{ route('tenant.login', ['business' => $tenantSlug]) }}">Sign in</a>
        </div>
    </div>
@push('scripts')
    <script>
        function handleSubmit(form) {
            const btn = form.querySelector('button[type="submit"]');
            if (btn && !btn.disabled) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner"></span> Sending...';
            }
        }
    </script>
@endpush
@endsection
