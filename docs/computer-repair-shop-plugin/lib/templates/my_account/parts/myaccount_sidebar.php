<?php
    defined( 'ABSPATH' ) || exit;
?>
<!-- Sidebar -->
<nav class="sidebar bg-dark text-white" id="sidebar">
    <div class="bg-grey sidebar-header p-2 border-bottom border-secondary">
        <?php
            $logoUrl = wc_rb_return_logo_url_with_img( 'shoplogo' );
            $brandlink = ( ! defined( 'REPAIRBUDDY_LOGO_URL' ) ) ? esc_url( WC_COMPUTER_REPAIR_DIR_URL . '/assets/admin/images/repair-buddy-logo.png' ) : REPAIRBUDDY_LOGO_URL;
            $content = ( ! empty( $logoUrl ) ) ? $logoUrl : '<img src="' . esc_url( $brandlink ) . '" alt="RepairBuddy CRM Logo" />';

            echo wp_kses_post( $content );
        ?>
    </div>

    <div class="sidebar-nav p-3">
        <?php
            $WCRB_MYACCOUNT_DASHBOARD = WCRB_MYACCOUNT_DASHBOARD::getInstance();
            $navigation_html          = $WCRB_MYACCOUNT_DASHBOARD->generate_navigation();

            echo wp_kses( $navigation_html, $allowedHTML );
        ?>
    </div>
</nav>