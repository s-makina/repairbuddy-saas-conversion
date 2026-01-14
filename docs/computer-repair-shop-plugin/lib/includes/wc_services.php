<?php
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wc_repair_shop_services_init' ) ) :
	function wc_repair_shop_services_init() {
		$labels = array(
			'add_new_item' 			=> esc_html__('Add new Service', 'computer-repair-shop'),
			'singular_name' 		=> esc_html__('Service', 'computer-repair-shop'), 
			'menu_name' 			=> esc_html__('Services', 'computer-repair-shop'),
			'all_items' 			=> esc_html__('Services', 'computer-repair-shop'),
			'edit_item' 			=> esc_html__('Edit Service', 'computer-repair-shop'),
			'new_item' 				=> esc_html__('New Service', 'computer-repair-shop'),
			'view_item' 			=> esc_html__('View Service', 'computer-repair-shop'),
			'search_items' 			=> esc_html__('Search Services', 'computer-repair-shop'),
			'not_found' 			=> esc_html__('No service found', 'computer-repair-shop'),
			'not_found_in_trash' 	=> esc_html__('No service in trash', 'computer-repair-shop')
		);
		$capabilities = wc_custom_post_capabilities('repair_service', 'repair_services');
		$args = array(
			'labels'             	=> $labels,
			'label'					=> esc_html__( 'Services', 'computer-repair-shop' ),
			'description'        	=> esc_html__( 'Services Section', 'computer-repair-shop' ),
			'public'             	=> true,
			'publicly_queryable' 	=> true,
			'show_ui'            	=> true,
			'show_in_menu'       	=> '',
			'query_var'          	=> true,
			'rewrite'            	=> array( 'slug' => 'rbservices' ),
			'capabilities'    		=> $capabilities,
			'map_meta_cap' 			=> true,
			'has_archive'        	=> true,
			'menu_icon'			 	=> 'dashicons-clipboard',
			'menu_position'      	=> 30,
			'supports'           	=> array( 'title', 'editor', 'thumbnail'), 	
			'register_meta_box_cb' 	=> 'wc_service_features',
			'template'				=> array(),
			'template_lock'			=> false,
			'taxonomies' 			=> array('service_type')
		);
		
		register_post_type( 'rep_services', $args );
	}
	add_action('init', 'wc_repair_shop_services_init');
endif;
//registeration of post type ends here.

if ( ! function_exists( 'wc_create_service_tax_type' ) ) :
	add_action( 'init', 'wc_create_service_tax_type');
	function wc_create_service_tax_type() {
		$labels = array(
			'name'              => esc_html__('Service Types', 'computer-repair-shop'),
			'singular_name'     => esc_html__('Service Type', 'computer-repair-shop'),
			'search_items'      => esc_html__('Search Service Types', 'computer-repair-shop'),
			'all_items'         => esc_html__('All Service Types', 'computer-repair-shop'),
			'parent_item'       => esc_html__('Parent Type', 'computer-repair-shop'),
			'parent_item_colon' => esc_html__('Parent Type', 'computer-repair-shop') . ':',
			'edit_item'         => esc_html__('Edit Service Type', 'computer-repair-shop'),
			'update_item'       => esc_html__('Update Service Type', 'computer-repair-shop'),
			'add_new_item'      => esc_html__('Add New Service Type', 'computer-repair-shop'),
			'new_item_name'     => esc_html__('New Service Type Name', 'computer-repair-shop'),
			'menu_name'         => esc_html__('Service Type', 'computer-repair-shop')
		);
		
		$args = array(
				'label' 		=> esc_html__( 'Service Type', "computer-repair-shop"),
				'rewrite' 		=> array('slug' => 'service_type'),
				'public' 		=> true,
				'labels' 		=> $labels,
				'hierarchical' 	=> true,
				'capabilities' => array(
								'manage_terms' => 'manage_rep_products',
								'edit_terms'   => 'manage_rep_products',
								'delete_terms' => 'manage_rep_products',
								'assign_terms' => 'edit_rep_products',
							),
				'show_ui'       => true,
				'query_var'     => true,
				'show_admin_column' => true,
		);
		
		register_taxonomy(
			'service_type',
			'rep_services',
			$args
		);
	}
	//Registration of Taxanomy Ends here.
