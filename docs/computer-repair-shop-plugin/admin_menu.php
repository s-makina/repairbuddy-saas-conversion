<?php
defined( 'ABSPATH' ) || exit;
/**
 * RepairBuddy Admin Pages
 * Adds pages to backend
 *
 * @Since 1.0.0
 */
function wc_add_comp_rep_pages() {
	global $OBJ_MAINTENANCE_REMINDER, $WCRB_MANAGE_DEVICES;

	// main_sub Menu Page.
	$menu_name_p = get_option( 'menu_name_p' );
	
	$menu_name_p = ( empty( $menu_name_p ) ) ? esc_html__( 'Repair Buddy', 'computer-repair-shop' ) : wp_unslash( $menu_name_p );

	$wc_device_label              = ( empty( get_option( 'wc_device_label' ) ) ) ? esc_html__( 'Device', 'computer-repair-shop' ) : get_option( 'wc_device_label' );
	$wc_device_label_plural       = ( empty( get_option( 'wc_device_label_plural' ) ) ) ? esc_html__( 'Devices', 'computer-repair-shop' ) : get_option( 'wc_device_label_plural' );
	$wc_device_brand_label        = ( empty( get_option( 'wc_device_brand_label' ) ) ) ? esc_html__( 'Device Brand', 'computer-repair-shop' ) : get_option( 'wc_device_brand_label' );
	$wc_device_brand_label_plural = ( empty( get_option( 'wc_device_brand_label_plural' ) ) ) ? esc_html__( 'Device Brands', 'computer-repair-shop' ) : get_option( 'wc_device_brand_label_plural' );
	$wc_device_type_label_plural = ( empty( get_option( 'wc_device_type_label_plural' ) ) ) ? esc_html__( 'Device Types', 'computer-repair-shop' ) : get_option( 'wc_device_type_label_plural' );
	$wcrb_turn_estimates_on = get_option( 'wcrb_turn_estimates_on' );

	add_menu_page( esc_html( $menu_name_p ), esc_html( $menu_name_p ), 'read', 'wc-computer-rep-shop-handle', 'wc_comp_repair_shop_main', plugins_url( 'assets/admin/images/computer-repair.png', __FILE__ ), '50' );

	//add_submenu_page( 'wc-computer-rep-shop-handle', __( 'Stores', 'computer-repair-shop' ), __( 'Stores', 'computer-repair-shop' ), 'manage_options' , 'edit.php?post_type=store' );

	$WCRB_APPOINTMENTS = WCRB_APPOINTMENTS::getInstance();
	add_submenu_page( 
		'wc-computer-rep-shop-handle', 
		__( 'Appointments', 'computer-repair-shop' ), 
		__( 'Appointments', 'computer-repair-shop' ), 
		'delete_posts', 
		'wc-computer-rep-shop-appointments', 
		array( $WCRB_APPOINTMENTS,'appointments_page_output' ), 
		200 );
	add_submenu_page( 
		'wc-computer-rep-shop-handle', 
		__( 'Repair Jobs', 'computer-repair-shop' ), 
		__( 'Jobs', 'computer-repair-shop' ), 
		'edit_rep_job', 
		'edit.php?post_type=rep_jobs', 
		'', 
		300 );
	if ( $wcrb_turn_estimates_on != 'on' ) {
		add_submenu_page( 'wc-computer-rep-shop-handle', 
			__( 'Repair Estimates', 'computer-repair-shop' ), 
			__( 'Estimates', 'computer-repair-shop' ), 
			'edit_rep_job', 
			'edit.php?post_type=rep_estimates', 
			'', 
			259 );
	}
	if ( wcrb_use_woo_as_devices() == 'NO' ) {
		add_submenu_page( 
			'wc-computer-rep-shop-handle', 
			$wc_device_label_plural, 
			$wc_device_label_plural, 
			'edit_rep_job', 
			'edit.php?post_type=rep_devices', 
			'', 
			400 );
	}
	add_submenu_page( 
		'wc-computer-rep-shop-handle', 
		__( 'Repair Services', 'computer-repair-shop' ), 
		__( 'Services', 'computer-repair-shop' ), 
		'edit_rep_job', 
		'edit.php?post_type=rep_services', 
		'', 
		500 );
	if ( is_parts_switch_woo() === true ) {
		add_submenu_page( 
			'wc-computer-rep-shop-handle', 
			__( 'Products', 'computer-repair-shop' ), 
			__( 'Products', 'computer-repair-shop' ), 
			'edit_posts', 
			'edit.php?post_type=product', 
			'', 
			500 );
	} else {
		add_submenu_page( 
			'wc-computer-rep-shop-handle', 
			__( 'Parts', 'computer-repair-shop' ), 
			__( 'Parts', 'computer-repair-shop' ), 
			'edit_rep_job', 
			'edit.php?post_type=rep_products', 
			'', 
			500 );
	}
	add_submenu_page( 
		'wc-computer-rep-shop-handle', 
		__( 'Payments', 'computer-repair-shop' ), 
		__( 'Payments', 'computer-repair-shop' ), 
		'delete_posts', 
		'wc-computer-rep-shop-payments', 
		'wc_comp_rep_shop_payments', 
		600 );
	
	
	add_submenu_page( 
		'wc-computer-rep-shop-handle', 
		__( 'Reports', 'computer-repair-shop' ), 
		__( 'Reports', 'computer-repair-shop' ), 
		'manage_options', 
		'wc-computer-rep-reports', 
		'wc_computer_rep_reports', 
		700 );
	add_submenu_page( 'wc-computer-rep-shop-handle', __( 'Reviews on Jobs', 'computer-repair-shop' ), __( 'Job Reviews', 'computer-repair-shop' ), 
	'manage_options', 'edit.php?post_type=rep_reviews', '', 1099 );
	add_submenu_page( 
		'wc-computer-rep-shop-handle', 
		__( 'Reminder Logs', 'computer-repair-shop' ), 
		__( 'Reminder Logs', 'computer-repair-shop' ), 
		'manage_options', 
		'wcrb_reminder_logs', 
		'wcrb_display_reminder_logs', 
		800 );
	
	
	add_submenu_page( 
		'wc-computer-rep-shop-handle', 
		__( 'Clients', 'computer-repair-shop' ), 
		__( 'Clients', 'computer-repair-shop' ), 
		'delete_posts', 
		'wc-computer-rep-shop-clients', 
		'wc_comp_rep_shop_clients', 
		900 );

	add_submenu_page( 
		'wc-computer-rep-shop-handle', __( 'Customer ', 'computer-repair-shop' ) . $wc_device_label_plural, 
		__( 'Customer ', 'computer-repair-shop' ) . $wc_device_label_plural, 
		'manage_options', 'wcrb_customer_devices', 
		array( $WCRB_MANAGE_DEVICES, 'backend_customer_devices_output' ), 1000 );

	if ( wcrb_use_woo_as_devices() == 'NO' ) {
		add_submenu_page( 'wc-computer-rep-shop-handle', $wc_device_brand_label_plural, $wc_device_brand_label_plural, 'manage_options', 
		'edit-tags.php?taxonomy=device_brand&post_type=rep_devices', '', 1100 );
		add_submenu_page( 
			'wc-computer-rep-shop-handle', $wc_device_type_label_plural, 
			$wc_device_type_label_plural, 'manage_options', 
			'edit-tags.php?taxonomy=device_type&post_type=rep_devices', '', 1200 );
	}
	add_submenu_page( 
		'wc-computer-rep-shop-handle', __( 'Technicians', 'computer-repair-shop' ), 
		__( 'Technicians', 'computer-repair-shop' ), 'delete_posts', 
		'wc-computer-rep-shop-technicians', 'wc_comp_rep_shop_technicians', 1300 );

	add_submenu_page( 
		'wc-computer-rep-shop-handle', __( 'Managers', 'computer-repair-shop' ), 
		__( 'Managers', 'computer-repair-shop' ), 'manage_options', 
		'wc-computer-rep-shop-managers', 'wc_comp_rep_shop_store_manager', 1400 );

	$wcrb_disable_timelog = get_option( 'wcrb_disable_timelog' );
	if ( $wcrb_disable_timelog !== 'on' ) {
		add_submenu_page( 'wc-computer-rep-shop-handle', __('Time Logs', 'computer-repair-shop'), 
			__('Time Logs', 'computer-repair-shop'), 
			'manage_options', 
			'wc-computer-repshop-timelogs', 
			'wcrb_view_time_logs', 
			1405 
		);

		add_submenu_page( 
			'wc-computer-rep-shop-handle', __( 'Hourly Rates', 'computer-repair-shop' ), 
			__( 'Manage Hourly Rates', 'computer-repair-shop' ), 'manage_options', 
			'wc-computer-repshop-hourlyrates', 'wcrb_manage_hourly_rates', 1400 );	
	}

	$_myaccount_page = get_option('wc_rb_my_account_page_id');
	if ( ! empty( $_myaccount_page ) ) : 
		add_submenu_page(
			'wc-computer-rep-shop-handle',
			__('Expenses', 'computer-repair-shop'),
			__('Expenses', 'computer-repair-shop'),
			'delete_posts',
			'wcrb-manage-expenses',
			function() {
				$_myaccount_page = get_option('wc_rb_my_account_page_id');
				$theurl = get_permalink( $_myaccount_page );
				$theurl = add_query_arg( array( 'screen' => 'expenses' ), $theurl );
				?>
				<div class="wrap">
					<h1><?php _e('Expenses', 'computer-repair-shop'); ?></h1>
					<div class="notice notice-info">
						<p><?php _e('Go to expenses system.', 'computer-repair-shop'); ?></p>
					</div>
					<p>
						<a href="<?php echo esc_url($theurl); ?>" class="button button-primary">
							<?php _e('Go to Expenses', 'computer-repair-shop'); ?>
						</a>
					</p>
				</div>
				<?php
			},
			1400
		);
	endif;

	add_submenu_page( 
		'edit.php?post_type=rep_jobs', __( 'Print Screen', 'computer-repair-shop' ), 
		__( 'Print Screen', 'computer-repair-shop' ), 'edit_rep_job', 
		'wc_computer_repair_print', 'wc_computer_repair_print_functionality', 1500 );
}
add_action( 'admin_menu', 'wc_add_comp_rep_pages' );

