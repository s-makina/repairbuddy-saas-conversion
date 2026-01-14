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

class WCRB_MANAGE_DEVICES {

	function __construct() {
        add_action( 'wc_rb_settings_tab_menu_item', array( $this, 'add_devices_tab_in_settings_menu' ), 10, 2 );
        add_action( 'wc_rb_settings_tab_body', array( $this, 'add_devices_tab_in_settings_body' ), 10, 2 );
		add_action( 'wp_ajax_wc_rb_update_device_settings', array( $this, 'wc_rb_update_device_settings' ) );
		add_action( 'wp_ajax_wc_add_device_for_manufacture', array( $this, 'wc_add_device_for_manufacture' ) );

		add_action( 'wp_ajax_wc_add_device_row', array( $this, 'wc_add_device_row' ) );
		add_action( 'wp_ajax_add_device_based_parts_dropdown', array( $this, 'add_device_based_parts_dropdown' ) );

		add_action('wp_ajax_wc_update_device_options', array($this, 'ajax_update_device_options'));
    }

	function add_devices_tab_in_settings_menu() {
        $active = '';

        $menu_output = '<li class="tabs-title' . esc_attr($active) . '" role="presentation">';
        $menu_output .= '<a href="#wc_rb_manage_devices" role="tab" aria-controls="wc_rb_manage_devices" aria-selected="true" id="wc_rb_manage_devices-label">';
        $menu_output .= '<h2>' . esc_html__( 'Devices & Brands', 'computer-repair-shop' ) . '</h2>';
        $menu_output .=	'</a>';
        $menu_output .= '</li>';

        echo wp_kses_post( $menu_output );
    }
	
