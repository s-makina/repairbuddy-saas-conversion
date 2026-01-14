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

class WCRB_REVIEWS {

	private static $instance = NULL;

	static public function getInstance() {
		if ( self::$instance === NULL )
			self::$instance = new WCRB_REVIEWS();
		return self::$instance;
	}

	private $TABID = "wcrb_reviews_tab";

	function __construct() {
        add_action( 'wc_rb_settings_tab_menu_item', array( $this, 'add_review_tab_in_settings_menu' ), 10, 2 );
        add_action( 'wc_rb_settings_tab_body', array( $this, 'add_review_tab_in_settings_body' ), 10, 2 );
		add_action( 'wp_ajax_wc_rb_update_review_settings', array( $this, 'wc_rb_update_review_settings' ) );


		//Register Estimate Post type.
		add_action( 'init', array( $this, 'wcrb_reviews_post_type' ) );
		add_action( 'save_post', array( $this, 'wcrb_save_review_meta' ) );
		add_filter( 'manage_edit-rep_reviews_columns', array( $this, 'wcrb_reviews_columns' ) ) ;
		add_action( 'manage_rep_reviews_posts_custom_column', array( $this, 'wcrb_reviews_table_meta_data' ), 10, 2 );

		add_action( 'wp_ajax_wcrb_request_feedback', array( $this, 'wcrb_request_feedback' ) );

		add_shortcode( 'wc_get_order_feedback', array( $this, 'wc_get_order_feedback' ) );
		add_shortcode( 'wcrb_display_reviews', array( $this, 'wcrb_display_reviews' ) );

		add_action( 'wp_ajax_wcrb_submit_case_bring_review', array( $this, 'wcrb_submit_case_bring_review' ) );
		add_action( 'wp_ajax_nopriv_wcrb_submit_case_bring_review', array( $this, 'wcrb_submit_case_bring_review' ) );

		add_action( 'wcrb_review_daily_event', array( $this, 'review_auto_request_mechanism' ) );
		add_action( 'init', array( $this, 'wcrb_add_schedule_review' ) );
    }

	function add_review_tab_in_settings_menu() {
        $active = '';

        $menu_output = '<li class="tabs-title' . esc_attr($active) . '" role="presentation">';
        $menu_output .= '<a href="#' . esc_attr( $this->TABID ) . '" role="tab" aria-controls="' . esc_attr( $this->TABID ) . '" aria-selected="true" id="' . esc_attr( $this->TABID ) . '-label">';
        $menu_output .= '<h2>' . esc_html__( 'Job Reviews', 'computer-repair-shop' ) . '</h2>';
        $menu_output .=	'</a>';
        $menu_output .= '</li>';

        echo wp_kses_post( $menu_output );
    }
	
	function add_review_tab_in_settings_body() {
        $active = '';
		
		$setting_body = '<div class="tabs-panel team-wrap' . esc_attr( $active ) . '" 
        id="' . esc_attr( $this->TABID ) . '" 
        role="tabpanel" 
        aria-hidden="true" 
        aria-labelledby="' . esc_attr( $this->TABID ) . '-label">';

		$setting_body .= '<div class="wc-rb-manage-devices">';
		$setting_body .= '<h2>' . esc_html__( 'Reviews Settings', 'computer-repair-shop' ) . '</h2>';
		$setting_body .= '<div class="review_success_msg"></div>';
		
		$setting_body .= '<form data-async data-abide class="needs-validation" novalidate method="post" data-success-class=".review_success_msg">';
		$setting_body .= '<table class="form-table border"><tbody>';

		//Setting Item Starts
		$setting_body .= '<tr>
							<th scope="row">
								<label for="wcrb_review_notification_sms_on">
									' . esc_html__( 'Request Feedback by SMS', 'computer-repair-shop' ) . '
								</label>
							</th>';
		$setting_body .= '<td>';

		$wcrb_review_notification_sms_on = get_option( 'wcrb_review_notification_sms_on' );
		$wcrb_review_notification_sms_on = ( $wcrb_review_notification_sms_on == 'on' ) ? 'checked="checked"' : '';
		
		$setting_body .= '<input type="checkbox" ' . esc_html( $wcrb_review_notification_sms_on ) . ' name="wcrb_review_notification_sms_on" id="wcrb_review_notification_sms_on" />';
		
		$setting_body .= '<label for="wcrb_review_notification_sms_on">';
		$setting_body .= esc_html__( 'Enable SMS notification for feedback request', 'computer-repair-shop' );
		$setting_body .= '</label></td></tr>';
		//Setting Item Ends

		//Setting Item Starts
		$setting_body .= '<tr>
							<th scope="row">
								<label for="wcrb_review_notification_email_on">
									' . esc_html__( 'Request Feedback by Email', 'computer-repair-shop' ) . '
								</label>
							</th>';
		$setting_body .= '<td>';

		$wcrb_review_notification_email_on = get_option( 'wcrb_review_notification_email_on' );
		$wcrb_review_notification_email_on = ( $wcrb_review_notification_email_on == 'on' ) ? 'checked="checked"' : '';
		
		$setting_body .= '<input type="checkbox" ' . esc_html( $wcrb_review_notification_email_on ) . ' name="wcrb_review_notification_email_on" id="wcrb_review_notification_email_on" />';
		
		$setting_body .= '<label for="wcrb_review_notification_email_on">';
		$setting_body .= esc_html__( 'Enable Email notification for feedback request', 'computer-repair-shop' );
		$setting_body .= '</label></td></tr>';
		//Setting Item Ends

		$setting_body .= '<tr>
							<th scope="row">
								<label for="wc_rb_get_feedback_page_id_id">
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
			'id'                  	=> 'wc_rb_get_feedback_page_id_id',
			'class'                 => 'form-control',
			'show_option_no_change'	=> esc_attr( $default_value ),
			'value_field'           => 'ID',
		) );
		
		$setting_body .= '<label>';
		$setting_body .= esc_html__( 'A page that have shortcode ', 'computer-repair-shop' ) . '<strong>[wc_get_order_feedback]</strong> ';
		$setting_body .= esc_html__( 'If set this would be used to send link to customers so they can leave feedback on jobs.', 'computer-repair-shop' );
		$setting_body .= '</label></td></tr>';
		//Setting Ends. 

		//Setting Starts
		$setting_body .= '<tr>
							<th scope="row">
								<label for="wcrb_send_feedback_request_jobstatus">
									' . esc_html__( 'Send review request if job status is', 'computer-repair-shop' ) . '
								</label>
							</th>';
		$setting_body .= '<td>';

		$selected_status = ( empty( get_option( 'wcrb_send_feedback_request_jobstatus' ) ) ) ? '' : get_option( 'wcrb_send_feedback_request_jobstatus' );

		$setting_body .= '<select name="wcrb_send_feedback_request_jobstatus" class="form-control" id="wcrb_send_feedback_request_jobstatus">';
		$setting_body .= '<option value="">' . esc_html__( 'Select job status to send review request', 'computer-repair-shop' ) . '</option>';
		$setting_body .= wc_generate_status_options( $selected_status );
		$setting_body .= '</select>';
		
		$setting_body .= '<label>';
		$setting_body .= esc_html__( 'When job have status you selected above only then you can auto or manually request feedback.', 'computer-repair-shop' );
		$setting_body .= '</label></td></tr>';
		//Setting Ends.

		//Setting Starts
		$setting_body .= '<tr>
							<th scope="row">
								<label for="wcrb_send_feedback_interval">
									' . esc_html__( 'Auto feedback request', 'computer-repair-shop' ) . '
								</label>
							</th>';
		$setting_body .= '<td>';

		$selected_status = ( empty( get_option( 'wcrb_send_feedback_interval' ) ) ) ? '' : get_option( 'wcrb_send_feedback_interval' );

		$setting_body .= '<select name="wcrb_send_feedback_interval" class="form-control" id="wcrb_send_feedback_interval">';
		if ( ! wc_rs_license_state() ) :
			$setting_body .= '<option value="">' . esc_html__( 'Available in pro version', 'computer-repair-shop' ) . '</option>';
		else :
			$selected_one = ( $selected_status == 'one-notification' ) ? ' selected' : '';
			$selected_two = ( $selected_status == 'two-notifications' ) ? ' selected' : '';
			$selected_disabled = ( $selected_status == 'disabled' ) ? ' selected' : '';
			$setting_body .= '<option value="">' . esc_html__( 'Select interval', 'computer-repair-shop' ) . '</option>';
			$setting_body .= '<option '. esc_attr( $selected_disabled ) .' value="one-notification">' . esc_html__( 'Disabled', 'computer-repair-shop' ) . '</option>';
			$setting_body .= '<option '. esc_attr( $selected_one ) .' value="one-notification">' . esc_html__( '1 Notification - After 24 Hours', 'computer-repair-shop' ) . '</option>';
			$setting_body .= '<option '. esc_attr( $selected_two ) .' value="two-notifications">' . esc_html__( '2 Notifications - After 24 Hrs and 48 Hrs', 'computer-repair-shop' ) . '</option>';
		endif;
		$setting_body .= '</select>';
		
		$setting_body .= '<label>';
		$setting_body .= esc_html__( 'A request for customer feedback will be sent automatically.', 'computer-repair-shop' );
		$setting_body .= '</label></td></tr>';
		//Setting Ends.

		//Setting Starts
		$setting_body .= '<tr>
							<th scope="row"><label for="wcrb_feedback_email_notification_message">
								' . esc_html__( 'Email message to request feedback', 'computer-repair-shop' ) . '
							</label></th>';
		$setting_body .= '<td>';

		$_casenumberl = wcrb_get_label( 'casenumber', 'none' );

		$saved_message = ( empty( get_option( 'wcrb_feedback_email_notification_message' ) ) ) ? '' : get_option( 'wcrb_feedback_email_notification_message' );

$message = 'Hello {{customer_full_name}},

We hope you are doing good! We are reaching out to you about your recent job id : {{job_id}} and '. $_casenumberl .' : {{case_number}} 

For your device : {{customer_device_label}} 

We would like to know your experience can you please take a moment and help us to improve our services. 

{{st_feedback_anch}} Review your job {{end_feedback_anch}}

Direct link to review : {{feedback_link}}

