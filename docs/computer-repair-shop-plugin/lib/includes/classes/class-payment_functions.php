<?php
/**
 * The file contains the functions related to Payments
 *
 * From handling the default payment methods
 *
 * This file contains important functions for managing currencies in repairBuddy
 * if you are developer you shouldn't edit this file and create your own plugin
 * maintain compatibility. We try to do this as little as possible, but it does
 * in case you want to include new functions or modify existing functions
 * to override a function make sure you do that correctly.
 *
 * @package computer-repair-shop
 * @version 3.7947
 */

defined( 'ABSPATH' ) || exit;

class WCRB_PAYMENT_METHODS {
    
    function __construct() {
        add_action( 'wc_rb_settings_tab_menu_item', array( $this, 'add_payment_status_tab_in_settings_menu' ), 10, 2 );
        add_action( 'wc_rb_settings_tab_body', array( $this, 'add_payment_status_tab_in_settings_body' ), 10, 2 );
		add_action( 'wc_rb_jobs_action_payments', array( $this, 'return_payment_taking_link' ), 10, 2 );

		add_action( 'wp_ajax_wc_post_payment_status', array( $this, 'wc_post_payment_status' ) );
		add_action( 'wp_ajax_wc_rb_update_payment_methods', array( $this, 'wc_rb_update_payment_methods' ) );
		add_action( 'wp_ajax_wc_rb_add_payment_into_job', array( $this, 'wc_rb_add_payment_into_job' ) );
		add_action( 'wp_ajax_wc_rb_generate_woocommerce_order', array( $this, 'wc_rb_generate_woocommerce_order' ) );
		add_action( 'wp_ajax_wc_add_joblist_payment_form_output', array( $this, 'wc_add_joblist_payment_form_output' ) );

		$this->load_jobs_list_admin_footer();
    }

	function load_jobs_list_admin_footer() {
		global $pagenow;

		if ( isset( $_GET['post_type'] ) && 'edit.php' === $pagenow && $_GET['post_type'] == 'rep_jobs' ) {
			add_filter( 'admin_footer', array( $this, 'wc_joblist_add_payment_form' ) );
		}
	}

	function wc_add_joblist_payment_form_output() {
		$customerName = '';

		$dashboard = WCRB_MYACCOUNT_DASHBOARD::getInstance();

		if ( isset( $_POST['recordID'] ) && ! empty ( $_POST['recordID'] ) && $dashboard->have_job_access( absint( $_POST["recordID"] ) ) ) :
			$_jl_payment_id = sanitize_text_field( $_POST['recordID'] );

			$_case_number = get_the_title( $_jl_payment_id );
			$customer 	  = get_post_meta( $_jl_payment_id, '_customer', true );
			
			if ( ! empty( $customer ) ) {
				$user 		 = get_user_by( 'id', $customer );
				
				$first_name	  = empty( $user->first_name ) ? "" : $user->first_name;
				$last_name 	  = empty( $user->last_name ) ? "" : $user->last_name;
				$customerName =  ' { ' . esc_html__( 'Customer', 'computer-repair-shop' ) . ': ' . $first_name. ' ' .$last_name . ' } ';
			}

			$wc_order_status = get_post_meta( $_jl_payment_id, '_wc_order_status', true );
			$wc_order_status = ( empty( $wc_order_status ) ) ? 'neworder' : $wc_order_status;

			$receiving      = $this->wc_return_receivings_total( $_jl_payment_id );
			$theGrandTotal  = wc_order_grand_total( $_jl_payment_id, 'grand_total' );
			$theBalance     = $theGrandTotal-$receiving;

			if ( $theBalance > 0.5 ) :

				$output = '<div class="set_addpayment_joblist_message"></div>
				<p>{ ' . wcrb_get_label( 'casenumber', 'first' ) . ' - ' . esc_html( $_case_number ) . ' }' . $customerName . '</p>
				<div class="wcrb_the_payment_info">';
				$output .= '<table class="grey-bg wc_table wcrb_payment_table table">';

				$output .= '<tr><td>' . esc_html__( 'Payable', 'computer-repair-shop' ) . ' <span class="wcrb_amount_payable">' . wc_cr_currency_format( $theBalance ) . '</span>
				<input class="wcrb_amount_payable_value" type="hidden" value="' . esc_html( $theBalance ) . '" /></td><td>&nbsp;</td></tr>';
				$output .= '<tr><td class="orange-bg">' . esc_html__( 'Paying', 'computer-repair-shop' ) . ' <span class="wcrb_amount_paying">0.00</span></td>';
				$output .= '<td class="blue-bg">' . esc_html__( 'Balance', 'computer-repair-shop' ) . ' <span class="wcrb_amount_balance">' . wc_cr_currency_format( $theBalance ) . '</span></td></tr>';

				$output .= '</table>';
				$output .= '</div><!-- the_payment_info /-->';

				$output .= '<div class="wcrb_the_payment_note">';
				$output .= '<label>' . esc_html__( 'Notes', 'computer-repair-shop' );
				$output .= '<textarea name="wcrb_payment_note" rows="1" class="form-control"></textarea><label>';

				$output .= '</div><!-- the_payment_note /-->';

				$output .= '<div class="wcrb_the_payment_date">';

				$output .= '<table class="form-table">';
				$output .= '<tr>';

				$output .= '<td class="alignright"><label for="wcrb_payment_datetime" class="text-right">' . esc_html__( 'Payment Date', 'computer-repair-shop' ) . '</label></td>';
				$output .= '<td><input type="datetime-local" id="wcrb_payment_datetime" class="form-control" name="wcrb_payment_datetime" value="' . esc_html( wp_date( 'Y-m-d H:i:s' ) ) . '" /></td>';

				$output .= '</tr>';

				$output .= '<tr>';

				$output .= '<td class="alignright"><label for="wcrb_transaction_id" class="text-right">' . esc_html__( 'Transaction ID', 'computer-repair-shop' ) . '</label></td>';
				$output .= '<td><input type="text" id="wcrb_transaction_id" class="form-control" name="wcrb_transaction_id" value="" /></td>';

				$output .= '</tr>';
				$output .= '</table>';
				$output .= '</div><!-- the_payment_date /-->';

				$output .= '<div class="wcrb_the_payment_payment">';
				$output .= '<table class="form-table table">';
				$output .= '<tr>';
				$output .= '<td>';
				$output .= '<label>' . esc_html__( 'Payment Status', 'computer-repair-shop' ) . '
									<select name="wcRB_payment_status" required class="form-select form-control"><option value="">' . esc_html__( 'Select Status', 'computer-repair-shop' ) . '</option>';
						$output .= $this->wc_generate_payment_status_options( '' );
						$output .= '</select>
										</label>';
				$output .= '</td>';
				$output .= '<td>';
				$output .= '<label>' . esc_html__( 'Payment Method', 'computer-repair-shop' ) . '
							<select name="wcRB_payment_method" required class="form-control form-select">
							<option value="">' . esc_html__( 'Select Method', 'computer-repair-shop' ) . '</option>
							' . $this->wc_generate_payment_method_options( 'NO', 'NO', '' ) . '
							</select>
							</label>';
				$output .= '</td>';
				$output .= '</tr>';

				$output .= '<tr>';
				$output .= '<td><label>' . esc_html__( 'Job Status', 'computer-repair-shop' ) . '
							<select name="wcRB_after_jobstatus" required class="form-control form-select">
							<option value="">' . esc_html__( 'Change job status', 'computer-repair-shop' ) . '</option>
							' . wc_generate_status_options( $wc_order_status ) . '
							</select>
							</label></td>';
				$output .= '<td>
							<label for="wcRb_payment_amount">' . esc_html__( 'Payment Amount', 'computer-repair-shop' ) . '
							<input type="number" class="form-control" step="any" required id="wcRb_payment_amount" name="wcRb_payment_amount" value="0.00"></label></td>';
				$output .= '</tr>';
				$output .= '</table>';
				$output .= '</div><!-- the_payment_date /-->';
				
				$output .= wp_nonce_field( 'wcrb_nonce_add_payment', 'wcrb_nonce_add_payment_field', true, false );
				
				$output .= '<input type="hidden" name="wcrb_job_id" value="' . esc_html( $_jl_payment_id ) . '" />';
				$output .= '<table class="form-table widthfifty">
								<tr><td>
									<button class="button button-primary expanded btn btn-primary btn-sm" type="submit" value="Submit">' . esc_html__( 'Add Payment', 'computer-repair-shop' ) . '</button>
								</td><td>
								</fieldset><small>' . esc_html__( '(*) fields are required', 'computer-repair-shop' ) . '</small>
								</td></tr>
							</table>';

				$allowedHTML = wc_return_allowed_tags(); 
				$message = wp_kses( $output, $allowedHTML );
			else:
				$message = esc_html__( 'Not payable balance', 'computer-repair-shop' );
			endif;
		else: 
			$message = esc_html__( 'Could not find the job id', 'computer-repair-shop' );
		endif;
					
		$values['message'] = $message;
		$values['success'] = "YES";

		wp_send_json( $values );
		wp_die();
	}

	function wc_joblist_add_payment_form() {
		$allowedHTML = wc_return_allowed_tags(); 
		$content = wc_cr_add_js_fields_for_currency_formating();
		echo wp_kses( $content, $allowedHTML );
	?>
		<!-- Modal for Post Entry /-->
		<div class="reveal" id="addjoblistpaymentreveal" data-reveal>
		<h2><?php echo esc_html__( 'Make a payment', 'computer-repair-shop' ); ?></h2>
			<form class="" method="post" name="wcrb_jl_form_submit_payment" data-success-class=".set_addpayment_joblist_message">
			<div id="replacementpart_joblist_formfields">
				<!-- Replacementpart starts /-->
				<!-- Replacementpart Ends /-->
			</div></form>
			<button class="close-button" data-close aria-label="Close modal" type="button">
				<span aria-hidden="true">&times;</span>
			</button>
		</div>
		<?php
	}

	function return_payment_taking_link( $job_id ) {
		if ( empty( $job_id ) ) {
			return;
		}

		$the_html = '<div class="two-equal-buttons">';
		
		$the_html .= '<a class="button button-primary button-small" data-open="wc_rb_modal_takePayment">' . esc_html__( 'Take On-Site Payment', 'computer-repair-shop' ) . '</a>';
		add_filter( 'admin_footer', array( $this, 'wc_add_receive_payment_modal'), 10 );

		//For online Payment button first check the system.
		if ( wcrb_is_method_woocommerce() == TRUE ) {
			$the_html .= '<br><a class="button expanded button-primary button-small" style="width:100%;margin-top:10px;" recordid="' . $job_id . '" target="wc_rb_generate_woo_order">' . esc_html__( 'Generate Online Payment Link', 'computer-repair-shop' ) . '</a>';

			add_action( 'wp_ajax_wc_rb_generate_woocommerce_order', array( $this, 'wc_rb_generate_woocommerce_order' ) );
		}

		$the_html .= '</div>';

		return $the_html;
	}