	function add_devices_tab_in_settings_body() {
        global $wpdb;

        $active = '';

		$wc_note_label 				  = ( empty( get_option( 'wc_note_label' ) ) ) ? esc_html__( 'Note', 'computer-repair-shop' ) : get_option( 'wc_note_label' );
		$wc_pin_code_label			  = ( empty( get_option( 'wc_pin_code_label' ) ) ) ? esc_html__( 'Pin Code/Password', 'computer-repair-shop' ) : get_option( 'wc_pin_code_label' );
		$wc_device_label 			  = ( empty( get_option( 'wc_device_label' ) ) ) ? esc_html__( 'Device', 'computer-repair-shop' ) : get_option( 'wc_device_label' );
		$wc_device_brand_label        = ( empty( get_option( 'wc_device_brand_label' ) ) ) ? esc_html__( 'Device Brand', 'computer-repair-shop' ) : get_option( 'wc_device_brand_label' );
		$wc_device_label_plural       = ( empty( get_option( 'wc_device_label_plural' ) ) ) ? esc_html__( 'Devices', 'computer-repair-shop' ) : get_option( 'wc_device_label_plural' );

		$wc_device_type_label        = ( empty( get_option( 'wc_device_type_label' ) ) ) ? esc_html__( 'Device Type', 'computer-repair-shop' ) : get_option( 'wc_device_type_label' );
		$wc_device_type_label_plural = ( empty( get_option( 'wc_device_type_label_plural' ) ) ) ? esc_html__( 'Device Type', 'computer-repair-shop' ) : get_option( 'wc_device_type_label_plural' );

		$wc_device_brand_label_plural = ( empty( get_option( 'wc_device_brand_label_plural' ) ) ) ? esc_html__( 'Device Brands', 'computer-repair-shop' ) : get_option( 'wc_device_brand_label_plural' );
		$wc_device_id_imei_label      = ( empty( get_option( 'wc_device_id_imei_label' ) ) ) ? esc_html__( 'ID/IMEI', 'computer-repair-shop' ) : get_option( 'wc_device_id_imei_label' );
		$wc_pin_code_field			  = get_option( 'wc_pin_code_field' );
		$wcpincodefield 			  = ( $wc_pin_code_field == 'on' ) ? 'checked="checked"' : '';

		$wc_pin_code_show_inv		  = get_option( 'wc_pin_code_show_inv' );
		$wcpincodeshowinvoice 		  = ( $wc_pin_code_show_inv == 'on' ) ? 'checked="checked"' : '';

		//If offer Pick and Delivery.
		$wc_offer_pick_deli = get_option( 'wc_offer_pick_deli' );
		$instruct 			= ( $wc_offer_pick_deli == 'on' ) ? 'checked="checked"' : '';
		
		$offer_laptop_one	= get_option( 'wc_one_day' );
		$offer_laptop_week 	= get_option( 'wc_one_week' );

		$wc_offer_laptop = get_option( 'wc_offer_laptop' );
		$offer_laptop    = ( $wc_offer_laptop == 'on' ) ? 'checked="checked"' : '';

		$pick_deliver_charg = get_option('wc_pick_delivery_charges');

		$setting_body = '<div class="tabs-panel team-wrap' . esc_attr($active) . '" 
        id="wc_rb_manage_devices" 
        role="tabpanel" 
        aria-hidden="true" 
        aria-labelledby="wc_rb_manage_devices-label">';

		$setting_body .= '<div class="wc-rb-manage-devices">';
		$setting_body .= '<h2>' . esc_html__( 'Brands & Devices', 'computer-repair-shop' ) . '</h2>';
		$setting_body .= '<div class="devices_success_msg"></div>';
		
		$setting_body .= '<form data-async data-abide class="needs-validation" novalidate method="post" data-success-class=".devices_success_msg">';

		$setting_body .= '<table class="form-table border"><tbody>';

		$setting_body .= '<tr><th scope="row"><label for="wc_pin_code_field">' . esc_html__( 'Enable Pin Code Field in Jobs page', 'computer-repair-shop' ) . '</label></th>';
		$setting_body .= '<td><input type="checkbox"  ' . esc_html( $wcpincodefield ) . ' name="wc_pin_code_field" id="wc_pin_code_field" /></td></tr>';

		$setting_body .= '<tr><th scope="row"><label for="wc_pin_code_show_inv">' . esc_html__( 'Show Pin Code in Invoices/Emails/Status Check', 'computer-repair-shop' ) . '</label></th>';
		$setting_body .= '<td><input type="checkbox"  ' . esc_html( $wcpincodeshowinvoice ) . ' name="wc_pin_code_show_inv" id="wc_pin_code_show_inv" /></td></tr>';

		if ( rb_is_woocommerce_activated() == TRUE ) {
			$wcrbreplacedevices_f	= get_option( 'wc_enable_devices_as_woo_products' );
			$wcrbreplacedevices     = ( $wcrbreplacedevices_f == 'on' ) ? 'checked="checked"' : '';
		
			$setting_body .= '<tr>
								<th scope="row">
								<label for="wc_enable_devices_as_woo_products">' . esc_html__( 'Replace devices & brands with WooCommerce products', 'computer-repair-shop' ) . '</label></th>';
			$setting_body .= '<td><input type="checkbox"  ' . esc_html( $wcrbreplacedevices ) . ' name="wc_enable_devices_as_woo_products" id="wc_enable_devices_as_woo_products" /></td></tr>';
		}

		$setting_body .= '<tr>';
		$setting_body .= '<th scope="row"><label for="wc_note_label">' . esc_html__( 'Other Labels', 'computer-repair-shop' ) . '</label></th>';

		$setting_body .= '<td><table class="form-table no-padding-table"><tr>';
		$setting_body .= '<td><label>' . esc_html__( 'Note label like Device Note', 'computer-repair-shop' );
		$setting_body .= '<input name="wc_note_label" id="wc_note_label" class="regular-text" value="' . esc_html( $wc_note_label ) . '" type="text" 
		placeholder="' . esc_html__( 'Note', 'computer-repair-shop' ) . '" /></label></td>';

		$setting_body .= '<td><label>' . esc_html__( 'Pin Code/Password Label', 'computer-repair-shop' ) . '<input name="wc_pin_code_label" id="wc_pin_code_label" class="regular-text" 
						value="' . esc_html( $wc_pin_code_label ) . '" type="text" placeholder="' . esc_html__( 'Pin Code/Password', 'computer-repair-shop' ) . '" /></label></td>';
		$setting_body .= '</tr></table></td></tr>';
		
		$setting_body .= '<tr>';
		$setting_body .= '<th scope="row"><label for="wc_device_label">' . esc_html__( 'Device Label', 'computer-repair-shop' ) . '</label></th>';

		$setting_body .= '<td><table class="form-table no-padding-table"><tr>';
		$setting_body .= '<td><label>' . esc_html__( 'Singular device label', 'computer-repair-shop' );
		$setting_body .= '<input name="wc_device_label" id="wc_device_label" class="regular-text" value="' . esc_html( $wc_device_label ) . '" type="text" 
		placeholder="' . esc_html__( 'Device', 'computer-repair-shop' ) . '" /></label></td>';

		$setting_body .= '<td><label>' . esc_html__( 'Plural device label', 'computer-repair-shop' ) . '<input name="wc_device_label_plural" id="wc_device_label_plural" class="regular-text" 
						value="' . esc_html( $wc_device_label_plural ) . '" type="text" placeholder="' . esc_html__( 'Devices', 'computer-repair-shop' ) . '" /></label></td>';
		$setting_body .= '</tr></table></td></tr>';

		$setting_body .= '<tr><th scope="row"><label for="wc_device_brand_label">' . esc_html__( 'Device Brand Label', 'computer-repair-shop' ) . '</label></th>';
		$setting_body .= '<td><table class="form-table no-padding-table"><tr><td><label>' . esc_html__( 'Singular device brand label', 'computer-repair-shop' );
		$setting_body .= '<input name="wc_device_brand_label" id="wc_device_brand_label" class="regular-text" value="' . esc_html( $wc_device_brand_label ) . '" type="text" 
						placeholder="' . esc_html__( 'Device Brand', 'computer-repair-shop' ) . '" /></label></td>';
					
		$setting_body .= '<td><label>' . esc_html__( 'Plural device brand label', 'computer-repair-shop' );
		$setting_body .= '<input name="wc_device_brand_label_plural" id="wc_device_brand_label_plural" class="regular-text" value="' . esc_html( $wc_device_brand_label_plural ) . '" 
						type="text" placeholder="' . esc_html__( 'Device Brands', 'computer-repair-shop' ) . '" /></label></td>';
		$setting_body .= '</tr></table></td></tr>';

		$setting_body .= '<tr><th scope="row"><label for="wc_device_type_label">' . esc_html__( 'Device Type Label', 'computer-repair-shop' ) . '</label></th>';
		$setting_body .= '<td><table class="form-table no-padding-table"><tr><td><label>' . esc_html__( 'Singular device type label', 'computer-repair-shop' );
		$setting_body .= '<input name="wc_device_type_label" id="wc_device_type_label" class="regular-text" value="' . esc_html( $wc_device_type_label ) . '" type="text" 
						placeholder="' . esc_html__( 'Device Type', 'computer-repair-shop' ) . '" /></label></td>';
					
		$setting_body .= '<td><label>' . esc_html__( 'Plural device type label', 'computer-repair-shop' );
		$setting_body .= '<input name="wc_device_type_label_plural" id="wc_device_type_label_plural" class="regular-text" value="' . esc_html( $wc_device_type_label_plural ) . '" 
						type="text" placeholder="' . esc_html__( 'Device Types', 'computer-repair-shop' ) . '" /></label></td>';
		$setting_body .= '</tr></table></td></tr>';

		$setting_body .= '<tr><th scope="row"><label for="wc_device_id_imei_label">' . esc_html__( 'ID/IMEI Label', 'computer-repair-shop' ) . '</label></th>';
		$setting_body .= '<td><input name="wc_device_id_imei_label" id="wc_device_id_imei_label" class="regular-text" value="' . esc_html( $wc_device_id_imei_label ) . '" type="text" 
			placeholder="' . esc_html__( 'ID/IMEI', 'computer-repair-shop' ) . '" /></td></tr></tbody></table>';

		//Start of Additional Fields Devices
		$setting_body .= '<div class="wc-rb-payment-methods">
							<h2>'. esc_html__( 'Additional device fields', 'computer-repair-shop' ) .'</h2>';
		$setting_body .= '<div class="wcrb_additional_device_fields_wrap clearfix">';

		$extra_device_fields = get_option( '_extra_device_fields' );

		if ( is_array( $extra_device_fields ) && ! empty( $extra_device_fields ) ) {
			if ( ! wc_rs_license_state() ) :
				$setting_body .= '<div class="callout success">'. esc_html__( 'Pro feature! Plugin activation required.', 'computer-repair-shop' ) .'</div>';
			endif;

			$setting_body .= '<table class="form-table border"><tbody>';
			$counter = 0;
			foreach( $extra_device_fields as $extra_device_field ) {
				$class = ( $counter == 0 ) ? 'wcrb_original wcrb_repater_field' : 'wcrb_repater_field';
				$delop = ( $counter == 0 ) ? '' : '<a class="delme delmedeviceextrafield" href="#" title="Remove row"><span class="dashicons dashicons-trash"></span></a>';

				$rb_device_field_label 					= ( isset( $extra_device_field['rb_device_field_label'] ) && ! empty( $extra_device_field['rb_device_field_label'] ) ) ? $extra_device_field['rb_device_field_label'] : '';
				$rb_device_field_type 					= ( isset( $extra_device_field['rb_device_field_type'] ) && ! empty( $extra_device_field['rb_device_field_type'] ) ) ? $extra_device_field['rb_device_field_type'] : '';
				$rb_device_field_display_booking_form 	= ( isset( $extra_device_field['rb_device_field_display_booking_form'] ) && ! empty( $extra_device_field['rb_device_field_display_booking_form'] ) ) ? $extra_device_field['rb_device_field_display_booking_form'] : '';
				$rb_device_field_display_in_invoice 	= ( isset( $extra_device_field['rb_device_field_display_in_invoice'] ) && ! empty( $extra_device_field['rb_device_field_display_in_invoice'] ) ) ? $extra_device_field['rb_device_field_display_in_invoice'] : '';
				$rb_device_field_display_for_customer 	= ( isset( $extra_device_field['rb_device_field_display_for_customer'] ) && ! empty( $extra_device_field['rb_device_field_display_for_customer'] ) ) ? $extra_device_field['rb_device_field_display_for_customer'] : '';
				$rb_device_field_id 					= ( isset( $extra_device_field['rb_device_field_id'] ) && ! empty( $extra_device_field['rb_device_field_id'] ) ) ? $extra_device_field['rb_device_field_id'] : '';

				$setting_body .= '<tr class="'. esc_attr( $class ) .'">
								<td class="wc_device_name">'. $delop .'
								<label>'. esc_html__( 'Field label', 'computer-repair-shop' ) .'<input class="regular-text" name="rb_device_field_label[]" value="'. esc_html( $rb_device_field_label ) .'" type="text"></label></td>
								<td><label>'. esc_html__( 'Type', 'computer-repair-shop' ) .'<select name="rb_device_field_type[]"><option value="text">' . esc_html__( 'Text', 'computer-repair-shop' ) . '</option></select></label></td>';

				$booking_form_yes = ( $rb_device_field_display_booking_form == 'yes' ) ? ' selected' : '';
				$booking_form_no =  ( $rb_device_field_display_booking_form == 'no' ) ? ' selected' : '';
				$setting_body .= '<td><label>'. esc_html__( 'In booking form?', 'computer-repair-shop' ) .'<select name="rb_device_field_display_booking_form[]">
				<option '. esc_attr( $booking_form_yes ) .' value="yes">' . esc_html__( 'Display', 'computer-repair-shop' ) . '</option>
				<option '. esc_attr( $booking_form_no ) .' value="no">' . esc_html__( 'Hide', 'computer-repair-shop' ) . '</option></select></label></td>';

				$ininvoice_yes =  ( $rb_device_field_display_in_invoice == 'yes' ) ? ' selected' : '';
				$ininvoice_no =  ( $rb_device_field_display_in_invoice == 'no' ) ? ' selected' : '';
				$setting_body .= '<td><label>'. esc_html__( 'In invoice?', 'computer-repair-shop' ) .'<select name="rb_device_field_display_in_invoice[]">
				<option '. esc_attr( $ininvoice_yes ) .' value="yes">' . esc_html__( 'Display', 'computer-repair-shop' ) . '</option>
				<option '. esc_attr( $ininvoice_no ) .' value="no">' . esc_html__( 'Hide', 'computer-repair-shop' ) . '</option></select></label></td>';

				$customerout_yes =  ( $rb_device_field_display_for_customer == 'yes' ) ? ' selected' : '';
				$customerout_no =  ( $rb_device_field_display_for_customer == 'no' ) ? ' selected' : '';
				$setting_body .= '<td><label>'. esc_html__( 'In customer output?', 'computer-repair-shop' ) .'<select name="rb_device_field_display_for_customer[]">
				<option '. esc_attr( $customerout_yes ) .' value="yes">' . esc_html__( 'Display in Status check, my account, emails', 'computer-repair-shop' ) . '</option>
				<option '. esc_attr( $customerout_no ) .' value="no">' . esc_html__( 'Hide', 'computer-repair-shop' ) . '</option></select></label>';

				$setting_body .= '<input type="hidden" name="rb_device_field_id[]" value="'. esc_html( $rb_device_field_id ) .'" />
								</td>
							</tr>';
				$counter++;
			}
			$setting_body .= '</tbody></table>';
		} else {
			$setting_body .= '<table class="form-table border"><tbody>';
			$setting_body .= '<tr class="wcrb_original wcrb_repater_field">
								<td class="wc_device_name">
								<label>'. esc_html__( 'Field label', 'computer-repair-shop' ) .'<input class="regular-text" name="rb_device_field_label[]" type="text"></label></td>
								<td><label>'. esc_html__( 'Type', 'computer-repair-shop' ) .'<select name="rb_device_field_type[]"><option value="text">' . esc_html__( 'Text', 'computer-repair-shop' ) . '</option></select></label></td>
								<td><label>'. esc_html__( 'In booking form?', 'computer-repair-shop' ) .'<select name="rb_device_field_display_booking_form[]"><option value="yes">' . esc_html__( 'Display', 'computer-repair-shop' ) . '</option><option value="no">' . esc_html__( 'Hide', 'computer-repair-shop' ) . '</option></select></label></td>
								<td><label>'. esc_html__( 'In invoice?', 'computer-repair-shop' ) .'<select name="rb_device_field_display_in_invoice[]"><option value="yes">' . esc_html__( 'Display', 'computer-repair-shop' ) . '</option><option value="no">' . esc_html__( 'Hide', 'computer-repair-shop' ) . '</option></select></label></td>
								<td><label>'. esc_html__( 'In customer output?', 'computer-repair-shop' ) .'<select name="rb_device_field_display_for_customer[]"><option value="yes">' . esc_html__( 'Display in Status check, my account, emails', 'computer-repair-shop' ) . '</option><option value="no">' . esc_html__( 'Hide', 'computer-repair-shop' ) . '</option></select></label>
								<input type="hidden" name="rb_device_field_id[]" value="" />
								</td>
							</tr>';
			$setting_body .= '</tbody></table>';				
		}
		$setting_body .= '<a href="#" class="button-primary alignright adddeviceextrafield">'. esc_html__( 'Add device field', 'computer-repair-shop' ) .'</a>';
		$setting_body .= '</div></div>';
		
		//End of Additional Fields Devices

		$setting_body .= '<table class="form-table border"><tbody><tr><th scope="row"><label for="offer_pic_de">' . esc_html__( 'Offer pickup and delivery?', 'computer-repair-shop' ) . '</label></th>';
		$setting_body .= '<td><input type="checkbox"  ' . esc_html( $instruct ) . ' name="offer_pic_de" id="offer_pic_de" /></td></tr>';

		$setting_body .= '<tr><th scope="row"><label for="pick_deliver">' . esc_html__( 'Pick up and delivery charges', 'computer-repair-shop' ) . '</label></th>';
		$setting_body .= '<td><input name="pick_deliver" id="pick_deliver" class="regular-text wc_validate_number" value="' . esc_html( $pick_deliver_charg ) . '" type="text" 
					placeholder="' . esc_html__( 'Enter the Pick up and delivery charges here', 'computer-repair-shop' ) . '"/></td></tr>';

		$setting_body .= '<tr><th scope="row"><label for="offer_laptop">' . esc_html__( 'Offer device rental?', 'computer-repair-shop' ) . '</label></th>';
		$setting_body .= '<td><input type="checkbox"  ' . esc_html( $offer_laptop ) . ' name="offer_laptop" id="offer_laptop" /></td></tr>';

		$setting_body .= '<tr><th scope="row"><label for="offer_laptop_one">' . esc_html__( 'Device rent', 'computer-repair-shop' ) . '</label></th>';
		$setting_body .= '<td><table class="form-table no-padding-table"><tr><td><label>' . esc_html__( 'Device rent per day', 'computer-repair-shop' );
		$setting_body .= '<input name="offer_laptop_one" id="offer_laptop_one" class="regular-text wc_validate_number" value="' . esc_html( $offer_laptop_one ) . '" type="text" 
							placeholder="' . esc_html__( 'Enter the device rent for one day', 'computer-repair-shop' ) . '"/></label></td>';
		$setting_body .= '<td><label>' . esc_html__( 'Device rent per week', 'computer-repair-shop' );
		$setting_body .= '<input name="offer_laptop_week" id="offer_laptop_week" class="regular-text wc_validate_number" value="' . esc_html( $offer_laptop_week ) . '" type="text" 
							placeholder="' . esc_html__( 'Enter the Device rent for one week', 'computer-repair-shop' ) . '"/></label></td></tr></table></td></tr>';

		$setting_body .= '</tbody></table>';

		$setting_body .= '<input type="hidden" name="form_type" value="wc_rb_update_sett_devices_brands" />';
		$setting_body .= wp_nonce_field( 'wcrb_nonce_setting_payment', 'wcrb_nonce_setting_payment_field', true, false );

		$setting_body .= '<button type="submit" class="button button-primary" data-type="rbssubmitdevices">' . esc_html__( 'Update Options', 'computer-repair-shop' ) . '</button></form>';

		$setting_body .= '</div><!-- wc rb Devices /-->';

		$setting_body .= '</div><!-- Tabs Panel /-->';

		$allowedHTML = ( function_exists( 'wc_return_allowed_tags' ) ) ? wc_return_allowed_tags() : '';
		echo wp_kses( $setting_body, $allowedHTML );
	}

