<?php
defined( 'ABSPATH' ) || exit;

	function wc_repair_shop_devices_init() {
		$wc_device_label        = ( empty( get_option( 'wc_device_label' ) ) ) ? esc_html__( 'Device', 'computer-repair-shop' ) : get_option( 'wc_device_label' );
		$wc_device_label_plural = ( empty( get_option( 'wc_device_label_plural' ) ) ) ? esc_html__( 'Devices', 'computer-repair-shop' ) : get_option( 'wc_device_label_plural' );
		$labels = array(
			'add_new_item' 			=> esc_html__('Add New', 'computer-repair-shop') . ' ' . esc_html( $wc_device_label ),
			'add_new' 				=> esc_html__('Add New', 'computer-repair-shop') . ' ' . esc_html( $wc_device_label ),
			'singular_name' 		=> esc_html( $wc_device_label ), 
			'menu_name' 			=> esc_html( $wc_device_label_plural ),
			'all_items' 			=> esc_html( $wc_device_label_plural ),
			'edit_item' 			=> esc_html__('Edit', 'computer-repair-shop') . ' ' . esc_html( $wc_device_label ),
			'new_item' 				=> esc_html__('New', 'computer-repair-shop') . ' ' . esc_html( $wc_device_label ),
			'view_item' 			=> esc_html__('View', 'computer-repair-shop') . ' ' . esc_html( $wc_device_label ),
			'search_items' 			=> esc_html__('Search', 'computer-repair-shop') . ' ' . esc_html( $wc_device_label ),
			'not_found' 			=> esc_html__('Nothing found', 'computer-repair-shop'),
			'not_found_in_trash' 	=> esc_html__('Nothing in trash', 'computer-repair-shop')
		);
		
		$args = array(
			'labels'                => $labels,
			'label'                 => esc_html( $wc_device_label_plural ),
			'description'           => esc_html( $wc_device_label_plural ) . ' ' . esc_html__('Section', 'computer-repair-shop'),
			'public'                => true,
			'publicly_queryable'    => true,
			'show_ui'               => true,
			'show_in_menu'          => false,
			'query_var'             => true,
			'rewrite'               => array('slug' => 'device'),
			'capability_type'       => array('rep_device', 'rep_devices'),
			'has_archive'           => true,
			'menu_icon'             => 'dashicons-clipboard',
			'menu_position'         => 30,
			'supports'              => array( 'title', 'editor', 'thumbnail', 'page-attributes' ), // Added page-attributes support
			'register_meta_box_cb'  => 'wcrb_devices_metabox',
			'taxonomies'            => array( 'device_type', 'device_brand' ),
			'hierarchical'          => true // Added this line to make it hierarchical
		);
		register_post_type( 'rep_devices', $args );
	}
	add_action( 'init', 'wc_repair_shop_devices_init');

