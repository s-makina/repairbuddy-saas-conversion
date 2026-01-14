<?php
/**
 * The file contains the functions related to Shortcode Pages
 *
 * Help setup pages to they can be used in notifications and other items
 *
 * @package computer-repair-shop
 * @version 3.7947
 */

defined( 'ABSPATH' ) || exit;

class WCRB_PARTS {

	function __construct() {
		add_action( 'wp_ajax_wc_add_part_for_fly', array( $this, 'wc_add_part_for_fly' ) );
    }

	function post_exists_with_status( $title, $post_type = 'post', $status = array('publish', 'private') ) {
		global $wpdb;
		
		$post_title = wp_unslash( sanitize_post_field( 'post_title', $title, 0, 'db' ) );
		
		$query = "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = %s";
		$params = array($post_title, $post_type);
		
		if ( ! empty( $status ) ) {
			if ( is_array( $status ) ) {
				$placeholders = implode(',', array_fill(0, count($status), '%s'));
				$query .= " AND post_status IN ($placeholders)";
				$params = array_merge($params, $status);
			} else {
				$query .= " AND post_status = %s";
				$params[] = $status;
			}
		}
		
		return $wpdb->get_var( $wpdb->prepare( $query, $params ) );
	}

	function wc_add_part_for_fly() {
		$message = '';
		$part_id = '';
		$success = 'NO';

		$part_partName 	  	    = ( isset( $_POST['part_partName'] ) ) ? sanitize_text_field( $_POST['part_partName'] ) : '';
		$part_partBrand 	    = ( isset( $_POST['part_partBrand'] ) ) ? sanitize_text_field( $_POST['part_partBrand'] ) : '';
		$part_partType 		    = ( isset( $_POST['part_partType'] ) ) ? sanitize_text_field( $_POST['part_partType'] ) : '';
		$part_manufacturingCode = ( isset( $_POST['part_manufacturingCode'] ) ) ? sanitize_text_field( $_POST['part_manufacturingCode'] ) : '';
		$part_StockCode 		= ( isset( $_POST['part_StockCode'] ) ) ? sanitize_text_field( $_POST['part_StockCode'] ) : '';
		$part_price 			= ( isset( $_POST['part_price'] ) ) ? sanitize_text_field( $_POST['part_price'] ) : '';

		if (!isset( $_POST['wc_rb_mb_device_submit'] ) || ! wp_verify_nonce( $_POST['wc_rb_mb_device_submit'], 'wc_computer_repair_mb_nonce' )) :
			$message = esc_html__("Something is wrong with your submission!", "computer-repair-shop");
			$success = "NO";
        else:
			if ( empty ( $part_partName ) || empty( $part_manufacturingCode ) || empty( $part_price ) ) {
				$message = esc_html__( 'Part name, manufacturing code and price are required fields.', 'computer-repair-shop' );
			} else {
				//Check device status
				$curr = $this->post_exists_with_status( $part_partName, 'rep_products' );

				if ( empty( $curr ) ) {
					//Post didn't exist let's add 
					$post_data = array(
						'post_title'    => $part_partName,
						'post_status'   => 'private',
						'post_type' 	=> 'rep_products',
					);
					$post_id = wp_insert_post( $post_data );

					if ( ! empty( $part_partBrand ) ) {
						$tag = array( $part_partBrand );
						wp_set_post_terms( $post_id, $tag, 'brand_type' );
					}
					if ( ! empty( $part_partType ) ) {
						$tag = array( $part_partType );
						wp_set_post_terms( $post_id, $tag, 'part_type' );
					}

					update_post_meta($post_id, '_manufacturing_code', $part_manufacturingCode);
					update_post_meta($post_id, '_stock_code', $part_StockCode);
					update_post_meta($post_id, '_price', $part_price);

					$part_id = $post_id;
					$message = esc_html__( 'Part added to add variations, featured image, features and other information go to parts.', 'computer-repair-shop' );
				} else {
					$part_id = $curr;
					$message = esc_html__( 'Part with same name already exists', 'computer-repair-shop' );
				}
			}
		endif;

		$values['message'] = $message;
		$values['part_id'] = $part_id;
		$values['success'] = $success;

		wp_send_json( $values );
		wp_die();
	}

