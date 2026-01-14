<?php
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wc_repair_shop_products_init' ) ) :
	function wc_repair_shop_products_init() {
		$labels = array(
			'add_new_item' 			=> esc_html__('Add new part', 'computer-repair-shop'),
			'singular_name' 		=> esc_html__('Part', 'computer-repair-shop'), 
			'menu_name' 			=> esc_html__('Parts', 'computer-repair-shop'),
			'all_items' 			=> esc_html__('Parts', 'computer-repair-shop'),
			'edit_item' 			=> esc_html__('Edit part', 'computer-repair-shop'),
			'new_item' 				=> esc_html__('New part', 'computer-repair-shop'),
			'view_item' 			=> esc_html__('View part', 'computer-repair-shop'),
			'search_items' 			=> esc_html__('Search part', 'computer-repair-shop'),
			'not_found' 			=> esc_html__('No part found', 'computer-repair-shop'),
			'not_found_in_trash' 	=> esc_html__('No part in trash', 'computer-repair-shop')
		);
		
		$args = array(
			'labels'             	=> $labels,
			'label'					=> esc_html__("Parts", "computer-repair-shop"),
			'description'        	=> esc_html__('Parts section', 'computer-repair-shop'),
			'public'             	=> true, // Changed to false for private
			'publicly_queryable' 	=> true, // Changed to false for private
			'show_ui'            	=> true,  // IMPORTANT: Must be true for admin UI
			'show_in_menu'       	=> false,  // Changed to true to show in admin
			'query_var'          	=> true, // Changed to false for private
			'rewrite'            	=> array('slug' => 'rep_parts'),
			'capability_type'    	=> array('rep_product', 'rep_products'),
			'has_archive'        	=> true, // Changed to false for private
			'menu_icon'			 	=> 'dashicons-clipboard',
			'menu_position'      	=> 30,
			'supports'           	=> array( 'title', 'editor', 'thumbnail' ), 	
			'register_meta_box_cb' 	=> 'wc_parts_features',
			'taxonomies' 			=> array( 'part_type', 'brand_type' ),
			'show_in_nav_menus'     => false, // Added for private post type
			'exclude_from_search'   => true,  // Added for private post type
		);
		register_post_type('rep_products', $args);
		
		// Now register the taxonomies AFTER the post type
		wc_create_parts_type_tax();
		wc_create_parts_tax_brand();
	}
	add_action( 'init', 'wc_repair_shop_products_init', 0 );
endif;

if ( ! function_exists( 'wc_create_parts_type_tax' ) ) :
	function wc_create_parts_type_tax() {
		$labels = array(
			'name'              => esc_html__( 'Part types', 'computer-repair-shop' ),
			'singular_name'     => esc_html__( 'Part type', 'computer-repair-shop' ),
			'search_items'      => esc_html__( 'Search part types', 'computer-repair-shop' ),
			'all_items'         => esc_html__( 'All part types', 'computer-repair-shop' ),
			'parent_item'       => esc_html__( 'Parent part type', 'computer-repair-shop' ),
			'parent_item_colon' => esc_html__( 'Parent part type:', 'computer-repair-shop' ),
			'edit_item'         => esc_html__( 'Edit part type', 'computer-repair-shop' ),
			'update_item'       => esc_html__( 'Update part type', 'computer-repair-shop' ),
			'add_new_item'      => esc_html__( 'Add new part type', 'computer-repair-shop' ),
			'new_item_name'     => esc_html__( 'New part type name', 'computer-repair-shop' ),
			'menu_name'         => esc_html__( 'Part types', 'computer-repair-shop' )
		);
		
		$args = array(
			'labels' => $labels,
			'public' => true, 
			'rewrite' => array( 'slug' => 'part-type' ),
			'show_ui' => true, // MUST be true to show in admin
			'show_in_menu' => false, // Show in admin menu
			'show_in_nav_menus' => false,
			'show_tagcloud' => false,
			'show_in_quick_edit' => true,
			'show_admin_column' => true,
			'hierarchical' => true,
			'capabilities' => array(
								'manage_terms' => 'manage_rep_products',
								'edit_terms'   => 'manage_rep_products',
								'delete_terms' => 'manage_rep_products',
								'assign_terms' => 'edit_rep_products',
							),
		);
		
		register_taxonomy(
			'part_type',
			array( 'rep_products' ),
			$args
		);
	}