endif;

if ( ! function_exists( 'wc_service_features' ) ) :
	function wc_service_features() { 
		$screens = array('rep_services');

		foreach ( $screens as $screen ) {
			add_meta_box(
				'myplugin_sectionid',
				esc_html__( 'Service details', 'computer-repair-shop' ),
				'wc_services_features_callback',
				$screen,
				'advanced',
				'high'
			);
		}
	} //Parts features post.
	add_action( 'add_meta_boxes', 'wc_service_features');
endif;

if ( ! function_exists( 'wc_services_features_callback' ) ) :
	function wc_services_features_callback( $post ) {

		wp_nonce_field( 'wc_meta_box_nonce', 'wc_services_features_sub' );
		settings_errors();
		echo '<table class="form-table" style="table-layout: fixed; width: 100%;">';
		echo '<tr>';
		
		// Left column cell
		echo '<td style="width: 50%; vertical-align: top; padding-right: 15px;">';
		
		$value = get_post_meta( $post->ID, '_service_code', true );
		echo '<p><label for="service_code" style="display: block; margin-bottom: 5px; font-weight: 600;">'.esc_html__("Service Code", "computer-repair-shop").'</label>';
		echo '<input type="text" class="regular-text" name="service_code" id="service_code" value="'.esc_attr($value). '" style="width: 100%;" /></p>';
		
		$value = get_post_meta( $post->ID, '_time_required', true );
		echo '<p><label for="time_required" style="display: block; margin-bottom: 5px; font-weight: 600;">'.esc_html__("Time Required", "computer-repair-shop").'</label>';
		echo '<input type="text" class="regular-text" name="time_required" id="time_required" value="'.esc_attr($value). '" style="width: 100%;" /></p>';
		
		$value = get_post_meta( $post->ID, '_cost', true );
		$value = ( empty( $value ) ) ? '0.00' : $value;
		echo '<p><label for="cost" style="display: block; margin-bottom: 5px; font-weight: 600;">'.esc_html__("Cost", "computer-repair-shop").'</label>';
		echo '<input type="number" class="regular-text" name="cost" step="any" value="'.esc_attr($value). '" style="width: 100%;" />';
		echo '<br><span class="description">'. esc_html__( 'To set price by device type, manufacture or device move to section.', 'computer-repair-shop' ) . '<a href="#wcrb_service_price_device">' . esc_html__( 'Set Prices By Devices', 'computer-repair-shop' ) . '</a>' .'</span></p>';
		
		echo '</td>';
		
		// Right column cell
		echo '<td style="width: 50%; vertical-align: top; padding-left: 15px;">';
		
		//wc_use_tax
		$wc_use_taxes 		= get_option("wc_use_taxes");
		$wc_primary_tax		= get_option("wc_primary_tax");

		if($wc_use_taxes == "on"):
			$value = get_post_meta( $post->ID, '_wc_use_tax', true );
			if(empty($value)) {
				$value = $wc_primary_tax;
			}
			echo '<p><label for="wc_use_tax" style="display: block; margin-bottom: 5px; font-weight: 600;">'.esc_html__("Select Service Tax", "computer-repair-shop").'</label>';
			echo '<select class="regular-text form-control" name="wc_use_tax" id="wc_use_tax" style="width: 100%;">';
			echo '<option value="">'.esc_html__("Select tax for service", "computer-repair-shop").'</option>';
			$allowed_html 	  = wc_return_allowed_tags();
			$optionsGenerated = wc_generate_tax_options( $value) ;
			echo wp_kses( $optionsGenerated, $allowed_html );
			echo "</select></p>";
		endif;

		$value = get_post_meta( $post->ID, '_warranty', true );
		echo '<p><label for="warranty" style="display: block; margin-bottom: 5px; font-weight: 600;">'.esc_html__("Warranty", "computer-repair-shop").'</label>';
		echo '<input type="text" class="regular-text" name="warranty" id="warranty" value="'.esc_attr($value). '" style="width: 100%;" /></p>';

		// Checkbox fields
		if(get_post_meta( $post->ID, '_pick_deliver', true ) == "on") { 
			$mystring_pick = 'checked';
		} else { 
			$mystring_pick = '';
		}
		
		$wc_offer_pick_deli = get_option('wc_offer_pick_deli');
		if($wc_offer_pick_deli == "on"){
			echo '<p><label for="pick_deliver" style="display: flex; align-items: center; gap: 8px; font-weight: normal;">';
			echo '<input type="checkbox" name="pick_deliver" id="pick_deliver" value="on" ' . esc_attr( $mystring_pick ) . ' />';
			echo esc_html__("Pick Up & Delivery Available", "computer-repair-shop");
			echo '</label></p>';
		}
		
		if(get_post_meta( $post->ID, '_laptop_rental', true ) == "on") { 
			$mystring_rent = 'checked';
		} else { 
			$mystring_rent = '';
		}
		$wc_offer_laptop = get_option('wc_offer_laptop');
		if($wc_offer_laptop == "on"){
			echo '<p><label for="laptop_rental" style="display: flex; align-items: center; gap: 8px; font-weight: normal;">';
			echo '<input type="checkbox" name="laptop_rental" id="laptop_rental" value="on" ' . esc_attr( $mystring_rent ) . ' />';
			echo esc_html__("Laptop Rental Availability", "computer-repair-shop");
			echo '</label></p>';
		}
		
		echo '</td>';
		echo '</tr>';
		echo '</table>';
	}
