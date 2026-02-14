@extends('tenant.layouts.myaccount', ['title' => 'Profile'])

@section('content')
@php
    $user = $user ?? auth()->user();
@endphp

<!-- Dashboard Content -->
<main class="dashboard-content container-fluid py-4">
    <!-- Page Header -->
    <!--<div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted mb-0">Manage your account information and preferences</p>
                </div>
                <div class="btn-group">
                    <button class="btn btn-outline-secondary">
                        <i class="bi bi-download me-2"></i>Download Jobs Report
                    </button>
                    <button class="btn btn-outline-secondary">
                        <i class="bi bi-download me-2"></i>Download Estimates Report
                    </button>
                </div>
            </div>
        </div>
    </div>-->

    <div class="row g-4">
        <!-- Left Column - Profile Form -->
        <div class="col-xl-8">
            <!-- Personal Information Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-person me-2"></i>Personal Information
                    </h5>
                </div>
                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success">{{ session('status') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <div class="fw-semibold mb-1">Please fix the errors below.</div>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form id="profileForm" method="post" action="{{ route('tenant.profile.update', ['business' => $tenant->slug]) }}">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Full Name *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input
                                        type="text"
                                        name="name"
                                        class="form-control @error('name') is-invalid @enderror"
                                        id="name"
                                        value="{{ old('name', $user?->name ?? '') }}"
                                        required
                                        autocomplete="name"
                                    >
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label">Email *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input
                                        type="email"
                                        name="email"
                                        class="form-control @error('email') is-invalid @enderror"
                                        id="email"
                                        value="{{ old('email', $user?->email ?? '') }}"
                                        required
                                        autocomplete="email"
                                    >
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                    <input
                                        type="text"
                                        name="phone"
                                        class="form-control @error('phone') is-invalid @enderror"
                                        id="phone"
                                        value="{{ old('phone', $user?->phone ?? '') }}"
                                        autocomplete="tel"
                                    >
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="company" class="form-label">Company</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-building"></i></span>
                                    <input
                                        type="text"
                                        name="company"
                                        class="form-control @error('company') is-invalid @enderror"
                                        id="company"
                                        value="{{ old('company', $user?->company ?? '') }}"
                                        autocomplete="organization"
                                    >
                                    @error('company')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="tax_id" class="form-label">Tax ID</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-receipt"></i></span>
                                    <input
                                        type="text"
                                        name="tax_id"
                                        class="form-control @error('tax_id') is-invalid @enderror"
                                        id="tax_id"
                                        value="{{ old('tax_id', $user?->tax_id ?? '') }}"
                                    >
                                    @error('tax_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="address_line1" class="form-label">Address line 1</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                    <input
                                        type="text"
                                        name="address_line1"
                                        class="form-control @error('address_line1') is-invalid @enderror"
                                        id="address_line1"
                                        value="{{ old('address_line1', $user?->address_line1 ?? '') }}"
                                        autocomplete="address-line1"
                                    >
                                    @error('address_line1')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="address_line2" class="form-label">Address line 2</label>
                                <input
                                    type="text"
                                    name="address_line2"
                                    class="form-control @error('address_line2') is-invalid @enderror"
                                    id="address_line2"
                                    value="{{ old('address_line2', $user?->address_line2 ?? '') }}"
                                    autocomplete="address-line2"
                                >
                                @error('address_line2')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="address_postal_code" class="form-label">Postal/Zip Code</label>
                                <input
                                    type="text"
                                    name="address_postal_code"
                                    class="form-control @error('address_postal_code') is-invalid @enderror"
                                    id="address_postal_code"
                                    value="{{ old('address_postal_code', $user?->address_postal_code ?? '') }}"
                                    autocomplete="postal-code"
                                >
                                @error('address_postal_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="address_city" class="form-label">City</label>
                                <input
                                    type="text"
                                    name="address_city"
                                    class="form-control @error('address_city') is-invalid @enderror"
                                    id="address_city"
                                    value="{{ old('address_city', $user?->address_city ?? '') }}"
                                    autocomplete="address-level2"
                                >
                                @error('address_city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="address_state" class="form-label">State/Province</label>
                                <input
                                    type="text"
                                    name="address_state"
                                    class="form-control @error('address_state') is-invalid @enderror"
                                    id="address_state"
                                    value="{{ old('address_state', $user?->address_state ?? '') }}"
                                    autocomplete="address-level1"
                                >
                                @error('address_state')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="address_country" class="form-label">Country</label>
                                <select name="address_country" class="form-select @error('address_country') is-invalid @enderror" id="address_country">
                                    <option value="">Select a country</option>
                                    @foreach (($countries ?? []) as $code => $label)
                                        <option value="{{ $code }}" @selected(old('address_country', $user?->address_country ?? '') === $code)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('address_country')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">(*) fields are required</small>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-circle me-2"></i>Update Profile
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Change Password Card -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-shield-lock me-2"></i>Change Password
                    </h5>
                </div>
                <div class="card-body">
                    <form id="passwordForm" method="post" action="{{ route('tenant.profile.password.update', ['business' => $tenant->slug]) }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="current_password" class="form-label">Current Password *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" id="current_password" required>
                                    @error('current_password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="password" class="form-label">New Password *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-key"></i></span>
                                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" id="password" required>
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-text">Minimum 8 characters with letters and numbers</div>
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label">Confirm New Password *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                                    <input type="password" name="password_confirmation" class="form-control" id="password_confirmation" required>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-arrow-repeat me-2"></i>Update Password
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Column - Profile Sidebar -->
        <div class="col-xl-4">
            <!-- Profile Picture Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Profile Picture</h5>
                </div>
                <div class="card-body text-center">
                    <div class="profile-picture-wrapper position-relative d-inline-block mb-3">
                        @if ($user?->avatar_url)
                            <img src="{{ $user->avatar_url }}" alt="Profile picture" class="rounded-circle" style="width: 96px; height: 96px; object-fit: cover;" />
                        @else
                            <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center" style="width: 96px; height: 96px;">
                                <i class="bi bi-person" style="font-size: 2rem;"></i>
                            </div>
                        @endif
                        <div class="position-absolute bottom-0 end-0">
                            <label for="profilePhotoUpload" class="btn btn-primary btn-sm rounded-circle mb-0 cursor-pointer">
                                <i class="bi bi-camera"></i>
                            </label>
                        </div>
                    </div>
                    
                    <form id="profilePhotoForm" method="post" action="{{ route('tenant.profile.photo.update', ['business' => $tenant->slug]) }}" enctype="multipart/form-data">
                        @csrf
                        <input type="file" id="profilePhotoUpload" name="profile_photo" accept=".jpg,.jpeg,.png,.gif" class="d-none">
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('profilePhotoUpload').click()">
                                <i class="bi bi-upload me-1"></i>Upload New Photo
                            </button>
                            <button type="submit" class="btn btn-primary btn-sm" id="profilePhotoSubmit" disabled>Save Photo</button>
                        </div>
                        <div class="small text-muted mt-2" id="profilePhotoFilename">No file selected</div>
                        <small class="text-muted d-block mt-2">JPG, PNG or. GIF. Max size 2MB.</small>
                        
                        <!-- Progress bar -->
                        <div class="progress mt-3 d-none" id="uploadProgress" style="height: 6px;">
                            <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                        </div>
                        
                        <!-- Upload status -->
                        <div id="uploadStatus" class="mt-2"></div>
                    </form>

                    <script>
                        (function () {
                            var input = document.getElementById('profilePhotoUpload');
                            var filename = document.getElementById('profilePhotoFilename');
                            var submit = document.getElementById('profilePhotoSubmit');

                            if (!input || !filename || !submit) return;

                            var updateUi = function () {
                                var file = input.files && input.files.length ? input.files[0] : null;
                                filename.textContent = file ? file.name : 'No file selected';
                                submit.disabled = !file;
                            };

                            input.addEventListener('change', updateUi);
                            updateUi();
                        })();
                    </script>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Your Activity</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">Total Jobs</span>
                        <span class="fw-bold">{{ $_jobs_count }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">Total Estimates</span>
                        <span class="fw-bold">{{ $_estimates_count }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">Lifetime Value</span>
                        <span class="fw-bold">{{ $lifetime_value_formatted }}</span>
                    </div>
                </div>
            </div>

            <!-- Account Status Card -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Account Status</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">Member Since</span>
                        <span class="fw-bold">
                            {{ $dateTime }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">Account Type</span>
                        <span class="badge bg-success">
                            {{ $userRole }}
                        </span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</main>
@endsection
