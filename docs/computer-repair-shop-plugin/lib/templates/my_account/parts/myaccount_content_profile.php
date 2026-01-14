<?php
    defined( 'ABSPATH' ) || exit;

    // Get current user data
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;

    add_action('wp_enqueue_scripts', 'wcrb_enqueue_profile_scripts');
    
    $first_name    = $current_user->first_name;
    $last_name     = $current_user->last_name;
    $user_email    = $current_user->user_email;
    $phone_number  = get_user_meta( $user_id, 'billing_phone', true );
    $company       = get_user_meta( $user_id, 'billing_company', true );
    $billing_tax   = get_user_meta( $user_id, 'billing_tax', true );
    $address       = get_user_meta( $user_id, 'billing_address_1', true );
    $city          = get_user_meta( $user_id, 'billing_city', true );
    $zip_code      = get_user_meta( $user_id, 'billing_postcode', true );
    $state         = get_user_meta( $user_id, 'billing_state', true );
    $country       = get_user_meta( $user_id, 'billing_country', true );
    $country       = ( empty( $country ) ) ? get_option( 'wc_primary_country' ) : $country;

    //intl-tel-input
    wp_register_script( 'intl-tel-input', WC_COMPUTER_REPAIR_DIR_URL . '/assets/vendors/intl-tel-input/js/intlTelInputWithUtils.min.js', array( 'jquery' ), '23.1.0', true );
    wp_register_style( 'intl-tel-input', WC_COMPUTER_REPAIR_DIR_URL . '/assets/vendors/intl-tel-input/css/intlTelInput.min.css', array(),'23.1.0','all' );
    wp_enqueue_script("intl-tel-input");
    wp_enqueue_style("intl-tel-input");

    add_action( 'wp_print_footer_scripts', 'wcrb_intl_tel_input_script' );
