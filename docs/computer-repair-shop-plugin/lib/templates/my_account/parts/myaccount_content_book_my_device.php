<?php
    defined( 'ABSPATH' ) || exit;
?>
<main class="dashboard-content container-fluid py-4">
    <?php
        add_action( 'wp_enqueue_scripts', 'wc_comp_rep_register_foundation' );
        wp_enqueue_script( 'ajax_script', WC_COMPUTER_REPAIR_DIR_URL . '/assets/js/ajax_scripts.js', array( 'jquery' ), WC_CR_SHOP_VERSION, true );
		    wp_localize_script( 'ajax_script', 'ajax_obj', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

        wp_enqueue_script("foundation-js");
        //wp_enqueue_script("wc-cr-js");
        //wp_enqueue_script("select2");
        wp_enqueue_style( 'foundation-css');
        wp_enqueue_style( 'plugin-styles-wc' );
        wp_enqueue_script("intl-tel-input");
        wp_enqueue_style("intl-tel-input");

        add_action( 'wp_print_footer_scripts', 'wcrb_intl_tel_input_script' );

        $wc_device_label = ( empty( get_option( 'wc_device_label' ) ) ) ? esc_html__( 'Device', 'computer-repair-shop' ) : get_option( 'wc_device_label' );

		$output = '<div class="card shadow-sm border-0 mb-4" id="booking_page">';
		$output .= "<h2>" . esc_html__( 'You can book a new', 'computer-repair-shop' ) . ' ' . $wc_device_label . "</h2>";
		
		$wc_account_booking_form = get_option( 'wc_account_booking_form' );
        
        if( $wc_account_booking_form == 'with_type' ) {
          $output .= WCRB_TYPE_GROUPED_SERVICE();
        } elseif ( $wc_account_booking_form == 'warranty_booking' ) {
          $output .= wc_book_my_warranty();
        } else {
          $output .= wc_book_my_service();
        }

		$output .= '</div>';

		$allowedHTML = wc_return_allowed_tags();
		echo wp_kses( $output, $allowedHTML );
    ?>
</main>