endif;

if ( ! function_exists( 'wc_create_parts_tax_brand' ) ) :
	function wc_create_parts_tax_brand() {
		$labels = array(
			'name'              => esc_html__( 'Part brands', 'computer-repair-shop' ),
			'singular_name'     => esc_html__( 'Part brand', 'computer-repair-shop' ),
			'search_items'      => esc_html__( 'Search part brands', 'computer-repair-shop' ),
			'all_items'         => esc_html__( 'All part brands', 'computer-repair-shop' ),
			'parent_item'       => esc_html__( 'Parent part brand', 'computer-repair-shop' ),
			'parent_item_colon' => esc_html__( 'Parent part brand', 'computer-repair-shop' ) . ':',
			'edit_item'         => esc_html__( 'Edit part brand', 'computer-repair-shop' ),
			'update_item'       => esc_html__( 'Update part brand', 'computer-repair-shop' ),
			'add_new_item'      => esc_html__( 'Add new part brand', 'computer-repair-shop' ),
			'new_item_name'     => esc_html__( 'New part brand name', 'computer-repair-shop' ),
			'menu_name'         => esc_html__( 'Part brands', 'computer-repair-shop' )
		);

		$args = array(
			'labels' => $labels,
			'public' => true, 
			'rewrite' => array( 'slug' => 'part-brand' ),
			'show_ui' => true,
			'show_in_menu' => false,
			'show_in_nav_menus' => false,
			'show_tagcloud' => false,
			'show_in_quick_edit' => true,
			'show_admin_column' => true,
			'hierarchical' => true,
			'capabilities' => array(
								'manage_terms' => 'manage_rep_products',
								'edit_terms'   => 'manage_rep_products',
								'delete_terms' => 'manage_rep_products',
								'assign_terms' => 'edit_rep_products',
							),
		);
		
		register_taxonomy(
			'brand_type',
			array( 'rep_products' ),
			$args
		);
	}
endif;