	/**
	 * Parts Brands Options
	 * Return options
	 * Outputs selected options
	 */
	function generate_brands_select_options( $selected_brand ) {
		$selected_brand = ( ! empty( $selected_brand ) ) ? $selected_brand : '';

		$wcrb_type = 'rep_products';
		$wcrb_tax  = 'brand_type';

		$cat_terms = get_terms(
			array(
					'taxonomy'		=> $wcrb_tax,
					'hide_empty'    => false,
					'orderby'       => 'name',
					'order'         => 'ASC',
					'number'        => 0
				)
		);

		$output = "<option value='All'>" . esc_html__( 'Select Brand', 'computer-repair-shop' ) . "</option>";

		if( $cat_terms ) :
			foreach( $cat_terms as $term ) :
				$selected = ( $term->term_id == $selected_brand ) ? ' selected' : '';
				$output .= '<option ' . $selected . ' value="' . esc_html( $term->term_id ) . '">';
				$output .= $term->name;
				$output .= '</option>';

			endforeach;
		endif;

		return $output;
	}

	function generate_type_select_options( $selected_type ) {
		$selected_type = ( ! empty( $selected_type ) ) ? $selected_type : '';

		$wcrb_type = 'rep_products';
		$wcrb_tax  = 'part_type';

		$cat_terms = get_terms(
			array(
					'taxonomy'		=> $wcrb_tax,
					'hide_empty'    => false,
					'orderby'       => 'name',
					'order'         => 'ASC',
					'number'        => 0
				)
		);

		$output = "<option value='All'>" . esc_html__( 'Select Type', 'computer-repair-shop' ) . "</option>";

		if( $cat_terms ) :
			foreach( $cat_terms as $term ) :
				$selected = ( $term->term_id == $selected_type ) ? ' selected' : '';
				$output .= '<option ' . $selected . ' value="' . esc_html( $term->term_id ) . '">';
				$output .= $term->name;
				$output .= '</option>';

			endforeach;
		endif;

		return $output;
	}

	function add_parts_reveal_form() {
		$output = '<div class="small reveal" id="partFormReveal" data-reveal>';

		$output .= '<h2>' . esc_html__( 'Add a new part', 'computer-repair-shop' ) . '</h2>';
		$output .= '<div class="part-form-message"></div>';

		$output .= '<form data-async data-abide data-success-class=".part-form-message" class="needs-validation" novalidate method="post">';
		
		$output .= '<div class="grid-x grid-margin-x">';
		
		$output .= '<div class="cell medium-6">
						<label>' . esc_html__( 'Part Name', 'computer-repair-shop' ) . '*
							<input name="part_partName" type="text" class="form-control login-field"
								   value="" required id="part_partName"/>
						</label>
					</div>';

		$output .= '<div class="cell medium-6">
		<label>' . esc_html__( 'Part Price', 'computer-repair-shop' ) . '*
			<input name="part_price" type="number" class="form-control login-field"
					value="" required id="part_price" step="any" />
		</label></div>';

		$output .= '</div>';

		$output .= '<div class="grid-x grid-margin-x">';

		$output .= '<div class="cell medium-6">
					<label class="have-addition">' . esc_html__( 'Select Brand', 'computer-repair-shop' ) . '*';
		$output .= '<select name="part_partBrand">';
		$output .= $this->generate_brands_select_options( '' );
		$output .= '</select>';
		$output .= '<a href="edit-tags.php?taxonomy=brand_type&post_type=rep_products" target="_blank" class="button button-primary button-small" title="' . esc_html__( 'Add Brand', 'computer-repair-shop' ) . '"><span class="dashicons dashicons-plus"></span></a>';
		$output .= '</label>
					</div>';

		$output .= '<div class="cell medium-6">
					<label class="have-addition">' . esc_html__( 'Select Type', 'computer-repair-shop' ) . '*';
		$output .= '<select name="part_partType">';
		$output .= $this->generate_type_select_options( '' );
		$output .= '</select>';
		$output .= '<a href="edit-tags.php?taxonomy=part_type&post_type=rep_products" target="_blank" class="button button-primary button-small" title="' . esc_html__( 'Add Brand', 'computer-repair-shop' ) . '"><span class="dashicons dashicons-plus"></span></a>';
		$output .= '</label>
					</div>';					
					
		$output .= '</div>';

		$output .= '<div class="grid-x grid-margin-x">';
		$output .= '<div class="cell medium-6">
						<label>' . esc_html__( 'Manufacturing Code', 'computer-repair-shop' ) . '*
							<input name="part_manufacturingCode" type="text" class="form-control login-field"
								   value="" required id="part_manufacturingCode"/>
						</label></div>';
	
		$output .= '<div class="cell medium-6">
						<label>' . esc_html__( 'Stock Code', 'computer-repair-shop' ) . '
							<input name="part_StockCode" type="text" class="form-control login-field"
								   value="" id="part_StockCode"/>
						</label></div>';
		$output .= '</div>';

		$output .=  wp_nonce_field( 'wc_computer_repair_mb_nonce', 'wc_rb_mb_device_submit', true, false);
		$output .= '<input name="form_type" type="hidden" value="add_part_fly_form" />';

		$output .= '<div class="grid-x grid-margin-x">';
		$output .= '<fieldset class="cell medium-6">';
		$output .= '<button class="button" type="submit" value="Submit">';
		$output .= esc_html__( 'Add Part', 'computer-repair-shop' );
		$output .= '</button></fieldset>';
					
		$output .= '<small>' . esc_html__( '(*) fields are required', 'computer-repair-shop' ) . '</small>';	
		$output .= '</div></form>';
	
		$output .= '<button class="close-button" data-close aria-label="Close modal" type="button"><span aria-hidden="true">&times;</span></button></div>';

		$allowedHTML = ( function_exists( 'wc_return_allowed_tags' ) ) ? wc_return_allowed_tags() : '';
		echo wp_kses( $output, $allowedHTML );
	}

