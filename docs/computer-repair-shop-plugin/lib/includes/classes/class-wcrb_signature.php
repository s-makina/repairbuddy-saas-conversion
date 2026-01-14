<?php
/**
 * Digital Signature Workflow
 * RepairBuddy's feature to let customers sign their job in different job statuses.
 *
 * @package computer-repair-shop
 * @version 4.1111
 */

defined( 'ABSPATH' ) || exit;

class WCRB_SIGNATURE_WORKFLOW {

	private static $instance = NULL;

	static public function getInstance() {
		if ( self::$instance === NULL )
            self::$instance = new self();
		return self::$instance;
	}

	private $TABID 		   = "wcrb_signature_workflow";
    private $success_class = 'signature_workflow_msg';

    function __construct() {
        add_action( 'wc_rb_settings_tab_menu_item',           [ $this, 'add_tab_in_settings_menu' ], 10, 2 );
        add_action( 'wc_rb_settings_tab_body',                [ $this, 'add_tab_in_settings_body' ], 10, 2 );
		add_action( 'wp_ajax_wcrb_update_signature_settings', [ $this, 'wcrb_update_settings' ] );

		add_action( 'wp_ajax_wc_upload_and_save_signature', [ $this, 'wc_upload_and_save_signature_handler' ] );
		add_action( 'wp_ajax_nopriv_wc_upload_and_save_signature', [ $this, 'wc_upload_and_save_signature_handler' ] );
    }

    function add_tab_in_settings_menu() {
        $active = '';

        $menu_output = '<li class="tabs-title' . esc_attr($active) . '" role="presentation">';
        $menu_output .= '<a href="#' . esc_attr( $this->TABID ) . '" role="tab" aria-controls="' . esc_attr( $this->TABID ) . '" aria-selected="true" id="' . esc_attr( $this->TABID ) . '-label">';
        $menu_output .= '<h2>' . esc_html__( 'Signature Workflow', 'computer-repair-shop' ) . '</h2>';
        $menu_output .=	'</a>';
        $menu_output .= '</li>';

        echo wp_kses_post( $menu_output );
    }