	function wc_add_receive_payment_modal() {
		$theJobId = '';
		if ( isset( $_GET['post'] ) && ! empty( $_GET['post'] )  ) {
			$theJobId = sanitize_text_field( $_GET['post'] );
		}
		if ( empty( $theJobId ) ) {
			return;
		}

		$output = '<div class="reveal" id="wc_rb_modal_takePayment" data-reveal>';
		$output .= '<h2>' . esc_html__( 'Make a payment', 'computer-repair-shop' ) . '</h2>';

		$output .= '<form action="" method="post" name="wcrb_form_submit_payment">';

		$output .= '<div class="wcrb_the_payment_info"><div class="wcrb_payment_status_msg"></div>';
		$output .= '<table class="grey-bg wc_table wcrb_payment_table">';

		$output .= '<tr><td class="grey-bg">' . esc_html__( 'Payable', 'computer-repair-shop' ) . ' <span class="wcrb_amount_payable">0.00</span><input class="wcrb_amount_payable_value" type="hidden" value="" /></td><td>&nbsp;</td></tr>';
		$output .= '<tr><td class="orange-bg">' . esc_html__( 'Paying', 'computer-repair-shop' ) . ' <span class="wcrb_amount_paying">0.00</span></td>';
		$output .= '<td class="blue-bg">' . esc_html__( 'Balance', 'computer-repair-shop' ) . ' <span class="wcrb_amount_balance">0.00</span></td></tr>';

		$output .= '</table>';
		$output .= '</div><!-- the_payment_info /-->';

		$output .= '<div class="wcrb_the_payment_note">';
		
		$output .= '<label>' . esc_html__( 'Notes', 'computer-repair-shop' );
		$output .= '<textarea name="wcrb_payment_note" rows="1"></textarea><label>';

		$output .= '</div><!-- the_payment_note /-->';

		$output .= '<div class="wcrb_the_payment_date">';

		$output .= '<div class="grid-x grid-padding-x">
					<div class="small-3 cell">
						<label for="wcrb_payment_datetime" class="text-right">' . esc_html__( 'Payment Date', 'computer-repair-shop' ) . '</label>
					</div>
					<div class="small-9 cell">
						<input type="datetime-local" id="wcrb_payment_datetime" name="wcrb_payment_datetime" value="' . esc_html( wp_date( 'Y-m-d H:i:s' ) ) . '" />
					</div></div>';

		$output .= '<br><div class="grid-x grid-padding-x">
					<div class="small-3 cell">
						<label for="wcrb_transaction_id" class="text-right">' . esc_html__( 'Transaction ID', 'computer-repair-shop' ) . '</label>
					</div>
					<div class="small-9 cell">
						<input type="text" id="wcrb_transaction_id" name="wcrb_transaction_id" value="" />
					</div></div>';

		$output .= '</div><!-- the_payment_date /-->';

		$output .= '<div class="wcrb_the_payment_payment">';

		$output .= '<div class="grid-container">';
		
			$output .= '<div class="grid-x grid-padding-x">';
				$output .= '<div class="medium-6 cell">
							<label>' . esc_html__( 'Payment Status', 'computer-repair-shop' ) . '
							<select name="wcRB_payment_status" class="form-control form-select" required><option value="">' . esc_html__( 'Select Status', 'computer-repair-shop' ) . '</option>';
							
				if ( empty ( $wc_payment_status ) ) {
					$wc_payment_status = '';
				}
				$output .= $this->wc_generate_payment_status_options( $wc_payment_status );
				$output .= '</select>
								</label>
							</div>';

				$output .= '<div class="medium-6 cell">
								<label>' . esc_html__( 'Payment Method', 'computer-repair-shop' ) . '
								<select name="wcRB_payment_method" class="form-control form-select" required>
								<option value="">' . esc_html__( 'Select Method', 'computer-repair-shop' ) . '</option>
								' . $this->wc_generate_payment_method_options( 'NO', 'NO', '' ) . '
								</select>
								</label>
							</div>';
			$output .= '</div>';
		
			$output .= '<div class="grid-x grid-padding-x">';
				$output .= '<div class="medium-offset-3 medium-3 small-3 cell">';
				$output .= '<label for="wcRb_payment_amount" class="text-right">' . esc_html__( 'Amount', 'computer-repair-shop' ) . '</label>';
				$output .= '</div>';

				$output .= '<div class="medium-6 small-6 cell">';
				$output .= '<input type="number" step="any" required id="wcRb_payment_amount" name="wcRb_payment_amount" value="0.00">';
				$output .= '</div>';
			$output .= '</div>';

		$output .= '</div>';

		$output .= '</div><!-- the_payment_date /-->';
		
		$output .= wp_nonce_field( 'wcrb_nonce_add_payment', 'wcrb_nonce_add_payment_field', true, false );
		
		$output .= '<input type="hidden" name="wcrb_job_id" value="' . $theJobId . '" />';
		$output .= '<div class="grid-x grid-margin-x">
						<fieldset class="cell medium-6">
							<button class="button button-primary expanded" type="submit" value="Submit">' . esc_html__( 'Add Payment', 'computer-repair-shop' ) . '</button>
						</fieldset><small>' . esc_html__( '(*) fields are required', 'computer-repair-shop' ) . '</small>
					</div>';
		
		$output .= '</form>';

		$output .= '<button class="close-button" data-close type="button">
		  <span aria-hidden="true">&times;</span>
		</button>
	  </div>';

	  $allowedHTML = wc_return_allowed_tags(); 
	  echo wp_kses( $output, $allowedHTML );
	}

    function add_payment_status_tab_in_settings_menu() {
        $active = '';
        if ( isset( $_GET['update_payment_status'] ) && ! empty ( $_GET['update_payment_status'] ) ) {
            $active = ' is-active';
        }
        $menu_output = '<li class="tabs-title' . esc_attr($active) . '" role="presentation">';
        $menu_output .= '<a href="#wc_rb_payment_status" role="tab" aria-controls="wc_rb_payment_status" aria-selected="true" id="wc_rb_payment_status-label">';
        $menu_output .= '<h2>' . esc_html__( 'Payment Status', 'computer-repair-shop' ) . '</h2>';
        $menu_output .=	'</a>';
        $menu_output .= '</li>';

        echo wp_kses_post( $menu_output );
    }
	
