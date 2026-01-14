<?php
/**
 * Plugin Name: WCRB Blank Dashboard
 */

defined( 'ABSPATH' ) || exit;

define( 'MYACCOUNT_TEMP_DIR', WC_COMPUTER_REPAIR_SHOP_DIR . 'lib' . DS . 'templates' . DS . 'my_account' . DS );
define( 'MYACCOUNT_FOLDER_URL', WC_COMPUTER_REPAIR_DIR_URL . '/lib/templates/my_account' );

class WCRB_MYACCOUNT_DASHBOARD {
    private static $instance = null;

    public $_device_label_plural    = '';
	public $_device_label           = '';
	public $_imei_label             = '';
	public $_pin_code_label         = '';
    public $_delivery_date_label    = '';
    public $_pickup_date_label      = '';
    public $_nextservice_date_label = '';

    public $_myaccount_page_id      = 0;

    public static function getInstance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->setup_labels();

        add_shortcode( 'wc_cr_my_account', [ $this, 'shortcode_handler' ] );

        //save theme via ajax
        add_action( 'wp_ajax_wcrb_save_theme', array( $this, 'ajax_save_theme' ) );
        add_action( 'wp_ajax_nopriv_wcrb_save_theme', array( $this, 'ajax_save_theme' ) );

        // Add this to your constructor
        add_action( 'wp_ajax_wcrb_get_theme', array( $this, 'ajax_get_theme' ) );
        add_action( 'wp_ajax_nopriv_wcrb_get_theme', array( $this, 'ajax_get_theme' ) );

        // Register template for the auto-created page
        add_filter( 'theme_page_templates', [ $this, 'register_template' ] );
        add_filter( 'template_include', [ $this, 'load_combined_template' ] );

        add_action( 'wp_enqueue_scripts', [ $this, 'clean_and_enqueue_dashboard_assets' ], 99999 );

        // Add the new AJAX action for the form
        add_action( 'wp_ajax_wcrb_register_user', [ $this, 'wcrb_register_account' ] );
        add_action( 'wp_ajax_nopriv_wcrb_register_user', [ $this, 'wcrb_register_account' ] );

        //Update profile page
        add_action( 'wp_ajax_wcrb_update_profile', [ $this, 'wcrb_update_profile' ] );
        add_action( 'wp_ajax_wcrb_update_password', [ $this, 'wcrb_update_password' ] );
        add_action( 'wp_ajax_wcrb_update_profile_photo', [ $this, 'wcrb_update_profile_photo' ] );
        add_filter( 'get_avatar', [ $this, 'wcrb_custom_avatar' ], 10, 5 );

