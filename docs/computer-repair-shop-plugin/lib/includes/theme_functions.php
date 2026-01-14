<?php
	/*
		Function to check if role exists
		
		If does not exist 
		
		Return null on not exists
		
		Return object with capabilities on exists
		
		@since 1.0.0
	*/
	if ( ! function_exists( 'wc_get_role' ) ) :
		function wc_get_role( $role ) {
			return wp_roles()->get_role( $role );
		}
	endif;

	// get taxonomies terms links
	function custom_taxonomies_terms_links($post_id, $post_type){
		$post 		= $post_id;
		$taxonomies = get_object_taxonomies( $post_type, 'objects' );

		$out = array();
		foreach ( $taxonomies as $taxonomy_slug => $taxonomy ){

			$terms = get_the_terms($post_id, $taxonomy_slug );

			if ( !empty( $terms ) ) {
				foreach ( $terms as $term ) {
					$out[] =
					'<a href="'
					.    get_term_link( $term->slug, $taxonomy_slug ) .'">'
					.    $term->name
					. "</a>";
				}
			}
		}
		return implode('', $out );
	}

	//Get Itemmeta 
	if ( ! function_exists( 'wcrb_return_order_meta' ) ) :
		function wcrb_return_order_meta( $order_item_id, $meta_key ) {
			global $wpdb;

			if ( empty( $order_item_id ) || empty( $meta_key ) ) {
				return '';
			}

			$computer_repair_items_meta = $wpdb->prefix . 'wc_cr_order_itemmeta';
			$wc_meta_value	 = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$computer_repair_items_meta} WHERE `order_item_id` = %d AND `meta_key` = %s", $order_item_id, $meta_key ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

			return ( ! empty( $wc_meta_value->meta_value ) ) ? $wc_meta_value->meta_value : '';
		}
	endif;

	/*
		* Add Status Form
		* Ajax Form
	*/
	if ( ! function_exists( "wc_post_status" ) ) :
		function wc_post_status() { 
			global $wpdb;

			if ( ! isset( $_POST['wcrb_nonce_jobstatus_field'] ) || ! wp_verify_nonce( $_POST['wcrb_nonce_jobstatus_field'], 'wcrb_nonce_jobstatus' ) ) {
				$message = esc_html__( "Something is wrong with your submission!", "computer-repair-shop" );
			} else {
				$computer_repair_job_status = $wpdb->prefix.'wc_cr_job_status';

				$form_type 			= sanitize_text_field( $_POST["form_type"] );
				$status_name 		= sanitize_text_field( $_POST["status_name"] );
				$status_slug 		= sanitize_text_field( $_POST["status_slug"] );
				$status_description	= sanitize_textarea_field( $_POST["status_description"] );
				$invoice_label		= sanitize_text_field( $_POST["invoice_label"] );
				$status_status 		= sanitize_text_field($_POST["status_status"]);	
				$status_email_msg 	= sanitize_textarea_field( $_POST["statusEmailMessage"] );

				if ( isset( $_POST["form_type_status"] ) && $_POST["form_type_status"] == "update" ) {
					if ( isset( $_POST["status_id"] ) && is_numeric( $_POST["status_id"] ) ) {
						$update_form = sanitize_text_field($_POST["status_id"]);
					}
				}

				if ( $form_type == "status_form" ) {
					//Process form
					if(empty($status_name)) {
						$message = esc_html__("Name required", "computer-repair-shop");
					} elseif(empty($status_slug)) {
						$message = esc_html__("Slug is required", "computer-repair-shop");
					} else {

						if(isset($update_form) && is_numeric($update_form)) {
							//Update functionality
							$data 	= array(
								"status_name" 			=> $status_name,
								"status_slug" 			=> $status_slug,
								"status_description" 	=> $status_description,
								"invoice_label" 		=> $invoice_label,
								"status_email_message" 	=> $status_email_msg,
								"status_status" 		=> $status_status, 
							); 
							$where 	= ['status_id' 	=> $update_form];

							$update_row = $wpdb->update($computer_repair_job_status, $data, $where); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

							$message = esc_html__( 'Settings updated!', 'computer-repair-shop' );
						} else {
							$insert_query =  "INSERT INTO `{$computer_repair_job_status}` VALUES( NULL, %s, %s, %s, %s, %s, '', %s )";
			
							$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
									$wpdb->prepare( $insert_query, $status_name, $status_slug, $status_description, $status_email_msg, $invoice_label, $status_status )
							);

							$status_id = $wpdb->insert_id;
							$message = esc_html__( 'Settings updated!', 'computer-repair-shop' ) . $invoice_label;
						}
					}
				} else {
					$message = esc_html__("Invalid Form", "computer-repair-shop");	
				}
			}

			$values['message'] = $message;
			$values['success'] = "YES";

			wp_send_json($values);
			wp_die();
		}
		add_action( 'wp_ajax_wc_post_status', 'wc_post_status' );
	endif;

	/*
	 * WC Update Tax or Status 
	 * 
	 * Helps to update the record
	 */
	if(!function_exists("wc_update_tax_or_status")) {
		function wc_update_tax_or_status() {
			global $wpdb;

			$cr_taxes_table 		= $wpdb->prefix.'wc_cr_taxes';
			$cr_status_table 		= $wpdb->prefix.'wc_cr_job_status';
			$cr_payment_table   	= $wpdb->prefix.'wc_cr_payment_status';
			$table_wc_cr_payments   = $wpdb->prefix.'wc_cr_payments';

			if ( ! isset( $_POST['data_security'] ) || ! wp_verify_nonce( $_POST['data_security'], 'request_statusupdates_security' ) ) {
				$message = esc_html__( "Something is wrong with your submission!", "computer-repair-shop" );
			} else {
				if(isset($_POST["recordID"]) && isset($_POST["recordType"])) {

					if ( $_POST["recordType"] == "tax" ) {
						$recordId = sanitize_text_field( $_POST["recordID"] );
						
						$wc_curr_tax_status	= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$cr_taxes_table} WHERE `tax_id` = %d", $recordId ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
						$curr_status 		= $wc_curr_tax_status->tax_status;

						if($curr_status == "active") {
							$curr_status = "inactive";
						} else {
							$curr_status = "active";
						}
						$data 	= ['tax_status' => $curr_status]; 
						$where 	= ['tax_id' 	=> $recordId];

						$update_row = $wpdb->update( $cr_taxes_table, $data, $where ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

						$message = esc_html__("Tax status updated!", "computer-repair-shop");


					} elseif($_POST["recordType"] == "status") {
						$recordId = sanitize_text_field($_POST["recordID"]);
						/*
						* Updating Job Status
						* Status
						* In DB by Staus ID
						*/
						$wc_curr_job_status	= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$cr_status_table} WHERE `status_id` = %d", $recordId ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
						$curr_status 		= $wc_curr_job_status->status_status;

						if($curr_status == "active") {
							$curr_status = "inactive";
						} else {
							$curr_status = "active";
						}
						$data 	= ['status_status' 	=> $curr_status]; 
						$where 	= ['status_id' 		=> $recordId];

						$update_row = $wpdb->update( $cr_status_table, $data, $where ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

						$message = esc_html__("Job status updated!", "computer-repair-shop");

					} elseif($_POST["recordType"] == "paymentStatus") {
						$recordId = sanitize_text_field( $_POST['recordID'] );
						/*
						* Updating Job Status
						* Status
						* In DB by Staus ID
						*/
						$wc_curr_payment_status	= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$cr_payment_table} WHERE `status_id` = %d", $recordId ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
						$curr_status 		    = $wc_curr_payment_status->status_status;

						if ( $curr_status == 'active' ) {
							$curr_status = 'inactive';
						} else {
							$curr_status = 'active';
						}
						$data 	= ['status_status' 	=> $curr_status]; 
						$where 	= ['status_id' 		=> $recordId];

						$update_row = $wpdb->update( $cr_payment_table, $data, $where ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

						$message = esc_html__("Payment status updated!", "computer-repair-shop");

					} elseif($_POST["recordType"] == "inventory_count") {
						$recordId = sanitize_text_field($_POST["recordID"]);
						/*
						* Switch inventory counter
						* For
						* Products Sold Through CRM
						*/
						$wc_curr_job_status	= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$cr_status_table} WHERE `status_id` = %d", $recordId ) );	// phpcs:ignore WordPress.DB.DirectDatabaseQuery
						
						$curr_status 		= $wc_curr_job_status->inventory_count;

						if(empty($curr_status) || $curr_status == "off") {
							$curr_status = "on";
						} else {
							$curr_status = "off";
						}
						$data 	= ['inventory_count' 	=> $curr_status]; 
						$where 	= ['status_id' 			=> $recordId];

						$update_row = $wpdb->update( $cr_status_table, $data, $where ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

						$message = esc_html__("Now products would automatically deduct with this status from WOO inventory balance.!", "computer-repair-shop");
					} elseif ( $_POST['recordType'] == 'thePayment' ) {
						//Get Current Status first.
						$recordId = sanitize_text_field( $_POST["recordID"] );

						$wcPayment = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_wc_cr_payments} WHERE `payment_id` = %d", $recordId ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
						$order_id  = $wcPayment->order_id;
						$paymentAmount  = $wcPayment->amount;
						$currStatus 	= $wcPayment->status;
						$orderBalance = wc_order_grand_total( $order_id, 'balance' );
						$orderBalance = round( $orderBalance );
						$paymentAmount = round( $paymentAmount );

						$WCRB_JOB_HISTORY_LOGS = WCRB_JOB_HISTORY_LOGS::getInstance();
						//If Status is Active change to inactive without any check.
						if ( $currStatus == 'active' ) {
							$data 	= ['status' 	=> 'inactive']; 
							$where 	= ['payment_id' => $recordId];
		
							$update_row = $wpdb->update( $table_wc_cr_payments, $data, $where ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
							
							$args = array(
								"job_id" 		=> $order_id, 
								"name" 			=> esc_html__( 'Payment ID: ', 'computer-repair-shop' ) . esc_html( $recordId ) . ' ' . esc_html__( 'Status changed from', 'computer-repair-shop' ) . ' active ' . esc_html__( 'to', 'computer-repair-shop' ), 
								"type" 			=> 'public', 
								"field" 		=> '_payment_amount', 
								"change_detail" => 'inactive'
							);
							$WCRB_JOB_HISTORY_LOGS->wc_record_job_history($args);

							$message = esc_html__( "Record updated!", "computer-repair-shop" );
						} elseif ( $currStatus == 'inactive' ) {
							//If status is inactive Check if balance is equal or more than the payment amount
							if ( $orderBalance < 1 ) {
								$message = esc_html__( "Order balance is less than amount of this payment to activate it!", "computer-repair-shop" );
							} else if ( $orderBalance < $paymentAmount ) {
								$message = esc_html__( "Order balance is less than amount of this payment to activate it!", "computer-repair-shop" ) . ' order balance ' . $orderBalance . ' payment ' . $paymentAmount;
							} else {
								$data 	= ['status' 	=> 'active']; 
								$where 	= ['payment_id' => $recordId];
			
								$update_row = $wpdb->update( $table_wc_cr_payments, $data, $where ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
								
								$args = array(
									"job_id" 		=> $order_id, 
									"name" 			=> esc_html__( 'Payment ID: ', 'computer-repair-shop' ) . esc_html( $recordId ) . ' ' . esc_html__( 'Status changed from', 'computer-repair-shop' ) . ' inactive ' . esc_html__( 'to', 'computer-repair-shop' ), 
									"type" 			=> 'public', 
									"field" 		=> '_payment_amount', 
									"change_detail" => 'active'
								);
								$WCRB_JOB_HISTORY_LOGS->wc_record_job_history($args);

								$message = esc_html__( "Record updated!", "computer-repair-shop" );
							}
						}
					}
					//$message = esc_html__("Recort Type and Reecord ID missing", "computer-repair-shop");	
				} else {
					$message = esc_html__("Record updated!", "computer-repair-shop");	
				}
			}
			$values['message'] = $message;
			$values['success'] = "YES";

			wp_send_json($values);
			wp_die();
		}
		add_action( 'wp_ajax_wc_update_tax_or_status', 'wc_update_tax_or_status');
	}

	/*
	 * WC Update Job Status 
	 * 
	 * From Job list page
	 */
	if (!function_exists("wc_update_job_status")) {
		function wc_update_job_status() {
			// Verify nonce first
			if (!isset($_POST['wcrb_nonce_adrepairbuddy_field']) || 
				!wp_verify_nonce($_POST['wcrb_nonce_adrepairbuddy_field'], 'wcrb_nonce_adrepairbuddy')) {
				wp_send_json_error(esc_html__("Something is wrong with your submission!", "computer-repair-shop"));
				wp_die();
			}

			$dashboard = WCRB_MYACCOUNT_DASHBOARD::getInstance();

			// Validate required parameters
			if (!isset($_POST["recordID"]) || !isset($_POST["orderStatus"]) || ! $dashboard->have_job_access( absint( $_POST["recordID"] ) ) ) {
				wp_send_json_error(esc_html__("Something is wrong with your submission!", "computer-repair-shop"));
				wp_die();
			}

			// Sanitize inputs
			$record_id = absint($_POST["recordID"]);
			$new_job_status = sanitize_text_field($_POST["orderStatus"]);
			
			// Validate post exists
			if ( ! get_post( $record_id ) ) {
				wp_send_json_error( esc_html__( "Something is wrong with your submission!", "computer-repair-shop" ) );
				wp_die();
			}

			global $wpdb, $OBJ_SMS_SYSTEM, $WCRB_WOO_FUNCTIONS_OBJ;

			try {
				$wc_send_cr_notice = get_option('wc_job_status_cr_notice');
				$old_job_status    = get_post_meta($record_id, "_wc_order_status", true);

				if ( $old_job_status === $new_job_status ) {
					wp_send_json_success(esc_html__('Record updated!', 'computer-repair-shop'));
					wp_die();
				}

				// Update the status
				update_post_meta( $record_id, "_wc_order_status", $new_job_status );

				// Record history
				$name = esc_html__( "Order status modified to", "computer-repair-shop" );
				$order_status = $change_detail = wc_return_status_name($new_job_status);
				
				$args = array(
					"job_id" => $record_id,
					"name" => $name,
					"type" => "public",
					"field" => '_wc_order_status',
					"change_detail" => $change_detail
				);
				
				$WCRB_JOB_HISTORY_LOGS = WCRB_JOB_HISTORY_LOGS::getInstance();
				$WCRB_JOB_HISTORY_LOGS->wc_record_job_history($args);
				
				update_post_meta( $record_id, '_wc_order_status_label', $order_status );

				// Send SMS if enabled
				$is_sms_active = get_option('wc_rb_sms_active');
				if ($is_sms_active === 'YES' && isset( $OBJ_SMS_SYSTEM )) {
					$OBJ_SMS_SYSTEM->wc_rb_status_send_the_sms( $record_id, $new_job_status );
				}

				// Send customer email if enabled
				if ($wc_send_cr_notice === "on") {
					$_GET["wc_case_number"] = get_post_meta( $record_id, '_case_number', true );
					wc_cr_send_customer_update_email( $record_id );
				}

				// Update WooCommerce stock
				if (isset($WCRB_WOO_FUNCTIONS_OBJ)) {
					$WCRB_WOO_FUNCTIONS_OBJ->wc_update_woo_stock_if_enabled( $record_id, $new_job_status );
				}

				//Signature request
				$WCRB_SIGNATURE_WORKFLOW = WCRB_SIGNATURE_WORKFLOW::getInstance();
				$WCRB_SIGNATURE_WORKFLOW->send_signature_request( $new_job_status, $record_id );

				wp_send_json_success(esc_html__('Record updated!', 'computer-repair-shop'));
				
			} catch (Exception $e) {
				wp_send_json_error(esc_html__('Something is wrong with your submission!', 'computer-repair-shop'));
			}
			
			wp_die();
		}
		add_action('wp_ajax_wc_update_job_status', 'wc_update_job_status');
	}

	if ( ! function_exists( 'wcrb_update_job_priority' ) ) {
		function wcrb_update_job_priority() {
			// Verify nonce first
			if ( ! isset( $_POST['nonce'] ) || 
				! wp_verify_nonce( $_POST['nonce'], 'wcrb_update_priority_nonce' ) ) {
				wp_send_json_error( esc_html__( "Something is wrong with your submission!", "computer-repair-shop" ) );
				wp_die();
			}

			$dashboard = WCRB_MYACCOUNT_DASHBOARD::getInstance();

			// Validate required parameters
			if ( ! isset($_POST["recordID"]) || ! isset($_POST["priority"]) || ! $dashboard->have_job_access( absint( $_POST["recordID"] ) ) ) {
				wp_send_json_error( esc_html__( "Something is wrong with your submission!", "computer-repair-shop" ) );
				wp_die();
			}

			// Sanitize inputs
			$record_id = absint($_POST["recordID"]);
			$_new_priority  = sanitize_text_field( $_POST["priority"] );
			
			// Validate post exists
			if ( ! get_post( $record_id ) ) {
				wp_send_json_error( esc_html__( "Something is wrong with your submission!", "computer-repair-shop" ) );
				wp_die();
			}

			try {
				$_old_priority = get_post_meta( $record_id, "_wc_job_priority", true );

				if ( $_old_priority === $_new_priority ) {
					wp_send_json_success( esc_html__('Record updated!', 'computer-repair-shop'));
					wp_die();
				}

				// Update the status
				update_post_meta( $record_id, "_wc_job_priority", $_new_priority );

				// Record history
				$name = esc_html__( "Job priority modified to", "computer-repair-shop" );
				$change_detail 	= wcrb_job_priorities( $_new_priority );
				
				$args = array(
					"job_id" => $record_id,
					"name" => $name,
					"type" => "public",
					"field" => '_wc_order_status',
					"change_detail" => $change_detail
				);
				
				$WCRB_JOB_HISTORY_LOGS = WCRB_JOB_HISTORY_LOGS::getInstance();
				$WCRB_JOB_HISTORY_LOGS->wc_record_job_history($args);

				wp_send_json_success( esc_html__('Record updated!', 'computer-repair-shop'));
				
			} catch (Exception $e) {
				wp_send_json_error( esc_html__('Something is wrong with your submission!', 'computer-repair-shop'));
			}
			
			wp_die();
		}
		add_action('wp_ajax_wcrb_update_job_priority', 'wcrb_update_job_priority');
	}

	if ( ! function_exists( 'wc_post_customer' ) ):
		function wc_post_customer() {
			//Register User
			$first_name = $last_name = $company = $customer_phone = $message = $user_id = $email = '';

			if ( ! isset( $_POST['wcrb_nonce_addclient_field'] ) || ! wp_verify_nonce( $_POST['wcrb_nonce_addclient_field'], 'wcrb_nonce_addclient' ) ) {
				$message = esc_html__( "Something is wrong with your submission!", "computer-repair-shop" );
			} else {
				// $country
				$first_name 		= ( isset( $_POST["reg_fname"] ) ) ? sanitize_text_field( $_POST["reg_fname"] ) : '';
				$last_name 			= ( isset( $_POST["reg_lname"] ) ) ? sanitize_text_field( $_POST["reg_lname"] ) : '';
				
				$username 			= ( ! isset( $_POST['reg_email'] ) || empty( $_POST['reg_email'] ) ) ? wcrb_get_random_unique_username( $first_name ) : sanitize_email( $_POST["reg_email"] );

				$email 				= ( isset( $_POST["reg_email"] ) ) ? sanitize_email( $_POST["reg_email"] ) : '';
				$customer_phone 	= ( isset( $_POST["customer_phone"] ) ) ? sanitize_text_field( $_POST["customer_phone"] ) : '';
				$company 			= ( isset( $_POST["customer_company"] ) ) ? sanitize_text_field( $_POST["customer_company"] ) : "";
				$billing_tax 		= ( isset( $_POST["billing_tax"] ) ) ? sanitize_text_field( $_POST["billing_tax"] ) : "";
				$customer_address 	= ( isset( $_POST["customer_address"] ) ) ? sanitize_text_field( $_POST["customer_address"] ) : "";

				$customer_city 		= ( isset( $_POST["customer_city"] ) ) ? sanitize_text_field( $_POST["customer_city"] ) : '';
				$zip_code 			= ( isset( $_POST["zip_code"] ) ) ? sanitize_text_field( $_POST["zip_code"] ) : '';
				$state 				= ( isset( $_POST["state_province"] ) ) ? sanitize_text_field( $_POST["state_province"] ) : '';
				$country 			= ( isset( $_POST["country"] ) ) ? sanitize_text_field( $_POST["country"] ) : '';
				$user_role 			= ( isset( $_POST["userrole"] ) && ! empty( $_POST["userrole"] ) ) ? "technician" : "customer";

				if ( isset( $_POST["userrole"] ) && $_POST["userrole"] == "store_manager" ) {
					$user_role = "store_manager";
				}

				$password 	= wp_generate_password(8, false );

				if ( ! empty( $username ) && username_exists( $username ) ) {
					$message = esc_html__( 'Duplicate User', 'computer-repair-shop' );
					$user = get_user_by( 'login', $username );
					$user_id = ( $user ) ? $user->ID : '';
				} elseif( empty( $username ) || ! validate_username( $username ) ) {
					$message = esc_html__( "Not a valid username", "computer-repair-shop" ) . ' ' . esc_html( $username );
				} elseif( ! empty( $username ) ) {

					if ( ! empty( $email ) ) {
						if ( ! is_email($email) ) {
							$message = esc_html__("Email is not valid", "computer-repair-shop");
						} elseif( email_exists( $email ) ) {
							$message = esc_html__("Email already in user. Try resetting password if its your Email.", "computer-repair-shop");
							$user = get_user_by( 'email', $email );
							$user_id = ( $user ) ? $user->ID : 'No';
						}
					}

					if ( empty( $message ) && empty( $user_id ) ) :
						//We are all set to Register User.
						$userdata = array(
							'user_login' 	=> $username,
							'user_email' 	=> $email,
							'user_pass' 	=> $password,
							'first_name' 	=> $first_name,
							'last_name' 	=> $last_name,
							'role'			=> $user_role
						);
						//StateProvince // Country

						//Insert User Data
						$register_user = wp_insert_user( $userdata );

						//If Not exists
						if ( ! is_wp_error( $register_user ) ) {
							//Use user instead of both in case sending notification to only user
							$message = esc_html__( 'User account is created logins sent to email.', 'computer-repair-shop' );
							$user_id = $register_user;

							global $WCRB_EMAILS;
							$WCRB_EMAILS->send_user_logins_after_register( $user_id, $password );

							if ( ! empty( $user_id ) ) {
								update_user_meta( $user_id, 'billing_first_name', $first_name );
								update_user_meta( $user_id, 'billing_last_name', $last_name );
								update_user_meta( $user_id, 'billing_company', $company );
								update_user_meta( $user_id, 'billing_tax', $billing_tax );
								update_user_meta( $user_id, 'billing_address_1', $customer_address );
								update_user_meta( $user_id, 'billing_city', $customer_city );
								update_user_meta( $user_id, 'billing_postcode', $zip_code );
								update_user_meta( $user_id, 'billing_state', $state );
								update_user_meta( $user_id, 'billing_country', $country );
								update_user_meta( $user_id, 'billing_phone', $customer_phone );

								update_user_meta( $user_id, 'billing_email', $email );

								update_user_meta( $user_id, 'shipping_first_name', $first_name );
								update_user_meta( $user_id, 'shipping_last_name', $last_name );
								update_user_meta( $user_id, 'shipping_company', $company );
								update_user_meta( $user_id, 'shipping_tax', $billing_tax );
								update_user_meta( $user_id, 'shipping_address_1', $customer_address );
								update_user_meta( $user_id, 'shipping_city', $customer_city );
								update_user_meta( $user_id, 'shipping_postcode', $zip_code );
								update_user_meta( $user_id, 'shipping_state', $state );
								update_user_meta( $user_id, 'shipping_country', $country );
								update_user_meta( $user_id, 'shipping_phone', $customer_phone );
							}
						} else {
							$message = '<strong>' . $register_user->get_error_message() . '</strong>';
						}
					endif;
				}
			}

			$values['message'] = $message;
			$values['success'] = "YES";
			$values['user_id'] = $user_id;
			$values['optionlabel'] = $user_id . ' | ' . $first_name . ' ' . $last_name . ' | ' . $company . '(' . $customer_phone . ')' . '(' . $email . ')';

			wp_send_json( $values );
			wp_die();
		}
		add_action( 'wp_ajax_wc_post_customer', 'wc_post_customer' );
	endif;

	if ( ! function_exists( 'wcrb_get_random_unique_username' ) ) :
		function wcrb_get_random_unique_username( $prefix = '' ) {
			$user_exists = 1;

			if ( empty( $prefix ) ) {
				return;
			}

			// Sanitize the prefix to remove/replace non-ASCII characters
			$prefix = wcrb_sanitize_username_prefix( $prefix );
			
			// If after sanitization the prefix is empty, use a default
			if ( empty( $prefix ) ) {
				$prefix = 'wcrbuser';
			}

			$prefix = strtolower( $prefix );

			do {
				$rnd_str = sprintf("%06d", mt_rand(1, 999999));
				$username = $prefix . $rnd_str;
				$user_exists = username_exists( $username );
			} while( $user_exists > 0 );

			return $username;
		}
	endif;

	// Helper function to sanitize username prefix
	if ( ! function_exists( 'wcrb_sanitize_username_prefix' ) ) :
		function wcrb_sanitize_username_prefix( $prefix ) {
			// Remove accents and convert to ASCII
			$prefix = remove_accents( $prefix );
			
			// Convert to lowercase
			$prefix = strtolower( $prefix );
			
			// Remove all non-allowed characters
			$prefix = preg_replace( '/[^a-z0-9_\-\.@\s]/', '', $prefix );
			
			// Replace spaces with underscores
			$prefix = str_replace( ' ', '_', $prefix );
			
			// Remove multiple consecutive underscores
			$prefix = preg_replace( '/_{2,}/', '_', $prefix );
			
			// Trim underscores from beginning and end
			$prefix = trim( $prefix, '_' );
			
			// Limit length to avoid exceeding 60 characters with random numbers
			$prefix = substr( $prefix, 0, 20 );
			
			return $prefix;
		}
	endif;

	if ( ! function_exists( 'wc_print_existing_parts' ) ): 
		function wc_print_existing_parts( $order_arg ) {
			global $wpdb;
			
			$order_type = $display_type = '';
			if ( is_array( $order_arg ) ) {
				$order_id 	  = $order_arg['order_id'];
				$display_type = $order_arg['display_type'];
				$order_type   = ( isset( $order_arg['order_type'] ) ) ? $order_arg['order_type'] : '';
			} else {
				$order_id = $order_arg;
			}
			$prices_inclu_exclu = ( isset( $order_id ) && ! empty( $order_id ) ) ? get_post_meta( $order_id, '_wc_prices_inclu_exclu', true ) : 'exclusive';

			$wc_use_taxes = get_option( 'wc_use_taxes' );

			if ( isset( $_POST['wc_case_number'] ) || isset( $_GET['wc_case_number'] ) ) {
				$print_values = 'YES';
			} elseif ( isset ( $_GET['page'] ) && $_GET['page'] == 'wc_computer_repair_print' ) {
				$print_values = 'YES';
			} elseif ( $display_type == 'YES' || $display_type == 'pdf' ) {
				$print_values = 'YES';
			} else {
				$print_values = 'NO';
			}

			$computer_repair_items 	= $wpdb->prefix . 'wc_cr_order_items';
			$select_items_query 	= $wpdb->prepare( "SELECT * FROM `{$computer_repair_items}` WHERE `order_id`= %d AND `order_item_type`='parts'", $order_id);
			$items_result 			= $wpdb->get_results($select_items_query); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			
			$content = '';
			
			foreach( $items_result as $item ) {
				$order_item_id 	 = $item->order_item_id;
				$order_item_name = wp_unslash( $item->order_item_name );
				
				$wc_part_id 			= wcrb_return_order_meta( $order_item_id, 'wc_part_id' );
				$wc_part_code			= wcrb_return_order_meta( $order_item_id, 'wc_part_code' );
				$wc_part_capacity		= wcrb_return_order_meta( $order_item_id, 'wc_part_capacity' );
				$wc_part_qty			= wcrb_return_order_meta( $order_item_id, 'wc_part_qty' );
				$wc_part_price			= wcrb_return_order_meta( $order_item_id, 'wc_part_price' );
				$wc_part_tax			= wcrb_return_order_meta( $order_item_id, 'wc_part_tax' );
				$wc_part_device  		= wcrb_return_order_meta( $order_item_id, 'wc_part_device' );
				$wc_part_device_serial = wcrb_return_order_meta( $order_item_id, 'wc_part_device_serial' );
				
				$content .= "<tr class='item-row wc_part_row'>";
				
				if ( $print_values == 'YES' ) {
					$device_name = return_device_name_if_more_than_one( $order_id, $wc_part_device );
					$wc_part_serial_p = ( ! empty( $wc_part_device_serial ) ) ? ' (' . $wc_part_device_serial . ')' : '';
					$content .= "<td class='wc_part_name'>" . $order_item_name . $device_name . $wc_part_serial_p . "</td>";
					$content .= "<td class='wc_part_code'>" . $wc_part_code . "</td>";
					$content .= "<td class='wc_capacity'>" . $wc_part_capacity . "</td>";
				} else {
					$content .= "<td class='wc_part_name'><a class='delme' href='#' title='Remove row'>X</a>";
					$content .= $order_item_name."<input type='hidden' name='wc_part_id[]' value='".$wc_part_id."' /><input type='hidden' name='wc_part_name[]' value='".$order_item_name."'></td>";
					$content .= "<td class='wc_part_code'>".$wc_part_code."<input type='hidden' name='wc_part_code[]' value='".$wc_part_code."'></td>";
					$content .= "<td class='wc_capacity'>".$wc_part_capacity."<input type='hidden' name='wc_part_capacity[]' value='".$wc_part_capacity."'></td>";
				}
				
				if ( $print_values == "YES" ) {
					//Nothing
				} else {
					$content .= "<td class='wc_part_device'>" . return_job_device_options( $order_id, $wc_part_device, $wc_part_device_serial, 'part', 'html' ) . "</td>";	
				}

				if($print_values == "YES") {
					$content .= "<td class='wc_qty'>".$wc_part_qty."</td>";
					$content .= ( $order_type != 'work_order' ) ? "<td class='wc_price'>".$wc_part_price."</td>" : '';
				} else {
					$content .= "<td class='wc_qty'><input type='number' step='any' class='wc_validate_number wc_special_input' name='wc_part_qty[]' value='".$wc_part_qty."' /></td>";
					$content .= "<td class='wc_price'><input type='number' step='any' class='wc_validate_number wc_special_input' name='wc_part_price[]' value='".$wc_part_price."' /></td>";
				}	

				$calculate_tax = 0;

				if(!empty($wc_part_tax) || $wc_use_taxes == "on") {
					if(!empty($wc_part_tax)) {
						$tax_rate		= $wc_part_tax;
						$wc_tax_id 		= wc_return_tax_id( $tax_rate );
					} else {
						$tax_rate		= "0";
						$wc_tax_id 		= "0";
					}

					if ( $order_type != 'work_order' ) {
					$content .= "<td class='wc_tax'>";

					if($print_values != "YES") {
						$content .= '<select class="regular-text wc_part_tax wc_small_select form-control" name="wc_part_tax[]">';
						$content .= '<option value="">'.esc_html__("Select tax", "computer-repair-shop").'</option>';

						$wc_part_tax_arr = array(
							"wc_default_tax_value"	=> $wc_tax_id,
							"value_type"		=> "tax_rate"
						);
						$content .= wc_generate_tax_options($wc_part_tax_arr);	
						$content .= '</select>';
					} else {
						$content .= $tax_rate;
					}	
					$content .= "</td>";

					$content .= "<td class='wc_part_tax_price'>";
					
					$total_price 	= (int)$wc_part_qty*(float)$wc_part_price;

					if(empty($tax_rate)) {
						$tax_rate = 0;
					}
					if ( $prices_inclu_exclu == 'inclusive' ) {
						$calculate_tax 	= $total_price*$tax_rate/(100+$tax_rate);
					} else {
						$calculate_tax 	= ($total_price/100)*$tax_rate;
					}

					$calculate_tax_disp = wc_cr_currency_format( $calculate_tax, FALSE, TRUE );

					$content .= $calculate_tax_disp;
					$content .= "</td>";
					}
				}
				$total_price_disp = ( $prices_inclu_exclu == 'inclusive' ) ? ( (float)$wc_part_price * (int)$wc_part_qty ) : ( (float)$wc_part_price * (int)$wc_part_qty ) + $calculate_tax;
				$total_price_disp = wc_cr_currency_format( $total_price_disp, FALSE, TRUE );

				$content .= ( $order_type != 'work_order' ) ? '<td class="wc_price_total">' . $total_price_disp . '</td>' : '';
				$content .= '</tr>';
			}
			return $content;
		}
	endif;

	if ( ! function_exists( 'wc_print_existing_products' ) ): 
		function wc_print_existing_products( $order_arg ) {
			global $wpdb;

			$order_type = $display_type = '';
			if ( is_array( $order_arg ) ) {
				$order_id 	  = $order_arg['order_id'];
				$display_type = $order_arg['display_type'];
				$order_type   = ( isset( $order_arg['order_type'] ) ) ? $order_arg['order_type'] : '';
			} else {
				$order_id = $order_arg;
			}
			$prices_inclu_exclu = ( isset( $order_id ) && ! empty( $order_id ) ) ? get_post_meta( $order_id, '_wc_prices_inclu_exclu', true ) : 'exclusive';

			$wc_use_taxes = get_option( 'wc_use_taxes' );

			if ( isset ( $_POST['wc_case_number'] ) || isset( $_GET['wc_case_number'] ) ) {
				$print_values = 'YES';
			} elseif ( isset ( $_GET['page'] ) && $_GET['page'] == 'wc_computer_repair_print' ) {
				$print_values = 'YES';
			} elseif ( $display_type == 'email' || $display_type == 'pdf' ) {
				$print_values = 'YES';
			} else {
				$print_values = 'NO';
			}
			$computer_repair_items 		= $wpdb->prefix . 'wc_cr_order_items';
			
			$select_items_query = $wpdb->prepare( "SELECT * FROM `{$computer_repair_items}` WHERE `order_id`= %d AND `order_item_type`='products'", $order_id );
			
			$items_result = $wpdb->get_results( $select_items_query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			
			$content = '';
			
			foreach ( $items_result as $item ) {
				$order_item_id 	 = $item->order_item_id;
				$order_item_name = wp_unslash( $item->order_item_name );
				
				$wc_product_id 		= wcrb_return_order_meta( $order_item_id, 'wc_product_id' );
				$wc_product_sku		= wcrb_return_order_meta( $order_item_id, 'wc_product_sku' );
				$wc_product_qty		= wcrb_return_order_meta( $order_item_id, 'wc_product_qty' );
				$wc_product_price	= wcrb_return_order_meta( $order_item_id, 'wc_product_price' );
				$wc_product_tax		= wcrb_return_order_meta( $order_item_id, 'wc_product_tax' );
				$wc_product_device  = wcrb_return_order_meta( $order_item_id, 'wc_product_device' );
				$wc_product_device_serial = wcrb_return_order_meta( $order_item_id, 'wc_product_device_serial' );

				$content .= "<tr class='item-row wc_product_row'>";

				if( $print_values == 'YES' ) {
					$device_name = return_device_name_if_more_than_one( $order_id, $wc_product_device );
					$wc_product_serial_p = ( ! empty( $wc_product_device_serial ) ) ? ' (' . $wc_product_device_serial . ')' : '';

					$content .= '<td class="wc_product_name">' . $order_item_name . $device_name . $wc_product_serial_p . '</td>';
					$content .= '<td class="wc_product_sku">' . $wc_product_sku . '</td>';
				} else {
					$content .= '<td class="wc_product_name">
								<a class="delme" href="#" title="Remove row">X</a>
								'.$order_item_name.'<input type="hidden" name="wc_product_id[]" value="'.$wc_product_id.'">
								<input type="hidden" name="wc_product_name[]" value="'.$order_item_name.'">
							</td>';

					$content .= '<td class="wc_product_sku">
									'.$wc_product_sku.'
									<input type="hidden" name="wc_product_sku[]" value="'.$wc_product_sku.'">
								</td>';
				}

				if ( $print_values == "YES" ) {
					//Nothing
				} else {
					$content .= "<td class='wc_product_device'>" . return_job_device_options( $order_id, $wc_product_device, $wc_product_device_serial, 'product', 'html' ) . "</td>";	
				}

				if ( $print_values == 'YES' ) {
					$content .= '<td class="wc_qty">
									'.$wc_product_qty.'
								</td>';
					$content .= ( $order_type != 'work_order' ) ? '<td class="wc_price">' . $wc_product_price . '</td>' : '';
				} else {
					$content .= '<td class="wc_qty">
									<input type="number" step="any" class="wc_validate_number wc_special_input" name="wc_product_qty[]" value="'.$wc_product_qty.'">
								</td>';
					$content .= '<td class="wc_price">
									<input type="number" step="any" class="wc_validate_number wc_special_input" name="wc_product_price[]" value="'.$wc_product_price.'">
								</td>';
				}	
				$calculate_tax = 0;

				if ( ! empty( $wc_product_tax ) || $wc_use_taxes == "on" ) {
					
					if ( ! empty( $wc_product_tax ) ) {
						$tax_rate		= $wc_product_tax;
						$wc_tax_id 		= wc_return_tax_id($tax_rate);
					} else {
						$tax_rate		= "0";
						$wc_tax_id 		= "0";
					}
					
					if ( $order_type != 'work_order' ) {
					$content .= "<td class='wc_tax'>";

					if($print_values != "YES") {
						$content .= '<select class="regular-text wc_part_tax wc_small_select form-control" name="wc_product_tax[]">';
						$content .= '<option value="">'.esc_html__("Select tax", "computer-repair-shop").'</option>';

						$wc_part_tax_arr = array(
							"wc_default_tax_value"	=> $wc_tax_id,
							"value_type"		=> "tax_rate"
						);
						$content .= wc_generate_tax_options($wc_part_tax_arr);	
						$content .= '</select>';
					} else {
						$content .= $tax_rate;
					}	
					$content .= "</td>";
					$content .= "<td class='wc_product_tax_price'>";
					
					$total_price 	= (int)$wc_product_qty*(float)$wc_product_price;

					if(empty($tax_rate)) {
						$tax_rate = 0;
					}
					if ( $prices_inclu_exclu == 'inclusive' ) {
						$calculate_tax 	= $total_price*$tax_rate/(100+$tax_rate);
					} else {
						$calculate_tax 	= ($total_price/100)*$tax_rate;
					}
					$calculate_tax_disp = wc_cr_currency_format( $calculate_tax, FALSE, TRUE );
					$content .= $calculate_tax_disp;

					$content .= "</td>";
				}
				}
				$total_price_disp = ( $prices_inclu_exclu == 'inclusive' ) ? ( (float)$wc_product_price * (int)$wc_product_qty ) : ( (float)$wc_product_price * (int)$wc_product_qty ) + $calculate_tax;
				
				$total_price_disp = wc_cr_currency_format( $total_price_disp, FALSE, TRUE );

				$content .= ( $order_type != 'work_order' ) ? "<td class='wc_product_price_total'>" . $total_price_disp . "</td>" : '';
				$content .= "</tr>";
			}
			return $content;
		}
	endif; // End Existing Products

	if ( ! function_exists( 'wc_print_existing_services' ) ): 
		function wc_print_existing_services( $order_arg ) {
			global $wpdb;
			
			$order_type = $display_type = '';
			if ( is_array( $order_arg ) ) {
				$order_id 	  = $order_arg['order_id'];
				$display_type = $order_arg['display_type'];
				$order_type   = ( isset( $order_arg['order_type'] ) ) ? $order_arg['order_type'] : '';
			} else {
				$order_id = $order_arg;
			}
			$prices_inclu_exclu = ( isset( $order_id ) && ! empty( $order_id ) ) ? get_post_meta( $order_id, '_wc_prices_inclu_exclu', true ) : 'exclusive';

			$wc_use_taxes 	= get_option( 'wc_use_taxes' );

			if ( isset ( $_POST['wc_case_number']) || isset ( $_GET['wc_case_number'] ) ) {
				$print_values = 'YES';
			} elseif ( isset ( $_GET['page'] ) && $_GET['page'] == 'wc_computer_repair_print' ) {
				$print_values = 'YES';
			} elseif ( $display_type == 'email' || $display_type == 'pdf' ) {
				$print_values = 'YES';
			} else {
				$print_values = 'NO';
			}

			$computer_repair_items 		= $wpdb->prefix . 'wc_cr_order_items';
			
			$select_items_query = $wpdb->prepare( "SELECT * FROM `{$computer_repair_items}` WHERE `order_id`= %d AND `order_item_type`='services'", $order_id );
			
			$items_result = $wpdb->get_results( $select_items_query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			
			$content = '';
			
			foreach ( $items_result as $item ) {
				$order_item_id 	 = $item->order_item_id;
				$order_item_name = wp_unslash( $item->order_item_name );
				
				$wc_service_id 		= wcrb_return_order_meta( $order_item_id, 'wc_service_id' );
				$wc_service_code	= wcrb_return_order_meta( $order_item_id, 'wc_service_code' );
				$wc_service_qty		= wcrb_return_order_meta( $order_item_id, 'wc_service_qty' );
				$wc_service_price	= wcrb_return_order_meta( $order_item_id, 'wc_service_price' );
				$wc_service_tax		= wcrb_return_order_meta( $order_item_id, 'wc_service_tax' );
				$wc_service_device  = wcrb_return_order_meta( $order_item_id, 'wc_service_device' );
				$wc_service_device_serial = wcrb_return_order_meta( $order_item_id, 'wc_service_device_serial' );
				

				$content .= "<tr class='item-row wc_service_row'>";
				$content .= "<td class='wc_service_name'>";

				if ( $print_values == 'YES' ) {
					$device_name = return_device_name_if_more_than_one( $order_id, $wc_service_device );
					$wc_service_serial_p = ( ! empty( $wc_service_device_serial ) ) ? ' (' . $wc_service_device_serial . ')' : '';

					$content .= $order_item_name . $device_name . $wc_service_serial_p . "</td>";
					$content .= "<td class='wc_service_code'>" . $wc_service_code . "</td>";
				} else {
					$content .= "<a class='delme' href='#' title='Remove row'>X</a>";
					$content .= $order_item_name."<input type='hidden' name='wc_service_id[]' value='".$wc_service_id."' />
					<input type='hidden' name='wc_service_name[]' value='".$order_item_name."' /></td>";
					$content .= "<td class='wc_service_code'>".$wc_service_code."<input type='hidden' name='wc_service_code[]' value='".$wc_service_code."' /></td>";
				}

				if ( $print_values == "YES" ) {
					//Nothing
				} else {
					$content .= "<td class='wc_service_device'>" . return_job_device_options( $order_id, $wc_service_device, $wc_service_device_serial, 'service', 'html' ) . "</td>";	
				}

				if ( $print_values == 'YES' ) {
					$content .= "<td class='wc_service_qty'>".$wc_service_qty."</td>";
				} else {
					$content .= "<td class='wc_service_qty'><input type='number' step='any' class='wc_validate_number wc_special_input' name='wc_service_qty[]' value='".$wc_service_qty."' /></td>";
				}	
				
				if ( $print_values == 'YES' ) {
					$content .= ( $order_type != 'work_order' ) ? "<td class='wc_service_price'>" . $wc_service_price . "</td>" : '';
				} else {
					$content .= "<td class='wc_service_price'><input type='number' step='any' class='wc_validate_number wc_special_input' name='wc_service_price[]' value='".$wc_service_price."' /></td>";
				}

				$calculate_tax = 0;

				if ( ! empty( $wc_service_tax ) || $wc_use_taxes == "on" ) {
					if ( ! empty( $wc_service_tax ) ){
						$tax_rate		= $wc_service_tax;
						$wc_tax_id 		= wc_return_tax_id( $tax_rate );
					} else {
						$tax_rate		= "0";
						$wc_tax_id 		= "0";
					}

					if ( $order_type != 'work_order' ) {

					$content .= "<td class='wc_tax'>";

					if($print_values != "YES") {
						$content .= '<select class="regular-text wc_service_tax wc_small_select form-control" name="wc_service_tax[]">';
						$content .= '<option value="">'.esc_html__("Select tax", "computer-repair-shop").'</option>';

						$wc_service_tax_arr = array(
							"wc_default_tax_value"	=> $wc_tax_id,
							"value_type"		=> "tax_rate"
						);
						$content .= wc_generate_tax_options( $wc_service_tax_arr );	
						$content .= '</select>';
					} else {
						$content .= $tax_rate;
					}	
					$content .= "</td>";

					$content .= "<td class='wc_service_tax_price'>";
					
					$total_price 	= (float)$wc_service_price*(float)$wc_service_qty;

					if(empty($tax_rate)) {
						$tax_rate = 0;
					}
					if ( $prices_inclu_exclu == 'inclusive' ) {
						$calculate_tax 	= $total_price*$tax_rate/(100+$tax_rate);
					} else {
						$calculate_tax 	= ($total_price/100)*$tax_rate;
					}

					$calculate_tax_disp = wc_cr_currency_format( $calculate_tax, FALSE, TRUE );
					
					$content .= $calculate_tax_disp;

					$content .= "</td>";
					}
				}
				$grand_total = ( $prices_inclu_exclu == 'inclusive' ) ? (((float)$wc_service_price*(float)$wc_service_qty)) : (((float)$wc_service_price*(float)$wc_service_qty)+$calculate_tax);

				$total_price_disp = wc_cr_currency_format( $grand_total, FALSE, TRUE );

				$content .= ( $order_type != 'work_order' ) ? '<td class="wc_service_price_total">'. $total_price_disp .'</td>' : '';
				$content .= '</tr>';
			}
			return $content;
		}
	endif;

	if ( ! function_exists( 'wc_print_existing_extras' ) ): 
		function wc_print_existing_extras( $order_arg ) {
			global $wpdb;

			$order_type = $display_type = '';
			if ( is_array( $order_arg ) ) {
				$order_id 	  = $order_arg['order_id'];
				$display_type = $order_arg['display_type'];
				$order_type   = ( isset( $order_arg['order_type'] ) ) ? $order_arg['order_type'] : '';
			} else {
				$order_id = $order_arg;
			}
			
			$prices_inclu_exclu = ( isset( $order_id ) && ! empty( $order_id ) ) ? get_post_meta( $order_id, '_wc_prices_inclu_exclu', true ) : 'exclusive';

			$wc_use_taxes = get_option( 'wc_use_taxes' );

			if ( isset ( $_POST['wc_case_number'] ) || isset ( $_GET['wc_case_number'] ) ) {
				$print_values = 'YES';
			} elseif ( isset ( $_GET['page'] ) && $_GET['page'] == 'wc_computer_repair_print' ) {
				$print_values = 'YES';
			} elseif ( $display_type == 'email' || $display_type == 'pdf' ) {
				$print_values = 'YES';
			} else {
				$print_values = 'NO';
			}

			$computer_repair_items 		= $wpdb->prefix . 'wc_cr_order_items';

			$select_items_query = $wpdb->prepare( "SELECT * FROM `{$computer_repair_items}` WHERE `order_id`= %d AND `order_item_type`='extras'", $order_id);
			
			$items_result = $wpdb->get_results( $select_items_query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			
			$content = '';
			
			foreach ( $items_result as $item ) {
				$order_item_id 	 = $item->order_item_id;
				$order_item_name = wp_unslash( $item->order_item_name );
				//metameta
				$wc_extra_code	 = wcrb_return_order_meta( $order_item_id, 'wc_extra_code' );
				$wc_extra_qty	 = wcrb_return_order_meta( $order_item_id, 'wc_extra_qty' );
				$wc_extra_price	 = wcrb_return_order_meta( $order_item_id, 'wc_extra_price' );
				$wc_extra_device = wcrb_return_order_meta( $order_item_id, 'wc_extra_device' );
				$wc_extra_tax	 = wcrb_return_order_meta( $order_item_id, 'wc_extra_tax' );
				$wc_extra_device_serial = wcrb_return_order_meta( $order_item_id, 'wc_extra_device_serial' );
				
				$content .= "<tr class='item-row wc_extra_row'>";
				$content .= "<td class='wc_extra_name'>";

				if($print_values == "YES") {
					$device_name = return_device_name_if_more_than_one( $order_id, $wc_extra_device );
					$wc_extra_serial_p = ( ! empty( $wc_extra_device_serial ) ) ? ' (' . $wc_extra_device_serial . ')' : '';

					$content .= $order_item_name . $device_name . $wc_extra_serial_p . "</td>";
				} else {
					$content .= "<a class='delme' href='#' title='Remove row'>X</a>";
					$content .= "<input type='text' class='wc_special_input' name='wc_extra_name[]' value='".$order_item_name."' placeholder='".esc_html__("Extra name here...", "computer-repair-shop")."' /></td>";
				}

				if($print_values == "YES") {
					$content .= "<td class='wc_extra_code'>" . $wc_extra_code . "</td>";
				} else {
					$content .= "<td class='wc_extra_code'><input type='text' class='wc_special_input' name='wc_extra_code[]' value='" . $wc_extra_code . "' /></td>";
				}
				
				if($print_values == "YES") {
					//Nothing
				} else {
					$content .= "<td class='wc_extra_device'>" . return_job_device_options( $order_id, $wc_extra_device, $wc_extra_device_serial, 'extra', 'html' ) . "</td>";	
				}

				if($print_values == "YES") {
					$content .= "<td class='wc_extra_qty'>".$wc_extra_qty."</td>";
				} else {
					$content .= "<td class='wc_extra_qty'><input type='number' step='any' class='wc_validate_number wc_special_input' name='wc_extra_qty[]' value='" . $wc_extra_qty . "' /></td>";	
				}
				
				if($print_values == "YES") {
					$content .= ( $order_type != 'work_order' ) ? "<td class='wc_extra_price'>" . $wc_extra_price . "</td>" : '';
				} else {
					$content .= "<td class='wc_extra_price'><input type='number' step='any' class='wc_validate_number wc_special_input' name='wc_extra_price[]' value='" . $wc_extra_price . "' /></td>";
				}

				$calculate_tax = 0;

				if ( ! empty( $wc_extra_tax ) || $wc_use_taxes == "on" ) {

					if(!empty($wc_extra_tax)) {
						$tax_rate		= $wc_extra_tax;
						$wc_tax_id 		= wc_return_tax_id( $tax_rate );	
					} else {
						$tax_rate		= "0";
						$wc_tax_id 		= "0";
					}

					if ( $order_type != 'work_order' ) {
						$content .= "<td class='wc_tax'>";

					if ( $print_values != "YES" ) {
						$content .= '<select class="regular-text wc_extra_tax wc_small_select form-control" name="wc_extra_tax[]">';
						$content .= '<option value="">'.esc_html__("Select tax", "computer-repair-shop").'</option>';

						$wc_extra_tax_arr = array(
							"wc_default_tax_value"	=> $wc_tax_id,
							"value_type"		=> "tax_rate"
						);
						$content .= wc_generate_tax_options($wc_extra_tax_arr);	
						$content .= '</select>';
					} else {
						$content .= $tax_rate;
					}	
					$content .= "</td>";

					$content .= "<td class='wc_extra_tax_price'>";
					
					$total_price 	= (float)$wc_extra_price*(float)$wc_extra_qty;
					
					if(empty($tax_rate)) {
						$tax_rate = 0;
					}
					if ( $prices_inclu_exclu == 'inclusive' ) {
						$calculate_tax 	= $total_price*$tax_rate/(100+$tax_rate);
					} else {
						$calculate_tax 	= ($total_price/100)*$tax_rate;
					}
					
					$calculate_tax_disp = wc_cr_currency_format( $calculate_tax, FALSE, TRUE );
					$content .= $calculate_tax_disp;

					$content .= '</td>';
				}	
				}
				$wc_extra_price = (empty($wc_extra_price)) ? 0 : $wc_extra_price;
				$wc_extra_qty 	= (empty($wc_extra_qty)) ? 0 : $wc_extra_qty;

				$grand_total = ( $prices_inclu_exclu == 'inclusive' ) ? ( ( $wc_extra_price*$wc_extra_qty ) ) : ( ( $wc_extra_price*$wc_extra_qty ) + $calculate_tax );

				$total_price_disp = wc_cr_currency_format( $grand_total, FALSE, TRUE );

				$content .= ( $order_type != 'work_order' ) ? '<td class="wc_extra_price_total">'. $total_price_disp .'</td>' : '';
				$content .= '</tr>';
			}
			return $content;
		}
	endif;

	if ( ! function_exists( 'return_job_device_options' ) ) :
		function return_job_device_options( $job_id, $selected_device, $serial_number, $type, $format ) {
			if ( empty( $type ) ) {
				return '';
			}

			switch ( $type ) {
				case "service":
					$nameField = 'wc_service_device[]';
					break;
				case "part":
					$nameField = 'wc_part_device[]';
					break;
				case "product":
					$nameField = 'wc_product_device[]';
					break;
				case "extra":
					$nameField = 'wc_extra_device[]';
					break;
				default:
					$nameField = 'unknown_device_type[]';
			}
			$wc_device_label = ( empty( get_option( 'wc_device_label' ) ) ) ? esc_html__( 'Device', 'computer-repair-shop' ) : get_option( 'wc_device_label' );

			$select_start = '<select data-label="' . esc_html( $wc_device_label ) . '" class="regular-text '. $nameField .' thedevice_selecter_identity wc_small_select form-control" name="' . $nameField . '">';
			$select_option = '<option value="">' . esc_html( $wc_device_label ) . '</option>';
			$select_end = '</select>';

			if ( empty( $job_id ) ) {
				return $select_start . $select_option . $select_end;
			}
			
			$wc_device_data = get_post_meta( $job_id, '_wc_device_data', true );

			if ( empty( $wc_device_data ) ) {
				wc_set_new_device_format( $job_id );
				$wc_device_data = get_post_meta( $job_id, '_wc_device_data', true );
			}
			$wc_pin_code_field       = get_option( 'wc_pin_code_field' );

			if ( is_array( $wc_device_data ) && !empty( $wc_device_data ) ) :
				$count = count( $wc_device_data );
				$selected_device_r = ( ! empty( $serial_number ) && ! empty( $selected_device ) ) ? $selected_device . '_' . $serial_number : $selected_device;

				foreach ( $wc_device_data as $device_data ) :
					$device_post_id = ( isset($device_data['device_post_id']) ) ? $device_data['device_post_id'] : '';
					$serial_number  = ( isset( $device_data['device_id'] ) && ! empty( $device_data['device_id'] ) ) ? $device_data['device_id'] : '';

					$device_post_id_p = ( ! empty( $serial_number ) ) ? $device_post_id . '_' . $serial_number : $device_post_id;

					$serial_number_v = ( ! empty( $serial_number ) ) ? ' (' . $serial_number . ')' : '';
					$selected 		= ( $device_post_id_p == $selected_device_r ) ? ' selected' : '';
					$selected 		= ( empty( $selected ) && $count == 1 ) ? ' selected' : $selected;
					$select_option .= '<option '. $selected .' value="' . $device_post_id_p . '">' . return_device_label( $device_post_id_p ) . $serial_number_v . '</option>';

				endforeach;
			endif;
			return $select_start . $select_option . $select_end;
		}
	endif;

	if ( ! function_exists( 'return_device_name_if_more_than_one' ) ) :
		function return_device_name_if_more_than_one( $job_id, $device_id ) {
			if ( empty( $job_id ) || empty( $device_id ) ) {
				return;
			}
			$wc_device_data = get_post_meta( $job_id, '_wc_device_data', true );

			if ( empty( $wc_device_data ) ) {
				wc_set_new_device_format( $job_id );
				$wc_device_data = get_post_meta( $job_id, '_wc_device_data', true );
			}

			if ( is_array( $wc_device_data ) && ! empty( $wc_device_data ) ) :
				$count = count( $wc_device_data );

				if ( $count > 1 ) {
					$theTitle = ( ! empty( $device_id ) ) ? return_device_label( $device_id ) : '';
					return ( ! empty( $theTitle ) ) ? ' - ' . $theTitle : '';
				}
			endif;
		}
	endif;

	if ( ! function_exists( "wc_update_parts_row" ) ) {
		function wc_update_parts_row() {
			
			if ( ! isset( $_POST['product'] ) || empty( $_POST['product'] ) ) {
				$values['row'] = esc_html__( 'No ID selected', 'computer-repair-shop' );
			} elseif ( isset( $_POST['product_type'] ) && $_POST['product_type'] == 'woo' && ! empty( $_POST['product'] ) ) {
				$product_obj 	= wc_get_product( sanitize_text_field( $_POST['product'] ) );
				$prices_inclu_exclu = ( isset( $_POST['prices_inclu_exclu'] ) && ! empty( $_POST['prices_inclu_exclu'] ) ) ? sanitize_text_field( $_POST['prices_inclu_exclu'] ) : '';

				$prices_inclu_exclu = ( $prices_inclu_exclu == 'exclusive' || $prices_inclu_exclu == 'inclusive' ) ? $prices_inclu_exclu : 'exclusive';

				$product_id 	= $product_obj->get_id();

				$wc_use_taxes 		= get_option("wc_use_taxes");
				$wc_primary_tax		= get_option("wc_primary_tax");

				$wc_part_tax_value 	= $wc_primary_tax;

				$part_name 			= $product_obj->get_name();
				$part_code 			= $product_obj->get_sku();
				$part_price 		= $product_obj->get_price();

				$content = "<tr class='item-row wc_product_row'>";
				$content .= "<td class='wc_product_name'><a class='delme' href='#' title='Remove row'>X</a>";
				$content .= $part_name."<input type='hidden' name='wc_product_id[]' value='".$product_id."' /><input type='hidden' name='wc_product_name[]' value='".$part_name."'></td>";
				$content .= "<td class='wc_product_sku'>".$part_code."<input type='hidden' name='wc_product_sku[]' value='".$part_code."'></td>";
				$content .= "<td class='wc_product_device'>" . return_job_device_options( '', '', '', 'product', 'html' ) . "</td>";
				$content .= "<td class='wc_qty'><input type='number' step='any' class='wc_validate_number wc_special_input' name='wc_product_qty[]' value='1' /></td>";
				$content .= "<td class='wc_price'><input type='number' step='any' class='wc_validate_number wc_special_input' name='wc_product_price[]' value='".$part_price."' /></td>";

				if($wc_use_taxes == "on"):
					$content .= "<td class='wc_tax'>";
					$content .= '<select class="regular-text wc_part_tax wc_small_select form-control" name="wc_product_tax[]">';
					$content .= '<option value="">' . esc_html__( 'Select tax', 'computer-repair-shop' ) . '</option>';

					$wc_part_tax_arr = array(
						'wc_default_tax_value'	=> $wc_part_tax_value,
						'value_type'			=> 'tax_rate'
					);
					$content .= wc_generate_tax_options( $wc_part_tax_arr );	
					$content .= '</select>';
					$content .= "</td>";

					$content .= "<td class='wc_product_tax_price'>";
					
					$tax_rate		= wc_return_tax_rate( $wc_part_tax_value );

					if ( $prices_inclu_exclu == 'inclusive' ) {
						//172.50 x 15 / (100+15)
						$calculate_tax 	= $part_price*$tax_rate/(100+$tax_rate);
					} else {
						$calculate_tax 	= ($part_price/100)*$tax_rate;
					}

					$formatted_calculate_tax = wc_cr_currency_format( $calculate_tax, FALSE, TRUE );

					$content .= $formatted_calculate_tax;

					$content .= "</td>";
				endif;	

				if ( ! isset( $calculate_tax ) ) { $calculate_tax = 0; }

				$the_part_total = ( $prices_inclu_exclu == 'inclusive' ) ? $part_price : $part_price+$calculate_tax;
				$the_part_total = wc_cr_currency_format( $the_part_total, FALSE, TRUE );

				$content .= "<td class='wc_product_price_total'>" . $the_part_total . "</td>";

				$content .= "</tr>";
				
				$values['row'] = $content;

			} else {
				//Get data here.	
				$values['row'] = wc_add_part_product_data( $_POST );
			}
			
			wp_send_json( $values );
			wp_die();
		}
		add_action( 'wp_ajax_wc_update_parts_row', 'wc_update_parts_row' );
	}

	if ( ! function_exists( 'wc_add_part_product_data' ) ) :
		function wc_add_part_product_data( $_args ) {
			$return = '';

			if (!isset( $_args['data_security'] ) || ! wp_verify_nonce( $_args['data_security'], 'add-products' ) ) :
				$return = esc_html__( "Something is wrong with your submission!", "computer-repair-shop" );
			else:
				$part_id 	= ( isset( $_args['product'] ) ) ? sanitize_text_field( $_args['product'] ) : '';
				$device_id  = ( isset( $_args['device_id'] ) ) ? sanitize_text_field( $_args['device_id'] ) : '';
				$sub_part = '';
				$prices_inclu_exclu = ( isset( $_args['prices_inclu_exclu'] ) && ! empty( $_args['prices_inclu_exclu'] ) ) ? sanitize_text_field( $_args['prices_inclu_exclu'] ) : '';
				$prices_inclu_exclu = ( $prices_inclu_exclu == 'exclusive' || $prices_inclu_exclu == 'inclusive' ) ? $prices_inclu_exclu : 'exclusive';

				//28572 _POST
				//parentid_28630__part_id_990040
				if ( str_contains( $part_id, 'parentid_' ) ) {
					//Let's bring out parent
					$partarr = explode( '__', $part_id );
					$sub_part = $partarr[1];
					$part_id = $partarr[0];
					$part_id = str_replace( 'parentid_', '', $part_id );
				}

				if ( is_numeric( $part_id ) ) {
					$post_obj = get_post( $part_id );
					$post_id  = $post_obj->ID;

					$wc_use_taxes 		= get_option( 'wc_use_taxes' );
					$wc_primary_tax		= get_option( 'wc_primary_tax' );
					$wc_special_tax 	= get_post_meta( $post_id, '_wc_use_tax', true );
					
					$wc_part_tax_value 	= ( empty( $wc_special_tax ) ) ? $wc_primary_tax :  $wc_special_tax;

					$WCRB_DEVICE_PARTS = WCRB_DEVICE_PARTS::getInstance();
					$received_array = $WCRB_DEVICE_PARTS->get_price_by_device_for_part( $device_id, $part_id, $sub_part );

					if ( ! empty( $sub_part ) ) {
						$_Vpart = ( $sub_part == 'default' ) ? '' : $sub_part;
						$part_id = $post_id . '__' . $sub_part;

						$part_name 		= get_post_meta( $post_id, $_Vpart . '_part_title', true );
						$part_code 		= $received_array['stock_code'];
						$part_capacity 	= get_post_meta( $post_id, $_Vpart . '_capacity', true );
						$part_price 	= $received_array['price'];
					} else {
						$part_name = get_post_meta( $post_id, '_part_title', true );

						$part_code 		= $received_array['stock_code'];
						$part_capacity 	= get_post_meta( $post_id, '_capacity', true );
						$part_price 	= $received_array['price'];
					}
					$part_name = ( empty( $part_name ) ) ? $post_obj->post_title : $part_name;
					
					
					$content = "<tr class='item-row wc_part_row'>";
					$content .= "<td class='wc_part_name'><a class='delme' href='#' title='Remove row'>X</a>";
					$content .= $part_name."<input type='hidden' name='wc_part_id[]' value='".$part_id."' /><input type='hidden' name='wc_part_name[]' value='".$part_name."'></td>";
					$content .= "<td class='wc_part_code'>".$part_code."<input type='hidden' name='wc_part_code[]' value='".$part_code."'></td>";
					$content .= "<td class='wc_capacity'>".$part_capacity."<input type='hidden' name='wc_part_capacity[]' value='".$part_capacity."'></td>";
					$content .= "<td class='wc_part_device'>" . return_job_device_options( '', $device_id, '', 'part', 'html' ) . "</td>";
					$content .= "<td class='wc_qty'><input type='number' step='any' class='wc_validate_number wc_special_input' name='wc_part_qty[]' value='1' /></td>";
					$content .= "<td class='wc_price'><input type='number' step='any' class='wc_validate_number wc_special_input' name='wc_part_price[]' value='".$part_price."' /></td>";

					if($wc_use_taxes == "on"):
						$content .= "<td class='wc_tax'>";
						$content .= '<select class="regular-text wc_part_tax wc_small_select form-control" name="wc_part_tax[]">';
						$content .= '<option value="">' . esc_html__( 'Select tax', 'computer-repair-shop' ) . '</option>';

						$wc_part_tax_arr = array(
							'wc_default_tax_value'	=> $wc_part_tax_value,
							'value_type'			=> 'tax_rate'
						);
						$content .= wc_generate_tax_options( $wc_part_tax_arr );	
						$content .= '</select>';
						$content .= "</td>";

						$content .= "<td class='wc_part_tax_price'>";
						
						$tax_rate = wc_return_tax_rate( $wc_part_tax_value );
						
						$tax_rate = (float)$tax_rate;
						$part_price = (float)$part_price;

						if ( $prices_inclu_exclu == 'inclusive' ) {
							//172.50 x 15 / (100+15)
							$calculate_tax 	= $part_price*$tax_rate/(100+$tax_rate);
						} else {
							$calculate_tax 	= ($part_price/100)*$tax_rate;
						}

						$formatted_calculate_tax = wc_cr_currency_format( $calculate_tax, FALSE, TRUE );

						$content .= $formatted_calculate_tax;
						$content .= "</td>";
					endif;	

					if(!isset($calculate_tax)) { $calculate_tax = 0; }
					
					$the_part_total = ( $prices_inclu_exclu == 'inclusive' ) ? $part_price : $part_price+$calculate_tax;

					$the_part_total = wc_cr_currency_format( $the_part_total, FALSE, TRUE );

					$content .= "<td class='wc_price_total'>" . $the_part_total . "</td>";
					$content .= "</tr>";
					
					$return = $content;
				} else {
					$return = esc_html__( "Something is wrong with your submission!", "computer-repair-shop" );
				}
			endif;

			return $return;
		}
	endif;

	if(!function_exists("wc_update_services_row")) {
		function wc_update_services_row() {
			global $WCRB_DEVICE_SERVICES;

			if(!isset($_POST["service"]) || empty($_POST["service"])) {
				$values['row'] = 'No ID selected';
			} else {
				$post_obj 		= get_post(sanitize_text_field($_POST['service']));
				$post_id 		= $post_obj->ID;

				$prices_inclu_exclu = ( isset( $_POST['prices_inclu_exclu'] ) && ! empty( $_POST['prices_inclu_exclu'] ) ) ? sanitize_text_field( $_POST['prices_inclu_exclu'] ) : '';
				$prices_inclu_exclu = ( $prices_inclu_exclu == 'exclusive' || $prices_inclu_exclu == 'inclusive' ) ? $prices_inclu_exclu : 'exclusive';

				$wc_use_taxes 		= get_option("wc_use_taxes");
				$wc_primary_tax		= get_option("wc_primary_tax");
				$wc_special_tax 	= get_post_meta( $post_id, '_wc_use_tax', true );
				
				$wc_service_tax_value 	= '';

				if ( empty( $wc_special_tax ) ) {
					$wc_service_tax_value = $wc_primary_tax;	
				} else {
					$wc_service_tax_value = $wc_special_tax;
				}
				
				$device_id = '';
				if ( isset( $_POST['devices'] ) ) {
					foreach( $_POST['devices'] as $device ) {
						$device_id = sanitize_text_field( $device['value'] );
					}
				}

				$service_name 	= $post_obj->post_title;
				$service_code  	= get_post_meta( $post_id, "_service_code", true );
				if ( ! empty( $device_id ) ) {
					$service_price = $WCRB_DEVICE_SERVICES->get_price_by_device_for_service( $device_id, $post_id );
				}
				$service_price 	= ( empty( $service_price ) ) ? get_post_meta( $post_id, "_cost", true ) : $service_price;
				
				$content = "<tr class='item-row wc_service_row'>";
				$content .= "<td class='wc_service_name'><a class='delme' href='#' title='Remove row'>X</a>";
				$content .= $service_name."<input type='hidden' name='wc_service_id[]' value='".$post_id."' /><input type='hidden' name='wc_service_name[]' value='".$service_name."' /></td>";
				$content .= "<td class='wc_service_code'>".$service_code."<input type='hidden' name='wc_service_code[]' value='".$service_code."' /></td>";
				$content .= "<td class='wc_service_device'>" . return_job_device_options( '', '', '', 'service', 'html' ) . "</td>";
				$content .= "<td class='wc_service_qty'><input type='number' step='any' class='wc_validate_number wc_special_input' name='wc_service_qty[]' value='1' /></td>";
				$content .= "<td class='wc_service_price'><input type='number' step='any' class='wc_validate_number wc_special_input' name='wc_service_price[]' value='".$service_price."' /></td>";

				if ( $wc_use_taxes == 'on' ) :
					$content .= "<td class='wc_tax'>";
					$content .= '<select class="regular-text wc_service_tax wc_small_select form-control" name="wc_service_tax[]">';
					$content .= '<option value="">'.esc_html__("Select tax", "computer-repair-shop").'</option>';

					$wc_service_tax_arr = array(
						"wc_default_tax_value"	=> $wc_service_tax_value,
						"value_type"		=> "tax_rate"
					);
					$content .= wc_generate_tax_options( $wc_service_tax_arr );	
					$content .= '</select>';
					$content .= "</td>";

					$content .= "<td class='wc_service_tax_price'>";
					
					$tax_rate = wc_return_tax_rate( $wc_service_tax_value );
					
					if ( $prices_inclu_exclu == 'inclusive' ) {
						$calculate_tax 	= $service_price*$tax_rate/(100+$tax_rate);
					} else {
						$calculate_tax 	= ($service_price/100)*$tax_rate;
					}

					$formatted_calculate_tax = wc_cr_currency_format( $calculate_tax, FALSE, TRUE );

					$content .= $formatted_calculate_tax;

					$content .= "</td>";
				endif;	

				if(!isset($calculate_tax)) { $calculate_tax = 0; }

				$the_service_total = ( $prices_inclu_exclu == 'inclusive' ) ? $service_price : $service_price+$calculate_tax;

				$the_service_total = wc_cr_currency_format( $the_service_total, FALSE, TRUE );

				$content .= "<td class='wc_service_price_total'>" . $the_service_total . "</td>";
				$content .= "</tr>";
				
				$values['row'] = $content;
			}
			
			wp_send_json($values);
			wp_die();
		}
		add_action( 'wp_ajax_wc_update_services_row', 'wc_update_services_row' );
	}

	if(!function_exists("wc_update_extra_row")) {
		function wc_update_extra_row() {
			
			if(!isset($_POST["extra"]) || empty($_POST["extra"])) {
				$values['row'] = esc_html__('No ID selected', "computer-repair-shop");
			} else {
				$wc_use_taxes 		= get_option("wc_use_taxes");
				$wc_primary_tax		= get_option("wc_primary_tax");
								
				$wc_extra_tax_value 	= $wc_primary_tax;

				$content = "<tr class='item-row wc_extra_row'>";
				$content .= "<td class='wc_extra_name'><a class='delme' href='#' title='Remove row'>X</a>";
				$content .= "<input type='text' class='wc_special_input' name='wc_extra_name[]' value='' placeholder='".esc_html__("Extra name here...", "computer-repair-shop")."' /></td>";
				$content .= "<td class='wc_extra_code'><input type='text' class='wc_special_input' name='wc_extra_code[]' value='' /></td>";
				$content .= "<td class='wc_extra_device'>" . return_job_device_options( '', '', '', 'extra', 'html' ) . "</td>";
				$content .= "<td class='wc_extra_qty'><input type='number' step='any' class='wc_validate_number wc_special_input' name='wc_extra_qty[]' value='0' /></td>";
				$content .= "<td class='wc_extra_price'><input type='number' step='any' class='wc_validate_number wc_special_input' name='wc_extra_price[]' value='' /></td>";

				if($wc_use_taxes == "on"):
					$content .= "<td class='wc_tax'>";
					$content .= '<select class="regular-text wc_extra_tax wc_small_select form-control" name="wc_extra_tax[]">';
					$content .= '<option value="">'.esc_html__("Select tax", "computer-repair-shop").'</option>';

					$wc_part_tax_arr = array(
						"wc_default_tax_value"	=> $wc_extra_tax_value,
						"value_type"		=> "tax_rate"
					);
					$content .= wc_generate_tax_options($wc_part_tax_arr);	
					$content .= '</select>';
					$content .= "</td>";

					$content .= "<td class='wc_extra_tax_price'>";
					$content .= "0";
					$content .= "</td>";
				endif;

				$content .= "<td class='wc_extra_price_total'>0</td>";
				$content .= "</tr>";
				
				$values['row'] = $content;
			}
			
			wp_send_json($values);
			wp_die();
		}
		add_action( 'wp_ajax_wc_update_extra_row', 'wc_update_extra_row');
	}

	//wc_update_user_form
	function wc_add_user_form() {
		wp_enqueue_script("intl-tel-input");
		wp_enqueue_style("intl-tel-input");

		add_action( 'admin_print_footer_scripts', 'wcrb_intl_tel_input_script_admin' );
	?>
	<!-- Modal for Post Entry /-->
	<div class="small reveal" id="customerFormReveal" data-reveal>
		<h2><?php echo esc_html__("Add a new customer", "computer-repair-shop"); ?></h2>

		<div class="form-message"></div>

		<form data-async data-abide class="needs-validation" novalidate method="post">
			<div class="grid-x grid-margin-x">
				<div class="cell">
					<div data-abide-error class="alert callout" style="display: none;">
						<p><i class="fi-alert"></i> <?php echo esc_html__("There are some errors in your form.", "computer-repair-shop"); ?></p>
					</div>
				</div>
			</div>

			<!-- Login Form Starts /-->
			<div class="grid-x grid-margin-x">

				<div class="cell medium-6">
					<label><?php echo esc_html__("First Name", "computer-repair-shop"); ?>*
						<input name="reg_fname" type="text" class="form-control login-field"
							   value="" required id="reg-fname"/>
						<span class="form-error">
							<?php echo esc_html__("First Name Is Required.", "computer-repair-shop"); ?>
						</span>
					</label>
				</div>

				<div class="cell medium-6">
					<label for="cLastName"><?php echo esc_html__("Last Name", "computer-repair-shop"); ?>
						<input name="reg_lname" type="text" class="form-control login-field"
							   value="" id="cLastName" />
					</label>
				</div>

			</div>

			<div class="grid-x grid-margin-x">

				<div class="cell medium-6">
					<label><?php echo esc_html__("Email", "computer-repair-shop"); ?>
						<input name="reg_email" type="email" class="form-control login-field"
							   value="" id="reg-email"/>
					</label>
				</div>

				<div class="cell medium-6">
					<label><?php echo esc_html__("Phone Number", "computer-repair-shop"); ?>
						<input name="customer_phone_ol" type="text" class="customer_phone_ol form-control login-field" value="" id="customer_phone_ol" />
					</label>
				</div>

			</div>
			<!-- Login Form Ends /-->

			<div class="grid-x grid-margin-x">
				<div class="cell medium-6">
					<label><?php echo esc_html__("Company", "computer-repair-shop"); ?>
						<input name="customer_company" type="text" class="form-control login-field"
							value="" id="customer_company" />
					</label>
				</div>

				<div class="cell medium-6">
					<label><?php echo esc_html__( "Tax ID", "computer-repair-shop"); ?>
						<input name="billing_tax" type="text" class="form-control login-field"
							value="" id="billing_tax" />
					</label>
				</div>

				<div class="cell medium-12">
					<label><?php echo esc_html__("Address", "computer-repair-shop"); ?>
						<input name="customer_address" type="text" class="form-control login-field"
							value="" id="customer_address" />
					</label>
				</div>	
			</div>
			<!-- Login Form Ends /-->

			<div class="grid-x grid-margin-x">
				<div class="cell medium-6">
					<label><?php echo esc_html__("Postal/Zip Code", "computer-repair-shop"); ?>
						<input name="zip_code" type="text" class="form-control login-field"
							value="" id="zip_code" />
					</label>
				</div>

				<div class="cell medium-6">
					<label><?php echo esc_html__("City", "computer-repair-shop"); ?>
						<input name="customer_city" type="text" class="form-control login-field"
							value="" id="customer_city" />
					</label>
				</div>
			</div>

			<div class="grid-x grid-margin-x">
				<div class="cell medium-6">
					<label><?php echo esc_html__("State/Province", "computer-repair-shop"); ?>
						<input name="state_province" type="text" class="form-control login-field"
							value="" id="state_province" />
					</label>
				</div>

				<div class="cell medium-6">
					<label><?php echo esc_html__("Country", "computer-repair-shop"); ?>
						<select name="country" id="country" class="form-control">
							<?php 
								$country = (get_option("wc_primary_country")) ? get_option("wc_primary_country") : "";
								$allowed_html = wc_return_allowed_tags();
								$optionsGenerated = wc_cr_countries_dropdown( $country, 'return' );
								echo wp_kses( $optionsGenerated, $allowed_html );
							?>
						</select>	
					</label>
				</div>
			</div>
			<?php wp_nonce_field( 'wcrb_nonce_addclient', 'wcrb_nonce_addclient_field', true, true ); ?>
			<div class="grid-x grid-margin-x">
				<fieldset class="cell medium-6">
					<button class="button" type="submit" value="Submit">
						<?php echo esc_html__("Add Customer", "computer-repair-shop"); ?>
					</button>
				</fieldset>
				<small>
					<?php echo esc_html__("(*) fields are required", "computer-repair-shop"); ?>
				</small>	
			</div>
		</form>

		<button class="close-button" data-close aria-label="Close modal" type="button">
			<span aria-hidden="true">&times;</span>
		</button>
	</div>
	<?php
	}

	/*
		* Update User Data
		*
		* Accepts only Technician and Customer user types.
		* Returns Success or Failure
	*/
	if ( ! function_exists( "wc_update_user_data" ) ) {
		function wc_update_user_data() {
			$values = array();

			if (!isset( $_POST['wcrb_updateuser_nonce_post'] ) 
            || ! wp_verify_nonce( $_POST['wcrb_updateuser_nonce_post'], 'wcrb_updateuser_nonce' )) {
                $values['message'] = esc_html__( 'Something is wrong with your submission!', 'computer-repair-shop' );
                $values['success'] = "YES";
			} else {
				if ( isset( $_POST["update_user"] ) && ! empty( $_POST["update_user"] ) ) {
					$user_id = sanitize_text_field( $_POST["update_user"] );	

					$user_role   = wc_get_user_roles_by_user_id( $user_id );
					
					$message = esc_html__( 'Something is wrong with your submission!', 'computer-repair-shop' );

					$_current_user_id = get_current_user_id();
					$_current_user_role = wc_get_user_roles_by_user_id( $_current_user_id );

					if ( in_array( 'store_manager', $_current_user_role ) || in_array( 'editor', $_current_user_role ) || in_array( 'technician', $_current_user_role ) || in_array( 'customer', $_current_user_role ) || in_array( 'administrator', $_current_user_role ) ) {
						if ( in_array( 'customer', $_current_user_role ) && $_current_user_id != $user_id ) {
							$message = esc_html__( 'Something is wrong with your submission!', 'computer-repair-shop' );
						} elseif ( in_array( 'technician', $_current_user_role ) && ! in_array( 'customer', $user_role ) ) {
							$message = esc_html__( 'Something is wrong with your submission!', 'computer-repair-shop' );
						} elseif ( in_array( 'customer', $user_role ) || in_array( 'technician', $user_role ) ) {
							$first_name = ( isset( $_POST["reg_fname"] ) ) ? sanitize_text_field( $_POST["reg_fname"] ) : '';
							$last_name = ( isset( $_POST["reg_lname"] ) ) ? sanitize_text_field($_POST["reg_lname"]) : '';
							$useremail = ( isset( $_POST["reg_email"] ) ) ? sanitize_email($_POST["reg_email"]) : '';

							$user_fields = array(
								'ID'           => $user_id,
								'first_name'   => $first_name,
								'last_name'    => $last_name,
								'user_email'   => $useremail,
							);
							
							$user_data = wp_update_user($user_fields);

							if ( is_wp_error($user_data) ) {
								// There was an error; possibly this user doesn't exist.
								$message = "Error ".$user_data->get_error_message();
							} else {
								// Success!
								$customer_phone 	= sanitize_text_field($_POST["customer_phone"]);
								$customer_city 		= sanitize_text_field($_POST["customer_city"]);
								$zip_code 			= sanitize_text_field($_POST["zip_code"]);
								$company 			= ($_POST["customer_company"])? sanitize_text_field($_POST["customer_company"]) : "";
								$billing_tax 		= ($_POST["billing_tax"])? sanitize_text_field($_POST["billing_tax"]) : "";
								$customer_address 	= ($_POST["customer_address"])? sanitize_text_field($_POST["customer_address"]) : "";
								$state 				= ($_POST["state_province"])? sanitize_text_field($_POST["state_province"]) : "";
								$country 			= ($_POST["country"])? sanitize_text_field($_POST["country"]) : "";
				
								$company 			= esc_attr($company);
								$customer_address	= esc_attr($customer_address);
				
								$update_type 		= ($_POST["update_type"])? "technician" : "customer";
				
								update_user_meta( $user_id, 'billing_first_name', $first_name );
								update_user_meta( $user_id, 'billing_last_name', $last_name );
								update_user_meta( $user_id, 'billing_company', $company );
								update_user_meta( $user_id, 'billing_tax', $billing_tax );
								update_user_meta( $user_id, 'billing_address_1', $customer_address );
								update_user_meta( $user_id, 'billing_city', $customer_city );
								update_user_meta( $user_id, 'billing_postcode', $zip_code );
								update_user_meta( $user_id, 'billing_state', $state );
								update_user_meta( $user_id, 'billing_country', $country );
								update_user_meta( $user_id, 'billing_phone', $customer_phone );
				
								update_user_meta( $user_id, 'billing_email', $useremail );
				
								update_user_meta( $user_id, 'shipping_first_name', $first_name );
								update_user_meta( $user_id, 'shipping_last_name', $last_name );
								update_user_meta( $user_id, 'shipping_company', $company );
								update_user_meta( $user_id, 'shipping_tax', $billing_tax );
								update_user_meta( $user_id, 'shipping_address_1', $customer_address );
								update_user_meta( $user_id, 'shipping_city', $customer_city );
								update_user_meta( $user_id, 'shipping_postcode', $zip_code );
								update_user_meta( $user_id, 'shipping_state', $state );
								update_user_meta( $user_id, 'shipping_country', $country );
								update_user_meta( $user_id, 'shipping_phone', $customer_phone );
				
								$message = esc_html__("User Updated!", "computer-repair-shop");
							}
						}
					}
					
					$values['message'] = $message;
					$values['success'] = "YES";
				} else {
					$values['message'] = esc_html__("Something is wrong with your submission!", "computer-repair-shop");
					$values['success'] = "YES";
				}
			}

			wp_send_json( $values );
			wp_die();
		}
		add_action( 'wp_ajax_wc_update_user_data', 'wc_update_user_data' );
	}


	/*
		* Generate Reveal Form
		*
		* Form Handles User update.
	*/
	if(!function_exists("wc_update_user_form")):
		function wc_update_user_form() {
			wp_enqueue_script("intl-tel-input");
			wp_enqueue_style("intl-tel-input");

			add_action( 'admin_print_footer_scripts', 'wcrb_intl_tel_input_script_admin' );

			if ( isset( $_GET["page"] ) ) {
				if($_GET["page"] == "wc-computer-rep-shop-clients") {
					$update_label 	= esc_html__("Customer", "computer-repair-shop");
					$update_role	= "customer";
				}elseif($_GET["page"] == "wc-computer-rep-shop-managers") {
					$update_label 	= esc_html__("Store Manager", "computer-repair-shop");
					$update_role	= "store_manager";	
				} else {
					$update_label 	= esc_html__("Technician", "computer-repair-shop");
					$update_role	= "technician";
				}	
			} else {
				return;
			}

			if ( isset( $_GET['update_user'] ) ) {
				$user_id = 	sanitize_text_field( $_GET['update_user']  );
				$user 	 = get_user_by( 'ID', $user_id );
				
				if ( $user ) {
					$user_role		= $user->roles;
					
					if ( in_array( $update_role, (array) $user_role ) ) {
						//The user has the "author" role
						$first_name		= $user->first_name;
						$last_name		= $user->last_name;
						$user_email		= $user->user_email;

						$phone_number 	= get_user_meta( $user_id, 'billing_phone', true );
						$company 		= get_user_meta( $user_id, 'billing_company', true );
						$billing_tax 	= get_user_meta( $user_id, 'billing_tax', true );
						$address 		= get_user_meta( $user_id, 'billing_address_1', true );
						$city 			= get_user_meta( $user_id, 'billing_city', true );
						$zip_code 		= get_user_meta( $user_id, 'billing_postcode', true );
						$state 			= get_user_meta( $user_id, 'billing_state', true );
						$country 		= get_user_meta( $user_id, 'billing_country', true );
						$country 		= ( empty( $country ) ) ? get_option( 'wc_primary_country' ) : $country;

					}

				} else { return; }	

			} else { return; }
			?>

			<!-- Modal for Post Entry /-->
			<div class="small reveal" id="updateUserFormReveal" data-reveal="active">
				<h2><?php echo esc_html__("Update", "computer-repair-shop")." ".esc_html($update_label); ?></h2>
				<div class="form-message"></div>
		
				<form data-async data-abide class="needs-validation" novalidate method="post">
					<div class="grid-x grid-margin-x">
						<div class="cell">
							<div data-abide-error class="alert callout" style="display: none;">
								<p><i class="fi-alert"></i> <?php echo esc_html__("There are some errors in your form.", "computer-repair-shop"); ?></p>
							</div>
						</div>
					</div>
		
					<!-- Login Form Starts /-->
					<div class="grid-x grid-margin-x">
		
						<div class="cell medium-6">
							<label><?php echo esc_html__("First Name", "computer-repair-shop"); ?>*
								<input name="reg_fname" type="text" class="form-control login-field"
									value="<?php echo esc_html($first_name); ?>" required id="reg-fname"/>
								<span class="form-error">
									<?php echo esc_html__("First Name Is Required.", "computer-repair-shop"); ?>
								</span>
							</label>
						</div>
		
						<div class="cell medium-6">
							<label for="lastName"><?php echo esc_html__("Last Name", "computer-repair-shop"); ?>
								<input name="reg_lname" type="text" class="form-control login-field"
									value="<?php echo esc_html($last_name); ?>" id="lastName"/>
							</label>
						</div>
		
					</div>
		
					<div class="grid-x grid-margin-x">
		
						<div class="cell medium-6">
							<label><?php echo esc_html__("Email", "computer-repair-shop"); ?>*
								<input name="reg_email" type="email" class="form-control login-field"
									value="<?php echo esc_html($user_email); ?>" id="reg-email"/>
								<span class="form-error">
									<?php echo esc_html__("Email Is Required.", "computer-repair-shop"); ?>
								</span>
							</label>
						</div>
		
						<div class="cell medium-6">
							<label><?php echo esc_html__("Phone Number", "computer-repair-shop"); ?>
								<input name="customer_phone_ol" type="text" class="customer_phone_ol form-control login-field"
									value="<?php echo esc_html($phone_number); ?>" id="customer_phone" />
							</label>
						</div>
		
					</div>
					<!-- Login Form Ends /-->
		
					<div class="grid-x grid-margin-x">
						<div class="cell medium-6">
							<label><?php echo esc_html__("Company", "computer-repair-shop"); ?>
								<input name="customer_company" type="text" class="form-control login-field"
									value="<?php echo esc_html($company); ?>" id="customer_company" />
							</label>
						</div>

						<div class="cell medium-6">
							<label><?php echo esc_html__("Tax ID", "computer-repair-shop"); ?>
								<input name="billing_tax" type="text" class="form-control login-field"
									value="<?php echo esc_html($billing_tax); ?>" id="billing_tax" />
							</label>
						</div>
					</div>

					<div class="grid-x grid-margin-x">
						<div class="cell medium-12">
							<label><?php echo esc_html__("Address", "computer-repair-shop"); ?>
								<input name="customer_address" type="text" class="form-control login-field"
									value="<?php echo esc_html($address); ?>" id="customer_address" />
							</label>
						</div>
					</div>

					<div class="grid-x grid-margin-x">

						<div class="cell medium-6">
							<label><?php echo esc_html__("Postal/Zip Code", "computer-repair-shop"); ?>
								<input name="zip_code" type="text" class="form-control login-field"
									value="<?php echo esc_html($zip_code); ?>" id="zip_code" />
							</label>
						</div>

						<div class="cell medium-6">
							<label><?php echo esc_html__("City", "computer-repair-shop"); ?>
								<input name="customer_city" type="text" class="form-control login-field"
									value="<?php echo esc_html($city); ?>" id="customer_city" />
							</label>
						</div>
					</div>
					<!-- Login Form Ends /-->

					<div class="grid-x grid-margin-x">

						<div class="cell medium-6">
							<label><?php echo esc_html__("State/Province", "computer-repair-shop"); ?>
								<input name="state_province" type="text" class="form-control login-field"
									value="<?php echo esc_html($state); ?>" id="state_province" />
							</label>
						</div>

						<div class="cell medium-6">
							<label><?php echo esc_html__("Country", "computer-repair-shop"); ?>
								<select name="country" id="country" class="form-control">
									<?php 
										$allowed_html = wc_return_allowed_tags();
										$optionsGenerated = wc_cr_countries_dropdown( $country, 'return' );
										echo wp_kses( $optionsGenerated, $allowed_html );
									?>
								</select>
							</label>
						</div>
					</div>
					<!-- Login Form Ends /-->

					<?php wp_nonce_field( 'wcrb_updateuser_nonce', 'wcrb_updateuser_nonce_post', true, true ); ?>
					<input type="hidden" name="form_type" value="update_user" />
					<input type="hidden" name="update_type" value="<?php echo esc_html($update_role); ?>" />
					<input type="hidden" name="update_user" value="<?php echo esc_html($user_id); ?>" />
					<div class="grid-x grid-margin-x">
						<fieldset class="cell medium-6">
							<button class="button" type="submit" value="Submit"><?php echo esc_html__("Update ", "computer-repair-shop").esc_html($update_role); ?></button>
						</fieldset>
						<small>
							<?php echo esc_html__("(*) fields are required", "computer-repair-shop"); ?>
						</small>	
					</div>
				</form>
		
				<button class="close-button" data-close aria-label="Close modal" type="button">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<?php
			}
		endif;

	/***
	 * Add Technician Form Modal into Footer
	 * 
	*/
	function wc_add_technician_form() {
		wp_enqueue_script("intl-tel-input");
		wp_enqueue_style("intl-tel-input");

		add_action( 'admin_print_footer_scripts', 'wcrb_intl_tel_input_script_admin' );
	?>
		<!-- Modal for Post Entry /-->
		<div class="small reveal" id="technicianFormReveal" data-reveal>
			<h2><?php echo esc_html__("Add a new technician", "computer-repair-shop"); ?></h2>
	
			<div class="form-message"></div>
	
			<form data-async data-abide class="needs-validation" novalidate method="post">
				<div class="grid-x grid-margin-x">
					<div class="cell">
						<div data-abide-error class="alert callout" style="display: none;">
							<p><i class="fi-alert"></i> <?php echo esc_html__("There are some errors in your form.", "computer-repair-shop"); ?></p>
						</div>
					</div>
				</div>
	
				<!-- Login Form Starts /-->
				<div class="grid-x grid-margin-x">
	
					<div class="cell medium-6">
						<label for="firstName"><?php echo esc_html__("First Name", "computer-repair-shop"); ?>*
							<input name="reg_fname" type="text" class="form-control login-field"
								   value="" required id="firstName"/>
							<span class="form-error">
								<?php echo esc_html__("First Name Is Required.", "computer-repair-shop"); ?>
							</span>
						</label>
					</div>
	
					<div class="cell medium-6">
						<label for="lastName"><?php echo esc_html__("Last Name", "computer-repair-shop"); ?>
							<input name="reg_lname" type="text" class="form-control login-field"
								   value="" id="lastName"/>
						</label>
					</div>
	
				</div>
	
				<div class="grid-x grid-margin-x">
	
					<div class="cell medium-6">
						<label for="theEmail"><?php echo esc_html__("Email", "computer-repair-shop"); ?>*
							<input name="reg_email" type="email" class="form-control login-field"
								   value="" id="theEmail" required/>
							<span class="form-error">
								<?php echo esc_html__("Email Is Required.", "computer-repair-shop"); ?>
							</span>
						</label>
					</div>
					<?php
						$screen = get_current_screen();
						$_name = ( $screen->parent_base == 'edit' ) ? 'customer_phone' : 'customer_phone_ol';
					?>
					<div class="cell medium-6">
						<label for="phoneNumbers"><?php echo esc_html__("Phone Number", "computer-repair-shop"); ?>
							<input name="<?php echo esc_attr( $_name ); ?>" type="text" class="<?php echo esc_attr( $_name ); ?> form-control login-field"
								value="" id="phoneNumbers" />
						</label>
					</div>
	
				</div>
				<!-- Login Form Ends /-->
	
				<div class="grid-x grid-margin-x">
	
					<div class="cell medium-6">
						<label for="theCity"><?php echo esc_html__("City", "computer-repair-shop"); ?>
							<input name="customer_city" type="text" class="form-control login-field"
								value="" id="theCity" />
						</label>
					</div>
	
					<div class="cell medium-6">
						<label for="theZipCode"><?php echo esc_html__( "Postal Code", "computer-repair-shop" ); ?>
							<input name="zip_code" type="text" class="form-control login-field" value="" id="theZipCode" />
						</label>
					</div>
	
				</div>
				<!-- Login Form Ends /-->
				<input name="userrole" type="hidden" value="technician" />
				<?php wp_nonce_field( 'wcrb_nonce_addclient', 'wcrb_nonce_addclient_field', true, true ); ?>
	
				<div class="grid-x grid-margin-x">
					<fieldset class="cell medium-6">
						<button class="button" type="submit"><?php echo esc_html__("Add Technician", "computer-repair-shop"); ?></button>
					</fieldset>
					<small>
						<?php echo esc_html__("(*) fields are required", "computer-repair-shop"); ?>
					</small>	
				</div>
			</form>
	
			<button class="close-button" data-close aria-label="Close modal" type="button">
				<span aria-hidden="true">&times;</span>
			</button>
		</div>
		<?php
	}

	if ( ! function_exists( 'wc_add_extra_field_to_job_modal' ) ) :
		function wc_add_extra_field_to_job_modal() {
		?>
		<!-- Modal for Post Entry /-->
		<div class="small reveal" id="extraFieldAddition" data-reveal>
			<h2><?php echo esc_html__( 'Add Extra Field', 'computer-repair-shop' ); ?></h2>
			<div class="extrafield-form-message"></div>
	
			<form class="" id="submitAdminExtraField"  method="post">
				<!-- Login Form Starts /-->
				<div class="grid-x grid-margin-x">
					<div class="cell medium-6">
						<label><?php echo esc_html__( 'Date Time', 'computer-repair-shop' ); ?>*
							<input name="extraFieldDateTime" type="datetime-local" class="form-control" value="<?php echo esc_html( wp_date('Y-m-d H:i:s') ); ?>" required id="dateTime"/>
						</label>
					</div>
					<div class="cell medium-6">
						<label><?php echo esc_html__( 'Field Label', 'computer-repair-shop' ); ?>
							<input name="extraFieldLabel" type="text" class="form-control" value="" id="reg-lname" />
						</label>
					</div>
				</div>
				<div class="grid-x grid-margin-x">
					<div class="cell medium-6">
						<label><?php echo esc_html__( 'Field Data', 'computer-repair-shop' ); ?>
							<input name="extraFieldData" type="text" class="form-control" value="" id="extraFieldData" />
						</label>
					</div>
	
					<div class="cell medium-6">
						<label><?php echo esc_html__( 'Field Description', 'computer-repair-shop' ); ?>
							<input name="extraFieldDescription" type="text" class="form-control" value="" id="extraFieldDescription" />
						</label>
					</div>
				</div>
				<!-- Login Form Ends /-->
	
				<div class="grid-x grid-margin-x">
	
					<div class="cell medium-6">
						<label><?php echo esc_html__( 'Field Visibility', 'computer-repair-shop' ); ?>
							<select name="extraFieldVisibility" class="form-control">
								<option value=""><?php echo esc_html__( 'Visibility of this field', 'computer-repair-shop' ); ?></option>
								<option value="public"><?php echo esc_html__( 'Customer & Staff', 'computer-repair-shop' ); ?></option>
								<option value="private"><?php echo esc_html__( 'Staff', 'computer-repair-shop' ); ?></option>
							</select>
						</label>
					</div>
	
					<div class="cell medium-6">
						<div class="attachmentserror"></div>
						<div class="jobAttachments displayNone" id="jobAttachments"></div>
						<label for="reciepetAttachment" class="button button-primary">
							<?php echo esc_html__( 'Attach File', 'computer-repair-shop' ); ?>
							<input type="file" id="reciepetAttachment" name="reciepetAttachment" data-security="<?php echo esc_attr( wp_create_nonce( 'file_security' ) ); ?>" class="show-for-sr">
						</label>
					</div>
				</div>
				<?php wp_nonce_field( 'wcrb_nonce_adrepairbuddy', 'wcrb_nonce_adrepairbuddy_field', true, true ); ?>
				<!-- Login Form Ends /-->
				<div class="grid-x grid-margin-x">
					<fieldset class="cell medium-6">
						<button class="button" type="submit">
							<?php echo esc_html__( 'Add Extra Field', 'computer-repair-shop' ); ?>
						</button>
					</fieldset>
					<small>
						<?php echo esc_html__("(*) fields are required", "computer-repair-shop"); ?>
					</small>	
				</div>
			</form>
	
			<button class="close-button" data-close aria-label="Close modal" type="button">
				<span aria-hidden="true">&times;</span>
			</button>
		</div>
		<?php
		}
	endif;

	/***
	 * @since 3.2
	 * 
	 * Adds Post Status form in footer.
	*/
	function wc_add_status_form() {
		$status_name = $status_slug = $status_description = $invoice_label = $status_status = $status_email_message = "";
		$button_label = $modal_label = esc_html__("Add new", "computer-repair-shop");

		if(isset($_GET["update_status"]) && !empty($_GET["update_status"])):
			global $wpdb;

			$update_status = sanitize_text_field($_GET["update_status"]);
			$button_label = $modal_label = esc_html__("Update", "computer-repair-shop");

			$status_id = sanitize_text_field($_GET["update_status"]);

			$computer_repair_job_status 	= $wpdb->prefix.'wc_cr_job_status';
			$wc_status_row					= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$computer_repair_job_status} WHERE `status_id` = %d", $status_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			
			$status_name 			= $wc_status_row->status_name;
			$status_slug 			= $wc_status_row->status_slug;
			$status_description		= $wc_status_row->status_description;
			$invoice_label			= $wc_status_row->invoice_label;
			$status_status			= $wc_status_row->status_status;
			$status_email_message 	= stripslashes( $wc_status_row->status_email_message );
		endif;

		$invoice_label = ( empty( $invoice_label ) ) ? 'Invoice' : $invoice_label;
		?>
		<!-- Modal for Post Entry /-->
		<div class="small reveal" id="statusFormReveal" data-reveal>
			<h2><?php echo esc_html($modal_label)." ".esc_html__("Status", "computer-repair-shop"); ?></h2>
	
			<div class="form-message"></div>
	
			<form data-async data-abide class="needs-validation" name="status_form_sync" novalidate method="post">
				<div class="grid-x grid-margin-x">
					<div class="cell">
						<div data-abide-error class="alert callout" style="display: none;">
							<p><i class="fi-alert"></i> <?php echo esc_html__("There are some errors in your form.", "computer-repair-shop"); ?></p>
						</div>
					</div>
				</div>
	
				<!-- Login Form Starts /-->
				<div class="grid-x grid-margin-x">
	
					<div class="cell medium-6">
						<label><?php echo esc_html__("Status Name", "computer-repair-shop"); ?>*
							<input name="status_name" type="text" class="form-control login-field"
									value="<?php echo esc_html($status_name); ?>" required id="status_name"/>
							<span class="form-error">
								<?php echo esc_html__("Name the status to recognize.", "computer-repair-shop"); ?>
							</span>
						</label>
					</div>
	
					<div class="cell medium-6">
						<label><?php echo esc_html__("Status Slug", "computer-repair-shop"); ?>*
							<input name="status_slug" type="text" class="form-control login-field"
									value="<?php echo esc_html($status_slug); ?>" required id="status_slug"/>
							<span class="form-error">
								<?php echo esc_html__("Slug is required to recognize the status make sure to not change it.", "computer-repair-shop"); ?>
							</span>	   
						</label>
					</div>
	
				</div>
	
				<div class="grid-x grid-margin-x">
	
					<div class="cell medium-6">
						<label><?php echo esc_html__("Description", "computer-repair-shop"); ?>
							<input name="status_description" type="text" class="form-control login-field"
									value="<?php echo esc_html( $status_description ); ?>" id="status_description" />
						</label>
					</div>

					<div class="cell medium-6">
						<label><?php echo esc_html__( "Invoice Label", "computer-repair-shop" ); ?>
							<input name="invoice_label" type="text" class="form-control login-field"
									value="<?php echo esc_html( $invoice_label ); ?>" id="invoice_label" />
						</label>
					</div>
	
					<div class="cell medium-6">
						<label><?php echo esc_html__("Status", "computer-repair-shop"); ?>
							<select class="form-control" name="status_status">
								<?php $theStatusAc = ( $status_status == 'active' ) ? "selected" : ""; ?>
								<?php $theStatusIn = ( $status_status == 'inactive' ) ? "selected" : ""; ?>
								<option <?php echo esc_html( $theStatusAc ); ?> value="active"><?php echo esc_html__("Active", "computer-repair-shop"); ?>
								<option <?php echo esc_html( $theStatusIn ); ?> value="inactive"><?php echo esc_html__("Inactive", "computer-repair-shop"); ?>
							</select>
						</label>
					</div>
	
				</div>

				<div class="grid-x grid-margin-x">
	
					<div class="cell medium-12">
						<label><?php echo esc_html__( 'Status Email Message', 'computer-repair-shop' ); ?> <small><?php echo esc_html__( 'Can be used in other mediums of notifications like SMS if used. Available keywords brackets required {{KEYWORDHERE}}', 'computer-repair-shop' ); ?><pre> {{device_name}} {{customer_name}} {{order_total}} {{order_balance}}</pre></small>
							<textarea rows="5" placeholder="<?php echo esc_html__("This message would be sent when a job status is changed to this.", "computer-repair-shop"); ?>" name="statusEmailMessage"><?php echo esc_textarea( $status_email_message ); ?></textarea>
						</label>
					</div>
	
				</div>
				<!-- Login Form Ends /-->
	
				<!-- Login Form Ends /-->
				<input name="form_type" type="hidden" value="status_form" />

				<?php 
					wp_nonce_field( 'wcrb_nonce_jobstatus', 'wcrb_nonce_jobstatus_field', true, true );
					if ( ! empty( $update_status ) ): 
				?>
					<input name="form_type_status" type="hidden" value="update" />
					<input name="status_id" type="hidden" value="<?php echo esc_html($update_status); ?>" />
				<?php else: ?>
					<input name="form_type_status" type="hidden" value="add" />
				<?php endif; ?>

				<div class="grid-x grid-margin-x">
					<fieldset class="cell medium-6">
						<button class="button" type="submit"><?php echo esc_html($button_label); ?></button>
					</fieldset>
					<small>
						<?php echo esc_html__("(*) fields are required", "computer-repair-shop"); ?>
					</small>	
				</div>
			</form>
	
			<button class="close-button" data-close aria-label="Close modal" type="button">
				<span aria-hidden="true">&times;</span>
			</button>
		</div>
	<?php
		if ( ! empty( $update_status ) ) {
			echo "<div id='updateStatus'></div>";
		}
	}


	/*
		Generate Select options
		
		Accepts Post Type
		
		return select field
	*/	
	if ( ! function_exists( "wc_post_select_options" ) ) :
		function wc_post_select_options( $post_type ) {

			if ( empty( $post_type ) ) {
				return '';
			}

			$brand_args = array(
							'post_type' 		=> $post_type,
							'orderby'			=> 'title',
							'order' 			=> 'ASC',
							'posts_per_page' 	=> -1,
						);

			$brand_query = new WP_Query( $brand_args );
			
			$select_id = $post_type;
			
			$wc_options = '<select name="'.$post_type.'" id="select_'.$select_id.'">';
			$wc_options .= '<option value="">----</option>';

			if ($brand_query->have_posts() ) { 
				while($brand_query->have_posts()) {
					$brand_query->the_post();

					$brand_id 		= $brand_query->post->ID;
					$brand_title 	= get_the_title();
					
					$extra_field = '';
					
					if($post_type == "rep_products") {
						$_stock_code = get_post_meta($brand_id, "_stock_code", true);
						
						if(!empty($_stock_code)) {
							$extra_field = $_stock_code.' | ';	
						}
					} elseif($post_type == "rep_services") {
						$_service_code = get_post_meta($brand_id, "_service_code", true);
						
						if(!empty($_service_code)) {
							$extra_field = $_service_code.' | ';	
						}
					}

					$part_title = ( $post_type == 'rep_products' ) ? get_post_meta( $brand_id, '_part_title', true ) : '';
					$brand_title = ( !empty( $part_title ) ) ? $part_title : $brand_title;

					$wc_options .= '<option value="' . $brand_id . '">' . $extra_field . $brand_title . '</option>';

					if ( $post_type == 'rep_products' ) {
						//Let's list sub_parts.
						$sub_parts = get_post_meta( $brand_id, '_sub_parts_arr', true );

						if ( ! empty( $sub_parts ) ) {
							foreach ( $sub_parts as $sub_part ) {
								if ( $sub_part != 'default' ) {
									$_Vpart = ( $sub_part == 'default' ) ? '' : $sub_part;
									$_title 	= get_post_meta( $brand_id, $_Vpart . '_part_title', true );
									$extra_field = get_post_meta( $brand_id, $_Vpart . '_stock_code', true );
									$extra_field = ( ! empty( $extra_field ) ) ? $extra_field . ' | ' : '';
									if ( ! empty( $_title ) ) {
										$wc_options .= '<option value="post' . $brand_id . '__'. $sub_part .'">--' . $extra_field . $_title . '</option>';
									}
								}
							}
						}
					}
				}
			} else {
				$wc_options .= '<option>' . esc_html__( 'Sorry nothing to display!', 'computer-repair-shop' ) . '</option>';
			}
			wp_reset_postdata(); //important

			$wc_options .= '</select>';

			return $wc_options;
		}
	endif;

	/*
		Generate Select options
		
		only works with WooCommerce Enabled products
		
		Wouldn't work if WooCommerce is not active

		return select field with options
	*/	
	if ( ! function_exists( "wc_woo_select_options" ) ):
		function wc_woo_select_options( $post_type ) {

			if(rb_is_woocommerce_activated() == false) {
				return;
			}

			$product_args = array(
							'post_type' 		=> $post_type,
							'orderby'			=> 'title',
							'order' 			=> 'ASC',
							'posts_per_page' 	=> -1,
						);

			$product_query = new WP_Query($product_args);
			
			$select_id = $post_type;
			
			$wc_options = '<select name="'.$post_type.'" id="select_'.$select_id.'">';
			$wc_options .= '<option value="">----</option>';

			if ($product_query->have_posts() ) { 
				while($product_query->have_posts()) {
					$product_query->the_post();

					$_product_id 	= $product_query->post->ID;
					
					$product_obj 	= wc_get_product( $_product_id );
					
					$product_title 	= $product_obj->get_name(); 
					
					$extra_field = '';
					
					$type =  $product_obj->get_type();

					$product_sku = $product_obj->get_sku();

					if($type == 'variable') {
						
						$extra_field = (!empty($product_sku))? $product_sku." | " : "";

						$wc_options .= '<optgroup label="'.$extra_field.$product_title.'">';
						
						foreach ( $product_obj->get_children( ) as $child_id ) {
							$variation = wc_get_product( $child_id ); 
				
							if ( ! $variation || !$variation->exists() ) {
								continue;
							}
							
							$variation_sku 	= $variation->get_sku();
							$variation_name = $variation->get_name();

							if(!empty($variation_sku)) {
								$extra_field = $product_sku." | ";
							}
							$wc_options .= '<option value="'.$child_id.'">'.$extra_field.$variation_name.'</option>';
						}
					
						$wc_options .= '</optgroup>';
					} else {

						if(!empty($product_sku)) {
							$extra_field = $product_sku." | ";
						}
						$wc_options .= '<option value="'.$_product_id.'">'.$extra_field.$product_title.'</option>';	
					}
				}
			} else {
				$wc_options .= '<option>' . esc_html__("Sorry nothing to display!", "computer-repair-shop") . '</option>';
			}
			wp_reset_postdata(); //important

			$wc_options .= '</select>';

			return $wc_options;
		}
	endif;

	if(!function_exists('wc_generate_random_case_num')):
		function wc_generate_random_case_num() {
			$length = empty( get_option( 'case_number_length' ) ) ? 6 : get_option( 'case_number_length' );

			$case_number_prefix = empty( get_option( 'case_number_prefix' ) ) ? 'WC_' : get_option( 'case_number_prefix' );

			$case_prefix_store = '';
			if ( function_exists( 'rb_ms_generate_prefix' ) ) {
				$case_prefix_store = rb_ms_generate_prefix();
			}
			$case_number_prefix = ( empty( $case_prefix_store ) ) ? $case_number_prefix : $case_prefix_store;

			$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$charactersLength = strlen($characters);
			$randomString = '';
			for ($i = 0; $i < $length; $i++) {
				$randomString .= $characters[rand(0, $charactersLength - 1)];
			}
			return $case_number_prefix . $randomString . time();
		}
	endif;

	if ( ! function_exists( 'wc_order_grand_total' ) ):
		function wc_order_grand_total( $post_id, $term ) {
			global $wpdb, $PAYMENT_STATUS_OBJ;

			if ( empty ( $post_id ) ) {
				return;
			}
			$order_id = $post_id;
			
			$prices_inclu_exclu = ( isset( $order_id ) && ! empty( $order_id ) ) ? get_post_meta( $order_id, '_wc_prices_inclu_exclu', true ) : 'exclusive';

			$computer_repair_items 		= $wpdb->prefix.'wc_cr_order_items';
			$computer_repair_items_meta = $wpdb->prefix.'wc_cr_order_itemmeta';
			
			$select_items_query = $wpdb->prepare( "SELECT * FROM `{$computer_repair_items}` WHERE `order_id`= %d AND `order_item_type`='extras'", $order_id);
			$items_result = $wpdb->get_results($select_items_query); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		
			$extras_total = 0;
			$extra_tax 	 = 0;

			foreach($items_result as $item) {
				$order_item_id 	 = $item->order_item_id;
				
				$wc_extra_qty		= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$computer_repair_items_meta} WHERE `order_item_id` = %d AND `meta_key` = 'wc_extra_qty'", $order_item_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wc_extra_price		= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$computer_repair_items_meta} WHERE `order_item_id` = %d AND `meta_key` = 'wc_extra_price'", $order_item_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				
				$wc_extra_tax		= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$computer_repair_items_meta} WHERE `order_item_id` = %d AND `meta_key` = 'wc_extra_tax'", $order_item_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

				$wc_extra_price 	= (empty($wc_extra_price->meta_value)) ? 0 : $wc_extra_price->meta_value;
				$wc_extra_qty 		= (empty($wc_extra_qty->meta_value)) ? 0 : $wc_extra_qty->meta_value;
				$row_total 			= (float)$wc_extra_price*(float)$wc_extra_qty;	

				$extras_total += $row_total;

				if(isset($wc_extra_tax)) {
					$wc_extra_tax_value = (float)$wc_extra_tax->meta_value;
					if ( $prices_inclu_exclu == 'inclusive' ) {
						$extra_tax += $row_total*$wc_extra_tax_value/(100+$wc_extra_tax_value);
					} else {
						$extra_tax += ($row_total/100)*$wc_extra_tax_value;
					}
				}
			}
			
			//Getting Services Total
			$services_total = 0;
			$service_tax 	 = 0;

			$select_items_query = $wpdb->prepare( "SELECT * FROM `{$computer_repair_items}` WHERE `order_id`= %d AND `order_item_type`='services'", $order_id );
			$items_result = $wpdb->get_results($select_items_query); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			
			foreach($items_result as $item) {
				$order_item_id 	 = $item->order_item_id;
				
				$wc_service_qty		= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$computer_repair_items_meta} WHERE `order_item_id` = %d AND `meta_key` = 'wc_service_qty'", $order_item_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wc_service_price	= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$computer_repair_items_meta} WHERE `order_item_id` = %d AND `meta_key` = 'wc_service_price'", $order_item_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wc_service_tax		= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$computer_repair_items_meta} WHERE `order_item_id` = %d AND `meta_key` = 'wc_service_tax'", $order_item_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

				$row_total =	(float)$wc_service_price->meta_value*(float)$wc_service_qty->meta_value;

				$services_total += $row_total;

				if(isset($wc_service_tax)) {
					$wc_service_tax_value = (float)$wc_service_tax->meta_value;
					if ( $prices_inclu_exclu == 'inclusive' ) {
						$service_tax += $row_total*$wc_service_tax_value/(100+$wc_service_tax_value);
					} else {
						$service_tax += ($row_total/100)*$wc_service_tax_value;
					}
				}
			}
			
			
			//Getting Parts Total
			$parts_total = 0;
			$part_tax 	 = 0;

			$select_items_query = $wpdb->prepare( "SELECT * FROM `{$computer_repair_items}` WHERE `order_id`= %d AND `order_item_type`='parts'", $order_id );
			$items_result = $wpdb->get_results($select_items_query); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			
			foreach($items_result as $item) {
				$order_item_id 	 = $item->order_item_id;
				
				$wc_part_qty		= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$computer_repair_items_meta} WHERE `order_item_id` = %d AND `meta_key` = 'wc_part_qty'", $order_item_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wc_part_price		= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$computer_repair_items_meta} WHERE `order_item_id` = %d AND `meta_key` = 'wc_part_price'", $order_item_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wc_part_tax		= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$computer_repair_items_meta} WHERE `order_item_id` = %d AND `meta_key` = 'wc_part_tax'", $order_item_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				
				$row_total =	(float)$wc_part_price->meta_value*(float)$wc_part_qty->meta_value;	

				$parts_total += $row_total;

				if(isset($wc_part_tax)) {
					$wc_part_tax_new = (float)$wc_part_tax->meta_value;
					if ( $prices_inclu_exclu == 'inclusive' ) {
						$part_tax += $row_total*$wc_part_tax_new/(100+$wc_part_tax_new);
					} else {
						$part_tax += ($row_total/100)*$wc_part_tax_new;
					}
				}
			}

			//Getting Parts Total
			$products_total 	= 0;
			$products_tax 	 	= 0;

			$select_items_query = $wpdb->prepare( "SELECT * FROM `{$computer_repair_items}` WHERE `order_id`= %d AND `order_item_type`='products'", $order_id );
			$items_result 		= $wpdb->get_results($select_items_query); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			
			foreach($items_result as $item) {
				$order_item_id 	 = $item->order_item_id;
				
				$wc_product_qty		= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$computer_repair_items_meta} WHERE `order_item_id` = %d AND `meta_key` = 'wc_product_qty'", $order_item_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wc_product_price	= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$computer_repair_items_meta} WHERE `order_item_id` = %d AND `meta_key` = 'wc_product_price'", $order_item_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wc_product_tax		= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$computer_repair_items_meta} WHERE `order_item_id` = %d AND `meta_key` = 'wc_product_tax'", $order_item_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

				$row_total 			=	(float)$wc_product_price->meta_value*(float)$wc_product_qty->meta_value;	

				$products_total += $row_total;

				if(isset($wc_product_tax)) {
					$wc_product_tax_new = (float)$wc_product_tax->meta_value;
					if ( $prices_inclu_exclu == 'inclusive' ) {
						$products_tax += $row_total*$wc_product_tax_new/(100+$wc_product_tax_new);
					} else {
						$products_tax += ($row_total/100)*$wc_product_tax_new;
					}
				}
			}
			
			if ( $prices_inclu_exclu == 'inclusive' ) {
				$grand_total = $products_total+$parts_total+$services_total+$extras_total;
			} else {
				$grand_total = $products_total+$parts_total+$services_total+$extras_total+$products_tax+$part_tax+$service_tax+$extra_tax;
			}
			
			if($term == "grand_total") {
				return round( $grand_total, 3 );
			} elseif($term == "parts_total") {	
				return ( $prices_inclu_exclu == 'inclusive' ) ? round( $parts_total, 3 ) : round( $parts_total+$part_tax, 3 );
			} elseif($term == "products_total") {
				return ( $prices_inclu_exclu == 'inclusive' ) ? round( $products_total, 3 ) : round( $products_total+$products_tax, 3 );
			} elseif($term == "services_total") {
				return ( $prices_inclu_exclu == 'inclusive' ) ? round( $services_total, 3 ) : round( $services_total+$service_tax, 3 );
			} elseif($term == "extras_total") {
				return ( $prices_inclu_exclu == 'inclusive' ) ? round( $extras_total, 3 ) : round( $extras_total+$extra_tax, 3 );	
			} elseif($term == "parts_tax") {
				return round( $part_tax, 3 );
			} elseif($term == "products_tax") {
				return round( $products_tax, 3 );
			} elseif($term == "services_tax") {
				return round( $service_tax, 3 );
			} elseif($term == "extras_tax") {
				return round( $extra_tax, 3 );
			} elseif ( $term == "balance" ) {
				$receiving      = $PAYMENT_STATUS_OBJ->wc_return_receivings_total( $post_id );
				$theBalance     = $grand_total-$receiving;
				return round( $theBalance, 3 );
			} elseif ( $term == "received" ) {
				$receiving      = $PAYMENT_STATUS_OBJ->wc_return_receivings_total( $post_id );
				return round( $receiving, 3 );
			}
		}
	endif;


	/**
	 * Function Creates Tax Options
	 * 
	 * This doesn't create select around options
	 * $wc_part_tax_value = array(
	 *					"wc_default_tax_value"	=> $wc_part_tax_value,
	 *					"value_type"		=> "tax_rate"
	 *				);
	 *	
     * Single argument of selected ID.
	 * 	
	 * Takes parameter for selected option.
	 */
	if(!function_exists("wc_generate_tax_options")):
		function wc_generate_tax_options($wc_primary_tax) {
			global $wpdb;

			$field_to_select 	= "tax_id";
			$selected_field 	= $wc_primary_tax;

			if(is_array($wc_primary_tax)) {
				$field_to_select 	= $wc_primary_tax["value_type"];
				$selected_field 	= $wc_primary_tax["wc_default_tax_value"];
			}

			if(!isset($selected_field)) {
				$selected_field = "";
			}
			
			//Table
			$computer_repair_taxes 	= $wpdb->prefix.'wc_cr_taxes';

			$select_query 	= "SELECT * FROM `".$computer_repair_taxes."` WHERE `tax_status`='active'";
			$select_results = $wpdb->get_results($select_query); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			
			$output = '';

			if ( ! empty( $select_results ) ) {
				foreach( $select_results as $result ) {
					if ( $result->tax_id == $selected_field ) {
						$selected = 'selected="selected"';
					} else {
						$selected = '';
					}
					$output .= '<option ' . $selected . ' value="' . esc_attr( $result->$field_to_select ) . '">';
					$output .= esc_attr( $result->tax_name );
					$output .= '</option>';
				} // End Foreach	
			} else {
				$output .= '<option value="">You have not defined any tax rate yet please go to tax setting page and click add new tax.</option>';
			}

			return $output;
		}
	endif;


	/**
	 * Function Creates Job Status Options
	 * 
	 * @Snce 3.2
	 * 
	 * This doesn't create select around options
	 *	
     * Single argument of selected ID.
	 * 	
	 * Takes parameter for selected option.
	 */
	if(!function_exists("wc_generate_status_options")):
		function wc_generate_status_options( $wc_selected_status ) {
			global $wpdb;

			$field_to_select 	= "status_slug";
			//neworder
			$selected_field 	= ! empty( $wc_selected_status ) ? $wc_selected_status : '';

			if(!isset($selected_field)) {
				$selected_field = "";
			}
			
			//Table
			$computer_repair_job_status 	= $wpdb->prefix.'wc_cr_job_status';

			$select_query 	= "SELECT * FROM `".$computer_repair_job_status."` WHERE `status_status`='active'";
			$select_results = $wpdb->get_results($select_query); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			
			$output = '';
			foreach($select_results as $result) {

				if($result->status_slug == $selected_field) {
					$selected = 'selected="selected"';
				} else {
					$selected = '';
				}

				$output .= '<option ' . $selected . ' value="' . esc_attr( $result->$field_to_select ) . '">';
				$output .= esc_attr( $result->status_name );
				$output .= '</option>';

			} // End Foreach	

			return $output;
		}
	endif;

	if ( ! function_exists( 'wcrb_admin_post_storage' ) ) :
		function wcrb_admin_post_storage() {
			global $wp_query,
				$post;
			
			if( ! is_admin() ) {
				return;
			}
			$wp_query->post = $post;
		}
		add_action( 'loop_start', 'wcrb_admin_post_storage' );
	endif;

	/**
	 * Function Creates Device Options
	 * 
	 * @Snce 3.5
	 * 
	 * This doesn't create select around options
	 *	
     * Accepts the Post ID Device
	 * 	
	 */
	if(!function_exists("wc_generate_device_options")):
		function wc_generate_device_options( $wc_device_id ) {

			if(isset($wc_device_id) && $wc_device_id == "data-list") {
				$type_return 	= "data-list";
				$wc_device_id 	= "";
			}

			$wcrb_type = 'rep_devices';
			$wcrb_tax = 'device_brand';
			if ( wcrb_use_woo_as_devices() == 'YES' ) {
				$wcrb_type = 'product';
				$wcrb_tax = 'product_cat';
			}

			$cat_terms = get_terms(
				array(
						'taxonomy'		=> $wcrb_tax,
						'hide_empty'    => true,
						'orderby'       => 'name',
						'order'         => 'ASC',
						'number'        => 0
					)
			);

			$wc_device_label = ( empty( get_option( 'wc_device_label' ) ) ) ? esc_html__( 'Device', 'computer-repair-shop' ) : get_option( 'wc_device_label' );

			$output = "<option value='All'>" . esc_html__("Select", "computer-repair-shop") . ' ' . $wc_device_label . "</option>";

			if( $cat_terms ) :
				foreach( $cat_terms as $term ) :

					$output .= '<optgroup label="'.esc_html($term->name).'">';

					$args = array(
							'post_type'             => $wcrb_type,
							'posts_per_page'        => -1, //specify yours
							'post_status'           => 'publish',
							'tax_query'             => array(
														array(
															'taxonomy' => $wcrb_tax,
															'field'    => 'slug',
															'terms'    => $term->slug,
														),
													),
							'ignore_sticky_posts'   => true //caller_get_posts is deprecated since 3.1
						);
					$_posts = new WP_Query( $args );

					if( $_posts->have_posts() ) :
						while( $_posts->have_posts() ) : $_posts->the_post();

							$the_title = $term->name." | ".get_the_title();

							if($wc_device_id == $_posts->post->ID) {
								$selected = 'selected="selected"';
							} else {
								$selected = '';
							}

							$output .= '<option '.esc_html($selected).' value="'.$_posts->post->ID.'">'.esc_html($the_title).'</option>';

						endwhile;
					endif;
					wp_reset_postdata(); //important
					
					$output .= '</optgroup>';

				endforeach;
			endif;
			wp_reset_postdata(); //important
			wp_reset_query(); //important

			return $output;
		}
	endif;


	/**
	 * Function Creates Job Status links
	 * 
	 * @Snce 3.2
	 * 
	 * Created links with <li> around it for my Account with Job statuses.
	 *	
	 */
	if ( ! function_exists( "wc_generate_status_links_myaccount" ) ):
		function wc_generate_status_links_myaccount( $woo_end_point ) {
			global $wpdb;

			$field_to_select 	= "status_slug";
			$selected_field 	= "";

			if ( isset( $_GET['job_status'] ) && ! empty( $_GET['job_status'] ) ) {
				$selected_field = sanitize_text_field( $_GET['job_status'] );
			}
			
			$woo_end_point = ( ! empty( $woo_end_point ) && $woo_end_point == 'rb-repair-orders' ) ? 'rb-repair-orders/' : '';
			$woo_end_point = ( isset( $_GET['page_id'] ) && isset( $_GET['rb-repair-orders'] ) ) ? '&rb-repair-orders' : $woo_end_point;

			$job_status_link = ( isset( $_GET['page_id'] ) ) ? '&job_status' : '?job_status';
			
			//Table
			$computer_repair_job_status 	= $wpdb->prefix.'wc_cr_job_status';

			$select_query 	= "SELECT * FROM `".$computer_repair_job_status."` WHERE `status_status`='active'";
			$select_results = $wpdb->get_results($select_query); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			
			$output = '';
			foreach($select_results as $result) {

				if($result->status_slug == $selected_field) {
					$selected = 'class="active"';
				} else {
					$selected = '';
				}

				$output .= '<li '.$selected.'>';
				$output .= '<a href="' . get_the_permalink() . $woo_end_point . $job_status_link . '='.esc_attr($result->$field_to_select).'">';
				$output .= esc_attr($result->status_name);
				$output .= '</a></li>';

			} // End Foreach	

			return $output;
		}
	endif;

	if ( ! function_exists( "wc_generate_estimate_links_myaccount" ) ):
		function wc_generate_estimate_links_myaccount( $woo_end_point ) {
			$estimate_status = "";

			if ( isset( $_GET['estimate_status'] ) && ! empty( $_GET['estimate_status'] ) ) {
				$estimate_status = sanitize_text_field( $_GET['estimate_status'] );
			}
			
			$woo_end_point = ( ! empty( $woo_end_point ) && $woo_end_point == 'rb-repair-estimates' ) ? 'rb-repair-estimates/' : '';
			$woo_end_point = ( isset( $_GET['page_id'] ) && isset( $_GET['rb-repair-estimates'] ) ) ? '&rb-repair-estimates' : $woo_end_point;

			$_status_link = ( isset( $_GET['page_id'] ) ) ? '&estimate_screen=yes&estimate_status' : '?estimate_screen=yes&estimate_status';
			
			$_approved_sel = ( $estimate_status == 'approved' ) ? 'class="active"' : '';
			$_rej_sel 	   = ( $estimate_status == 'rejected' ) ? 'class="active"' : '';
			$_all_sel 	   = ( empty( $estimate_status ) || $estimate_status == 'all' ) ? 'class="active"' : '';

			//Table
			$output = '';

			$output .= '<li '.$_all_sel.'>';
			$output .= '<a href="' . get_the_permalink() . $woo_end_point . $_status_link . '='.esc_attr('all').'">';
			$output .= esc_html__( 'All', 'computer-repair-shop' );
			$output .= '</a></li>';

			$output .= '<li '.$_approved_sel.'>';
			$output .= '<a href="' . get_the_permalink() . $woo_end_point . $_status_link . '='.esc_attr('approved').'">';
			$output .= esc_html__( 'Approved', 'computer-repair-shop' );
			$output .= '</a></li>';

			$output .= '<li '.$_rej_sel.'>';
			$output .= '<a href="' . get_the_permalink() . $woo_end_point . $_status_link . '='.esc_attr('rejected').'">';
			$output .= esc_html__( 'Rejected', 'computer-repair-shop' );
			$output .= '</a></li>';

			return $output;
		}
	endif;

	/**
	 * Function Returns Status Label
	 * 
	 * Accepts Status Slug as Parameter
	 * 
	 * Returns Rate of Tax.
	 */
	if(!function_exists("wc_return_status_name")):
		function wc_return_status_name( $wc_status_slug ) {
			global $wpdb;

			if( ! isset( $wc_status_slug ) || empty ( $wc_status_slug ) ) {
				return $wc_status_slug;
			}
			
			//Table
			$computer_repair_job_status 	= $wpdb->prefix.'wc_cr_job_status';

			$select_query 	= "SELECT * FROM `".$computer_repair_job_status."` WHERE `status_slug`= %s";
			$select_results = $wpdb->get_row( $wpdb->prepare( $select_query, $wc_status_slug ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			
			$output = $select_results->status_name;

			return $output;
		}
	endif;

	/**
	 * Return Payment Status
	 */
	if(!function_exists("wc_return_payment_status")):
		function wc_return_payment_status( $wc_payment_slug ) {
			global $PAYMENT_STATUS_OBJ;
			$payment_status = $PAYMENT_STATUS_OBJ->wc_generate_payment_status_array( 'all' );

			$return_name = (isset($payment_status[$wc_payment_slug]) && !empty($payment_status[$wc_payment_slug])) ? $payment_status[$wc_payment_slug] : "";

			return $return_name;
		}
	endif;

	/**
	 * Function Returns Status ID
	 * 
	 * Accepts Status Slug as Parameter
	 * 
	 * Returns Rate of Tax.
	 */
	if(!function_exists("wc_return_status_id")):
		function wc_return_status_id( $wc_status_slug ) {
			global $wpdb;

			if ( ! isset( $wc_status_slug ) ) {
				$wc_status_slug = "";
			}
			
			//Table
			$computer_repair_job_status 	= $wpdb->prefix.'wc_cr_job_status';

			$select_query 	= "SELECT * FROM `".$computer_repair_job_status."` WHERE `status_slug`= %s";
			$select_results = $wpdb->get_row( $wpdb->prepare( $select_query, $wc_status_slug ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			
			$output = ( isset( $select_results->status_id ) && ! empty( $select_results->status_id ) ) ? $select_results->status_id : '';

			return $output;
		}
	endif;

	if ( ! function_exists( 'wc_return_status_invoice_label' ) ):
		function wc_return_status_invoice_label( $wc_status_slug ) {
			global $wpdb;

			if ( ! isset( $wc_status_slug ) ) {
				$wc_status_slug = "";
			}
			//Table
			$computer_repair_job_status 	= $wpdb->prefix.'wc_cr_job_status';

			$select_query 	= "SELECT * FROM `".$computer_repair_job_status."` WHERE `status_slug`= %s";
			$select_results = $wpdb->get_row( $wpdb->prepare( $select_query, $wc_status_slug ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			
			$output = ( isset( $select_results->invoice_label ) && ! empty( $select_results->invoice_label ) ) ? $select_results->invoice_label : '';

			return $output;
		}
	endif;

	/**
	 * Function Returns Tax Rate
	 * 
	 * Accepts Tax ID as Parameter
	 * 
	 * Returns Rate of Tax.
	 */
	if(!function_exists("wc_return_tax_rate")):
		function wc_return_tax_rate($wc_rate_to_return) {
			global $wpdb;

			if(!isset($wc_rate_to_return)) {
				$wc_rate_to_return = "";
			}
			
			//Table
			$computer_repair_taxes 	= $wpdb->prefix.'wc_cr_taxes';

			$select_query 	= "SELECT * FROM `".$computer_repair_taxes."` WHERE `tax_id`= %d";
			$select_results = $wpdb->get_row( $wpdb->prepare( $select_query, $wc_rate_to_return ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

			if ( null !== $select_results ) {
				$output = $select_results->tax_rate;
				$theStatus = $select_results->tax_status;
			} else {
				$theStatus = 'inactive';
				$output = 0;
			}
			$output = ( $theStatus == 'inactive' ) ? 0 : $output;

			return $output;
		}
	endif;

	/**
	 * Function Returns Tax ID
	 * 
	 * Accepts Tax Rate
	 * 
	 * Returns TAX ID
	 */
	if ( ! function_exists( "wc_return_tax_id" ) ) :
		function wc_return_tax_id( $wc_id_to_return ) {
			global $wpdb;

			if ( ! isset( $wc_id_to_return ) || empty( $wc_id_to_return ) ) {
				$wc_id_to_return = "";
			}
			
			//Table
			$computer_repair_taxes 	= $wpdb->prefix.'wc_cr_taxes';

			$select_query 	= "SELECT * FROM `".$computer_repair_taxes."` WHERE `tax_rate`= %s AND `tax_status`='active'";
			$select_results = $wpdb->get_row( $wpdb->prepare( $select_query, $wc_id_to_return ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			
			if( $select_results ) {
				$output = $select_results->tax_id;
			} else {
				$output = "";
			}	

			return $output;
		}
	endif;

	/*
	 * Returns Jobs/Estimates by User with multiple options
	 *
	 * @param int $user_id User ID
	 * @param string $user_type User type ('customer' or 'technician')
	 * @param array $args Additional arguments:
	 *    - 'post_type' => 'rep_jobs' or 'rep_estimates' (default: 'rep_jobs')
	 *    - 'return' => 'count' or 'ids' or 'objects' (default: 'count')
	 *    - 'status' => Post status filter (default: any)
	 *    - 'limit' => Number of posts to return (default: -1 for all)
	 * @return int|array|object Based on return parameter
	 */
	if ( ! function_exists( 'wc_return_jobs_by_user' ) ) {
		function wc_return_jobs_by_user( $user_id, $user_type, $args = array() ) {
			// Set defaults
			$defaults = array(
				'post_type' => 'rep_jobs',
				'return'    => 'count',
				'status'    => 'any',
				'limit'     => -1,
			);
			
			$args = wp_parse_args( $args, $defaults );
			
			// Validate user_type
			$user_type = ( $user_type == "customer" ) ? "_customer" : "_technician";    

			// Build query arguments
			$query_args = array(
				'post_type'      => $args['post_type'],
				'posts_per_page' => $args['limit'],
				'post_status'    => $args['status'],
				'meta_query'     => array(),
			);

			// Different meta queries for customer vs technician
			if ( $user_type === "_customer" ) {
				// Customer - simple exact match (single ID)
				$query_args['meta_query'][] = array(
					'key'   => $user_type,
					'value' => $user_id,
					'type'  => 'NUMERIC',
				);
			} else {
				// Technician - handle both single IDs and arrays
				$query_args['meta_query'][] = array(
					'relation' => 'OR',
					// For single technician IDs
					array(
						'key'   => $user_type,
						'value' => $user_id,
						'compare' => '=',
						'type'  => 'NUMERIC',
					),
					// For serialized arrays containing the technician ID
					array(
						'key'   => $user_type,
						'value' => '"' . $user_id . '"',
						'compare' => 'LIKE',
						'type'  => 'CHAR',
					),
					// For comma-separated arrays (if stored that way)
					array(
						'key'   => $user_type,
						'value' => $user_id,
						'compare' => 'REGEXP',
						'type'  => 'CHAR',
					)
				);
			}

			// Set fields based on return type
			if ( $args['return'] === 'ids' ) {
				$query_args['fields'] = 'ids';
			} elseif ( $args['return'] === 'count' ) {
				$query_args['fields'] = 'ids'; // More efficient for counting
			}
			// For 'objects', don't set fields (default WP_Post objects)

			$query = new WP_Query( $query_args );
			
			// Return based on parameters
			switch ( $args['return'] ) {
				case 'ids':
					return $query->posts;
				case 'objects':
					return $query->posts;
				case 'count':
				default:
					return $query->found_posts;
			}
		}
	}

	/*
		* Get User Role
		*
		* By User ID
	*/
	if ( ! function_exists( "wc_get_user_roles_by_user_id" ) ):
		function wc_get_user_roles_by_user_id( $user_id ) {
			$user = get_userdata( $user_id );
			return empty( $user ) ? array() : $user->roles;
		}
	endif;	

	/*
		* Sends Email to Technician
		*
		* Requires Job ID to send Email to technician. 
		* Sends Job Status Email
	*/
	if ( ! function_exists( "wcrb_send_technician_update_email" ) ) :
		function wcrb_send_technician_update_email( $job_id, $technician_id = '' ) {
			global $wpdb, $WCRB_EMAILS;

			if ( empty( $job_id ) ) { return; }
			if ( get_post_type( $job_id ) != 'rep_jobs' ) { return; }

			$status_label 		= get_post_meta( $job_id, "_wc_order_status_label", true );
			$wc_order_status 	= get_post_meta( $job_id, '_wc_order_status', true );

			if ( empty( $technician_id ) ) {
				return;
			}
			$user_info 	 = get_userdata( $technician_id );
			$user_name 	 = $user_info->first_name . ' ' . $user_info->last_name;
			$user_email  = $user_info->user_email;

			if ( empty ( $user_email ) ) { return; }

			$menu_name_p 	= get_option( 'menu_name_p' );
			$to 			= $user_email;

			$subject = esc_html__( 'A job is assigned to you!', 'computer-repair-shop' ) . ' | ' . esc_html( $menu_name_p );

			$body = esc_html__( 'Hi', 'computer-repair-shop' ) . ' ' . $user_name . '<br>';
			$body .= '<h2>' . esc_html__( 'A job have been assigned to you.', 'computer-repair-shop' ) . '</h2>';
			$body .= '<p>' . esc_html__( 'The details of job are attached in PDF.', 'computer-repair-shop' ) . '</p>';

			$customerLabel      = get_post_meta( $job_id, "_customer_label", true );
			$body .= '<p>' . esc_html__( 'Customer', 'computer-repair-shop' ) . ' : ' . esc_html__( $customerLabel ) . '</p>';

			$_arguments = array( 'attach_pdf_invoice' => $job_id, 'attachment_type' => 'work_order' );
			$WCRB_EMAILS->send_email( $to, $subject, $body, $_arguments );

			//Record Job History
			$arguments = array(
				'job_id'        => $job_id,
				'name'          => esc_html__( 'Work order email sent to technician', 'computer-repair-shop' ),
				'type'          => 'private',
				'field'         => '_notification_update_email',
				'change_detail' => sprintf(
					esc_html__( 'Email sent to %s', 'computer-repair-shop' ),
					sanitize_email( $user_email ) // Added email sanitization
				)
			);
			
			$wc_job_history_logs = WCRB_JOB_HISTORY_LOGS::getInstance();
			$wc_job_history_logs->wc_record_job_history( $arguments );
		} //wc_cr_send_customer_update_email
	endif;

	/*
		* Sends Email to Customer
		*
		* Requires Job ID to send Email to customer. 
		* Sends Job Status Email
	*/
	if ( ! function_exists( "wc_cr_send_customer_update_email" ) ) :
		function wc_cr_send_customer_update_email( $job_id ) {
			global $wpdb, $WCRB_EMAILS;

			if ( empty( $job_id ) ) {
				return;
			}
			$postType = get_post_type( $job_id ) ;

			if ( $postType != 'rep_jobs' ) {
				return;
			}

			$customer_id 		= get_post_meta( $job_id, "_customer", true );
			$status_label 		= get_post_meta( $job_id, "_wc_order_status_label", true );
			$wc_order_status 	= get_post_meta( $job_id, '_wc_order_status', true );

			$ext_message = "";
			$wc_order_status = wc_return_status_id( $wc_order_status );

			if(!empty($wc_order_status) && is_numeric($wc_order_status)) {
				$computer_repair_job_status 	= $wpdb->prefix.'wc_cr_job_status';
				$wc_status_row 					= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$computer_repair_job_status} WHERE `status_id` = %d", $wc_order_status ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

				$status_name 			= $wc_status_row->status_name;
				$message 				= stripslashes( $wc_status_row->status_email_message );
			}

			if ( empty( $customer_id ) ) {
				return;
			}

			$user_info 		= get_userdata( $customer_id );
			$user_name 		= $user_info->first_name . ' ' . $user_info->last_name;
			$user_email 	= $user_info->user_email;

			if ( empty ( $user_email ) ) {
				return;
				exit;
			}

			$menu_name_p 	= get_option( 'menu_name_p' );
			$to 			= $user_email;

			$subject = esc_html__( 'Your Job Status is Updated.', 'computer-repair-shop' ) . ' | ' . esc_html( $menu_name_p );

			$headers 		= array('Content-Type: text/html; charset=UTF-8');

			$body           = esc_html__( 'Hi', 'computer-repair-shop' ) . ' ' . $user_name . '<br>';
			
			$body .= '<h2>' . esc_html__( 'Your Job Status Is Updated.', 'computer-repair-shop' ) . '</h2>';

			if ( ! empty( $message ) ) {
				//Replace here.
				$order_total 	= wc_order_grand_total( $job_id, 'grand_total' );
				$order_total	= wc_cr_currency_format( $order_total );
				$order_balance	= wc_order_grand_total( $job_id, 'balance' );
				$order_balance  = wc_cr_currency_format( $order_balance );

				$available_devices = '';
				$current_devices = get_post_meta( $job_id, '_wc_device_data', true );

				if ( ! empty( $current_devices )  && is_array( $current_devices ) ) {
					foreach( $current_devices as $device_data ) {
						$device_post_id = ( isset( $device_data['device_post_id'] ) ) ? $device_data['device_post_id'] : '';
						$available_devices .= ' - ' . return_device_label( $device_post_id );
					}
				}
				$message = str_replace( '{{customer_name}}', $user_name, $message );
				$message = str_replace( '{{device_name}}', $available_devices, $message );
				$message = str_replace( '{{order_total}}', $order_total, $message );
				$message = str_replace( '{{order_balance}}', $order_balance, $message );
				//Replaced Msg
				$body		.= '<p>' . wpautop( esc_textarea( $message ) ) . '</p>';
			} else {
				$body .= "<p>".esc_html__("Your Job update is below if you have any questions please reach us.", "computer-repair-shop")."</p>";
			}

			$status_check_link = wc_rb_return_status_check_link( $job_id );

			if ( ! empty ( $status_check_link ) ) {
				$body .= '<h3>' . esc_html__( 'Check job status online', 'computer-repair-shop' ) . '</h3>';
				$body .= '<p><a href="' . $status_check_link . '">' . esc_html__( 'Click to open in browser' ) . '</a></p>'; 
			}

			$body .= '<div class="repair_box">' . wc_add_outlook_styles(wc_print_order_invoice( $job_id, 'email' )) . '</div>';

			$_arguments = array( 'attach_pdf_invoice' => $job_id );
			$WCRB_EMAILS->send_email( $to, $subject, $body, $_arguments );

			//Record Job History
			$arguments = array(
				'job_id'        => $job_id,
				'name'          => esc_html__( 'Status update email sent to customer', 'computer-repair-shop' ),
				'type'          => 'public',
				'field'         => '_notification_update_email',
				'change_detail' => sprintf(
					esc_html__( 'Email sent to %s', 'computer-repair-shop' ),
					sanitize_email( $user_email ) // Added email sanitization
				)
			);
			
			$wc_job_history_logs = WCRB_JOB_HISTORY_LOGS::getInstance();
			$wc_job_history_logs->wc_record_job_history( $arguments );
		} //wc_cr_send_customer_update_email
	endif;

	if( ! function_exists( 'wc_add_outlook_styles' ) ) :
		function wc_add_outlook_styles($content) {
			// Array of patterns and replacements for comprehensive Outlook styling
			$replacements = array(
				// Style main heading rows
				'/<tr class="heading">\s*<td colspan="(\d+)">\s*([^<]+)\s*<\/td>\s*<\/tr>/i' => 
					'<tr class="heading" style="background-color: #eeeeee !important; border-bottom: 1px solid #dddddd !important; font-weight: bold !important;">
						<td colspan="$1" style="background-color: #eeeeee !important; border-bottom: 1px solid #dddddd !important; font-weight: bold !important; padding: 12px 8px !important; text-align: center !important;">
							$2
						</td>
					</tr>',
					
				// Style estimate heading rows  
				'/<tr class="heading estimate">\s*<td colspan="(\d+)">\s*([^<]+)\s*<\/td>\s*<\/tr>/i' =>
					'<tr class="heading estimate" style="background-color: #000000 !important; border-bottom: 1px solid #dddddd !important; font-weight: bold !important; padding: 10px 5px !important; text-align: center !important;">
						<td colspan="$1" style="background-color: #000000 !important; border-bottom: 1px solid #dddddd !important; font-weight: bold !important; padding: 12px 8px !important; text-align: center !important; color: #ffffff !important; font-size: 18px !important; letter-spacing: 2px !important;">
							$2
						</td>
					</tr>',
					
				// Style special head rows (table headers)
				'/<tr class="heading special_head">/i' =>
					'<tr class="heading special_head" style="background-color: #f5f5f5 !important; border-bottom: 2px solid #dddddd !important; font-weight: bold !important;">',
					
				// Style all table cells with padding
				'/<td([^>]*)>/i' =>
					'<td$1 style="padding: 8px 5px !important; vertical-align: top !important; border: 1px solid #f0f0f0 !important;">',
					
				// Style table header cells
				'/<th([^>]*)>/i' =>
					'<th$1 style="background-color: #eeeeee !important; border-bottom: 2px solid #dddddd !important; font-weight: bold !important; padding: 10px 8px !important; text-align: left !important;">',
					
				// Style product/service/extra rows
				'/<tr class="item-row ([^"]*)">/i' =>
					'<tr class="item-row $1" style="border-bottom: 1px solid #f0f0f0 !important;">',
					
				// Style invoice totals tables
				'/<div class="invoice_totals"><table>/i' =>
					'<div class="invoice_totals" style="clear: both; display: table; content: \'\'; width: 100%; margin: 20px 0;"><table style="border: 1px solid #ededed !important; width: 250px !important; max-width: 250px !important; text-align: right !important; float: right !important; margin-bottom: 15px !important; background-color: #f9f9f9 !important;">',
					
				// Style total rows in invoice totals
				'/<tr><th>([^<]+)<\/th><td>([^<]+)<\/td><\/tr>/i' =>
					'<tr style="border-bottom: 1px solid #eeeeee !important;">
						<th style="background-color: #f5f5f5 !important; border-bottom: 1px solid #dddddd !important; font-weight: bold !important; padding: 10px 8px !important; text-align: left !important;">$1</th>
						<td style="padding: 10px 8px !important; font-weight: bold !important; text-align: right !important; border-bottom: 1px solid #eeeeee !important;">$2</td>
					</tr>',
					
				// Style buttons
				'/<button([^>]*)class="([^"]*button[^"]*)"([^>]*)>/i' =>
					'<button$1class="$2"$3 style="background-color: #0073aa !important; color: #ffffff !important; padding: 12px 20px !important; border: none !important; border-radius: 4px !important; font-weight: bold !important; text-decoration: none !important; display: inline-block !important; margin: 5px !important; cursor: pointer !important;">',
					
				// Style links that are buttons
				'/<a([^>]*)class="([^"]*button[^"]*)"([^>]*)>/i' =>
					'<a$1class="$2"$3 style="background-color: #0073aa !important; color: #ffffff !important; padding: 12px 20px !important; border: none !important; border-radius: 4px !important; font-weight: bold !important; text-decoration: none !important; display: inline-block !important; margin: 5px !important;">'
			);
			
			// Apply all replacements
			foreach ($replacements as $pattern => $replacement) {
				$content = preg_replace($pattern, $replacement, $content);
			}
			
			// Additional specific replacements for complex patterns
			$content = str_replace(
				array(
					'<table cellpadding="0" cellspacing="0">',
					'<table class="mb-twenty">',
					'<table class="invoice-items">',
					'<div class="invoice_totals"><table><tbody>',
					'<tr class="top">',
					'<tr class="information">',
					'<tr class="details">'
				),
				array(
					'<table cellpadding="0" cellspacing="0" style="width: 100% !important; border-collapse: collapse !important; mso-table-lspace: 0pt !important; mso-table-rspace: 0pt !important;">',
					'<table class="mb-twenty" style="width: 100% !important; border-collapse: collapse !important; margin-bottom: 20px !important; mso-table-lspace: 0pt !important; mso-table-rspace: 0pt !important;">',
					'<table class="invoice-items" style="width: 100% !important; border-collapse: collapse !important; margin-bottom: 15px !important; mso-table-lspace: 0pt !important; mso-table-rspace: 0pt !important;">',
					'<div class="invoice_totals" style="clear: both; display: table; content: \'\'; width: 100%;"><table style="border: 1px solid #ededed !important; width: 250px !important; max-width: 250px !important; text-align: right !important; float: right !important; margin-bottom: 15px !important; background-color: #f9f9f9 !important;"><tbody>',
					'<tr class="top" style="border-bottom: 2px solid #eeeeee !important;">',
					'<tr class="information" style="background-color: #fafafa !important;">',
					'<tr class="details" style="background-color: #f8f8f8 !important; border-left: 3px solid #0073aa !important;">'
				),
				$content
			);
			
			// Add inline styles to specific elements that need them
			$content = preg_replace_callback(
				'/<td class="([^"]*)">([^<]*)<\/td>/i',
				function($matches) {
					$class = $matches[1];
					$content = $matches[2];
					
					$style = "padding: 8px 5px !important; vertical-align: top !important; border: 1px solid #f0f0f0 !important;";
					
					// Add specific styles based on class
					if (strpos($class, 'text-left') !== false) {
						$style .= " text-align: left !important;";
					}
					if (strpos($class, 'text-center') !== false) {
						$style .= " text-align: center !important;";
					}
					if (strpos($class, 'wc_price') !== false || strpos($class, 'wc_tax') !== false || strpos($class, 'wc_product_price_total') !== false) {
						$style .= " text-align: right !important; font-family: monospace !important;";
					}
					
					return '<td class="' . $class . '" style="' . $style . '">' . $content . '</td>';
				},
				$content
			);
			
			return $content;
		}
	endif;

	/**
	* Check if WooCommerce is activated
	*/
	if ( ! function_exists( 'rb_is_woocommerce_activated' ) ) {
		function rb_is_woocommerce_activated() {
			if ( class_exists( 'woocommerce' ) ) { return true; } else { return false; }
		}
	}

	/**
	 * Add WooCommerce Menu
	 * If WooCommerce Is Active
	 * Items : Request Quote
	 * Items: Repair Orders
	 * 
	 * @Since: 
	 */
	if ( ! function_exists( 'wc_rp_customize_woo_menu_add' ) ) {
		add_filter ( 'woocommerce_account_menu_items', 'wc_rp_customize_woo_menu_add' );
		function wc_rp_customize_woo_menu_add( $menu_links ) {
			if ( rb_is_woocommerce_activated() ) :
				$new_menu_item = array(
									'rb-repair-orders' => esc_html__( 'Repair Orders', 'computer-repair-shop' ),
									'rb-repair-estimates' => esc_html__( 'Repair Estimates', 'computer-repair-shop' ),
									'rb-request-quote' => esc_html__( 'Request Quote', 'computer-repair-shop' ),
								);

				$menu_links = array_slice( $menu_links, 0, 2, true ) 
				+ $new_menu_item
				+ array_slice( $menu_links, 2, NULL, true );
			
				return $menu_links;
			endif;
		}
		
		add_action( 'init', 'wc_rb_rewriter_woo_pages' );
		function wc_rb_rewriter_woo_pages() {
			add_rewrite_endpoint( 'rb-repair-orders', EP_PAGES );
			add_rewrite_endpoint( 'rb-repair-estimates', EP_PAGES );
			add_rewrite_endpoint( 'rb-request-quote', EP_PAGES );
		}

		add_action( 'woocommerce_account_rb-repair-orders_endpoint', 'wc_rb_repair_orders_endpoint_woo' );
		function wc_rb_repair_orders_endpoint_woo() {
			global $WCRB_MY_ACCOUNT;
			wp_enqueue_script("foundation-js");
			//wp_enqueue_script("wc-cr-js");
			//wp_enqueue_script("select2");
			wp_enqueue_style( 'foundation-css');
			wp_enqueue_style( 'plugin-styles-wc' );
			wp_enqueue_style("intl-tel-input");
			wp_enqueue_script("intl-tel-input");

			$current_user 	= wp_get_current_user();
			$customer_id	= $current_user->ID;
			$allowedHTML 	= wc_return_allowed_tags();

			if( isset( $_GET["print"] ) && isset( $_GET["order_id"] ) && ! empty( $_GET["order_id"] ) ):
				$the_order_id     = sanitize_text_field( $_GET["order_id"] );
				$case_number      = get_post_meta( $the_order_id, '_case_number', true );
				$curr_case_number = ( isset( $_GET["wc_case_number"] ) ) ? sanitize_text_field( $_GET["wc_case_number"] ) : "";

				if($case_number != $curr_case_number) {
					echo esc_html__( "You do not have permission to view this record.", "computer-repair-shop" );
				} else {
					$order_id = $the_order_id;
					$generatedHTML = "<div class='callout success'><div class='orderstatusholder'>";
					$generatedHTML .= wcrb_return_job_history( $order_id );
					$generatedHTML .= '</div></div>';
					
					echo wp_kses( $generatedHTML, $allowedHTML );
					wc_computer_repair_print_functionality();
				}
			else:
				echo '<h2>' . esc_html__( 'Welcome', 'computer-repair-shop' ) . ' ' . esc_html( $current_user->user_firstname ) . ' ' . esc_html( $current_user->user_lastname ) . '</h2>';
				echo "<p>". esc_html__( "Here you can check your jobs and their statuses also you can request new quote.", "computer-repair-shop" ) ."</p>";
				echo "<h3>".esc_html__("Filter Jobs", "computer-repair-shop")."</h3>";		
				echo "<div class='job_status_holder'><ul class='horizontal wc_menu'>";
				$allowed_html = wc_return_allowed_tags();
				$optionsGenerated = wc_generate_status_links_myaccount( 'rb-repair-orders' );
				echo wp_kses( $optionsGenerated, $allowed_html );
				echo "</ul></div>";

				$job_status = "all";
				
				$generatedHTML = $WCRB_MY_ACCOUNT->wc_print_jobs_by_customer_table( $job_status, $customer_id, 'rb-repair-orders' );
				echo wp_kses( $generatedHTML, $allowedHTML );
			endif;
		}

		add_action( 'woocommerce_account_rb-repair-estimates_endpoint', 'wc_rb_repair_estimates_endpoint_woo' );
		function wc_rb_repair_estimates_endpoint_woo() {
			global $WCRB_MY_ACCOUNT;
			wp_enqueue_script("foundation-js");
			//wp_enqueue_script("wc-cr-js");
			//wp_enqueue_script("select2");
			wp_enqueue_style( 'foundation-css');
			wp_enqueue_style( 'plugin-styles-wc' );
			wp_enqueue_style("intl-tel-input");
			wp_enqueue_script("intl-tel-input");
			
			$current_user 	= wp_get_current_user();
			$customer_id	= $current_user->ID;
			$allowedHTML 	= wc_return_allowed_tags();

			if( isset( $_GET["print"] ) && isset( $_GET["order_id"] ) && ! empty( $_GET["order_id"] ) ):
				$the_order_id     = sanitize_text_field( $_GET["order_id"] );
				$case_number      = get_post_meta( $the_order_id, '_case_number', true );
				$curr_case_number = ( isset( $_GET["wc_case_number"] ) ) ? sanitize_text_field( $_GET["wc_case_number"] ) : "";

				if($case_number != $curr_case_number) {
					echo esc_html__("You do not have permission to view this record.", "computer-repair-shop");
				} else {
					$order_id = $the_order_id;
					$generatedHTML = "<div class='callout success'><div class='orderstatusholder'>";
					$generatedHTML .= wcrb_return_job_history( $order_id );
					$generatedHTML .= '</div></div>';
					
					echo wp_kses( $generatedHTML, $allowedHTML );
					wc_computer_repair_print_functionality();
				}
			else:
				echo '<h2>' . esc_html__( 'Welcome', 'computer-repair-shop' ) . ' ' . esc_html( $current_user->user_firstname ) . ' ' . esc_html( $current_user->user_lastname ) . '</h2>';
				echo "<p>".esc_html__("Here you can check your jobs and their statuses also you can request new quote.", "computer-repair-shop")."</p>";
				echo "<h3>".esc_html__("Filter Jobs", "computer-repair-shop")."</h3>";		
				echo "<div class='job_status_holder'><ul class='horizontal wc_menu'>";
				$allowed_html = wc_return_allowed_tags();
				$optionsGenerated = wc_generate_estimate_links_myaccount( 'rb-repair-estimates' );
				echo wp_kses( $optionsGenerated, $allowed_html );
				echo "</ul></div>";

				$job_status = "all";
				
				$generatedHTML = $WCRB_MY_ACCOUNT->wc_print_estimates_by_customer_table( $job_status, $customer_id, 'rb-repair-estimates' );
				echo wp_kses( $generatedHTML, $allowedHTML );
			endif;
		}

		add_action( 'woocommerce_account_rb-request-quote_endpoint', 'wc_rb_request_quote_endpoint_woo' );
		function wc_rb_request_quote_endpoint_woo() {
			$wc_account_booking_form = get_option( 'wc_account_booking_form' );
        
			if( $wc_account_booking_form == 'with_type' ) {
				$requestFormHtml = WCRB_TYPE_GROUPED_SERVICE();
			} elseif ( $wc_account_booking_form == 'warranty_booking' ) {
				$requestFormHtml = wc_book_my_warranty();
			} else {
				$requestFormHtml = wc_book_my_service();
			}

			$allowedTags 	 = wc_return_allowed_tags();
			echo wp_kses( $requestFormHtml, $allowedTags );
		}
	}


	/**
	 * Check If Parts Are Deactive
	 * 
	 * Check if Woo is Active/Deactive
	 */
	if(!function_exists("is_parts_switch_woo")) {
		function is_parts_switch_woo() {
			$wc_enable_woo_products = get_option("wc_enable_woo_products");

			if($wc_enable_woo_products == "on") {
				if(rb_is_woocommerce_activated() == true) {
					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
		}
	}

	if ( ! function_exists( 'is_wcrb_current_user_have_technician_access' ) ) :
		function is_wcrb_current_user_have_technician_access( $job_id = null ) {
			if ( ! is_user_logged_in() ) {
				return false;
			}
			
			$user_id = get_current_user_id();
			
			if ( empty( $job_id ) ) {
				return false;
			}
			
			$technicians = get_post_meta( $job_id, '_technician', true );
			
			// Handle both array and single technician ID
			if ( is_array( $technicians ) ) {
				return in_array( $user_id, $technicians );
			} elseif ( ! empty( $technicians ) ) {
				// Single technician ID stored as string or integer
				return (int) $technicians === $user_id;
			}
			
			return false;
		}
	endif;

	if ( ! function_exists( 'wcrb_get_job_customer_name' ) ) :
		function wcrb_get_job_customer_name( $job_id ) {
			// Adjust this based on how you store customer info in rep_jobs
			$customer_id = get_post_meta( $job_id, '_customer', true );
			if ( $customer_id ) {
				$user = get_user_by( 'id', $customer_id );
				if ( $user ) {
					return $user->first_name . ' ' . $user->last_name;
				}
			}
			return '';
		}
	endif;

	/*
	 * Takes Device ID
	 * 
	 * Returns Device Name and Brand
	 * 
	*/
	if(!function_exists("return_device_label")):
		function return_device_label( $device_id ) {
			if ( empty ( $device_id ) ) {
				return;
			}
			if ( $device_id == 'All' ) {
				return;
			}
			$wcrb_type = 'rep_devices';
			$wcrb_tax = 'device_brand';
			if ( wcrb_use_woo_as_devices() == 'YES' ) {
				$wcrb_type = 'product';
				$wcrb_tax = 'product_cat';
			}
			$terms = get_the_terms( $device_id, $wcrb_tax );

			$i = 1;
			$term_output = "";
			if ( ! empty( $terms ) && is_array( $terms ) ) {
				foreach ( $terms as $term ) {
					$term_output .= $term->name;
					$term_output .= ($i < count($terms))? " / " : "";
					$i++;
				}
			}

			if ( ! empty ( $term_output ) ) {
				$term_output = $term_output." ";
			}

			$device_title = $term_output.get_the_title( $device_id );

			return $device_title;
		}
	endif;

	/*
	 * File Upload Function
	 * 
	 * @Since 3.5
	 */
	if(!function_exists("wc_image_uploader_field")) :
		function wc_image_uploader_field( $name, $value = '') {
		
			$image = ' button">'.esc_html__("Upload File", "computer-repair-shop");

			$display 	= 'none'; // display state ot the "Remove image" button

			$feat_image_url = wp_get_attachment_url($value);
			
			if(!isset($file_html)) {
				$file_html = "";
			}

			if(!empty($feat_image_url)) {
				$file_html 	.= '<a href="'.esc_url($feat_image_url).'" class="true_pre_image" target="_blank"><span class="dashicons dashicons-media-document"></span></a>';
				$display 	= "inline-block";
			} 
			return '
			<div>
				<a href="#" class="misha_upload_image_button' . $image . '</a>
				<input type="hidden" name="' . $name . '" id="' . $name . '" value="' . $value . '" />
				
				'.$file_html.'

				<a href="#" class="misha_remove_image_button" style="display:inline-block;display:' . $display . '">'.esc_html__("Remove File", "computer-repair-shop").'</a>
			</div>';
		}
	endif;

	if(!function_exists("wc_inventory_management_status")):
		function wc_inventory_management_status() {
			//Inventory Management Header
			if(rb_is_woocommerce_activated() == true) {
				$stockManagement 		= get_option("woocommerce_manage_stock");
				$wc_enable_woo_products = get_option("wc_enable_woo_products");

				if($stockManagement == "yes" && $wc_enable_woo_products == "on") {
					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
		}
	endif;

	if(!function_exists("wc_rs_license_state")):
		function wc_rs_license_state() {
			wc_rs_verify_purchase( '', '' );
			//Get purchase data.
			$purchase_arr = get_option( 'wc_cr_license_details' );

			if ( empty( $purchase_arr ) ) {
				return FALSE;
			}
			if ( ! is_array( $purchase_arr ) ) {
				return FALSE;
			}
			$licenseState = ( isset( $purchase_arr['license_state'] ) && ! empty( $purchase_arr['license_state'] ) ) ? $purchase_arr['license_state'] : '';

			if ( $licenseState != "valid" ) {
				return FALSE;
			}
			$licenseExpiry 	= ( isset( $purchase_arr['support_until'] ) && ! empty( $purchase_arr['support_until'] ) ) ? $purchase_arr['support_until'] : '';

			$licenseExpiry 	= ( ! empty( $licenseExpiry ) ) ? date( 'Y-m-d', strtotime( $licenseExpiry ) ) : '';
			
			if(empty($licenseExpiry)) {
				return FALSE;
			}

			$date_now = date( 'Y-m-d' );
			
			if ( $date_now > $licenseExpiry ) {
				return FALSE;
			} else {
				return TRUE;
			}
		}
	endif;

	/**
	 * WC repairBuddy Status Check link
	 * Only Sends if exists
	 * 
	 * @Since 3.7946
	 * @package Computer Repair Shop
	 */
	if ( ! function_exists( 'wc_rb_return_status_check_link' ) ) {
		function wc_rb_return_status_check_link( $job_id ) {
			if ( empty( $job_id ) ) {
				return;
			}

			$get_status_link = get_option( 'wc_rb_status_check_page_id' );

			if ( empty ( $get_status_link ) ) {
				return;
			}

			$thePermalink  = get_the_permalink( $get_status_link );
			$theCaseNumber = get_post_meta( $job_id, '_case_number', true );

			if ( empty ( $thePermalink ) || empty ( $theCaseNumber ) ) {
				return;
			} else {
				$theRetunLink = add_query_arg( 'case_id', $theCaseNumber, $thePermalink );
				return wcrb_create_short_url( $theRetunLink, 'status_' . $theCaseNumber );
			}
		}
	}

	// Create short URLs with query string support
	if ( ! function_exists( 'wcrb_create_short_url' ) ) : 
		function wcrb_create_short_url( $long_url, $slug = null ) {
			// Generate random slug if not provided
			if (!$slug) {
				$slug = wp_generate_password(8, false, false); // 8-character random slug
			}
			
			$short_url = home_url('/s/' . $slug);
			
			// Store mapping in options - URL is stored with full query strings
			$url_mappings = get_option('url_shortener_mappings', array());
			$url_mappings[$slug] = $long_url; // Stores complete URL with query strings
			update_option('url_shortener_mappings', $url_mappings);
			
			return $short_url;
		}
	endif;

	// Redirect short URLs - handles query strings perfectly
	if ( ! function_exists( 'wcrb_redirect_short_urls' ) ) : 
		add_action( 'init', 'wcrb_redirect_short_urls' );
		function wcrb_redirect_short_urls() {
			if (preg_match('/\/s\/([a-zA-Z0-9_-]+)/', $_SERVER['REQUEST_URI'], $matches)) {
				$slug = $matches[1];
				$url_mappings = get_option('url_shortener_mappings', array());
				
				if (isset($url_mappings[$slug])) {
					$long_url = $url_mappings[$slug];
				
					// Permanent redirect to the long URL (with all query strings preserved)
					wp_redirect($long_url, 301);
					exit;
				} else {
					// Slug not found - redirect to homepage or show 404
					wp_redirect(home_url(), 302);
					exit;
				}
			}
		}
	endif;

	if ( ! function_exists( 'wcrb_download_pdf_link' ) ) {
		function wcrb_download_pdf_link( $job_id ) {
			if ( empty( $job_id ) ) {
				return;
			}

			$get_status_link = get_option( 'wc_rb_status_check_page_id' );

			if ( empty ( $get_status_link ) ) {
				$get_status_link = get_option( 'wc_rb_my_account_page_id' );
			}

			$thePermalink  = get_the_permalink( $get_status_link );
			$theCaseNumber = get_post_meta( $job_id, '_case_number', true );

			if ( empty ( $thePermalink ) || empty ( $theCaseNumber ) ) {
				return;
			} else {
				$args = array( 'case_number' => $theCaseNumber, 'job_id' => $job_id, 'dl_pdf' => 'yes' );
				$theRetunLink = add_query_arg( $args, $thePermalink );
				return $theRetunLink;
			}
		}
	}

	if ( ! function_exists( 'wcrb_on_plugins_loaded_front' ) ) :
		function wcrb_on_plugins_loaded_front() {
			if ( isset( $_GET['case_number'] ) && ! empty( $_GET['case_number'] ) && isset( $_GET['job_id'] ) && ! empty( $_GET['job_id'] ) && isset( $_GET['dl_pdf'] ) && $_GET['dl_pdf'] == 'yes' ) {
				$_thecasenumber = sanitize_text_field( $_GET['case_number'] );
				$_thejobid		= sanitize_text_field( $_GET['job_id'] );
				
				$WCRB_PDF_MAKER = WCRB_PDF_MAKER::getInstance();
				$WCRB_PDF_MAKER->generate_repair_estimate_invoice( $_thejobid, $_thecasenumber );
			}
		}
		add_action( 'init', 'wcrb_on_plugins_loaded_front' );
	endif;

	/**
	 * WC repairBuddy Get Review Link
	 * 
	 * @Since 3.8111
	 * @package Computer Repair Shop
	 */
	if ( ! function_exists( 'wc_rb_return_get_feedback_link' ) ) {
		function wc_rb_return_get_feedback_link( $feedback_id, $job_id ) {
			if ( empty( $job_id ) ) {
				return;
			}
			$wc_rb_get_feedback_page_id = get_option( 'wc_rb_get_feedback_page_id' );
			$theCaseNumber = get_post_meta( $job_id, '_case_number', true );

			if ( empty( $wc_rb_get_feedback_page_id ) ) {
				return;
			}
			$page_link 	 = get_the_permalink( $wc_rb_get_feedback_page_id );

			$feedback_id = ( empty( $feedback_id ) ) ? 'NO' : $feedback_id;

			$_params = array( 'review_id' => $feedback_id, 'case_number' => $theCaseNumber, 'job_id' => $job_id );
			$page_link = add_query_arg( $_params, $page_link );

			return ( ! empty( $page_link ) ) ? wcrb_create_short_url( $page_link, 'feedback_' . $theCaseNumber ) : '';
		}
	}

	/**
	 * Have to return TRUE or FALSE
	 * If WooCommerce Method is selected
	 * and WooCommerce is installed and active.
	 */
	if ( ! function_exists( 'wcrb_is_method_woocommerce' ) ) {
		function wcrb_is_method_woocommerce() {
			if ( rb_is_woocommerce_activated() == FALSE ) {
				return FALSE;
			}

			$selected_methods = get_option( 'wc_rb_payment_methods_active' );
			$selected_array   = ( ! empty( $selected_methods ) ) ? unserialize( $selected_methods ) : '';

			if ( is_array( $selected_array ) ) {
				if ( in_array( 'woocommerce', $selected_array ) ) {
					return TRUE;
				} else {
					return FALSE;
				}
			} else {
				return FALSE;
			}
		}
	}

	if(!function_exists("wc_custom_post_capabilities")):
		function wc_custom_post_capabilities($singular = 'post', $plural = 'posts') {
			return [
				'read'      			=> "read_$singular",
				'delete_post'        	=> "delete_$singular",
				'edit_posts'         	=> "edit_$plural",
				'edit_others_posts'  	 => "edit_others_$plural",
				'publish_posts'      	 => "publish_$plural",
				'read_private_posts'     => "read_private_$plural",
				'delete_posts'           => "delete_$plural",
				'delete_private_posts'   => "delete_private_$plural",
				'delete_published_posts' => "delete_published_$plural",
				'delete_others_posts'    => "delete_others_$plural",
				'edit_private_posts'     => "edit_private_$plural",
				'edit_published_posts'   => "edit_published_$plural",
				'create_posts'           => "edit_$plural",
			];
		}
	endif;

	/**
	 * Countries Dropdown
	 * @Since 3.6
	 * @Package: CRM RepairBuddy
	 * 
	 * Added: 28, January, 2022
	 */
	if( ! function_exists( "wc_cr_countries_dropdown" ) ) : 
		function wc_cr_countries_dropdown( $selected, $return ) {

			$countries_arr = 
			array(
			""	=> esc_html__("Select Country", "computer-repair-shop"),
			"AF" => esc_html__("Afghanistan", "computer-repair-shop"),
			"AL" => esc_html__("Albania", "computer-repair-shop"),
			"DZ" => esc_html__("Algeria", "computer-repair-shop"),
			"AS" => esc_html__("American Samoa", "computer-repair-shop"),
			"AD" => esc_html__("Andorra", "computer-repair-shop"),
			"AO" => esc_html__("Angola", "computer-repair-shop"),
			"AI" => esc_html__("Anguilla", "computer-repair-shop"),
			"AQ" => esc_html__("Antarctica", "computer-repair-shop"),
			"AG" => esc_html__("Antigua and Barbuda", "computer-repair-shop"),
			"AR" => esc_html__("Argentina", "computer-repair-shop"),
			"AM" => esc_html__("Armenia", "computer-repair-shop"),
			"AW" => esc_html__("Aruba", "computer-repair-shop"),
			"AU" => esc_html__("Australia", "computer-repair-shop"),
			"AT" => esc_html__("Austria", "computer-repair-shop"),
			"AZ" => esc_html__("Azerbaijan", "computer-repair-shop"),
			"BS" => esc_html__("Bahamas", "computer-repair-shop"),
			"BH" => esc_html__("Bahrain", "computer-repair-shop"),
			"BD" => esc_html__("Bangladesh", "computer-repair-shop"),
			"BB" => esc_html__("Barbados", "computer-repair-shop"),
			"BY" => esc_html__("Belarus", "computer-repair-shop"),
			"BE" => esc_html__("Belgium", "computer-repair-shop"),
			"BZ" => esc_html__("Belize", "computer-repair-shop"),
			"BJ" => esc_html__("Benin", "computer-repair-shop"),
			"BM" => esc_html__("Bermuda", "computer-repair-shop"),
			"BT" => esc_html__("Bhutan", "computer-repair-shop"),
			"BO" => esc_html__("Bolivia", "computer-repair-shop"),
			"BA" => esc_html__("Bosnia and Herzegovina", "computer-repair-shop"),
			"BW" => esc_html__("Botswana", "computer-repair-shop"),
			"BV" => esc_html__("Bouvet Island", "computer-repair-shop"),
			"BR" => esc_html__("Brazil", "computer-repair-shop"),
			"IO" => esc_html__("British Indian Ocean Territory", "computer-repair-shop"),
			"BN" => esc_html__("Brunei Darussalam", "computer-repair-shop"),
			"BG" => esc_html__("Bulgaria", "computer-repair-shop"),
			"BF" => esc_html__("Burkina Faso", "computer-repair-shop"),
			"BI" => esc_html__("Burundi", "computer-repair-shop"),
			"KH" => esc_html__("Cambodia", "computer-repair-shop"),
			"CM" => esc_html__("Cameroon", "computer-repair-shop"),
			"CA" => esc_html__("Canada", "computer-repair-shop"),
			"CV" => esc_html__("Cape Verde", "computer-repair-shop"),
			"KY" => esc_html__("Cayman Islands", "computer-repair-shop"),
			"CF" => esc_html__("Central African Republic", "computer-repair-shop"),
			"TD" => esc_html__("Chad", "computer-repair-shop"),
			"CL" => esc_html__("Chile", "computer-repair-shop"),
			"CN" => esc_html__("China", "computer-repair-shop"),
			"CX" => esc_html__("Christmas Island", "computer-repair-shop"),
			"CC" => esc_html__("Cocos (Keeling) Islands", "computer-repair-shop"),
			"CO" => esc_html__("Colombia", "computer-repair-shop"),
			"KM" => esc_html__("Comoros", "computer-repair-shop"),
			"CG" => esc_html__("Congo", "computer-repair-shop"),
			"CD" => esc_html__("Congo, the Democratic Republic of the", "computer-repair-shop"),
			"CK" => esc_html__("Cook Islands", "computer-repair-shop"),
			"CR" => esc_html__("Costa Rica", "computer-repair-shop"),
			"CI" => esc_html__("Cote D'Ivoire", "computer-repair-shop"),
			"HR" => esc_html__("Croatia", "computer-repair-shop"),
			"CU" => esc_html__("Cuba", "computer-repair-shop"),
			"CY" => esc_html__("Cyprus", "computer-repair-shop"),
			"CZ" => esc_html__("Czech Republic", "computer-repair-shop"),
			"DK" => esc_html__("Denmark", "computer-repair-shop"),
			"DJ" => esc_html__("Djibouti", "computer-repair-shop"),
			"DM" => esc_html__("Dominica", "computer-repair-shop"),
			"DO" => esc_html__("Dominican Republic", "computer-repair-shop"),
			"EC" => esc_html__("Ecuador", "computer-repair-shop"),
			"EG" => esc_html__("Egypt", "computer-repair-shop"),
			"SV" => esc_html__("El Salvador", "computer-repair-shop"),
			"GQ" => esc_html__("Equatorial Guinea", "computer-repair-shop"),
			"ER" => esc_html__("Eritrea", "computer-repair-shop"),
			"EE" => esc_html__("Estonia", "computer-repair-shop"),
			"ET" => esc_html__("Ethiopia", "computer-repair-shop"),
			"FK" => esc_html__("Falkland Islands (Malvinas)", "computer-repair-shop"),
			"FO" => esc_html__("Faroe Islands", "computer-repair-shop"),
			"FJ" => esc_html__("Fiji", "computer-repair-shop"),
			"FI" => esc_html__("Finland", "computer-repair-shop"),
			"FR" => esc_html__("France", "computer-repair-shop"),
			"GF" => esc_html__("French Guiana", "computer-repair-shop"),
			"PF" => esc_html__("French Polynesia", "computer-repair-shop"),
			"TF" => esc_html__("French Southern Territories", "computer-repair-shop"),
			"GA" => esc_html__("Gabon", "computer-repair-shop"),
			"GM" => esc_html__("Gambia", "computer-repair-shop"),
			"GE" => esc_html__("Georgia", "computer-repair-shop"),
			"DE" => esc_html__("Germany", "computer-repair-shop"),
			"GH" => esc_html__("Ghana", "computer-repair-shop"),
			"GI" => esc_html__("Gibraltar", "computer-repair-shop"),
			"GR" => esc_html__("Greece", "computer-repair-shop"),
			"GL" => esc_html__("Greenland", "computer-repair-shop"),
			"GD" => esc_html__("Grenada", "computer-repair-shop"),
			"GP" => esc_html__("Guadeloupe", "computer-repair-shop"),
			"GU" => esc_html__("Guam", "computer-repair-shop"),
			"GT" => esc_html__("Guatemala", "computer-repair-shop"),
			"GN" => esc_html__("Guinea", "computer-repair-shop"),
			"GW" => esc_html__("Guinea-Bissau", "computer-repair-shop"),
			"GY" => esc_html__("Guyana", "computer-repair-shop"),
			"HT" => esc_html__("Haiti", "computer-repair-shop"),
			"HM" => esc_html__("Heard Island and Mcdonald Islands", "computer-repair-shop"),
			"VA" => esc_html__("Holy See (Vatican City State)", "computer-repair-shop"),
			"HN" => esc_html__("Honduras", "computer-repair-shop"),
			"HK" => esc_html__("Hong Kong", "computer-repair-shop"),
			"HU" => esc_html__("Hungary", "computer-repair-shop"),
			"IS" => esc_html__("Iceland", "computer-repair-shop"),
			"IN" => esc_html__("India", "computer-repair-shop"),
			"ID" => esc_html__("Indonesia", "computer-repair-shop"),
			"IR" => esc_html__("Iran, Islamic Republic", "computer-repair-shop"),
			"IQ" => esc_html__("Iraq", "computer-repair-shop"),
			"IE" => esc_html__("Ireland", "computer-repair-shop"),
			"IL" => esc_html__("Israel", "computer-repair-shop"),
			"IT" => esc_html__("Italy", "computer-repair-shop"),
			"JM" => esc_html__("Jamaica", "computer-repair-shop"),
			"JP" => esc_html__("Japan", "computer-repair-shop"),
			"JO" => esc_html__("Jordan", "computer-repair-shop"),
			"KZ" => esc_html__("Kazakhstan", "computer-repair-shop"),
			"KE" => esc_html__("Kenya", "computer-repair-shop"),
			"KI" => esc_html__("Kiribati", "computer-repair-shop"),
			"KP" => esc_html__("Korea, Democratic People's Republic", "computer-repair-shop"),
			"KR" => esc_html__("Korea, Republic of", "computer-repair-shop"),
			"KW" => esc_html__("Kuwait", "computer-repair-shop"),
			"KG" => esc_html__("Kyrgyzstan", "computer-repair-shop"),
			"LA" => esc_html__("Lao People's Democratic Republic", "computer-repair-shop"),
			"LV" => esc_html__("Latvia", "computer-repair-shop"),
			"LB" => esc_html__("Lebanon", "computer-repair-shop"),
			"LS" => esc_html__("Lesotho", "computer-repair-shop"),
			"LR" => esc_html__("Liberia", "computer-repair-shop"),
			"LY" => esc_html__("Libyan Arab Jamahiriya", "computer-repair-shop"),
			"LI" => esc_html__("Liechtenstein", "computer-repair-shop"),
			"LT" => esc_html__("Lithuania", "computer-repair-shop"),
			"LU" => esc_html__("Luxembourg", "computer-repair-shop"),
			"MO" => esc_html__("Macao", "computer-repair-shop"),
			"MK" => esc_html__("Macedonia, the Former Yugoslav Republic of", "computer-repair-shop"),
			"MG" => esc_html__("Madagascar", "computer-repair-shop"),
			"MW" => esc_html__("Malawi", "computer-repair-shop"),
			"MY" => esc_html__("Malaysia", "computer-repair-shop"),
			"MV" => esc_html__("Maldives", "computer-repair-shop"),
			"ML" => esc_html__("Mali", "computer-repair-shop"),
			"MT" => esc_html__("Malta", "computer-repair-shop"),
			"MH" => esc_html__("Marshall Islands", "computer-repair-shop"),
			"MQ" => esc_html__("Martinique", "computer-repair-shop"),
			"MR" => esc_html__("Mauritania", "computer-repair-shop"),
			"MU" => esc_html__("Mauritius", "computer-repair-shop"),
			"YT" => esc_html__("Mayotte", "computer-repair-shop"),
			"MX" => esc_html__("Mexico", "computer-repair-shop"),
			"FM" => esc_html__("Micronesia, Federated States of", "computer-repair-shop"),
			"MD" => esc_html__("Moldova, Republic of", "computer-repair-shop"),
			"MC" => esc_html__("Monaco", "computer-repair-shop"),
			"MN" => esc_html__("Mongolia", "computer-repair-shop"),
			"MS" => esc_html__("Montserrat", "computer-repair-shop"),
			"MA" => esc_html__("Morocco", "computer-repair-shop"),
			"MZ" => esc_html__("Mozambique", "computer-repair-shop"),
			"MM" => esc_html__("Myanmar", "computer-repair-shop"),
			"NA" => esc_html__("Namibia", "computer-repair-shop"),
			"NR" => esc_html__("Nauru", "computer-repair-shop"),
			"NP" => esc_html__("Nepal", "computer-repair-shop"),
			"NL" => esc_html__("Netherlands", "computer-repair-shop"),
			"AN" => esc_html__("Netherlands Antilles", "computer-repair-shop"),
			"NC" => esc_html__("New Caledonia", "computer-repair-shop"),
			"NZ" => esc_html__("New Zealand", "computer-repair-shop"),
			"NI" => esc_html__("Nicaragua", "computer-repair-shop"),
			"NE" => esc_html__("Niger", "computer-repair-shop"),
			"NG" => esc_html__("Nigeria", "computer-repair-shop"),
			"NU" => esc_html__("Niue", "computer-repair-shop"),
			"NF" => esc_html__("Norfolk Island", "computer-repair-shop"),
			"MP" => esc_html__("Northern Mariana Islands", "computer-repair-shop"),
			"NO" => esc_html__("Norway", "computer-repair-shop"),
			"OM" => esc_html__("Oman", "computer-repair-shop"),
			"PK" => esc_html__("Pakistan", "computer-repair-shop"),
			"PW" => esc_html__("Palau", "computer-repair-shop"),
			"PS" => esc_html__("Palestine", "computer-repair-shop"),
			"PA" => esc_html__("Panama", "computer-repair-shop"),
			"PG" => esc_html__("Papua New Guinea", "computer-repair-shop"),
			"PY" => esc_html__("Paraguay", "computer-repair-shop"),
			"PE" => esc_html__("Peru", "computer-repair-shop"),
			"PH" => esc_html__("Philippines", "computer-repair-shop"),
			"PN" => esc_html__("Pitcairn", "computer-repair-shop"),
			"PL" => esc_html__("Poland", "computer-repair-shop"),
			"PT" => esc_html__("Portugal", "computer-repair-shop"),
			"PR" => esc_html__("Puerto Rico", "computer-repair-shop"),
			"QA" => esc_html__("Qatar", "computer-repair-shop"),
			"RE" => esc_html__("Reunion", "computer-repair-shop"),
			"RO" => esc_html__("Romania", "computer-repair-shop"),
			"RU" => esc_html__("Russian Federation", "computer-repair-shop"),
			"RW" => esc_html__("Rwanda", "computer-repair-shop"),
			"SH" => esc_html__("Saint Helena", "computer-repair-shop"),
			"KN" => esc_html__("Saint Kitts and Nevis", "computer-repair-shop"),
			"LC" => esc_html__("Saint Lucia", "computer-repair-shop"),
			"PM" => esc_html__("Saint Pierre and Miquelon", "computer-repair-shop"),
			"VC" => esc_html__("Saint Vincent and the Grenadines", "computer-repair-shop"),
			"WS" => esc_html__("Samoa", "computer-repair-shop"),
			"SM" => esc_html__("San Marino", "computer-repair-shop"),
			"ST" => esc_html__("Sao Tome and Principe", "computer-repair-shop"),
			"SA" => esc_html__("Saudi Arabia", "computer-repair-shop"),
			"SN" => esc_html__("Senegal", "computer-repair-shop"),
			"CS" => esc_html__("Serbia and Montenegro", "computer-repair-shop"),
			"SC" => esc_html__("Seychelles", "computer-repair-shop"),
			"SL" => esc_html__("Sierra Leone", "computer-repair-shop"),
			"SG" => esc_html__("Singapore", "computer-repair-shop"),
			"SK" => esc_html__("Slovakia", "computer-repair-shop"),
			"SI" => esc_html__("Slovenia", "computer-repair-shop"),
			"SB" => esc_html__("Solomon Islands", "computer-repair-shop"),
			"SO" => esc_html__("Somalia", "computer-repair-shop"),
			"ZA" => esc_html__("South Africa", "computer-repair-shop"),
			"GS" => esc_html__("South Georgia and the South Sandwich Islands", "computer-repair-shop"),
			"ES" => esc_html__("Spain", "computer-repair-shop"),
			"LK" => esc_html__("Sri Lanka", "computer-repair-shop"),
			"SD" => esc_html__("Sudan", "computer-repair-shop"),
			"SR" => esc_html__("Suriname", "computer-repair-shop"),
			"SJ" => esc_html__("Svalbard and Jan Mayen", "computer-repair-shop"),
			"SZ" => esc_html__("Swaziland", "computer-repair-shop"),
			"SE" => esc_html__("Sweden", "computer-repair-shop"),
			"CH" => esc_html__("Switzerland", "computer-repair-shop"),
			"SY" => esc_html__("Syrian Arab Republic", "computer-repair-shop"),
			"TW" => esc_html__("Taiwan, Province of China", "computer-repair-shop"),
			"TJ" => esc_html__("Tajikistan", "computer-repair-shop"),
			"TZ" => esc_html__("Tanzania, United Republic of", "computer-repair-shop"),
			"TH" => esc_html__("Thailand", "computer-repair-shop"),
			"TL" => esc_html__("Timor-Leste", "computer-repair-shop"),
			"TG" => esc_html__("Togo", "computer-repair-shop"),
			"TK" => esc_html__("Tokelau", "computer-repair-shop"),
			"TO" => esc_html__("Tonga", "computer-repair-shop"),
			"TT" => esc_html__("Trinidad and Tobago", "computer-repair-shop"),
			"TN" => esc_html__("Tunisia", "computer-repair-shop"),
			"TR" => esc_html__("Turkey", "computer-repair-shop"),
			"TM" => esc_html__("Turkmenistan", "computer-repair-shop"),
			"TC" => esc_html__("Turks and Caicos Islands", "computer-repair-shop"),
			"TV" => esc_html__("Tuvalu", "computer-repair-shop"),
			"UG" => esc_html__("Uganda", "computer-repair-shop"),
			"UA" => esc_html__("Ukraine", "computer-repair-shop"),
			"AE" => esc_html__("United Arab Emirates", "computer-repair-shop"),
			"GB" => esc_html__("United Kingdom", "computer-repair-shop"),
			"US" => esc_html__("United States", "computer-repair-shop"),
			"UM" => esc_html__("United States Minor Outlying Islands", "computer-repair-shop"),
			"UY" => esc_html__("Uruguay", "computer-repair-shop"),
			"UZ" => esc_html__("Uzbekistan", "computer-repair-shop"),
			"VU" => esc_html__("Vanuatu", "computer-repair-shop"),
			"VE" => esc_html__("Venezuela", "computer-repair-shop"),
			"VN" => esc_html__("Viet Nam", "computer-repair-shop"),
			"VG" => esc_html__("Virgin Islands, British", "computer-repair-shop"),
			"VI" => esc_html__("Virgin Islands, U.s.", "computer-repair-shop"),
			"WF" => esc_html__("Wallis and Futuna", "computer-repair-shop"),
			"EH" => esc_html__("Western Sahara", "computer-repair-shop"),
			"YE" => esc_html__("Yemen", "computer-repair-shop"),
			"ZM" => esc_html__("Zambia", "computer-repair-shop"),
			"ZW" => esc_html__("Zimbabwe", "computer-repair-shop")
			);
			
			$output = "";

			foreach($countries_arr as $code => $country) {
				$slectfi = ($selected == $code) ? "selected='selected'" : "";
				$output .= "<option {$slectfi} value='{$code}'>{$country}</option>";
			}

			$allowedHTML = wc_return_allowed_tags(); 
			if ( $return == 'echo' ) {
				echo wp_kses( $output, $allowedHTML );
			} else {
				return wp_kses( $output, $allowedHTML );
			}
		}
	endif;

	if(!function_exists("wc_cr_new_purchase_link")) {
		function wc_cr_new_purchase_link( $screen ) {
			$pro_url = ( ! defined( 'REPAIRBUDDY_PRO_URL' ) ) ? esc_url( 'https://www.webfulcreations.com/products/computer-repair-shop-crm-wordpress-plugin/' ) : REPAIRBUDDY_PRO_URL;
			if ( $screen == "license" ) {
				$output = '<div class="purchase_banner_wc">';
				$output .= '<h2>'.esc_html__("If you don't have license or want to purchase another one click link below.", "computer-repair-shop").'</h2>';
				$output .= '<a href="' . esc_url( $pro_url ) . '" 
				class="button btn-secondary secondary-btn primary" 
				target="_blank">'.esc_html__("Purchase License", "computer-repair-shop").'</a>';
				$output .= '<p>'.esc_html__("Please check email for your account details to get your purchase code after buying plugin.", "computer-repair-shop").'</p>';
				$output .= '</div>';
			} else {
				$output = '<h2>Print and This detail is available only in Premium Version. <a href="' . esc_url( $pro_url ) . '" class="button btn-primary primary-btn primary" style="color:#FFF;background:orange;text-transform:uppercase;border:0px;" target="_blank">Check Details</a></h2>';
			}
			return $output;
		}
	}

	if(!function_exists("wc_rb_return_logo_url_with_img")):
		function wc_rb_return_logo_url_with_img($image_class) {
			$computer_repair_logo 	= get_option("computer_repair_logo");                                    
            $content 				= "";

			$image_class 			= (empty($image_class)) ? "company_logo" : $image_class;

            if(!has_custom_logo() && empty($computer_repair_logo)) { 
                $content .= '<h1 class="site-title">'.get_bloginfo( 'name' ).'</h1>';
            } else { 
				if(empty($computer_repair_logo)) {
					$custom_logo_id 		= get_theme_mod( 'custom_logo' );
					$image 					= wp_get_attachment_image_src( $custom_logo_id , 'full' );
					$computer_repair_logo 	= $image[0];
				}
                $content .= '<img src="'.esc_url($computer_repair_logo).'" class="'.esc_attr( $image_class ).'" />';
            }
			return $content;
		}
	endif;

	if(!function_exists("wc_return_allowed_tags")):
		function wc_return_allowed_tags() {
			$allowed_tags = array(
			'div' => array(
				'class' 		  => array(),
				'id' 			  => array(),
				'style' 		  => array(),
				'data-position'   => array(),
				'data-alignment'  => array(),
				'data-dropdown'   => array(),
				'data-auto-focus' => array(),
				'data-reveal' 	  => array(),
				'data-abide-error' => array(),
				'data-tab-content' => array(),
			),
			'form' => array(
				'class' => array(),
				'id' => array(),
				'name' => array(),
				'method' => array(),
				'action' => array(),
				'data-async' => array(),
				'data-success-class' => array(),
				'data-abide' => array()
			),
			'label' => array(
				'class' => array(),
				'id' => array(),
				'for'	=> array()
			),
			'i' => array(
				'data-star' => array(),
				'class' => array(),
			),
			'input' => array(
				'class' => array(),
				'id' => array(),
				'data-identifier' => array(),
				'data-security' => array(),
				'type'	=> array(),
				'name'	=> array(),
				'required' => array(),
				'value'	=> array(),
				'placeholder'	=> array(),
				'checked' => array(),
				'step'	=> array(),
				'inputmode' => array(),
				'min'	=> array(),
				'style'	=> array(),
				'max'	=> array(),
				'list'	=> array(),
				'disabled' => array(),
				'readonly' => array(),
				'data-target' => array(),
				'title_target' => array(),
			),
			'textarea' => array(
				'class' => array(),
				'id' => array(),
				'type'	=> array(),
				'name'	=> array(),
				'required' => array(),
				'placeholder'	=> array(),
				'cols'	=> array(),
				'rows' => array()
			),
			'optgroup' => array(
				'label' => array(),
			),
			'select' => array(
				'class' => array(),
				'id' => array(),
				'name'	=> array(),
				'required' => array(),
				'multiple' => array(),
				'data-placeholder' => array(),
				'data-security' => array(),
				'data-device-id' => array(),
				'data-exclude_type' => array(),
				'data-display_stock' => array(),
				'data-post' => array(),
				'data-type' => array(),
				'data-value' => array(),
				'recordid' => array(),
				'data-label' => array(),
				'style' => array(),
			),
			'option' => array(
				'value' => array(),
				'selected' => array(),
			),
			'button' => array(
				'class' => array(),
				'id' => array(),
				'for'	=> array(),
				'data-bs-dismiss' => array(),
				'name'	=> array(),
				'type' => array(),
				'data-bs-toggle' => array(),
				'data-bs-target' => array(),
				'data-open' => array(),
				'data-close' => array(),
				'data-type' => array(), 'recordid' => array(), 'data-value' => array(),
				'data-job-id' => array(),
				'data-part-identifier' => array(),
				'data-toggle' => array(),
				'for' => array(),
				'data-field' => array()
			),
			'fieldset' => array(
				'class' => array(),
			),
			'legend' => array(
				'class' => array(),
			),
			'datalist' => array(
				'id' => array(),
			),
			'a' => array(
				'class' 				=> array(), 'id' 					=> array(), 
				'href'					=> array(), 'title'					=> array(),
				'target' 				=> array(), 'recordid' 				=> array(),
				'data-open' 			=> array(), 'data-type' 			=> array(),
				'data-value' 			=> array(), 'style' 				=> array(),
				'dt_brand_device' 		=> array(), 'dt_brand_id' 			=> array(),
				'dt_device_type' 		=> array(), 'dt_type_id' 			=> array(),
				'dt_device_type_id' 	=> array(), 'dt_device_brand_id'	=> array(),
				'checkbox-toggle-group' => array(), 'data-security' 		=> array(),
				'dt_brand_g_id' 		=> array(), 'data-identifier' 		=> array(),
				'data-job-id' 			=> array(),	'target'				=> array(),
				'data-alert-msg' 		=> array(), 'data-bs-toggle' 		=> array(),
				'data-bs-target'		=> array()
			),
			'table' => array( 'class' => array(), 'id' => array(), 'cellpadding' => array(), 'cellspacing' => array() ),
			'thead' => array( 'class' => array(), 'id' => array() ),
			'tbody' => array( 'class' => array(), 'id' => array() ),
			'tr' => array( 'class' => array(), 'id' => array() ),
			'th' => array( 'class' => array(), 'id' => array(), 'colspan' => array(), 'data-colname' => array(), 'data-label' => array() ),
			'td' => array( 'class' => array(), 'id' => array(), 'colspan' => array(), 'data-colname' => array(), 'data-label' => array(), 'data-bs-toggle' => array(), 'data-bs-title' => array(), 'style' => array() ),
			'img' => array( 'class' => array(), 'id' => array(), 'src' => array(), 'alt' => array() ),
			'h1' => array( 'class' => array(), 'id' => array(), ),
			'h2' => array( 'class' => array(), 'id' => array(), ),
			'h3' => array( 'class' => array(), 'id' => array(), ),
			'h4' => array( 'class' => array(), 'id' => array(), ),
			'h5' => array( 'class' => array(), 'id' => array(), ),
			'h6' => array( 'class' => array(), 'id' => array(), ),
			'small' => array( 'class' => array(), 'id' => array(), ),
			'ul' => array( 'class' => array(), 'id' => array(), 'data-accordion'	=> array(), 'data-multi-expand'	=> array(), 'data-allow-all-closed' => array(), ),
			'ol' => array(), 'li' => array( 'class' => array(), 'id' => array(), 'data-accordion-item' => array(), ), 'p' => array( 'class' => array() ), 'br' => array(), 'em' => array(), 'em' => array(), 'hr' => array(), 'pre' => array(),
			'strong' => array( 'class' => array(), 'title' => array() ), 
			'span' => array( 'class' => array() ),
			'style' => array( 'type' => array() ), 'head' => array(), 'body' => array(), 'html' => array(),
		);

			return $allowed_tags;
		}
	endif;

	/**
	 * Set new  way of job device
	 * Accepts Job ID
	 * Set _wc_device_data as serliazed array
	 * 
	 * @Since 3.75
	 */
	if ( ! function_exists( 'wc_set_new_device_format' ) ) {
		function wc_set_new_device_format( $job_id ) {
			if ( empty( $job_id ) ) {
				return;
			}
			
			$return_type = ( empty( $return_type ) ) ? 'job_html' : $return_type;

			$wc_device_data = get_post_meta( $job_id, '_wc_device_data', true );

			if ( empty( $wc_device_data ) ) {
				//Let's arrange data in new form.
				$device_post_id = get_post_meta( $job_id, "_device_post_id", true );
				$device_id      = get_post_meta( $job_id, "_device_id", true );
				$device_login   = get_post_meta( $job_id, "_device_login", true );

				if ( !empty( $device_post_id ) || !empty( $device_id ) || !empty( $device_login ) ) {
					$array_devices = array(
						array(
							'device_post_id' => $device_post_id,
							'device_id'      => $device_id,
							'device_login'   => $device_login,
							'device_note'	 => '',
						),
					);

					update_post_meta( $job_id, '_wc_device_data', $array_devices );

					update_post_meta( $job_id, '_device_post_id', '' );
					update_post_meta( $job_id, '_device_id', '' );
					update_post_meta( $job_id, '_device_login', '' );
				}
			}//If device have data.
		}//Function ends.
	}

	/**
	 * Function Generate 
	 * GDPR Acceptance Field
	 * 
	 * @Since: 3.794
	 */
	if ( ! function_exists( 'wc_rb_gdpr_acceptance_link_generate' ) ) :
		function wc_rb_gdpr_acceptance_link_generate() {
			$wc_rb_gdpr_acceptance_link   = ( empty( get_option( 'wc_rb_gdpr_acceptance_link' ) ) ) ? '' : get_option( 'wc_rb_gdpr_acceptance_link' );
			$wc_rb_gdpr_acceptance 		  = ( empty( get_option( 'wc_rb_gdpr_acceptance' ) ) ) ? esc_html__( 'I understand that I will be contacted by a representative regarding this request and I agree to the privacy policy.', 'computer-repair-shop' ) : get_option( 'wc_rb_gdpr_acceptance' );

			$privacy_policy_label = ( empty( get_option( 'wc_rb_gdpr_acceptance_link_label' ) ) ) ? esc_html__( 'Privacy policy', 'computer-repair-shop' ) : get_option( 'wc_rb_gdpr_acceptance_link_label' );

			$content = '<div class="grid-x grid-margin-x">';
			$content .= '<div class="medium-12 cell">';

			$content .= '<label><input type="checkbox" name="theGdprAccept" required value="Yes" /> (*) ' . esc_html( $wc_rb_gdpr_acceptance );

			$content .= ( empty( $wc_rb_gdpr_acceptance_link ) ) ? '' : ' <a href="' . esc_url( $wc_rb_gdpr_acceptance_link ) . '" target="_blank">'. esc_html( $privacy_policy_label ) .'</a>';
			
			$content .= '</label>';
		
			$content .= '</div><!-- column Ends /-->';  
			$content .= '</div><!-- grid-x ends /-->';

			return $content;
		}
	endif;

	if ( ! function_exists( 'wc_rb_order_checkout_page' ) ) :
		add_action( 'before_woocommerce_pay', 'wc_rb_order_checkout_page', 10 );
		function wc_rb_order_checkout_page() {
			global $PAYMENT_STATUS_OBJ;
			$the_order_id = '';
			if ( isset( $_GET['order-pay'] ) && ! empty( $_GET['order-pay'] ) ) {
				$the_order_id = sanitize_text_field( $_GET['order-pay'] );
			} elseif ( ! empty( get_query_var( 'order-pay' ) ) ) {
				$the_order_id = get_query_var( 'order-pay' );
			}
			//Get order ID.
			
			if ( ! empty( $the_order_id )  ) {
				$job_id = $PAYMENT_STATUS_OBJ->wc_return_order_id_by_woo_order_num( $the_order_id );

				if ( ! empty( $job_id ) ) {
					$case_number  = get_post_meta( $job_id, '_case_number', true );
					$check_status = wc_rb_return_status_check_link( $job_id );

					$message = '<div class="woocommerce-info">';
					$message .= esc_html__( 'The following order is related to your ticket number', 'computer-repair-shop' ) . '{ ' . $case_number . ' }';
					$message .= ( ! empty( $check_status ) ) ? '<br>' . esc_html__( 'Checkout details of your ticket', 'computer-repair-shop' ) . '<a href="'. esc_url( $check_status ) .'" class="button alt wp-element-button" target="_blank">'. esc_html__( 'View Details', 'computer-repair-shop' ) .'</a>' : '';
					$message .= '</div>';

					$allowedHTML = wc_return_allowed_tags(); 
					echo wp_kses( $message, $allowedHTML );
				}
			}
		}
	endif;
	
	if ( ! function_exists( 'wcrb_order_pay_without_login' ) ) :
		add_filter( 'user_has_cap', 'wcrb_order_pay_without_login', 9999, 3 );
		function wcrb_order_pay_without_login( $allcaps, $caps, $args ) {
		   if ( isset( $caps[0], $_GET['key'] ) ) {
			  if ( $caps[0] == 'pay_for_order' ) {
				 $order_id = isset( $args[2] ) ? $args[2] : null;
				 $order = wc_get_order( $order_id );
				 if ( $order ) {
					$allcaps['pay_for_order'] = true;
				 }
			  }
		   }
		   return $allcaps;
		}
	endif;

	//state can be set, decrease or increase
	if ( ! function_exists( 'update_woo_record' ) ) {
		function update_woo_record( $thepID, $qty, $state ) {
			if ( ! empty( $thepID ) && ! empty( $qty ) && ! empty( $state ) ) {
				wc_update_product_stock( $thepID, $qty, $state, false);
				wc_delete_product_transients( $thepID );
			}		
		}
	}

	if ( ! function_exists( 'wcrb_use_woo_as_devices' ) ) {
		function wcrb_use_woo_as_devices() {
			if ( rb_is_woocommerce_activated() ) {
				$wcrbreplacedevices_f	= get_option( 'wc_enable_devices_as_woo_products' );

				if ( $wcrbreplacedevices_f == 'on' ) {
					return 'YES';
				} else {
					return 'NO';
				}
			} else {
				return 'NO';
			}
		}
	}

	if ( ! function_exists( 'wcrb_count_jobs_by_status' ) ) : 
		function wcrb_count_jobs_by_status( $status_slug, $state = 'backend' ) {
			if ( empty( $status_slug ) ) {
				return 0;
			}

			$user_id    = get_current_user_id();
			$user_roles = wc_get_user_roles_by_user_id( $user_id );
			
			// Determine if user should see all jobs
			$can_view_all = array_intersect( array( 'administrator', 'store_manager' ), $user_roles );
			
			// Base query arguments
			$args = array(
				'post_type'      => 'rep_jobs',
				'posts_per_page' => -1,
				'fields'         => 'ids', // Optimize: only get IDs
				'meta_query'     => array(
					array(
						'key'     => '_wc_order_status',
						'value'   => sanitize_text_field( $status_slug ),
						'compare' => '=',
					)
				),
			);

			// For backend or users who can view all jobs
			if ( $state !== 'frontend' || ! empty( $can_view_all ) ) {
				$args['post_status'] = array( 'publish', 'pending', 'draft', 'private', 'future' );
			}
			
			// For frontend users with limited view
			if ( $state === 'frontend' && empty( $can_view_all ) ) {
				$user_meta_query = wcrb_get_user_jobs_meta_query( $user_id, $user_roles );
				
				if ( $user_meta_query ) {
					$args['meta_query'][] = $user_meta_query;
					$args['meta_query']['relation'] = 'AND';
				}
			}

			$query = new WP_Query( $args );
			return $query->found_posts;
		}
	endif;

	// Helper function to get user-specific meta query
	if ( ! function_exists( 'wcrb_get_user_jobs_meta_query' ) ) :
		function wcrb_get_user_jobs_meta_query( $user_id, $user_roles ) {
			if ( in_array( 'customer', $user_roles ) ) {
				return array(
					'key'     => '_customer',
					'value'   => (int) $user_id,
					'compare' => '=',
					'type'    => 'NUMERIC',
				);
			}
			
			if ( in_array( 'technician', $user_roles ) ) {
				return array(
					'relation' => 'OR',
					// Exact numeric match (single technician)
					array(
						'key'     => '_technician',
						'value'   => (int) $user_id,
						'compare' => '=',
						'type'    => 'NUMERIC',
					),
					// Serialized array with quotes (most common)
					array(
						'key'     => '_technician',
						'value'   => '"' . (int) $user_id . '"',
						'compare' => 'LIKE',
					),
					// Serialized array without quotes
					array(
						'key'     => '_technician',
						'value'   => ':' . (int) $user_id . ';',
						'compare' => 'LIKE',
					),
					// For comma-separated strings
					array(
						'key'     => '_technician',
						'value'   => '^' . (int) $user_id . '$|^' . (int) $user_id . ',|,' . (int) $user_id . '$|,' . (int) $user_id . ',',
						'compare' => 'REGEXP',
					),
				);
			}
			
			return null;
		}
	endif;

	if ( ! function_exists( 'wcrb_return_device_terms' ) ) :
		function wcrb_return_device_terms( $device_id, $return_term ) {
			if ( empty( $device_id ) || empty( $return_term ) ) {
				return '';
			}
			$returnTerms = get_the_terms( $device_id, $return_term );
			$id = '';
			if ( ! empty( $returnTerms ) ) {
				foreach( $returnTerms as $theterm ) {
					$id = $theterm->term_id;
				}
			}
			return $id;
		}
	endif;

	if ( ! function_exists( 'rb_redirect_user_after_login' ) ) :
		function rb_redirect_user_after_login() {
			$current_user = wp_get_current_user();

			if ( in_array( 'customer', (array) $current_user->roles ) ) {
				$redirect_customer = get_option( 'wc_rb_customer_login_page' );
				if ( ! empty( $redirect_customer ) ) {
					$redirect_customer = get_permalink( $redirect_customer );
				}
				if ( $redirect_customer != FALSE ) {
					$redirect_customer = esc_url( $redirect_customer );
					wp_redirect( $redirect_customer ); exit;
				}
			}//End of redirecting customer
		}
		add_action('wp_login','rb_redirect_user_after_login');
	endif;

	if ( ! function_exists( 'wc_job_extra_items_add' ) ) : 
		function wc_job_extra_items_add( $arguments, $post_id ) {
			if ( empty( $post_id ) ) {
				return;
			}
			if ( empty( $arguments ) ) {
				return;
			}
			if ( ! is_array( $arguments ) ) {
				return;
			}
			$WCRB_JOB_HISTORY_LOGS = WCRB_JOB_HISTORY_LOGS::getInstance();

			$date   	 = wp_date( 'Y-m-d H:i:s' ); 
			$label  	 = ( isset( $arguments['label'] ) && ! empty( $arguments['label'] ) ) ? $arguments['label'] : '';
			$detail 	 = ( isset( $arguments['details'] ) && ! empty( $arguments['details'] ) ) ? $arguments['details'] : '';
			$type 		 = ( isset( $arguments['type'] ) && ! empty( $arguments['type'] ) ) ? $arguments['type'] : 'unknown';
			$visibility  = ( isset( $arguments['visibility'] ) && ! empty( $arguments['visibility'] ) ) ? $arguments['visibility'] : 'public';
			$description = ( isset( $arguments['description'] ) && ! empty( $arguments['description'] ) ) ? $arguments['description'] : '';

			$add_array = array(
				'date'   => $date,
				'label'  => $label,
				'detail' => $detail,
				'description' => $description,
				'type' 		 => $type,
				'visibility' => $visibility,
			);

			$update_array = array();

			//Get current array
			$current_array = get_post_meta( $post_id, 'wc_job_extra_items', true );
			
			if ( ! empty( $current_array ) ) {
				$current_array = unserialize( $current_array );
				$update_array = $current_array;
			}

			$update_array[] = $add_array;
			$update_array = serialize( $update_array );

			update_post_meta( $post_id, 'wc_job_extra_items', $update_array );
			
			$customer_id = get_current_user_id();
			if ( $customer_id == 0 ) {
				$customer_id = get_post_meta( $post_id, "_customer", true );
			}
			$args = array(
				"job_id" 		=> $post_id, 
				"name" 			=> esc_html__( 'Extra fields & files', 'computer-repair-shop' ) . ' : ', 
				"type" 			=> 'private',
				"field" 		=> '_wc_job_extra_items',
				"change_detail" => $update_array,
				"user_id"		=> $customer_id
			);
			$WCRB_JOB_HISTORY_LOGS->wc_record_job_history( $args );
		}
	endif;

	if ( ! function_exists( 'wc_upload_file_ajax' ) ) :
		function wc_upload_file_ajax() {
			$values = array( 'message' => '', 'error' => '' );

			if ( isset( $_REQUEST['data_security'] ) && ! empty( $_REQUEST['data_security'] ) && wp_verify_nonce( $_REQUEST['data_security'], 'file_security' ) ) {
				//file_security
				if ( isset( $_FILES["file"] ) && $_FILES["file"]["error"] == 0 ) {
					$response = wc_upload_image_return_url( $_FILES["file"], 'reciepts' );
					$values['message'] = '';
					$values['error'] = $response['error'];

					$theReciepet = ( isset( $response['message'] ) ) ? $response['message'] : '';

					$supported_image = array( 'gif','jpg','jpeg','png','bmp' );
					$filetype 		 = wp_check_filetype( $theReciepet );
					$ext 			 = ( ! empty( $filetype['ext'] ) ) ? strtolower( $filetype['ext'] ) : '';

					$src_url = ( in_array( $ext, $supported_image ) ) ? $theReciepet : WC_COMPUTER_REPAIR_DIR_URL . '/assets/images/attachment.png';

					if ( ! empty( $theReciepet ) ) : 
						$message = '<a href="' . esc_url( $theReciepet ) . '" target="_blank"><img src="' . esc_url( $src_url ) . '" class="" /></a>';
						$message .= '<input type="hidden" name="repairBuddAttachment_file[]" value="' . esc_url( $theReciepet ) . '" />';

						$values['message'] = $message;
					endif;
				} else {
					$values['message'] = '';
					$values['error'] = esc_html__( "Something is wrong with your submission!", "computer-repair-shop" ) . 'ER1';
				}
			} else {
				$values['message'] = '';
				$values['error'] = esc_html__( "Something is wrong with your submission!", "computer-repair-shop" ) . 'ER2';
			}
			wp_send_json( $values );
			wp_die();
		}
		add_action( 'wp_ajax_wc_upload_file_ajax', 'wc_upload_file_ajax' );
		add_action( 'wp_ajax_nopriv_wc_upload_file_ajax', 'wc_upload_file_ajax' );
	endif;

	if ( ! function_exists( 'wc_upload_image_return_url' ) ) :
		function wc_upload_image_return_url( $image_submit, $directory ) {
			$values = array( 'error' => '', 'message' => '' );

			if ( ! function_exists( 'wp_handle_upload' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/file.php' );
			}

			if ( empty( $image_submit ) && $image_submit['error'] != 0 ) {
				$values['error'] = esc_html__( 'Nothing uploaded', 'computer-repair-shop' );
				return $values;
			}
			if ( ! is_array( $image_submit ) && ! isset( $image_submit[ "type" ] ) && ! isset( $image_submit[ "size" ] ) && ! isset( $image_submit[ "name" ] ) ) {
				return;
			}

			$directory 		= ( empty( $directory ) ) ? 'reciepts/' : $directory . '/';
			$directory_name = sanitize_text_field( $directory );
			$directory 		= ABSPATH . 'wp-content/uploads/repairbuddy_uploads/' . $directory;
			$directory 		= sanitize_text_field( $directory );

			if ( ! file_exists( $directory ) ) {
				mkdir( $directory, 0755, true );
			}
			$file = $directory . 'index.php';

			if( ! is_file( $file ) ){
				$contents = '<?php //Silence is golden';
				file_put_contents( $file, $contents );
			}
			$fileType = sanitize_text_field( $image_submit[ "type" ] );
			$fileSize = sanitize_text_field( $image_submit[ "size" ] );
			$fileName = sanitize_file_name( $image_submit["name"] );

			$allowedMimes = array(
				'jpg|jpeg|jpe' => 'image/jpeg',
				'gif'          => 'image/gif',
				'png'          => 'image/png',
				'pdf'		   => 'application/pdf',
			);
			$fileInfo = wp_check_filetype( basename( $fileName ), $allowedMimes );

			if ( ! $fileInfo['ext'] ) {
				// This file is not valid
				$values['error'] = esc_html__( 'Sorry this file type is not supported.', 'computer-repair-shop' );
				return $values;
				exit();
			}
			if ( $fileSize/1024 > "8192" ) {
				//Its good idea to restrict large files to be uploaded.
				$values['error'] = esc_html__( 'Acceptable filesize is 8 MB.', 'computer-repair-shop' );
				return $values;
				exit();
			} //FileSize Checking

			if ( $fileInfo['type'] ) {
				add_filter( 'upload_dir', 'wcrb_upload_dir' );

				$uploadInfo = wp_handle_upload( $image_submit, array(
					'test_form' 			   => false,
					'mimes'     			   => $allowedMimes,
					'unique_filename_callback' => true
				));

				if ( $uploadInfo && ! isset( $uploadInfo['error'] ) ) {
					//$values['message'] = get_home_url() . "/wp-content/repairbuddy_uploads/" . $directory_name . $uploadInfo['file'];
					$values['message'] = ( isset( $uploadInfo['url'] ) && ! empty( $uploadInfo['url'] ) ) ? $uploadInfo['url'] : '';
				} else {
					$values['error'] = esc_html__( 'Problem could not move file to destination.', 'computer-repair-shop' );
					return $values;
					exit;
				}
				remove_filter( 'upload_dir', 'wcrb_upload_dir' );
			} else {
				$values['error'] = esc_html__( 'Problem: Possible file upload attack.', 'computer-repair-shop' );
				return $values;
				exit;
			}
			return $values;
		}
	endif;

	if ( ! function_exists( 'wcrb_upload_dir' ) ) :
		function wcrb_upload_dir( $dirs ) {
			$dirs['subdir'] = '/repairbuddy_uploads/reciepts';
			$dirs['path'] = $dirs['basedir'] . '/repairbuddy_uploads/reciepts';
			$dirs['url'] = $dirs['baseurl'] . '/repairbuddy_uploads/reciepts';
			return $dirs;
		}
	endif;

	if ( ! function_exists( 'wc_add_extra_field_admin_side' ) ) :
		function wc_add_extra_field_admin_side() {
			$values = array(
				'message' => '',
				'success' => '',
			);

			if ( ! isset( $_POST['wcrb_nonce_adrepairbuddy_field'] ) || ! wp_verify_nonce( $_POST['wcrb_nonce_adrepairbuddy_field'], 'wcrb_nonce_adrepairbuddy' ) ) {
				$values['message'] = esc_html__( "Something is wrong with your submission!", "computer-repair-shop" );
			} else {
				$post_id 				= ( isset( $_POST['post_ID'] ) && ! empty( $_POST['post_ID'] ) ) ? sanitize_text_field( $_POST['post_ID'] ) : '';
				$extraFieldDateTime 	= ( isset( $_POST['extraFieldDateTime'] ) && ! empty( $_POST['extraFieldDateTime'] ) ) ? sanitize_text_field( $_POST['extraFieldDateTime'] ) : wp_date( 'Y-m-d H:i:s' );
				$extraFieldLabel 		= ( isset( $_POST['extraFieldLabel'] ) && ! empty( $_POST['extraFieldLabel'] ) ) ? sanitize_text_field( $_POST['extraFieldLabel'] ) : '';
				$extraFieldData 		= ( isset( $_POST['extraFieldData'] ) && ! empty( $_POST['extraFieldData'] ) ) ? sanitize_text_field( $_POST['extraFieldData'] ) : '';
				$extraFieldDescription 	= ( isset( $_POST['extraFieldDescription'] ) && ! empty( $_POST['extraFieldDescription'] ) ) ? sanitize_text_field( $_POST['extraFieldDescription'] ) : '';
				$extraFieldVisibility 	= ( isset( $_POST['extraFieldVisibility'] ) && ! empty( $_POST['extraFieldVisibility'] ) ) ? sanitize_text_field( $_POST['extraFieldVisibility'] ) : 'private';

				if ( empty( $post_id ) ) {
					$values['message'] = esc_html__( 'Unknown job', 'computer-repair-shop' );	
				} elseif ( empty( $extraFieldLabel ) ) {
					$values['message'] = esc_html__( 'Label is required', 'computer-repair-shop' );
				} elseif ( empty( $extraFieldData ) && ! isset( $_POST['repairBuddAttachment_file'] ) ) {
					$values['message'] = esc_html__( 'Either add something in data field or include a file.', 'computer-repair-shop' );
				} else {
					//Let's process form here.
					//$extraFieldData  

					if ( isset( $_POST["repairBuddAttachment_file"] ) && ! empty( $_POST["repairBuddAttachment_file"] ) ) {
						$attachments = $_POST["repairBuddAttachment_file"];
						foreach( $attachments as $attachment ) {
							$attachment = sanitize_url( $attachment );

							$arguments = array(
								'date'        => $extraFieldDateTime,
								'label'       => $extraFieldLabel,
								'details'     => $attachment,
								'visibility'  => $extraFieldVisibility,
								'type' 		  => 'file',
								'description' => $extraFieldDescription,
							);
							wc_job_extra_items_add( $arguments, $post_id );
						}
					} else {
						$arguments = array(
							'date'        => $extraFieldDateTime,
							'label'       => $extraFieldLabel,
							'details'     => $extraFieldData,
							'visibility'  => $extraFieldVisibility,
							'type' 		  => 'extra_field',
							'description' => $extraFieldDescription,
						);
						wc_job_extra_items_add( $arguments, $post_id );
					}
					$values['message'] = esc_html__( 'Extra field have been added', 'computer-repair-shop' );
					$values['success'] = 'YES';
				}
			}

			wp_send_json( $values );
			wp_die();
		}
		add_action( 'wp_ajax_wc_add_extra_field_admin_side', 'wc_add_extra_field_admin_side' );
	endif;

	if ( ! function_exists( 'wcrb_delete_job_est_extra_field' ) ) :
		function wcrb_delete_job_est_extra_field() {
			$values = array(
				'message' => '',
				'success' => '',
			);

			$values['message'] = esc_html__( 'Something is wrong with your submission!', 'computer-repair-shop' );
			$values['success'] = 'NO';

			if ( isset( $_POST['post_id'] ) && isset( $_POST['array_index'] ) ) {
				$post_id 	 = sanitize_text_field( $_POST['post_id'] );
				$array_index = sanitize_text_field( $_POST['array_index'] );

				if ( ! empty( $post_id ) && $array_index != '' ) {
					$wc_job_extra_items = get_post_meta( $post_id, 'wc_job_extra_items', true );
					$wc_job_extra_items = unserialize( $wc_job_extra_items );

					if ( ! empty( $wc_job_extra_items ) && is_array( $wc_job_extra_items ) ) {
						//Delete from array
						unset( $wc_job_extra_items[$array_index] );
						$wc_job_extra_items = array_values( $wc_job_extra_items );

						//Save back to post.
						$wc_job_extra_items = serialize( $wc_job_extra_items );
						update_post_meta( $post_id, 'wc_job_extra_items', $wc_job_extra_items );

						$values['message'] = esc_html__( 'Extra field have been removed', 'computer-repair-shop' );
						$values['success'] = 'YES';
					}
				}
			}
			wp_send_json( $values );
			wp_die();
		}
		add_action( 'wp_ajax_wcrb_delete_job_est_extra_field', 'wcrb_delete_job_est_extra_field' );
	endif;

	if ( ! function_exists( 'wcrb_return_customer_select_options' ) ) :
		function wcrb_return_customer_select_options( $selected_user, $name, $triggerId ) {
			$content = '';

			$name = ( empty( $name ) ) ? 'customer' : $name;
			$triggerId = ( empty( $triggerId ) ) ? 'customer' : $triggerId;
			
			$content .= wp_nonce_field( 'wcrb_nonce_adrepairbuddy', 'wcrb_nonce_adrepairbuddy_field', true, false );
			$content .= '<select id="' . esc_attr( $triggerId ) . '" name="' . esc_attr( $name ) . '" class="wcrb_select_customers form-control form-select">';
			$content .= '<option value="">' . esc_html__( 'Select Customer', 'computer-repair-shop' ) . '</option>';

			if ( ! empty( $selected_user ) ) :
				$user 			= get_user_by( 'id', $selected_user );
				$phone_number 	= get_user_meta( $selected_user, "billing_phone", true );
				$company 		= get_user_meta( $selected_user, "billing_company", true );
				
				$first_name		= empty($user->first_name)? "" : $user->first_name;
				$last_name 		= empty($user->last_name)? "" : $user->last_name;
				$theFullName 	= $first_name. ' ' .$last_name;
				$email 			= empty( $user->user_email ) ? "" : $user->user_email;

				$print_ 		= esc_attr( $selected_user ) . ' | ';
				$print_ 		.= ( ! empty( $theFullName ) ) ? esc_html( $theFullName ) : '';
				$print_ 		.= ( ! empty( $company ) ) ? ' | ' . esc_html( $company ) : '';
				$print_ 		.= ( ! empty( $phone_number ) ) ? ' ( ' . esc_html( $phone_number ) . ')' : '';
				$print_ 		.= ( ! empty( $email ) ) ? ' ( ' . esc_html( $email ) . ')' : '';

				$content .= '<option selected value="' . esc_attr( $selected_user ) . '"> ' . esc_html( $print_ ) . ' </option>';
			endif; 

			$content .= '</select>';

			return $content;
		}
	endif;

	if ( ! function_exists( 'wcrb_return_job_ids_options' ) ) {
		function wcrb_return_job_ids_options( $selected ) {
			$jobs_query = array(
				'posts_per_page' => -1,
				'post_type'      => 'rep_jobs',
				'post_status'    => 'publish',
				'orderby'		 => 'ID',
				'order'			 => 'DESC'
			);
			
			$wc_jobs_query = new WP_Query( $jobs_query );

			$content = '';
			if ( $wc_jobs_query->have_posts() ) {
				while( $wc_jobs_query->have_posts() ) {
					$wc_jobs_query->the_post();

					$job_id = $wc_jobs_query->post->ID;

					$selected_h = ( ! empty( $selected ) && $selected == $job_id ) ? ' selected' : '';

					$content .= '<option ' . esc_attr( $selected_h ) . ' value="' . esc_html( $job_id ) . '">' . esc_html( $job_id ) . ' | ' . esc_html( get_the_title( $job_id ) ) . '</option>';
				}
			} else {
				$content = '<option value="">' . esc_html__( 'Have not you added any job yet? Nothing to select here.', 'computer-repair-shop' ) . '</option>';
			}
			wp_reset_postdata();

			return $content;
		}
	}

	if ( ! function_exists( 'wcrb_return_customer_data_select2' ) ) :
		function wcrb_return_customer_data_select2() {
			$results = array();
			// we will pass post IDs and titles to this array
			if ( ! isset( $_GET['security'] ) || ! wp_verify_nonce( $_GET['security'], 'wcrb_nonce_adrepairbuddy' ) ) {
				$results[] = array( 
						'', 
						esc_html__( 'Something is wrong with your submission!', 'computer-repair-shop' ),
					);
			} else {
				if ( isset( $_GET[ 'q' ] ) && ! empty( $_GET[ 'q' ] ) ) {
					$search_term = sanitize_text_field( $_GET[ 'q' ] );

					$args = array (
						'role' => 'customer',
						'order' => 'ASC',
						'orderby' => 'display_name',
						'meta_query' => array(
							'relation' => 'OR',
							array(
								'key'     => 'first_name',
								'value'   => $search_term,
								'compare' => 'LIKE'
							),
							array(
								'key'     => 'last_name',
								'value'   => $search_term,
								'compare' => 'LIKE'
							),
							array(
								'key' => 'billing_company',
								'value' => $search_term,
								'compare' => 'LIKE'
							),
							array(
								'key' => 'billing_phone',
								'value' => $search_term,
								'compare' => 'LIKE'
							),
							array(
								'key' => 'billing_tax',
								'value' => $search_term,
								'compare' => 'LIKE'
							)
						)
					);
					$wp_user_query = new WP_User_Query($args);
					$customers = $wp_user_query->get_results();

					if ( empty( $customers ) ) {
						$args = array(
							'role' => 'customer',
							'order' => 'ASC',
							'orderby' => 'display_name',
							'search' => '*'.esc_attr( $search_term ).'*',
							'search_columns' => array( 'user_login', 'user_email', 'user_nicename', 'display_name' )
						);
						$wp_user_query = new WP_User_Query($args);
						$customers = $wp_user_query->get_results();
					}
					
					// Check for results
					if ( ! empty( $customers ) ) {
						foreach( $customers as $customer ) {
							$user 			= get_user_by( 'id', $customer->ID );
							$phone_number 	= get_user_meta( $customer->ID, "billing_phone", true );
							$company 		= get_user_meta( $customer->ID, "billing_company", true );
							
							$first_name		= empty($user->first_name)? "" : $user->first_name;
							$last_name 		= empty($user->last_name)? "" : $user->last_name;
							$theFullName 	= $first_name. ' ' .$last_name;
							$email 			= empty( $user->user_email ) ? "" : $user->user_email;

							$print_ 		= esc_attr( $customer->ID ) . ' | ';
							$print_ 		.= ( ! empty( $theFullName ) ) ? esc_html( $theFullName ) : '';
							$print_ 		.= ( ! empty( $company ) ) ? ' | ' . esc_html( $company ) : '';
							$print_ 		.= ( ! empty( $phone_number ) ) ? ' ( ' . esc_html( $phone_number ) . ')' : '';
							$print_ 		.= ( ! empty( $email ) ) ? ' ( ' . esc_html( $email ) . ')' : '';

							$results[] = array( 
								$customer->ID, 
								$print_,
							);
						}
					} else {
						$results[] = array( 
							'', 
							esc_html__( 'Nothing related to your search.', 'computer-repair-shop' ),
						);
					}
				} else {
					$results[] = array( 
						'', 
						esc_html__( 'Something is wrong with your submission!', 'computer-repair-shop' ),
					);
				}
			}
			echo json_encode( $results );
			die;
		}
		add_action( 'wp_ajax_wcrb_return_customer_data_select2', 'wcrb_return_customer_data_select2' );
	endif;

	if ( ! function_exists( 'wcrb_reload_customer_data' ) ) :
		function wcrb_reload_customer_data() {
			$values = array();

			if ( isset( $_POST['post_user_id'] ) && ! empty( $_POST['post_user_id'] ) ) {
				$user_value 	= sanitize_text_field( $_POST['post_user_id'] );

				$user 			= get_user_by( 'id', $user_value );
				$phone_number 	= get_user_meta( $user_value, "billing_phone", true );
				$company 		= get_user_meta( $user_value, "billing_company", true );
				$tax 			= get_user_meta( $user_value, "billing_tax", true );
				$first_name		= empty($user->first_name)? "" : $user->first_name;
				$last_name 		= empty($user->last_name)? "" : $user->last_name;
				$theFullName 	= $first_name. ' ' .$last_name;
				$email 			= empty( $user->user_email ) ? "" : $user->user_email;
		
				$customer_address 	= get_user_meta( $user_value, 'billing_address_1', true );
				$customer_city 		= get_user_meta( $user_value, 'billing_city', true );
				$customer_zip		= get_user_meta( $user_value, 'billing_postcode', true );
				$state		        = get_user_meta( $user_value, 'billing_state', true );
				$country		    = get_user_meta( $user_value, 'billing_country', true );
		
				$content = ( ! empty( $theFullName ) ) ? $theFullName . '<br>' : '';
				
				$eprow = ( ! empty( $email ) ) ? '<strong>E :</strong> ' . $email . ' ' : '';
				$eprow .= ( ! empty( $phone_number ) ) ? '<strong>P :</strong> ' . $phone_number . '' : '';
				$content .= ( ! empty( $eprow ) ) ? $eprow . '<br>' : '';
		
				$ctrow = ( ! empty( $company ) ) ? '<strong>' . esc_html__( 'Company', 'computer-repair-shop' ) . ' :</strong> ' . $company . ' ' : '';
				$ctrow .= ( ! empty( $tax ) ) ? '<strong> ' . esc_html__( 'Tax ID', 'computer-repair-shop' ) . ' :</strong> ' . $tax . '' : '';
				$content .= ( ! empty( $ctrow ) ) ? $ctrow . '<br>' : '';
		
				if(!empty($customer_zip) || !empty($customer_city) || !empty($customer_address)) {
					$content .= "<strong>".esc_html__("Address", "computer-repair-shop")." :</strong> ";
		
					$content .= ! empty( $customer_address ) ? $customer_address.", " : " ";
					$content .= ! empty( $customer_city ) ? " ".$customer_city.", " : " ";
					$content .= ! empty( $customer_zip ) ? $customer_zip.", " : " ";
					$content .= ! empty( $state ) ? $state.", " : " ";
					$content .= ! empty( $country ) ? $country : " ";
				}

				$values['message'] = $content;
				$values['success'] = 'YES';
			} else {
				$values['message'] = '';
				$values['success'] = 'NO';
			}

			wp_send_json( $values );
			wp_die();
		}
		add_action( 'wp_ajax_wcrb_reload_customer_data', 'wcrb_reload_customer_data' );
	endif;

	if ( ! function_exists( 'wcrb_return_booking_post_type' ) ) : 
		//Either to create jobs by rep_estimates or rep_jobs
		function wcrb_return_booking_post_type() {

			$wcrb_turn_booking_forms_to_jobs = get_option( 'wcrb_turn_booking_forms_to_jobs' );
			$return_type 					 = ( $wcrb_turn_booking_forms_to_jobs == 'on' ) ? 'rep_jobs' : 'rep_estimates';

			$wcrb_turn_estimates_on = get_option( 'wcrb_turn_estimates_on' );
			$return_type 			= ( $wcrb_turn_estimates_on == 'on' ) ? 'rep_jobs' : $return_type;
			
			return $return_type;
		}
	endif;

	/**
	 * Get label for various fields
	 * @param string $key
	 * @return string
	 * 
	 * Available keys: delivery_date, pickup_date, nextservice_date, casenumber
	 * $capital: all, first, none
	 */
	if ( ! function_exists( 'wcrb_get_label' ) ) :
		function wcrb_get_label( $key, $capital = '' ) {
			$labels = [
				'delivery_date' 	=> esc_html__( 'Delivery date', 'computer-repair-shop' ),
				'pickup_date' 		=> esc_html__( 'Pickup date', 'computer-repair-shop' ),
				'nextservice_date' 	=> esc_html__( 'Next service date', 'computer-repair-shop' ),
				'casenumber' 		=> esc_html__( 'Case number', 'computer-repair-shop' ),
			];

			$option_value = get_option( "wcrb_{$key}_label" );
			$option_value = !empty($option_value) ? $option_value : $labels[$key];

			if ( $capital == 'all' ) {
				$option_value = ucwords( $option_value );
			} elseif ( $capital == 'first' ) {
				$option_value = ucfirst( $option_value );
			} elseif ( $capital == 'none' ) {
				$option_value = strtolower( $option_value );
			}
			return $option_value;
		}
	endif;

	if ( ! function_exists( 'wcrb_job_priorities' ) ) :
		function wcrb_job_priorities( $arg = '' ) {
			$priorities = array(
				'low'       => esc_html__( 'Low', 'computer-repair-shop' ),
				'normal'    => esc_html__( 'Normal', 'computer-repair-shop' ),
				'medium'    => esc_html__( 'Medium', 'computer-repair-shop' ),
				'high'      => esc_html__( 'High', 'computer-repair-shop' ),
				'urgent'    => esc_html__( 'Urgent', 'computer-repair-shop' ),
			);

			if ( empty( $arg ) || $arg == 'array' ) {
				return $priorities;
			} else {
				return isset( $priorities[$arg] ) ? $priorities[$arg] : esc_html__( 'Normal', 'computer-repair-shop' );
			}
		}
	endif;

	if ( ! function_exists( 'wcrb_return_priority_styles' ) ) :
		function wcrb_return_priority_styles( $arg ) {
			$priority_styles = array(
				'low' => array(
					'color' => 'success',
					'icon' => 'bi-arrow-down-circle',
					'class' => 'btn-success'
				),
				'normal' => array(
					'color' => 'secondary',
					'icon' => 'bi-check-circle',
					'class' => 'btn-primary'
				),
				'medium' => array(
					'color' => 'info',
					'icon' => 'bi-arrow-right-circle',
					'class' => 'btn-info'
				),
				'high' => array(
					'color' => 'warning',
					'icon' => 'bi-arrow-up-circle',
					'class' => 'btn-warning'
				),
				'urgent' => array(
					'color' => 'danger',
					'icon' => 'bi-exclamation-triangle',
					'class' => 'btn-danger'
				),
			);

			if ( empty( $arg ) || $arg == 'array' ) {
				return $priority_styles;
			} else {
				return isset( $priorities[$arg] ) ? $priorities[$arg] : $priorities['normal'];
			}
		}
	endif;

	if ( ! function_exists( 'wcrb_job_priority_options' ) ) :
		function wcrb_job_priority_options( $return, $job_id, $default ) {
			$selected = '';
			if ( ! empty( $job_id ) ) {
				$selected = get_post_meta( $job_id, '_wc_job_priority', true );
			}
			$selected = ( empty( $selected ) && ! empty( $default ) ) ? $default : $selected;

			$return = ( empty( $return ) ) ? 'select' : $return;
			$priorities = wcrb_job_priorities( 'array' );
			
			// Priority colors and icons
			$priority_styles = wcrb_return_priority_styles( 'array' );

			$content = '';
			$nonce = wp_create_nonce( 'wcrb_update_priority_nonce' );
			
			if ( $return == 'select' || $return == 'select_no_label' ) {
				// Standard select dropdown
				
				$content .= ( $return == 'select' ) ? '<label><strong>'. esc_html__( 'Priority', 'computer-repair-shop' ) .'</strong>' : '';
				
				$content .= '<select name="wc_job_priority" class="form-select wc_job_priority_select form-control" data-type="update_job_priority" data-security="'. esc_attr( $nonce ) .'" recordid="' . esc_attr( $job_id ) . '" data-value="selected_option">';
				$content .= '<option value="">' . esc_html__( 'Select Priority', 'computer-repair-shop' ) . '</option>';
				foreach ( $priorities as $key => $value ) {
					$selected_attr = ( ! empty( $selected ) && $selected == $key ) ? ' selected' : '';
					$content .= '<option ' . esc_attr( $selected_attr ) . ' value="' . esc_attr( $key ) . '">' . esc_html( $value ) . '</option>';
				}
				$content .= '</select>';
				$content .= ( $return == 'select' ) ? '</label>' : '';
			} else {
				$current_user = wp_get_current_user();
			    $current_user = $current_user->roles[0] ?? 'guest';

				// Enhanced Bootstrap dropdown
				$current_priority = ! empty( $selected ) ? $selected : '';
				$current_label = ! empty( $current_priority ) && isset( $priorities[$current_priority] ) ? $priorities[$current_priority] : esc_html__( 'Select Priority', 'computer-repair-shop' );
				$current_style = ! empty( $current_priority ) && isset( $priority_styles[$current_priority] ) ? $priority_styles[$current_priority] : array( 'color' => 'secondary', 'icon' => 'bi-circle', 'class' => 'btn-outline-secondary' );
				
				if ( $current_user == 'technician' || $current_user == 'store_manager' || $current_user == 'administrator' ) :
					$content .= '<div class="dropdown">';
					$content .= '<button class="btn btn-sm dropdown-toggle d-flex align-items-center ' . esc_attr( $current_style['class'] ) . '" type="button" data-bs-toggle="dropdown" aria-expanded="false">';
					$content .= '<i class="' . esc_attr( $current_style['icon'] ) . ' me-2"></i>';
					$content .= '<span>' . esc_html( $current_label ) . '</span>';
					$content .= '</button>';
					
					$content .= '<ul class="dropdown-menu shadow-sm">';
					
					foreach ( $priorities as $key => $value ) {
						$is_active = ( $current_priority == $key ) ? ' active' : '';
						$style = $priority_styles[$key];
						
						$content .= '<li>';
						$content .= '<a class="dropdown-item d-flex justify-content-between align-items-center py-2' . esc_attr( $is_active ) . '" 
									recordid="' . esc_attr( $job_id ) . '" 
									data-type="update_job_priority" 
									data-value="' . esc_attr( $key ) . '" 
									data-security="' . esc_attr( $nonce ) . '" 
									href="#">';
						$content .= '<div class="d-flex align-items-center">';
						$content .= '<i class="' . esc_attr( $style['icon'] ) . ' text-' . esc_attr( $style['color'] ) . ' me-2"></i>';
						$content .= '<span>' . esc_html( $value ) . '</span>';
						$content .= '</div>';
						if ( $is_active ) {
							$content .= '<i class="bi-check2 text-primary ms-2"></i>';
						}
						$content .= '</a>';
						$content .= '</li>';
					}
					
					$content .= '</ul>';
					$content .= '</div>';
				else: 
					$content .= '<div class="btn btn-sm align-items-center ' . esc_attr( $current_style['class'] ) . '">';
					$content .= '<i class="' . esc_attr( $current_style['icon'] ) . ' me-2"></i>';
					$content .= '<span>' . esc_html( $current_label ) . '</span>';
					$content .= '</div>';
				endif;

			}
			
			return $content;
		}
	endif;

	if ( ! function_exists( 'wcrb_dropdown_users_multiple_roles' ) ) :
		function wcrb_dropdown_users_multiple_roles( $args = array() ) {
			$defaults = array(
				'role__in' => array(),
				'show_option_all' => '',
				'name' => 'user',
				'selected' => 0,
				'show_roles' => true,
				'multiple' => false,
				'placeholder' => '',
				'class' => 'form-select'
			);

			$args = wp_parse_args( $args, $defaults );

			// Define the role order you want
			$role_order = array( 'technician', 'store_manager', 'administrator' );
			
			// Filter roles to only include those requested and in the correct order
			$roles = array_intersect( $role_order, $args['role__in'] );
			
			// Build select attributes
			$select_attrs = array(
				'name' => $args['name'] . ($args['multiple'] ? '[]' : ''),
				'id' => $args['name'],
				'class' => $args['class']
			);
			
			if ($args['multiple']) {
				$select_attrs['multiple'] = 'multiple';
			}
			
			if ($args['placeholder']) {
				$select_attrs['data-placeholder'] = $args['placeholder'];
			}
			
			// Build select opening tag
			$output = '<select';
			foreach ($select_attrs as $attr => $value) {
				$output .= ' ' . $attr . '="' . esc_attr($value) . '"';
			}
			$output .= '>';
			
			// Show "All" option (only for single select)
			if ( ! empty( $args['show_option_all'] ) && ! $args['multiple'] ) {
				$output .= '<option value="">' . esc_html( $args['show_option_all'] ) . '</option>';
			}

			// For multiple select with placeholder, add empty option
			if ($args['multiple'] && $args['placeholder']) {
				$output .= '<option value=""></option>';
			}

			// Get WordPress roles for display names
			$wp_roles = wp_roles();
			
			foreach ( $roles as $role ) {
				// Get users for this specific role
				$users = get_users( array(
					'role' 		=> $role,
					'orderby' 	=> 'display_name',
					'order' 	=> 'ASC'
				) );

				if ( ! empty( $users ) ) {
					// Add role label as an option group or disabled option
					$role_name = isset( $wp_roles->roles[$role] ) ? $wp_roles->roles[$role]['name'] : $role;
					if ( $args['show_roles'] ) {
						$output .= '<optgroup label="' . esc_attr( $role_name ) . '">';
					}
					
					foreach ( $users as $user ) {
						$selected = '';
						
						// Handle both single value and array of selected values
						if ($args['multiple'] && is_array($args['selected'])) {
							$selected = in_array($user->ID, $args['selected']) ? 'selected="selected"' : '';
						} else {
							$selected = selected( $user->ID, $args['selected'], false );
						}
						
						$output .= '<option value="' . esc_attr( $user->ID ) . '" ' . $selected . '>';
						$output .= esc_html( $user->display_name );
						if ( ! $args['show_roles'] ) {
							$output .= ' (' . esc_html( $role_name ) . ')';
						}
						$output .= '</option>';
					}
					
					if ( $args['show_roles'] ) {
						$output .= '</optgroup>';
					}
				}
			}

			$output .= '</select>';

			return $output;
		}
	endif;

	if ( ! function_exists( 'wcrb_update_job_status_consideration' ) ) :
		function wcrb_update_job_status_consideration() {
			$message = '';
			$success = 'NO';

			$form_type = ( isset( $_POST['form_type'] ) ) ? sanitize_text_field( $_POST['form_type'] ) : '';
					
			if ( 
				isset( $_POST['wcrb_nonce_delivered_status_field'] ) 
				&& wp_verify_nonce( $_POST['wcrb_nonce_delivered_status_field'], 'wcrb_nonce_delivered_status' ) 
				&& $form_type == 'wcrb_update_job_status_consideration' ) {

				$submit_arr = array( 
								'wcrb_job_status_cancelled', 
								'wcrb_job_status_delivered' 
							);

				foreach( $submit_arr as $field ) {
					$_field_value = ( isset( $_POST[$field] ) && ! empty( $_POST[$field] ) ) ? sanitize_text_field( $_POST[$field] ) : '';
					update_option( $field, $_field_value );
				}
				$message = esc_html__( 'Settings updated!', 'computer-repair-shop' );
			} else {
				$message = esc_html__( 'Invalid Form', 'computer-repair-shop' );	
			}

			$values['message'] = $message;
			$values['success'] = $success;

			wp_send_json( $values );
			wp_die();
		}
		add_action( 'wp_ajax_wcrb_update_job_status_consideration', 'wcrb_update_job_status_consideration' );
	endif;

	if ( ! function_exists( 'repairbuddy_booking_captcha_field' ) ) :
		/**
		 * Simple math captcha placeholder for booking form
		 * Usage : repairbuddy_booking_captcha_field();
		 */
		function repairbuddy_booking_captcha_field() {
			// Just output a placeholder div
			$output = '<div id="repairbuddy-captcha-placeholder" class="repairbuddy-captcha-field">';
			$output .= '<div class="captcha-loader">'. esc_html__( 'Loading security check...', 'computer-repair-shop' ) .'</div>';
			$output .= '</div>';
			
			return $output;
		}
	endif;

	if ( ! function_exists( 'repairbuddy_generate_captcha_ajax' ) ) :
		/**
		 * Generate captcha via AJAX
		 */
		function repairbuddy_generate_captcha_ajax() {
			$num1 = rand(1, 9);
			$num2 = rand(1, 9);
			$answer = $num1 + $num2;

			// Generate unique key for each form
			$key = wp_generate_uuid4();
			set_transient('repairbuddy_captcha_' . $key, $answer, 5 * MINUTE_IN_SECONDS);

			$output = '<div class="repairbuddy-captcha-content">';
			$output .= '<label for="repairbuddy_captcha_answer">'. esc_html__( 'Solve this to verify you are human', 'computer-repair-shop' ) .':</label>';
			$output .= '<strong>' . esc_html($num1) . ' + ' . esc_html($num2) . ' = ?</strong><br>';
			$output .= '<input type="text" name="repairbuddy_captcha_answer" id="repairbuddy_captcha_answer" required style="width:80px;background-color:#f1f1f1;padding:5px;border:1px solid #ddd;"> ';
			$output .= '<input type="hidden" name="repairbuddy_captcha_key" value="' . esc_attr($key) . '">';
			$output .= '<button type="button" class="refresh-captcha" style="margin-left:10px;padding:5px 10px;" title="'. esc_attr__('Refresh captcha', 'computer-repair-shop') .'">↻</button>';
			$output .= '</div>';

			wp_send_json_success(array(
				'html' => $output,
				'key' => $key
			));
		}
	endif;

	if ( ! function_exists( 'repairbuddy_verify_captcha_on_submit' ) ) :
		/**
		 * Verify captcha answer on form submission
		 * Usage: Call this function on form submission handler
		 * @return true|FALSE
		 */ 
		function repairbuddy_verify_captcha_on_submit() {
			if ( empty( $_POST['repairbuddy_captcha_answer'] ) || empty( $_POST['repairbuddy_captcha_key'] ) ) {
				return FALSE;
			}

			$key           = sanitize_text_field($_POST['repairbuddy_captcha_key']);
			$saved_answer = get_transient('repairbuddy_captcha_' . $key);
			
			// Clean up old transient
			delete_transient('repairbuddy_captcha_' . $key);

			if ( ! $saved_answer || intval( $_POST['repairbuddy_captcha_answer'] ) !== intval( $saved_answer ) ) {
				return FALSE;
			}
			return true;
		}
	endif;

	// AJAX handlers
	add_action('wp_ajax_repairbuddy_get_captcha', 'repairbuddy_generate_captcha_ajax');
	add_action('wp_ajax_nopriv_repairbuddy_get_captcha', 'repairbuddy_generate_captcha_ajax');

	// Optional: Add refresh captcha endpoint
	add_action('wp_ajax_repairbuddy_refresh_captcha', 'repairbuddy_generate_captcha_ajax');
	add_action('wp_ajax_nopriv_repairbuddy_refresh_captcha', 'repairbuddy_generate_captcha_ajax');

	if ( ! function_exists( 'wcrb_current_user_role' ) ) :
		function wcrb_current_user_role() {
			$current_user = wp_get_current_user();
			$roles = ( array ) $current_user->roles;
			return ! empty( $roles ) ? $roles[0] : '';
		}
	endif;