	function add_payment_status_tab_in_settings_body() {
        global $wpdb;

        $active = '';
        if ( isset( $_GET['update_payment_status'] ) && ! empty ( $_GET['update_payment_status'] ) ) {
            $active = ' is-active';
        }

		$setting_body = '<div class="tabs-panel team-wrap' . esc_attr($active) . '" 
        id="wc_rb_payment_status" 
        role="tabpanel" 
        aria-hidden="true" 
        aria-labelledby="wc_rb_payment_status-label">';

        $setting_body .= '<p class="help-text">
                                <a class="button button-primary button-small" data-open="paymentStatusFormReveal">'.
                                     esc_html__("Add New Payment Status", "computer-repair-shop")
                                .'</a></p>';
        add_filter( 'admin_footer', array( $this, 'wc_add_payment_status_form' ) );

        $setting_body .= '<div id="payment_status_wrapper">
        <table id="paymentStatus_poststuff" class="wp-list-table widefat fixed striped posts">
            <thead>
                <tr>
                    <th class="column-id">' . esc_html__( 'ID', 'computer-repair-shop' ) . '</th>
                    <th>' . esc_html__( 'Name', 'computer-repair-shop' ) . '</th>
                    <th>' . esc_html__( 'Slug', 'computer-repair-shop' ) . '</th>
                    <th>' . esc_html__( 'Description', 'computer-repair-shop' ) . '</th>
                    <th class="column-id">' . esc_html__( 'Status', 'computer-repair-shop' ) . '</th>
                    <th class="column-id">' . esc_html__( 'Actions', 'computer-repair-shop' ) . '</th>
                </tr>
            </thead>';

        $setting_body .= '<tbody>';

        $computer_repair_payment_status = $wpdb->prefix.'wc_cr_payment_status';
            
        $select_query 	= "SELECT * FROM `" . $computer_repair_payment_status . "`";
        $select_results = $wpdb->get_results( $select_query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            
        $output = '';
        foreach( $select_results as $result ) {
            $output .= '<tr><td>' . $result->status_id . '</td>';
            $output .= '<td><strong>' . $result->status_name . '</strong></td>';
            $output .= '<td>' . $result->status_slug . '</td>';
            $output .= '<td>' . $result->status_description . '</td>';
            $output .= '<td><a href="#" title="'.esc_html__("Change Status", "computer-repair-shop").'" class="change_tax_status"  data-security="' . wp_create_nonce( 'request_statusupdates_security' ) . '" data-type="paymentStatus" data-value="'.esc_attr($result->status_id).'">'.$result->status_status.'</a></td>';
            $output .= '<td><a href="'.esc_url( add_query_arg( 'update_payment_status', $result->status_id, remove_query_arg( 'update_status' ) ) ).'" class="update_tax_status" data-type="status" data-value="'.esc_attr($result->status_id).'">'.esc_html__("Edit", "computer-repair-shop").'</a>';
            $output .= '</td></tr>';
        }
        $setting_body .= $output;
        
        $setting_body .= '</tbody>';
       
        $setting_body .= '</table></div><!-- Payment Status Wrapper /-->';

		$setting_body .= '<div class="wc-rb-payment-methods">';
		$setting_body .= '<h2>' . esc_html__( 'Payment Methods', 'computer-repair-shop' ) . '</h2>';
		$setting_body .= '<div class="methods_success_msg"></div>';
		
		$setting_body .= '<form data-async data-abide class="needs-validation" novalidate method="post" data-success-class=".methods_success_msg">';
		$setting_body .= '<fieldset class="fieldset">';
		$setting_body .= '<legend>' . esc_html__( 'Select Payment Methods', 'computer-repair-shop' ) . '</legend>';
		
		$receive_array = $this->wc_return_payment_methods_array();
		$defaultMethods = get_option( 'wc_rb_payment_methods_active' );
		
		$defaultMethodsC = unserialize( $defaultMethods );

		foreach ( $receive_array as $the_array ) {
			$theName   		= ( isset ( $the_array['name'] ) && ! empty ( $the_array['name'] ) ) ? $the_array['name'] : '';
			$theLabel  		= ( isset ( $the_array['label'] ) && ! empty ( $the_array['label'] ) ) ? $the_array['label'] : '';
			$theStatus 		= ( isset ( $the_array['status'] ) && ! empty ( $the_array['status'] ) ) ? $the_array['status'] : '';
			$theDescription = ( isset ( $the_array['description'] ) && ! empty ( $the_array['description'] ) ) ? $the_array['description'] : '';

			$theChecked = '';
			if ( is_array( $defaultMethodsC ) ) {
				$theChecked = ( in_array ( $theName, $defaultMethodsC ) ) ? ' checked' : '';
			}

			if ( ! empty ( $theName ) && ! empty ( $theLabel )  ) {
				$setting_body .= ( ! empty ( $theDescription ) ) ? '<br>' : '';
				$setting_body .= '<label for="' . $theName . '"><input ' . $theChecked . ' id="' . $theName . '" name="wc_rb_payment_method[]" value="' . $theName . '" type="checkbox">' . $theLabel;
				$setting_body .= ( ! empty ( $theDescription ) ) ? ' <small>' . $theDescription . '</small>' : '';
				$setting_body .= '</label>';
			}
		}
		$setting_body .= '</fieldset>';

		$setting_body .= '<input type="hidden" name="form_type" value="wc_rb_update_methods_ac" />';
		$setting_body .= wp_nonce_field( 'wcrb_nonce_adrepairbuddy', 'wcrb_nonce_adrepairbuddy_field', true, false );

		$setting_body .= '<button type="submit" class="button button-primary" data-type="rbsubmitmethods">' . esc_html__( 'Update Methods', 'computer-repair-shop' ) . '</button></form>';

		$setting_body .= '</div><!-- wc rb payment methods /-->';

		$setting_body .= '</div><!-- Tabs Panel /-->';

		$allowedHTML = ( function_exists( 'wc_return_allowed_tags' ) ) ? wc_return_allowed_tags() : '';
		echo wp_kses( $setting_body, $allowedHTML );
	}

	function wc_post_payment_status() { 
		global $wpdb;

		if ( ! isset( $_POST['wcrb_nonce_paymentstatus_field'] ) || ! wp_verify_nonce( $_POST['wcrb_nonce_paymentstatus_field'], 'wcrb_nonce_paymentstatus' ) ) {
			$message = esc_html__( "Something is wrong with your submission!", "computer-repair-shop" );
		} else {

			$computer_repair_payment_status = $wpdb->prefix . 'wc_cr_payment_status';

			$form_type 			  = sanitize_text_field( $_POST['form_type'] );
			$status_name 		  = sanitize_text_field( $_POST['payment_status_name'] );
			$status_slug 		  = sanitize_text_field( $_POST['payment_status_slug'] );
			$status_description	  = sanitize_textarea_field( $_POST['payment_status_description'] );
			$status_status 		  = sanitize_text_field( $_POST['payment_status_status'] );	
			$status_email_message = '';

			if ( isset ( $_POST['form_type_status_payment'] ) && $_POST['form_type_status_payment'] == 'update' ) {
				if ( isset ( $_POST['status_id'] ) && is_numeric( $_POST['status_id'] ) ) {
					$update_form = sanitize_text_field( $_POST['status_id'] );
				}
			}

			if ( $form_type == 'payment_status_form' ) {
				//Process form
				if ( empty ( $status_name ) ) {
					$message = esc_html__("Name required", "computer-repair-shop");
				} elseif ( empty ( $status_slug ) ) {
					$message = esc_html__( 'Slug is required', 'computer-repair-shop' );
				} else {
					if ( isset ( $update_form ) && is_numeric( $update_form ) ) {
						//Update functionality
						$data 	= array(
							'status_name' 			=> $status_name,
							'status_slug' 			=> $status_slug,
							'status_description' 	=> $status_description,
							'status_status' 		=> $status_status, 
						); 
						$where 	= ['status_id' 	=> $update_form];

						$update_row = $wpdb->update( $computer_repair_payment_status, $data, $where ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

						$message = esc_html__( "Settings updated!", "computer-repair-shop" );
					} else {
						$insert_query =  "INSERT INTO `{$computer_repair_payment_status}` VALUES( NULL, %s, %s, %s, %s, %s )";
		
						$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
								$wpdb->prepare( $insert_query, $status_name, $status_slug, $status_description, $status_email_message, $status_status )
						);

						$status_id = $wpdb->insert_id;

						$message = esc_html__( 'Settings updated!', 'computer-repair-shop' );
					}
				}
			} else {
				$message = esc_html__( 'Invalid Form', 'computer-repair-shop' );	
			}
		}

		$values['message'] = $message;
		$values['success'] = "YES";

		wp_send_json( $values );
		wp_die();
	}

	function wc_rb_generate_woocommerce_order() {
		$error = 0;
		if ( ! isset( $_POST['wcrb_nonce_adrepairbuddy_field'] ) || ! wp_verify_nonce( $_POST['wcrb_nonce_adrepairbuddy_field'], 'wcrb_nonce_adrepairbuddy' ) ) {
			$message = esc_html__( "Something is wrong with your submission!", "computer-repair-shop" );
		} else {
			if ( wcrb_is_method_woocommerce() == FALSE ) {
				$message = esc_html__( 'Woo Method and WooCommerce should be active.', 'computer-repair-shop' );            
				$error = 1;
			}

			if ( !isset( $_POST['wcrb_submit_type'] ) || $_POST['wcrb_submit_type'] != 'create_the_order' ) {
				$message = esc_html__( 'Unknown data!', 'computer-repair-shop' );
				$error = 1;
			}

			if ( ! isset( $_POST['wcrb_job_id'] ) || empty( $_POST['wcrb_job_id'] ) ) {
				$message = esc_html__( 'Unknown data!', 'computer-repair-shop' );
				$error = 1;
			}
			if ( ! wc_rs_license_state() ) {
				$message = esc_html__( 'Pro feature! Plugin activation required.', 'computer-repair-shop' );
				$error = 1;
			}
			
			if ( $error == 0 ) :
				$theJobId   = sanitize_text_field( $_POST['wcrb_job_id'] );
				$orders_array = $this->wc_return_woo_order_numbers( $theJobId );

				$theBalance    = 0;
				$receiving      = $this->wc_return_receivings_total( $theJobId );
				$theGrandTotal  = wc_order_grand_total( $theJobId, 'grand_total' );
				$theBalance     = $theGrandTotal - $receiving;

				if ( $theBalance < 0.01 ) {
					$message = esc_html__( 'The remaining amount is less than 0.01 which is not enough to create WooCommerce Order.', 'computer-repair-shop' );
					if ( ! empty( $orders_array ) && is_array( $orders_array ) ) {
						$orderNumbs = implode(', ', $orders_array);
						$message .= '<br><br>' . esc_html__( 'Existing WooCommerce order numbers for this job', 'computer-repair-shop' ) . ': ' . $orderNumbs;
					}
				} else {
					// Create order as pending initially
					$order_obj = $this->wc_create_wooCommerce_order_return_id( $theJobId );
					$order_id  = $order_obj->get_id();

					if ( empty( $order_id ) ) {
						$message = esc_html__( 'Something is wrong with your submission!', 'computer-repair-shop' );
					} else {
						// Add items to the order
						$this->wc_add_items_from_job_to_woo( $theJobId, 'parts', $order_id );
						$this->wc_add_items_from_job_to_woo( $theJobId, 'services', $order_id );
						$this->wc_add_items_from_job_to_woo( $theJobId, 'products', $order_id );
						$this->wc_add_items_from_job_to_woo( $theJobId, 'extras', $order_id );

						// Refresh the order object after adding items
						$order_obj = wc_get_order($order_id);
						
						// Apply discount for already received payments
						if ($receiving > 0) {
							// Calculate current total BEFORE adding fee
							$order_obj->calculate_totals();
							$current_total = $order_obj->get_total();

							// Cap discount to current total to prevent negative/zero total
							$discount = min($receiving, $current_total);
							$fee_amount = -1 * $discount; // Make negative

							$WCfee = new WC_Order_Item_Fee();
							$WCfee->set_name(esc_html__('Received', 'computer-repair-shop'));
							$WCfee->set_amount($fee_amount);
							$WCfee->set_total($fee_amount);
							$WCfee->set_tax_status('none'); // Make fee non-taxable
							
							// Add fee to order
							$order_obj->add_item($WCfee);
						}

						// Recalculate totals after adding discount fee
						$order_obj->calculate_totals();
						$order_obj->save();

						// Get final order total
						$order_total = $order_obj->get_total();
						
						// Check if total is valid
						if ($order_total <= 0) {
							$message = esc_html__( 'Order total is invalid. Cannot generate payment link.', 'computer-repair-shop' );
						} else {
							// Set payment method
							$gateways = WC()->payment_gateways->get_available_payment_gateways();

							if (empty($gateways)) {
								$message = esc_html__( 'No payment gateways available.', 'computer-repair-shop' );
							} else {
								// Only auto-set payment method if there's exactly one gateway
								// Otherwise, let customer choose at checkout
								if (count($gateways) === 1) {
									$first_gateway = reset($gateways);
									$order_obj->set_payment_method($first_gateway->id);
									$order_obj->set_payment_method_title($first_gateway->get_title());
								}
								// For multiple gateways, don't set any specific method - let WooCommerce handle it
								
								// Set status to pending payment explicitly
								$order_obj->set_status('pending');
								$order_obj->save();
								
								$payment_url = $order_obj->get_checkout_payment_url();

								$user_id                = get_current_user_id();
								$wcrb_payment_datetime  = wp_date( 'Y-m-d H:i:s' );
								
								// Record payment in our system
								$args = array(
									'date'              => $wcrb_payment_datetime, 
									'order_id'          => $theJobId,
									'receiver_id'       => $user_id,
									'method'            => 'woocommerce',  // Keep as woocommerce for tracking
									'identifier'        => 'Online Payment Link',
									'payment_status'    => 'pending',  // Change to pending since no payment made yet
									'note'              => esc_html__( 'Payment link generated', 'computer-repair-shop' ) . ' : <a href="' . $payment_url . '" target="_blank">' . esc_html__( 'View Order', 'computer-repair-shop' ) . '</a>',
									'amount'            => $order_total,
									'discount'          => '0.0',
									'status'            => 'active',
									'woo_orders'        => $order_id,
									'transaction_id'	=> $order_id,
									'currency'			=> get_option( 'wc_cr_selected_currency' )
								);
								$this->record_a_payment( $args );

								// Send email about payment link to Customer
								$this->wc_send_payment_link_to_customer_email( $theJobId, $order_id, $payment_url );

								$message = esc_html__( 'Order generated and sent to customer.', 'computer-repair-shop' );
							}
						}
					}
				}
			endif;
		}
		$values['message'] = $message;
		$values['success'] = "YES";

		wp_send_json( $values );
		wp_die();
	}

	function wc_send_payment_link_to_customer_email( $job_id, $order_id, $payment_url ) {
		if ( empty( $job_id ) || empty( $order_id )  || empty( $payment_url ) ) {
			return;
		}

		$selected_user 	= get_post_meta( $job_id, '_customer', true );

		if ( empty( $selected_user ) ) {
			return '';
		}
		$user       	= get_user_by( 'id', $selected_user );
        $customer_email =  !empty( $user ) ? $user->user_email : '';

		$menu_name_p    = get_option( 'menu_name_p' );
		$customerLabel  = get_post_meta( $job_id, "_customer_label", true );

		if ( empty( $customer_email ) ) {
			return;
		}

		$to 		= $customer_email;
		$subject 	= esc_html__( 'Online Payment Link for Your Job', 'computer-repair-shop' ). ' | ' . esc_html( $menu_name_p );
		$headers 	= array('Content-Type: text/html; charset=UTF-8');

		$body = ( ! empty( $customerLabel ) ) ? '<p>' . esc_html( 'Hi', 'computer-repair-shop' ) . ' ' . $customerLabel . '</p>' : '';
		$body .= '<h2>' . esc_html__( 'Please find your online payment link below.', 'computer-repair-shop' ) . '</h2>';

		$the_site_url 	 = get_option( 'siteurl' );

		$body .= '<p>' . esc_html__( 'Please pay online by ', 'computer-repair-shop' ) . '<a href="'. esc_url( $payment_url ) .'" target="_blank">' . esc_html__( 'Clicking Here', 'computer-repair-shop' ) . '</a></p>';

		$body .= '<p>' . esc_html__( 'Copy the url : ', 'computer-repair-shop' ) . esc_url( $payment_url ) . '</p>';

		$status_check_link = wc_rb_return_status_check_link( $job_id );

		if ( ! empty ( $status_check_link ) ) {
			$body .= '<h3>' . esc_html__( 'Check details about your job online', 'computer-repair-shop' ) . '</h3>';
			$body .= '<p><a href="' . $status_check_link . '">' . esc_html__( 'Click to open in browser' ) . '</a></p>'; 
		}

		$body_output  = wc_rs_get_email_head();
		$body_output .= $body;
		$body_output .= wc_rs_get_email_footer();

		wp_mail( $to, $subject, $body_output, $headers );

		$argums = array( 'job_id' => $job_id, 'name' => esc_html__( 'Payment link sent', 'computer-repair-shop' ), 'type' => 'public', "field" => 'payment_link_sent', "change_detail" => $payment_url );
		$WCRB_JOB_HISTORY_LOGS = WCRB_JOB_HISTORY_LOGS::getInstance();
		$WCRB_JOB_HISTORY_LOGS->wc_record_job_history( $argums );
	}

	function wc_add_items_from_job_to_woo( $job_id, $item_type, $woo_order_id ) {
		global $wpdb;
		if ( empty( $job_id ) || empty( $item_type ) || empty( $woo_order_id ) ) {
			return;
		}
		$prices_inclu_exclu = get_post_meta( $job_id, '_wc_prices_inclu_exclu', true ) ?: 'exclusive';

		$type_label = $qty_key = $price_key = $tax_key = '';
		if ( $item_type == 'parts' ) {
			$type_label = esc_html__( 'Part', 'computer-repair-shop' ) . ' - ';
			$qty_key    = 'wc_part_qty';
			$price_key  = 'wc_part_price';
			$tax_key    = 'wc_part_tax';
		} elseif ( $item_type == 'services' ) {
			$type_label = esc_html__( 'Service', 'computer-repair-shop' ) . ' - ';
			$qty_key    = 'wc_service_qty';
			$price_key  = 'wc_service_price';
			$tax_key    = 'wc_service_tax';
		} elseif ( $item_type == 'extras' ) {
			$type_label = esc_html__( 'Other', 'computer-repair-shop' ) . ' - ';
			$qty_key    = 'wc_extra_qty';
			$price_key  = 'wc_extra_price';
			$tax_key    = 'wc_extra_tax';
		} elseif ( $item_type == 'products' ) {
			$type_label = esc_html__( 'Product', 'computer-repair-shop' ) . ' - ';
			$qty_key    = 'wc_product_qty';
			$price_key  = 'wc_product_price';
			$tax_key    = 'wc_product_tax';
		}

		$table_items       = $wpdb->prefix . 'wc_cr_order_items';
		$table_items_meta  = $wpdb->prefix . 'wc_cr_order_itemmeta';
		
		$select_items_query = $wpdb->prepare( "SELECT * FROM `{$table_items}` WHERE `order_id`= %d AND `order_item_type`='%s'", $job_id, $item_type );
		$items_result       = $wpdb->get_results( $select_items_query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		foreach( $items_result as $item ) {
			$_item_id    = $item->order_item_id;
			$_item_name  = $type_label . $item->order_item_name;

			$_qty   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_items_meta} WHERE `order_item_id` = %d AND `meta_key` = %s", $_item_id, $qty_key ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$_price = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_items_meta} WHERE `order_item_id` = %d AND `meta_key` = %s", $_item_id, $price_key ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$_tax   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_items_meta} WHERE `order_item_id` = %d AND `meta_key` = %s", $_item_id, $tax_key ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

			$_qty      = $_qty->meta_value;
			$_price    = ( empty( $_price->meta_value ) ) ? 0 : (float) $_price->meta_value;
			$tax_rate  = ( empty( $_tax->meta_value ) ) ? 0 : (float) $_tax->meta_value;

			// Calculate final price based on tax setting
			$final_price = $_price;
			if ($prices_inclu_exclu === 'exclusive' && $tax_rate > 0) {
				$final_price = $_price * (1 + ($tax_rate/100));
			}

			$this->wc_create_woocommerce_link_item_in_order(
				$woo_order_id, 
				$_item_name, 
				$_qty, 
				$final_price
			);
		}
	}