	function wc_rb_update_device_settings() {
		$message = '';
		$success = 'NO';

		if ( ! isset( $_POST['wcrb_nonce_setting_payment_field'] ) || ! wp_verify_nonce( $_POST['wcrb_nonce_setting_payment_field'], 'wcrb_nonce_setting_payment' ) ) {
			$message = esc_html__( "Something is wrong with your submission!", "computer-repair-shop" );
		} else {
			// process form data
			if ( rb_is_woocommerce_activated() ) {
				$wc_enable_devices_as_woo_products = ( ! isset( $_POST['wc_enable_devices_as_woo_products'] ) ) ? '' : sanitize_text_field( $_POST['wc_enable_devices_as_woo_products'] );
				update_option( 'wc_enable_devices_as_woo_products', $wc_enable_devices_as_woo_products );
			}

			$wc_note_label 				  = ( ! isset( $_POST['wc_note_label'] ) ) ? '' : sanitize_text_field( $_POST['wc_note_label'] );
			$wc_pin_code_label			  = ( ! isset( $_POST['wc_pin_code_label'] ) ) ? '' : sanitize_text_field( $_POST['wc_pin_code_label'] );
			$wc_device_label			  = ( ! isset( $_POST['wc_device_label'] ) ) ? '' : sanitize_text_field( $_POST['wc_device_label'] );
			$wc_device_brand_label		  = ( ! isset( $_POST['wc_device_brand_label'] ) ) ? '' : sanitize_text_field( $_POST['wc_device_brand_label'] );
			$wc_device_id_imei_label	  = ( ! isset( $_POST['wc_device_id_imei_label'] ) ) ? '' : sanitize_text_field( $_POST['wc_device_id_imei_label'] );
			$wc_device_label_plural		  = ( ! isset( $_POST['wc_device_label_plural'] ) ) ? '' : sanitize_text_field( $_POST['wc_device_label_plural'] );
			$wc_device_brand_label_plural = ( ! isset( $_POST['wc_device_brand_label_plural'] ) ) ? '' : sanitize_text_field( $_POST['wc_device_brand_label_plural'] );

			$wc_device_type_label		  = ( ! isset( $_POST['wc_device_type_label'] ) ) ? '' : sanitize_text_field( $_POST['wc_device_type_label'] );
			$wc_device_type_label_plural = ( ! isset( $_POST['wc_device_type_label_plural'] ) ) ? '' : sanitize_text_field( $_POST['wc_device_type_label_plural'] );

			$wc_pin_code_field 			  = ( ! isset( $_POST['wc_pin_code_field'] ) ) ? '' : sanitize_text_field( $_POST['wc_pin_code_field'] );
			$wc_pin_code_show_inv 		  = ( ! isset( $_POST['wc_pin_code_show_inv'] ) ) ? '' : sanitize_text_field( $_POST['wc_pin_code_show_inv'] );
			
			$pick_deliver				  = (!isset($_POST['pick_deliver'])) ? "" : 			sanitize_text_field($_POST['pick_deliver']);
			$offer_laptop				  = (!isset($_POST['offer_laptop'])) ? "" : 			sanitize_text_field($_POST['offer_laptop']);
			$offer_laptop_one			  = (!isset($_POST['offer_laptop_one'])) ? "" : 		sanitize_text_field($_POST['offer_laptop_one']);
			$offer_laptop_week			  = (!isset($_POST['offer_laptop_week'])) ? "" : 		sanitize_text_field($_POST['offer_laptop_week']);
			$offer_pic_de 				  = (!isset($_POST['offer_pic_de'])) ? "" : sanitize_text_field($_POST['offer_pic_de']);
			
			//Process Extra Device fields. 
			$device_extra_options = array();
			if ( isset( $_POST["rb_device_field_label"] ) && is_array( $_POST["rb_device_field_label"] ) ) {
				for ( $i = 0; $i < count( $_POST["rb_device_field_label"] ); $i++ ) {
					$rb_device_field_id 					= ( isset( $_POST["rb_device_field_id"][$i] ) && ! empty( $_POST["rb_device_field_id"][$i] ) ) ? sanitize_text_field( $_POST["rb_device_field_id"][$i] ) : wcrb_get_random_unique_username( 'device_field_' );
					$rb_device_field_label 					= ( isset( $_POST["rb_device_field_label"][$i] ) && ! empty( $_POST["rb_device_field_label"][$i] ) ) ? sanitize_text_field( $_POST["rb_device_field_label"][$i] ) : '';
					$rb_device_field_type 					= ( isset( $_POST["rb_device_field_type"][$i] ) && ! empty( $_POST["rb_device_field_type"][$i] ) ) ? sanitize_text_field( $_POST["rb_device_field_type"][$i] ) : '';
					$rb_device_field_display_booking_form 	= ( isset( $_POST["rb_device_field_display_booking_form"][$i] ) && ! empty( $_POST["rb_device_field_display_booking_form"][$i] ) ) ? sanitize_text_field( $_POST["rb_device_field_display_booking_form"][$i] ) : '';
					$rb_device_field_display_in_invoice 	= ( isset( $_POST["rb_device_field_display_in_invoice"][$i] ) && ! empty( $_POST["rb_device_field_display_in_invoice"][$i] ) ) ? sanitize_text_field( $_POST["rb_device_field_display_in_invoice"][$i] ) : '';
					$rb_device_field_display_for_customer 	= ( isset( $_POST["rb_device_field_display_for_customer"][$i] ) && ! empty( $_POST["rb_device_field_display_for_customer"][$i] ) ) ? sanitize_text_field( $_POST["rb_device_field_display_for_customer"][$i] ) : '';
	
					if ( ! empty( $rb_device_field_label ) ) : 
						$device_extra_options[] = array(
							'rb_device_field_id'					=> esc_html( $rb_device_field_id ),
							'rb_device_field_label' 				=> esc_html( $rb_device_field_label ),
							'rb_device_field_type' 					=> esc_html( $rb_device_field_type ),
							'rb_device_field_display_booking_form'  => esc_html( $rb_device_field_display_booking_form ),
							'rb_device_field_display_in_invoice' 	=> esc_html( $rb_device_field_display_in_invoice ),
							'rb_device_field_display_for_customer' 	=> esc_html( $rb_device_field_display_for_customer )	
						);
					endif;
				}
			}
			update_option( '_extra_device_fields', $device_extra_options );
		
			update_option('wc_offer_pick_deli', $offer_pic_de);//Processing offer_pic_de checkbox.
			update_option('wc_one_day', $offer_laptop_one);//Processing offer_laptop for one day input box.
			update_option('wc_one_week', $offer_laptop_week);//Processing offer_laptop for one week input box.
			update_option('wc_offer_laptop', $offer_laptop);//Processing offer_laptop checkbox.
			update_option('wc_pick_delivery_charges', $pick_deliver); //Processing pickup and delivery charges.
			update_option( 'wc_device_label', $wc_device_label );
			update_option( 'wc_device_brand_label', $wc_device_brand_label );
			update_option( 'wc_device_label_plural', $wc_device_label_plural );
			update_option( 'wc_device_brand_label_plural', $wc_device_brand_label_plural );

			update_option( 'wc_device_type_label', $wc_device_type_label );
			update_option( 'wc_device_type_label_plural', $wc_device_type_label_plural );

			update_option( 'wc_note_label', $wc_note_label );
			update_option( 'wc_pin_code_label', $wc_pin_code_label );

			update_option( 'wc_device_id_imei_label', $wc_device_id_imei_label );
			update_option( 'wc_pin_code_field', $wc_pin_code_field );
			update_option( 'wc_pin_code_show_inv', $wc_pin_code_show_inv );

			$message = esc_html__( 'Settings updated!', 'computer-repair-shop' );
		}

		$values['message'] = $message;
		$values['success'] = $success;

		wp_send_json( $values );
		wp_die();
	}

	function wc_add_device_for_manufacture() {
		$message = '';
		$device_id = '';
		$success = 'NO';

		if ( ! isset( $_POST['wcrb_nonce_adrepairbuddy_field'] ) || ! wp_verify_nonce( $_POST['wcrb_nonce_adrepairbuddy_field'], 'wcrb_nonce_adrepairbuddy' ) ) {
			$message = esc_html__( "Something is wrong with your submission!", "computer-repair-shop" );
		} else {

			$manufacture = ( isset( $_POST['manufacture'] ) ) ? sanitize_text_field( $_POST['manufacture'] ) : '';
			$devicetype  = ( isset( $_POST['devicetype'] ) ) ? sanitize_text_field( $_POST['devicetype'] ) : '';
			$device_name = ( isset( $_POST['device_name'] ) ) ? sanitize_text_field( $_POST['device_name'] ) : '';

			if ( empty ( $manufacture ) || empty( $device_name ) || $manufacture == 'All' ) {
				$message = esc_html__( 'Brand and device names cannot be empty.', 'computer-repair-shop' );
			} else {
				//Check device status
				$curr = post_exists( $device_name,'','','rep_devices' );

				if ( $curr == '0' ) {
					//Post didn't exist let's add 
					$post_data = array(
						'post_title'    => $device_name,
						'post_status'   => 'publish',
						'post_type' 	=> 'rep_devices',
					);
					$post_id = wp_insert_post( $post_data );

					if ( isset( $_POST['disable_in_booking_form'] ) && ! empty( $_POST['disable_in_booking_form'] ) ) {
						update_post_meta( $post_id, '_disable_in_booking_form', sanitize_text_field( $_POST['disable_in_booking_form'] ) );
					}

					$tag = array( $manufacture );
					wp_set_post_terms( $post_id, $tag, 'device_brand' );

					if ( ! empty( $devicetype ) ) {
						$type = array( $devicetype );
						wp_set_post_terms( $post_id, $type, 'device_type' );
					}

					$device_id = $post_id;
					$message = esc_html__( 'Device Added', 'computer-repair-shop' );
				} else {
					$device_id = $curr;
					$message = esc_html__( 'Device with same name already exists', 'computer-repair-shop' );
				}
			}
		}
		
		$values['message'] = $message;
		$values['device_id'] = $device_id;
		$values['success'] = $success;

		wp_send_json( $values );
		wp_die();
	}

	/**
	 * Device Manufacture Options
	 * Return options
	 * Outputs selected options
	 */
	function generate_manufacture_options( $selected_manufacture, $select_all ) {

		$selected_manufacture = ( ! empty( $selected_manufacture ) ) ? $selected_manufacture : '';

		$wcrb_type = 'rep_devices';
		$wcrb_tax  = 'device_brand';

		$cat_terms = get_terms(
			array(
					'taxonomy'		=> $wcrb_tax,
					'hide_empty'    => false,
					'orderby'       => 'name',
					'order'         => 'ASC',
					'number'        => 0
				)
		);

		$wc_device_label = ( empty( get_option( 'wc_device_brand_label' ) ) ) ? esc_html__( 'Brand', 'computer-repair-shop' ) : get_option( 'wc_device_brand_label' );
		$wc_device_labels = ( empty( get_option( 'wc_device_brand_label_plural' ) ) ) ? esc_html__( 'Brands', 'computer-repair-shop' ) : get_option( 'wc_device_brand_label_plural' );

		$output = "<option value='All'>" . esc_html__("Select", "computer-repair-shop") . ' ' . $wc_device_label . "</option>";

		$output = ( isset( $select_all ) && ! empty( $select_all ) ) ? "<option value='All'>" . esc_html__("For All ", "computer-repair-shop") . ' ' . $wc_device_labels . "</option>" : $output;

		if( $cat_terms ) :
			foreach( $cat_terms as $term ) :
				$selected = ( $term->term_id == $selected_manufacture ) ? ' selected' : '';
				$output .= '<option ' . $selected . ' value="' . esc_html( $term->term_id ) . '">';
				$output .= $term->name;
				$output .= '</option>';

			endforeach;
		endif;

		return $output;
	}

	function generate_device_type_options( $selected_type, $extra_field ) {
		$selected_type = ( ! empty( $selected_type ) ) ? $selected_type : '';

		$wcrb_type = 'rep_devices';
		$wcrb_tax  = 'device_type';

		$cat_terms = get_terms(
			array(
					'taxonomy'		=> $wcrb_tax,
					'hide_empty'    => false,
					'orderby'       => 'name',
					'order'         => 'ASC',
					'number'        => 0
				)
		);

		$wc_device_label = ( empty( get_option( 'wc_device_type_label' ) ) ) ? esc_html__( 'Type', 'computer-repair-shop' ) : get_option( 'wc_device_type_label' );
		$wc_device_labels = ( empty( get_option( 'wc_device_type_label_plural' ) ) ) ? esc_html__( 'Types', 'computer-repair-shop' ) : get_option( 'wc_device_type_label_plural' );

		$output = "<option value='All'>" . esc_html__("Select", "computer-repair-shop") . ' ' . $wc_device_label . "</option>";

		$output = ( isset( $extra_field ) && ! empty( $extra_field ) ) ? "<option value='All'>" . esc_html__("For All ", "computer-repair-shop") . ' ' . $wc_device_labels . "</option>" : $output;

		if( $cat_terms ) :
			foreach( $cat_terms as $term ) :
				$selected = ( $term->term_id == $selected_type ) ? ' selected' : '';
				$output .= '<option ' . $selected . ' value="' . esc_html( $term->term_id ) . '">';
				$output .= $term->name;
				$output .= '</option>';

			endforeach;
		endif;

		return $output;
	}

