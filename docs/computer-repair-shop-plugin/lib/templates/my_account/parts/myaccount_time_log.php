<?php
    defined( 'ABSPATH' ) || exit;

    // Get current user data
    $current_user   = wp_get_current_user();
    $technician_id  = $current_user->ID;

    //wcrb_timelog_script
    wp_enqueue_script( 'wcrb_timelog_script' );

    $_user_role = $current_user->roles[0] ?? 'guest';
    if ( $_user_role != 'technician' && $_user_role != 'administrator' && $_user_role != 'store_manager' ) {
        echo esc_html__( "You do not have sufficient permissions to access this page.", "computer-repair-shop" );
        exit;
    }

    if ( ! wc_rs_license_state() ) {
        echo esc_html__( 'This is a pro feature please activate your license.', 'computer-repair-shop' );
        return;
    }

    $WCRB_TIME_MANAGEMENT = WCRB_TIME_MANAGEMENT::getInstance();
    $dashboard            = WCRB_MYACCOUNT_DASHBOARD::getInstance();
    $jobs_manager         = WCRB_JOBS_MANAGER::getInstance();

    //cards Stats Data
    $table_name           = $wpdb->prefix . 'wc_cr_time_logs';
        
    // Get current date ranges
    $today_start    = date( 'Y-m-d 00:00:00' );
    $today_end      = date( 'Y-m-d 23:59:59' );
    $week_start     = date( 'Y-m-d 00:00:00', strtotime( 'monday this week' ) );
    $week_end       = date( 'Y-m-d 23:59:59', strtotime( 'sunday this week' ) );
    $month_start    = date( 'Y-m-01 00:00:00' );
    $month_end      = date( 'Y-m-t 23:59:59' );
    
    // Get stats data
    $stats          = $WCRB_TIME_MANAGEMENT->get_technician_time_stats( $technician_id, $today_start, $today_end, $week_start, $week_end, $month_start, $month_end );
?>

