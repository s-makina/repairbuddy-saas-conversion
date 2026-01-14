<?php
/**
 * Expense Manager
 *
 * Helpful class for expense managers
 *
 * @package computer-repair-shop
 * @version 4.1115
 */
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_CR_Expense_Manager' ) ) :
class WC_CR_Expense_Manager {
    private static $instance = null;
    
    public static function getInstance() {
        if ( self::$instance == null ) {
            self::$instance = new WC_CR_Expense_Manager();
        }
        return self::$instance;
    }
    
    /**
     * Generate unique expense number
     */
    public function generate_expense_number() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_cr_expenses';
        
        $year = date('Y');
        
        // Get last expense number for this year
        $last_number = $wpdb->get_var($wpdb->prepare(
            "SELECT expense_number FROM $table_name 
             WHERE expense_number LIKE %s 
             ORDER BY expense_id DESC LIMIT 1",
            "EXP-$year-%"
        ));
        
        if ($last_number) {
            $parts = explode('-', $last_number);
            $last_seq = intval($parts[2]);
            $new_seq = str_pad($last_seq + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $new_seq = '001';
        }
        
        return "EXP-$year-$new_seq";
    }
    
    /**
     * Get expense categories
     */
    public function get_categories($type = 'expense', $active_only = true, $parent_id = 0) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_cr_expense_categories';
        
        $current_role = wcrb_current_user_role();

        if ( $current_role != 'administrator' && $current_role != 'technician' && $current_role != 'store_manager' ) {
            return;
        }

        $where = array('category_type = %s');
        $params = array($type);
        
        if ($active_only) {
            $where[] = 'is_active = %d';
            $params[] = 1;
        }
        
        if ($parent_id !== null) {
            $where[] = 'parent_category_id = %d';
            $params[] = $parent_id;
        }
        
