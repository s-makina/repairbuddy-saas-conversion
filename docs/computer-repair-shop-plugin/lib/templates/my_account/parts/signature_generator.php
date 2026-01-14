<?php
    defined('ABSPATH') || exit;

    $_sitename      = get_bloginfo('name');
    $_thepagetitle  = esc_html__( 'Generate Signature', 'computer-repair-shop' ) . ' - ' . $_sitename;

    $allowedHTML  = wc_return_allowed_tags();

    // Get current page URL for redirects
    $current_url = get_the_permalink() ?: home_url();

    $_case_number     = ( isset( $_GET['case_number'] ) && ! empty( $_GET['case_number'] ) ) ? sanitize_text_field( $_GET['case_number'] ) : '';
    $order_id         = ( isset( $_GET['job_id'] ) && ! empty( $_GET['job_id'] ) ) ? sanitize_text_field( $_GET['job_id'] ) : '';
    $job_case_number  = get_the_title( $order_id );
    $signature_label  = isset($_GET['signature_label']) ? sanitize_text_field($_GET['signature_label']) : '';
    $signature_type   = isset($_GET['signature_type']) ? sanitize_text_field($_GET['signature_type']) : 'normal';
    
    // Check user role - you need to get $role from somewhere
    // Assuming you have a way to get current user role
    $current_user = wp_get_current_user();
    $role = !empty($current_user->roles) ? $current_user->roles[0] : '';
    
    if ( $job_case_number != $_case_number ) {
        wp_die( esc_html__( 'Something is wrong with your submission!', 'computer-repair-shop' ) );
    }

    // Check user permissions
    $allowed_roles = array('technician', 'store_manager', 'administrator', 'shop_manager');
    if ( !in_array($role, $allowed_roles) ) {
        wp_die( esc_html__( 'You do not have permission to access this page.', 'computer-repair-shop' ) );
    }

    $jobs_manager = WCRB_JOBS_MANAGER::getInstance();
    $job_data 	  = $jobs_manager->get_job_display_data( $order_id );
    $_job_id  	  = ( ! empty( $job_data['formatted_job_number'] ) ) ? $job_data['formatted_job_number'] : $order_id;
    
    // Get main page for signature URL
    $_mainpage = get_option('wc_rb_my_account_page_id');
    $signature_url = '';
    
    if ( ! empty( $_mainpage ) && $mainpage_url = get_the_permalink( $_mainpage ) ) {
        $WCRB_SIGNATURE_WORKFLOW = WCRB_SIGNATURE_WORKFLOW::getInstance();
        $signature_url = $WCRB_SIGNATURE_WORKFLOW->wcrb_generate_signature_url_with_verification( $signature_label, $signature_type, $order_id, $mainpage_url );
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
                <div class="col-md-10 col-lg-8">
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
                    <div class="card auth-card border-0 shadow-lg">
                        <div class="card-body p-4 p-md-5">
                            <!-- Logo -->
                            <div class="auth-logo mb-4">
                                <?php
                                    $logoUrl = wc_rb_return_logo_url_with_img( 'shoplogo' );
                                    $brandlink = ( ! defined( 'REPAIRBUDDY_LOGO_URL' ) ) ? esc_url( WC_COMPUTER_REPAIR_DIR_URL . '/assets/admin/images/repair-buddy-logo.png' ) : REPAIRBUDDY_LOGO_URL;
                                    $content = ( ! empty( $logoUrl ) ) ? $logoUrl : '<img src="' . esc_url( $brandlink ) . '" alt="RepairBuddy CRM Logo" class="img-fluid" />';

                                    echo wp_kses_post( $content );
                                ?>
                            </div>

                            <div class="text-center mb-3">
                                <?php 
                                    $business_name = get_bloginfo('name');
                                    if (empty($business_name)) {
                                        $business_name = 'RepairBuddy';
                                    } 
                                ?>
                                <h4 class="fw-bold mb-3"><?php echo esc_html__( 'Generate signature request', 'computer-repair-shop' ); ?></h4>
                                <?php if (!empty($signature_label)) : ?>
                                    <div class="alert alert-success d-inline-block">
                                        <i class="bi bi-check-circle me-2"></i>
                                        <?php echo esc_html__('Signature label set:', 'computer-repair-shop'); ?>
                                        <strong><?php echo esc_html($signature_label); ?></strong>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Job Details Card -->
                            <div class="card border shadow-sm mb-3">
                                <div class="card-body p-4">
                                    <div class="row">
                                        <?php
                                            // Get customer data
                                            $customerLabel   = get_post_meta( $order_id, "_customer_label", true );
                                            $customer_id     = get_post_meta( $order_id, '_customer', true );
                                            $pickup_date     = get_post_meta( $order_id, '_pickup_date', true );
                                            $pickup_date     = ( ! empty( $pickup_date ) ) ? $pickup_date : get_the_date( '', $order_id );
                                            $date_format     = get_option( 'date_format' );
                                            $pickup_date     = date_i18n( $date_format, strtotime( $pickup_date ) );
                                            
                                            $customer_phone   = get_user_meta( $customer_id, 'billing_phone', true);
                                            $customer_company = get_user_meta( $customer_id, 'billing_company', true);
                                            $billing_tax      = get_user_meta( $customer_id, 'billing_tax', true);
                                            $user_email       = get_user_by('id', $customer_id)->user_email ?? '';
                                            $case_number      = get_post_meta( $order_id, "_case_number", true );
                                            $order_status     = get_post_meta( $order_id, "_wc_order_status_label", true );
                                        ?>
                                        
                                        <!-- Customer Information Column -->
                                        <div class="col-md-6 mb-4 mb-md-0">
                                            <div class="card h-100 border-0 bg-light">
                                                <div class="card-body">
                                                    <h6 class="card-subtitle mb-3 text-muted fw-bold">
                                                        <i class="bi bi-person-circle me-2"></i>
                                                        <?php echo esc_html__( 'Customer Information', 'computer-repair-shop' ); ?>
                                                    </h6>
                                                    <div class="d-flex flex-column gap-2">
                                                        <?php if ( ! empty( $customer_company ) ) : ?>
                                                        <div class="d-flex align-items-start">
                                                            <span class="text-muted me-2" style="min-width: 80px;">
                                                                <i class="bi bi-building me-1"></i>
                                                                <?php echo esc_html__( 'Company', 'computer-repair-shop' ); ?>:
                                                            </span>
                                                            <span class="fw-medium"><?php echo esc_html( $customer_company ); ?></span>
                                                        </div>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ( ! empty( $billing_tax ) ) : ?>
                                                        <div class="d-flex align-items-start">
                                                            <span class="text-muted me-2" style="min-width: 80px;">
                                                                <i class="bi bi-file-text me-1"></i>
                                                                <?php echo esc_html__( 'Tax ID', 'computer-repair-shop' ); ?>:
                                                            </span>
                                                            <span class="fw-medium"><?php echo esc_html( $billing_tax ); ?></span>
                                                        </div>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ( ! empty( $customerLabel ) ) : ?>
                                                        <div class="d-flex align-items-start">
                                                            <span class="text-muted me-2" style="min-width: 80px;">
                                                                <i class="bi bi-person me-1"></i>
                                                                <?php echo esc_html__( 'Name', 'computer-repair-shop' ); ?>:
                                                            </span>
                                                            <span class="fw-medium"><?php echo wp_kses( $customerLabel, $allowedHTML ); ?></span>
                                                        </div>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ( ! empty( $customer_phone ) ) : ?>
                                                        <div class="d-flex align-items-start">
                                                            <span class="text-muted me-2" style="min-width: 80px;">
                                                                <i class="bi bi-telephone me-1"></i>
                                                                <?php echo esc_html__( 'Phone', 'computer-repair-shop' ); ?>:
                                                            </span>
                                                            <span class="fw-medium"><?php echo esc_html( $customer_phone ); ?></span>
                                                        </div>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ( ! empty( $user_email ) ) : ?>
                                                        <div class="d-flex align-items-start">
                                                            <span class="text-muted me-2" style="min-width: 80px;">
                                                                <i class="bi bi-envelope me-1"></i>
                                                                <?php echo esc_html__( 'Email', 'computer-repair-shop' ); ?>:
                                                            </span>
                                                            <span class="fw-medium"><?php echo esc_html( $user_email ); ?></span>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Job Information Column -->
                                        <div class="col-md-6">
                                            <div class="card h-100 border-0 bg-light">
                                                <div class="card-body">
                                                    <h6 class="card-subtitle mb-3 text-muted fw-bold">
                                                        <i class="bi bi-tools me-2"></i>
                                                        <?php echo esc_html__( 'Job Information', 'computer-repair-shop' ); ?>
                                                    </h6>
                                                    <div class="d-flex flex-column gap-2">
                                                        <div class="d-flex align-items-start">
                                                            <span class="text-muted me-2" style="min-width: 100px;">
                                                                <i class="bi bi-hash me-1"></i>
                                                                <?php echo esc_html__( 'Order #', 'computer-repair-shop' ); ?>:
                                                            </span>
                                                            <span class="fw-bold text-primary"><?php echo esc_html( $_job_id ); ?></span>
                                                        </div>
                                                        
                                                        <div class="d-flex align-items-start">
                                                            <span class="text-muted me-2" style="min-width: 100px;">
                                                                <i class="bi bi-folder me-1"></i>
                                                                <?php echo wcrb_get_label( 'casenumber', 'first' ); ?>:
                                                            </span>
                                                            <span class="fw-medium"><?php echo esc_html( $case_number ); ?></span>
                                                        </div>
                                                        
                                                        <div class="d-flex align-items-start">
                                                            <span class="text-muted me-2" style="min-width: 100px;">
                                                                <i class="bi bi-calendar me-1"></i>
                                                                <?php echo esc_html__( 'Created', 'computer-repair-shop' ); ?>:
                                                            </span>
                                                            <span class="fw-medium"><?php echo esc_html( $pickup_date ); ?></span>
                                                        </div>
                                                        
                                                        <div class="d-flex align-items-start">
                                                            <span class="text-muted me-2" style="min-width: 100px;">
                                                                <i class="bi bi-info-circle me-1"></i>
                                                                <?php echo esc_html__( 'Status', 'computer-repair-shop' ); ?>:
                                                            </span>
                                                            <span class="badge bg-secondary"><?php echo esc_html( $order_status ); ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Signature Label Form / URL Display -->
                             <div class="alert alert-success d-block">
                                <i class="bi bi-check-circle me-2"></i>
                                <?php echo esc_html__( 'To generate automated signature request through email/sms please check settings.', 'computer-repair-shop'); ?>
                                <strong><?php echo esc_html($signature_label); ?></strong>
                            </div>
                            <div class="card border shadow-sm">
                                <div class="card-header bg-primary py-3">
                                    <h5 class="card-title mb-0 text-white">
                                        <i class="bi bi-pen me-2"></i>
                                        <?php echo esc_html__( 'Generate Signature Request', 'computer-repair-shop' ); ?>
                                    </h5>
                                </div>
                                <div class="card-body p-4">
                                    
                                    <?php if (empty($signature_label)) : ?>
                                        <!-- Form to enter signature label -->
                                        <form method="GET" action="<?php echo esc_url(remove_query_arg('signature_label')); ?>" class="signature-label-form">
                                            <!-- Explicitly pass required parameters -->
                                            <input type="hidden" name="screen" value="<?php echo esc_attr($_GET['screen'] ?? ''); ?>">
                                            <input type="hidden" name="signature_link_generator" value="yes" />
                                            <input type="hidden" name="job_id" value="<?php echo esc_attr($order_id); ?>">
                                            <input type="hidden" name="case_number" value="<?php echo esc_attr($job_case_number); ?>">
                                            <input type="hidden" name="signature_type" value="<?php echo esc_attr($signature_type); ?>">
                                            
                                            <?php if ( isset( $_GET['page_id'] ) && ! empty( $_GET['page_id'] ) ) { ?>
                                                <input type="hidden" name="page_id" value="<?php echo sanitize_text_field( $_GET['page_id'] ); ?>" />
                                            <?php } ?>

                                            <div class="mb-4">
                                                <label for="signature_label" class="form-label fw-bold">
                                                    <i class="bi bi-card-text me-2"></i>
                                                    <?php echo esc_html__( 'Signature Label', 'computer-repair-shop' ); ?>
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" 
                                                    class="form-control form-control-lg" 
                                                    id="signature_label" 
                                                    name="signature_label" 
                                                    placeholder="<?php echo esc_attr__('e.g., Delivery Signature, Pickup Signature, Authorization', 'computer-repair-shop'); ?>" 
                                                    required>
                                                <div class="form-text">
                                                    <?php echo esc_html__( 'Enter a descriptive label for this signature request (e.g., "Delivery Signature", "Pickup Authorization", "Work Completion")', 'computer-repair-shop' ); ?>
                                                </div>
                                            </div>
                                            
                                            <div class="mt-4 text-end">
                                                <button type="submit" class="btn btn-primary btn-lg" name="generate_url">
                                                    <i class="bi bi-check-circle me-1"></i>
                                                    <?php echo esc_html__( 'Generate Signature URL', 'computer-repair-shop' ); ?>
                                                </button>
                                            </div>
                                        </form>
                                        
                                    <?php else : ?>
                                        <!-- Display signature URL -->
                                        <div class="text-center">
                                            <div class="alert alert-success mb-4">
                                                <i class="bi bi-check-circle-fill me-2"></i>
                                                <strong><?php echo esc_html__('Signature Request Generated!', 'computer-repair-shop'); ?></strong>
                                                <p class="mb-0 mt-2"><?php echo esc_html__('Share this URL with the customer to collect their signature:', 'computer-repair-shop'); ?></p>
                                            </div>
                                            
                                            <div class="mb-4">
                                                <label class="form-label fw-bold">
                                                    <i class="bi bi-link-45deg me-2"></i>
                                                    <?php echo esc_html__('Signature Request URL:', 'computer-repair-shop'); ?>
                                                </label>
                                                <div class="input-group input-group-lg">
                                                    <input type="text" 
                                                           class="form-control" 
                                                           id="signatureUrl" 
                                                           value="<?php echo esc_url($signature_url); ?>" 
                                                           readonly>
                                                    <button class="btn btn-outline-primary" type="button" id="copyUrlBtn">
                                                        <i class="bi bi-clipboard"></i>
                                                    </button>
                                                </div>
                                                <div class="mt-2">
                                                    <a href="<?php echo esc_url($signature_url); ?>" 
                                                       class="btn btn-success me-2" 
                                                       target="_blank">
                                                        <i class="bi bi-box-arrow-up-right me-1"></i>
                                                        <?php echo esc_html__('Open Signature Page', 'computer-repair-shop'); ?>
                                                    </a>
                                                </div>
                                            </div>
                                            
                                            <div class="card bg-light mt-3">
                                                <div class="card-body">
                                                    <h6 class="card-title">
                                                        <i class="bi bi-info-circle me-2"></i>
                                                        <?php echo esc_html__('Request Details:', 'computer-repair-shop'); ?>
                                                    </h6>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <p class="mb-1">
                                                                <strong><?php echo esc_html__('Job ID:', 'computer-repair-shop'); ?></strong> 
                                                                <?php echo esc_html($order_id); ?>
                                                            </p>
                                                            <p class="mb-1">
                                                                <strong><?php echo esc_html__('Case Number:', 'computer-repair-shop'); ?></strong> 
                                                                <?php echo esc_html($job_case_number); ?>
                                                            </p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <p class="mb-1">
                                                                <strong><?php echo esc_html__('Signature Label:', 'computer-repair-shop'); ?></strong> 
                                                                <?php echo esc_html($signature_label); ?>
                                                            </p>
                                                            <p class="mb-1">
                                                                <strong><?php echo esc_html__('Signature Type:', 'computer-repair-shop'); ?></strong> 
                                                                <?php echo esc_html($signature_type); ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                </div>
                            </div>
                            
                            <!-- Footer Note -->
                            <div class="text-center mt-4 pt-3 border-top">
                                <p class="text-muted small mb-0">
                                    <i class="bi bi-shield-check me-1"></i>
                                    <?php echo esc_html__( 'Generated by: ', 'computer-repair-shop' ); ?>
                                    <?php echo esc_html($current_user->display_name); ?> 
                                    (<?php echo esc_html($role); ?>)
                                    | 
                                    <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format')); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php wp_footer(); ?>
    
    <?php if (!empty($signature_label)) : ?>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            // Copy URL to clipboard
            const copyBtn = document.getElementById('copyUrlBtn');
            const urlInput = document.getElementById('signatureUrl');
            
            if (copyBtn && urlInput) {
                copyBtn.addEventListener('click', function() {
                    urlInput.select();
                    urlInput.setSelectionRange(0, 99999); // For mobile devices
                    
                    try {
                        navigator.clipboard.writeText(urlInput.value).then(function() {
                            // Show success message
                            const originalHTML = copyBtn.innerHTML;
                            copyBtn.innerHTML = '<i class="bi bi-check"></i>';
                            copyBtn.classList.remove('btn-outline-primary');
                            copyBtn.classList.add('btn-success');
                            
                            setTimeout(function() {
                                copyBtn.innerHTML = originalHTML;
                                copyBtn.classList.remove('btn-success');
                                copyBtn.classList.add('btn-outline-primary');
                            }, 2000);
                        });
                    } catch (err) {
                        // Fallback for older browsers
                        document.execCommand('copy');
                        
                        const originalHTML = copyBtn.innerHTML;
                        copyBtn.innerHTML = '<i class="bi bi-check"></i>';
                        copyBtn.classList.remove('btn-outline-primary');
                        copyBtn.classList.add('btn-success');
                        
                        setTimeout(function() {
                            copyBtn.innerHTML = originalHTML;
                            copyBtn.classList.remove('btn-success');
                            copyBtn.classList.add('btn-outline-primary');
                        }, 2000);
                    }
                });
            }
            
            // Auto-select URL on focus
            if (urlInput) {
                urlInput.addEventListener('focus', function() {
                    this.select();
                });
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>