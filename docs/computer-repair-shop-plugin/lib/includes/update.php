<?php
if ( ! defined( 'ABSPATH' ) ) { 
	exit;
}

$current_plugin_version = get_option( 'wc_cr_shop_version' );

if ( ! function_exists( 'wcrb_check_myaccount_page' ) ) :
	function wcrb_check_myaccount_page() {
		$page_id = get_option('wc_rb_my_account_page_id');
		
		// If page ID exists, check if page still exists
		if ($page_id) {
			$page = get_post($page_id);
			
			// If page doesn't exist or is in trash, reset the option
			if (!$page || $page->post_status === 'trash') {
				delete_option('wc_rb_my_account_page_id');
				delete_option('wc_rb_customer_login_page');
				$page_id = false;
			}
		}
		
		// Create page if it doesn't exist
		if (empty($page_id)) {
			$page_args = array(
				'post_title'    => esc_html__('My Account', 'computer-repair-shop'),
				'post_content'  => '[wc_cr_my_account]',
				'post_status'   => 'publish',
				'post_author'   => get_current_user_id(),
				'post_type'     => 'page',
			);
			
			// Insert the page
			$new_page_id = wp_insert_post($page_args);
			
			if (!is_wp_error($new_page_id) && $new_page_id) {
				// Set the correct template
				update_post_meta($new_page_id, '_wp_page_template', 'myaccount_dashboard.php');
				
				// Save options
				update_option('wc_rb_my_account_page_id', $new_page_id);
				update_option('wc_rb_customer_login_page', $new_page_id);
			}
		} else {
			// Page exists, ensure it has the correct template and shortcode
			$page = get_post($page_id);
			
			// Check if page has the shortcode
			if (!has_shortcode($page->post_content, 'wc_cr_my_account')) {
				wp_update_post(array(
					'ID' => $page_id,
					'post_content' => '[wc_cr_my_account]'
				));
			}
			
			// Check if page has correct template
			$current_template = get_post_meta($page_id, '_wp_page_template', true);
			if ($current_template !== 'myaccount_dashboard.php') {
				update_post_meta($page_id, '_wp_page_template', 'myaccount_dashboard.php');
			}
		}
		
		return $page_id ?: $new_page_id ?? false;
	}
endif;

if ( ! function_exists( 'wcrb_update_customers_meta' ) ) :
	function wcrb_update_customers_meta() {
		$customersUpdated 	= get_option( 'customersUpdated' );

		if ( $customersUpdated != 'YES' ) {
			//Let's update customers data.
			$role_query = new WP_User_Query( array( 'role__in' => array( 'customer', 'technician', 'store_manager' ) ) );

			foreach ( $role_query->get_results() as $userdata ) {
				$phone_number 	= get_user_meta( $userdata->ID, "customer_phone", true );
				update_user_meta( $userdata->ID, 'billing_phone', $phone_number );

				$company 		= get_user_meta( $userdata->ID, "company", true );
				update_user_meta( $userdata->ID, 'billing_company', $company );

				$address 		= get_user_meta( $userdata->ID, "customer_address", true );
				update_user_meta( $userdata->ID, 'billing_address_1', $address );

				$city 			= get_user_meta( $userdata->ID, "customer_city", true );
				update_user_meta( $userdata->ID, 'billing_city', $city );

				$zip_code 		= get_user_meta( $userdata->ID, "zip_code", true );
				update_user_meta( $userdata->ID, 'billing_postcode', $zip_code );

				$state_province = get_user_meta( $userdata->ID, "state_province", true );
				update_user_meta( $userdata->ID, 'billing_state', $state_province );

				$country 		= get_user_meta( $userdata->ID, "country", true );
				update_user_meta( $userdata->ID, 'billing_country', $country );
			}//EndForeach.
			update_option( 'customersUpdated', 'YES' );
		}
	}
endif;