        $where_clause = implode(' AND ', $where);
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name 
             WHERE $where_clause 
             ORDER BY sort_order, category_name",
            $params
        ));
    }
    
    /**
     * Get category by ID
     */
    public function get_category( $category_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_cr_expense_categories';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE category_id = %d",
            $category_id
        ));
    }
    
    /**
     * Calculate expense totals with tax
     */
    public function calculate_expense_total($amount, $category_id = null) {
        global $wpdb;
        
        $tax_rate = 0;
        
        if ($category_id) {
            $category = $wpdb->get_row($wpdb->prepare(
                "SELECT taxable, tax_rate FROM {$wpdb->prefix}wc_cr_expense_categories 
                 WHERE category_id = %d",
                $category_id
            ));
            
            if ($category && $category->taxable) {
                $tax_rate = $category->tax_rate;
            }
        }
        
        $tax_amount = ($amount * $tax_rate) / 100;
        $total_amount = $amount + $tax_amount;
        
        return array(
            'amount' => $amount,
            'tax_rate' => $tax_rate,
            'tax_amount' => $tax_amount,
            'total_amount' => $total_amount
        );
    }
    
    /**
     * Add new expense
     */
    public function add_expense($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_cr_expenses';
        
        // Generate expense number if not provided
        if (empty($data['expense_number'])) {
            $data['expense_number'] = $this->generate_expense_number();
        }
        
        // Calculate totals if not provided
        if (!isset($data['tax_amount']) || !isset($data['total_amount'])) {
            $totals = $this->calculate_expense_total($data['amount'], $data['category_id']);
            $data['tax_amount'] = $totals['tax_amount'];
            $data['total_amount'] = $totals['total_amount'];
        }
        
        // Set default values
        $defaults = array(
            'currency' => get_option('woocommerce_currency', 'USD'),
            'expense_type' => 'general',
            'payment_status' => 'paid',
            'status' => 'active',
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'expense_date' => current_time('mysql')
        );
        
        $data = wp_parse_args($data, $defaults);
        
        $wpdb->insert($table_name, $data);
        
        return $wpdb->insert_id;
    }
    
    /**
     * Update expense
     */
    public function update_expense($expense_id, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_cr_expenses';
        
        // Calculate totals if amount or category changed
        if (isset($data['amount']) || isset($data['category_id'])) {
            $amount = isset($data['amount']) ? $data['amount'] : $this->get_expense($expense_id)->amount;
            $category_id = isset($data['category_id']) ? $data['category_id'] : $this->get_expense($expense_id)->category_id;
            
            $totals = $this->calculate_expense_total($amount, $category_id);
            $data['tax_amount'] = $totals['tax_amount'];
            $data['total_amount'] = $totals['total_amount'];
        }
        
        $data['updated_at'] = current_time('mysql');
        
        $wpdb->update(
            $table_name,
            $data,
            array('expense_id' => $expense_id)
        );
        
        return $wpdb->rows_affected;
    }
    
    /**
     * Get expense by ID
     */
    public function get_expense($expense_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_cr_expenses';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT e.*, c.category_name, c.color_code 
             FROM $table_name e
             LEFT JOIN {$wpdb->prefix}wc_cr_expense_categories c ON e.category_id = c.category_id
             WHERE e.expense_id = %d",
            $expense_id
        ));
    }
    
    /**
     * Get all expenses with filters
     */
    public function get_expenses($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'expense_id',
            'order' => 'DESC',
            'search' => '',
            'category_id' => '',
            'payment_status' => '',
            'status' => 'active',  // Default to active
            'start_date' => '',
            'end_date' => '',
            'created_by' => '',
            'job_id' => '',
            'technician_id' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $table_name = $wpdb->prefix . 'wc_cr_expenses';
        $categories_table = $wpdb->prefix . 'wc_cr_expense_categories';
        
        $where = array('1=1');
        $params = array();
        
        // Status filter - FIXED: Always apply status filter
        if (!empty($args['status'])) {
            $where[] = 'e.status = %s';
            $params[] = $args['status'];
        }
        
        // Category filter
        if (!empty($args['category_id'])) {
            $where[] = 'e.category_id = %d';
            $params[] = $args['category_id'];
        }
        
        // Payment status filter
        if (!empty($args['payment_status'])) {
            $where[] = 'e.payment_status = %s';
            $params[] = $args['payment_status'];
        }
        
        // Date range filter
        if (!empty($args['start_date'])) {
            $where[] = 'e.expense_date >= %s';
            $params[] = $args['start_date'];
        }
        
        if (!empty($args['end_date'])) {
            $where[] = 'e.expense_date <= %s';
            $params[] = $args['end_date'];
        }
        
        // Created by filter
        if (!empty($args['created_by'])) {
            $where[] = 'e.created_by = %d';
            $params[] = $args['created_by'];
        }
        
        // Job filter
        if (!empty($args['job_id'])) {
            $where[] = 'e.job_id = %d';
            $params[] = $args['job_id'];
        }
        
        // Technician filter
        if (!empty($args['technician_id'])) {
            $where[] = 'e.technician_id = %d';
            $params[] = $args['technician_id'];
        }
        
        // Search filter
        if (!empty($args['search'])) {
            $where[] = '(e.description LIKE %s OR e.expense_number LIKE %s OR e.receipt_number LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        $where_clause = implode(' AND ', $where);
        
        // Get total count
        $count_query = "SELECT COUNT(*) FROM $table_name e WHERE $where_clause";
        
        // Prepare the count query with parameters if any
        if (!empty($params)) {
            // Create a copy of params for count query
            $count_params = $params;
            $count_query = $wpdb->prepare($count_query, $count_params);
        }
        
        $total = $wpdb->get_var($count_query);
        
        // Get data
        $query = "SELECT e.*, c.category_name, c.color_code, 
                        u.display_name as created_by_name
                FROM $table_name e
                LEFT JOIN $categories_table c ON e.category_id = c.category_id
                LEFT JOIN {$wpdb->prefix}users u ON e.created_by = u.ID
                WHERE $where_clause
                ORDER BY e.{$args['orderby']} {$args['order']}";
        
        // Add LIMIT and OFFSET if limit > 0
        if ($args['limit'] > 0) {
            $query .= " LIMIT %d OFFSET %d";
            $params[] = $args['limit'];
            $params[] = $args['offset'];
        }
        
        // Prepare and execute the query
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        $expenses = $wpdb->get_results($query);
        
        return array(
            'expenses' => $expenses,
            'total' => $total
        );
    }
    
    /**
     * Get expense statistics - FIXED: Removed ambiguous column references
     */
    public function get_statistics($period = 'month', $user_id = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_cr_expenses';
        
        $where = array("e.status = 'active'");
        $params = array();
        
        if ($user_id) {
            $where[] = 'e.created_by = %d';
            $params[] = $user_id;
        }
        
        // Date filters based on period
        $date_format = '%Y-%m';
        if ($period == 'week') {
            $date_format = '%Y-%u';
        } elseif ($period == 'day') {
            $date_format = '%Y-%m-%d';
        } elseif ($period == 'year') {
            $date_format = '%Y';
        }
        
        $where_clause = implode(' AND ', $where);
        $where_sql = $where_clause ? "WHERE $where_clause" : '';
        
        // Get total amounts - FIXED: Added table alias
        $totals_query = "SELECT 
                SUM(e.amount) as total_amount,
                SUM(e.tax_amount) as total_tax,
                SUM(e.total_amount) as grand_total,
                COUNT(*) as total_count
            FROM $table_name e
            $where_sql";
        
        if ($params) {
            $totals_query = $wpdb->prepare($totals_query, $params);
        }
        
        $totals = $wpdb->get_row($totals_query);
        
        // Get monthly breakdown - FIXED: Added table alias
        $breakdown_query = "SELECT 
                DATE_FORMAT(e.expense_date, %s) as period,
                SUM(e.total_amount) as amount,
                COUNT(*) as count
            FROM $table_name e
            $where_sql
            GROUP BY DATE_FORMAT(e.expense_date, %s)
            ORDER BY period DESC
            LIMIT 12";
        
        $breakdown_params = array_merge(array($date_format), $params, array($date_format));
        $breakdown = $wpdb->get_results($wpdb->prepare($breakdown_query, $breakdown_params));
        
        // Get category breakdown - FIXED: Added table aliases
        $category_query = "SELECT 
                c.category_name,
                c.color_code,
                SUM(e.total_amount) as amount,
                COUNT(*) as count
            FROM $table_name e
            LEFT JOIN {$wpdb->prefix}wc_cr_expense_categories c ON e.category_id = c.category_id
            $where_sql
            GROUP BY e.category_id
            ORDER BY amount DESC";
        
        if ($params) {
            $category_query = $wpdb->prepare($category_query, $params);
        }
        
        $categories = $wpdb->get_results($category_query);
        
        return array(
            'totals' => $totals,
            'breakdown' => $breakdown,
            'categories' => $categories
        );
    }
    
    /**
     * Delete expense (soft delete)
     */
    public function delete_expense($expense_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_cr_expenses';
        
        return $wpdb->update(
            $table_name,
            array(
                'status' => 'void',
                'updated_at' => current_time('mysql')
            ),
            array('expense_id' => $expense_id)
        );
    }

    /**
     * Get total expense amount for a job
     *
     * @param int  $job_id
     * @param bool $include_tax Whether to include tax in total
     *
     * @return float
     */
    public function get_job_expense_total( $job_id, $include_tax = true ) {
        global $wpdb;

        if ( empty( $job_id ) ) {
            return 0;
        }

        $table = $wpdb->prefix . 'wc_cr_expenses';

        $column = $include_tax ? 'SUM(total_amount)' : 'SUM(amount)';

        $total = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT {$column}
                FROM {$table}
                WHERE job_id = %d
                AND status = %s",
                $job_id,
                'active'
            )
        );

        return (float) $total;
    }

    
    /**
     * Add new category
     */
    public function add_category($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_cr_expense_categories';
        
        // Check if category with same name already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
            WHERE category_name = %s AND category_type = %s",
            $data['category_name'],
            $data['category_type'] ?? 'expense'
        ));
        
        if ($existing > 0) {
            return new WP_Error( 'category_exists', __( 'Category with this name already exists', 'computer-repair-shop' ) );
        }
        
        $defaults = array(
            'category_type' => 'expense',
            'is_active' => 1,
            'taxable' => 1,
            'tax_rate' => 0,
            'sort_order' => 0,
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql')
        );
        
        $data = wp_parse_args($data, $defaults);
        
        $result = $wpdb->insert($table_name, $data);
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    function wcrb_check_category_exists($category_name, $category_type = 'expense') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_cr_expense_categories';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
            WHERE category_name = %s 
            AND category_type = %s 
            AND is_active = 1",
            $category_name,
            $category_type
        )) > 0;
    }

    /**
     * Update category
     */
    public function update_category($category_id, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_cr_expense_categories';
        
        $data['updated_at'] = current_time('mysql');
        
        $wpdb->update(
            $table_name,
            $data,
            array('category_id' => $category_id)
        );
        
        return $wpdb->rows_affected;
    }
    
    /**
     * Delete category
     */
    public function delete_category($category_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_cr_expense_categories';
        
        // Check if category has expenses
        $expense_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}wc_cr_expenses 
             WHERE category_id = %d AND status != 'void'",
            $category_id
        ));
        
        if ($expense_count > 0) {
            return new WP_Error('category_in_use', __('Cannot delete category with existing expenses', 'computer-repair-shop'));
        }
        
        return $wpdb->delete($table_name, array('category_id' => $category_id));
    }
    
    /**
     * Get payment methods
     */
    public function get_payment_methods() {
        return array(
            'cash' => __('Cash', 'computer-repair-shop'),
            'credit' => __('Credit Card', 'computer-repair-shop'),
            'debit' => __('Debit Card', 'computer-repair-shop'),
            'bank_transfer' => __('Bank Transfer', 'computer-repair-shop'),
            'check' => __('Check', 'computer-repair-shop'),
            'online' => __('Online Payment', 'computer-repair-shop'),
            'paypal' => __('PayPal', 'computer-repair-shop'),
            'other' => __('Other', 'computer-repair-shop')
        );
    }
    
    /**
     * Get payment statuses
     */
    public function get_payment_statuses() {
        return array(
            'paid'    => __('Paid', 'computer-repair-shop'),
            'pending' => __('Pending', 'computer-repair-shop'),
            'partial' => __('Partially Paid', 'computer-repair-shop'),
            'overdue' => __('Overdue', 'computer-repair-shop')
        );
    }
    
    /**
     * Get expense statuses
     */
    public function get_expense_statuses() {
        return array(
            'active'   => __('Active', 'computer-repair-shop'),
            'void'     => __('Void', 'computer-repair-shop'),
            'refunded' => __('Refunded', 'computer-repair-shop')
        );
    }
    
    /**
     * Get expense types
     */
    public function get_expense_types() {
        return array(
            'general' => __('General', 'computer-repair-shop'),
            'business' => __('Business', 'computer-repair-shop'),
            'personal' => __('Personal', 'computer-repair-shop'),
            'operational' => __('Operational', 'computer-repair-shop')
        );
    }

    /**
     * Get or create labor expense category
     */
    public function get_labor_category() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_cr_expense_categories';
        
        // First, try to find an existing labor category
        $labor_category = $wpdb->get_row(
            "SELECT * FROM $table_name 
            WHERE category_name LIKE '%labor%' OR category_name LIKE '%labour%' 
            LIMIT 1"
        );
        
        if ($labor_category) {
            return $labor_category;
        }
        
        // If no labor category found, create one
        $default_labor_category = array(
            'category_name' => __('Labor Costs', 'computer-repair-shop'),
            'category_description' => __('Technician labor and wages', 'computer-repair-shop'),
            'category_type' => 'expense',
            'parent_category_id' => 0,
            'color_code' => '#3498db',
            'is_active' => 1,
            'taxable' => 1,
            'tax_rate' => 0,
            'sort_order' => 10,
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql')
        );
        
        $wpdb->insert($table_name, $default_labor_category);
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE category_id = %d",
            $wpdb->insert_id
        ));
    }

    /**
     * Create expense from time log entry (Alternative with comment in description)
     */
    public function create_expense_from_time_log($time_log_entry, $technician_id) {
        global $wpdb;
        
        // Get labor category
        $labor_category = $this->get_labor_category();
        
        // Get technician information
        $technician = get_userdata($technician_id);
        $technician_name = $technician ? $technician->display_name : __('Unknown Technician', 'computer-repair-shop');
        
        // Calculate hours and cost
        $total_minutes = $time_log_entry['total_minutes'] ?? 0;
        $total_hours = $total_minutes / 60;
        $hourly_cost = $time_log_entry['hourly_cost'] ?? 0;
        $total_cost = $hourly_cost * $total_hours;
        
        if ($total_cost <= 0) {
            return false; // No cost to record
        }
        
        // Add time log reference to description
        $log_id = $time_log_entry['log_id'] ?? 0;
        
        // Prepare expense data
        $expense_data = array(
            'expense_number' => $this->generate_expense_number(),
            'expense_date' => $time_log_entry['start_time'] ?? current_time('mysql'),
            'category_id' => $labor_category->category_id,
            'description' => sprintf(
                __('Labor: %s - %s hours by %s [Time Log #%d]', 'computer-repair-shop'),
                $time_log_entry['activity'] ?? __('Work', 'computer-repair-shop'),
                number_format($total_hours, 2),
                $technician_name,
                $log_id
            ),
            'amount' => $total_cost,
            'payment_method' => 'internal',
            'payment_status' => 'paid',
            'expense_type' => 'operational',
            'job_id' => $time_log_entry['job_id'] ?? 0,
            'technician_id' => $technician_id,
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql')
        );
        
        // Add expense
        $expense_id = $this->add_expense($expense_data);
        
        return $expense_id;
    }

    /**
     * Check if expense already exists for a time log entry - NEW METHOD
     */
    public function expense_exists_for_time_log($time_log_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_cr_expenses';
        
        // Since we don't have reference_type and reference_id columns,
        // we check by looking for the time log ID in the description
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
            WHERE description LIKE %s 
            AND status = 'active'",
            '%[Time Log #' . $time_log_id . ']%'
        )) > 0;
    }
}
endif;