	function get_term_count_including_private( $term_id, $taxonomy ) {
		global $wpdb;
		
		$query = $wpdb->prepare(
			"SELECT COUNT(*) FROM $wpdb->term_relationships
			INNER JOIN $wpdb->posts ON $wpdb->posts.ID = $wpdb->term_relationships.object_id
			WHERE $wpdb->term_relationships.term_taxonomy_id = %d
			AND $wpdb->posts.post_type = 'rep_products'
			AND $wpdb->posts.post_status IN ('publish', 'private')",
			$term_id
		);
		
		return $wpdb->get_var( $query );
	}


	function add_parts_dropdown_by_device( $_device_id ) {
		$_parts_array = array();
		
		// First get all terms without empty filtering
		$_groups = get_terms( array(
			'taxonomy'   => 'part_type',
			'orderby'    => 'name',
			'order'      => 'ASC',
			'hide_empty' => false,
		) );

		// Then filter out truly empty terms
		if ( ! empty( $_groups ) && ! is_wp_error( $_groups ) ) {
			foreach ( $_groups as $key => $term ) {
				$count = $this->get_term_count_including_private( $term->term_id, 'part_type' );
				if ( $count === 0 ) {
					unset( $_groups[$key] );
				}
			}
			// Re-index array
			$_groups = array_values( $_groups );
		}

		$counter = 1; //counter init

		$options = '';

		if ( ! empty( $_groups ) ) {
			foreach( $_groups as $group ) {
				$group_slug = $group->slug;
				$group_name = $group->name;
				$group_id   = $group->term_id;

				$_posts_ids = $this->return_parts_post_array_for_device_and_group( $_device_id, $group_id );

				if ( ! empty( $_posts_ids ) ) {
					$_parts_array['types'][] = array(
						'type_id' => $group_id,
						'type_name' => $group_name,
						'type_slug' => $group_slug,
						'type_posts' => $_posts_ids
					);
				}
			}
		}//Terms array completed.
		
		//Other posts
		$_posts_ids = $this->return_parts_post_array_for_device_and_group( $_device_id, '_no_term' );

		if ( ! empty( $_posts_ids ) ) {
			$_parts_array['types'][] = array(
				'type_id' => 'other',
				'type_name' => esc_html__( 'Other', 'computer-repair-shop' ),
				'type_slug' => 'other',
				'type_posts' => $_posts_ids
			);
		}

		$_name_id = ( ! empty( $_device_id ) ) ? 'part_device_' . $_device_id : 'part_device';
		//$_name_tw = ( ! empty( $_device_id ) ) ? 'part_device_' . $_device_id : 'part_device_default';
		$_name_tw = '';
		$_select_label = ( ! empty( $_device_id ) ) ? esc_html__( 'Select Part For', 'computer-repair-shop' ) . ' ' . get_the_title( $_device_id ) : esc_html__( 'Select Part', 'computer-repair-shop' );

		$select_option = '<select class="select-repair-products part_device_select addrepairproducttolist" name="' . esc_attr( $_name_id ) . '" id="' . esc_attr( $_name_id ) . '" data-device-id="' . esc_attr( $_device_id ) . '" data-security="' . wp_create_nonce( 'add-products' ) . '">';
		$select_option .= '<option value="">' . $_select_label . '</option>';

		if ( ! empty( $_parts_array ) ) {
			foreach( $_parts_array['types'] as $type ) {
				$select_option .= '<optgroup label="' . $type['type_name'] . '">';
				foreach( $type['type_posts'] as $post ) {
					$_parent_id = $post['parent_id'];
					$_sub_ids = $post['sub_ids'];

					$_post = get_post( $_parent_id );
					$_post_name = $_post->post_title;

					$part_title = get_post_meta( $_parent_id, '_part_title', true );
					$_post_name = ( ! empty( $part_title ) ) ? $part_title : $_post_name;

					$select_option .= '<option value="' . $_parent_id .'">' . $_post_name . '</option>';

					if ( ! empty( $_sub_ids ) ) {
						foreach( $_sub_ids as $sub_id ) {
							$_sub_post = get_post_meta( $_parent_id, $sub_id . '_part_title', true );

							$select_option .= '<option value="parentid_'. $_parent_id .'__' . $sub_id . '">' . $_sub_post . '</option>';
						}
					}
				}
				$select_option .= '</optgroup>';
			}
		}

		$select_option .= '</select>';

		return $select_option;
	} // End of function

