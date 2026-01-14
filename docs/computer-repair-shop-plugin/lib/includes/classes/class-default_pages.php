<?php
/**
 * The file contains the functions related to Shortcode Pages
 *
 * Help setup pages to they can be used in notifications and other items
 *
 * @package computer-repair-shop
 * @version 3.7947
 */

defined( 'ABSPATH' ) || exit;

class WCRB_DEFAULT_PAGES {
    
    function __construct() {
        add_action( 'wc_rb_settings_tab_menu_item', array( $this, 'add_pages_tab_in_settings_menu' ), 10, 2 );
        add_action( 'wc_rb_settings_tab_body', array( $this, 'add_pages_tab_options_body' ), 10, 2 );
		add_action( 'wp_ajax_wc_post_default_pages_indexes', array( $this, 'wc_post_default_pages_indexes' ), 10, 2 );
    }

    function add_pages_tab_in_settings_menu() {
        $active = '';

        $menu_output = '<li class="tabs-title' . esc_attr( $active ) . '" role="presentation">';
        $menu_output .= '<a href="#wc_rb_page_settings" role="tab" aria-controls="wc_rb_page_settings" aria-selected="true" id="wc_rb_page_settings-label">';
        $menu_output .= '<h2>' . esc_html__( 'Pages Setup', 'computer-repair-shop' ) . '</h2>';
        $menu_output .=	'</a>';
        $menu_output .= '</li>';

        echo wp_kses_post( $menu_output );
    }
	
	function wc_post_default_pages_indexes() { 
		global $wpdb;

		if ( ! isset( $_POST['wcrb_nonce_pages_field'] ) || ! wp_verify_nonce( $_POST['wcrb_nonce_pages_field'], 'wcrb_nonce_pages' ) ) {
			$message = esc_html__( "Something is wrong with your submission!", "computer-repair-shop" );
		} else {

			$form_type 			          = ( isset( $_POST['form_type'] ) ) ? sanitize_text_field( $_POST['form_type'] ) : '';
			$wc_rb_status_check_page_id   = ( isset( $_POST['wc_rb_status_check_page_id'] ) ) ? sanitize_text_field( $_POST['wc_rb_status_check_page_id'] ) : '';
			$wc_rb_device_booking_page_id = ( isset( $_POST['wc_rb_device_booking_page_id'] ) ) ? sanitize_text_field( $_POST['wc_rb_device_booking_page_id'] ) : '';
			$wc_rb_my_account_page_id 	  = ( isset( $_POST['wc_rb_my_account_page_id'] ) ) ? sanitize_text_field( $_POST['wc_rb_my_account_page_id'] ) : '';
			$wc_rb_customer_login_page 	  = ( isset( $_POST['wc_rb_customer_login_page'] ) ) ? sanitize_text_field( $_POST['wc_rb_customer_login_page'] ) : '';
			$wc_rb_list_services_page_id  = ( isset( $_POST['wc_rb_list_services_page_id'] ) ) ? sanitize_text_field( $_POST['wc_rb_list_services_page_id'] ) : '';
			$wc_rb_list_parts_page_id 	  = ( isset( $_POST['wc_rb_list_parts_page_id'] ) ) ? sanitize_text_field( $_POST['wc_rb_list_parts_page_id'] ) : '';
			$wc_rb_turn_registration_on   = ( isset( $_POST['wc_rb_turn_registration_on'] ) ) ? sanitize_text_field( $_POST['wc_rb_turn_registration_on'] ) : '';
			$wc_rb_get_feedback_page_id   = ( isset( $_POST['wc_rb_get_feedback_page_id'] ) ) ? sanitize_text_field( $_POST['wc_rb_get_feedback_page_id'] ) : '';

			if ( $form_type == 'submit_default_pages_WP' ) {

				update_option( 'wc_rb_status_check_page_id', $wc_rb_status_check_page_id );
				update_option( 'wc_rb_device_booking_page_id', $wc_rb_device_booking_page_id );
				update_option( 'wc_rb_my_account_page_id', $wc_rb_my_account_page_id );
				update_option( 'wc_rb_customer_login_page', $wc_rb_customer_login_page );
				update_option( 'wc_rb_list_services_page_id', $wc_rb_list_services_page_id );
				update_option( 'wc_rb_list_parts_page_id', $wc_rb_list_parts_page_id );
				update_option( 'wc_rb_turn_registration_on', $wc_rb_turn_registration_on );
				update_option( 'wc_rb_get_feedback_page_id', $wc_rb_get_feedback_page_id );

				update_option( 'wc_rb_setup_pages_once', 'YES' );

				$message = esc_html__( 'Settings updated!', 'computer-repair-shop' );
			} else {
				$message = esc_html__( 'Invalid Form', 'computer-repair-shop' );	
			}
		}

		$values['message'] = $message;
		$values['success'] = "YES";

		wp_send_json( $values );
		wp_die();
	}