// Initialize the class
function WC_CR_EXPENSE_MANAGEMENT() {
    return WC_CR_Expense_Manager::getInstance();
}


// Get expense details for view modal
add_action( 'wp_ajax_wcrb_get_expense_details', 'wcrb_ajax_get_expense_details' );
add_action( 'wp_ajax_nopriv_wcrb_get_expense_details', 'wcrb_ajax_get_expense_details' );

function wcrb_ajax_get_expense_details() {
    if ( ! wp_verify_nonce( $_POST['nonce'], 'wcrb_expense_nonce' ) ) {
        wp_send_json_error( array('message' => __( 'Security check failed', 'computer-repair-shop' ) ) );
    }
    
    // Check permissions
    $current_role = wcrb_current_user_role();
    if ( $current_role != 'administrator' && $current_role != 'store_manager' && $current_role != 'technician' ) {
        wp_send_json_error(array('message' => __('Security check failed', 'computer-repair-shop')));
    }

    $expense_id      = intval($_POST['expense_id']);
    $expense_manager = WC_CR_EXPENSE_MANAGEMENT();
    $expense         = $expense_manager->get_expense($expense_id);
    
    if (!$expense) {
        wp_send_json_error(array('message' => __('Expense not found', 'computer-repair-shop')));
    }
    
    // Format data for display
    $payment_methods  = $expense_manager->get_payment_methods();
    $payment_statuses = $expense_manager->get_payment_statuses();
    
    $status_class = array(
        'paid'    => 'success',
        'pending' => 'warning',
        'partial' => 'info',
        'overdue' => 'danger'
    );
    
    // Get created by user name safely
    $created_by_name = '';
    if (!empty($expense->created_by)) {
        $user = get_userdata($expense->created_by);
        if ($user) {
            $created_by_name = $user->display_name;
        }
    }
    
    wp_send_json_success( array(
        'expense_id'           => $expense->expense_id,
        'expense_number'       => $expense->expense_number,
        'formatted_date'       => date_i18n(get_option('date_format'), strtotime($expense->expense_date)),
        'category_name'        => $expense->category_name,
        'color_code'           => $expense->color_code,
        'description'          => nl2br(esc_html($expense->description)),
        'formatted_amount'     => wc_cr_currency_format($expense->amount),
        'formatted_tax'        => wc_cr_currency_format($expense->tax_amount),
        'formatted_total'      => wc_cr_currency_format($expense->total_amount),
        'payment_status'       => $expense->payment_status,
        'payment_status_label' => $payment_statuses[$expense->payment_status] ?? $expense->payment_status,
        'payment_status_class' => $status_class[$expense->payment_status] ?? 'secondary',
        'payment_method'       => $expense->payment_method,
        'payment_method_label' => $payment_methods[$expense->payment_method] ?? $expense->payment_method,
        'receipt_number'       => $expense->receipt_number,
        'created_by_name'      => $created_by_name,
        'formatted_created_at' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($expense->created_at))
    ) );
}

// Calculate expense total
add_action('wp_ajax_wcrb_calculate_expense_total', 'wcrb_ajax_calculate_expense_total');
add_action('wp_ajax_nopriv_wcrb_calculate_expense_total', 'wcrb_ajax_calculate_expense_total');

function wcrb_ajax_calculate_expense_total() {
    if ( ! wp_verify_nonce( $_POST['nonce'], 'wcrb_expense_nonce' ) ) {
        wp_send_json_error( array( 'message' => __( 'Security check failed', 'computer-repair-shop' ) ) );
    }

    // Check permissions
    $current_role = wcrb_current_user_role();
    if ( $current_role != 'administrator' && $current_role != 'store_manager' && $current_role != 'technician' ) {
        wp_send_json_error(array('message' => __('Security check failed', 'computer-repair-shop')));
    }
    
    $amount      = floatval($_POST['amount']);
    $category_id = intval($_POST['category_id']);
    
    $expense_manager = WC_CR_EXPENSE_MANAGEMENT();
    $totals          = $expense_manager->calculate_expense_total($amount, $category_id);
    
    wp_send_json_success(array(
        'tax_amount'      => $totals['tax_amount'],
        'total_amount'    => $totals['total_amount'],
        'formatted_tax'   => wc_cr_currency_format($totals['tax_amount']),
        'formatted_total' => wc_cr_currency_format($totals['total_amount'])
    ));
}

// Delete expense
// Delete expense - Add job history logging
add_action('wp_ajax_wcrb_delete_expense', 'wcrb_ajax_delete_expense');
add_action('wp_ajax_nopriv_wcrb_delete_expense', 'wcrb_ajax_delete_expense');

