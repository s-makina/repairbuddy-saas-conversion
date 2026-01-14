<?php
defined( 'ABSPATH' ) || exit;
/***
 * Repair Print Functinoality
 * Properly prints the reports
 *
 * @package computer repair shop
 */

function wc_computer_repair_print_functionality( $return = false ) {
	if ( ! current_user_can( 'read' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'computer-repair-shop' ) );
	}
	if ( ! is_admin() ) {
		wp_enqueue_script("foundation-js");
		wp_enqueue_script("wc-cr-js");
	}
	$allowedHTML = wc_return_allowed_tags();

	if ( isset( $_GET["order_id"] ) && ! empty( $_GET["order_id"] ) ) {
		$the_order_id = ( isset( $_GET["order_id"] ) ) ? sanitize_text_field( $_GET["order_id"] ) : '';

		if ( isset( $_GET["email_customer"] ) && !empty( $_GET["email_customer"] ) ) {
			wc_cr_send_customer_update_email( $the_order_id );
			echo '<h2>' . esc_html__( 'Email have been sent to the customer.', 'computer-repair-shop' ) . '</h2>';
		}

		if ( isset( $_GET["print_type"] ) && $_GET["print_type"] == "repair_order" ) {
			$repair_order_type = get_option( 'repair_order_type' );

			if ( $repair_order_type == 'invoice_type' ) {
				$generatedHTML 	= wcrb_print_large_work_order( $the_order_id, 'print' );
				echo wp_kses( $generatedHTML, $allowedHTML );
			} else {
				//Repair Order to print.
				$generatedHTML 	= wc_print_repair_order( $the_order_id );
				echo wp_kses( $generatedHTML, $allowedHTML );
			}
		} elseif( isset( $_GET['print_type'] ) && $_GET['print_type'] == 'repair_ticket' ) {
			//Repair Order to print.
			$WCRB_REPAIR_TICKET_OBJ = WCRB_REPAIR_TICKET::getInstance();
			$generatedHTML 	= $WCRB_REPAIR_TICKET_OBJ->print_repair_ticket( $the_order_id );
			echo wp_kses( $generatedHTML, $allowedHTML );
		} elseif ( isset( $_GET["print_type"] ) && $_GET["print_type"] == "repair_label" ) {
			//Repair label to print.
			$generatedHTML 	= wc_print_repair_label( $the_order_id );
			echo wp_kses( $generatedHTML, $allowedHTML );
		} else {
			//Let's call or Print our order Invoice Here.
			$generatedHTML 	= wc_print_order_invoice( $the_order_id, 'print' );
			if ( isset( $_GET['dl_pdf'] ) && $_GET['dl_pdf'] == 'yes' ) {
				$WCRB_PDF_MAKER = WCRB_PDF_MAKER::getInstance();
				$WCRB_PDF_MAKER->generate_repair_estimate_invoice( $the_order_id, '' );
			}
			if ( $return == TRUE ) {
				return wp_kses( $generatedHTML, $allowedHTML );
			} else {
				echo wp_kses( $generatedHTML, $allowedHTML );
			}
		}
	}

	if ( isset( $_GET['payment_id'] ) && ! empty( $_GET['payment_id'] ) ) {
		$_payment_id = sanitize_text_field( $_GET['payment_id'] );
		
		if ( isset( $_GET["print_reciept"] ) && $_GET["print_reciept"] == "reciept_print" ) {
			//Repair Order to print.
			if ( isset( $_REQUEST['reciept_security_p'] ) && ! empty( $_REQUEST['reciept_security_p'] ) && wp_verify_nonce( sanitize_text_field( $_REQUEST['reciept_security_p'] ), 'reciept_security' ) ) {
				global $PAYMENT_STATUS_OBJ;
				$generatedHTML 	= $PAYMENT_STATUS_OBJ->print_reciept( $_payment_id );
				
				if ( isset( $_GET['email'] ) && $_GET['email'] == 'reciept_email' ) {
					
					if ( isset( $_GET['job_id'] ) && ! empty( $_GET['job_id'] ) ) {
						$job_id = sanitize_text_field( $_GET['job_id'] );

						$customer_id = get_post_meta( $job_id, '_customer', true );
						if ( empty( $customer_id ) ) {
							$message = esc_html__( "Something is wrong with your submission!", "computer-repair-shop" ) . 'ER4';	
						} else {
							$user       = get_user_by( 'id', $customer_id );
							$user_email =  !empty( $user ) ? $user->user_email : '';

							if ( ! empty( $user_email ) ) {
								$subject = esc_html__( 'Thank you for your payment', 'computer-repair-shop' );
								$emailBody = wp_kses( $generatedHTML, $allowedHTML );

								global $WCRB_EMAILS;
								$WCRB_EMAILS->send_email( $user_email, $subject, $emailBody, '' );

								$message = esc_html__( 'Email sent', 'computer-repair-shop' );
							} else {
								$message = esc_html__( "Something is wrong with your submission!", "computer-repair-shop" ) . 'ER5';		
							}
						}
					} else {
						$message = esc_html__( "Something is wrong with your submission!", "computer-repair-shop" ) . 'ER2';
					}
				
					echo wp_kses( $generatedHTML, $allowedHTML ) . '<h2>' . esc_html( $message ) . '</h2>';
				} else {
					echo wp_kses( $generatedHTML, $allowedHTML ) . '<div style="max-width:800px;margin:auto;padding:15px;"><button id="btnPrint" class="hidden-print button button-primary wcrb_ml-5">Print</button></div>';
				}
			} else {
				echo esc_html__( "Something is wrong with your submission!", "computer-repair-shop" ) . 'ER1';
			}
		}
	}

	/**
	 * Reports of different types
	 */
	if ( isset( $_GET["print_reports"] ) && ! empty( $_GET["print_reports"] ) ) {
		//Daily Sales Summary
		if(isset($_GET["report_type"]) && $_GET["report_type"] == "daily_sales_summary") {
			if(!isset($_GET["start_date"]) && !isset($_GET["end_date"])) {
				print_report_criteria_select_form("date_range", "selected_today");
			} else {
				$argus = array_map( 'sanitize_text_field', $_GET );
				if ( ! wc_rs_license_state() ) :
					echo '<div class="callout success">'. esc_html__( 'Pro feature! Plugin activation required.', 'computer-repair-shop' ) .'</div>';
				else :
					wc_generate_sale_report( $argus );
				endif;
			}
		}
		//Jobs by Technicians
		if ( isset( $_GET["report_type"] ) && $_GET["report_type"] == "jobs_by_technician" ) {
			$REPORTS_TECHNICIANS = new REPORT_TECHNICIANS;
			if ( ! isset( $_GET["start_date"] ) && ! isset( $_GET["end_date"] ) ) {
				$REPORTS_TECHNICIANS->generate_form_output_jobs_by_technician( "date_range", "selected_today" );
			} else {
				$argus = array_map( 'sanitize_text_field', $_GET );
				if ( ! wc_rs_license_state() ) :
					echo '<div class="callout success">'. esc_html__( 'Pro feature! Plugin activation required.', 'computer-repair-shop' ) .'</div>';
				else :
					$REPORTS_TECHNICIANS->wc_generate_technician_report( $argus );
				endif;
			}
		}
		//technicians_summary
		if ( isset( $_GET["report_type"] ) && $_GET["report_type"] == "technicians_summary" ) {
			$REPORTS_TECHNICIANS = new REPORT_TECHNICIANS;
			if ( ! isset( $_GET["start_date"] ) && ! isset( $_GET["end_date"] ) ) {
				echo 'yes';
				$REPORTS_TECHNICIANS->generate_form_output_jobs_by_technician( "technicians_summary", "selected_today" );
			} else {
				$argus = array_map( 'sanitize_text_field', $_GET );
				if ( ! wc_rs_license_state() ) :
					echo '<div class="callout success">'. esc_html__( 'Pro feature! Plugin activation required.', 'computer-repair-shop' ) .'</div>';
				else :
					$REPORTS_TECHNICIANS->wc_generate_technicians_summary( $argus );
				endif;
			}
		}
		//Jobs by Technicians
		if ( isset( $_GET["report_type"] ) && $_GET["report_type"] == "jobs_by_customer" ) {
			$REPORTS_CUSTOMERS = new REPORT_CUSTOMERS;
			if ( ! isset( $_GET["start_date"] ) && ! isset( $_GET["end_date"] ) ) {
				$REPORTS_CUSTOMERS->generate_form_output_jobs_by_customer( "date_range", "selected_today" );
			} else {
				$argus = array_map( 'sanitize_text_field', $_GET );
				if ( ! wc_rs_license_state() ) :
					echo '<div class="callout success">'. esc_html__( 'Pro feature! Plugin activation required.', 'computer-repair-shop' ) .'</div>';
				else :
					$REPORTS_CUSTOMERS->wc_generate_customer_report( $argus );
				endif;
			}
		}
		//customers_summary
		if ( isset( $_GET["report_type"] ) && $_GET["report_type"] == "customers_summary" ) {
			$REPORTS_CUSTOMERS = new REPORT_CUSTOMERS;
			if ( ! isset( $_GET["start_date"] ) && ! isset( $_GET["end_date"] ) ) {
				$REPORTS_CUSTOMERS->generate_form_output_jobs_by_customer( "customers_summary", "selected_today" );
			} else {
				$argus = array_map( 'sanitize_text_field', $_GET );
				if ( ! wc_rs_license_state() ) :
					echo '<div class="callout success">'. esc_html__( 'Pro feature! Plugin activation required.', 'computer-repair-shop' ) .'</div>';
				else :
					$REPORTS_CUSTOMERS->wc_generate_customers_summary( $argus );
				endif;
			}
		}
	}
}