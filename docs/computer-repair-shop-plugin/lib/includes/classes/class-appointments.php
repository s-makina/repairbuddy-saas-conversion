<?php
/**
 * This file handles the functions related to Reviews
 *
 * Help setup pages to they can be used in notifications and other items
 *
 * @package computer-repair-shop
 * @version 3.7947
 */

defined( 'ABSPATH' ) || exit;

( ! defined( 'MYACCOUNT_FOLDER_URL' ) ) ? define( 'MYACCOUNT_FOLDER_URL', WC_COMPUTER_REPAIR_DIR_URL . '/lib/templates/my_account' ) : MYACCOUNT_FOLDER_URL;

add_action( 'plugins_loaded', array( 'WCRB_APPOINTMENTS', 'getInstance' ) );

class WCRB_APPOINTMENTS {

    private static $instance = NULL;

    static public function getInstance() {
        if ( self::$instance === NULL )
            self::$instance = new WCRB_APPOINTMENTS();
        return self::$instance;
    }

    function __construct() {
        $wc_the_page = ( isset( $_GET['page'] ) ) ? sanitize_text_field( $_GET['page'] ) : "";

        add_action( 'wp_ajax_wcrb_get_frontend_calendar_events', [$this, 'wcrb_get_frontend_calendar_events'] );
        add_action( 'wp_ajax_nopriv_wcrb_get_frontend_calendar_events', [$this, 'wcrb_frontend_calendar_no_priv'] );

        if ( $wc_the_page == 'wc-computer-rep-shop-appointments' ) {
            add_action( 'admin_footer', array( $this, 'appointment_script_output' ) );
        }
        add_action('wp_ajax_wcrb_get_calendar_events', array($this, 'wcrb_get_calendar_events'));

        if ( isset( $_GET['page'] ) && $_GET['page'] == 'wc-computer-rep-shop-appointments' ) {
            add_action( 'admin_enqueue_scripts', [$this, 'enque_admin_scripts'] );
        }
    }

    function enque_admin_scripts() {
        wp_enqueue_script( 'bootstrap',            MYACCOUNT_FOLDER_URL . '/js/bootstrap.bundle.min.js', ['jquery'], '5.3.2', true );
        wp_enqueue_style( 'bootstrap',             MYACCOUNT_FOLDER_URL . '/css/bootstrap.min.css', [], '5.3.2', 'all' );
        wp_enqueue_style( 'bootstrap-icons',       MYACCOUNT_FOLDER_URL . '/css/bootstrap-icons.min.css', [], '1.13.1', 'all' );
    }