	function return_parts_post_array_for_device_and_group( $MtheDeviceId, $term_id ) {
		if ( empty( $term_id ) ) {
			return '';
		}

		$post_output = array();
		
		if ( $term_id == '_no_term' ) {
			$_tax_query = array(
				'taxonomy' => 'part_type',
				'operator' => 'NOT EXISTS',
			);
		} else {
			$_tax_query = array(
				'taxonomy' => 'part_type',
				'field'    => 'term_id',
				'terms'    => $term_id,
			);
		}
		
		$post_query = array(
			'posts_per_page' => -1,
			'post_type'      => 'rep_products',
			'post_status'    => array('publish', 'private'), // This is correct
			'tax_query'      => array( $_tax_query ),
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		$the_post = new WP_Query( $post_query );
		$WCRB_DEVICE_PARTS = WCRB_DEVICE_PARTS::getInstance();

		if( $the_post->have_posts() ) : 
			while( $the_post->have_posts() ): 
				$the_post->the_post();
				$part_id   = $the_post->post->ID;
				$sub_part = 'default';

				$_receive_array = $WCRB_DEVICE_PARTS->get_price_by_device_for_part( $MtheDeviceId, $part_id, $sub_part );
				
				// Initialize $_parent_id
				$_parent_id = null;
				$_sub_ids = array();
				
				// Check if main part is active for this device
				if ( $_receive_array['status'] != 'inactive' ) {
					$_parent_id = $part_id;
					
					// Now check for sub-parts
					$sub_parts = get_post_meta( $part_id, '_sub_parts_arr', true );

					if ( ! empty( $sub_parts ) && is_array( $sub_parts ) ) {
						foreach ( $sub_parts as $key => $sub_part ) {
							if ( $sub_part != 'default' ) {
								$_receive_array_sub = $WCRB_DEVICE_PARTS->get_price_by_device_for_part( $MtheDeviceId, $part_id, $sub_part );
								if ( $_receive_array_sub['status'] != 'inactive' ) {
									$_sub_ids[] = $sub_part;
								}
							}
						}
					}
				}
				
				// Only add to output if we have a valid parent part
				if ( ! is_null( $_parent_id ) ) {
					$post_output[] = array(
						'parent_id' => $_parent_id,
						'sub_ids'   => $_sub_ids
					);
				}
			endwhile;
		endif;
		wp_reset_postdata();

		return $post_output;
	}
} // end of class