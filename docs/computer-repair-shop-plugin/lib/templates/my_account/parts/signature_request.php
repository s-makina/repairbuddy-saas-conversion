<?php
    defined('ABSPATH') || exit;

    $_sitename      = get_bloginfo('name');
    $_thepagetitle  = esc_html__( 'Sign your job', 'computer-repair-shop' ) . ' - ' . $_sitename;

    $allowedHTML  = wc_return_allowed_tags();

    // Get current page URL for redirects
    $current_url = get_the_permalink() ?: home_url();

    $_case_number     = ( isset( $_GET['case_number'] ) && ! empty( $_GET['case_number'] ) ) ? sanitize_text_field( $_GET['case_number'] ) : '';
    $order_id         = ( isset( $_GET['job_id'] ) && ! empty( $_GET['job_id'] ) ) ? sanitize_text_field( $_GET['job_id'] ) : '';
    $verification_code = ( isset( $_GET['verification'] ) && ! empty( $_GET['verification'] ) ) ? sanitize_text_field( $_GET['verification'] ) : '';
    $timestamp         = ( isset( $_GET['timestamp'] ) && ! empty( $_GET['timestamp'] ) ) ? sanitize_text_field( $_GET['timestamp'] ) : '';
    $job_case_number  = get_the_title( $order_id );
    $signature_label  = '';

    if ( isset( $_GET['signature_link_generator'] ) && ! empty( $_GET['signature_link_generator'] ) ) {
        require_once MYACCOUNT_TEMP_DIR . 'parts/signature_generator.php';
        exit();
    }

    if ( ! isset( $_GET['signature_label'] ) || empty( $_GET['signature_label'] ) ) {
        wp_die( esc_html__( 'Something is wrong with your submission!', 'computer-repair-shop' ) );
    }
    if ( $job_case_number != $_case_number ) {
        wp_die( esc_html__( 'Something is wrong with your submission!', 'computer-repair-shop' ) );
    }
    if ( ! isset( $verification_code ) || empty( $verification_code ) ) {
        wp_die( esc_html__( 'Something is wrong with your submission!', 'computer-repair-shop' ) );
    }

    $signature_label = sanitize_text_field( $_GET['signature_label'] );
    $signature_type  = ( isset( $_GET['signature_type'] ) ) ? sanitize_text_field( $_GET['signature_type'] ) : 'normal';

    $jobs_manager = WCRB_JOBS_MANAGER::getInstance();
    $job_data 	  = $jobs_manager->get_job_display_data( $order_id );
    $_job_id  	  = ( ! empty( $job_data['formatted_job_number'] ) ) ? $job_data['formatted_job_number'] : $order_id;
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html( $_thepagetitle ); ?></title>
    
    <?php
        wp_head(); 
    ?>
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
                                <h4 class="fw-bold mb-3"><?php echo esc_html__( 'Complete Your Signature Requirement', 'computer-repair-shop' ); ?></h4>
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
                            
                            <!-- Signature Section -->
                            <div class="card border shadow-sm">
                                <div class="card-header bg-primary py-3">
                                    <h5 class="card-title mb-0 text-white">
                                        <i class="bi bi-pen me-2"></i>
                                        <?php echo esc_html__( 'Signature Required', 'computer-repair-shop' ); ?>
                                    </h5>
                                </div>
                                <div class="card-body p-4">
                                    <p class="text-muted mb-4">
                                        <i class="bi bi-info-circle me-2"></i>
                                        <?php echo esc_html__( 'Please provide your signature in the area below to acknowledge and approve the job details.', 'computer-repair-shop' ); ?>
                                    </p>
                                    <!-- Start signature pad -->
                                    <?php
                                        $cansign = $mssg = '';
                                        $jobstatus = get_post_meta( $order_id, "_wc_order_status", true );

                                        if ( $signature_type == 'pickup' ) {
                                            $pickup_status = get_option( 'wcrb_pickup_signature_job_status' );

                                            if ( $pickup_status == $jobstatus ) {
                                                $cansign = 'YES';
                                            } else {
                                                $cansign = 'NO';
                                                $mssg    = esc_html__( 'Job status is different than allowed for signature.', 'computer-repair-shop' );
                                            }
                                        }

                                        if ( $signature_type == 'delivery' ) {
                                            $delivery_status = get_option( 'wcrb_delivery_signature_job_status' );

                                            if ( $delivery_status == $jobstatus ) {
                                                $cansign = 'YES';
                                            } else {
                                                $cansign = 'NO';
                                                $mssg    = esc_html__( 'Job status is different than allowed for signature.', 'computer-repair-shop' );
                                            }
                                        }

                                        if ( ! wc_rs_license_state() ) {
                                            $cansign = 'NO';
                                            $mssg    = esc_html__( 'Pro feature! Plugin activation required.', 'computer-repair-shop' );
                                        }

                                        if ( $cansign != 'NO' ) :
                                    ?>
                                    <div id="signaturepad" class="signaturepad bg-light border rounded p-3" style="min-height: 200px;">
                                        <!-- Signature pad will be rendered here -->
                                        <div class="text-center text-muted py-5">
                                            <i class="bi bi-pencil-square display-4 mb-3"></i>
                                            <p class="mb-0"><?php echo esc_html__( 'Signature area - draw your signature here', 'computer-repair-shop' ); ?></p>
                                        </div>
                                    </div><!-- end signature pad /-->
                                    
                                    <div class="mt-4 text-end">
                                        <button type="button" class="btn btn-outline-secondary me-2">
                                            <i class="bi bi-x-circle me-1"></i>
                                            <?php echo esc_html__( 'Clear Signature', 'computer-repair-shop' ); ?>
                                        </button>
                                        <button type="button" class="btn btn-primary">
                                            <i class="bi bi-check-circle me-1"></i>
                                            <?php echo esc_html__( 'Submit Signature', 'computer-repair-shop' ); ?>
                                        </button>
                                    </div>
                                    <?php else: ?>
                                        <div class="alert alert-warning"><?php echo esc_html( $mssg ); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Footer Note -->
                            <div class="text-center mt-4 pt-3 border-top">
                                <p class="text-muted small mb-0">
                                    <i class="bi bi-shield-check me-1"></i>
                                    <?php echo esc_html__( 'Your information is secure and will only be used for this job approval.', 'computer-repair-shop' ); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php 
        wp_enqueue_script( 'signature-pad',  MYACCOUNT_FOLDER_URL . '/js/signature_pad.umd.min.js', ['jquery'], '4.0.0', true );
        wp_footer(); 
    ?>
