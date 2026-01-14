<?php
    defined( 'ABSPATH' ) || exit;

    if ( $role != 'store_manager' && $role != 'administrator' ) {
        echo esc_html__( "You do not have sufficient permissions to access this page.", "computer-repair-shop" );
        exit;
    }

    $expense_manager = WC_CR_EXPENSE_MANAGEMENT();
    
    // Get filters
    $search         = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
    $category_id    = isset($_GET['category_id']) ? intval($_GET['category_id']) : '';
    $payment_status = isset($_GET['payment_status']) ? sanitize_text_field($_GET['payment_status']) : '';
    $start_date     = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
    $end_date       = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';
    $page           = isset($_GET['expenses_page']) ? max(1, intval($_GET['expenses_page'])) : 1;
    $limit          = 20;
    $offset         = ($page - 1) * $limit;
    
    // Get expenses
    $args = array(
        'search'         => $search,
        'category_id'    => $category_id,
        'payment_status' => $payment_status,
        'start_date'     => $start_date,
        'end_date'       => $end_date,
        'limit'          => $limit,
        'offset'         => $offset
    );
    
    $expenses_data  = $expense_manager->get_expenses( $args );
    $expenses       = $expenses_data['expenses'];
    $total_expenses = $expenses_data['total'];
    $total_pages    = ceil($total_expenses / $limit);
    
    // Get statistics
    $stats = $expense_manager->get_statistics('month');
    
    // Get categories for dropdown
    $categories = $expense_manager->get_categories();
    
    // Get payment methods and statuses
    $payment_methods = $expense_manager->get_payment_methods();
    $payment_statuses = $expense_manager->get_payment_statuses();
