<?php
/**
 * This file handles the functions related to Reviews
 *
 * Help setup pages to they can be used in notifications and other items
 *
 * @package computer-repair-shop
 * @version 3.7947
 */

defined( 'ABSPATH' ) || exit;

class WCRB_REPAIR_TICKET {

	private static $instance = NULL;

	static public function getInstance() {
		if ( self::$instance === NULL )
			self::$instance = new WCRB_REPAIR_TICKET();
		return self::$instance;
	}

	private $TABID = "wcrb_repair_ticket_tab";

	function print_repair_ticket( $order_id ) {
		if ( empty( $order_id ) ) {
			return;
		}

		//Let's do magic.
		$customer_id 	= get_post_meta( $order_id, '_customer', true );
		$user 			= ( ! empty( $customer_id ) ) ? get_user_by( 'id', $customer_id ) : false;
		$user_email 	= ( $user ) ? $user->user_email : '';
		
		$customer_phone  	= get_user_meta( $customer_id, 'billing_phone', true );
		$customer_address 	= get_user_meta( $customer_id, 'billing_address_1', true );
		$customer_city 		= get_user_meta( $customer_id, 'billing_city', true );
		$customer_zip		= get_user_meta( $customer_id, 'billing_postcode', true );
		$customer_company	= get_user_meta( $customer_id, 'billing_company', true );
		$billing_tax  	    = get_user_meta( $customer_id, 'billing_tax', true );

		$current_devices         = get_post_meta( $order_id, '_wc_device_data', true );
		$wc_device_label         = ( empty( get_option( 'wc_device_label' ) ) ) ? esc_html__( 'Device', 'computer-repair-shop' ) : get_option( 'wc_device_label' );
		$wc_device_id_imei_label = ( empty( get_option( 'wc_device_id_imei_label' ) ) ) ? esc_html__( 'ID/IMEI', 'computer-repair-shop' ) : get_option( 'wc_device_id_imei_label' );

		$wc_repair_order_print_size = get_option( 'wc_repair_order_print_size' );
        $ticket_class = ( $wc_repair_order_print_size == 'a5' || $wc_repair_order_print_size == 'a4') ? 'ticket a5 titlelogo' : 'ticket titlelogo';

		$content = '';
		$content .= '<div id="invoice-box" class="invoice-box ticket-box"><div class="' . esc_attr( $ticket_class ) . '">';

        $content .= wc_rb_return_logo_url_with_img( 'company_logo' );
		$content .= '<div class="wcrb_ticket_head">' . esc_html__( 'Repair Ticket', 'computer-repair-shop' ) . '</div>';
		
		$jobs_manager = WCRB_JOBS_MANAGER::getInstance();
		$job_data 	  = $jobs_manager->get_job_display_data( $order_id );
		$_job_id  	  = ( ! empty( $job_data['formatted_job_number'] ) ) ? $job_data['formatted_job_number'] : $order_id;

		$content .= '<table><tbody>
                    <tr>
                        <td class="description"><strong>' . esc_html__( 'Order', 'computer-repair-shop' ) . ' #</strong></td>
                        <td class="price">' . esc_html( $_job_id ) . '</td>
                    </tr>

                    <tr>
                        <td class="description"><strong>' . esc_html__( 'Customer', 'computer-repair-shop' ) . ':</strong></td>
                        <td class="price">' . get_post_meta( $order_id, '_customer_label', true ).'</td>
                    </tr>';

			if ( ! empty( $customer_phone ) ) {
				$content .= "<tr>";
				$content .= "<td><strong>".esc_html__("Phone", "computer-repair-shop")." :</strong></td><td>".$customer_phone;	
				$content .= "</td></tr>";
			}

			if ( get_option("wc_rb_cr_display_add_on_ro_cu") == 'on' ) {
				if ( ! empty( $user_email ) ) {
					$content .= "<tr>";
					$content .= "<td colspan='2'><strong>" . esc_html__( "Email", "computer-repair-shop" ) . " :</strong> ".$user_email;	
					$content .= "</td></tr>";
				}
				if ( ! empty( $billing_tax ) ) {
					$content .= "<tr>";
					$content .= "<td>";
					$content .= '<strong>'. esc_html__( 'Tax ID', 'computer-repair-shop' ) .'</strong> : </td><td>' . $billing_tax . '</td></tr>';
				}
				if ( ! empty( $customer_company ) || ! empty( $customer_zip ) || ! empty( $customer_city ) || ! empty( $customer_address ) ) {
					$content .= "<tr>";
					$content .= "<td colspan='2'>";
					
					$content .= "<strong>".esc_html__("Address", "computer-repair-shop")." :</strong> ";

					$content .= !empty( $customer_company ) ? $customer_company.", " : " ";
			
					$content .= !empty($customer_address) ? $customer_address.", " : " ";
					$content .= !empty($customer_city) ? $customer_city.", " : " ";
					$content .= !empty($customer_zip) ? $customer_zip : " ";
					$content .= "</td></tr>";
				}
			}

			$device_id 	        = get_post_meta( $order_id, "_device_id", true );
			$device_post_number = get_post_meta( $order_id, "_device_post_id", true );

			if(!empty($device_post_number)):
				$content .= '<tr>
								<td colspan="2"><strong>' . $wc_device_label . ':</strong> <br>
								' . return_device_label( $device_post_number ) . '</td>
							</tr>';
			endif;            

			if ( ! empty( $current_devices )  && is_array( $current_devices ) ) {
				$counter = 0;
				$content .= '<tr><td colspan="2">';
				foreach( $current_devices as $device_data ) {
					$content .= ( $counter != 0 ) ? '<br>' : '<strong>' . $wc_device_label . ' ' . $wc_device_id_imei_label . ' :</strong><br>';				
					$device_post_id = ( isset( $device_data['device_post_id'] ) ) ? $device_data['device_post_id'] : '';
					$device_id      = ( isset( $device_data['device_id'] ) ) ? $device_data['device_id'] : '';
	
					$content .= return_device_label( $device_post_id );
					$content .= ( ! empty( $device_id ) ) ? ' (' . esc_html( $device_id ) . ')' : '';
					$counter++;
				}
				$content .= '</td></tr>';
			}

			$theGrandTotal = wc_order_grand_total( $order_id, 'grand_total' );

			$content .= '<tr>
							<td><strong>'.esc_html__("Cost", "computer-repair-shop").':</strong></td>
							<td class="price">' . wc_cr_currency_format( $theGrandTotal ) . '</td>
						</tr>';

			$case_detail = get_post_meta( $order_id, "_case_detail", true );
                    
			if ( ! empty( $case_detail ) ) :
				$content .= '<tr>
							<td><strong>' . esc_html__( "Problem", "computer-repair-shop" ) . ':</strong></td>
							<td>' . esc_html( $case_detail ) . '</td>
							</tr>';                                
			endif;

			$recieved = wc_order_grand_total( $order_id, 'received' );

			$content .= '<tr><td><strong>'. esc_html__( 'Deposit', 'computer-repair-shop' ) .'</strong></td><td>'. esc_html( wc_cr_currency_format( $recieved ) ) .'</td></tr>';
			$content .= '</tbody></table>';

			$wc_rb_business_name = wp_unslash( get_option( 'wc_rb_business_name' ) );
			$wc_rb_business_name = ( empty( $wc_rb_business_name ) ) ? wp_unslash( get_bloginfo( 'name' ) ) : $wc_rb_business_name;

			$content .= '<div class="wcrb_ticket_head">' . esc_html( wp_unslash( $wc_rb_business_name ) ) . '</div>';

			if ( get_option( 'wc_rb_cr_display_add_on_ro' ) == 'on' ) {
                $wc_rb_business_phone	= get_option( 'wc_rb_business_phone' );
                $wc_rb_business_address	= get_option( 'wc_rb_business_address' );

                $computer_repair_email = get_option( 'computer_repair_email' );

                if ( empty( $computer_repair_email ) ) {
                    $computer_repair_email	= get_option( 'admin_email' );	
                }
                $_store_id = get_post_meta( $order_id, '_store_id', true );
                if ( ! empty( $_store_id ) ) {
                    $store_address = get_post_meta( $_store_id, '_store_address', true );
                    $store_email   = get_post_meta( $_store_id, '_store_email', true );

                    $wc_rb_business_address = ( ! empty( $store_address ) ) ? $store_address : $wc_rb_business_address;
                    $computer_repair_email = ( ! empty( $store_email ) ) ? $store_email : $computer_repair_email;
                }

                $content .= '<div class="company_info repair_order_invoice text-center">';
                $content .= "<div class='address-side'>";
                $content .= "<p>";
                $content .= $wc_rb_business_address;
                $content .= ( ! empty( $computer_repair_email ) ) ? "<br>".$computer_repair_email : "";
                $content .= ( ! empty( $wc_rb_business_phone ) ) ? "<br>".$wc_rb_business_phone : "";
                $content .= "</p></div>";
                $content .= '</div>';
            }

			$content .= '<table><tbody>';
			$content .= '<tr>
							<td><strong>' . wcrb_get_label( 'casenumber', 'first' ) . ':</strong></td>
							<td>' . esc_html( get_post_meta( $order_id, '_case_number', true ) ) . '</td>
							</tr>';

			$status_check_link = wc_rb_return_status_check_link( $order_id );
			if ( empty( $status_check_link ) ) {
				$content .= '<tr>
							<td class="centered" colspan="2">
								<div class="qr_Code_label__"> Please create a page for status check with shortcode of status check, and set that page from RepairBuddy Settings.</div>
							</td>
						</td>';
			} else {
			$content .= '<tr>
							<td class="centered" colspan="2">
								<img src="https://quickchart.io/qr?text='. esc_html( rawurlencode( $status_check_link ) ) .'&size=177" title="'.esc_html__("Scan to read policy", "computer-repair-shop").'" />
								<div class="qr_Code_label__"> ' . esc_html__( 'Scan to check status', 'computer-repair-shop' ) . '</div>
							</td>
						</td>';
			}
			$content .= '</tbody></table>';


            $content .= '<p class="centered">';
            $content .= ( ! empty( get_option( 'wc_rb_ro_thanks_msg' ) ) ) ? wp_unslash( get_option( 'wc_rb_ro_thanks_msg' ) ) : esc_html__("Thanks for your business!", "computer-repair-shop");
            $content .= '</p>';
		
		$content .= '</div><button id="btnPrint" class="hidden-print button button-primary">'.esc_html__("Print", "computer-repair-shop").'</button>';	
		$content .= '</div>';



		return $content;
	}

}