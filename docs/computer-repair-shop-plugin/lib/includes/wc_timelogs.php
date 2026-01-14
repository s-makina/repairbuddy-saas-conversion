<?php
defined( 'ABSPATH' ) || exit;

/**
 * Get time logs data for admin page
 * 
 * @return array Array containing filters, summary, and table data
 */
function wcrb_get_time_logs_data() {
    if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'list_users' ) ) {
        return array(
            'error' => esc_html__( 'You do not have sufficient permissions to access this page.', 'computer-repair-shop' )
        );
    }

    global $wpdb;
    $table_name     = $wpdb->prefix . 'wc_cr_time_logs';
    $jobs_manager   = WCRB_JOBS_MANAGER::getInstance();

    // Get filter parameters
    $filter_technician  = isset($_GET['technician_id']) ? intval($_GET['technician_id']) : 0;
    $filter_job         = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
    $filter_status      = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
    $filter_date_from   = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
    $filter_date_to     = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
    $filter_activity    = isset($_GET['activity']) ? sanitize_text_field($_GET['activity']) : '';
    
    // Build query with WHERE conditions
    $where_conditions = array('1=1');
    $query_params = array();
    
    if ( $filter_technician > 0 ) {
        $where_conditions[] = "tl.technician_id = %d";
        $query_params[]     = $filter_technician;
    }
    
    if ( $filter_job > 0 ) {
        $where_conditions[] = "tl.job_id = %d";
        $_filter_job        = $jobs_manager->get_post_id_by_job_id( $filter_job );
        $query_params[]     = $_filter_job;
    }
    
    if ( ! empty( $filter_status ) ) {
        $where_conditions[] = "tl.log_state = %s";
        $query_params[]     = $filter_status;
    }
    
    if ( ! empty( $filter_activity ) ) {
        $where_conditions[] = "tl.activity = %s";
        $query_params[]     = $filter_activity;
    }
    
    if ( ! empty( $filter_date_from ) ) {
        $where_conditions[] = "DATE(tl.start_time) >= %s";
        $query_params[]     = $filter_date_from;
    }
    
    if (!empty($filter_date_to)) {
        $where_conditions[] = "DATE(tl.start_time) <= %s";
        $query_params[]     = $filter_date_to;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Get total count for pagination
    if ( ! empty( $query_params ) ) {
        $count_query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} tl WHERE {$where_clause}",
            $query_params
        );
    } else {
        $count_query = "SELECT COUNT(*) FROM {$table_name} tl WHERE {$where_clause}";
    }
    $total_logs = $wpdb->get_var( $count_query );
    
    // Pagination
    $per_page     = 100;
    $current_page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
    $offset       = ( $current_page - 1 ) * $per_page;
    $total_pages = ceil( $total_logs / $per_page );
    
    // Get unique activities for filter dropdown
    $activities = $wpdb->get_col( "SELECT DISTINCT activity FROM {$table_name} WHERE activity != '' ORDER BY activity" );
    
    // Get technicians for filter dropdown
    $technicians = $wpdb->get_results("
        SELECT DISTINCT u.ID, u.display_name
        FROM {$table_name} tl
        JOIN {$wpdb->users} u ON tl.technician_id = u.ID
        ORDER BY u.display_name
    ");
    
    // Get unique statuses
    $statuses = $wpdb->get_col("SELECT DISTINCT log_state FROM {$table_name} ORDER BY log_state");
    
    // Main query with job display data
    $base_query = "
        SELECT 
            tl.log_id,
            tl.start_time,
            tl.end_time,
            tl.time_type,
            tl.activity,
            tl.priority,
            tl.work_description,
            tl.technician_id,
            tl.job_id,
            tl.device_data,
            tl.log_state,
            tl.total_minutes,
            tl.hourly_rate,
            tl.hourly_cost,
            tl.is_billable,
            tl.approved_by,
            tl.approved_at,
            tl.rejection_reason,
            tl.created_at,
            p.post_title as job_title,
            pm.meta_value as customer_id,
            u.display_name as technician_name,
            cu.display_name as customer_name,
            cu.user_email as customer_email
        FROM {$table_name} tl
        LEFT JOIN {$wpdb->posts} p ON tl.job_id = p.ID
        LEFT JOIN {$wpdb->postmeta} pm ON tl.job_id = pm.post_id AND pm.meta_key = '_customer'
        LEFT JOIN {$wpdb->users} u ON tl.technician_id = u.ID
        LEFT JOIN {$wpdb->users} cu ON pm.meta_value = cu.ID
        WHERE {$where_clause}
        ORDER BY tl.log_id DESC
        LIMIT %d OFFSET %d
    ";
    
    // Add pagination parameters
    $pagination_params   = $query_params;
    $pagination_params[] = $per_page;
    $pagination_params[] = $offset;
    
    // Prepare the query
    $logs = $wpdb->get_results( $wpdb->prepare($base_query, $pagination_params), ARRAY_A );
    
    // Summary Stats
    $stats_query = "
        SELECT 
            COUNT(*) as total_logs,
            SUM(total_minutes) as total_minutes,
            AVG(hourly_rate) as avg_rate,
            AVG(hourly_cost) as avg_cost,
            SUM((total_minutes/60) * hourly_rate) as total_amount,
            SUM((total_minutes/60) * hourly_cost) as total_cost
        FROM {$table_name} tl
        WHERE {$where_clause}
    ";
    
    if ( ! empty( $query_params ) ) {
        $stats = $wpdb->get_row( $wpdb->prepare( $stats_query, $query_params ), ARRAY_A);
    } else {
        $stats = $wpdb->get_row( $stats_query, ARRAY_A );
    }
    
    // Process logs for display
    $processed_logs = array();
    $total_cost = $total_charged = $total_profit = 0;
    
    foreach ( $logs as $log ) {
        // Format job info
        $job_data = $jobs_manager->get_job_display_data($log['job_id']);
        $job_display = !empty($job_data['formatted_job_number']) ? $job_data['formatted_job_number'] : esc_html__( 'JOB', 'computer-repair-shop' ) . '-' . $log['job_id'];
        
        // Calculate hours
        $total_hours = $log['total_minutes'] / 60;
        
        // Calculate amounts
        $profit_amount = $cost_amount = $charged_amount = 0;
        
        if ( ! empty( $log['hourly_rate'] ) && $log['total_minutes'] > 0 ) {
            $charged_amount = $total_hours * floatval( $log['hourly_rate'] );
        }
        
        if ( ! empty( $log['hourly_cost']) && $log['total_minutes'] > 0 ) {
            $cost_amount = $total_hours * floatval($log['hourly_cost']);
        }
        
        $profit_amount = $charged_amount - $cost_amount;
        
        // Add to totals
        $total_charged += $charged_amount;
        $total_cost += $cost_amount;
        $total_profit += $profit_amount;
        
        // Status class
        $status_class = '';
        switch ($log['log_state']) {
            case 'pending':
                $status_class = 'warning';
                break;
            case 'approved':
                $status_class = 'success';
                break;
            case 'rejected':
                $status_class = 'error';
                break;
            case 'billed':
                $status_class = 'info';
                break;
            default:
                $status_class = 'secondary';
        }
        
        $processed_logs[] = array(
            'log_id'        => $log['log_id'],
            'job_display'   => $job_display,
            'job_title'     => !empty($log['job_title']) ? $log['job_title'] : '',
            'customer_name'  => !empty($log['customer_name']) ? $log['customer_name'] : 
                             (!empty($log['customer_id']) ? 'User #' . $log['customer_id'] : 'Unknown'),
            'customer_email'  => !empty($log['customer_email']) ? $log['customer_email'] : '',
            'technician_name' => !empty($log['technician_name']) ? $log['technician_name'] : 
                               'User #' . $log['technician_id'],
            'activity' => $log['activity'],
            'start_date' => !empty($log['start_time']) ? date_i18n('Y M j', strtotime($log['start_time'])) : '',
            'start_time' => !empty($log['start_time']) ? date_i18n('g:i A', strtotime($log['start_time'])) : '--',
            'end_date' => !empty($log['end_time']) ? date_i18n('Y M j', strtotime($log['end_time'])) : '',
            'end_time' => !empty($log['end_time']) ? date_i18n('g:i A', strtotime($log['end_time'])) : '--',
            'duration' => number_format($total_hours, 2) . 'h',
            'duration_full' => sprintf('%dh %dm (%.2fh)', floor($log['total_minutes'] / 60), $log['total_minutes'] % 60, $total_hours),
            'hourly_rate' => $log['hourly_rate'] ?? 0,
            'hourly_cost' => $log['hourly_cost'] ?? 0,
            'charged_amount' => $charged_amount,
            'cost_amount' => $cost_amount,
            'profit_amount' => $profit_amount,
            'log_state' => $log['log_state'],
            'status_class' => $status_class,
            'created_at' => !empty($log['created_at']) ? date_i18n('Y-m-d H:i', strtotime($log['created_at'])) : '--'
        );
    }
    
    return array(
        'filters' => array(
            'technician_id' => $filter_technician,
            'job_id' => $filter_job,
            'status' => $filter_status,
            'date_from' => $filter_date_from,
            'date_to' => $filter_date_to,
            'activity' => $filter_activity,
            'technicians' => $technicians,
            'activities' => $activities,
            'statuses' => $statuses
        ),
        'summary' => array(
            'total_logs' => $stats ? $stats['total_logs'] : 0,
            'total_hours' => $stats ? $stats['total_minutes'] / 60 : 0,
            'avg_rate' => $stats ? $stats['avg_rate'] : 0,
            'avg_cost' => $stats ? $stats['avg_cost'] : 0,
            'total_amount' => $stats ? $stats['total_amount'] : 0,
            'total_cost' => $stats ? $stats['total_cost'] : 0,
            'total_profit' => $stats ? ($stats['total_amount'] - $stats['total_cost']) : 0,
            'current_totals' => array(
                'charged' => $total_charged,
                'cost' => $total_cost,
                'profit' => $total_profit
            )
        ),
        'table' => array(
            'logs' => $processed_logs,
            'total_logs' => $total_logs,
            'current_page' => $current_page,
            'total_pages' => $total_pages,
            'per_page' => $per_page,
            'showing' => count($logs),
            'has_logs' => !empty($logs)
        ),
        'pagination' => array(
            'base' => add_query_arg('paged', '%#%'),
            'current' => $current_page,
            'total' => $total_pages
        )
    );
}

