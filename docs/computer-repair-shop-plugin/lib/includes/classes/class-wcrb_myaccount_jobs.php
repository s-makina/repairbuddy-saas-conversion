<?php
/**
 * Plugin Name: WCRB Blank Dashboard
 */

defined( 'ABSPATH' ) || exit;

class WCRB_DASHBOARD_JOBS {
    private static $instance = null;

    public $_user_role = '';
    public $_user_id = 0;
    public $_allowedHTML = array();
    public $_date_format = '';

    public static function getInstance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        add_action( 'init', [ $this, 'init_properties' ] );
        add_action( 'init', array( $this, 'handle_export_requests' ) );
        //add_action( 'wp_ajax_wcrb_update_job_status', [ $this, 'wcrb_update_job_status' ] );
    }

    public function init_properties() {
        $current_user = wp_get_current_user();
        $this->_user_role = $current_user->roles[0] ?? 'guest';
        $this->_user_id = $current_user->ID;
        $this->_allowedHTML = function_exists( 'wc_return_allowed_tags' ) ? wc_return_allowed_tags() : array();
        $this->_date_format = get_option( 'date_format' );
    }

    function list_jobs_card_view( $arguments = array() ) {
        if ( ! is_user_logged_in() ) {
            return array(
                'rows' => esc_html__( 'You are not logged in.', 'computer-repair-shop' ),
                'pagination' => ''
            );
        }

        $_mainpage = get_queried_object_id();
        $WCRB_DASHBOARD = WCRB_MYACCOUNT_DASHBOARD::getInstance();

        $loadAllJobs = 'NO';

        //hi
        if ( 'customer' === $this->_user_role ) {
            $user_role_string = '_customer';
        } elseif ( 'technician' === $this->_user_role ) {
            $user_role_string = '_technician';
        } elseif ( 'administrator' === $this->_user_role || 'store_manager' === $this->_user_role ) {
            $user_role_string = '_technician';
            $loadAllJobs = 'YES';
        } else {
            $user_role_string = '_customer';
        }

        if ( $user_role_string == '_technician' ) {
            // Check if _technician stores arrays or single values
            $meta_query_b = array(
                'relation' => 'OR',
                // For single technician IDs
                array(
                    'key' 		=> '_technician',
                    'value'    	=> $this->_user_id,
                    'compare' 	=> 'IN',
                    'type'    	=> 'NUMERIC',  
                ),
                // For serialized arrays
                array(
                    'key' 		=> '_technician',
                    'value'    	=> $this->_user_id,
                    'compare' 	=> 'REGEXP',  // More reliable than LIKE for arrays
                    'type'    	=> 'CHAR',  
                )
            );
        } else {
            $meta_query_b = array(
                'key'     => $user_role_string,
                'value'   => $this->_user_id,
                'compare' => '=',
            );
        }
        
        $meta_query_b = ( $loadAllJobs == 'YES' ) ? array() : $meta_query_b;

        // Pagination setup
        $jobs_per_page = 12; // More cards per page since they take less space
        $current_page = isset( $_GET['jobs_page'] ) ? max( 1, intval( $_GET['jobs_page'] ) ) : 1;
        $offset = ( $current_page - 1 ) * $jobs_per_page;

        // Initialize meta query array
        $meta_query_arr = array();

        // Only add user role filter if not loading all jobs
        if ( $loadAllJobs != 'YES' ) {
            $meta_query_arr[] = $meta_query_b;
        }

        // Apply all the same filters as list_jobs()
        // Filter: Job Status
        if ( isset( $_GET["job_status"] ) && ! empty( $_GET["job_status"] ) && $_GET["job_status"] != 'all' ) {
            $meta_query_arr[] = array(
                'key'     => '_wc_order_status',
                'value'   => sanitize_text_field( $_GET['job_status'] ),
                'compare' => '=',
            );
        }

        // Filter: Store
        if ( isset( $_GET["wc_store"] ) && ! empty( $_GET["wc_store"] ) && $_GET["wc_store"] != 'all' ) {
            $meta_query_arr[] = array(
                'key'     => '_store_id',
                'value'   => sanitize_text_field( $_GET['wc_store'] ),
                'compare' => '=',
            );
        }

        // Filter: Payment Status
        if ( isset( $_GET["wc_payment_status"] ) && ! empty( $_GET["wc_payment_status"] ) && $_GET["wc_payment_status"] != 'all' ) {
            $meta_query_arr[] = array(
                'key'     => '_wc_payment_status',
                'value'   => sanitize_text_field( $_GET['wc_payment_status'] ),
                'compare' => '=',
            );
        }

        // Filter: Customer
        if ( isset( $_GET["job_customer"] ) && ! empty( $_GET["job_customer"] ) && $_GET["job_customer"] != 'all' ) {
            $meta_query_arr[] = array(
                'key'     => '_customer',
                'value'   => intval( $_GET['job_customer'] ),
                'compare' => '=',
            );
        }

        // Filter: Priority
        if ( isset( $_GET["wc_job_priority"] ) && ! empty( $_GET["wc_job_priority"] ) && $_GET["wc_job_priority"] != 'all' ) {
            $meta_query_arr[] = array(
                'key'     => '_wc_job_priority',
                'value'   => sanitize_text_field( $_GET['wc_job_priority'] ),
                'compare' => '=',
            );
        }

        // Filter: Technician
        if ( isset( $_GET["job_technician"] ) && ! empty( $_GET["job_technician"] ) && $_GET["job_technician"] != 'all' ) {
            $meta_query_arr[] = array(
                'key'     => '_technician',
                'value'   => intval( $_GET['job_technician'] ),
                'compare' => '=',
            );
        }

        // Filter: Device Post ID
        if ( isset( $_GET["device_post_id"] ) && ! empty( $_GET["device_post_id"] ) && $_GET["device_post_id"] != 'All' ) {
            $device_post_id = sanitize_text_field( $_GET['device_post_id'] );
            $meta_query_arr[] = array(
                'key'     => '_wc_device_data',
                'value'   => '"device_post_id";s:' . strlen($device_post_id) . ':"' . $device_post_id . '"',
                'compare' => 'LIKE',
            );
        }

        // Global Search Functionality
        if ( isset( $_GET['searchinput'] ) && ! empty( $_GET['searchinput'] ) ) {
            $search_term = sanitize_text_field( $_GET['searchinput'] );
            
            $search_meta_query = array(
                'relation' => 'OR',
                array(
                    'key' => '_case_number',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_wc_device_data',
                    'value' => sprintf( ':"%s";', $search_term ),
                    'compare' => 'RLIKE',
                    'type'    => 'CHAR',
                ),
                array(
                    'key' => '_case_detail',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_wc_order_note',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_wc_job_priority',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_customer_label',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_wc_order_status_label',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_wc_payment_status_label',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_wcrb_job_id',
                    'value' => ltrim( $search_term, '0' ),
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_order_id',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
            );
            
            if ( $this->is_date_like( $search_term ) ) {
                $search_meta_query[] = array(
                    'key' => '_pickup_date',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                );
                $search_meta_query[] = array(
                    'key' => '_next_service_date',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                );
                $search_meta_query[] = array(
                    'key' => '_delivery_date',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                );
            }
            
            if ( ! empty( $meta_query_arr ) ) {
                $meta_query_arr = array(
                    'relation' => 'AND',
                    $meta_query_arr,
                    $search_meta_query
                );
            } else {
                $meta_query_arr = $search_meta_query;
            }
        }

        // WordPress Query for Rep Jobs
        $jobs_args = array(
            'post_type'      => 'rep_jobs',
            'orderby'        => 'id',
            'order'          => 'DESC',
            'posts_per_page' => $jobs_per_page,
            'offset'         => $offset,
            'post_status'    => array( 'publish', 'pending', 'draft', 'private', 'future' ),
        );

        // Add meta query only if we have meta queries
        if ( ! empty( $meta_query_arr ) ) {
            $jobs_args['meta_query'] = $meta_query_arr;
        }

        $jobs_query = new WP_Query( $jobs_args );
        $cards_content = '';
        $pagination_content = '';

        if ( $jobs_query->have_posts() ) :
            $cards_content .= '<div class="row g-3 p-3">';
            
            while( $jobs_query->have_posts() ) :
                $jobs_query->the_post();

                $jobs_manager    = WCRB_JOBS_MANAGER::getInstance();
                $job_data        = $jobs_manager->get_job_display_data( $jobs_query->post->ID );
                $_job_id         = ( ! empty( $job_data['formatted_job_number'] ) ) ? $job_data['formatted_job_number'] : $jobs_query->post->ID;

                $job_id          = $jobs_query->post->ID;
                $_customer_id    = get_post_meta( $job_id, '_customer', true );
                $case_number     = get_post_meta( $job_id, '_case_number', true ); 
                $wc_payment_status = get_post_meta( $job_id, '_wc_payment_status', true );
                $payment_status  = get_post_meta( $job_id, '_wc_payment_status_label', true );
                $job_status      = get_post_meta( $job_id, '_wc_order_status_label', true );
                $order_total     = wc_order_grand_total( $job_id, 'grand_total' );
                $theBalance      = wc_order_grand_total( $job_id, 'balance' );
                $order_total_fmt = wc_cr_currency_format( $order_total );
                $technician      = get_post_meta( $job_id, '_technician', true );
                $current_devices = get_post_meta( $job_id, '_wc_device_data', true );
                $delivery_date   = get_post_meta( $job_id, '_delivery_date', true );
                $pickup_date     = get_post_meta( $job_id, '_pickup_date', true );
                $next_service_date = get_post_meta( $job_id, '_next_service_date', true );
                $wc_job_priority = get_post_meta( $job_id, '_wc_job_priority', true );

                // Edit Link from front end. $_edit_link = add_query_arg( array( 'screen' => 'edit-job', 'job_id' => $job_id ), get_the_permalink( $_mainpage ) );
                $_edit_link = admin_url('post.php?post=' . $job_id . '&action=edit');
                $_edit_link = ( $user_role_string == '_technician' ) ? $_edit_link : add_query_arg( array( 'addms_viewjob' => 'yes', 'wc_case_number' => $case_number, 'screen' => 'print-screen', 'job_id' => $job_id, 'order_id' => $job_id, 'data-security' => wp_create_nonce( 'wcrb_nonce_printscreen' ), 'template' => 'customer_job' ), get_the_permalink( $_mainpage ) );

                $_print_invoice = add_query_arg( array( 'wc_case_number' => $case_number, 'screen' => 'print-screen', 'job_id' => $job_id, 'data-security' => wp_create_nonce( 'wcrb_nonce_printscreen' ), 'template' => 'print-invoice' ), get_the_permalink( $_mainpage ) );

                // Format customer info
                $customer_name = "";
                if ( ! empty( $_customer_id ) ) {
                    $user = get_user_by( 'id', $_customer_id );
                    $first_name = empty($user->first_name)? "" : $user->first_name;
                    $last_name = empty($user->last_name)? "" : $user->last_name;
                    $customer_name = $first_name . ' ' . $last_name;
                }

                // Format devices
                $primary_device = "";
                $device_icon = "bi-laptop"; // Default icon
                if ( ! empty( $current_devices ) && is_array( $current_devices ) ) {
                    $first_device = reset( $current_devices );
                    $device_post_id = isset( $first_device['device_post_id'] ) ? $first_device['device_post_id'] : '';
                    $primary_device = return_device_label( $device_post_id );
                    
                    // Determine icon based on device type
                    if ( stripos( $primary_device, 'phone' ) !== false || stripos( $primary_device, 'iphone' ) !== false ) {
                        $device_icon = "bi-phone";
                    } elseif ( stripos( $primary_device, 'tablet' ) !== false || stripos( $primary_device, 'ipad' ) !== false ) {
                        $device_icon = "bi-tablet";
                    } elseif ( stripos( $primary_device, 'desktop' ) !== false || stripos( $primary_device, 'pc' ) !== false ) {
                        $device_icon = "bi-pc";
                    } elseif ( stripos( $primary_device, 'printer' ) !== false ) {
                        $device_icon = "bi-printer";
                    }
                }

                // Format status badge
                $status_badge_class = "bg-secondary";
                switch( $wc_payment_status ) {
                    case 'completed':
                    case 'paid':
                        $status_badge_class = "bg-success";
                        break;
                    case 'pending':
                    case 'processing':
                        $status_badge_class = "bg-warning";
                        break;
                    case 'cancelled':
                    case 'refunded':
                        $status_badge_class = "bg-danger";
                        break;
                    case 'onhold':
                        $status_badge_class = "bg-info";
                        break;
                }

                // Format priority badge
                $priority_badge_class = "bg-secondary";
                switch( $wc_job_priority ) {
                    case 'high':
                        $priority_badge_class = "bg-danger";
                        break;
                    case 'medium':
                        $priority_badge_class = "bg-warning";
                        break;
                    case 'low':
                        $priority_badge_class = "bg-success";
                        break;
                }

                $cards_content .= '<div class="col-xl-3 col-lg-4 col-md-6">';
                $cards_content .= '<div class="card h-100 job-card border">';
                
                // Card Header
                $cards_content .= '<div class="card-header d-flex justify-content-between align-items-center py-2">';
                $cards_content .= '<strong class="text-primary">' . esc_html( $_job_id ) . '</strong>';
                $cards_content .= '<span class="badge ' . esc_attr( $status_badge_class ) . '">' . esc_html( $job_status ) . '</span>';
                $cards_content .= '</div>';
                
                // Card Body
                $cards_content .= '<div class="card-body">';
                
                // Device Info
                $cards_content .= '<div class="d-flex align-items-start mb-3">';
                $cards_content .= '<span class="device-icon me-3">';
                $cards_content .= '<i class="bi ' . esc_attr( $device_icon ) . ' display-6 text-primary"></i>';
                $cards_content .= '</span>';
                $cards_content .= '<div>';
                $cards_content .= '<h6 class="card-title mb-1">' . esc_html( $primary_device ?: 'No Device' ) . '</h6>';
                $cards_content .= '<p class="text-muted small mb-0">' . esc_html( $case_number ) . '</p>';
                $cards_content .= '</div>';
                $cards_content .= '</div>';
                
                // Job Meta
                $cards_content .= '<div class="job-meta">';
                $cards_content .= '<div class="d-flex justify-content-between mb-2">';
                $cards_content .= '<span class="text-muted">' . esc_html__( 'Customer', 'computer-repair-shop' ) . ':</span>';
                $cards_content .= '<span class="fw-semibold text-truncate ms-2" style="max-width: 120px;">' . esc_html( $customer_name ) . '</span>';
                $cards_content .= '</div>';
                
                $cards_content .= '<div class="d-flex justify-content-between mb-2">';
                $cards_content .= '<span class="text-muted">' . esc_html__( 'Priority', 'computer-repair-shop' ) . ':</span>';
                $cards_content .= '<span class="badge ' . esc_attr( $priority_badge_class ) . '">' . esc_html( ucfirst( $wc_job_priority ) ) . '</span>';
                $cards_content .= '</div>';
                
                $cards_content .= '<div class="d-flex justify-content-between mb-2">';
                $cards_content .= '<span class="text-muted">' . esc_html__( 'Total', 'computer-repair-shop' ) . ':</span>';
                $cards_content .= '<span class="fw-semibold">' . esc_html( $order_total_fmt ) . '</span>';
                $cards_content .= '</div>';
                
                if ( ! empty( $delivery_date ) ) {
                    $delivery_date_fmt = date_i18n( $this->_date_format, strtotime( $delivery_date ) );
                    $cards_content .= '<div class="d-flex justify-content-between">';
                    $cards_content .= '<span class="text-muted">' . esc_html__( 'Due', 'computer-repair-shop' ) . ':</span>';
                    $cards_content .= '<span class="fw-semibold">' . esc_html( $delivery_date_fmt ) . '</span>';
                    $cards_content .= '</div>';
                }
                $cards_content .= '</div>'; // .job-meta
                
                $cards_content .= '</div>'; // .card-body
                
                // Card Footer
                $cards_content .= '<div class="card-footer bg-transparent border-top-0 pt-0">';
                $cards_content .= '<div class="btn-group w-100">';
                $cards_content .= '<a href="' . esc_url( $_edit_link ) . '" class="btn btn-outline-primary btn-sm">';
                $cards_content .= '<i class="bi bi-eye me-1"></i>' . esc_html__( 'View', 'computer-repair-shop' );
                $cards_content .= '</a>';
                $cards_content .= '<a href="' . esc_url( $_edit_link ) . '" class="btn btn-outline-secondary btn-sm">';
                $cards_content .= '<i class="bi bi-pencil me-1"></i>' . esc_html__( 'Edit', 'computer-repair-shop' );
                $cards_content .= '</a>';
                $cards_content .= '<a href="' . esc_url( $_print_invoice ) . '" target="_blank" class="btn btn-outline-info btn-sm">';
                $cards_content .= '<i class="bi bi-printer me-1"></i>';
                $cards_content .= '</a>';
                $cards_content .= '</div>';
                $cards_content .= '</div>'; // .card-footer
                
                $cards_content .= '</div>'; // .card
                $cards_content .= '</div>'; // .col

            endwhile;
            
            $cards_content .= '</div>'; // .row
        else:
            $cards_content .= '<div class="col-12 text-center py-5">';
            $cards_content .= '<i class="bi bi-inbox display-1 text-muted"></i>';
            $cards_content .= '<h4 class="text-muted mt-3">' . esc_html__( 'No jobs found!', 'computer-repair-shop' ) . '</h4>';
            $cards_content .= '</div>';
        endif;

        wp_reset_postdata();

        // Pagination data (same as table view)
        $total_jobs = $jobs_query->found_posts;
        $total_pages = ceil( $total_jobs / $jobs_per_page );

        // Showing text
        $showing_start = $offset + 1;
        $showing_end = min( $offset + $jobs_per_page, $total_jobs );

        $current_url = add_query_arg( $_GET, get_the_permalink() );
        
        // Generate pagination HTML (same as table view)
        $pagination_content = '<div class="card-footer">';
        $pagination_content .= '<div class="d-flex justify-content-between align-items-center">';
        $pagination_content .= '<div class="text-muted">';
        $pagination_content .= sprintf( 
            esc_html__( 'Showing %1$s to %2$s of %3$s jobs', 'computer-repair-shop' ),
            $showing_start,
            $showing_end,
            $total_jobs
        );
        $pagination_content .= '</div>';
        
        if ( $total_pages > 1 ) {
            $pagination_content .= '<nav><ul class="pagination mb-0">';
            
            // Previous button
            if ( $current_page > 1 ) {
                $prev_url = add_query_arg( 'jobs_page', $current_page - 1, $current_url );
                $pagination_content .= '<li class="page-item"><a class="page-link" href="' . esc_url( $prev_url ) . '"><i class="bi bi-chevron-left"></i></a></li>';
            } else {
                $pagination_content .= '<li class="page-item disabled"><a class="page-link" href="#" tabindex="-1" aria-disabled="true"><i class="bi bi-chevron-left"></i></a></li>';
            }

            // Page numbers
            for ( $i = 1; $i <= $total_pages; $i++ ) {
                if ( $i == 1 || $i == $total_pages || ( $i >= $current_page - 2 && $i <= $current_page + 2 ) ) {
                    $page_url = add_query_arg( 'jobs_page', $i, $current_url );
                    $active_class = ( $i == $current_page ) ? ' active' : '';
                    $pagination_content .= '<li class="page-item' . $active_class . '"><a class="page-link" href="' . esc_url( $page_url ) . '">' . $i . '</a></li>';
                } elseif ( $i == $current_page - 3 || $i == $current_page + 3 ) {
                    $pagination_content .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
            }

            // Next button
            if ( $current_page < $total_pages ) {
                $next_url = add_query_arg( 'jobs_page', $current_page + 1, $current_url );
                $pagination_content .= '<li class="page-item"><a class="page-link" href="' . esc_url( $next_url ) . '"><i class="bi bi-chevron-right"></i></a></li>';
            } else {
                $pagination_content .= '<li class="page-item disabled"><a class="page-link" href="#"><i class="bi bi-chevron-right"></i></a></li>';
            }
            
            $pagination_content .= '</ul></nav>';
        }
        
        $pagination_content .= '</div>';
        $pagination_content .= '</div>';

        return array(
            'rows' => $cards_content,
            'pagination' => $pagination_content
        );
    }

    function list_jobs( $arguments = array() ) {
        if ( ! is_user_logged_in() ) {
            return array(
                'rows' => esc_html__( 'You are not logged in.', 'computer-repair-shop' ),
                'pagination' => ''
            );
        }

        $_mainpage = get_queried_object_id();
        $WCRB_DASHBOARD = WCRB_MYACCOUNT_DASHBOARD::getInstance();

        $loadAllJobs = 'NO';

        if ( 'customer' === $this->_user_role ) {
            $user_role_string = '_customer';
        } elseif ( 'technician' === $this->_user_role ) {
            $user_role_string = '_technician';
        } elseif ( 'administrator' === $this->_user_role || 'store_manager' === $this->_user_role ) {
            $user_role_string = '_technician';
            $loadAllJobs = 'YES';
        } else {
            $user_role_string = '_customer';
        }

        if ( $user_role_string == '_technician' ) {
            // Check if _technician stores arrays or single values
            $meta_query_b = array(
                'relation' => 'OR',
                // For single technician IDs
                array(
                    'key' 		=> '_technician',
                    'value'    	=> $this->_user_id,
                    'compare' 	=> 'IN',
                    'type'    	=> 'NUMERIC',  
                ),
                // For serialized arrays
                array(
                    'key' 		=> '_technician',
                    'value'    	=> $this->_user_id,
                    'compare' 	=> 'REGEXP',  // More reliable than LIKE for arrays
                    'type'    	=> 'CHAR',  
                )
            );
        } else {
            $meta_query_b = array(
                'key'     => $user_role_string,
                'value'   => $this->_user_id,
                'compare' => '=',
            );
        }
        
        $meta_query_b = ( $loadAllJobs == 'YES' ) ? array() : $meta_query_b;

        // Pagination setup
        $jobs_per_page = 10;
        $current_page = isset( $_GET['jobs_page'] ) ? max( 1, intval( $_GET['jobs_page'] ) ) : 1;
        $offset = ( $current_page - 1 ) * $jobs_per_page;

        // Initialize meta query array
        $meta_query_arr = array();

        // Only add user role filter if not loading all jobs
        if ( $loadAllJobs != 'YES' ) {
            $meta_query_arr[] = $meta_query_b;
        }

        // Filter: Job Status
        if ( isset( $_GET["job_status"] ) && ! empty( $_GET["job_status"] ) && $_GET["job_status"] != 'all' ) {
            $meta_query_arr[] = array(
                'key'     => '_wc_order_status',
                'value'   => sanitize_text_field( $_GET['job_status'] ),
                'compare' => '=',
            );
        }

        // Filter: Store
        if ( isset( $_GET["wc_store"] ) && ! empty( $_GET["wc_store"] ) && $_GET["wc_store"] != 'all' ) {
            $meta_query_arr[] = array(
                'key'     => '_store_id',
                'value'   => sanitize_text_field( $_GET['wc_store'] ),
                'compare' => '=',
            );
        }

        // Filter: Payment Status
        if ( isset( $_GET["wc_payment_status"] ) && ! empty( $_GET["wc_payment_status"] ) && $_GET["wc_payment_status"] != 'all' ) {
            $meta_query_arr[] = array(
                'key'     => '_wc_payment_status',
                'value'   => sanitize_text_field( $_GET['wc_payment_status'] ),
                'compare' => '=',
            );
        }

        // Filter: Customer
        if ( isset( $_GET["job_customer"] ) && ! empty( $_GET["job_customer"] ) && $_GET["job_customer"] != 'all' ) {
            $meta_query_arr[] = array(
                'key'     => '_customer',
                'value'   => intval( $_GET['job_customer'] ),
                'compare' => '=',
            );
        }

        //Filter: Priority
        if ( isset( $_GET["wc_job_priority"] ) && ! empty( $_GET["wc_job_priority"] ) && $_GET["wc_job_priority"] != 'all' ) {
            $meta_query_arr[] = array(
                'key'     => '_wc_job_priority',
                'value'   => sanitize_text_field( $_GET['wc_job_priority'] ),
                'compare' => '=',
            );
        }

        // Filter: Technician - Handles both single IDs and arrays
        if ( isset( $_GET["job_technician"] ) && ! empty( $_GET["job_technician"] ) && $_GET["job_technician"] != 'all' ) {
            $technician_id = intval( $_GET['job_technician'] );
            
            $meta_query_arr[] = array(
                'relation' => 'OR',
                // For single technician IDs
                array(
                    'key'     => '_technician',
                    'value'   => $technician_id,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ),
                // For serialized arrays
                array(
                    'key'     => '_technician',
                    'value'   => '"' . $technician_id . '"',
                    'compare' => 'LIKE',
                    'type'    => 'CHAR',
                )
            );
        }

        // Filter: Device Post ID
        if ( isset( $_GET["device_post_id"] ) && ! empty( $_GET["device_post_id"] ) && $_GET["device_post_id"] != 'All' ) {
            $device_post_id = sanitize_text_field( $_GET['device_post_id'] );
            $meta_query_arr[] = array(
                'key'     => '_wc_device_data',
                'value'   => '"device_post_id";s:' . strlen($device_post_id) . ':"' . $device_post_id . '"',
                'compare' => 'LIKE',
            );
        }

        // Global Search Functionality
        if ( isset( $_GET['searchinput'] ) && ! empty( $_GET['searchinput'] ) ) {
            $search_term = sanitize_text_field( $_GET['searchinput'] );
            
            // Use WordPress search for title and content
            //$jobs_args['s'] = $search_term;

            // Add meta query for additional fields including _wcrb_job_id
            $search_meta_query = array(
                'relation' => 'OR',
                array(
                    'key' => '_case_number',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' 		=> '_wc_device_data',
                    'value' 	=> sprintf( ':"%s";', $search_term ),
                    'compare' 	=> 'RLIKE',
                    'type'    	=> 'CHAR',
                ),
                array(
                    'key' => '_case_detail',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_wc_order_note',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_wc_job_priority',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_customer_label',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_wc_order_status_label',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_wc_payment_status_label',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_wc_payment_status_label',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_wcrb_job_id',
                    'value' => ltrim( $search_term, '0' ),
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_order_id',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
            );
            
            // Add date fields search if the term looks like a date
            if ( $this->is_date_like( $search_term ) ) {
                $search_meta_query[] = array(
                    'key' => '_pickup_date',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                );
                $search_meta_query[] = array(
                    'key' => '_next_service_date',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                );
                $search_meta_query[] = array(
                    'key' => '_delivery_date',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                );
            }
            
            // Merge with existing meta queries
            if ( ! empty( $meta_query_arr ) ) {
                $meta_query_arr = array(
                    'relation' => 'AND',
                    $meta_query_arr,
                    $search_meta_query
                );
            } else {
                $meta_query_arr = $search_meta_query;
            }
        }

        // WordPress Query for Rep Jobs
        $jobs_args = array(
            'post_type'      => 'rep_jobs',
            'orderby'        => 'id',
            'order'          => 'DESC',
            'posts_per_page' => $jobs_per_page,
            'offset'         => $offset,
            'post_status'    => array( 'publish', 'pending', 'draft', 'private', 'future' ),
        );

        // Add meta query only if we have meta queries
        if ( ! empty( $meta_query_arr ) ) {
            $jobs_args['meta_query'] = $meta_query_arr;
        }

        $jobs_query = new WP_Query( $jobs_args );

        $rows_content = '';
        $pagination_content = '';

        if ( $jobs_query->have_posts() ) :
            while( $jobs_query->have_posts() ) :
                $jobs_query->the_post();

                $jobs_manager    = WCRB_JOBS_MANAGER::getInstance();
                $job_data 	     = $jobs_manager->get_job_display_data( $jobs_query->post->ID );
                $_job_id  	     = ( ! empty( $job_data['formatted_job_number'] ) ) ? $job_data['formatted_job_number'] : $jobs_query->post->ID;

                $job_id          = $jobs_query->post->ID;
                $_customer_id    = get_post_meta( $job_id, '_customer', true );
                $case_number     = get_post_meta( $job_id, '_case_number', true ); 
                $order_date      = get_the_date( '', $job_id );
                $wc_payment_status = get_post_meta( $job_id, '_wc_payment_status', true );
                $payment_status  = get_post_meta( $job_id, '_wc_payment_status_label', true );
                $job_status      = get_post_meta( $job_id, '_wc_order_status_label', true );
                $order_total     = wc_order_grand_total( $job_id, 'grand_total' );
                $theBalance      = wc_order_grand_total( $job_id, 'balance' );
                $order_total     = wc_cr_currency_format( $order_total );
				$current_devices   = get_post_meta( $job_id, '_wc_device_data', true );
                $delivery_date     = get_post_meta( $job_id, '_delivery_date', true );
                $pickup_date 	   = get_post_meta( $job_id, '_pickup_date', true );
                $next_service_date = get_post_meta( $job_id, '_next_service_date', true );
                $wc_order_status   = get_post_meta( $job_id, '_wc_order_status', true );


                // Edit Link from front end. $_edit_link        = add_query_arg( array( 'screen' => 'edit-job', 'job_id' => $job_id ), get_the_permalink( $_mainpage ) );
                $_edit_link = admin_url('post.php?post=' . $job_id . '&action=edit');

                $_edit_link = ( $user_role_string == '_technician' ) ? $_edit_link : add_query_arg( array( 'addms_viewjob' => 'yes', 'wc_case_number' => $case_number, 'screen' => 'print-screen', 'job_id' => $job_id, 'order_id' => $job_id, 'data-security' => wp_create_nonce( 'wcrb_nonce_printscreen' ), 'template' => 'customer_job' ), get_the_permalink( $_mainpage ) );

                $_print_invoice    = add_query_arg( array( 'wc_case_number' => $case_number, 'screen' => 'print-screen', 'job_id' => $job_id, 'data-security' => wp_create_nonce( 'wcrb_nonce_printscreen' ), 'template' => 'print-invoice' ), get_the_permalink( $_mainpage ) );
                $_email_invoice    = add_query_arg( array( 'email_customer' => 'yes', 'wc_case_number' => $case_number, 'screen' => 'print-screen', 'job_id' => $job_id, 'data-security' => wp_create_nonce( 'wcrb_nonce_printscreen' ), 'template' => 'print-invoice' ), get_the_permalink( $_mainpage ) );
                $_download_invoice = add_query_arg( array( 'dl_pdf' => 'yes', 'wc_case_number' => $case_number, 'screen' => 'print-screen', 'job_id' => $job_id, 'data-security' => wp_create_nonce( 'wcrb_nonce_printscreen' ), 'template' => 'print-invoice' ), get_the_permalink( $_mainpage ) );

                $tech_name = "";
                $WCRB_TIME_MANAGEMENT = WCRB_TIME_MANAGEMENT::getInstance();
                $_technician = $WCRB_TIME_MANAGEMENT->return_technician_names( $job_id );

                if ( ! empty( $_technician ) ) {
                    $tech_name = esc_html( $_technician );
                }

                $_customercontent = "";
                if ( ! empty( $_customer_id ) ) {
					$user 			= get_user_by( 'id', $_customer_id );
					$phone_number 	= get_user_meta( $_customer_id, "billing_phone", true );
					$billing_tax 	= get_user_meta( $_customer_id, "billing_tax", true );
					$company 		= get_user_meta( $_customer_id, "billing_company", true );
					
					$first_name		= empty($user->first_name)? "" : $user->first_name;
					$last_name 		= empty($user->last_name)? "" : $user->last_name;
					$theFullName 	= $first_name. ' ' .$last_name;
					$email 			= empty( $user->user_email ) ? "" : $user->user_email;
					$_customercontent .= esc_html( $theFullName );

					if(!empty($phone_number)) {
						$_customercontent .= "<br><strong>". esc_html__( "P", "computer-repair-shop" ) . "</strong>: ".esc_html( $phone_number );
					}
					if ( ! empty( $email ) ) {
						$_customercontent .= "<br><strong>" . esc_html__( "E", "computer-repair-shop" )."</strong>: ".esc_html( $email );	
					}
					if ( ! empty( $company ) ) {
						$_customercontent .= "<br><strong>" . esc_html__( "Company", "computer-repair-shop" ) . "</strong>: " . esc_html( $company );	
					}
					if ( ! empty( $billing_tax ) ) {
						$_customercontent .= "<br><strong>" . esc_html__( "Tax ID", "computer-repair-shop" ) . "</strong>: " . esc_html( $billing_tax );	
					}
				}

				$_devices = '';
				if ( ! empty( $current_devices )  && is_array( $current_devices ) ) {
					$counter = 0;
					foreach( $current_devices as $device_data ) {
						$_devices .= ( $counter != 0 ) ? '<br>' : '';				
						$device_post_id = ( isset( $device_data['device_post_id'] ) ) ? $device_data['device_post_id'] : '';
						$device_id      = ( isset( $device_data['device_id'] ) ) ? $device_data['device_id'] : '';
		
						$_devices .= return_device_label( $device_post_id );
						$_devices .= ( ! empty ( $device_id ) ) ? ' (' . esc_html( $device_id ) . ')' : '';
						$counter++;
					}
				}
				
                $_date_tooltip = '';
                if ( ! empty( $pickup_date ) ) {
                    $pickup_date = date_i18n( $this->_date_format, strtotime( $pickup_date ) );
                    $_date_tooltip .= 'P: = ' . esc_html( $WCRB_DASHBOARD->_pickup_date_label ) . ' ';
                }
                if ( ! empty( $delivery_date ) ) {
					$delivery_date = date_i18n( $this->_date_format, strtotime( $delivery_date ) );
                    $_date_tooltip .= 'D: = ' . esc_html( $WCRB_DASHBOARD->_delivery_date_label ) . ' ';
				}
                if ( ! empty( $next_service_date ) ) {
                    $next_service_date = date_i18n( $this->_date_format, strtotime( $next_service_date ) );
                    $_date_tooltip .= 'N: = ' . esc_html( $WCRB_DASHBOARD->_nextservice_date_label ) . ' ';
                }

                $rows_content .= '<tr class="job_id_'. esc_attr( $job_id ) .' job_status_'. esc_attr( $job_status ) .'">';
                $rows_content .= '<td  class="ps-4" data-label="'. esc_html__( "ID", "computer-repair-shop" ) .'"><a href="'. esc_url( $_edit_link ) .'" target="_blank"><strong>'. esc_html( $_job_id ) .'</a></strong></th>';
                $rows_content .= '<td data-label="'. esc_html( wcrb_get_label( 'casenumber', 'first' ) ) .'/'. esc_html__( 'Tech', 'computer-repair-shop' ) .'">'
                                    . '<a href="'. esc_url( $_edit_link ) .'" target="_blank">' . esc_html( $case_number ) . '</a>' . ( ! empty( $tech_name ) ? '<br><strong class="text-primary">' . esc_html__( 'Tech', 'computer-repair-shop' ) . ': ' . esc_html( $tech_name ) .'</strong>' : '' ) .
                                 '</td>';
                $rows_content .= '<td data-label="'. esc_html__( 'Customer', 'computer-repair-shop' ) .'">' . wp_kses( $_customercontent, $this->_allowedHTML ) . '</td>';
                $rows_content .= '<td data-label="'. esc_html( $WCRB_DASHBOARD->_device_label_plural ) .'">
                                        ' . wp_kses( $_devices, $this->_allowedHTML ) . '
                                   </td>';

                $rows_content .= '<td ' . ( ! empty( $_date_tooltip ) ? 'data-bs-toggle="tooltip" data-bs-title="' . esc_html( $_date_tooltip ) . '"' : '' ) . ' data-label="' . esc_html__( 'Dates', 'computer-repair-shop' ) . '">';
                $rows_content .= ( ! empty( $pickup_date ) ) ? '<strong>'. esc_html__( 'P', 'computer-repair-shop' ) .'</strong>:'. esc_html( $pickup_date ) : '';
                $rows_content .= ( ! empty( $delivery_date ) ) ? '<br><strong>'. esc_html__( 'D', 'computer-repair-shop' ) .'</strong>:'. esc_html( $delivery_date ) : '';
                $rows_content .= ( ! empty( $next_service_date ) ) ? '<br><strong>'. esc_html__( 'N', 'computer-repair-shop' ) .'</strong>:'. esc_html( $next_service_date ) : '';
                $rows_content .= '</td>';

                $rows_content .= '<td data-label="'. esc_html__( 'Total', 'computer-repair-shop' ) .'">
                                    <strong>' . esc_html( $order_total ) . '</strong>
                                  </td>';
                
                $theClass = ( $theBalance > 0 ) ? 'p-2 text-success-emphasis bg-success-subtle border border-success-subtle rounded-3' : 'p-2 text-primary-emphasis bg-primary-subtle border border-primary-subtle rounded-3';
				$thePrice = '<span class="' . esc_attr( $theClass ) . '">' . wc_cr_currency_format( $theBalance ) . '</span>';

                $rows_content .= '<td class="gap-3 p-3" data-label="'. esc_html__( 'Balance', 'computer-repair-shop' ) .'">
                                    '. wp_kses( $thePrice, $this->_allowedHTML ) .'
                                  </td>';
                
                $payment_status = $GLOBALS['PAYMENT_STATUS_OBJ']->wc_generate_payment_status_array( 'all' );
				$wc_payment_status = empty ( $wc_payment_status ) ? "nostatus" : $wc_payment_status;

                $rows_content .= '<td data-label="'. esc_html__( 'Payment', 'computer-repair-shop' ) .'">
                                    '. esc_html( isset( $payment_status[ $wc_payment_status ] ) ? $payment_status[ $wc_payment_status ] : $wc_payment_status ) .'
                                  </td>';

                $rows_content .= '<td data-label="'. esc_html__( 'Status', 'computer-repair-shop' ) .'">
                                    ' . wp_kses( $WCRB_DASHBOARD->wc_generate_status_dropdown( $job_id, $this->_user_role ), $this->_allowedHTML ) . '
                                  </td>';
                
                $rows_content .= '<td data-label="'. esc_html__( 'Priority', 'computer-repair-shop' ) .'">
                                    ' . wp_kses( wcrb_job_priority_options( 'dropdown', $job_id, 'normal' ), $this->_allowedHTML ) . '
                                  </td>
                                  
                                  <td data-label="'. esc_html__( 'Actions', 'computer-repair-shop' ) .'" class="text-end pe-4">
                                    <div class="dropdown">
                                        <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-gear me-1"></i> '. esc_html__( 'Actions', 'computer-repair-shop' ) .'
                                        </button>
                                        <ul class="dropdown-menu shadow-sm">';

                $rows_content .=  ( $user_role_string == '_technician' ) ?        '<li>
                                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#openTakePaymentModal" recordid="'. esc_attr( $job_id ) .'" data-security="'. wp_create_nonce( 'wcrb_update_payment_nonce' ) .'">
                                                    <i class="bi bi-credit-card text-success me-2"></i>'. esc_html__( 'Take Payment', 'computer-repair-shop' ) .'
                                                </a>
                                            </li>' : '';

                $rows_content .=            '<li><a class="dropdown-item" href="'. esc_url( $_print_invoice ) .'" target="_blank">
                                                <i class="bi bi-printer text-secondary me-2"></i>'. esc_html__("Print Job Invoice", "computer-repair-shop") .'</a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="'. esc_url( $_download_invoice ) .'" target="_blank">
                                                    <i class="bi bi-file-earmark-pdf text-danger me-2"></i>'. esc_html__( 'Download PDF', 'computer-repair-shop' ) .'
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="'. esc_url( $_email_invoice ) .'" target="_blank">
                                                    <i class="bi bi-envelope text-info me-2"></i>'. ( ( $user_role_string == '_technician' ) ? esc_html__( 'Email Customer', 'computer-repair-shop' ) : esc_html__( 'Email Yourself', 'computer-repair-shop' ) ) .'
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>';
                if ( $user_role_string == '_technician' ) :                                           
                    $rows_content .= '<li>
                                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#wcrbduplicatejobfront" recordid="'. esc_attr( $job_id ) .'" data-security="'. wp_create_nonce( 'wcrb_nonce_duplicate_job' ) .'">
                                                    <i class="bi bi-files text-warning me-2"></i>'. esc_html__( 'Duplicate job', 'computer-repair-shop' ) .'
                                                </a>
                                            </li>
                                            
                                            <li><a class="dropdown-item" href="'. esc_url( $_edit_link ) .'" target="_blank"><i class="bi bi-pencil-square text-primary me-2"></i>'. esc_html__( 'Edit', 'computer-repair-shop' ) .'</a></li>';
                else :
                    $rows_content .= '<li>
                                                <a class="dropdown-item" href="'. esc_url( $_edit_link ) .'" target="_blank">
                                                    <i class="bi bi-files text-warning me-2"></i>'. esc_html__( 'Add message/files', 'computer-repair-shop' ) .'
                                                </a>
                                            </li>
                                            
                                            <li><a class="dropdown-item" href="'. esc_url( $_edit_link ) .'" target="_blank"><i class="bi bi-pencil-square text-primary me-2"></i>'. esc_html__( 'View Job', 'computer-repair-shop' ) .'</a></li>';
                endif;

                $rows_content .= '
                                        </ul>
                                    </div>
                                  </td>
                                </tr>';
            endwhile;
        else:
            $rows_content .= '<tr><td colspan="10">' . esc_html__( 'No job found!', 'computer-repair-shop' ) . '</td></tr>';
        endif;

        wp_reset_postdata();

        // Pagination data
        $total_jobs = $jobs_query->found_posts;
        $total_pages = ceil( $total_jobs / $jobs_per_page );

        // Showing text
        $showing_start = $offset + 1;
        $showing_end = min( $offset + $jobs_per_page, $total_jobs );

        $current_url = add_query_arg( $_GET, get_the_permalink() );
        
        // Generate pagination HTML
        $pagination_content = '<div class="card-footer">';
        $pagination_content .= '<div class="d-flex justify-content-between align-items-center">';
        $pagination_content .= '<div class="text-muted">';
        $pagination_content .= sprintf( 
            esc_html__( 'Showing %1$s to %2$s of %3$s jobs', 'computer-repair-shop' ),
            $showing_start,
            $showing_end,
            $total_jobs
        );
        $pagination_content .= '</div>';
        
        if ( $total_pages > 1 ) {
            $pagination_content .= '<nav><ul class="pagination mb-0">';
            
            // Previous button
            if ( $current_page > 1 ) {
                $prev_url = add_query_arg( 'jobs_page', $current_page - 1, $current_url );
                $pagination_content .= '<li class="page-item"><a class="page-link" href="' . esc_url( $prev_url ) . '"><i class="bi bi-chevron-left"></i></a></li>';
            } else {
                $pagination_content .= '<li class="page-item disabled"><a class="page-link" href="#" tabindex="-1" aria-disabled="true"><i class="bi bi-chevron-left"></i></a></li>';
            }

            // Page numbers
            for ( $i = 1; $i <= $total_pages; $i++ ) {
                if ( $i == 1 || $i == $total_pages || ( $i >= $current_page - 2 && $i <= $current_page + 2 ) ) {
                    $page_url = add_query_arg( 'jobs_page', $i, $current_url );
                    $active_class = ( $i == $current_page ) ? ' active' : '';
                    $pagination_content .= '<li class="page-item' . $active_class . '"><a class="page-link" href="' . esc_url( $page_url ) . '">' . $i . '</a></li>';
                } elseif ( $i == $current_page - 3 || $i == $current_page + 3 ) {
                    $pagination_content .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
            }

            // Next button
            if ( $current_page < $total_pages ) {
                $next_url = add_query_arg( 'jobs_page', $current_page + 1, $current_url );
                $pagination_content .= '<li class="page-item"><a class="page-link" href="' . esc_url( $next_url ) . '"><i class="bi bi-chevron-right"></i></a></li>';
            } else {
                $pagination_content .= '<li class="page-item disabled"><a class="page-link" href="#"><i class="bi bi-chevron-right"></i></a></li>';
            }
            
            $pagination_content .= '</ul></nav>';
        }
        
        $pagination_content .= '</div>';
        $pagination_content .= '</div>';

        return array(
            'rows' => $rows_content,
            'pagination' => $pagination_content
        );
    }

    function list_estimates( $arguments = array() ) {
        if ( ! is_user_logged_in() ) {
            return array(
                'rows' => esc_html__( 'You are not logged in.', 'computer-repair-shop' ),
                'pagination' => ''
            );
        }

        $_mainpage = get_queried_object_id();
        $WCRB_DASHBOARD = WCRB_MYACCOUNT_DASHBOARD::getInstance();

        $loadAllJobs = 'NO';

        if ( 'customer' === $this->_user_role ) {
            $user_role_string = '_customer';
            $_jobstatuses = array( 'publish', 'pending', 'future' );
        } elseif ( 'technician' === $this->_user_role ) {
            $user_role_string = '_technician';
            $_jobstatuses = array( 'publish', 'pending', 'draft', 'private', 'future' );
        } elseif ( 'administrator' === $this->_user_role || 'store_manager' === $this->_user_role ) {
            $user_role_string = '_technician';
            $loadAllJobs = 'YES';
            $_jobstatuses = array( 'publish', 'pending', 'draft', 'private', 'future' );
        } else {
            $user_role_string = '_customer';
            $_jobstatuses = array( 'publish', 'pending', 'future' );
        }

        if ( $user_role_string == '_technician' ) {
            // Check if _technician stores arrays or single values
            $meta_query_b = array(
                'relation' => 'OR',
                // For single technician IDs
                array(
                    'key' 		=> '_technician',
                    'value'    	=> $this->_user_id,
                    'compare' 	=> 'IN',
                    'type'    	=> 'NUMERIC',  
                ),
                // For serialized arrays
                array(
                    'key' 		=> '_technician',
                    'value'    	=> $this->_user_id,
                    'compare' 	=> 'REGEXP',  // More reliable than LIKE for arrays
                    'type'    	=> 'CHAR',  
                )
            );
        } else {
            $meta_query_b = array(
                'key'     => $user_role_string,
                'value'   => $this->_user_id,
                'compare' => '=',
            );
        }
        
        $meta_query_b = ( $loadAllJobs == 'YES' ) ? array() : $meta_query_b;

        // Pagination setup
        $jobs_per_page = 10;
        $current_page = isset( $_GET['jobs_page'] ) ? max( 1, intval( $_GET['jobs_page'] ) ) : 1;
        $offset       = ( $current_page - 1 ) * $jobs_per_page;

        // Initialize meta query array
        $meta_query_arr = array();

        // Only add user role filter if not loading all jobs
        if ( $loadAllJobs != 'YES' ) {
            $meta_query_arr[] = $meta_query_b;
        }

        // Filter: Job Status
        if ( isset( $_GET["estimate_status"] ) && ! empty( $_GET["estimate_status"] ) && $_GET["estimate_status"] != 'all' ) {
            if ( $_GET["estimate_status"] == 'pending' ) {
                $meta_query_arr[] = array(
                        'key'     => '_wc_estimate_status',
                        'compare' => 'NOT EXISTS',
                    );
            } else {
                $meta_query_arr[] = array(
                    'key'     => '_wc_estimate_status',
                    'value'   => sanitize_text_field( $_GET['estimate_status'] ),
                    'compare' => '=',
                );
            }
        }

        // Filter: Store
        if ( isset( $_GET["wc_store"] ) && ! empty( $_GET["wc_store"] ) && $_GET["wc_store"] != 'all' ) {
            $meta_query_arr[] = array(
                'key'     => '_store_id',
                'value'   => sanitize_text_field( $_GET['wc_store'] ),
                'compare' => '=',
            );
        }

        // Filter: Customer
        if ( isset( $_GET["job_customer"] ) && ! empty( $_GET["job_customer"] ) && $_GET["job_customer"] != 'all' ) {
            $meta_query_arr[] = array(
                'key'     => '_customer',
                'value'   => intval( $_GET['job_customer'] ),
                'compare' => '=',
            );
        }

        // Filter: Technician - Handles both single IDs and arrays
        if ( isset( $_GET["job_technician"] ) && ! empty( $_GET["job_technician"] ) && $_GET["job_technician"] != 'all' ) {
            $technician_id = intval( $_GET['job_technician'] );
            
            $meta_query_arr[] = array(
                'relation' => 'OR',
                // For single technician IDs
                array(
                    'key'     => '_technician',
                    'value'   => $technician_id,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ),
                // For serialized arrays
                array(
                    'key'     => '_technician',
                    'value'   => '"' . $technician_id . '"',
                    'compare' => 'LIKE',
                    'type'    => 'CHAR',
                )
            );
        }

        // Filter: Device Post ID
        if ( isset( $_GET["device_post_id"] ) && ! empty( $_GET["device_post_id"] ) && $_GET["device_post_id"] != 'All' ) {
            $device_post_id = sanitize_text_field( $_GET['device_post_id'] );
            $meta_query_arr[] = array(
                'key'     => '_wc_device_data',
                'value'   => '"device_post_id";s:' . strlen($device_post_id) . ':"' . $device_post_id . '"',
                'compare' => 'LIKE',
            );
        }

        // Global Search Functionality
        if ( isset( $_GET['searchinput'] ) && ! empty( $_GET['searchinput'] ) ) {
            $search_term = sanitize_text_field( $_GET['searchinput'] );
            
            // Use WordPress search for title and content
            //$jobs_args['s'] = $search_term;

            // Add meta query for additional fields including _wcrb_job_id
            $search_meta_query = array(
                'relation' => 'OR',
                array(
                    'key' => '_case_number',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' 		=> '_wc_device_data',
                    'value' 	=> sprintf( ':"%s";', $search_term ),
                    'compare' 	=> 'RLIKE',
                    'type'    	=> 'CHAR',
                ),
                array(
                    'key' => '_case_detail',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_wc_order_note',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_customer_label',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_wc_order_status_label',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_wc_payment_status_label',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_wcrb_job_id',
                    'value' => ltrim( $search_term, '0' ),
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_order_id',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
            );
            
            // Add date fields search if the term looks like a date
            if ( $this->is_date_like( $search_term ) ) {
                $search_meta_query[] = array(
                    'key' => '_pickup_date',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                );
                $search_meta_query[] = array(
                    'key' => '_next_service_date',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                );
                $search_meta_query[] = array(
                    'key' => '_delivery_date',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                );
            }
            
            // Merge with existing meta queries
            if ( ! empty( $meta_query_arr ) ) {
                $meta_query_arr = array(
                    'relation' => 'AND',
                    $meta_query_arr,
                    $search_meta_query
                );
            } else {
                $meta_query_arr = $search_meta_query;
            }
        }

        // WordPress Query for Rep Jobs
        $jobs_args = array(
            'post_type'      => 'rep_estimates',
            'orderby'        => 'id',
            'order'          => 'DESC',
            'posts_per_page' => $jobs_per_page,
            'offset'         => $offset,
            'post_status'    => $_jobstatuses,
        );

        // Add meta query only if we have meta queries
        if ( ! empty( $meta_query_arr ) ) {
            $jobs_args['meta_query'] = $meta_query_arr;
        }

        $jobs_query = new WP_Query( $jobs_args );

        $rows_content = '';
        $pagination_content = '';

        $selected_page = get_option( 'wc_rb_status_check_page_id' );
        $page_link     = get_the_permalink( $selected_page );

        if ( $jobs_query->have_posts() ) :
            while( $jobs_query->have_posts() ) :
                $jobs_query->the_post();

                $job_id = $_job_id = $jobs_query->post->ID;
                $_customer_id      = get_post_meta( $job_id, '_customer', true );
                $case_number       = get_post_meta( $job_id, '_case_number', true ); 
                $order_date        = get_the_date( '', $job_id );
                $wc_payment_status = get_post_meta( $job_id, '_wc_payment_status', true );
                $payment_status    = get_post_meta( $job_id, '_wc_payment_status_label', true );
                $job_status        = get_post_meta( $job_id, '_wc_order_status_label', true );
                $estimate_status   = get_post_meta( $job_id, '_wc_estimate_status', true );
                $order_total       = wc_order_grand_total( $job_id, 'grand_total' );
                $order_total       = wc_cr_currency_format( $order_total );
				$current_devices   = get_post_meta( $job_id, '_wc_device_data', true );
                $delivery_date     = get_post_meta( $job_id, '_delivery_date', true );
                $pickup_date 	   = get_post_meta( $job_id, '_pickup_date', true );
                $next_service_date = get_post_meta( $job_id, '_next_service_date', true );
                $wc_order_status   = get_post_meta( $job_id, '_wc_order_status', true );

                // Edit Link from front end. $_edit_link        = add_query_arg( array( 'screen' => 'edit-job', 'job_id' => $job_id ), get_the_permalink( $_mainpage ) );
                $_edit_link = admin_url('post.php?post=' . $job_id . '&action=edit');

                $_edit_link = ( $user_role_string == '_technician' ) ? $_edit_link : add_query_arg( array( 'addms_viewjob' => 'yes', 'wc_case_number' => $case_number, 'screen' => 'print-screen', 'job_id' => $job_id, 'order_id' => $job_id, 'data-security' => wp_create_nonce( 'wcrb_nonce_printscreen' ), 'template' => 'customer_job' ), get_the_permalink( $_mainpage ) );

                $_print_invoice    = add_query_arg( array( 'wc_case_number' => $case_number, 'screen' => 'print-screen', 'job_id' => $job_id, 'data-security' => wp_create_nonce( 'wcrb_nonce_printscreen' ), 'template' => 'print-invoice' ), get_the_permalink( $_mainpage ) );
                $_email_invoice    = add_query_arg( array( 'email_customer' => 'yes', 'wc_case_number' => $case_number, 'screen' => 'print-screen', 'job_id' => $job_id, 'data-security' => wp_create_nonce( 'wcrb_nonce_printscreen' ), 'template' => 'print-invoice' ), get_the_permalink( $_mainpage ) );
                $_download_invoice = add_query_arg( array( 'dl_pdf' => 'yes', 'wc_case_number' => $case_number, 'screen' => 'print-screen', 'job_id' => $job_id, 'data-security' => wp_create_nonce( 'wcrb_nonce_printscreen' ), 'template' => 'print-invoice' ), get_the_permalink( $_mainpage ) );

                $tech_name = "";
                $WCRB_TIME_MANAGEMENT = WCRB_TIME_MANAGEMENT::getInstance();
                $_technician = $WCRB_TIME_MANAGEMENT->return_technician_names( $job_id );

                if ( ! empty( $_technician ) ) {
                    $tech_name = esc_html( $_technician );
                }

                $_customercontent = "";
                if ( ! empty( $_customer_id ) ) {
					$user 			= get_user_by( 'id', $_customer_id );
					$phone_number 	= get_user_meta( $_customer_id, "billing_phone", true );
					$billing_tax 	= get_user_meta( $_customer_id, "billing_tax", true );
					$company 		= get_user_meta( $_customer_id, "billing_company", true );
					
					$first_name		= empty($user->first_name)? "" : $user->first_name;
					$last_name 		= empty($user->last_name)? "" : $user->last_name;
					$theFullName 	= $first_name. ' ' .$last_name;
					$email 			= empty( $user->user_email ) ? "" : $user->user_email;
					$_customercontent .= esc_html( $theFullName );

					if(!empty($phone_number)) {
						$_customercontent .= "<br><strong>". esc_html__( "P", "computer-repair-shop" ) . "</strong>: ".esc_html( $phone_number );
					}
					if ( ! empty( $email ) ) {
						$_customercontent .= "<br><strong>" . esc_html__( "E", "computer-repair-shop" )."</strong>: ".esc_html( $email );	
					}
					if ( ! empty( $company ) ) {
						$_customercontent .= "<br><strong>" . esc_html__( "Company", "computer-repair-shop" ) . "</strong>: " . esc_html( $company );	
					}
					if ( ! empty( $billing_tax ) ) {
						$_customercontent .= "<br><strong>" . esc_html__( "Tax ID", "computer-repair-shop" ) . "</strong>: " . esc_html( $billing_tax );	
					}
				}

				$_devices = '';
				if ( ! empty( $current_devices )  && is_array( $current_devices ) ) {
					$counter = 0;
					foreach( $current_devices as $device_data ) {
						$_devices .= ( $counter != 0 ) ? '<br>' : '';				
						$device_post_id = ( isset( $device_data['device_post_id'] ) ) ? $device_data['device_post_id'] : '';
						$device_id      = ( isset( $device_data['device_id'] ) ) ? $device_data['device_id'] : '';
		
						$_devices .= return_device_label( $device_post_id );
						$_devices .= ( ! empty ( $device_id ) ) ? ' (' . esc_html( $device_id ) . ')' : '';
						$counter++;
					}
				}
				
                $_date_tooltip = '';
                if ( ! empty( $pickup_date ) ) {
                    $pickup_date = date_i18n( $this->_date_format, strtotime( $pickup_date ) );
                    $_date_tooltip .= 'P: = ' . esc_html( $WCRB_DASHBOARD->_pickup_date_label ) . ' ';
                }
                if ( ! empty( $delivery_date ) ) {
					$delivery_date = date_i18n( $this->_date_format, strtotime( $delivery_date ) );
                    $_date_tooltip .= 'D: = ' . esc_html( $WCRB_DASHBOARD->_delivery_date_label ) . ' ';
				}
                if ( ! empty( $next_service_date ) ) {
                    $next_service_date = date_i18n( $this->_date_format, strtotime( $next_service_date ) );
                    $_date_tooltip .= 'N: = ' . esc_html( $WCRB_DASHBOARD->_nextservice_date_label ) . ' ';
                }

                $rows_content .= '<tr class="job_id_'. esc_attr( $job_id ) .' job_status_'. esc_attr( $job_status ) .'">';
                $rows_content .= '<td  class="ps-4" data-label="'. esc_html__( "ID", "computer-repair-shop" ) .'"><a href="'. esc_url( $_edit_link ) .'" target="_blank"><strong>'. esc_html( $_job_id ) .'</a></strong></th>';
                $rows_content .= '<td data-label="'. esc_html( wcrb_get_label( 'casenumber', 'first' ) ) .'/'. esc_html__( 'Tech', 'computer-repair-shop' ) .'">'
                                    . '<a href="'. esc_url( $_edit_link ) .'" target="_blank">' . esc_html( $case_number ) . '</a>' . ( ! empty( $tech_name ) ? '<br><strong class="text-primary">' . esc_html__( 'Tech', 'computer-repair-shop' ) . ': ' . esc_html( $tech_name ) .'</strong>' : '' ) .
                                 '</td>';
                $rows_content .= '<td data-label="'. esc_html__( 'Customer', 'computer-repair-shop' ) .'">' . wp_kses( $_customercontent, $this->_allowedHTML ) . '</td>';
                $rows_content .= '<td data-label="'. esc_html( $WCRB_DASHBOARD->_device_label_plural ) .'">
                                        ' . wp_kses( $_devices, $this->_allowedHTML ) . '
                                   </td>';

                $rows_content .= '<td ' . ( ! empty( $_date_tooltip ) ? 'data-bs-toggle="tooltip" data-bs-title="' . esc_html( $_date_tooltip ) . '"' : '' ) . ' data-label="' . esc_html__( 'Dates', 'computer-repair-shop' ) . '">';
                $rows_content .= ( ! empty( $pickup_date ) ) ? '<strong>'. esc_html__( 'P', 'computer-repair-shop' ) .'</strong>:'. esc_html( $pickup_date ) : '';
                $rows_content .= ( ! empty( $delivery_date ) ) ? '<br><strong>'. esc_html__( 'D', 'computer-repair-shop' ) .'</strong>:'. esc_html( $delivery_date ) : '';
                $rows_content .= ( ! empty( $next_service_date ) ) ? '<br><strong>'. esc_html__( 'N', 'computer-repair-shop' ) .'</strong>:'. esc_html( $next_service_date ) : '';
                $rows_content .= '</td>';

                $rows_content .= '<td data-label="'. esc_html__( 'Total', 'computer-repair-shop' ) .'">
                                    <span class="badge bg-primary bg-gradient d-inline-flex align-items-center px-3 py-2"><strong>' . esc_html( $order_total ) . '</strong></span>
                                  </td>';
                
                // Using Bootstrap Icons
                if ( $estimate_status == 'approved' ) {
                    $_colord = '<span class="badge bg-success bg-gradient d-inline-flex align-items-center px-3 py-2">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        ' . esc_html__( 'Approved', 'computer-repair-shop' ) . '
                    </span>';
                } elseif ( $estimate_status == 'rejected' ) {
                    $_colord = '<span class="badge bg-danger bg-gradient d-inline-flex align-items-center px-3 py-2">
                        <i class="bi bi-x-circle-fill me-2"></i>
                        ' . esc_html__( 'Rejected', 'computer-repair-shop' ) . '
                    </span>';
                } else {
                    $_colord = '<span class="badge bg-warning bg-gradient d-inline-flex align-items-center px-3 py-2">
                        <i class="bi bi-clock-history me-2"></i>
                        ' . esc_html__( 'Pending', 'computer-repair-shop' ) . '
                    </span>';
                }
                $rows_content .= '<td data-label="'. esc_html__( 'Status', 'computer-repair-shop' ) .'">
                                    ' . wp_kses( $_colord, $this->_allowedHTML ) . '
                                  </td>';
                
                $rows_content .= '<td data-label="'. esc_html__( 'Actions', 'computer-repair-shop' ) .'" class="text-end pe-4">
                                    <div class="dropdown">
                                        <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-gear me-1"></i> '. esc_html__( 'Actions', 'computer-repair-shop' ) .'
                                        </button>
                                        <ul class="dropdown-menu shadow-sm">';

                $rows_content .=            '<li><a class="dropdown-item" href="'. esc_url( $_print_invoice ) .'" target="_blank">
                                                <i class="bi bi-printer text-secondary me-2"></i>'. esc_html__("Print Estimate", "computer-repair-shop") .'</a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="'. esc_url( $_download_invoice ) .'" target="_blank">
                                                    <i class="bi bi-file-earmark-pdf text-danger me-2"></i>'. esc_html__( 'Download PDF', 'computer-repair-shop' ) .'
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="'. esc_url( $_email_invoice ) .'" target="_blank">
                                                    <i class="bi bi-envelope text-info me-2"></i>'. ( ( $user_role_string == '_technician' ) ? esc_html__( 'Email Customer', 'computer-repair-shop' ) : esc_html__( 'Email Yourself', 'computer-repair-shop' ) ) .'
                                                </a>
                                            </li>';
                if ( $user_role_string != '_technician' && $estimate_status != 'approved' && $estimate_status != 'rejected' ) : 
                    $case_number   = get_post_meta( $job_id, '_case_number', true );
                    $appro_params  = array( 'estimate_id' => $job_id, 'case_number' => $case_number, 'choice' => 'approved' );
                    $reje_params   = array( 'estimate_id' => $job_id, 'case_number' => $case_number, 'choice' => 'rejected' );
                    
                    $approve_url = add_query_arg( $appro_params, $page_link );
                    $reject_url = add_query_arg( $reje_params, $page_link );

                    $rows_content .= '<li><hr class="dropdown-divider"></li>';
                    $rows_content .= '<li>
                                        <a class="dropdown-item" href="'. esc_url( $approve_url ) .'" target="_blank"><i class="bi bi-files text-warning me-2"></i>'. esc_html__( 'Approve Estimate', 'computer-repair-shop' ) .'</a></li>
                                      <li>
                                        <a class="dropdown-item" href="'. esc_url( $reject_url ) .'" target="_blank"><i class="bi bi-pencil-square text-primary me-2"></i>'. esc_html__( 'Reject Estimate', 'computer-repair-shop' ) .'</a></li>';
                endif; 

                $rows_content .= '<li><hr class="dropdown-divider"></li>';
                if ( $user_role_string == '_technician' ) :                                           
                    $rows_content .= '<li>
                                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#wcrbduplicatejobfront" recordid="'. esc_attr( $job_id ) .'" data-security="'. wp_create_nonce( 'wcrb_nonce_duplicate_job' ) .'">
                                                    <i class="bi bi-files text-warning me-2"></i>'. esc_html__( 'Duplicate Estimate', 'computer-repair-shop' ) .'
                                                </a>
                                            </li>
                                            
                                            <li><a class="dropdown-item" href="'. esc_url( $_edit_link ) .'" target="_blank"><i class="bi bi-pencil-square text-primary me-2"></i>'. esc_html__( 'Edit', 'computer-repair-shop' ) .'</a></li>';
                else :
                    $rows_content .= '<li>
                                                <a class="dropdown-item" href="'. esc_url( $_edit_link ) .'" target="_blank">
                                                    <i class="bi bi-files text-warning me-2"></i>'. esc_html__( 'Add message/files', 'computer-repair-shop' ) .'
                                                </a>
                                            </li>
                                            
                                            <li><a class="dropdown-item" href="'. esc_url( $_edit_link ) .'" target="_blank"><i class="bi bi-pencil-square text-primary me-2"></i>'. esc_html__( 'View Job', 'computer-repair-shop' ) .'</a></li>';
                endif;

                $rows_content .= '
                                        </ul>
                                    </div>
                                  </td>
                                </tr>';
            endwhile;
        else:
            $rows_content .= '<tr><td colspan="10">' . esc_html__( 'No job found!', 'computer-repair-shop' ) . '</td></tr>';
        endif;

        wp_reset_postdata();

        // Pagination data
        $total_jobs = $jobs_query->found_posts;
        $total_pages = ceil( $total_jobs / $jobs_per_page );

        // Showing text
        $showing_start = $offset + 1;
        $showing_end = min( $offset + $jobs_per_page, $total_jobs );

        $current_url = add_query_arg( $_GET, get_the_permalink() );
        
        // Generate pagination HTML
        $pagination_content = '<div class="card-footer">';
        $pagination_content .= '<div class="d-flex justify-content-between align-items-center">';
        $pagination_content .= '<div class="text-muted">';
        $pagination_content .= sprintf( 
            esc_html__( 'Showing %1$s to %2$s of %3$s jobs', 'computer-repair-shop' ),
            $showing_start,
            $showing_end,
            $total_jobs
        );
        $pagination_content .= '</div>';
        
        if ( $total_pages > 1 ) {
            $pagination_content .= '<nav><ul class="pagination mb-0">';
            
            // Previous button
            if ( $current_page > 1 ) {
                $prev_url = add_query_arg( 'jobs_page', $current_page - 1, $current_url );
                $pagination_content .= '<li class="page-item"><a class="page-link" href="' . esc_url( $prev_url ) . '"><i class="bi bi-chevron-left"></i></a></li>';
            } else {
                $pagination_content .= '<li class="page-item disabled"><a class="page-link" href="#" tabindex="-1" aria-disabled="true"><i class="bi bi-chevron-left"></i></a></li>';
            }

            // Page numbers
            for ( $i = 1; $i <= $total_pages; $i++ ) {
                if ( $i == 1 || $i == $total_pages || ( $i >= $current_page - 2 && $i <= $current_page + 2 ) ) {
                    $page_url = add_query_arg( 'jobs_page', $i, $current_url );
                    $active_class = ( $i == $current_page ) ? ' active' : '';
                    $pagination_content .= '<li class="page-item' . $active_class . '"><a class="page-link" href="' . esc_url( $page_url ) . '">' . $i . '</a></li>';
                } elseif ( $i == $current_page - 3 || $i == $current_page + 3 ) {
                    $pagination_content .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
            }

            // Next button
            if ( $current_page < $total_pages ) {
                $next_url = add_query_arg( 'jobs_page', $current_page + 1, $current_url );
                $pagination_content .= '<li class="page-item"><a class="page-link" href="' . esc_url( $next_url ) . '"><i class="bi bi-chevron-right"></i></a></li>';
            } else {
                $pagination_content .= '<li class="page-item disabled"><a class="page-link" href="#"><i class="bi bi-chevron-right"></i></a></li>';
            }
            
            $pagination_content .= '</ul></nav>';
        }
        
        $pagination_content .= '</div>';
        $pagination_content .= '</div>';

        return array(
            'rows' => $rows_content,
            'pagination' => $pagination_content
        );
    }

    function list_estimates_card_view( $arguments = array() ) {
        if ( ! is_user_logged_in() ) {
            return array(
                'rows' => esc_html__( 'You are not logged in.', 'computer-repair-shop' ),
                'pagination' => ''
            );
        }

        $_mainpage = get_queried_object_id();
        $WCRB_DASHBOARD = WCRB_MYACCOUNT_DASHBOARD::getInstance();

        $loadAllEstimates = 'NO';

        // Determine user role
        if ( 'customer' === $this->_user_role ) {
            $user_role_string = '_customer';
            $_jobstatuses = array( 'publish', 'pending', 'future' );
        } elseif ( 'technician' === $this->_user_role ) {
            $user_role_string = '_technician';
            $_jobstatuses = array( 'publish', 'pending', 'draft', 'private', 'future' );
        } elseif ( 'administrator' === $this->_user_role || 'store_manager' === $this->_user_role ) {
            $user_role_string = '_technician';
            $loadAllEstimates = 'YES';
            $_jobstatuses = array( 'publish', 'pending', 'draft', 'private', 'future' );
        } else {
            $user_role_string = '_customer';
            $_jobstatuses = array( 'publish', 'pending', 'future' );
        }

        // Build user-specific meta query
        if ( $user_role_string == '_technician' ) {
            $meta_query_b = array(
                'relation' => 'OR',
                array(
                    'key'     => '_technician',
                    'value'   => $this->_user_id,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ),
                array(
                    'key'     => '_technician',
                    'value'   => '"' . $this->_user_id . '"',
                    'compare' => 'LIKE',
                    'type'    => 'CHAR',
                )
            );
        } else {
            $meta_query_b = array(
                'key'     => $user_role_string,
                'value'   => $this->_user_id,
                'compare' => '=',
            );
        }
        
        $meta_query_b = ( $loadAllEstimates == 'YES' ) ? array() : $meta_query_b;

        // Pagination setup
        $estimates_per_page = 12;
        $current_page = isset( $_GET['estimates_page'] ) ? max( 1, intval( $_GET['estimates_page'] ) ) : 1;
        $offset = ( $current_page - 1 ) * $estimates_per_page;

        // Initialize meta query array
        $meta_query_arr = array();

        // Only add user role filter if not loading all estimates
        if ( $loadAllEstimates != 'YES' ) {
            $meta_query_arr[] = $meta_query_b;
        }

        // Filter: Estimate Status (unique to estimates)
        if ( isset( $_GET["estimate_status"] ) && ! empty( $_GET["estimate_status"] ) && $_GET["estimate_status"] != 'all' ) {
            if ( $_GET["estimate_status"] == 'pending' ) {
                $meta_query_arr[] = array(
                    'key'     => '_wc_estimate_status',
                    'compare' => 'NOT EXISTS',
                );
            } else {
                $meta_query_arr[] = array(
                    'key'     => '_wc_estimate_status',
                    'value'   => sanitize_text_field( $_GET['estimate_status'] ),
                    'compare' => '=',
                );
            }
        }

        // Filter: Store
        if ( isset( $_GET["wc_store"] ) && ! empty( $_GET["wc_store"] ) && $_GET["wc_store"] != 'all' ) {
            $meta_query_arr[] = array(
                'key'     => '_store_id',
                'value'   => sanitize_text_field( $_GET['wc_store'] ),
                'compare' => '=',
            );
        }

        // Filter: Customer
        if ( isset( $_GET["job_customer"] ) && ! empty( $_GET["job_customer"] ) && $_GET["job_customer"] != 'all' ) {
            $meta_query_arr[] = array(
                'key'     => '_customer',
                'value'   => intval( $_GET['job_customer'] ),
                'compare' => '=',
            );
        }

        // Filter: Technician
        if ( isset( $_GET["job_technician"] ) && ! empty( $_GET["job_technician"] ) && $_GET["job_technician"] != 'all' ) {
            $technician_id = intval( $_GET['job_technician'] );
            $meta_query_arr[] = array(
                'relation' => 'OR',
                array(
                    'key'     => '_technician',
                    'value'   => $technician_id,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ),
                array(
                    'key'     => '_technician',
                    'value'   => '"' . $technician_id . '"',
                    'compare' => 'LIKE',
                    'type'    => 'CHAR',
                )
            );
        }

        // Filter: Device Post ID
        if ( isset( $_GET["device_post_id"] ) && ! empty( $_GET["device_post_id"] ) && $_GET["device_post_id"] != 'All' ) {
            $device_post_id = sanitize_text_field( $_GET['device_post_id'] );
            $meta_query_arr[] = array(
                'key'     => '_wc_device_data',
                'value'   => '"device_post_id";s:' . strlen($device_post_id) . ':"' . $device_post_id . '"',
                'compare' => 'LIKE',
            );
        }

        // Global Search Functionality
        if ( isset( $_GET['searchinput'] ) && ! empty( $_GET['searchinput'] ) ) {
            $search_term = sanitize_text_field( $_GET['searchinput'] );
            
            $search_meta_query = array(
                'relation' => 'OR',
                array(
                    'key' => '_case_number',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_wc_device_data',
                    'value' => sprintf( ':"%s";', $search_term ),
                    'compare' => 'RLIKE',
                    'type'    => 'CHAR',
                ),
                array(
                    'key' => '_case_detail',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_customer_label',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_wcrb_job_id',
                    'value' => ltrim( $search_term, '0' ),
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_order_id',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
            );
            
            if ( $this->is_date_like( $search_term ) ) {
                $search_meta_query[] = array(
                    'key' => '_pickup_date',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                );
                $search_meta_query[] = array(
                    'key' => '_next_service_date',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                );
                $search_meta_query[] = array(
                    'key' => '_delivery_date',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                );
            }
            
            if ( ! empty( $meta_query_arr ) ) {
                $meta_query_arr = array(
                    'relation' => 'AND',
                    $meta_query_arr,
                    $search_meta_query
                );
            } else {
                $meta_query_arr = $search_meta_query;
            }
        }

        // WordPress Query for Estimates
        $estimates_args = array(
            'post_type'      => 'rep_estimates',
            'orderby'        => 'id',
            'order'          => 'DESC',
            'posts_per_page' => $estimates_per_page,
            'offset'         => $offset,
            'post_status'    => $_jobstatuses,
        );

        // Add meta query only if we have meta queries
        if ( ! empty( $meta_query_arr ) ) {
            $estimates_args['meta_query'] = $meta_query_arr;
        }

        $estimates_query = new WP_Query( $estimates_args );
        $cards_content = '';
        $pagination_content = '';

        // Get approval/rejection page link
        $selected_page = get_option( 'wc_rb_status_check_page_id' );
        $page_link     = get_the_permalink( $selected_page );

        if ( $estimates_query->have_posts() ) :
            $cards_content .= '<div class="row g-3 p-3">';
            
            while( $estimates_query->have_posts() ) :
                $estimates_query->the_post();

                $estimate_id = $estimates_query->post->ID;
                $_customer_id    = get_post_meta( $estimate_id, '_customer', true );
                $case_number     = get_post_meta( $estimate_id, '_case_number', true ); 
                $estimate_status = get_post_meta( $estimate_id, '_wc_estimate_status', true );
                $order_total     = wc_order_grand_total( $estimate_id, 'grand_total' );
                $order_total_fmt = wc_cr_currency_format( $order_total );
                $current_devices = get_post_meta( $estimate_id, '_wc_device_data', true );
                $created_date    = get_the_date( $this->_date_format, $estimate_id );
                $expiry_date     = get_post_meta( $estimate_id, '_estimate_expiry_date', true );
                
                // Format expiry date if exists
                if ( ! empty( $expiry_date ) ) {
                    $expiry_date = date_i18n( $this->_date_format, strtotime( $expiry_date ) );
                }

                // Edit Links
                $_edit_link = admin_url('post.php?post=' . $estimate_id . '&action=edit');
                if ( $user_role_string != '_technician' ) {
                    $_edit_link = add_query_arg( array( 'addms_viewjob' => 'yes', 'wc_case_number' => $case_number, 'screen' => 'print-screen', 'job_id' => $estimate_id, 'order_id' => $estimate_id, 'data-security' => wp_create_nonce( 'wcrb_nonce_printscreen' ), 'template' => 'customer_job' ), get_the_permalink( $_mainpage ) );
                }

                $_print_estimate = add_query_arg( array( 'wc_case_number' => $case_number, 'screen' => 'print-screen', 'job_id' => $estimate_id, 'data-security' => wp_create_nonce( 'wcrb_nonce_printscreen' ), 'template' => 'print-invoice' ), get_the_permalink( $_mainpage ) );
                $_download_pdf   = add_query_arg( array( 'dl_pdf' => 'yes', 'wc_case_number' => $case_number, 'screen' => 'print-screen', 'job_id' => $estimate_id, 'data-security' => wp_create_nonce( 'wcrb_nonce_printscreen' ), 'template' => 'print-invoice' ), get_the_permalink( $_mainpage ) );
                $_email_estimate = add_query_arg( array( 'email_customer' => 'yes', 'wc_case_number' => $case_number, 'screen' => 'print-screen', 'job_id' => $estimate_id, 'data-security' => wp_create_nonce( 'wcrb_nonce_printscreen' ), 'template' => 'print-invoice' ), get_the_permalink( $_mainpage ) );

                // Format customer info
                $customer_name = "";
                if ( ! empty( $_customer_id ) ) {
                    $user = get_user_by( 'id', $_customer_id );
                    $first_name = empty($user->first_name) ? "" : $user->first_name;
                    $last_name = empty($user->last_name) ? "" : $user->last_name;
                    $customer_name = $first_name . ' ' . $last_name;
                }

                // Format devices
                $primary_device = "";
                $device_icon = "bi-file-earmark-text"; // Document icon for estimates
                if ( ! empty( $current_devices ) && is_array( $current_devices ) ) {
                    $first_device = reset( $current_devices );
                    $device_post_id = isset( $first_device['device_post_id'] ) ? $first_device['device_post_id'] : '';
                    $primary_device = return_device_label( $device_post_id );
                    
                    // Determine icon based on device type
                    if ( stripos( $primary_device, 'phone' ) !== false || stripos( $primary_device, 'iphone' ) !== false ) {
                        $device_icon = "bi-phone";
                    } elseif ( stripos( $primary_device, 'tablet' ) !== false || stripos( $primary_device, 'ipad' ) !== false ) {
                        $device_icon = "bi-tablet";
                    } elseif ( stripos( $primary_device, 'laptop' ) !== false || stripos( $primary_device, 'macbook' ) !== false ) {
                        $device_icon = "bi-laptop";
                    } elseif ( stripos( $primary_device, 'desktop' ) !== false || stripos( $primary_device, 'pc' ) !== false ) {
                        $device_icon = "bi-pc";
                    }
                }

                // Format estimate status badge
                $status_badge = '';
                $status_color = '';
                
                if ( $estimate_status == 'approved' ) {
                    $status_badge = '<span class="badge bg-success bg-gradient d-inline-flex align-items-center px-2 py-1">
                        <i class="bi bi-check-circle-fill me-1"></i>
                        ' . esc_html__( 'Approved', 'computer-repair-shop' ) . '
                    </span>';
                    $status_color = 'success';
                } elseif ( $estimate_status == 'rejected' ) {
                    $status_badge = '<span class="badge bg-danger bg-gradient d-inline-flex align-items-center px-2 py-1">
                        <i class="bi bi-x-circle-fill me-1"></i>
                        ' . esc_html__( 'Rejected', 'computer-repair-shop' ) . '
                    </span>';
                    $status_color = 'danger';
                } else {
                    $status_badge = '<span class="badge bg-warning bg-gradient d-inline-flex align-items-center px-2 py-1">
                        <i class="bi bi-clock-history me-1"></i>
                        ' . esc_html__( 'Pending', 'computer-repair-shop' ) . '
                    </span>';
                    $status_color = 'warning';
                }

                $cards_content .= '<div class="col-xl-3 col-lg-4 col-md-6">';
                $cards_content .= '<div class="card h-100 estimate-card border">';
                
                // Card Header with estimate ID and status
                $cards_content .= '<div class="card-header d-flex justify-content-between align-items-center py-2">';
                $cards_content .= '<strong class="text-primary">EST-' . esc_html( $estimate_id ) . '</strong>';
                $cards_content .= wp_kses( $status_badge, $this->_allowedHTML );
                $cards_content .= '</div>';
                
                // Card Body
                $cards_content .= '<div class="card-body">';
                
                // Device Info
                $cards_content .= '<div class="d-flex align-items-start mb-3">';
                $cards_content .= '<span class="device-icon me-3">';
                $cards_content .= '<i class="bi ' . esc_attr( $device_icon ) . ' display-6 text-primary"></i>';
                $cards_content .= '</span>';
                $cards_content .= '<div>';
                $cards_content .= '<h6 class="card-title mb-1">' . esc_html( $primary_device ?: 'No Device' ) . '</h6>';
                $cards_content .= '<p class="text-muted small mb-0">' . esc_html( $case_number ) . '</p>';
                $cards_content .= '</div>';
                $cards_content .= '</div>';
                
                // Estimate Meta
                $cards_content .= '<div class="estimate-meta">';
                
                $cards_content .= '<div class="d-flex justify-content-between mb-2">';
                $cards_content .= '<span class="text-muted">' . esc_html__( 'Customer', 'computer-repair-shop' ) . ':</span>';
                $cards_content .= '<span class="fw-semibold text-truncate ms-2" style="max-width: 120px;">' . esc_html( $customer_name ) . '</span>';
                $cards_content .= '</div>';
                
                $cards_content .= '<div class="d-flex justify-content-between mb-2">';
                $cards_content .= '<span class="text-muted">' . esc_html__( 'Created', 'computer-repair-shop' ) . ':</span>';
                $cards_content .= '<span class="fw-semibold">' . esc_html( $created_date ) . '</span>';
                $cards_content .= '</div>';
                
                if ( ! empty( $expiry_date ) ) {
                    $cards_content .= '<div class="d-flex justify-content-between mb-2">';
                    $cards_content .= '<span class="text-muted">' . esc_html__( 'Expires', 'computer-repair-shop' ) . ':</span>';
                    $cards_content .= '<span class="fw-semibold">' . esc_html( $expiry_date ) . '</span>';
                    $cards_content .= '</div>';
                }
                
                $cards_content .= '<div class="d-flex justify-content-between mb-2">';
                $cards_content .= '<span class="text-muted">' . esc_html__( 'Amount', 'computer-repair-shop' ) . ':</span>';
                $cards_content .= '<span class="fw-semibold text-primary">' . esc_html( $order_total_fmt ) . '</span>';
                $cards_content .= '</div>';
                
                $cards_content .= '</div>'; // .estimate-meta
                
                $cards_content .= '</div>'; // .card-body
                
                // Card Footer with action buttons
                $cards_content .= '<div class="card-footer bg-transparent border-top-0 pt-0">';
                
                if ( $user_role_string == '_technician' ) :
                    // Technician actions
                    $cards_content .= '<div class="btn-group w-100">';
                    $cards_content .= '<a href="' . esc_url( $_edit_link ) . '" class="btn btn-outline-primary btn-sm" target="_blank">';
                    $cards_content .= '<i class="bi bi-eye me-1"></i>' . esc_html__( 'View', 'computer-repair-shop' );
                    $cards_content .= '</a>';
                    $cards_content .= '<a href="' . esc_url( $_edit_link ) . '" class="btn btn-outline-secondary btn-sm" target="_blank">';
                    $cards_content .= '<i class="bi bi-pencil me-1"></i>' . esc_html__( 'Edit', 'computer-repair-shop' );
                    $cards_content .= '</a>';
                    $cards_content .= '<a href="' . esc_url( $_print_estimate ) . '" target="_blank" class="btn btn-outline-info btn-sm" target="_blank">';
                    $cards_content .= '<i class="bi bi-printer me-1"></i>';
                    $cards_content .= '</a>';
                    $cards_content .= '</div>';
                else :
                    // Customer actions
                    if ( empty( $estimate_status ) || $estimate_status == 'pending' ) :
                        $cards_content .= '<div class="d-grid gap-2">';
                        
                        // Approve/Reject buttons for pending estimates
                        $approve_params = array( 'estimate_id' => $estimate_id, 'case_number' => $case_number, 'choice' => 'approved' );
                        $reject_params = array( 'estimate_id' => $estimate_id, 'case_number' => $case_number, 'choice' => 'rejected' );
                        $approve_url = add_query_arg( $approve_params, $page_link );
                        $reject_url = add_query_arg( $reject_params, $page_link );
                        
                        $cards_content .= '<div class="btn-group w-100">';
                        $cards_content .= '<a href="' . esc_url( $approve_url ) . '" class="btn btn-success btn-sm" target="_blank">';
                        $cards_content .= '<i class="bi bi-check-circle me-1"></i>' . esc_html__( 'Approve', 'computer-repair-shop' );
                        $cards_content .= '</a>';
                        $cards_content .= '<a href="' . esc_url( $reject_url ) . '" class="btn btn-danger btn-sm" target="_blank">';
                        $cards_content .= '<i class="bi bi-x-circle me-1"></i>' . esc_html__( 'Reject', 'computer-repair-shop' );
                        $cards_content .= '</a>';
                        $cards_content .= '</div>';
                        
                        $cards_content .= '<div class="btn-group w-100 mt-1">';
                        $cards_content .= '<a href="' . esc_url( $_print_estimate ) . '" target="_blank" class="btn btn-outline-info btn-sm" target="_blank">';
                        $cards_content .= '<i class="bi bi-printer me-1"></i>' . esc_html__( 'Print', 'computer-repair-shop' );
                        $cards_content .= '</a>';
                        $cards_content .= '<a href="' . esc_url( $_download_pdf ) . '" target="_blank" class="btn btn-outline-warning btn-sm" target="_blank">';
                        $cards_content .= '<i class="bi bi-download me-1"></i>' . esc_html__( 'PDF', 'computer-repair-shop' );
                        $cards_content .= '</a>';
                        $cards_content .= '</div>';
                        
                        $cards_content .= '</div>';
                    else :
                        // View only for approved/rejected estimates
                        $cards_content .= '<div class="btn-group w-100">';
                        $cards_content .= '<a href="' . esc_url( $_edit_link ) . '" class="btn btn-outline-primary btn-sm" target="_blank">';
                        $cards_content .= '<i class="bi bi-eye me-1"></i>' . esc_html__( 'View', 'computer-repair-shop' );
                        $cards_content .= '</a>';
                        $cards_content .= '<a href="' . esc_url( $_print_estimate ) . '" target="_blank" class="btn btn-outline-info btn-sm" target="_blank">';
                        $cards_content .= '<i class="bi bi-printer me-1"></i>' . esc_html__( 'Print', 'computer-repair-shop' );
                        $cards_content .= '</a>';
                        $cards_content .= '<a href="' . esc_url( $_download_pdf ) . '" target="_blank" class="btn btn-outline-warning btn-sm" target="_blank">';
                        $cards_content .= '<i class="bi bi-download me-1"></i>' . esc_html__( 'PDF', 'computer-repair-shop' );
                        $cards_content .= '</a>';
                        $cards_content .= '</div>';
                    endif;
                endif;
                
                $cards_content .= '</div>'; // .card-footer
                
                $cards_content .= '</div>'; // .card
                $cards_content .= '</div>'; // .col

            endwhile;
            
            $cards_content .= '</div>'; // .row
        else:
            $cards_content .= '<div class="col-12 text-center py-5">';
            $cards_content .= '<i class="bi bi-file-earmark-text display-1 text-muted"></i>';
            $cards_content .= '<h4 class="text-muted mt-3">' . esc_html__( 'No estimates found!', 'computer-repair-shop' ) . '</h4>';
            $cards_content .= '</div>';
        endif;

        wp_reset_postdata();

        // Pagination
        $total_estimates = $estimates_query->found_posts;
        $total_pages = ceil( $total_estimates / $estimates_per_page );

        $showing_start = $offset + 1;
        $showing_end = min( $offset + $estimates_per_page, $total_estimates );

        $current_url = add_query_arg( $_GET, get_the_permalink() );
        
        $pagination_content = '<div class="card-footer">';
        $pagination_content .= '<div class="d-flex justify-content-between align-items-center">';
        $pagination_content .= '<div class="text-muted">';
        $pagination_content .= sprintf( 
            esc_html__( 'Showing %1$s to %2$s of %3$s estimates', 'computer-repair-shop' ),
            $showing_start,
            $showing_end,
            $total_estimates
        );
        $pagination_content .= '</div>';
        
        if ( $total_pages > 1 ) {
            $pagination_content .= '<nav><ul class="pagination mb-0">';
            
            // Previous button
            if ( $current_page > 1 ) {
                $prev_url = add_query_arg( 'estimates_page', $current_page - 1, $current_url );
                $pagination_content .= '<li class="page-item"><a class="page-link" href="' . esc_url( $prev_url ) . '"><i class="bi bi-chevron-left"></i></a></li>';
            } else {
                $pagination_content .= '<li class="page-item disabled"><a class="page-link" href="#" tabindex="-1" aria-disabled="true"><i class="bi bi-chevron-left"></i></a></li>';
            }

            // Page numbers
            for ( $i = 1; $i <= $total_pages; $i++ ) {
                if ( $i == 1 || $i == $total_pages || ( $i >= $current_page - 2 && $i <= $current_page + 2 ) ) {
                    $page_url = add_query_arg( 'estimates_page', $i, $current_url );
                    $active_class = ( $i == $current_page ) ? ' active' : '';
                    $pagination_content .= '<li class="page-item' . $active_class . '"><a class="page-link" href="' . esc_url( $page_url ) . '">' . $i . '</a></li>';
                } elseif ( $i == $current_page - 3 || $i == $current_page + 3 ) {
                    $pagination_content .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
            }

            // Next button
            if ( $current_page < $total_pages ) {
                $next_url = add_query_arg( 'estimates_page', $current_page + 1, $current_url );
                $pagination_content .= '<li class="page-item"><a class="page-link" href="' . esc_url( $next_url ) . '"><i class="bi bi-chevron-right"></i></a></li>';
            } else {
                $pagination_content .= '<li class="page-item disabled"><a class="page-link" href="#"><i class="bi bi-chevron-right"></i></a></li>';
            }
            
            $pagination_content .= '</ul></nav>';
        }
        
        $pagination_content .= '</div>';
        $pagination_content .= '</div>';

        return array(
            'rows' => $cards_content,
            'pagination' => $pagination_content
        );
    }

    /**
     * Check if a string looks like a date
     */
    private function is_date_like( $string ) {
        // Common date patterns
        $date_patterns = array(
            '/\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}/', // MM/DD/YYYY or DD-MM-YY
            '/\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2}/',   // YYYY-MM-DD
            '/\d{1,2}[\/\-]\d{1,2}/',              // MM/DD or DD-MM
            '/\d{4}/',                             // YYYY
        );
        
        foreach ( $date_patterns as $pattern ) {
            if ( preg_match( $pattern, $string ) ) {
                return true;
            }
        }
        
        return false;
    }

    function return_jobs_count_by_status_array() {
        global $wpdb;

        $_return_array = array();

        //Table
        $computer_repair_job_status = $wpdb->prefix.'wc_cr_job_status';

        $select_query 	= "SELECT * FROM `".$computer_repair_job_status."` WHERE `status_status`='active' ORDER BY `status_name` ASC";
        $select_results = $wpdb->get_results( $select_query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        
        foreach($select_results as $result) {
            $status_label = $result->status_name;
            $status_slug  = $result->status_slug;
            $number_jobs  = wcrb_count_jobs_by_status( $result->status_slug, 'frontend' );

            $_return_array[] = array(
                'status_name' => $status_label,
                'status_slug' => $status_slug,
                'jobs_count' => $number_jobs
            );
        } //End Foreach

        return $_return_array;
    }

    function export_jobs( $arguments = array() ) {
        if ( ! is_user_logged_in() ) {
            wp_die( esc_html__( 'You are not logged in.', 'computer-repair-shop' ) );
        }

        // Check export type
        $export_type = isset( $_GET['export'] ) ? sanitize_text_field( $_GET['export'] ) : 'csv';
        
        if ( ! in_array( $export_type, array( 'csv', 'excel', 'pdf' ) ) ) {
            wp_die( esc_html__( 'Invalid export type.', 'computer-repair-shop' ) );
        }

        // Set headers based on export type
        switch( $export_type ) {
            case 'csv':
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename=jobs-export-' . date('Y-m-d') . '.csv');
                break;
            case 'excel':
                header('Content-Type: application/vnd.ms-excel; charset=utf-8');
                header('Content-Disposition: attachment; filename=jobs-export-' . date('Y-m-d') . '.xls');
                break;
            case 'pdf':
                // We'll handle PDF separately as it requires different approach
                $this->generate_pdf_export();
                exit;
        }

        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

        // Get all jobs with same filters as list_jobs
        $jobs_data = $this->get_jobs_for_export( $arguments );
        
        if ( empty( $jobs_data ) ) {
            fclose($output);
            wp_die( esc_html__( 'No jobs found to export.', 'computer-repair-shop' ) );
        }

        // Output headers
        fputcsv($output, array_keys( $jobs_data[0] ), ',', '"');

        // Output data
        foreach ( $jobs_data as $job ) {
            fputcsv($output, $job, ',', '"');
        }

        fclose($output);
        exit;
    }

    function get_jobs_for_export( $arguments = array() ) {
        // Reuse the same filtering logic from list_jobs()
        $_mainpage = get_queried_object_id();
        $WCRB_DASHBOARD = WCRB_MYACCOUNT_DASHBOARD::getInstance();

        $loadAllJobs = 'NO';
        $is_admin_user = false;

        if ( 'customer' === $this->_user_role ) {
            $user_role_string = '_customer';
        } elseif ( 'technician' === $this->_user_role ) {
            $user_role_string = '_technician';
        } elseif ( 'administrator' === $this->_user_role || 'store_manager' === $this->_user_role ) {
            $user_role_string = '_technician';
            $loadAllJobs = 'YES';
            $is_admin_user = true;
        } else {
            $user_role_string = '_customer';
        }

        // Initialize meta query array
        $meta_query_arr = array();

        // Build the user-specific meta query based on role
        if ( $user_role_string == '_technician' && !$is_admin_user ) {
            // For regular technicians - use the OR condition for both single IDs and serialized arrays
            $meta_query_arr[] = array(
                'relation' => 'OR',
                // For single technician IDs
                array(
                    'key'     => '_technician',
                    'value'   => $this->_user_id,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ),
                // For serialized arrays with quotes
                array(
                    'key'     => '_technician',
                    'value'   => '"' . $this->_user_id . '"',
                    'compare' => 'LIKE',
                    'type'    => 'CHAR',
                ),
                // For serialized arrays with colon-semicolon
                array(
                    'key'     => '_technician',
                    'value'   => ':' . $this->_user_id . ';',
                    'compare' => 'LIKE',
                    'type'    => 'CHAR',
                )
            );
        } elseif ( $user_role_string == '_customer' ) {
            // For customers - simple exact match
            $meta_query_arr[] = array(
                'key'     => $user_role_string,
                'value'   => $this->_user_id,
                'compare' => '=',
                'type'    => 'NUMERIC',
            );
        }
        // For admin users ($is_admin_user = true), don't add any user filter - they see all jobs

        // Apply all the same filters as list_jobs()
        // Filter: Job Status
        if ( isset( $_GET["job_status"] ) && ! empty( $_GET["job_status"] ) && $_GET["job_status"] != 'all' ) {
            $meta_query_arr[] = array(
                'key'     => '_wc_order_status',
                'value'   => sanitize_text_field( $_GET['job_status'] ),
                'compare' => '=',
            );
        }

        // Filter: Store
        if ( isset( $_GET["wc_store"] ) && ! empty( $_GET["wc_store"] ) && $_GET["wc_store"] != 'all' ) {
            $meta_query_arr[] = array(
                'key'     => '_store_id',
                'value'   => sanitize_text_field( $_GET['wc_store'] ),
                'compare' => '=',
            );
        }

        // Filter: Payment Status
        if ( isset( $_GET["wc_payment_status"] ) && ! empty( $_GET["wc_payment_status"] ) && $_GET["wc_payment_status"] != 'all' ) {
            $meta_query_arr[] = array(
                'key'     => '_wc_payment_status',
                'value'   => sanitize_text_field( $_GET['wc_payment_status'] ),
                'compare' => '=',
            );
        }

        // Filter: Customer - ONLY for admin users
        if ( $is_admin_user && isset( $_GET["job_customer"] ) && ! empty( $_GET["job_customer"] ) && $_GET["job_customer"] != 'all' ) {
            $meta_query_arr[] = array(
                'key'     => '_customer',
                'value'   => intval( $_GET['job_customer'] ),
                'compare' => '=',
                'type'    => 'NUMERIC',
            );
        }

        // Filter: Priority
        if ( isset( $_GET["wc_job_priority"] ) && ! empty( $_GET["wc_job_priority"] ) && $_GET["wc_job_priority"] != 'all' ) {
            $meta_query_arr[] = array(
                'key'     => '_wc_job_priority',
                'value'   => sanitize_text_field( $_GET['wc_job_priority'] ),
                'compare' => '=',
            );
        }

        if ( isset( $_GET['estimate_status'] ) && ! empty( $_GET['estimate_status'] ) ) {
            if ( $_GET["estimate_status"] == 'pending' ) {
                $meta_query_arr[] = array(
                        'key'     => '_wc_estimate_status',
                        'compare' => 'NOT EXISTS',
                    );
            } else {
                $meta_query_arr[] = array(
                    'key'     => '_wc_estimate_status',
                    'value'   => sanitize_text_field( $_GET['estimate_status'] ),
                    'compare' => '=',
                );
            }
        }

        // Filter: Technician - ONLY for admin users
        if ( $is_admin_user && isset( $_GET["job_technician"] ) && ! empty( $_GET["job_technician"] ) && $_GET["job_technician"] != 'all' ) {
            $technician_id = intval( $_GET['job_technician'] );
            $meta_query_arr[] = array(
                'relation' => 'OR',
                // For single technician IDs
                array(
                    'key'     => '_technician',
                    'value'   => $technician_id,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ),
                // For serialized arrays with quotes
                array(
                    'key'     => '_technician',
                    'value'   => '"' . $technician_id . '"',
                    'compare' => 'LIKE',
                    'type'    => 'CHAR',
                ),
                // For serialized arrays with colon-semicolon
                array(
                    'key'     => '_technician',
                    'value'   => ':' . $technician_id . ';',
                    'compare' => 'LIKE',
                    'type'    => 'CHAR',
                )
            );
        }

        // Filter: Device Post ID
        if ( isset( $_GET["device_post_id"] ) && ! empty( $_GET["device_post_id"] ) && $_GET["device_post_id"] != 'All' ) {
            $device_post_id = sanitize_text_field( $_GET['device_post_id'] );
            $meta_query_arr[] = array(
                'key'     => '_wc_device_data',
                'value'   => '"device_post_id";s:' . strlen($device_post_id) . ':"' . $device_post_id . '"',
                'compare' => 'LIKE',
            );
        }

        // Set the meta query relation if we have multiple conditions
        if ( count( $meta_query_arr ) > 1 ) {
            $meta_query_arr = array(
                'relation' => 'AND',
                $meta_query_arr
            );
        }

        // Global Search Functionality
        if ( isset( $_GET['searchinput'] ) && ! empty( $_GET['searchinput'] ) ) {
            $search_term = sanitize_text_field( $_GET['searchinput'] );
            
            $search_meta_query = array(
                'relation' => 'OR',
                array(
                    'key' => '_case_number',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_wc_device_data',
                    'value' => sprintf( ':"%s";', $search_term ),
                    'compare' => 'RLIKE',
                    'type'    => 'CHAR',
                ),
                array(
                    'key' => '_case_detail',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_wc_order_note',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_wc_job_priority',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_customer_label',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_wc_order_status_label',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_wc_payment_status_label',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_wcrb_job_id',
                    'value' => ltrim( $search_term, '0' ),
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_order_id',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                ),
            );
            
            if ( $this->is_date_like( $search_term ) ) {
                $search_meta_query[] = array(
                    'key' => '_pickup_date',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                );
                $search_meta_query[] = array(
                    'key' => '_next_service_date',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                );
                $search_meta_query[] = array(
                    'key' => '_delivery_date',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                );
            }
            
            if ( ! empty( $meta_query_arr ) ) {
                $meta_query_arr = array(
                    'relation' => 'AND',
                    $meta_query_arr,
                    $search_meta_query
                );
            } else {
                $meta_query_arr = $search_meta_query;
            }
        }

        // Get ALL jobs (no pagination)
        $_posttype = ( isset( $_GET['screen'] ) && ( $_GET['screen'] == 'estimates_card' || $_GET['screen'] == 'estimates' ) ) ? 'rep_estimates' : 'rep_jobs';

        $jobs_args = array(
            'post_type'      => $_posttype,
            'orderby'        => 'id',
            'order'          => 'DESC',
            'posts_per_page' => -1, // Get all posts
            'post_status'    => array( 'publish', 'pending', 'draft', 'private', 'future' ),
        );

        if ( ! empty( $meta_query_arr ) ) {
            $jobs_args['meta_query'] = $meta_query_arr;
        }

        $jobs_query = new WP_Query( $jobs_args );
        $export_data = array();

        if ( $jobs_query->have_posts() ) {
            while( $jobs_query->have_posts() ) {
                $jobs_query->the_post();

                $job_id = $jobs_query->post->ID;
                
                // Get all the same data as list_jobs()
                $jobs_manager = WCRB_JOBS_MANAGER::getInstance();
                $job_data = $jobs_manager->get_job_display_data( $job_id );
                $_job_id = ( ! empty( $job_data['formatted_job_number'] ) ) ? $job_data['formatted_job_number'] : $job_id;

                $_customer_id = get_post_meta( $job_id, '_customer', true );
                $case_number = get_post_meta( $job_id, '_case_number', true ); 
                $order_date = get_the_date( '', $job_id );
                $wc_payment_status = get_post_meta( $job_id, '_wc_payment_status', true );
                $payment_status = get_post_meta( $job_id, '_wc_payment_status_label', true );
                $job_status = get_post_meta( $job_id, '_wc_order_status_label', true );
                $estimate_status = get_post_meta( $job_id, '_wc_estimate_status', 'true' );
                $order_total = wc_order_grand_total( $job_id, 'grand_total' );
                $theBalance = wc_order_grand_total( $job_id, 'balance' );
                $technician = get_post_meta( $job_id, '_technician', true );
                $current_devices = get_post_meta( $job_id, '_wc_device_data', true );
                $delivery_date = get_post_meta( $job_id, '_delivery_date', true );
                $pickup_date = get_post_meta( $job_id, '_pickup_date', true );
                $next_service_date = get_post_meta( $job_id, '_next_service_date', true );
                $wc_order_status = get_post_meta( $job_id, '_wc_order_status', true );

                // Format customer info
                $customer_info = "";
                if ( ! empty( $_customer_id ) ) {
                    $user = get_user_by( 'id', $_customer_id );
                    $phone_number = get_user_meta( $_customer_id, "billing_phone", true );
                    $billing_tax = get_user_meta( $_customer_id, "billing_tax", true );
                    $company = get_user_meta( $_customer_id, "billing_company", true );
                    
                    $first_name = empty($user->first_name)? "" : $user->first_name;
                    $last_name = empty($user->last_name)? "" : $user->last_name;
                    $theFullName = $first_name. ' ' .$last_name;
                    $email = empty( $user->user_email ) ? "" : $user->user_email;
                    
                    $customer_info = $theFullName . " | " . $phone_number . " | " . $email . " | " . $company;
                }

                // Format devices
                $devices_info = "";
                if ( ! empty( $current_devices ) && is_array( $current_devices ) ) {
                    $device_list = array();
                    foreach( $current_devices as $device_data ) {
                        $device_post_id = isset( $device_data['device_post_id'] ) ? $device_data['device_post_id'] : '';
                        $device_id = isset( $device_data['device_id'] ) ? $device_data['device_id'] : '';
                        $device_list[] = return_device_label( $device_post_id ) . ( $device_id ? " ($device_id)" : "" );
                    }
                    $devices_info = implode("; ", $device_list);
                }

                // Format technician - handle both single IDs and serialized arrays
                $tech_name = "";
                if ( ! empty( $technician ) ) {
                    // If it's a serialized array, try to unserialize
                    if ( is_serialized( $technician ) ) {
                        $tech_array = maybe_unserialize( $technician );
                        if ( is_array( $tech_array ) ) {
                            $tech_names = array();
                            foreach ( $tech_array as $tech_id ) {
                                $tech_user = get_user_by( 'id', $tech_id );
                                if ( $tech_user ) {
                                    $tech_names[] = $tech_user->first_name . ' ' . $tech_user->last_name;
                                }
                            }
                            $tech_name = implode(", ", $tech_names);
                        } elseif ( is_numeric( $tech_array ) ) {
                            $tech_user = get_user_by( 'id', $tech_array );
                            $tech_name = $tech_user->first_name . ' ' . $tech_user->last_name;
                        }
                    } elseif ( is_numeric( $technician ) ) {
                        // Single technician ID
                        $tech_user = get_user_by( 'id', $technician );
                        $tech_name = $tech_user->first_name . ' ' . $tech_user->last_name;
                    }
                }

                if ( isset( $_GET['screen'] ) && ( $_GET['screen'] == 'estimates' || $_GET['screen'] == 'estimates_card' ) ) {
                    $job_status = $estimate_status;
                }

                // Add to export data
                $export_data[] = array(
                    'Job ID' => $_job_id,
                    'Case Number' => $case_number,
                    'Customer' => $customer_info,
                    'Technician' => $tech_name,
                    'Devices' => $devices_info,
                    'Order Date' => $order_date,
                    'Pickup Date' => $pickup_date,
                    'Delivery Date' => $delivery_date,
                    'Next Service Date' => $next_service_date,
                    'Job Status' => $job_status,
                    'Payment Status' => $payment_status,
                    'Order Total' => wc_cr_currency_format( $order_total ),
                    'Balance' => wc_cr_currency_format( $theBalance ),
                    'Priority' => get_post_meta( $job_id, '_wc_job_priority', true ),
                );
            }
        }

        wp_reset_postdata();
        return $export_data;
    }

    function generate_pdf_export() {
        $jobs_data = $this->get_jobs_for_export();
    
        if ( empty( $jobs_data ) ) {
            wp_die( esc_html__( 'No jobs found to export.', 'computer-repair-shop' ) );
        }

        $_title = esc_html__( 'Jobs Export', 'computer-repair-shop' );
        if ( isset( $_GET['screen'] ) && ( $_GET['screen'] == 'estimates' || $_GET['screen'] == 'estimates_card' ) ) {
            $_title = esc_html__( 'Estimates Export', 'computer-repair-shop' );
        }

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <style>
                @page {
                    margin: 20px;
                    size: A4 landscape;
                }
                body {
                    font-family: "DejaVu Sans", "Helvetica", Arial, sans-serif;
                    font-size: 10px;
                    line-height: 1.4;
                    color: #333;
                    margin: 0;
                    padding: 0;
                }
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                    padding-bottom: 15px;
                    border-bottom: 2px solid #2c3e50;
                }
                .header h1 {
                    color: #2c3e50;
                    font-size: 18px;
                    margin: 0 0 5px 0;
                }
                .header .subtitle {
                    color: #7f8c8d;
                    font-size: 12px;
                }
                .summary {
                    background: #f8f9fa;
                    padding: 10px;
                    margin-bottom: 15px;
                    border-radius: 4px;
                    font-size: 9px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 0;
                    page-break-inside: auto;
                }
                thead {
                    display: table-header-group;
                }
                tr {
                    page-break-inside: avoid;
                    page-break-after: auto;
                }
                th {
                    background: #2c3e50;
                    color: white;
                    font-weight: bold;
                    padding: 8px 5px;
                    text-align: left;
                    font-size: 9px;
                    border: 1px solid #34495e;
                }
                td {
                    padding: 6px 5px;
                    border: 1px solid #ddd;
                    font-size: 8px;
                    word-wrap: break-word;
                    max-width: 120px;
                }
                tbody tr:nth-child(even) {
                    background: #f8f9fa;
                }
                tbody tr:hover {
                    background: #e9ecef;
                }
                .text-center {
                    text-align: center;
                }
                .text-right {
                    text-align: right;
                }
                .currency {
                    text-align: right;
                    font-family: "Courier New", monospace;
                }
                .status-badge {
                    padding: 2px 6px;
                    border-radius: 3px;
                    font-size: 7px;
                    font-weight: bold;
                }
                .status-completed {
                    background: #d4edda;
                    color: #155724;
                }
                .status-pending {
                    background: #fff3cd;
                    color: #856404;
                }
                .status-cancelled {
                    background: #f8d7da;
                    color: #721c24;
                }
                .footer {
                    margin-top: 20px;
                    padding-top: 10px;
                    border-top: 1px solid #ddd;
                    text-align: center;
                    font-size: 8px;
                    color: #7f8c8d;
                }
                .page-break {
                    page-break-before: always;
                }
                .nowrap {
                    white-space: nowrap;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>' . esc_html( $_title ) . '</h1>
                <div class="subtitle">' . date('F j, Y') . '</div>
            </div>
            
            <div class="summary">
                <strong>' . esc_html__( 'Total Jobs:', 'computer-repair-shop' ) . '</strong> ' . count($jobs_data) . ' | 
                <strong>' . esc_html__( 'Generated:', 'computer-repair-shop' ) . '</strong> ' . date('Y-m-d H:i:s') . '
            </div>';

        $html .= '<table>';
        $html .= '<thead><tr>';
        
        // Headers
        foreach ( array_keys( $jobs_data[0] ) as $header ) {
            $html .= '<th>' . esc_html( $header ) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        
        // Data
        foreach ( $jobs_data as $job ) {
            $html .= '<tr>';
            foreach ( $job as $value ) {
                $html .= '<td>' . esc_html( $value ) . '</td>';
            }
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';

        // Create PDF
        $dompdf = new Dompdf\Dompdf();
        $dompdf->loadHtml( $html );
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        
        // Output
        $dompdf->stream('jobs-export-' . date('Y-m-d') . '.pdf', array('Attachment' => 1));
        exit;
    }

    function handle_export_requests() {
        if ( isset( $_GET['export'] ) && in_array( $_GET['export'], array( 'csv', 'excel', 'pdf' ) ) ) {
            // Verify nonce for security
            if ( ! isset( $_GET['export_nonce'] ) || ! wp_verify_nonce( $_GET['export_nonce'], 'wcrb_export_jobs' ) ) {
                wp_die( esc_html__( 'Security verification failed.', 'computer-repair-shop' ) );
            }
            
            $this->export_jobs();
        }
    }

    function get_export_buttons() {
        // Check license state
        if ( ! wc_rs_license_state() ) {
            return '
                <ul class="dropdown-menu">
                    <li><span class="dropdown-item text-muted">
                        <i class="bi bi-lock me-2"></i>' . esc_html__( 'Pro Feature', 'computer-repair-shop' ) . '
                    </span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-success" href="https://www.webfulcreations.com/repairbuddy-wordpress-plugin/pricing/" target="_blank" data-bs-toggle="modal" data-bs-target="#upgradeModal">
                        <i class="bi bi-star me-2"></i>' . esc_html__( 'Upgrade Now', 'computer-repair-shop' ) . '
                    </a></li>
                    <li><a class="dropdown-item text-info" href="https://www.webfulcreations.com/repairbuddy-wordpress-plugin/repairbuddy-features/" target="_blank">
                        <i class="bi bi-info-circle me-2"></i>' . esc_html__( 'View Features', 'computer-repair-shop' ) . '
                    </a></li>
                </ul>';
        }

        $current_url = add_query_arg( $_GET, get_the_permalink() );
        $export_nonce = wp_create_nonce( 'wcrb_export_jobs' );
        
        return '
            <ul class="dropdown-menu">
                <li><a href="' . esc_url( add_query_arg( array( 'export' => 'csv', 'export_nonce' => $export_nonce ), $current_url ) ) . '" class="dropdown-item">
                    <i class="bi bi-filetype-csv me-2"></i>' . esc_html__( 'CSV', 'computer-repair-shop' ) . '
                </a></li>
                <li><a href="' . esc_url( add_query_arg( array( 'export' => 'pdf', 'export_nonce' => $export_nonce ), $current_url ) ) . '" class="dropdown-item">
                    <i class="bi bi-filetype-pdf me-2"></i>' . esc_html__( 'PDF', 'computer-repair-shop' ) . '
                </a></li>
                <li><a href="' . esc_url( add_query_arg( array( 'export' => 'excel', 'export_nonce' => $export_nonce ), $current_url ) ) . '" class="dropdown-item">
                    <i class="bi bi-filetype-xlsx me-2"></i>' . esc_html__( 'Excel', 'computer-repair-shop' ) . '
                </a></li>
            </ul>';
    }
}
WCRB_DASHBOARD_JOBS::getInstance();