        add_action( 'wp_ajax_wcrb_get_chart_data', [$this, 'ajax_wcrb_get_chart_data'] );
        add_action( 'wp_ajax_nopriv_wcrb_get_chart_data', [$this, 'ajax_wcrb_get_chart_data'] );
    }

    public function load_combined_template( $template ) {
        if ( is_page_template( 'myaccount_dashboard.php' ) ) {
            $custom_path = MYACCOUNT_TEMP_DIR . 'myaccount_dashboard.php';
            return file_exists( $custom_path ) ? $custom_path : $template;
        }
        return $this->load_myaccount_template( $template );
    }

    public function register_template( $templates ) {
        $templates['myaccount_dashboard.php'] = __( 'RepairBuddy Dashboard', 'computer-repair-shop' );
        return $templates;
    }

    public function load_template( $template ) {
        if ( is_page_template( 'myaccount_dashboard.php' ) ) {
            return MYACCOUNT_TEMP_DIR . 'myaccount_dashboard.php';
        }
        return $template;
    }

    public function load_myaccount_template( $template ) {
        if ( ! is_singular( 'page' ) ) {
            return $template;
        }
        global $post;
        
        // Check if template is already assigned
        $current_template = get_page_template_slug( $post->ID );
        if ( $current_template === 'myaccount_dashboard.php' ) {
            $custom_path = MYACCOUNT_TEMP_DIR . 'myaccount_dashboard.php';
            return file_exists( $custom_path ) ? $custom_path : $template;
        }

        // Only check shortcode if template isn't already assigned
        if ( has_shortcode( $post->post_content, 'wc_cr_my_account' ) ) {
            $current_template = get_post_meta( $post->ID, '_wp_page_template', true );
            if ( $current_template !== 'myaccount_dashboard.php' ) {
                update_post_meta( $post->ID, '_wp_page_template', 'myaccount_dashboard.php' );
            }
            $custom_path = MYACCOUNT_TEMP_DIR . 'myaccount_dashboard.php';
            return file_exists( $custom_path ) ? $custom_path : $template;
        }

        return $template;
    }

    public function clean_and_enqueue_dashboard_assets() {
        if ( ! is_singular( 'page' ) || ( ! has_shortcode( get_post()->post_content, 'wc_cr_my_account' )  ) ) {
            return;
        }

        // Prevent double enqueuing
        static $enqueued = false;
        if ($enqueued) {
            return;
        }
        $enqueued = true;

        add_filter('show_admin_bar', '__return_false');

        // Only dequeue, don't deregister (less destructive)
        $allowed_scripts = ['jquery', 'jquery-core', 'jquery-migrate', 'wp-i18n', 'wp-hooks', 'wp-util'];
        $allowed_styles = ['dashicons'];

        foreach ( wp_scripts()->queue as $handle ) {
            if ( ! in_array( $handle, $allowed_scripts ) ) {
                wp_dequeue_script( $handle );
            }
        }

        foreach ( wp_styles()->queue as $handle ) {
            if ( ! in_array( $handle, $allowed_styles ) ) {
                wp_dequeue_style( $handle );
            }
        }

        // Enqueue your dashboard dependencies
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'bootstrap', MYACCOUNT_FOLDER_URL . '/js/bootstrap.bundle.min.js', ['jquery'], '5.3.2', true );
        wp_enqueue_script( 'chart-js', MYACCOUNT_FOLDER_URL . '/js/chart.js', [], '4.5.1', true );
        wp_enqueue_script( 'select2', WC_COMPUTER_REPAIR_DIR_URL . '/assets/admin/js/select2.min.js', ['jquery'], '4.0.13', true );

        // Get AJAX data
        $ajax_data = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wcrb_dashboard_nonce')
        );

        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        $is_customer = in_array('customer', $user_roles);

        // Get chart data
        $initial_period = $is_customer ? 'yearly' : 'weekly'; // 'yearly' for last 90 days
        $initial_data = $this->get_real_chart_data($initial_period);

        // ====== IMPORTANT: Load wcrb_ajax.js FIRST ======
        wp_enqueue_script( 'wcrb-ajax-handler', MYACCOUNT_FOLDER_URL . '/js/wcrb_ajax.js', ['jquery', 'bootstrap'], WC_CR_SHOP_VERSION, true );
        wp_localize_script( 'wcrb-ajax-handler', 'wcrb_ajax', $ajax_data );

        // ====== Also register it as 'wcrb-ajax' for other scripts that depend on it ======
        wp_register_script( 'wcrb-ajax', MYACCOUNT_FOLDER_URL . '/js/wcrb_ajax.js', ['jquery', 'bootstrap'], WC_CR_SHOP_VERSION, true );
        wp_localize_script( 'wcrb-ajax', 'wcrb_ajax', $ajax_data );

        // ====== Load chart script SECOND ======
        wp_enqueue_script( 'wcrb-dashboard-charts', MYACCOUNT_FOLDER_URL . '/js/wcrbscript.js', ['jquery', 'bootstrap', 'chart-js', 'wcrb-ajax-handler'], WC_CR_SHOP_VERSION, true );
        
        // Localize chart data
        wp_localize_script( 'wcrb-dashboard-charts', 'wcrbChartData', $initial_data );
        
        // Also localize AJAX data for charts script (using camelCase)
        wp_localize_script( 'wcrb-dashboard-charts', 'wcrbAjax', $ajax_data );

        //Register scripts for later usages
        if ( isset( $_GET['screen'] ) && sanitize_text_field( $_GET['screen'] ) === 'timelog' ) {
            wp_enqueue_script( 'wcrb_timelog_script',  MYACCOUNT_FOLDER_URL . '/js/wcrb_timelog.js', ['jquery', 'bootstrap', 'chart-js', 'wcrb-ajax-handler'], WC_CR_SHOP_VERSION, true );

            $WCRB_TIME_MANAGEMENT = WCRB_TIME_MANAGEMENT::getInstance();
        
            wp_localize_script( 'wcrb_timelog_script', 'wcrb_timelog_i18n', array(
                'timer_started'         => __('Timer started!', 'computer-repair-shop'),
                'timer_paused'          => __('Timer paused', 'computer-repair-shop'),
                'timer_running'         => __('Running', 'computer-repair-shop'),
                'timer_stopped'         => __('Stopped', 'computer-repair-shop'),
                'time_entry_saved'      => __('Time entry saved! (%d minutes)', 'computer-repair-shop'),
                'timelog_page_url'      => esc_url( get_permalink( $this->_myaccount_page_id ) ),
                'work_description_required' => __('Please enter work description before starting timer.', 'computer-repair-shop'),
                'activity_type_required'    => __('Please select activity type before starting timer.', 'computer-repair-shop'),
                'ajax_url'                 => admin_url('admin-ajax.php'),
                'wcrb_timelog_nonce_field' => wp_create_nonce( 'wcrb_timelog_nonce_action' ),
                'current_user_id'          => get_current_user_id(),
                'time_entry_saved'          => esc_html__( 'Record updated!', 'computer-repair-shop' ),
                'weekly_chart_data'         => $WCRB_TIME_MANAGEMENT->get_weekly_chart_data(get_current_user_id(), 'week'),
                'hours_worked'              => __('Hours Worked', 'computer-repair-shop'),
                'weekly_hours_chart'        => __('Weekly Hours Distribution', 'computer-repair-shop'),
                'hours'                     => __('Hours', 'computer-repair-shop'),
                'end_time_after_start'  => esc_html__( 'End time must be after start time', 'computer-repair-shop' ),
            ));
        }

        // Conditionally enqueue profile edit script
        if ( isset( $_GET['screen'] ) && sanitize_text_field( $_GET['screen'] ) === 'profile' ) {
            wp_enqueue_script( 'wcrb-profile-edit', MYACCOUNT_FOLDER_URL . '/js/profile-edit.js', ['jquery', 'wcrb-ajax'], WC_CR_SHOP_VERSION, true );
        }

        if ( isset( $_GET['screen'] ) && sanitize_text_field( $_GET['screen'] ) === 'calendar' ) {
            // Get next service date option
            $wcrb_next_service_date = get_option('wcrb_next_service_date');
            $_enable_next_service_d = ($wcrb_next_service_date == 'on') ? 'yes' : 'no';

            wp_enqueue_script( 'fullcalendar-core', WC_COMPUTER_REPAIR_DIR_URL . '/assets/admin/js/fullcalendar/index.global.min.js', ['jquery'], '6.1.19', true );
            wp_enqueue_script( 'fullcalendar-locales', WC_COMPUTER_REPAIR_DIR_URL . '/assets/admin/js/fullcalendar/locales-all.global.min.js', array('fullcalendar-core'), '6.1.19', true );
            
            // Enqueue your custom calendar script
            wp_enqueue_script( 'frontend-calendar', MYACCOUNT_FOLDER_URL . '/js/frontend-calendar.js', ['jquery', 'bootstrap', 'wcrb-ajax-handler'], WC_CR_SHOP_VERSION, true );
            
            wp_localize_script( 'frontend-calendar', 'calendar_frontend_vars', array(
                'ajax_url'               => admin_url('admin-ajax.php'),
                'nonce'                  => wp_create_nonce('wcrb_frontend_calendar_nonce'),
                'locale'                 => str_replace('_', '-', get_locale()),
                'is_user_logged_in'      => is_user_logged_in(),
                'user_id'                => get_current_user_id(),
                'user_roles'             => wp_get_current_user()->roles,
                'enable_next_service'    => $_enable_next_service_d,
                'pickup_date_label'      => wcrb_get_label('pickup_date', 'first'),
                'delivery_date_label'    => wcrb_get_label('delivery_date', 'first'),
                'nextservice_date_label' => wcrb_get_label('nextservice_date', 'first'),
                'day'                    => esc_html__( 'Day', 'computer-repair-shop' ),
                'month'                  => esc_html__( 'Month', 'computer-repair-shop' ),
                'week'                   => esc_html__( 'Week', 'computer-repair-shop' ),
                'list'                   => esc_html__( 'List', 'computer-repair-shop' ),
                'today'                  => esc_html__( 'Today', 'computer-repair-shop' )
            ));
        }
        
        wp_enqueue_style( 'bootstrap',             MYACCOUNT_FOLDER_URL . '/css/bootstrap.min.css', [], '5.3.2', 'all' );
        wp_enqueue_style( 'bootstrap-icons',       MYACCOUNT_FOLDER_URL . '/css/bootstrap-icons.min.css', [], '1.13.1', 'all' );
        wp_enqueue_style( 'wcrb-dark-mode',        MYACCOUNT_FOLDER_URL . '/css/dark-mode.css', [], WC_CR_SHOP_VERSION, 'all' );
        wp_enqueue_style( 'wcrb-myaccount-styles', MYACCOUNT_FOLDER_URL . '/css/style.css', [], WC_CR_SHOP_VERSION, 'all' );
        wp_enqueue_style( 'select2',               WC_COMPUTER_REPAIR_DIR_URL . '/assets/admin/css/select2.min.css', [], '4.0.13', 'all' );
        wp_register_style( 'checkstatus-style',     MYACCOUNT_FOLDER_URL . '/css/statuscheck.css', [], WC_CR_SHOP_VERSION, 'all' );

        // Conditionally enqueue expenses scripts
        if ( isset( $_GET['screen'] ) && sanitize_text_field( $_GET['screen'] ) === 'expenses' ) {
            wp_enqueue_script( 'wcrb-expenses-script', MYACCOUNT_FOLDER_URL . '/js/expenses.js', ['jquery', 'bootstrap', 'wcrb-ajax'], WC_CR_SHOP_VERSION, true );
            
            // Localize script with necessary data
            wp_localize_script( 'wcrb-expenses-script', 'expenses_data', array(
                'ajax_url'        => admin_url('admin-ajax.php'),
                'nonce'           => wp_create_nonce('wcrb_expense_nonce'),
                'currency_symbol' => return_wc_rb_currency_symbol(),
                'date_format'     => get_option('date_format'),
                'i18n' => array(
                    'confirm_delete' => __('Are you sure you want to delete this expense?', 'computer-repair-shop'),
                    'loading'        => __('Loading...', 'computer-repair-shop'),
                    'error'          => __('An error occurred', 'computer-repair-shop')
                )
            ));
        }
        
        // Conditionally enqueue expense categories script
        if ( isset( $_GET['screen'] ) && sanitize_text_field( $_GET['screen'] ) === 'expense_categories' ) {
            wp_enqueue_script( 'wcrb-expense-categories-script', MYACCOUNT_FOLDER_URL . '/js/expense-categories.js', ['jquery', 'bootstrap', 'wcrb-ajax'], WC_CR_SHOP_VERSION, true );
            
            wp_localize_script( 'wcrb-expense-categories-script', 'categories_data', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wcrb_expense_category_nonce'),
                'i18n' => array(
                    'confirm_delete' => __('Are you sure you want to delete this category?', 'computer-repair-shop'),
                    'loading' => __('Loading...', 'computer-repair-shop')
                )
            ));
        }
    }

    function ajax_wcrb_get_chart_data() {
        // Check nonce for security
        if (!check_ajax_referer('wcrb_dashboard_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Invalid security token'));
            return;
        }
        
        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : 'weekly';
        
        // Validate period
        $valid_periods = array('weekly', 'monthly', 'yearly');
        if (!in_array($period, $valid_periods)) {
            $period = 'weekly';
        }
        
        $chart_data = $this->get_real_chart_data($period);
        
        wp_send_json_success($chart_data);
    }

    public function get_real_chart_data($period = 'weekly') {
        // Get current user info
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        $user_roles = $current_user->roles;
        
        $is_administrator = in_array('administrator', $user_roles);
        $is_store_manager = in_array('store_manager', $user_roles);
        $is_technician = in_array('technician', $user_roles);
        $is_customer = in_array('customer', $user_roles);
        
        // Get job status settings
        $job_status_delivered = (!empty(get_option('wcrb_job_status_delivered'))) ? get_option('wcrb_job_status_delivered') : 'delivered';
        $job_status_cancelled = (!empty(get_option('wcrb_job_status_cancelled'))) ? get_option('wcrb_job_status_cancelled') : 'cancelled';
        
        if ($is_customer) {
            return $this->get_customer_chart_data($period);
        }

        $data = array();
        
        // Build user condition for WP_Query
        $user_meta_query = array();
        if ($is_administrator || $is_store_manager) {
            // See all jobs - no condition
        } elseif ($is_technician) {
            $user_meta_query[] = array(
                'relation' => 'OR',
                array(
                    'key' => '_technician',
                    'value' => $user_id,
                    'compare' => '=',
                    'type' => 'NUMERIC'
                ),
                array(
                    'key' => '_technician',
                    'value' => $user_id,
                    'compare' => 'LIKE'
                )
            );
        }
        
        // 1. REVENUE CHART DATA
        if ($period === 'weekly') {
            // Weekly data (last 7 days)
            $labels = array();
            $revenue = array();
            $jobs = array();
            
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $day_label = date_i18n('D', strtotime($date));
                $labels[] = $day_label;
                
                $day_revenue = 0;
                $day_jobs = 0;
                
                // Query jobs for this day
                $args = array(
                    'post_type' => 'rep_jobs',
                    'posts_per_page' => -1,
                    'post_status' => array('publish', 'pending', 'draft', 'private', 'future'),
                    'meta_query' => array(
                        array(
                            'key' => '_pickup_date',
                            'value' => $date,
                            'compare' => 'LIKE'
                        )
                    )
                );
                
                // Add user condition if needed
                if (!empty($user_meta_query)) {
                    $args['meta_query'][] = $user_meta_query;
                }
                
                $day_query = new WP_Query($args);
                
                if ($day_query->have_posts()) {
                    while ($day_query->have_posts()) {
                        $day_query->the_post();
                        $job_id = get_the_ID();
                        
                        // Get job status
                        $job_status = get_post_meta($job_id, '_wc_order_status', true);
                        
                        // Skip cancelled jobs for revenue
                        if ($job_status !== $job_status_cancelled) {
                            // Get total using wc_order_grand_total()
                            $job_total = wc_order_grand_total($job_id, 'grand_total');
                            $day_revenue += floatval($job_total);
                        }
                        
                        // Count completed jobs (delivered status only)
                        if ($job_status === $job_status_delivered) {
                            $day_jobs++;
                        }
                    }
                    wp_reset_postdata();
                }
                
                $revenue[] = $day_revenue;
                $jobs[] = $day_jobs;
            }
            
            $data['revenue'] = array(
                'labels' => $labels,
                'revenue' => $revenue,
                'jobs' => $jobs
            );
            
        } elseif ($period === 'monthly') {
            // Monthly data (last 12 months)
            $labels = array();
            $revenue = array();
            $jobs = array();
            
            for ($i = 11; $i >= 0; $i--) {
                $month = date('n', strtotime("-$i months"));
                $year = date('Y', strtotime("-$i months"));
                $month_label = date_i18n('M', strtotime("$year-$month-01"));
                $labels[] = $month_label;
                
                $month_revenue = 0;
                $month_jobs = 0;
                
                // Get first and last day of month
                $first_day = date('Y-m-01', strtotime("$year-$month-01"));
                $last_day = date('Y-m-t', strtotime("$year-$month-01"));
                
                // Query jobs for this month
                $args = array(
                    'post_type' => 'rep_jobs',
                    'posts_per_page' => -1,
                    'post_status' => array('publish', 'pending', 'draft', 'private', 'future'),
                    'meta_query' => array(
                        array(
                            'key' => '_pickup_date',
                            'value' => array($first_day, $last_day),
                            'compare' => 'BETWEEN',
                            'type' => 'DATE'
                        )
                    )
                );
                
                // Add user condition if needed
                if (!empty($user_meta_query)) {
                    $args['meta_query'][] = $user_meta_query;
                }
                
                $month_query = new WP_Query($args);
                
                if ($month_query->have_posts()) {
                    while ($month_query->have_posts()) {
                        $month_query->the_post();
                        $job_id = get_the_ID();
                        
                        // Get job status
                        $job_status = get_post_meta($job_id, '_wc_order_status', true);
                        
                        // Skip cancelled jobs for revenue
                        if ($job_status !== $job_status_cancelled) {
                            // Get total using wc_order_grand_total()
                            $job_total = wc_order_grand_total($job_id, 'grand_total');
                            $month_revenue += floatval($job_total);
                        }
                        
                        // Count completed jobs (delivered status only)
                        if ($job_status === $job_status_delivered) {
                            $month_jobs++;
                        }
                    }
                    wp_reset_postdata();
                }
                
                $revenue[] = $month_revenue;
                $jobs[] = $month_jobs;
            }
            
            $data['revenue'] = array(
                'labels' => $labels,
                'revenue' => $revenue,
                'jobs' => $jobs
            );
            
        } elseif ($period === 'yearly') {
            // Yearly data (last 5 years)
            $labels = array();
            $revenue = array();
            $jobs = array();
            
            $current_year = date('Y');
            
            for ($i = 4; $i >= 0; $i--) {
                $year = $current_year - $i;
                $labels[] = $year;
                
                $year_revenue = 0;
                $year_jobs = 0;
                
                // Query jobs for this year
                $args = array(
                    'post_type' => 'rep_jobs',
                    'posts_per_page' => -1,
                    'post_status' => array('publish', 'pending', 'draft', 'private', 'future'),
                    'meta_query' => array(
                        array(
                            'key' => '_pickup_date',
                            'value' => array($year . '-01-01', $year . '-12-31'),
                            'compare' => 'BETWEEN',
                            'type' => 'DATE'
                        )
                    )
                );
                
                // Add user condition if needed
                if (!empty($user_meta_query)) {
                    $args['meta_query'][] = $user_meta_query;
                }
                
                $year_query = new WP_Query($args);
                
                if ($year_query->have_posts()) {
                    while ($year_query->have_posts()) {
                        $year_query->the_post();
                        $job_id = get_the_ID();
                        
                        // Get job status
                        $job_status = get_post_meta($job_id, '_wc_order_status', true);
                        
                        // Skip cancelled jobs for revenue
                        if ($job_status !== $job_status_cancelled) {
                            // Get total using wc_order_grand_total()
                            $job_total = wc_order_grand_total($job_id, 'grand_total');
                            $year_revenue += floatval($job_total);
                        }
                        
                        // Count completed jobs (delivered status only)
                        if ($job_status === $job_status_delivered) {
                            $year_jobs++;
                        }
                    }
                    wp_reset_postdata();
                }
                
                $revenue[] = $year_revenue;
                $jobs[] = $year_jobs;
            }
            
            $data['revenue'] = array(
                'labels' => $labels,
                'revenue' => $revenue,
                'jobs' => $jobs
            );
        }
        
        // 2. JOB STATUS DISTRIBUTION DATA
        $status_data = array();
        
        // Query all jobs for status distribution
        $status_args = array(
            'post_type' => 'rep_jobs',
            'posts_per_page' => -1,
            'post_status' => array('publish', 'pending', 'draft', 'private', 'future')
        );
        
        // Add user condition if needed
        if (!empty($user_meta_query)) {
            $status_args['meta_query'] = $user_meta_query;
        }
        
        $status_query = new WP_Query($status_args);
        
        $completed_count = 0;
        $active_count = 0;
        $cancelled_count = 0;
        
        if ($status_query->have_posts()) {
            while ($status_query->have_posts()) {
                $status_query->the_post();
                $job_id = get_the_ID();
                $job_status = get_post_meta($job_id, '_wc_order_status', true);
                
                if ($job_status === $job_status_delivered) {
                    $completed_count++;
                } elseif ($job_status === $job_status_cancelled) {
                    $cancelled_count++;
                } else {
                    // All other statuses are considered active
                    $active_count++;
                }
            }
            wp_reset_postdata();
        }
        
        // Pending estimates (for admin/manager/technician)
        if ($is_administrator || $is_store_manager || $is_technician) {
            $estimate_args = array(
                'post_type' => 'rep_estimates',
                'posts_per_page' => -1,
                'post_status' => array('publish', 'pending', 'draft', 'private', 'future'),
                'meta_query' => array(
                    array(
                        'relation' => 'OR',
                        array(
                            'key' => '_wc_estimate_status',
                            'compare' => 'NOT EXISTS'
                        ),
                        array(
                            'key' => '_wc_estimate_status',
                            'value' => array('approved', 'rejected'),
                            'compare' => 'NOT IN'
                        )
                    )
                )
            );
            
            // Add technician condition if needed
            if ($is_technician && !empty($user_meta_query)) {
                $estimate_args['meta_query'][] = $user_meta_query[0]; // Use same technician condition
            }
            
            $estimate_query = new WP_Query($estimate_args);
            $pending_estimate_count = $estimate_query->found_posts;
        } else {
            $pending_estimate_count = 0;
        }
        
        $status_data = array(
            $completed_count, 
            $active_count, 
            $pending_estimate_count,
            $cancelled_count
        );
        
        $data['jobStatus'] = $status_data;
        
        // 3. DEVICE TYPE DATA
        $device_posts = get_posts(array(
            'post_type' => 'rep_devices',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        $device_counts = array();
        $other_count = 0;
        
        foreach ($device_posts as $device) {
            $device_id = $device->ID;
            $device_name = $device->post_title;
            
            $device_args = array(
                'post_type' => 'rep_jobs',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'post_status' => array('publish', 'pending', 'draft', 'private', 'future'),
                'meta_query' => array(
                    array(
                        'key' => '_wc_device_data',
                        'value' => '"' . $device_id . '"',
                        'compare' => 'LIKE'
                    )
                )
            );
            
            // Add user condition if needed
            if (!empty($user_meta_query)) {
                $device_args['meta_query'][] = $user_meta_query;
            }
            
            $device_query = new WP_Query($device_args);
            $count = $device_query->found_posts;
            
            if ($count > 0) {
                if (count($device_counts) < 5) {
                    $device_counts[$device_name] = $count;
                } else {
                    $other_count += $count;
                }
            }
        }
        
        // Sort devices by count
        arsort($device_counts);
        
        $device_labels = array();
        $device_data = array();
        
        foreach ($device_counts as $name => $count) {
            $device_labels[] = $name;
            $device_data[] = $count;
        }
        
        if ($other_count > 0) {
            $device_labels[] = 'Other';
            $device_data[] = $other_count;
        }
        
        $data['deviceType'] = array(
            'labels' => $device_labels,
            'data' => $device_data
        );
        
        // 4. PERFORMANCE DATA (Average repair time by month)
        $performance_labels = array();
        $performance_data = array();

        // Get average repair time for last 6 months
        for ($i = 5; $i >= 0; $i--) {
            $month = date('n', strtotime("-$i months"));
            $year = date('Y', strtotime("-$i months"));
            $month_label = date_i18n('M', strtotime("$year-$month-01"));
            $performance_labels[] = $month_label;
            
            // Calculate average repair time for completed jobs in this month
            $perf_args = array(
                'post_type' => 'rep_jobs',
                'posts_per_page' => -1,
                'post_status' => array('publish', 'pending', 'draft', 'private', 'future'),
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => '_wc_order_status',
                        'value' => $job_status_delivered,
                        'compare' => '='
                    ),
                    array(
                        'key' => '_pickup_date',
                        'value' => array($year . '-' . sprintf('%02d', $month) . '-01', $year . '-' . sprintf('%02d', $month) . '-31'),
                        'compare' => 'BETWEEN',
                        'type' => 'DATE'
                    ),
                    array(
                        'key' => '_delivery_date',
                        'compare' => 'EXISTS'
                    ),
                    array(
                        'key' => '_pickup_date',
                        'compare' => 'EXISTS'
                    )
                )
            );
            
            // Add user condition if needed
            if (!empty($user_meta_query)) {
                $perf_args['meta_query'][] = $user_meta_query;
            }
            
            $perf_query = new WP_Query($perf_args);
            $total_days = 0;
            $job_count = 0;
            
            if ($perf_query->have_posts()) {
                while ($perf_query->have_posts()) {
                    $perf_query->the_post();
                    $job_id = get_the_ID();
                    
                    $pickup_date = get_post_meta($job_id, '_pickup_date', true);
                    $delivery_date = get_post_meta($job_id, '_delivery_date', true);
                    
                    if ($pickup_date && $delivery_date) {
                        $pickup = strtotime($pickup_date);
                        $delivery = strtotime($delivery_date);
                        
                        if ($pickup && $delivery && $delivery > $pickup) {
                            $days = ($delivery - $pickup) / (60 * 60 * 24);
                            
                            if ($days > 0) {
                                $total_days += $days;
                                $job_count++;
                            }
                        }
                    }
                }
                wp_reset_postdata();
            }
            
            $avg_days = $job_count > 0 ? round($total_days / $job_count, 1) : 0;
            $performance_data[] = floatval($avg_days);
        }

        $data['performance'] = array(
            'labels' => $performance_labels,
            'data' => $performance_data
        );
        
        return $data;
    }

    public function get_customer_chart_data($period = 'yearly') {
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        
        // Get job status settings
        $job_status_delivered = (!empty(get_option('wcrb_job_status_delivered'))) ? get_option('wcrb_job_status_delivered') : 'delivered';
        $job_status_cancelled = (!empty(get_option('wcrb_job_status_cancelled'))) ? get_option('wcrb_job_status_cancelled') : 'cancelled';
    
        $data = array();
        
        // 1. MY JOBS OVERVIEW CHART (timeline of jobs) - DYNAMIC DATA
        if ($period === 'weekly') {
            $labels = array();
            $job_counts = array();
            $completed_jobs = array();
            
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $day_label = date_i18n('D', strtotime($date));
                $labels[] = $day_label;
                
                // Count all customer's jobs for this day
                $args = array(
                    'post_type' => 'rep_jobs',
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                    'post_status' => array('publish', 'pending', 'draft', 'private', 'future'),
                    'meta_query' => array(
                        'relation' => 'AND',
                        array(
                            'key' => '_customer',
                            'value' => $user_id,
                            'compare' => '=',
                            'type' => 'NUMERIC'
                        ),
                        array(
                            'key' => '_pickup_date',
                            'value' => $date,
                            'compare' => 'LIKE'
                        )
                    )
                );
                
                $day_jobs = get_posts($args);
                $job_counts[] = count($day_jobs);
                
                // Count completed jobs for this day
                $completed_args = array(
                    'post_type' => 'rep_jobs',
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                    'post_status' => array('publish', 'pending', 'draft', 'private', 'future'),
                    'meta_query' => array(
                        'relation' => 'AND',
                        array(
                            'key' => '_customer',
                            'value' => $user_id,
                            'compare' => '=',
                            'type' => 'NUMERIC'
                        ),
                        array(
                            'key' => '_wc_order_status',
                            'value' => $job_status_delivered,
                            'compare' => '='
                        ),
                        array(
                            'key' => '_delivery_date',
                            'value' => $date,
                            'compare' => 'LIKE'
                        )
                    )
                );
                
                $completed_day_jobs = get_posts($completed_args);
                $completed_jobs[] = count($completed_day_jobs);
            }
            
            $data['customerJobs'] = array(
                'labels' => $labels,
                'jobCounts' => $job_counts,
                'completedJobs' => $completed_jobs
            );
            
        } elseif ($period === 'monthly') {
            $labels = array();
            $job_counts = array();
            $completed_jobs = array();
            
            for ($i = 11; $i >= 0; $i--) {
                $month = date('n', strtotime("-$i months"));
                $year = date('Y', strtotime("-$i months"));
                $month_label = date_i18n('M', strtotime("$year-$month-01"));
                $labels[] = $month_label;
                
                $first_day = date('Y-m-01', strtotime("$year-$month-01"));
                $last_day = date('Y-m-t', strtotime("$year-$month-01"));
                
                // Count all customer's jobs for this month
                $args = array(
                    'post_type' => 'rep_jobs',
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                    'post_status' => array('publish', 'pending', 'draft', 'private', 'future'),
                    'meta_query' => array(
                        'relation' => 'AND',
                        array(
                            'key' => '_customer',
                            'value' => $user_id,
                            'compare' => '=',
                            'type' => 'NUMERIC'
                        ),
                        array(
                            'key' => '_pickup_date',
                            'value' => array($first_day, $last_day),
                            'compare' => 'BETWEEN',
                            'type' => 'DATE'
                        )
                    )
                );
                
                $month_jobs = get_posts($args);
                $job_counts[] = count($month_jobs);
                
                // Count completed jobs for this month
                $completed_args = array(
                    'post_type' => 'rep_jobs',
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                    'post_status' => array('publish', 'pending', 'draft', 'private', 'future'),
                    'meta_query' => array(
                        'relation' => 'AND',
                        array(
                            'key' => '_customer',
                            'value' => $user_id,
                            'compare' => '=',
                            'type' => 'NUMERIC'
                        ),
                        array(
                            'key' => '_wc_order_status',
                            'value' => $job_status_delivered,
                            'compare' => '='
                        ),
                        array(
                            'key' => '_delivery_date',
                            'value' => array($first_day, $last_day),
                            'compare' => 'BETWEEN',
                            'type' => 'DATE'
                        )
                    )
                );
                
                $completed_month_jobs = get_posts($completed_args);
                $completed_jobs[] = count($completed_month_jobs);
            }
            
            $data['customerJobs'] = array(
                'labels' => $labels,
                'jobCounts' => $job_counts,
                'completedJobs' => $completed_jobs
            );
            
        } elseif ($period === 'yearly') {
            $labels = array();
            $job_counts = array();
            $completed_jobs = array();
            
            $current_year = date('Y');
            
            for ($i = 4; $i >= 0; $i--) {
                $year = $current_year - $i;
                $labels[] = $year;
                
                // Count all customer's jobs for this year
                $args = array(
                    'post_type' => 'rep_jobs',
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                    'post_status' => array('publish', 'pending', 'draft', 'private', 'future'),
                    'meta_query' => array(
                        'relation' => 'AND',
                        array(
                            'key' => '_customer',
                            'value' => $user_id,
                            'compare' => '=',
                            'type' => 'NUMERIC'
                        ),
                        array(
                            'key' => '_pickup_date',
                            'value' => array($year . '-01-01', $year . '-12-31'),
                            'compare' => 'BETWEEN',
                            'type' => 'DATE'
                        )
                    )
                );
                
                $year_jobs = get_posts($args);
                $job_counts[] = count($year_jobs);
                
                // Count completed jobs for this year
                $completed_args = array(
                    'post_type' => 'rep_jobs',
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                    'post_status' => array('publish', 'pending', 'draft', 'private', 'future'),
                    'meta_query' => array(
                        'relation' => 'AND',
                        array(
                            'key' => '_customer',
                            'value' => $user_id,
                            'compare' => '=',
                            'type' => 'NUMERIC'
                        ),
                        array(
                            'key' => '_wc_order_status',
                            'value' => $job_status_delivered,
                            'compare' => '='
                        ),
                        array(
                            'key' => '_delivery_date',
                            'value' => array($year . '-01-01', $year . '-12-31'),
                            'compare' => 'BETWEEN',
                            'type' => 'DATE'
                        )
                    )
                );
                
                $completed_year_jobs = get_posts($completed_args);
                $completed_jobs[] = count($completed_year_jobs);
            }
            
            $data['customerJobs'] = array(
                'labels' => $labels,
                'jobCounts' => $job_counts,
                'completedJobs' => $completed_jobs
            );
        }
        
        // ====== STATIC DATA - Only calculate once ======
        // These charts should show TOTAL data, not filtered by period
        
        // 2. CUSTOMER STATUS CHART (ALL jobs, not filtered by period)
        $status_args = array(
            'post_type' => 'rep_jobs',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'post_status' => array('publish', 'pending', 'draft', 'private', 'future'),
            'meta_query' => array(
                array(
                    'key' => '_customer',
                    'value' => $user_id,
                    'compare' => '=',
                    'type' => 'NUMERIC'
                )
            )
        );
        
        $customer_jobs = get_posts($status_args);
        
        $completed_count = 0;
        $active_count = 0;
        $cancelled_count = 0;
        
        foreach ($customer_jobs as $job_id) {
            $job_status = get_post_meta($job_id, '_wc_order_status', true);
            
            if ($job_status === $job_status_delivered) {
                $completed_count++;
            } elseif ($job_status === $job_status_cancelled) {
                $cancelled_count++;
            } else {
                // Count as active ONLY if it's not "quote" status
                // You might want to adjust this based on your actual statuses
                if ($job_status !== 'quote') {
                    $active_count++;
                }
            }
        }
        
        // Pending estimates count for customer
        $estimate_args = array(
            'post_type' => 'rep_estimates',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'post_status' => array('publish', 'pending', 'draft', 'private', 'future'),
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_customer',
                    'value' => $user_id,
                    'compare' => '=',
                    'type' => 'NUMERIC'
                ),
                array(
                    'relation' => 'OR',
                    array(
                        'key' => '_wc_estimate_status',
                        'compare' => 'NOT EXISTS'
                    ),
                    array(
                        'key' => '_wc_estimate_status',
                        'value' => array('approved', 'rejected'),
                        'compare' => 'NOT IN'
                    )
                )
            )
        );
        
        $pending_estimates = get_posts($estimate_args);
        $pending_estimate_count = count($pending_estimates);
        
        $status_data = array(
            $completed_count, 
            $active_count, 
            $pending_estimate_count,
            $cancelled_count
        );
        
        $data['customerStatus'] = $status_data;
        
        // 3. DEVICE TYPE DATA for customer (ALL jobs)
        $device_posts = get_posts(array(
            'post_type'      => 'rep_devices',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'orderby'        => 'title',
            'order'          => 'ASC'
        ));

        $device_counts = array();
        $other_count = 0;

        foreach ($device_posts as $device) {
            $device_id = $device->ID;
            $device_name = $device->post_title;
            
            $device_args = array(
                'post_type' => 'rep_jobs',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'post_status' => array('publish', 'pending', 'draft', 'private', 'future'),
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => '_customer',
                        'value' => $user_id,
                        'compare' => '=',
                        'type' => 'NUMERIC'
                    ),
                    array(
                        'key' => '_wc_device_data',
                        'value' => '"' . $device_id . '"',
                        'compare' => 'LIKE'
                    )
                )
            );
            
            $device_query = new WP_Query($device_args);
            $count = $device_query->found_posts;
            
            if ($count > 0) {
                if (count($device_counts) < 5) {
                    $device_counts[$device_name] = $count;
                } else {
                    $other_count += $count;
                }
            }
        }

        // If no devices found with the LIKE query, try alternative method
        if (empty($device_counts) && $other_count == 0) {
            // Try searching without quotes
            foreach ($device_posts as $device) {
                $device_id = $device->ID;
                $device_name = $device->post_title;
                
                $device_args = array(
                    'post_type' => 'rep_jobs',
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                    'post_status' => array('publish', 'pending', 'draft', 'private', 'future'),
                    'meta_query' => array(
                        'relation' => 'AND',
                        array(
                            'key' => '_customer',
                            'value' => $user_id,
                            'compare' => '=',
                            'type' => 'NUMERIC'
                        ),
                        array(
                            'key' => '_wc_device_data',
                            'value' => $device_id,
                            'compare' => 'LIKE'
                        )
                    )
                );
                
                $device_query = new WP_Query($device_args);
                $count = $device_query->found_posts;
                
                if ($count > 0) {
                    if (count($device_counts) < 5) {
                        $device_counts[$device_name] = $count;
                    } else {
                        $other_count += $count;
                    }
                }
            }
        }

        // Sort devices by count
        arsort($device_counts);

        $device_labels = array();
        $device_data = array();

        foreach ($device_counts as $name => $count) {
            $device_labels[] = $name;
            $device_data[] = $count;
        }

        if ($other_count > 0) {
            $device_labels[] = 'Other';
            $device_data[] = $other_count;
        }

        // Fallback if no device data found
        if (empty($device_labels)) {
            $device_labels = array('No Device Data');
            $device_data = array(1);
        }

        $data['deviceType'] = array(
            'labels' => $device_labels,
            'data' => $device_data
        );
        
        // 4. PERFORMANCE DATA for customer (average completion time - ALL jobs)
        $performance_labels = array();
        $performance_data = array();
        
        // Get customer's completed jobs
        $completed_jobs_args = array(
            'post_type' => 'rep_jobs',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'post_status' => array('publish', 'pending', 'draft', 'private', 'future'),
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_customer',
                    'value' => $user_id,
                    'compare' => '=',
                    'type' => 'NUMERIC'
                ),
                array(
                    'key' => '_wc_order_status',
                    'value' => $job_status_delivered,
                    'compare' => '='
                ),
                array(
                    'key' => '_delivery_date',
                    'compare' => 'EXISTS'
                ),
                array(
                    'key' => '_pickup_date',
                    'compare' => 'EXISTS'
                )
            )
        );
        
        $completed_jobs_list = get_posts($completed_jobs_args);
        
        if (!empty($completed_jobs_list)) {
            $total_days = 0;
            $job_count = 0;
            
            foreach ($completed_jobs_list as $job_id) {
                $pickup_date = get_post_meta($job_id, '_pickup_date', true);
                $delivery_date = get_post_meta($job_id, '_delivery_date', true);
                
                if ($pickup_date && $delivery_date) {
                    $pickup = strtotime($pickup_date);
                    $delivery = strtotime($delivery_date);
                    
                    if ($pickup && $delivery && $delivery > $pickup) {
                        $days = ($delivery - $pickup) / (60 * 60 * 24);
                        
                        if ($days > 0) {
                            $total_days += $days;
                            $job_count++;
                        }
                    }
                }
            }
            
            $avg_days = $job_count > 0 ? round($total_days / $job_count, 1) : 0;
            
            // For customer, show single performance metric
            $performance_labels = array('Average Repair Time');
            $performance_data = array(floatval($avg_days));
        } else {
            $performance_labels = array('No Data');
            $performance_data = array(0);
        }
        
        $data['performance'] = array(
            'labels' => $performance_labels,
            'data' => $performance_data
        );

        return $data;
    }

    function wcrb_register_account() {
        $values = array( 'message' => '', 'success' => '', 'redirect_to' => '' );
        // Check if this is an AJAX request
        if (!isset( $_POST['wcrb_register_account_nonce_post'] ) || ! wp_verify_nonce( $_POST['wcrb_register_account_nonce_post'], 'wcrb_register_account_nonce' ) || ! repairbuddy_verify_captcha_on_submit() ) {
                $values['message'] = esc_html__( "Something is wrong with your submission!", "computer-repair-shop");
                $values['success'] = false;
                wp_send_json($values);
                wp_die();
        }

        // New User Information
        $first_name     = ( isset( $_POST["firstName"] )      && ! empty( $_POST["firstName"] ) ) ? sanitize_text_field( $_POST["firstName"] ) : '';
        $last_name      = ( isset( $_POST["lastName"] )       && ! empty( $_POST["lastName"] ) ) ?  sanitize_text_field($_POST["lastName"]) : '';
        $user_email     = ( isset( $_POST["userEmail"] )      && ! empty( $_POST["userEmail"] ) ) ?  sanitize_email($_POST["userEmail"]) : '';
        $username       = ( isset( $_POST["userEmail"] )      && ! empty( $_POST["userEmail"] ) ) ?  sanitize_email($_POST["userEmail"]) : '';
        $phone_number   = ( isset( $_POST["phoneNumber_ol"] ) && ! empty( $_POST["phoneNumber_ol"] ) ) ?  sanitize_text_field($_POST["phoneNumber_ol"]) : '';
        $user_city      = ( isset( $_POST["userCity"] )       && ! empty( $_POST["userCity"] ) ) ?  sanitize_text_field($_POST["userCity"]) : '';
        $userState      = ( isset( $_POST["userState"] )      && ! empty( $_POST["userState"] ) ) ?  sanitize_text_field($_POST["userState"]) : '';
        $userCountry    = ( isset( $_POST["userCountry"] )    && ! empty( $_POST["userCountry"] ) ) ?  sanitize_text_field($_POST["userCountry"]) : '';
        $postal_code    = ( isset( $_POST["postalCode"] )     && ! empty( $_POST["postalCode"] ) ) ?  sanitize_text_field($_POST["postalCode"]) : '';
        $user_company   = ( isset( $_POST["userCompany"] )    && ! empty( $_POST["userCompany"] ) ) ?  sanitize_text_field($_POST["userCompany"]) : '';
        $user_address   = ( isset( $_POST["userAddress"] )    && ! empty( $_POST["userAddress"] ) ) ?  sanitize_text_field($_POST["userAddress"]) : '';
        $accountNumber  = ( isset( $_POST["accountNumber"] )  && ! empty( $_POST["accountNumber"] ) ) ? sanitize_text_field( $_POST["accountNumber"] ) : '';

        $user_role = "customer";
        $error = 0;
        $message = '';

        // Validation
        if( empty( $user_email ) ) {
            $error = 1;
            $message = esc_html__( "Email is required.", "computer-repair-shop"); 
        } elseif ( empty( $first_name ) ) {
            $error = 1;
            $message = esc_html__("First name is required.", "computer-repair-shop");
        } elseif ( ! empty( $user_email ) && ! is_email( $user_email ) ) {
            $error = 1;
            $message = esc_html__("Email is not valid", "computer-repair-shop");
        } elseif( ! empty( $username ) && ! validate_username( $username ) ) {
            $error = 1;
            $message = esc_html__("Not a valid username", "computer-repair-shop");
        } elseif( ! empty( $username ) && username_exists( $username ) ) {
            $error = 1;
            $message = esc_html__( "A user already exists with this email. Please try to reset your password.", "computer-repair-shop" );
        } elseif ( email_exists( $user_email ) ) {
            $error = 1;
            $message = esc_html__("A user already exists with this email. Please try to reset your password.", "computer-repair-shop");
        }

        // Check if user already exists
        $user = get_user_by( 'login', $user_email );
        $theUserId = '';
        if( $user ) {
            $theUserId = $user->ID;
        } else {
            $user = get_user_by( 'email', $user_email );
            if ($user) {
                $theUserId = $user->ID;
            }
        }

        if ( ! empty( $theUserId ) ) {
            $error = 1;
            $message = esc_html__("A user already exists with this email. Please try to reset your password.", "computer-repair-shop");
        }

        if ( $error == 0 && empty( $theUserId ) ) {
            // Let's add user and get ID
            $password    = wp_generate_password( 8, false );
                
            if( ! empty ( $username ) && ! empty ( $user_email ) ) {
                // We are all set to Register User.
                $userdata = array(
                    'user_login'    => $username,
                    'user_email'    => $user_email,
                    'user_pass'     => $password,
                    'first_name'    => $first_name,
                    'last_name'     => $last_name,
                    'role'          => $user_role
                );
            
                // Insert User Data
                $register_user = wp_insert_user( $userdata );
            
                // If Not exists
                if ( ! is_wp_error( $register_user ) ) {
                    $theUserId = $register_user;
                    
                    // Update user meta
                    update_user_meta( $theUserId, 'customer_account_number', $accountNumber );
                    update_user_meta( $theUserId, 'billing_first_name', $first_name );
                    update_user_meta( $theUserId, 'billing_last_name', $last_name );
                    update_user_meta( $theUserId, 'billing_company', $user_company );
                    update_user_meta( $theUserId, 'billing_address_1', $user_address );
                    update_user_meta( $theUserId, 'billing_city', $user_city );
                    update_user_meta( $theUserId, 'billing_postcode', $postal_code );
                    update_user_meta( $theUserId, 'billing_state', $userState );
                    update_user_meta( $theUserId, 'billing_phone', $phone_number );
                    update_user_meta( $theUserId, 'billing_country', $userCountry );
                    update_user_meta( $theUserId, 'billing_email', $user_email );

                    update_user_meta( $theUserId, 'shipping_first_name', $first_name );
                    update_user_meta( $theUserId, 'shipping_last_name', $last_name );
                    update_user_meta( $theUserId, 'shipping_company', $user_company );
                    update_user_meta( $theUserId, 'shipping_address_1', $user_address );
                    update_user_meta( $theUserId, 'shipping_city', $user_city );
                    update_user_meta( $theUserId, 'shipping_postcode', $postal_code );
                    update_user_meta( $theUserId, 'shipping_state', $userState );
                    update_user_meta( $theUserId, 'shipping_country', $userCountry );
                    update_user_meta( $theUserId, 'shipping_phone', $phone_number );

                    // Send email notification
                    if ( isset( $GLOBALS['WCRB_EMAILS'] ) ) {
                        $GLOBALS['WCRB_EMAILS']->send_user_logins_after_register( $theUserId, $password );
                    }

                    $values['success'] = true;
                    $message = esc_html__("Account created successfully! Login details have been sent to your email.", "computer-repair-shop");
                    $values['redirect_to'] = get_the_permalink();
                } else {
                    $error = 1;
                    $message = '<strong>' . $register_user->get_error_message() . '</strong>';
                }
            }
        }
        $values['message'] = $message;

        wp_send_json( $values );
        wp_die();
    }

    private function setup_labels() {
        // Initialize properties in constructor
        $this->_device_label_plural = empty( get_option( 'wc_device_label_plural' ) ) 
            ? esc_html__( 'Devices', 'computer-repair-shop' ) 
            : get_option( 'wc_device_label_plural' );
            
        $this->_device_label = empty( get_option( 'wc_device_label' ) ) 
            ? esc_html__( 'Device', 'computer-repair-shop' ) 
            : get_option( 'wc_device_label' );
            
        $this->_imei_label = empty( get_option( 'wc_device_id_imei_label' ) ) 
            ? esc_html__( 'ID/IMEI', 'computer-repair-shop' ) 
            : get_option( 'wc_device_id_imei_label' );
            
        $this->_pin_code_label = empty( get_option( 'wc_pin_code_label' ) ) 
            ? esc_html__( 'Pin Code/Password', 'computer-repair-shop' ) 
            : get_option( 'wc_pin_code_label' );

        $this->_delivery_date_label = empty( get_option( 'wcrb_delivery_date_label' ) ) 
            ? esc_html__( 'Delivery Date', 'computer-repair-shop' ) 
            : get_option( 'wcrb_delivery_date_label' );

        $this->_pickup_date_label = empty( get_option( 'wcrb_pickup_date_label' ) ) 
            ? esc_html__( 'Pickup Date', 'computer-repair-shop' ) 
            : get_option( 'wcrb_pickup_date_label' );

        $this->_nextservice_date_label = empty( get_option( 'wcrb_nextservice_date_label' ) ) 
            ? esc_html__( 'Next Service Date', 'computer-repair-shop' ) 
            : get_option( 'wcrb_nextservice_date_label' );
        
        $this->_myaccount_page_id = get_option( 'wc_rb_my_account_page_id', 0 );
    }

    public function ajax_get_theme() {
        // Verify nonce for security
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wcrb_dashboard_nonce' ) ) {
            wp_die( 'Security check failed' );
        }

        $theme = 'light'; // Default theme

        // Check if user is logged in and has a saved preference
        if ( is_user_logged_in() ) {
            $user_id = get_current_user_id();
            $saved_theme = get_user_meta($user_id, 'wcrb_theme_preference', true);
            
            if ($saved_theme && in_array($saved_theme, array('light', 'dark', 'auto'))) {
                $theme = $saved_theme;
            }
        }

        wp_send_json_success(array(
            'theme' => $theme
        ));
    }

    public function ajax_save_theme() {
        // Verify nonce for security
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wcrb_dashboard_nonce' ) ) {
            wp_die('Security check failed');
        }

        // Get the theme from POST data
        $theme = sanitize_text_field( $_POST['theme'] );
        
        // Validate theme option
        $allowed_themes = array( 'light', 'dark', 'auto' );
        if ( ! in_array( $theme, $allowed_themes ) ) {
            wp_send_json_error( 'Invalid theme selection' );
        }

        // Check if user is logged in to save to user meta
        if ( is_user_logged_in() ) {
            $user_id = get_current_user_id();
            update_user_meta( $user_id, 'wcrb_theme_preference', $theme );
            
            wp_send_json_success(array(
                'message' => 'Theme preference saved to user profile',
                'theme' => $theme
            ));
        } else {
            // or just rely on localStorage
            wp_send_json_success(array(
                'message' => 'Theme preference saved locally',
                'theme' => $theme
            ));
        }
    }

    public function shortcode_handler() {
        return '';
    }

    public function generate_navigation() {
        $_active_item = 'dashboard';
        $user_id = get_current_user_id();

        $nav_items = $this->navigation_items();
        $current_page = $this->get_current_page();
        $_active_item = $_active_item ?: $current_page;

        $output = '<ul class="nav nav-pills flex-column wcrb-sidebar-nav">';
        
        $user = wp_get_current_user();
        $user_roles = (array) $user->roles;

        // First, separate parent and child items
        $parent_items = array();
        $child_items = array();

        foreach ($nav_items as $item) {
            if (isset($item['parent'])) {
                $child_items[$item['parent']][] = $item;
            } else {
                $parent_items[$item['id']] = $item;
            }
        }

        // Now generate the navigation
        foreach ($parent_items as $item) {
            if (in_array('all', $item['access']) || array_intersect($user_roles, $item['access'])) {
                $has_children = isset($child_items[$item['id']]);
                
                // Check if this item is active or has active child
                $is_active = ($current_page === $item['id']) ? 'active text-white' : 'text-white-50';
                $has_active_child = false;
                
                if ($has_children) {
                    foreach ($child_items[$item['id']] as $child) {
                        if ($current_page === $child['id']) {
                            $has_active_child = true;
                            break;
                        }
                    }
                }
                
                $item_is_active = $is_active === 'active text-white' || $has_active_child;
                
                $output .= '<li class="wcrb-nav-item nav-item ' . (isset($item['extra_class']) ? esc_attr($item['extra_class']) : '') . '">';
                
                if ($has_children) {
                    // Add toggle functionality for parent with children
                    $output .= '<div class="wcrb-nav-parent">';
                    // For parent with children, use $item_is_active to determine if it should be highlighted
                    $parent_class = $item_is_active ? 'active text-white' : 'text-white-50';
                    $output .= '<a href="' . esc_url($item['url']) . '" class="wcrb-nav-link nav-link d-flex justify-content-between align-items-center ' . $parent_class . '" data-bs-toggle="collapse" data-bs-target="#wcrb-submenu-' . esc_attr($item['id']) . '" ' . (isset($item['target']) ? ' target="' . esc_attr($item['target']) . '"' : '') . '>';
                    $output .= '<span>';
                    $output .= '<i class="' . esc_attr($item['icon']) . ' me-2"></i>';
                    $output .= '<span>' . esc_html($item['title']) . '</span>';
                    $output .= '</span>';
                    // Add chevron icon for expand/collapse
                    $chevron_class = $item_is_active ? 'bi bi-chevron-down' : 'bi bi-chevron-right';
                    $output .= '<i class="' . $chevron_class . ' ms-2 wcrb-chevron"></i>';
                    $output .= '</a>';
                    $output .= '</div>';
                    
                    // Child items in a collapsible div
                    $collapse_class = $item_is_active ? 'show' : '';
                    $output .= '<div id="wcrb-submenu-' . esc_attr($item['id']) . '" class="collapse ' . $collapse_class . '">';
                    $output .= '<ul class="nav flex-column ms-3">';
                    foreach ($child_items[$item['id']] as $child) {
                        if (in_array('all', $child['access']) || array_intersect($user_roles, $child['access'])) {
                            $child_is_active = ($current_page === $child['id']) ? 'active text-white' : 'text-white-50';
                            
                            $output .= '<li class="wcrb-nav-item nav-item">';
                            $output .= '<a href="' . esc_url($child['url']) . '" class="wcrb-nav-link nav-link ' . $child_is_active . '" ' . (isset($child['target']) ? ' target="' . esc_attr($child['target']) . '"' : '') . '>';
                            $output .= '<i class="' . esc_attr($child['icon']) . ' me-2"></i>';
                            $output .= '<span>' . esc_html($child['title']) . '</span>';
                            $output .= '</a>';
                            $output .= '</li>';
                        }
                    }
                    $output .= '</ul>';
                    $output .= '</div>';
                } else {
                    // Regular item without children - use $is_active directly
                    $output .= '<a href="' . esc_url($item['url']) . '" class="wcrb-nav-link nav-link ' . $is_active . '" ' . (isset($item['target']) ? ' target="' . esc_attr($item['target']) . '"' : '') . '>';
                    $output .= '<i class="' . esc_attr($item['icon']) . ' me-2"></i>';
                    $output .= '<span>' . esc_html($item['title']) . '</span>';
                    $output .= '</a>';
                }
                
                $output .= '</li>';
            }
        }

        $output .= '</ul>';

        return $output;
    }

    public function navigation_items() {
        $_nav_array = array();

        $_nav_array[] = array(
            'id'     => 'dashboard',
            'title'  => __( 'Dashboard', 'computer-repair-shop' ),
            'icon'   => 'bi bi-speedometer2',
            'url'    => add_query_arg( 'screen', 'dashboard', get_permalink() ),
            'access' => array( 'all' ),
        );
        $_nav_array[] = array(
            'id'     => 'calendar',
            'title'  => __( 'Calendar', 'computer-repair-shop' ),
            'icon'   => 'bi bi-bag-check',
            'url'    => add_query_arg( 'screen', 'calendar', get_permalink() ),
            'access' => array( 'administrator', 'store_manager', 'technician' ),
        );
        //Jobs, Estimates, My Devices, Customer Devices, Reviews, Book My Device
        $_nav_array[] = array(
            'id'     => 'jobs',
            'title'  => __( 'Jobs', 'computer-repair-shop' ),
            'icon'   => 'bi bi-wrench',
            'url'    => add_query_arg( 'screen', 'jobs', get_permalink() ),
            'access' => array( 'all' ),
        );
        $wcrb_disable_timelog = get_option( 'wcrb_disable_timelog' );
        if ( $wcrb_disable_timelog !== 'on' ) {
            $_nav_array[] = array(
                'id'     => 'timelog',
                'title'  => __( 'Time Log', 'computer-repair-shop' ),
                'icon'   => 'bi bi-stopwatch',
                'url'    => add_query_arg( 'screen', 'timelog', get_permalink() ),
                'access' => array( 'administrator', 'store_manager', 'technician' ),
            );
        }
        $_nav_array[] = array(
            'id'     => 'estimates',
            'title'  => __( 'Estimates', 'computer-repair-shop' ),
            'icon'   => 'bi bi-file-earmark-text',
            'url'    => add_query_arg( 'screen', 'estimates', get_permalink() ),
            'access' => array( 'all' ),
        );
        $_nav_array[] = array(
            'id'     => 'my-devices',
            'title'  => sprintf( __( 'My %s', 'computer-repair-shop' ), $this->_device_label_plural ),
            'icon'   => 'bi bi-phone',
            'url'    => add_query_arg( 'screen', 'customer-devices', get_permalink() ),
            'access' => array( 'customer' ),
        );
        $_nav_array[] = array(
            'id'     => 'customer-devices',
            'title'  => sprintf( __( 'Customer %s', 'computer-repair-shop' ), $this->_device_label_plural ),
            'icon'   => 'bi bi-phone',
            'url'    => add_query_arg( 'screen', 'customer-devices', get_permalink() ),
            'access' => array( 'administrator', 'store_manager', 'technician' ),
        );
        $_nav_array[] = array(
            'id'     => 'reviews',
            'title'  => __( 'Reviews', 'computer-repair-shop' ),
            'icon'   => 'bi bi-star',
            'url'    => add_query_arg( 'screen', 'reviews', get_permalink() ),
            'access' => array( 'all' ),
        );
        $_nav_array[] = array(
            'id'     => 'book-my-device',
            'title'  => sprintf( __( 'Book My %s', 'computer-repair-shop' ), $this->_device_label ),
            'icon'   => 'bi bi-calendar-plus',
            'url'    => add_query_arg( 'screen', 'book-my-device', get_permalink() ),
            'access' => array( 'customer' ),
        );
        $_nav_array[] = array(
            'id'     => 'expenses_parent',
            'title'  => __( 'Expenses', 'computer-repair-shop' ),
            'icon'   => 'bi bi-calculator',
            'url'    => '#',
            'access' => array( 'administrator', 'store_manager' ),
            'extra_class' => 'mt-3',
        );
        $_nav_array[] = array(
            'id'     => 'expenses',
            'title'  => __( 'Expenses', 'computer-repair-shop' ),
            'parent' => 'expenses_parent',
            'icon'   => 'bi bi-calculator',
            'url'    => add_query_arg( 'screen', 'expenses', get_permalink() ),
            'access' => array( 'administrator', 'store_manager' ),
        );
        $_nav_array[] = array(
            'id'     => 'expense_categories',
            'title'  => __( 'Expense Categories', 'computer-repair-shop' ),
            'parent' => 'expenses_parent',
            'icon'   => 'bi bi-tags',
            'url'    => add_query_arg( 'screen', 'expense_categories', get_permalink() ),
            'access' => array( 'administrator', 'store_manager' ),
        );
        //Profile, Settings, Support
        $_nav_array[] = array(
            'id'     => 'profile',
            'title'  => __( 'Profile', 'computer-repair-shop' ),
            'icon'   => 'bi bi-person-circle',
            'url'    => add_query_arg( 'screen', 'profile', get_permalink() ),
            'access' => array( 'all' ),
            'extra_class' => 'mt-3',
        );
        $_nav_array[] = array(
            'id'     => 'settings',
            'title'  => __( 'Settings', 'computer-repair-shop' ),
            'icon'   => 'bi bi-gear',
            'url'    => admin_url( 'admin.php?page=wc-computer-rep-shop-handle' ),
            'access' => array( 'administrator' ),
        );
        $_nav_array[] = array(
            'id'     => 'support',
            'title'  => __( 'Support', 'computer-repair-shop' ),
            'icon'   => 'bi bi-life-preserver',
            'url'    => 'https://www.webfulcreations.com/repairbuddy-wordpress-plugin/contact/',
            'access' => array( 'administrator', 'store_manager' ),
            'target' => '_blank',
        );

        return $_nav_array;
    }

    public function get_current_page() {
        return sanitize_text_field( $_GET['screen'] ?? 'dashboard' );
    }

    public function get_page_title() {
        $current_page = $this->get_current_page();

        $title_map = [
                            'dashboard'          => esc_html__( 'Dashboard',                              'computer-repair-shop' ),
                            'jobs'               => esc_html__( 'Manage jobs',                            'computer-repair-shop' ),
                            'estimates'          => esc_html__( 'Manage estimates',                       'computer-repair-shop' ),
                            'calendar'           => esc_html__( 'Jobs & estimates calendar appointments', 'computer-repair-shop' ),
                            'customer-devices'   => esc_html__( 'Customer',                               'computer-repair-shop' ) . ' ' . lcfirst( $this->_device_label_plural ),
                            'my-devices'         => esc_html__( 'My',                                     'computer-repair-shop' ) . ' ' . lcfirst( $this->_device_label_plural ),
                            'reviews'            => esc_html__( 'Job reviews',                            'computer-repair-shop' ),
                            'book-my-device'     => esc_html__( 'Book your',                              'computer-repair-shop' ) . ' ' . lcfirst( $this->_device_label ),
                            'profile'            => esc_html__( 'Manage your profile',                    'computer-repair-shop' ),
                            'jobs_card'          => esc_html__( 'Manage Job',                             'computer-repair-shop' ),
                            'estimates_card'     => esc_html__( 'Manage Estimate',                        'computer-repair-shop' ),
                            'timelog'            => esc_html__( 'Manage Timelog',                         'computer-repair-shop' ),
                            'expenses'           => esc_html__( 'Manage Expenses',                        'computer-repair-shop' ),
                            'expense_categories' => esc_html__( 'Manage Expense Categories',              'computer-repair-shop' ),
                            'print-screen'       => esc_html__( 'Print, View & Downloads',                'computer-repair-shop' ),
                        ];
        return $title_map[ $current_page ] ?? esc_html__( 'Dashboard', 'computer-repair-shop' );
    }

    function wcrb_update_profile() {
        // Verify nonce
        if (!isset($_POST['wcrb_updateuser_nonce_post']) || ! wp_verify_nonce( $_POST['wcrb_updateuser_nonce_post'], 'wcrb_updateuser_nonce' ) ) {
            wp_send_json_error( array('message' => esc_html__("Security verification failed!", "computer-repair-shop") ) );
            wp_die();
        }

        // Initialize response
        $response = array( 'success' => false, 'message' => '' );

        // Get current user
        $current_user = wp_get_current_user();
        if ( ! $current_user->ID ) {
            $response['message'] = esc_html__("User not logged in.", "computer-repair-shop");
            wp_send_json_error($response);
            wp_die();
        }

        $user_id = $current_user->ID;

        // Sanitize and validate input data
        $first_name    = isset( $_POST['reg_fname'] ) ? sanitize_text_field($_POST['reg_fname']) : '';
        $last_name     = isset($_POST['reg_lname']) ? sanitize_text_field($_POST['reg_lname']) : '';
        $user_email    = isset($_POST['reg_email']) ? sanitize_email($_POST['reg_email']) : '';
        $phone_number  = isset($_POST['phoneNumber']) ? sanitize_text_field($_POST['phoneNumber']) : '';
        $company       = isset($_POST['customer_company']) ? sanitize_text_field($_POST['customer_company']) : '';
        $billing_tax   = isset($_POST['billing_tax']) ? sanitize_text_field($_POST['billing_tax']) : '';
        $address       = isset($_POST['customer_address']) ? sanitize_text_field($_POST['customer_address']) : '';
        $city          = isset($_POST['customer_city']) ? sanitize_text_field($_POST['customer_city']) : '';
        $zip_code      = isset($_POST['zip_code']) ? sanitize_text_field($_POST['zip_code']) : '';
        $state         = isset($_POST['state_province']) ? sanitize_text_field($_POST['state_province']) : '';
        $country       = isset($_POST['country']) ? sanitize_text_field($_POST['country']) : '';

        // Validation
        if (empty($first_name)) {
            $response['message'] = esc_html__("First name is required.", "computer-repair-shop");
            wp_send_json_error($response);
            wp_die();
        }

        if (empty($user_email)) {
            $response['message'] = esc_html__("Email address is required.", "computer-repair-shop");
            wp_send_json_error($response);
            wp_die();
        }

        if (!is_email($user_email)) {
            $response['message'] = esc_html__("Invalid email address.", "computer-repair-shop");
            wp_send_json_error($response);
            wp_die();
        }

        // Check if email is already used by another user
        $email_exists = email_exists($user_email);
        if ($email_exists && $email_exists != $user_id) {
            $response['message'] = esc_html__("This email address is already registered.", "computer-repair-shop");
            wp_send_json_error($response);
            wp_die();
        }

        // Update user data
        $userdata = array(
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'user_email' => $user_email
        );

        $update_user = wp_update_user($userdata);

        if (is_wp_error($update_user)) {
            $response['message'] = $update_user->get_error_message();
            wp_send_json_error($response);
            wp_die();
        }

        // Update user meta
        update_user_meta($user_id, 'billing_phone', $phone_number);
        update_user_meta($user_id, 'billing_company', $company);
        update_user_meta($user_id, 'billing_tax', $billing_tax);
        update_user_meta($user_id, 'billing_address_1', $address);
        update_user_meta($user_id, 'billing_city', $city);
        update_user_meta($user_id, 'billing_postcode', $zip_code);
        update_user_meta($user_id, 'billing_state', $state);
        update_user_meta($user_id, 'billing_country', $country);

        update_user_meta($user_id, 'shipping_first_name', $first_name);
        update_user_meta($user_id, 'shipping_last_name', $last_name);
        update_user_meta($user_id, 'shipping_company', $company);
        update_user_meta($user_id, 'shipping_address_1', $address);
        update_user_meta($user_id, 'shipping_city', $city);
        update_user_meta($user_id, 'shipping_postcode', $zip_code);
        update_user_meta($user_id, 'shipping_state', $state);
        update_user_meta($user_id, 'shipping_country', $country);
        update_user_meta($user_id, 'shipping_phone', $phone_number);

        $response['success'] = true;
        $response['message'] = esc_html__("Profile updated successfully!", "computer-repair-shop");

        wp_send_json_success($response);
        wp_die();
    }

    function wcrb_update_password() {
        // Verify nonce
        if (!isset($_POST['wcrb_updatepassword_nonce_post']) || ! wp_verify_nonce( $_POST['wcrb_updatepassword_nonce_post'], 'wcrb_updatepassword_nonce') ) {
            wp_send_json( array( 'message' => esc_html__( "Security verification failed!", "computer-repair-shop" ) ) );
            wp_die();
        }

        // Initialize response
        $response = array( 'success' => false, 'message' => '' );

        // Get current user
        $current_user = wp_get_current_user();
        if ( ! $current_user->ID ) {
            $response['message'] = esc_html__( "User not logged in.", "computer-repair-shop" );
            wp_send_json( $response );
            wp_die();
        }

        $user_id = $current_user->ID;

        // Get password fields
        $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
        $new_password     = isset($_POST['new_password']) ? $_POST['new_password'] : '';
        $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

        // Validation
        if ( empty( $current_password ) || empty( $new_password ) || empty( $confirm_password ) ) {
            $response['message'] = esc_html__( "All password fields are required.", "computer-repair-shop" );
            wp_send_json( $response );
            wp_die();
        }

        if ( $new_password !== $confirm_password ) {
            $response['message'] = esc_html__( "New passwords do not match.", "computer-repair-shop" );
            wp_send_json( $response );
            wp_die();
        }

        if ( strlen( $new_password ) < 8) {
            $response['message'] = esc_html__( "Password must be at least 8 characters long.", "computer-repair-shop" );
            wp_send_json( $response );
            wp_die();
        }

        // Verify current password
        if ( ! wp_check_password( $current_password, $current_user->user_pass, $user_id ) ) {
            $response['message'] = esc_html__( "Current password is incorrect.", "computer-repair-shop" );
            wp_send_json($response);
            wp_die();
        }

        // Update password
        wp_set_password( $new_password, $user_id );

        // Re-login user after password change
        wp_set_current_user( $user_id );
        wp_set_auth_cookie( $user_id );

        $response['success'] = true;
        $response['message'] = esc_html__( "Password updated successfully!", "computer-repair-shop" );

        wp_send_json( $response );
        wp_die();
    }

    // Handle profile photo upload
    public function wcrb_update_profile_photo() {
        // Verify nonce
        if ( ! isset( $_POST['wcrb_profile_photo_nonce'] ) || ! wp_verify_nonce( $_POST['wcrb_profile_photo_nonce'], 'wcrb_update_profile_photo' ) ) {
            wp_send_json_error( 'Security verification failed.' );
            wp_die();
        }

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( 'User not logged in.' );
            wp_die();
        }

        $user_id = get_current_user_id();

        // Check if file was uploaded
        if ( empty( $_FILES['profile_photo'] ) || ! is_uploaded_file( $_FILES['profile_photo']['tmp_name'] ) ) {
            wp_send_json_error( 'No file uploaded.' );
            wp_die();
        }

        // Include WordPress image handling functions
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/media.php' );

        // Handle file upload
        $upload = wp_handle_upload( $_FILES['profile_photo'], array( 'test_form' => false ) );
        
        if (isset($upload['error'])) {
            wp_send_json_error( $upload['error'] );
            wp_die();
        }

        // Create attachment
        $attachment = array(
            'post_mime_type' => $upload['type'],
            'post_title' => sanitize_file_name($_FILES['profile_photo']['name']),
            'post_content' => '',
            'post_status' => 'inherit',
            'guid' => $upload['url']
        );

        $attach_id = wp_insert_attachment($attachment, $upload['file']);

        // Generate attachment metadata
        $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);

        // Update user meta with attachment ID
        $old_avatar = get_user_meta($user_id, 'custom_avatar', true);
        if ($old_avatar) {
            wp_delete_attachment($old_avatar, true);
        }

        update_user_meta($user_id, 'custom_avatar', $attach_id);

        // Get the avatar URL to return
        $avatar_url = wp_get_attachment_image_url($attach_id, 'medium');
        
        wp_send_json_success(array(
            'message' => 'Profile photo updated successfully!',
            'avatar_url' => $avatar_url
        ));
        wp_die();
    }

    // Add this to your class
    public function wcrb_custom_avatar($avatar, $id_or_email, $size, $default, $alt) {
        $user = false;

        if (is_numeric($id_or_email)) {
            $id = (int) $id_or_email;
            $user = get_user_by('id', $id);
        } elseif (is_object($id_or_email)) {
            if (!empty($id_or_email->user_id)) {
                $id = (int) $id_or_email->user_id;
                $user = get_user_by('id', $id);
            }
        } else {
            $user = get_user_by('email', $id_or_email);
        }

        if ($user && is_object($user)) {
            $custom_avatar_id = get_user_meta($user->ID, 'custom_avatar', true);
            if ($custom_avatar_id) {
                $avatar_url = wp_get_attachment_image_url($custom_avatar_id, array($size, $size));
                if ($avatar_url) {
                    $avatar = "<img alt='{$alt}' src='{$avatar_url}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
                }
            }
        }

        return $avatar;
    }

    function wc_generate_status_dropdown( $job_id = '', $userrole = '' ) {
        global $wpdb;

        if ( empty( $userrole ) ) {
            return;
        }

        $wc_order_status = ( ! empty( $job_id ) ) ? get_post_meta( $job_id, '_wc_order_status', true ) : '';

        $selected_field = !empty( $wc_order_status ) ? $wc_order_status : 'new';
        
        // Table
        $computer_repair_job_status = $wpdb->prefix . 'wc_cr_job_status';

        $select_query = "SELECT * FROM `" . $computer_repair_job_status . "` WHERE `status_status` = 'active'";
        $select_results = $wpdb->get_results( $select_query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        
        // Get the display name for selected status
        $selected_display_name = esc_html__( "New Order", "computer-repair-shop" );
        $selected_icon         = '';
        $button_class          = ( $userrole == 'administrator' || $userrole == 'technician' || $userrole == 'store_manager' ) ? 'btn-sm dropdown-toggle d-flex align-items-center ' : 'btn-sm align-items-center ';
        
        foreach( $select_results as $result ) {
            if ( $result->status_slug == $selected_field ) {
                $selected_display_name = $result->status_name;
                $selected_icon = $this->wc_get_status_icon( $result->status_slug );
                $button_class .= $this->wc_get_status_button_class( $result->status_slug );
                break;
            }
        }
        $output = '';
        if ( $userrole == 'technician' || $userrole == 'store_manager' || $userrole == 'administrator' ) :
            $output = '<div class="dropdown">';
            $output .= '<button class="btn ' . $button_class . '" type="button" data-bs-toggle="dropdown" aria-expanded="false">';
            if ( $selected_icon ) {
                $output .= '<i class="' . esc_attr( $selected_icon ) . ' me-2"></i>';
            }
            $output .= esc_html( $selected_display_name );
            $output .= '</button>';

            $output .= '<ul class="dropdown-menu">';
            
            foreach( $select_results as $result ) {
                $is_selected = ( $result->status_slug == $selected_field ) ? 'active' : '';
                $checkmark = ( $result->status_slug == $selected_field ) ? '<i class="bi bi-check2 text-primary ms-2"></i>' : '';
                $status_icon = $this->wc_get_status_icon( $result->status_slug );
                $status_color = $this->wc_get_status_color( $result->status_slug );
                
                $output .= '<li>';
                $output .= '<a class="dropdown-item d-flex justify-content-between align-items-center py-2 ' . $is_selected . '"
                            recordid="' . esc_attr( $job_id ) . '"
                            data-type="job_status_update"
                            data-value="' . esc_attr( $result->status_slug ) . '"
                            data-security="' . esc_attr( wp_create_nonce( 'wcrb_nonce_adrepairbuddy' ) ) . '"
                            href="#" data-status="' . esc_attr( $result->status_slug ) . '">';
                $output .= '<div class="d-flex align-items-center">';
                if ( $status_icon ) {
                    $output .= '<i class="' . esc_attr( $status_icon ) . ' ' . esc_attr( $status_color ) . ' me-2"></i>';
                }
                $output .= '<span>' . esc_html( $result->status_name ) . '</span>';
                $output .= '</div>';
                $output .= $checkmark;
                $output .= '</a>';
                $output .= '</li>';
            }
            
            $output .= '</ul>';
            $output .= '</div>';
        else: 
            $output = '<div class="btn ' . $button_class . '" type="button">';
            if ( $selected_icon ) {
                $output .= '<i class="' . esc_attr( $selected_icon ) . ' me-2"></i>';
            }
            $output .= esc_html( $selected_display_name );
            $output .= '</div>';
        endif;

        return $output;
    }

    function wc_get_status_icon( $status_slug ) {
        $icons = [
            'new'            => 'bi-plus-circle',
            'quote'          => 'bi-file-text',
            'cancelled'      => 'bi-x-circle',
            'inprocess'      => 'bi-gear',
            'inservice'      => 'bi-tools',
            'ready_complete' => 'bi-check-circle',
            'delivered'      => 'bi-check-square',
        ];
        
        return isset( $icons[$status_slug] ) ? $icons[$status_slug] : 'bi-circle';
    }

    function wc_get_status_bg_color( $status_slug ) {
        $colors = [
            'new'           => 'bg-primary',
            'quote'         => 'bg-warning',
            'cancelled'     => 'bg-danger',
            'inprocess'     => 'bg-info',
            'inservice'     => 'bg-primary',
            'ready_complete' => 'bg-success',
            'delivered'     => 'bg-success',
        ];
        
        return isset( $colors[$status_slug] ) ? $colors[$status_slug] : 'bg-secondary';
    }

    function wc_get_status_color( $status_slug ) {
        $colors = [
            'new'           => 'text-primary',
            'quote'         => 'text-warning',
            'cancelled'     => 'text-danger',
            'inprocess'     => 'text-info',
            'inservice'     => 'text-primary',
            'ready_complete' => 'text-success',
            'delivered'     => 'text-success',
        ];
        
        return isset( $colors[$status_slug] ) ? $colors[$status_slug] : 'text-muted';
    }

    function wc_get_status_button_class( $status_slug ) {
        $button_classes = [
            'new'           => 'btn-primary',
            'quote'         => 'btn-warning',
            'cancelled'     => 'btn-danger',
            'inprocess'     => 'btn-info',
            'inservice'     => 'btn-primary',
            'ready_complete' => 'btn-success',
            'delivered'     => 'btn-success',
        ];
        
        return isset( $button_classes[$status_slug] ) ? $button_classes[$status_slug] : 'btn-secondary';
    }

    function have_job_access( $job_id ) {
        if ( ! is_user_logged_in() ) {
            return false;
        }

        $current_user = wp_get_current_user();
        $user_roles = (array) $current_user->roles;
        $user_id = $current_user->ID;

        // Administrators and Store Managers have access to all jobs
        if ( array_intersect( $user_roles, array( 'administrator', 'store_manager' ) ) ) {
            return true;
        }

        // Technicians can access jobs they're assigned to
        if ( in_array( 'technician', $user_roles ) ) {
            $assigned_technicians = get_post_meta( $job_id, '_technician', true );
            
            // Handle both array and string formats
            if ( is_array( $assigned_technicians ) ) {
                $assigned_technicians = array_map( 'strval', $assigned_technicians );
                if ( in_array( (string) $user_id, $assigned_technicians, true ) ) {
                    return true;
                }
            } elseif ( ! empty( $assigned_technicians ) ) {
                // Handle single technician stored as string
                if ( (string) $user_id === (string) $assigned_technicians ) {
                    return true;
                }
            }
        }

        // Customers can only access their own jobs
        if ( in_array( 'customer', $user_roles ) ) {
            $job_customer_id = get_post_meta( $job_id, '_customer', true );
            if ( $job_customer_id && (int) $job_customer_id === $user_id ) {
                return true;
            }
        }

        return false;
    }

    function extend_current_url( $page, $arguments = array() ) {
        if ( empty( $page ) || empty( $arguments ) ) {
            return;
        }

        unset( $_GET['jobs_page'] );
        $_pageurl = get_the_permalink( $page );
        $_pageurl = ( isset( $_GET ) && ! empty( $_GET ) ) ? add_query_arg( $_GET, $_pageurl ) : $_pageurl;

        $_pageurl = add_query_arg( $arguments, $_pageurl );

        return $_pageurl;
    }

    function dashboard_overview_stats() {
        // Get job status settings
        $job_status_delivered = ( ! empty( get_option( 'wcrb_job_status_delivered' ) ) ) ? get_option( 'wcrb_job_status_delivered' ) : 'delivered';
        $job_status_cancelled = ( ! empty( get_option( 'wcrb_job_status_cancelled' ) ) ) ? get_option( 'wcrb_job_status_cancelled' ) : 'cancelled';

        // Initialize counters
        $active_jobs_count       = 0;
        $completed_jobs_count    = 0;
        $pending_estimates_count = 0;
        $revenue_total           = 0;

        // Determine user role and permissions
        $current_user = wp_get_current_user();
        $user_id      = $current_user->ID;
        $user_roles   = $current_user->roles;

        $is_administrator = in_array('administrator', $user_roles);
        $is_store_manager = in_array('store_manager', $user_roles);
        $is_technician    = in_array('technician', $user_roles);
        $is_customer      = in_array('customer', $user_roles);

        // Get all jobs based on user role
        $meta_query_arr   = array();

        if ( $is_administrator || $is_store_manager ) {
            
        } elseif ( $is_technician ) {
            // Technician sees only jobs assigned to them
            $meta_query_arr[] = array(
                'relation' => 'OR',
                array(
                    'key' => '_technician',
                    'value' => $user_id,
                    'compare' => 'IN',
                    'type' => 'NUMERIC',
                ),
                array(
                    'key' => '_technician',
                    'value' => $user_id,
                    'compare' => 'REGEXP',
                    'type' => 'CHAR',
                )
            );
        } elseif ($is_customer) {
            // Customer sees only their own jobs
            $meta_query_arr[] = array(
                'key' => '_customer',
                'value' => $user_id,
                'compare' => '=',
            );
        }

        // Query for jobs (rep_jobs post type)
        $jobs_args = array(
            'post_type'      => 'rep_jobs',
            'posts_per_page' => -1, // Get all jobs
            'post_status'    => array('publish', 'pending', 'draft', 'private', 'future'),
        );

        if ( ! empty( $meta_query_arr ) ) {
            $jobs_args['meta_query'] = $meta_query_arr;
        }

        $jobs_query = new WP_Query($jobs_args);

        // Calculate stats from jobs
        if ( $jobs_query->have_posts() ) {
            while ( $jobs_query->have_posts() ) {
                $jobs_query->the_post();
                $job_id = get_the_ID();
                
                // Get job status
                $job_status = get_post_meta($job_id, '_wc_order_status', true);
                
                // Get job total
                $job_total = wc_order_grand_total($job_id, 'grand_total');
                
                // Check if job is cancelled
                if ($job_status === $job_status_cancelled) {
                    // Skip cancelled jobs for all calculations
                    continue;
                }
                
                // Calculate completed jobs (delivered)
                if ($job_status === $job_status_delivered) {
                    $completed_jobs_count++;
                    $revenue_total += $job_total; // Include delivered jobs in revenue
                } 
                // Calculate active jobs (all other statuses except delivered and cancelled)
                else {
                    $active_jobs_count++;
                    $revenue_total += $job_total; // Include active jobs in revenue
                }
            }
            wp_reset_postdata();
        }

        // Get pending estimates (from rep_estimates post type)
        $estimates_args = array(
            'post_type'      => 'rep_estimates',
            'posts_per_page' => -1, // Get all estimates
            'post_status'    => array('publish', 'pending', 'draft', 'private', 'future'),
            'meta_query' => array(
                array(
                    'relation' => 'AND',
                    // Only pending estimates (where _wc_estimate_status doesn't exist or is not 'approved'/'rejected')
                    array(
                        'relation' => 'OR',
                        array(
                            'key' => '_wc_estimate_status',
                            'compare' => 'NOT EXISTS',
                        ),
                        array(
                            'key' => '_wc_estimate_status',
                            'value' => array('approved', 'rejected'),
                            'compare' => 'NOT IN',
                        )
                    ),
                    // User-specific filters
                    !empty($meta_query_arr) ? $meta_query_arr : array()
                )
            )
        );
        $estimates_query = new WP_Query($estimates_args);
        $pending_estimates_count = $estimates_query->found_posts;

        // Format revenue for display
        $revenue_formatted = wc_cr_currency_format($revenue_total);

        // For demonstration, I'm using simple random trends
        $active_jobs_count       = $active_jobs_count;
        $active_jobs_trend       = ( $active_jobs_count > 5 ) ? '+2 from last week' : '-1 from last week';
        $completed_jobs_count    = $completed_jobs_count;
        $completed_jobs_trend    = ( $completed_jobs_count > 20 ) ? '+5 this month' : '+0 this month';
        $pending_estimates_count = $pending_estimates_count;
        $pending_estimates_trend = ( $pending_estimates_count > 2 ) ? '-1 from yesterday' : '+0 from yesterday';
        $revenue_trend           = ( $revenue_total > 2000 ) ? '+12% this month' : '-5% this month';

        return array(
            'active_jobs_count'       => $active_jobs_count,
            'completed_jobs_count'    => $completed_jobs_count,
            'pending_estimates_count' => $pending_estimates_count,
            'revenue_formatted'       => $revenue_formatted
        );
    }
}
WCRB_MYACCOUNT_DASHBOARD::getInstance();