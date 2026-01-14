<?php
defined( 'ABSPATH' ) || exit;

$WCRB_MANAGE_REVIEWS = WCRB_REVIEWS::getInstance();

// Load labels
$wc_device_label    = ( empty( get_option( 'wc_device_label_plural' ) ) ) ? esc_html__( 'Devices', 'computer-repair-shop' ) : get_option( 'wc_device_label_plural' );
$sing_device_label  = ( empty( get_option( 'wc_device_label' ) ) ) ? esc_html__( 'Device', 'computer-repair-shop' ) : get_option( 'wc_device_label' );

$view_type    = 'frontend';
$current_user = wp_get_current_user();
$user_roles   = $current_user->roles;
$role         = $current_user->roles[0] ?? 'guest';

$view_type     = ( $role == 'administrator' || $role == 'store_manager' || $role == 'technician' ) ? 'admin' : 'frontend';

// Check if user can view reviews (admin, store_manager, or customer)
$can_view_reviews = ! empty( array_intersect( array( 'administrator', 'store_manager', 'customer', 'technician' ), $user_roles ) );

if ( ! $can_view_reviews ) {
    ?>
    <main class="dashboard-content container-fluid py-4">
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?php esc_html_e( 'You do not have permission to view reviews.', 'computer-repair-shop' ); ?>
        </div>
    </main>
    <?php
    return;
}

$reviews_data = $WCRB_MANAGE_REVIEWS->list_customer_reviews_bootstrap( $view_type );

// Determine column count based on user role
$is_admin_user = ! empty( array_intersect( array( 'administrator', 'store_manager', 'technician' ), $user_roles ) );
$colspan = $is_admin_user ? 9 : 8;

?>
<!-- Reviews Content -->
<main class="dashboard-content container-fluid py-4">
    
    <!-- Stats Overview -->
    <?php echo wp_kses( $reviews_data['stats'], $allowedHTML ); ?>
    
    <!-- Search and Filters -->
    <?php echo wp_kses( $reviews_data['filters'], $allowedHTML ); ?>
    
    <!-- Reviews Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="bi bi-star me-2"></i>
                <?php echo $is_admin_user ? esc_html__( 'All Reviews', 'computer-repair-shop' ) : esc_html__( 'My Reviews', 'computer-repair-shop' ); ?>
            </h5>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i>
                    <?php echo esc_html__( 'Print', 'computer-repair-shop' ); ?>
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive reviewslistcustomer" id="reviewslistcustomer">
                <div class="aj_msg"></div>
                <table class="table table-hover mb-0 table-striped">
                    <thead class="table-light">
                        <tr>
                            <?php if ( $is_admin_user ) : ?>
                            <th class="ps-4"><?php esc_html_e( 'ID', 'computer-repair-shop' ); ?></th>
                            <?php endif; ?>
                            <th class="ps-4"><?php esc_html_e( 'Job ID', 'computer-repair-shop' ); ?></th>
                            <th><?php esc_html_e( 'Case Number', 'computer-repair-shop' ); ?></th>
                            <th><?php echo esc_html( $sing_device_label ); ?></th>
                            <th><?php esc_html_e( 'Order Date', 'computer-repair-shop' ); ?></th>
                            <?php if ( ! $is_admin_user ) : ?>
                            <th><?php esc_html_e( 'Order Total', 'computer-repair-shop' ); ?></th>
                            <?php endif; ?>
                            <th><?php esc_html_e( 'Rating', 'computer-repair-shop' ); ?></th>
                            <th><?php esc_html_e( 'Review Summary', 'computer-repair-shop' ); ?></th>
                            <?php if ( $is_admin_user ) : ?>
                            <th><?php esc_html_e( 'Customer', 'computer-repair-shop' ); ?></th>
                            <?php endif; ?>
                            <th class="text-end pe-4"><?php esc_html_e( 'Actions', 'computer-repair-shop' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php echo wp_kses( $reviews_data['rows'], $allowedHTML ); ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Pagination -->
        <?php echo wp_kses( $reviews_data['pagination'], $allowedHTML ); ?>
    </div>
    
</main>