<!-- Time Logs Content -->
<main class="dashboard-content container-fluid py-4">
<!-- Stats Overview -->
<div class="row g-3 mb-4">
    <!-- Today's Hours -->
    <div class="col">
        <div class="card stats-card bg-primary text-white">
            <div class="card-body text-center p-3">
                <h6 class="card-title text-white-50 mb-1"><?php echo esc_html__( 'Today', 'computer-repair-shop' ); ?></h6>
                <h4 class="mb-0" id="todayHours"><?php echo esc_html( $stats['today_hours'] ); ?>h</h4>
                <small class="text-white-50"><?php echo esc_html__( 'Hours Logged', 'computer-repair-shop' ); ?></small>
            </div>
        </div>
    </div>
    
    <!-- This Week -->
    <div class="col">
        <div class="card stats-card bg-success text-white">
            <div class="card-body text-center p-3">
                <h6 class="card-title text-white-50 mb-1"><?php echo esc_html__( 'This Week', 'computer-repair-shop' ); ?></h6>
                <h4 class="mb-0" id="weekHours"><?php echo esc_html( $stats['week_hours'] ); ?>h</h4>
                <small class="text-white-50"><?php echo esc_html__( 'Total Hours', 'computer-repair-shop' ); ?></small>
            </div>
        </div>
    </div>
   
    <!-- Billable Hours -->
    <div class="col">
        <div class="card stats-card bg-warning text-dark">
            <div class="card-body text-center p-3">
                <h6 class="card-title mb-1"><?php echo esc_html__( 'Billable', 'computer-repair-shop' ); ?></h6>
                <h4 class="mb-0" id="billableRate"><?php echo esc_html( $stats['billable_rate'] ); ?>%</h4>
                <small class="text-muted"><?php echo esc_html__( 'This Week', 'computer-repair-shop' ); ?></small>
            </div>
        </div>
    </div>

    <!-- Month's Earnings -->
    <div class="col">
        <div class="card stats-card bg-success text-white">
            <div class="card-body text-center p-3">
                <h6 class="card-title text-white-50 mb-1"><?php echo esc_html__( 'Month\'s Earnings', 'computer-repair-shop' ); ?></h6>
                <h4 class="mb-0" id="monthEarnings">
                    <?php echo esc_html( wc_cr_currency_format( $stats['month_earnings'] ) ); ?>
                </h4>
                <small class="text-white-50"><?php echo esc_html__( 'This Month', 'computer-repair-shop' ); ?></small>
            </div>
        </div>
    </div>
    
    <!-- Completed Jobs -->
    <div class="col">
        <div class="card stats-card bg-secondary text-white">
            <div class="card-body text-center p-3">
                <h6 class="card-title text-white-50 mb-1"><?php echo esc_html__( 'Completed', 'computer-repair-shop' ); ?></h6>
                <h4 class="mb-0" id="completedJobs"><?php echo esc_html( $stats['completed_jobs'] ); ?></h4>
                <small class="text-white-50"><?php echo esc_html__( 'This Month', 'computer-repair-shop' ); ?></small>
            </div>
        </div>
    </div>
    
    <!-- Avg. Time Per Job -->
    <div class="col">
        <div class="card stats-card bg-dark text-white">
            <div class="card-body text-center p-3">
                <h6 class="card-title text-white-50 mb-1"><?php echo esc_html__( 'Avg. Time', 'computer-repair-shop' ); ?></h6>
                <h4 class="mb-0" id="avgTime"><?php echo esc_html( $stats['avg_time_per_job'] ); ?>h</h4>
                <small class="text-white-50"><?php echo esc_html__( 'Per Job', 'computer-repair-shop' ); ?></small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Left Column - Active Time Tracking -->
    <div class="col-lg-6">
        <!-- Current Time Entry -->
        <div class="card time-log-widget">
            <div class="widget-header">
                <h5 class="mb-0">
                    <i class="bi bi-play-circle me-2 text-primary"></i>
                    <?php echo esc_html__( "Current Time Entry", "computer-repair-shop" ); ?>
                    <span class="badge bg-success float-end" id="timerStatus"><?php echo esc_html__( 'Stopped', 'computer-repair-shop' ); ?></span>
                </h5>
            </div>
            <?php 
            $selected_value = '';
            if ( isset( $_GET['job_id'] ) && ! empty( $_GET['job_id'] ) && is_wcrb_current_user_have_technician_access( $_GET['job_id'] ) ) : 
                $_job_id        = sanitize_text_field( wp_unslash( $_GET['job_id'] ) );
                $_device_id     = isset( $_GET['device_id'] ) ? sanitize_text_field( wp_unslash( $_GET['device_id'] ) ) : '';
                $_device_serial = isset( $_GET['device_serial'] ) ? sanitize_text_field( wp_unslash( $_GET['device_serial'] ) ) : '';
                $_device_index  = isset( $_GET['device_index'] ) ? intval( wp_unslash( $_GET['device_index'] ) ) : 0;

                $job_data       = $jobs_manager->get_job_display_data( $_job_id );
                $forma_job_id   = ( ! empty( $job_data['formatted_job_number'] ) ) ? $job_data['formatted_job_number'] : $job->ID;

                $selected_value = '';
                if ( ! empty( $_device_id ) && ! empty( $_device_serial ) ) {
                    $selected_value = $_job_id . '|' . $_device_id . '-' . $_device_serial . '|' . $_device_index;
                } elseif ( ! empty( $_device_id ) && empty( $_device_serial ) ) {
                    $selected_value = $_job_id . '|' . $_device_id . '|' . $_device_index;
                } else {
                    $selected_value = $_job_id . '|0|' . $_device_index;
                }
            endif;

            $_select_options = '<div class="row g-3 mb-4"><div class="col">' . $WCRB_TIME_MANAGEMENT->wcrb_get_eligible_jobs_with_devices_dropdown( $selected_value) . '</div></div>';
            echo wp_kses( $_select_options, $allowedHTML );

            if ( isset( $_job_id ) && ! empty( $_job_id ) ) :
                $_case_number = get_the_title( $_job_id );
                $_device_name = ( ! empty( $_device_id ) ) ? get_the_title( $_device_id ) : '';

                $display_name = $_case_number;
                $display_name .= ! empty( $_device_name ) ? ' | ' . $_device_name : '';
                $display_name .= ! empty( $_device_serial ) ? ' (' . $_device_serial . ')' : '';
            ?>
            <div class="widget-body">
                <div class="time-entry" id="currentTimeEntry">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6 class="mb-1"><?php echo esc_html( $display_name ); ?></h6>
                            <p class="text-muted mb-2"><?php echo esc_html__( 'JOB', 'computer-repair-shop' ); ?>-<?php echo esc_html( $forma_job_id ); ?> | <?php echo esc_html__( 'Started', 'computer-repair-shop' ); ?>: <span id="startTime">--:--</span></p>
                            <div class="timer-display mb-2" id="currentTimer">00:00:00</div>
                            <input type="hidden" id="technicianId" value="<?php echo esc_attr( $technician_id ); ?>">
                            <input type="hidden" id="jobId" value="<?php echo esc_attr( $_job_id ); ?>">
                            <input type="hidden" id="deviceId" value="<?php echo esc_attr( $_device_id ?? '' ); ?>">
                            <input type="hidden" id="deviceSerial" value="<?php echo esc_attr( $_device_serial ?? '' ); ?>">
                            <input type="hidden" id="deviceIndex" value="<?php echo esc_attr( $_device_index ?? 0 ); ?>">
                            <?php wp_nonce_field( 'wcrb_timelog_nonce_action', 'wcrb_timelog_nonce_field' ); ?>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="btn-group-vertical w-100">
                                <button class="btn btn-success btn-timer mb-2" id="startTimer">
                                    <i class="bi bi-play-fill me-1"></i><?php echo esc_html__( 'Start', 'computer-repair-shop' ); ?>
                                </button>
                                <button class="btn btn-warning btn-timer mb-2" id="pauseTimer" disabled>
                                    <i class="bi bi-pause-fill me-1"></i><?php echo esc_html__( 'Pause', 'computer-repair-shop' ); ?>
                                </button>
                                <button class="btn btn-danger btn-timer" id="stopTimer" disabled>
                                    <i class="bi bi-stop-fill me-1"></i><?php echo esc_html__( 'Stop', 'computer-repair-shop' ); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Work Description -->
                    <div class="mt-3">
                        <label class="form-label fw-semibold"><?php echo esc_html__( 'Work Description', 'computer-repair-shop' ); ?></label>
                        <textarea class="form-control" rows="3" id="workDescription" placeholder="<?php echo esc_html__( 'Brief description of work performed...', 'computer-repair-shop' ); ?>"></textarea>
                    </div>

                    <!-- Activity Type -->
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?php echo esc_html__( 'Activity Type', 'computer-repair-shop' ); ?></label>
                            <?php echo wp_kses( $WCRB_TIME_MANAGEMENT->get_timelog_activity_types_dropdown( '', '' ), $allowedHTML ); ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?php echo esc_html__( 'Is Billable?', 'computer-repair-shop' ); ?></label>
                            <select class="form-select" id="isBillable">
                                <option value="1"><?php echo esc_html__( 'Yes', 'computer-repair-shop' ); ?></option>
                                <option value="0"><?php echo esc_html__( 'No', 'computer-repair-shop' ); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
                <div class="widget-body">
                    <div class="alert alert-info mb-0">
                        <?php echo sprintf( esc_html__( 'Please select a job or device to start logging time.', 'computer-repair-shop' ), $dashboard->_device_label ); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Time Entry -->
        <div class="card time-log-widget">
            <div class="widget-header">
                <h5 class="mb-0">
                    <i class="bi bi-plus-circle me-2 text-primary"></i>
                    <?php echo esc_html__( "Quick Time Entry", "computer-repair-shop" ); ?>
                </h5>
            </div>
            <?php if ( isset( $_job_id ) && ! empty( $_job_id ) ) : ?>
            <div class="widget-body">
                <form id="quickTimeForm">
                    <input type="hidden" name="technicianId" value="<?php echo esc_attr( $technician_id ); ?>">
                    <input type="hidden" name="jobId" value="<?php echo esc_attr( $_job_id ); ?>">
                    <input type="hidden" name="deviceId" value="<?php echo esc_attr( $_device_id ?? '' ); ?>">
                    <input type="hidden" name="deviceSerial" value="<?php echo esc_attr( $_device_serial ?? '' ); ?>">
                    <input type="hidden" name="deviceIndex" value="<?php echo esc_attr( $_device_index ?? 0 ); ?>">
                    <?php wp_nonce_field( 'wcrb_timelog_nonce_action', 'wcrb_timelog_nonce_field' ); ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?php echo esc_html__( 'Is Billable?', 'computer-repair-shop' ); ?></label>
                            <select class="form-select" name="isBillable_manual">
                                <option value="1"><?php echo esc_html__( 'Yes', 'computer-repair-shop' ); ?></option>
                                <option value="0"><?php echo esc_html__( 'No', 'computer-repair-shop' ); ?></option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?php echo esc_html__( 'Activity Type', 'computer-repair-shop' ); ?></label>
                            <!-- Produce name timelog_activity_type  /-->
                            <?php echo wp_kses( $WCRB_TIME_MANAGEMENT->get_timelog_activity_types_dropdown( '', 'activityType_manual' ), $allowedHTML ); ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?php echo esc_html__( 'Start Time', 'computer-repair-shop' ); ?></label>
                            <input type="datetime-local" class="form-control" name="manual_start_time" id="quickStartTime">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?php echo esc_html__( 'End Time', 'computer-repair-shop' ); ?></label>
                            <input type="datetime-local" class="form-control" name="manual_end_time" id="quickEndTime">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold"><?php echo esc_html__( 'Description', 'computer-repair-shop' ); ?></label>
                            <textarea class="form-control" rows="2" id="quickDescription" name="manual_entry_description" placeholder="<?php echo esc_html__( 'Brief description of work performed...', 'computer-repair-shop' ); ?>"></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-plus-circle me-2"></i><?php echo esc_html__( 'Add Time Entry', 'computer-repair-shop' ); ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <?php else: ?>
                <div class="widget-body">
                    <div class="alert alert-info mb-0">
                        <?php echo sprintf( esc_html__( 'Please select a job or device to start logging time.', 'computer-repair-shop' ), $dashboard->_device_label ); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Weekly Summary -->
        <div class="card time-log-widget">
            <div class="widget-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-graph-up me-2 text-primary"></i>
                    <?php echo esc_html__('Time Distribution', 'computer-repair-shop'); ?>
                </h5>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-calendar-week"></i> <?php echo esc_html__('This Week', 'computer-repair-shop'); ?>
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item active" href="#" data-chart-period="week">
                                <i class="bi bi-calendar-week me-2"></i>
                                <?php echo esc_html__('This Week', 'computer-repair-shop'); ?>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" data-chart-period="month">
                                <i class="bi bi-calendar-month me-2"></i>
                                <?php echo esc_html__('This Month', 'computer-repair-shop'); ?>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" data-chart-period="year">
                                <i class="bi bi-calendar me-2"></i>
                                <?php echo esc_html__('This Year', 'computer-repair-shop'); ?>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="#" data-chart-period="last-week">
                                <i class="bi bi-arrow-left-circle me-2"></i>
                                <?php echo esc_html__('Last Week', 'computer-repair-shop'); ?>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" data-chart-period="last-month">
                                <i class="bi bi-arrow-left-circle me-2"></i>
                                <?php echo esc_html__('Last Month', 'computer-repair-shop'); ?>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="widget-body">
                <div class="time-chart" style="position: relative; height: 250px;">
                    <canvas id="weeklyTimeChart"></canvas>
                </div>
            </div>
        </div>

        <?php
            $productivity_stats = $WCRB_TIME_MANAGEMENT->get_technician_productivity_stats( $technician_id, 'week' );
            $activity_distribution = $productivity_stats['activity_distribution'] ?? [];
        ?>
        <!-- Productivity Stats -->
        <div class="card time-log-widget">
            <div class="widget-header">
                <h5 class="mb-0">
                    <i class="bi bi-speedometer2 me-2 text-primary"></i>
                    <?php echo esc_html__( "Productivity Stats", "computer-repair-shop" ); ?>
                    <small class="text-muted float-end"><?php echo esc_html__( 'This Week', 'computer-repair-shop' ); ?></small>
                </h5>
            </div>
            <div class="widget-body">
                <div class="row text-center">
                    <div class="col-4">
                        <div class="fw-semibold text-primary" id="avgDailyHours">
                            <?php echo esc_html($productivity_stats['avg_daily_hours']); ?>h
                        </div>
                        <small class="text-muted"><?php echo esc_html__( 'Avg Daily', 'computer-repair-shop' ); ?></small>
                    </div>
                    <div class="col-4">
                        <div class="fw-semibold text-success" id="totalJobsCompleted">
                            <?php echo esc_html($productivity_stats['total_jobs_completed']); ?>
                        </div>
                        <small class="text-muted"><?php echo esc_html__( 'Jobs Done', 'computer-repair-shop' ); ?></small>
                    </div>
                    <div class="col-4">
                        <div class="fw-semibold text-info" id="efficiencyScore">
                            <?php echo esc_html($productivity_stats['efficiency_score']); ?>%
                        </div>
                        <small class="text-muted"><?php echo esc_html__( 'Efficiency', 'computer-repair-shop' ); ?></small>
                    </div>
                </div>
                <hr>
                <div class="progress-stats">
                    <?php
                    // Get user's activity types or use defaults
                    $activity_types = get_option('wcrb_timelog_activity_types', '');
                    if (empty($activity_types) || !is_array($activity_types)) {
                        $activity_types = array('Diagnosis', 'Repair', 'Testing', 'Cleaning', 'Consultation', 'Other');
                    }
                    
                    // Define colors for activities
                    $activity_colors = [
                        'repair' => 'primary',
                        'diagnostic' => 'info', 
                        'diagnosis' => 'info',
                        'testing' => 'success',
                        'test' => 'success',
                        'cleaning' => 'warning',
                        'consultation' => 'secondary',
                        'other' => 'secondary'
                    ];
                    
                    // Get default color if not defined
                    $default_color = 'secondary';
                    
                    // Display only activities that have time logged (> 0%)
                    foreach ($activity_distribution as $activity_key => $percentage) {
                        if ($percentage > 0) {
                            // Get the human-readable label
                            $label = '';
                            foreach ($activity_types as $type) {
                                $type_key = strtolower(preg_replace('/[^a-z0-9]/', '_', $type));
                                if ($type_key === $activity_key) {
                                    $label = $type;
                                    break;
                                }
                            }
                            
                            // If label not found in settings, create one from the key
                            if (empty($label)) {
                                $label = ucwords(str_replace('_', ' ', $activity_key));
                            }
                            
                            // Get color for this activity
                            $color = $default_color;
                            foreach ($activity_colors as $color_key => $color_value) {
                                if (stripos($activity_key, $color_key) !== false) {
                                    $color = $color_value;
                                    break;
                                }
                            }
                            ?>
                            <div class="d-flex justify-content-between mb-1">
                                <span><?php echo esc_html($label); ?></span>
                                <span class="fw-semibold"><?php echo esc_html($percentage); ?>%</span>
                            </div>
                            <div class="progress mb-3" style="height: 8px;">
                                <div class="progress-bar bg-<?php echo esc_attr($color); ?>" 
                                    style="width: <?php echo esc_attr($percentage); ?>%"
                                    role="progressbar">
                                </div>
                            </div>
                            <?php
                        }
                    }
                    
                    // If no activities have time logged, show a message
                    if (empty(array_filter($activity_distribution))) {
                        echo '<div class="text-center text-muted py-3">';
                        echo esc_html__('No activity data available for this period.', 'computer-repair-shop');
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column - History & Reports -->
    <div class="col-lg-6">
        <!-- Today's Time Logs -->
        <div class="card time-log-widget">
            <div class="widget-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-clock-history me-2 text-primary"></i>
                    <?php echo esc_html__( 'Your Time Logs', 'computer-repair-shop' ); ?>
                </h5>
            </div>
            <div class="widget-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover largetext mb-0 log-table">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4"><?php echo esc_html__( 'Job', 'computer-repair-shop' ); ?></th>
                                <th><?php echo esc_html__( 'Activity', 'computer-repair-shop' ); ?></th>
                                <th><?php echo esc_html__( 'Time', 'computer-repair-shop' ); ?></th>
                                <th><?php echo esc_html__( 'Duration', 'computer-repair-shop' ); ?></th>
                                <th class="text-end pe-4"><?php echo esc_html__( 'Amount', 'computer-repair-shop' ); ?></th>
                            </tr>
                        </thead>
                        <tbody id="todayLogsTable">
                            <?php
                                // Fetch and display recent time logs
                                echo wp_kses( $WCRB_TIME_MANAGEMENT->get_recent_time_logs_html( 100, '', $technician_id ), $allowedHTML );
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
</main>