endif;

/**
 * Save infor.
 *
 * @param int $post_id The ID of the post being saved.
 */
if ( ! function_exists( 'wc_services_features_save_box' ) ) :
	function wc_services_features_save_box( $post_id ) {
		// Verify that the nonce is valid.
		if (!isset( $_POST['wc_services_features_sub']) || ! wp_verify_nonce( $_POST['wc_services_features_sub'], 'wc_meta_box_nonce' )) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
			return;
		}

		// Check the user's permissions.
		if ( isset( $_POST['post_type'] )) {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
		}

		//Form PRocessing
		$submission_values = array(
							"service_code",
							"time_required",
							"cost",
							"warranty",
							"pick_deliver",
							"laptop_rental",
							"wc_use_tax"
							);

		foreach($submission_values as $submit_value) {
			$my_value = ( isset( $_POST[$submit_value] ) ) ? sanitize_text_field( $_POST[$submit_value] ) : '';
			update_post_meta($post_id, '_'.$submit_value, $my_value);
		}
	}
	add_action( 'save_post', 'wc_services_features_save_box' );
endif;

//Add filter to show Meta Data in front end of post!
if ( ! function_exists( 'wc_front_services_filter' ) ) :
	add_filter('the_content', 'wc_front_services_filter', 0);
	function wc_front_services_filter($content) {
		
		if ( is_singular('rep_services') ) {
			
			global $post;
			$wc_offer_pick_deli = get_option('wc_offer_pick_deli');
			$wc_offer_laptop 	= get_option('wc_offer_laptop');
			$pick_deliver_charg = get_option('wc_pick_delivery_charges'); //Getting charges we set in other function.

			$content = $content;
			$content .= '<div class="grid-container grid-x grid-margin-x">';
			$content .= '<h2 class="small-12 cell">'.esc_html__("Service Details", "computer-repair-shop").'</h2>';

			if ( $wc_offer_pick_deli == "on" ) {
				if ( get_post_meta( $post->ID, '_pick_deliver', true ) == "on" ) {
					$content .= '<div class="large-8 medium-8 small-8 cell"><strong>'.esc_html__("Pickup and delivery", "computer-repair-shop").'</strong></div>';
					$content .= '<div class="large-4 medium-4 small-4 cell">'.esc_html__("Yes", "computer-repair-shop").'</div>';
					$content .= "<hr  class='rp-hr-line' />";

					if ( ! empty( $pick_deliver_charg ) ) :
						$content .= '<div class="large-8 medium-8 small-8 cell"><strong>'.esc_html__("Pick and delivery charges", "computer-repair-shop").'</strong></div>';
						$content .= '<div class="large-4 medium-4 small-4 cell">'. wc_cr_currency_format( $pick_deliver_charg, TRUE ) .'</div>';
						$content .= "<hr class='rp-hr-line' />";
					endif;
				}
			}

			//Getting the value of required fields
			$wc_one_week	= get_option('wc_one_week');
			$wc_one_day 	= get_option('wc_one_day');
			$wc_cost 		= get_post_meta( $post->ID, '_cost', true );
			$wc_time 		= get_post_meta( $post->ID, '_time_required', true );
			$wc_servicecode	= get_post_meta( $post->ID, '_service_code', true );
			$wc_warranty	= get_post_meta( $post->ID, '_warranty', true );
				
			if($wc_offer_laptop == "on"){
			if(get_post_meta( $post->ID, '_laptop_rental', true ) == "on") { 
			
				$content .= '<div class="large-8 medium-8 small-8 cell"><strong>'.esc_html__("Laptop Rental Service", "computer-repair-shop").'</strong></div>';
				$content .= '<div class="large-4 medium-4 small-4 cell">'.esc_html__("Yes", "computer-repair-shop").'</div>';

				if ( ! empty( $wc_one_day ) ) : 
					$content .= '<div class="large-8 medium-8 small-8 cell"><strong>'.esc_html__("For One Day", "computer-repair-shop").'</strong></div>';
					$content .= '<div class="large-4 medium-4 small-4 cell">' . wc_cr_currency_format( $wc_one_day, TRUE ) . '</div>';
				endif;

				if ( ! empty( $wc_one_week ) ) :
					$content .= '<div class="large-8 medium-8 small-8 cell"><strong>'.esc_html__("For one week", "computer-repair-shop").'</strong></div>';
					$content .= '<div class="large-4 medium-4 small-4 cell">' . wc_cr_currency_format( $wc_one_week, TRUE ) . '</div>';
				endif;
				$content .= "<hr class='rp-hr-line' />";
			}
			}
				if ( ! empty( $wc_cost ) ) :
					$content .= '<div class="large-8 medium-8 small-8 cell"><strong>'.esc_html__("Service Price", "computer-repair-shop").'</strong></div>';
					$content .= '<div class="large-4 medium-4 small-4 cell">' . wc_cr_currency_format( $wc_cost, TRUE ) . '</div>';
					$content .= "<hr class='rp-hr-line' />";
				endif;

				if ( ! empty( $wc_time ) ) :
					$content .= '<div class="large-8 medium-8 small-8 cell"><strong>'.esc_html__("Time Required", "computer-repair-shop").'</strong></div>';
					$content .= '<div class="large-4 medium-4 small-4 cell">'.$wc_time.'</div>';
					$content .= "<hr class='rp-hr-line' />";
				endif;

				if ( ! empty( $wc_servicecode ) ) :
					$content .= '<div class="large-8 medium-8 small-8 cell"><strong>'.esc_html__("Service Code", "computer-repair-shop").'</strong></div>';
					$content .= '<div class="large-4 medium-4 small-4 cell">'.$wc_servicecode.'</div>';
					$content .= "<hr class='rp-hr-line' />";
				endif;

				$custom_taxonomies = get_post_taxonomies( $post );

				if ( $custom_taxonomies ) :
					$content .= '<div class="large-8 medium-8 small-8 cell"><strong>'.esc_html__("Service Type", "computer-repair-shop").'</strong></div>';
					$content .= '<div class="large-4 medium-4 small-4 cell">'.custom_taxonomies_terms_links($post->ID, $post->post_type).'</div>';
					$content .= "<hr class='rp-hr-line' />";
				endif;

				if ( ! empty( $wc_warranty ) ) :
					$content .= '<div class="large-8 medium-8 small-8 cell"><strong>'.esc_html__("Warranty", "computer-repair-shop").'</strong></div>';
					$content .= '<div class="large-4 medium-4 small-4 cell">'.$wc_warranty.'</div>';
					$content .= "<hr class='rp-hr-line' />";
				endif;
				$content .= '</div><!--row ends here.-->'; 
		}
		return $content;
	}