    function add_tab_in_settings_body() {
        $active = '';
		
		$setting_body = '<div class="tabs-panel team-wrap' . esc_attr( $active ) . '" 
        id="' . esc_attr( $this->TABID ) . '" 
        role="tabpanel" 
        aria-hidden="true" 
        aria-labelledby="' . esc_attr( $this->TABID ) . '-label">';

		$setting_body .= '<div class="wc-rb-manage-devices">';
		$setting_body .= '<h2>' . esc_html__( 'Digital Signature Workflow', 'computer-repair-shop' ) . '</h2>';
				
		$setting_body .= '<form data-async data-abide class="needs-validation" novalidate method="post" data-success-class=".'. esc_html( $this->success_class ) .'">';

		$setting_body .= '<div class="wc-rb-grey-bg-box">';
        $setting_body .= '<h3 class="mt-4 mb-3 border-bottom pb-2">' . esc_html__( 'Pickup Signature', 'computer-repair-shop' ) . '</h3>';

        $setting_body .= '<table class="form-table border"><tbody>';
		// ================== PICKUP SIGNATURE SECTION ==================

        $wcrb_pickup_signature_status = get_option( 'wcrb_pickup_signature_status' );
		$wcrb_pickup_signature_status = ( $wcrb_pickup_signature_status == 'on' ) ? 'checked="checked"' : '';
		
		$setting_body .= '<tr>
							<th scope="row">
								<label for="wcrb_pickup_signature_status">
									' . esc_html__( 'Pickup signature status', 'computer-repair-shop' ) . '
								</label>
							</th>';
		$setting_body .= '<td>';
		$setting_body .= '<input type="checkbox" ' . esc_html( $wcrb_pickup_signature_status ) . ' name="wcrb_pickup_signature_status" id="wcrb_pickup_signature_status" />';
		$setting_body .= '<label for="wcrb_pickup_signature_status">';
		$setting_body .= esc_html__( 'Enable pickup signature request.', 'computer-repair-shop' );
		$setting_body .= '</label></td></tr>';

		// Pickup - Send Signature request when job enters to status
		$pickup_signature_status = get_option( 'wcrb_pickup_signature_job_status', '' );
		$setting_body .= '<tr>
							<th scope="row">
								<label for="wcrb_pickup_signature_job_status">
									' . esc_html__( 'Send Signature request when job enters to status?', 'computer-repair-shop' ) . '
								</label>
							</th>';
		$setting_body .= '<td>';
		$setting_body .= '<select name="wcrb_pickup_signature_job_status" class="form-control" id="wcrb_pickup_signature_job_status">';
		$setting_body .= '<option value="">' . esc_html__( 'Select job status', 'computer-repair-shop' ) . '</option>';
		$setting_body .= wc_generate_status_options( $pickup_signature_status );
		$setting_body .= '</select>';
		$setting_body .= '<label>' . esc_html__( 'Select the job status when pickup signature request should be sent.', 'computer-repair-shop' ) . '</label>';
		$setting_body .= '</td></tr>';

		// Pickup - Email Subject
		$pickup_email_subject = get_option( 'wcrb_pickup_signature_email_subject', 'Signature Required: Device Pickup Authorization' );
		$setting_body .= '<tr>
							<th scope="row">
								<label for="wcrb_pickup_signature_email_subject">
									' . esc_html__( 'Email Subject', 'computer-repair-shop' ) . '
								</label>
							</th>';
		$setting_body .= '<td>';
		$setting_body .= '<input type="text" class="regular-text" name="wcrb_pickup_signature_email_subject" value="' . esc_html( $pickup_email_subject ) . '" id="wcrb_pickup_signature_email_subject" />';
		$setting_body .= '</td></tr>';

		// Pickup - Email Template
		$pickup_email_template = $this->get_pickup_email_template();
		
		$setting_body .= '<tr>
							<th scope="row">
								<label for="wcrb_pickup_signature_email_template">
									' . esc_html__( 'Email Template', 'computer-repair-shop' ) . '
								</label>
							</th>';
		$setting_body .= '<td>';
		$setting_body .= esc_html__( 'Available keywords', 'computer-repair-shop' ) . '<br>';
		$setting_body .= '{{pickup_signature_url}} {{job_id}} {{customer_device_label}} {{case_number}} {{customer_full_name}} {{order_invoice_details}}<br>';
		$setting_body .= '<textarea rows="6" name="wcrb_pickup_signature_email_template" id="wcrb_pickup_signature_email_template">' . esc_textarea( $pickup_email_template ) . '</textarea>';
		$setting_body .= '</td></tr>';

		// Pickup - SMS Text
		$pickup_sms_text = $this->get_pickup_sms_template();
		
		$setting_body .= '<tr>
							<th scope="row">
								<label for="wcrb_pickup_signature_sms_text">
									' . esc_html__( 'SMS Text', 'computer-repair-shop' ) . '
								</label>
							</th>';
		$setting_body .= '<td>';
		$setting_body .= esc_html__( 'Available keywords', 'computer-repair-shop' ) . '<br>';
		$setting_body .= '{{pickup_signature_url}} {{job_id}} {{customer_device_label}} {{case_number}} {{customer_full_name}} {{order_invoice_details}}<br>';
		$setting_body .= '<textarea rows="3" name="wcrb_pickup_signature_sms_text" id="wcrb_pickup_signature_sms_text">' . esc_textarea( $pickup_sms_text ) . '</textarea>';
		$setting_body .= '</td></tr>';

		// Pickup - Change Job status after signature submission
		$pickup_after_signature_status = get_option( 'wcrb_pickup_after_signature_status', '' );
		$setting_body .= '<tr>
							<th scope="row">
								<label for="wcrb_pickup_after_signature_status">
									' . esc_html__( 'Change Job status after signature submission', 'computer-repair-shop' ) . '
								</label>
							</th>';
		$setting_body .= '<td>';
		$setting_body .= '<select name="wcrb_pickup_after_signature_status" class="form-control" id="wcrb_pickup_after_signature_status">';
		$setting_body .= '<option value="">' . esc_html__( 'Select job status', 'computer-repair-shop' ) . '</option>';
		$setting_body .= wc_generate_status_options( $pickup_after_signature_status );
		$setting_body .= '</select>';
		$setting_body .= '<label>' . esc_html__( 'Select the status to change to after pickup signature is submitted.', 'computer-repair-shop' ) . '</label>';
		$setting_body .= '</td></tr>';
        $setting_body .= '</tbody></table>';

		$setting_body .= '</div><!-- Grey box -->';

		// ================== DELIVERY SIGNATURE SECTION ==================
		$setting_body .= '<div class="wc-rb-grey-bg-box">';
        $setting_body .= '<h3 class="mt-5 mb-3 border-bottom pb-2">' . esc_html__( 'Delivery Signature', 'computer-repair-shop' ) . '</h3>';

        $setting_body .= '<table class="form-table border"><tbody>';

        $wcrb_delivery_signature_status = get_option( 'wcrb_delivery_signature_status' );
		$wcrb_delivery_signature_status = ( $wcrb_delivery_signature_status == 'on' ) ? 'checked="checked"' : '';
		
		$setting_body .= '<tr>
							<th scope="row">
								<label for="wcrb_delivery_signature_status">
									' . esc_html__( 'Delivery signature status', 'computer-repair-shop' ) . '
								</label>
							</th>';
		$setting_body .= '<td>';
		$setting_body .= '<input type="checkbox" ' . esc_html( $wcrb_delivery_signature_status ) . ' name="wcrb_delivery_signature_status" id="wcrb_delivery_signature_status" />';
		$setting_body .= '<label for="wcrb_delivery_signature_status">';
		$setting_body .= esc_html__( 'Enable delivery signature request.', 'computer-repair-shop' );
		$setting_body .= '</label></td></tr>';

		// Delivery - Send Signature request when job enters to status
		$delivery_signature_status = get_option( 'wcrb_delivery_signature_job_status', '' );
		$setting_body .= '<tr>
							<th scope="row">
								<label for="wcrb_delivery_signature_job_status">
									' . esc_html__( 'Send Signature request when job enters to status?', 'computer-repair-shop' ) . '
								</label>
							</th>';
		$setting_body .= '<td>';
		$setting_body .= '<select name="wcrb_delivery_signature_job_status" class="form-control" id="wcrb_delivery_signature_job_status">';
		$setting_body .= '<option value="">' . esc_html__( 'Select job status', 'computer-repair-shop' ) . '</option>';
		$setting_body .= wc_generate_status_options( $delivery_signature_status );
		$setting_body .= '</select>';
		$setting_body .= '<label>' . esc_html__( 'Select the job status when delivery signature request should be sent.', 'computer-repair-shop' ) . '</label>';
		$setting_body .= '</td></tr>';

		// Delivery - Email Subject
		$delivery_email_subject = get_option( 'wcrb_delivery_signature_email_subject', 'Signature Required: Device Delivery Confirmation' );
		$setting_body .= '<tr>
							<th scope="row">
								<label for="wcrb_delivery_signature_email_subject">
									' . esc_html__( 'Email Subject', 'computer-repair-shop' ) . '
								</label>
							</th>';
		$setting_body .= '<td>';
		$setting_body .= '<input type="text" class="regular-text" name="wcrb_delivery_signature_email_subject" value="' . esc_html( $delivery_email_subject ) . '" id="wcrb_delivery_signature_email_subject" />';
		$setting_body .= '</td></tr>';

		// Delivery - Email Template
		$delivery_email_template = $this->get_delivery_email_template();
		
		$setting_body .= '<tr>
							<th scope="row">
								<label for="wcrb_delivery_signature_email_template">
									' . esc_html__( 'Email Template', 'computer-repair-shop' ) . '
								</label>
							</th>';
		$setting_body .= '<td>';
		$setting_body .= esc_html__( 'Available keywords', 'computer-repair-shop' ) . '<br>';
		$setting_body .= '{{delivery_signature_url}} {{job_id}} {{customer_device_label}} {{case_number}} {{customer_full_name}} {{order_invoice_details}}<br>';
		$setting_body .= '<textarea rows="6" name="wcrb_delivery_signature_email_template" id="wcrb_delivery_signature_email_template">' . esc_textarea( $delivery_email_template ) . '</textarea>';
		$setting_body .= '</td></tr>';

		// Delivery - SMS Text
		$delivery_sms_text = $this->get_delivery_sms_template();
		
		$setting_body .= '<tr>
							<th scope="row">
								<label for="wcrb_delivery_signature_sms_text">
									' . esc_html__( 'SMS Text', 'computer-repair-shop' ) . '
								</label>
							</th>';
		$setting_body .= '<td>';
		$setting_body .= esc_html__( 'Available keywords', 'computer-repair-shop' ) . '<br>';
		$setting_body .= '{{delivery_signature_url}} {{job_id}} {{customer_device_label}} {{case_number}} {{customer_full_name}} {{order_invoice_details}}<br>';
		$setting_body .= '<textarea rows="3" name="wcrb_delivery_signature_sms_text" id="wcrb_delivery_signature_sms_text">' . esc_textarea( $delivery_sms_text ) . '</textarea>';
		$setting_body .= '</td></tr>';

		// Delivery - Change Job status after signature submission
		$delivery_after_signature_status = get_option( 'wcrb_delivery_after_signature_status', '' );
		$setting_body .= '<tr>
							<th scope="row">
								<label for="wcrb_delivery_after_signature_status">
									' . esc_html__( 'Change Job status after signature submission', 'computer-repair-shop' ) . '
								</label>
							</th>';
		$setting_body .= '<td>';
		$setting_body .= '<select name="wcrb_delivery_after_signature_status" class="form-control" id="wcrb_delivery_after_signature_status">';
		$setting_body .= '<option value="">' . esc_html__( 'Select job status', 'computer-repair-shop' ) . '</option>';
		$setting_body .= wc_generate_status_options( $delivery_after_signature_status );
		$setting_body .= '</select>';
		$setting_body .= '<label>' . esc_html__( 'Select the status to change to after delivery signature is submitted.', 'computer-repair-shop' ) . '</label>';
		$setting_body .= '</td></tr>';

		$setting_body .= '</tbody></table>';

		$setting_body .= '</div><!-- Grey Box /-->';

		$setting_body .= '<input type="hidden" name="form_type" value="wcrb_update_settings_form" />';
		$setting_body .= '<input type="hidden" name="form_action" value="wcrb_update_signature_settings" />';
		
		$setting_body .= wp_nonce_field( 'wcrb_nonce_reviews', 'wcrb_nonce_reviews_field', true, false );

		$setting_body .= '<button type="submit" class="button button-primary" data-type="rbssubmitdevices">' . esc_html__( 'Update Options', 'computer-repair-shop' ) . '</button></form>';

		$setting_body .= '<div class="'. esc_html( $this->success_class ) .'"></div>';
		$setting_body .= '</div><!-- wc rb Devices /-->';

		$WCRB_SUPPORT_DOCS = WCRB_SUPPORT_DOCS::getInstance();
		$setting_body .= $WCRB_SUPPORT_DOCS->return_helpful_link_signature_workflow();

		$setting_body .= '</div><!-- Tabs Panel /-->';

		$allowedHTML = ( function_exists( 'wc_return_allowed_tags' ) ) ? wc_return_allowed_tags() : '';
		echo wp_kses( $setting_body, $allowedHTML );
    }

