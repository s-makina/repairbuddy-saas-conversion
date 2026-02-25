@extends('tenant.layouts.customer')

@section('title', 'My Account — ' . ($tenant->name ?? 'My Portal'))

@section('content')

    {{-- Page header --}}
    <div class="cp-page-header">
        <h1 class="cp-page-title">My Account</h1>
        <p class="cp-page-subtitle">Update your personal details and contact information.</p>
    </div>

    {{-- Account Form --}}
    <div class="cp-card">
        <div class="cp-card-header">
            <h2 class="cp-card-title"><i class="bi bi-person"></i> Personal Information</h2>
        </div>
        <div class="cp-card-body">
            <form method="POST" action="{{ route('tenant.customer.account.update', ['business' => $business]) }}">
                @csrf

                {{-- Name --}}
                <div class="cp-form-grid cp-form-grid-2">
                    <div class="cp-form-group">
                        <label class="cp-form-label" for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name"
                               class="cp-form-input"
                               value="{{ old('first_name', $user->first_name) }}"
                               required>
                        @error('first_name')
                            <div class="cp-form-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="cp-form-group">
                        <label class="cp-form-label" for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name"
                               class="cp-form-input"
                               value="{{ old('last_name', $user->last_name) }}">
                        @error('last_name')
                            <div class="cp-form-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Contact --}}
                <div class="cp-form-grid cp-form-grid-2">
                    <div class="cp-form-group">
                        <label class="cp-form-label" for="email">Email Address *</label>
                        <input type="email" id="email" name="email"
                               class="cp-form-input"
                               value="{{ old('email', $user->email) }}"
                               required>
                        @error('email')
                            <div class="cp-form-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="cp-form-group">
                        <label class="cp-form-label" for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone"
                               class="cp-form-input"
                               value="{{ old('phone', $user->phone) }}"
                               placeholder="e.g. +1 555 123 4567">
                        @error('phone')
                            <div class="cp-form-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Company --}}
                <div class="cp-form-group">
                    <label class="cp-form-label" for="company">Company</label>
                    <input type="text" id="company" name="company"
                           class="cp-form-input"
                           value="{{ old('company', $user->company) }}"
                           placeholder="Optional">
                    @error('company')
                        <div class="cp-form-error">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Divider --}}
                <hr style="border:0; border-top:1px solid var(--cp-border); margin:1.5rem 0;">

                <h3 style="font-size:.82rem; font-weight:700; color:var(--cp-text); margin-bottom:1rem;">
                    <i class="bi bi-geo-alt" style="color:var(--cp-brand);"></i> Address
                </h3>

                {{-- Address --}}
                <div class="cp-form-group">
                    <label class="cp-form-label" for="address">Street Address</label>
                    <input type="text" id="address" name="address"
                           class="cp-form-input"
                           value="{{ old('address', $user->address) }}">
                    @error('address')
                        <div class="cp-form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="cp-form-grid cp-form-grid-2">
                    <div class="cp-form-group">
                        <label class="cp-form-label" for="city">City</label>
                        <input type="text" id="city" name="city"
                               class="cp-form-input"
                               value="{{ old('city', $user->city) }}">
                        @error('city')
                            <div class="cp-form-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="cp-form-group">
                        <label class="cp-form-label" for="state">State / Province</label>
                        <input type="text" id="state" name="state"
                               class="cp-form-input"
                               value="{{ old('state', $user->state) }}">
                        @error('state')
                            <div class="cp-form-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="cp-form-grid cp-form-grid-2">
                    <div class="cp-form-group">
                        <label class="cp-form-label" for="zip">ZIP / Postal Code</label>
                        <input type="text" id="zip" name="zip"
                               class="cp-form-input"
                               value="{{ old('zip', $user->zip) }}">
                        @error('zip')
                            <div class="cp-form-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="cp-form-group">
                        <label class="cp-form-label" for="country">Country</label>
                        <input type="text" id="country" name="country"
                               class="cp-form-input"
                               value="{{ old('country', $user->country) }}">
                        @error('country')
                            <div class="cp-form-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Submit --}}
                <div style="padding-top:1rem; border-top:1px solid var(--cp-border); margin-top:1rem; display:flex; align-items:center; gap:.75rem;">
                    <button type="submit" class="cp-btn-primary">
                        <i class="bi bi-check-lg"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Account Info --}}
    <div class="cp-card">
        <div class="cp-card-header">
            <h2 class="cp-card-title"><i class="bi bi-shield-check"></i> Account Information</h2>
        </div>
        <div class="cp-card-body">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                <div>
                    <div class="cp-form-label" style="margin-bottom:.15rem;">Account Created</div>
                    <div style="font-size:.84rem; color:var(--cp-text);">
                        {{ $user->created_at?->format('F j, Y') ?? '—' }}
                    </div>
                </div>
                <div>
                    <div class="cp-form-label" style="margin-bottom:.15rem;">Email Verified</div>
                    <div style="font-size:.84rem;">
                        @if($user->email_verified_at)
                            <span style="color:var(--cp-success);">
                                <i class="bi bi-check-circle-fill"></i> Verified
                            </span>
                        @else
                            <span style="color:var(--cp-warning);">
                                <i class="bi bi-exclamation-circle"></i> Not verified
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