function wcrb_ajax_delete_expense() {
    if (!wp_verify_nonce($_POST['nonce'], 'wcrb_expense_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed', 'computer-repair-shop')));
    }
    
    // Check permissions
    $current_role = wcrb_current_user_role();
    if ( $current_role != 'administrator' && $current_role != 'store_manager' ) {
        wp_send_json_error(array('message' => __('Security check failed', 'computer-repair-shop')));
    }

    $expense_id = intval($_POST['expense_id']);
    $expense_manager = WC_CR_EXPENSE_MANAGEMENT();
    
    // Get expense details before deletion for logging
    $expense = $expense_manager->get_expense($expense_id);
    
    if (!$expense) {
        wp_send_json_error(array('message' => __('Expense not found', 'computer-repair-shop')));
    }
    
    $result = $expense_manager->delete_expense($expense_id);
    
    if ($result) {
        // Log to job history if expense is related to a job
        if (!empty($expense->job_id)) {
            $job_id = $expense->job_id;
            
            // Prepare history log message
            $history_message = sprintf(
                __('Expense #%d voided: %s (Amount: %s)', 'computer-repair-shop'),
                $expense_id,
                $expense->description,
                wc_cr_currency_format($expense->total_amount)
            );
            
            // Add to job history
            if (class_exists('WCRB_JOB_HISTORY_LOGS')) {
                $WCRB_JOB_HISTORY_LOGS = WCRB_JOB_HISTORY_LOGS::getInstance();
                $history_data = array(
                    "job_id"        => $job_id,
                    "name"          => $history_message,
                    "type"          => 'private',
                    "field"         => '_wc_expense_data',
                    "change_detail" => __('Expense voided/soft deleted', 'computer-repair-shop')
                );
                $WCRB_JOB_HISTORY_LOGS->wc_record_job_history($history_data);
            }
        }
        
        wp_send_json_success(array(
            'message' => __('Expense deleted successfully', 'computer-repair-shop'),
            'job_logged' => !empty($expense->job_id)
        ));
    } else {
        wp_send_json_error(array('message' => __('Failed to delete expense', 'computer-repair-shop')));
    }
}

// Add Expense AJAX handler
add_action( 'wp_ajax_wcrb_add_expense', 'wcrb_ajax_add_expense' );
add_action( 'wp_ajax_nopriv_wcrb_add_expense', 'wcrb_ajax_add_expense' );

function wcrb_ajax_add_expense() {
    if (!wp_verify_nonce($_POST['nonce'], 'wcrb_expense_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed', 'computer-repair-shop')));
    }
    
    // Check permissions
    $current_role = wcrb_current_user_role();
    if (!in_array($current_role, array('administrator', 'store_manager'))) {
        wp_send_json_error(array('message' => __('You do not have permission to add expenses', 'computer-repair-shop')));
    }
    
    // Sanitize and validate data
    $expense_date   = sanitize_text_field($_POST['expense_date'] ?? '');
    $category_id    = intval($_POST['category_id'] ?? 0);
    $description    = sanitize_textarea_field($_POST['description'] ?? '');
    $amount         = floatval($_POST['amount'] ?? 0);
    $payment_method = sanitize_text_field($_POST['payment_method'] ?? '');
    $payment_status = sanitize_text_field($_POST['payment_status'] ?? 'paid');
    $receipt_number = sanitize_text_field($_POST['receipt_number'] ?? '');
    $expense_type   = sanitize_text_field($_POST['expense_type'] ?? 'general');
    $job_id         = ( isset( $_POST['job_id'] ) && ! empty( $_POST['job_id'] ) ) ? sanitize_text_field( $_POST['job_id'] ) : '';

    // Validation
    if ( empty( $expense_date ) ) {
        wp_send_json_error( array('message' => __('Expense date is required', 'computer-repair-shop')) );
    }
    
    if (!$category_id) {
        wp_send_json_error(array('message' => __('Category is required', 'computer-repair-shop')));
    }
    
    if (empty($description)) {
        wp_send_json_error(array('message' => __('Description is required', 'computer-repair-shop')));
    }
    
    if ($amount <= 0) {
        wp_send_json_error(array('message' => __('Amount must be greater than 0', 'computer-repair-shop')));
    }
    
    // Get expense manager
    $expense_manager = WC_CR_EXPENSE_MANAGEMENT();
    
    // Prepare data
    $expense_data = array(
        'expense_date'   => $expense_date,
        'category_id'    => $category_id,
        'description'    => $description,
        'amount'         => $amount,
        'payment_method' => $payment_method,
        'payment_status' => $payment_status,
        'receipt_number' => $receipt_number,
        'expense_type'   => $expense_type,
        'job_id'        => $job_id,
        'created_by'     => get_current_user_id(),
        'created_at'     => current_time('mysql')
    );
    
    // Add expense
    $expense_id = $expense_manager->add_expense( $expense_data );
    
    if ( ! empty( $job_id ) ) {
        $expense_argz = array(
            "job_id" 		=> $job_id, 
            "name" 			=> sprintf(esc_html__( 'Expense added with id %d and payment status %s', 'computer-repair-shop' ), $expense_id, $payment_status ) , 
            "type" 			=> 'private', 
            "field" 		=> '_wc_expense_data', 
            "change_detail" => $description
        );
        $WCRB_JOB_HISTORY_LOGS = WCRB_JOB_HISTORY_LOGS::getInstance();
        $WCRB_JOB_HISTORY_LOGS->wc_record_job_history( $expense_argz );
    }

    if ($expense_id) {
        wp_send_json_success(array(
            'message' => __('Expense added successfully', 'computer-repair-shop'),
            'expense_id' => $expense_id
        ));
    } else {
        wp_send_json_error(array('message' => __('Failed to add expense', 'computer-repair-shop')));
    }
}

// Get Expense for Edit AJAX handler
add_action('wp_ajax_wcrb_get_expense', 'wcrb_ajax_get_expense');
add_action('wp_ajax_nopriv_wcrb_get_expense', 'wcrb_ajax_get_expense');

function wcrb_ajax_get_expense() {
    if (!wp_verify_nonce($_POST['nonce'], 'wcrb_expense_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed', 'computer-repair-shop')));
    }
    
    // Check permissions
    $current_role = wcrb_current_user_role();
    if ( $current_role != 'administrator' && $current_role != 'store_manager' && $current_role != 'technician' ) {
        wp_send_json_error(array('message' => __('Security check failed', 'computer-repair-shop')));
    }

    $expense_id      = intval($_POST['expense_id']);
    $expense_manager = WC_CR_EXPENSE_MANAGEMENT();
    $expense         = $expense_manager->get_expense($expense_id);
    
    if ( ! $expense ) {
        wp_send_json_error(array('message' => __('Expense not found', 'computer-repair-shop')));
    }
    
    wp_send_json_success(array(
        'expense_id'     => $expense->expense_id,
        'expense_date'   => $expense->expense_date,
        'category_id'    => $expense->category_id,
        'description'    => $expense->description,
        'amount'         => $expense->amount,
        'payment_method' => $expense->payment_method,
        'payment_status' => $expense->payment_status,
        'receipt_number' => $expense->receipt_number,
        'expense_type'   => $expense->expense_type,
        'status'         => $expense->status
    ));
}

// Update Expense AJAX handler
// Update Expense AJAX handler - Add job history logging
add_action('wp_ajax_wcrb_update_expense', 'wcrb_ajax_update_expense');
add_action('wp_ajax_nopriv_wcrb_update_expense', 'wcrb_ajax_update_expense');