    function wcrb_update_settings() {
        $message = '';
		$success = 'NO';

		$form_type = ( isset( $_POST['form_type'] ) ) ? sanitize_text_field( $_POST['form_type'] ) : '';
				
		if ( 
			isset( $_POST['wcrb_nonce_reviews_field'] ) 
			&& wp_verify_nonce( $_POST['wcrb_nonce_reviews_field'], 'wcrb_nonce_reviews' ) 
			&& $form_type == 'wcrb_update_settings_form' ) {

			$submit_arr = array(
				// Pickup Signature
                'wcrb_pickup_signature_status',
				'wcrb_pickup_signature_job_status',
				'wcrb_pickup_signature_email_subject',
				'wcrb_pickup_signature_email_template',
				'wcrb_pickup_signature_sms_text',
				'wcrb_pickup_after_signature_status',
				
				// Delivery Signature
                'wcrb_delivery_signature_status',
				'wcrb_delivery_signature_job_status',
				'wcrb_delivery_signature_email_subject',
				'wcrb_delivery_signature_email_template',
				'wcrb_delivery_signature_sms_text',
				'wcrb_delivery_after_signature_status',
			);

			foreach( $submit_arr as $field ) {

				if ( $field == 'wcrb_pickup_signature_email_template' || $field == 'wcrb_delivery_signature_email_template' ) {
					$_field_value = ( isset( $_POST[$field] ) && ! empty( $_POST[$field] ) ) ? sanitize_textarea_field( $_POST[$field] ) : '';
				} else {
					$_field_value = ( isset( $_POST[$field] ) && ! empty( $_POST[$field] ) ) ? sanitize_text_field( $_POST[$field] ) : '';
				}
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
    } // End wcrb_update_settings

	function get_pickup_sms_template() {
		$pickup_sms_text = get_option( 'wcrb_pickup_signature_sms_text', '' );
		if ( empty( $pickup_sms_text ) ) {
			$pickup_sms_text = 'Hello {{customer_full_name}}, please sign pickup authorization for your device {{customer_device_label}}. Signature link: {{signature_url}}';
		}
		return $pickup_sms_text;
	}

	function get_delivery_sms_template() {
		$delivery_sms_text = get_option( 'wcrb_delivery_signature_sms_text', '' );
		if ( empty( $delivery_sms_text ) ) {
			$delivery_sms_text = 'Hello {{customer_full_name}}, please sign delivery confirmation for your device {{customer_device_label}}. Signature link: {{signature_url}}';
		}
		return $delivery_sms_text;
	}

	function get_pickup_email_template() {
		$pickup_email_template = get_option( 'wcrb_pickup_signature_email_template', '' );
		if ( empty( $pickup_email_template ) ) {
			$pickup_email_template = 'Hello {{customer_full_name}},

Please sign to authorize the pickup of your device: {{customer_device_label}}

Job ID: {{job_id}}
Case Number: {{case_number}}

Please click the link below to sign the pickup authorization:
{{pickup_signature_url}}

Thank you,
' . get_bloginfo( 'name' );
		}

		return $pickup_email_template;
	}

	function get_delivery_email_template() {
		$delivery_email_template = get_option( 'wcrb_delivery_signature_email_template', '' );
		if ( empty( $delivery_email_template ) ) {
			$delivery_email_template = 'Hello {{customer_full_name}},

Please sign to confirm the delivery of your repaired device: {{customer_device_label}}

Job ID: {{job_id}}
Case Number: {{case_number}}

Please click the link below to sign the delivery confirmation:
{{delivery_signature_url}}

Thank you,
' . get_bloginfo( 'name' );
		}
		
		return $delivery_email_template;
	}

    function send_signature_request( $job_status, $job_id ) {
        if ( empty( $job_status ) || empty( $job_id ) ) {
            return;
        } 

        $wcrb_delivery_signature_status = get_option( 'wcrb_delivery_signature_status' );
        $wcrb_pickup_signature_status   = get_option( 'wcrb_pickup_signature_status' );
        
        if ( $wcrb_pickup_signature_status != 'on' && $wcrb_delivery_signature_status != 'on' ) {
            return;
        }

        $delivery_signature_status = get_option( 'wcrb_delivery_signature_job_status', '' );
        $pickup_signature_status   = get_option( 'wcrb_pickup_signature_job_status', '' );

        $request_type = '';
        if ( $job_status == $delivery_signature_status  ) {
            $request_type = 'delivery_signature';
        } elseif ( $job_status == $pickup_signature_status ) {
            $request_type = 'pickup_signature';
        }

        if ( empty( $request_type ) ) {
            return;
        }

        if ( $request_type == 'pickup_signature' ) {
			//Let's send pickup signature request
			$this->send_pickup_signature_request( $job_id );
		} elseif ( $request_type == 'delivery_signature' ) {
			//Let's send Delivery Signature request
			$this->send_delivery_signature_request( $job_id );
		}
    }

	function send_pickup_signature_request( $job_id ) {
		global $WCRB_EMAILS, $OBJ_SMS_SYSTEM;

		if ( empty( $job_id ) ) {
			return;
		}

		// Get email recipient (customer)
		$customer_id = get_post_meta( $job_id, '_customer', true );
		if ( empty( $customer_id ) ) {
			return;
		}
		
		$customer = get_user_by( 'id', $customer_id );
		if ( ! $customer ) {
			return;
		}
		
		$mailto 		= $customer->user_email;
		$customer_phone = get_user_meta( $customer_id, 'billing_phone', true );

		if ( ! empty( $mailto ) ) {
			//Process email here. 
			$subject = get_option( 'wcrb_pickup_signature_email_subject', 'Signature Required: Device Pickup Authorization' ) . ' | ' . esc_html( get_option( 'blogname' ) );
			$email_message = $this->get_pickup_email_template();

			if ( empty( $email_message ) ) {
				return;
			}

			$body_message = $WCRB_EMAILS->return_body_replacing_keywords( array( 'job_id' => $job_id, 'email_message' => $email_message) );
			$WCRB_EMAILS->send_email( $mailto, $subject, $body_message, '' );

			//Add Job history
			$args = array(
				"job_id" 		=> $job_id, 
				"name" 			=> esc_html__( 'Pickup signature request sent to', 'computer-repair-shop' ), 
				"type" 			=> 'public', 
				"field" 		=> '_signature_email_to_customer', 
				"change_detail" => 'To : ' . $mailto
			);
			$WCRB_JOB_HISTORY_LOGS = WCRB_JOB_HISTORY_LOGS::getInstance();
			$WCRB_JOB_HISTORY_LOGS->wc_record_job_history( $args );
		}

		$is_sms_active = get_option( 'wc_rb_sms_active' );
		if ( $is_sms_active == 'YES' && ! empty( $customer_phone )  ) {
			$message = $this->get_pickup_sms_template();
			$message = $WCRB_EMAILS->return_body_replacing_keywords( array( 'job_id' => $job_id, 'email_message' => $message) );
			$OBJ_SMS_SYSTEM->send_sms( $customer_phone, $message, $job_id );

			//Add Job history
			$args = array(
				"job_id" 		=> $job_id, 
				"name" 			=> esc_html__( 'Pickup signature request sent to', 'computer-repair-shop' ), 
				"type" 			=> 'public', 
				"field" 		=> '_signature_sms_to_customer', 
				"change_detail" => 'To : ' . $customer_phone
			);
			$WCRB_JOB_HISTORY_LOGS = WCRB_JOB_HISTORY_LOGS::getInstance();
			$WCRB_JOB_HISTORY_LOGS->wc_record_job_history( $args );
		}
	}

	function send_delivery_signature_request( $job_id ) {
		global $WCRB_EMAILS, $OBJ_SMS_SYSTEM;

		if ( empty( $job_id ) ) {
			return;
		}

		// Get email recipient (customer)
		$customer_id = get_post_meta( $job_id, '_customer', true );
		if ( empty( $customer_id ) ) {
			return;
		}
		
		$customer = get_user_by( 'id', $customer_id );
		if ( ! $customer ) {
			return;
		}
		
		$mailto 		= $customer->user_email;
		$customer_phone = get_user_meta( $customer_id, 'billing_phone', true );

		if ( ! empty( $mailto ) ) {
			//Process email here. 
			$subject = get_option( 'wcrb_delivery_signature_email_subject', 'Signature Required: Device Delivery Confirmation' ) . ' | ' . esc_html( get_option( 'blogname' ) );
			$email_message = $this->get_delivery_email_template();

			if ( empty( $email_message ) ) {
				return;
			}

			$body_message = $WCRB_EMAILS->return_body_replacing_keywords( array( 'job_id' => $job_id, 'email_message' => $email_message) );
			$WCRB_EMAILS->send_email( $mailto, $subject, $body_message, '' );

			//Add Job history
			$args = array(
				"job_id" 		=> $job_id, 
				"name" 			=> esc_html__( 'Delivery signature request sent to', 'computer-repair-shop' ), 
				"type" 			=> 'public', 
				"field" 		=> '_signature_email_to_customer', 
				"change_detail" => 'To : ' . $mailto
			);
			$WCRB_JOB_HISTORY_LOGS = WCRB_JOB_HISTORY_LOGS::getInstance();
			$WCRB_JOB_HISTORY_LOGS->wc_record_job_history( $args );
		}

		$is_sms_active = get_option( 'wc_rb_sms_active' );
		if ( $is_sms_active == 'YES' && ! empty( $customer_phone )  ) {
			$message = $this->get_delivery_sms_template();
			$message = $WCRB_EMAILS->return_body_replacing_keywords( array( 'job_id' => $job_id, 'email_message' => $message) );
			$OBJ_SMS_SYSTEM->send_sms( $customer_phone, $message, $job_id );

			//Add Job history
			$args = array(
				"job_id" 		=> $job_id, 
				"name" 			=> esc_html__( 'Delivery signature request sent to', 'computer-repair-shop' ), 
				"type" 			=> 'public', 
				"field" 		=> '_signature_sms_to_customer', 
				"change_detail" => 'To : ' . $customer_phone
			);
			$WCRB_JOB_HISTORY_LOGS = WCRB_JOB_HISTORY_LOGS::getInstance();
			$WCRB_JOB_HISTORY_LOGS->wc_record_job_history( $args );
		}
	}

	function wc_upload_and_save_signature_handler() {
		global $WCRB_EMAILS;

		$response = array('success' => false, 'message' => '', 'error' => '', 'data' => array());

		// 1. Verify nonce first
		if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'signature_upload_nonce')) {
			$response['error'] = esc_html__( "Security check failed", "computer-repair-shop" );
			wp_send_json($response);
			wp_die();
		}

		// 2. Validate required parameters
		$required_params = array('order_id', 'job_case_number', 'signature_label');
		foreach ($required_params as $param) {
			if (!isset($_POST[$param]) || empty($_POST[$param])) {
				$response['error'] = sprintf( esc_html__("Missing required parameter: %s", "computer-repair-shop"), $param );
				wp_send_json($response);
				wp_die();
			}
		}

		// 3. Get parameters
		$order_id         = intval($_POST['order_id']);
		$job_case_number  = sanitize_text_field($_POST['job_case_number']);
		$signature_label  = sanitize_text_field($_POST['signature_label']);
		$signature_type   = isset($_POST['signature_type']) ? sanitize_text_field($_POST['signature_type']) : 'normal';
		
		// 4. CRITICAL: Verify the verification code
		$verification_code = isset($_POST['verification']) ? sanitize_text_field($_POST['verification']) : '';
		
		if (empty($verification_code)) {
			$response['error'] = esc_html__("Missing verification code", "computer-repair-shop");
			wp_send_json($response);
			wp_die();
		}
		
		// Get the correct meta key based on signature type
		$meta_key = '';
		switch ($signature_type) {
			case 'pickup':
				$meta_key = '_wcrb_signature_pickup_signature_verification';
				break;
			case 'delivery':
				$meta_key = '_wcrb_signature_delivery_signature_verification';
				break;
			default:
				// For custom signature types
				$sanitized_label = sanitize_title($signature_label);
				$meta_key = '_wcrb_signature_' . $sanitized_label . '_verification';
		}
		
		// Get stored verification code
		$stored_code = get_post_meta($order_id, $meta_key, true);
		
		if (empty($stored_code)) {
			$response['error'] = esc_html__("No signature request found for this job", "computer-repair-shop");
			wp_send_json($response);
			wp_die();
		}
		
		// Verify the code matches
		if ($stored_code !== $verification_code) {
			$response['error'] = esc_html__("Invalid verification code", "computer-repair-shop");
			wp_send_json($response);
			wp_die();
		}
		
		// 5. Check if signature already submitted
		$completed = get_post_meta($order_id, $meta_key . '_completed', true);
		if (!empty($completed)) {
			$response['error'] = esc_html__("This signature has already been submitted", "computer-repair-shop");
			wp_send_json($response);
			wp_die();
		}
		
		// 6. Optional: Verify timestamp (expiration check)
		$timestamp = isset($_POST['timestamp']) ? intval($_POST['timestamp']) : 0;
		if ($timestamp > 0) {
			$expiration_days = 7; // URLs expire after 7 days
			$expiration_seconds = $expiration_days * 24 * 60 * 60;
			
			if ((time() - $timestamp) > $expiration_seconds) {
				$response['error'] = esc_html__("This signature link has expired", "computer-repair-shop");
				wp_send_json($response);
				wp_die();
			}
		}
		
		// 7. Additional verification: Check job exists and case number matches
		$jobs_manager = WCRB_JOBS_MANAGER::getInstance();
		$job_data     = $jobs_manager->get_job_display_data($order_id);
		
		if (empty($job_data)) {
			$response['error'] = esc_html__("Job not found", "computer-repair-shop");
			wp_send_json($response);
			wp_die();
		}
		
		// Verify case number matches
		$stored_case_number = get_post_meta($order_id, '_case_number', true);
		if ($job_case_number !== $stored_case_number) {
			$response['error'] = esc_html__("Case number mismatch", "computer-repair-shop");
			wp_send_json($response);
			wp_die();
		}
		
		// 8. Now handle file upload if present
		$file_url = '';
		if (isset($_FILES["signature_file"]) && $_FILES["signature_file"]["error"] == 0) {
			$upload_response = wc_upload_image_return_url($_FILES["signature_file"], 'reciepts');
			
			if (!empty($upload_response['error'])) {
				$response['error'] = $upload_response['error'];
				wp_send_json($response);
				wp_die();
			}
			
			if (!empty($upload_response['message'])) {
				$file_url = $upload_response['message'];
			}
		} else {
			$response['error'] = esc_html__("No signature data provided", "computer-repair-shop");
			wp_send_json($response);
			wp_die();
		}
		
		// Validate file URL
		if (empty($file_url) || !filter_var($file_url, FILTER_VALIDATE_URL)) {
			$response['error'] = esc_html__("Invalid file URL generated", "computer-repair-shop");
			wp_send_json($response);
			wp_die();
		}
		
		// 9. Mark signature as completed BEFORE processing to prevent race conditions
		update_post_meta($order_id, $meta_key . '_completed', current_time('mysql'));
		update_post_meta($order_id, $meta_key . '_completed_by_ip', $_SERVER['REMOTE_ADDR'] ?? '');
		update_post_meta($order_id, $meta_key . '_completed_at', time());
		update_post_meta($order_id, $meta_key . '_signature_file', $file_url);
		update_post_meta($order_id, $meta_key . '_signature_label', $signature_label);
		
		// 10. Save all signature data to order
		$ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
		$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
		
		$description = sprintf(
			esc_html__('Customer left signature, From IP: %s, User Agent: %s', 'computer-repair-shop'),
			$ip_address,
			substr($user_agent, 0, 100) // Store first 100 chars only
		);
		
		$customer_id = get_post_meta($order_id, '_customer', true);
		
		$_history_args = array();
		
		// Save Extra Field
		$user_id = (get_current_user_id()) ? get_current_user_id() : $customer_id;
		
		// Save Job log
		$arguments = array(
			'date'        => wp_date('Y-m-d H:i:s'),
			'label'       => esc_html($signature_label),
			'details'     => $file_url,
			'visibility'  => 'public',
			'type'        => 'signature',
			'description' => $description,
			'verified'    => true, // Add verification flag
		);
		wc_job_extra_items_add($arguments, $order_id);
		
		$_history_args[] = array(
			"user_id"      => $user_id,
			"job_id"       => $order_id,
			"name"         => esc_html($signature_label) . ' - ' . esc_html__('Verified Signature', 'computer-repair-shop'),
			"type"         => 'public',
			"field"        => '_signature_submission',
			"change_detail" => $file_url . ' (Verified: ' . $verification_code . ')'
		);
		
		// 11. Email admin
		$jobs_manager = WCRB_JOBS_MANAGER::getInstance();
		$job_data     = $jobs_manager->get_job_display_data($order_id);
		$_job_id      = (!empty($job_data['formatted_job_number'])) ? $job_data['formatted_job_number'] : $order_id;
		
		$email_body = '';
		$menu_name_p = get_option('blogname');
		$subject    = sprintf(esc_html__('Verified Signature Received on Case # %s', 'computer-repair-shop'), $job_case_number) . '! | ' . $menu_name_p;
		$admin_email = (!empty(get_option('admin_email'))) ? get_option('admin_email') : '';
		
		$email_body = 'Hello,

You have received a VERIFIED signature on job # ' . $_job_id . ' and case # ' . $job_case_number . '

For request labeled as {' . $signature_label . '}

Signature Type: ' . $signature_type . '
Verification Code: ' . $verification_code . '
IP Address: ' . $ip_address . '
Timestamp: ' . current_time('mysql') . '

Signature File: ' . $file_url . '

This signature has been verified and is valid.

Thank you!';
		
		if (!empty($subject) || !empty($email_body) || !empty($admin_email)) {
			$email_body = nl2br($email_body);
			$WCRB_EMAILS->send_email($admin_email, $subject, $email_body, '');
			
			$_history_args[] = array(
				"user_id"      => $user_id,
				"job_id"       => $order_id,
				"name"         => esc_html__('Verified signature notification sent to', 'computer-repair-shop'),
				"type"         => 'private',
				"field"        => '_signature_notification',
				"change_detail" => $admin_email
			);
		}
		
		// 12. Change job status (if applicable)
		if (isset($_POST['signature_type'])) {
			$signature_type = sanitize_text_field($_POST['signature_type']);
			$cansign = $mssg = $new_job_status = '';
			$old_job_status = get_post_meta($order_id, "_wc_order_status", true);
			
			if ($signature_type == 'pickup') {
				$pickup_status = get_option('wcrb_pickup_signature_job_status');
				
				if ($pickup_status == $old_job_status) {
					$cansign = 'YES';
					$new_job_status = get_option('wcrb_pickup_after_signature_status');
				}
			}
			if ($signature_type == 'delivery') {
				$delivery_status = get_option('wcrb_delivery_signature_job_status');
				
				if ($delivery_status == $old_job_status) {
					$cansign = 'YES';
					$new_job_status = get_option('wcrb_delivery_after_signature_status');
				}
			}
			
			if (!empty($new_job_status)) {
				update_post_meta($order_id, '_wc_order_status', $new_job_status);
				$change_detail = wc_return_status_name($new_job_status);
				update_post_meta($order_id, '_wc_order_status_label', $new_job_status);
				
				$_history_args[] = array(
					"user_id"      => $user_id,
					"job_id"       => $order_id,
					"name"         => esc_html__("Order status modified to", "computer-repair-shop"),
					"type"         => 'public',
					"field"        => '_wc_order_status',
					"change_detail" => $change_detail
				);
				
				if (($old_job_status != $new_job_status) || empty($old_job_status)) {
					global $OBJ_SMS_SYSTEM;
					$wc_send_cr_notice = get_option('wc_job_status_cr_notice');
					
					$is_sms_active = get_option('wc_rb_sms_active');
					if ($is_sms_active == 'YES') {
						$OBJ_SMS_SYSTEM->wc_rb_status_send_the_sms($order_id, $new_job_status);
					}
					
					if (function_exists('rb_qb_update_invoice_status')) {
						rb_qb_update_invoice_status($old_job_status, $new_job_status, $order_id);
					}
					
					if ($wc_send_cr_notice == 'on') {
						$_GET['wc_case_number'] = sanitize_text_field($_POST['case_number']);
						
						wc_cr_send_customer_update_email($order_id);
					}
					
					global $WCRB_WOO_FUNCTIONS_OBJ;
					$WCRB_WOO_FUNCTIONS_OBJ->wc_update_woo_stock_if_enabled($order_id, $new_job_status);
				}
			}
		}
		
		// 13. Record all history logs
		if (isset($_history_args) && !empty($_history_args)) {
			foreach ($_history_args as $args) {
				$WCRB_JOB_HISTORY_LOGS = WCRB_JOB_HISTORY_LOGS::getInstance();
				$WCRB_JOB_HISTORY_LOGS->wc_record_job_history($args);
			}
		}
		
		// 14. Return success
		$myaccountpage = get_option('wc_rb_my_account_page_id');
		$myaccountpage = (!empty($myaccountpage)) ? get_the_permalink($myaccountpage) : home_url();
		
		$response['success'] = true;
		$response['message'] = esc_html__("Signature saved and verified successfully", "computer-repair-shop");
		$response['data'] = array(
			'file_url' => $file_url,
			'redirect' => add_query_arg(array(
				'signature_success' => '1',
				'order_id' => $order_id
			), $myaccountpage)
		);
		
		wp_send_json($response);
		wp_die();
	} // End wc_upload_and_save_signature_handler()