endif;


/*
*Add meta data to table fields post list.. 
*/
if ( ! function_exists( 'wc_table_list_services_type_columns' ) ) :
    add_filter('manage_edit-rep_services_columns', 'wc_table_list_services_type_columns') ;
    function wc_table_list_services_type_columns( $columns ) {
        // Create new columns array while preserving existing ones (including taxonomies)
        $new_columns = array();
        
        // Preserve the checkbox
        $new_columns['cb'] = $columns['cb'];
		unset( $columns['date'] );
        // Preserve the title
        $new_columns['title'] = esc_html__('Service Name', "computer-repair-shop");

        // Add your custom columns after title
        $new_columns['service_code'] = esc_html__('Service Code', "computer-repair-shop");
        $new_columns['time_required'] = esc_html__('Time Required', "computer-repair-shop");
        $new_columns['cost'] = esc_html__('Service Cost', "computer-repair-shop");
        $new_columns['warranty'] = esc_html__('Warranty', "computer-repair-shop");
        
        // Add all remaining columns (including taxonomy columns and date)
        foreach ($columns as $key => $value) {
            if (!isset($new_columns[$key]) && $key !== 'cb' && $key !== 'title') {
                $new_columns[$key] = $value;
            }
        }
        
        return $new_columns;
    }
endif;