// Add meta box for device variations
function wcrb_add_device_variations_meta_box() {
    add_meta_box(
        'wcrb_device_variations',
        esc_html__('Device Variations', 'computer-repair-shop'),
        'wcrb_device_variations_meta_box_callback',
        'rep_devices',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'wcrb_add_device_variations_meta_box');

// Meta box callback function
function wcrb_device_variations_meta_box_callback($post) {
    // Security nonce
    wp_nonce_field('wcrb_save_device_variations', 'wcrb_device_variations_nonce');
    
    // Get existing variations
    $variations = get_posts(array(
        'post_type' => 'rep_devices',
        'post_parent' => $post->ID,
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ));
    
    ?>
    <div class="wcrb-variations-manager">
        <div class="wcrb-variations-input">
            <p>
                <label for="wcrb_variations_list"><strong><?php esc_html_e('Add Variations', 'computer-repair-shop'); ?></strong></label>
                <textarea id="wcrb_variations_list" name="wcrb_variations_list" rows="4" style="width:100%;" placeholder="<?php esc_attr_e('Black, 64GB, Silver, 128GB, etc.', 'computer-repair-shop'); ?>"></textarea>
                <span class="description"><?php esc_html_e('Enter variations separated by commas. Each variation will be created as a child device.', 'computer-repair-shop'); ?></span>
            </p>
            <p>
                <button type="button" id="wcrb_add_variations" class="button button-primary"><?php esc_html_e('Create Variations', 'computer-repair-shop'); ?></button>
            </p>
        </div>
        
        <?php if ($variations) : ?>
        <div class="wcrb-existing-variations">
            <h3><?php esc_html_e('Existing Variations', 'computer-repair-shop'); ?></h3>
            <ul>
            <?php foreach ($variations as $variation) : ?>
                <li>
                    <a href="<?php echo get_edit_post_link($variation->ID); ?>" target="_blank">
                        <?php echo esc_html($variation->post_title); ?>
                    </a>
                    <span class="wcrb-remove-variation" data-id="<?php echo $variation->ID; ?>">×</span>
                </li>
            <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
    
    <style>
    .wcrb-variations-manager {
        padding: 10px 0;
    }
    .wcrb-existing-variations ul {
        list-style: none;
        margin: 0;
        padding: 0;
    }
    .wcrb-existing-variations li {
        background: #f6f7f7;
        border: 1px solid #c3c4c7;
        padding: 10px;
        margin: 5px 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .wcrb-remove-variation {
        color: #d63638;
        cursor: pointer;
        font-size: 18px;
        font-weight: bold;
    }
    .wcrb-remove-variation:hover {
        color: #aa2e2e;
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // Add variations
        $('#wcrb_add_variations').click(function(e) {
            e.preventDefault();
            
            var variations = $('#wcrb_variations_list').val().split(',');
            if (variations.length === 0 || (variations.length === 1 && variations[0].trim() === '')) {
                alert('<?php echo esc_js(__('Please enter at least one variation.', 'computer-repair-shop')); ?>');
                return;
            }
            
            // Show loading indicator
            $(this).text('<?php echo esc_js(__('Creating...', 'computer-repair-shop')); ?>').prop('disabled', true);
            
            // Send AJAX request
            $.post(ajaxurl, {
                action: 'wcrb_create_variations',
                post_id: '<?php echo $post->ID; ?>',
                variations: variations,
                security: '<?php echo wp_create_nonce('wcrb_create_variations'); ?>'
            }, function(response) {
                if (response.success) {
                    // Reload the page to see new variations
                    location.reload();
                } else {
                    alert('<?php echo esc_js(__('Error creating variations: ', 'computer-repair-shop')); ?>' + response.data);
                    $('#wcrb_add_variations').text('<?php echo esc_js(__('Create Variations', 'computer-repair-shop')); ?>').prop('disabled', false);
                }
            }).fail(function() {
                alert('<?php echo esc_js(__('An error occurred. Please try again.', 'computer-repair-shop')); ?>');
                $('#wcrb_add_variations').text('<?php echo esc_js(__('Create Variations', 'computer-repair-shop')); ?>').prop('disabled', false);
            });
        });
        
        // Remove variation
        $('.wcrb-remove-variation').click(function() {
            if (!confirm('<?php echo esc_js(__('Are you sure you want to delete this variation?', 'computer-repair-shop')); ?>')) {
                return;
            }
            
            var variationId = $(this).data('id');
            var $listItem = $(this).closest('li');
            
            // Send AJAX request
            $.post(ajaxurl, {
                action: 'wcrb_delete_variation',
                variation_id: variationId,
                security: '<?php echo wp_create_nonce('wcrb_delete_variation'); ?>'
            }, function(response) {
                if (response.success) {
                    $listItem.remove();
                } else {
                    alert('<?php echo esc_js(__('Error deleting variation: ', 'computer-repair-shop')); ?>' + response.data);
                }
            }).fail(function() {
                alert('<?php echo esc_js(__('An error occurred. Please try again.', 'computer-repair-shop')); ?>');
            });
        });
    });
    </script>
    <?php
}

// Handle AJAX request to create variations
function wcrb_create_variations_ajax() {
    check_ajax_referer('wcrb_create_variations', 'security');
    
    if (!current_user_can('edit_posts')) {
        wp_die('Unauthorized');
    }
    
    $post_id = intval($_POST['post_id']);
    $variations = $_POST['variations'];
    
    if (empty($post_id) || empty($variations)) {
        wp_send_json_error(__('Missing required parameters.', 'computer-repair-shop'));
    }
    
    $parent_post = get_post($post_id);
    if (!$parent_post || $parent_post->post_type !== 'rep_devices') {
        wp_send_json_error(__('Invalid parent device.', 'computer-repair-shop'));
    }
    
    // Get parent post taxonomies and featured image
    $taxonomies = array('device_brand', 'device_type');
    $taxonomy_terms = array();
    
    foreach ($taxonomies as $taxonomy) {
        $terms = wp_get_post_terms($post_id, $taxonomy, array('fields' => 'ids'));
        if (!is_wp_error($terms)) {
            $taxonomy_terms[$taxonomy] = $terms;
        }
    }
    
    $featured_image_id = get_post_thumbnail_id($post_id);
    
    $created = 0;
    $errors = array();
    
    foreach ($variations as $variation) {
        $variation = trim($variation);
        if (empty($variation)) continue;
        
        // Check if variation already exists
        $existing = get_posts(array(
            'post_type' => 'rep_devices',
            'post_parent' => $post_id,
            'title' => $parent_post->post_title . ' - ' . $variation,
            'posts_per_page' => 1
        ));
        
        if ($existing) {
            $errors[] = sprintf(__('Variation "%s" already exists.', 'computer-repair-shop'), $variation);
            continue;
        }
        
        // Create new variation post
        $variation_post = array(
            'post_title' => $parent_post->post_title . ' - ' . $variation,
            'post_type' => 'rep_devices',
            'post_parent' => $post_id,
            'post_status' => 'publish',
            'post_content' => $parent_post->post_content
        );
        
        $variation_id = wp_insert_post($variation_post);
        
        if (is_wp_error($variation_id)) {
            $errors[] = sprintf(__('Error creating "%s": %s', 'computer-repair-shop'), $variation, $variation_id->get_error_message());
            continue;
        }
        
        // Copy taxonomies
        foreach ($taxonomy_terms as $taxonomy => $terms) {
            if (!empty($terms)) {
                wp_set_post_terms($variation_id, $terms, $taxonomy);
            }
        }
        
        // Copy featured image
        if ($featured_image_id) {
            set_post_thumbnail($variation_id, $featured_image_id);
        }
        
        $created++;
    }
    
    if ($created > 0) {
        wp_send_json_success(sprintf(_n('Created %d variation.', 'Created %d variations.', $created, 'computer-repair-shop'), $created));
    } else {
        $error_message = !empty($errors) ? implode('<br>', $errors) : __('No variations were created.', 'computer-repair-shop');
        wp_send_json_error($error_message);
    }
}
add_action('wp_ajax_wcrb_create_variations', 'wcrb_create_variations_ajax');

// Handle AJAX request to delete a variation
function wcrb_delete_variation_ajax() {
    check_ajax_referer('wcrb_delete_variation', 'security');
    
    if (!current_user_can('delete_posts')) {
        wp_die('Unauthorized');
    }
    
    $variation_id = intval($_POST['variation_id']);
    
    if (empty($variation_id)) {
        wp_send_json_error(__('Missing variation ID.', 'computer-repair-shop'));
    }
    
    $result = wp_delete_post($variation_id, true);
    
    if ($result) {
        wp_send_json_success(__('Variation deleted successfully.', 'computer-repair-shop'));
    } else {
        wp_send_json_error(__('Error deleting variation.', 'computer-repair-shop'));
    }
}
add_action('wp_ajax_wcrb_delete_variation', 'wcrb_delete_variation_ajax');

	if ( ! function_exists( 'wcrb_devices_metabox' ) ) :
		function wcrb_devices_metabox() { 
			$screens = array( 'rep_devices' );
	
			foreach ( $screens as $screen ) {
				add_meta_box(
					'myplugin_sectionid',
					esc_html__( 'Device Options', 'computer-repair-shop' ),
					'wcrb_devices_metabox_callback',
					$screen,
					'advanced',
					'high'
				);
			}
		} //Parts features post.
		add_action( 'add_meta_boxes', 'wcrb_devices_metabox' );
	endif;
	
	if ( ! function_exists( 'wcrb_devices_metabox_callback' ) ) :
		function wcrb_devices_metabox_callback( $post ) {
			wp_nonce_field( 'wc_meta_box_nonce', 'wc_services_features_sub' );

			settings_errors();

			$wc_device_label = ( empty( get_option( 'wc_device_label' ) ) ) ? esc_html__( 'Device', 'computer-repair-shop' ) : get_option( 'wc_device_label' );

			echo '<table class="form-table">';
			
			$disableinbooking = ( get_post_meta( $post->ID, '_disable_in_booking_form', true ) == 'yes' ) ? 'checked' : '';
	
			echo '<tr><td scope="row" style="width:250px;"><label for="disable_in_booking_form">' . sprintf( esc_html__( 'Disable this %s in booking forms', 'computer-repair-shop' ), esc_html( $wc_device_label )  ) . '</label></td><td>';
			echo '<input type="checkbox" name="disable_in_booking_form" id="disable_in_booking_form" value="yes" ' . esc_attr( $disableinbooking ) . ' />';
			echo '</td></tr>';

			echo '</table>';
		}
	endif;

	if ( ! function_exists( 'wcrb_devices_metabox_save' ) ) :
		function wcrb_devices_metabox_save( $post_id ) {
			global $post;

			// Verify that the nonce is valid.
			if (!isset( $_POST['wc_services_features_sub']) || ! wp_verify_nonce( $_POST['wc_services_features_sub'], 'wc_meta_box_nonce' )) {
				return;
			}
	
			// If this is an autosave, our form has not been submitted, so we don't want to do anything.
			if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
				return;
			}
	
			// bail out if this is not an event item
			if ( 'rep_devices' !== $post->post_type ) {
				return;
			}
			
			// Check the user's permissions.
			if ( isset( $_POST['post_type'] )) {
				if ( ! current_user_can( 'edit_post', $post_id ) ) {
					return;
				}
			}

			//Form PRocessing
			$submission_values = array( "disable_in_booking_form" );
	
			foreach ( $submission_values as $submit_value ) {
				$my_value = ( isset( $_POST[$submit_value] ) ) ? sanitize_text_field( $_POST[$submit_value] ) : '';
				update_post_meta( $post_id, '_'.$submit_value, $my_value );
			}
		}
		add_action( 'save_post', 'wcrb_devices_metabox_save' );
	endif;

	add_filter('manage_rep_devices_posts_columns', 'modify_rep_devices_columns');
	function modify_rep_devices_columns($columns) {
		// Remove Date column
		unset($columns['date']);
		
		// Add new Actions column after Types
		$columns['wc_job_actions'] = esc_html__( 'Actions', 'computer-repair-shop' );
		
		return $columns;
	}

	add_filter('manage_rep_devices_other_posts_columns', 'modify_rep_devices_other_columns');
	function modify_rep_devices_other_columns( $columns ) {
		// Remove Date column
		unset($columns['date']);
		
		return $columns;
	}

	add_action( 'manage_rep_devices_posts_custom_column', 'fill_rep_devices_actions_column', 10, 2 );
	function fill_rep_devices_actions_column( $column, $post_id ) {
		global $WCRB_MANAGE_DEVICES;
		$allowedHTML = wc_return_allowed_tags(); 

		if ( $column === 'wc_job_actions' ) {
			// Edit link
			$_bookpageid = get_option( 'wc_rb_device_booking_page_id' );

			$_pagetab = '';
			if ( empty( $_bookpageid ) || $_bookpageid < 1 ) {
				$wc_booking_on_account_page_status = get_option( 'wc_booking_on_account_page_status' );
				if ( $wc_booking_on_account_page_status != 'on' ) {
					$_bookpageid = get_option( 'wc_rb_my_account_page_id' );
					$_pagetab = 'myaccountbooking';
				}
			}

			$_setbooking = '';
			if ( empty( $_bookpageid ) || $_bookpageid < 1 ) {
				$_setbooking = 'data-alert-msg="' . esc_html__( 'From settings go to pages setup and set the booking page for this function to work. From administrator panel. ', 'computer-repair-shop' ) . '"';
			}
			// Get the booking page permalink
			$booking_url = get_permalink( $_bookpageid );

			// Prepare query parameters
			$query_args = array();

			if ( ! empty( $post_id ) ) {
				// Get device type and brand IDs
				$_typeid  = $WCRB_MANAGE_DEVICES->get_device_term_id_for_post( $post_id, 'device_type');
				$_brandid = $WCRB_MANAGE_DEVICES->get_device_term_id_for_post( $post_id, 'device_brand');
				
				if ( $_typeid ) {
					$query_args['wcrb_selected_type'] = $_typeid;
				}
				if ( $_brandid ) {
					$query_args['wcrb_selected_brand'] = $_brandid;
				}
				$query_args['wcrb_selected_device'] = $post_id;
			}
			if ( $_pagetab == 'myaccountbooking' ) {
				$query_args['book_device'] = 'yes'; // Add the tab parameter if needed
			}

			// Build the final URL with parameters
			$final_url = add_query_arg( $query_args, $booking_url );

			$actions_output = '<div class="actionswrapperjobs">';
			$disable_booking = get_post_meta( $post_id, '_disable_in_booking_form', true );
			if ( $disable_booking != 'yes' ) {
				$actions_output .= '<a '. wp_kses_post( $_setbooking ) .' title="' . esc_html__( 'Book Device', 'computer-repair-shop' ) . '" target="_blank" href="' . esc_url( $final_url ) . '">
										<span class="dashicons dashicons-admin-page"></span>
									</a>';
			}
			$actions_output .= '</div>';

			echo wp_kses( $actions_output, $allowedHTML );
		}
	}

	function wc_repair_shop_devices_other_init() {
		$wc_device_label        = ( empty( get_option( 'wc_device_label' ) ) ) ? esc_html__( 'Other', 'computer-repair-shop' ) . ' ' . esc_html__( 'Device', 'computer-repair-shop' ) : esc_html__( 'Other', 'computer-repair-shop' ) . ' ' . get_option( 'wc_device_label' );
		$wc_device_label_plural = ( empty( get_option( 'wc_device_label_plural' ) ) ) ? esc_html__( 'Other', 'computer-repair-shop' ) . ' ' . esc_html__( 'Devices', 'computer-repair-shop' ) : esc_html__( 'Other', 'computer-repair-shop' ) . ' ' . get_option( 'wc_device_label_plural' );
		$labels = array(
			'add_new' 				=> esc_html__('Add New', 'computer-repair-shop') . ' ' . esc_html( $wc_device_label ),
			'singular_name' 		=> esc_html( $wc_device_label ), 
			'menu_name' 			=> esc_html( $wc_device_label_plural ),
			'all_items' 			=> esc_html( $wc_device_label_plural ),
			'edit_item' 			=> esc_html__('Edit', 'computer-repair-shop') . ' ' . esc_html( $wc_device_label ),
			'new_item' 				=> esc_html__('New', 'computer-repair-shop') . ' ' . esc_html( $wc_device_label ),
			'view_item' 			=> esc_html__('View', 'computer-repair-shop') . ' ' . esc_html( $wc_device_label ),
			'search_items' 			=> esc_html__('Search', 'computer-repair-shop') . ' ' . esc_html( $wc_device_label ),
			'not_found' 			=> esc_html__('Nothing found', 'computer-repair-shop'),
			'not_found_in_trash' 	=> esc_html__('Nothing in trash', 'computer-repair-shop')
		);
		
		$args = array(
			'labels'             	=> $labels,
			'label'					=> esc_html( $wc_device_label_plural ),
			'description'        	=> esc_html( $wc_device_label_plural ) . ' ' . esc_html__('Section', 'computer-repair-shop'),
			'public'             	=> false,
			'publicly_queryable' 	=> false,
			'show_ui'            	=> true,
			'show_in_menu'       	=> false,
			'query_var'          	=> true,
			'rewrite'            	=> array( 'slug' => 'device_other' ),
			'capability_type'    	=> array('rep_device', 'rep_devices'),
			'capabilities' 			=> array( 'create_post' => 'do_not_allow', 'create_posts' => 'do_not_allow' ),
			'map_meta_cap'       	=> true,
			'has_archive'        	=> false,
			'menu_icon'			 	=> 'dashicons-clipboard',
			'menu_position'      	=> 30,
			'supports'           	=> array( 'title' ), 	
			'taxonomies' 			=> array( 'device_type', 'device_brand' )
		);
		register_post_type( 'rep_devices_other', $args);
	}
	add_action( 'init', 'wc_repair_shop_devices_other_init' );
	//registeration of post type ends here.

	add_action( 'init', 'wc_create_device_tax_brand');
	function wc_create_device_tax_brand() {
		$wc_device_brand_label        = ( empty( get_option( 'wc_device_brand_label' ) ) ) ? esc_html__( 'Device Brand', 'computer-repair-shop' ) : get_option( 'wc_device_brand_label' );
		$wc_device_brand_label_plural = ( empty( get_option( 'wc_device_brand_label_plural' ) ) ) ? esc_html__( 'Device Brands', 'computer-repair-shop' ) : get_option( 'wc_device_brand_label_plural' );

		$labels = array(
			'name'              => esc_html( $wc_device_brand_label_plural ),
			'singular_name'     => esc_html( $wc_device_brand_label ),
			'search_items'      => esc_html__('Search', 'computer-repair-shop') . ' ' . esc_html( $wc_device_brand_label_plural ),
			'all_items'         => esc_html__('All', 'computer-repair-shop') . ' ' . esc_html( $wc_device_brand_label_plural ),
			'parent_item'       => esc_html__('Parent', 'computer-repair-shop') . ' ' . esc_html( $wc_device_brand_label ),
			'parent_item_colon' => esc_html__('Parent', 'computer-repair-shop') . ' ' . esc_html( $wc_device_brand_label ),
			'edit_item'         => esc_html__('Edit', 'computer-repair-shop') . ' ' . esc_html( $wc_device_brand_label ),
			'update_item'       => esc_html__('Update', 'computer-repair-shop') . ' ' . esc_html( $wc_device_brand_label ),
			'add_new_item'      => esc_html__('Add New', 'computer-repair-shop') . ' ' . esc_html( $wc_device_brand_label ),
			'new_item_name'     => esc_html__('New Name', 'computer-repair-shop'),
			'menu_name'         => esc_html( $wc_device_brand_label ),
		);
		
		$args = array(
				'label'   			=> esc_html( $wc_device_brand_label ),
				'rewrite' 			=> array( 'slug' => 'device-brand' ),
				'public'  			=> true,
				'labels'  			=> $labels,
				'hierarchical' 		=> true,
				'capabilities' => array(
								'manage_terms' => 'manage_rep_products',
								'edit_terms'   => 'manage_rep_products',
								'delete_terms' => 'manage_rep_products',
								'assign_terms' => 'edit_rep_products',
							),
				'show_admin_column' => true,
		);
		
		register_taxonomy(
			'device_brand',
			array( 'rep_devices', 'rep_devices_other' ),
			$args
		);
	} //Registration of Taxanomy Ends here.

	//Add image field in taxonomy page
	if( ! function_exists( 'wc_rp_add_custom_taxonomy_image' ) ) :
		add_action( 'device_brand_add_form_fields', 'wc_rp_add_custom_taxonomy_image', 10, 2 );
		function wc_rp_add_custom_taxonomy_image ( $taxonomy ) {
		?>
			<div class="form-field term-group">
				<label for="image_id"><?php echo esc_html__( 'Image', 'computer-repair-shop' ); ?></label>
				<input type="hidden" id="image_id" name="image_id" class="custom_media_url" value="">
				<div id="image_wrapper"></div>
				<p>
					<input type="button" class="button button-secondary taxonomy_media_button" id="taxonomy_media_button" name="taxonomy_media_button"
					 value="<?php echo esc_html__( 'Add Image', 'computer-repair-shop' ); ?>">
					<input type="button" class="button button-secondary taxonomy_media_remove" id="taxonomy_media_remove" name="taxonomy_media_remove"
					 value="<?php echo esc_html__( 'Remove Image', 'computer-repair-shop' ); ?>">
				</p>
			</div>
		<?php
		}
	endif;

	//Save the taxonomy image field
	if ( ! function_exists( 'wc_rp_save_custom_taxonomy_image' ) ) :
		add_action( 'created_device_brand', 'wc_rp_save_custom_taxonomy_image', 10, 2 );
		function wc_rp_save_custom_taxonomy_image ( $term_id, $tt_id ) {
			if( isset( $_POST['image_id'] ) && '' !== $_POST['image_id'] ) {
				$image = sanitize_text_field( $_POST['image_id'] );
				add_term_meta( $term_id, 'image_id', $image, true );
			}
		}
	endif;

	//Add the image field in edit form page
	if ( ! function_exists( 'wc_rb_update_custom_taxonomy_image' ) ) :
		add_action( 'device_brand_edit_form_fields', 'wc_rb_update_custom_taxonomy_image', 10, 2 );
		function wc_rb_update_custom_taxonomy_image ( $term, $taxonomy ) { ?>
			<tr class="form-field term-group-wrap">
				<th scope="row">
					<label for="image_id"><?php echo esc_html__( 'Image', 'computer-repair-shop' ); ?></label>
				</th>
				<td>
					<?php $image_id = get_term_meta ( $term -> term_id, 'image_id', true ); ?>
					<input type="hidden" id="image_id" name="image_id" value="<?php echo esc_html($image_id); ?>">

					<div id="image_wrapper">
					<?php 
						if ( $image_id ) {
							$the_rb_tx_img = wp_get_attachment_image ( $image_id, 'thumbnail' );
							echo wp_kses_post( $the_rb_tx_img );
						}
					?>
					</div>
					<p>
						<input type="button" class="button button-secondary taxonomy_media_button" id="taxonomy_media_button" name="taxonomy_media_button"
						 value="<?php echo esc_html__( 'Add Image', 'computer-repair-shop' ); ?>">
						<input type="button" class="button button-secondary taxonomy_media_remove" id="taxonomy_media_remove" name="taxonomy_media_remove"
						 value="<?php echo esc_html__( 'Remove Image', 'computer-repair-shop' ); ?>">
					</p>
				</div></td>
			</tr>
		<?php
		}
	endif;

	//Update the taxonomy image field
	if( ! function_exists( 'wc_rb_updated_custom_taxonomy_image' ) ) :
		add_action( 'edited_device_brand', 'wc_rb_updated_custom_taxonomy_image', 10, 2 );
		function wc_rb_updated_custom_taxonomy_image ( $term_id, $tt_id ) {
			if( isset( $_POST['image_id'] ) && '' !== $_POST['image_id'] ){
				$image = sanitize_text_field( $_POST['image_id'] );
				update_term_meta ( $term_id, 'image_id', $image );
			} else {
				update_term_meta ( $term_id, 'image_id', '' );
			}
		}
	endif;

	//Enqueue the wp_media library
	if ( ! function_exists( 'wc_rb_custom_taxonomy_load_media' ) ) :
		add_action( 'admin_enqueue_scripts', 'wc_rb_custom_taxonomy_load_media' );
		function wc_rb_custom_taxonomy_load_media () {
			if( isset( $_GET['taxonomy'] ) && ( $_GET['taxonomy'] == 'device_brand' || $_GET['taxonomy'] == 'device_type' ) ) {
				wp_enqueue_media();
			}
		}
	endif;

	//Custom script
	if ( ! function_exists( 'wc_rb_add_custom_taxonomy_script' ) ) :
		add_action( 'admin_footer', 'wc_rb_add_custom_taxonomy_script' );
		function wc_rb_add_custom_taxonomy_script() {
			if( ! isset( $_GET['taxonomy'] ) || ( $_GET['taxonomy'] != 'device_brand' && $_GET['taxonomy'] != 'device_type' ) ) {
			return;
			}
			?> <script>jQuery(document).ready( function($) {
					function taxonomy_media_upload(button_class) {
						var custom_media = true,
						original_attachment = wp.media.editor.send.attachment;
						$('body').on('click', button_class, function(e) {
							var button_id = '#'+$(this).attr('id');
							var send_attachment = wp.media.editor.send.attachment;
							var button = $(button_id);
							custom_media = true;
							wp.media.editor.send.attachment = function(props, attachment){
								if ( custom_media ) {
									$('#image_id').val(attachment.id);
									$('#image_wrapper').html('<img class="custom_media_image" src="" style="margin:0;padding:0;max-height:100px;float:none;" />');
									$('#image_wrapper .custom_media_image').attr('src',attachment.url).css('display','block');
								} else {
									return original_attachment.apply( button_id, [props, attachment] );
								}
							}
							wp.media.editor.open(button);
							return false;
						});
					}
					taxonomy_media_upload('.taxonomy_media_button.button'); 
					$('body').on('click','.taxonomy_media_remove',function(){
						$('#image_id').val('');
						$('#image_wrapper').html('<img class="custom_media_image" src="" style="margin:0;padding:0;max-height:100px;float:none;" />');
					});

					$(document).ajaxComplete(function(event, xhr, settings) {
						var queryStringArr = settings.data.split('&');
						if( $.inArray('action=add-tag', queryStringArr) !== -1 ){
							var xml = xhr.responseXML;
							$response = $(xml).find('term_id').text();
							if($response!=""){
								$('#image_wrapper').html('');
							}
						}
					});
				});</script> <?php
		}
	endif;

	//Add new column heading
	if ( ! function_exists( 'wc_rb_display_custom_taxonomy_image_column_heading' ) ) :
		add_filter( 'manage_edit-device_brand_columns', 'wc_rb_display_custom_taxonomy_image_column_heading' ); 
		function wc_rb_display_custom_taxonomy_image_column_heading( $columns ) {
			$columns['category_image'] = esc_html__( 'Logo', 'computer-repair-shop' );
			$columns['category_actions'] = esc_html__( 'Actions', 'computer-repair-shop' );
			return $columns;
		}
	endif;

	//Display new columns values
	if ( ! function_exists( 'wc_rb_display_custom_taxonomy_image_column_value' ) ) :
		add_action( 'manage_device_brand_custom_column', 'wc_rb_display_custom_taxonomy_image_column_value' , 10, 3); 
		function wc_rb_display_custom_taxonomy_image_column_value( $columns, $column, $id ) {
			if ( 'category_image' == $column ) {
				$image_id = esc_html( get_term_meta( $id, 'image_id', true ) );
				$columns = wp_get_attachment_image ( $image_id, array('50', '50') );
			} elseif ( 'category_actions' == $column ) {
				// Edit link
				$_bookpageid = get_option( 'wc_rb_device_booking_page_id' );

				$_pagetab = '';
				if ( empty( $_bookpageid ) || $_bookpageid < 1 ) {
					$wc_booking_on_account_page_status = get_option( 'wc_booking_on_account_page_status' );
					if ( $wc_booking_on_account_page_status != 'on' ) {
						$_bookpageid = get_option( 'wc_rb_my_account_page_id' );
						$_pagetab = 'myaccountbooking';
					}
				}

				$_setbooking = '';
				if ( empty( $_bookpageid ) || $_bookpageid < 1 ) {
					$_setbooking = 'data-alert-msg="' . esc_html__( 'From settings go to pages setup and set the booking page for this function to work. From administrator panel. ', 'computer-repair-shop' ) . '"';
				}
				// Get the booking page permalink
				$booking_url = get_permalink( $_bookpageid );

				// Prepare query parameters
				$query_args = array();

				if ( $id ) {
					$query_args['wcrb_selected_brand'] = $id;
				}
			
				if ( $_pagetab == 'myaccountbooking' ) {
					$query_args['book_device'] = 'yes'; // Add the tab parameter if needed
				}

				// Build the final URL with parameters
				$final_url = add_query_arg( $query_args, $booking_url );

				$actions_output = '<div class="actionswrapperjobs">';
				$actions_output .= '<a '. wp_kses_post( $_setbooking ) .' title="' . esc_html__( 'Book Device', 'computer-repair-shop' ) . '" target="_blank" href="' . esc_url( $final_url ) . '">
										<span class="dashicons dashicons-admin-page"></span>
									</a>';
				$actions_output .= '</div>';

				$columns = $actions_output;
			}
			return $columns;
		}
	endif;

	add_action( 'init', 'wc_create_device_tax_type');
	function wc_create_device_tax_type() {
		$wc_device_type_label        = ( empty( get_option( 'wc_device_type_label' ) ) ) ? esc_html__( 'Device Type', 'computer-repair-shop' ) : get_option( 'wc_device_type_label' );
		$wc_device_type_label_plural = ( empty( get_option( 'wc_device_type_label_plural' ) ) ) ? esc_html__( 'Device Type', 'computer-repair-shop' ) : get_option( 'wc_device_type_label_plural' );

		$labels = array(
			'name'              => esc_html( $wc_device_type_label_plural ),
			'singular_name'     => esc_html( $wc_device_type_label ),
			'search_items'      => esc_html__( 'Search', 'computer-repair-shop' ) . ' ' . esc_html( $wc_device_type_label_plural ),
			'all_items'         => esc_html__( 'All', 'computer-repair-shop' ) . ' ' . esc_html( $wc_device_type_label_plural ),
			'parent_item'       => esc_html__( 'Parent', 'computer-repair-shop' ) . ' ' . esc_html( $wc_device_type_label ),
			'parent_item_colon' => esc_html__( 'Parent', 'computer-repair-shop' ) . ' ' . esc_html( $wc_device_type_label ),
			'edit_item'         => esc_html__( 'Edit', 'computer-repair-shop' ) . ' ' . esc_html( $wc_device_type_label ),
			'update_item'       => esc_html__( 'Update', 'computer-repair-shop' ) . ' ' . esc_html( $wc_device_type_label ),
			'add_new_item'      => esc_html__( 'Add New', 'computer-repair-shop' ) . ' ' . esc_html( $wc_device_type_label ),
			'new_item_name'     => esc_html__( 'New Name', 'computer-repair-shop' ),
			'menu_name'         => esc_html( $wc_device_type_label ),
		);
		
		$args = array(
				'label'   => esc_html( $wc_device_type_label ),
				'rewrite' => array('slug' => 'device-type'),
				'public'  => true,
				'labels'  => $labels,
				'hierarchical' => true,
				'capabilities' => array(
								'manage_terms' => 'manage_rep_products',
								'edit_terms'   => 'manage_rep_products',
								'delete_terms' => 'manage_rep_products',
								'assign_terms' => 'edit_rep_products',
							),
				'show_admin_column' => true,	
		);
		
		register_taxonomy(
			'device_type',
			array( 'rep_devices', 'rep_devices_other' ),
			$args
		);
	} //Registration of Taxanomy Ends here.

	//Add image field in taxonomy page
	if( ! function_exists( 'wc_rp_add_custom_type_image' ) ) :
		add_action( 'device_type_add_form_fields', 'wc_rp_add_custom_type_image', 10, 2 );
		function wc_rp_add_custom_type_image ( $taxonomy ) {
		?>
			<div class="form-field term-group">
				<label for="image_id"><?php echo esc_html__( 'Image', 'computer-repair-shop' ); ?></label>
				<input type="hidden" id="image_id" name="image_id" class="custom_media_url" value="">
				<div id="image_wrapper"></div>
				<p>
					<input type="button" class="button button-secondary taxonomy_media_button" id="taxonomy_media_button" name="taxonomy_media_button"
					 value="<?php echo esc_html__( 'Add Image', 'computer-repair-shop' ); ?>">
					<input type="button" class="button button-secondary taxonomy_media_remove" id="taxonomy_media_remove" name="taxonomy_media_remove"
					 value="<?php echo esc_html__( 'Remove Image', 'computer-repair-shop' ); ?>">
				</p>
			</div>
		<?php
		}
	endif;

	//Save the taxonomy image field
	if ( ! function_exists( 'wc_rp_save_custom_type_image' ) ) :
		add_action( 'created_device_type', 'wc_rp_save_custom_type_image', 10, 2 );
		function wc_rp_save_custom_type_image ( $term_id, $tt_id ) {
			if( isset( $_POST['image_id'] ) && '' !== $_POST['image_id'] ) {
				$image = sanitize_text_field( $_POST['image_id'] );
				add_term_meta( $term_id, 'image_id', $image, true );
			}
		}
	endif;

	//Add the image field in edit form page
	if ( ! function_exists( 'wc_rb_update_custom_type_image' ) ) :
		add_action( 'device_type_edit_form_fields', 'wc_rb_update_custom_type_image', 10, 2 );
		function wc_rb_update_custom_type_image ( $term, $taxonomy ) { ?>
			<tr class="form-field term-group-wrap">
				<th scope="row">
					<label for="image_id"><?php echo esc_html__( 'Image', 'computer-repair-shop' ); ?></label>
				</th>
				<td>
					<?php $image_id = get_term_meta ( $term -> term_id, 'image_id', true ); ?>
					<input type="hidden" id="image_id" name="image_id" value="<?php echo esc_html($image_id); ?>">

					<div id="image_wrapper">
					<?php 
						if ( $image_id ) {
							$the_rb_tx_img = wp_get_attachment_image ( $image_id, 'thumbnail' );
							echo wp_kses_post( $the_rb_tx_img );
						}
					?>
					</div>
					<p>
						<input type="button" class="button button-secondary taxonomy_media_button" id="taxonomy_media_button" name="taxonomy_media_button"
						 value="<?php echo esc_html__( 'Add Image', 'computer-repair-shop' ); ?>">
						<input type="button" class="button button-secondary taxonomy_media_remove" id="taxonomy_media_remove" name="taxonomy_media_remove"
						 value="<?php echo esc_html__( 'Remove Image', 'computer-repair-shop' ); ?>">
					</p>
				</div></td>
			</tr>
		<?php
		}
	endif;

	//Update the taxonomy image field
	if( ! function_exists( 'wc_rb_updated_custom_type_image' ) ) :
		add_action( 'edited_device_type', 'wc_rb_updated_custom_type_image', 10, 2 );
		function wc_rb_updated_custom_type_image ( $term_id, $tt_id ) {
			if( isset( $_POST['image_id'] ) && '' !== $_POST['image_id'] ){
				$image = sanitize_text_field( $_POST['image_id'] );
				update_term_meta ( $term_id, 'image_id', $image );
			} else {
				update_term_meta ( $term_id, 'image_id', '' );
			}
		}
	endif;

	//Add new column heading
	if ( ! function_exists( 'wc_rb_display_custom_type_image_column_heading' ) ) :
		add_filter( 'manage_edit-device_type_columns', 'wc_rb_display_custom_type_image_column_heading' ); 
		function wc_rb_display_custom_type_image_column_heading( $columns ) {
			$columns['category_image'] = esc_html__( 'Icon', 'computer-repair-shop' );
			$columns['category_actions'] = esc_html__( 'Actions', 'computer-repair-shop' );
			return $columns;
		}
	endif;

	//Display new columns values
	if ( ! function_exists( 'wc_rb_display_custom_type_image_column_value' ) ) :
		add_action( 'manage_device_type_custom_column', 'wc_rb_display_custom_type_image_column_value' , 10, 3); 
		function wc_rb_display_custom_type_image_column_value( $columns, $column, $id ) {
			if ( 'category_image' == $column ) {
				$image_id = esc_html( get_term_meta( $id, 'image_id', true ) );
				$columns = wp_get_attachment_image ( $image_id, array('50', '50') );
			} elseif ( 'category_actions' == $column ) {
				// Edit link
				$_bookpageid = get_option( 'wc_rb_device_booking_page_id' );

				$_pagetab = '';
				if ( empty( $_bookpageid ) || $_bookpageid < 1 ) {
					$wc_booking_on_account_page_status = get_option( 'wc_booking_on_account_page_status' );
					if ( $wc_booking_on_account_page_status != 'on' ) {
						$_bookpageid = get_option( 'wc_rb_my_account_page_id' );
						$_pagetab = 'myaccountbooking';
					}
				}

				$_setbooking = '';
				if ( empty( $_bookpageid ) || $_bookpageid < 1 ) {
					$_setbooking = 'data-alert-msg="' . esc_html__( 'From settings go to pages setup and set the booking page for this function to work. From administrator panel. ', 'computer-repair-shop' ) . '"';
				}
				// Get the booking page permalink
				$booking_url = get_permalink( $_bookpageid );

				// Prepare query parameters
				$query_args = array();

				if ( $id ) {
					$query_args['wcrb_selected_type'] = $id;
				}
			
				if ( $_pagetab == 'myaccountbooking' ) {
					$query_args['book_device'] = 'yes'; // Add the tab parameter if needed
				}

				// Build the final URL with parameters
				$final_url = add_query_arg( $query_args, $booking_url );

				$actions_output = '<div class="actionswrapperjobs">';
				$actions_output .= '<a '. wp_kses_post( $_setbooking ) .' title="' . esc_html__( 'Book Device', 'computer-repair-shop' ) . '" target="_blank" href="' . esc_url( $final_url ) . '">
										<span class="dashicons dashicons-admin-page"></span>
									</a>';
				$actions_output .= '</div>';

				$columns = $actions_output;
			}
			return $columns;
		}
	endif;

	if ( ! function_exists( 'other_device_link' ) ) :
		add_action( 'admin_head-edit.php', 'other_device_link' );
		function other_device_link() {
			global $current_screen;
			
			// Validate we're on one of our target screens
			if ( ! in_array( $current_screen->post_type, array( 'rep_devices', 'rep_devices_other' ) ) ) {
				return;
			}
			
			// Determine target post type and labels
			if ( 'rep_devices' === $current_screen->post_type ) {
				$target_post_type = 'rep_devices_other';
				$label = ! empty( get_option( 'wc_device_label_plural' ) ) 
					? esc_html__( 'Other', 'computer-repair-shop' ) . ' ' . get_option( 'wc_device_label_plural' )
					: esc_html__( 'Other Devices', 'computer-repair-shop' );
			} else {
				$target_post_type = 'rep_devices';
				$label = ! empty( get_option( 'wc_device_label_plural' ) ) 
					? get_option( 'wc_device_label_plural' )
					: esc_html__( 'Devices', 'computer-repair-shop' );
			}
			
			$target_link = esc_url( 'edit.php?post_type=' . $target_post_type );
			?>
			<script type="text/javascript">
				jQuery(function($) {
					var linksHtml = '<a id="doc_popup" href="<?php echo $target_link; ?>" class="add-new-h2"><?php echo esc_js( $label ); ?></a>';
					
					// Try multiple insertion points with proper fallbacks
					var $headerEnd = $('hr.wp-header-end');
					var $pageTitleAction = $('a.page-title-action');
					var $heading = $('h1.wp-heading-inline');
					
					if ($headerEnd.length) {
						$headerEnd.before(linksHtml);
					} else if ($pageTitleAction.length) {
						$pageTitleAction.after(linksHtml);
					} else if ($heading.length) {
						$heading.after(linksHtml);
					} else {
						$('.wrap:first').prepend(linksHtml);
					}
				});
			</script>
			<?php
		}

	endif;

	if ( ! function_exists( 'wcrb_add_other_device_return_id' ) ) :
		function wcrb_add_other_device_return_id( $new_device_name, $brand_id, $type_id ) {
			if ( empty( $new_device_name ) ) {
				return;
			}
			$device_post_id_h = '';

			$tax_query = array();
			if ( ! empty( $brand_id ) && ! empty( $type_id ) ) {
				$tax_query['relation'] = 'AND';
			}
			if ( ! empty( $brand_id ) ) {
				$tax_query[] = array(
					'taxonomy'	=> 'device_brand',
					'terms'		=> $brand_id,
					'field'		=> 'term_id',
				);
			}
			if ( ! empty( $type_id ) ) {
				$tax_query[] = array(
					'taxonomy'	=> 'device_type',
					'terms'		=> $type_id,
					'field'		=> 'term_id',
				);
			}
			$args = array(
				'post_type' => array('rep_devices', 'rep_devices_other'),
				'title'     => $new_device_name,
				'tax_query' => $tax_query,
				'posts_per_page' => '1',
			);
			$the_query = new WP_Query( $args );

			if ( $the_query->have_posts() ) {
				while ( $the_query->have_posts() ) {
					$the_query->the_post();

					$device_post_id_h = $the_query->post->ID;
				}
			} else {
				$device_data = array(
					'post_title' => esc_html( $new_device_name ),
					'post_status' => 'publish',
					'post_type'   => 'rep_devices_other',
					'tax_input'    => array(
											"device_brand" => $brand_id,
											"device_type" => $type_id
											),
				);
				$device_post_id_h = wp_insert_post( $device_data );
			}
			wp_reset_postdata();
			return $device_post_id_h;
		}
	endif;