function wcrb_ajax_update_expense() {
    if (!wp_verify_nonce($_POST['nonce'], 'wcrb_expense_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed', 'computer-repair-shop')));
    }
    
    // Check permissions
    $current_role = wcrb_current_user_role();
    if ( $current_role != 'administrator' && $current_role != 'store_manager' ) {
        wp_send_json_error(array('message' => __('Security check failed', 'computer-repair-shop')));
    }
    
    $expense_id = intval($_POST['expense_id'] ?? 0);
    
    if (!$expense_id) {
        wp_send_json_error(array('message' => __('Invalid expense ID', 'computer-repair-shop')));
    }
    
    // Get expense manager
    $expense_manager = WC_CR_EXPENSE_MANAGEMENT();
    
    // Get the existing expense to compare changes and get job_id
    $existing_expense = $expense_manager->get_expense($expense_id);
    
    if (!$existing_expense) {
        wp_send_json_error(array('message' => __('Expense not found', 'computer-repair-shop')));
    }
    
    // Sanitize and validate data
    $expense_date   = sanitize_text_field($_POST['expense_date'] ?? '');
    $category_id    = intval($_POST['category_id'] ?? 0);
    $description    = sanitize_textarea_field($_POST['description'] ?? '');
    $amount         = floatval($_POST['amount'] ?? 0);
    $payment_method = sanitize_text_field($_POST['payment_method'] ?? '');
    $payment_status = sanitize_text_field($_POST['payment_status'] ?? 'paid');
    $receipt_number = sanitize_text_field($_POST['receipt_number'] ?? '');
    $expense_type   = sanitize_text_field($_POST['expense_type'] ?? 'general');
    $status         = sanitize_text_field($_POST['status'] ?? 'active');
    
    // Validation
    if (empty($expense_date)) {
        wp_send_json_error(array('message' => __('Expense date is required', 'computer-repair-shop')));
    }
    
    if (!$category_id) {
        wp_send_json_error(array('message' => __('Category is required', 'computer-repair-shop')));
    }
    
    if (empty($description)) {
        wp_send_json_error(array('message' => __('Description is required', 'computer-repair-shop')));
    }
    
    if ($amount <= 0) {
        wp_send_json_error(array('message' => __('Amount must be greater than 0', 'computer-repair-shop')));
    }
    
    // Track changes for history log
    $changes = array();
    
    // Compare and track changes
    if ($existing_expense->expense_date != $expense_date) {
        $changes[] = sprintf(
            __('Date changed from %s to %s', 'computer-repair-shop'),
            date_i18n(get_option('date_format'), strtotime($existing_expense->expense_date)),
            date_i18n(get_option('date_format'), strtotime($expense_date))
        );
    }
    
    if ($existing_expense->category_id != $category_id) {
        $old_category = $expense_manager->get_category($existing_expense->category_id);
        $new_category = $expense_manager->get_category($category_id);
        
        $old_name = $old_category ? $old_category->category_name : __('Unknown', 'computer-repair-shop');
        $new_name = $new_category ? $new_category->category_name : __('Unknown', 'computer-repair-shop');
        
        $changes[] = sprintf(
            __('Category changed from %s to %s', 'computer-repair-shop'),
            $old_name,
            $new_name
        );
    }
    
    if ($existing_expense->description != $description) {
        $changes[] = __('Description updated', 'computer-repair-shop');
    }
    
    if ($existing_expense->amount != $amount) {
        $changes[] = sprintf(
            __('Amount changed from %s to %s', 'computer-repair-shop'),
            wc_cr_currency_format($existing_expense->amount),
            wc_cr_currency_format($amount)
        );
    }
    
    if ($existing_expense->payment_method != $payment_method) {
        $payment_methods = $expense_manager->get_payment_methods();
        $old_method = $payment_methods[$existing_expense->payment_method] ?? $existing_expense->payment_method;
        $new_method = $payment_methods[$payment_method] ?? $payment_method;
        
        $changes[] = sprintf(
            __('Payment method changed from %s to %s', 'computer-repair-shop'),
            $old_method,
            $new_method
        );
    }
    
    if ($existing_expense->payment_status != $payment_status) {
        $payment_statuses = $expense_manager->get_payment_statuses();
        $old_status = $payment_statuses[$existing_expense->payment_status] ?? $existing_expense->payment_status;
        $new_status = $payment_statuses[$payment_status] ?? $payment_status;
        
        $changes[] = sprintf(
            __('Payment status changed from %s to %s', 'computer-repair-shop'),
            $old_status,
            $new_status
        );
    }
    
    if ($existing_expense->receipt_number != $receipt_number) {
        if (empty($existing_expense->receipt_number)) {
            $changes[] = sprintf(
                __('Receipt number set to %s', 'computer-repair-shop'),
                $receipt_number
            );
        } else if (empty($receipt_number)) {
            $changes[] = sprintf(
                __('Receipt number removed (was %s)', 'computer-repair-shop'),
                $existing_expense->receipt_number
            );
        } else {
            $changes[] = sprintf(
                __('Receipt number changed from %s to %s', 'computer-repair-shop'),
                $existing_expense->receipt_number,
                $receipt_number
            );
        }
    }
    
    if ($existing_expense->expense_type != $expense_type) {
        $expense_types = $expense_manager->get_expense_types();
        $old_type = $expense_types[$existing_expense->expense_type] ?? $existing_expense->expense_type;
        $new_type = $expense_types[$expense_type] ?? $expense_type;
        
        $changes[] = sprintf(
            __('Expense type changed from %s to %s', 'computer-repair-shop'),
            $old_type,
            $new_type
        );
    }
    
    if ($existing_expense->status != $status) {
        $expense_statuses = $expense_manager->get_expense_statuses();
        $old_status = $expense_statuses[$existing_expense->status] ?? $existing_expense->status;
        $new_status = $expense_statuses[$status] ?? $status;
        
        $changes[] = sprintf(
            __('Status changed from %s to %s', 'computer-repair-shop'),
            $old_status,
            $new_status
        );
    }
    
    // Prepare update data
    $expense_data = array(
        'expense_date' => $expense_date,
        'category_id' => $category_id,
        'description' => $description,
        'amount' => $amount,
        'payment_method' => $payment_method,
        'payment_status' => $payment_status,
        'receipt_number' => $receipt_number,
        'expense_type' => $expense_type,
        'status' => $status,
        'updated_at' => current_time('mysql')
    );
    
    // Update expense
    $result = $expense_manager->update_expense($expense_id, $expense_data);
    
    if ($result) {
        // Log to job history if expense is related to a job
        if (!empty($existing_expense->job_id)) {
            $job_id = $existing_expense->job_id;
            
            // Prepare history log message
            if (!empty($changes)) {
                $history_message = sprintf(
                    __('Expense #%d updated: %s', 'computer-repair-shop'),
                    $expense_id,
                    implode(', ', $changes)
                );
            } else {
                $history_message = sprintf(
                    __('Expense #%d details refreshed', 'computer-repair-shop'),
                    $expense_id
                );
            }
            
            // Add to job history
            if (class_exists('WCRB_JOB_HISTORY_LOGS')) {
                $WCRB_JOB_HISTORY_LOGS = WCRB_JOB_HISTORY_LOGS::getInstance();
                $history_data = array(
                    "job_id"        => $job_id,
                    "name"          => $history_message,
                    "type"          => 'private',
                    "field"         => '_wc_expense_data',
                    "change_detail" => __('Expense details updated', 'computer-repair-shop')
                );
                $WCRB_JOB_HISTORY_LOGS->wc_record_job_history($history_data);
            }
        }
        
        wp_send_json_success(array(
            'message' => __('Expense updated successfully', 'computer-repair-shop'),
            'changes_logged' => !empty($changes)
        ));
    } else {
        wp_send_json_error(array('message' => __('Failed to update expense', 'computer-repair-shop')));
    }
}

// Export Expenses CSV
add_action('wp_ajax_wcrb_export_expenses_csv', 'wcrb_ajax_export_expenses_csv');
add_action('wp_ajax_nopriv_wcrb_export_expenses_csv', 'wcrb_ajax_export_expenses_csv');

