<?php
	//List Services shortcode
	//Used to display Services on a page.
	//Linked to single service pages. 

	if ( ! function_exists( 'wcrb_status_check_by_serial' ) ) :
		function wcrb_status_check_by_serial() {
			$_statuscheck_serial   = get_option( 'wcrb_disable_statuscheck_serial' );
			
			return ( $_statuscheck_serial == 'on' ) ? FALSE : TRUE;
		}
	endif;

	if ( ! function_exists( 'wc_order_status_form' ) ) :
		function wc_order_status_form() { 
			global $WCRB_ESTIMATES_OBJ;

			wp_enqueue_style( 'foundation-css');
	        wp_enqueue_style( 'plugin-styles-wc' );
			wp_enqueue_script("foundation-js");
			wp_enqueue_script("wc-cr-js");
			wp_enqueue_script("select2");

			$content = '';

			if ( isset( $_GET['choice'] ) && ! empty( $_GET['choice'] ) ) {
				$choice 	 = sanitize_text_field( $_GET['choice'] );
				$estimate_id = ( isset( $_GET['estimate_id'] ) ) ? sanitize_text_field( $_GET['estimate_id'] ) : '';
				$case_number = ( isset( $_GET['case_number'] ) ) ? sanitize_text_field( $_GET['case_number'] ) : '';

				$estMsg = $WCRB_ESTIMATES_OBJ->process_estimate_choice( $estimate_id, $case_number, $choice );
				$content .= '<div class="callout success">' . esc_html( $estMsg ) . ' </div>';
			}
			
			$content .= '<div class="wc_order_status_form">';
			$content .= '<h2>'.esc_html__("Check your job status!", "computer-repair-shop").'</h2>';
			$content .= '<p>'. sprintf( esc_html__("Please enter your %s which you may received in email or from our outlet.", "computer-repair-shop"), wcrb_get_label( 'casenumber', 'none' ) ) .'</p>';
			
			$the_case_id = '';
			if ( isset( $_GET['case_id'] ) && ! empty( $_GET['case_id'] ) ) {
				$the_case_id = sanitize_text_field( $_GET['case_id'] );
				$content .= '<div id="auto_submit_status"></div>';
			}

			$_serial_label = ( empty( get_option( 'wc_device_id_imei_label' ) ) ) ? esc_html__( 'ID/IMEI', 'computer-repair-shop' ) : get_option( 'wc_device_id_imei_label' );
			$_device_label = ( empty( get_option( 'wc_device_label' ) ) ) ? esc_html__( 'Device', 'computer-repair-shop' ) : get_option( 'wc_device_label' );

			$theplaceholder = ( wcrb_status_check_by_serial() ) ? sprintf( esc_html__( "Your %s or %s %s", "computer-repair-shop" ), wcrb_get_label( 'casenumber', 'none' ), $_device_label, $_serial_label ) : sprintf( esc_html__( "Your %s", "computer-repair-shop" ), wcrb_get_label( 'casenumber', 'none' ) );
			$theplaceholder .= ' ...';

			$content .= '<form data-async="" method="post">';
			$content .= '<input type="text" required autofocus class="wcrb_capitialize" placeholder="'. esc_html( $theplaceholder ) .'" value="' . esc_html( $the_case_id ) . '" name="wc_case_number" />';
			$content .=  wp_nonce_field( 'wc_computer_repair_nonce', 'wc_job_status_nonce', $echo = false);
			$content .= '<input type="submit" class="button button-primary primary" value="'.esc_html__("Check Now!", "computer-repair-shop").'" />';
			$content .= '</form>';

			$content .= '</div>';
			
			$content .= '<div class="form-message orderstatusholder"></div>';

			return $content;
		}//wc_list_services.
		add_shortcode('wc_order_status_form', 'wc_order_status_form');
	endif;


	if(!function_exists("wc_cmp_rep_check_order_status")):

		function wc_cmp_rep_check_order_status() { 
			if (!isset( $_POST['wc_job_status_nonce'] ) 
				|| ! wp_verify_nonce( $_POST['wc_job_status_nonce'], 'wc_computer_repair_nonce' )) :
					$values['message'] = esc_html__("Something is wrong with your submission!", "computer-repair-shop");
					$values['success'] = "YES";
			else:
				//Register User
				$wcCasaeNumber 	  = sanitize_text_field( $_POST["wc_case_number"] );
				$available_action = do_action( 'wc_rb_before_status_check_result' );

				if ( ! empty( $wcCasaeNumber ) ) {
					$wc_cr_args = array(
						'posts_per_page'   => 1,
						'post_type'        => 'rep_jobs',
						'meta_key'         => '_case_number',
						'meta_value'       => $wcCasaeNumber
					);
					if ( wcrb_status_check_by_serial() ) {
					$wc_cr_args = array(
										'posts_per_page'   => 1,
										'post_type'        => 'rep_jobs',
										'meta_query' => array(
																'relation' => 'OR',
																array(
																	'key' 		=> '_case_number',
																	'value' 	=> $wcCasaeNumber,
																	'compare' 	=> '=',
																),
																array(
																	'key' 		=> '_wc_device_data',
																	'value' 	=> sprintf( ':"%s";', $wcCasaeNumber ),
																	'compare' 	=> 'RLIKE',
																	'type'    	=> 'CHAR',
																),
															),
										);
					}
					$wc_cr_query = new WP_Query( $wc_cr_args );

					if ( $wc_cr_query->have_posts() ) : 

						while ( $wc_cr_query->have_posts() ) : 
							$wc_cr_query->the_post();

							$order_id = get_the_ID();
							$post_output = wcrb_return_job_history( $order_id );

							$post_output .= wc_print_order_invoice( $order_id, "status_check" );
						endwhile;

						$values['message'] = $available_action . $post_output;
					else: 
						$values['message'] = esc_html__( "We haven't found any job with the given details!", "computer-repair-shop" );
					endif;
					wp_reset_postdata();
				}
				$values['success'] = "YES";
			endif;
			wp_send_json( $values );
			wp_die();
		}
		add_action( 'wp_ajax_wc_cmp_rep_check_order_status', 'wc_cmp_rep_check_order_status' );
		add_action( 'wp_ajax_nopriv_wc_cmp_rep_check_order_status', 'wc_cmp_rep_check_order_status' );
	endif;

	if ( ! function_exists( 'wcrb_return_job_history' ) ) :
		function wcrb_return_job_history( $order_id ) {
			if ( empty( $order_id ) ) {
				return;
			}

			$generatedHTML = '<a href="#" class="wcCrJobHistoryHideShowBtn"><span class="text-left">' . esc_html__("Add a message/attach files", "computer-repair-shop") . '</span>';
			$generatedHTML .= '<span class="text-right">' . esc_html__("Job history show/hide", "computer-repair-shop") . '</span>';
			$generatedHTML .= "</a>";

			$generatedHTML .= '<div class="wcCrShowHideHistory">';
			
			$generatedHTML .= '<div class="wcrb_post_message_by_customer_status row"><div class="grid-x grid-padding-x"><div class="medium-12 cell">';
			$generatedHTML .= '<h2>' . esc_html__( 'Add a message', 'computer-repair-shop' ) . '</h2>';
			$generatedHTML .= '<form id="wcrb_post_customer_msg" class="needs-validation" method="post">
							<label><textarea name="wcrb_message_on_status" required="" class="form-control login-field" 
							placeholder="' . esc_html__( 'Add a message for technician or owner', 'computer-repair-shop' ) . '"></textarea></label>';

			$generatedHTML .= '<div class="attachmentserror"></div><div class="jobAttachments displayNone" id="jobAttachments"></div>';
			$generatedHTML .= '<label for="reciepetAttachment" class="button button-primary">' . esc_html__( 'Attach Files', 'computer-repair-shop' ) . '</label>
						<input type="file" id="reciepetAttachment" name="reciepetAttachment" data-security="'. esc_attr( wp_create_nonce( 'file_security' ) ) .'" class="show-for-sr">';
			
			$generatedHTML .= wp_nonce_field( 'wcrb_customer_msg_post_action', 'wcrb_customer_msg_post_action_field', true, false );

			$generatedHTML .=	'<input type="hidden" name="order_id" value="' . esc_html( $order_id ) . '" />';
			$generatedHTML .=	'<input type="submit" class="button button-primary primary" value="' . esc_html__( 'Post message', 'computer-repair-shop' ) . '">
				<div class="client_msg_post_reply"></div><!-- AjaX Return /-->
			</form></div></div></div>';

			$generatedHTML .= '<ul class="order_notes">';

			$WCRB_JOB_HISTORY_LOGS = WCRB_JOB_HISTORY_LOGS::getInstance();
			$generatedHTML .= $WCRB_JOB_HISTORY_LOGS->wc_list_job_history( $order_id, "public" );
			$generatedHTML .= '</ul></div>';

			return $generatedHTML;
		} 
	endif;

	if ( ! function_exists( 'wcrb_return_job_history_bootstrap' ) ) :
		function wcrb_return_job_history_bootstrap( $order_id, $message_field ) {
			if ( empty( $order_id ) ) {
				return '';
			}
			
			wp_enqueue_style( 'checkstatus-style');

			$message_field = ( $message_field == 'active' ) ? 'active' : '';
			ob_start(); // Use output buffering for cleaner code
			?>
			<!-- Job History Bootstrap Component -->
			<div class="card job-history-card border-0 shadow-sm mb-4 hidden-print">
				
				<!-- Toggle Header -->
				<div class="card-header bg-primary border-bottom-0 p-3" id="jobHistoryHeading">
					<button class="btn btn-link w-100 d-flex justify-content-between align-items-center p-0" 
							type="button" 
							data-bs-toggle="collapse" 
							data-bs-target="#jobHistoryCollapse" 
							aria-expanded="false" 
							aria-controls="jobHistoryCollapse">
						
						<span class="fs-5 fw-semibold text-white">
							<i class="fas fa-history me-2"></i>
							<?php esc_html_e( 'Job History & Messages', 'computer-repair-shop' ); ?>
						</span>
						
						<span class="badge bg-light text-dark">
							<i class="bi bi-chevron-bar-up"></i>
						</span>
					</button>
				</div>

				<!-- Collapsible Content -->
				<div class="collapse show hidden-print orderstatusholder" id="jobHistoryCollapse">
					<div class="card-body p-0">
						
						<!-- Message Form -->
						<div class="message-form-section p-4 bg-light rounded-top">
							<h3 class="h5 mb-3 fw-semibold">
								<i class="fas fa-comment-dots me-2 text-primary"></i>
								<?php esc_html_e( 'Add New Message', 'computer-repair-shop' ); ?>
							</h3>
							
							<form id="wcrb_post_customer_msg" class="needs-validation" method="post" enctype="multipart/form-data">

								<!-- Message Textarea -->
								<div class="mb-3">
									<label for="wcrb_message_on_status" class="form-label fw-medium">
										<?php esc_html_e( 'Your Message', 'computer-repair-shop' ); ?>
									</label>
									<textarea 
										name="wcrb_message_on_status" 
										id="wcrb_message_on_status" 
										class="form-control" 
										rows="4"
										placeholder="<?php esc_attr_e( 'Type your message here...', 'computer-repair-shop' ); ?>"
										required></textarea>
									<div class="form-text">
										<?php esc_html_e( 'This message will be visible to technicians and administrators.', 'computer-repair-shop' ); ?>
									</div>
								</div>
								<div class="attachmentserror"></div>
								<div class="jobAttachments displayNone" id="jobAttachments"></div>

								<!-- File Attachments -->
								<div class="mb-3">
									<div class="attachments-error alert alert-danger d-none" role="alert"></div>
									<div class="file-attachment-preview mb-2" id="fileAttachmentPreview"></div>
								</div>

								<!-- Hidden Fields -->
								<?php wp_nonce_field( 'wcrb_customer_msg_post_action', 'wcrb_customer_msg_post_action_field' ); ?>
								<input type="hidden" name="order_id" value="<?php echo esc_attr( $order_id ); ?>">

								<!-- Submit Button -->
								<div class="d-grid gap-2">
									<label for="reciepetAttachment" class="btn btn-success btn-sm w-100" id="addAttachmentBtn">
											<i class="fas fa-paperclip me-1"></i>
											<?php esc_html_e( 'Add Files', 'computer-repair-shop' ); ?>
										</label>
										<input type="file" reciepetAttachment
											id="reciepetAttachment" 
											name="reciepetAttachment" 
											multiple 
											class="d-none"
											data-security="<?php echo esc_attr( wp_create_nonce( 'file_security' ) ); ?>">
									<button type="submit" class="btn btn-primary btn-sm expanded extended w-100">
										<i class="fas fa-paper-plane me-2"></i>
										<?php esc_html_e( 'Post Message', 'computer-repair-shop' ); ?>
									</button>
								</div>

								<!-- AJAX Response -->
								<div class="client_msg_post_reply mt-3"></div>
							</form>
						</div>

						<!-- Job History Logs -->
						<div class="history-logs-section p-4">
							<h3 class="h5 mb-4 fw-semibold border-bottom pb-2">
								<i class="fas fa-stream me-2 text-primary"></i>
								<?php esc_html_e( 'Job History', 'computer-repair-shop' ); ?>
							</h3>
							
							<div class="timeline">
								<ul>
								<?php
									$WCRB_JOB_HISTORY_LOGS = WCRB_JOB_HISTORY_LOGS::getInstance();
									echo $WCRB_JOB_HISTORY_LOGS->wc_list_job_history( $order_id, "public" );
								?>
								</ul>
							</div>
						</div>

					</div>
				</div>

			</div>
			<?php
			
			return ob_get_clean();
		}
	endif;

	if ( ! function_exists( 'wcrb_post_customer_message_status' ) ) :
		function wcrb_post_customer_message_status() {
			global $WCRB_EMAILS;

			$values = array();

			if (!isset( $_POST['wcrb_customer_msg_post_action_field'] ) 
				|| ! wp_verify_nonce( $_POST['wcrb_customer_msg_post_action_field'], 'wcrb_customer_msg_post_action' )) :
					$values['message'] = esc_html__( "Something is wrong with your submission!", "computer-repair-shop" );
					$values['success'] = "YES";
			else:
				//Register User
				if ( empty( $_POST['order_id'] ) || empty( $_POST['wcrb_message_on_status'] ) ) {
					$message = esc_html__( 'Something is wrong with your submission!', 'computer-repair-shop' );
				} else {
					$job_id 				= sanitize_text_field( $_POST['order_id'] );
					$wcrb_message_on_status = sanitize_textarea_field( $_POST['wcrb_message_on_status'] );
					$customer_id 			= get_post_meta( $job_id, "_customer", true );

					if ( empty( $customer_id ) ) {
						$message = esc_html__( 'Customer not set for this job', 'computer-repair-shop' );
					} else {
						$WCRB_JOB_HISTORY_LOGS = WCRB_JOB_HISTORY_LOGS::getInstance();

						if ( isset( $_POST["repairBuddAttachment_file"] ) && ! empty( $_POST["repairBuddAttachment_file"] ) ) {
							//[]
							$attachment_msg = '<br><br>' . esc_html__( 'File attachment by customer', 'computer-repair-shop' ) . '<br>';

							$attachments = $_POST["repairBuddAttachment_file"];
							foreach( $attachments as $attachment ) {
								$attachment = sanitize_url( $attachment );
								$attachment_msg .= '<br>' . $attachment;
								$arguments = array(
									'label' => esc_html__( 'Attached Receipt', 'computer-repair-shop' ),
									'details' => $attachment,
									'visibility' => 'public',
									'type' => 'file',
									'description' => esc_html__( 'File attachment by customer', 'computer-repair-shop' ),
								);
								wc_job_extra_items_add( $arguments, $job_id );
							}
						}
						$wcrb_message_on_status .= $attachment_msg;

						//Let's add msg to db.
						$args = array(
							"job_id" 		=> $job_id, 
							"name" 			=> esc_html__( 'Customer posted message: ', 'computer-repair-shop' ), 
							"type" 			=> 'public', 
							"field" 		=> '_customer_message', 
							"change_detail" => $wcrb_message_on_status,
							"user_id"		=> $customer_id
						);
						$WCRB_JOB_HISTORY_LOGS->wc_record_job_history( $args );

						//Now needs to send mail to admin.
						$admin_email = ( ! empty( get_option( 'admin_email' ) ) ) ? get_option( 'admin_email' ) : '';

						if ( ! empty( $admin_email ) ) {
							$subject = esc_html__( 'Customer posted message: ', 'computer-repair-shop' ) . esc_html__( 'Job ID', 'computer-repair-shop' ) . ' | ' . esc_html( $job_id );
							$wcrb_message_on_status .= $wcrb_message_on_status . '<br><br><br>' . sprintf( esc_html__( 'Message posted to %s', 'computer-repair-shop' ), wcrb_get_label( 'casenumber', 'none' ) ) . get_the_title( $job_id );
							$email_body = wp_kses_post( $wcrb_message_on_status );
							$WCRB_EMAILS->send_email( $admin_email, $subject, $email_body, '' );
						}
						$values['redirect_url'] = wc_rb_return_status_check_link( $job_id );
					}
					$message = esc_html__( 'Message posted', 'computer-repair-shop' );
				}

				$values['message'] = $message;
				$values['success'] = "YES";
			endif;
			
			wp_send_json( $values );
			wp_die();
		}
		add_action( 'wp_ajax_wcrb_post_customer_message_status', 'wcrb_post_customer_message_status' );
		add_action( 'wp_ajax_nopriv_wcrb_post_customer_message_status', 'wcrb_post_customer_message_status' );
	endif;