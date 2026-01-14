<?php
    /**
     * Template Name: My Account Dashboard
     */
    defined( 'ABSPATH' ) || exit;

    // Optional: get current user and role
    $current_user = wp_get_current_user();
    $role         = $current_user->roles[0] ?? 'guest';
    $allowedHTML  = wc_return_allowed_tags();
    $date_format  = get_option( 'date_format' );
    $_mainpage    = get_queried_object_id();

    $dasboard     = WCRB_MYACCOUNT_DASHBOARD::getInstance();

    $page_title = ucwords( $dasboard->get_page_title() );

    if ( isset( $_GET['screen'] ) && $_GET['screen'] == 'signature_request' ) {
        require_once MYACCOUNT_TEMP_DIR . 'parts/signature_request.php';
        exit;
    }

    if ( ! is_user_logged_in() ) {
        require_once MYACCOUNT_TEMP_DIR . 'parts/myaccount_signin.php';
        exit;
    }

    //Include header
    require_once MYACCOUNT_TEMP_DIR . 'parts/myaccount_head.php';

    //Include Sidebar
    require_once MYACCOUNT_TEMP_DIR . 'parts/myaccount_sidebar.php';

    //Include topbar
    require_once MYACCOUNT_TEMP_DIR . 'parts/myaccount_topbar.php';
    
    //Include content part
    $_current_page = $dasboard->get_current_page();

    $template_map = [
        'dashboard'          => 'myaccount_content_dashboard.php',
        'jobs'               => 'myaccount_content_jobs.php',
        'jobs_card'          => 'myaccount_content_jobs.php',
        'estimates'          => 'myaccount_content_estimates.php',
        'estimates_card'     => 'myaccount_content_estimates.php',
        'customer-devices'   => 'myaccount_customer_devices.php',
        'timelog'            => 'myaccount_time_log.php',
        'calendar'           => 'myaccount_content_calendar.php',
        'reviews'            => 'myaccount_content_reviews.php',
        'expenses'           => 'myaccount_expenses.php',
        'expense_categories' => 'myaccount_expenses_categories.php',
        'book-my-device'     => 'myaccount_content_book_my_device.php',
        'profile'            => 'myaccount_content_profile.php',
        'settings'           => 'myaccount_content_settings.php',
        'support'            => 'myaccount_content_support.php',
        'edit-job'           => 'myaccount_content_job_management.php',
        'print-screen'       => 'myaccount_print_screen.php'
    ];

    $template_file = $template_map[$_current_page] ?? 'myaccount_content_dashboard.php';
    require_once MYACCOUNT_TEMP_DIR . 'parts/' . $template_file;

    //Include footer
    require_once MYACCOUNT_TEMP_DIR . 'parts/myaccount_footer.php';