Thank you again for your business!';

		$saved_message = ( empty( $saved_message ) ) ? $message : $saved_message;

		$setting_body .= esc_html__( 'Available keywords', 'computer-repair-shop' ) . '<br>';
		$setting_body .= '{{st_feedback_anch}} Click me to review {{end_feedback_anch}} {{feedback_link}} {{job_id}} {{customer_device_label}} {{case_number}} {{customer_full_name}}' . '<br>';
		$setting_body .= '<textarea rows="6" name="wcrb_feedback_email_notification_message" id="wcrb_feedback_email_notification_message">' . esc_textarea( $saved_message ) . '</textarea>';
		
		$setting_body .= '</td></tr>';
		//Setting Ends.

		//Setting Starts
		$setting_body .= '<tr>
							<th scope="row"><label for="feedback_email_subject">
								' . esc_html__( 'Email subject to request feedback', 'computer-repair-shop' ) . '
							</label></th>';
		$setting_body .= '<td>';

		$saved_message = ( empty( get_option( 'feedback_email_subject' ) ) ) ? 'How would you rate the service you received?' : get_option( 'feedback_email_subject' );
		$setting_body .= '<input type="text" class="regular-text" name="feedback_email_subject" value="' . esc_html( $saved_message ) . '" id="feedback_email_subject" />';
		
		$setting_body .= '</td></tr>';
		//Setting Ends.sss

		//Setting Starts
		$setting_body .= '<tr>
							<th scope="row"><label for="wcrb_feedback_sms_notification_message">
								' . esc_html__( 'SMS message to request feedback', 'computer-repair-shop' ) . '
							</label></th>';
		$setting_body .= '<td>';

		$_casenumberl = wcrb_get_label( 'casenumber', 'none' );

		$saved_message = ( empty( get_option( 'wcrb_feedback_sms_notification_message' ) ) ) ? '' : get_option( 'wcrb_feedback_sms_notification_message' );

		$message = 'Hello {{customer_full_name}}, Please take a moment to review your job '. $_casenumberl .' : {{case_number}} going to {{feedback_link}} . Thank you for your business!';

		$saved_message = ( empty( $saved_message ) ) ? $message : $saved_message;

		$setting_body .= esc_html__( 'Available keywords', 'computer-repair-shop' ) . '<br>';
		$setting_body .= '{{feedback_link}} {{job_id}} {{customer_device_label}} {{case_number}} {{customer_full_name}}' . '<br>';
		$setting_body .= '<textarea rows="3" name="wcrb_feedback_sms_notification_message" id="wcrb_feedback_sms_notification_message">' . esc_html( $saved_message ) . '</textarea>';
		
		$setting_body .= '</td></tr>';
		//Setting Ends.

		$setting_body .= '<tr><td colspan="2">'. esc_html__( 'You should have set a review page with correct shortcode.', 'computer-repair-shop' ) .'</td></tr>';

		$setting_body .= '</tbody></table>';

		$setting_body .= '<input type="hidden" name="form_type" value="wcrb_update_settings_form" />';
		$setting_body .= '<input type="hidden" name="form_action" value="wc_rb_update_review_settings" />';
		
		$setting_body .= wp_nonce_field( 'wcrb_nonce_reviews', 'wcrb_nonce_reviews_field', true, false );

		$setting_body .= '<button type="submit" class="button button-primary" data-type="rbssubmitdevices">' . esc_html__( 'Update Options', 'computer-repair-shop' ) . '</button></form>';

		$setting_body .= '</div><!-- wc rb Devices /-->';
		$setting_body .= '</div><!-- Tabs Panel /-->';

		$allowedHTML = ( function_exists( 'wc_return_allowed_tags' ) ) ? wc_return_allowed_tags() : '';
		echo wp_kses( $setting_body, $allowedHTML );
	}

	function wc_rb_update_review_settings() {
		$message = '';
		$success = 'NO';

		$form_type = ( isset( $_POST['form_type'] ) ) ? sanitize_text_field( $_POST['form_type'] ) : '';
				
		if ( 
			isset( $_POST['wcrb_nonce_reviews_field'] ) 
			&& wp_verify_nonce( $_POST['wcrb_nonce_reviews_field'], 'wcrb_nonce_reviews' ) 
			&& $form_type == 'wcrb_update_settings_form' ) {

			$submit_arr = array(
				'wcrb_review_notification_email_on',
				'wcrb_review_notification_sms_on',
				'wcrb_send_feedback_request_jobstatus',
				'wc_rb_get_feedback_page_id',
				'wcrb_send_feedback_interval',
				'feedback_email_subject',
				'wcrb_feedback_email_notification_message',
				'wcrb_feedback_sms_notification_message'
			);

			foreach( $submit_arr as $field ) {

				if ( $field == 'wcrb_feedback_email_notification_message' ) {
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
	}

	//Estimate job post
	function wcrb_reviews_post_type() {
		$labels = array(
			'add_new_item' 			=> esc_html__( 'Add New Review', 'computer-repair-shop' ),
			'singular_name' 		=> esc_html__( 'Review', 'computer-repair-shop' ), 
			'menu_name' 			=> esc_html__( 'Reviews', 'computer-repair-shop' ),
			'all_items' 			=> esc_html__( 'Reviews', 'computer-repair-shop' ),
			'edit_item' 			=> esc_html__( 'Edit Review', 'computer-repair-shop' ),
			'new_item' 				=> esc_html__( 'New Review', 'computer-repair-shop' ),
			'view_item' 			=> esc_html__( 'View Review', 'computer-repair-shop' ),
			'search_items' 			=> esc_html__( 'Search Review', 'computer-repair-shop' ),
			'not_found' 			=> esc_html__( 'No Review found', 'computer-repair-shop' ),
			'not_found_in_trash' 	=> esc_html__( 'No Review in trash', 'computer-repair-shop' )
		);

		$args = array(
			'labels'             	=> $labels,
			'label'					=> esc_html__( 'Reviews', 'computer-repair-shop' ),
			'description'        	=> esc_html__( 'Reviews Section', 'computer-repair-shop' ),
			'public'             	=> false,
			'publicly_queryable' 	=> false,
			'show_ui'            	=> true,
			'show_in_menu'       	=> '',
			'query_var'          	=> true,
			'rewrite'            	=> array( 'slug' => 'wcrb_review' ),
			'capability_type'    	=> array( 'rep_job', 'rep_jobs' ),
			'has_archive'        	=> true,
			'menu_icon'			 	=> 'dashicons-clipboard',
			'menu_position'      	=> 30,
			'supports'           	=> array(''), 	
			'register_meta_box_cb' 	=> array( $this, 'wcrb_review_features' ),
		);
		register_post_type( 'rep_reviews', $args );
	}

	function wcrb_review_features() { 
		add_meta_box( 'wc_order_info_id', esc_html__( 'Review Details', 'computer-repair-shop' ), array( $this, 'wcrb_review_details' ), 'rep_reviews', 'advanced', 'high' );
	} //Parts features post.
	
	function wcrb_review_details( $post ) {
		wp_nonce_field( 'wc_review_meta_box_nonce', 'wc_review_details_box' );
		settings_errors();

		$content = '<table class="form-table">';
		
		$value = get_post_meta( $post->ID, '_job_id', true );
		
		$content .= '<tr><td scope="row"><label for="job_id">' . esc_html__( "Job ID", "computer-repair-shop" ) . '</label></td><td>';

		if ( ! empty( $value ) ) :
			$jobs_manager = WCRB_JOBS_MANAGER::getInstance();
			$job_data 	  = $jobs_manager->get_job_display_data( $value );
			$_job_id  	  = ( ! empty( $job_data['formatted_job_number'] ) ) ? $job_data['formatted_job_number'] : $value;

			$admin_url  = admin_url( 'post.php?post=' . $value . '&action=edit' );

			$content .= '<h2 class="abovezero">' . esc_html( $_job_id ) . ' <br> 
			<a href="' . esc_url( $admin_url ) . '" target="_blank">' . esc_html( get_the_title( $value ) ) . '</a></h2>';
		else :
			$content .= '<select name="job_id" id="selectjob_id" disabled="disabled">';
			$content .= '<option value="">' . esc_html__( 'Select job', 'computer-repair-shop' ) . '</option>';
			$content .= wcrb_return_job_ids_options( $value );
			$content .= '</select>';
		endif;

		$content .= '</td></tr>';

		$value = get_post_meta( $post->ID, '_review_rating', true );
		
		$content .= '<tr><td scope="row"><label for="review_rating">' . esc_html__( "Rating", "computer-repair-shop" ) . '</label></td><td>
		<select name="review_rating" id="review_rating" class="form-control">';
		$content .= '<option value="">' . esc_html__( 'Select rating', 'computer-repair-shop' ) . '</option>';
		$catch_array = $this->return_ratings_array( $value );
		if ( ! empty( $catch_array ) ) {
			foreach( $catch_array as $rating_arr ) {
				$selected_h = ( ! empty( $rating_arr['selected'] ) && $rating_arr['selected'] == 'selected' ) ? ' selected' : '';
				$content .= '<option ' . esc_attr( $selected_h ) . ' value="' . esc_attr( $rating_arr['value'] ) . '">' . esc_html( $rating_arr['name'] ) . '</option>';
			}
		}
		$content .= '</select></td></tr>';
		
		$value = get_post_meta( $post->ID, '_review_feedback', true );
		
		$content .= '<tr><td scope="row"><label for="review_feedback">' . esc_html__( 'Feedback', 'computer-repair-shop' ) . '</label></td><td>';
		$content .= '<textarea rows="4" class="form-control" name="review_feedback" id="review_feedback">' . esc_html( $value ) . '</textarea>';
		$content .= '</td></tr>';

		$value = get_post_meta( $post->ID, '_visibility', true );
		
		$public    = ( $value == 'public' ) ? ' selected' : '';
		$anonymous = ( $value == 'anonymous' ) ? ' selected' : '';
		$private   = ( $value == 'private' ) ? ' selected' : '';

		$content .= '<tr><td scope="row"><label for="visibility">' . esc_html__( 'Feedback Visibility', 'computer-repair-shop' ) . '</label></td><td>';
		$content .= '<select name="visibility">
						<option ' . esc_attr( $public ) . ' value="public">' . esc_html__( 'Public - can list on website', 'computer-repair-shop' ) . '</option>
						<option ' . esc_attr( $anonymous ) . ' value="anonymous">' . esc_html__( 'Anonymous - can list on website without name', 'computer-repair-shop' ) . '</option>
						<option ' . esc_attr( $private ) . ' value="private">' . esc_html__( 'Private - cannot list anywhere', 'computer-repair-shop' ) . '</option>
					</select>';
		$content .= '</td></tr>';

		$content .= '</table>';

		$allowed_html = wc_return_allowed_tags();
		echo wp_kses( $content, $allowed_html );
	}

	function return_ratings_array( $selected ) {
		$ratings = array( 1, 2, 3, 4, 5 );
		$stars 	 = array();

		foreach( $ratings as $rating ) {
			$selected_h = ( ! empty( $selected ) && $selected == $rating ) ? 'selected' : '';
			$stars[] = array(
				'value' => $rating,
				'name'  => $rating,
				'selected' => $selected_h
			);
		}

		return $stars;
	}

	function wcrb_save_review_meta( $post_id ) {
		global $post, $wpdb;

		// Verify that the nonce is valid.'', ''
		if ( ! isset( $_POST['wc_review_details_box'] ) || ! wp_verify_nonce( $_POST['wc_review_details_box'], 'wc_review_meta_box_nonce' )) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
			return;
		}

		// bail out if this is not an event item
		if ( 'rep_reviews' !== $post->post_type ) {
			return;
		}

		// Check the user's permissions.
		if ( isset( $_POST['post_type'] )) {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
		}

		//Form PRocessing
		$submission_values = array (
			'review_feedback',
			'review_rating',
			'job_id',
			'visibility'
		);

		foreach( $submission_values as $submit_value ) {
			if ( $_POST[$submit_value] == 'review_feedback' ) {
				$my_value 	= ( isset( $_POST[$submit_value] ) ) ? sanitize_textarea_field( $_POST[$submit_value] ) : '';
			} else {
				$my_value 	= ( isset( $_POST[$submit_value] ) ) ? sanitize_text_field( $_POST[$submit_value] ) : '';
			}
			if ( ! empty( $my_value ) ) {
				update_post_meta( $post_id, '_'.$submit_value, $my_value );
			}
		}
		$job_id = ( isset( $_POST['job_id'] ) && ! empty( $_POST['job_id'] ) ) ? sanitize_text_field( $_POST['job_id'] ) : '';

		if ( ! empty( $job_id ) ) {
			update_post_meta( $job_id, '_review_id', $post_id );
		}

		$job_id = ( ! empty( $job_id ) ) ? $job_id : get_post_meta( $post_id, '_job_id', true );

		//Update history of job!
		if ( ! empty( $job_id ) ) {
			$args = array(
				"job_id" 		=> $job_id, 
				"name" 			=> esc_html__( 'Review Added/Updated', 'computer-repair-shop' ), 
				"type" 			=> 'public', 
				"field" 		=> '_review_id', 
				"change_detail" => esc_html__( 'Review updated', 'computer-repair-shop' )
			);
			$WCRB_JOB_HISTORY_LOGS = WCRB_JOB_HISTORY_LOGS::getInstance();
			$WCRB_JOB_HISTORY_LOGS->wc_record_job_history($args);

			$jobs_manager = WCRB_JOBS_MANAGER::getInstance();
			$job_data 	  = $jobs_manager->get_job_display_data( $job_id );
			$__job_id  	  = ( ! empty( $job_data['formatted_job_number'] ) ) ? $job_data['formatted_job_number'] : $job_id;

			$title = esc_html( $__job_id ) . ' | ' . esc_html( get_the_title( $job_id ) );
			$where = array( 'ID' => $post_id );
			$wpdb->update( $wpdb->posts, array( 'post_title' => $title ), $where ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}
	}

	function wcrb_reviews_columns( $columns ) {
		$wc_device_label = ( empty( get_option( 'wc_device_label' ) ) ) ? esc_html__( 'Device', 'computer-repair-shop' ) : get_option( 'wc_device_label' );

		$columns = array(
			'cb' => '<input type="checkbox" />',
			'review_id' 		=> __( 'Review ID', 'computer-repair-shop' ),
			'title' 			=> sprintf( __( 'Job ID / %s', 'computer-repair-shop' ), wcrb_get_label( 'casenumber', 'first' ) ),
			'customers' 		=> __( 'Customer', 'computer-repair-shop' ),
			'device' 			=> $wc_device_label,
			'ratings' 			=> __( 'Rating', 'computer-repair-shop' ),
			'visibility' 		=> __( 'Visibility', 'computer-repair-shop' ),
			'wc_job_actions' => __( 'View Job', 'computer-repair-shop' )
		);
		return $columns;
	}

	function wcrb_reviews_table_meta_data( $column, $post_id ) {
		global $post, $PAYMENT_STATUS_OBJ;

		$allowedHTML = wc_return_allowed_tags(); 

		switch( $column ) {
			case 'review_id' :
				$theOrderId = "# ".$post_id; 
				echo esc_html( $theOrderId );
			break;
			case 'customers' :
				$job_id 	= get_post_meta( $post_id, '_job_id', true );
				$customer 	= get_post_meta( $job_id, '_customer', true );
				
				if ( ! empty( $customer ) ) {
					$user 			= get_user_by( 'id', $customer );
					$phone_number 	= get_user_meta( $customer, "billing_phone", true );
					$billing_tax 	= get_user_meta( $customer, "billing_tax", true );
					$company 		= get_user_meta( $customer, "billing_company", true );
					
					$first_name		= empty( $user->first_name ) ? "" : $user->first_name;
					$last_name 		= empty( $user->last_name ) ? "" : $user->last_name;
					$theFullName 	= $first_name. ' ' .$last_name;
					$email 			= empty( $user->user_email ) ? "" : $user->user_email;
					echo esc_html( $theFullName );

					echo ( ! empty( $phone_number ) ) ? "<br>" . esc_html__( 'P', 'computer-repair-shop' ) . ": " . esc_html( $phone_number ) : '';
					echo ( ! empty( $email ) ) ? "<br>" . esc_html__( 'E', 'computer-repair-shop' ).": ".esc_html( $email ) : '';
					echo ( ! empty( $company ) ) ? "<br>" . esc_html__( 'Company', 'computer-repair-shop' ) . ": " . esc_html( $company ) : '';
				}	
			break;
			case 'device' :
				$job_id 	= get_post_meta( $post_id, '_job_id', true );
				
				$device_post_id	 = get_post_meta( $job_id, '_device_post_id', true );
				$current_devices = get_post_meta( $job_id, '_wc_device_data', true );

				$setup_new_type = ( ! empty ( $device_post_id ) ) ? wc_set_new_device_format( $job_id ) : '';

				if ( ! empty( $current_devices )  && is_array( $current_devices ) ) {
					$counter = 0;
					$content = '';
					foreach( $current_devices as $device_data ) {
						$content .= ( $counter != 0 ) ? '<br>' : '';				
						$device_post_id = ( isset( $device_data['device_post_id'] ) ) ? $device_data['device_post_id'] : '';
						$device_id      = ( isset( $device_data['device_id'] ) ) ? $device_data['device_id'] : '';
		
						$content .= return_device_label( $device_post_id );
						$content .= ( ! empty ( $device_id ) ) ? ' (' . esc_html( $device_id ) . ')' : '';
						$counter++;
					}
					echo wp_kses_post( $content );
				}
			break;
			case 'ratings' : 
				$rating = get_post_meta( $post_id, '_review_rating', true );
				echo ( ! empty( $rating ) && is_numeric( $rating ) ) ? '<i data-star="' . esc_attr( $rating ) . '"></i>' : '<i data-star="0"></i>';
			break;
			case 'visibility' :
				$visibility = get_post_meta( $post_id, '_visibility', true );
				echo esc_html( ucfirst( $visibility ) );
			break;
			case 'wc_job_actions' :
				$admin_url  = admin_url( 'post.php?post=' . get_post_meta( $post_id, '_job_id', true ) . '&action=edit' );

				$actions_output = '<div class="actionswrapperjobs">';
				$actions_output .= '<a title="' . esc_html__( 'View Job', 'computer-repair-shop' ) . '" target="_blank" href="' . esc_url( $admin_url ) . '"><span class="dashicons dashicons-visibility"></span></a>';
				$actions_output .= '</div>';

				echo wp_kses( $actions_output, $allowedHTML );
			break;
			//Break for everything else to show default things.
			default :
				break;
		}
	}

	function wcrb_jobs_feedback_box( $post )  {

		$content = '';

		if ( ! empty( get_post_meta( $post->ID, '_review_id', true ) ) ) :
			$review_id = get_post_meta( $post->ID, '_review_id', true );
			$date_format = get_option( 'date_format' );
			$theDate 	 = get_the_date( $date_format, $review_id );
			$review_rating = get_post_meta( $review_id, '_review_rating', true );

			$content .= '<div class="wcrb-review-wrapper">';
			$content .= '<div class="wcrb-review-head">
							<div class="ratings column-ratings review-ratings"><i data-star="'. esc_attr( $review_rating ) .'"></i></div>
							<div class="review-date head-column">'. esc_html( $theDate ) .'</div>
						 </div>';
			$content .= '<div class="wcrb-review-body">'. esc_html( get_post_meta( $review_id, '_review_feedback', true ) ) .'</div>';
			$content .= '<div class="wcrb-review-visibility"><small>'. esc_html__( 'Visibility', 'computer-repair-shop' ) . ' - ' . esc_html( ucfirst( get_post_meta( $review_id, '_visibility', true ) ) ) .'</small></div>';
			$content .= '</div>';
		endif;

		//Request History
		$content .= '<div class="wcRbJob_services_wrap">';
		$content .= '<h3>' . esc_html__( 'Request History', 'computer-repair-shop' );

		if ( empty( get_post_meta( $post->ID, '_review_id', true ) ) ) :
			$content .= '<a class="button button-primary button-small float-right" 
			data-open="manualRequestFeedback" 
			data-security="' . wp_create_nonce( 'request_review_security' ) . '" 
			recordid="'. esc_html( $post->ID ) .'" 
			aria-haspopup="true" tabindex="0">' . esc_html__( 'Request Feedback', 'computer-repair-shop' ) . '</a>';
		endif;
		
		$content .= '</h3>';

		$content .= '<div class="request_feedback_message"></div>';

		$content .= '<div class="grid-x grid-margin-x">';
		$content .= '<div class="cell small-12" id="reloadFeedbackRequestFields">';
		
		$content .= '<table class="grey-bg wc_table">
						<thead>
							<tr>
								<th>' . esc_html__( 'ID', 'computer-repair-shop' ) . '</th>
								<th>' . esc_html__( 'Date Time', 'computer-repair-shop' ) . '</th>
								<th>' . esc_html__( 'Type', 'computer-repair-shop' ) . '</th>
								<th>' . esc_html__( 'Mail To', 'computer-repair-shop' ) . '</th>
								<th>' . esc_html__( 'SMS To', 'computer-repair-shop' ) . '</th>
								<th>' . esc_html__( 'Link Clicked', 'computer-repair-shop' ) . '</th>
							</tr>
						</thead>';
						
		$content .= '<tbody>' . $this->return_requests_logs_history( $post->ID ) . '</tbody>';
					 
	 	$content .= '</table></div></div></div>';

		$allowedHTML = wc_return_allowed_tags(); 
		echo wp_kses( $content, $allowedHTML );
	}

	function return_requests_logs_history( $job_id ) {
		global $wpdb;

		$wcrb_feedback_log_table   = $wpdb->prefix . 'wc_cr_feedback_log';

		if ( $job_id == 'All' ) {
			$select_query 	= "SELECT * FROM `" . $wcrb_feedback_log_table . "` ORDER BY `log_id` DESC";
		} else {
			$select_query = "SELECT * FROM `" . $wcrb_feedback_log_table . "` WHERE `job_id`=%d ORDER BY `log_id` DESC";
			$select_query = $wpdb->prepare( $select_query, $job_id );
		}
        $select_results = $wpdb->get_results( $select_query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            
        $output = ( $wpdb->num_rows == 0 ) ? esc_html__( 'There is no record available', 'computer-repair-shop' ) : '';

		$date_format = get_option('date_format');
		$date_format .= ' ' . get_option('time_format');
		//id, dateTime, Type, By, Mail To , SMS to , Link Clicked
        foreach( $select_results as $result ) {
			$_date = date_i18n( $date_format, strtotime( $result->datetime ) );

			$output .= '<tr>';
			$output .= '<td>' . esc_html( $result->log_id ) . '</td>';
			$output .= '<td>' . esc_html( $_date ) . '</td>';
			$output .= '<td>' . esc_html( $result->type ) . '</td>';
			$output .= '<td>' . esc_html( $result->email_to ) . '</td>';
			$output .= '<td>' . esc_html( $result->sms_to ) . '</td>';
			$output .= '<td>' . esc_html( $result->action ) . '</td>';
			$output .= '</tr>';
		}
		return $output;
	}

	function wcrb_request_feedback() {
		global $OBJ_SMS_SYSTEM, $WCRB_EMAILS;
		//Process manual feedback request

		if ( isset( $_POST['data_security'] ) && wp_verify_nonce( $_POST['data_security'], 'request_review_security' ) ) {
			// Valid.
			if ( ! isset( $_POST['recordID'] ) || empty( $_POST['recordID'] ) ) {
				$message = esc_html__( 'Unknonwn', 'computer-repair-shop' );
			} else {
				$job_id = sanitize_text_field( $_POST['recordID'] );

				if ( ! wc_rs_license_state() ) :
					$message = esc_html__( 'This is a pro feature please activate your license.', 'computer-repair-shop' );
				else :	
					//License is active...
					$message = 'Everything is okay';

					$wcrb_review_notification_email_on = get_option( 'wcrb_review_notification_email_on' );
					$wcrb_review_notification_sms_on = get_option( 'wcrb_review_notification_sms_on' );

					$wcrb_review_notification_email_on = ( $wcrb_review_notification_email_on == 'on' ) ? 'YES' : '';
					$wcrb_review_notification_sms_on = ( $wcrb_review_notification_sms_on == 'on' ) ? 'YES' : '';

					if ( $wcrb_review_notification_email_on != 'YES' && $wcrb_review_notification_sms_on != 'YES' ) {
						$message = esc_html__( 'Please activate feedback to be sent either in sms or email as notification from settings', 'computer-repair-shop' );
					} else {
						//Ready to send the review notification... 
						$wc_rb_get_feedback_page_id = get_option( 'wc_rb_get_feedback_page_id' );
						if ( empty( $wc_rb_get_feedback_page_id ) ) {
							$message = esc_html__( 'Please create and set a page with feedback shortcode going to settings pages setup.', 'computer-repair-shop' );
						} else {
							// Ready to send n notification
							$selected_status = get_option( 'wcrb_send_feedback_request_jobstatus' );
							$current_status  = get_post_meta( $job_id, '_wc_order_status', true );
							$review_id 		= get_post_meta( $job_id, '_review_id', true );

							if ( ! empty( $review_id ) || empty( $selected_status ) || $selected_status != $current_status ) {
								$message = esc_html__( 'Your current job status does not match with job status you have selected in review settngs to request job feedback.', 'computer-repair-shop' );
							} else {
								//ready now.
								$customer_id = get_post_meta( $job_id, "_customer", true );

								if ( empty( $customer_id ) ) {
									$message = esc_html__( 'Customer not set for this job', 'computer-repair-shop' );
								} else {
									//Ready now...
									$message = esc_html__( 'Message field is empty please add a message to deliver.', 'computer-repair-shop' );

									$email_message = get_option( 'wcrb_feedback_email_notification_message' );
									if ( ! empty( $email_message ) && $wcrb_review_notification_email_on == 'YES' ) {
										//Send Email.
										$user_info 	= get_userdata( $customer_id );
										$user_email = $user_info->user_email;

										if ( ! empty( $user_email ) ) {
											//Finally ready to process email. 
											$arguments  = array( 'job_id' => $job_id, 'email_message' => $email_message, 'record_feed_type' => 'email' );
											$email_body = $WCRB_EMAILS->return_body_replacing_keywords( $arguments );

											if ( ! empty( $email_body ) ) {
												$to = $user_email;
												$subject = esc_html( get_option( 'feedback_email_subject' ) );
												$subject = ( ! empty( $subject ) ) ? $subject . ' | ' . get_bloginfo( 'name' ) : 'Send us your feedback' . ' | ' . get_bloginfo( 'name' );
												
												$WCRB_EMAILS->send_email( $to, $subject, $email_body, '' );

												//Add log
												$args = array(
													"job_id" 		=> $job_id, 
													"name" 			=> esc_html__( "Feedback request sent to email", 'computer-repair-shop' ), 
													"type" 			=> 'public', 
													"field" 		=> '_feedbak_request', 
													"change_detail" => 'To : ' . $user_email
												);
												$WCRB_JOB_HISTORY_LOGS = WCRB_JOB_HISTORY_LOGS::getInstance();
												$WCRB_JOB_HISTORY_LOGS->wc_record_job_history($args);

												$message = esc_html__( 'Request sent', 'computer-repair-shop' );
											}
										}
									}

									$sms_message = get_option( 'wcrb_feedback_sms_notification_message' );
									if ( ! empty( $sms_message ) && $wcrb_review_notification_sms_on == 'YES' ) {
										//Send SMS
										$user_info 	= get_userdata( $customer_id );
										$user_name 	= $user_info->first_name . ' ' . $user_info->last_name;
										$phone_number = get_user_meta( $customer_id, 'billing_phone', true );

										$is_sms_active = get_option( 'wc_rb_sms_active' );

										if ( ! empty( $phone_number ) && $is_sms_active == 'YES' ) {
											$arguments = array( 'job_id' => $job_id, 'email_message' => $sms_message, 'record_feed_type' => 'sms' );
											$sms_body = $WCRB_EMAILS->return_body_replacing_keywords( $arguments );

											$OBJ_SMS_SYSTEM->send_sms( $phone_number, $sms_body, $job_id );

											//Add log
											$args = array(
												"job_id" 		=> $job_id, 
												"name" 			=> esc_html__( "Feedback request sent to sms", 'computer-repair-shop' ), 
												"type" 			=> 'public', 
												"field" 		=> '_feedbak_request', 
												"change_detail" => 'To : ' . $phone_number
											);
											$WCRB_JOB_HISTORY_LOGS = WCRB_JOB_HISTORY_LOGS::getInstance();
											$WCRB_JOB_HISTORY_LOGS->wc_record_job_history( $args );

											$message = esc_html__( 'Request sent', 'computer-repair-shop' );
										}
									}
								}
							}
						}
					}
				endif;
			}
		} else {
			$values['message'] = esc_html__( 'Something is wrong with your submission!', 'computer-repair-shop' );
		}

		$values['message'] = $message;
		$values['success'] = 'YES';

		wp_send_json( $values );
		wp_die();
	}

	/**
	 * Accepts array with arguments
	 * 
	 * array( 'job_id' => , 'email_to' => , 'sms_to' => , 'type' => , 'action' => , ) 
	 */
	function wcrb_add_feedback_log( $args ) {
		global $wpdb;

		if ( ! is_array( $args ) ) {
			return esc_html__( 'Is not an array', 'computer-repair-shop' );
		}
		if ( ! isset( $args['job_id'] ) || empty( $args['job_id'] ) ) {
			return esc_html__( 'Missing Job id', 'computer-repair-shop' );
		}

		//'job_id' => , 'email_to' => , 'sms_to' => , 'type' => , ''
		$job_id 	 = ( isset( $args['job_id'] ) && ! empty( $args['job_id'] ) ) ? sanitize_text_field( $args['job_id'] ) : '';
		$email_to 	 = ( isset( $args['email_to'] ) && ! empty( $args['email_to'] ) ) ? sanitize_text_field( $args['email_to'] ) : '';
		$sms_to   	 = ( isset( $args['sms_to'] ) && ! empty( $args['sms_to'] ) ) ? sanitize_text_field( $args['sms_to'] ) : '';
		$type   	 = ( isset( $args['type'] ) && ! empty( $args['type'] ) ) ? sanitize_text_field( $args['type'] ) : 'auto';
		$action   	 = ( isset( $args['action'] ) && ! empty( $args['action'] ) ) ? sanitize_text_field( $args['action'] ) : esc_html__( 'No feedback' );

		$wcrb_feedback_log_table   = $wpdb->prefix . 'wc_cr_feedback_log';
		$wcrb_log_dateti = wp_date( 'Y-m-d H:i:s' );

		//log_id datetime job_id email_to sms_to type action
		$insert_query =  "INSERT INTO `{$wcrb_feedback_log_table}` VALUES( NULL, %s, %d, %s, %s, %s, %s )";
	
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prepare( $insert_query, $wcrb_log_dateti, $job_id, $email_to, $sms_to, $type, $action )
		);

		$log_id = $wpdb->insert_id;
		return $log_id;
	}

	function wcrb_display_reviews( $atts ) {
		wp_enqueue_style( 'foundation-css');
        wp_enqueue_style( 'plugin-styles-wc' );

		$atts = shortcode_atts( array(
			'columns' 			=> '2',
			'hide_below_rating' => '3'
		), $atts, 'wcrb_display_reviews' );

		if ( ! wc_rs_license_state() ) {
			return esc_html__( 'Pro feature! Plugin activation required.', 'computer-repair-shop' );
		}

		$_query_reviews = array(
			'posts_per_page' => -1,
			'post_type'      => 'rep_reviews',
			'orderby'       => 'ID',
			'order'         => 'DESC',
			'meta_query' => array(
							'relation' => 'AND',
								array(
									'key'     => '_visibility',
									'value'   => 'private',
									'compare' => '!=',
								),
								array(
									'key'     => '_review_rating',
									'value'	  => esc_html( $atts['hide_below_rating'] ),
									'compare' => '>',
								),
							),
		);

		$wrapper_class = 'wcrb-review-wrapper';
		$wrapper_class = ( $atts['columns'] == '3' ) ? 'large-4 medium-6 small-12 cell wcrb-review-wrapper' : $wrapper_class;
		$wrapper_class = ( $atts['columns'] == '2' ) ? 'large-6 medium-1 small-12 cell wcrb-review-wrapper' : $wrapper_class;

		$reviews_query = new WP_Query( $_query_reviews );

		if( $reviews_query->have_posts() ) : 
			$post_output = '<div class="wcrb_main_reviews_wrapper grid-container grid-x grid-margin-x grid-padding-y">';
			while( $reviews_query->have_posts() ): 
				$reviews_query->the_post();
                $_g_review_id = $reviews_query->post->ID;
				$date_format = get_option( 'date_format' );
				$theDate 	 = get_the_date( $date_format, $_g_review_id );

				$review_rating = get_post_meta( $_g_review_id, '_review_rating', true );

				$post_output .= '<div class="'. esc_attr( $wrapper_class ) .'">';
				$post_output .= '<div class="wcrb-review-head">
									<div class="ratings column-ratings review-ratings"><i data-star="'. esc_attr( $review_rating ) .'"></i></div>
									<div class="review-date head-column">'. esc_html( $theDate ) .'</div>
								</div><!-- reviews_head -->';
				$post_output .= '<div class="wcrb-review-body">'. esc_html( get_post_meta( $_g_review_id, '_review_feedback', true ) ) .'</div>';

				$_visibility = get_post_meta( $_g_review_id, '_visibility', true );
				$_customerName = '';

				if ( $_visibility == 'anonymous' ) {
					$_customerName = ucfirst( $_visibility );
				}
				if ( $_visibility == 'public' ) {
					$order_id   	 = get_post_meta( $_g_review_id, "_job_id", true );
					$customerLabel   = get_post_meta( $order_id, "_customer_label", true );
					$customer_id     = get_post_meta( $order_id, '_customer', true );

					$_customerName = ucfirst( $customerLabel );
				}

				$post_output .= '<div class="wcrb-review-visibility">
				<small>'. esc_html__( 'By', 'computer-repair-shop' ) . ' - ' . esc_html( $_customerName ) .'</small></div>';
				$post_output .= '</div>';
			endwhile;
			$post_output .= '</div><!-- End reviews wrap/-->';
		endif;

		wp_reset_postdata();
			
		return $post_output;
	}

	function wc_get_order_feedback() { 
		wp_enqueue_style( 'foundation-css');
        wp_enqueue_style( 'plugin-styles-wc' );
		wp_enqueue_script("foundation-js");
		wp_enqueue_script("wc-cr-js");
		wp_enqueue_script("select2");

		$content = '';

		if ( isset( $_GET['review_id'] ) && isset( $_GET['case_number'] ) && isset( $_GET['job_id'] ) ) {
			$_request_id = ( isset( $_GET['review_id'] ) && ! empty( $_GET['review_id'] ) ) ? sanitize_text_field( $_GET['review_id'] ) : '';
			$_case_number = ( isset( $_GET['case_number'] ) && ! empty( $_GET['case_number'] ) ) ? sanitize_text_field( $_GET['case_number'] ) : '';
			$order_id = $_job_id = ( isset( $_GET['job_id'] ) && ! empty( $_GET['job_id'] ) ) ? sanitize_text_field( $_GET['job_id'] ) : '';
			$_g_review_id = get_post_meta( $order_id, '_review_id', true );

			if ( $_request_id != 'NO' && is_numeric( $_request_id ) ) {
				$this->update_feedback_action_column( $_request_id, 'Clicked' );
			}

			//Check if job ID matches the Casenumber.get_post_meta
			$job_case_number = get_the_title( $_job_id );

			if ( $job_case_number == $_case_number && empty( $_g_review_id ) ) {
				//We can print feedback form here.
				$content = '<div class="wc_order_status_form wcrb_review_form">';
				$content .= '<h2>' . esc_html__( 'Please rate the service your received', 'computer-repair-shop' ) . '</h2>';
				$content .= '<p>' . esc_html__( 'Your feedback is valueable to us and help us to improve our services.', 'computer-repair-shop' ) . '</p>';

				$content .= '<table><tr><td class="alignleft">';
					$customerLabel   = get_post_meta( $order_id, "_customer_label", true );
					$customer_id     = get_post_meta( $order_id, '_customer', true );
					$pickup_date = get_post_meta( $order_id, '_pickup_date', true );
					$pickup_date = ( ! empty( $pickup_date ) ) ? $pickup_date : get_the_date( '', $order_id );
					$date_format = get_option( 'date_format' );
					$pickup_date = date_i18n( $date_format, strtotime( $pickup_date ) );

					$customer_phone  	= get_user_meta( $customer_id, 'billing_phone', true);
					$customer_company	= get_user_meta( $customer_id, 'billing_company', true);
					$billing_tax	    = get_user_meta( $customer_id, 'billing_tax', true);
					
					$content .= ( ! empty( $customer_company ) ) ? '<strong>' . esc_html__( 'Company', 'computer-repair-shop' ) . ' : </strong>' . $customer_company . '<br>' : '';
					$content .= ( ! empty( $billing_tax ) ) ? '<strong>' . esc_html__( 'Tax ID', 'computer-repair-shop' ) . ' : </strong>' . $billing_tax . '<br>' : '';
					$content .= ( ! empty( $customerLabel ) ) ? $customerLabel : '';

					if(!empty($customer_phone)) {
						$content .= "<br><strong>".esc_html__("Phone", "computer-repair-shop")." :</strong> ".$customer_phone;	
					}
					if(!empty($user_email)) {
						$content .= "<br><strong>".esc_html__("Email", "computer-repair-shop")." :</strong> ".$user_email;	
					}
				$content .= '</td>';

				$content .= '<td class="alignright">
                                <strong>'.esc_html__("Order", "computer-repair-shop").' #:</strong> '.$order_id.'<br>
                                <strong>'. wcrb_get_label( 'casenumber', 'first' ) .' :</strong> '.get_post_meta( $order_id, "_case_number", true ).'<br>
                                <strong>'.esc_html__("Created", "computer-repair-shop").' :</strong> ' . esc_html( $pickup_date ) . '<br>
                                <strong>'.esc_html__("Order status", "computer-repair-shop").' :</strong> '.get_post_meta( $order_id, "_wc_order_status_label", true ).'
                            </td></tr></table>';

				$content .= '<form id="wcrb_reviews" method="post">';
				$content .= '<h3 class="wcrb-rating-h-t">' . esc_html__( 'Select rating', 'computer-repair-shop' ) . '</h3>';

				$content .= '<div class="wcrb-rating">
								<input type="radio" id="star5" name="rating" value="5" />
								<label class="wcrb-star" for="star5" title="Awesome" aria-hidden="true"></label>

								<input type="radio" id="star4" name="rating" value="4" />
								<label class="wcrb-star" for="star4" title="Great" aria-hidden="true"></label>

								<input type="radio" id="star3" name="rating" value="3" />
								<label class="wcrb-star" for="star3" title="Very good" aria-hidden="true"></label>

								<input type="radio" id="star2" name="rating" value="2" />
								<label class="wcrb-star" for="star2" title="Good" aria-hidden="true"></label>

								<input type="radio" id="star1" name="rating" value="1" />
								<label class="wcrb-star" for="star1" title="Bad" aria-hidden="true"></label>
						  </div>';

				$content .= '<div class="formcontrol">';
				$content .= '<label>
								'. esc_html__( 'Please writer your feedback', 'computer-repair-shop' ) .'
								<textarea rows="4" name="wcrb_feedback" placeholder="' . esc_html__( 'Please writer your feedback', 'computer-repair-shop' ) . '"></textarea>
							</label>';
				$content .= '</div>';

				$content .= '<label>' . esc_html__( 'Feedback Visibility', 'computer-repair-shop' ) . '
								<select name="wcrb_feedback_visibility">
									<option value="public">' . esc_html__( 'Public - can list on website', 'computer-repair-shop' ) . '</option>
									<option value="anonymous">' . esc_html__( 'Anonymous - can list on website without name', 'computer-repair-shop' ) . '</option>
									<option value="private">' . esc_html__( 'Private - cannot list anywhere', 'computer-repair-shop' ) . '</option>
								</select>
							</label>';

				$content .=  wp_nonce_field( 'wc_computer_repair_nonce', 'wc_job_status_nonce', $echo = false );
				$content .= '<input type="hidden" name="_case_number" value="' . esc_html( $job_case_number ) . '" />';
				$content .= '<input type="hidden" name="_job_id" value="' . esc_html( $_job_id ) . '" />';
				
				$_request_id = ( isset( $_GET['review_id'] ) && ! empty( $_GET['review_id'] ) ) ? sanitize_text_field( $_GET['review_id'] ) : '';
				if ( $_request_id != 'NO' && ! empty( $_request_id ) ) {
					$content .= '<input type="hidden" name="review_id" value="' . esc_html( $_request_id ) . '" />';
				}

				$content .= '<input type="submit" class="button button-primary primary" value="'.esc_html__( "Submit Feedback", "computer-repair-shop").'" />';
				$content .= '</form>';

				$content .= '</div>';

				$content .= '<div class="form-message orderstatusholder"></div>';
			} elseif ( $job_case_number == $_case_number && ! empty( $_g_review_id ) ) {
				$date_format = get_option( 'date_format' );
				$theDate 	 = get_the_date( $date_format, $_g_review_id );
				$review_rating = get_post_meta( $_g_review_id, '_review_rating', true );

				$content = '<div class="wc_order_status_form wcrb_review_form">';
				$content .= '<h2>' . esc_html__( 'Thank you for your interest, Your feedback already have been received.', 'computer-repair-shop' ) . '</h2>';

				$content .= '<table><tr><td class="alignleft">';
					$customerLabel   = get_post_meta( $order_id, "_customer_label", true );
					$customer_id     = get_post_meta( $order_id, '_customer', true );
					$pickup_date = get_post_meta( $order_id, '_pickup_date', true );
					$pickup_date = ( ! empty( $pickup_date ) ) ? $pickup_date : get_the_date( '', $order_id );
					$date_format = get_option( 'date_format' );
					$pickup_date = date_i18n( $date_format, strtotime( $pickup_date ) );

					$customer_phone  	= get_user_meta( $customer_id, 'billing_phone', true);
					$customer_company	= get_user_meta( $customer_id, 'billing_company', true);
					$billing_tax	    = get_user_meta( $customer_id, 'billing_tax', true);
					
					$content .= ( ! empty( $customer_company ) ) ? '<strong>' . esc_html__( 'Company', 'computer-repair-shop' ) . ' : </strong>' . $customer_company . '<br>' : '';
					$content .= ( ! empty( $billing_tax ) ) ? '<strong>' . esc_html__( 'Tax ID', 'computer-repair-shop' ) . ' : </strong>' . $billing_tax . '<br>' : '';
					$content .= ( ! empty( $customerLabel ) ) ? $customerLabel : '';

					if(!empty($customer_phone)) {
						$content .= "<br><strong>".esc_html__("Phone", "computer-repair-shop")." :</strong> ".$customer_phone;	
					}
					if(!empty($user_email)) {
						$content .= "<br><strong>".esc_html__("Email", "computer-repair-shop")." :</strong> ".$user_email;	
					}
				$content .= '</td>';

				$content .= '<td class="alignright">
                                <strong>'.esc_html__("Order", "computer-repair-shop").' #:</strong> '.$order_id.'<br>
                                <strong>'. wcrb_get_label( 'casenumber', 'first' ) .' :</strong> '.get_post_meta( $order_id, "_case_number", true ).'<br>
                                <strong>'.esc_html__("Created", "computer-repair-shop").' :</strong> ' . esc_html( $pickup_date ) . '<br>
                                <strong>'.esc_html__("Order status", "computer-repair-shop").' :</strong> '.get_post_meta( $order_id, "_wc_order_status_label", true ).'
                            </td></tr></table>';

				$content .= '<div class="wcrb-review-wrapper">';
				$content .= '<div class="wcrb-review-head">
								<div class="ratings column-ratings review-ratings"><i data-star="'. esc_attr( $review_rating ) .'"></i></div>
								<div class="review-date head-column">'. esc_html( $theDate ) .'</div>
							</div>';
				$content .= '<div class="wcrb-review-body">'. esc_html( get_post_meta( $_g_review_id, '_review_feedback', true ) ) .'</div>';
				$content .= '<div class="wcrb-review-visibility"><small>'. esc_html__( 'Visibility', 'computer-repair-shop' ) . ' - ' . esc_html( ucfirst( get_post_meta( $_g_review_id, '_visibility', true ) ) ) .'</small></div>';
				$content .= '</div>';
				$content .= '</div>';
			} else {
				//Something is not right message. // Fetch form to Print Form... 
				$content .= $this->get_job_feedback_form();
			}
		} else {
			//Form to fetch job details by Entering the Case #
			$content .= $this->get_job_feedback_form();
		}
		return $content;
	}//wc_list_services.

	function get_job_feedback_form() {
		
		$content = '<div class="wc_order_status_form wcrb_review_form">';
		$content .= '<h2>' . esc_html__( 'How would you rate the service you received?', 'computer-repair-shop' ) . '</h2>';
		$content .= '<p>' . sprintf( esc_html__( 'Please enter your %s of your job to leave feedback on service you received.', 'computer-repair-shop' ), wcrb_get_label( 'casenumber', 'none' ) ) . '</p>';
		
		$content .= '<form id="wcrb_reviews" method="post">';
		$content .= '<input type="text" required autofocus placeholder="' . sprintf( esc_html__( "Your %s", "computer-repair-shop" ), wcrb_get_label( 'casenumber', 'none' ) ) . '...' . '" name="wcrb_case_number" />';
		$content .=  wp_nonce_field( 'wc_computer_repair_nonce', 'wc_job_status_nonce', $echo = false );
		$content .= '<input type="submit" class="button button-primary primary" value="'.esc_html__( "Review Now!", "computer-repair-shop").'" />';
		$content .= '</form>';

		$content .= '</div>';
		
		$content .= '<div class="form-message orderstatusholder"></div>';

		return $content;
	}

	function wcrb_submit_case_bring_review() {
		global $wpdb, $WCRB_EMAILS;

		$values = array();
		$redirect_link = '';
		
		if (!isset( $_POST['wc_job_status_nonce'] ) || ! wp_verify_nonce( $_POST['wc_job_status_nonce'], 'wc_computer_repair_nonce' )) :
			$values['message'] = esc_html__( 'Something is wrong with your submission!', 'computer-repair-shop' );
			$values['success'] = "YES";
		else:
			if ( isset( $_POST['wcrb_case_number'] ) && ! empty( $_POST['wcrb_case_number'] ) ) {
				$wcCasaeNumber = sanitize_text_field( $_POST["wcrb_case_number"] );

				$wc_cr_args = array(
					'posts_per_page'   => 1,
					'post_type'        => 'rep_jobs',
					'meta_key'         => '_case_number',
					'meta_value'       => $wcCasaeNumber
				);
				$wc_cr_query = new WP_Query( $wc_cr_args );

				if ( $wc_cr_query->have_posts() ): 
					while( $wc_cr_query->have_posts() ): 
						$wc_cr_query->the_post();

						$order_id = get_the_ID();

						$_feedback_page = get_option( 'wc_rb_get_feedback_page_id' );
						if ( empty( $_feedback_page ) ) {
							$message = esc_html__( 'Please create and set a page with feedback shortcode going to settings pages setup.', 'computer-repair-shop' );
						} else {
							//let's create redirect link
							$message = esc_html__( 'Redirecting...', 'computer-repair-shop' );
							$page_link = get_the_permalink( $_feedback_page );

							$_params = array( 'review_id' => 'NO', 'job_id' => $order_id, 'case_number' => $wcCasaeNumber );
							$page_link = add_query_arg( $_params, $page_link );

							$redirect_link = esc_url_raw( $page_link );
						}
					endwhile;
				else: 
					$message = sprintf( esc_html__( 'We haven\'t found any job with your given %s!', 'computer-repair-shop' ), wcrb_get_label( 'casenumber', 'none' ) );
				endif;
				wp_reset_postdata();
			} elseif ( isset( $_POST['_case_number'] ) && ! empty( $_POST['_case_number'] ) && isset( $_POST['_job_id'] ) && ! empty( $_POST['_job_id'] ) ) {
				//Let's submit review if set...
				$order_id = $_job_id  = sanitize_text_field( $_POST["_job_id"] );
				$_case_number  		  = sanitize_text_field( $_POST["_case_number"] );
				$review_id 			  = get_post_meta( $order_id, '_review_id', true );

				//Check if job ID matches the Casenumber.
				$job_case_number = get_the_title( $_job_id );

				if ( $_case_number != $job_case_number ) {
					$message = esc_html__( 'Something is wrong with your submission!', 'computer-repair-shop' );
				} elseif ( ! empty( $review_id ) ) {
					$message = esc_html__( 'We already have received your feedback. Thank you!', 'computer-repair-shop' );
				} else {
					if ( 
						isset( $_POST['rating'] ) && ! empty( $_POST['rating'] ) &&
						isset( $_POST['wcrb_feedback_visibility'] ) && ! empty( $_POST['wcrb_feedback_visibility'] ) &&
						isset( $_POST['wcrb_feedback'] ) && ! empty( $_POST['wcrb_feedback'] ) 
					   ) {
							$_rating     = sanitize_text_field( $_POST["rating"] );
							$_feedback   = sanitize_textarea_field( $_POST["wcrb_feedback"] );
							$_visibility = sanitize_text_field( $_POST["wcrb_feedback_visibility"] );
							$customer_id = get_post_meta( $_job_id, '_customer', true );

							$jobs_manager = WCRB_JOBS_MANAGER::getInstance();
							$job_data 	  = $jobs_manager->get_job_display_data( $_job_id );
							$__job_id  	  = ( ! empty( $job_data['formatted_job_number'] ) ) ? $job_data['formatted_job_number'] : $_job_id;

							$title = esc_html( $__job_id ) . ' | ' . esc_html( get_the_title( $_job_id ) );
							$my_post = array(
								'post_title'    => $title,
								'post_status'   => 'publish',
								'post_type' 	=> 'rep_reviews',
								'post_author'   => $customer_id,
							);
							// Insert the post into the database
							$review_id = wp_insert_post( $my_post );

							update_post_meta( $review_id, '_review_feedback', $_feedback );
							update_post_meta( $review_id, '_review_rating', $_rating );
							update_post_meta( $review_id, '_job_id', $_job_id );
							update_post_meta( $review_id, '_visibility', $_visibility );

							if ( ! empty( $_job_id ) ) {
								update_post_meta( $_job_id, '_review_id', $review_id );
							}
				
							//Update history of job!
							if ( ! empty( $_job_id ) ) {
								$args = array(
									"job_id" 		=> $_job_id, 
									"name" 			=> esc_html__( 'Review Added', 'computer-repair-shop' ), 
									"type" 			=> 'public', 
									"field" 		=> '_review_id', 
									"change_detail" => esc_html__( 'Review added by customer', 'computer-repair-shop' )
								);
								$WCRB_JOB_HISTORY_LOGS = WCRB_JOB_HISTORY_LOGS::getInstance();
								$WCRB_JOB_HISTORY_LOGS->wc_record_job_history( $args );
					
								$jobs_manager = WCRB_JOBS_MANAGER::getInstance();
								$job_data 	  = $jobs_manager->get_job_display_data( $_job_id );
								$__job_id  	  = ( ! empty( $job_data['formatted_job_number'] ) ) ? $job_data['formatted_job_number'] : $_job_id;

								$title = esc_html( $__job_id ) . ' | ' . esc_html( get_the_title( $_job_id ) );
								$where = array( 'ID' => $review_id );
								$wpdb->update( $wpdb->posts, array( 'post_title' => $title ), $where ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

								$page_link = wc_rb_return_get_feedback_link( '', $order_id );
								$redirect_link = esc_url_raw( $page_link );

								$_request_id = ( isset( $_POST['review_id'] ) && ! empty( $_POST['review_id'] ) ) ? sanitize_text_field( $_POST['review_id'] ) : '';
								if ( $_request_id != 'NO' && ! empty( $_request_id ) ) {
									$this->update_feedback_action_column( $_request_id, 'Feedback Given' );
								}

								$jobs_manager = WCRB_JOBS_MANAGER::getInstance();
								$job_data 	  = $jobs_manager->get_job_display_data( $_job_id );
								$__job_id  	  = ( ! empty( $job_data['formatted_job_number'] ) ) ? $job_data['formatted_job_number'] : $_job_id;

								//Send update to admin.
								$subject = esc_html__( 'A review have been received on job id', 'computer-repair-shop' ) . ' | ' . esc_html( $__job_id );

								$body  	 = esc_html__( 'A customer have left feedback', 'computer-repair-shop' );
								$body .= '<br><br><a href="' . esc_url( $page_link ) . '" target="_blank">' . esc_html__( 'View Review', 'computer-repair-shop' ) . '</a>';
								$body .= '<br><br>' . esc_html__( 'Thank you!', 'computer-repair-shop' ); 

								$admin_email = ( ! empty( get_option( 'admin_email' ) ) ) ? get_option( 'admin_email' ) : '';

								if ( ! empty( $admin_email ) ) :
									$WCRB_EMAILS->send_email( $admin_email, $subject, $body, '' );

									$args = array(
										"job_id" 		=> $_job_id, 
										"name" 			=> esc_html__( "Feedback received email sent", 'computer-repair-shop' ), 
										"type" 			=> 'public', 
										"field" 		=> '_feedbak_received', 
										"change_detail" => 'To : ' . $admin_email
									);
									$WCRB_JOB_HISTORY_LOGS = WCRB_JOB_HISTORY_LOGS::getInstance();
									$WCRB_JOB_HISTORY_LOGS->wc_record_job_history($args);
								endif;

								$message = esc_html__( 'Thank you for your feedback!', 'computer-repair-shop' ) . ' - ' . esc_html__( 'Redirecting...', 'computer-repair-shop' );
							}
					   } else {
							$message = esc_html__( 'All fields are required.', 'computer-repair-shop' );
					   }
				}
			} else {
				$message = sprintf( esc_html__( '%s is required', 'computer-repair-shop' ), wcrb_get_label( 'casenumber', 'first' ) );
			}
		endif;

		$values['redirect_url'] = $redirect_link;
		$values['message'] = $message;

		wp_send_json( $values );
		wp_die();
	}

	function update_feedback_action_column( $_request_id, $action ) {
		global $wpdb;

		if ( empty( $_request_id ) || empty( $action ) ) {
			return;
		}

		$computer_repair_feedback_log 	  = $wpdb->prefix.'wc_cr_feedback_log';
		//Update functionality
		$data 	= array(
			"action" => $action, 
		); 
		$where 	= ['log_id' => $_request_id];

		$update_row = $wpdb->update( $computer_repair_feedback_log, $data, $where ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	function wcrb_add_schedule_review() {
		if ( ! wp_next_scheduled( 'wcrb_review_daily_event' ) ) {
			wp_schedule_event( time(), 'daily', 'wcrb_review_daily_event' );
		}
	}

	function review_auto_request_mechanism() {
		global $OBJ_SMS_SYSTEM, $WCRB_EMAILS;
		//Process manual feedback request

		if ( ! wc_rs_license_state() ) {
			return;
		}

		$wcrb_review_notification_email_on = get_option( 'wcrb_review_notification_email_on' );
		$wcrb_review_notification_sms_on = get_option( 'wcrb_review_notification_sms_on' );

		$wcrb_review_notification_email_on = ( $wcrb_review_notification_email_on == 'on' ) ? 'YES' : '';
		$wcrb_review_notification_sms_on = ( $wcrb_review_notification_sms_on == 'on' ) ? 'YES' : '';

		if ( $wcrb_review_notification_email_on != 'YES' && $wcrb_review_notification_sms_on != 'YES' ) {
			return;
		}

		$wc_rb_get_feedback_page_id = get_option( 'wc_rb_get_feedback_page_id' );

		if ( empty( $wc_rb_get_feedback_page_id ) ) {
			return;
		}

		$selected_status = get_option( 'wcrb_send_feedback_request_jobstatus' );
		if ( empty( $selected_status ) ) {
			return;
		}
		$_feedback_interval = get_option( 'wcrb_send_feedback_interval' );

		if ( $_feedback_interval != 'disabled' ) {
			//one-notification 
			//two-notification 24 + 48

			$secon_argu = ( $_feedback_interval == 'two-notification' ) ? array(
				'key' => '_second_review_notification',
				'value' => 'YES',
				'compare' => '!='
			) : array();

			//Query Starts
			$meta_query_arr = array(
				'relation' => 'AND',
				array(
					'key'     => '_first_review_notification',
					'value'	  => 'YES',
					'compare' => '!='
				),
				$secon_argu,
				array(
					'key'	 => '_can_review_it',
					'value'  => 'YES',
					'compare' => '='
				),
				array(
					'key'     => '_review_id',
					'compare' => 'NOT EXISTS'
				),
				array(
					'key'     => '_review_id',
					'value'	  => '',
					'compare' => '!='
				),
				array(
					'key'		=> '_wc_order_status',
					'value'		=> sanitize_text_field( $selected_status ),
					'compare'	=> '=',
				)
			);

			//WordPress Query for Rep Jobs
			$jobs_args = array(
				'post_type' 		=> 'rep_jobs',
				'orderby'			=> 'id',
				'order' 			=> 'DESC',
				'posts_per_page' 	=> -1,
				'post_status'		=> array('publish'),
				'meta_query' 		=> $meta_query_arr,
			);

			$jobs_query = new WP_Query( $jobs_args );

			if ( $jobs_query->have_posts() ): 
				while( $jobs_query->have_posts() ): 
					$jobs_query->the_post();
					$job_id = $jobs_query->post->ID;
					//ready now.
					$customer_id = get_post_meta( $job_id, "_customer", true );

					if ( ! empty( $customer_id ) ) {
						//Ready now...
						$WCRB_JOB_HISTORY_LOGS = WCRB_JOB_HISTORY_LOGS::getInstance();
						
						$email_message = get_option( 'wcrb_feedback_email_notification_message' );
						if ( ! empty( $email_message ) && $wcrb_review_notification_email_on == 'YES' ) {
							//Send Email.
							$user_info 	= get_userdata( $customer_id );
							$user_email = $user_info->user_email;

							if ( ! empty( $user_email ) ) {
								//Finally ready to process email. 
								$arguments  = array( 'job_id' => $job_id, 'email_message' => $email_message, 'record_feed_type' => 'email' );
								$email_body = $WCRB_EMAILS->return_body_replacing_keywords( $arguments );

								if ( ! empty( $email_body ) ) {
									$to = $user_email;
									$subject = esc_html( get_option( 'feedback_email_subject' ) );
									$subject = ( ! empty( $subject ) ) ? $subject . ' | ' . get_bloginfo( 'name' ) : 'Send us your feedback' . ' | ' . get_bloginfo( 'name' );

									$WCRB_EMAILS->send_email( $to, $subject, $email_body, '' );

									//Add log
									$args = array(
										"job_id" 		=> $job_id, 
										"name" 			=> esc_html__( 'Auto', 'computer-repair-shop' ) . ' - ' . esc_html__( "Feedback request sent to email", 'computer-repair-shop' ), 
										"type" 			=> 'public', 
										"field" 		=> '_feedbak_request', 
										"change_detail" => 'To : ' . $user_email
									);
									$WCRB_JOB_HISTORY_LOGS->wc_record_job_history($args);
								}
							}
						} //Send email Ends

						$sms_message = get_option( 'wcrb_feedback_sms_notification_message' );
						if ( ! empty( $sms_message ) && $wcrb_review_notification_sms_on == 'YES' ) {
							//Send SMS
							$user_info 	= get_userdata( $customer_id );
							$user_name 	= $user_info->first_name . ' ' . $user_info->last_name;
							$phone_number = get_user_meta( $customer_id, 'billing_phone', true );

							$is_sms_active = get_option( 'wc_rb_sms_active' );

							if ( ! empty( $phone_number ) && $is_sms_active == 'YES' ) {
								$arguments = array( 'job_id' => $job_id, 'email_message' => $sms_message, 'record_feed_type' => 'sms' );
								$sms_body = $WCRB_EMAILS->return_body_replacing_keywords( $arguments );

								$OBJ_SMS_SYSTEM->send_sms( $phone_number, $sms_body, $job_id );

								//Add log
								$args = array(
									"job_id" 		=> $job_id, 
									"name" 			=> esc_html__( 'Auto', 'computer-repair-shop' ) . ' - ' . esc_html__( "Feedback request sent to sms", 'computer-repair-shop' ), 
									"type" 			=> 'public', 
									"field" 		=> '_feedbak_request', 
									"change_detail" => 'To : ' . $phone_number
								);
								$WCRB_JOB_HISTORY_LOGS->wc_record_job_history( $args );
							}
						} //Send SMS ends

					} //End customer ID
					( get_post_meta( $job_id, '_first_review_notification', true ) == 'YES' ) ? update_post_meta( $job_id, '_second_review_notification', 'YES' ) : update_post_meta( $job_id, '_first_review_notification', 'YES' );
				endwhile;
			endif;
			wp_reset_postdata();
			//Query Ends
		}
	}
    
	function generate_star_rating( $rating ) {
		$rating = intval( $rating );
		$stars_html = '';
		
		$stars_html .= '<div class="star-rating d-inline-block">';
		for ( $i = 1; $i <= 5; $i++ ) {
			if ( $i <= $rating ) {
				$stars_html .= '<i class="bi bi-star-fill text-warning"></i>';
			} else {
				$stars_html .= '<i class="bi bi-star text-muted"></i>';
			}
		}
		$stars_html .= '</div>';
		$stars_html .= '<small class="text-muted ms-1">(' . esc_html( $rating ) . '/5)</small>';
		
		return $stars_html;
	}

	function list_customer_reviews_bootstrap( $view = 'frontend' ) {
		global $wpdb;
		
		if ( ! is_user_logged_in() ) {
			return array(
				'stats'      => '',
				'filters'    => '',
				'rows'       => '<tr><td colspan="9" class="text-center py-5">' . esc_html__( 'You are not logged in.', 'computer-repair-shop' ) . '</td></tr>',
				'pagination' => ''
			);
		}
		$jobs_manager    = WCRB_JOBS_MANAGER::getInstance();

		$current_user = wp_get_current_user();
		$user_id 	  = $current_user->ID;
		$user_roles   = (array) $current_user->roles;
		
		// Determine user type
		$is_admin_user = ! empty( array_intersect( array( 'administrator', 'store_manager', 'technician' ), $user_roles ) );
		$is_customer   = in_array( 'customer', $user_roles );
		$is_admin_view = $is_admin_user && $view === 'admin';
		
		// Setup pagination
		$reviews_per_page = 20;
		$current_page     = isset( $_GET['reviews_page'] ) ? max( 1, intval( $_GET['reviews_page'] ) ) : 1;
		$offset           = ( $current_page - 1 ) * $reviews_per_page;
		
		// Get device labels
		$sing_device_label = ( empty( get_option( 'wc_device_label' ) ) ) ? esc_html__( 'Device', 'computer-repair-shop' ) : get_option( 'wc_device_label' );
		$casenumber_label = wcrb_get_label( 'casenumber', 'first' );
		
		// Initialize data arrays
		$rows_html = '';
		$total_items = 0;
		
		if ( $is_admin_user ) {
			// ADMIN/STORE_MANAGER/TECHNICIAN VIEW: Show all reviews
			
			// Build query for reviews
			$args = array(
				'post_type'      => 'rep_reviews',
				'posts_per_page' => $reviews_per_page,
				'offset'         => $offset,
				'post_status'    => 'publish',
				'meta_query'     => array()
			);
			
			// Apply filters
			if ( isset( $_GET['review_search'] ) && ! empty( $_GET['review_search'] ) ) {
				$search_term = sanitize_text_field( $_GET['review_search'] );
				$args['s'] = $search_term;
			}
			
			// Filter by rating
			if ( isset( $_GET['rating_filter'] ) && ! empty( $_GET['rating_filter'] ) && $_GET['rating_filter'] != 'all' ) {
				$rating = intval( $_GET['rating_filter'] );
				$args['meta_query'][] = array(
					'key'     => '_review_rating',
					'value'   => $rating,
					'compare' => '='
				);
			}

			// Get reviews
			$reviews_query = new WP_Query( $args );
			$total_reviews = $reviews_query->found_posts;
			$total_items   = $total_reviews;
			
			// Generate table rows for admin view
			if ( ! $reviews_query->have_posts() ) {
				$rows_html .= '<tr>';
				$rows_html .= '<td colspan="9" class="text-center py-5">';
				$rows_html .= '<i class="bi bi-star display-1 text-muted"></i>';
				$rows_html .= '<h4 class="text-muted mt-3">' . esc_html__( 'No reviews found!', 'computer-repair-shop' ) . '</h4>';
				$rows_html .= '</td>';
				$rows_html .= '</tr>';
			} else {
				while ( $reviews_query->have_posts() ) {
					$reviews_query->the_post();
					$review_id = get_the_ID();
					
					// Get review data
					$rating = get_post_meta( $review_id, '_review_rating', true );
					$job_id = get_post_meta( $review_id, '_job_id', true );
					$review_summary = get_post_meta( $review_id, '_review_feedback', true );
					
					// Get job data
					$case_number = '';
					$device_name = '';
					$device_post_id = '';
					$customer_id = 0;
					$customer_name = '';
					$order_date = '';
					
					if ( $job_id ) {
						$case_number     = get_post_meta( $job_id, '_case_number', true );
						$customer_id     = get_post_meta( $job_id, '_customer', true );
						$order_date      = get_the_date( 'M d, Y', $job_id );

						// Get device info
						$device_post_id = get_post_meta( $job_id, '_device_post_id', true );
						$current_devices = get_post_meta( $job_id, '_wc_device_data', true );
						
						$devicess = '';
						if ( ! empty( $current_devices ) && is_array( $current_devices ) ) {
							$counter = 0;
							foreach( $current_devices as $device_data ) {
								$devicess .= ( $counter != 0 ) ? '<br>' : '';                
								$device_post_id = ( isset( $device_data['device_post_id'] ) ) ? $device_data['device_post_id'] : '';
								$device_id = ( isset( $device_data['device_id'] ) ) ? $device_data['device_id'] : '';
								
								$devicess .= return_device_label( $device_post_id );
								$devicess .= ( ! empty( $device_id ) ) ? ' (' . esc_html( $device_id ) . ')' : '';
								$counter++;
							}
						}
						
						// Get customer name
						if ( $customer_id ) {
							$user = get_user_by( 'id', $customer_id );
							if ( $user ) {
								$first_name = empty( $user->first_name ) ? "" : $user->first_name;
								$last_name = empty( $user->last_name ) ? "" : $user->last_name;
								$customer_name = trim( $first_name . ' ' . $last_name );
								if ( empty( $customer_name ) ) {
									$customer_name = $user->display_name;
								}
							}
						}
					}
  
					$rows_html .= '<tr>';
					$rows_html .= '<td class="ps-4"><strong>#' . esc_html( $review_id ) . '</strong></td>';
					
					$job_data 	     = $jobs_manager->get_job_display_data( $job_id );
					$_job_id  	     = ( ! empty( $job_data['formatted_job_number'] ) ) ? $job_data['formatted_job_number'] : $job_id;

					// Job ID
					$rows_html .= '<td>';
					if ( ! empty( $job_id ) ) {
						$rows_html .= '<span class="badge bg-secondary">#' . esc_html( $_job_id ) . '</span>';
					} else {
						$rows_html .= '<span class="text-muted">-</span>';
					}
					$rows_html .= '</td>';
					
					// Case Number
					$rows_html .= '<td>';
					if ( ! empty( $case_number ) ) {
						$rows_html .= '<span class="badge bg-primary">' . esc_html( $case_number ) . '</span>';
					} else {
						$rows_html .= '<span class="text-muted">-</span>';
					}
					$rows_html .= '</td>';
					
					// Device
					$rows_html .= '<td>';
					if ( ! empty( $devicess ) ) {
						$rows_html .= '<strong>' . wp_kses_post( $devicess ) . '</strong>';
					} else {
						$rows_html .= '<span class="text-muted">-</span>';
					}
					$rows_html .= '</td>';
					
					// Order Date
					$rows_html .= '<td>' . esc_html( $order_date ) . '</td>';
					
					// Rating
					$rows_html .= '<td>';
					$rows_html .= $this->generate_star_rating( $rating );
					$rows_html .= '</td>';
					
					// Review Summary
					$rows_html .= '<td>';
					if ( ! empty( $review_summary ) ) {
						$rows_html .= '<div class="text-truncate" style="max-width: 200px;" title="' . esc_attr( $review_summary ) . '">';
						$rows_html .= esc_html( $review_summary );
						$rows_html .= '</div>';
					} else {
						$rows_html .= '<span class="text-muted">' . esc_html__( 'No summary', 'computer-repair-shop' ) . '</span>';
					}
					$rows_html .= '</td>';
					
					// Customer Column
					$rows_html .= '<td>';
					if ( ! empty( $customer_name ) ) {
						$rows_html .= '<div class="customer-info">';
						$rows_html .= '<div class="fw-medium">' . esc_html( $customer_name ) . '</div>';
						$rows_html .= '</div>';
					} else {
						$rows_html .= '<span class="text-muted">-</span>';
					}
					$rows_html .= '</td>';
					
					// Actions
					$rows_html .= '<td class="text-end pe-4">';
					
					// View Review button
					$_feedback_page = get_option( 'wc_rb_get_feedback_page_id' );
					if ( ! empty( $_feedback_page ) && $job_id ) {
						$page_link = wc_rb_return_get_feedback_link( '', $job_id );
						$rows_html .= '<a href="' . esc_url( $page_link ) . '" target="_blank" ';
						$rows_html .= 'class="btn btn-sm btn-outline-primary d-inline-flex align-items-center">';
						$rows_html .= '<i class="bi bi-eye me-1"></i>';
						$rows_html .= esc_html__( 'View', 'computer-repair-shop' );
						$rows_html .= '</a>';
					} else {
						$rows_html .= '<span class="badge bg-warning">' . esc_html__( 'Unavailable', 'computer-repair-shop' ) . '</span>';
					}
					
					$rows_html .= '</td>';
					$rows_html .= '</tr>';
				}
				wp_reset_postdata();
			}
			
		} elseif ( $is_customer ) {
			// CUSTOMER VIEW: Show jobs they can review AND jobs they've already reviewed
			
			$selected_status = ( empty( get_option( 'wcrb_send_feedback_request_jobstatus' ) ) ) ? 'delivered' : get_option( 'wcrb_send_feedback_request_jobstatus' );
			
			// Query for customer's jobs with selected status
			$meta_query_arr = array(
				array(
					'key'     => '_customer',
					'value'   => $user_id,
					'compare' => '=',
				),
				array(
					'key'     => '_wc_order_status',
					'value'   => sanitize_text_field( $selected_status ),
					'compare' => '=',
				)
			);
			
			// WordPress Query for Rep Jobs
			$jobs_args = array(
				'post_type'      => 'rep_jobs',
				'orderby'        => 'ID',
				'order'          => 'DESC',
				'posts_per_page' => $reviews_per_page,
				'offset'         => $offset,
				'post_status'    => array('publish'),
				'meta_query'     => $meta_query_arr,
			);
			
			$jobs_query = new WP_Query( $jobs_args );
			$total_jobs = $jobs_query->found_posts;
			$total_items = $total_jobs;
			
			if ( ! $jobs_query->have_posts() ) {
				$rows_html .= '<tr>';
				$rows_html .= '<td colspan="8" class="text-center py-5">';
				$rows_html .= '<i class="bi bi-clipboard-check display-1 text-muted"></i>';
				$rows_html .= '<h4 class="text-muted mt-3">' . esc_html__( 'No jobs available for review!', 'computer-repair-shop' ) . '</h4>';
				$rows_html .= '<p class="text-muted">' . sprintf( 
					esc_html__( 'Jobs with status "%s" will appear here for review.', 'computer-repair-shop' ), 
					$selected_status 
				) . '</p>';
				$rows_html .= '</td>';
				$rows_html .= '</tr>';
			} else {
				while ( $jobs_query->have_posts() ) {
					$jobs_query->the_post();
					$job_id = get_the_ID();
					
					// Get job data
					$case_number = get_post_meta( $job_id, '_case_number', true );
					$order_date = get_the_date( 'M d, Y', $job_id );
					$order_total = wc_order_grand_total( $job_id, 'grand_total' );
					$order_total = wc_cr_currency_format( $order_total );
					
					// Get device info
					$device_post_id = get_post_meta( $job_id, '_device_post_id', true );
					$current_devices = get_post_meta( $job_id, '_wc_device_data', true );
					
					$devicess = '';
					if ( ! empty( $current_devices ) && is_array( $current_devices ) ) {
						$counter = 0;
						foreach( $current_devices as $device_data ) {
							$devicess .= ( $counter != 0 ) ? '<br>' : '';                
							$device_post_id = ( isset( $device_data['device_post_id'] ) ) ? $device_data['device_post_id'] : '';
							$device_id = ( isset( $device_data['device_id'] ) ) ? $device_data['device_id'] : '';
							
							$devicess .= return_device_label( $device_post_id );
							$devicess .= ( ! empty( $device_id ) ) ? ' (' . esc_html( $device_id ) . ')' : '';
							$counter++;
						}
					}
					
					// Check if review exists
					$review_id = get_post_meta( $job_id, '_review_id', true );
					$review_rating = ( ! empty( $review_id ) ) ? get_post_meta( $review_id, '_review_rating', true ) : 0;
					$review_rating = ( ! empty( $review_rating ) ) ? $review_rating : 0;
					
					$job_data 	     = $jobs_manager->get_job_display_data( $job_id );
					$_job_id  	     = ( ! empty( $job_data['formatted_job_number'] ) ) ? $job_data['formatted_job_number'] : $job_id;

					$rows_html .= '<tr>';
					$rows_html .= '<td class="ps-4"><strong>#' . esc_html( $_job_id ) . '</strong></td>';
					$rows_html .= '<td>' . esc_html( $case_number ) . '</td>';
					$rows_html .= '<td>' . wp_kses_post( $devicess ) . '</td>';
					$rows_html .= '<td>' . esc_html( $order_date ) . '</td>';
					$rows_html .= '<td>' . esc_html( $order_total ) . '</td>';
					
					// Rating column - shows actual rating or empty if not reviewed
					$rows_html .= '<td>';
					if ( $review_rating > 0 ) {
						$rows_html .= $this->generate_star_rating( $review_rating );
					} else {
						$rows_html .= '<span class="badge bg-light text-muted">' . esc_html__( 'Not reviewed', 'computer-repair-shop' ) . '</span>';
					}
					$rows_html .= '</td>';
					
					// Review Status
					$rows_html .= '<td>';
					if ( $review_id ) {
						$rows_html .= '<span class="badge bg-success">' . esc_html__( 'Reviewed', 'computer-repair-shop' ) . '</span>';
					} else {
						$rows_html .= '<span class="badge bg-warning">' . esc_html__( 'Pending Review', 'computer-repair-shop' ) . '</span>';
					}
					$rows_html .= '</td>';
					
					// Actions
					$rows_html .= '<td class="text-end pe-4">';
					
					$_feedback_page = get_option( 'wc_rb_get_feedback_page_id' );
					if ( ! empty( $_feedback_page ) ) {
						$page_link = wc_rb_return_get_feedback_link( '', $job_id );
						
						if ( $review_id ) {
							// Already reviewed - show View button
							$rows_html .= '<a href="' . esc_url( $page_link ) . '" target="_blank" ';
							$rows_html .= 'class="btn btn-sm btn-outline-primary d-inline-flex align-items-center">';
							$rows_html .= '<i class="bi bi-eye me-1"></i>';
							$rows_html .= esc_html__( 'View', 'computer-repair-shop' );
							$rows_html .= '</a>';
						} else {
							// Not reviewed - show Review button
							$rows_html .= '<a href="' . esc_url( $page_link ) . '" target="_blank" ';
							$rows_html .= 'class="btn btn-sm btn-success d-inline-flex align-items-center">';
							$rows_html .= '<i class="bi bi-pencil-square me-1"></i>';
							$rows_html .= esc_html__( 'Review', 'computer-repair-shop' );
							$rows_html .= '</a>';
						}
					} else {
						$rows_html .= '<span class="badge bg-warning">' . esc_html__( 'Unavailable', 'computer-repair-shop' ) . '</span>';
					}
					
					$rows_html .= '</td>';
					$rows_html .= '</tr>';
				}
				wp_reset_postdata();
			}
		}
		
		// Get stats
		$stats_html = $this->generate_reviews_stats( $is_admin_user, $user_id );
		
		// Generate filters HTML
		$filters_html = ( $is_admin_user ) ? $this->generate_reviews_filters( $is_admin_view ) : '';
		
		// Generate pagination
		$pagination_html = $this->generate_reviews_pagination( $total_items, $reviews_per_page, $current_page, $is_admin_user );
		
		return array(
			'stats' => $stats_html,
			'filters' => $filters_html,
			'rows' => $rows_html,
			'pagination' => $pagination_html
		);
	}

	function generate_reviews_stats( $is_admin_user, $user_id ) {
		global $wpdb;
		
		$stats_html = '<div class="row g-3 mb-4">';
		
		if ( $is_admin_user ) {
			// Admin/Technician/Store Manager stats: Rating distribution
			$rating_counts = array(5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0);
			
			$all_reviews_args = array(
				'post_type'      => 'rep_reviews',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'fields'         => 'ids'
			);
			
			$all_reviews = new WP_Query( $all_reviews_args );
			
			foreach ( $all_reviews->posts as $review_id ) {
				$rating = get_post_meta( $review_id, '_review_rating', true );
				if ( $rating && isset( $rating_counts[$rating] ) ) {
					$rating_counts[$rating]++;
				}
			}
			wp_reset_postdata();
			
			// Total reviews
			$total_reviews = array_sum( $rating_counts );
			$average_rating = 0;
			
			if ( $total_reviews > 0 ) {
				$total_stars = 0;
				foreach ( $rating_counts as $stars => $count ) {
					$total_stars += $stars * $count;
				}
				$average_rating = round( $total_stars / $total_reviews, 1 );
			}
			
			// Total reviews card
			$stats_html .= '<div class="col">';
			$stats_html .= '<div class="card stats-card bg-primary text-white">';
			$stats_html .= '<div class="card-body text-center p-3">';
			$stats_html .= '<div class="mb-2"><i class="bi bi-star-fill fs-1 opacity-75"></i></div>';
			$stats_html .= '<h6 class="card-title mb-1 text-white">' . esc_html__( 'Total Reviews', 'computer-repair-shop' ) . '</h6>';
			$stats_html .= '<h3 class="mb-0 text-white">' . esc_html( $total_reviews ) . '</h3>';
			$stats_html .= '</div>';
			$stats_html .= '</div>';
			$stats_html .= '</div>';
			
			// Average rating card
			$stats_html .= '<div class="col">';
			$stats_html .= '<div class="card stats-card bg-success text-white">';
			$stats_html .= '<div class="card-body text-center p-3">';
			$stats_html .= '<div class="mb-2"><i class="bi bi-graph-up fs-1 opacity-75"></i></div>';
			$stats_html .= '<h6 class="card-title mb-1 text-white">' . esc_html__( 'Avg. Rating', 'computer-repair-shop' ) . '</h6>';
			$stats_html .= '<h3 class="mb-0 text-white">' . esc_html( $average_rating ) . '/5</h3>';
			$stats_html .= '</div>';
			$stats_html .= '</div>';
			$stats_html .= '</div>';
			
			// Rating distribution cards (5-star and 1-star only for space)
			$stats_html .= '<div class="col">';
			$stats_html .= '<div class="card stats-card bg-warning text-white">';
			$stats_html .= '<div class="card-body text-center p-3">';
			$stats_html .= '<div class="mb-2"><i class="bi bi-star-fill fs-1 opacity-75"></i><i class="bi bi-star-fill fs-1 opacity-75"></i><i class="bi bi-star-fill fs-1 opacity-75"></i><i class="bi bi-star-fill fs-1 opacity-75"></i><i class="bi bi-star-fill fs-1 opacity-75"></i></div>';
			$stats_html .= '<h6 class="card-title mb-1 text-white">' . esc_html__( '5 Stars', 'computer-repair-shop' ) . '</h6>';
			$stats_html .= '<h3 class="mb-0 text-white">' . esc_html( $rating_counts[5] ) . '</h3>';
			$stats_html .= '</div>';
			$stats_html .= '</div>';
			$stats_html .= '</div>';
			
			$stats_html .= '<div class="col">';
			$stats_html .= '<div class="card stats-card bg-danger text-white">';
			$stats_html .= '<div class="card-body text-center p-3">';
			$stats_html .= '<div class="mb-2"><i class="bi bi-star-fill fs-1 opacity-75"></i></div>';
			$stats_html .= '<h6 class="card-title mb-1 text-white">' . esc_html__( '1 Star', 'computer-repair-shop' ) . '</h6>';
			$stats_html .= '<h3 class="mb-0 text-white">' . esc_html( $rating_counts[1] ) . '</h3>';
			$stats_html .= '</div>';
			$stats_html .= '</div>';
			$stats_html .= '</div>';
			
		} else {
			// Customer stats
			$selected_status = ( empty( get_option( 'wcrb_send_feedback_request_jobstatus' ) ) ) ? 'delivered' : get_option( 'wcrb_send_feedback_request_jobstatus' );
			
			// Get user's completed jobs (with reviewable status)
			$completed_jobs_args = array(
				'post_type'      => 'rep_jobs',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => '_customer',
						'value'   => $user_id,
						'compare' => '='
					),
					array(
						'key'     => '_wc_order_status',
						'value'   => $selected_status,
						'compare' => '='
					)
				),
				'fields'         => 'ids'
			);
			
			$completed_jobs = new WP_Query( $completed_jobs_args );
			$total_completed = $completed_jobs->found_posts;
			
			// Get user's reviews by finding reviews linked to their jobs
			$user_reviews_count = 0;
			$total_stars = 0;
			
			if ( $completed_jobs->have_posts() ) {
				foreach ( $completed_jobs->posts as $job_id ) {
					$review_id = get_post_meta( $job_id, '_review_id', true );
					if ( ! empty( $review_id ) ) {
						$user_reviews_count++;
						
						// Get rating for this review
						$rating = get_post_meta( $review_id, '_review_rating', true );
						if ( $rating ) {
							$total_stars += $rating;
						}
					}
				}
				wp_reset_postdata();
			}
			
			// Calculate average rating
			$average_rating = 0;
			if ( $user_reviews_count > 0 ) {
				$average_rating = round( $total_stars / $user_reviews_count, 1 );
			}
			
			// Stats cards for customer
			$customer_stats = array(
				array(
					'label' => esc_html__( 'Jobs Completed', 'computer-repair-shop' ),
					'count' => $total_completed,
					'icon' => 'bi-check-circle',
					'color' => 'bg-primary'
				),
				array(
					'label' => esc_html__( 'Reviews Given', 'computer-repair-shop' ),
					'count' => $user_reviews_count,
					'icon' => 'bi-star-fill',
					'color' => 'bg-success'
				),
				array(
					'label' => esc_html__( 'Average Rating', 'computer-repair-shop' ),
					'count' => $average_rating . '/5',
					'icon' => 'bi-graph-up',
					'color' => 'bg-info'
				),
				array(
					'label' => esc_html__( 'Pending Review', 'computer-repair-shop' ),
					'count' => max( 0, $total_completed - $user_reviews_count ),
					'icon' => 'bi-clock',
					'color' => 'bg-warning'
				)
			);
			
			foreach ( $customer_stats as $stat ) {
				$stats_html .= '<div class="col">';
				$stats_html .= '<div class="card stats-card ' . esc_attr( $stat['color'] ) . ' text-white">';
				$stats_html .= '<div class="card-body text-center p-3">';
				$stats_html .= '<div class="mb-2"><i class="bi ' . esc_attr( $stat['icon'] ) . ' fs-1 opacity-75"></i></div>';
				$stats_html .= '<h6 class="card-title mb-1 text-white">' . esc_html( $stat['label'] ) . '</h6>';
				$stats_html .= '<h3 class="mb-0 text-white">' . esc_html( $stat['count'] ) . '</h3>'; // FIXED: Changed $rows_html to $stats_html
				$stats_html .= '</div>';
				$stats_html .= '</div>';
				$stats_html .= '</div>';
			}
		}
		
		$stats_html .= '</div>';
		return $stats_html;
	}

	function generate_reviews_filters( $is_admin_view ) {
		$filters_html = '';
		$filters_html .= '<div class="card mb-4">';
		$filters_html .= '<div class="card-body">';
		$filters_html .= '<form method="get" action="" class="row g-3">';
		$filters_html .= '<input type="hidden" name="screen" value="reviews" />';
		
		// Search input
		$filters_html .= '<div class="col-md-4">';
		$filters_html .= '<div class="input-group">';
		$filters_html .= '<span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>';
		$filters_html .= '<input type="text" class="form-control border-start-0" name="review_search" id="reviewSearch" ';
		$filters_html .= 'value="' . ( isset( $_GET['review_search'] ) ? esc_attr( sanitize_text_field( $_GET['review_search'] ) ) : '' ) . '" ';
		$filters_html .= 'placeholder="' . esc_attr__( 'Search...', 'computer-repair-shop' ) . '">';
		$filters_html .= '</div>';
		$filters_html .= '</div>';
		
		// Rating filter (for admin view)
		if ( $is_admin_view ) {
			$filters_html .= '<div class="col-md-3">';
			$filters_html .= '<select name="rating_filter" class="form-select">';
			$filters_html .= '<option value="all">' . esc_html__( 'All Ratings', 'computer-repair-shop' ) . '</option>';
			for ( $i = 5; $i >= 1; $i-- ) {
				$selected = ( isset( $_GET['rating_filter'] ) && $_GET['rating_filter'] == $i ) ? 'selected' : '';
				$filters_html .= '<option value="' . $i . '" ' . $selected . '>';
				for ( $j = 1; $j <= $i; $j++ ) {
					$filters_html .= '★';
				}
				$filters_html .= ' (' . $i . ' ' . esc_html__( 'stars', 'computer-repair-shop' ) . ')';
				$filters_html .= '</option>';
			}
			$filters_html .= '</select>';
			$filters_html .= '</div>';
		} else {
			// For customer view, we can add other filters if needed
			$filters_html .= '<div class="col-md-4"></div>';
			$filters_html .= '<div class="col-md-4"></div>';
		}
		
		// Action buttons
		$filters_html .= '<div class="col-md-2">';
		$filters_html .= '<div class="d-flex gap-2">';
		$filters_html .= '<a href="' . esc_url( add_query_arg( 'screen', 'customer-reviews', get_the_permalink() ) ) . '" ';
		$filters_html .= 'class="btn btn-outline-secondary" id="clearReviewFilters">';
		$filters_html .= '<i class="bi bi-arrow-clockwise"></i>';
		$filters_html .= '</a>';
		$filters_html .= '<button type="submit" class="btn btn-primary" id="applyReviewFilters">';
		$filters_html .= '<i class="bi bi-funnel"></i> ' . esc_html__( 'Filter', 'computer-repair-shop' );
		$filters_html .= '</button>';
		$filters_html .= '</div>';
		$filters_html .= '</div>';
		
		$filters_html .= '</form>';
		$filters_html .= '</div>';
		$filters_html .= '</div>';
		
		return $filters_html;
	}

	function generate_reviews_pagination( $total_items, $items_per_page, $current_page, $is_admin_user = true ) {
		$pagination_html = '';
		$total_pages = ceil( $total_items / $items_per_page );
		
		if ( $total_pages > 1 ) {
			$showing_start = ( $current_page - 1 ) * $items_per_page + 1;
			$showing_end = min( ( $current_page - 1 ) * $items_per_page + $items_per_page, $total_items );
			$current_url = add_query_arg( $_GET, get_the_permalink() );
			
			$pagination_html .= '<div class="card-footer">';
			$pagination_html .= '<div class="d-flex justify-content-between align-items-center">';
			$pagination_html .= '<div class="text-muted">';
			if ( $is_admin_user ) {
				$pagination_html .= sprintf( 
					esc_html__( 'Showing %1$s to %2$s of %3$s reviews', 'computer-repair-shop' ),
					$showing_start,
					$showing_end,
					$total_items
				);
			} else {
				$pagination_html .= sprintf( 
					esc_html__( 'Showing %1$s to %2$s of %3$s jobs', 'computer-repair-shop' ),
					$showing_start,
					$showing_end,
					$total_items
				);
			}
			$pagination_html .= '</div>';
			
			$pagination_html .= '<nav><ul class="pagination mb-0">';
			
			// Previous button
			if ( $current_page > 1 ) {
				$prev_url = add_query_arg( 'reviews_page', $current_page - 1, $current_url );
				$pagination_html .= '<li class="page-item"><a class="page-link" href="' . esc_url( $prev_url ) . '">';
				$pagination_html .= '<i class="bi bi-chevron-left"></i></a></li>';
			} else {
				$pagination_html .= '<li class="page-item disabled"><a class="page-link" href="#" tabindex="-1" aria-disabled="true">';
				$pagination_html .= '<i class="bi bi-chevron-left"></i></a></li>';
			}
			
			// Page numbers
			for ( $i = 1; $i <= $total_pages; $i++ ) {
				if ( $i == 1 || $i == $total_pages || ( $i >= $current_page - 2 && $i <= $current_page + 2 ) ) {
					$page_url = add_query_arg( 'reviews_page', $i, $current_url );
					$active_class = ( $i == $current_page ) ? ' active' : '';
					$pagination_html .= '<li class="page-item' . $active_class . '">';
					$pagination_html .= '<a class="page-link" href="' . esc_url( $page_url ) . '">' . $i . '</a></li>';
				} elseif ( $i == $current_page - 3 || $i == $current_page + 3 ) {
					$pagination_html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
				}
			}
			
			// Next button
			if ( $current_page < $total_pages ) {
				$next_url = add_query_arg( 'reviews_page', $current_page + 1, $current_url );
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
		
		return $pagination_html;
	}
}