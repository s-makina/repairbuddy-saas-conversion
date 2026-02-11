@extends('tenant.layouts.myaccount', ['title' => 'Profile'])

@section('content')
@php
    $user = $user ?? auth()->user();

    $user_id = $user_id ?? ($user->id ?? null);

    $first_name = $first_name ?? ($user->first_name ?? '');
    $last_name = $last_name ?? ($user->last_name ?? '');
    $user_email = $user_email ?? ($user->email ?? '');
    $phone_number = $phone_number ?? ($user->phone_number ?? ($user->billing_phone ?? ''));
    $company = $company ?? ($user->company ?? ($user->billing_company ?? ''));
    $billing_tax = $billing_tax ?? ($user->billing_tax ?? '');
    $address = $address ?? ($user->address ?? ($user->billing_address_1 ?? ''));
    $city = $city ?? ($user->city ?? ($user->billing_city ?? ''));
    $zip_code = $zip_code ?? ($user->zip_code ?? ($user->billing_postcode ?? ''));
    $state = $state ?? ($user->state ?? ($user->billing_state ?? ''));
    $country = $country ?? ($user->country ?? ($user->billing_country ?? ''));

    $optionsGenerated = $optionsGenerated ?? '';
    $current_avatar = $current_avatar ?? '';

    $_jobs_count = $_jobs_count ?? 0;
    $_estimates_count = $_estimates_count ?? 0;
    $_lifetime_value = $_lifetime_value ?? 0;
    $lifetime_value_formatted = $lifetime_value_formatted ?? $_lifetime_value;

    $dateTime = $dateTime ?? '';

    $userRole = $userRole ?? (isset($user->role) ? ucfirst($user->role) : (isset($user->roles[0]) ? ucfirst($user->roles[0]) : 'Customer'));

    $wcrb_updateuser_nonce_post = $wcrb_updateuser_nonce_post ?? '';
    $wcrb_updatepassword_nonce_post = $wcrb_updatepassword_nonce_post ?? '';
    $wcrb_profile_photo_nonce = $wcrb_profile_photo_nonce ?? '';
    $wp_http_referer = $wp_http_referer ?? '';
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
                    <form id="profileForm" data-async method="post">
                        <div class="alert alert-danger d-none" id="formErrors"></div>
                        <div class="alert alert-success d-none" id="formSuccess"></div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="reg_fname" class="form-label">First Name *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" name="reg_fname" class="form-control" id="reg_fname" 
                                           value="{{ $first_name }}" required>
                                </div>
                                <div class="form-text text-danger d-none">First Name Is Required.</div>
                            </div>

                            <div class="col-md-6">
                                <label for="reg_lname" class="form-label">Last Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" name="reg_lname" class="form-control" id="reg_lname" 
                                           value="{{ $last_name }}">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="reg_email" class="form-label">Email *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="reg_email" class="form-control" id="reg_email" 
                                           value="{{ $user_email }}" required>
                                </div>
                                <div class="form-text text-danger d-none">Email Is Required.</div>
                            </div>

                            <div class="col-md-6">
                                <label for="phoneNumber_ol" class="form-label">Phone Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                    <input type="text" name="phoneNumber_ol" class="form-control" id="phoneNumber_ol" 
                                           value="{{ $phone_number }}">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="customer_company" class="form-label">Company</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-building"></i></span>
                                    <input type="text" name="customer_company" class="form-control" id="customer_company" 
                                           value="{{ $company }}">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="billing_tax" class="form-label">Tax ID</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-receipt"></i></span>
                                    <input type="text" name="billing_tax" class="form-control" id="billing_tax" 
                                           value="{{ $billing_tax }}">
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="customer_address" class="form-label">Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                    <input type="text" name="customer_address" class="form-control" id="customer_address" 
                                           value="{{ $address }}">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label for="zip_code" class="form-label">Postal/Zip Code</label>
                                <input type="text" name="zip_code" class="form-control" id="zip_code" 
                                       value="{{ $zip_code }}">
                            </div>

                            <div class="col-md-4">
                                <label for="customer_city" class="form-label">City</label>
                                <input type="text" name="customer_city" class="form-control" id="customer_city" 
                                       value="{{ $city }}">
                            </div>

                            <div class="col-md-4">
                                <label for="state_province" class="form-label">State/Province</label>
                                <input type="text" name="state_province" class="form-control" id="state_province" 
                                       value="{{ $state }}">
                            </div>

                            <div class="col-12">
                                <label for="country" class="form-label">Country</label>
                                <select name="country" class="form-select" id="country">
                                    {!! $optionsGenerated !!}
                                </select>
                            </div>

                            <input type="hidden" id="wcrb_updateuser_nonce_post" name="wcrb_updateuser_nonce_post" value="{{ $wcrb_updateuser_nonce_post }}" />
                            <input type="hidden" name="_wp_http_referer" value="{{ $wp_http_referer }}" />
                            <input type="hidden" name="form_type" value="update_user" />
                            <input type="hidden" name="update_type" value="customer" />
                            <input type="hidden" name="update_user" value="{{ $user_id }}" />

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
                    <form id="passwordForm" data-async method="post">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="current_password" class="form-label">Current Password *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" name="current_password" class="form-control" id="current_password" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="new_password" class="form-label">New Password *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-key"></i></span>
                                    <input type="password" name="new_password" class="form-control" id="new_password" required>
                                </div>
                                <div class="form-text">Minimum 8 characters with letters and numbers</div>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirm New Password *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                                    <input type="password" name="confirm_password" class="form-control" id="confirm_password" required>
                                </div>
                            </div>
                            <input type="hidden" id="wcrb_updatepassword_nonce_post" name="wcrb_updatepassword_nonce_post" value="{{ $wcrb_updatepassword_nonce_post }}" />
                            <input type="hidden" name="_wp_http_referer" value="{{ $wp_http_referer }}" />
                            <input type="hidden" name="form_type" value="update_password" />
                            <input type="hidden" name="user_id" value="{{ $user_id }}" />
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
                        {!! $current_avatar !!}
                        <div class="position-absolute bottom-0 end-0">
                            <label for="profilePhotoUpload" class="btn btn-primary btn-sm rounded-circle mb-0 cursor-pointer">
                                <i class="bi bi-camera"></i>
                            </label>
                        </div>
                    </div>
                    
                    <form id="profilePhotoForm" enctype="multipart/form-data">
                        <input type="file" id="profilePhotoUpload" name="profile_photo" accept=".jpg,.jpeg,.png,.gif" class="d-none">
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('profilePhotoUpload').click()">
                                <i class="bi bi-upload me-1"></i>Upload New Photo
                            </button>
                        </div>
                        <small class="text-muted d-block mt-2">JPG, PNG or. GIF. Max size 2MB.</small>
                        
                        <!-- Progress bar -->
                        <div class="progress mt-3 d-none" id="uploadProgress" style="height: 6px;">
                            <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                        </div>
                        
                        <!-- Upload status -->
                        <div id="uploadStatus" class="mt-2"></div>
                        
                        <input type="hidden" id="wcrb_profile_photo_nonce" name="wcrb_profile_photo_nonce" value="{{ $wcrb_profile_photo_nonce }}" />
                        <input type="hidden" name="_wp_http_referer" value="{{ $wp_http_referer }}" />
                        <input type="hidden" name="action" value="wcrb_update_profile_photo">
                        <input type="hidden" name="user_id" value="{{ $user_id }}">
                    </form>
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