	function add_pages_tab_options_body() {
        global $wpdb;

        $active = '';

		$setting_body = '<div class="tabs-panel team-wrap' . esc_attr( $active ) . '" 
        id="wc_rb_page_settings" 
        role="tabpanel" 
        aria-hidden="true" 
        aria-labelledby="wc_rb_page_settings-label">';

		$setting_body .= '<div class="wrap"><div class="form-message"></div>';
		$setting_body .= '<h3>' . esc_html__( 'You may change pages which have related shortcodes.', 'computer-repair-shop' ) . '</h3>';

		$setting_body .= '<form data-async data-abide class="needs-validation" name="submit_default_pages_WP" novalidate method="post">';
		$setting_body .= '<table cellpadding="5" cellspacing="5" class="form-table border">';
		$setting_body .= '<tbody>';

		//My Account Option
		$setting_body .= '<tr>
							<th scope="row">
								<label for="wc_rb_my_account_page_id"><strong>
									' . esc_html__( 'Select Dashboard Page', 'computer-repair-shop' ) . '
								</strong></label>
							</th>';
		$setting_body .= '<td>';

		$selected_page = get_option( 'wc_rb_my_account_page_id' );
		$default_value = esc_html__( 'Select my account page', 'computer-repair-shop' );

		$setting_body .= wp_dropdown_pages( array(
			'selected'              => esc_attr( $selected_page ),
			'echo'                  => 0,
			'name'                  => 'wc_rb_my_account_page_id',
			'class'                 => 'form-control',
			'show_option_no_change'	=> esc_attr( $default_value ),
			'value_field'           => 'ID',
		) );
		
		if ( ! empty( $selected_page ) && $selected_page > 0 ) {
			$url = esc_url( get_the_permalink( $selected_page ) );
			$setting_body .= sprintf(
				'<a href="%s" target="_blank"><span class="dashicons dashicons-external pagelink"></span></a>',
				$url
			);
		}
		$setting_body .= '<label>';
		$setting_body .= esc_html__( 'A page for customers, technicians, store managers and administrators to perform various tasks page with shortcode ', 'computer-repair-shop' ) . '<strong>[wc_cr_my_account]</strong> ';
		$setting_body .= '</label></td></tr>';

		$setting_body .= '<tr>
							<th scope="row">
								<label for="wc_rb_status_check_page_id">
									' . esc_html__( 'Select Status Check Page', 'computer-repair-shop' ) . '
								</label>
							</th>';
		$setting_body .= '<td>';

		$selected_page = get_option( 'wc_rb_status_check_page_id' );
		$default_value = esc_html__( 'Select status page', 'computer-repair-shop' );

		$setting_body .= wp_dropdown_pages( array(
			'selected'              => esc_attr( $selected_page ),
			'echo'                  => 0,
			'name'                  => 'wc_rb_status_check_page_id',
			'class'                 => 'form-control',
			'show_option_no_change'	=> esc_attr( $default_value ),
			'value_field'           => 'ID',
		) );

		if ( ! empty( $selected_page ) && $selected_page > 0 ) {
			$url = esc_url( get_the_permalink( $selected_page ) );
			$setting_body .= sprintf(
				'<a href="%s" target="_blank"><span class="dashicons dashicons-external pagelink"></span></a>',
				$url
			);
		}
		$setting_body .= '<label>';
		$setting_body .= esc_html__( 'A page that have shortcode ', 'computer-repair-shop' ) . '<strong>[wc_order_status_form]</strong> ';
		$setting_body .= esc_html__( 'If set this would be used to send link to customers for status check in email and other notification mediums.', 'computer-repair-shop' );
		$setting_body .= '</label>';
		$setting_body .= '</td></tr>';

		$setting_body .= '<tr>
							<th scope="row">
								<label for="wc_rb_get_feedback_page_id">
									' . esc_html__( 'Get feedback on job page', 'computer-repair-shop' ) . '
								</label>
							</th>';
		$setting_body .= '<td>';

		$selected_page = get_option( 'wc_rb_get_feedback_page_id' );
		$default_value = esc_html__( 'Select job review page', 'computer-repair-shop' );

		$setting_body .= wp_dropdown_pages( array(
			'selected'              => esc_attr( $selected_page ),
			'echo'                  => 0,
			'name'                  => 'wc_rb_get_feedback_page_id',
			'class'                 => 'form-control',
			'show_option_no_change'	=> esc_attr( $default_value ),
			'value_field'           => 'ID',
		) );
		
		if ( ! empty( $selected_page ) && $selected_page > 0 ) {
			$url = esc_url( get_the_permalink( $selected_page ) );
			$setting_body .= sprintf(
				'<a href="%s" target="_blank"><span class="dashicons dashicons-external pagelink"></span></a>',
				$url
			);
		}
		$setting_body .= '<label>';
		$setting_body .= esc_html__( 'A page that have shortcode ', 'computer-repair-shop' ) . '<strong>[wc_get_order_feedback]</strong> ';
		$setting_body .= esc_html__( 'If set this would be used to send link to customers so they can leave feedback on jobs.', 'computer-repair-shop' );
		$setting_body .= '</label></td></tr>';

		//Booking Option
		$setting_body .= '<tr>
							<th scope="row">
								<label for="wc_rb_device_booking_page_id">
									' . esc_html__( 'Select Device Booking Page', 'computer-repair-shop' ) . '
								</label>
							</th>';
		$setting_body .= '<td>';

		$selected_page = get_option( 'wc_rb_device_booking_page_id' );
		$default_value = esc_html__( 'Select booking page', 'computer-repair-shop' );

		$setting_body .= wp_dropdown_pages( array(
			'selected'              => esc_attr( $selected_page ),
			'echo'                  => 0,
			'name'                  => 'wc_rb_device_booking_page_id',
			'class'                 => 'form-control',
			'show_option_no_change'	=> esc_attr( $default_value ),
			'value_field'           => 'ID',
		) );
		
		if ( ! empty( $selected_page ) && $selected_page > 0 ) {
			$url = esc_url( get_the_permalink( $selected_page ) );
			$setting_body .= sprintf(
				'<a href="%s" target="_blank"><span class="dashicons dashicons-external pagelink"></span></a>',
				$url
			);
		}
		$setting_body .= '<label>';
		$setting_body .= esc_html__( 'A page for booking process with shortcode ', 'computer-repair-shop' ) . '<strong>[wc_book_my_service]</strong> ';
		$setting_body .= '</label></td></tr>';


		//Service Page Options
		$setting_body .= '<tr>
							<th scope="row">
								<label for="wc_rb_list_services_page_id">
									' . esc_html__( 'Select Services Page', 'computer-repair-shop' ) . '
								</label>
							</th>';
		$setting_body .= '<td>';

		$selected_page = get_option( 'wc_rb_list_services_page_id' );
		$default_value = esc_html__( 'Select services page', 'computer-repair-shop' );

		$setting_body .= wp_dropdown_pages( array(
			'selected'              => esc_attr( $selected_page ),
			'echo'                  => 0,
			'name'                  => 'wc_rb_list_services_page_id',
			'class'                 => 'form-control',
			'show_option_no_change'	=> esc_attr( $default_value ),
			'value_field'           => 'ID',
		) );
		
		if ( ! empty( $selected_page ) && $selected_page > 0 ) {
			$url = esc_url( get_the_permalink( $selected_page ) );
			$setting_body .= sprintf(
				'<a href="%s" target="_blank"><span class="dashicons dashicons-external pagelink"></span></a>',
				$url
			);
		}
		$setting_body .= '<label>';
		$setting_body .= esc_html__( 'A page lists services should have shortcode ', 'computer-repair-shop' ) . '<strong>[wc_list_services]</strong> ';
		$setting_body .= '</label></td></tr>';


		//Products Page Options
		$setting_body .= '<tr>
							<th scope="row">
								<label for="wc_rb_list_parts_page_id">
									' . esc_html__( 'Select Parts Page', 'computer-repair-shop' ) . '
								</label>
							</th>';
		$setting_body .= '<td>';

		$selected_page = get_option( 'wc_rb_list_parts_page_id' );
		$default_value = esc_html__( 'Select parts page', 'computer-repair-shop' );

		$setting_body .= wp_dropdown_pages( array(
			'selected'              => esc_attr( $selected_page ),
			'echo'                  => 0,
			'name'                  => 'wc_rb_list_parts_page_id',
			'class'                 => 'form-control',
			'show_option_no_change'	=> esc_attr( $default_value ),
			'value_field'           => 'ID',
		) );
		
		if ( ! empty( $selected_page ) && $selected_page > 0 ) {
			$url = esc_url( get_the_permalink( $selected_page ) );
			$setting_body .= sprintf(
				'<a href="%s" target="_blank"><span class="dashicons dashicons-external pagelink"></span></a>',
				$url
			);
		}
		$setting_body .= '<label>';
		$setting_body .= esc_html__( 'A page lists parts should have shortcode ', 'computer-repair-shop' ) . '<strong>[wc_list_products]</strong> ';
		$setting_body .= esc_html__( 'If you are using WooCommerce products as parts then its not needed.', 'computer-repair-shop' );
		$setting_body .= '</label></td></tr>';

		$setting_body .= '</tbody>';
		$setting_body .= '</table>';

		$setting_body .= '<h3>' . esc_html__( 'Redirect user after login.', 'computer-repair-shop' ) . '</h3>';

		$setting_body .= '<table cellpadding="5" cellspacing="5" class="form-table border">';
		$setting_body .= '<tbody>';

		$setting_body .= '<tr>
							<th scope="row">
								<label for="wc_rb_customer_login_page">
									' . esc_html__( 'Select Page for customer to redirect after login', 'computer-repair-shop' ) . '
								</label>
							</th>';
		$setting_body .= '<td>';

		$selected_page = get_option( 'wc_rb_customer_login_page' );
		$default_value = esc_html__( 'Select customer page after login', 'computer-repair-shop' );

		$setting_body .= wp_dropdown_pages( array(
			'selected'              => esc_attr( $selected_page ),
			'echo'                  => 0,
			'name'                  => 'wc_rb_customer_login_page',
			'class'                 => 'form-control',
			'show_option_no_change'	=> esc_attr( $default_value ),
			'value_field'           => 'ID',
		) );
		
		if ( ! empty( $selected_page ) && $selected_page > 0 ) {
			$url = esc_url( get_the_permalink( $selected_page ) );
			$setting_body .= sprintf(
				'<a href="%s" target="_blank"><span class="dashicons dashicons-external pagelink"></span></a>',
				$url
			) . $selected_page;
		}
		$setting_body .= '<label>';
		$setting_body .= esc_html__( 'A page that have shortcode ', 'computer-repair-shop' ) . '<strong>[wc_cr_my_account]</strong> ';
		$setting_body .= esc_html__( 'If you want to use WooCommerce My Account page please select that.', 'computer-repair-shop' );
		$setting_body .= '</label></td></tr>';

		//Products Page Options
		$setting_body .= '<tr>
							<th scope="row">
								<label for="wc_rb_turn_registration_on">
									' . esc_html__( 'Turn on Customer Registration on My Account Page', 'computer-repair-shop' ) . '
								</label>
							</th>';
		$setting_body .= '<td>';

		$wc_rb_turn_registration_on = get_option( 'wc_rb_turn_registration_on' );
		$wc_rb_turn_registration_on = ( $wc_rb_turn_registration_on == 'on' ) ? 'checked="checked"' : '';
		
		$setting_body .= '<input type="checkbox" ' . esc_html( $wc_rb_turn_registration_on ) . ' name="wc_rb_turn_registration_on" id="wc_rb_turn_registration_on" />';
		
		$setting_body .= '<label for="wc_rb_turn_registration_on">';
		$setting_body .= esc_html__( 'If checked customer registration form will appear in my account page which have shortcode ', 'computer-repair-shop' ) . '<strong>[wc_cr_my_account]</strong> ';
		$setting_body .= '</label></td></tr>';

		$setting_body .= '<!-- Login Form Ends /-->
				<input name="form_type" type="hidden" 
								value="submit_default_pages_WP" />';

		$setting_body .= '<tr><td colspan="2"><button class="button button-primary" type="submit">' . esc_html__( 'Submit', 'computer-repair-shop' ) . '</button></td></tr>';

		$setting_body .= '</tbody>';
		$setting_body .= '</table>';
		$setting_body .= wp_nonce_field( 'wcrb_nonce_pages', 'wcrb_nonce_pages_field', true, false );
		$setting_body .= '</form>';
		$setting_body .= '</div>';
       
        $setting_body .= '</div><!--Tabs Panel /-->';

		$allowedHTML = ( function_exists( 'wc_return_allowed_tags' ) ) ? wc_return_allowed_tags() : '';
		echo wp_kses( $setting_body, $allowedHTML );
	}
}