if ( ! function_exists( 'other_parts_link' ) ) :
	add_action( 'admin_head-edit.php','other_parts_link' );
	function other_parts_link() {
		global $current_screen;
		if ( 'rep_products' == $current_screen->post_type ) {
			$part_brands_link 		= 'edit-tags.php?taxonomy=brand_type&post_type=rep_products';
			$part_types_link 		= 'edit-tags.php?taxonomy=part_type&post_type=rep_products';
		?>
			<script type="text/javascript">
				jQuery(function () {
					var linksHtml = "<a id='part_brands_link' href='edit-tags.php?taxonomy=brand_type&#038;post_type=rep_products' class='add-new-h2'><?php echo esc_html__( 'Part Brands', 'computer-repair-shop' ); ?></a>" +
								"<a id='part_types_link' href='edit-tags.php?taxonomy=part_type&#038;post_type=rep_products' class='add-new-h2'><?php echo esc_html__( 'Part Types', 'computer-repair-shop' ); ?></a>";
					
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

if ( ! function_exists( 'wc_parts_features' ) ) :
	function wc_parts_features() { 
		$screens = array( 'rep_products' );

		foreach ( $screens as $screen ) {
			add_meta_box(
				'wcrb_parts_options_mb',
				esc_html__( 'Product details', 'computer-repair-shop' ),
				'wc_parts_features_callback',
				$screen
			);
		}
	} //Parts features post.
	add_action( 'add_meta_boxes', 'wc_parts_features');
endif;//wc_parts_features

if ( ! function_exists( 'wc_parts_features_callback' ) ) :
	function wc_parts_features_callback( $post ) {
		wp_nonce_field( 'wc_meta_box_nonce', 'wc_parts_features_sub' );
		settings_errors();

		$output = '';
		$sub_parts = get_post_meta( $post->ID, '_sub_parts_arr', true );

		$WCRB_SUPPORT_DOCS = WCRB_SUPPORT_DOCS::getInstance();
		add_action( 'admin_footer', array( $WCRB_SUPPORT_DOCS, 'return_helpful_link_parts' ) ) ;

		$output .= '<ul class="accordion" data-accordion data-allow-all-closed="true" id="parentofpartvariations">';

		$WCRB_DEVICE_PARTS = WCRB_DEVICE_PARTS::getInstance();
		$output .= $WCRB_DEVICE_PARTS->the_part_metabox( $post->ID, 'default' );

		if ( ! empty( $sub_parts ) && is_array( $sub_parts ) ) {
			foreach ( $sub_parts as $key => $sub_part ) {
				if ( $sub_part != 'default' ) {
					$output .= $WCRB_DEVICE_PARTS->the_part_metabox( $post->ID, $sub_part );
				}
			}
		}
		
		$output .= '</ul>';

		$output .= '<div class="msgabovevar"></div><a href="#" class="button button-primary alignright" id="addnewpartvariation" data-job-id="'. esc_html( $post->ID ) .'">' . esc_html__( 'Add another part variation', 'computer-repair-shop' ) . '</a>';
		$output .= '<div class="clearfix"></div>';

		$allowedHTML = wc_return_allowed_tags(); 
		echo wp_kses( $output, $allowedHTML );
	}
endif;//wc_parts_features_callback

/**
 * Save infor.
 *
 * @param int $post_id The ID of the post being saved.
 */
if ( ! function_exists( 'wc_parts_features_save_box' ) ) :
	function wc_parts_features_save_box( $post_id ) {
		// Verify that the nonce is valid.
		if (!isset( $_POST['wc_parts_features_sub']) || ! wp_verify_nonce( $_POST['wc_parts_features_sub'], 'wc_meta_box_nonce' )) {
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
							"manufacturing_code",
							"part_title",
							"stock_code",
							"core_features",
							"capacity",
							"price",
							"warranty",
							"installation_charges",
							"installation_message",
							"wc_use_tax"
							);

		foreach( $submission_values as $submit_value ) {
			$my_value = ( isset( $_POST[$submit_value] ) ) ? sanitize_text_field( $_POST[$submit_value] ) : '';
			update_post_meta( $post_id, '_'.$submit_value, $my_value );
		}

		$sub_parts = get_post_meta( $post_id, '_sub_parts_arr', true );
		if ( ! empty( $sub_parts ) && is_array( $sub_parts ) ) {
			foreach ( $sub_parts as $key => $sub_part ) {
				if ( $sub_part != 'default' ) {
					foreach( $submission_values as $submit_value ) {
						$my_value = ( isset( $_POST[$sub_part. '_' . $submit_value] ) ) ? sanitize_text_field( $_POST[$sub_part. '_' . $submit_value] ) : '';
						update_post_meta( $post_id, $sub_part. '_' . $submit_value, $my_value );
					} // End foreach
				} //End if.
			} // End foreach
		} // End if.
	}
	add_action( 'save_post', 'wc_parts_features_save_box' );
endif;//wc_parts_features_save_box

/*
*Add meta data to table fields post list.. 
*/
if ( ! function_exists( 'wc_table_list_products_type_columns' ) ) :
    add_filter('manage_edit-rep_products_columns', 'wc_table_list_products_type_columns') ;
    function wc_table_list_products_type_columns( $columns ) {
        // Reorder columns while keeping all existing ones (including taxonomies)
        $new_columns = array();
        
        // Preserve the checkbox
        $new_columns['cb'] = $columns['cb'];
        unset( $columns['date'] );
        // Preserve the title
        $new_columns['title'] = $columns['title'];
        
        // Add your custom columns after title
        $new_columns['stock_code'] = esc_html__('Stock Code', "computer-repair-shop");
        $new_columns['capacity'] = esc_html__('Capacity', "computer-repair-shop");
        $new_columns['price'] = esc_html__('Price', "computer-repair-shop");
        $new_columns['warranty'] = esc_html__('Warranty', "computer-repair-shop");
        
        // Add all remaining columns (including taxonomy columns)
        foreach ($columns as $key => $value) {
            if (!isset($new_columns[$key]) && $key !== 'cb' && $key !== 'title') {
                $new_columns[$key] = $value;
            }
        }
        
        return $new_columns;
    }
endif;

if ( ! function_exists( 'wc_table_list_meta_data' ) ) :
	add_action( 'manage_rep_products_posts_custom_column', 'wc_table_list_meta_data', 10, 2 );
	function wc_table_list_meta_data($column, $post_id) {
		global $post;
		
		switch( $column ) {
			case 'stock_code' :
				$stock_code = get_post_meta($post_id, '_stock_code', true );
				echo esc_html($stock_code);
			break;
			
			case 'capacity' :
				$capacity = get_post_meta($post_id, '_capacity', true);
				echo esc_html($capacity);
			break;
			
			case 'price' :
				$price = get_post_meta($post_id, '_price', true);
				$thePrice = wc_cr_currency_format( $price, TRUE );
				echo esc_html($thePrice);
			break;	
			
			case 'warranty' :
				$warranty = get_post_meta($post_id, '_warranty', true);
				echo esc_html($warranty);
			break;
			
			default :
				break;
		}
	}
endif;//wc_table_list_meta_data

// Make the Brand and Type columns sortable
if ( ! function_exists( 'wc_table_list_sortable_columns' ) ) :
	add_filter( 'manage_edit-rep_products_sortable_columns', 'wc_table_list_sortable_columns' );
	function wc_table_list_sortable_columns( $columns ) {
		$columns['stock_code'] = 'stock_code';
		$columns['capacity'] = 'capacity';
		$columns['price'] = 'price';
		$columns['warranty'] = 'warranty';
		return $columns;
	}
endif;//wc_table_list_sortable_columns

// Handle sorting for the custom columns
if ( ! function_exists( 'wc_table_list_sort_custom_columns' ) ) :
	add_action( 'pre_get_posts', 'wc_table_list_sort_custom_columns' );
	function wc_table_list_sort_custom_columns( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}
		
		$orderby = $query->get( 'orderby' );
		
		// Handle meta field sorting
		if ( $orderby === 'stock_code' ) {
			$query->set( 'meta_key', '_stock_code' );
			$query->set( 'orderby', 'meta_value' );
		} elseif ( $orderby === 'capacity' ) {
			$query->set( 'meta_key', '_capacity' );
			$query->set( 'orderby', 'meta_value' );
		} elseif ( $orderby === 'price' ) {
			$query->set( 'meta_key', '_price' );
			$query->set( 'orderby', 'meta_value_num' ); // Use meta_value_num for numeric values
		} elseif ( $orderby === 'warranty' ) {
			$query->set( 'meta_key', '_warranty' );
			$query->set( 'orderby', 'meta_value' );
		}
		
		// For taxonomy columns (brand and type), we need to use a different approach
		// This is more complex and might require a JOIN with the terms table
	}
endif;//wc_table_list_sort_custom_columns

// Advanced sorting for taxonomy columns (brand and type)
if ( ! function_exists( 'wc_table_list_sort_taxonomy_columns' ) ) :
	add_filter( 'posts_clauses', 'wc_table_list_sort_taxonomy_columns', 10, 2 );
	function wc_table_list_sort_taxonomy_columns( $clauses, $query ) {
		global $wpdb;
		
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return $clauses;
		}
		
		$orderby = $query->get( 'orderby' );
		$order = $query->get( 'order' );
		
		// Handle brand sorting
		if ( $orderby === 'brand' ) {
			$clauses['join'] .= "
				LEFT OUTER JOIN {$wpdb->term_relationships} AS brand_rel ON {$wpdb->posts}.ID = brand_rel.object_id
				LEFT OUTER JOIN {$wpdb->term_taxonomy} AS brand_tax ON brand_rel.term_taxonomy_id = brand_tax.term_taxonomy_id
				LEFT OUTER JOIN {$wpdb->terms} AS brand_terms ON brand_tax.term_id = brand_terms.term_id
			";
			$clauses['where'] .= " AND brand_tax.taxonomy = 'brand_type'";
			$clauses['groupby'] = "{$wpdb->posts}.ID";
			$clauses['orderby'] = "GROUP_CONCAT(brand_terms.name ORDER BY brand_terms.name ASC) {$order}";
		}
		
		// Handle type sorting
		if ( $orderby === 'type' ) {
			$clauses['join'] .= "
				LEFT OUTER JOIN {$wpdb->term_relationships} AS type_rel ON {$wpdb->posts}.ID = type_rel.object_id
				LEFT OUTER JOIN {$wpdb->term_taxonomy} AS type_tax ON type_rel.term_taxonomy_id = type_tax.term_taxonomy_id
				LEFT OUTER JOIN {$wpdb->terms} AS type_terms ON type_tax.term_id = type_terms.term_id
			";
			$clauses['where'] .= " AND type_tax.taxonomy = 'part_type'";
			$clauses['groupby'] = "{$wpdb->posts}.ID";
			$clauses['orderby'] = "GROUP_CONCAT(type_terms.name ORDER BY type_terms.name ASC) {$order}";
		}
		
		return $clauses;
	}
endif;//wc_table_list_sort_taxonomy_columns

if ( ! function_exists( "wc_extend_products_admin_search" ) ) :
	function wc_extend_products_admin_search( $query ) {
		// Extend search for document post type
		$_post_type = 'rep_products';
		// Custom fields to search for
		$custom_fields = array(
			"_stock_code",
			"_capacity"
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
					'key' => $custom_field,
					'value' => $search_term,
					'compare' => 'LIKE'
				));
			}
			$query->set( 'meta_query', $meta_query );
		};
	}
	add_action( 'pre_get_posts', 'wc_extend_products_admin_search', 6, 2);

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