    function appointments_page_output() {
        $current_user     = wp_get_current_user();
        $user_roles       = (array) $current_user->roles;
        $is_technician    = in_array( 'technician', $user_roles );
        $is_admin         = in_array( 'administrator', $user_roles );
        $is_store_manager = in_array( 'store_manager', $user_roles );

        if ( $is_technician && $is_admin && $is_store_manager ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'computer-repair-shop' ) );
        }

        $thePage = 'pickup_date'; // Default

        if ( isset( $_GET['dataload'] ) ) {
            if ( $_GET['dataload'] == 'by_delivery_date' ) {
                $thePage = 'delivery_date';
            } elseif ( $_GET['dataload'] == 'by_next_service' ) {
                $thePage = 'next_service_date';
            } elseif ( $_GET['dataload'] == 'by_creation_date' ) {
                $thePage = 'creation_date';
            }
        }
        
        $page_titles = array(
            'pickup_date'       => sprintf( esc_html__( 'Appointments by %s', 'computer-repair-shop' ), wcrb_get_label( 'pickup_date', 'none' ) ),
            'delivery_date'     => sprintf( esc_html__( 'Appointments by %s', 'computer-repair-shop' ), wcrb_get_label( 'delivery_date', 'none' ) ),
            'next_service_date' => sprintf( esc_html__( 'Appointments by %s', 'computer-repair-shop' ), wcrb_get_label( 'nextservice_date', 'none' ) ),
            'creation_date'     => esc_html__( 'Appointments by Creation Date', 'computer-repair-shop' )
        );
        
        $wcrb_next_service_date = get_option( 'wcrb_next_service_date' );
        $_enable_next_service_d = ( $wcrb_next_service_date == 'on' ) ? 'yes' : 'no';
        ?>
        <div class="wrap" id="poststuff">
            <h1 class="wp-heading-inline"><?php echo esc_html__( "Jobs & Estimates Calendar", "computer-repair-shop" ); ?></h1>
            <a href="post-new.php?post_type=rep_jobs" class="page-title-action"><?php echo esc_html__( 'Add New Job', 'computer-repair-shop' ); ?></a>
            <a href="post-new.php?post_type=rep_estimates" class="page-title-action"><?php echo esc_html__( 'Add New Estimate', 'computer-repair-shop' ); ?></a>

            <div class="mt-3 mb-3">
                <div class="row align-items-center">
                    <div class="col-lg-8 col-md-7 mb-2 mb-md-0">
                        <div class="btn-group btn-group-sm" role="group" aria-label="Calendar view options">
                            <a href="admin.php?page=wc-computer-rep-shop-appointments" 
                              class="button btn <?php echo ( ! isset( $_GET['dataload'] ) || $_GET['dataload'] == 'by_pickup_date' ) ? esc_attr( 'btn-primary' ) : esc_attr( 'btn-outline-secondary' ); ?>">
                                <?php echo wcrb_get_label( 'pickup_date', 'first' ); ?>
                            </a>
                            <a href="admin.php?page=wc-computer-rep-shop-appointments&dataload=by_delivery_date" 
                              class="button btn <?php echo ( isset( $_GET['dataload'] ) && $_GET['dataload'] == 'by_delivery_date' ) ? esc_attr( 'btn-primary' ) : esc_attr( 'btn-outline-secondary' ); ?>">
                                <?php echo wcrb_get_label( 'delivery_date', 'first' ); ?>
                            </a>
                            <?php if ( $_enable_next_service_d == 'yes' ) : ?>
                                <a href="admin.php?page=wc-computer-rep-shop-appointments&dataload=by_next_service" 
                                class="button btn <?php echo ( isset( $_GET['dataload'] ) && $_GET['dataload'] == 'by_next_service' ) ? esc_attr( 'btn-primary' ) : esc_attr( 'btn-outline-secondary' ); ?>">
                                    <?php echo wcrb_get_label( 'nextservice_date', 'first' ); ?>
                                </a>
                            <?php endif; ?>
                            <a href="admin.php?page=wc-computer-rep-shop-appointments&dataload=by_creation_date" 
                              class="button btn <?php echo ( isset( $_GET['dataload'] ) && $_GET['dataload'] == 'by_creation_date' ) ? esc_attr( 'btn-primary' ) : esc_attr( 'btn-outline-secondary' ); ?>">
                                <?php esc_html_e( 'Creation Date', 'computer-repair-shop' ); ?>
                            </a>
                        </div>
                    </div>
                    
                    <div class="col-lg-4 col-md-5">
                        <?php if ( $is_technician || $is_admin || $is_store_manager ) : ?>
                        <div class="d-flex align-items-center justify-content-md-end">
                            <label class="me-2 mb-0"><strong><?php esc_html_e( 'Filter:', 'computer-repair-shop' ); ?></strong></label>
                            <select id="calendarFilter" class="form-select form-select-sm" style="width: auto; min-width: 180px;">
                                <option value="all"><?php esc_html_e( 'All Items', 'computer-repair-shop' ); ?></option>
                                <option value="jobs"><?php esc_html_e( 'Jobs Only', 'computer-repair-shop' ); ?></option>
                                <option value="estimates"><?php esc_html_e( 'Estimates Only', 'computer-repair-shop' ); ?></option>
                                <?php if ( $is_admin || $is_store_manager ) : ?>
                                <option value="my_assignments"><?php esc_html_e( 'My Assignments', 'computer-repair-shop' ); ?></option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div id="calendarmessage"></div>
            <div id='calendar' class="wcrb_calendar_wrapper"></div>

            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title"><?php echo esc_html( $page_titles[$thePage] ); ?></h5>
                    <div class="row g-2">
                        <div class="col-auto">
                            <span class="badge bg-primary"><?php esc_html_e( 'Job', 'computer-repair-shop' ); ?></span>
                        </div>
                        <div class="col-auto">
                            <span class="badge bg-warning"><?php esc_html_e( 'Estimate', 'computer-repair-shop' ); ?></span>
                        </div>
                        <div class="col-auto">
                            <span class="badge bg-success"><?php esc_html_e( 'New/Quote', 'computer-repair-shop' ); ?></span>
                        </div>
                        <div class="col-auto">
                            <span class="badge bg-info"><?php esc_html_e( 'In Process', 'computer-repair-shop' ); ?></span>
                        </div>
                        <div class="col-auto">
                            <span class="badge bg-danger"><?php esc_html_e( 'Cancelled', 'computer-repair-shop' ); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    function appointment_script_output() {
        // Get locale and convert to FullCalendar format
        $locale = get_bloginfo('language');
        $fullcalendar_locale = str_replace('_', '-', $locale);

        // Get current user for filtering
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        $user_roles = (array) $current_user->roles;
        
        // Determine date field to use
        $date_field = 'pickup_date';
        if ( isset( $_GET['dataload'] ) ) {
            if ( $_GET['dataload'] == 'by_delivery_date' ) {
                $date_field = 'delivery_date';
            } elseif ( $_GET['dataload'] == 'by_next_service' ) {
                $date_field = 'next_service_date';
            } elseif ( $_GET['dataload'] == 'by_creation_date' ) {
                $date_field = 'post_date';
            }
        }
        
        // Create a fresh nonce
        $nonce = wp_create_nonce('wcrb_calendar_nonce');
        
        // Ensure ajaxurl is defined
        $ajaxurl = admin_url('admin-ajax.php');
        ?>
        <style>
            /* Your existing styles */
            .fc-event {
                border-radius: 4px;
                border: none;
                font-size: 0.85em;
                padding: 2px 4px;
            }
            .fc-event-dot {
                display: none;
            }
            .job-event { border-left: 4px solid #007bff; }
            .estimate-event { border-left: 4px solid #ffc107; }
            .status-new { background-color: #28a745 !important; }
            .status-quote { background-color: #17a2b8 !important; }
            .status-inprocess { background-color: #20c997 !important; }
            .status-ready { background-color: #fd7e14 !important; }
            .status-completed { background-color: #6f42c1 !important; }
            .status-delivered { background-color: #e83e8c !important; }
            .status-cancelled { background-color: #dc3545 !important; }
            .tooltip-inner {
                max-width: 300px;
                text-align: left;
            }
            .legend-badge {
                display: inline-block;
                width: 12px;
                height: 12px;
                margin-right: 5px;
                border-radius: 2px;
            }
        </style>
        
        <script type="text/javascript">
            // Define global variables
            var wcrb_calendar_data = {
                nonce: '<?php echo esc_js($nonce); ?>',
                date_field: '<?php echo esc_js($date_field); ?>',
                ajaxurl: '<?php echo esc_js($ajaxurl); ?>'
            };
            
            // Ensure ajaxurl is available globally
            if (typeof ajaxurl === 'undefined') {
                var ajaxurl = wcrb_calendar_data.ajaxurl;
            }
            
            document.addEventListener('DOMContentLoaded', function() {
                var calendarEl = document.getElementById('calendar');
                
                var filterValue = 'all';
                
                var calendar = new FullCalendar.Calendar(calendarEl, {
                    expandRows: true,
                    slotMinTime: '07:00',
                    slotMaxTime: '21:00',
                    buttonText: {
                        today:    '<?php echo esc_html__( 'Today', 'computer-repair-shop' ); ?>',
                        month:    '<?php echo esc_html__( 'Month', 'computer-repair-shop' ); ?>',
                        week:     '<?php echo esc_html__( 'Week', 'computer-repair-shop' ); ?>',
                        day:      '<?php echo esc_html__( 'Day', 'computer-repair-shop' ); ?>',
                        list:     '<?php echo esc_html__( 'List', 'computer-repair-shop' ); ?>'
                    },
                    headerToolbar: {
                        left: 'prevYear,prev,next,nextYear today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                    },
                    height: 'auto',
                    initialView: 'dayGridMonth',
                    initialDate: '<?php echo esc_html( wp_date( 'Y-m-d' ) ); ?>',
                    locale: '<?php echo esc_attr( $fullcalendar_locale ); ?>',
                    navLinks: true,
                    editable: false,
                    selectable: true,
                    nowIndicator: true,
                    dayMaxEvents: 10,
                    events: function(fetchInfo, successCallback, failureCallback) {
                        // Format dates to YYYY-MM-DD (strip timezone info)
                        var startDate = fetchInfo.startStr.split('T')[0];
                        var endDate = fetchInfo.endStr.split('T')[0];
                        
                        var requestData = {
                            action: 'wcrb_get_calendar_events',
                            date_field: wcrb_calendar_data.date_field,
                            filter: filterValue,
                            start: startDate,
                            end: endDate,
                            security: wcrb_calendar_data.nonce
                        };
                        
                        jQuery.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: requestData,
                            dataType: 'json',
                            success: function(response) {
                                if (response && response.success) {
                                    successCallback(response.data);
                                } else {
                                    failureCallback(response && response.message ? response.message : 'Unknown error');
                                }
                            }
                        });
                    },
                    eventDidMount: function(info) {
                        // Add Bootstrap tooltip
                        if (info.event.extendedProps && info.event.extendedProps.tooltip) {
                            info.el.setAttribute('title', info.event.extendedProps.tooltip);
                            info.el.setAttribute('data-bs-toggle', 'tooltip');
                            info.el.setAttribute('data-bs-placement', 'top');
                            // Initialize Bootstrap tooltip
                            new bootstrap.Tooltip(info.el);
                        }
                    },
                    eventClick: function(info) {
                        if (info.event.url) {
                            info.jsEvent.preventDefault();
                            window.open(info.event.url, "_blank");
                        }
                    },
                    eventContent: function(arg) {
                        // Custom event content
                        var title = document.createElement('div');
                        title.classList.add('fc-event-title');
                        title.innerText = arg.event.title || 'No title';
                        
                        var status = document.createElement('div');
                        status.classList.add('fc-event-status');
                        status.style.fontSize = '0.8em';
                        status.style.opacity = '0.8';
                        
                        if (arg.event.extendedProps && arg.event.extendedProps.status) {
                            status.innerText = arg.event.extendedProps.status;
                        }
                        
                        var arrayOfDomNodes = [title];
                        if (arg.event.extendedProps && arg.event.extendedProps.status) {
                            arrayOfDomNodes.push(status);
                        }
                        
                        return { domNodes: arrayOfDomNodes };
                    }
                });
                
                calendar.render();
                
                // Filter events
                var filterElement = document.getElementById('calendarFilter');
                if (filterElement) {
                    filterElement.addEventListener('change', function(e) {
                        filterValue = e.target.value;
                        calendar.refetchEvents();
                    });
                }
                
                // Initialize Bootstrap tooltips
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            });
        </script>
        <?php
    }

    function wcrb_get_calendar_events() {
        if ( ! defined('DOING_AJAX') || ! DOING_AJAX ) {
            wp_die( esc_html__( 'Invalid request', 'computer-repair-shop' ) );
        }
        
        if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], 'wcrb_calendar_nonce' ) ) {
            wp_send_json_error( array(
                'message'   => esc_html__( 'Security verification failed', 'computer-repair-shop' ),
                'debug'     => esc_html__( 'Nonce mismatch or missing', 'computer-repair-shop' )
            ));
        }
        
        $date_field = isset( $_POST['date_field'] ) ? sanitize_text_field( $_POST['date_field'] ) : 'pickup_date';
        $filter     = isset( $_POST['filter'] ) ? sanitize_text_field( $_POST['filter'] ) : 'all';
        $start_date = isset( $_POST['start'] ) ? sanitize_text_field( $_POST['start'] ) : '';
        $end_date   = isset( $_POST['end'] ) ? sanitize_text_field( $_POST['end'] ) : '';
        
        // Get current user
        $current_user = wp_get_current_user();
        $user_id      = $current_user->ID;
        $user_roles   = (array) $current_user->roles;
        
        // Build query args
        $args = array(
            'post_type'      => array( 'rep_jobs', 'rep_estimates' ),
            'posts_per_page' => 200,
            'post_status'    => array( 'publish', 'pending', 'draft', 'private', 'future', 'auto-draft' ),
            'orderby'        => 'ID',
            'order'          => 'DESC',
        );
        
        // Filter by post type
        if ($filter === 'jobs') {
            $args['post_type'] = 'rep_jobs';
        } elseif ($filter === 'estimates') {
            $args['post_type'] = 'rep_estimates';
        }
        
        $meta_query = array();
        $date_query = array();
        
        // Handle meta field vs post_date differently
        if ( $date_field !== 'post_date' ) {
            // For meta fields: prepend underscore
            $meta_field = '_' . $date_field;
            
            // Ensure the field exists and is not empty (for non-draft posts)
            $meta_query[] = array(
                'relation' => 'OR',
                // Non-draft posts must have the meta field
                array(
                    'relation' => 'AND',
                    array(
                        'post_status' => array('publish', 'pending', 'private', 'future')
                    ),
                    array(
                        'key' => $meta_field,
                        'compare' => 'EXISTS'
                    ),
                    array(
                        'key' => $meta_field,
                        'value' => '',
                        'compare' => '!='
                    )
                ),
                // Draft posts: meta field is optional
                array(
                    'relation' => 'AND',
                    array(
                        'post_status' => 'draft'
                    )
                )
            );
            
            // Add date range if provided
            if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
                $start_date_clean = date('Y-m-d', strtotime($start_date));
                $end_date_clean   = date('Y-m-d', strtotime($end_date));
                
                // Add date range to meta query for non-draft posts
                $meta_query[] = array(
                    'relation' => 'OR',
                    // Non-draft posts within date range
                    array(
                        'relation' => 'AND',
                        array(
                            'post_status' => array('publish', 'pending', 'private', 'future')
                        ),
                        array(
                            'key' => $meta_field,
                            'value' => array( $start_date_clean, $end_date_clean ),
                            'compare' => 'BETWEEN',
                            'type' => 'DATE'
                        )
                    ),
                    // Draft posts: include regardless of date range
                    array(
                        'relation' => 'AND',
                        array(
                            'post_status' => 'draft'
                        )
                    )
                );
            }
        } else {
            // For post_date: use date_query
            if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
                $args['date_query'] = array(
                    array(
                        'column' => 'post_date',
                        'after' => $start_date,
                        'before' => $end_date,
                        'inclusive' => true,
                    )
                );
            }
        }
        
        // For technicians: filter by assignments
        if ( in_array('technician', $user_roles) && $filter !== 'my_assignments' ) {
            $meta_query[] = array(
                'relation' => 'OR',
                array(
                    'key' => '_technician',
                    'value' => $user_id,
                    'compare' => '='
                ),
                array(
                    'key' => '_technician',
                    'value' => $user_id,
                    'compare' => 'LIKE'
                )
            );
        }
        
        // For "my_assignments" filter for admins/store managers
        if ($filter === 'my_assignments' && (in_array('administrator', $user_roles) || in_array('store_manager', $user_roles))) {
            $meta_query[] = array(
                'relation' => 'OR',
                array(
                    'key' => '_technician',
                    'value' => $user_id,
                    'compare' => '='
                ),
                array(
                    'key' => '_technician',
                    'value' => $user_id,
                    'compare' => 'LIKE'
                )
            );
        }
        
        // Add meta query to args if we have any
        if (!empty($meta_query)) {
            if (count($meta_query) > 1) {
                $args['meta_query'] = array(
                    'relation' => 'AND',
                    $meta_query
                );
            } else {
                $args['meta_query'] = $meta_query[0];
            }
        }
    
        $query = new WP_Query($args);
    
        $events = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                $post_type = get_post_type($post_id);
                $post_status = get_post_status($post_id);
                
                // Get the date
                if ($date_field === 'post_date') {
                    $event_date = get_the_date('Y-m-d');
                } else {
                    $meta_field = '_' . $date_field;
                    $event_date = get_post_meta($post_id, $meta_field, true);
                    
                    // Handle empty dates for draft posts
                    if (empty($event_date)) {
                        if ($post_status === 'draft') {
                            // Use modified date or current date for drafts
                            $event_date = get_the_modified_date('Y-m-d', $post_id);
                            if (empty($event_date)) {
                                $event_date = date('Y-m-d');
                            }
                        } else {
                            continue; // Skip non-draft posts without date
                        }
                    }
                    
                    // Format the date properly
                    $timestamp = strtotime($event_date);
                    if ($timestamp === false) {
                        continue;
                    }
                    $event_date = date('Y-m-d', $timestamp);
                }
                
                // Get job/estimate number
                $case_number = get_post_meta($post_id, '_case_number', true);
                $customer_id = get_post_meta($post_id, '_customer', true);
                
                $customer_name = esc_html__( 'Unknown', 'computer-repair-shop' );
                if ($customer_id) {
                    $user = get_user_by('id', $customer_id);
                    if ($user) {
                        $customer_name = trim($user->first_name . ' ' . $user->last_name);
                        if (empty($customer_name)) {
                            $customer_name = $user->display_name;
                        }
                        $customer_name = esc_html($customer_name);
                    }
                }
                
                // Get status with proper mapping
                $status = get_post_meta($post_id, '_wc_order_status_label', true);
                if (empty($status)) {
                    $status = ($post_type === 'rep_estimates') ? 
                        esc_html__('Estimate', 'computer-repair-shop') : 
                        esc_html__('Job', 'computer-repair-shop');
                } else {
                    // Map to your default statuses if needed
                    $status_lower = strtolower($status);
                    $status_mapping = array(
                        'new order' => esc_html__('New Order', 'computer-repair-shop'),
                        'quote' => esc_html__('Quote', 'computer-repair-shop'),
                        'cancelled' => esc_html__('Cancelled', 'computer-repair-shop'),
                        'in process' => esc_html__('In Process', 'computer-repair-shop'),
                        'in service' => esc_html__('In Service', 'computer-repair-shop'),
                        'ready/complete' => esc_html__('Ready/Complete', 'computer-repair-shop'),
                        'delivered' => esc_html__('Delivered', 'computer-repair-shop')
                    );
                    
                    // Use mapped status if exists, otherwise use original
                    if (isset($status_mapping[$status_lower])) {
                        $status = $status_mapping[$status_lower];
                    } else {
                        $status = esc_html($status);
                    }
                }
                
                // Build title with formatted job number for jobs
                if ($post_type === 'rep_estimates') {
                    // Keep estimate number as post ID
                    $title = sprintf(
                        esc_html__('Estimate #%d - %s', 'computer-repair-shop'),
                        $post_id,
                        $customer_name
                    );
                } else {
                    // Use formatted job number for jobs
                    $jobs_manager = WCRB_JOBS_MANAGER::getInstance();
                    $job_data = $jobs_manager->get_job_display_data($post_id);
                    $formatted_job_number = (!empty($job_data['formatted_job_number'])) ? 
                        '#' . $job_data['formatted_job_number'] : 
                        '#' . $post_id;
                    
                    $title = sprintf(
                        esc_html__('Job %s - %s', 'computer-repair-shop'),
                        $formatted_job_number,
                        $customer_name
                    );
                }
                
                // Get color class
                $color_class = $this->get_event_color_class($status, ($post_type === 'rep_estimates' ? 'estimate' : 'job'));
                
                // Build tooltip with translations
                $tooltip_parts = [];
                
                // Add case number
                if ($case_number) {
                    $tooltip_parts[] = sprintf(
                        esc_html__('Case: %s', 'computer-repair-shop'),
                        esc_html($case_number)
                    );
                } else {
                    $tooltip_parts[] = esc_html__('Case: N/A', 'computer-repair-shop');
                }
                
                // Add customer
                $tooltip_parts[] = sprintf(
                    esc_html__('Customer: %s', 'computer-repair-shop'),
                    $customer_name
                );
                
                // Add status
                $tooltip_parts[] = sprintf(
                    esc_html__('Status: %s', 'computer-repair-shop'),
                    $status
                );
                
                // Join tooltip parts
                $tooltip = implode(' | ', $tooltip_parts);
                
                $events[] = array(
                    'id' => $post_id,
                    'title' => $title,
                    'start' => $event_date,
                    'url' => admin_url( 'post.php?post=' . $post_id . '&action=edit' ),
                    'classNames' => array($color_class, ($post_type === 'rep_estimates') ? 'estimate-event' : 'job-event'),
                    'extendedProps' => array(
                        'type' => ($post_type === 'rep_estimates') ? 'estimate' : 'job',
                        'type_label' => ($post_type === 'rep_estimates') ? 
                            esc_html__('Estimate', 'computer-repair-shop') : 
                            esc_html__('Job', 'computer-repair-shop'),
                        'status' => $status,
                        'tooltip' => $tooltip,
                        'case_number' => $case_number ? esc_html($case_number) : 'N/A',
                        'customer' => $customer_name,
                        'post_status' => $post_status,
                        'formatted_id' => ($post_type === 'rep_estimates') ? 
                            '#' . $post_id : 
                            ($formatted_job_number ?? '#' . $post_id)
                    )
                );
            }
        }
        wp_reset_postdata();
        wp_send_json_success($events);
    }
    
    private function get_event_color_class($status, $type) {
        // Clean the status for matching
        $status_clean = strtolower(trim($status));
        $status_clean = preg_replace('/[^a-zA-Z0-9]/', '', $status_clean);
        
        // Define colors for default job statuses only
        $job_colors = array(
            // Exact matches from your $order_status array
            'new' => 'status-new',               // "New Order"
            'neworder' => 'status-new',          // Alternative
            'quote' => 'status-quote',           // "Quote"
            'inprocess' => 'status-inprocess',   // "In Process"
            'inservice' => 'status-inprocess',   // "In Service" → In Process
            'readycomplete' => 'status-ready',   // "Ready/Complete" → Ready
            'delivered' => 'status-delivered',   // "Delivered"
            'cancelled' => 'status-cancelled',   // "Cancelled"
        );
        
        // Return colors based on type
        if ($type === 'job') {
            // Check for exact match
            if (isset($job_colors[$status_clean])) {
                return $job_colors[$status_clean];
            }
            
            // Also check for partial matches
            foreach ($job_colors as $key => $color) {
                if (strpos($status_clean, $key) !== false) {
                    return $color;
                }
            }
            
            // Default job color (blue)
            return 'bg-primary';
        } else {
            // Estimates always yellow
            return 'bg-warning';
        }
    }

    // Add AJAX handler for frontend calendar
    function wcrb_frontend_calendar_no_priv() {
        wp_send_json_error(array(
            'message' => 'You must be logged in to view the calendar.'
        ));
    }

    private function prepare_calendar_event( $post_id, $post_type, $post_status, $event_date, $date_field, $is_frontend = false ) {
        // Get job/estimate number
        $case_number = get_post_meta( $post_id, '_case_number', true );
        $customer_id = get_post_meta( $post_id, '_customer', true );
        
        $customer_name = esc_html__( 'Unknown', 'computer-repair-shop' );
        if ($customer_id) {
            $user = get_user_by('id', $customer_id);
            if ($user) {
                $customer_name = trim( $user->first_name . ' ' . $user->last_name );
                if ( empty( $customer_name ) ) {
                    $customer_name = $user->display_name;
                }
                $customer_name = esc_html( $customer_name );
            }
        }
        
        // Get status with proper mapping
        $status = get_post_meta($post_id, '_wc_order_status_label', true);
        if (empty($status)) {
            $status = ($post_type === 'rep_estimates') ? 
                esc_html__('Estimate', 'computer-repair-shop') : 
                esc_html__('Job', 'computer-repair-shop');
        } else {
            // Map to your default statuses if needed
            $status_lower = strtolower($status);
            $status_mapping = array(
                'new order' => esc_html__('New Order', 'computer-repair-shop'),
                'quote' => esc_html__('Quote', 'computer-repair-shop'),
                'cancelled' => esc_html__('Cancelled', 'computer-repair-shop'),
                'in process' => esc_html__('In Process', 'computer-repair-shop'),
                'in service' => esc_html__('In Service', 'computer-repair-shop'),
                'ready/complete' => esc_html__('Ready/Complete', 'computer-repair-shop'),
                'delivered' => esc_html__('Delivered', 'computer-repair-shop')
            );
            
            // Use mapped status if exists, otherwise use original
            if (isset($status_mapping[$status_lower])) {
                $status = $status_mapping[$status_lower];
            } else {
                $status = esc_html($status);
            }
        }
        
        // Build title with formatted job number for jobs
        if ($post_type === 'rep_estimates') {
            // Keep estimate number as post ID
            $title = sprintf(
                esc_html__('Estimate #%d - %s', 'computer-repair-shop'),
                $post_id,
                $customer_name
            );
        } else {
            // Use formatted job number for jobs
            $jobs_manager = WCRB_JOBS_MANAGER::getInstance();
            $job_data = $jobs_manager->get_job_display_data($post_id);
            $formatted_job_number = (!empty($job_data['formatted_job_number'])) ? 
                '#' . $job_data['formatted_job_number'] : 
                '#' . $post_id;
            
            $title = sprintf(
                esc_html__('Job %s - %s', 'computer-repair-shop'),
                $formatted_job_number,
                $customer_name
            );
        }
        
        // Get color class
        $color_class = $this->get_event_color_class($status, ($post_type === 'rep_estimates' ? 'estimate' : 'job'));
        
        // Determine URL based on frontend or backend
        $url = admin_url('post.php?post=' . $post_id . '&action=edit');
        
        // Build tooltip with translations
        $tooltip_parts = [];
        
        // Add case number
        if ($case_number) {
            $tooltip_parts[] = sprintf(
                esc_html__('Case: %s', 'computer-repair-shop'),
                esc_html($case_number)
            );
        } else {
            $tooltip_parts[] = esc_html__('Case: N/A', 'computer-repair-shop');
        }
        
        // Add customer
        $tooltip_parts[] = sprintf(
            esc_html__('Customer: %s', 'computer-repair-shop'),
            $customer_name
        );
        
        // Add status
        $tooltip_parts[] = sprintf(
            esc_html__('Status: %s', 'computer-repair-shop'),
            $status
        );
        
        // Add date field info
        if ($date_field === 'post_date') {
            $tooltip_parts[] = esc_html__('Date Field: Creation Date', 'computer-repair-shop');
        } else {
            $date_field_label = '';
            switch ($date_field) {
                case 'pickup_date':
                    $date_field_label = wcrb_get_label('pickup_date', 'none');
                    break;
                case 'delivery_date':
                    $date_field_label = wcrb_get_label('delivery_date', 'none');
                    break;
                case 'next_service_date':
                    $date_field_label = wcrb_get_label('nextservice_date', 'none');
                    break;
                default:
                    $date_field_label = ucfirst(str_replace('_', ' ', $date_field));
            }
            $tooltip_parts[] = sprintf(
                esc_html__('Date Field: %s', 'computer-repair-shop'),
                $date_field_label
            );
        }
        
        // Join tooltip parts with line breaks for better display
        $tooltip = implode(' | ', $tooltip_parts);
        
        return array(
            'id' => $post_id,
            'title' => $title,
            'start' => $event_date,
            'url' => $url,
            'classNames' => array(
                $color_class, 
                ($post_type === 'rep_estimates') ? 'estimate-event' : 'job-event'
            ),
            'extendedProps' => array(
                'type' => ($post_type === 'rep_estimates') ? 'estimate' : 'job',
                'type_label' => ($post_type === 'rep_estimates') ? 
                    esc_html__('Estimate', 'computer-repair-shop') : 
                    esc_html__('Job', 'computer-repair-shop'),
                'status' => $status,
                'tooltip' => $tooltip,
                'case_number' => $case_number ? esc_html($case_number) : 'N/A',
                'customer' => $customer_name,
                'post_status' => $post_status,
                'date_field' => $date_field,
                'formatted_id' => ($post_type === 'rep_estimates') ? 
                    '#' . $post_id : 
                    ($formatted_job_number ?? '#' . $post_id)
            )
        );
    }

    function wcrb_get_frontend_calendar_events() {
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => 'Authentication required.'
            ));
        }
        
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'wcrb_frontend_calendar_nonce')) {
            wp_send_json_error(array(
                'message' => 'Security verification failed.'
            ));
        }
        
        $date_field = isset($_POST['date_field']) ? sanitize_text_field($_POST['date_field']) : 'pickup_date';
        $filter     = isset($_POST['filter']) ? sanitize_text_field($_POST['filter']) : 'all';
        $start_date = isset($_POST['start']) ? sanitize_text_field($_POST['start']) : '';
        $end_date   = isset($_POST['end']) ? sanitize_text_field($_POST['end']) : '';
        
        // Get current user
        $current_user = wp_get_current_user();
        $user_id      = $current_user->ID;
        $user_roles   = (array) $current_user->roles;
        
        // Build query args - same as backend but with appropriate restrictions
        $args = array(
            'post_type'      => array('rep_jobs', 'rep_estimates'),
            'posts_per_page' => 200,
            'post_status'    => array('publish', 'pending', 'draft', 'private', 'future', 'auto-draft'),
            'orderby'        => 'ID',
            'order'          => 'DESC',
        );
        
        if ($filter === 'jobs') {
            $args['post_type'] = 'rep_jobs';
        } elseif ($filter === 'estimates') {
            $args['post_type'] = 'rep_estimates';
        }
        
        if (in_array('customer', $user_roles)) {
            $args['author'] = $current_user->ID;
        }
        
        $meta_query = array();
        
        if ($date_field !== 'post_date') {
            $meta_field = '_' . $date_field;
            
            $meta_query[] = array(
                'relation' => 'OR',
                array(
                    'relation' => 'AND',
                    array(
                        'post_status' => array('publish', 'pending', 'private', 'future')
                    ),
                    array(
                        'key' => $meta_field,
                        'compare' => 'EXISTS'
                    ),
                    array(
                        'key' => $meta_field,
                        'value' => '',
                        'compare' => '!='
                    )
                ),
                array(
                    'relation' => 'AND',
                    array(
                        'post_status' => 'draft'
                    )
                )
            );

            if (!empty($start_date) && !empty($end_date)) {
                $start_date_clean = date('Y-m-d', strtotime($start_date));
                $end_date_clean = date('Y-m-d', strtotime($end_date));
                
                $meta_query[] = array(
                    'relation' => 'OR',
                    array(
                        'relation' => 'AND',
                        array(
                            'post_status' => array('publish', 'pending', 'private', 'future')
                        ),
                        array(
                            'key' => $meta_field, // Use $meta_field (with underscore)
                            'value' => array($start_date_clean, $end_date_clean),
                            'compare' => 'BETWEEN',
                            'type' => 'DATE'
                        )
                    ),
                    array(
                        'relation' => 'AND',
                        array(
                            'post_status' => 'draft'
                        )
                    )
                );
            }
        } else {
            if (!empty($start_date) && !empty($end_date)) {
                $args['date_query'] = array(
                    array(
                        'column'    => 'post_date',
                        'after'     => $start_date,
                        'before'    => $end_date,
                        'inclusive' => true,
                    )
                );
            }
        }
        
        if ( in_array( 'technician', $user_roles ) && $filter !== 'my_assignments' ) {
            $meta_query[] = array(
                'relation' => 'OR',
                array(
                    'key' => '_technician',
                    'value' => $user_id,
                    'compare' => '='
                ),
                array(
                    'key' => '_technician',
                    'value' => $user_id,
                    'compare' => 'LIKE'
                )
            );
        }
        
        if ($filter === 'my_assignments' && (in_array('administrator', $user_roles) || in_array('store_manager', $user_roles))) {
            $meta_query[] = array(
                'relation' => 'OR',
                array(
                    'key' => '_technician',
                    'value' => $user_id,
                    'compare' => '='
                ),
                array(
                    'key' => '_technician',
                    'value' => $user_id,
                    'compare' => 'LIKE'
                )
            );
        }
        
        if ( ! empty( $meta_query ) ) {
            if ( count( $meta_query ) > 1 ) {
                $args['meta_query'] = array(
                    'relation' => 'AND',
                    $meta_query
                );
            } else {
                $args['meta_query'] = $meta_query[0];
            }
        }
        
        $query = new WP_Query( $args );
        $events = array();
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $post_id     = get_the_ID();
                $post_type   = get_post_type( $post_id );
                $post_status = get_post_status( $post_id );
                
                if ($date_field === 'post_date') {
                    $event_date = get_the_date( 'Y-m-d' );
                } else {
                    $meta_field = '_' . $date_field;
                    $event_date = get_post_meta( $post_id, $meta_field, true );
                    
                    if ( empty( $event_date ) ) {
                        if ( $post_status === 'draft' ) {
                            $event_date = get_the_modified_date( 'Y-m-d', $post_id );
                            if ( empty( $event_date ) ) {
                                $event_date = date( 'Y-m-d' );
                            }
                        } else {
                            continue;
                        }
                    }
                    
                    $timestamp = strtotime( $event_date );
                    if ( $timestamp === false ) {
                        continue;
                    }
                    $event_date = date('Y-m-d', $timestamp);
                }
                
                $event = $this->prepare_calendar_event(
                    $post_id, 
                    $post_type, 
                    $post_status, 
                    $event_date, 
                    $date_field,
                    true
                );
                $events[] = $event;
            }
        }
        wp_reset_postdata();
        wp_send_json_success( $events );
    }
    
}