if ( ! function_exists( 'wc_table_service_list_meta_data' ) ) :
	add_action( 'manage_rep_services_posts_custom_column', 'wc_table_service_list_meta_data', 10, 2 );
	function wc_table_service_list_meta_data($column, $post_id) {
		global $post;
		
		switch( $column ) {
			case 'service_code' :
				$stock_code = get_post_meta( $post_id, '_service_code', true );
				echo esc_html( $stock_code );
				break;
				
			case 'time_required' :
				$capacity = get_post_meta( $post_id, '_time_required', true );
				echo esc_html( $capacity );
				break;
				
			case 'cost' :
				$price = get_post_meta( $post_id, '_cost', true );
				$thePrice = wc_cr_currency_format( $price, TRUE );
				echo esc_html( $thePrice );
				break;	
			
			case 'warranty' :
				$warranty = get_post_meta( $post_id, '_warranty', true );
				echo esc_html( $warranty );
				break;
					
			default :
				break;
		}
	}
endif;


if ( ! function_exists( 'wc_extend_services_admin_search' ) ) :
	function wc_extend_services_admin_search( $query ) {
		// Extend search for document post type
		$_post_type = 'rep_services';
		// Custom fields to search for
		$custom_fields = array(
			"_service_code",
			"_time_required"
		);

		if( ! is_admin() )
			return;

		if ( ! isset( $query->query_vars['post_type'] ) ) {
			return;
		}

		if ( $query->query_vars['post_type'] != $_post_type )  {
			return;
		}
		$search_term = $query->query_vars['s'];

		// Set to empty, otherwise it won't find anything
		$query->query_vars['s'] = '';

		$query->set('_meta_or_title', $search_term);

		if ( $search_term != '' ) {
			$meta_query = array( 'relation' => 'OR' );

			foreach( $custom_fields as $custom_field ) {
				array_push( $meta_query, array(
					'key' 		=> $custom_field,
					'value' 	=> $search_term,
					'compare' 	=> 'LIKE'
				));
			}
			$query->set( 'meta_query', $meta_query );
		};
	}
	add_action( 'pre_get_posts', 'wc_extend_services_admin_search', 6, 2);

	add_action( 'pre_get_posts', function( $q )
	{
		if( $title = $q->get( '_meta_or_title' ) )
		{
			add_filter( 'get_meta_sql', function( $sql ) use ( $title )
			{
				global $wpdb;

				// Only run once:
				static $nr = 0; 
				if( 0 != $nr++ ) return $sql;

				// Modified WHERE
				$sql['where'] = sprintf(
					" AND ( %s OR %s ) ",
					$wpdb->prepare( "{$wpdb->posts}.post_title like '%%%s%%'", $title),
					mb_substr( $sql['where'], 5, mb_strlen( $sql['where'] ) )
				);

				return $sql;
			}, 12, 1);
		}
	}, 12, 1);
endif;