	function wc_create_woocommerce_link_item_in_order( $order_id, $name, $qty, $price ) {
		if ( empty( $order_id ) || empty( $name ) ) return;

		$item = new WC_Order_Item_Product();
		$item->set_name( $name );
		$item->set_quantity( $qty );
		
		// Set prices
		$item->set_subtotal( $price * $qty );
		$item->set_total( $price * $qty );
		
		// Disable taxes safely
		$item->set_tax_class( '' ); // FIXED: Use default tax class
		$item->set_props( array( 'tax_status' => 'none' ) );
		$item->set_subtotal_tax( 0 );
		$item->set_total_tax( 0 );
		
		// Add to order
		$order = wc_get_order($order_id);
		$order->add_item($item);
		$order->save();
	}

	function wc_create_wooCommerce_order_return_id( $job_id ) {
		if ( empty( $job_id ) ) {
			return '';
		}
		
		$selected_user  = get_post_meta( $job_id, '_customer', true );
		$user_value     = ( $selected_user == '' ) ? '' : $selected_user;
		
		$arguments = array(
			'customer_id'   => $user_value,
			'status'        => 'pending',
			'customer_note' => sprintf( esc_html__( 'Order is created for job %s', 'computer-repair-shop' ), wcrb_get_label( 'casenumber', 'none' ) ) . ' : ' . get_the_title( $job_id ),
			'created_via'   => esc_html__( 'Order created via RepairBuddy CRM by staff', 'computer-repair-shop' ),
		);
		$new_order = wc_create_order( $arguments );
		
		// Force zero tax settings
		$new_order->set_cart_tax(0);
		$new_order->set_shipping_tax(0);
		$new_order->set_discount_tax(0);
		$new_order->save();

		return $new_order;
	}

	function wc_rb_update_payment_methods() {
		if ( ! isset( $_POST['wcrb_nonce_adrepairbuddy_field'] ) || ! wp_verify_nonce( $_POST['wcrb_nonce_adrepairbuddy_field'], 'wcrb_nonce_adrepairbuddy' ) ) {
			$message = esc_html__( "Something is wrong with your submission!", "computer-repair-shop" );
		} else {
			if ( isset ( $_POST['form_type'] ) && $_POST['form_type'] == 'wc_rb_update_methods_ac' ) {
				
				if ( isset ( $_POST['wc_rb_payment_method'] ) ) {
					$_POST['wc_rb_payment_method'] = ( is_array( $_POST['wc_rb_payment_method'] ) ) ? $_POST['wc_rb_payment_method'] : unserialize( $_POST['wc_rb_payment_method'] );

					if ( is_array( $_POST['wc_rb_payment_method'] ) ) {
						$received_data = sanitize_text_field( serialize( $_POST['wc_rb_payment_method'] ) );

						update_option( 'wc_rb_payment_methods_active', $received_data );

						$message = esc_html__( 'Payment methods updated!', 'computer-repair-shop' );
					} else {
						$received_data = sanitize_text_field( serialize( $_POST['wc_rb_payment_method'] ) );
						$message = esc_html__( 'Invalid format', 'computer-repair-shop' );
					}
				}
			} else {
				$message = esc_html__( 'Invalid format', 'computer-repair-shop' );
			}
		}

		$values['message'] = $message;
		$values['success'] = "YES";

		wp_send_json( $values );
		wp_die();
	}