// WordPress menu highlighting fix
if ( ! function_exists( 'wc_repairbuddy_menu_highlight_fix' ) ) :
	function wc_repairbuddy_menu_highlight_fix() {
		global $parent_file, $submenu_file, $post_type, $taxonomy;
		
		$repairbuddy_post_types = array( 'rep_jobs', 'rep_estimates', 'rep_devices', 'rep_services', 'rep_products', 'rep_reviews', 'rep_devices_other' );
		$repairbuddy_taxonomies = array( 'device_brand', 'device_type', 'service_type', 'brand_type', 'part_type' );
		$repairbuddy_pages = array(
			'wc-computer-rep-shop-handle',
			'wc-computer-rep-shop-appointments',
			'wc-computer-rep-shop-payments',
			'wc-computer-rep-reports',
			'wcrb_reminder_logs',
			'wc-computer-rep-shop-clients',
			'wcrb_customer_devices',
			'wc-computer-rep-shop-technicians',
			'wc-computer-rep-shop-managers',
			'wc_computer_repair_print'
		);
		
		if (in_array($post_type, $repairbuddy_post_types)) {
			$parent_file = 'wc-computer-rep-shop-handle';
		}
		
		if (in_array($taxonomy, $repairbuddy_taxonomies)) {
			$parent_file = 'wc-computer-rep-shop-handle';
		}
		
		if (isset($_GET['page']) && in_array($_GET['page'], $repairbuddy_pages)) {
			$parent_file = 'wc-computer-rep-shop-handle';
			$submenu_file = $_GET['page'];
		}
	}
	add_action('admin_head', 'wc_repairbuddy_menu_highlight_fix');