	function wcrb_generate_signature_slug($signature_label, $type = 'underscore') {
		// Convert to lowercase first
		$signature_label = strtolower(trim($signature_label));
		
		switch($type) {
			case 'underscore':
				// Replace spaces with underscores and remove special chars
				$slug = str_replace(' ', '_', $signature_label);
				$slug = preg_replace('/[^a-z0-9_]/', '', $slug);
				break;
				
			case 'dash':
				// Replace spaces with dashes
				$slug = str_replace(' ', '-', $signature_label);
				$slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
				break;
				
			case 'camel':
				// Convert to camelCase (first letter lowercase)
				$words = explode(' ', $signature_label);
				$slug = '';
				foreach($words as $index => $word) {
					$word = preg_replace('/[^a-z0-9]/', '', $word);
					if($index === 0) {
						$slug .= $word;
					} else {
						$slug .= ucfirst($word);
					}
				}
				break;
				
			default:
				$slug = preg_replace('/[^a-z0-9]/', '_', $signature_label);
		}
		
		// Ensure it's not empty
		if(empty($slug)) {
			$slug = 'signature_' . time();
		}
		
		return $slug;
	}

	/**
	 * Generate signature URL with verification code
	 * 
	 * @param string $_signature_label The label for the signature (e.g., 'Pickup', 'Delivery', 'Customer Approval')
	 * @param string $_signature_type The type of signature (e.g., 'pickup', 'delivery', 'custom')
	 * @param int $_job_id The job/order ID
	 * @param string $base_url Optional base URL. If not provided, uses main page URL.
	 * 
	 * @return string|false The generated signature URL or false on failure
	 */
	function wcrb_generate_signature_url_with_verification( $_signature_label, $_signature_type, $_job_id, $base_url = '' ) {
		// Get the job case number
		$job_case_number = get_post_meta( $_job_id, '_case_number', true );
		if ( empty( $job_case_number ) ) {
			return false;
		}
		
		// Get base URL if not provided
		if ( empty( $base_url ) ) {
			$_mainpage = get_option( 'wc_rb_my_account_page_id' );
			if ( empty( $_mainpage ) ) {
				return false;
			}
			$base_url = get_the_permalink( $_mainpage );
		}
		
		// Determine meta key based on signature type
		$meta_key = '';
		switch ( $_signature_type ) {
			case 'pickup':
				$meta_key = '_wcrb_signature_pickup_signature_verification';
				break;
			case 'delivery':
				$meta_key = '_wcrb_signature_delivery_signature_verification';
				break;
			default:
				$sanitized_label = sanitize_title($_signature_label);
				$meta_key = '_wcrb_signature_' . $sanitized_label . '_verification';
		}
		
		// Check if verification code already exists
		$existing_code = get_post_meta($_job_id, $meta_key, true);
		$completed = get_post_meta($_job_id, $meta_key . '_completed', true);
		
		// If code exists and signature is NOT completed, return existing URL
		if (!empty($existing_code) && empty($completed)) {
			// Reconstruct the URL
			$signature_url = add_query_arg(array(
				'screen' => 'signature_request',
				'job_id' => $_job_id,
				'case_number' => $job_case_number,
				'signature_label' => urlencode($_signature_label),
				'signature_type' => $_signature_type,
				'verification' => $existing_code,
				'timestamp' => get_post_meta($_job_id, $meta_key . '_timestamp', true)
			), $base_url);
			
			return $signature_url;
		}
		
		// Generate a new verification code
		$verification_code = wp_generate_password(12, false, false);
		
		// Save the verification code to job meta
		update_post_meta($_job_id, $meta_key, $verification_code);
		
		// Also save the signature type and label for reference
		update_post_meta($_job_id, $meta_key . '_type', $_signature_type);
		update_post_meta($_job_id, $meta_key . '_label', $_signature_label);
		update_post_meta($_job_id, $meta_key . '_generated', current_time('mysql'));
		update_post_meta($_job_id, $meta_key . '_timestamp', time()); // Save timestamp
		
		// Build the signature URL with verification code
		$signature_url = add_query_arg(array(
			'screen'        => 'signature_request',
			'job_id'        => $_job_id,
			'case_number'   => $job_case_number,
			'signature_label' => urlencode($_signature_label),
			'signature_type' => $_signature_type,
			'verification'  => $verification_code,
			'timestamp'     => time() // Add timestamp for additional security
		), $base_url);
		
		$_thelabel = $this->wcrb_generate_signature_slug( $_signature_label );
		
		$short_url = wcrb_create_short_url($signature_url, $_thelabel . '_' . $job_case_number);

		$args = array(
			"job_id"        => $_job_id, 
			"name"          => sprintf( esc_html__( '%s signature url generated', 'computer-repair-shop' ), $_signature_label ), 
			"type"          => 'public', 
			"field"         => '_signature_url_generated', 
			"change_detail" => $short_url
		);
		$WCRB_JOB_HISTORY_LOGS = WCRB_JOB_HISTORY_LOGS::getInstance();
		$WCRB_JOB_HISTORY_LOGS->wc_record_job_history( $args );
		
		// Return the short URL if created, otherwise return the full URL
		return !empty($short_url) ? $short_url : $signature_url;
	}