/**
 * Admin page to view all time logs (read-only)
 */
function wcrb_view_time_logs() {
    $data = wcrb_get_time_logs_data();
    
    if (isset($data['error'])) {
        wp_die($data['error']);
    }
    ?>
    
    <div class="wrap">
        <h1 class="wp-heading-inline">
            <i class="dashicons dashicons-clock" style="vertical-align: middle; margin-right: 10px;"></i>
            <?php echo esc_html__('Time Logs', 'computer-repair-shop'); ?>
        </h1>
        
        <hr class="wp-header-end">
        
        <!-- Summary Stats -->
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header">
                <h3><?php echo esc_html__('Summary', 'computer-repair-shop'); ?></h3>
            </div>
            <div class="card-body">
                <div style="display: flex; flex-wrap: wrap; gap: 30px;">
                    <div>
                        <strong><?php echo esc_html__('Total Logs:', 'computer-repair-shop'); ?></strong><br>
                        <span style="font-size: 24px; color: #2271b1;"><?php echo esc_html( number_format($data['summary']['total_logs']) ); ?></span>
                    </div>
                    <div>
                        <strong><?php echo esc_html__('Total Hours:', 'computer-repair-shop'); ?></strong><br>
                        <span style="font-size: 24px; color: #2271b1;"><?php echo esc_html( number_format($data['summary']['total_hours'], 2) ); ?>h</span>
                    </div>
                    <div>
                        <strong><?php echo esc_html__('Avg. Rate:', 'computer-repair-shop'); ?></strong><br>
                        <span style="font-size: 24px; color: #2271b1;"><?php echo esc_html( wc_cr_currency_format($data['summary']['avg_rate']) ); ?>/h</span>
                    </div>
                    <div>
                        <strong><?php echo esc_html__('Avg. Cost:', 'computer-repair-shop'); ?></strong><br>
                        <span style="font-size: 24px; color: #2271b1;"><?php echo esc_html( wc_cr_currency_format($data['summary']['avg_cost']) ); ?>/h</span>
                    </div>
                    <div>
                        <strong><?php echo esc_html__('Total Charged:', 'computer-repair-shop'); ?></strong><br>
                        <span style="font-size: 24px; color: #2271b1;"><?php echo esc_html( wc_cr_currency_format($data['summary']['total_amount']) ); ?></span>
                    </div>
                    <div>
                        <strong><?php echo esc_html__('Total Cost:', 'computer-repair-shop'); ?></strong><br>
                        <span style="font-size: 24px; color: #2271b1;"><?php echo esc_html( wc_cr_currency_format($data['summary']['total_cost']) ); ?></span>
                    </div>
                    <div>
                        <strong><?php echo esc_html__('Total Profit:', 'computer-repair-shop'); ?></strong><br>
                        <span style="font-size: 24px; color: <?php echo $data['summary']['total_profit'] >= 0 ? esc_html( '#5cb85c' ) : esc_html( '#d9534f' ); ?>;">
                            <?php echo esc_html( wc_cr_currency_format($data['summary']['total_profit']) ); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header">
                <h3><?php echo esc_html__('Filters', 'computer-repair-shop'); ?></h3>
            </div>
            <div class="card-body">
                <form method="get" action="">
                    <input type="hidden" name="page" value="wc-computer-repshop-timelogs">
                    
                    <div class="row" style="display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 15px;">
                        <div>
                            <label for="technician_id"><?php echo esc_html__('Technician:', 'computer-repair-shop'); ?></label><br>
                            <select name="technician_id" id="technician_id" style="width: 200px;">
                                <option value=""><?php echo esc_html__('All Technicians', 'computer-repair-shop'); ?></option>
                                <?php foreach ($data['filters']['technicians'] as $tech): ?>
                                    <option value="<?php echo esc_attr($tech->ID); ?>" <?php selected($data['filters']['technician_id'], $tech->ID); ?>>
                                        <?php echo esc_html($tech->display_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="job_id"><?php echo esc_html__('Job ID:', 'computer-repair-shop'); ?></label><br>
                            <input type="number" name="job_id" id="job_id" value="<?php echo esc_attr($data['filters']['job_id']); ?>" 
                                   placeholder="<?php echo esc_attr__('Job ID', 'computer-repair-shop'); ?>" style="width: 120px;">
                        </div>
                        
                        <div>
                            <label for="activity"><?php echo esc_html__('Activity:', 'computer-repair-shop'); ?></label><br>
                            <select name="activity" id="activity" style="width: 150px;">
                                <option value=""><?php echo esc_html__('All Activities', 'computer-repair-shop'); ?></option>
                                <?php foreach ($data['filters']['activities'] as $activity): ?>
                                    <option value="<?php echo esc_attr($activity); ?>" <?php selected($data['filters']['activity'], $activity); ?>>
                                        <?php echo esc_html($activity); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="status"><?php echo esc_html__('Status:', 'computer-repair-shop'); ?></label><br>
                            <select name="status" id="status" style="width: 150px;">
                                <option value=""><?php echo esc_html__('All Status', 'computer-repair-shop'); ?></option>
                                <?php foreach ($data['filters']['statuses'] as $status): ?>
                                    <option value="<?php echo esc_attr($status); ?>" <?php selected($data['filters']['status'], $status); ?>>
                                        <?php echo esc_html(ucfirst($status)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="date_from"><?php echo esc_html__('Date From:', 'computer-repair-shop'); ?></label><br>
                            <input type="date" name="date_from" id="date_from" value="<?php echo esc_attr($data['filters']['date_from']); ?>" 
                                   style="width: 150px;">
                        </div>
                        
                        <div>
                            <label for="date_to"><?php echo esc_html__('Date To:', 'computer-repair-shop'); ?></label><br>
                            <input type="date" name="date_to" id="date_to" value="<?php echo esc_attr($data['filters']['date_to']); ?>" 
                                   style="width: 150px;">
                        </div>
                    </div>
                    
                    <button type="submit" class="button button-primary">
                        <?php echo esc_html__('Apply Filters', 'computer-repair-shop'); ?>
                    </button>
                    <a href="?page=wc-computer-repshop-timelogs" class="button button-secondary">
                        <?php echo esc_html__('Clear Filters', 'computer-repair-shop'); ?>
                    </a>
                </form>
            </div>
        </div>
        
        <!-- Time Logs Table -->
        <div class="card">
            <div class="card-header">
                <h3><?php echo esc_html__('Time Logs', 'computer-repair-shop'); ?> 
                    <span class="text-muted" style="font-size: 14px; font-weight: normal;">
                        (<?php printf(esc_html__('Showing %d of %d', 'computer-repair-shop'), $data['table']['showing'], $data['table']['total_logs']); ?>)
                    </span>
                </h3>
            </div>
            <div class="card-body">
                <?php if (!$data['table']['has_logs']): ?>
                    <div class="notice notice-info">
                        <p><?php echo esc_html__('No time logs found.', 'computer-repair-shop'); ?></p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="wp-list-table widefat fixed striped" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th><?php echo esc_html__('ID', 'computer-repair-shop'); ?></th>
                                    <th><?php echo esc_html__('Job', 'computer-repair-shop'); ?></th>
                                    <th><?php echo esc_html__('Customer', 'computer-repair-shop'); ?></th>
                                    <th><?php echo esc_html__('Technician', 'computer-repair-shop'); ?></th>
                                    <th><?php echo esc_html__('Activity', 'computer-repair-shop'); ?></th>
                                    <th><?php echo esc_html__('Time', 'computer-repair-shop'); ?></th>
                                    <th><?php echo esc_html__('Duration', 'computer-repair-shop'); ?></th>
                                    <th><?php echo esc_html__('Cost', 'computer-repair-shop'); ?></th>
                                    <th><?php echo esc_html__('Charged', 'computer-repair-shop'); ?></th>
                                    <th><?php echo esc_html__('Profit', 'computer-repair-shop'); ?></th>
                                    <th><?php echo esc_html__('Status', 'computer-repair-shop'); ?></th>
                                    <th><?php echo esc_html__('Created', 'computer-repair-shop'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['table']['logs'] as $log): ?>
                                <tr>
                                    <td><?php echo esc_html($log['log_id']); ?></td>
                                    <td>
                                        <strong><?php echo esc_html($log['job_display']); ?></strong>
                                        <?php if (!empty($log['job_title'])): ?>
                                        <br><small class="text-muted"><?php echo esc_html($log['job_title']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo esc_html($log['customer_name']); ?>
                                        <?php if (!empty($log['customer_email'])): ?>
                                        <br><small class="text-muted"><?php echo esc_html($log['customer_email']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html($log['technician_name']); ?></td>
                                    <td>
                                        <span class="badge"><?php echo esc_html($log['activity']); ?></span>
                                    </td>
                                    <td>
                                        <small><strong><?php echo esc_html__('Start:', 'computer-repair-shop'); ?></strong> <?php echo esc_html($log['start_date'] . ' ' . $log['start_time']); ?></small><br>
                                        <small><strong><?php echo esc_html__('End:', 'computer-repair-shop'); ?></strong> <?php echo esc_html($log['end_date'] . ' ' . $log['end_time']); ?></small>
                                    </td>
                                    <td title="<?php echo esc_attr($log['duration_full']); ?>">
                                        <?php echo esc_html($log['duration']); ?>
                                    </td>
                                    <td>
                                        <strong><?php echo esc_html( wc_cr_currency_format($log['cost_amount']) ); ?></strong><br>
                                        <small class="text-muted"><?php echo esc_html( wc_cr_currency_format($log['hourly_cost']) ); ?>/h</small>
                                    </td>
                                    <td>
                                        <strong><?php echo esc_html( wc_cr_currency_format($log['charged_amount']) ); ?></strong><br>
                                        <small class="text-muted"><?php echo esc_html( wc_cr_currency_format($log['hourly_rate']) ); ?>/h</small>
                                    </td>
                                    <td>
                                        <strong style="color: <?php echo $log['profit_amount'] >= 0 ? esc_html( '#5cb85c' ) : esc_html( '#d9534f' ); ?>;">
                                            <?php echo esc_html( wc_cr_currency_format($log['profit_amount']) ); ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo esc_attr($log['status_class']); ?>">
                                            <?php echo esc_html(ucfirst($log['log_state'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html($log['created_at']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="12" style="text-align: right; padding: 15px; background: #f8f9fa;">
                                        <strong><?php echo esc_html__('Page Totals:', 'computer-repair-shop'); ?></strong>
                                        <span style="margin-left: 20px;">
                                            <?php echo esc_html__('Cost:', 'computer-repair-shop'); ?> 
                                            <strong><?php echo esc_html( wc_cr_currency_format($data['summary']['current_totals']['cost']) ); ?></strong>
                                        </span>
                                        <span style="margin-left: 20px;">
                                            <?php echo esc_html__('Charged:', 'computer-repair-shop'); ?> 
                                            <strong><?php echo esc_html( wc_cr_currency_format($data['summary']['current_totals']['charged']) ); ?></strong>
                                        </span>
                                        <span style="margin-left: 20px;">
                                            <?php echo esc_html__('Profit:', 'computer-repair-shop'); ?> 
                                            <strong style="color: <?php echo $data['summary']['current_totals']['profit'] >= 0 ? esc_html( '#5cb85c' ) : esc_html( '#d9534f' ); ?>;">
                                                <?php echo esc_html( wc_cr_currency_format($data['summary']['current_totals']['profit']) ); ?>
                                            </strong>
                                        </span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($data['table']['total_pages'] > 1): ?>
                    <div class="tablenav">
                        <div class="tablenav-pages">
                            <?php
                            echo wp_kses(paginate_links(array(
                                'base' => $data['pagination']['base'],
                                'format' => '',
                                'prev_text' => '&laquo;',
                                'next_text' => '&raquo;',
                                'total' => $data['pagination']['total'],
                                'current' => $data['pagination']['current'],
                                'show_all' => false,
                                'end_size' => 1,
                                'mid_size' => 2,
                            )), wc_return_allowed_tags() );
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <style>
            .badge {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 12px;
                font-size: 12px;
                font-weight: 600;
                background: #f0f0f0;
                color: #333;
            }
            .badge-warning { background: #f0ad4e; color: white; }
            .badge-success { background: #5cb85c; color: white; }
            .badge-error { background: #d9534f; color: white; }
            .badge-info { background: #5bc0de; color: white; }
            .badge-secondary { background: #777; color: white; }
            .text-muted { color: #666; }
            .card { width: 100%; border-radius: 4px; max-width:unset !important; }
            .card { background: white; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); margin-bottom: 20px; }
            .card-header { border-bottom: 1px solid #ccd0d4; padding: 0px; }
            .card-body { padding: 15px; }
            .table-responsive { overflow-x: auto; }
        </style>
    </div>
    <?php
}