//Add filter to show Meta Data in front end of post!
add_filter('the_content', 'wc_front_products_filter', 0);

function wc_front_products_filter($content) {
	if ( is_singular('rep_products') ) {
		global $post;
		
		$manufacturing_code 	= get_post_meta( $post->ID, '_manufacturing_code', true );
		$stock_code 			= get_post_meta( $post->ID, '_stock_code', true );
		$core_features 			= get_post_meta( $post->ID, '_core_features', true );
		$capacity 				= get_post_meta( $post->ID, '_capacity', true );
		$price 					= get_post_meta( $post->ID, '_price', true );
		$warranty 				= get_post_meta( $post->ID, '_warranty', true );
		$installation_charges 	= get_post_meta( $post->ID, '_installation_charges', true );
		$installation_message 	= get_post_meta( $post->ID, '_installation_message', true );
		
		$content = '<strong>'.esc_html__("Product Description", "computer-repair-shop").':</strong> '.$content;
		
		$content .= '<div class="grid-container grid-x grid-margin-x grid-margin-y">';
		$content .= '<h2 class="small-12 cell">'.esc_html__("Product details", "computer-repair-shop").'</h2>';
		$content .= '<div class="large-4 medium-4 small-4 cell"><strong>'.esc_html__("Manufacturing code", "computer-repair-shop").'</strong></div>';
		$content .= '<div class="large-8 medium-8 small-8 cell">'.$manufacturing_code.'</div>';
		$content .= "<hr class='rp-hr-line' />";
		
		$content .= '<div class="large-4 medium-4 small-4 cell"><strong>'.esc_html__("Stock code", "computer-repair-shop").'</strong></div>';
		$content .= '<div class="large-8 medium-8 small-8 cell">'.$stock_code.'</div>';
		$content .= "<hr class='rp-hr-line' />";
		
		$content .= '<div class="large-4 medium-4 small-4 cell"><strong>'.esc_html__("Core features", "computer-repair-shop").'</strong></div>';
		$content .= '<div class="large-8 medium-8 small-8 cell">'.$core_features.'</div>';
		$content .= "<hr class='rp-hr-line' />";
		
		$content .= '<div class="large-4 medium-4 small-4 cell"><strong>'.esc_html__("Capacity", "computer-repair-shop").'</strong></div>';
		$content .= '<div class="large-8 medium-8 small-8 cell">'.$capacity.'</div>';
		$content .= "<hr class='rp-hr-line' />";
		
		$content .= '<div class="large-4 medium-4 small-4 cell"><strong>'.esc_html__("Price", "computer-repair-shop").'</strong></div>';
		$content .= '<div class="large-8 medium-8 small-8 cell">' . wc_cr_currency_format( $price, TRUE ) . '</div>';
		$content .= "<hr class='rp-hr-line' />";
		
		if($installation_charges != '') { 
		$content .= '<div class="large-4 medium-4 small-4 cell"><strong>'.esc_html__("Installation charges", "computer-repair-shop").'</strong></div>';
		$content .= '<div class="large-8 medium-8 small-8 cell">' . wc_cr_currency_format( $installation_charges, TRUE ) . ' '.$installation_message.'</div>';
		$content .= "<hr class='rp-hr-line' />";
		}
		
		$content .= '<div class="large-4 medium-4 small-4 cell"><strong>'.esc_html__("Warranty", "computer-repair-shop").'</strong></div>';
		$content .= '<div class="large-8 medium-8 small-8 cell">'.$warranty.'</div>';
		$content .= "<hr class='rp-hr-line' />";
		
		$content .= '<div class="large-4 medium-4 small-4 cell"><strong>'.esc_html__("Brand", "computer-repair-shop").'</strong></div>';
		$content .= '<div class="large-8 medium-8 small-8 cell">'.custom_taxonomies_terms_links($post->ID, $post->post_type).'</div>';
		$content .= "<hr class='rp-hr-line' />";
		
		$content .= '</div><!--row ends here.-->';
	}
	return $content;
}

