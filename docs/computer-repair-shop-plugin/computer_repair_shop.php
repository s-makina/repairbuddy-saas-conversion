<?php
/***
	Plugin Name: CRM WordPress Plugin - RepairBuddy
	Plugin URI: https://www.webfulcreations.com/
	Description: WordPress CRM Plugin which helps you manage your jobs, parts, services and extras better client and jobs management system.
	Version: 4.1121
	Author: Webful Creations
	Author URI: https://www.webfulcreations.com/
	License: GPLv2 or later.
	License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
	Text Domain: computer-repair-shop
	Domain Path: languages
	Requires at least: 5.0
	Tested up to: 6.9
	Requires PHP: 8.1

	@package : 4.1121
 */
if ( ! defined( 'ABSPATH' ) ) { 
	exit;
}
if ( ! defined( 'DS' ) ) {
	define( 'DS', '/' ); // Defining Directory seprator, not using php default Directory seprator to avoide problem in windows.
}
define( 'WC_CR_SHOP_VERSION', '4.1121' );

if ( ! function_exists( 'wc_language_plugin_init' ) ) :
	/**
	 * Function to initialize language
	 *
	 * @Since: V 1.0
	 */
	function wc_language_plugin_init() {
		load_plugin_textdomain( 'computer-repair-shop', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
	add_action( 'plugins_loaded', 'wc_language_plugin_init' );
endif;

// Define folder name.
if (is_ssl() ) {
    define( 'REPAIRBUDDY_HOME_URL', home_url('', 'https') );
} else {
    define( 'REPAIRBUDDY_HOME_URL', home_url() );
}

define( 'WC_COMPUTER_REPAIR_SHOP_FOLDER', dirname( plugin_basename( __FILE__ ) ) );
define( 'WCRB_DEFINE_PLUGIN_BASE_FILE', plugin_basename(__FILE__) );
define( 'WC_COMPUTER_REPAIR_SHOP_DIR', plugin_dir_path( __FILE__ ) );

define( 'WC_COMPUTER_REPAIR_DIR_URL', plugins_url( '', __FILE__ ) );

require_once WC_COMPUTER_REPAIR_SHOP_DIR . 'activate.php';
register_activation_hook( __FILE__, 'wc_computer_repair_shop_install' ); // Plugin activation hook.

require_once WC_COMPUTER_REPAIR_SHOP_DIR . 'lib' . DS . 'includes' . DS . 'update.php';

// Admin pages starts here.
require_once WC_COMPUTER_REPAIR_SHOP_DIR . 'lib' . DS . 'includes' . DS . 'classes' . DS . 'index.php';
require_once WC_COMPUTER_REPAIR_SHOP_DIR . 'lib' . DS . 'vendor' . DS . 'autoload.php';
require_once WC_COMPUTER_REPAIR_SHOP_DIR . 'lib' . DS . 'includes' . DS . 'index.php';
require_once WC_COMPUTER_REPAIR_SHOP_DIR . 'lib' . DS . 'templates' . DS . 'main_templates.php';
require_once WC_COMPUTER_REPAIR_SHOP_DIR . 'lib' . DS . 'shortcodes' . DS . 'shortcodes.php';
require_once WC_COMPUTER_REPAIR_SHOP_DIR . 'admin_menu.php'; // Include admin menu file.
// Admin pages ends here.

if ( ! function_exists( 'wc_cmp_rep_ser_admin_scripts' ) ) :
	/**
	 * Adding Styles and Scripts
	 * To WordPress Admin side
	 *
	 * @Since 1.0.1
	 */
	function wc_cmp_rep_ser_admin_scripts() {
		global $pagenow;

		$current_page = get_current_screen();
		$wc_the_page  = ( isset( $_GET['page'] ) ) ? sanitize_text_field( $_GET['page'] ) : "";

		//Register scripts to use later
		//intl-tel-input
        wp_register_script( 'intl-tel-input', WC_COMPUTER_REPAIR_DIR_URL . '/assets/vendors/intl-tel-input/js/intlTelInputWithUtils.min.js', array( 'jquery' ), '23.1.0', true );
        wp_register_style( 'intl-tel-input', WC_COMPUTER_REPAIR_DIR_URL . '/assets/vendors/intl-tel-input/css/intlTelInput.min.css', array(),'23.1.0','all' );

		if ( ( 'rep_jobs' === $current_page->post_type && ( 'post-new.php' === $pagenow || 'post.php' === $pagenow || 'edit.php' === $pagenow ) ) ||
 			( 'rep_estimates' === $current_page->post_type && ( 'post-new.php' === $pagenow || 'post.php' === $pagenow || 'edit.php' === $pagenow ) ) ||
			( 'rep_products' === $current_page->post_type && ( 'post-new.php' === $pagenow || 'post.php' === $pagenow ) ) ||
			( 'rep_reviews' === $current_page->post_type && ( 'post-new.php' === $pagenow || 'post.php' === $pagenow || 'edit.php' === $pagenow ) ) || 
			( 'rep_services' === $current_page->post_type && ( 'post-new.php' === $pagenow || 'post.php' === $pagenow ) ) ||
			( isset( $wc_the_page ) &&
			( 'wc-computer-rep-shop-handle' === $wc_the_page ||
			'wc-computer-rep-shop-payments' === $wc_the_page ||
			'wc-computer-rep-shop-appointments' === $wc_the_page ||
			'wcrb_customer_devices' == $wc_the_page ||
			'wc_computer_repair_print' === $wc_the_page ||
			'wc-computer-rep-shop-technicians' === $wc_the_page ||
			'wc-computer-rep-shop-managers' === $wc_the_page ||
			'wc-computer-rep-shop-reports' === $wc_the_page ||
			'wc-computer-rep-reports' === $wc_the_page ||
			'wc-computer-rep-shop-clients' === $wc_the_page ) ) ) {
			if ( 'edit.php' !== $pagenow ) {
				wp_register_style( 'foundation-css', plugins_url( 'assets/admin/css/foundation.min.css', __FILE__ ), array(), '6.5.3', 'all', true );
				wp_enqueue_style( 'foundation-css' );

				// Foundation CSS enque.
				wp_enqueue_style( 'wc-admin-style', plugins_url( 'assets/admin/css/style.css', __FILE__ ), array(), WC_CR_SHOP_VERSION, 'all' );
			} else {
				wp_enqueue_style( 'wc-admin-edit-style', plugins_url( 'assets/admin/css/editpage_styles.css', __FILE__ ), array(), WC_CR_SHOP_VERSION, 'all' );
			}
			//Admin styles enque
			wp_enqueue_style( 'select2', plugins_url( 'assets/admin/css/select2.min.css', __FILE__ ), array(), '4.0.13', 'all' );

			//Admin JS enque
			wp_enqueue_script( 'foundation-js', plugins_url( 'assets/admin/js/foundation.min.js', __FILE__ ), array( 'jquery' ), '6.5.3', true );

			wp_enqueue_script( 'select2', plugins_url( 'assets/admin/js/select2.min.js', __FILE__ ), array( 'jquery' ), '4.0.13', true );
			wp_enqueue_script( 'wc-js', plugins_url( 'assets/admin/js/my-admin.js', __FILE__ ), array( 'jquery' ), WC_CR_SHOP_VERSION, true );

			wp_enqueue_script( 'ajax_script', plugins_url( 'assets/admin/js/ajax_scripts.js', __FILE__ ), array('jquery'), WC_CR_SHOP_VERSION, true );
			wp_localize_script( 'ajax_script', 'ajax_obj', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

			if ( $wc_the_page == 'wc-computer-rep-shop-appointments' ) {
				wp_enqueue_script( 'popper-js', plugins_url( 'assets/admin/js/fullcalendar/popper.min.js', __FILE__ ), array( 'jquery' ), '1.16.1', true );
				wp_enqueue_script( 'tooltip-js', plugins_url( 'assets/admin/js/fullcalendar/tooltip.min.js', __FILE__ ), array( 'jquery' ), '1.3.3', true );
				wp_enqueue_script( 'fullcalendar-core', WC_COMPUTER_REPAIR_DIR_URL . '/assets/admin/js/fullcalendar/index.global.min.js', ['jquery'], '6.1.19', true );
		        wp_enqueue_script( 'fullcalendar-locales', WC_COMPUTER_REPAIR_DIR_URL . '/assets/admin/js/fullcalendar/locales-all.global.min.js', array('fullcalendar-core'), '6.1.19', true );
			}
			$wc_file_attachment_in_job = get_option( 'wc_file_attachment_in_job' );

			if ( 'on' === $wc_file_attachment_in_job ) {
				if ( 'rep_jobs' === $current_page->post_type ) {
					wp_enqueue_script( 'wc-file-js', plugins_url( 'assets/admin/js/file_upload.js', __FILE__ ), array( 'jquery' ), WC_CR_SHOP_VERSION, true );
					if ( ! did_action( 'wp_enqueue_media' ) ) {
						wp_enqueue_media();
					}
				}
			}
		}
	}//end wc_cmp_rep_ser_admin_scripts()
	add_action( 'admin_enqueue_scripts', 'wc_cmp_rep_ser_admin_scripts', 1 );
endif;

if ( ! function_exists( 'wcrb_run_reminders' ) ) :
	function wcrb_run_reminders() {
		global $OBJ_MAINTENANCE_REMINDER;
		
		$OBJ_MAINTENANCE_REMINDER->execute_reminder();
	}
	add_action( 'admin_init', 'wcrb_run_reminders' );
endif;

if ( ! function_exists( 'wcrb_get_details_ofubsub' ) ) :
	add_action( 'init', 'wcrb_get_details_ofubsub' );
	function wcrb_get_details_ofubsub() {
		if ( ( isset( $_GET['theunsub'] ) && ! empty( $_GET['theunsub'] ) ) && ( isset( $_GET['thejobid'] ) && ! empty( $_GET['thejobid'] ) ) ) {
			// If so echo the value
			$thejobid = sanitize_text_field( $_GET['thejobid'] );
			$theunsub = sanitize_text_field( $_GET['theunsub'] );
	
			update_post_meta( $thejobid, '_email_optout', 'YES' );
		}
	}
endif;

if ( ! function_exists( 'wc_cyber_sale_notice' ) ) :
    /**
     * Show Cyber Sale notice in admin
     */
    function wc_cyber_sale_notice() {
        if ( ! wc_rs_license_state() && ! defined( 'REPAIRBUDDY_WHITELABEL' ) ) {
            ?>
            <div class="notice notice-success is-dismissible" style="border-left-color: #d62828;">
                <h2>🎄 Cyber Sale — RepairBuddy WP Plugin Is Now 40% OFF! Limited-Time Offer 🎁</h2>

                <p>
                    <a href="<?php echo esc_url( 'https://www.webfulcreations.com/repairbuddy-wordpress-plugin/pricing/?event=cyberweek' ); ?>" target="_blank" class="button button-primary">
                        <?php echo esc_html__( 'Buy Plugin!', 'computer-repair-shop' ); ?>
                    </a>
                </p>

                <p>
                    🎅 Enjoy the holiday season with a special <strong>40% OFF</strong> on the RepairBuddy plugin!  
                    Use coupon code  
                    <strong style="color: #d62828; font-size: 15px;">HEYBUDDY</strong>  
                    at checkout during our limited-time <strong>Cyber Sale</strong>. ❄️  
                    <br><strong style="color:#d62828;">Hurry — this offer can end anytime!</strong>
                </p>
            </div>
            <?php
        }
    }
endif;

if ( ! function_exists( 'wcrb_disable_admin_notices' ) ) :
	function wcrb_disable_admin_notices() {
		global $pagenow;

		$current_page = get_current_screen();
		$wc_the_page  = ( isset( $_GET['page'] ) ) ? sanitize_text_field( $_GET['page'] ) : "";

		if ( ( 'rep_jobs' === $current_page->post_type && ( 'post-new.php' === $pagenow || 'post.php' === $pagenow || 'edit.php' === $pagenow ) ) ||
 			( 'rep_estimates' === $current_page->post_type && ( 'post-new.php' === $pagenow || 'post.php' === $pagenow || 'edit.php' === $pagenow ) ) ||
			( 'rep_products' === $current_page->post_type && ( 'post-new.php' === $pagenow || 'post.php' === $pagenow  || 'edit.php' === $pagenow ) ) ||
			( 'rep_reviews' === $current_page->post_type && ( 'post-new.php' === $pagenow || 'post.php' === $pagenow || 'edit.php' === $pagenow ) ) || 
			( 'rep_services' === $current_page->post_type && ( 'post-new.php' === $pagenow || 'post.php' === $pagenow  || 'edit.php' === $pagenow ) ) || 
			( 'rep_devices' === $current_page->post_type && ( 'post-new.php' === $pagenow || 'post.php' === $pagenow  || 'edit.php' === $pagenow ) ) || 
			( 'rep_devices_other' === $current_page->post_type && ( 'post-new.php' === $pagenow || 'post.php' === $pagenow  || 'edit.php' === $pagenow ) ) || 
			( isset( $wc_the_page ) &&
			( 'wc-computer-rep-shop-handle' === $wc_the_page ||
			'wc-computer-rep-shop-payments' === $wc_the_page ||
			'wc_computer_repair_print' === $wc_the_page ||
			'wc-computer-rep-shop-appointments' === $wc_the_page ||
			'wc-computer-rep-shop-technicians' === $wc_the_page ||
			'wcrb_customer_devices' == $wc_the_page ||
			'wcrb_reminder_logs' == $wc_the_page ||
			'wc-computer-rep-shop-managers' === $wc_the_page ||
			'wc-computer-rep-shop-reports' === $wc_the_page ||
			'wc-computer-rep-reports' === $wc_the_page ||
			'wc-computer-rep-shop-clients' === $wc_the_page ) ) ) {
				remove_all_actions( 'admin_notices' );

				if ( isset( $_POST['wc_rep_currency_submit'] ) || isset( $_POST['wc_rep_labels_submit'] ) || isset( $_POST['wc_rep_settings'] ) ) {
					add_action( "admin_notices", "wc_main_settings_saved" );
				}
			}
			//Disable after sale
			//add_action( 'admin_notices', 'wc_cyber_sale_notice' );
	}
	add_action( 'in_admin_header', 'wcrb_disable_admin_notices' );
endif;