?>

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
                        <i class="bi bi-person me-2"></i><?php esc_html_e( "Personal Information", "computer-repair-shop" ); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form id="profileForm" data-async method="post">
                        <div class="alert alert-danger d-none" id="formErrors"></div>
                        <div class="alert alert-success d-none" id="formSuccess"></div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="reg_fname" class="form-label"><?php echo esc_html__("First Name", "computer-repair-shop"); ?> *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" name="reg_fname" class="form-control" id="reg_fname" 
                                           value="<?php echo esc_attr($first_name); ?>" required>
                                </div>
                                <div class="form-text text-danger d-none"><?php echo esc_html__("First Name Is Required.", "computer-repair-shop"); ?></div>
                            </div>

                            <div class="col-md-6">
                                <label for="reg_lname" class="form-label"><?php echo esc_html__("Last Name", "computer-repair-shop"); ?></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" name="reg_lname" class="form-control" id="reg_lname" 
                                           value="<?php echo esc_attr($last_name); ?>">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="reg_email" class="form-label"><?php echo esc_html__("Email", "computer-repair-shop"); ?> *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="reg_email" class="form-control" id="reg_email" 
                                           value="<?php echo esc_attr($user_email); ?>" required>
                                </div>
                                <div class="form-text text-danger d-none"><?php echo esc_html__("Email Is Required.", "computer-repair-shop"); ?></div>
                            </div>

                            <div class="col-md-6">
                                <label for="phoneNumber_ol" class="form-label"><?php echo esc_html__("Phone Number", "computer-repair-shop"); ?></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                    <input type="text" name="phoneNumber_ol" class="form-control" id="phoneNumber_ol" 
                                           value="<?php echo esc_attr($phone_number); ?>">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="customer_company" class="form-label"><?php echo esc_html__("Company", "computer-repair-shop"); ?></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-building"></i></span>
                                    <input type="text" name="customer_company" class="form-control" id="customer_company" 
                                           value="<?php echo esc_attr($company); ?>">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="billing_tax" class="form-label"><?php echo esc_html__("Tax ID", "computer-repair-shop"); ?></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-receipt"></i></span>
                                    <input type="text" name="billing_tax" class="form-control" id="billing_tax" 
                                           value="<?php echo esc_attr($billing_tax); ?>">
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="customer_address" class="form-label"><?php echo esc_html__("Address", "computer-repair-shop"); ?></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                    <input type="text" name="customer_address" class="form-control" id="customer_address" 
                                           value="<?php echo esc_attr($address); ?>">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label for="zip_code" class="form-label"><?php echo esc_html__("Postal/Zip Code", "computer-repair-shop"); ?></label>
                                <input type="text" name="zip_code" class="form-control" id="zip_code" 
                                       value="<?php echo esc_attr($zip_code); ?>">
                            </div>

                            <div class="col-md-4">
                                <label for="customer_city" class="form-label"><?php echo esc_html__("City", "computer-repair-shop"); ?></label>
                                <input type="text" name="customer_city" class="form-control" id="customer_city" 
                                       value="<?php echo esc_attr($city); ?>">
                            </div>

                            <div class="col-md-4">
                                <label for="state_province" class="form-label"><?php echo esc_html__("State/Province", "computer-repair-shop"); ?></label>
                                <input type="text" name="state_province" class="form-control" id="state_province" 
                                       value="<?php echo esc_attr($state); ?>">
                            </div>

                            <div class="col-12">
                                <label for="country" class="form-label"><?php echo esc_html__("Country", "computer-repair-shop"); ?></label>
                                <select name="country" class="form-select" id="country">
                                    <?php 
                                        $allowed_html = wc_return_allowed_tags();
                                        $optionsGenerated = wc_cr_countries_dropdown( $country, 'return' );
                                        echo wp_kses( $optionsGenerated, $allowed_html );
                                    ?>
                                </select>
                            </div>

                            <?php wp_nonce_field( 'wcrb_updateuser_nonce', 'wcrb_updateuser_nonce_post', true, true ); ?>
                            <input type="hidden" name="form_type" value="update_user" />
                            <input type="hidden" name="update_type" value="customer" />
                            <input type="hidden" name="update_user" value="<?php echo esc_attr( $user_id ); ?>" />

                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted"><?php echo esc_html__("(*) fields are required", "computer-repair-shop"); ?></small>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-circle me-2"></i><?php echo esc_html__("Update Profile", "computer-repair-shop"); ?>
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
                        <i class="bi bi-shield-lock me-2"></i><?php esc_html_e( "Change Password", "computer-repair-shop" ); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form id="passwordForm" data-async method="post">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="current_password" class="form-label"><?php esc_html_e( "Current Password", "computer-repair-shop" ); ?> *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" name="current_password" class="form-control" id="current_password" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="new_password" class="form-label"><?php esc_html_e( "New Password", "computer-repair-shop" ); ?> *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-key"></i></span>
                                    <input type="password" name="new_password" class="form-control" id="new_password" required>
                                </div>
                                <div class="form-text"><?php esc_html_e( "Minimum 8 characters with letters and numbers", "computer-repair-shop" ); ?></div>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label"><?php esc_html_e( "Confirm New Password", "computer-repair-shop" ); ?> *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                                    <input type="password" name="confirm_password" class="form-control" id="confirm_password" required>
                                </div>
                            </div>
                            <?php wp_nonce_field( 'wcrb_updatepassword_nonce', 'wcrb_updatepassword_nonce_post', true, true ); ?>
                            <input type="hidden" name="form_type" value="update_password" />
                            <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>" />
                            <div class="col-12">
                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-arrow-repeat me-2"></i><?php esc_html_e( "Update Password", "computer-repair-shop" ); ?>
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
                    <h5 class="card-title mb-0"><?php esc_html_e( "Profile Picture", "computer-repair-shop" ); ?></h5>
                </div>
                <div class="card-body text-center">
                    <div class="profile-picture-wrapper position-relative d-inline-block mb-3">
                        <?php 
                        $current_avatar = get_avatar( $user_id, 150, '', '', array( 'class' => 'rounded-circle' ) );
                        echo wp_kses( $current_avatar, $allowed_html );
                        ?>
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
                                <i class="bi bi-upload me-1"></i><?php esc_html_e( "Upload New Photo", "computer-repair-shop" ); ?>
                            </button>
                        </div>
                        <small class="text-muted d-block mt-2">JPG, PNG <?php esc_html_e( "or.", "computer-repair-shop" ); ?> GIF. <?php esc_html_e( "Max size 2MB.", "computer-repair-shop" ); ?></small>
                        
                        <!-- Progress bar -->
                        <div class="progress mt-3 d-none" id="uploadProgress" style="height: 6px;">
                            <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                        </div>
                        
                        <!-- Upload status -->
                        <div id="uploadStatus" class="mt-2"></div>
                        
                        <?php wp_nonce_field('wcrb_update_profile_photo', 'wcrb_profile_photo_nonce'); ?>
                        <input type="hidden" name="action" value="wcrb_update_profile_photo">
                        <input type="hidden" name="user_id" value="<?php echo esc_html( $user_id ); ?>">
                    </form>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><?php esc_html_e( "Your Activity", "computer-repair-shop" ); ?></h5>
                </div>
                <?php 
                    $user_role = $current_user->roles;
                    $user_type = ( in_array( 'customer', $user_role, true ) ) ? 'customer' : 'technician';

                    $_jobs_count      = wc_return_jobs_by_user( $user_id, $user_type, $args = array() );
                    $_estimates_count = wc_return_jobs_by_user( $user_id, $user_type, $args = array( 'post_type' => 'rep_estimates' ) );
                    $_job_ids         = wc_return_jobs_by_user( $user_id, $user_type, $args = array( 'return' => 'ids' ) );
                    $_lifetime_value  = 0;

                    if ( ! empty( $_job_ids ) && is_array( $_job_ids ) ) {
                        foreach( $_job_ids as $thejob_id ) {
                            $grand_total = wc_order_grand_total( $thejob_id, 'grand_total' );
                            $_lifetime_value += floatval( $grand_total );
                        }
                    }
                ?>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted"><?php esc_html_e( "Total Jobs", "computer-repair-shop" ); ?></span>
                        <span class="fw-bold"><?php echo esc_html( $_jobs_count ); ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted"><?php esc_html_e( "Total Estimates", "computer-repair-shop" ); ?></span>
                        <span class="fw-bold"><?php echo esc_html( $_estimates_count ); ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted"><?php esc_html_e( "Lifetime Value", "computer-repair-shop" ); ?></span>
                        <span class="fw-bold"><?php echo esc_html( wc_cr_currency_format( $_lifetime_value ) ); ?></span>
                    </div>
                </div>
            </div>

            <!-- Account Status Card -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><?php esc_html_e( "Account Status", "computer-repair-shop" ); ?></h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted"><?php esc_html_e( "Member Since", "computer-repair-shop" ); ?></span>
                        <span class="fw-bold">
                            <?php 
                                $dateTime    = date_i18n( $date_format, strtotime( $current_user->user_registered ) );
                                echo esc_html( $dateTime ); 
                            ?>
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted"><?php esc_html_e( "Account Type", "computer-repair-shop" ); ?></span>
                        <span class="badge bg-success">
                            <?php 
                                $userRole = ucfirst( $current_user->roles[0] ?? 'Customer');
                                echo esc_html( $userRole ); 
                            ?>
                        </span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</main>