// Make the Service Type column sortable
if ( ! function_exists( 'wc_table_list_services_sortable_columns' ) ) :
	add_filter( 'manage_edit-rep_services_sortable_columns', 'wc_table_list_services_sortable_columns' );
	function wc_table_list_services_sortable_columns( $columns ) {
		$columns['service_type'] = 'service_type';
		$columns['service_code'] = 'service_code';
		$columns['time_required'] = 'time_required';
		$columns['cost'] = 'cost';
		$columns['warranty'] = 'warranty';
		return $columns;
	}
endif;

// Handle sorting for the custom columns
if ( ! function_exists( 'wc_table_list_services_sort_custom_columns' ) ) :
	add_action( 'pre_get_posts', 'wc_table_list_services_sort_custom_columns' );
	function wc_table_list_services_sort_custom_columns( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}
		
		$orderby = $query->get( 'orderby' );
		
		// Handle meta field sorting
		if ( $orderby === 'service_code' ) {
			$query->set( 'meta_key', '_service_code' );
			$query->set( 'orderby', 'meta_value' );
		} elseif ( $orderby === 'time_required' ) {
			$query->set( 'meta_key', '_time_required' );
			$query->set( 'orderby', 'meta_value' );
		} elseif ( $orderby === 'cost' ) {
			$query->set( 'meta_key', '_cost' );
			$query->set( 'orderby', 'meta_value_num' );
		} elseif ( $orderby === 'warranty' ) {
			$query->set( 'meta_key', '_warranty' );
			$query->set( 'orderby', 'meta_value' );
		}
	}
endif;

// Advanced sorting for service_type column
if ( ! function_exists( 'wc_table_list_services_sort_taxonomy_columns' ) ) :
	add_filter( 'posts_clauses', 'wc_table_list_services_sort_taxonomy_columns', 10, 2 );
	function wc_table_list_services_sort_taxonomy_columns( $clauses, $query ) {
		global $wpdb;
		
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return $clauses;
		}
		
		$orderby = $query->get( 'orderby' );
		$order = $query->get( 'order' );
		
		// Handle service_type sorting
		if ( $orderby === 'service_type' ) {
			$clauses['join'] .= "
				LEFT OUTER JOIN {$wpdb->term_relationships} AS service_type_rel ON {$wpdb->posts}.ID = service_type_rel.object_id
				LEFT OUTER JOIN {$wpdb->term_taxonomy} AS service_type_tax ON service_type_rel.term_taxonomy_id = service_type_tax.term_taxonomy_id
				LEFT OUTER JOIN {$wpdb->terms} AS service_type_terms ON service_type_tax.term_id = service_type_terms.term_id
			";
			$clauses['where'] .= " AND service_type_tax.taxonomy = 'service_type'";
			$clauses['groupby'] = "{$wpdb->posts}.ID";
			$clauses['orderby'] = "GROUP_CONCAT(service_type_terms.name ORDER BY service_type_terms.name ASC) {$order}";
		}
		
		return $clauses;
	}
endif;

// Add Service Type button to admin header
if ( ! function_exists( 'wc_service_type_admin_button' ) ) :
	add_action( 'admin_head-edit.php', 'wc_service_type_admin_button' );
	function wc_service_type_admin_button() {
		global $current_screen;
		if ( 'rep_services' == $current_screen->post_type ) {
				$service_type_link = 'edit-tags.php?taxonomy=service_type&post_type=rep_services';
			?>
			<script type="text/javascript">
				jQuery(function () {
					var linksHtml = "<a href='<?php echo esc_url( $service_type_link . '&action=edit' ); ?>' class='add-new-h2'><?php echo esc_html__( 'Service Types', 'computer-repair-shop' ); ?></a>";
					
					// Multiple insertion strategies with fallbacks
					if (jQuery('hr.wp-header-end').length) {
						jQuery('hr.wp-header-end').before(linksHtml);
					} else if (jQuery('a.page-title-action').length) {
						jQuery('a.page-title-action').after(linksHtml);
					} else if (jQuery('h1.wp-heading-inline').length) {
						jQuery('h1.wp-heading-inline').after(linksHtml);
					} else {
						// Final fallback - append to the wrap container
						jQuery('.wrap:first').prepend(linksHtml);
					}
				});
			</script>
			<?php
		}
	}
endif;