	/**
	 * Verify signature URL
	 * 
	 * @param int $job_id The job/order ID
	 * @param string $signature_type The type of signature to verify
	 * @param string $verification_code The verification code from the URL
	 * 
	 * @return bool|array Returns false if invalid, or array with signature data if valid
	 */
	function wcrb_verify_signature_url($job_id, $signature_type, $verification_code) {
		// Determine meta key based on signature type
		$meta_key = '';
		switch ($signature_type) {
			case 'pickup':
				$meta_key = '_wcrb_signature_pickup_signature_verification';
				break;
			case 'delivery':
				$meta_key = '_wcrb_signature_delivery_signature_verification';
				break;
			default:
				// Try to get the meta key from the URL parameters
				$signature_label = isset($_GET['signature_label']) ? sanitize_text_field($_GET['signature_label']) : '';
				if (!empty($signature_label)) {
					$sanitized_label = sanitize_title($signature_label);
					$meta_key = '_wcrb_signature_' . $sanitized_label . '_verification';
				}
		}
		
		if (empty($meta_key)) {
			return false;
		}
		
		// Get the stored verification code
		$stored_code = get_post_meta($job_id, $meta_key, true);
		
		if (empty($stored_code) || $stored_code !== $verification_code) {
			return false;
		}
		
		// Get additional signature data
		$signature_data = array(
			'type' => get_post_meta($job_id, $meta_key . '_type', true),
			'label' => get_post_meta($job_id, $meta_key . '_label', true),
			'generated' => get_post_meta($job_id, $meta_key . '_generated', true),
			'verified' => current_time('mysql')
		);
		
		return $signature_data;
	}
}