	function wc_rb_add_payment_into_job() {
		$message = '';
		$success = 'NO';

		if ( ! isset( $_POST['wcrb_nonce_add_payment_field'] ) || ! wp_verify_nonce( $_POST['wcrb_nonce_add_payment_field'], 'wcrb_nonce_add_payment' ) ) {
			$message = esc_html__( "Something is wrong with your submission!", "computer-repair-shop" );
		} else {
			// process form data
			$wcrb_transaction_id  = ( isset( $_POST['wcrb_transaction_id'] ) && ! empty( $_POST['wcrb_transaction_id'] ) ) ? sanitize_text_field( $_POST['wcrb_transaction_id'] ) : '';
			$wcrb_payment_note     = ( isset( $_POST['wcrb_payment_note'] ) && ! empty( $_POST['wcrb_payment_note'] ) ) ? sanitize_text_field( $_POST['wcrb_payment_note'] ) : '';
			$wcrb_payment_datetime = ( isset( $_POST['wcrb_payment_datetime'] ) && ! empty( $_POST['wcrb_payment_datetime'] ) ) ? sanitize_text_field( $_POST['wcrb_payment_datetime'] ) : wp_date( 'Y-m-d H:i:s' );
			$wcrb_job_id		   = ( isset( $_POST['wcrb_job_id'] ) && ! empty( $_POST['wcrb_job_id'] ) ) ? sanitize_text_field( $_POST['wcrb_job_id'] ) : '';
			$wcRB_payment_status   = ( isset( $_POST['wcRB_payment_status'] ) && ! empty( $_POST['wcRB_payment_status'] ) ) ? sanitize_text_field( $_POST['wcRB_payment_status'] ) : '';
			$wcRB_payment_method   = ( isset( $_POST['wcRB_payment_method'] ) && ! empty( $_POST['wcRB_payment_method'] ) ) ? sanitize_text_field( $_POST['wcRB_payment_method'] ) : '';
			$wcRb_payment_amount   = ( isset( $_POST['wcRb_payment_amount'] ) && ! empty( $_POST['wcRb_payment_amount'] ) ) ? sanitize_text_field( $_POST['wcRb_payment_amount'] ) : '';

			//cannot be empty
			$user_id = get_current_user_id();
			if ( empty( $wcrb_job_id ) ) {
				$message = esc_html__( 'Unknown Job.', 'computer-repair-shop' );
			} elseif ( empty( $user_id ) ) {
				$message = esc_html__( 'Cannot identify your access.', 'computer-repair-shop' );
			} elseif ( empty( $wcRB_payment_status ) || empty( $wcRB_payment_method ) || empty( $wcRb_payment_amount ) || ( $wcRb_payment_amount == 0 ) ) {
				$message = esc_html__( 'Status, Method and amount is required.', 'computer-repair-shop' );
			} else {
				//We can now add the payment.
				//Let's prepare arguments to pass in function
				$args = array(
					'date' 			 => $wcrb_payment_datetime, 
					'order_id' 		 => $wcrb_job_id,
					'receiver_id'    => $user_id,
					'method' 	 	 => $wcRB_payment_method,
					'identifier' 	 => 'Take Payment',
					'payment_status' => $wcRB_payment_status,
					'note' 			 => $wcrb_payment_note,
					'amount' 		 => $wcRb_payment_amount,
					'discount' 		 => '0.0',
					'status' 		 => 'active',
					'woo_orders' 	 => '',
					'transaction_id'	=> $wcrb_transaction_id,
					'currency'			=> get_option( 'wc_cr_selected_currency' )
				);

				$this->record_a_payment( $args );

				if ( isset( $_POST['wcRB_after_jobstatus'] ) && ! empty( $_POST['wcRB_after_jobstatus'] ) ) {
					$old_job_status = get_post_meta( $wcrb_job_id, '_wc_order_status', true );

					$new_job_status = sanitize_text_field( $_POST['wcRB_after_jobstatus'] );
					update_post_meta( $wcrb_job_id, '_wc_order_status', $new_job_status );

					if ( ( $old_job_status != $new_job_status ) || empty ( $old_job_status ) ) {
						global $OBJ_SMS_SYSTEM;
						$wc_send_cr_notice 	= get_option( 'wc_job_status_cr_notice' );

						$is_sms_active = get_option( 'wc_rb_sms_active' );
						if ( $is_sms_active == 'YES' ) {
							$OBJ_SMS_SYSTEM->wc_rb_status_send_the_sms( $wcrb_job_id, $new_job_status );
						}

						if ( function_exists( 'rb_qb_update_invoice_status' ) ) {
							rb_qb_update_invoice_status( $old_job_status, $new_job_status, $wcrb_job_id );
						}

						if ( $wc_send_cr_notice == 'on' ) {
							$_GET['wc_case_number'] = sanitize_text_field( $_POST['case_number'] );
							
							wc_cr_send_customer_update_email( $wcrb_job_id );
						}

						//Signature request
						$WCRB_SIGNATURE_WORKFLOW = WCRB_SIGNATURE_WORKFLOW::getInstance();
						$WCRB_SIGNATURE_WORKFLOW->send_signature_request( $new_job_status, $wcrb_job_id );
					}
				}

				$message = esc_html__( 'Payment added!', 'computer-repair-shop' );
				$success = 'YES';
			}
		}

		$values['message'] = $message;
		$values['success'] = $success;

		wp_send_json( $values );
		wp_die();
	}

	/**
	 * Function adds the payment
	 * 
	 * Arguments are in form of array with following keys
	 * {date, order_id, receiver_id, method, identifier, payment_status, note, amount, discount, status, woo_orders}
	 */
	function record_a_payment( $args ) {
		global $wpdb;
		$table_wc_cr_payments = $wpdb->prefix.'wc_cr_payments';

		if ( empty( $args ) || ! is_array( $args ) ) {
			return;
		}

		$date           = ( isset( $args['date'] ) ) ? $args['date'] : wp_date( 'Y-m-d H:i:s' );
		$order_id       = ( isset( $args['order_id'] ) ) ? $args['order_id'] : '';
		$receiver_id    = ( isset( $args['receiver_id'] ) ) ? $args['receiver_id'] : get_current_user_id();
		$method         = ( isset( $args['method'] ) ) ? $args['method'] : 'cash';
		$identifier     = ( isset( $args['identifier'] ) ) ? $args['identifier'] : '';
		$transaction_id = ( isset( $args['transaction_id'] ) ) ? $args['transaction_id'] : '';
		$payment_status = ( isset( $args['payment_status'] ) ) ? $args['payment_status'] : 'nostatus';
		$note           = ( isset( $args['note'] ) ) ? $args['note'] : '';
		$amount         = ( isset( $args['amount'] ) ) ? (float) $args['amount'] : 0.00;
		$discount       = ( isset( $args['discount'] ) ) ? (float) $args['discount'] : 0.00;
		$currency 		= ( isset( $args['currency'] ) ) ? $args['currency'] : '';
		$status         = ( isset( $args['status'] ) ) ? $args['status'] : 'pending';
		$woo_orders     = ( isset( $args['woo_orders'] ) ) ? $args['woo_orders'] : '';

		if ( empty( $order_id ) ) {
			return;
		}

		// Get current financial values
		$grand_total = (float) wc_order_grand_total( $order_id, 'grand_total' );
		$payments_total = (float) $this->wc_return_receivings_total( $order_id );
		
		// Calculate remaining balance
		$remaining = $grand_total - $payments_total;
		
		// Allow overpayment but track it
		$is_overpayment = false;
		$overpayment_amount = 0.00;
		
		if ($amount > $remaining && $remaining > 0) {
			$is_overpayment = true;
			$overpayment_amount = $amount - $remaining;
			// Note: We're NOT capping the amount here - allowing the full payment to be recorded
		}
		
		// Handle micro-adjustments for very small remaining amounts
		$tolerance = 0.05; // 5 cent tolerance for auto-adjustment
		
		if ($remaining > 0 && $remaining <= $tolerance && $amount >= $remaining) {
			// If remaining is very small and payment covers it, auto-adjust to exact amount
			$amount = $remaining;
		}
		
		// Round to 2 decimal places for financial precision
		$amount = round($amount, 2);
		
		// Insert the payment record
		$data = array( 
			'date' => $date, 
			'order_id' => $order_id, 
			'receiver_id' => $receiver_id, 
			'method' => $method, 
			'identifier' => $identifier, 
			'transaction_id' => $transaction_id,
			'payment_status' => $payment_status, 
			'note' => $note, 
			'amount' => $amount, 
			'discount' => $discount, 
			'currency' => $currency,
			'status' => $status, 
			'woo_orders' => $woo_orders 
		);
		
		$format = array( '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s', '%s', '%s' );
		
		$wpdb->insert( $table_wc_cr_payments, $data, $format );
		$thePaymentID = $wpdb->insert_id;

		// Update payment status
		update_post_meta( $order_id, '_wc_payment_status', $payment_status );
		update_post_meta( $order_id, '_wc_payment_status_label', wc_return_payment_status( $payment_status ) );
		
		// If there was overpayment, add a note to the payment record
		if ($is_overpayment && $overpayment_amount > 0) {
			$overpayment_note = sprintf(
				__('Overpayment of %s detected', 'computer-repair-shop'),
				wc_price($overpayment_amount)
			);
			
			// Update the note to include overpayment information
			$wpdb->update(
				$table_wc_cr_payments,
				array('note' => $note . ' | ' . $overpayment_note),
				array('id' => $thePaymentID),
				array('%s'),
				array('%d')
			);
			
			// Also add to order notes/history
			$overpayment_history = array(
				'job_id' => $order_id,
				'name' => esc_html__('Overpayment recorded', 'computer-repair-shop'),
				'type' => 'public',
				"field" => 'payment_overpayment',
				"change_detail" => $overpayment_note
			);
			$WCRB_JOB_HISTORY_LOGS = WCRB_JOB_HISTORY_LOGS::getInstance();
			$WCRB_JOB_HISTORY_LOGS->wc_record_job_history( $overpayment_history );
		}

		// Record the payment in history
		$history_name   = esc_html__( 'Payment added', 'computer-repair-shop' );
		$history_detail = serialize( $args );

		$argums = array( 
			'job_id' => $order_id, 
			'name' => $history_name, 
			'type' => 'public', 
			"field" => 'payment_table', 
			"change_detail" => $history_detail 
		);
		$WCRB_JOB_HISTORY_LOGS = WCRB_JOB_HISTORY_LOGS::getInstance();
		$WCRB_JOB_HISTORY_LOGS->wc_record_job_history( $argums );

		return $thePaymentID;
	}