?>
    <!-- Expenses Content -->
    <main class="dashboard-content container-fluid py-4">
        <!-- Stats Overview -->
        <div class="row g-3 mb-4">
            <div class="col">
                <div class="card stats-card bg-primary text-white">
                    <div class="card-body text-center p-3">
                        <div class="mb-2">
                            <i class="bi bi-cash-stack fs-1 opacity-75"></i>
                        </div>
                        <h6 class="card-title mb-1 text-white-50"><?php esc_html_e('Total Expenses', 'computer-repair-shop'); ?></h6>
                        <h3 class="mb-0"><?php echo wc_cr_currency_format($stats['totals']->grand_total ?? 0); ?></h3>
                        <small class="d-block mt-1 opacity-75"><?php echo sprintf(__('%d Expenses', 'computer-repair-shop'), $stats['totals']->total_count ?? 0); ?></small>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card stats-card bg-success text-white">
                    <div class="card-body text-center p-3">
                        <div class="mb-2">
                            <i class="bi bi-check-circle fs-1 opacity-75"></i>
                        </div>
                        <h6 class="card-title mb-1 text-white-50"><?php esc_html_e('Paid', 'computer-repair-shop'); ?></h6>
                        <h3 class="mb-0"><?php echo wc_cr_currency_format($stats['totals']->total_amount ?? 0); ?></h3>
                        <small class="d-block mt-1 opacity-75"><?php esc_html_e('Amount', 'computer-repair-shop'); ?></small>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card stats-card bg-info text-white">
                    <div class="card-body text-center p-3">
                        <div class="mb-2">
                            <i class="bi bi-receipt fs-1 opacity-75"></i>
                        </div>
                        <h6 class="card-title mb-1 text-white-50"><?php esc_html_e('Tax', 'computer-repair-shop'); ?></h6>
                        <h3 class="mb-0"><?php echo wc_cr_currency_format($stats['totals']->total_tax ?? 0); ?></h3>
                        <small class="d-block mt-1 opacity-75"><?php esc_html_e('Total Tax', 'computer-repair-shop'); ?></small>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card stats-card bg-warning text-white">
                    <div class="card-body text-center p-3">
                        <div class="mb-2">
                            <i class="bi bi-hourglass-split fs-1 opacity-75"></i>
                        </div>
                        <h6 class="card-title mb-1 text-white-50"><?php esc_html_e('Pending', 'computer-repair-shop'); ?></h6>
                        <h3 class="mb-0"><?php echo wc_cr_currency_format(0); // You'll need to calculate pending ?></h3>
                        <small class="d-block mt-1 opacity-75"><?php esc_html_e('Unpaid Expenses', 'computer-repair-shop'); ?></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="get" action="">
                    <input type="hidden" name="screen" value="expenses" />
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="bi bi-search text-muted"></i>
                                </span>
                                <input type="text" class="form-control border-start-0" 
                                       name="search" value="<?php echo esc_attr($search); ?>" 
                                       placeholder="<?php esc_attr_e('Search expenses...', 'computer-repair-shop'); ?>">
                            </div>
                        </div>
                        <div class="col">
                            <select name="category_id" class="form-select">
                                <option value=""><?php esc_html_e('All Categories', 'computer-repair-shop'); ?></option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category->category_id; ?>" 
                                        <?php selected($category_id, $category->category_id); ?>>
                                        <?php echo esc_html($category->category_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col">
                            <select name="payment_status" class="form-select">
                                <option value=""><?php esc_html_e('All Status', 'computer-repair-shop'); ?></option>
                                <?php foreach ($payment_statuses as $key => $label): ?>
                                    <option value="<?php echo esc_attr($key); ?>"
                                        <?php selected($payment_status, $key); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col">
                            <input type="date" class="form-control" name="start_date" 
                                   value="<?php echo esc_attr($start_date); ?>"
                                   placeholder="<?php esc_attr_e('From Date', 'computer-repair-shop'); ?>">
                        </div>
                        <div class="col">
                            <input type="date" class="form-control" name="end_date" 
                                   value="<?php echo esc_attr($end_date); ?>"
                                   placeholder="<?php esc_attr_e('To Date', 'computer-repair-shop'); ?>">
                        </div>
                        <div class="col-md-2 d-flex justify-content-end">
                            <div class="d-flex gap-2">
                                <a href="<?php echo esc_url(remove_query_arg(array('search', 'category_id', 'payment_status', 'start_date', 'end_date', 'expenses_page'))); ?>" 
                                   class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-funnel"></i> <?php esc_html_e('Filter', 'computer-repair-shop'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Expenses Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-receipt me-2"></i>
                    <?php esc_html_e('All Expenses', 'computer-repair-shop'); ?>
                </h5>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm" 
                            data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                        <i class="bi bi-plus-circle me-1"></i>
                        <?php esc_html_e('Add Expense', 'computer-repair-shop'); ?>
                    </button>

                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-download me-1"></i> <?php esc_html_e('Export', 'computer-repair-shop'); ?>
                        </button>
                        <?php if ( wc_rs_license_state() ) { ?>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item export-csv" href="#">
                                        <i class="bi bi-filetype-csv me-2"></i><?php esc_html_e('CSV', 'computer-repair-shop'); ?>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item export-pdf" href="#">
                                        <i class="bi bi-filetype-pdf me-2"></i><?php esc_html_e('PDF', 'computer-repair-shop'); ?>
                                    </a>
                                </li>
                            </ul>
                        <?php } else { ?>
                            <ul class="dropdown-menu">
                                <li><span class="dropdown-item text-muted">
                                    <i class="bi bi-lock me-2"></i><?php echo esc_html__( 'Pro Feature', 'computer-repair-shop' ); ?>
                                </span></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-success" href="https://www.webfulcreations.com/repairbuddy-wordpress-plugin/pricing/" target="_blank" data-bs-toggle="modal" data-bs-target="#upgradeModal">
                                    <i class="bi bi-star me-2"></i><?php echo esc_html__( 'Upgrade Now', 'computer-repair-shop' ); ?>
                                </a></li>
                                <li><a class="dropdown-item text-info" href="https://www.webfulcreations.com/repairbuddy-wordpress-plugin/repairbuddy-features/" target="_blank">
                                    <i class="bi bi-info-circle me-2"></i><?php echo esc_html__( 'View Features', 'computer-repair-shop' ); ?>
                                </a></li>
                            </ul>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 table-striped">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4"><?php esc_html_e('ID', 'computer-repair-shop'); ?></th>
                                <th><?php esc_html_e('Date', 'computer-repair-shop'); ?></th>
                                <th><?php esc_html_e('Category', 'computer-repair-shop'); ?></th>
                                <th><?php esc_html_e('Description', 'computer-repair-shop'); ?></th>
                                <th><?php esc_html_e('Amount', 'computer-repair-shop'); ?></th>
                                <th><?php esc_html_e('Tax', 'computer-repair-shop'); ?></th>
                                <th><?php esc_html_e('Total', 'computer-repair-shop'); ?></th>
                                <th><?php esc_html_e('Payment', 'computer-repair-shop'); ?></th>
                                <th><?php esc_html_e('Method', 'computer-repair-shop'); ?></th>
                                <th><?php esc_html_e('Status', 'computer-repair-shop'); ?></th>
                                <th><?php esc_html_e('By', 'computer-repair-shop'); ?></th>
                                <th class="text-end pe-4"><?php esc_html_e('Actions', 'computer-repair-shop'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($expenses)): ?>
                                <tr>
                                    <td colspan="12" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="bi bi-receipt fs-1 opacity-50"></i>
                                            <p class="mt-2"><?php esc_html_e('No expenses found', 'computer-repair-shop'); ?></p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($expenses as $expense): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <strong>#<?php echo esc_html($expense->expense_number); ?></strong>
                                        </td>
                                        <td>
                                            <?php echo date_i18n(get_option('date_format'), strtotime($expense->expense_date)); ?>
                                        </td>
                                        <td>
                                            <span class="badge" style="background-color: <?php echo esc_attr($expense->color_code); ?>">
                                                <?php echo esc_html($expense->category_name); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo esc_html(wp_trim_words($expense->description, 10)); ?>
                                            <?php if ($expense->receipt_number): ?>
                                                <br><small class="text-muted"><?php echo sprintf(__('Receipt: %s', 'computer-repair-shop'), esc_html($expense->receipt_number)); ?></small>
                                            <?php endif; ?>
                                            <?php 
                                                if ( $expense->job_id ) : 
                                                    $jobs_manager = WCRB_JOBS_MANAGER::getInstance();
                                                    $job_data 	  = $jobs_manager->get_job_display_data( $expense->job_id );
                                                    $_job_id  	  = ( ! empty( $job_data['formatted_job_number'] ) ) ? $job_data['formatted_job_number'] : $expense->job_id;
                                            ?>
                                                <br><small class="text-muted"><?php echo sprintf(__('Job ID: %s', 'computer-repair-shop'), esc_html( $_job_id ) ); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo wc_cr_currency_format($expense->amount); ?></strong>
                                        </td>
                                        <td>
                                            <?php echo wc_cr_currency_format($expense->tax_amount); ?>
                                        </td>
                                        <td>
                                            <strong class="text-primary"><?php echo wc_cr_currency_format($expense->total_amount); ?></strong>
                                        </td>
                                        <td>
                                            <?php 
                                            $payment_status_labels = $expense_manager->get_payment_statuses();
                                            $status_class = array(
                                                'paid' => 'success',
                                                'pending' => 'warning',
                                                'partial' => 'info',
                                                'overdue' => 'danger'
                                            );
                                            ?>
                                            <span class="badge bg-<?php echo $status_class[$expense->payment_status] ?? 'secondary'; ?>">
                                                <?php echo esc_html($payment_status_labels[$expense->payment_status] ?? $expense->payment_status); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($expense->payment_method): ?>
                                                <small class="text-muted"><?php echo esc_html($expense->payment_method); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $expense->status == 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo esc_html( $expense->status ); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                                $user_info = get_userdata( $expense->created_by );
                                                $first_name = $user_info ? $user_info->first_name : '';
                                                $last_name = $user_info ? $user_info->last_name : '';
                                                echo esc_html( $first_name || $last_name ? trim("$first_name $last_name") : '' );
                                            ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="dropdown">
                                                <button class="btn btn-outline-primary btn-sm dropdown-toggle" 
                                                        type="button" data-bs-toggle="dropdown">
                                                    <i class="bi bi-gear me-1"></i> <?php esc_html_e('Actions', 'computer-repair-shop'); ?>
                                                </button>
                                                <ul class="dropdown-menu shadow-sm">
                                                    <li>
                                                        <a class="dropdown-item" href="#" 
                                                           data-bs-toggle="modal" data-bs-target="#editExpenseModal"
                                                           data-expense-id="<?php echo $expense->expense_id; ?>">
                                                            <i class="bi bi-pencil-square text-primary me-2"></i>
                                                            <?php esc_html_e('Edit', 'computer-repair-shop'); ?>
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="#" 
                                                           data-bs-toggle="modal" data-bs-target="#viewExpenseModal"
                                                           data-expense-id="<?php echo $expense->expense_id; ?>">
                                                            <i class="bi bi-eye text-info me-2"></i>
                                                            <?php esc_html_e('View Details', 'computer-repair-shop'); ?>
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item text-danger delete-expense-btn" href="#" 
                                                        data-expense-id="<?php echo $expense->expense_id; ?>">
                                                            <i class="bi bi-trash text-danger me-2"></i>
                                                            <?php esc_html_e('Delete', 'computer-repair-shop'); ?>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            <?php echo sprintf(__('Showing %d to %d of %d expenses', 'computer-repair-shop'), 
                                $offset + 1, 
                                min($offset + $limit, $total_expenses), 
                                $total_expenses); ?>
                        </div>
                        <ul class="pagination mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" 
                                       href="<?php echo esc_url(add_query_arg('expenses_page', $page - 1)); ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <a class="page-link" href="#">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" 
                                           href="<?php echo esc_url(add_query_arg('expenses_page', $i)); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" 
                                       href="<?php echo esc_url(add_query_arg('expenses_page', $page + 1)); ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <a class="page-link" href="#">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Add Expense Modal -->
    <div class="modal fade" id="addExpenseModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php esc_html_e('Add New Expense', 'computer-repair-shop'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addExpenseForm" data-async method="post">
                        <input type="hidden" name="action" value="wcrb_add_expense">
                        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wcrb_expense_nonce'); ?>">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="expense_date" class="form-label">
                                    <?php esc_html_e('Date *', 'computer-repair-shop'); ?>
                                </label>
                                <input type="date" class="form-control" id="expense_date" 
                                       name="expense_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="category_id" class="form-label">
                                    <?php esc_html_e('Category *', 'computer-repair-shop'); ?>
                                </label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value=""><?php esc_html_e('Select Category', 'computer-repair-shop'); ?></option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category->category_id; ?>">
                                            <?php echo esc_html($category->category_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="description" class="form-label">
                                    <?php esc_html_e('Description *', 'computer-repair-shop'); ?>
                                </label>
                                <textarea class="form-control" id="description" name="description" 
                                          rows="2" required></textarea>
                            </div>
                            <div class="col-md-4">
                                <label for="amount" class="form-label">
                                    <?php esc_html_e('Amount *', 'computer-repair-shop'); ?>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><?php echo return_wc_rb_currency_symbol(); ?></span>
                                    <input type="number" class="form-control" id="amount" 
                                           name="amount" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="payment_method" class="form-label">
                                    <?php esc_html_e('Payment Method', 'computer-repair-shop'); ?>
                                </label>
                                <select class="form-select" id="payment_method" name="payment_method">
                                    <option value=""><?php esc_html_e('Select Method', 'computer-repair-shop'); ?></option>
                                    <?php foreach ($payment_methods as $key => $label): ?>
                                        <option value="<?php echo esc_attr($key); ?>">
                                            <?php echo esc_html($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="payment_status" class="form-label">
                                    <?php esc_html_e('Payment Status', 'computer-repair-shop'); ?>
                                </label>
                                <select class="form-select" id="payment_status" name="payment_status">
                                    <?php foreach ($payment_statuses as $key => $label): ?>
                                        <option value="<?php echo esc_attr($key); ?>" 
                                            <?php selected($key, 'paid'); ?>>
                                            <?php echo esc_html($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="receipt_number" class="form-label">
                                    <?php esc_html_e('Receipt Number', 'computer-repair-shop'); ?>
                                </label>
                                <input type="text" class="form-control" id="receipt_number" 
                                       name="receipt_number">
                            </div>
                            <div class="col-md-6">
                                <label for="expense_type" class="form-label">
                                    <?php esc_html_e('Expense Type', 'computer-repair-shop'); ?>
                                </label>
                                <select class="form-select" id="expense_type" name="expense_type">
                                    <?php 
                                    $expense_types = $expense_manager->get_expense_types();
                                    foreach ($expense_types as $key => $label): ?>
                                        <option value="<?php echo esc_attr($key); ?>">
                                            <?php echo esc_html($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <?php esc_html_e('Close', 'computer-repair-shop'); ?>
                    </button>
                    <button type="button" class="btn btn-primary" id="submitExpenseForm">
                        <?php esc_html_e('Add Expense', 'computer-repair-shop'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Expense Modal -->
<div class="modal fade" id="editExpenseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php esc_html_e('Edit Expense', 'computer-repair-shop'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editExpenseForm" data-async method="post">
                    <input type="hidden" name="action" value="wcrb_update_expense">
                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wcrb_expense_nonce'); ?>">
                    <input type="hidden" name="expense_id" id="edit_expense_id">
                    
                    <!-- Same fields as add form but with edit_ prefix IDs -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="edit_expense_date" class="form-label">
                                <?php esc_html_e('Date *', 'computer-repair-shop'); ?>
                            </label>
                            <input type="date" class="form-control" id="edit_expense_date" 
                                   name="expense_date" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_category_id" class="form-label">
                                <?php esc_html_e('Category *', 'computer-repair-shop'); ?>
                            </label>
                            <select class="form-select" id="edit_category_id" name="category_id" required>
                                <option value=""><?php esc_html_e('Select Category', 'computer-repair-shop'); ?></option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category->category_id; ?>">
                                        <?php echo esc_html($category->category_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="edit_description" class="form-label">
                                <?php esc_html_e('Description *', 'computer-repair-shop'); ?>
                            </label>
                            <textarea class="form-control" id="edit_description" name="description" 
                                      rows="2" required></textarea>
                        </div>
                        <div class="col-md-4">
                            <label for="edit_amount" class="form-label">
                                <?php esc_html_e('Amount *', 'computer-repair-shop'); ?>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><?php echo return_wc_rb_currency_symbol(); ?></span>
                                <input type="number" class="form-control" id="edit_amount" 
                                       name="amount" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="edit_payment_method" class="form-label">
                                <?php esc_html_e('Payment Method', 'computer-repair-shop'); ?>
                            </label>
                            <select class="form-select" id="edit_payment_method" name="payment_method">
                                <option value=""><?php esc_html_e('Select Method', 'computer-repair-shop'); ?></option>
                                <?php foreach ($payment_methods as $key => $label): ?>
                                    <option value="<?php echo esc_attr($key); ?>">
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="edit_payment_status" class="form-label">
                                <?php esc_html_e('Payment Status', 'computer-repair-shop'); ?>
                            </label>
                            <select class="form-select" id="edit_payment_status" name="payment_status">
                                <?php foreach ($payment_statuses as $key => $label): ?>
                                    <option value="<?php echo esc_attr($key); ?>">
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_receipt_number" class="form-label">
                                <?php esc_html_e('Receipt Number', 'computer-repair-shop'); ?>
                            </label>
                            <input type="text" class="form-control" id="edit_receipt_number" 
                                   name="receipt_number">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_expense_type" class="form-label">
                                <?php esc_html_e('Expense Type', 'computer-repair-shop'); ?>
                            </label>
                            <select class="form-select" id="edit_expense_type" name="expense_type">
                                <?php 
                                $expense_types = $expense_manager->get_expense_types();
                                foreach ($expense_types as $key => $label): ?>
                                    <option value="<?php echo esc_attr($key); ?>">
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_status" class="form-label">
                                <?php esc_html_e('Status', 'computer-repair-shop'); ?>
                            </label>
                            <select class="form-select" id="edit_status" name="status">
                                <option value="active"><?php echo esc_html__( 'Active', 'computer-repair-shop' ); ?></option>
                                <option value="void"><?php echo esc_html__( 'Void', 'computer-repair-shop' ); ?></option>
                                <option value="refunded"><?php echo esc_html__( 'Refunded', 'computer-repair-shop' ); ?></option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <?php esc_html_e( 'Close', 'computer-repair-shop' ); ?>
                </button>
                <button type="button" class="btn btn-primary" id="submitEditExpenseForm">
                    <?php esc_html_e( 'Update Expense', 'computer-repair-shop' ); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- View Expense Modal -->
<div class="modal fade" id="viewExpenseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php esc_html_e('Expense Details', 'computer-repair-shop'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="expense-details">
                    <!-- AJAX content will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <?php esc_html_e('Close', 'computer-repair-shop'); ?>
                </button>
            </div>
        </div>
    </div>
</div>