<script type="text/javascript">
(function() {
    // Store PHP variables
    const signatureParams = {
        orderId: '<?php echo esc_html($order_id); ?>',
        caseNumber: '<?php echo esc_html($job_case_number); ?>',
        signatureLabel: '<?php echo esc_html($signature_label); ?>',
        signatureType:'<?php echo esc_html( $signature_type ); ?>',
        verificationCode:'<?php echo esc_html( $verification_code ); ?>',
        timestamp:'<?php echo esc_html( $timestamp ); ?>',
        ajaxUrl: '<?php echo admin_url("admin-ajax.php"); ?>',
        nonce: '<?php echo wp_create_nonce("signature_upload_nonce"); ?>'
    };

    let signaturePad = null;

    // Initialize when page loads
    document.addEventListener('DOMContentLoaded', function() {
        initSignaturePad();
        setupEventListeners();
    });

    function initSignaturePad() {
        const canvasContainer = document.getElementById('signaturepad');
        if (!canvasContainer) return;

        // Create canvas
        canvasContainer.innerHTML = '';
        const canvas = document.createElement('canvas');
        canvas.id = 'signatureCanvas';
        canvas.style.width = '100%';
        canvas.style.height = '200px';
        canvas.style.borderRadius = '4px';
        canvas.style.cursor = 'crosshair';
        
        canvas.width = canvasContainer.offsetWidth;
        canvas.height = 200;
        canvasContainer.appendChild(canvas);

        // Initialize
        signaturePad = new SignaturePad(canvas, {
            backgroundColor: 'rgb(255, 255, 255)',
            penColor: 'rgb(0, 0, 0)',
            minWidth: 1,
            maxWidth: 3,
            throttle: 16,
        });

        // Handle resize
        window.addEventListener('resize', function() {
            canvas.width = canvasContainer.offsetWidth;
            const data = signaturePad.toData();
            if (data) signaturePad.fromData(data);
        });
    }

    function setupEventListeners() {
        // Clear button
        const clearButton = document.querySelector('.btn-outline-secondary');
        if (clearButton) {
            clearButton.addEventListener('click', function() {
                if (signaturePad) signaturePad.clear();
            });
        }

        // Submit button
        const submitButton = document.querySelector('.btn-primary:not(.dropdown-toggle)');
        if (submitButton) {
            submitButton.addEventListener('click', uploadSignature);
        }

        // Touch device support
        const canvas = document.getElementById('signatureCanvas');
        if (canvas) {
            let isDrawing = false;
            canvas.addEventListener('touchstart', function(e) {
                isDrawing = true;
                if (e.target === canvas) e.preventDefault();
            }, { passive: false });
            
            canvas.addEventListener('touchmove', function(e) {
                if (isDrawing && e.target === canvas) e.preventDefault();
            }, { passive: false });
            
            canvas.addEventListener('touchend', function() {
                isDrawing = false;
            });
        }
    }

    function uploadSignature() {
        if (!signaturePad || signaturePad.isEmpty()) {
            alert('<?php echo esc_js(__("Please provide your signature first.", "computer-repair-shop")); ?>');
            return;
        }

        const submitButton = document.querySelector('.btn-primary:not(.dropdown-toggle)');
        const originalText = submitButton.innerHTML;
        
        // Show loading
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> <?php echo esc_js(__("Saving...", "computer-repair-shop")); ?>';
        submitButton.disabled = true;

        // Get signature as base64
        const signatureData = signaturePad.toDataURL('image/png');
        
        // Convert to blob for file upload
        const blob = dataURLtoBlob(signatureData);
        const formData = new FormData();
        const fileName = `signature-${signatureParams.orderId}-${Date.now()}.png`;
        
        // Add file
        formData.append('signature_file', blob, fileName);
        
        // Add all parameters
        formData.append('action', 'wc_upload_and_save_signature');
        formData.append('security', signatureParams.nonce);
        formData.append('order_id', signatureParams.orderId);
        formData.append('job_case_number', signatureParams.caseNumber);
        formData.append('signature_label', signatureParams.signatureLabel);
        formData.append('signature_type', signatureParams.signatureType);
        formData.append('verification', signatureParams.verificationCode);
        formData.append('timestamp', signatureParams.timestamp);
        
        // Single AJAX call
        fetch(signatureParams.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            submitButton.innerHTML = originalText;
            submitButton.disabled = false;

            if (data.success) {
                // Success
                alert('<?php echo esc_js(__("Signature submitted successfully!", "computer-repair-shop")); ?>');
                
                // Optional: Show success message on page
                showSuccessMessage(data.message);
                
                // Redirect if needed
                if (data.data && data.data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.data.redirect;
                    }, 2000);
                }
            } else {
                // Error
                alert('<?php echo esc_js(__("Error: ", "computer-repair-shop")); ?>' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            submitButton.innerHTML = originalText;
            submitButton.disabled = false;
            alert('<?php echo esc_js(__("Network error. Please try again.", "computer-repair-shop")); ?>');
            console.error('Upload error:', error);
        });
    }

    function dataURLtoBlob(dataurl) {
        const arr = dataurl.split(',');
        const mime = arr[0].match(/:(.*?);/)[1];
        const bstr = atob(arr[1]);
        let n = bstr.length;
        const u8arr = new Uint8Array(n);
        while(n--) {
            u8arr[n] = bstr.charCodeAt(n);
        }
        return new Blob([u8arr], {type: mime});
    }

    function showSuccessMessage(message) {
        // Create success message element
        const successDiv = document.createElement('div');
        successDiv.className = 'alert alert-success alert-dismissible fade show mt-3';
        successDiv.innerHTML = `
            <i class="bi bi-check-circle me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Insert after signature section
        const signatureSection = document.querySelector('.card.border.shadow-sm');
        if (signatureSection) {
            signatureSection.parentNode.insertBefore(successDiv, signatureSection.nextSibling);
        }
    }
})();
</script>
</body>
</html>