if ( ! function_exists( 'wcrb_update_shipping_address_customers' ) ) :
	function wcrb_update_shipping_address_customers() {
		$shippingUpdated 	= get_option( 'shippingupdated' );
		$customersUpdated 	= get_option( 'customersUpdated' );

		if ( $customersUpdated == 'YES' && $shippingUpdated != 'YES' ) {
			//Let's update customers data.
			$role_query = new WP_User_Query( array( 'role__in' => array( 'customer', 'technician', 'store_manager' ) ) );

			foreach ( $role_query->get_results() as $userdata ) {
				$first_name   = get_user_meta( $userdata->ID, 'billing_first_name', true );
				$last_name    = get_user_meta( $userdata->ID, 'billing_last_name', true );
				$user_company = get_user_meta( $userdata->ID, 'billing_company', true );
				$user_address = get_user_meta( $userdata->ID, 'billing_address_1', true );
				$user_city    = get_user_meta( $userdata->ID, 'billing_city', true );
				$postal_code  = get_user_meta( $userdata->ID, 'billing_postcode', true );
				$phone_number = get_user_meta( $userdata->ID, 'billing_phone', true );
				$billing_tax  = get_user_meta( $userdata->ID, 'billing_tax', true );
				$userState    = get_user_meta( $userdata->ID, 'billing_state', true );
				$userCountry  = get_user_meta( $userdata->ID, 'billing_country', true );

				update_user_meta( $userdata->ID, 'billing_email', $userdata->email );

				update_user_meta( $userdata->ID, 'shipping_first_name', $first_name );
				update_user_meta( $userdata->ID, 'shipping_last_name', $last_name );
				update_user_meta( $userdata->ID, 'shipping_company', $user_company );
				update_user_meta( $userdata->ID, 'shipping_tax', $billing_tax );
				update_user_meta( $userdata->ID, 'shipping_address_1', $user_address );
				update_user_meta( $userdata->ID, 'shipping_city', $user_city );
				update_user_meta( $userdata->ID, 'shipping_postcode', $postal_code );
				update_user_meta( $userdata->ID, 'shipping_state', $userState );
				update_user_meta( $userdata->ID, 'shipping_country', $userCountry );
				update_user_meta( $userdata->ID, 'shipping_phone', $phone_number );


			}//EndForeach.
			update_option( 'shippingupdated', 'YES' ); 
		}
	}
endif;

if ( ! function_exists( 'wcrb_migrate_existing_jobs_to_table' ) ) :
	function wcrb_migrate_existing_jobs_to_table() {
		$jobs_migrated = get_option( 'wcrb_jobs_migration_completed' );

		if ( $jobs_migrated != 'YES' ) {
			// Let's migrate existing jobs to the new table
			global $wpdb;
			$jobs_manager = WCRB_JOBS_MANAGER::getInstance();
			
			// Get all rep_jobs posts in any status
			$args = [
				'post_type' => 'rep_jobs',
				'post_status' => ['publish', 'pending', 'draft', 'private', 'trash', 'inherit', 'auto-draft'],
				'posts_per_page' => -1,
				'fields' => 'ids',
				'orderby' => 'ID',
				'order' => 'ASC' // Oldest posts first
			];
			
			$job_posts = get_posts($args);
			$migrated_count = 0;
			
			foreach ( $job_posts as $post_id ) {
				// Check if post meta already exists with _wcrb_job_id
				$existing_job_meta = get_post_meta( $post_id, '_wcrb_job_id', true );
				
				if ( ! empty( $existing_job_meta ) ) {
					continue; // Skip if meta already exists
				}
				
				// Check if job already exists in custom table
				$existing_table_job = $jobs_manager->get_job_by_post_id( $post_id );
				if ( $existing_table_job ) {
					// Update post meta with existing job_id
					update_post_meta( $post_id, '_wcrb_job_id', $existing_table_job->job_id );
					$migrated_count++;
					continue;
				}
				
				// Create new job entry
				$job_id = $jobs_manager->create_job( $post_id );
				
				if ( ! is_wp_error( $job_id ) ) {
					$migrated_count++;
				}
			}
			
			update_option( 'wcrb_jobs_migration_completed', 'YES' );
			update_option( 'wcrb_jobs_migration_count', $migrated_count );
		}
	}
endif;