	public function ajax_update_device_options() {
		$message = $success = '';
        // Verify nonce
		if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], 'wcrb_nonce_adrepairbuddy' ) ) {
			$message = '<option>' .  esc_html__( "Something is wrong with your submission!", "computer-repair-shop" ) . '</option>';
		} else {
        
			$selected_brand = isset($_POST['brand']) ? intval($_POST['brand']) : '';
			$selected_type = isset($_POST['type']) ? intval($_POST['type']) : '';
			$selected_device = isset($_POST['device']) ? intval($_POST['device']) : '';
			
			// Update options based on selections
			if (!empty($selected_brand)) {
				update_option('wc_booking_default_brand', $selected_brand);
			}
			
			if (!empty($selected_type)) {
				update_option('wc_booking_default_type', $selected_type);
			}
			
			// Generate new options
			$message = $this->generate_device_options($selected_device, '');
		}
		$values['message'] = $message;
		$values['success'] = $success;

		wp_send_json( $values );
		wp_die();
    }


	/**
     * Generate device options based on selected manufacturer and type
     * 
     * @param string $selected_device Currently selected device ID
     * @param string $extra_field Additional context parameter
     * @return string HTML options for device select
     */
    public function generate_device_options($selected_device, $extra_field) {
        // Get default settings
        $wc_booking_default_type = empty(get_option('wc_booking_default_type')) ? '' : get_option('wc_booking_default_type');
        $wc_booking_default_brand = empty(get_option('wc_booking_default_brand')) ? '' : get_option('wc_booking_default_brand');
        
        // Determine filter parameters
        $brand_filter = !empty($wc_booking_default_brand) ? $wc_booking_default_brand : '';
        $type_filter = !empty($wc_booking_default_type) ? $wc_booking_default_type : '';
        
        // Handle empty cases
        if ( empty( $brand_filter ) || $brand_filter == 'All' ) {
            return '<option value="">' . esc_html__('Please select manufacturer to load devices', 'computer-repair-shop') . '</option>';
        }
        
        // Build tax query
        $tax_query = array();
		$tax_query = ( ! empty( $brand_filter ) && $type_filter != 'All' && ! empty( $type_filter ) ) ? array('relation' => 'AND') : array();
        
        if ( ! empty( $brand_filter ) && $brand_filter != 'All' ) {
            $tax_query[] = array(
                'taxonomy' => 'device_brand',
                'field' => 'term_id',
                'terms' => $brand_filter
            );
        }
        
        if  (! empty( $type_filter )  && $type_filter != 'All' ) {
            $tax_query[] = array(
                'taxonomy' => 'device_type',
                'field' => 'term_id',
                'terms' => $type_filter
            );
        }
        
        // Query devices
        $devices = new WP_Query(array(
            'post_type' => 'rep_devices',
            'posts_per_page' => -1,
            'tax_query' => $tax_query,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
		$wc_device_label = ( empty( get_option( 'wc_device_label' ) ) ) ? esc_html__( 'Device', 'computer-repair-shop' ) : get_option( 'wc_device_label' );
        // Generate options
        $options = '<option value="">' . esc_html__('Select Default', 'computer-repair-shop' ) . ' ' . esc_html( $wc_device_label ) . '</option>';
        
        if ($devices->have_posts()) {
            while ($devices->have_posts()) {
                $devices->the_post();
                $device_id = get_the_ID();
                $selected = selected($selected_device, $device_id, false);
                $options .= '<option value="' . esc_attr($device_id) . '" ' . $selected . '>' . esc_html(get_the_title()) . '</option>';
            }
            wp_reset_postdata();
        } else {
            $options .= '<option value="">' . esc_html__('No devices found', 'computer-repair-shop') . '</option>';
        }
        
        return $options;
    }

	/**
	 * Add Device Reveal Form
	 * Needs to load in footer first
	 */
	function add_device_reveal_form() {
		$output = '';

		$wc_device_label 	   = ( empty( get_option( 'wc_device_label' ) ) ) ? esc_html__( 'Device', 'computer-repair-shop' ) : get_option( 'wc_device_label' );
		$wc_device_brand_label = ( empty( get_option( 'wc_device_brand_label' ) ) ) ? esc_html__( 'Device Brand', 'computer-repair-shop' ) : get_option( 'wc_device_brand_label' );
		$wc_device_type_label = ( empty( get_option( 'wc_device_type_label' ) ) ) ? esc_html__( 'Device Type', 'computer-repair-shop' ) : get_option( 'wc_device_type_label' );

		$output .= '<div class="small reveal" id="deviceFormReveal" data-reveal>';
		$output .= '<h2>' . esc_html__( 'Add New', 'computer-repair-shop' ) . ' ' . esc_html( $wc_device_label ) . '</h2>';
	
		$output .= '<div class="form-message"></div>';
	
		$output .= '<form data-async data-abide class="needs-validation" novalidate method="post">';
		
		$output .= '<div class="grid-x grid-margin-x">';
		$output .= '<div class="cell">
						<div data-abide-error class="alert callout hidden">
							<p>' . esc_html__( 'There are some errors in your form.', 'computer-repair-shop' ) . '</p>
						</div>
					</div></div>';
	
		$output .= '<div class="grid-x grid-margin-x">';
	
		$output .= '<div class="cell medium-6">
						<label class="have-addition">' . esc_html__( 'Select ', 'computer-repair-shop' ) . esc_html( $wc_device_brand_label ) . '*';
		$output .= '<select name="manufacture">';
		$output .= $this->generate_manufacture_options( '', '' );
		$output .= '</select>';
		$output .= '<a href="edit-tags.php?taxonomy=device_brand&post_type=rep_devices" target="_blank" class="button button-primary button-small" title="' . esc_html__( 'Add ', 'computer-repair-shop' ) . $wc_device_brand_label . '"><span class="dashicons dashicons-plus"></span></a>';
		$output .= '</label>
					</div>';

		$output .= '<div class="cell medium-6"><label class="have-addition">' . esc_html__( 'Select ', 'computer-repair-shop' ) . esc_html( $wc_device_type_label ) . '*';
		$output .= '<select name="devicetype">';
		$output .= $this->generate_device_type_options( '', '' );
		$output .= '</select>';
		$output .= '<a href="edit-tags.php?taxonomy=device_type&post_type=rep_devices" target="_blank" class="button button-primary button-small" title="' . esc_html__( 'Add ', 'computer-repair-shop' ) . $wc_device_brand_label . '"><span class="dashicons dashicons-plus"></span></a>';
		$output .= '</label></div>';
	
		$output .= '<div class="cell medium-6">
						<label>' . esc_html( $wc_device_label ) . esc_html__( ' Name', 'computer-repair-shop' ) . '
							<input name="device_name" type="text" class="form-control login-field"
								   value="" id="device_name"/>
						</label>';
		$output .= '</div>';

		$output .= '<div class="cell medium-6"><p></p><p></p>
						<label for="disable_in_booking_form">' . sprintf( esc_html__( 'Disable this %s in booking forms', 'computer-repair-shop' ), esc_html( $wc_device_label )  )  . '
							<input name="disable_in_booking_form" type="checkbox" class="form-control login-field"
								   value="yes" id="disable_in_booking_form" /></label>';
		$output .= '</div>';
					
		$output .= '</div>';
		
		$output .= '<input name="form_type" type="hidden" value="add_device_form" />';
		$output .= wp_nonce_field( 'wcrb_nonce_adrepairbuddy', 'wcrb_nonce_adrepairbuddy_field', true, false );
	
		$output .= '<div class="grid-x grid-margin-x">
					<fieldset class="cell medium-6">
						<button class="button" type="submit">' . esc_html__( "Add", "computer-repair-shop" ) . ' ' . esc_html( $wc_device_label ) . '</button>
					</fieldset>
				</div>
			</form>
	
			<button class="close-button" data-close aria-label="Close modal" type="button">
				<span aria-hidden="true">&times;</span>
			</button>
		</div>';

		$allowedHTML = ( function_exists( 'wc_return_allowed_tags' ) ) ? wc_return_allowed_tags() : '';
		echo wp_kses( $output, $allowedHTML );
	}

	function return_add_device_form_boot( $view ) {
		global $WCRB_MANAGE_DEVICES;

		$current_user = wp_get_current_user();
		$role         = $current_user->roles[0] ?? 'guest';

		$view = ( isset( $view ) ) ? $view : 'front';

		$wc_device_id_imei_label = ( empty( get_option( 'wc_device_id_imei_label' ) ) ) ? esc_html__( 'ID/IMEI', 'computer-repair-shop' ) : get_option( 'wc_device_id_imei_label' );
		$wc_pin_code_label 		 = ( empty( get_option( 'wc_pin_code_label' ) ) ) ? esc_html__( 'Pin Code/Password', 'computer-repair-shop' ) : get_option( 'wc_pin_code_label' );
		$wc_device_label         = ( empty( get_option( 'wc_device_label' ) ) ) ? esc_html__( 'Device', 'computer-repair-shop' ) : get_option( 'wc_device_label' );
		$wc_device_brand_label   = ( empty( get_option( 'wc_device_brand_label' ) ) ) ? esc_html__( 'Device Brand', 'computer-repair-shop' ) : get_option( 'wc_device_brand_label' );
		$wc_device_type_label    = ( empty( get_option( 'wc_device_type_label' ) ) ) ? esc_html__( 'Device Type', 'computer-repair-shop' ) : get_option( 'wc_device_type_label' );

		$output = '<div class="modal fade" id="addDeviceModal" tabindex="-1" aria-labelledby="addDeviceModalLabel" aria-hidden="true">';
		$output .= '<div class="modal-dialog modal-lg">';
		$output .= '<div class="modal-content">';
		$output .= '<div class="modal-header">';
		$output .= '<h5 class="modal-title" id="addDeviceModalLabel">' . esc_html__( 'Add New ', 'computer-repair-shop' ) . $wc_device_label . '</h5>';
		$output .= '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
		$output .= '</div>'; // .modal-header
		
		$output .= '<div class="modal-body">';
		$output .= '<div class="adddevicecustomermessage"></div>';
		
		$output .= '<form data-async class="needs-validation" novalidate method="post" data-success-class=".adddevicecustomermessage">';
		$output .= '<input type="hidden" name="form_action" value="wcrb_add_customer_device" />';
		$output .= '<input type="hidden" name="reload_location" value="deviceslistcustomer" />';

		$output .= '<div class="row g-3">';
		
		if ( $role == 'administrator' || $role == 'store_manager' ) : 
			// Select Customer
			$output .= '<div class="col-md-6">';
			$output .= '<label for="devicecustomer" class="form-label">' . esc_html__( 'Select Customer', 'computer-repair-shop' ) . ' *</label>';
			$output .= wcrb_return_customer_select_options( '', 'devicecustomer', 'devicecustomer' );
			$output .= '<div class="invalid-feedback">' . esc_html__( 'Please select a customer.', 'computer-repair-shop' ) . '</div>';
			$output .= '</div>';
		endif;

		// Device Type
		$output .= '<div class="col-md-6">';
		$output .= '<label for="devicetype" class="form-label">' . esc_html__( 'Select ', 'computer-repair-shop' ) . $wc_device_type_label . ' *</label>';
		$output .= '<select class="form-select wcrbupdatedatalist" id="devicetype" name="devicetype" required>';
		$output .= $this->generate_device_type_options( '', '' );
		$output .= '</select>';
		$output .= '<div class="invalid-feedback">' . sprintf( esc_html__( 'Please select a %s.', 'computer-repair-shop' ), $wc_device_type_label ) . '</div>';
		$output .= '</div>';

		// Device Brand
		$output .= '<div class="col-md-6">';
		$output .= '<label for="manufacture" class="form-label">' . esc_html__( 'Select ', 'computer-repair-shop' ) . $wc_device_brand_label . ' *</label>';
		$output .= '<select class="form-select wcrbupdatedatalist" id="manufacture" name="manufacture" required>';
		$output .= $this->generate_manufacture_options( '', '' );
		$output .= '</select>';
		$output .= '<div class="invalid-feedback">' . sprintf( esc_html__( 'Please select a %s.', 'computer-repair-shop' ), $wc_device_brand_label ) . '</div>';
		$output .= '</div>';


		// Device Name
		$output .= '<div class="col-md-6">';
		$output .= '<label for="device_name" class="form-label">' . $wc_device_label . ' ' . esc_html__( 'Name', 'computer-repair-shop' ) . '</label>';
		$output .= '<input list="device_name_list" class="form-control" type="text" id="device_name" name="device_name" />';
		$output .= '<datalist id="device_name_list">';
		$output .= $GLOBALS['WCRB_MY_ACCOUNT']->wc_return_devices_datalist();
		$output .= '</datalist>';
		$output .= '</div>';

		// IMEI/Serial
		$output .= '<div class="col-md-6">';
		$output .= '<label for="imei_serial" class="form-label">' . $wc_device_id_imei_label . '</label>';
		$output .= '<input name="imei_serial" type="text" class="form-control" id="imei_serial" />';
		$output .= '</div>';

		// Pin Code
		$output .= '<div class="col-md-6">';
		$output .= '<label for="device_pincode" class="form-label">' . $wc_pin_code_label . '</label>';
		$output .= '<input name="device_pincode" type="text" class="form-control" id="device_pincode" />';
		$output .= '</div>';
						
		$output .= '</div>'; // .row
		
		$output .= '<input name="form_type" type="hidden" value="add_device_form_front" />';
		$output .= wp_nonce_field( 'wcrb_add_device_nonce', 'wcrb_add_device_nonce_post', true, false );

		$output .= '</div>'; // .modal-body
		
		$output .= '<div class="modal-footer">';
		$output .= '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' . esc_html__( 'Close', 'computer-repair-shop' ) . '</button>';
		$output .= '<button type="submit" class="btn btn-primary">' . esc_html__( 'Add ', 'computer-repair-shop' ) . $wc_device_label . '</button>';
		$output .= '</div>'; // .modal-footer
		
		$output .= '</form>';
		$output .= '</div>'; // .modal-content
		$output .= '</div>'; // .modal-dialog
		$output .= '</div>'; // .modal

		$allowedHTML = ( function_exists( 'wc_return_allowed_tags' ) ) ? wc_return_allowed_tags() : '';
		return wp_kses( $output, $allowedHTML );
	}

	function list_customer_devices_bootstrap( $view = 'frontend' ) {
		global $wpdb;
		
		if ( ! is_user_logged_in() ) {
			return array(
				'stats' 	 => '',
				'filters' 	 => '',
				'rows' 		 => esc_html__( 'You are not logged in.', 'computer-repair-shop' ),
				'pagination' => ''
			);
		}
		
		// Load device labels from options
		$wc_device_label 		 = ( empty( get_option( 'wc_device_label_plural' ) ) ) ? esc_html__( 'Devices', 'computer-repair-shop' ) : get_option( 'wc_device_label_plural' );
		$sing_device_label 		 = ( empty( get_option( 'wc_device_label' ) ) ) ? esc_html__( 'Device', 'computer-repair-shop' ) : get_option( 'wc_device_label' );
		$wc_device_id_imei_label = ( empty( get_option( 'wc_device_id_imei_label' ) ) ) ? esc_html__( 'ID/IMEI', 'computer-repair-shop' ) : get_option( 'wc_device_id_imei_label' );
		$wc_pin_code_label 		 = ( empty( get_option( 'wc_pin_code_label' ) ) ) ? esc_html__( 'Pin Code/Password', 'computer-repair-shop' ) : get_option( 'wc_pin_code_label' );
		
		$current_user = wp_get_current_user();
		$user_id = $current_user->ID;
		$user_roles = (array) $current_user->roles;
		
		// Determine if user can view all devices (admin/store manager)
		$can_view_all = array_intersect( array( 'administrator', 'store_manager' ), $user_roles );
		$is_admin 	  = ! empty( $can_view_all ) && $view === 'admin';
		
		// Setup pagination
		$devices_per_page = 20;
		$current_page 	  = isset( $_GET['devices_page'] ) ? max( 1, intval( $_GET['devices_page'] ) ) : 1;
		$offset 		  = ( $current_page - 1 ) * $devices_per_page;
		
		// Table name
		$computer_repair_customer_devices = $wpdb->prefix . 'wc_cr_customer_devices';
		
		// Build WHERE clause
		$where_clause = '';
		$query_params = array();
		
		if ( ! $is_admin ) {
			// Customer sees only their devices
			$where_clause   = "WHERE `customer_id` = %d";
			$query_params[] = $user_id;
		}
		
		// Apply filters
		$filters = array();
		
		// Filter: Search (Device Label, Serial Number, PIN)
		if ( isset( $_GET["device_search"] ) && ! empty( $_GET["device_search"] ) ) {
			$search_term = sanitize_text_field( $_GET['device_search'] );
			$filters[] = $wpdb->prepare( "(`device_label` LIKE %s OR `serial_nuumber` LIKE %s OR `pint_code` LIKE %s)", 
				'%' . $wpdb->esc_like( $search_term ) . '%',
				'%' . $wpdb->esc_like( $search_term ) . '%',
				'%' . $wpdb->esc_like( $search_term ) . '%'
			);
		}
		
		// Filter: Device Post ID
		if ( isset( $_GET["device_post_id"] ) && ! empty( $_GET["device_post_id"] ) && $_GET["device_post_id"] != 'All' ) {
			$device_post_id = intval( $_GET["device_post_id"] );
			$filters[] = $wpdb->prepare( "`device_post_id` = %d", $device_post_id );
		}
		
		// Filter: Customer (for admin only)
		if ( $is_admin && isset( $_GET["job_customer"] ) && ! empty( $_GET["job_customer"] ) && $_GET["job_customer"] != 'all' ) {
			$customer_id = intval( $_GET['job_customer'] );
			$filters[] = $wpdb->prepare( "`customer_id` = %d", $customer_id );
		}
		
		// Add filters to WHERE clause
		if ( ! empty( $filters ) ) {
			$where_clause .= ( empty( $where_clause ) ? 'WHERE ' : ' AND ' ) . implode( ' AND ', $filters );
		}
		
		// Get total count for pagination
		$count_query = "SELECT COUNT(*) FROM `{$computer_repair_customer_devices}` {$where_clause}";
		if ( ! empty( $query_params ) ) {
			$count_query = $wpdb->prepare( $count_query, $query_params );
		}
		$total_devices = $wpdb->get_var( $count_query );
		
		// Get stats for top 6 device types/brands
		$stats = array();
		if ( $is_admin ) {
			$stats_query = "
				SELECT 
					c.`customer_id`,
					COUNT(*) as device_count,
					u.display_name,
					u.user_email,
					um_first.meta_value as first_name,
					um_last.meta_value as last_name
				FROM `{$computer_repair_customer_devices}` c
				LEFT JOIN {$wpdb->users} u ON c.customer_id = u.ID
				LEFT JOIN {$wpdb->usermeta} um_first ON (
					um_first.user_id = u.ID 
					AND um_first.meta_key = 'first_name'
				)
				LEFT JOIN {$wpdb->usermeta} um_last ON (
					um_last.user_id = u.ID 
					AND um_last.meta_key = 'last_name'
				)
				GROUP BY c.`customer_id`
				ORDER BY device_count DESC
				LIMIT 6
			";
			
			$stats_results = $wpdb->get_results( $stats_query );
			
			foreach ( $stats_results as $stat ) {
				// Build the best possible name
				$customer_name = '';
				if ( ! empty( $stat->first_name ) && ! empty( $stat->last_name ) ) {
					$customer_name = $stat->first_name . ' ' . $stat->last_name;
				} elseif ( ! empty( $stat->display_name ) ) {
					$customer_name = $stat->display_name;
				} elseif ( ! empty( $stat->user_email ) ) {
					$customer_name = $stat->user_email;
				} else {
					$customer_name = __( 'Customer #', 'computer-repair-shop' ) . $stat->customer_id;
				}
				
				$stats[] = array(
					'label' => $customer_name,
					'count' => $stat->device_count,
					'email' => $stat->user_email,
					'customer_id' => $stat->customer_id
				);
			}
		} else {
			// For customer: Show device distribution by type (if you have device type data)
			$stats = array(
				array(
					'label' => $wc_device_label,
					'count' => $total_devices,
					'icon' => 'bi-device-hdd',
					'color' => 'bg-primary'
				),
				array(
					'label' => esc_html__( 'Active', 'computer-repair-shop' ),
					'count' => $total_devices,
					'icon' => 'bi-check-circle',
					'color' => 'bg-success'
				),
				array(
					'label' => esc_html__( 'Needs Repair', 'computer-repair-shop' ),
					'count' => 0,
					'icon' => 'bi-tools',
					'color' => 'bg-warning'
				),
				array(
					'label' => esc_html__( 'Under Warranty', 'computer-repair-shop' ),
					'count' => 0,
					'icon' => 'bi-shield-check',
					'color' => 'bg-info'
				)
			);
		}
		
		// Generate stats HTML
		$stats_html = '';
		if ( ! empty( $stats ) ) {
			$stats_html .= '<div class="row g-3 mb-4">';
			foreach ( $stats as $index => $stat ) {
				$bg_color = isset( $stat['color'] ) ? $stat['color'] : 'bg-primary';
				$text_color = 'text-white';
				$icon = isset( $stat['icon'] ) ? $stat['icon'] : 'bi-device-hdd';
				
				$stats_html .= '<div class="col">';
				$stats_html .= '<div class="card stats-card ' . esc_attr( $bg_color ) . ' ' . esc_attr( $text_color ) . '">';
				$stats_html .= '<div class="card-body text-center p-3">';
				$stats_html .= '<div class="mb-2"><i class="bi ' . esc_attr( $icon ) . ' fs-1 opacity-75"></i></div>';
				$stats_html .= '<h6 class="card-title mb-1 '. esc_attr( $text_color ) .'">' . esc_html( $stat['label'] ) . '</h6>';
				$stats_html .= '<h3 class="mb-0 '. esc_attr( $text_color ) .'">' . esc_html( $stat['count'] ) . '</h3>';
				
				if ( $is_admin && isset( $stat['email'] ) ) {
					$stats_html .= '<small class="d-block mt-1 opacity-75">' . esc_html( $stat['email'] ) . '</small>';
					$view_url = add_query_arg( array( 'screen' => 'customer-devices', 'job_customer' => $stat['customer_id'] ), get_the_permalink() );
					$stats_html .= '<a href="' . esc_url( $view_url ) . '" class="stretched-link"></a>';
				}
				
				$stats_html .= '</div>';
				$stats_html .= '</div>';
				$stats_html .= '</div>';
			}
			$stats_html .= '</div>';
		}
		
		// Generate filters HTML
		$filters_html = '';
		$filters_html .= '<div class="card mb-4">';
		$filters_html .= '<div class="card-body">';
		$filters_html .= '<form method="get" action="" class="row g-3">';
		$filters_html .= '<input type="hidden" name="screen" value="customer-devices" />';
		
		// Search input
		$filters_html .= '<div class="col-md-3">';
		$filters_html .= '<div class="input-group">';
		$filters_html .= '<span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>';
		$filters_html .= '<input type="text" class="form-control border-start-0" name="device_search" id="deviceSearch" ';
		$filters_html .= 'value="' . ( isset( $_GET['device_search'] ) ? esc_attr( sanitize_text_field( $_GET['device_search'] ) ) : '' ) . '" ';
		$filters_html .= 'placeholder="' . sprintf( esc_attr__( 'Search %s...', 'computer-repair-shop' ), strtolower( $wc_device_label ) ) . '">';
		$filters_html .= '</div>';
		$filters_html .= '</div>';
		
		// Device filter
		$filters_html .= '<div class="col-md-3">';
		if ( wcrb_use_woo_as_devices() == 'YES' ) {
			$theSearchClass = ( ! empty( get_option('wcrb_special_PR_Search_class') ) ) ? get_option('wcrb_special_PR_Search_class') : 'bc-product-search';
			
			$filters_html .= '<select name="device_post_id" id="rep_devices" data-display_stock="true" data-exclude_type="variable" data-security="' . wp_create_nonce( 'search-products' ) . '" ';
			$filters_html .= 'class="' . esc_attr( $theSearchClass ) . ' form-select">';
			$filters_html .= '<option value="">' . esc_html( $sing_device_label ) . ' ' . esc_html__( '(All)', 'computer-repair-shop' ) . '</option>';
			$filters_html .= '</select>';
		} else {
			$device_post_id = isset( $_GET["device_post_id"] ) ? sanitize_text_field( $_GET["device_post_id"] ) : "";
			$optionsGenerated = wc_generate_device_options( $device_post_id );
			$filters_html .= '<select id="rep_devices" name="device_post_id" class="form-select">';
			$filters_html .= '<option value="All">' . esc_html( $sing_device_label ) . ' ' . esc_html__( '(All)', 'computer-repair-shop' ) . '</option>';
			$filters_html .= $optionsGenerated;
			$filters_html .= '</select>';
		}
		$filters_html .= '</div>';
		
		// Customer filter (admin/technician only)
		if ( $is_admin || in_array( 'technician', $user_roles ) ) {
			$filters_html .= '<div class="col-md-3">';
			$current_job_customer = isset( $_GET['job_customer'] ) ? sanitize_text_field( $_GET['job_customer'] ) : '';
			$optionsGenerated = wcrb_return_customer_select_options( $current_job_customer, 'job_customer', 'updatenone' );
			$filters_html .= $optionsGenerated;
			$filters_html .= '</div>';
		}
		
		// Action buttons
		$filters_html .= '<div class="col-md-3">';
		$filters_html .= '<div class="d-flex gap-2">';
		$filters_html .= '<a href="' . esc_url( add_query_arg( 'screen', 'customer-devices', get_the_permalink() ) ) . '" ';
		$filters_html .= 'class="btn btn-outline-secondary" id="clearDeviceFilters">';
		$filters_html .= '<i class="bi bi-arrow-clockwise"></i>';
		$filters_html .= '</a>';
		$filters_html .= '<button type="submit" class="btn btn-primary" id="applyDeviceFilters">';
		$filters_html .= '<i class="bi bi-funnel"></i> ' . esc_html__( 'Filter', 'computer-repair-shop' );
		$filters_html .= '</button>';
		$filters_html .= '</div>';
		$filters_html .= '</div>';
		
		$filters_html .= '</form>';
		$filters_html .= '</div>';
		$filters_html .= '</div>';
		
		// Get devices for current page
		$select_query = "
			SELECT 
				c.*,
				u.display_name,
				u.user_email,
				u.user_login
			FROM `{$computer_repair_customer_devices}` c
			LEFT JOIN {$wpdb->users} u ON c.customer_id = u.ID
			{$where_clause}
			ORDER BY c.`device_id` DESC
			LIMIT %d OFFSET %d
		";
		
		$query_params[] = $devices_per_page;
		$query_params[] = $offset;
		
		$select_query = $wpdb->prepare( $select_query, $query_params );
		$select_results = $wpdb->get_results( $select_query );
		
		// Generate table rows
		$rows_html = '';
		if ( empty( $select_results ) ) {
			$rows_html .= '<tr>';
			$rows_html .= '<td colspan="' . ( $is_admin ? '8' : '6' ) . '" class="text-center py-5">';
			$rows_html .= '<i class="bi bi-device-hdd display-1 text-muted"></i>';
			$rows_html .= '<h4 class="text-muted mt-3">' . sprintf( esc_html__( 'No %s found!', 'computer-repair-shop' ), strtolower( $wc_device_label ) ) . '</h4>';
			if ( $is_admin && ! isset( $_GET['job_customer'] ) ) {
				$rows_html .= '<p class="text-muted">' . esc_html__( 'Try filtering by customer or search term.', 'computer-repair-shop' ) . '</p>';
			}
			$rows_html .= '</td>';
			$rows_html .= '</tr>';
		} else {
			foreach ( $select_results as $item ) {
				$device_id = $item->device_id;
				$device_post_id = $item->device_post_id;
				$device_label = $item->device_label;
				$serial_number = $item->serial_nuumber;
				$pint_code = $item->pint_code;
				$customer_id = $item->customer_id;
				$customer_name = $item->display_name ?: __( 'Customer #', 'computer-repair-shop' ) . $customer_id;
				
				$rows_html .= '<tr>';
				$rows_html .= '<td class="ps-4"><strong>#' . esc_html( $device_id ) . '</strong></td>';
				$rows_html .= '<td><strong>' . esc_html( $device_label ) . '</strong></td>';
				$rows_html .= '<td>';
				if ( ! empty( $serial_number ) ) {
					$rows_html .= '<span class="badge bg-light text-dark border">' . esc_html( $serial_number ) . '</span>';
				} else {
					$rows_html .= '<span class="text-muted">-</span>';
				}
				$rows_html .= '</td>';
				$rows_html .= '<td>';
				if ( ! empty( $pint_code ) ) {
					$rows_html .= '<span class="badge bg-info">' . esc_html( $pint_code ) . '</span>';
				} else {
					$rows_html .= '<span class="text-muted">-</span>';
				}
				$rows_html .= '</td>';
				
				// Device details column (shows device post details if available)
				$rows_html .= '<td>';
				if ( ! empty( $device_post_id ) && get_post_status( $device_post_id ) ) {
					$device_post = get_post( $device_post_id );
					if ( $device_post ) {
						$rows_html .= '<a href="' . get_edit_post_link( $device_post_id ) . '" target="_blank" class="text-decoration-none">';
						$rows_html .= '<span class="badge bg-secondary">' . esc_html( $device_post->post_title ) . '</span>';
						$rows_html .= '</a>';
					}
				} else {
					$rows_html .= '<span class="text-muted">-</span>';
				}
				$rows_html .= '</td>';
				
				if ( $is_admin ) {
					// Customer details column
					$rows_html .= '<td>';
					if ( ! empty( $customer_id ) ) {
						$user = get_user_by( 'id', $customer_id );
						if ( $user ) {
							$phone_number = get_user_meta( $customer_id, "billing_phone", true );
							$billing_tax = get_user_meta( $customer_id, "billing_tax", true );
							$company = get_user_meta( $customer_id, "billing_company", true );
							
							$first_name = empty( $user->first_name ) ? "" : $user->first_name;
							$last_name = empty( $user->last_name ) ? "" : $user->last_name;
							$theFullName = $first_name . ' ' . $last_name;
							$email = empty( $user->user_email ) ? "" : $user->user_email;
							
							$rows_html .= '<div class="customer-info">';
							$rows_html .= '<div class="fw-medium">' . esc_html( $theFullName ) . '</div>';
							
							if ( ! empty( $phone_number ) ) {
								$rows_html .= '<small class="d-block text-muted">';
								$rows_html .= '<i class="bi bi-telephone me-1"></i>' . esc_html( $phone_number );
								$rows_html .= '</small>';
							}
							
							if ( ! empty( $email ) ) {
								$rows_html .= '<small class="d-block text-muted">';
								$rows_html .= '<i class="bi bi-envelope me-1"></i>' . esc_html( $email );
								$rows_html .= '</small>';
							}
							
							if ( ! empty( $company ) ) {
								$rows_html .= '<small class="d-block text-muted">';
								$rows_html .= '<i class="bi bi-building me-1"></i>' . esc_html( $company );
								$rows_html .= '</small>';
							}
							
							if ( ! empty( $billing_tax ) ) {
								$rows_html .= '<small class="d-block text-muted">';
								$rows_html .= '<i class="bi bi-card-text me-1"></i>' . esc_html__( 'Tax ID:', 'computer-repair-shop' ) . ' ' . esc_html( $billing_tax );
								$rows_html .= '</small>';
							}
							
							$rows_html .= '</div>';
						}
					} else {
						$rows_html .= '<span class="text-muted">-</span>';
					}
					$rows_html .= '</td>';
				}
				
				// Booking button
				$booking_btn = '';
				$_bookpageid = get_option( 'wc_rb_device_booking_page_id' );
				
				if ( ! empty( $_bookpageid ) && $_bookpageid > 0 ) {
					$booking_url = get_permalink( $_bookpageid );
					$query_args = array();
					
					if ( ! empty( $device_post_id ) ) {
						$_typeid = $this->get_device_term_id_for_post( $device_post_id, 'device_type' );
						$_brandid = $this->get_device_term_id_for_post( $device_post_id, 'device_brand' );
						
						if ( $_typeid ) $query_args['wcrb_selected_type'] = $_typeid;
						if ( $_brandid ) $query_args['wcrb_selected_brand'] = $_brandid;
						$query_args['wcrb_selected_device'] = $device_post_id;
					}
					
					if ( ! empty( $serial_number ) ) $query_args['serial_number'] = $serial_number;
					if ( ! empty( $pint_code ) ) $query_args['pincode'] = $pint_code;
					if ( ! empty( $customer_id ) ) $query_args['customer'] = $customer_id;
					
					$final_url = add_query_arg( $query_args, $booking_url );
					
					$booking_btn = '<a href="' . esc_url( $final_url ) . '" target="_blank" ';
					$booking_btn .= 'class="btn btn-sm btn-success d-inline-flex align-items-center">';
					$booking_btn .= '<i class="bi bi-tools me-1"></i>';
					$booking_btn .= esc_html__( 'Book Repair', 'computer-repair-shop' );
					$booking_btn .= '</a>';
				} else {
					$booking_btn = '<span class="badge bg-warning">' . esc_html__( 'Booking unavailable', 'computer-repair-shop' ) . '</span>';
				}
				
				$rows_html .= '<td class="text-end pe-4">';
				$rows_html .= $booking_btn;
				$rows_html .= '</td>';
				
				$rows_html .= '</tr>';
			}
		}
		
		// Generate pagination
		$pagination_html = '';
		$total_pages = ceil( $total_devices / $devices_per_page );
		
		if ( $total_pages > 1 ) {
			$showing_start = $offset + 1;
			$showing_end = min( $offset + $devices_per_page, $total_devices );
			$current_url = add_query_arg( $_GET, get_the_permalink() );
			
			$pagination_html .= '<div class="card-footer">';
			$pagination_html .= '<div class="d-flex justify-content-between align-items-center">';
			$pagination_html .= '<div class="text-muted">';
			$pagination_html .= sprintf( 
				esc_html__( 'Showing %1$s to %2$s of %3$s %4$s', 'computer-repair-shop' ),
				$showing_start,
				$showing_end,
				$total_devices,
				strtolower( $wc_device_label )
			);
			$pagination_html .= '</div>';
			
			$pagination_html .= '<nav><ul class="pagination mb-0">';
			
			// Previous button
			if ( $current_page > 1 ) {
				$prev_url = add_query_arg( 'devices_page', $current_page - 1, $current_url );
				$pagination_html .= '<li class="page-item"><a class="page-link" href="' . esc_url( $prev_url ) . '">';
				$pagination_html .= '<i class="bi bi-chevron-left"></i></a></li>';
			} else {
				$pagination_html .= '<li class="page-item disabled"><a class="page-link" href="#" tabindex="-1" aria-disabled="true">';
				$pagination_html .= '<i class="bi bi-chevron-left"></i></a></li>';
			}
			
			// Page numbers
			for ( $i = 1; $i <= $total_pages; $i++ ) {
				if ( $i == 1 || $i == $total_pages || ( $i >= $current_page - 2 && $i <= $current_page + 2 ) ) {
					$page_url = add_query_arg( 'devices_page', $i, $current_url );
					$active_class = ( $i == $current_page ) ? ' active' : '';
					$pagination_html .= '<li class="page-item' . $active_class . '">';
					$pagination_html .= '<a class="page-link" href="' . esc_url( $page_url ) . '">' . $i . '</a></li>';
				} elseif ( $i == $current_page - 3 || $i == $current_page + 3 ) {
					$pagination_html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
				}
			}
			
			// Next button
			if ( $current_page < $total_pages ) {
				$next_url = add_query_arg( 'devices_page', $current_page + 1, $current_url );
				$pagination_html .= '<li class="page-item"><a class="page-link" href="' . esc_url( $next_url ) . '">';
				$pagination_html .= '<i class="bi bi-chevron-right"></i></a></li>';
			} else {
				$pagination_html .= '<li class="page-item disabled"><a class="page-link" href="#">';
				$pagination_html .= '<i class="bi bi-chevron-right"></i></a></li>';
			}
			
			$pagination_html .= '</ul></nav>';
			$pagination_html .= '</div>';
			$pagination_html .= '</div>';
		}
		
		return array(
			'stats' => $stats_html,
			'filters' => $filters_html,
			'rows' => $rows_html,
			'pagination' => $pagination_html
		);
	}

	function list_customer_devices( $view ) {
		global $wpdb;

		if ( ! is_user_logged_in() ) {
			return;
		}
		$computer_repair_customer_devices = $wpdb->prefix . 'wc_cr_customer_devices';

		$current_user = wp_get_current_user();
		$customer_id = $current_user->ID;

		if ( in_array( 'administrator', (array) $current_user->roles ) && is_admin() ) {
			$select_query = "SELECT * FROM `{$computer_repair_customer_devices}` ORDER BY `device_id` DESC";
			$view = ( empty( $view ) ) ? 'customer' : $view;
		} else {
			$select_query = $wpdb->prepare( "SELECT * FROM `{$computer_repair_customer_devices}` WHERE `customer_id`= %d ORDER BY `device_id` DESC", $customer_id );			
			$view = ( empty( $view ) ) ? $view : 'admin';
		}
        $select_results = $wpdb->get_results( $select_query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            
        $output = ( $wpdb->num_rows == 0 ) ? esc_html__( 'There is no record available', 'computer-repair-shop' ) : '';

        foreach( $select_results as $item ) {
			$device_id 		= $item->device_id;
			$device_post_id = $item->device_post_id;
			$device_label 	= $item->device_label;
			$serial_nuumber = $item->serial_nuumber;
			$pint_code 		= $item->pint_code;
			$customerId 	= $item->customer_id;

			$output .= '<tr>';
			$output .= '<td>' . esc_html( $device_id ) . '</td>';
			$output .= '<td>' . esc_html( $device_label ) . '</td>';
			$output .= '<td>' . esc_html( $serial_nuumber ) . '</td>';
			$output .= '<td>' . esc_html( $pint_code ) . '</td>';
			if ( is_admin() ) {
				$user 		= get_user_by( 'id', $customerId );
				$first_name	= empty( $user->first_name ) ? "" : $user->first_name;
				$last_name 	= empty( $user->last_name )? "" : $user->last_name;
				$cust_name  =  $first_name. ' ' .$last_name ;

				$output .= '<td>' . esc_html( $cust_name ) . '</td>';
			}

			$_bookpageid = get_option( 'wc_rb_device_booking_page_id' );

			$_pagetab = '';
			if ( empty( $_bookpageid ) || $_bookpageid < 1 ) {
				$wc_booking_on_account_page_status = get_option( 'wc_booking_on_account_page_status' );
				if ( $wc_booking_on_account_page_status != 'on' ) {
					$_bookpageid = get_option( 'wc_rb_my_account_page_id' );
					$_pagetab = 'myaccountbooking';
				}
			}

			$_setbooking = '';
			if ( empty( $_bookpageid ) || $_bookpageid < 1 ) {
				$_setbooking = 'data-alert-msg="' . esc_html__( 'From settings go to pages setup and set the booking page for this function to work. From administrator panel. ', 'computer-repair-shop' ) . '"';
			}

			// Get the booking page permalink
			$booking_url = get_permalink( $_bookpageid );

			// Prepare query parameters
			$query_args = array();

			if ( ! empty( $device_post_id ) ) {
				// Get device type and brand IDs
				$_typeid = $this->get_device_term_id_for_post($device_post_id, 'device_type');
				$_brandid = $this->get_device_term_id_for_post($device_post_id, 'device_brand');
				
				if ( $_typeid ) {
					$query_args['wcrb_selected_type'] = $_typeid;
				}
				if ( $_brandid ) {
					$query_args['wcrb_selected_brand'] = $_brandid;
				}
				$query_args['wcrb_selected_device'] = $device_post_id;
			}

			if ( ! empty( $serial_nuumber ) ) {
				$query_args['serial_number'] = $serial_nuumber;
			}
			if ( ! empty( $pint_code ) ) {
				$query_args['pincode'] = $pint_code;  // Note: corrected variable name
			}
			if ( ! empty( $customerId ) ) {
				$query_args['customer'] = $customerId;
			}
			if ( $_pagetab == 'myaccountbooking' ) {
				$query_args['book_device'] = 'yes'; // Add the tab parameter if needed
			}

			// Build the final URL with parameters
			$final_url = add_query_arg( $query_args, $booking_url );

			$bookingBtn = '<a '. wp_kses_post( $_setbooking ) .' target="_blank" href="'. esc_url( $final_url ) .'" 
			class="button button-primary">' . esc_html__( 'Book Repair', 'computer-repair-shop' ) . '</a>';
			$output .= '<td class="actions">' . wp_kses_post( $bookingBtn ) . '</td>';
			$output .= '</tr>';
		}
		return $output;
	}

	function backend_customer_devices_output() {
		global $wpdb;

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'computer-repair-shop' ) );
		}

		$wc_device_label 		 = ( empty( get_option( 'wc_device_label_plural' ) ) ) ? esc_html__( 'Devices', 'computer-repair-shop' ) : get_option( 'wc_device_label_plural' );
		$sing_device_label  	 = ( empty( get_option( 'wc_device_label' ) ) ) ? esc_html__( 'Device', 'computer-repair-shop' ) : get_option( 'wc_device_label' );
		$wc_device_id_imei_label = ( empty( get_option( 'wc_device_id_imei_label' ) ) ) ? esc_html__( 'ID/IMEI', 'computer-repair-shop' ) : get_option( 'wc_device_id_imei_label' );
		$wc_pin_code_label 		 = ( empty( get_option( 'wc_pin_code_label' ) ) ) ? esc_html__( 'Pin Code/Password', 'computer-repair-shop' ) : get_option( 'wc_pin_code_label' );
	?>
		<div class="wrap" id="poststuff">
			<h1 class="wp-heading-inline"><?php echo esc_html__( "Customer", "computer-repair-shop" ) . ' ' . esc_html( $wc_device_label ); ?></h1>
			
			<table class="wp-list-table widefat fixed striped users">
			<thead><tr>
				<th class="manage-column column-id">
					<span><?php echo esc_html__( 'ID', 'computer-repair-shop' ); ?></span>
				</th>
				<th class="manage-column column-name">
					<span><?php echo esc_html( $sing_device_label ); ?></span>
				</th>
				<th class="manage-column column-email">
					<span><?php echo esc_html( $wc_device_id_imei_label ); ?></span>
				</th>
				<th class="manage-column column-phone">
					<?php echo esc_html( $wc_pin_code_label ); ?>
				</th>
				<th class="manage-column column-address">
					<?php echo esc_html__( 'Customer', 'computer-repair-shop' ); ?>
				</th>
				<th class="manage-column column-actions">
					<?php echo esc_html__( 'Actions', 'computer-repair-shop' ); ?>
				</th>
			</tr></thead>
			<tbody data-wp-lists="list:user">
				<?php 
					$output = $this->list_customer_devices( 'admin' ); 
					$allowedHTML = wc_return_allowed_tags();
					echo wp_kses( $output, $allowedHTML );
				?>
			</tbody>
			<tfoot><tr>
				<th class="manage-column column-id">
					<span><?php echo esc_html__( 'ID', 'computer-repair-shop' ); ?></span>
				</th>
				<th class="manage-column column-name">
					<span><?php echo esc_html( $sing_device_label ); ?></span>
				</th>
				<th class="manage-column column-email">
					<span><?php echo esc_html( $wc_device_id_imei_label ); ?></span>
				</th>
				<th class="manage-column column-phone">
					<?php echo esc_html( $wc_pin_code_label ); ?>
				</th>
				<th class="manage-column column-address">
					<?php echo esc_html__( 'Customer', 'computer-repair-shop' ); ?>
				</th>
				<th class="manage-column column-actions">
					<?php echo esc_html__( 'Actions', 'computer-repair-shop' ); ?>
				</th>
			</tr></tfoot>
			</table>
		</div> <!-- Wrap Ends /-->
		<?php
	}

	function add_customer_device( $device_post_id, $imei_serial, $device_pincode, $customer_id ) {
		global $wpdb;

		if ( empty( $device_post_id ) || empty( $customer_id ) ) {
			return;
		}

		$computer_repair_customer_devices = $wpdb->prefix.'wc_cr_customer_devices';
		$wc_meta_value	 = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$computer_repair_customer_devices} WHERE `customer_id` = %d AND `device_post_id` = %s AND `serial_nuumber` = %s", $customer_id, $device_post_id, $imei_serial ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( ! empty( $wc_meta_value ) ) {
			return;
		}
		$device_label = return_device_label( $device_post_id );
		$insert_query = "INSERT INTO 
						`" . $computer_repair_customer_devices . "` 
					VALUES
						(NULL, %d, %s, %s, %s, %d)";
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prepare($insert_query, array( $device_post_id, $device_label, $imei_serial, $device_pincode, $customer_id ))
		);
		$history_id = $wpdb->insert_id;
	}

	function return_extra_device_input_fields( $type ) {
		$type 			 = ( empty( $type ) ) ? 'backend' : $type;
		$container_class = ( $type == 'backend' ) ? 'cell medium-2_5 small-6' : 'device-item-b';

		$extra_fields_array = get_option( '_extra_device_fields' );
		$extrafields = '';

		if ( ! wc_rs_license_state() ) :
			return;
		endif;

		$extra_fields_arr = '';
		$counter = 0;

		if ( ! empty( $extra_fields_array ) && is_array( $extra_fields_array ) ) {
			foreach( $extra_fields_array as $field_array ) {
				$rb_device_field_id 				  = ( isset( $field_array['rb_device_field_id'] ) ) ? $field_array['rb_device_field_id'] : '';
				$rb_device_field_label 				  = ( isset( $field_array['rb_device_field_label'] ) ) ? $field_array['rb_device_field_label'] : '';
				$rb_device_field_type 				  = ( isset( $field_array['rb_device_field_type'] ) ) ? $field_array['rb_device_field_type'] : '';
				$rb_device_field_display_booking_form = ( isset( $field_array['rb_device_field_display_booking_form'] ) ) ? $field_array['rb_device_field_display_booking_form'] : '';
				$rb_device_field_display_in_invoice   = ( isset( $field_array['rb_device_field_display_in_invoice'] ) ) ? $field_array['rb_device_field_display_in_invoice'] : '';
				$rb_device_field_display_for_customer = ( isset( $field_array['rb_device_field_display_for_customer'] ) ) ? $field_array['rb_device_field_display_for_customer'] : '';

				if ( $rb_device_field_display_booking_form == 'yes' || $type == 'backend' ) :
					$extra_fields_arr .= ( $counter > 0 ) ? '|' : '';
					$extrafields .= '<div class="'. esc_attr( $container_class ) .'">';
					$extrafields .= '<label for="'. esc_attr( $rb_device_field_id ) .'">';
					$extrafields .= ( $type != 'backend' ) ? esc_html( $rb_device_field_label ) : '';
					
					if ( $type == 'frontend' ) {
						$extrafields .= '<input type="text" id="'. esc_attr( $rb_device_field_id ) .'" name="'. esc_attr( $rb_device_field_id ) .'_html[]" value="" />';
					} else {
						$extrafields .= '<input type="text" id="'. esc_attr( $rb_device_field_id ) .'" name="'. esc_attr( $rb_device_field_id ) .'_html" placeholder="'. esc_html( $rb_device_field_label ) .'" value="" />';
					}
					
					$extrafields .= '</label>';
					$extrafields .= '</div>';

					$extra_fields_arr .= $rb_device_field_id;
					$counter++;
				endif;
			}
		}

		$extra_fields_ident = ( ! empty( $extra_fields_arr ) ) ? '<input type="hidden" id="extrafields_identifier" name="extra_fields_identifier" value="'. esc_html( $extra_fields_arr ) .'" />' : '';

		return $extrafields . $extra_fields_ident;
	}

	function wc_return_job_devices( $job_id, $return_type) {

		if ( empty( $job_id ) ) {
			return;
		}
		
		$return_type = ( empty( $return_type ) ) ? 'job_html' : $return_type;

		$wc_device_data = get_post_meta( $job_id, '_wc_device_data', true );

		if ( empty( $wc_device_data ) ) {
			wc_set_new_device_format( $job_id );
			$wc_device_data = get_post_meta( $job_id, '_wc_device_data', true );
		}

		$wc_pin_code_field       = get_option( 'wc_pin_code_field' );
		$wc_device_label         = ( empty( get_option( 'wc_device_label' ) ) ) ? esc_html__( 'Device', 'computer-repair-shop' ) : get_option( 'wc_device_label' );
		$wc_device_id_imei_label = ( empty( get_option( 'wc_device_id_imei_label' ) ) ) ? esc_html__( 'ID/IMEI', 'computer-repair-shop' ) : get_option( 'wc_device_id_imei_label' );

		$content = '<table class="grey-bg wc_table"><thead><tr>';

		$content .= '<th>' . $wc_device_label . '</th>';
		$content .= '<th>' . $wc_device_id_imei_label . '</th>';

		if ( $wc_pin_code_field == 'on' ):
			$wc_pin_code_label	  = ( empty( get_option( 'wc_pin_code_label' ) ) ) ? esc_html__( 'Pin Code/Password', 'computer-repair-shop' ) : get_option( 'wc_pin_code_label' );
			$content .= '<th>' . esc_html( $wc_pin_code_label ) . '</th>';
		endif;
		$wc_note_label = ( empty( get_option( 'wc_note_label' ) ) ) ? esc_html__( 'Note', 'computer-repair-shop' ) : get_option( 'wc_note_label' );
		$content .= '<th>' . esc_html( $wc_note_label ) . '</th>';

		$content .= $this->return_extra_devices_fields( 'heads', '', '' );

		$content .= '</tr></thead>';
		$content .= '<tbody class="devices_body">';

		if ( is_array( $wc_device_data ) && !empty( $wc_device_data ) ) :
			foreach ( $wc_device_data as $device_data ) :

				$deive_note     = ( isset( $device_data['device_note'] ) ) ? $device_data['device_note'] : '';
				$device_post_id = ( isset( $device_data['device_post_id'] ) ) ? $device_data['device_post_id'] : '';
				$device_id      = ( isset( $device_data['device_id'] ) ) ? $device_data['device_id'] : '';

				$content .= '<tr class="item-row wc_devices_row">';

				$content .= '<td class="wc_device_name"><a class="delme delmewedit" dt_brand_device="'. esc_html( $device_post_id ) .'" href="#" title="Remove row"><span class="dashicons dashicons-trash"></span></a><a class="editme editmedevice" href="#" title="Edit row"><span class="dashicons dashicons-edit"></span></a>';
				$device_label = return_device_label( $device_post_id );
				$content .= $device_label;
				$content .= '<input type="hidden" name="device_post_name_html[]" value="' . $device_label . '">';
				$content .= '<input type="hidden" name="device_post_id_html[]" value="' . $device_post_id . '">';
				$content .= '</td>';

				$content .= '<td class="wc_device_serial">';
				$content .= $device_id;
				$content .= '<input type="hidden" name="device_serial_id_html[]" value="' . $device_id . '">';
				$content .= '</td>';

				if ( $wc_pin_code_field == 'on' ):
					$content .= '<td class="wc_device_pin">';
					$device_login = ( isset( $device_data['device_login'] ) ) ? $device_data['device_login'] : '';
					$content .= esc_html( $device_login );
					$content .= '<input type="hidden" name="device_login_html[]" value="' . esc_html( $device_login ) . '">';
					$content .= '</td>';
				endif;

				$content .= '<td class="wc_device_note">';
				$content .= $deive_note;
				$content .= '<input type="hidden" name="device_note_html[]" value="' . $deive_note . '">';
				$content .= '</td>';

				$body_arr = $this->return_extra_devices_fields( 'body', '', '' );

				if ( is_array( $body_arr ) ) {
					foreach( $body_arr as $body_item ) {
						$content .= '<td>';
						$item_data = ( isset( $device_data[$body_item] ) ) ? $device_data[$body_item] : '';
						$content .= esc_html( $item_data );
						$content .= '<input type="hidden" name="'. esc_attr( $body_item ) .'_html[]" value="' . esc_html( $item_data ) . '">';
						$content .= '</td>';
					}
				}

				$content .= '</tr>';

			endforeach;
		endif;

		$content .= '</tbody>';
		$content .= '</table>';
		return $content;
	}
	
	function return_extra_devices_fields( $type, $in_invoice, $to_customer ) {
		if ( empty( $type ) ) {
			return;
		}
		if ( ! wc_rs_license_state() ) {
			return;
		}

		$extra_fields_array = get_option( '_extra_device_fields' );
		$return_type = ( ! empty( $type ) ) ? $type : 'heads';

		$return_fields_arr = array();
		$return_heads  = '';

		if ( ! empty( $extra_fields_array ) && is_array( $extra_fields_array ) ) {
			foreach( $extra_fields_array as $field_array ) {
				$rb_device_field_id 				  = ( isset( $field_array['rb_device_field_id'] ) ) ? $field_array['rb_device_field_id'] : '';
				$rb_device_field_label 				  = ( isset( $field_array['rb_device_field_label'] ) ) ? $field_array['rb_device_field_label'] : '';
				$rb_device_field_type 				  = ( isset( $field_array['rb_device_field_type'] ) ) ? $field_array['rb_device_field_type'] : '';
				$rb_device_field_display_booking_form = ( isset( $field_array['rb_device_field_display_booking_form'] ) ) ? $field_array['rb_device_field_display_booking_form'] : '';

				$rb_device_field_display_in_invoice   = ( isset( $field_array['rb_device_field_display_in_invoice'] ) ) ? $field_array['rb_device_field_display_in_invoice'] : '';
				$rb_device_field_display_for_customer = ( isset( $field_array['rb_device_field_display_for_customer'] ) ) ? $field_array['rb_device_field_display_for_customer'] : '';

				$return = 'YES';
				if ( ! empty( $to_customer ) && $to_customer == 'YES' ) {
					$return = 'YES';
					//Include items says YES
					if ( $rb_device_field_display_for_customer == 'no' ) {
						$return = 'NO';
					}
				}
				if ( ! empty( $in_invoice ) && $in_invoice == 'YES' ) {
					$return = 'YES';
					//Include items says YES
					if ( $rb_device_field_display_in_invoice == 'no' ) {
						$return = 'NO';
					}
				}

				if ( $return == 'YES' ) {
					$return_heads .= '<th>' . esc_html( $rb_device_field_label ) . '</th>';
					$return_fields_arr[] = $rb_device_field_id;
				}
			}
		}
		return ( $return_type == 'heads' ) ? $return_heads : $return_fields_arr;
	}

	function wc_add_device_row() {
		$content = '';

		$wc_pin_code_field     = get_option( 'wc_pin_code_field' );

		$device_post_id_html   = ( isset( $_POST['device_post_id_html'] ) && !empty( $_POST['device_post_id_html'] ) ) ? sanitize_text_field( $_POST['device_post_id_html'] ) : '';
		$device_serial_id_html = ( isset( $_POST['device_serial_id_html'] ) && !empty( $_POST['device_serial_id_html'] ) ) ? sanitize_text_field( $_POST['device_serial_id_html'] ) : '';
		$device_login_html     = ( isset( $_POST['device_login_html'] ) && !empty( $_POST['device_login_html'] ) ) ? sanitize_text_field( $_POST['device_login_html'] ) : '';
		$device_note_html      = ( isset( $_POST['device_note_html'] ) && !empty( $_POST['device_note_html'] ) ) ? sanitize_text_field( $_POST['device_note_html'] ) : ''; 		
		
		if ( ! empty( $device_post_id_html ) || ! empty( $device_serial_id_html ) ) {

			$content .= '<tr class="item-row wc_devices_row">';

			$content .= '<td class="wc_device_name"><a class="delme delmewedit" dt_brand_device="'. esc_html( $device_post_id_html ) .'" href="#" title="Remove row"><span class="dashicons dashicons-trash"></span></a>
			<a class="editme editmedevice" href="#" title="Edit row"><span class="dashicons dashicons-edit"></span></a>';
			$device_label = return_device_label( $device_post_id_html );
			$content .= $device_label;
			$content .= '<input type="hidden" name="device_post_name_html[]" value="' . $device_label . '">';
			$content .= '<input type="hidden" name="device_post_id_html[]" value="' . $device_post_id_html . '">';
			$content .= '</td>';

			$content .= '<td class="wc_device_serial">';
			$content .= $device_serial_id_html;
			$content .= '<input type="hidden" name="device_serial_id_html[]" value="' . $device_serial_id_html . '">';
			$content .= '</td>';

			if ( $wc_pin_code_field == 'on' ):
				$content .= '<td class="wc_device_pin">';
				$content .= $device_login_html;
				$content .= '<input type="hidden" name="device_login_html[]" value="' . $device_login_html . '">';
				$content .= '</td>';
			endif;

			$content .= '<td class="wc_device_note">';
			$content .= $device_note_html;
			$content .= '<input type="hidden" name="device_note_html[]" value="' . $device_note_html . '">';
			$content .= '</td>';

			$body_arr = $this->return_extra_devices_fields( 'body', '', '' );

			if ( is_array( $body_arr ) ) {
				foreach( $body_arr as $body_item ) {
					$content .= '<td>';
					$item_data = ( isset( $_POST[$body_item.'_html'] ) ) ? sanitize_text_field( $_POST[$body_item.'_html'] ) : '';
					$content .= esc_html( $item_data );
					$content .= '<input type="hidden" name="'. esc_attr( $body_item ) .'_html[]" value="' . esc_html( $item_data ) . '">';
					$content .= '</td>';
				}
			}
			$content .= '</tr>';
			$values['row'] = $content;
		}
		wp_send_json( $values );
		wp_die();
	}

	function add_device_based_parts_dropdown() {
		global $WCRB_PARTS;

		$values = array();
		$values['partsdropdown'] = '';

		$device_post_id_html = ( isset( $_POST['device_post_id_html'] ) && !empty( $_POST['device_post_id_html'] ) ) ? sanitize_text_field( $_POST['device_post_id_html'] ) : '';

		if ( ! empty( $device_post_id_html ) ) {
			$dropdownout  = '<div class="cell small-4 device_id'. $device_post_id_html  .'">';
			$dropdownout .= '<label class="reloadPartsData">';
			$dropdownout .= $WCRB_PARTS->add_parts_dropdown_by_device( $device_post_id_html );
			$dropdownout .= '</label>';
			$dropdownout .= '</div>'; //Column Ends

			$values['partsdropdown'] = $dropdownout;
		}

		wp_send_json( $values );
		wp_die();
	}

	/**
	 * Get first term ID assigned to a device post for a taxonomy.
	 * 
	 * @param int    $post_id  Device post ID
	 * @param string $taxonomy 'device_brand' or 'device_type'
	 * @return int|false       Term ID or false if no terms
	 */
	function get_device_term_id_for_post( $post_id, $taxonomy ) {
		$terms = get_the_terms($post_id, $taxonomy);
		
		if (is_wp_error($terms) || empty($terms)) {
			return false;
		}
		
		// Return first term's ID
		return $terms[0]->term_id;
	}
}