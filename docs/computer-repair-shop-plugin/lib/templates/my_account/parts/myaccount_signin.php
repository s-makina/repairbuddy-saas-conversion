<?php
    defined('ABSPATH') || exit;

    $_sitename      = get_bloginfo('name');
    $_thepagetitle  = esc_html__( 'Sign in to your account', 'computer-repair-shop' ) . ' - ' . $_sitename;

    // Get current page URL for redirects
    $current_url = get_the_permalink() ?: home_url();

    // Handle forgot password form submission
    if ( isset( $_POST['wp-submit-forgot'] ) ) {
        $errors = retrieve_password();
        
        if ( is_wp_error( $errors ) ) {
            $forgot_error = $errors->get_error_message();
        } else {
            // Password reset email sent successfully - redirect with query string
            $redirect_url = ! empty( $_POST['redirect_to'] ) ? $_POST['redirect_to'] : add_query_arg( 'forgotmsg', 'sent', $current_url );
            wp_safe_redirect( $redirect_url );
            exit;
        }
    }

    // Check for success message in URL
    if (isset($_GET['forgotmsg']) && $_GET['forgotmsg'] === 'sent') {
        $forgot_success = true;
    }
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html( $_thepagetitle ); ?></title>
    <?php wp_head(); ?>
</head>