if ( ! function_exists( "wc_computer_repair_shop_update" ) ) :
	function wc_computer_repair_shop_update() {
		//Installs default values on activation.
		global $wpdb;
		require_once( ABSPATH .'wp-admin/includes/upgrade.php' );

		$charset_collate = $wpdb->get_charset_collate();

		$computer_repair_items 			 = $wpdb->prefix.'wc_cr_order_items';
		$computer_repair_items_meta 	 = $wpdb->prefix.'wc_cr_order_itemmeta';
		$computer_repair_taxes 			 = $wpdb->prefix.'wc_cr_taxes';
		$computer_repair_job_status 	 = $wpdb->prefix.'wc_cr_job_status';
		$computer_repair_payment_status  = $wpdb->prefix.'wc_cr_payment_status';
		$computer_repair_history 		 = $wpdb->prefix.'wc_cr_job_history';
		$computer_repair_payments 		 = $wpdb->prefix.'wc_cr_payments';
		$computer_repair_maint_reminders = $wpdb->prefix.'wc_cr_maint_reminders';
		$computer_repair_reminder_logs   = $wpdb->prefix.'wc_cr_reminder_logs';
		$computer_repair_customer_devices = $wpdb->prefix.'wc_cr_customer_devices';
		$computer_repair_feedback_log 	  = $wpdb->prefix.'wc_cr_feedback_log';
		$computer_repair_time_logs 		  = $wpdb->prefix . 'wc_cr_time_logs';

		$expense_categories = $wpdb->prefix . 'wc_cr_expense_categories';

		$sql = 'CREATE TABLE IF NOT EXISTS ' . $expense_categories . '(
			`category_id` bigint(20) NOT NULL AUTO_INCREMENT,
			`category_name` varchar(100) NOT NULL,
			`category_description` text NULL,
			`category_type` varchar(50) NOT NULL DEFAULT "expense", -- expense/income
			`parent_category_id` bigint(20) DEFAULT 0,
			`color_code` varchar(7) DEFAULT "#3498db",
			`icon_class` varchar(50) NULL,
			`is_active` tinyint(1) DEFAULT 1,
			`is_default` tinyint(1) DEFAULT 0,
			`taxable` tinyint(1) DEFAULT 1,
			`tax_rate` decimal(5,2) DEFAULT 0.00,
			`sort_order` int(11) DEFAULT 0,
			`created_by` bigint(20) NULL,
			`created_at` datetime DEFAULT CURRENT_TIMESTAMP,
			`updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (`category_id`),
			KEY `idx_category_type` (`category_type`),
			KEY `idx_parent_category` (`parent_category_id`),
			KEY `idx_is_active` (`is_active`),
			KEY `idx_sort_order` (`sort_order`)
		) ' . $charset_collate . ';';
		dbDelta($sql);
					
		$sql = 'CREATE TABLE IF NOT EXISTS '.$computer_repair_customer_devices.'(
			`device_id` bigint(20) NOT NULL AUTO_INCREMENT,
			`device_post_id` bigint(20) NULL,
			`device_label` varchar(600) NOT NULL,
			`serial_nuumber` varchar(200) NOT NULL,
			`pint_code` varchar(200) NOT NULL,
			`customer_id` bigint(20) NULL,
			PRIMARY KEY (`device_id`)
		) '.$charset_collate.';';	
		dbDelta($sql);

		$sql = 'CREATE TABLE IF NOT EXISTS '.$computer_repair_reminder_logs.'(
			`log_id` bigint(20) NOT NULL AUTO_INCREMENT,
			`datetime` datetime NULL,
			  `customer_id` bigint(20) NULL,
			`job_id` bigint(20) NULL,
			`reminder_id` bigint(20) NULL,
			`email_to` varchar(200) NOT NULL,
			`sms_to` varchar(200) NOT NULL,
			`status` varchar(200) NOT NULL,
			  PRIMARY KEY (`log_id`)
		) '.$charset_collate.';';	
		dbDelta($sql);

		$sql = 'CREATE TABLE IF NOT EXISTS '.$computer_repair_feedback_log.'(
			`log_id` bigint(20) NOT NULL AUTO_INCREMENT,
			`datetime` datetime NULL,
			`job_id` bigint(20) NULL,
			`email_to` varchar(200) NOT NULL,
			`sms_to` varchar(200) NOT NULL,
			`type` varchar(200) NOT NULL,
			`action` varchar(200) NOT NULL,
			  PRIMARY KEY (`log_id`)
		) '.$charset_collate.';';	
		dbDelta($sql);

		$sql = 'CREATE TABLE IF NOT EXISTS '.$computer_repair_maint_reminders.'(
			`reminder_id` bigint(20) NOT NULL AUTO_INCREMENT,
			`datetime` datetime NULL,
			  `name` varchar(500) NULL,
			`description` longtext NULL,
			`interval` varchar(200) NOT NULL,
			`email_body` longtext NULL,
			`sms_body` longtext NULL,
			`device_type` varchar(200) NOT NULL,
			`device_brand` varchar(200) NOT NULL,
			`email_status` varchar(200) NOT NULL,
			`sms_status` varchar(200) NOT NULL,
			`reminder_status` varchar(200) NOT NULL,
			`last_execution` datetime NULL,
			  PRIMARY KEY (`reminder_id`)
		) '.$charset_collate.';';	
		dbDelta($sql);

		$sql = 'CREATE TABLE IF NOT EXISTS '.$computer_repair_payments.'(
			`payment_id` bigint(20) NOT NULL AUTO_INCREMENT,
			`date` datetime NULL,
			`order_id` bigint(20) NOT NULL,
			`receiver_id` bigint(20) NULL,
			`method` varchar(50) NULL,
			`identifier` longtext NULL,
			`transaction_id` varchar(200) NULL,
			`payment_status` varchar(50) NOT NULL,
			`note` longtext NULL,
			`amount` double NULL,
			`discount` double NULL,
			`currency` varchar(30) NULL,
			`status` varchar(50) NULL,
			`woo_orders` longtext NULL,
			PRIMARY KEY (`payment_id`)
		) '.$charset_collate.';';	
		dbDelta($sql);
		
		$sql = 'CREATE TABLE IF NOT EXISTS '.$computer_repair_items.'(
			`order_item_id` bigint(20) NOT NULL AUTO_INCREMENT,
			`order_item_name` varchar(100) NOT NULL,
			  `order_item_type` varchar(50) NOT NULL,
			`order_id` bigint(20) NOT NULL,
			  PRIMARY KEY (`order_item_id`)
		) '.$charset_collate.';';	
		dbDelta($sql);
		
		
		$sql = 'CREATE TABLE IF NOT EXISTS '.$computer_repair_items_meta.'(
			`meta_id` bigint(20) NOT NULL AUTO_INCREMENT,
			`order_item_id` bigint(20) NOT NULL,
			  `meta_key` varchar(250) NOT NULL,
			`meta_value` longtext NOT NULL,
			  PRIMARY KEY (`meta_id`),
			FOREIGN KEY (order_item_id) REFERENCES '.$computer_repair_items.'(order_item_id)
		) '.$charset_collate.';';	
		dbDelta($sql);

		/*
			@Since 2.5

			Reactivate the Plugin required
		*/
		$sql = 'CREATE TABLE IF NOT EXISTS '.$computer_repair_taxes.'(
			`tax_id` bigint(20) NOT NULL AUTO_INCREMENT,
			`tax_name` varchar(250) NOT NULL,
			`tax_description` varchar(250) NOT NULL,
			`tax_rate` varchar(50) NOT NULL,
			`tax_status` varchar(20) NOT NULL,
			PRIMARY KEY (`tax_id`)
		) '.$charset_collate.';';	
		dbDelta($sql);


		/*
			@Since 3.1

			Reactivate the Plugin required
		*/
		$sql = 'CREATE TABLE IF NOT EXISTS '.$computer_repair_job_status.'(
			`status_id` bigint(20) NOT NULL AUTO_INCREMENT,
			`status_name` varchar(250) NOT NULL,
			`status_slug` varchar(250) NOT NULL,
			`status_description` varchar(250) NOT NULL,
			`status_email_message` varchar(600) NOT NULL,
			`invoice_label` varchar(100) NOT NULL,
			`inventory_count` varchar(20) NOT NULL,
			`status_status` varchar(20) NOT NULL,
			PRIMARY KEY (`status_id`)
		) '.$charset_collate.';';	
		dbDelta($sql);

		/*
			@Since 3.7946

			Reactivate the Plugin required
		*/
		$sql = 'CREATE TABLE IF NOT EXISTS '.$computer_repair_payment_status.'(
			`status_id` bigint(20) NOT NULL AUTO_INCREMENT,
			`status_name` varchar(250) NOT NULL,
			`status_slug` varchar(250) NOT NULL,
			`status_description` varchar(250) NOT NULL,
			`status_email_message` varchar(600) NOT NULL,
			`status_status` varchar(20) NOT NULL,
			PRIMARY KEY (`status_id`)
		) '.$charset_collate.';';	
		dbDelta($sql);
		
		/*
			@Since 3.59

			Reactivate the Plugin
		*/
		$sql = 'CREATE TABLE IF NOT EXISTS '.$computer_repair_history.'(
			`history_id` 	bigint(20) NOT NULL AUTO_INCREMENT,
			`datetime`		datetime NULL,
			`job_id`		bigint(20) NULL,
			`name`			varchar(600) NULL,
			`type`			varchar(50) NULL,
			`field`			varchar(50) NULL,
			`change_detail`	longtext NULL,
			`user_id`		bigint(20) NULL,
			PRIMARY KEY (`history_id`)
		) '.$charset_collate.';';	
		dbDelta($sql);

		// Add this to activate.php/update.php
		$sql = 'CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . 'wc_cr_jobs' . ' (
			`job_id` bigint(20) NOT NULL AUTO_INCREMENT,
			`post_id` bigint(20) NOT NULL,
			`created_at` datetime NOT NULL,
			PRIMARY KEY (`job_id`),
			UNIQUE KEY `post_id` (`post_id`)
		) ' . $charset_collate . ';';
		dbDelta($sql);

		$row = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
			WHERE `TABLE_NAME` = '".$computer_repair_job_status."' AND `COLUMN_NAME` = 'inventory_count'" );

		if(empty($row)){
			$wpdb->query("ALTER TABLE `".$computer_repair_job_status."` ADD `inventory_count` varchar(20) NOT NULL AFTER `status_description`"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}

		$row = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
			WHERE `TABLE_NAME` = '".$computer_repair_job_status."' AND `COLUMN_NAME` = 'status_email_message'" );

		if(empty($row)){
			$wpdb->query("ALTER TABLE `".$computer_repair_job_status."` ADD `status_email_message` varchar(600) NOT NULL AFTER `status_description`"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}

		$row = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
			WHERE `TABLE_NAME` = '".$computer_repair_job_status."' AND `COLUMN_NAME` = 'invoice_label'" );

		if ( empty( $row ) ){
			$wpdb->query("ALTER TABLE `".$computer_repair_job_status."` ADD `invoice_label` varchar(100) NOT NULL AFTER `status_email_message`"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}

		//Payment table > Transaction ID
		$row = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
			WHERE `TABLE_NAME` = '".$computer_repair_payments."' AND `COLUMN_NAME` = 'transaction_id'" );

		if ( empty( $row ) ){
			$wpdb->query("ALTER TABLE `" . $computer_repair_payments . "` ADD `transaction_id` varchar(200) NULL AFTER `identifier`"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}

		//Payment table > Currency
		$row = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
			WHERE `TABLE_NAME` = '".$computer_repair_payments."' AND `COLUMN_NAME` = 'currency'" );

		if ( empty( $row ) ){
			$wpdb->query("ALTER TABLE `" . $computer_repair_payments . "` ADD `currency` varchar(30) NULL AFTER `discount`"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}

		$payment_methods = get_option( 'wc_rb_payment_methods_active' );
		if ( empty ( $payment_methods ) ) {
			$default_methods = array( 'cash', 'bank-transfer', 'check', 'card-swipe', 'mobile-payment' );
			update_option( 'wc_rb_payment_methods_active', serialize( $default_methods ) );
		}

		//Declared in active.php
		wc_computer_repair_shop_default_status_data();
		wc_computer_repair_shop_default_payment_data();
		wcrb_update_customers_meta();
		wc_rs_verify_purchase( '', '' );
		wcrb_update_shipping_address_customers();
		wcrb_migrate_existing_jobs_to_table();
		wcrb_check_myaccount_page(); // Now this will work!
		repairbuddy_create_expense_tables();

		update_option( "wc_cr_shop_version", WC_CR_SHOP_VERSION );
	}//end of function wc_restaurant_install()
endif;	

/*
	check Update status and run functions
*/
if ( ! empty( $current_plugin_version ) && $current_plugin_version != WC_CR_SHOP_VERSION ) {
	add_action( 'wp_loaded', 'wc_computer_repair_shop_update' );
}