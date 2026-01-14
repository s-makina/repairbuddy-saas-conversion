<?php
    defined( 'ABSPATH' ) || exit;

    $WCRB_MANAGE_DEVICES = new WCRB_MANAGE_DEVICES;
    
    // Load device labels
    $wc_device_label         = ( empty( get_option( 'wc_device_label_plural' ) ) ) ? esc_html__( 'Devices', 'computer-repair-shop' ) : get_option( 'wc_device_label_plural' );
    $sing_device_label       = ( empty( get_option( 'wc_device_label' ) ) ) ? esc_html__( 'Device', 'computer-repair-shop' ) : get_option( 'wc_device_label' );
    $wc_device_id_imei_label = ( empty( get_option( 'wc_device_id_imei_label' ) ) ) ? esc_html__( 'ID/IMEI', 'computer-repair-shop' ) : get_option( 'wc_device_id_imei_label' );
    $wc_pin_code_label       = ( empty( get_option( 'wc_pin_code_label' ) ) ) ? esc_html__( 'Pin Code/Password', 'computer-repair-shop' ) : get_option( 'wc_pin_code_label' );
    
    // Get devices data - pass correct view parameter
    $is_admin_view = isset( $_GET['screen'] ) && $_GET['screen'] === 'customer-devices' && current_user_can( 'manage_options' );
    $view_type     = $is_admin_view ? 'admin' : 'frontend';
    $devices_data  = $WCRB_MANAGE_DEVICES->list_customer_devices_bootstrap( $view_type );
    
    // Determine if user is admin/store manager for column count
    $current_user  = wp_get_current_user();
    $user_roles    = (array) $current_user->roles;
    $is_admin_user = ! empty( array_intersect( array( 'administrator', 'store_manager' ), $user_roles ) );
    $colspan       = $is_admin_user ? 8 : 6;
?>
<!-- Devices Content -->
<main class="dashboard-content container-fluid py-4">
    
    <!-- Stats Overview -->
    <?php echo wp_kses( $devices_data['stats'], $allowedHTML ); ?>
    
    <!-- Search and Filters -->
    <?php echo wp_kses( $devices_data['filters'], $allowedHTML ); ?>
    
    <!-- Devices Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="bi bi-device-hdd me-2"></i>
                <?php echo $is_admin_user ? sprintf( esc_html__( 'All %s', 'computer-repair-shop' ), $wc_device_label ) : sprintf( esc_html__( 'My %s', 'computer-repair-shop' ), $wc_device_label ); ?>
            </h5>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addDeviceModal">
                    <i class="bi bi-plus-circle me-1"></i>
                    <?php echo sprintf( esc_html__( 'Add %s', 'computer-repair-shop' ), $sing_device_label ); ?>
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i>
                    <?php echo esc_html__( 'Print', 'computer-repair-shop' ); ?>
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive deviceslistcustomer" id="deviceslistcustomer">
                <div class="aj_msg"></div>
                <table class="table table-hover mb-0 table-striped">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4"><?php esc_html_e( 'ID', 'computer-repair-shop' ); ?></th>
                            <th><?php echo esc_html( $sing_device_label ); ?></th>
                            <th><?php echo esc_html( $wc_device_id_imei_label ); ?></th>
                            <th><?php echo esc_html( $wc_pin_code_label ); ?></th>
                            <th><?php echo esc_html( sprintf( esc_html__( '%s Details', 'computer-repair-shop' ), $sing_device_label ) ); ?></th>
                            <?php if ( $is_admin_user ) : ?>
                            <th><?php esc_html_e( 'Customer', 'computer-repair-shop' ); ?></th>
                            <?php endif; ?>
                            <th class="text-end pe-4"><?php esc_html_e( 'Actions', 'computer-repair-shop' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php echo wp_kses( $devices_data['rows'], $allowedHTML ); ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Pagination -->
        <?php echo wp_kses( $devices_data['pagination'], $allowedHTML ); ?>
    </div>
    
</main>
<?php echo wp_kses( $WCRB_MANAGE_DEVICES->return_add_device_form_boot( 'frontend' ), $allowedHTML ); ?>