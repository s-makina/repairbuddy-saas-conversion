<?php
    defined( 'ABSPATH' ) || exit;

    $WCRB_DASHBOARD_JOBS = WCRB_DASHBOARD_JOBS::getInstance();
    $WCRB_DASHBOARD      = WCRB_MYACCOUNT_DASHBOARD::getInstance();

    $current_view = isset( $_GET['screen'] ) && $_GET['screen'] === 'jobs_card' ? 'card' : 'table';
    $_page         = isset( $_GET['screen'] ) && $_GET['screen'] === 'jobs_card' ? 'jobs_card' : 'jobs';

    // Get the appropriate view
    if ( $current_view === 'card' ) {
        $jobs_list = $WCRB_DASHBOARD_JOBS->list_jobs_card_view();
        $view_label = esc_html__( 'Table View', 'computer-repair-shop' );
        $view_url  = esc_url( add_query_arg( 'screen', 'jobs', get_the_permalink( $_mainpage ) ) );
    } else {
        $jobs_list = $WCRB_DASHBOARD_JOBS->list_jobs();
        $view_label = esc_html__( 'Card View', 'computer-repair-shop' );
        $view_url  = esc_url( add_query_arg( 'screen', 'jobs_card', get_the_permalink( $_mainpage ) ) );
    }

    $_job_status = $WCRB_DASHBOARD_JOBS->return_jobs_count_by_status_array();