// In both export functions, update the args array:
function wcrb_ajax_export_expenses_csv() {
    if (!wp_verify_nonce($_POST['nonce'], 'wcrb_expense_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed', 'computer-repair-shop')));
    }
    
    // Check permissions
    $current_role = wcrb_current_user_role();
    if ( $current_role != 'administrator' && $current_role != 'store_manager' ) {
        wp_send_json_error(array('message' => __('Permission denied', 'computer-repair-shop')));
    }
    
    // Get filters from AJAX request
    $search         = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $category_id    = isset($_POST['category_id']) ? intval($_POST['category_id']) : '';
    $payment_status = isset($_POST['payment_status']) ? sanitize_text_field($_POST['payment_status']) : '';
    $start_date     = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
    $end_date       = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';
    
    $expense_manager = WC_CR_EXPENSE_MANAGEMENT();
    
    // Get all expenses (no pagination for export)
    $args = array(
        'search'         => $search,
        'category_id'    => $category_id,
        'payment_status' => $payment_status,
        'start_date'     => $start_date,
        'end_date'       => $end_date,
        'limit'          => 0, // Get all
        'offset'         => 0,
        // Remove user filter to get all expenses
        // 'created_by'     => get_current_user_id()
    );
    
    $expenses_data = $expense_manager->get_expenses($args);
    $expenses = $expenses_data['expenses'];
    
    // Generate CSV content
    $csv_content = generate_expenses_csv($expenses);
    
    // Set headers for file download
    $filename = 'expenses-' . date('Y-m-d-H-i-s') . '.csv';
    
    wp_send_json_success(array(
        'filename' => $filename,
        'content' => $csv_content
    ));
}

// Helper function to generate CSV content
function generate_expenses_csv($expenses) {
    // Create output buffer
    ob_start();
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // CSV Headers
    $headers = array(
        __('ID', 'computer-repair-shop'),
        __('Date', 'computer-repair-shop'),
        __('Category', 'computer-repair-shop'),
        __('Description', 'computer-repair-shop'),
        __('Amount', 'computer-repair-shop'),
        __('Tax', 'computer-repair-shop'),
        __('Total', 'computer-repair-shop'),
        __('Payment Method', 'computer-repair-shop'),
        __('Payment Status', 'computer-repair-shop'),
        __('Receipt Number', 'computer-repair-shop'),
        __('Expense Type', 'computer-repair-shop'),
        __('Status', 'computer-repair-shop'),
        __('Created By', 'computer-repair-shop'),
        __('Created At', 'computer-repair-shop')
    );
    
    // Output headers to buffer
    fputcsv($output, $headers);
    
    // CSV Data
    foreach ($expenses as $expense) {
        // Get created by name safely
        $created_by_name = '';
        if (!empty($expense->created_by)) {
            $user = get_userdata($expense->created_by);
            if ($user) {
                $created_by_name = $user->display_name;
            }
        }
        
        $row = array(
            $expense->expense_number,
            date_i18n(get_option('date_format'), strtotime($expense->expense_date)),
            $expense->category_name,
            $expense->description,
            wc_cr_currency_format($expense->amount, false, true),
            wc_cr_currency_format($expense->tax_amount, false, true),
            wc_cr_currency_format($expense->total_amount, false, true),
            $expense->payment_method ?: '',
            $expense->payment_status,
            $expense->receipt_number ?: '',
            $expense->expense_type,
            $expense->status,
            $created_by_name,
            date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($expense->created_at))
        );
        
        fputcsv($output, $row);
    }
    
    fclose($output);
    $csv_content = ob_get_clean();
    
    return $csv_content;
}

// Export Expenses PDF using DomPDF
add_action('wp_ajax_wcrb_export_expenses_pdf', 'wcrb_ajax_export_expenses_pdf');
add_action('wp_ajax_nopriv_wcrb_export_expenses_pdf', 'wcrb_ajax_export_expenses_pdf');

function wcrb_ajax_export_expenses_pdf() {
    if (!wp_verify_nonce($_POST['nonce'], 'wcrb_expense_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed', 'computer-repair-shop')));
    }
    
    // Check permissions
    $current_role = wcrb_current_user_role();
    if ( $current_role != 'administrator' && $current_role != 'store_manager' ) {
        wp_send_json_error(array('message' => __('Security check failed', 'computer-repair-shop')));
    }
    
    // Get filters from AJAX request
    $search         = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $category_id    = isset($_POST['category_id']) ? intval($_POST['category_id']) : '';
    $payment_status = isset($_POST['payment_status']) ? sanitize_text_field($_POST['payment_status']) : '';
    $start_date     = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
    $end_date       = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';
    
    $expense_manager = WC_CR_EXPENSE_MANAGEMENT();
    
    // Get all expenses (no pagination for export)
    $args = array(
        'search'         => $search,
        'category_id'    => $category_id,
        'payment_status' => $payment_status,
        'start_date'     => $start_date,
        'end_date'       => $end_date,
        'limit'          => 0, // Get all
        'offset'         => 0,
        'created_by'     => get_current_user_id()
    );
    
    $expenses_data = $expense_manager->get_expenses($args);
    $expenses = $expenses_data['expenses'];
    
    // Get statistics for summary
    $stats = $expense_manager->get_statistics('month', get_current_user_id());
    
    // Generate HTML for PDF
    $html_content = generate_expenses_pdf_html($expenses, $stats, $search, $start_date, $end_date, $category_id, $payment_status);

    // Generate PDF using your existing WCRB_PDF_MAKER class
    $pdf_maker = WCRB_PDF_MAKER::getInstance();
    $pdf_content = $pdf_maker->generate_pdf_from_html($html_content, 'expenses-report-' . date('Y-m-d-H-i-s'));
    
    wp_send_json_success(array(
        'filename' => 'expenses-' . date('Y-m-d-H-i-s') . '.pdf',
        'content' => base64_encode($pdf_content)
    ));
}