if ( ! function_exists( 'wcrb_taxnomy_edit_field' ) ) {
	function wcrb_taxnomy_edit_field( $term ) {
		$t_id = $term->term_id;
		$term_meta = get_option( "taxonomy_$t_id" );
	?>
	<tr class="form-field">
		<th scope="row" valign="top"><label for="term_meta[custom_term_meta]"><?php echo esc_html__( 'Hide in booking form of RepairBuddy:', 'computer-repair-shop' ); ?></label></th>
		<td>
			<input type="hidden" value="0" name="term_meta[wcrb_disable_in_booking]">
			<input type="checkbox" <?php echo ( ! empty( $term_meta['wcrb_disable_in_booking'] ) ? ' checked="checked" ' : '' ); ?> value="yes" name="term_meta[wcrb_disable_in_booking]" />
		</td>
	</tr>
	<?php
	}
	add_action( 'product_cat_edit_form_fields', 'wcrb_taxnomy_edit_field', 10, 2 );
	add_action( 'device_type_edit_form_fields', 'wcrb_taxnomy_edit_field', 10, 2 );
	add_action( 'device_brand_edit_form_fields', 'wcrb_taxnomy_edit_field', 10, 2 );
}

if ( ! function_exists( 'wcrb_taxnomy_save_field' ) ) {
	function wcrb_taxnomy_save_field( $term_id ) {
		if ( isset( $_POST['term_meta'] ) ) {
			$t_id = $term_id;
			$term_meta = get_option( "taxonomy_$t_id" );
			$cat_keys = array_keys( $_POST['term_meta'] );
			foreach ( $cat_keys as $key ) {
				if ( isset ( $_POST['term_meta'][$key] ) ) {
					$term_meta[$key] = $_POST['term_meta'][$key];
				}
			}
			update_option( "taxonomy_$t_id", $term_meta );
		}
	}  
	add_action( 'edited_product_cat', 'wcrb_taxnomy_save_field', 10, 2 );  
	add_action( 'create_product_cat', 'wcrb_taxnomy_save_field', 10, 2 );
	add_action( 'edited_device_type', 'wcrb_taxnomy_save_field', 10, 2 );  
	add_action( 'create_device_type', 'wcrb_taxnomy_save_field', 10, 2 );
	add_action( 'edited_device_brand', 'wcrb_taxnomy_save_field', 10, 2 );  
	add_action( 'create_device_brand', 'wcrb_taxnomy_save_field', 10, 2 );
} //wcrb_taxnomy_edit_field