endif;

// 2. JavaScript fallback for edge cases
if ( ! function_exists( 'wc_keep_repairbuddy_menu_open_js' ) ) :
	function wc_keep_repairbuddy_menu_open_js() {
		$current_screen = get_current_screen();
		$is_repairbuddy_page = false;
		
		$repairbuddy_ids = array(
			'toplevel_page_wc-computer-rep-shop-handle',
			'admin_page_wc-computer-rep-shop-appointments',
			'edit-rep_jobs',
			'edit-rep_estimates',
			'edit-rep_devices',
			'edit-rep_services',
			'edit-rep_products',
			'admin_page_wc-computer-rep-shop-payments',
			'admin_page_wc-computer-rep-reports',
			'edit-rep_reviews',
			'admin_page_wcrb_reminder_logs',
			'admin_page_wc-computer-rep-shop-clients',
			'admin_page_wcrb_customer_devices',
			'admin_page_wc-computer-rep-shop-technicians',
			'admin_page_wc-computer-rep-shop-managers',
			'admin_page_wc_computer_repair_print'
		);
		
		if ($current_screen && in_array($current_screen->id, $repairbuddy_ids)) {
			$is_repairbuddy_page = true;
		}
		
		if ($is_repairbuddy_page) {
			?>
			<script type="text/javascript">
			jQuery(document).ready(function($) {
				$('#toplevel_page_wc-computer-rep-shop-handle').addClass('wp-has-current-submenu wp-menu-open').removeClass('wp-not-current-submenu');
				$('#toplevel_page_wc-computer-rep-shop-handle > a').addClass('wp-has-current-submenu wp-menu-open');
			});
			</script>
			<?php
		}
	}
	add_action( 'admin_footer', 'wc_keep_repairbuddy_menu_open_js' );
endif;