function generate_expenses_pdf_html($expenses, $stats, $search, $start_date, $end_date, $category_id, $payment_status) {
    $currency_symbol = return_wc_rb_currency_symbol();
    $current_user = wp_get_current_user();
    
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title><?php esc_html_e('Expenses Report', 'computer-repair-shop'); ?></title>
        <style>
            body {
                font-family: 'Helvetica', 'Arial', sans-serif;
                font-size: 12px;
                line-height: 1.4;
                color: #333;
                margin: 0;
                padding: 20px;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 2px solid #333;
            }
            .header h1 {
                margin: 0;
                font-size: 24px;
                color: #333;
            }
            .header .subtitle {
                color: #666;
                margin-top: 5px;
                font-size: 14px;
            }
            .summary {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                margin-bottom: 20px;
            }
            .summary-grid {
                display: flex;
                justify-content: space-between;
                flex-wrap: wrap;
            }
            .summary-item {
                text-align: center;
                padding: 10px;
                background: white;
                border-radius: 4px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                flex: 1;
                margin: 0 5px;
                min-width: 200px;
            }
            .summary-label {
                font-size: 11px;
                color: #666;
                margin-bottom: 5px;
            }
            .summary-value {
                font-size: 16px;
                font-weight: bold;
                color: #333;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
                font-size: 11px;
            }
            th {
                background-color: #f8f9fa;
                border: 1px solid #dee2e6;
                padding: 8px;
                text-align: left;
                font-weight: bold;
            }
            td {
                border: 1px solid #dee2e6;
                padding: 6px;
            }
            .text-right {
                text-align: right;
            }
            .text-center {
                text-align: center;
            }
            .total-row {
                background-color: #f8f9fa;
                font-weight: bold;
            }
            .footer {
                margin-top: 40px;
                padding-top: 20px;
                border-top: 1px solid #dee2e6;
                text-align: center;
                color: #666;
                font-size: 10px;
            }
            .badge {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 3px;
                font-size: 10px;
                font-weight: normal;
            }
            .badge-success { background-color: #d1e7dd; color: #0f5132; }
            .badge-warning { background-color: #fff3cd; color: #664d03; }
            .badge-info { background-color: #d1ecf1; color: #0c5460; }
            .badge-danger { background-color: #f8d7da; color: #842029; }
            .badge-secondary { background-color: #e2e3e5; color: #41464b; }
            .page-break {
                page-break-before: always;
            }
            .no-data {
                text-align: center;
                padding: 30px;
                color: #666;
                font-style: italic;
            }
            .filter-info {
                background: #f0f8ff;
                padding: 10px;
                border-radius: 4px;
                margin-bottom: 15px;
                font-size: 11px;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1><?php esc_html_e('Expenses Report', 'computer-repair-shop'); ?></h1>
            <div class="subtitle">
                <?php 
                echo date_i18n(get_option('date_format'));
                if ($start_date || $end_date) {
                    echo ' | ';
                    if ($start_date) echo __('From:', 'computer-repair-shop') . ' ' . $start_date;
                    if ($end_date) echo ' ' . __('To:', 'computer-repair-shop') . ' ' . $end_date;
                }
                if ($search) {
                    echo ' | ' . __('Search:', 'computer-repair-shop') . ' "' . esc_html($search) . '"';
                }
                ?>
            </div>
        </div>
        
        <?php 
        // Initialize $expense_manager here since we need it for category and status lookups
        $expense_manager = WC_CR_EXPENSE_MANAGEMENT();
        
        if ($search || $start_date || $end_date || $category_id || $payment_status): ?>
        <div class="filter-info">
            <strong><?php esc_html_e('Applied Filters:', 'computer-repair-shop'); ?></strong>
            <?php
            $filters = array();
            if ($search) $filters[] = __('Search:', 'computer-repair-shop') . ' "' . esc_html($search) . '"';
            if ($start_date) $filters[] = __('From:', 'computer-repair-shop') . ' ' . $start_date;
            if ($end_date) $filters[] = __('To:', 'computer-repair-shop') . ' ' . $end_date;
            if ($category_id) {
                $category = $expense_manager->get_category($category_id);
                if ($category) {
                    $filters[] = __('Category:', 'computer-repair-shop') . ' ' . $category->category_name;
                } else {
                    $filters[] = __('Category ID:', 'computer-repair-shop') . ' ' . $category_id;
                }
            }
            if ($payment_status) {
                $payment_statuses = $expense_manager->get_payment_statuses();
                $filters[] = __('Payment Status:', 'computer-repair-shop') . ' ' . ($payment_statuses[$payment_status] ?? $payment_status);
            }
            echo implode(' | ', $filters);
            ?>
        </div>
        <?php endif; ?>
        
        <?php if ($stats['totals']): ?>
        <div class="summary">
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-label"><?php esc_html_e('Total Expenses', 'computer-repair-shop'); ?></div>
                    <div class="summary-value"><?php echo wc_cr_currency_format($stats['totals']->grand_total ?? 0); ?></div>
                    <div class="summary-label"><?php echo sprintf(__('%d expenses', 'computer-repair-shop'), $stats['totals']->total_count ?? 0); ?></div>
                </div>
                <div class="summary-item">
                    <div class="summary-label"><?php esc_html_e('Total Amount', 'computer-repair-shop'); ?></div>
                    <div class="summary-value"><?php echo wc_cr_currency_format($stats['totals']->total_amount ?? 0); ?></div>
                </div>
                <div class="summary-item">
                    <div class="summary-label"><?php esc_html_e('Total Tax', 'computer-repair-shop'); ?></div>
                    <div class="summary-value"><?php echo wc_cr_currency_format($stats['totals']->total_tax ?? 0); ?></div>
                </div>
                <div class="summary-item">
                    <div class="summary-label"><?php esc_html_e('Generated By', 'computer-repair-shop'); ?></div>
                    <div class="summary-value"><?php echo esc_html($current_user->display_name); ?></div>
                    <div class="summary-label"><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format')); ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <table>
            <thead>
                <tr>
                    <th><?php esc_html_e('ID', 'computer-repair-shop'); ?></th>
                    <th><?php esc_html_e('Date', 'computer-repair-shop'); ?></th>
                    <th><?php esc_html_e('Category', 'computer-repair-shop'); ?></th>
                    <th><?php esc_html_e('Description', 'computer-repair-shop'); ?></th>
                    <th class="text-right"><?php esc_html_e('Amount', 'computer-repair-shop'); ?></th>
                    <th class="text-right"><?php esc_html_e('Tax', 'computer-repair-shop'); ?></th>
                    <th class="text-right"><?php esc_html_e('Total', 'computer-repair-shop'); ?></th>
                    <th><?php esc_html_e('Payment', 'computer-repair-shop'); ?></th>
                    <th><?php esc_html_e('Status', 'computer-repair-shop'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total_amount = 0;
                $total_tax = 0;
                $grand_total = 0;
                
                if (empty($expenses)): ?>
                    <tr>
                        <td colspan="9" class="no-data">
                            <?php esc_html_e('No expenses found', 'computer-repair-shop'); ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    // Payment statuses are already available from $expense_manager above
                    $payment_statuses = $expense_manager->get_payment_statuses();
                    
                    foreach ($expenses as $expense): 
                        $total_amount += $expense->amount;
                        $total_tax += $expense->tax_amount;
                        $grand_total += $expense->total_amount;
                        
                        // Status classes
                        $payment_status_classes = array(
                            'paid' => 'badge-success',
                            'pending' => 'badge-warning',
                            'partial' => 'badge-info',
                            'overdue' => 'badge-danger'
                        );
                        $status_class = $payment_status_classes[$expense->payment_status] ?? 'badge-secondary';
                        
                        $expense_status_classes = array(
                            'active' => 'badge-success',
                            'void' => 'badge-danger',
                            'refunded' => 'badge-info'
                        );
                        $expense_status_class = $expense_status_classes[$expense->status] ?? 'badge-secondary';
                        ?>
                        <tr>
                            <td><?php echo esc_html($expense->expense_number); ?></td>
                            <td><?php echo date_i18n(get_option('date_format'), strtotime($expense->expense_date)); ?></td>
                            <td><?php echo esc_html($expense->category_name); ?></td>
                            <td><?php echo esc_html(wp_trim_words($expense->description, 10)); ?></td>
                            <td class="text-right"><?php echo wc_cr_currency_format($expense->amount); ?></td>
                            <td class="text-right"><?php echo wc_cr_currency_format($expense->tax_amount); ?></td>
                            <td class="text-right"><?php echo wc_cr_currency_format($expense->total_amount); ?></td>
                            <td>
                                <span class="badge <?php echo $status_class; ?>">
                                    <?php echo esc_html($payment_statuses[$expense->payment_status] ?? $expense->payment_status); ?>
                                </span>
                                <?php if ($expense->payment_method): ?>
                                    <br><small><?php echo esc_html($expense->payment_method); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $expense_status_class; ?>">
                                    <?php echo esc_html($expense->status); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="4" class="text-right"><strong><?php esc_html_e('Totals:', 'computer-repair-shop'); ?></strong></td>
                        <td class="text-right"><strong><?php echo wc_cr_currency_format($total_amount); ?></strong></td>
                        <td class="text-right"><strong><?php echo wc_cr_currency_format($total_tax); ?></strong></td>
                        <td class="text-right"><strong><?php echo wc_cr_currency_format($grand_total); ?></strong></td>
                        <td colspan="2"></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="footer">
            <p><?php echo esc_html(get_bloginfo('name')); ?></p>
            <p><?php echo esc_url(site_url()); ?></p>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

/**
 * Categories handlres
 */

// Get category for edit modal
add_action('wp_ajax_wcrb_get_category', 'wcrb_ajax_get_category');
add_action('wp_ajax_nopriv_wcrb_get_category', 'wcrb_ajax_get_category');

function wcrb_ajax_get_category() {
    if (!wp_verify_nonce($_POST['nonce'], 'wcrb_expense_category_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed', 'computer-repair-shop')));
    }
    
    // Check permissions
    $current_role = wcrb_current_user_role();
    if ( $current_role != 'administrator' && $current_role != 'store_manager' ) {
        wp_send_json_error(array('message' => __('Security check failed', 'computer-repair-shop')));
    }

    $category_id = intval($_POST['category_id']);
    $expense_manager = WC_CR_EXPENSE_MANAGEMENT();
    $category = $expense_manager->get_category($category_id);
    
    if (!$category) {
        wp_send_json_error(array('message' => __('Category not found', 'computer-repair-shop')));
    }
    
    wp_send_json_success(array(
        'category_id' => $category->category_id,
        'category_name' => $category->category_name,
        'category_description' => $category->category_description,
        'color_code' => $category->color_code,
        'sort_order' => $category->sort_order,
        'taxable' => $category->taxable,
        'tax_rate' => $category->tax_rate,
        'is_active' => $category->is_active
    ));
}

// Update category AJAX handler
add_action('wp_ajax_wcrb_update_category', 'wcrb_ajax_update_category');
add_action('wp_ajax_nopriv_wcrb_update_category', 'wcrb_ajax_update_category');

function wcrb_ajax_update_category() {
    if (!wp_verify_nonce($_POST['nonce'], 'wcrb_expense_category_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed', 'computer-repair-shop')));
    }
    
    // Check permissions
    $current_role = wcrb_current_user_role();
    if ( $current_role != 'administrator' && $current_role != 'store_manager' ) {
        wp_send_json_error(array('message' => __('Security check failed', 'computer-repair-shop')));
    }
    
    $category_id = intval($_POST['category_id'] ?? 0);
    $category_name = sanitize_text_field($_POST['category_name'] ?? '');
    $category_description = sanitize_textarea_field($_POST['category_description'] ?? '');
    $color_code = sanitize_hex_color($_POST['color_code'] ?? '#3498db');
    $sort_order = intval($_POST['sort_order'] ?? 0);
    $taxable = isset($_POST['taxable']) ? 1 : 0;
    $tax_rate = floatval($_POST['tax_rate'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($category_name)) {
        wp_send_json_error(array('message' => __('Category name is required', 'computer-repair-shop')));
    }
    
    if ($tax_rate < 0 || $tax_rate > 100) {
        wp_send_json_error(array('message' => __('Tax rate must be between 0 and 100', 'computer-repair-shop')));
    }
    
    $expense_manager = WC_CR_EXPENSE_MANAGEMENT();
    
    // Check if another category with same name exists (excluding current one)
    global $wpdb;
    $table_name = $wpdb->prefix . 'wc_cr_expense_categories';
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name 
         WHERE category_name = %s 
         AND category_type = 'expense' 
         AND category_id != %d",
        $category_name,
        $category_id
    ));
    
    if ($existing > 0) {
        wp_send_json_error(array(
            'message' => sprintf(
                __('Category "%s" already exists', 'computer-repair-shop'),
                $category_name
            )
        ));
    }
    
    $category_data = array(
        'category_name' => $category_name,
        'category_description' => $category_description,
        'color_code' => $color_code,
        'sort_order' => $sort_order,
        'taxable' => $taxable,
        'tax_rate' => $tax_rate,
        'is_active' => $is_active,
        'updated_at' => current_time('mysql')
    );
    
    $result = $expense_manager->update_category($category_id, $category_data);
    
    if ($result) {
        wp_send_json_success(array(
            'message' => __('Category updated successfully', 'computer-repair-shop')
        ));
    } else {
        wp_send_json_error(array('message' => __('Failed to update category', 'computer-repair-shop')));
    }
}

// Add Expense Category AJAX handler
add_action( 'wp_ajax_wcrb_add_expense_category', 'wcrb_ajax_add_expense_category' );
add_action( 'wp_ajax_nopriv_wcrb_add_expense_category', 'wcrb_ajax_add_expense_category' );

if ( ! function_exists( 'wcrb_ajax_add_expense_category' ) ) :
    function wcrb_ajax_add_expense_category() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'wcrb_expense_category_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'computer-repair-shop' ) ) );
        }
        
        // Check permissions
        $current_role = wcrb_current_user_role();
        if ( $current_role != 'administrator' && $current_role != 'technician' && $current_role != 'store_manager' ) {
            return;
        }
        
        // Get and sanitize data
        $category_name        = sanitize_text_field( $_POST['category_name'] ?? '' );
        $category_description = sanitize_textarea_field( $_POST['category_description'] ?? '' );
        $color_code           = sanitize_hex_color( $_POST['color_code'] ?? '#3498db' );
        $sort_order           = intval( $_POST['sort_order'] ?? 0 );
        $taxable              = isset( $_POST['taxable']) ? 1 : 0;
        $tax_rate             = floatval( $_POST['tax_rate'] ?? 0 );
        $is_active            = isset( $_POST['is_active']) ? 1 : 0;
        
        // Validate required fields
        if ( empty( $category_name ) ) {
            wp_send_json_error( array( 'message' => __( 'Category name is required', 'computer-repair-shop' ) ) );
        }
        
        // Validate tax rate
        if ($tax_rate < 0 || $tax_rate > 100) {
            wp_send_json_error( array( 'message' => __( 'Tax rate must be between 0 and 100', 'computer-repair-shop' ) ) );
        }
        
        // Get expense manager instance
        $expense_manager = WC_CR_EXPENSE_MANAGEMENT();
        
        // Check if category already exists
        $existing_category = $expense_manager->wcrb_check_category_exists( $category_name );
        if ( $existing_category ) {
            wp_send_json_error(array(
                'message' => sprintf(
                    __( 'Category "%s" already exists', 'computer-repair-shop' ),
                    $category_name
                )
            ));
        }
        
        // Prepare category data
        $category_data = array(
            'category_name'         => $category_name,
            'category_description'  => $category_description,
            'category_type'         => 'expense',
            'parent_category_id'    => 0,
            'color_code'            => $color_code,
            'icon_class'            => null,
            'is_active'             => $is_active,
            'is_default'            => 0,
            'taxable'               => $taxable,
            'tax_rate'              => $tax_rate,
            'sort_order'            => $sort_order,
            'created_by'            => get_current_user_id(),
            'created_at'            => current_time('mysql')
        );
        
        // Add category
        $category_id = $expense_manager->add_category($category_data );
        
        if ($category_id) {
            wp_send_json_success(array(
                'message'     => __( 'Category added successfully', 'computer-repair-shop' ),
                'category_id' => $category_id
            ));
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to add category', 'computer-repair-shop' ) ) );
        }
    }
endif;

// Delete category
add_action('wp_ajax_wcrb_delete_category', 'wcrb_ajax_delete_category');
add_action('wp_ajax_nopriv_wcrb_delete_category', 'wcrb_ajax_delete_category');

if ( ! function_exists( 'wcrb_ajax_delete_category' ) ) :
    function wcrb_ajax_delete_category() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'wcrb_expense_category_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'computer-repair-shop' ) ) );
        }
        
        // Check permissions
        $current_role = wcrb_current_user_role();
        if ( $current_role != 'administrator' && $current_role != 'store_manager' ) {
            return;
        }

        $category_id      = intval($_POST['category_id']);
        $expense_manager  = WC_CR_EXPENSE_MANAGEMENT();
        
        $result = $expense_manager->delete_category($category_id);
        
        if ( is_wp_error( $result ) ) {
            wp_send_json_error (array( 'message' => $result->get_error_message() ) );
        } elseif ( $result ) {
            wp_send_json_success( array( 'message' => __( 'Category deleted successfully', 'computer-repair-shop' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to delete category', 'computer-repair-shop' ) ) );
        }
    }
endif;