	/**
	 * @Since 3.479
	 * 
	 * list_payments: takes array of arguments
	 * array( 'job_id' => $post->ID, 'print_head' => 'NO', 'include_job' => 'YES', 'limit' => $_per_page_rec, 'offset' => $display_from, 'discounts' => 'nodiscounts' )
	 */
	function list_the_payments( $args ) {
		global $wpdb;
		$table_wc_cr_payments = $wpdb->prefix.'wc_cr_payments';

		if ( empty( $args ) || ! is_array( $args ) ) {
			return;
		}

		$print_head = ( isset( $args['print_head'] ) ) ? $args['print_head'] : 'NO';
		$state 		= ( isset( $args['job_id'] ) ) ? 'FILTERED' : 'ALL';

		$theHEad = '';

		if ( $print_head == 'YES' ) {
			$theHEad = '<table class="grey-bg wc_table"><thead><tr>';
			$theHEad .= '<th class="column-id">' . esc_html__( 'ID', 'computer-repair-shop' ) . '</th>';
			$theHEad .= '<th>' . esc_html__( 'ON', 'computer-repair-shop' ) . '</th>';
			
			$theHEad .= ( isset( $args['include_job'] ) && $args['include_job'] == 'YES' ) ? '<th>' . esc_html__( 'JOB', 'computer-repair-shop' ) . '</th>' : '';
			
			$theHEad .= '<th>' . esc_html__( 'Receiver', 'computer-repair-shop' ) . '</th>';
			$theHEad .= '<th>' . esc_html__( 'Method', 'computer-repair-shop' ) . '</th>';
			$theHEad .= '<th>' . esc_html__( 'Transaction ID', 'computer-repair-shop' ) . '</th>';
			$theHEad .= '<th>' . esc_html__( 'Status', 'computer-repair-shop' ) . '</th>';
			$theHEad .= '<th>' . esc_html__( 'Note', 'computer-repair-shop' ) . '</th>';
			$theHEad .= '<th>' . esc_html__( 'Validity', 'computer-repair-shop' ) . '</th>';
			$theHEad .= '<th class="column-id">' . esc_html__( 'Amount', 'computer-repair-shop' ) . '</th>';
			$theHEad .= '<th>' . esc_html__( 'Actions', 'computer-repair-shop' ) . '</th>';
			$theHEad .= '</tr></thead>';
		}

		if ( $state == 'ALL' ) {
			$offset = $args['offset'];
			$limit = $args['limit'];
			$discount = 0;

			$select_items_query = "SELECT * FROM `{$table_wc_cr_payments}` WHERE `discount` = %s ORDER BY `payment_id` DESC LIMIT %d OFFSET %d";
			$items_result = $wpdb->get_results( $wpdb->prepare( $select_items_query, $discount, $limit, $offset ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		} else {
			$select_items_query = "SELECT * FROM `{$table_wc_cr_payments}` WHERE `order_id`= %d ORDER BY `payment_id` DESC";
			$items_result = $wpdb->get_results( $wpdb->prepare( $select_items_query, $args['job_id'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}
		
		$content 	 = $theHEad;
		$date_format = get_option( 'date_format' );
		$time_format = get_option( 'time_format' );

		foreach ( $items_result as $item ) {
			$payment_id 	= $item->payment_id;
			$date 			= $item->date;
			$order_id 		= $item->order_id;
			$receiver_id 	= $item->receiver_id;
			$method 		= $item->method;
			$identifier 	= $item->identifier;
			$transaction_id = $item->transaction_id;
			$payment_status = $item->payment_status;
			$note 			= stripslashes( $item->note );
			$amount 		= $item->amount;
			$discount 		= $item->discount;
			$status 		= $item->status;
			$woo_orders 	= $item->woo_orders;

			$user_info 		= get_userdata( $receiver_id );
			$theName 		= $user_info->first_name;
			$theName 		.= ' ' . $user_info->last_name;

			$theName		= ( empty( $theName ) ) ? $user_info->user_login : $theName;

			$formated_date =  date( $date_format, strtotime( $date ) );
			$formated_time =  date( $time_format, strtotime( $date ) );

			if ( $note != 'Automatic balance reconciliation' ) {
				$content .= '<tr class="wcrb_payment_'. $status .'">';
				$content .= '<td>' . $payment_id . '</td>';
				$content .= '<td>' . $formated_date . ' ' . $formated_time . '</td>';

				$admin_url  = admin_url( 'post.php?post=' . $order_id . '&action=edit' );
				
				$jobs_manager = WCRB_JOBS_MANAGER::getInstance();
				$job_data 	  = $jobs_manager->get_job_display_data( $order_id );
				$_job_id  	  = ( ! empty( $job_data['formatted_job_number'] ) ) ? $job_data['formatted_job_number'] : $order_id;

				$order_details = esc_html__( 'ID', 'computer-repair-shop' );
				$order_details .= ' : ' . $_job_id;
				$order_details .= '<br>' . wcrb_get_label( 'casenumber', 'first' ) . ': <a href="' . esc_url( $admin_url ) . '" target="_blank">' . get_the_title( $order_id ) . '</a>';

				$content .= ( isset( $args['include_job'] ) && $args['include_job'] == 'YES' ) ? '<td>' . $order_details . '</td>' : '';

				$content .= '<td>' . $theName . '</td>';
				$label 	  = ( ( $method == 'woocommerce' ) ) ? ' { ' . esc_html__( 'Order', 'computer-repair-shop' ) . ' # ' . $woo_orders . ' } ' : '';
				$content .= '<td>' . $this->wc_payment_method_label( $method ) . '</td>';
				$content .= '<td>' . esc_html( $transaction_id ) . '</td>';
				$content .= '<td>' . wc_return_payment_status( $payment_status ) . '</td>';
				$content .= '<td>' . $note . '</td>';
				
				$url_title = ( $status == 'active' ) ? esc_html__( 'Deactivate', 'computer-repair-shop' ) : esc_html__( 'Activate', 'computer-repair-shop' );

				$content .= '<td><a href="#" title="' . esc_html( $url_title ) . '" class="change_tax_status"  data-security="' . wp_create_nonce( 'request_statusupdates_security' ) . '" data-type="thePayment" data-value="' . esc_html( $payment_id ) . '">' . ucfirst( $status ) . '</a></td>';

				$returnField = ( $status == 'active' ) ? '<input type="hidden" name="wcrb_payment_field[]" value="' . $amount . '" />' : '';
				$content .= '<td>' . $returnField . wc_cr_currency_format( $amount, TRUE, TRUE ) . '</td>';

				$content .= '<td>
								<div class="actionswrapperjobs">
									<a title="'. esc_html__( 'Print Receipt', 'computer-repair-shop' ) .'" target="_blank" 
									href="admin.php?page=wc_computer_repair_print&print_reciept=reciept_print&payment_id='. esc_attr( $payment_id ) .'&reciept_security_p='. esc_attr( wp_create_nonce( 'reciept_security' ) ) .'">
										<span class="dashicons dashicons-printer"></span>
									</a>
									<a title="'. esc_html__( 'View Receipt', 'computer-repair-shop' ) .'" target="_blank" 
									href="admin.php?page=wc_computer_repair_print&print_reciept=reciept_print&payment_id='. esc_attr( $payment_id ) .'&reciept_security_p='. esc_attr( wp_create_nonce( 'reciept_security' ) ) .'">
										<span class="dashicons dashicons-visibility"></span>
									</a>
									<a title="'. esc_html__( 'Email to customer', 'computer-repair-shop' ) .'" target="_blank" 
									href="admin.php?page=wc_computer_repair_print&print_reciept=reciept_print&email=reciept_email&job_id='. esc_attr( $order_id ) .'&payment_id='. esc_attr( $payment_id ) .'&reciept_security_p='. esc_attr( wp_create_nonce( 'reciept_security' ) ) .'">
										<span class="dashicons dashicons-email-alt"></span>
									</a>
								</div>
							</td>';
				
				$content .= '</tr>';
			}
		}

		$content .= ( $print_head == 'YES' ) ? '</table>' : '';

		if ( $wpdb->num_rows > 0 ) {
			return $content;
		}
	}

	function wc_return_order_id_by_woo_order_num($woo_order_id) {
		global $wpdb;
		
		if (empty($woo_order_id)) {
			return;
		}

		$table = $wpdb->prefix . 'wc_cr_payments';
		
		$query = "SELECT order_id FROM `{$table}` 
				WHERE method = %s AND woo_orders = %s 
				ORDER BY payment_id DESC 
				LIMIT 1";
				
		$order_id = $wpdb->get_var(
			$wpdb->prepare($query, 'woocommerce', $woo_order_id)
		);
		
		return $order_id ? (int)$order_id : 0;
	}

	function wc_return_woo_order_numbers($job_id) {
		global $wpdb;
		
		if (empty($job_id) || !is_numeric($job_id)) {
			return array();
		}
		
		$job_id = (int)$job_id;
		$table = $wpdb->prefix . 'wc_cr_payments';
		
		$query = "SELECT woo_orders FROM `{$table}` 
				WHERE order_id = %d 
					AND method = 'woocommerce' 
					AND status = 'active' 
				ORDER BY payment_id DESC";
		
		$results = $wpdb->get_col(
			$wpdb->prepare($query, $job_id)
		);
		
		return $results ?: array();
	}

	function wc_return_receivings_total( $job_id ) {
		global $wpdb;
		
		if (empty($job_id) || !is_numeric($job_id)) {
			return 0;
		}
		
		$job_id = (int)$job_id;
		$table = $wpdb->prefix . 'wc_cr_payments';
		
		$query = "SELECT SUM(amount) as total 
				FROM `{$table}` 
				WHERE order_id = %d 
					AND status = 'active'";
		
		$total = $wpdb->get_var(
			$wpdb->prepare($query, $job_id)
		);
		
		// Return 0 if no records found, otherwise the float value
		return $total ? (float)$total : 0;
	}

	function wc_payment_method_label( $returnFor ) {
		if ( empty( $returnFor ) ) {
			return;
		}
		$themethodsArray = $this->wc_return_payment_methods_array();

		$theLabel = '';

		foreach ( $themethodsArray as $theArr ) {
			if ( $theArr['name'] == $returnFor ) {
				$theLabel = $theArr['label'];
			}
		}
		return $theLabel;
	}

	/***
	 * @since 3.2
	 * 
	 * Adds Post Status form in footer.
	*/
	function wc_add_payment_status_form() {
		$status_name = $status_slug = $status_status = $status_description = "";
		$button_label = $modal_label = esc_html__( 'Add new', 'computer-repair-shop' );

		if ( isset( $_GET['update_payment_status'] ) && ! empty ( $_GET['update_payment_status'] ) ) :
			global $wpdb;

			$update_payment_status = sanitize_text_field( $_GET['update_payment_status'] );
			$button_label          = $modal_label = esc_html__( 'Update', 'computer-repair-shop' );

			$status_id = sanitize_text_field( $_GET["update_payment_status"] );

			$computer_repair_payment_status = $wpdb->prefix . 'wc_cr_payment_status';
			$wc_status_row				    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$computer_repair_payment_status} WHERE `status_id` = %d", $status_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			
			$status_name 		= $wc_status_row->status_name;
			$status_slug 		= $wc_status_row->status_slug;
			$status_description	= $wc_status_row->status_description;
			$status_status		= $wc_status_row->status_status;
		endif;
		?>
		<!-- Modal for Post Entry /-->
		<div class="small reveal" id="paymentStatusFormReveal" data-reveal>
			<h2><?php echo esc_html( $modal_label ) . " " . esc_html__( 'Payment status', 'computer-repair-shop' ); ?></h2>
	
			<div class="form-message"></div>
	
			<form data-async data-abide class="needs-validation" name="payment_status_form_sync" novalidate method="post">
				<div class="grid-x grid-margin-x">
					<div class="cell">
						<div data-abide-error class="alert callout" style="display: none;">
							<p><i class="fi-alert"></i> <?php echo esc_html__( 'There are some errors in your form.', 'computer-repair-shop' ); ?></p>
						</div>
					</div>
				</div>
	
				<!-- Login Form Starts /-->
				<div class="grid-x grid-margin-x">
	
					<div class="cell medium-6">
						<label><?php echo esc_html__("Status Name", "computer-repair-shop"); ?>*
							<input name="payment_status_name" type="text" class="form-control login-field"
									value="<?php echo esc_html( $status_name ); ?>" required id="payment_status_name"/>
							<span class="form-error">
								<?php echo esc_html__( 'Name the status to recognize.', 'computer-repair-shop' ); ?>
							</span>
						</label>
					</div>
	
					<div class="cell medium-6">
						<label><?php echo esc_html__( 'Status Slug', 'computer-repair-shop' ); ?>*
							<input name="payment_status_slug" type="text" class="form-control login-field"
									value="<?php echo esc_html( $status_slug ); ?>" required id="payment_status_slug"/>
							<span class="form-error">
								<?php echo esc_html__( 'Slug is required to recognize the status make sure to not change it.', 'computer-repair-shop' ); ?>
							</span>
						</label>
					</div>
				</div>
	
				<div class="grid-x grid-margin-x">
					<div class="cell medium-6">
						<label><?php echo esc_html__( 'Description', 'computer-repair-shop' ); ?>
							<input name="payment_status_description" type="text" class="form-control login-field"
									value="<?php echo esc_html( $status_description ); ?>" id="payment_status_description" />
						</label>
					</div>
	
					<div class="cell medium-6">
						<label><?php echo esc_html__( 'Status', 'computer-repair-shop' ); ?>
							<select class="form-control form-select" name="payment_status_status">
								<?php $theStatusAc = ( $status_status == "active" ) ? "selected" : ""; ?>
								<?php $theStatusIn = ( $status_status == "inactive" ) ? "selected" : ""; ?>
								<option <?php echo esc_html( $theStatusAc ); ?> value="active"><?php echo esc_html__( 'Active', 'computer-repair-shop' ); ?>
								<option <?php echo esc_html( $theStatusIn ); ?> value="inactive"><?php echo esc_html__( 'Inactive', 'computer-repair-shop' ); ?>
							</select>
						</label>
					</div>
				</div>
				<!-- Login Form Ends /-->
	
				<!-- Login Form Ends /-->
				<input name="form_type" type="hidden" value="payment_status_form" />

				<?php 
					wp_nonce_field( 'wcrb_nonce_paymentstatus', 'wcrb_nonce_paymentstatus_field', true, true );
					if ( ! empty( $update_payment_status ) ) : ?>
					<input name="form_type_status_payment" type="hidden" value="update" />
					<input name="status_id" type="hidden" value="<?php echo esc_html( $update_payment_status ); ?>" />
				<?php else: ?>
					<input name="form_type_status_payment" type="hidden" value="add" />
				<?php endif; ?>

				<div class="grid-x grid-margin-x">
					<fieldset class="cell medium-6">
						<button class="button" type="submit"><?php echo esc_html( $button_label ); ?></button>
					</fieldset>
					<small>(*) <?php echo esc_html__( 'fields are required', 'computer-repair-shop' ); ?></small>	
				</div>
			</form>
	
			<button class="close-button" data-close aria-label="Close modal" type="button">
				<span aria-hidden="true">&times;</span>
			</button>
		</div>
		<?php
			if ( ! empty( $update_payment_status ) ) {
				echo "<div id='updatePaymentStatus'></div>";
			}
		}


	/**
	 * Function create Payment Options
	 * 
	 * @Snce 3.7946
	 * 
	 * This doesn't create select around options
	 *	
     * Single argument of selected ID.
	 * 	
	 * Takes parameter for selected option.
	 */
	function wc_generate_payment_status_options( $wc_selected_status ) {
		global $wpdb;

		$field_to_select  = "status_slug";
		$selected_field   = $wc_selected_status;

		if ( ! isset ( $selected_field ) ) {
			$selected_field = '';
		}
		
		//Table
		$computer_repair_payment_status = $wpdb->prefix.'wc_cr_payment_status';

		// Modified query to order by status_name alphabetically
		$select_query   = "SELECT * FROM `" . $computer_repair_payment_status . "` WHERE `status_status`='active' ORDER BY `status_name` ASC";
		$select_results = $wpdb->get_results( $select_query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		
		$output = '';
		foreach($select_results as $result) {
			if($result->status_slug == $selected_field) {
				$selected = 'selected="selected"';
			} else {
				$selected = '';
			}

			$output .= '<option '.$selected.' value="' . esc_attr( $result->$field_to_select ) . '">';
			$output .= esc_html( $result->status_name );
			$output .= '</option>';
		} // End Foreach    

		return $output;
	}

	/**
	 * Function create Payment Method Options
	 * 
	 * @Snce 3.7946
	 * 
	 * This doesn't create select around options
	 *	
     * @arg 1 : YES to include unselected Methods
	 * @arg 2 : YES to add eCommerce Methods
	 * @arg 3 : Selected Method
	 * 	
	 */
	function wc_generate_payment_method_options( $non_selected, $online_options, $wc_selected_method ) {
		global $wpdb;

		$non_selected		= ( ! isset ( $non_selected ) || empty ( $non_selected ) ) ? 'NO' : $non_selected;
		$online_options		= ( ! isset ( $online_options ) || empty ( $online_options ) ) ? 'NO' : $online_options;
		$wc_selected_method = ( ! isset ( $wc_selected_method ) || empty ( $wc_selected_method ) ) ? '' : $wc_selected_method;
		
		$array_of_all_methods = $this->wc_return_payment_methods_array();

		if ( ! is_array( $array_of_all_methods ) ) {
			return '';
		}

		$selectedMethods = get_option( 'wc_rb_payment_methods_active' );
		$selectedMethods = unserialize( $selectedMethods );

		$output = '';
		foreach ( $array_of_all_methods as $the_array ) {
			//label, name, status
			if ( $online_options == 'NO' && $the_array['type'] == 'online' ) {
				//Nothing to d o
			} else {
				$theName   		= ( isset ( $the_array['name'] ) && ! empty ( $the_array['name'] ) ) ? $the_array['name'] : '';
				$theLabel  		= ( isset ( $the_array['label'] ) && ! empty ( $the_array['label'] ) ) ? $the_array['label'] : '';
				$theStatus 		= ( isset ( $the_array['status'] ) && ! empty ( $the_array['status'] ) ) ? $the_array['status'] : '';
				$theDescription = ( isset ( $the_array['description'] ) && ! empty ( $the_array['description'] ) ) ? $the_array['description'] : '';
				
				$theselected 	= ( $theName == $wc_selected_method ) ? ' selected' : '';

				$theChecked = 'addIt';
				if ( $non_selected == 'NO' ) {
					if ( is_array( $selectedMethods ) ) {
						$theChecked = ( in_array ( $theName, $selectedMethods ) ) ? 'addIt' : '';
					}
				}
				if ( $theChecked == 'addIt' ) {
					$output .= '<option ' . $theselected . ' value="' . $theName . '">' . $theLabel . '</option>';
				}
			}
		}
		return $output;
	}


	/**
	 * Function return payment status array
	 * 
	 * @Snce 3.7946
	 * 
	 * Return key as slug and value as value
	 *	
	 * Takes active or all parameter
	 */
	function wc_generate_payment_status_array( $return_status ) {
		global $wpdb;

		$return_status = ( $return_status == 'active' ) ? 'active' : 'all';
	
		//Table
		$computer_repair_payment_status = $wpdb->prefix.'wc_cr_payment_status';

		if ( $return_status == 'active' ) {
			$select_query 	= "SELECT * FROM `" . $computer_repair_payment_status . "` WHERE `status_status`='active'";
		} else {
			$select_query 	= "SELECT * FROM `" . $computer_repair_payment_status . "`";
		}
		$select_results = $wpdb->get_results( $select_query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		
		$output = array();
		foreach ( $select_results as $result ) {
			$output[$result->status_slug] = esc_html( $result->status_name );
		} // End Foreach	

		return $output;
	}

	/**
	 * Takes Job id
	 * Return payment link for  unpaid generated order.
	 * 
	 * If order isn't generated already, it will help customer generrate it.
	 */
	function wc_return_online_payment_link( $order_id ) {
		global $wpdb;
		$table_wc_cr_payments = $wpdb->prefix.'wc_cr_payments';

		if ( rb_is_woocommerce_activated() == false ) {
			return;
		}

		if ( empty( $order_id ) ) {
			return '';
		}

		$select_items_query = "SELECT * FROM `{$table_wc_cr_payments}` WHERE `order_id`= %d AND `method`='woocommerce' AND `status` = 'active' ORDER BY `payment_id` DESC";
		$items_result = $wpdb->get_results( $wpdb->prepare( $select_items_query, $order_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		$content = '';
		foreach( $items_result as $theresult ) {
			$orderID = $theresult->woo_orders;
			
			if ( ! empty( $orderID ) ) {
				$order = wc_get_order( $orderID );
				
				// FIX: Validate the order object before calling methods
				if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
					// Log error for debugging
					continue; // Skip to next iteration
				}
				
				// FIX: Check if order status is valid before getting payment URL
				if ( $order->get_status() == 'pending' ) {
					try {
						$paymentLink = $order->get_checkout_payment_url();
						
						if ( ! empty( $paymentLink ) ) {
							$content .= '<a href="'. esc_url( $paymentLink ) .'" class="button btn btn-primary btn-sm button-primary expanded wcrb">' . esc_html__( 'Pay Now', 'computer-repair-shop' ) . '</a>';
						}
					} catch ( Exception $e ) {
						// Log any exceptions that might occur
						continue;
					}
				}
			}
		}

		$content = ( ! empty( $content ) ) ? '<div class="wcrb_payment_links"><div class="d-grid gap-2 mb-2">' . $content . '</div></div>' : '';
		
		$allowedHTML = wc_return_allowed_tags(); 
		return wp_kses( $content, $allowedHTML );
	}

	/**
	 * Function return payment methods array
	 * 
	 * @Since 3.7947
	 * @package RepairBuddy
	 */
	function wc_return_payment_methods_array() {
		$array_return = array();

		$array_return[] = array(
			'label'  => esc_html__( 'Cash', 'computer-repair-shop' ),
			'name'	 => 'cash',
			'type'	 => 'onsite',
			'status' => 'disable'
		);
		
		$array_return[] = array(
			'label'  => esc_html__( 'Bank Transfer', 'computer-repair-shop' ),
			'name' 	 => 'bank-transfer',
			'type'	 => 'onsite',
			'status' => 'disable'
		);

		$array_return[] = array(
			'label'  => esc_html__( 'Check', 'computer-repair-shop' ),
			'name' 	 => 'check',
			'type'	 => 'onsite',
			'status' => 'disable'
		);

		$array_return[] = array(
			'label'  => esc_html__( 'Swipe Transaction', 'computer-repair-shop' ),
			'name' 	 => 'card-swipe',
			'type'	 => 'onsite',
			'status' => 'disable'
		);

		$array_return[] = array(
			'label'  => esc_html__( 'Mobile Payments', 'computer-repair-shop' ),
			'name' 	 => 'mobile-payment',
			'type'	 => 'onsite',
			'status' => 'enable',
		);

		$array_return[] = array(
			'label'  => esc_html__( 'WooCommerce', 'computer-repair-shop' ),
			'name' 	 => 'woocommerce',
			'status' => 'enable',
			'type'	 => 'online',
			'description' => esc_html__( 'WooCommerce needs to be active to process orders.', 'computer-repair-shop' ),
		);
		return $array_return;
	}

	function print_reciept( $payment_id ) {
		global $wpdb;

        if ( empty( $payment_id ) ) {
            return;
        }
		$table_wc_cr_payments = $wpdb->prefix.'wc_cr_payments';
		$_payment_ob = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_wc_cr_payments} WHERE `payment_id` = %d", $payment_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		$order_id = $_payment_ob->order_id;
		$customer_id = get_post_meta( $order_id, '_customer', true );

		$current_user = wp_get_current_user();

		if ( in_array( 'technician', (array) $current_user->roles ) || in_array( 'store_manager', (array) $current_user->roles ) || in_array( 'administrator', (array) $current_user->roles ) ) {
			$error = 0;
		} elseif( in_array( 'customer', (array) $current_user->roles ) ) {
			if ( $customer_id !=  get_current_user_id() ) {
				$error = 1;
			} else {
				$error = 0;
			}
		}

		if ( $error == 1 ) {
			return esc_html__( 'Something is wrong with your submission!', 'computer-repair-shop' ) . 'ER2';
		}

		$date_format  	= get_option( 'date_format' );
		$time_format	= get_option( 'time_format' );
        $payment_time 	= ( ! empty( $_payment_ob->date ) ) ? date( $time_format, strtotime( $_payment_ob->date ) ) : '';
		$payment_date 	= ( ! empty( $_payment_ob->date ) ) ? date_i18n( $date_format, strtotime( $_payment_ob->date ) ) : '';

        //Let's do magic.
        $user            = get_user_by( 'id', $customer_id );
        $user_email      =  !empty( $user ) ? $user->user_email : '';
        
        $content = '<div id="invoice-box" class="invoice-box paymentReciept border-top-ten border-bottom-five">
        <table cellpadding="0" cellspacing="0">
            <tr class="top">
                <td colspan="2">
                    <table>
                        <tr>
                            <td>';
        $content .= '<div  class="title">' . wc_rb_return_logo_url_with_img( 'company_logo' ) . '</div>';
		$wc_rb_business_name	= get_option( 'wc_rb_business_name' );
		$wc_rb_business_phone	= get_option( 'wc_rb_business_phone' );
		$wc_rb_business_address	= get_option( 'wc_rb_business_address' );
		$wc_rb_business_name	= ( empty( $wc_rb_business_name ) ) ? get_bloginfo( 'name' ) : $wc_rb_business_name;
		$computer_repair_email = get_option( 'computer_repair_email' );

		$_store_id = get_post_meta( $order_id, '_store_id', true );
		if ( ! empty( $_store_id ) ) {
			$store_address = get_post_meta( $_store_id, '_store_address', true );
			$store_email   = get_post_meta( $_store_id, '_store_email', true );

			$wc_rb_business_address = ( ! empty( $store_address ) ) ? $store_address : $wc_rb_business_address;
			$computer_repair_email = ( ! empty( $store_email ) ) ? $store_email : $computer_repair_email;
		}

		if(empty($computer_repair_email)) {
			$computer_repair_email	= get_option("admin_email");	
		}

		$content .= '<div class="company_info large_invoice">';
		$content .= "<div class='address-side topmargin-fifteen'>
						<h2>" . esc_html( wp_unslash( $wc_rb_business_name ) ) . "</h2>";
		$content .= "<p>";
		$content .= $wc_rb_business_address;
		$content .= (!empty($computer_repair_email)) ? "<br><strong>".esc_html__("Email", "computer-repair-shop")."</strong>: ".$computer_repair_email : "";
		$content .= (!empty($wc_rb_business_phone)) ? "<br><strong>".esc_html__("Phone", "computer-repair-shop")."</strong>: ".$wc_rb_business_phone : "";
		$content .= "</p></div>";
		$content .= '</div>';

		$_transaction_id = ( ! empty( $_payment_ob->transaction_id ) ) ? '<strong>'. esc_html__( 'Transaction ID', 'computer-repair-shop' ) .' :</strong>' . esc_html( $_payment_ob->transaction_id ) . '<br>' : '';

		$payment_status = ( ! empty( $_payment_ob->status ) && $_payment_ob->status == 'active' ) ? esc_html__( 'Active', 'computer-repair-shop' ) : esc_html__( 'Inactive', 'computer-repair-shop' );
        $content .= '</td>
                            <td class="invoice_headers">
                                <h2 class="wcrb_invoice_label">'. esc_html__( 'Payment receipt', 'computer-repair-shop' ) .'</h2>
                                <strong>'.esc_html__( "Payment ID", "computer-repair-shop").' :</strong> '. esc_html( $payment_id ) .'<br>
								<strong>'.esc_html__( "Order ID", "computer-repair-shop").' :</strong> '. esc_html( $order_id ) .'<br>
								<strong>'. wcrb_get_label( 'casenumber', 'first' ) .' :</strong> ' . esc_html( get_post_meta( $order_id, "_case_number", true ) ) . '<br>
                                <strong>'.esc_html__( "Payment date", "computer-repair-shop").' :</strong> '. esc_html( $payment_date ) . ' - ' . esc_html( $payment_time ) . '<br>' . wp_kses_post( $_transaction_id ) . '
								<strong>'.esc_html__( 'Status', 'computer-repair-shop' ).' :</strong> ' . esc_html( $payment_status ) . '
                            </td>
                        </tr>
                    </table>
                </td>
            </tr></table>';	

		if ( ! wc_rs_license_state() ) {
			$content .= esc_html__( 'Pro feature! Plugin activation required.', 'computer-repair-shop' );
		} else {
			$customerDetails = '';
			$customerDetails    = '<span>' . get_post_meta( $order_id, "_customer_label", true ) . '</span>';

			$customer_phone  	= get_user_meta( $customer_id, 'billing_phone', true);
			$customer_address 	= get_user_meta( $customer_id, 'billing_address_1', true);
			$customer_city 		= get_user_meta( $customer_id, 'billing_city', true);
			$customer_zip		= get_user_meta( $customer_id, 'billing_postcode', true);
			$state		        = get_user_meta( $customer_id, 'billing_state', true);
			$country		    = get_user_meta( $customer_id, 'billing_country', true);
			$customer_company	= get_user_meta( $customer_id, 'billing_company', true);
			$billing_tax	    = get_user_meta( $customer_id, 'billing_tax', true);

			$customerDetails .= ( ! empty( $customer_company ) ) ? '<strong>' . esc_html__( 'Company', 'computer-repair-shop' ) . ' : </strong><span>' . $customer_company . '</span>' : '';
			$customerDetails .= ( ! empty( $billing_tax ) ) ? '<strong>' . esc_html__( 'Tax ID', 'computer-repair-shop' ) . ' : </strong><span>' . $billing_tax . '</span>' : '';

			if ( ! empty( $customer_zip ) || ! empty( $customer_city ) || ! empty( $customer_address ) ) {
				$customerDetails .= "<strong>".esc_html__("Address", "computer-repair-shop")." :</strong><span>";

				$customerDetails .= ! empty( $customer_address ) ? $customer_address.", " : " ";
				$customerDetails .= ! empty( $customer_city ) ? ", ".$customer_city.", " : " ";
				$customerDetails .= ! empty( $customer_zip ) ? $customer_zip.", " : " ";
				$customerDetails .= ! empty( $state ) ? $state.", " : " ";
				$customerDetails .= ! empty( $country ) ? $country : " ";
				$customerDetails .= '</span>';
			}
			if ( ! empty( $customer_phone )) {
				$customerDetails .= "<strong>".esc_html__("Phone", "computer-repair-shop")." :</strong><span>".$customer_phone . '</span>';	
			}
			if ( ! empty( $user_email ) ) {
				$customerDetails .= "<strong>".esc_html__("Email", "computer-repair-shop")." :</strong><span>".$user_email . '</span>';	
			}
			
			$paymentNote = $_payment_ob->note;
			$paymentAmount = $_payment_ob->amount;
			$paymentMethod = $_payment_ob->method;
			$paymentReceiver = $_payment_ob->receiver_id;

			$receiver 	 	= get_user_by( 'ID', $paymentReceiver );
			$receiver_name	= $receiver->first_name;
			$receiver_name	.= ' ' . $receiver->last_name;

			$content .= '<table class="paymentRecieptTable">';
			$content .= '<tr>';
			$content .= '<td colspan="6">
							<p>
								<strong>'. esc_html__( 'Received From', 'computer-repair-shop' ) .' :</strong>
								'. wp_kses_post( $customerDetails ) .'
							</p>
						</td>';
			$content .= '</tr>';
			$content .= '<tr>
							<td colspan="6">
								<p>
									<strong>'. esc_html__( 'Note', 'computer-repair-shop' ) .':</strong>
									<span>'. wp_kses_post( $paymentNote ) .'</span>
								</p>
							</td>
						</tr>';
			$content .= '<tr>
						<td colspan="2">
							<p>
								<strong>'. esc_html__( 'Amount', 'computer-repair-shop' ) .':</strong>
								<span>'. esc_html( wc_cr_currency_format( $paymentAmount ) ) .'</span>
							</p>
						</td>
						<td colspan="2" style="text-align:left;">
							<p>
								<strong>'. esc_html__( 'Method', 'computer-repair-shop' ) .':</strong>
								<span>'. esc_html( ucfirst( $paymentMethod ) ) .'</span>
							</p>
						</td>
						<td colspan="2">
							<p>
								<strong>'. esc_html__( 'Received By', 'computer-repair-shop' ) .':</strong>
								<span>' . esc_html( $receiver_name ) . '</span>
							</p>
						</td>
					</tr>';
			$content .= '</table>';
		}

        $content .= "<p class='text-center signatureblock'>";
        $content .= ( ! empty( get_option( 'wc_rb_io_thanks_msg' ) ) ) ? get_option( 'wc_rb_io_thanks_msg' ) : esc_html__( 'Thanks for your business!', 'computer-repair-shop' );
        $content .= "</p>";

        if( isset( $_GET['email'] ) && $_GET['email'] == 'reciept_email' ){
            $content .= '<button id="btnPrint" class="hidden-print button button-primary">' . esc_html__( 'Print', 'computer-repair-shop' ) . '</button>';
        }
        $content .= '</div>';
        
        return $content;
	}
}