<?php
/**
 * Plugin Name: WCRB Blank Dashboard
 */

defined( 'ABSPATH' ) || exit;

add_action( 'plugins_loaded', [ 'WCRB_TIME_MANAGEMENT', 'getInstance' ] );

class WCRB_TIME_MANAGEMENT {
    private static $instance = null;

    public $_user_role   = '';
    public $_user_id     = 0;
    public $_allowedHTML = array();
    public $_date_format = '';
    public $_timelog_table = '';

    private $TABID = "wcrb_timelog_tab";

    public static function getInstance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        global $wpdb;

        $this->_allowedHTML = function_exists( 'wc_return_allowed_tags' ) ? wc_return_allowed_tags() : array();
        $this->_date_format = get_option( 'date_format' );
        $this->_timelog_table = $wpdb->prefix . 'wc_cr_time_logs';

        add_action( 'wc_rb_settings_tab_menu_item', array( $this, 'add_timelog_tab_in_settings_menu' ), 10, 2 );
        add_action( 'wc_rb_settings_tab_body', array( $this, 'add_timelog_tab_in_settings_body' ), 10, 2 );
		add_action( 'wp_ajax_wcrb_update_timelog_settings', array( $this, 'wcrb_update_timelog_settings' ) );

        add_action( 'wp_ajax_wcrb_save_time_entry', array( $this, 'wcrb_save_time_entry_handler' ) );
        add_action( 'wp_ajax_wcrb_get_chart_data_tl', array( $this, 'wcrb_get_chart_data_handler' ) );
    }

    /**
     * Backend Options
     */
    function add_timelog_tab_in_settings_menu() {
        $active = '';

        $menu_output = '<li class="tabs-title' . esc_attr($active) . '" role="presentation">';
        $menu_output .= '<a href="#' . esc_attr( $this->TABID ) . '" role="tab" aria-controls="' . esc_attr( $this->TABID ) . '" aria-selected="true" id="' . esc_attr( $this->TABID ) . '-label">';
        $menu_output .= '<h2>' . esc_html__( 'Time Log Settings', 'computer-repair-shop' ) . '</h2>';
        $menu_output .=	'</a>';
        $menu_output .= '</li>';

        echo wp_kses_post( $menu_output );
    }
	
	function add_timelog_tab_in_settings_body() {
        $active = '';
		
		$setting_body = '<div class="tabs-panel team-wrap' . esc_attr( $active ) . '" 
        id="' . esc_attr( $this->TABID ) . '" 
        role="tabpanel" 
        aria-hidden="true" 
        aria-labelledby="' . esc_attr( $this->TABID ) . '-label">';

		$setting_body .= '<div class="wc-rb-manage-devices">';
		$setting_body .= '<h2>' . esc_html__( 'Time Log Settings', 'computer-repair-shop' ) . '</h2>';
		$setting_body .= '<div class="timelog_success_msg"></div>';
		
		$setting_body .= '<form data-async data-abide class="needs-validation" novalidate method="post" data-success-class=".timelog_success_msg">';
		$setting_body .= '<table class="form-table border"><tbody>';

        //Setting Item Starts
		$setting_body .= '<tr>
							<th scope="row">
								<label for="wcrb_disable_timelog">
									' . esc_html__( 'Disable Time Log Completely', 'computer-repair-shop' ) . '
								</label>
							</th>';
		$setting_body .= '<td>';

		$wcrb_disable_timelog = get_option( 'wcrb_disable_timelog' );
		$wcrb_disable_timelog = ( $wcrb_disable_timelog == 'on' ) ? 'checked="checked"' : '';
		
		$setting_body .= '<input type="checkbox" ' . esc_html( $wcrb_disable_timelog ) . ' name="wcrb_disable_timelog" id="wcrb_disable_timelog" />';
		
		$setting_body .= '<label for="wcrb_disable_timelog">';
		$setting_body .= esc_html__( 'Disable Time Log Completely', 'computer-repair-shop' );
		$setting_body .= '</label></td></tr>';
		//Setting Item Ends

        $time_log_tax = ( ! empty( get_option( 'wcrb_timelog_tax' ) ) ) ? get_option( 'wcrb_timelog_tax' ) : get_option( 'wc_primary_tax' );

        $setting_body .= '<tr><th scope="row"><label for="wcrb_timelog_tax">' . esc_html__( 'Default tax for hours', 'computer-repair-shop' ) . '</label></th>';
		$setting_body .= '<td><select name="wcrb_timelog_tax" id="wcrb_timelog_tax" class="form-control">';
		$setting_body .= '<option value="">' . esc_html__( 'Select tax', 'computer-repair-shop' ) . '</option>';
		
		$theOptions = wc_generate_tax_options( $time_log_tax ); 
		$allowedHTML = array(
			"option" => array(
				"value" => array(),
				"selected" => array()
			),
		);

		$setting_body .= wp_kses( $theOptions, $allowedHTML );
		$setting_body .= '</select></td></tr>';

        //Setting Starts
        $selected_page = get_option( 'wcrb_timelog_job_status' );
		$setting_body .= '<tr>
							<th scope="row">
								<label for="wcrb_timelog_job_status">
									' . esc_html__( 'Enable time log', 'computer-repair-shop' ) . '
								</label>
							</th>';
		$setting_body .= '<td>';

		$setting_body .= '<fieldset class="fieldset">
                            <legend>'.esc_html__( "Select job status to include", "computer-repair-shop" ).'</legend>';
		$setting_body .= $GLOBALS['OBJ_SMS_SYSTEM']->wc_rb_generate_status_checkboxes( $selected_page );
		$setting_body .= '<p>' . esc_html__( 'To make time log work make sure to create correct my account page in page settings.', 'computer-repair-shop' ) . '</p>';
		$setting_body .= '</fieldset>';

		$setting_body .= '</td></tr>';

        //Setting activity
        $selected_activity = get_option( 'wcrb_timelog_activities' );
        $selected_activity = empty( $selected_activity ) ? "Repair\nDiagnostic\nTesting\nCleaning\nConsultation\nOther" : $selected_activity;
        $setting_body .= '<tr>
                            <th scope="row">
                                <label for="wcrb_timelog_activities">
                                    ' . esc_html__( 'Time Log Activities', 'computer-repair-shop' ) . '
                                </label>
                            </th>';
        $setting_body .= '<td>';
        $setting_body .= '<fieldset class="fieldset">
                            <legend>'.esc_html__( "Define activities for time log", "computer-repair-shop" ).'</legend>';
        $setting_body .= '<textarea name="wcrb_timelog_activities" id="wcrb_timelog_activities" rows="5" cols="50" class="large-text code">' . esc_textarea( $selected_activity ) . '</textarea>';  
        $setting_body .= '<p>' . esc_html__( 'Define activities for time log, one per line.', 'computer-repair-shop' ) . '</p>';
        $setting_body .= '</fieldset>';
        $setting_body .= '</td></tr>';

		$setting_body .= '</tbody></table>';

		$setting_body .= '<input type="hidden" name="form_type" value="wcrb_update_settings_form" />';
		$setting_body .= '<input type="hidden" name="form_action" value="wcrb_update_timelog_settings" />';
		
		$setting_body .= wp_nonce_field( 'wcrb_nonce_timelog', 'wcrb_nonce_timelog_field', true, false );

		$setting_body .= '<button type="submit" class="button button-primary" data-type="rbssubmitdevices">' . esc_html__( 'Update Options', 'computer-repair-shop' ) . '</button></form>';

		$setting_body .= '</div><!-- wc rb Devices /-->';
		$setting_body .= '</div><!-- Tabs Panel /-->';

		$allowedHTML = ( function_exists( 'wc_return_allowed_tags' ) ) ? wc_return_allowed_tags() : '';
		echo wp_kses( $setting_body, $allowedHTML );
	}

	function wcrb_update_timelog_settings() {
		$message = '';
		$success = 'NO';

		$form_type = ( isset( $_POST['form_type'] ) ) ? sanitize_text_field( $_POST['form_type'] ) : '';
        $wc_rb_job_status_include = sanitize_text_field( serialize( $_POST['wc_rb_job_status_include'] ) );
				
		if ( 
			isset( $_POST['wcrb_nonce_timelog_field'] ) 
			&& wp_verify_nonce( $_POST['wcrb_nonce_timelog_field'], 'wcrb_nonce_timelog' ) 
			&& $form_type == 'wcrb_update_settings_form' ) {
            
            $wc_rb_job_status_include = ( empty( $wc_rb_job_status_include ) ) ? array( 'new' ) : $wc_rb_job_status_include;
            update_option( 'wcrb_timelog_job_status', $wc_rb_job_status_include );
            $wcrb_timelog_activities = ( isset( $_POST['wcrb_timelog_activities'] ) && ! empty( $_POST['wcrb_timelog_activities'] ) ) ? sanitize_textarea_field( wp_unslash( $_POST['wcrb_timelog_activities'] ) ) : '';
            update_option( 'wcrb_timelog_activities', $wcrb_timelog_activities );

            $submit_arr = array( 
                            'wcrb_timelog_tax', 
                            'wcrb_disable_timelog' 
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
    /**
     * Backend Options Ends here
     */
    public function return_technician_box( $_job_id ) {
        if ( empty( $_job_id ) ) {
            return '';
        }

        $current_user = wp_get_current_user();
        $_user_role = $current_user->roles[0] ?? 'guest';

        $_techarray = array();
        $content    = '';

        $_technicians = get_post_meta( $_job_id, '_technician', true );

        if ( is_array( $_technicians ) && ! empty( $_technicians ) ) {
            $_techarray = $_technicians;
        } elseif ( ! empty( $_technicians ) ) {
            $_techarray = array( $_technicians );
            update_post_meta( $_job_id, '_technician', $_techarray );
        } else {
            $_techarray = array();
        }

        // Get time logs for this job
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_cr_time_logs';
        
        $time_logs = $wpdb->get_results($wpdb->prepare("
            SELECT 
                log_id,
                start_time,
                end_time,
                activity,
                work_description,
                technician_id,
                device_data,
                total_minutes,
                hourly_cost,
                hourly_rate,
                created_at,
                log_state
            FROM {$table_name}
            WHERE job_id = %d
            ORDER BY start_time DESC
        ", $_job_id), ARRAY_A);

        $content = '<div class="wcrb-tech-box wcrb-box wcRbJob_services_wrap">';

        $content .= '<div class="grid-x grid-margin-x">
                        <div class="small-4 cell">
                            <h3> '. esc_html__( 'Technicians & Time Logs', 'computer-repair-shop' ) . ' </h3>
                        </div>

                        <div class="small-8 cell">';

        if ( $_user_role == 'administrator' || $_user_role == 'store_manager' ) {
            $content .= '<label class="have-addition"><strong>';
            $content .= esc_html__( 'Select Technician', 'computer-repair-shop' ) . '</strong>';
            
            $content .= wcrb_dropdown_users_multiple_roles( array(
                'show_option_all' => esc_html__('Select Technician', 'computer-repair-shop'),
                'name' 		  => 'technician',
                'role__in' 	  => array( 'technician', 'store_manager', 'administrator' ),
                'selected' 	  => $_techarray,
                'multiple' 	  => true,
                'placeholder' => esc_html__( 'Select Technician', 'computer-repair-shop' ),
                'show_roles'  => true) 
            );
            $content .= '<a class="button button-primary button-small" title="' . esc_html__( 'Add New Technician', 'computer-repair-shop' ) . '" data-open="technicianFormReveal"><span class="dashicons dashicons-plus"></span></a>';
            $content .= '</label>';
        }

        $content .= '</div>
                    </div><!-- end of grid x -->';

        $wcrb_disable_timelog = get_option( 'wcrb_disable_timelog' );
        if ( $wcrb_disable_timelog !== 'on' ) {
            $content .= '<a href="https://youtu.be/6AwbX7fbFPA" target="_blank" class="wcrbhelpfulvideo-job" title="Time log tutorial">';
            $content .= '<img src="'. esc_url( WC_COMPUTER_REPAIR_DIR_URL . '/assets/admin/images/icons/video-help.png' ) .'" />';
            $content .= '</a>';

            $content .= '<div class="cell small-12 table-scroll">
                            <div class="technician_timelog_msgs"></div>';
            
            $content .= '<table class="grey-bg wc_table technicianlogstable">
                            <thead>
                                <tr>
                                    <th>'. esc_html__( 'ID', 'computer-repair-shop' ) .'</th>
                                    <th>'. esc_html__( 'Started', 'computer-repair-shop' ) .'</th>
                                    <th>'. esc_html__( 'Ended', 'computer-repair-shop' ) .'</th>
                                    <th>'. esc_html__( 'Duration', 'computer-repair-shop' ) .'</th>
                                    <th>'. esc_html__( 'Activity', 'computer-repair-shop' ) .'</th>
                                    <th>'. esc_html__( 'Description', 'computer-repair-shop' ) .'</th>
                                    <th>'. esc_html__( 'Technician', 'computer-repair-shop' ) .'</th>
                                    <th>'. esc_html__( 'Device', 'computer-repair-shop' ) .'</th>
                                    <th>'. esc_html__( 'Rate', 'computer-repair-shop' ) .'</th>
                                    <th>'. esc_html__( 'Charge', 'computer-repair-shop' ) .'</th>
                                    <th>'. esc_html__( 'Status', 'computer-repair-shop' ) .'</th>
                                    <th>'. esc_html__( 'Created At', 'computer-repair-shop' ) .'</th>
                                </tr>
                            </thead>';
            
            $content .= '<tbody>';
            
            if (empty($time_logs)) {
                $content .= '<tr>
                                <td colspan="12" class="text-center">
                                    ' . esc_html__( 'No time logs found for this job.', 'computer-repair-shop' ) . '
                                </td>
                            </tr>';
            } else {
                foreach ($time_logs as $log) {
                    // Calculate hours and format time
                    $start_time = !empty($log['start_time']) ? date_i18n('M j, Y g:i A', strtotime($log['start_time'])) : '--';
                    $end_time = !empty($log['end_time']) ? date_i18n('M j, Y g:i A', strtotime($log['end_time'])) : '--';
                    //total_minutestotal_minutes
                    $total_hours = $log['total_minutes'] / 60;
                    $duration_formatted = sprintf('%.2f', $total_hours) . 'h';
                    
                    // Get technician name
                    $technician_name = 'Unknown';
                    if (!empty($log['technician_id'])) {
                        $user = get_userdata($log['technician_id']);
                        if ($user) {
                            $technician_name = $user->display_name;
                        }
                    }
                    
                    // Get device info
                    $device_info = '--';
                    if (!empty($log['device_data'])) {
                        $device_data = json_decode($log['device_data'], true);
                        if (is_array($device_data)) {
                            $device_info = '';
                            if (!empty($device_data['device_name'])) {
                                $device_info .= esc_html($device_data['device_name']);
                            }
                            if (!empty($device_data['device_serial'])) {
                                $device_info .= '<br><small>' . esc_html__('SN:', 'computer-repair-shop') . ' ' . esc_html($device_data['device_serial']) . '</small>';
                            }
                        }
                    }
                    
                    // Format costs
                    $hourly_rate = !empty($log['hourly_rate']) ? wc_cr_currency_format( $log['hourly_rate'] ) : '--';
                    $total_cost = '--';
                    if (!empty($log['hourly_rate']) && $log['total_minutes'] > 0) {
                        $total_charge = ($total_hours * $log['hourly_rate']);
                        $total_cost = wc_cr_currency_format($total_charge);
                    }
                    
                    // Status badge
                    $status_badge = '';
                    switch ($log['log_state']) {
                        case 'pending':
                            $status_badge = '<span class="badge badge-warning">' . esc_html__('Pending', 'computer-repair-shop') . '</span>';
                            break;
                        case 'approved':
                            $status_badge = '<span class="badge badge-success">' . esc_html__('Approved', 'computer-repair-shop') . '</span>';
                            break;
                        case 'rejected':
                            $status_badge = '<span class="badge badge-danger">' . esc_html__('Rejected', 'computer-repair-shop') . '</span>';
                            break;
                        case 'billed':
                            $status_badge = '<span class="badge badge-info">' . esc_html__('Billed', 'computer-repair-shop') . '</span>';
                            break;
                        default:
                            $status_badge = '<span class="badge badge-secondary">' . esc_html($log['log_state']) . '</span>';
                    }
                    
                    $created_at = !empty($log['created_at']) ? date_i18n('M j, Y', strtotime($log['created_at'])) : '--';
                    
                    $content .= '<tr>';
                    $content .= '<td>' . esc_html($log['log_id']) . '</td>';
                    $content .= '<td>' . esc_html($start_time) . '</td>';
                    $content .= '<td>' . esc_html($end_time) . '</td>';
                    $content .= '<td>' . esc_html($duration_formatted) . '</td>';
                    $content .= '<td>' . esc_html($log['activity']) . '</td>';
                    $content .= '<td>' . esc_html(wp_trim_words($log['work_description'], 10, '...')) . '</td>';
                    $content .= '<td>' . esc_html($technician_name) . '</td>';
                    $content .= '<td>' . $device_info . '</td>';
                    $content .= '<td>' . $hourly_rate . '</td>';
                    $content .= '<td>' . $total_cost . '</td>';
                    $content .= '<td>' . $status_badge . '</td>';
                    $content .= '<td>' . esc_html($created_at) . '</td>';
                    $content .= '</tr>';
                }
            }
            
            $content .= '</tbody></table>';

            if ( is_wcrb_current_user_have_technician_access( $_job_id ) ) {
                $_dashboardpage = get_option( 'wc_rb_my_account_page_id' );
                if ( empty( $_dashboardpage ) || $_dashboardpage < 1 ) {
                    $content .= '<div class="cell">' . esc_html__( 'Please set My Account page in Settings to log time.', 'computer-repair-shop' ) . '</div>';             
                } else {
                    $_pagelink = get_the_permalink( $_dashboardpage );
                    $_timeloglink = add_query_arg( array( 'screen' => 'timelog', 'job_id' => $_job_id ), $_pagelink );
                    
                    $content .= '<div class="cell">
                                <a class="button button-primary button-small float-right" target="_blank" href="' . esc_url( $_timeloglink ) . '">' 
                                    . esc_html__( 'Log Time', 'computer-repair-shop' ) . 
                                '</a>
                            </div>';
                }
            }

            $content .= '</div><!-- end of cell /-->';
        }
        $content .= '</div><!-- wcrb-tech-box wrcb-box -->';

        return $content;
    }

    function wcrb_update_job_technicians( $post_id, $submit_value ) {
        // Check if we have a valid post ID and technician data
        if ( empty( $post_id ) || ! isset( $_POST['technician'] ) ) {
            return;
        }

        $sanitized_technicians = array();
        
        // Sanitize the input values
        if ( isset( $submit_value ) ) {
            if ( is_array( $submit_value ) ) {
                $sanitized_technicians = array_map( 'sanitize_text_field', $submit_value );
            } else {
                $sanitized_technician = sanitize_text_field( $submit_value );
                $sanitized_technicians = array( $sanitized_technician );
            }
        }

        // Get old technician data
        $_old_technician = get_post_meta( $post_id, '_technician', true );
        $_new_technician = $sanitized_technicians;

        // Update the post meta
        update_post_meta( $post_id, '_technician', $_new_technician );

        // Check if technicians changed and send emails to NEW technicians
        if ( ( $_new_technician != $_old_technician ) || empty( $_old_technician ) ) {
            // Convert old technician to array for comparison
            $old_tech_array = is_array( $_old_technician ) ? $_old_technician : ( empty( $_old_technician ) ? array() : array( $_old_technician ) );
            $new_tech_array = is_array( $_new_technician ) ? $_new_technician : array( $_new_technician );
            
            // Find technicians that are newly added
            $newly_added_technicians = array_diff( $new_tech_array, $old_tech_array );
            
            // Send email to each newly added technician
            foreach ( $newly_added_technicians as $new_tech_id ) {
                if ( ! empty( $new_tech_id ) && $new_tech_id != "0" ) {
                    wcrb_send_technician_update_email( $post_id, $new_tech_id );
                }
            }

            //LEt's add history log
            // Log the change in job history
            $name = esc_html__( "Technician Modified To", "computer-repair-shop" );
            $type = "private";
            
            // Prepare change details for logging
            $change_detail = '';
            if ( ! empty( $_new_technician ) ) {
                $technician_names = array();
                foreach ( $_new_technician as $tech_id ) {
                    if ( ! empty( $tech_id ) && $tech_id != "0" ) {
                        $user_info = get_userdata( $tech_id );
                        if ( $user_info ) {
                            $first_name = $user_info->first_name;
                            $last_name = $user_info->last_name;
                            $technician_names[] = $first_name . ' ' . $last_name;
                        }
                    }
                }
                $change_detail = implode( ', ', $technician_names );
            }

            $args = array(
                "job_id"        => $post_id, 
                "name"          => $name, 
                "type"          => $type, 
                "field"         => '_technician', 
                "change_detail" => $change_detail
            );

            // Only log if we have valid technicians
            $error = ( empty( $_new_technician ) || ( count( $_new_technician ) === 1 && $_new_technician[0] === "0" ) ) ? 1 : 0;
            
            if ( $error != 1 ) {
                $WCRB_JOB_HISTORY_LOGS = WCRB_JOB_HISTORY_LOGS::getInstance();
                $WCRB_JOB_HISTORY_LOGS->wc_record_job_history( $args );
            }
        }
    }

    function return_technician_names( $job_id ) {
        if ( empty( $job_id ) ) {
            return '';
        }

        // Get technician IDs from post meta
        $technician_ids = get_post_meta( $job_id, '_technician', true );
        
        // If no technicians found, return empty string
        if ( empty( $technician_ids ) ) {
            return '';
        }

        // Convert to array if it's a single ID
        if ( ! is_array( $technician_ids ) ) {
            $technician_ids = array( $technician_ids );
        }

        $technician_names = array();

        foreach ( $technician_ids as $tech_id ) {
            // Skip empty or invalid IDs
            if ( empty( $tech_id ) || $tech_id == "0" ) {
                continue;
            }

            // Get user data
            $user_info = get_userdata( $tech_id );
            
            if ( $user_info ) {
                $first_name = $user_info->first_name;
                $last_name = $user_info->last_name;
                
                // Use display name if first/last names are empty
                if ( empty( $first_name ) && empty( $last_name ) ) {
                    $technician_names[] = $user_info->display_name;
                } else {
                    $technician_names[] = trim( $first_name . ' ' . $last_name );
                }
            }
        }

        // Return comma-separated names
        return implode( ', ', $technician_names );
    }

    function get_technician_time_stats( $technician_id, $today_start, $today_end, $week_start, $week_end, $month_start, $month_end ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_cr_time_logs';
        
        $stats = array(
            'today_hours' => '0.0',
            'week_hours' => '0.0',
            'active_jobs' => '0',
            'billable_rate' => '0',
            'completed_jobs' => '0',
            'avg_time_per_job' => '0.0',
            'today_earnings' => '0.00',
            'week_earnings' => '0.00',
            'month_earnings' => '0.00'
        );
        
        // Get technician's hourly cost
        $hourly_cost = floatval(get_user_meta($technician_id, 'technician_hourly_rate', true));
        
        // 1. Today's hours and earnings (only completed logs with end_time)
        $today_data = $wpdb->get_row( $wpdb->prepare( "
            SELECT 
                COALESCE(SUM(total_minutes), 0) as total_minutes,
                COALESCE(SUM(CASE WHEN is_billable = 1 THEN total_minutes ELSE 0 END), 0) as billable_minutes
            FROM {$table_name} 
            WHERE technician_id = %d 
            AND start_time >= %s 
            AND start_time <= %s 
            AND end_time IS NOT NULL
        ", $technician_id, $today_start, $today_end ), ARRAY_A );
        
        $today_minutes = $today_data['total_minutes'] ?? 0;
        $today_billable_minutes = $today_data['billable_minutes'] ?? 0;
        
        $stats['today_hours'] = number_format( $today_minutes / 60, 1 );
        $stats['today_earnings'] = number_format( ($today_billable_minutes / 60) * $hourly_cost, 2 );
        
        // 2. This week's hours and earnings
        $week_data = $wpdb->get_row( $wpdb->prepare( "
            SELECT 
                COALESCE(SUM(total_minutes), 0) as total_minutes,
                COALESCE(SUM(CASE WHEN is_billable = 1 THEN total_minutes ELSE 0 END), 0) as billable_minutes
            FROM {$table_name} 
            WHERE technician_id = %d 
            AND start_time >= %s 
            AND start_time <= %s 
            AND end_time IS NOT NULL
        ", $technician_id, $week_start, $week_end ), ARRAY_A );
        
        $week_minutes = $week_data['total_minutes'] ?? 0;
        $week_billable_minutes = $week_data['billable_minutes'] ?? 0;
        
        $stats['week_hours'] = number_format( $week_minutes / 60, 1 );
        $stats['week_earnings'] = number_format( ($week_billable_minutes / 60) * $hourly_cost, 2 );
        
        // 3. This month's earnings
        $month_data = $wpdb->get_row( $wpdb->prepare( "
            SELECT 
                COALESCE(SUM(CASE WHEN is_billable = 1 THEN total_minutes ELSE 0 END), 0) as billable_minutes
            FROM {$table_name} 
            WHERE technician_id = %d 
            AND start_time >= %s 
            AND start_time <= %s 
            AND end_time IS NOT NULL
        ", $technician_id, $month_start, $month_end ), ARRAY_A );
        
        $month_billable_minutes = $month_data['billable_minutes'] ?? 0;
        $stats['month_earnings'] = number_format( ($month_billable_minutes / 60) * $hourly_cost, 2 );
        
        // 4. Billable rate for this week
        if ( $week_minutes > 0 ) {
            $stats['billable_rate'] = round( ( $week_billable_minutes / $week_minutes ) * 100 );
        }
        
        // 5. Completed jobs this month
        $stats['completed_jobs'] = $wpdb->get_var( $wpdb->prepare( "
            SELECT COUNT(DISTINCT job_id) 
            FROM {$table_name} 
            WHERE technician_id = %d 
            AND start_time >= %s 
            AND start_time <= %s 
            AND end_time IS NOT NULL
        ", $technician_id, $month_start, $month_end ) );
        
        // 6. Average time per job (this month)
        $avg_time = $wpdb->get_var( $wpdb->prepare( "
            SELECT AVG(job_total_minutes) 
            FROM (
                SELECT job_id, SUM(total_minutes) as job_total_minutes 
                FROM {$table_name} 
                WHERE technician_id = %d 
                AND start_time >= %s 
                AND start_time <= %s 
                AND end_time IS NOT NULL 
                GROUP BY job_id
            ) as job_times
        ", $technician_id, $month_start, $month_end ) );
        
        if ( $avg_time ) {
            $stats['avg_time_per_job'] = number_format( $avg_time / 60, 1 );
        }
        
        return $stats;
    }

    function wcrb_get_eligible_jobs_with_devices_dropdown( $selected_value = '' ) {
        // Get eligible job statuses from options
        $eligible_statuses = get_option( 'wcrb_timelog_job_status', '' );
        
        if ( ! empty( $eligible_statuses ) ) {
            $eligible_statuses = maybe_unserialize( $eligible_statuses );
        }
        
        // Default statuses if option is empty
        if ( empty( $eligible_statuses ) || ! is_array( $eligible_statuses ) ) {
            $eligible_statuses = array( 'new', 'inprocess', 'inservice' );
        }
        
        $current_user_id = get_current_user_id();
        
        // Query jobs with eligible statuses
        $args = array(
            'post_type'      => 'rep_jobs',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'     => '_wc_order_status',
                    'value'   => $eligible_statuses,
                    'compare' => 'IN'
                )
            ),
            'orderby' => 'ID',
            'order'   => 'DESC'
        );
        
        $jobs = get_posts( $args );
        $dropdown_options = array();
        
        $jobs_manager    = WCRB_JOBS_MANAGER::getInstance();

        foreach ( $jobs as $job ) {
            // Check if current user is technician for this job
            if ( ! is_wcrb_current_user_have_technician_access( $job->ID ) ) {
                continue;
            }
            
            $job_title  = get_the_title( $job->ID );
            $job_status = get_post_meta( $job->ID, '_wc_order_status', true );
            $job_data   = $jobs_manager->get_job_display_data( $job->ID );
            $_job_id    = ( ! empty( $job_data['formatted_job_number'] ) ) ? $job_data['formatted_job_number'] : $job->ID;
            
            // Get customer information
            $customer_name = wcrb_get_job_customer_name( $job->ID );
            
            // Get devices data
            $devices_data = get_post_meta( $job->ID, '_wc_device_data', true );

            // If no devices, still show the job
            $display_text = esc_html__( 'JOB', 'computer-repair-shop' ) . '-' . $_job_id . ' > ' . $job_title;
            
            if ( ! empty( $customer_name ) ) {
                $display_text .= ' - ' . $customer_name;
            }
            
            $display_text .= ' - ' . $job_status;
            
            $option_value = $job->ID . '|0|0'; // 0 indicates no specific device
            
            $dropdown_options[] = array(
                'value' => $option_value,
                'text'  => $display_text
            );
            
            if ( ! empty( $devices_data ) && is_array( $devices_data ) ) {
                foreach ( $devices_data as $index => $device ) {
                    $device_post_id = isset( $device['device_post_id'] ) ? $device['device_post_id'] : '';
                    $device_id = isset( $device['device_id'] ) ? $device['device_id'] : '';
                    
                    if ( ! empty( $device_post_id ) ) {
                        // Get device name from post
                        $device_name = get_the_title( $device_post_id );
                        
                        // Build display text
                        $display_text = esc_html__( 'JOB', 'computer-repair-shop' ) . '-' . $_job_id . '> ' . $job_title . ' > ' . $device_name;
                        
                        if ( ! empty( $device_id ) ) {
                            $display_text .= ' (' . $device_id . ')';
                        }
                        
                        // Add customer name if available
                        if ( ! empty( $customer_name ) ) {
                            $display_text .= ' - ' . $customer_name;
                        }
                        
                        $display_text .= ' - ' . $job_status;
                        
                        // Build value with device ID included
                        $value_parts = array( $job->ID );
                        
                        if ( ! empty( $device_id ) ) {
                            $value_parts[] = $device_post_id . '-' . $device_id;
                        } else {
                            $value_parts[] = $device_post_id;
                        }
                        
                        $value_parts[] = $index;
                        
                        $option_value = implode( '|', $value_parts );
                        
                        $dropdown_options[] = array(
                            'value' => $option_value,
                            'text'  => $display_text
                        );
                    }
                }
            }
        }
        
        // Sort options by job ID (newest first)
        usort( $dropdown_options, function( $a, $b ) {
            return strnatcmp( $b['text'], $a['text'] );
        } );
        
        // Build the dropdown HTML
        $dropdown = '<select name="job_device" id="timeLogJobDeviceSelect" class="form-select" required>';
        $dropdown .= '<option value="">' . esc_html__( 'Select a job and device...', 'computer-repair-shop' ) . '</option>';
        
        foreach ( $dropdown_options as $option ) {
            $is_selected = selected( $selected_value, $option['value'], false );
            
            $dropdown .= '<option value="' . esc_attr( $option['value'] ) . '" ' . $is_selected . '>';
            $dropdown .= esc_html( $option['text'] );
            $dropdown .= '</option>';
        }
        
        $dropdown .= '</select>';
        
        return $dropdown;
    }

    function wcrb_save_time_entry_handler() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error( esc_html__( 'You are not logged in.', 'computer-repair-shop' ) );
            wp_die();
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'wcrb_timelog_nonce_action')) {
            wp_send_json_error( esc_html__( 'Something is wrong with your submission!', 'computer-repair-shop' ) );
            wp_die();
        }
        
        // Get the time_log_data array
        $time_log_data = $_POST['time_log_data'] ?? [];
        
        // Validate required fields
        $required_fields = ['start_time', 'end_time', 'activity', 'work_description', 'job_id'];
        foreach ($required_fields as $field) {
            if (empty($time_log_data[$field])) {
                wp_send_json_error('Missing required field: ' . $field);
                wp_die();
            }
        }
        
        try {
            // Get current user ID
            $current_user_id = get_current_user_id();
            
            // Get technician hourly rates
            $hourly_cost = floatval(get_user_meta($current_user_id, 'technician_hourly_rate', true));
            $hourly_rate = floatval(get_user_meta($current_user_id, 'client_hourly_rate', true));
            
            // Calculate costs
            $total_minutes = intval($time_log_data['total_minutes'] ?? 0);
            $total_hours = $total_minutes / 60;
            
            if ( $total_minutes <= 0 ) {
                wp_send_json_error( esc_html__( 'Total time must be greater than zero.', 'computer-repair-shop' ) );
                wp_die();
            }
            // Prepare device data for storage
            $device_data_array = [
                'device_id' => sanitize_text_field($time_log_data['device_id'] ?? ''),
                'device_name' => get_the_title(sanitize_text_field($time_log_data['device_id'] ?? '')),
                'device_serial' => sanitize_text_field($time_log_data['device_serial'] ?? ''),
                'device_index' => sanitize_text_field($time_log_data['device_index'] ?? '0')
            ];
            
            $device_data_json = json_encode($device_data_array);

            // Get WordPress timezone
            $timezone = wp_timezone();
            
            // Create DateTime objects from ISO strings (assume UTC)
            $start_utc  = new DateTime( sanitize_text_field( $time_log_data['start_time'] ), new DateTimeZone( 'UTC' ) );
            $end_utc    = new DateTime( sanitize_text_field( $time_log_data['end_time'] ), new DateTimeZone( 'UTC' ) );
            
            // Convert to WordPress timezone
            $start_utc->setTimezone( $timezone );
            $end_utc->setTimezone( $timezone );
            
            // Prepare data for database - MATCHING YOUR TABLE STRUCTURE
            $entry_data = [
                'start_time'       => $start_utc->format( 'Y-m-d H:i:s' ),
                'end_time'         => $end_utc->format( 'Y-m-d H:i:s' ),
                'time_type'        => sanitize_text_field( $time_log_data['time_type'] ?? 'time_charge' ),
                'activity'         => sanitize_text_field($time_log_data['activity'] ?? ''),
                'priority'         => sanitize_text_field($time_log_data['priority'] ?? 'medium'),
                'work_description' => sanitize_textarea_field($time_log_data['work_description'] ?? ''),
                'technician_id'    => $current_user_id,
                'job_id'           => intval($time_log_data['job_id'] ?? 0),
                'device_data'      => $device_data_json,
                'log_state'        => 'pending', // Default state
                'total_minutes'    => $total_minutes,
                'hourly_rate'      => $hourly_rate,
                'hourly_cost'      => $hourly_cost,
                'is_billable'      => sanitize_text_field($time_log_data['is_billable'] ?? ''),
                // approved_by, approved_at, rejection_reason are left NULL by default
                'created_at'       => current_time('mysql')
            ];
            
            // Validate that job_id is valid
            if ($entry_data['job_id'] <= 0) {
                wp_send_json_error('Invalid job ID');
                wp_die();
            }
            
            // Save to database
            global $wpdb;
            $table_name = $wpdb->prefix . 'wc_cr_time_logs';
            
            $result = $wpdb->insert($table_name, $entry_data);
            
            if ($result === false) {
                wp_send_json_error('Database error: ' . $wpdb->last_error);
                wp_die();
            }
            
            $entry_id = $wpdb->insert_id;

            if ( $entry_id ) {

                $this->add_hours_to_job( $entry_id );

                $user_info = get_userdata( $entry_data['technician_id'] );
                if ( $user_info ) {
                    $first_name = $user_info->first_name;
                    $last_name = $user_info->last_name;
                    $technician_name = $first_name . ' ' . $last_name;
                }
                
                //Let's add job history log
                $WCRB_JOB_HISTORY_LOGS = WCRB_JOB_HISTORY_LOGS::getInstance();
                $WCRB_JOB_HISTORY_LOGS->wc_record_job_history( array(
                    'job_id'        => $entry_data['job_id'],
                    'name'          => esc_html__( 'Time Entry Added', 'computer-repair-shop' ),
                    'type'          => 'private',
                    'field'         => 'time_log_entry',
                    'change_detail' => sprintf(
                        esc_html__( 'Time entry of %d minutes added by technician %s.', 'computer-repair-shop' ),
                        $entry_data['total_minutes'],
                        $technician_name
                    )
                ) );

                wp_send_json_success([
                    'message' => sprintf(
                        __('Time entry saved! (%d minutes)', 'computer-repair-shop'), 
                        $entry_data['total_minutes']
                    ),
                    'entry_id' => $entry_id,
                    'log_state' => $entry_data['log_state']
                ]);
            } else {
                wp_send_json_error('Failed to save time entry to database.');
            }
            
        } catch (Exception $e) {
            wp_send_json_error('Error saving time entry: ' . $e->getMessage());
        }
        
        wp_die();
    }

    function add_hours_to_job( $entry_id ) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wc_cr_time_logs';
        $computer_repair_items       = $wpdb->prefix.'wc_cr_order_items';
        $computer_repair_items_meta  = $wpdb->prefix.'wc_cr_order_itemmeta';

        $wc_use_taxes = get_option( 'wc_use_taxes' );
        $time_log_tax = ( ! empty( get_option( 'wcrb_timelog_tax' ) ) ) ? get_option( 'wcrb_timelog_tax' ) : get_option( 'wc_primary_tax' );

        // Get the time log entry
        $entry = $wpdb->get_row($wpdb->prepare("
            SELECT *
            FROM {$table_name}
            WHERE log_id = %d
        ", $entry_id), ARRAY_A);
        
        if ( ! $entry ) {
            return false;
        }
        
        if ( $entry['is_billable'] != '1' || $entry['log_state'] === 'billed' ) {
            return false; // Not billable or already billed
        }

        // Initialize expense manager
        $expense_manager = WC_CR_EXPENSE_MANAGEMENT();
        
        // Create expense for labor cost if technician has hourly cost
        $hourly_cost = floatval($entry['hourly_cost'] ?? 0);
        $total_minutes = intval($entry['total_minutes'] ?? 0);
        $total_cost = $hourly_cost * ($total_minutes / 60);
        
        $expense_created = false;
        $expense_id = null;
        
        // Create expense only if there's a cost to record
        if ($hourly_cost > 0 && $total_cost > 0) {
            // Check if expense already exists for this time log
            $expense_id = $expense_manager->create_expense_from_time_log($entry, $entry['technician_id']);
            $expense_created = ($expense_id !== false);
        }

        // Decode device data if it exists
        $device_data = [];
        if (!empty($entry['device_data'])) {
            $device_data = json_decode($entry['device_data'], true);
        }
        
        // Extract device information
        $wc_extra_device = isset($device_data['device_id']) ? $device_data['device_id'] : '';
        $wc_extra_device_name = isset($device_data['device_name']) ? $device_data['device_name'] : '';
        $wc_extra_device_serial = isset($device_data['device_serial']) ? $device_data['device_serial'] : '';
        
        // Prepare item name and details
        $wc_extra_name = ucfirst( $entry['activity'] ) . ' - ' . esc_html__( 'Time Log', 'computer-repair-shop' );
        $wc_extra_code = 'TL-' . $entry['log_id'];
        $wc_extra_qty = round( $entry['total_minutes'] / 60, 2 );
        $wc_extra_cost = $entry['hourly_cost'];
        $wc_extra_price = $entry['hourly_rate'];
        
        // Prepare the data array for insertion
        $process_extra_array = array(
            "wc_extra_code"             => $wc_extra_code, 
            "wc_extra_qty"              => $wc_extra_qty, 
            "wc_extra_cost"             => $wc_extra_cost,
            "wc_extra_price"            => $wc_extra_price,
            "wc_extra_name"             => $wc_extra_name,
            "wc_extra_device"           => $wc_extra_device,
            "wc_extra_device_name"      => $wc_extra_device_name,
            "wc_extra_device_serial"    => $wc_extra_device_serial,
            "timelog_entry_id"          => $entry_id
        );

        // Add tax if enabled
        if ( $wc_use_taxes == 'on' && ! empty( $time_log_tax ) ) {
            $wc_extra_tax = $time_log_tax;
            $process_extra_array["wc_extra_tax"]    = wc_return_tax_rate( $wc_extra_tax );
            $process_extra_array["wc_extra_tax_id"] = $wc_extra_tax;
        }

        // Insert the main order item
        $insert_query = "INSERT INTO `{$computer_repair_items}` VALUES(NULL, %s, 'extras', %s)";
        $wpdb->query(
            $wpdb->prepare($insert_query, $wc_extra_name, $entry['job_id'])
        );
        
        $order_item_id = $wpdb->insert_id;
        
        if (!$order_item_id) {
            return false;
        }
        
        // Insert all meta data
        foreach ( $process_extra_array as $key => $value ) {
            $extra_insert_query = "INSERT INTO `{$computer_repair_items_meta}` VALUES(NULL, %s, %s, %s)";
            $wpdb->query(
                $wpdb->prepare($extra_insert_query, $order_item_id, $key, $value)
            );
        }
        
        // Update time log entry status to mark it as added to job
        $update_result = $wpdb->update(
            $table_name,
            array('log_state' => 'billed'),
            array('log_id' => $entry_id),
            array('%s'),
            array('%d')
        );
        
        // Get technician information for history log
        $technician_info = get_userdata($entry['technician_id']);
        $technician_name = $technician_info ? $technician_info->display_name : __('Technician', 'computer-repair-shop');
        
        // Prepare history log messages
        $history_messages = array();
        
        // Add billing history
        $history_messages[] = array(
            'name' => esc_html__( 'Time Charges Added', 'computer-repair-shop' ),
            'type' => 'public',
            'field' => 'time_log_billing',
            'change_detail' => sprintf(
                esc_html__( 'Time charges added: %s hours of %s. (Time Log Entry #%d)', 'computer-repair-shop' ),
                number_format($wc_extra_qty, 2),
                $entry['activity'],
                $entry_id
            )
        );
        
        // Add expense history if expense was created
        if ($expense_created && $expense_id) {
            $history_messages[] = array(
                'name' => esc_html__( 'Labor Cost Recorded', 'computer-repair-shop' ),
                'type' => 'private',
                'field' => 'expense_creation',
                'change_detail' => sprintf(
                    esc_html__( 'Labor expense recorded: %s hours at %s/hour = %s (Expense #%s)', 'computer-repair-shop' ),
                    number_format($wc_extra_qty, 2),
                    wc_cr_currency_format($hourly_cost),
                    wc_cr_currency_format($total_cost),
                    $expense_manager->get_expense($expense_id)->expense_number ?? $expense_id
                )
            );
        }
        
        // Add all history logs
        $WCRB_JOB_HISTORY_LOGS = WCRB_JOB_HISTORY_LOGS::getInstance();
        foreach ($history_messages as $history_data) {
            $full_history_data = array_merge(array('job_id' => $entry['job_id']), $history_data);
            $WCRB_JOB_HISTORY_LOGS->wc_record_job_history($full_history_data);
        }
        
        return array(
            'order_item_id' => $order_item_id,
            'expense_created' => $expense_created,
            'expense_id' => $expense_id
        );
    }

    function get_timelog_activity_types_dropdown( $selected_value = '', $_id = '' ) {
        //We will save activity types later
        $activity_types = get_option( 'wcrb_timelog_activity_types', '' );
        
        if ( ! empty( $activity_types ) ) {
            $activity_types = str_replace( "\r", "", $activity_types );
            $activity_types = explode( "\n", $activity_types );
        }

        if ( ! empty( $activity_types ) ) {
            $activity_types = maybe_unserialize( $activity_types );
        }

        // Default activity types if option is empty
        if ( empty( $activity_types ) || ! is_array( $activity_types ) ) {
            $activity_types = array( 'Diagnosis', 'Repair', 'Testing', 'Cleaning', 'Consultation', 'Other' );
        }

        // Build the dropdown HTML
        $_id_name = ( ! empty( $_id ) ) ? $_id : 'activityType';
        $dropdown = '<select name="timelog_activity_type" id="'. esc_attr( $_id_name ) .'" class="form-select" required>';
        $dropdown .= '<option value="">' . esc_html__( 'Select activity type...', 'computer-repair-shop' ) . '</option>';
        
        foreach ( $activity_types as $type ) {
            $is_selected = selected( $selected_value, $type, false );
            
            $dropdown .= '<option value="' . esc_attr( $type ) . '" ' . $is_selected . '>';
            $dropdown .= esc_html( $type );
            $dropdown .= '</option>';
        }
        
        $dropdown .= '</select>';
        
        return $dropdown;
    }

    /**
     * Get productivity statistics for a technician
     * 
     * @param int $technician_id The technician user ID
     * @param string $time_period Time period: 'today', 'week', 'month', 'year'
     * @return array Productivity statistics
     */
    function get_technician_productivity_stats($technician_id, $time_period = 'week') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_cr_time_logs';
        
        // Get user's activity types from settings or use defaults
        $activity_types = get_option('wcrb_timelog_activity_types', '');
        if (empty($activity_types) || !is_array($activity_types)) {
            $activity_types = array('Diagnosis', 'Repair', 'Testing', 'Cleaning', 'Consultation', 'Other');
        }
        
        // Initialize stats with dynamic activity distribution
        $stats = [
            'avg_daily_hours'     => '0.0',
            'total_jobs_completed' => '0',
            'efficiency_score'    => '0',
            'activity_distribution' => [],
            'total_hours'         => 0,
            'total_billable_hours' => 0,
            'completion_rate'     => 0,
            'activity_minutes'    => [] // Store raw minutes for each activity
        ];
        
        // Initialize activity distribution with all activity types
        foreach ($activity_types as $activity_type) {
            $key = strtolower(preg_replace('/[^a-z0-9]/', '_', $activity_type));
            $stats['activity_distribution'][$key] = 0;
            $stats['activity_minutes'][$key] = 0;
        }
        
        // Set date ranges based on time period
        $today = current_time('mysql');
        switch ($time_period) {
            case 'today':
                $start_date = date('Y-m-d 00:00:00', strtotime($today));
                $end_date = date('Y-m-d 23:59:59', strtotime($today));
                $days_in_period = 1;
                break;
                
            case 'week':
                $start_date = date('Y-m-d 00:00:00', strtotime('monday this week'));
                $end_date = date('Y-m-d 23:59:59', strtotime('sunday this week'));
                $days_in_period = 7;
                break;
                
            case 'month':
                $start_date = date('Y-m-d 00:00:00', strtotime('first day of this month'));
                $end_date = date('Y-m-d 23:59:59', strtotime('last day of this month'));
                $days_in_period = date('t');
                break;
                
            case 'year':
                $start_date = date('Y-01-01 00:00:00');
                $end_date = date('Y-12-31 23:59:59');
                $days_in_period = 365;
                break;
                
            default:
                $start_date = date('Y-m-d 00:00:00', strtotime('monday this week'));
                $end_date = date('Y-m-d 23:59:59', strtotime('sunday this week'));
                $days_in_period = 7;
        }
        
        // 1. Get total hours and billable hours for the period
        $hours_data = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COALESCE(SUM(total_minutes), 0) as total_minutes,
                COALESCE(SUM(CASE WHEN is_billable = 1 THEN total_minutes ELSE 0 END), 0) as billable_minutes
            FROM {$table_name}
            WHERE technician_id = %d
            AND start_time >= %s
            AND start_time <= %s
            AND end_time IS NOT NULL
        ", $technician_id, $start_date, $end_date), ARRAY_A);
        
        $total_minutes = $hours_data['total_minutes'] ?? 0;
        $billable_minutes = $hours_data['billable_minutes'] ?? 0;
        
        $total_hours = $total_minutes / 60;
        $billable_hours = $billable_minutes / 60;
        
        $stats['total_hours'] = $total_hours;
        $stats['total_billable_hours'] = $billable_hours;
        
        // 2. Calculate average daily hours
        if ($days_in_period > 0) {
            $stats['avg_daily_hours'] = number_format($total_hours / $days_in_period, 1);
        }
        
        // 3. Get total unique jobs completed
        $stats['total_jobs_completed'] = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT job_id)
            FROM {$table_name}
            WHERE technician_id = %d
            AND start_time >= %s
            AND start_time <= %s
            AND end_time IS NOT NULL
        ", $technician_id, $start_date, $end_date));
        
        // 4. Calculate efficiency score (based on billable hours percentage)
        if ($total_minutes > 0) {
            $efficiency = ($billable_minutes / $total_minutes) * 100;
            $stats['efficiency_score'] = round($efficiency);
        }
        
        // 5. Get activity distribution - using actual activity names from database
        $activity_data = $wpdb->get_results($wpdb->prepare("
            SELECT 
                activity,
                COALESCE(SUM(total_minutes), 0) as total_minutes
            FROM {$table_name}
            WHERE technician_id = %d
            AND start_time >= %s
            AND start_time <= %s
            AND end_time IS NOT NULL
            GROUP BY activity
            ORDER BY total_minutes DESC
        ", $technician_id, $start_date, $end_date), ARRAY_A);
        
        $activity_minutes = [];
        $total_activity_minutes = 0;
        
        // First, collect all activities and their minutes
        foreach ($activity_data as $activity_row) {
            $activity_name = trim($activity_row['activity']);
            $minutes = $activity_row['total_minutes'];
            
            if (!empty($activity_name)) {
                // Create a sanitized key for the activity
                $key = strtolower(preg_replace('/[^a-z0-9]/', '_', $activity_name));
                $activity_minutes[$key] = $minutes;
                $stats['activity_minutes'][$key] = $minutes;
                $total_activity_minutes += $minutes;
            }
        }
        
        // Calculate percentages for each activity type
        if ($total_activity_minutes > 0) {
            // Calculate percentages for activities found in database
            foreach ($activity_minutes as $activity_key => $minutes) {
                $percentage = ($minutes / $total_activity_minutes) * 100;
                
                // If this activity exists in our initialized distribution, add it
                if (isset($stats['activity_distribution'][$activity_key])) {
                    $stats['activity_distribution'][$activity_key] = round($percentage);
                } else {
                    // If it's a new activity not in our defaults, add it
                    $stats['activity_distribution'][$activity_key] = round($percentage);
                    $stats['activity_minutes'][$activity_key] = $minutes;
                }
            }
            
            // If we have no activity data, ensure all percentages are 0
            foreach ($stats['activity_distribution'] as $key => $value) {
                if (!isset($activity_minutes[$key])) {
                    $stats['activity_distribution'][$key] = 0;
                }
            }
        }
        
        // 6. Sort activity distribution by minutes (highest first)
        arsort($stats['activity_distribution']);
        
        // 7. Create a formatted version with activity labels (optional)
        $stats['formatted_activity_distribution'] = [];
        foreach ($stats['activity_distribution'] as $key => $percentage) {
            $label = ucwords(str_replace('_', ' ', $key));
            $stats['formatted_activity_distribution'][$label] = $percentage;
        }
        
        return $stats;
    }

    /**
     * Get weekly hours data for chart
     * 
     * @param int $technician_id The technician user ID
     * @param string $period Time period: 'week', 'month', 'year'
     * @return array Chart data
     */
    function get_weekly_chart_data( $technician_id, $period = 'week' ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_cr_time_logs';
        
        // Initialize based on period
        switch ($period) {
            case 'week':
            case 'last-week':
                $chart_data = [
                    'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    'data' => [0, 0, 0, 0, 0, 0, 0]
                ];
                break;
                
            case 'month':
            case 'last-month':
                // For monthly view, we need dynamic labels for days of the month
                $chart_data = [
                    'labels' => [],
                    'data' => []
                ];
                break;
                
            case 'year':
                $chart_data = [
                    'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    'data' => array_fill(0, 12, 0)
                ];
                break;
                
            default:
                $chart_data = [
                    'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    'data' => [0, 0, 0, 0, 0, 0, 0]
                ];
        }
        
        $current_date = current_time('mysql');
        
        switch ($period) {
            case 'week':
                // This week
                $start_date = date('Y-m-d 00:00:00', strtotime('monday this week', strtotime($current_date)));
                $end_date = date('Y-m-d 23:59:59', strtotime('sunday this week', strtotime($current_date)));
                break;
                
            case 'last-week':
                // Last week
                $start_date = date('Y-m-d 00:00:00', strtotime('monday last week', strtotime($current_date)));
                $end_date = date('Y-m-d 23:59:59', strtotime('sunday last week', strtotime($current_date)));
                break;
                
            case 'month':
                // This month
                $start_date = date('Y-m-01 00:00:00', strtotime($current_date));
                $end_date = date('Y-m-t 23:59:59', strtotime($current_date));
                break;
                
            case 'last-month':
                // Last month
                $start_date = date('Y-m-01 00:00:00', strtotime('first day of last month', strtotime($current_date)));
                $end_date = date('Y-m-t 23:59:59', strtotime('last day of last month', strtotime($current_date)));
                break;
                
            case 'year':
                // This year
                $start_date = date('Y-01-01 00:00:00', strtotime($current_date));
                $end_date = date('Y-12-31 23:59:59', strtotime($current_date));
                break;
                
            default:
                $start_date = date('Y-m-d 00:00:00', strtotime('monday this week', strtotime($current_date)));
                $end_date = date('Y-m-d 23:59:59', strtotime('sunday this week', strtotime($current_date)));
        }
        
        // Query database for time logs
        if ($period === 'week' || $period === 'last-week') {
            // For weekly view, group by day of week
            $results = $wpdb->get_results($wpdb->prepare("
                SELECT 
                    DAYOFWEEK(start_time) as day_of_week,
                    COALESCE(SUM(total_minutes), 0) as total_minutes
                FROM {$table_name}
                WHERE technician_id = %d
                AND start_time >= %s
                AND start_time <= %s
                AND end_time IS NOT NULL
                GROUP BY DAYOFWEEK(start_time)
                ORDER BY DAYOFWEEK(start_time)
            ", $technician_id, $start_date, $end_date), ARRAY_A);
            
            // Map database results to chart data
            foreach ($results as $row) {
                // MySQL: 1=Sunday, 2=Monday, 3=Tuesday, 4=Wednesday, 5=Thursday, 6=Friday, 7=Saturday
                // Chart: 0=Monday, 1=Tuesday, 2=Wednesday, 3=Thursday, 4=Friday, 5=Saturday, 6=Sunday
                $day_index = ($row['day_of_week'] == 1) ? 6 : $row['day_of_week'] - 2;
                
                if ($day_index >= 0 && $day_index < 7) {
                    $chart_data['data'][$day_index] = round($row['total_minutes'] / 60, 1);
                }
            }
        } elseif ($period === 'month' || $period === 'last-month') {
            // For monthly view, group by day of month
            $results = $wpdb->get_results($wpdb->prepare("
                SELECT 
                    DAY(start_time) as day_of_month,
                    COALESCE(SUM(total_minutes), 0) as total_minutes
                FROM {$table_name}
                WHERE technician_id = %d
                AND start_time >= %s
                AND start_time <= %s
                AND end_time IS NOT NULL
                GROUP BY DAY(start_time)
                ORDER BY DAY(start_time)
            ", $technician_id, $start_date, $end_date), ARRAY_A);
            
            // Get number of days in the month
            $days_in_month = date('t', strtotime($start_date));
            
            // Initialize labels and data for each day
            $chart_data['labels'] = range(1, $days_in_month);
            $chart_data['data'] = array_fill(0, $days_in_month, 0);
            
            // Map results
            foreach ($results as $row) {
                $day_index = $row['day_of_month'] - 1; // Convert to 0-based index
                if ($day_index >= 0 && $day_index < $days_in_month) {
                    $chart_data['data'][$day_index] = round($row['total_minutes'] / 60, 1);
                }
            }
        } elseif ($period === 'year') {
            // For yearly view, group by month
            $results = $wpdb->get_results($wpdb->prepare("
                SELECT 
                    MONTH(start_time) as month_number,
                    COALESCE(SUM(total_minutes), 0) as total_minutes
                FROM {$table_name}
                WHERE technician_id = %d
                AND start_time >= %s
                AND start_time <= %s
                AND end_time IS NOT NULL
                GROUP BY MONTH(start_time)
                ORDER BY MONTH(start_time)
            ", $technician_id, $start_date, $end_date), ARRAY_A);
            
            // Map results
            foreach ($results as $row) {
                $month_index = $row['month_number'] - 1; // Convert to 0-based index
                if ($month_index >= 0 && $month_index < 12) {
                    $chart_data['data'][$month_index] = round($row['total_minutes'] / 60, 1);
                }
            }
        }
        
        return $chart_data;
    }

    /**
     * AJAX handler for chart data
     */
    function wcrb_get_chart_data_handler() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error('You must be logged in.');
            wp_die();
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'wcrb_timelog_nonce_action')) {
            wp_send_json_error('Security verification failed.');
            wp_die();
        }
        
        $technician_id = get_current_user_id();
        $period = sanitize_text_field($_POST['period'] ?? 'week');
        
        $chart_data = $this->get_weekly_chart_data($technician_id, $period);
        
        wp_send_json_success($chart_data);
        wp_die();
    }

    /**
     * Display recent time logs for current technician
     * 
     * @param int $limit Number of records to show (default: 100)
     * @return string HTML content of time logs table
     */
    function get_recent_time_logs_html( $limit = 100, $offset = '', $technician_id = 0 ) {
        $content = '';
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            $content .= '<tr><td colspan="5" class="text-center py-4">' . esc_html__( 'You are not logged in.', 'computer-repair-shop' ) . '</td></tr>';
            return $content;
        }
        
        $current_user_id = ( ! empty( $technician_id ) ) ? $technician_id : get_current_user_id();
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_cr_time_logs';
        
        // Get recent time logs for current technician
        $logs = $wpdb->get_results($wpdb->prepare("
            SELECT 
                tl.log_id,
                tl.start_time,
                tl.end_time,
                tl.activity,
                tl.work_description,
                tl.technician_id,
                tl.job_id,
                tl.device_data,
                tl.total_minutes,
                tl.hourly_rate,
                tl.hourly_cost,
                tl.created_at,
                p.post_title as job_title
            FROM {$table_name} tl
            LEFT JOIN {$wpdb->posts} p ON tl.job_id = p.ID
            WHERE tl.technician_id = %d
            AND tl.end_time IS NOT NULL
            ORDER BY tl.created_at DESC
            LIMIT %d
        ", $current_user_id, $limit), ARRAY_A);
        
        if ( empty( $logs ) ) {
            $content .= '<tr id="noLogsMessage">
                    <td colspan="5" class="text-center py-4">
                        <div class="text-muted">
                            <i class="bi bi-clock display-6 d-block mb-2"></i>
                            ' . esc_html__('There is no record available', 'computer-repair-shop') . '
                        </div>
                    </td>
                </tr>';
            return $content;
        }
        $jobs_manager = WCRB_JOBS_MANAGER::getInstance();

        foreach ( $logs as $log ) {
            // Format job info
            $job_data   = $jobs_manager->get_job_display_data( $log['job_id'] );
            $_djob_id    = ( ! empty( $job_data['formatted_job_number'] ) ) ? $job_data['formatted_job_number'] : $log['job_id'];

            $job_number = esc_html__( 'JOB', 'computer-repair-shop' ) . '-' . $_djob_id;

            $job_title = !empty($log['job_title']) ? esc_html($log['job_title']) : '';
            
            // Format time
            $start_date = !empty($log['start_time']) ? date_i18n('M j', strtotime($log['start_time'])) : '--';
            $start_time = !empty($log['start_time']) ? date_i18n('g:i A', strtotime($log['start_time'])) : '--';
            $end_date = !empty($log['end_time']) ? date_i18n('M j', strtotime($log['end_time'])) : '--';
            $end_time = !empty($log['end_time']) ? date_i18n('g:i A', strtotime($log['end_time'])) : '--';
            
            // Format date for tooltip
            $date_display = !empty($log['start_time']) ? date_i18n('M j, Y', strtotime($log['start_time'])) : '';
            
            // Calculate duration
            $hours = floor($log['total_minutes'] / 60);
            $minutes = $log['total_minutes'] % 60;
            $duration = '';
            if ($hours > 0) {
                $duration = sprintf('%dh %dm', $hours, $minutes);
            } else {
                $duration = sprintf('%dm', $minutes);
            }
            
            // Calculate amount
            $amount_display = '--';
            if (!empty($log['hourly_cost']) && $log['total_minutes'] > 0) {
                $total_hours = $log['total_minutes'] / 60;
                $total_amount = $total_hours * floatval($log['hourly_cost']);
                $amount_display = wc_cr_currency_format( $total_amount );
            }
            
            // Truncate description if needed
            $description = !empty($log['work_description']) ? esc_html($log['work_description']) : '';
            if (strlen($description) > 30) {
                $description = substr($description, 0, 100) . '...';
            }
            
            // Build row HTML
            $content .= '<tr>';
            $content .= '<td class="ps-4">';
            $content .= '<strong>' . esc_html($job_number) . '</strong>';
            if (!empty($job_title)) {
                $content .= '<br><small class="text-muted">' . $job_title . '</small>';
            }
            $content .= '</td>';
            
            $content .= '<td>';
            $content .= '<span class="badge bg-primary">' . esc_html($log['activity']) . '</span>';
            if (!empty($description)) {
                $content .= '<br><small>' . $description . '</small>';
            }
            if ( ! empty( $log['device_data'] ) ) :
                $device_data = json_decode( $log['device_data'], true );
                if ( ! empty( $device_data['device_name'] ) ) {
                    $content .= '<br><small class="text-muted">' . esc_html( $device_data['device_name'] );
                    $content .= ( ! empty( $device_data['device_serial'] ) ) ? ' (' . esc_html( $device_data['device_serial'] ) . ')' : '';
                    $content .= '</small>';
                }
            endif;
            $content .= '</td>';
            
            $content .= '<td>';
            $content .= '<small class="d-block" title="' . esc_attr($date_display) . '">';
            $content .= '<strong>' . esc_html__('Started:', 'computer-repair-shop') . '</strong> ' . esc_html($start_date) . ' ' . esc_html($start_time);
            $content .= '</small><br>';
            $content .= '<small class="d-block" title="' . esc_attr($date_display) . '">';
            $content .= '<strong>' . esc_html__('Ended:', 'computer-repair-shop') . '</strong> ' . esc_html($end_date) . ' ' . esc_html($end_time);
            $content .= '</small>';
            $content .= '</td>';
            
            $content .= '<td>';
            $content .= '<strong>' . esc_html($duration) . '</strong>';
            $content .= '</td>';
            
            $content .= '<td class="text-end pe-4">';
            $content .= '<strong>' . $amount_display . '</strong>';
            $content .= '</td>';
            $content .= '</tr>';
        }
        
        return $content;
    }
}