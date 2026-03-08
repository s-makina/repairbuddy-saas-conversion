@extends('layouts.tenant-auth', ['title' => 'Create Account'])

@section('content')
    <div class="auth-card wider">
        <div class="auth-header">
            <h1>Create your account</h1>
            <p>Sign up to book repairs and track your orders.</p>
        </div>

        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('tenant.register', ['business' => $tenantSlug]) }}" onsubmit="handleSubmit(this)">
            @csrf

            <div class="name-row">
                <div class="input-group">
                    <label class="form-label">First Name</label>
                    <input type="text" name="first_name" class="form-input-simple @error('first_name') is-invalid @enderror" placeholder="John" value="{{ old('first_name') }}" required />
                    @error('first_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="input-group">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="last_name" class="form-input-simple @error('last_name') is-invalid @enderror" placeholder="Smith" value="{{ old('last_name') }}" required />
                    @error('last_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="input-group">
                <label class="form-label">Email Address</label>
                <div class="input-wrap">
                    <input type="email" name="email" class="form-input @error('email') is-invalid @enderror" placeholder="john@email.com" value="{{ old('email') }}" autocomplete="email" required />
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="input-group">
                <label class="form-label">Password</label>
                <div class="input-wrap">
                    <input type="password" name="password" id="password" class="form-input @error('password') is-invalid @enderror" placeholder="Minimum 8 characters" autocomplete="new-password" required />
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </div>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="input-group">
                <label class="form-label">Confirm Password</label>
                <div class="input-wrap">
                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-input" placeholder="Repeat your password" autocomplete="new-password" required />
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
            </div>

            <div class="terms">
                <input type="checkbox" name="terms" id="terms" required />
                <label for="terms">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></label>
            </div>
            @error('terms')
                <div class="invalid-feedback" style="margin-top:-14px;margin-bottom:14px">{{ $message }}</div>
            @enderror

            <button type="submit" class="btn-submit">Create Account</button>
        </form>

        <div class="footer-link">
            Already have an account? <a href="{{ route('tenant.login', ['business' => $tenantSlug]) }}">Sign in</a>
        </div>
    </div>
@push('scripts')
    <script>
        function handleSubmit(form) {
            const btn = form.querySelector('button[type="submit"]');
            if (btn && !btn.disabled) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner"></span> Creating account...';
            }
        }
    </script>
@endpush
@endsection
