<?php
    defined( 'ABSPATH' ) || exit;

    $wcrb_next_service_date = get_option('wcrb_next_service_date');
    $_enable_next_service_d = ($wcrb_next_service_date == 'on') ? 'yes' : 'no';
?>
<main class="dashboard-content container-fluid py-4">
    <?php
        // Output calendar HTML
        $output = '<div class="calendar-container bg-white rounded-3 shadow-sm p-4 mb-4">';
        
        // Header with title and date options
        $output .= '<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">';
        $output .= '<div class="mb-3 mb-md-0">';
        $output .= '<h2 class="h4 mb-1">' . esc_html__('Service Calendar', 'computer-repair-shop') . '</h2>';
        $output .= '<p class="text-muted mb-0">' . esc_html__('View and manage all your service appointments and estimates', 'computer-repair-shop') . '</p>';
        $output .= '</div>';
        
        // Date field buttons (same as backend)
        $output .= '<div class="btn-group btn-group-sm flex-wrap" role="group" aria-label="Calendar date options">';
        $output .= '<button type="button" class="btn btn-outline-primary active date-field-btn" data-field="pickup_date">' . wcrb_get_label('pickup_date', 'first') . '</button>';
        $output .= '<button type="button" class="btn btn-outline-primary date-field-btn" data-field="delivery_date">' . wcrb_get_label('delivery_date', 'first') . '</button>';
        if ($_enable_next_service_d == 'yes') {
            $output .= '<button type="button" class="btn btn-outline-primary date-field-btn" data-field="next_service_date">' . wcrb_get_label('nextservice_date', 'first') . '</button>';
        }
        $output .= '<button type="button" class="btn btn-outline-primary date-field-btn" data-field="post_date">' . esc_html__('Creation', 'computer-repair-shop') . '</button>';
        $output .= '</div>';
        $output .= '</div>'; // End calendar-header
        
        // Filters section - simplified, removed view filter
        $output .= '<div class="calendar-filters bg-light rounded p-3 mb-4">';
        $output .= '<div class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-3">';
        
        $output .= '<div class="filter-group d-flex align-items-center">';
        $output .= '<label for="calendar-filter" class="form-label mb-0 me-2 fw-semibold">' . esc_html__('Filter:', 'computer-repair-shop') . '</label>';
        $output .= '<select id="calendar-filter" class="form-select form-select-sm" style="min-width: 180px;">';
        $output .= '<option value="all">' . esc_html__('All Items', 'computer-repair-shop') . '</option>';
        $output .= '<option value="jobs">' . esc_html__('Jobs Only', 'computer-repair-shop') . '</option>';
        $output .= '<option value="estimates">' . esc_html__('Estimates Only', 'computer-repair-shop') . '</option>';
        if ( $role == 'administrator' || $role == 'store_manager' ) {
            $output .= '<option value="my_assignments">' . esc_html__('My Assignments', 'computer-repair-shop') . '</option>';
        }
        $output .= '</select>';
        $output .= '</div>';
        
        // Hidden date field select (populated by buttons)
        $output .= '<input type="hidden" id="date-field" value="pickup_date">';
        
        $output .= '<div class="filter-group">';
        $output .= '<button id="refresh-calendar" class="btn btn-primary btn-sm">' . esc_html__('Refresh', 'computer-repair-shop') . '</button>';
        $output .= '</div>';
        $output .= '</div>'; // End flex container
        $output .= '</div>'; // End filters
        
        // Calendar container with Bootstrap classes
        $output .= '<div id="frontend-calendar" class="position-relative" style="min-height: 500px;"></div>';
        
        // Loading indicator with Bootstrap spinner
        $output .= '<div id="calendar-loading" class="d-none position-absolute top-50 start-50 translate-middle bg-white rounded-3 p-4 shadow" style="z-index: 1050;">';
        $output .= '<div class="d-flex flex-column align-items-center">';
        $output .= '<div class="spinner-border text-primary mb-2" role="status">';
        $output .= '<span class="visually-hidden">' . esc_html__('Loading...', 'computer-repair-shop') . '</span>';
        $output .= '</div>';
        $output .= '<p class="mb-0 text-muted">' . esc_html__('Loading calendar events...', 'computer-repair-shop') . '</p>';
        $output .= '</div>';
        $output .= '</div>';
        
        // Legend with Bootstrap badges
        $output .= '<div class="mt-4 pt-3 border-top">';
        $output .= '<div class="d-flex flex-wrap gap-2 align-items-center">';
        $output .= '<span class="text-muted me-2">' . esc_html__('Legend:', 'computer-repair-shop') . '</span>';
        $output .= '<span class="badge bg-primary">' . esc_html__('Job', 'computer-repair-shop') . '</span>';
        $output .= '<span class="badge bg-warning text-dark">' . esc_html__('Estimate', 'computer-repair-shop') . '</span>';
        $output .= '<span class="badge bg-success">' . esc_html__('New/Quote', 'computer-repair-shop') . '</span>';
        $output .= '<span class="badge bg-info">' . esc_html__('In Process', 'computer-repair-shop') . '</span>';
        $output .= '<span class="badge bg-danger">' . esc_html__('Cancelled', 'computer-repair-shop') . '</span>';
        $output .= '<span class="badge" style="background-color: #fd7e14; color: white;">' . esc_html__('Ready', 'computer-repair-shop') . '</span>';
        $output .= '<span class="badge" style="background-color: #6f42c1; color: white;">' . esc_html__('Completed', 'computer-repair-shop') . '</span>';
        $output .= '<span class="badge" style="background-color: #e83e8c; color: white;">' . esc_html__('Delivered', 'computer-repair-shop') . '</span>';
        $output .= '</div>';
        $output .= '</div>';
        
        $output .= '</div>'; // End calendar-container
        
        $output .= '<div class="row mt-4 g-3">';
        $output .= '<div class="col-md-6">';
        $output .= '<div class="card border-0 bg-primary text-white shadow-sm h-100">';
        $output .= '<div class="card-body d-flex flex-column justify-content-center">';
        $output .= '<h5 class="card-title h6 mb-2">' . esc_html__('Total Jobs', 'computer-repair-shop') . '</h5>';
        $output .= '<h3 class="card-text display-6 fw-bold mb-0" id="total-jobs">0</h3>';
        $output .= '</div>';
        $output .= '</div>';
        $output .= '</div>';
        
        $output .= '<div class="col-md-6">';
        $output .= '<div class="card border-0 bg-success text-white shadow-sm h-100">';
        $output .= '<div class="card-body d-flex flex-column justify-content-center">';
        $output .= '<h5 class="card-title h6 mb-2">' . esc_html__('Total Estimates', 'computer-repair-shop') . '</h5>';
        $output .= '<h3 class="card-text display-6 fw-bold mb-0" id="total-estimates">0</h3>';
        $output .= '</div>';
        $output .= '</div>';
        $output .= '</div>';
        $output .= '</div>';
        
        $allowedHTML = wc_return_allowed_tags();
        echo wp_kses( $output, $allowedHTML );
    ?>
</main>
<style type="text/css">
    .status-new { background-color: #28a745 !important; }
.status-quote { background-color: #17a2b8 !important; }
.status-inprocess { background-color: #20c997 !important; }
.status-ready { background-color: #fd7e14 !important; }
.status-completed { background-color: #6f42c1 !important; }
.status-delivered { background-color: #e83e8c !important; }
.status-cancelled { background-color: #dc3545 !important; }
</style>