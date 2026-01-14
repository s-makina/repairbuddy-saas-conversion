<?php
    defined( 'ABSPATH' ) || exit;
?>

<!-- Dashboard Content -->
<main class="dashboard-content container-fluid py-4">
    <?php
        $_overview_stats = $dasboard->dashboard_overview_stats();        
    ?>
    <!-- Stats Overview -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card stats-card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-white-50 mb-2"><?php echo esc_html__( 'Active Jobs', 'computer-repair-shop' ); ?></h6>
                            <h3 class="mb-0"><?php echo esc_html( $_overview_stats['active_jobs_count'] ); ?></h3>
                        </div>
                        <div class="stats-icon">
                            <i class="bi bi-briefcase display-6 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card stats-card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-white-50 mb-2"><?php echo esc_html__( 'Completed', 'computer-repair-shop' ); ?></h6>
                            <h3 class="mb-0"><?php echo esc_html( $_overview_stats['completed_jobs_count'] ); ?></h3>
                        </div>
                        <div class="stats-icon">
                            <i class="bi bi-check-circle display-6 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card stats-card bg-warning text-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-2"><?php echo esc_html__( 'Pending Estimates', 'computer-repair-shop' ); ?></h6>
                            <h3 class="mb-0"><?php echo esc_html( $_overview_stats['pending_estimates_count'] ); ?></h3>
                        </div>
                        <div class="stats-icon">
                            <i class="bi bi-clock display-6 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card stats-card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-white-50 mb-2"><?php esc_html__( 'Revenue', 'computer-repair-shop' ); ?></h6>
                            <h3 class="mb-0"><?php echo esc_html( $_overview_stats['revenue_formatted'] ); ?></h3>
                        </div>
                        <div class="stats-icon">
                            <i class="bi bi-currency-dollar display-6 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Overview stats ends /-->

    <!-- Charts Section -->
    <div class="row g-4 mb-4">
        <?php if ( $role == 'customer' ) : ?>
        <!-- Customer View -->
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><?php echo esc_html__( 'My Jobs Overview', 'computer-repair-shop' ); ?></h5>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-secondary active"><?php echo esc_html__( 'Last 7 Days', 'computer-repair-shop' ); ?></button>
                        <button class="btn btn-outline-secondary"><?php echo esc_html__( 'Last 30 Days', 'computer-repair-shop' ); ?></button>
                        <button class="btn btn-outline-secondary"><?php echo esc_html__( 'Last 90 Days', 'computer-repair-shop' ); ?></button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="customerJobsChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><?php echo esc_html__( 'My Active Jobs', 'computer-repair-shop' ); ?></h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="customerStatusChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <?php else : ?>
        <!-- Revenue Chart -->
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><?php echo esc_html__( 'Revenue Analytics', 'computer-repair-shop' ); ?></h5>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-secondary active"><?php echo esc_html__( 'Weekly', 'computer-repair-shop' ); ?></button>
                        <button class="btn btn-outline-secondary"><?php echo esc_html__( 'Monthly', 'computer-repair-shop' ); ?></button>
                        <button class="btn btn-outline-secondary"><?php echo esc_html__( 'Yearly', 'computer-repair-shop' ); ?></button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="revenueChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Job Status Distribution -->
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><?php echo esc_html__( 'Job Status', 'computer-repair-shop' ); ?></h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="jobStatusChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <!-- End Analytics and Status chart /-->

    <!-- Additional Charts & Content -->
    <div class="row g-4 mb-4">
        <!-- Left Column (8 columns) -->
        <div class="col-xl-8">
            <div class="row g-4">
                <?php if ( $role == 'customer' ) : ?>
                    <!-- My Jobs List -->
                    <div class="col-xl-6">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0"><?php echo esc_html__( 'My Jobs', 'computer-repair-shop' ); ?></h5>
                                <a href="<?php echo add_query_arg(array('screen' => 'jobs'), get_permalink()); ?>" class="btn btn-sm btn-outline-primary">
                                    <?php echo esc_html__( 'View All', 'computer-repair-shop' ); ?>
                                </a>
                            </div>
                            <div class="card-body p-0">
                                <div class="latest-items-list" style="max-height: 250px; overflow-y: auto;">
                                    <?php
                                    // Get current customer ID
                                    $current_user = wp_get_current_user();
                                    $user_id = $current_user->ID;
                                    
                                    // Query for latest jobs (5-6 to fit the space)
                                    $latest_jobs_args = array(
                                        'post_type' => 'rep_jobs',
                                        'posts_per_page' => 6,
                                        'post_status' => array('publish', 'pending', 'draft', 'private', 'future'),
                                        'orderby' => 'date',
                                        'order' => 'DESC',
                                        'meta_query' => array(
                                            array(
                                                'key' => '_customer',
                                                'value' => $user_id,
                                                'compare' => '=',
                                                'type' => 'NUMERIC'
                                            )
                                        )
                                    );
                                    
                                    $latest_jobs = new WP_Query($latest_jobs_args);
                                    
                                    if ($latest_jobs->have_posts()) :
                                        while ($latest_jobs->have_posts()) : $latest_jobs->the_post();
                                            $job_id      = get_the_ID();
                                            $job_title   = get_the_title();
                                            $job_date   = get_the_date('M j');
                                            $job_status = get_post_meta($job_id, '_wc_order_status', true);
                                            $job_total  = wc_order_grand_total($job_id, 'grand_total');
                                            $formatted_total = $job_total ? wc_cr_currency_format($job_total) : 'N/A';

                                            // Get job number
                                            $jobs_manager = WCRB_JOBS_MANAGER::getInstance();
                                            $job_data = $jobs_manager->get_job_display_data($job_id);
                                            $job_number = !empty($job_data['formatted_job_number']) ? $job_data['formatted_job_number'] : '#' . $job_id;
                                            
                                            // Truncate title if too long
                                            $display_title = strlen($job_title) > 30 ? substr($job_title, 0, 27) . '...' : $job_title;
                                            
                                            // Status badge
                                            $status_badge_class = 'bg-secondary';
                                            $status_display = ucfirst($job_status);
                                            
                                            if ($job_status === 'delivered') {
                                                $status_badge_class = 'bg-success';
                                            } elseif ($job_status === 'cancelled') {
                                                $status_badge_class = 'bg-danger';
                                            } elseif (in_array($job_status, array('inprocess', 'inservice'))) {
                                                $status_badge_class = 'bg-warning';
                                            } elseif ($job_status === 'quote') {
                                                $status_badge_class = 'bg-info';
                                            }
                                            $_case_number = get_post_meta( $job_id, '_case_number', true );
                                            $_editlink = add_query_arg( array( 'addms_viewjob' => 'yes', 'wc_case_number' => $_case_number, 'screen' => 'print-screen', 'job_id' => $job_id, 'order_id' => $job_id, 'data-security' => wp_create_nonce( 'wcrb_nonce_printscreen' ), 'template' => 'customer_job' ), get_the_permalink( $_mainpage ) );
                                            ?>
                                            
                                            <div class="latest-item p-3 border-bottom">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="flex-grow-1" style="min-width: 0;">
                                                        <h6 class="mb-1" style="font-size: 0.9rem;" title="<?php echo esc_attr($job_title); ?>">
                                                            <a href="<?php echo esc_url( $_editlink ); ?>" class="text-decoration-none text-truncate d-block">
                                                                <?php echo esc_html($display_title); ?>
                                                            </a>
                                                        </h6>
                                                        <div class="small text-muted">
                                                            <span style="font-size: 0.8rem;"><?php echo esc_html( $formatted_total ) . ' '. esc_html__( 'Job', 'computer-repair-shop' ) . '# ' . esc_html($job_number); ?></span>
                                                            <span class="mx-1">•</span>
                                                            <span style="font-size: 0.8rem;"><?php echo esc_html($job_date); ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="flex-shrink-0 ms-2">
                                                        <span class="badge <?php echo esc_attr($status_badge_class); ?>" style="font-size: 0.7rem; padding: 0.2em 0.4em;">
                                                            <?php echo esc_html($status_display); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                        <?php endwhile; ?>
                                        <?php wp_reset_postdata(); ?>
                                    <?php else : ?>
                                        <div class="text-center p-4">
                                            <i class="bi bi-briefcase display-6 text-muted mb-2"></i>
                                            <p class="small text-muted mb-0"><?php echo esc_html__('No jobs found', 'computer-repair-shop'); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- My Estimates List -->
                    <div class="col-xl-6">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0"><?php echo esc_html__( 'My Estimates', 'computer-repair-shop' ); ?></h5>
                                <a href="<?php echo add_query_arg(array('screen' => 'estimates'), get_permalink()); ?>" class="btn btn-sm btn-outline-primary">
                                    <?php echo esc_html__( 'View All', 'computer-repair-shop' ); ?>
                                </a>
                            </div>
                            <div class="card-body p-0">
                                <div class="latest-items-list" style="max-height: 250px; overflow-y: auto;">
                                    <?php
                                    // Query for latest estimates (5-6 to fit the space)
                                    $latest_estimates_args = array(
                                        'post_type' => 'rep_estimates',
                                        'posts_per_page' => 6,
                                        'post_status' => array('publish', 'pending', 'draft', 'private', 'future'),
                                        'orderby' => 'date',
                                        'order' => 'DESC',
                                        'meta_query' => array(
                                            array(
                                                'key' => '_customer',
                                                'value' => $user_id,
                                                'compare' => '=',
                                                'type' => 'NUMERIC'
                                            )
                                        )
                                    );
                                    
                                    $latest_estimates = new WP_Query($latest_estimates_args);
                                    
                                    if ($latest_estimates->have_posts()) :
                                        while ($latest_estimates->have_posts()) : $latest_estimates->the_post();
                                            $estimate_id = get_the_ID();
                                            $estimate_title = get_the_title();
                                            $estimate_date = get_the_date('M j');
                                            $estimate_status = get_post_meta($estimate_id, '_wc_estimate_status', true);
                                            $estimate_total = wc_order_grand_total($estimate_id, 'grand_total');
                                            
                                            // Truncate title if too long
                                            $display_title = strlen($estimate_title) > 30 ? substr($estimate_title, 0, 27) . '...' : $estimate_title;
                                            
                                            // Status badge
                                            $status_badge_class = 'bg-secondary';
                                            $status_display = ucfirst($estimate_status ?: 'pending');
                                            
                                            if ($estimate_status === 'approved') {
                                                $status_badge_class = 'bg-success';
                                            } elseif ($estimate_status === 'rejected') {
                                                $status_badge_class = 'bg-danger';
                                                $status_display = 'Rejected';
                                            } elseif (empty($estimate_status)) {
                                                $status_badge_class = 'bg-warning';
                                                $status_display = 'Pending';
                                            }
                                            
                                            // Format total
                                            $formatted_total = $estimate_total ? wc_cr_currency_format($estimate_total) : 'N/A';
                                            $_case_number = get_post_meta( $estimate_id, '_case_number', true );
                                            $_editlink = add_query_arg( array( 'addms_viewjob' => 'yes', 'wc_case_number' => $_case_number, 'screen' => 'print-screen', 'job_id' => $estimate_id, 'order_id' => $estimate_id, 'data-security' => wp_create_nonce( 'wcrb_nonce_printscreen' ), 'template' => 'customer_job' ), get_the_permalink( $_mainpage ) );
                                            ?>
                                            
                                            <div class="latest-item p-3 border-bottom">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="flex-grow-1" style="min-width: 0;">
                                                        <h6 class="mb-1" style="font-size: 0.9rem;" title="<?php echo esc_attr($estimate_title); ?>">
                                                            <a href="<?php echo esc_url( $_editlink ); ?>" class="text-decoration-none text-truncate d-block">
                                                                <?php echo esc_html($display_title); ?>
                                                            </a>
                                                        </h6>
                                                        <div class="small text-muted">
                                                            <span style="font-size: 0.8rem;"><?php echo esc_html($formatted_total); ?></span>
                                                            <span class="mx-1">•</span>
                                                            <span style="font-size: 0.8rem;"><?php echo esc_html($estimate_date); ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="flex-shrink-0 ms-2">
                                                        <span class="badge <?php echo esc_attr($status_badge_class); ?>" style="font-size: 0.7rem; padding: 0.2em 0.4em;">
                                                            <?php echo esc_html($status_display); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                        <?php endwhile; ?>
                                        <?php wp_reset_postdata(); ?>
                                    <?php else : ?>
                                        <div class="text-center p-4">
                                            <i class="bi bi-file-text display-6 text-muted mb-2"></i>
                                            <p class="small text-muted mb-0"><?php echo esc_html__('No estimates found', 'computer-repair-shop'); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else : ?>
                <!-- Device Types -->
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><?php echo esc_html__( 'Device Types', 'computer-repair-shop' ); ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="height: 250px;">
                                <canvas id="deviceTypeChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Performance Metrics -->
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><?php echo esc_html__( 'Performance', 'computer-repair-shop' ); ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="height: 250px;">
                                <canvas id="performanceChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php
                    // Get current user info for filtering
                    $current_user = wp_get_current_user();
                    $user_id      = $current_user->ID;
                    $user_roles   = $current_user->roles;

                    $is_administrator = in_array('administrator', $user_roles);
                    $is_store_manager = in_array('store_manager', $user_roles);
                    $is_technician    = in_array('technician', $user_roles);
                    $is_customer      = in_array('customer', $user_roles);

                    // Get WCRB_JOB_HISTORY_LOGS instance
                    $history_logs = WCRB_JOB_HISTORY_LOGS::getInstance();

                    // Global database prefix
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'wc_cr_job_history';

                    // Build query based on user role
                    if ( $is_administrator || $is_store_manager ) {
                        // Admin and Store Manager see all history logs (both public and private)
                        $query = "SELECT h.*, p.post_title as job_title 
                                FROM {$table_name} h 
                                LEFT JOIN {$wpdb->posts} p ON h.job_id = p.ID 
                                WHERE p.post_type = 'rep_jobs' 
                                AND p.post_status IN ('publish', 'pending', 'draft', 'private', 'future')
                                ORDER BY h.datetime DESC 
                                LIMIT 20";
                        
                        $activities = $wpdb->get_results($query);
                        
                    } elseif ($is_technician) {
                        // Technician sees both public and private logs for jobs assigned to them
                        $query = $wpdb->prepare(
                            "SELECT h.*, p.post_title as job_title 
                            FROM {$table_name} h 
                            LEFT JOIN {$wpdb->posts} p ON h.job_id = p.ID 
                            LEFT JOIN {$wpdb->postmeta} pm ON h.job_id = pm.post_id 
                            WHERE p.post_type = 'rep_jobs' 
                            AND p.post_status IN ('publish', 'pending', 'draft', 'private', 'future')
                            AND (pm.meta_key = '_technician' AND (pm.meta_value = %d OR pm.meta_value LIKE %s))
                            ORDER BY h.datetime DESC 
                            LIMIT 20",
                            $user_id,
                            '%' . $wpdb->esc_like($user_id) . '%'
                        );
                        $activities = $wpdb->get_results($query);
                        
                    } elseif ($is_customer) {
                        // Customer sees ONLY PUBLIC logs from their own jobs
                        $query = $wpdb->prepare(
                            "SELECT h.*, p.post_title as job_title 
                            FROM {$table_name} h 
                            LEFT JOIN {$wpdb->posts} p ON h.job_id = p.ID 
                            LEFT JOIN {$wpdb->postmeta} pm ON h.job_id = pm.post_id 
                            WHERE p.post_type = 'rep_jobs' 
                            AND p.post_status IN ('publish', 'pending', 'draft', 'private', 'future')
                            AND pm.meta_key = '_customer' 
                            AND pm.meta_value = %d
                            AND h.type = 'public'  -- CRITICAL: Only show public logs to customers
                            ORDER BY h.datetime DESC 
                            LIMIT 20",
                            $user_id
                        );
                        
                        $activities = $wpdb->get_results($query);
                    }
                    ?>

                    <!-- Recent Activity - Full width in left column -->
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0"><?php echo esc_html__( 'Recent Activity', 'computer-repair-shop' ); ?></h5>
                                <a href="#" class="btn btn-sm btn-outline-primary"><?php echo esc_html__( 'View All', 'computer-repair-shop' ); ?></a>
                            </div>
                            <div class="card-body">
                                <div class="activity-timeline" style="max-height: 550px; overflow-y: auto;">
                                    <?php
                                    // Display activities
                                    if (!empty($activities)) :
                                        $activity_count = 0;
                                        foreach ($activities as $activity) :
                                            $activity_count++;
                                            //if ($activity_count > 4) break; // Show only 4 most recent
                                            
                                            $job_id         = $activity->job_id;
                                            $name           = $activity->name;
                                            $type           = $activity->type; // This is 'public' or 'private'
                                            $field          = $activity->field;
                                            $change_detail  = $activity->change_detail;
                                            $datetime       = $activity->datetime;
                                            $user_id        = $activity->user_id;
                                            $job_title      = !empty($activity->job_title) ? $activity->job_title : __('Job #', 'computer-repair-shop') . $job_id;
                                            
                                            // For customers, we should never show private logs, but double-check
                                            if ( $is_customer && $type !== 'public' ) {
                                                continue; // Skip any non-public logs for customers
                                            }
                                            
                                            // Get user info
                                            $user_info = get_userdata($user_id);
                                            $user_name = '';
                                            if ($user_info) {
                                                $first_name = $user_info->first_name;
                                                $last_name = $user_info->last_name;
                                                $user_name = trim($first_name . ' ' . $last_name);
                                                if (empty($user_name)) {
                                                    $user_name = $user_info->display_name;
                                                }
                                            }
                                            
                                            // Format datetime
                                            $date_format    = get_option('date_format');
                                            $time_format    = get_option('time_format');
                                            $formatted_date = date_i18n($date_format, strtotime($datetime));
                                            $formatted_time = date_i18n($time_format, strtotime($datetime));
                                            
                                            // Get time ago
                                            $time_ago = $history_logs->get_time_ago(strtotime($datetime));
                                            
                                            // Determine activity type and icon/color
                                            $activity_type = esc_html__( 'Public', 'computer-repair-shop' );
                                            $activity_icon = 'bi-info-circle';
                                            $activity_color = 'info';
                                            
                                            // Get job display data
                                            $jobs_manager = WCRB_JOBS_MANAGER::getInstance();
                                            $job_data = $jobs_manager->get_job_display_data($job_id);
                                            $formatted_job_number = !empty($job_data['formatted_job_number']) ? $job_data['formatted_job_number'] : $job_id;
                                            
                                            $_case_number = get_the_title( $job_id );
                                            $device_display = $_case_number;
                                            
                                            // Determine badge text based on activity type
                                            $badge_text = ucfirst( $activity_type );
                                            
                                            // Add private indicator for staff users
                                            if ( ! $is_customer && $type === 'private' ) {
                                                $activity_color = 'warning';
                                                $badge_text     = esc_html__('Private', 'computer-repair-shop');
                                                $activity_icon  = 'bi-eye-slash';
                                            }
                                            ?>
                                            <div class="activity-item d-flex mb-3 border">
                                                <div class="activity-icon bg-<?php echo esc_attr( $activity_color ); ?> p-2 me-3">
                                                    <i class="bi <?php echo esc_attr( $activity_icon ); ?> text-white"></i>
                                                </div>
                                                <div class="activity-content flex-grow-1">
                                                    <h6 class="mb-1">
                                                        <?php 
                                                            $_editlink = '';
                                                            if ( $is_customer ) {
                                                                $_editlink = add_query_arg( array( 'addms_viewjob' => 'yes', 'wc_case_number' => $_case_number, 'screen' => 'print-screen', 'job_id' => $job_id, 'order_id' => $job_id, 'data-security' => wp_create_nonce( 'wcrb_nonce_printscreen' ), 'template' => 'customer_job' ), get_the_permalink( $_mainpage ) );
                                                            } else {
                                                                $_editlink = admin_url('post.php?post=' . $job_id . '&action=edit');
                                                            }
                                                        ?>
                                                        <a href="<?php echo esc_url( $_editlink ); ?>" target="_blank" class="text-decoration-none">
                                                            <?php echo esc_html( $name ); ?>
                                                        </a>
                                                    </h6>
                                                    <p class="text-strong">
                                                        <?php
                                                            $_change_detail = $history_logs->return_change_detail_by_field( $change_detail, $field, $type ); 
                                                            echo wp_kses( $_change_detail, $allowedHTML );
                                                        ?>
                                                    </p>
                                                    <p class="text-muted mb-1">
                                                        <?php echo esc_html($device_display); ?> 
                                                        (<?php echo esc_html__('Job #', 'computer-repair-shop') . ' ' . esc_html($formatted_job_number); ?>)
                                                    </p>
                                                    <small class="text-muted">
                                                        <?php echo esc_html($time_ago); ?> 
                                                        <?php if ($user_name && !$is_customer) : ?>
                                                            • <?php echo esc_html__('by', 'computer-repair-shop') . ' ' . esc_html($user_name); ?>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                                <div class="activity-badge">
                                                    <span class="badge bg-<?php echo esc_attr($activity_color); ?>">
                                                        <?php echo esc_html($badge_text); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <div class="text-center py-4">
                                            <i class="bi bi-activity display-4 text-muted mb-3"></i>
                                            <h6 class="text-muted"><?php echo esc_html__('No Recent Activity', 'computer-repair-shop'); ?></h6>
                                            <p class="small text-muted"><?php echo esc_html__('Your jobs will show updates here', 'computer-repair-shop'); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div><!-- Recent Activity ends /-->
            </div>
        </div>

        <!-- Right Column (4 columns) -->
        <div class="col-xl-4">
            <div class="row g-4">
                <!-- Priority Jobs -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0"><?php echo esc_html__( 'Priority Jobs', 'computer-repair-shop' ); ?></h5>
                            <span class="badge bg-danger"><?php echo esc_html__( 'High Priority', 'computer-repair-shop' ); ?></span>
                        </div>
                        <div class="card-body p-0">
                            <div class="priority-jobs-list" style="max-height: 300px; overflow-y: auto;">
                                <?php
                                // Get current user info for filtering
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
                                
                                // Build meta query for priority jobs
                                $priority_meta_query = array(
                                    'relation' => 'OR',
                                    array(
                                        'key'     => '_wc_job_priority',
                                        'value'   => 'urgent',
                                        'compare' => '=',
                                    ),
                                    array(
                                        'key'     => '_wc_job_priority',
                                        'value'   => 'high',
                                        'compare' => '=',
                                    )
                                );
                                
                                // Build user-specific conditions
                                $user_meta_query = array();
                                
                                if ($is_customer) {
                                    // Customer only sees their own priority jobs
                                    $user_meta_query[] = array(
                                        'key' => '_customer',
                                        'value' => $user_id,
                                        'compare' => '=',
                                        'type' => 'NUMERIC'
                                    );
                                } elseif ($is_technician) {
                                    // Technician sees priority jobs assigned to them
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
                                // Admin and Store Manager see all priority jobs
                                
                                // Exclude cancelled and delivered jobs
                                $status_meta_query = array(
                                    'relation' => 'AND',
                                    array(
                                        'key' => '_wc_order_status',
                                        'value' => $job_status_cancelled,
                                        'compare' => '!='
                                    ),
                                    array(
                                        'key' => '_wc_order_status',
                                        'value' => $job_status_delivered,
                                        'compare' => '!='
                                    )
                                );
                                
                                // Build the complete query args
                                $args = array(
                                    'post_type' => 'rep_jobs',
                                    'posts_per_page' => 4, // Reduced to 4 for better fit
                                    'post_status' => array('publish', 'pending', 'draft', 'private', 'future'),
                                    'meta_query' => array(
                                        'relation' => 'AND',
                                        $priority_meta_query,
                                        $status_meta_query
                                    ),
                                    'meta_key' => '_wc_job_priority',
                                    'orderby' => array(
                                        'meta_value' => 'DESC', // Sort by priority (urgent > high)
                                        'date' => 'ASC' // Then by oldest first
                                    )
                                );
                                
                                // Add user-specific conditions if not admin/store manager
                                if (!empty($user_meta_query) && !$is_administrator && !$is_store_manager) {
                                    $args['meta_query'][] = $user_meta_query;
                                }
                                
                                $priority_jobs_query = new WP_Query($args);
                                
                                if ($priority_jobs_query->have_posts()) :
                                    $priority_count = 0;
                                    while ($priority_jobs_query->have_posts()) : $priority_jobs_query->the_post();
                                        $job_id = get_the_ID();
                                        $priority_count++;
                                        
                                        // Get job data
                                        $jobs_manager = WCRB_JOBS_MANAGER::getInstance();
                                        $job_data = $jobs_manager->get_job_display_data($job_id);
                                        $_job_id = (!empty($job_data['formatted_job_number'])) ? $job_data['formatted_job_number'] : $job_id;
                                        
                                        // Get job priority
                                        $priority = get_post_meta($job_id, '_wc_job_priority', true);
                                        $priority_label = '';
                                        $priority_badge_class = '';
                                        
                                        switch($priority) {
                                            case 'urgent':
                                                $priority_label = esc_html__('Urgent', 'computer-repair-shop');
                                                $priority_badge_class = 'bg-danger';
                                                break;
                                            case 'high':
                                                $priority_label = esc_html__('High', 'computer-repair-shop');
                                                $priority_badge_class = 'bg-warning';
                                                break;
                                            case 'medium':
                                                $priority_label = esc_html__('Medium', 'computer-repair-shop');
                                                $priority_badge_class = 'bg-info';
                                                break;
                                            case 'normal':
                                                $priority_label = esc_html__('Normal', 'computer-repair-shop');
                                                $priority_badge_class = 'bg-primary';
                                                break;
                                            case 'low':
                                                $priority_label = esc_html__('Low', 'computer-repair-shop');
                                                $priority_badge_class = 'bg-secondary';
                                                break;
                                            default:
                                                $priority_label = esc_html__('Normal', 'computer-repair-shop');
                                                $priority_badge_class = 'bg-primary';
                                        }
                                        
                                        // Get job status
                                        $status = get_post_meta($job_id, '_wc_order_status', true);
                                        $status_label = $status;
                                        $status_badge_class = 'bg-secondary';
                                        
                                        // Simple status badge mapping
                                        if ($status === $job_status_delivered) {
                                            $status_badge_class = 'bg-success';
                                        } elseif ($status === $job_status_cancelled) {
                                            $status_badge_class = 'bg-danger';
                                        } else {
                                            // All other statuses are active
                                            $status_badge_class = 'bg-warning';
                                        }
                                        $device_name = get_the_title();
                                        
                                        // Truncate device name for display
                                        $max_length = 20;
                                        $device_display = $_case_number = $device_name;
                                        
                                        // Get customer name
                                        $customer_id = get_post_meta($job_id, '_customer', true);
                                        $customer_name = '';
                                        
                                        if ($customer_id) {
                                            $customer = get_user_by('ID', $customer_id);
                                            if ($customer) {
                                                $customer_name = $customer->display_name;
                                            }
                                        }
                                        
                                        // Get pickup date
                                        $pickup_date = get_post_meta($job_id, '_pickup_date', true);
                                        $formatted_date = '';
                                        
                                        if ($pickup_date) {
                                            $formatted_date = date_i18n(get_option('date_format'), strtotime($pickup_date));
                                        }
                                        ?>
                                        
                                        <div class="priority-job-item p-3 border-bottom <?php echo ($priority === 'urgent') ? 'urgent-job' : ''; ?>">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1 me-2" style="min-width: 0;">
                                                    <h6 class="mb-1" style="font-size: 0.9rem;">
                                                        <?php
                                                            $_editlink = '';
                                                            if ( $is_customer ) {
                                                                $_editlink = add_query_arg( array( 'addms_viewjob' => 'yes', 'wc_case_number' => $_case_number, 'screen' => 'print-screen', 'job_id' => $job_id, 'order_id' => $job_id, 'data-security' => wp_create_nonce( 'wcrb_nonce_printscreen' ), 'template' => 'customer_job' ), get_the_permalink( $_mainpage ) );
                                                            } else {
                                                                $_editlink = admin_url('post.php?post=' . $job_id . '&action=edit');
                                                            }
                                                        ?>
                                                        <a href="<?php echo esc_url( $_editlink ); ?>" target="_blank" class="text-decoration-none text-truncate d-block" title="<?php echo esc_attr($device_name); ?>">
                                                            <?php echo esc_html($device_display); ?>
                                                        </a>
                                                    </h6>
                                                    <div class="small text-muted">
                                                        <?php if ($customer_name && !$is_customer) : ?>
                                                            <div class="text-truncate" style="font-size: 0.8rem;" title="<?php echo esc_attr($customer_name); ?>">
                                                                <?php echo esc_html__('C:', 'computer-repair-shop') . ' ' . esc_html($customer_name); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div style="font-size: 0.8rem;"><?php echo esc_html__('Job #:', 'computer-repair-shop') . ' ' . esc_html($_job_id); ?></div>
                                                        <?php if ($formatted_date) : ?>
                                                            <div style="font-size: 0.8rem;"><?php echo esc_html__('Pickup:', 'computer-repair-shop') . ' ' . esc_html($formatted_date); ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="flex-shrink-0 text-end" style="min-width: 70px;">
                                                    <div class="mb-1">
                                                        <span class="badge <?php echo esc_attr($priority_badge_class); ?>" style="font-size: 0.7rem; padding: 0.2em 0.4em;">
                                                            <?php echo esc_html($priority_label); ?>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <small class="badge <?php echo esc_attr($status_badge_class); ?>" style="font-size: 0.65rem; padding: 0.15em 0.3em;">
                                                            <?php echo esc_html(ucfirst($status_label)); ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <?php if ($priority === 'urgent') : ?>
                                                <div class="urgent-alert mt-1">
                                                    <small class="text-danger d-flex align-items-center" style="font-size: 0.7rem;">
                                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                                        <?php echo esc_html__('Urgent', 'computer-repair-shop'); ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                    <?php endwhile; ?>
                                    
                                    <?php if ($priority_count < 4) : ?>
                                        <div class="text-center text-muted p-3">
                                            <small style="font-size: 0.8rem;">
                                                <i class="bi bi-check-circle me-1"></i>
                                                <?php 
                                                echo sprintf(
                                                    esc_html__('%d priority job(s)', 'computer-repair-shop'),
                                                    $priority_count
                                                );
                                                ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                    
                                <?php else : ?>
                                    <div class="text-center p-4">
                                        <i class="bi bi-check-circle display-6 text-success mb-2"></i>
                                        <h6 class="text-muted" style="font-size: 0.9rem;"><?php echo esc_html__('No Priority Jobs', 'computer-repair-shop'); ?></h6>
                                        <p class="small text-muted" style="font-size: 0.8rem;"><?php echo esc_html__('All jobs at normal priority', 'computer-repair-shop'); ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <?php wp_reset_postdata(); ?>
                            </div>
                            
                            <?php if ($priority_jobs_query->found_posts > 4) : ?>
                                <div class="card-footer text-center pt-2 pb-2">
                                    <?php $_mainpageurl = get_the_permalink( $_mainpage );
                                          $_mainpageurl = add_query_arg( array( 'screen' => 'jobs', 'wc_job_priority' => 'urgent' ), $_mainpageurl ); ?>
                                    <a href="<?php echo esc_url( $_mainpageurl ); ?>" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-list-ul me-1"></i>
                                        <?php 
                                        echo sprintf(
                                            esc_html__('View All (%d)', 'computer-repair-shop'),
                                            $priority_jobs_query->found_posts
                                        );
                                        ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Appointments -->
                <style type="text/css">
                    /* Add this to your stylesheet */
                    .appointment-item .badge {
                        white-space: nowrap;
                    }

                    .appointment-map {
                        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    }

                    .appointment-map iframe {
                        border-radius: 4px;
                    }

                    /* Mobile responsiveness */
                    @media (max-width: 768px) {
                        .appointment-item .row {
                            flex-direction: column;
                        }
                        
                        .appointment-item .col-md-5 {
                            margin-top: 10px;
                        }
                        
                        .appointment-map {
                            height: 120px !important;
                        }
                    }
                </style>
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><?php echo esc_html__( 'Upcoming Visits', 'computer-repair-shop' ); ?></h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="appointment-list" style="height:350px; overflow-y:scroll;">
                                
                                <!-- Appointment Item 1 -->
                                <div class="appointment-item p-3 border-bottom">
                                    <div class="row">
                                        <!-- Left Column: Appointment Details -->
                                        <div class="col-lg-8 col-md-7">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <div>
                                                    <h6 class="mb-1" style="font-size: 0.9rem;">Laptop Diagnostic</h6>
                                                    <small class="text-muted" style="font-size: 0.8rem;">Dell Inspiron 15</small>
                                                </div>
                                                <span class="badge bg-primary d-lg-none d-md-none d-block mb-2" style="font-size: 0.7rem;">Tomorrow</span>
                                                <span class="badge bg-primary d-none d-lg-block d-md-block" style="font-size: 0.7rem;">Tomorrow</span>
                                            </div>
                                            <small class="text-muted d-block mb-2" style="font-size: 0.8rem;">10:00 AM - 11:30 AM</small>
                                            
                                            <!-- Customer Address -->
                                            <div class="customer-address mb-2">
                                                <small class="text-muted d-flex align-items-center" style="font-size: 0.8rem;">
                                                    <i class="fas fa-map-marker-alt me-1" style="font-size: 0.8rem;"></i>
                                                    123 Main Street, San Francisco, CA 94105
                                                </small>
                                            </div>
                                            
                                            <!-- Customer Info -->
                                            <div class="customer-info">
                                                <small class="text-muted d-flex align-items-center" style="font-size: 0.8rem;">
                                                    <i class="fas fa-user me-1" style="font-size: 0.8rem;"></i>
                                                    John Smith • (555) 123-4567
                                                </small>
                                            </div>
                                        </div>
                                        
                                        <!-- Right Column: Map -->
                                        <div class="col-lg-4 col-md-5 mt-2 mt-md-0">
                                            <div class="appointment-map" style="height: 150px; border-radius: 4px; overflow: hidden;">
                                                <!-- Google Maps Embed -->
                                                <iframe 
                                                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3153.681434336427!2d-122.41941548468158!3d37.77492977975915!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8085808c3d6b3f3f%3A0x8d7c5b9a7b5b5b5b!2s123%20Main%20St%2C%20San%20Francisco%2C%20CA%2094105!5e0!3m2!1sen!2sus!4v1234567890123!5m2!1sen!2sus" 
                                                    width="100%" 
                                                    height="150" 
                                                    style="border:0;" 
                                                    allowfullscreen="" 
                                                    loading="lazy" 
                                                    referrerpolicy="no-referrer-when-downgrade">
                                                </iframe>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Appointment Item 2 -->
                                <div class="appointment-item p-3 border-bottom">
                                    <div class="row">
                                        <!-- Left Column: Appointment Details -->
                                        <div class="col-lg-8 col-md-7">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <div>
                                                    <h6 class="mb-1" style="font-size: 0.9rem;">Screen Replacement</h6>
                                                    <small class="text-muted" style="font-size: 0.8rem;">MacBook Pro 14"</small>
                                                </div>
                                                <span class="badge bg-warning d-lg-none d-md-none d-block mb-2" style="font-size: 0.7rem;">Today</span>
                                                <span class="badge bg-warning d-none d-lg-block d-md-block" style="font-size: 0.7rem;">Today</span>
                                            </div>
                                            <small class="text-muted d-block mb-2" style="font-size: 0.8rem;">2:00 PM - 3:30 PM</small>
                                            
                                            <!-- Customer Address -->
                                            <div class="customer-address mb-2">
                                                <small class="text-muted d-flex align-items-center" style="font-size: 0.8rem;">
                                                    <i class="fas fa-map-marker-alt me-1" style="font-size: 0.8rem;"></i>
                                                    456 Tech Avenue, San Jose, CA 95113
                                                </small>
                                            </div>
                                            
                                            <!-- Customer Info -->
                                            <div class="customer-info">
                                                <small class="text-muted d-flex align-items-center" style="font-size: 0.8rem;">
                                                    <i class="fas fa-user me-1" style="font-size: 0.8rem;"></i>
                                                    Sarah Johnson • (555) 987-6543
                                                </small>
                                            </div>
                                        </div>
                                        
                                        <!-- Right Column: Map -->
                                        <div class="col-lg-4 col-md-5 mt-2 mt-md-0">
                                            <div class="appointment-map" style="height: 150px; border-radius: 4px; overflow: hidden;">
                                                <!-- Google Maps Embed -->
                                                <iframe 
                                                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3172.3323495308524!2d-121.88699468472234!3d37.3388475798426!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x808fcc5b8b6b6b6b%3A0x8b7c5b9a7b5b5b5b!2s456%20Tech%20Ave%2C%20San%20Jose%2C%20CA%2095113!5e0!3m2!1sen!2sus!4v1234567890123!5m2!1sen!2sus" 
                                                    width="100%" 
                                                    height="150" 
                                                    style="border:0;" 
                                                    allowfullscreen="" 
                                                    loading="lazy" 
                                                    referrerpolicy="no-referrer-when-downgrade">
                                                </iframe>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Appointment Item 3 -->
                                <div class="appointment-item p-3">
                                    <div class="row">
                                        <!-- Left Column: Appointment Details -->
                                        <div class="col-lg-8 col-md-7">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <div>
                                                    <h6 class="mb-1" style="font-size: 0.9rem;">Virus Removal</h6>
                                                    <small class="text-muted" style="font-size: 0.8rem;">HP Pavilion</small>
                                                </div>
                                                <span class="badge bg-success d-lg-none d-md-none d-block mb-2" style="font-size: 0.7rem;">Tomorrow</span>
                                                <span class="badge bg-success d-none d-lg-block d-md-block" style="font-size: 0.7rem;">Tomorrow</span>
                                            </div>
                                            <small class="text-muted d-block mb-2" style="font-size: 0.8rem;">11:00 AM - 12:00 PM</small>
                                            
                                            <!-- Customer Address -->
                                            <div class="customer-address mb-2">
                                                <small class="text-muted d-flex align-items-center" style="font-size: 0.8rem;">
                                                    <i class="fas fa-map-marker-alt me-1" style="font-size: 0.8rem;"></i>
                                                    789 Innovation Way, Palo Alto, CA 94301
                                                </small>
                                            </div>
                                            
                                            <!-- Customer Info -->
                                            <div class="customer-info">
                                                <small class="text-muted d-flex align-items-center" style="font-size: 0.8rem;">
                                                    <i class="fas fa-user me-1" style="font-size: 0.8rem;"></i>
                                                    Michael Chen • (555) 456-7890
                                                </small>
                                            </div>
                                        </div>
                                        
                                        <!-- Right Column: Map -->
                                        <div class="col-lg-4 col-md-5 mt-2 mt-md-0">
                                            <div class="appointment-map" style="height: 150px; border-radius: 4px; overflow: hidden;">
                                                <!-- Google Maps Embed -->
                                                <iframe 
                                                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3168.635259966661!2d-122.16071918471973!3d37.44188337983366!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x808fbb5b8b6b6b6b%3A0x8b7c5b9a7b5b5b5b!2s789%20Innovation%20Way%2C%20Palo%20Alto%2C%20CA%2094301!5e0!3m2!1sen!2sus!4v1234567890123!5m2!1sen!2sus" 
                                                    width="100%" 
                                                    height="150" 
                                                    style="border:0;" 
                                                    allowfullscreen="" 
                                                    loading="lazy" 
                                                    referrerpolicy="no-referrer-when-downgrade">
                                                </iframe>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><?php echo esc_html__( 'Quick Actions', 'computer-repair-shop' ); ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <?php
                                    $_jobURL = ( $role == 'administrator' || $role == 'store_manager' || $role == 'technician' ) ? esc_url( admin_url('post-new.php?post_type=rep_jobs') ) : add_query_arg( array( 'screen' => 'book-my-device' ), get_the_permalink( $_mainpage ) );
                                ?>
                                <a class="btn btn-primary btn-sm" href="<?php echo esc_url( $_jobURL ); ?>" target="_blank">
                                    <i class="bi bi-plus-circle me-2"></i>
                                    <?php echo esc_html__( 'New Job', 'computer-repair-shop' ); ?>
                                </a>
                                <a class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-calendar-plus me-2"></i>
                                    <?php echo esc_html__( 'Schedule Appointment', 'computer-repair-shop' ); ?>
                                </a>
                                <?php if ( $role == 'administrator' || $role == 'store_manager' || $role == 'technician' ) : ?>
                                <a class="btn btn-outline-success btn-sm" href="<?php echo esc_url( admin_url('post-new.php?post_type=rep_estimates') ); ?>" target="_blank">
                                    <i class="bi bi-file-text me-2"></i>
                                    <?php echo esc_html__( 'Create Estimate', 'computer-repair-shop' ); ?>
                                </a>
                                <a class="btn btn-outline-info btn-sm" href="<?php echo esc_url( admin_url('admin.php?page=wc-computer-rep-reports') ); ?>" target="_blank">
                                    <i class="bi bi-graph-up me-2"></i>
                                    <?php echo esc_html__( 'Generate Report', 'computer-repair-shop' ); ?>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>