<body class="bg-light">
    <div class="auth-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <!-- Top Bar -->
                    <div class="d-flex justify-content-end mb-4">
                        <div class="dropdown">
                            <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" title="<?php echo esc_html__( 'Theme Settings', 'computer-repair-shop' ); ?>">
                                <i class="bi bi-palette"></i>
                            </button>
                            <ul class="dropdown-menu rounded-md rounded-3 p-0">
                                <li>
                                    <div class="btn-group w-100" role="group">
                                        <button type="button" class="btn theme-option" data-theme="light" title="<?php echo esc_html__( 'Light Mode', 'computer-repair-shop' ); ?>">
                                            <i class="bi bi-sun"></i>
                                        </button>
                                        <button type="button" class="btn border-start border-end theme-option" data-theme="dark" title="<?php echo esc_html__( 'Dark Mode', 'computer-repair-shop' ); ?>">
                                            <i class="bi bi-moon"></i>
                                        </button>
                                        <button type="button" class="btn theme-option" data-theme="auto" title="<?php echo esc_html__( 'Auto Mode', 'computer-repair-shop' ); ?>">
                                            <i class="bi bi-circle-half"></i>
                                        </button>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Auth Card -->
                    <div class="card auth-card">
                        <div class="card-body p-4 p-md-5">
                            <!-- Logo -->
                            <div class="auth-logo">
                                <?php
                                    $logoUrl = wc_rb_return_logo_url_with_img( 'shoplogo' );
                                    $brandlink = ( ! defined( 'REPAIRBUDDY_LOGO_URL' ) ) ? esc_url( WC_COMPUTER_REPAIR_DIR_URL . '/assets/admin/images/repair-buddy-logo.png' ) : REPAIRBUDDY_LOGO_URL;
                                    $content = ( ! empty( $logoUrl ) ) ? $logoUrl : '<img src="' . esc_url( $brandlink ) . '" alt="RepairBuddy CRM Logo" />';

                                    echo wp_kses_post( $content );
                                ?>
                            </div>

                            <!-- Tabs Navigation -->
                            <ul class="nav nav-tabs auth-tabs mb-4" id="authTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab">
                                        <i class="bi bi-box-arrow-in-right me-2"></i><?php echo esc_html( 'Sign In', 'computer-repair-shop' ); ?>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button" role="tab">
                                        <i class="bi bi-person-plus me-2"></i><?php echo esc_html( 'Register', 'computer-repair-shop' ); ?>
                                    </button>
                                </li>
                            </ul>

                            <!-- Tab Content -->
                            <div class="tab-content" id="authTabsContent">
                                
                                <!-- Login Tab -->
                                <div class="tab-pane fade show active" id="login" role="tabpanel" tabindex="0">
                                    <div class="text-center mb-4">
                                        <?php $business_name = get_bloginfo('name');
                                                if (empty($business_name)) {
                                                    $business_name = 'RepairBuddy';
                                                } 
                                        ?>
                                        <h4 class="fw-bold"><?php echo sprintf( esc_html__( 'Welcome to %s', 'computer-repair-shop' ), $business_name ); ?>!</h4>
                                        <p class="text-muted"><?php echo esc_html__( "Let's sign you in to your account", "computer-repair-shop" ); ?></p>
                                    </div>

                                    <?php
                                    // Display login errors if any
                                    if ( ( isset( $forgot_error ) && ! empty( $forgot_error ) ) || ( isset($_GET['forgotmsg']) && $_GET['forgotmsg'] == 'sent' ) ): ?>
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <i class="bi bi-exclamation-triangle me-2"></i>
                                            <?php echo ( isset( $forgot_error ) ) ? wp_kses_post( $forgot_error ) : esc_html__( 'Check your email for the password reset link.', 'computer-repair-shop' ); ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    <?php endif; ?>

                                    <?php
                                        // Custom login form with Bootstrap styling
                                        $login_args = array(
                                            'echo'            => true,
                                            'redirect'        => $current_url,
                                            'form_id'         => 'loginform',
                                            'label_username'  => __('Username or Email', 'computer-repair-shop'),
                                            'label_password'  => __('Password', 'computer-repair-shop'),
                                            'label_remember'  => __('Remember Me', 'computer-repair-shop'),
                                            'label_log_in'    => __('Sign In', 'computer-repair-shop'),
                                            'remember'        => true
                                        );
                                    ?>
                                    <form name="loginform" id="loginform" action="<?php echo esc_url(site_url('wp-login.php', 'login_post')); ?>" method="post">
                                        <div class="mb-3">
                                            <label for="user_login" class="form-label"><?php echo esc_html( $login_args['label_username'] ); ?></label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                                <input type="text" name="log" id="user_login" class="form-control" required>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="user_pass" class="form-label"><?php echo esc_html( $login_args['label_password'] ); ?></label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                                <input type="password" name="pwd" id="user_pass" class="form-control" required>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" name="rememberme" id="rememberme" class="form-check-input" value="forever">
                                            <label for="rememberme" class="form-check-label"><?php echo esc_html( $login_args['label_remember'] ); ?></label>
                                        </div>
                                        
                                        <div class="d-grid mb-3">
                                            <button type="submit" name="wp-submit" class="btn btn-primary btn-lg">
                                                <i class="bi bi-box-arrow-in-right me-2"></i><?php echo $login_args['label_log_in']; ?>
                                            </button>
                                            <input type="hidden" name="redirect_to" value="<?php echo esc_url($login_args['redirect']); ?>">
                                        </div>
                                    </form>

                                    <div class="text-center">
                                        <a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">
                                            <i class="bi bi-question-circle me-1"></i><?php echo esc_html__( 'Forgot your password?', 'computer-repair-shop' ); ?>
                                        </a>
                                    </div>
                                </div>

                                <!-- Register Tab -->
                                <div class="tab-pane fade" id="register" role="tabpanel" tabindex="0">
                                    <div class="text-center mb-4">
                                        <h4 class="fw-bold"><?php esc_html_e( "Create Account", "computer-repair-shop" ); ?></h4>
                                        <p class="text-muted"><?php esc_html_e( "Join us today for better service", "computer-repair-shop" ); ?></p>
                                    </div>

                                    <?php
                                    $wc_rb_turn_registration_on = get_option('wc_rb_turn_registration_on');
                                    
                                    if ($wc_rb_turn_registration_on == 'on'): ?>
                                        <form name="registerform" id="registerform" method="post">
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="firstName" class="form-label"><?php esc_html_e("First Name", "computer-repair-shop"); ?> *</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                                                        <input type="text" name="firstName" id="firstName" required class="form-control" placeholder="">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="lastName" class="form-label"><?php esc_html_e("Last Name", "computer-repair-shop"); ?> *</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                                                        <input type="text" name="lastName" id="lastName" required class="form-control" placeholder="">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="userEmail" class="form-label"><?php esc_html_e("Email", "computer-repair-shop"); ?> *</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                                        <input type="email" name="userEmail" id="userEmail" required class="form-control" placeholder="">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="phoneNumber_ol" class="form-label"><?php esc_html_e("Phone number", "computer-repair-shop"); ?></label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                                        <input type="text" name="phoneNumber_ol" id="phoneNumber_ol" class="form-control" placeholder="">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="userCompany" class="form-label"><?php esc_html_e("Company", "computer-repair-shop"); ?></label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="bi bi-building"></i></span>
                                                        <input type="text" name="userCompany" id="userCompany" class="form-control" placeholder="">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="userAddress" class="form-label"><?php esc_html_e("Address", "computer-repair-shop"); ?></label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                                        <input type="text" name="userAddress" id="userAddress" class="form-control" placeholder="">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="postalCode" class="form-label"><?php esc_html_e("Postal Code", "computer-repair-shop"); ?></label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="bi bi-mailbox"></i></span>
                                                        <input type="text" name="postalCode" id="postalCode" class="form-control" placeholder="">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="userCity" class="form-label"><?php esc_html_e("City", "computer-repair-shop"); ?></label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="bi bi-building"></i></span>
                                                        <input type="text" name="userCity" id="userCity" class="form-control" placeholder="">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="userState" class="form-label"><?php esc_html_e("State/Province", "computer-repair-shop"); ?></label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="bi bi-geo"></i></span>
                                                        <input type="text" name="userState" id="userState" class="form-control" placeholder="">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="userCountry" class="form-label"><?php esc_html_e("Country", "computer-repair-shop"); ?></label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="bi bi-globe"></i></span>
                                                        <select name="userCountry" id="userCountry" class="form-control">
                                                            <?php
                                                            $country = get_option("wc_primary_country") ?: "";
                                                            echo wc_cr_countries_dropdown( $country, 'return' );
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <?php 
                                            // Add CAPTCHA field if function exists
                                            if (function_exists('repairbuddy_booking_captcha_field')) {
                                                echo '<div class="mb-3">';
                                                echo repairbuddy_booking_captcha_field();
                                                echo '</div>';
                                            }
                                            ?>

                                            <input type="hidden" name="form_action" value="wcrb_register_account" />
                                            <?php wp_nonce_field('wcrb_register_account_nonce', 'wcrb_register_account_nonce_post', true, true); ?>

                                            <div class="d-grid">
                                                <button type="submit" class="btn btn-primary btn-lg">
                                                    <i class="bi bi-person-plus me-2"></i><?php esc_html_e('Register Account!', "computer-repair-shop"); ?>
                                                </button>
                                            </div>

                                            <div class="resgister_account_form_message mt-3"></div>
                                        </form>
                                    <?php else: ?>
                                        <div class="alert alert-info text-center">
                                            <i class="bi bi-info-circle me-2"></i>
                                            <?php esc_html_e('User registration is currently disabled.', 'computer-repair-shop'); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="forgotPasswordModalLabel">
                        <i class="bi bi-key me-2"></i><?php echo esc_html__( 'Forgot Your Password?', 'computer-repair-shop' ); ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (isset($forgot_success) && $forgot_success): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle me-2"></i>
                            <?php echo esc_html__( 'Check your email for the password reset link.', 'computer-repair-shop' ); ?>
                        </div>
                    <?php elseif (isset($forgot_error)): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <?php echo wp_kses_post( $forgot_error ); ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-4"><?php echo esc_html__( "Enter your email address and we'll send you a link to reset your password.", "computer-repair-shop" ); ?></p>
                    <?php endif; ?>
                    
                    <?php if ( ! isset( $forgot_success ) ) : ?>
                        <form id="forgotPasswordForm" method="post" action="<?php echo esc_url($current_url); ?>">
                            <div class="mb-3">
                                <label for="user_email_forgot" class="form-label"><?php echo esc_html__( "Email Address", "computer-repair-shop" ); ?></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="user_login" id="user_email_forgot" class="form-control" required placeholder="<?php echo esc_html__( "Email Address", "computer-repair-shop" ); ?>" value="<?php echo isset($_POST['user_login']) ? esc_attr($_POST['user_login']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="wp-submit-forgot" class="btn btn-primary">
                                    <i class="bi bi-send me-2"></i><?php echo esc_html__( 'Send Reset Link', 'computer-repair-shop' ); ?>
                                </button>
                            </div>
                            
                            <input type="hidden" name="redirect_to" value="<?php echo esc_url( add_query_arg( 'forgotmsg', 'sent', $current_url ) ); ?>">
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php wp_footer(); ?>
</body>
</html>