?>
<!-- Jobs Content -->
<main class="dashboard-content container-fluid py-4">
    <!-- Stats Overview -->
    <div class="row g-3 mb-4">
        <?php 
            if ( ! empty( $_job_status ) ) : foreach( $_job_status as $_jobsstatus ) : 
                $_color = $WCRB_DASHBOARD->wc_get_status_bg_color( $_jobsstatus['status_slug'] );

                if ( $_jobsstatus['jobs_count'] > 0 ) :
                    $_pageurl = $WCRB_DASHBOARD->extend_current_url( $_mainpage, array( 'job_status' => $_jobsstatus['status_slug'] )  );
        ?>
        <div class="col">
            <a href="<?php echo esc_url( $_pageurl ); ?>">
            <div class="card stats-card <?php echo esc_attr( $_color ); ?> text-white">
                <div class="card-body text-center p-3">
                    <h6 class="card-title text-white-50 mb-1"><?php echo esc_html( $_jobsstatus['status_name'] ); ?></h6>
                    <h4 class="mb-0"><?php echo esc_html( $_jobsstatus['jobs_count'] ); ?></h4>
                </div>
            </div>
            </a>
        </div>
        <?php endif; endforeach; endif; ?>

    </div>

    <!-- Search and Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" action="">
            <input type="hidden" name="screen" value="<?php echo esc_attr( $_page ); ?>" />
            <div class="row g-3">
                <div class="col-md-2">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0" name="searchinput" id="searchInput" 
                        value="<?php echo ( isset( $_GET['searchinput'] ) ) ? esc_attr( sanitize_text_field( $_GET['searchinput'] ) ) : ''; ?>"
                        placeholder="<?php echo esc_html__( 'Search by Job ID, Case Number, Title, Notes, Dates', 'computer-repair-shop' ); ?>...">
                    </div>
                </div>
                <!-- Job Status Starts /-->
                <div class="col">
                    <?php
                    $current_status = '';
                    if( isset( $_GET['job_status'] ) ) {
                        $current_status = sanitize_text_field( $_GET['job_status'] ); // Check if option has been selected
                    } ?>
                    <select class="form-select" name="job_status" id="job_status">
                        <option value="all" <?php selected( 'all', $current_status); ?>><?php echo esc_html__( 'Job Status (All)', 'computer-repair-shop' ); ?></option>
                    <?php
                        $optionsGenerated = wc_generate_status_options( $current_status );
                        echo wp_kses( $optionsGenerated, $allowedHTML );
                    ?>
                    </select>
                </div>
                <!-- Job Status Ends /-->
                
                <div class="col">
                    <?php
                        //Priority Processing
                        $current_priority = '';
                        if( isset( $_GET['wc_job_priority'] ) ) {
                            $current_priority = sanitize_text_field( $_GET['wc_job_priority'] ); // Check if option has been selected
                        } 
                        echo wp_kses( wcrb_job_priority_options( 'select_no_label', '', $current_priority ), $allowedHTML );
                    ?>
                </div>

                <!-- Start select store /-->
                <?php if ( function_exists( 'wc_store_select_options' ) ) : ?>
                    <div class="col">                    
                        <select name="wc_store" class="form-select" id="wc_store">
                            <option value="all"><?php echo esc_html__( 'Store (All)', 'computer-repair-shop' ); ?></option>
                            <?php
                                $selected_store = '';
                                if ( isset( $_GET['wc_store'] ) ) {
                                    $selected_store = sanitize_text_field( $_GET['wc_store'] );
                                }
                                echo wp_kses( wc_store_select_options( $selected_store ), $allowedHTML );
                            ?>
                        </select>
                    </div>
				<?php endif; ?>
                <!-- End Select store /-->

                <!-- Select device starts /-->
                <div class="col">
                    <?php    
                    if ( wcrb_use_woo_as_devices() == 'YES' ) {
                        $wc_device_label = esc_html( $WCRB_DASHBOARD->_device_label );

                        $theSearchClass = ( ! empty( get_option('wcrb_special_PR_Search_class') ) ) ? get_option('wcrb_special_PR_Search_class') : 'bc-product-search';

                        $contentSel = '<select name="device_post_id" id="rep_devices" data-display_stock="true" data-exclude_type="variable" data-security="' . wp_create_nonce( 'search-products' ) . '" 
                        class="' . esc_attr( $theSearchClass ) . ' form-select">
                            <option value="">' . $wc_device_label . ' ...'. '</option>
                        </select>';
                        echo wp_kses( $contentSel, $allowedHTML );
                    } else { ?>
                    <select id="rep_devices" name="device_post_id" class="form-select">
                    <?php
                        $device_post_id = ( isset( $_GET["device_post_id"] ) ) ? sanitize_text_field( $_GET["device_post_id"] ): "";
                        $optionsGenerated = wc_generate_device_options( $device_post_id );
                        echo wp_kses( $optionsGenerated, $allowedHTML );
                    ?>	
                    </select>
                    <?php } ?>
                </div>
                <!-- Select device Ends /-->

                <!-- Starts payment status /-->
                <div class="col">
                    <?php
                    //Payment Status Processing
                    $current_payment_status = '';
                    if( isset( $_GET['wc_payment_status'] ) ) {
                        $current_payment_status = sanitize_text_field( $_GET['wc_payment_status'] ); // Check if option has been selected
                    } ?>
                    <select name="wc_payment_status" id="wc_payment_status" class="form-select">
                        <option value="all" <?php selected( 'all', $current_payment_status); ?>><?php echo esc_html__( 'Payment Status (All)', 'computer-repair-shop' ); ?></option>
                        <?php echo wp_kses( $GLOBALS['PAYMENT_STATUS_OBJ']->wc_generate_payment_status_options( $current_payment_status ), $allowedHTML ); ?>
                    </select>
                </div>
                <!-- Ends payment status /-->
                
                <?php if ( $role == 'administrator' || $role == 'store_manager' || $role == 'technician' ) : ?>
                <div class="col">
                    <?php
                        //By Customer
                        $current_job_customer = '';
                        if( isset( $_GET['job_customer'] ) ) {
                            $current_job_customer = sanitize_text_field( $_GET['job_customer'] );
                        } 
                        
                        $optionsGenerated = wcrb_return_customer_select_options( $current_job_customer, 'job_customer', 'updatenone' );
                        echo wp_kses( $optionsGenerated, $allowedHTML );
                    ?>
                </div>
                <?php endif; ?>
                
                <?php if ( $role == 'administrator' || $role == 'store_manager' ) : ?>
                <div class="col">
                    <?php
                        //By Technician
                        $current_job_technician = '';
                        if( isset( $_GET['job_technician'] ) ) {
                            $current_job_technician = sanitize_text_field( $_GET['job_technician'] ); // Check if option has been selected
                        } 

                        $tech_options = wcrb_dropdown_users_multiple_roles( array(
                                                                'show_option_all' => esc_html__('Technician (All)', 'computer-repair-shop'),
                                                                'name' => 'job_technician',
                                                                'role__in' => array( 'technician', 'store_manager', 'administrator' ),
                                                                'selected' => $current_job_technician,
                                                                'show_roles' => false) 
                                                            );
                        echo wp_kses( $tech_options, $allowedHTML );
                    ?>
                </div>
                <?php endif; ?>

                <div class="col">
                    <div class="d-flex gap-2">
                        <a class="btn btn-outline-secondary" href="<?php echo esc_url( add_query_arg( 'screen', $_page, get_the_permalink( $_mainpage ) ) ); ?>" id="clearFilters">
                            <i class="bi bi-arrow-clockwise"></i>
                        </a>
                        <button class="btn btn-primary" id="applyFilters">
                            <i class="bi bi-funnel"></i> <?php echo esc_html__( 'Filter', 'computer-repair-shop' ); ?>
                        </button>
                    </div>
                </div>
            </div>
            </form>
        </div>
    </div>

    <!-- Jobs Table/Card -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><?php echo esc_html__( 'Jobs', 'computer-repair-shop' ); ?></h5>
            <div class="d-flex gap-2">
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="bi bi-download me-1"></i> <?php echo esc_html__( 'Export', 'computer-repair-shop' ); ?>
                    </button>
                    <?php echo wp_kses( $WCRB_DASHBOARD_JOBS->get_export_buttons(), $allowedHTML ); ?>
                </div>

                <?php if ( wc_rs_license_state() ) { ?>
                <a href="<?php echo esc_url( $view_url ); ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-grid-3x3-gap me-1"></i> <?php echo esc_html( $view_label ); ?>
                </a>
                <?php } else { ?>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php echo esc_html__( 'Card View', 'computer-repair-shop' ); ?>
                    </button>
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
                </div>
                <?php } ?>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if ( $current_view === 'card' ) : ?>
            <!-- Card View -->
            <div class="cardView" id="cardView">
                <?php echo wp_kses( $jobs_list['rows'], $allowedHTML ); ?>
            </div>
            <?php else : ?>
            <!-- Table View -->
            <div class="table-responsive" id="jobsTable_list">
                <div class="aj_msg"></div>
                <table class="table table-hover mb-0 table-striped">
                    <thead class="table-light">
                        <tr>
                            <th><?php esc_html_e( "ID", "computer-repair-shop" ); ?></th>
                            <th><?php echo esc_html__( wcrb_get_label( 'casenumber', 'first' ) ); ?>/<?php echo esc_html__( 'Tech', 'computer-repair-shop' ); ?></th>
                            <th><?php echo esc_html__( 'Customer', 'computer-repair-shop' ); ?></th>
                            <th><?php echo esc_html( $WCRB_DASHBOARD->_device_label_plural ); ?></th>
                            <th><?php echo esc_html__( 'Dates', 'computer-repair-shop' ); ?></th>
                            <th><?php echo esc_html__( 'Total', 'computer-repair-shop' ); ?></th>
                            <th><?php echo esc_html__( 'Balance', 'computer-repair-shop' ); ?></th>
                            <th><?php echo esc_html__( 'Payment', 'computer-repair-shop' ); ?></th>
                            <th><?php echo esc_html__( 'Status', 'computer-repair-shop' ); ?></th>
                            <th><?php echo esc_html__( 'Priority', 'computer-repair-shop' ); ?></th>
                            <th class="text-end pe-4"><?php echo esc_html__( 'Actions', 'computer-repair-shop' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php echo wp_kses( $jobs_list['rows'], $allowedHTML ); ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <!-- Pagination -->
        <?php echo wp_kses( $jobs_list['pagination'], $allowedHTML ); ?>
    </div>
</main>

<?php 
    $WCRB_DUPLICATE_JOB = WCRB_DUPLICATE_JOB::getInstance();
    $WCRB_DUPLICATE_JOB->wcrb_duplicate_job_reveal_front_box( $_mainpage ); ?>

<!-- Bootstrap Modal for Post Entry -->
<div class="modal fade" id="openTakePaymentModal" tabindex="-1" aria-labelledby="openTakePaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="openTakePaymentModalLabel">
                    <?php echo esc_html__( 'Make a payment', 'computer-repair-shop' ); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form class="" method="post" name="wcrb_jl_form_submit_payment" data-success-class=".set_addpayment_joblist_message">
                    <div id="replacementpart_joblist_formfields">
                        <!-- Replacementpart starts -->
                         Champ
                        <!-- Replacementpart Ends -->
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>