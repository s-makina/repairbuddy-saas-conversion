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

add_action( 'plugins_loaded', array( 'WCRB_DEVICE_PARTS', 'getInstance' ) );

class WCRB_DEVICE_PARTS extends WCRB_MANAGE_DEVICES {

    private static $instance = NULL;

	static public function getInstance() {
		if ( self::$instance === NULL )
			self::$instance = new WCRB_DEVICE_PARTS();
		return self::$instance;
	}

    public function __construct() {
        add_action( 'wp_ajax_wcrb_update_part_meta', array( $this, 'wcrb_update_part_meta' ) );
		add_action( 'wp_ajax_wcrb_update_parts_prices', array( $this, 'wcrb_update_parts_prices' ) );
		add_action( 'wp_ajax_wcrb_append_new_part', array( $this, 'wcrb_append_new_part' ) );

		add_action( 'admin_footer', array( $this, 'add_select_all_javascript' ) );
	}

	function wcrb_append_new_part() {
		$values = array();
        $success = $message = $output = '';

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wc_meta_box_nonce' ) ) {
			$message = esc_html__( 'Something is wrong with your submission!', 'computer-repair-shop' );
		} else {
			$_part = wcrb_get_random_unique_username( 'part_id_' );
			$_part_id = ( isset( $_POST['part_id'] ) && ! empty( $_POST['part_id'] ) ) ? sanitize_text_field( $_POST['part_id'] ) : '';

			$message = esc_html__( 'Part variation added', 'computer-repair-shop' );
			$output  = $this->the_part_metabox( $_part_id, $_part );
		}

		$values['partdata'] = $output;
		$values['message'] = $message;
        $values['success'] = $success;
        wp_send_json( $values );
        wp_die();
	}

	function the_part_metabox( $_part_id, $_part ) {
		$_Vpart = ( $_part == 'default' ) ? '' : $_part;
		$_NPart = ( $_part == 'default' ) ? '' : $_part . '_';
		$active_class = ( $_part == 'default' ) ? ' is-active' : '';

		$output = '<li class="accordion-item'. esc_html( $active_class ) .'" data-accordion-item>';
		
		$value = get_post_meta( $_part_id, $_Vpart . '_part_title', true );

		$output .= '<a href="#" class="accordion-title">';
		$output .= esc_html__( 'Set price by ', 'computer-repair-shop' )  . ' - <span class="part_title_place'. $_Vpart .'">' . esc_html( $value ) . '</span>';
		$output .= '</a>';

		$output .= '<div class="accordion-content" data-tab-content>';

			$output .= '<div class="grid-x grid-margin-x">';
			
			$output .= '<div class="cell medium-4">';
			$output .= '<label for="'. $_NPart .'part_title">' . esc_html__( 'Part name', 'computer-repair-shop' );
			$output .= '<input type="text" class="regular-text" name="'. $_NPart .'part_title" id="'. $_NPart .'part_title" title_target=".part_title_place'. $_Vpart .'" 
			placeholder="'. esc_html__( 'e.g Microphone (OEM) OR Microphone (Compatible)', 'computer-repair-shop' ) .'" value="'.esc_attr( $value ). '" />';
			$output .= '</label>';
			$output .= '</div>';//End of cell

			$value = get_post_meta( $_part_id, $_Vpart . '_manufacturing_code', true );
			$output .= '<div class="cell medium-4">';
			$output .= '<label for="'. $_NPart .'manufacturing_code">' . esc_html__( 'Manufacturing code', 'computer-repair-shop' );
			$output .= '<input type="text" class="regular-text" name="'. $_NPart .'manufacturing_code" id="'. $_NPart .'manufacturing_code" value="'.esc_attr( $value ). '" />';
			$output .= '</label>';
			$output .= '</div>';//End of cell

			$value = get_post_meta( $_part_id, $_Vpart . '_stock_code', true );
			$output .= '<div class="cell medium-4">';
			$output .= '<label for="'. $_NPart .'stock_code">' . esc_html__( 'Stock code', 'computer-repair-shop' );
			$output .= '<input type="text" class="regular-text" name="'. $_NPart .'stock_code" id="'. $_NPart .'stock_code" value="'.esc_attr($value). '" />';
			$output .= '</label>';
			$output .= '</div>';//End of cell
			
			$output .= '</div>'; //End of grid-x

			$output .= '<div class="grid-x grid-margin-x">';
			
			$value = get_post_meta( $_part_id, $_Vpart . '_price', true );
			$output .= '<div class="cell medium-4">';
			$output .= '<label for="'. $_NPart .'wc_price">' . esc_html__( 'Price', 'computer-repair-shop' );
			$output .= '<input type="number" step="any" class="regular-text" name="'. $_NPart .'price" id="'. $_NPart .'wc_price" value="'.esc_attr( $value ). '" />';
			$output .= '</label>';
			$output .= '</div>';//End of cell

			//wc_use_tax
			$wc_use_taxes 		= get_option("wc_use_taxes");
			$wc_primary_tax		= get_option("wc_primary_tax");

			if($wc_use_taxes == "on"):
				$value = get_post_meta( $_part_id, $_Vpart . '_wc_use_tax', true );
				$value = ( empty( $value ) ) ? $wc_primary_tax : $value;
				$output .= '<div class="cell medium-4">';
				$output .= '<label for="'. $_NPart .'wc_use_tax">' . esc_html__( 'Select tax', 'computer-repair-shop' );
				$output .= '<select class="regular-text form-control" name="'. $_NPart .'wc_use_tax" id="'. $_NPart .'wc_use_tax">';
				$output .= '<option value="">'.esc_html__( 'Select tax', 'computer-repair-shop' ).'</option>';
				$output .= wc_generate_tax_options( $value );
				$output .= "</select>";
				$output .= '</label>';
				$output .= '</div>';//End of cell
			endif; // Tax enabled

			$value = get_post_meta( $_part_id, $_Vpart . '_warranty', true );
			$output .= '<div class="cell medium-4">';
			$output .= '<label for="'. $_NPart .'warranty">' . esc_html__( 'Warranty', 'computer-repair-shop' );
			$output .= '<input type="text" class="regular-text" name="'. $_NPart .'warranty" id="'. $_NPart .'warranty" value="'.esc_attr( $value ). '" />';
			$output .= '</label>';
			$output .= '</div>';//End of cell
			
			$output .= '</div>'; //End of grid-x

			$output .= '<div class="grid-x grid-margin-x">';

			$value = get_post_meta( $_part_id, $_Vpart . '_core_features', true );
			$output .= '<div class="cell medium-6">';
			$output .= '<label for="'. $_NPart .'core_features">' . esc_html__( 'Core features', 'computer-repair-shop' );
			$output .= '<textarea class="large-text" name="'. $_NPart .'core_features" id="'. $_NPart .'core_features" rows="7">'.esc_attr($value).'</textarea>';
			$output .= '</label>';
			$output .= '</div>';//End of cell

			
			$output .= '<div class="cell medium-6">';

			$value = get_post_meta( $_part_id, $_Vpart . '_capacity', true );
			$output .= '<label for="'. $_NPart .'capacity">' . esc_html__( 'Capacity', 'computer-repair-shop' );
			$output .= '<input type="text" class="regular-text" name="'. $_NPart .'capacity" id="'. $_NPart .'capacity" value="'.esc_attr( $value ). '" />';
			$output .= '</label>';

			$value = get_post_meta( $_part_id, $_Vpart . '_installation_charges', true );
			$output .= '<label for="'. $_NPart .'installation_charges">' . esc_html__( 'Installation charges', 'computer-repair-shop' );
			$output .= '<input type="number" class="regular-text" step="any" name="'. $_NPart .'installation_charges" id="'. $_NPart .'installation_charges" value="'.esc_attr($value). '" />';
			$output .= '</label>';

			$value = get_post_meta( $_part_id, $_Vpart . '_installation_message', true );
			$output .= '<label for="'. $_NPart .'installation_message">' . esc_html__( 'Installation message', 'computer-repair-shop' );
			$output .= '<input type="text" class="regular-text" name="'. $_NPart .'installation_message" id="'. $_NPart .'installation_message" value="'.esc_attr( $value ). '" />';
			$output .= '</label>';

			$output .= '</div>';//End of cell

			$output .= '</div>'; //End of grid-x

			$output .= $this->wc_parts_price_by_device( $_part_id, $_part );

		$output .= '</div>';//End accordion-content
		$output .= '</li>';//End accordion-item

		return $output;
	}

	function wcrb_update_parts_prices() {
		$values = array();
        $success = $message = '';
        
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wc_meta_box_nonce' ) ) {
			$message = esc_html__( 'Something is wrong with your submission!', 'computer-repair-shop' );
		} else {
			$_part_id = ( isset( $_POST['part_id'] ) && ! empty( $_POST['part_id'] ) ) ? sanitize_text_field( $_POST['part_id'] ) : '';
			$_part 	  = ( isset( $_POST['part'] ) && ! empty( $_POST['part'] ) ) ? sanitize_text_field( $_POST['part'] ) : '';

			if ( empty( $_part_id ) ) {
				$message = esc_html__( 'Something is wrong with your submission!', 'computer-repair-shop' );
			} else {
				$this->add_sub_part( $_part_id, $_part );
				$preparedArray = array();

				if ( isset( $_POST['type_id'] ) ) {
					for ( $i = 0; $i < count( $_POST['type_id'] ); $i++) {
						$preparedArray[$i]['typeid'] = $type_id = ( ! empty( $_POST['type_id'][$i] ) ) ? sanitize_text_field( $_POST['type_id'][$i]['value'] ) : '';
						$preparedArray[$i]['manufacturingcode'] = ( ! empty( $_POST['manufacturing_code'][$i] ) ) ? sanitize_text_field( $_POST['manufacturing_code'][$i]['value'] ) : '';
						$preparedArray[$i]['stockcode'] = ( ! empty( $_POST['stock_code'][$i] ) ) ? sanitize_text_field( $_POST['stock_code'][$i]['value'] ) : '';
						$preparedArray[$i]['typeprice'] = ( ! empty( $_POST['type_price'][$i] ) ) ? sanitize_text_field( $_POST['type_price'][$i]['value'] ) : '';

						$preparedArray[$i]['typestatus'] = 'inactive';
						foreach( $_POST['type_status'] as $typeStatus  ) {
							if ( $typeStatus['value'] == $type_id ) {
								$preparedArray[$i]['typestatus'] = 'active';
							}
						}//EndForEach
					}//EndFor

					if ( ! empty( $preparedArray ) ) {
						if ( is_array( $preparedArray ) ) {
							foreach( $preparedArray as $prepared ) {
								$ARtypeid     		  = $prepared['typeid'];
								$ARmanufacturingcode  = $prepared['manufacturingcode'];
								$ARstockcode  		  = $prepared['stockcode'];
								$ARtypeprice  		  = $prepared['typeprice'];
								$ARtypestatus 		  = $prepared['typestatus'];

								update_post_meta( $_part_id, 'type_price_' . esc_html( $_part ) . '_' . esc_html( $ARtypeid ), $ARtypeprice );
								update_post_meta( $_part_id, 'type_stock_code_' . esc_html( $_part ) . '_' . esc_html( $ARtypeid ), $ARstockcode );
								update_post_meta( $_part_id, 'type_manufacturing_code_' . esc_html( $_part ) . '_' . esc_html( $ARtypeid ), $ARmanufacturingcode );
								update_post_meta( $_part_id, 'type_status_' . esc_html( $_part ) . '_' . esc_html( $ARtypeid ), $ARtypestatus );
							}//EndForEach
						}
					}
				} elseif ( isset( $_POST['brand_id'] ) ) {
					for ( $i = 0; $i < count( $_POST['brand_id'] ); $i++) {
						$preparedArray[$i]['brandid'] = $brand_id = ( ! empty( $_POST['brand_id'][$i] ) ) ? sanitize_text_field( $_POST['brand_id'][$i]['value'] ) : '';
						$preparedArray[$i]['manufacturingcode']   = ( ! empty( $_POST['manufacturing_code'][$i] ) ) ? sanitize_text_field( $_POST['manufacturing_code'][$i]['value'] ) : '';
						$preparedArray[$i]['stockcode'] 		  = ( ! empty( $_POST['stock_code'][$i] ) ) ? sanitize_text_field( $_POST['stock_code'][$i]['value'] ) : '';
						$preparedArray[$i]['brandprice'] 		  = ( ! empty( $_POST['brand_price'][$i] ) ) ? sanitize_text_field( $_POST['brand_price'][$i]['value'] ) : '';

						$preparedArray[$i]['brandstatus'] = 'inactive';
						foreach( $_POST['brand_status'] as $brandStatus  ) {
							if ( $brandStatus['value'] == $brand_id ) {
								$preparedArray[$i]['brandstatus'] = 'active';
							}
						}//EndForEach
					}//EndFor

					if ( ! empty( $preparedArray ) ) {
						if ( is_array( $preparedArray ) ) {
							foreach( $preparedArray as $prepared ) {
								$ARtypeid     		  = $prepared['brandid'];
								$ARmanufacturingcode  = $prepared['manufacturingcode'];
								$ARstockcode  		  = $prepared['stockcode'];
								$ARtypeprice  		  = $prepared['brandprice'];
								$ARtypestatus 		  = $prepared['brandstatus'];

								update_post_meta( $_part_id, 'brand_price_' . esc_html( $_part ) . '_' . esc_html( $ARtypeid ), $ARtypeprice );
								update_post_meta( $_part_id, 'brand_stock_code_' . esc_html( $_part ) . '_' . esc_html( $ARtypeid ), $ARstockcode );
								update_post_meta( $_part_id, 'brand_manufacturing_code_' . esc_html( $_part ) . '_' . esc_html( $ARtypeid ), $ARmanufacturingcode );
								update_post_meta( $_part_id, 'brand_status_' . esc_html( $_part ) . '_' . esc_html( $ARtypeid ), $ARtypestatus );
							}//EndForEach
						}
					}
				} elseif ( isset( $_POST['device_id'] ) ) {
					for ( $i = 0; $i < count( $_POST['device_id'] ); $i++) {
						$preparedArray[$i]['deviceid'] = $device_id = ( ! empty( $_POST['device_id'][$i] ) ) ? sanitize_text_field( $_POST['device_id'][$i]['value'] ) : '';
						$preparedArray[$i]['manufacturingcode']   = ( ! empty( $_POST['manufacturing_code'][$i] ) ) ? sanitize_text_field( $_POST['manufacturing_code'][$i]['value'] ) : '';
						$preparedArray[$i]['stockcode'] 		  = ( ! empty( $_POST['stock_code'][$i] ) ) ? sanitize_text_field( $_POST['stock_code'][$i]['value'] ) : '';
						$preparedArray[$i]['deviceprice'] 		  = ( ! empty( $_POST['device_price'][$i] ) ) ? sanitize_text_field( $_POST['device_price'][$i]['value'] ) : '';

						$preparedArray[$i]['devicestatus'] = 'inactive';
						foreach( $_POST['device_status'] as $deviceStatus  ) {
							if ( $deviceStatus['value'] == $device_id ) {
								$preparedArray[$i]['devicestatus'] = 'active';
							}
						}//EndForEach
					}//EndFor

					if ( ! empty( $preparedArray ) ) {
						if ( is_array( $preparedArray ) ) {
							foreach( $preparedArray as $prepared ) {
								$ARtypeid     		  = $prepared['deviceid'];
								$ARmanufacturingcode  = $prepared['manufacturingcode'];
								$ARstockcode  		  = $prepared['stockcode'];
								$ARtypeprice  		  = $prepared['deviceprice'];
								$ARtypestatus 		  = $prepared['devicestatus'];

								update_post_meta( $_part_id, 'device_price_' . esc_html( $_part ) . '_' . esc_html( $ARtypeid ), $ARtypeprice );
								update_post_meta( $_part_id, 'device_stock_code_' . esc_html( $_part ) . '_' . esc_html( $ARtypeid ), $ARstockcode );
								update_post_meta( $_part_id, 'device_manufacturing_code_' . esc_html( $_part ) . '_' . esc_html( $ARtypeid ), $ARmanufacturingcode );
								update_post_meta( $_part_id, 'device_status_' . esc_html( $_part ) . '_' . esc_html( $ARtypeid ), $ARtypestatus );
							}//EndForEach
						}
					}
				} //type_id
				$message = esc_html__( 'Record updated!', 'computer-repair-shop' );
			}
		}

		$values['message'] = $message;
        $values['success'] = $success;
        wp_send_json( $values );
        wp_die();
	}

    function wcrb_update_part_meta() {
        $values = array();
        $success = $message = '';
        
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wc_meta_box_nonce' ) ) {
			$message = esc_html__( 'Something is wrong with your submission!', 'computer-repair-shop' );
		} else {
            $_part_id = ( isset( $_POST['part_id'] ) && ! empty( $_POST['part_id'] ) ) ? sanitize_text_field( $_POST['part_id'] ) : '';
            $_part    = ( isset( $_POST['part'] ) && ! empty( $_POST['part'] ) ) ? sanitize_text_field( $_POST['part'] ) : '';
			$_addition = ( $_part != 'default' ) ? $_part : '';
			$_Naddition = ( $_part != 'default' ) ? $_part . '_' : '';

            if ( ! empty( $_part_id ) && ! empty( $_part ) ) {
				$this->add_sub_part( $_part_id, $_part );

                $_input_arr = array('part_title', 'manufacturing_code', 'stock_code', 'price', 'wc_use_tax', 'warranty', 'core_features', 'capacity', 'installation_charges', 'installation_message');

                foreach( $_input_arr as $input ) {
                    if ( isset( $_POST[$input] ) ) {
                        $_value = sanitize_text_field( $_POST[$input] );

                        update_post_meta( $_part_id, $_addition . '_' . $input, $_value );
                    } //end if
                } //end foreach
                $success = 'YES';
                $message = esc_html__( 'Record updated!', 'computer-repair-shop' );
            } //end if
        }

        $values['message'] = $message;
        $values['success'] = $success;
        wp_send_json( $values );
        wp_die();
    }

	//part_id or post_id, $_part
	function add_sub_part( $part_id, $_part ) {
		if ( empty( $part_id ) || empty( $_part ) ) {
			return '';
		}

		//Let's get saved parts first
		$sub_parts = get_post_meta( $part_id, '_sub_parts_arr', true );

		if ( empty( $sub_parts ) ) {
			$sub_parts = array( $_part );
		} else {
			if ( is_array( $sub_parts ) ) {
				if ( ! in_array( $_part, $sub_parts ) ) {
					$sub_parts[] = $_part;
				} else {
					//Do nothing
				}
			}
		}
		update_post_meta( $part_id, '_sub_parts_arr', $sub_parts );
	}

	function wc_parts_price_by_device( $post, $_partID ) {
		$thePostID = $post;
		settings_errors();
		
		$_Vpart = ( $_partID == 'default' ) ? '' : $_partID;

		$output = '';

		if ( empty( $thePostID ) ) :
			$output = esc_html__( 'Part ID could not be found, please publish or update post to get price settings', 'computer-repair-shop' );
		else :
			$thePrice = get_post_meta( $thePostID, $_Vpart . '_price', true );

			if ( empty( $thePrice ) ) {
				$output .= '<div class="updatepricing_'. esc_html( $_partID ) .'"></div>
							<button class="button button-primary button-large btn-fullwidth mb-twenty" id="WCRB_update_default_prices" data-part-identifier="'. esc_html( $_partID ) .'" data-job-id="' . esc_html( $thePostID ) . '">' . esc_html__( 'Update default values for all devices', 'computer-repair-shop' ) . '</button>';
			}

			$output .= '<div class="partsPricings '. esc_html( $_partID ) .'">' . esc_html__( 'Please note If set deivce price that will override manufacture price, if set manufacture price that will override type price. And if only type price is set that will apply to all devices related to that type or manufacture', 'computer-repair-shop' );

			$output .= '<div class="prices_message_'. esc_html( $_partID ) .'"></div>';

			$output .= wp_nonce_field( 'wcrb_nonce_setting_device', 'wcrb_nonce_setting_device_field', true, false );

			$output .= '<ul class="accordion" id="'. esc_html( $_partID ) .'" data-accordion data-allow-all-closed="true">';
			$output .= $this->return_device_type_accordion_price( $thePostID, $_partID );
			$output .= $this->return_device_brand_accordion_price( $thePostID, $_partID );
			$output .= $this->return_device_accordion_price( $thePostID, $_partID );

			$output .= '</ul></div>';
		endif;

		return $output;
	}

	function return_device_type_accordion_price( $thePostID, $_partID ) {
		if ( empty( $thePostID ) ) {
			return '';
		}

        $_partID = ( empty( $_partID ) ) ? 'default' : $_partID;

		$wc_device_type_label_plural = ( empty( get_option( 'wc_device_type_label_plural' ) ) ) ? esc_html__( 'Device Type', 'computer-repair-shop' ) : get_option( 'wc_device_type_label_plural' );
		$thePrice             = get_post_meta( $thePostID, '_price', true );
        $theStockCode         = get_post_meta( $thePostID, '_stock_code', true );
		$theManufacturingCode = get_post_meta( $thePostID, '_manufacturing_code', true );

		if ( $_partID != 'default' ) {
			$thePrice             = get_post_meta( $thePostID, $_partID . '_price', true );
			$theStockCode         = get_post_meta( $thePostID, $_partID . '_stock_code', true );
			$theManufacturingCode = get_post_meta( $thePostID, $_partID . '_manufacturing_code', true );
		}
		
		$output = '<li class="accordion-item" data-accordion-item>';
		$output .= '<a href="#" class="accordion-title">';
		$output .= esc_html__( 'Set price by ', 'computer-repair-shop' ) . ' ' . $wc_device_type_label_plural;		
		$output .= '</a>';

		$output .= '<div class="accordion-content" data-tab-content>';
		
		$output .= esc_html__( 'Update prices to apply to related devices', 'computer-repair-shop' );
		
		$output .= '<div class="button-right">
                        <button id="WCRB_submit_type_part_prices'. esc_html( $_partID ) .'" data-part-identifier="'. esc_html( $_partID ) .'" data-job-id="' . esc_html( $thePostID ) . '" class="button button-primary button-large preview WCRB_submit_type_part_prices">' . esc_html__( 'Update Prices', 'computer-repair-shop' ) . '</button></div>';

		$table_head = '<table class="wp-list-table widefat fixed striped posts"><thead>';
		$table_head .= '<tr>';
		$table_head .= '<th class="id_head">' . esc_html__( 'ID', 'computer-repair-shop' ) . '</th>';
		$table_head .= '<th>' . esc_html__( 'Name', 'computer-repair-shop' ) . '</th>';
		$table_head .= '<th>' . esc_html__( 'Manufacturing Code', 'computer-repair-shop' ) . '</th>';
		$table_head .= '<th>' . esc_html__( 'Stock Code', 'computer-repair-shop' ) . '</th>';
		$table_head .= '<th class="price_head">' . esc_html__( 'Price', 'computer-repair-shop' ) . '</th>';
		$table_head .= '<th class="action_head">';
		$table_head .= '<input type="checkbox" id="select_all_types_' . esc_attr( $_partID ) . '" class="select-all-checkbox" data-target="' . esc_attr( $_partID ) . '_type_status" /> ';
		$table_head .= esc_html__( 'Active', 'computer-repair-shop' );
		$table_head .= '</th>';
		$table_head .= '</tr>';
		$table_head .= '</thead>';

		$table_foot = '</table>';

		$wcrb_type = 'rep_devices';
		$wcrb_tax  = 'device_type';

		$cat_terms = get_terms(
			array(
					'taxonomy'	 => $wcrb_tax,
					'hide_empty' => false,
					'orderby'    => 'name',
					'order'      => 'ASC',
					'number'     => 0, //Returns all
				)
		);

		if( $cat_terms ) :
			$table_body = '<tbody>';
			foreach( $cat_terms as $term ) :
				$termPriceSet 	         = get_post_meta( $thePostID, 'type_price_' . esc_html( $_partID ) . '_' . esc_html( $term->term_id ), true );
                $theStockCodeSet         = get_post_meta( $thePostID, 'type_stock_code_' . esc_html( $_partID ) . '_' . esc_html( $term->term_id ), true );
                $theManufacturingCodeSet = get_post_meta( $thePostID, 'type_manufacturing_code_' . esc_html( $_partID ) . '_' . esc_html( $term->term_id ), true );
                $termPriceStatus         = get_post_meta( $thePostID, 'type_status_' . esc_html( $_partID ) . '_' . esc_html( $term->term_id ), true );

				$checked = ( $termPriceStatus == 'inactive' ) ? '' : 'checked';

				$thePrice = ( empty( $termPriceSet ) ) ? $thePrice : $termPriceSet;
				$thePrice = ( empty( $thePrice ) ) ? '0.00' : $thePrice;

                $theStockCode           = ( empty( $theStockCodeSet ) ) ? $theStockCode : $theStockCodeSet;
                $theManufacturingCode   = ( empty( $theManufacturingCodeSet ) ) ? $theManufacturingCode : $theManufacturingCodeSet;

				$table_body .= '<tr>';
				$table_body .= '<td><input type="hidden" name="'. esc_html( $_partID ) .'_type_id[]" value="' . esc_html( $term->term_id ) . '" /> ' . esc_html( $term->term_id ) . '</td>';
				$table_body .= '<td> ' . esc_html( $term->name ) . '</td>';
				$table_body .= '<td><input type="text" class="regular-text" name="'. esc_html( $_partID ) .'_type_manufacturing_code[]" step="any" value="'.esc_attr( $theManufacturingCode ). '" /></td>';
                $table_body .= '<td><input type="text" class="regular-text" name="'. esc_html( $_partID ) .'_type_stock_code[]" step="any" value="'.esc_attr( $theStockCode ). '" /></td>';
                $table_body .= '<td><input type="number" class="regular-text" name="'. esc_html( $_partID ) .'_type_price[]" step="any" value="'.esc_attr( $thePrice ). '" /></td>';
				$table_body .= '<td><input type="checkbox" name="'. esc_html( $_partID ) .'_type_status[]" value="' . esc_html( $term->term_id ) . '" ' . esc_attr( $checked ) . ' /></td>';
				$table_body .= '</tr>';
			endforeach;
			$table_body .= '<tbody>';
		endif;

		if ( isset( $table_body ) ) {
			$output .= $table_head;
			$output .= $table_body;
			$output .= $table_foot;
		} else {
			$output .= esc_html__( 'Add some', 'computer-repair-shop' ) .' ' . $wc_device_type_label_plural . esc_html__( 'to set prices', 'computer-repair-shop' )  . '<a target="_blank" href="edit-tags.php?taxonomy=device_type&post_type=rep_devices">'. esc_html__( 'Add some', 'computer-repair-shop' ) . ' ' . $wc_device_type_label_plural .'</a>';
		}

		$output .= '</div></li>';
			
		return $output;
	}

	function return_device_brand_accordion_price( $thePostID, $_partID ) {
		if ( empty( $thePostID ) ) {
			return '';
		}

        $_partID = ( empty( $_partID ) ) ? 'default' : $_partID;

		$wc_device_brand_label_plural = ( empty( get_option( 'wc_device_brand_label_plural' ) ) ) ? esc_html__( 'Device Brand', 'computer-repair-shop' ) : get_option( 'wc_device_brand_label_plural' );
		$thePrice             = get_post_meta( $thePostID, '_price', true );
        $theStockCode         = get_post_meta( $thePostID, '_stock_code', true );
		$theManufacturingCode = get_post_meta( $thePostID, '_manufacturing_code', true );

		if ( $_partID != 'default' ) {
			$thePrice             = get_post_meta( $thePostID, $_partID . '_price', true );
			$theStockCode         = get_post_meta( $thePostID, $_partID . '_stock_code', true );
			$theManufacturingCode = get_post_meta( $thePostID, $_partID . '_manufacturing_code', true );
		}
		
		$output = '<li class="accordion-item" data-accordion-item>';
		$output .= '<a href="#" class="accordion-title">';
		$output .= esc_html__( 'Set price by ', 'computer-repair-shop' ) . ' ' . $wc_device_brand_label_plural;		
		$output .= '</a>';

		$output .= '<div class="accordion-content" data-tab-content>';
		
		$output .= esc_html__( 'Update prices to apply to related devices', 'computer-repair-shop' );
		
		$output .= '<div class="button-right">
                <button id="WCRB_submit_brand_part_prices'. esc_html( $_partID ) .'" data-part-identifier="'. esc_html( $_partID ) .'" data-job-id="' . esc_html( $thePostID ) . '" 
		class="button button-primary button-large preview WCRB_submit_brand_part_prices">' . esc_html__( 'Update Prices', 'computer-repair-shop' ) . '</button></div>';

		$table_head = '<table class="wp-list-table widefat fixed striped posts"><thead>';
		$table_head .= '<tr>';
		$table_head .= '<th class="id_head">' . esc_html__( 'ID', 'computer-repair-shop' ) . '</th>';
		$table_head .= '<th>' . esc_html__( 'Name', 'computer-repair-shop' ) . '</th>';
		$table_head .= '<th>' . esc_html__( 'Manufacturing Code', 'computer-repair-shop' ) . '</th>';
		$table_head .= '<th>' . esc_html__( 'Stock Code', 'computer-repair-shop' ) . '</th>';
		$table_head .= '<th class="price_head">' . esc_html__( 'Price', 'computer-repair-shop' ) . '</th>';
		$table_head .= '<th class="action_head">';
		$table_head .= '<input type="checkbox" id="select_all_brands_' . esc_attr( $_partID ) . '" class="select-all-checkbox" data-target="' . esc_attr( $_partID ) . '_brand_status" /> ';
		$table_head .= esc_html__( 'Active', 'computer-repair-shop' );
		$table_head .= '</th>';
		$table_head .= '</tr>';
		$table_head .= '</thead>';

		$table_foot = '</table>';

		$wcrb_type = 'rep_devices';
		$wcrb_tax  = 'device_brand';

		$cat_terms = get_terms(
			array(
					'taxonomy'	 => $wcrb_tax,
					'hide_empty' => false,
					'orderby'    => 'name',
					'order'      => 'ASC',
					'number'     => 0, //Returns all
				)
		);

		if( $cat_terms ) :
			$table_body = '<tbody>';
			foreach( $cat_terms as $term ) :
                $termPriceSet 	         = get_post_meta( $thePostID, 'brand_price_' . esc_html( $_partID ) . '_' . esc_html( $term->term_id ), true );
                $theStockCodeSet         = get_post_meta( $thePostID, 'brand_stock_code_' . esc_html( $_partID ) . '_' . esc_html( $term->term_id ), true );
                $theManufacturingCodeSet = get_post_meta( $thePostID, 'brand_manufacturing_code_' . esc_html( $_partID ) . '_' . esc_html( $term->term_id ), true );
                $termPriceStatus         = get_post_meta( $thePostID, 'brand_status_' . esc_html( $_partID ) . '_' . esc_html( $term->term_id ), true );

				$checked = ( $termPriceStatus == 'inactive' ) ? '' : 'checked';

				$thePrice = ( empty( $termPriceSet ) ) ? $thePrice : $termPriceSet;
				$thePrice = ( empty( $thePrice ) ) ? '0.00' : $thePrice;

                $theStockCode           = ( empty( $theStockCodeSet ) ) ? $theStockCode : $theStockCodeSet;
                $theManufacturingCode   = ( empty( $theManufacturingCodeSet ) ) ? $theManufacturingCode : $theManufacturingCodeSet;

				$table_body .= '<tr>';
				$table_body .= '<td><input type="hidden" name="'. esc_html( $_partID ) .'_brand_id[]" value="' . esc_html( $term->term_id ) . '" /> ' . esc_html( $term->term_id ) . '</td>';
				$table_body .= '<td> ' . $term->name . '</td>';
				$table_body .= '<td><input type="text" class="regular-text" name="'. esc_html( $_partID ) .'_brand_manufacturing_code[]" step="any" value="'.esc_attr( $theManufacturingCode ). '" /></td>';
                $table_body .= '<td><input type="text" class="regular-text" name="'. esc_html( $_partID ) .'_brand_stock_code[]" step="any" value="'.esc_attr( $theStockCode ). '" /></td>';
                $table_body .= '<td><input type="number" class="regular-text" name="'. esc_html( $_partID ) .'_brand_price[]" step="any" value="'.esc_attr( $thePrice ). '" /></td>';
				$table_body .= '<td><input type="checkbox" name="'. esc_html( $_partID ) .'_brand_status[]" value="' . esc_html( $term->term_id ) . '" ' . esc_attr( $checked ) . ' /></td>';
				$table_body .= '</tr>';
			endforeach;
			$table_body .= '<tbody>';
		endif;

		if ( isset( $table_body ) ) {
			$output .= $table_head;
			$output .= $table_body;
			$output .= $table_foot;
		} else {
			$output .= esc_html__( 'Add some', 'computer-repair-shop' ) .' ' . $wc_device_brand_label_plural . esc_html__( 'to set prices', 'computer-repair-shop' )  . 
			'<a target="_blank" href="edit-tags.php?taxonomy=device_brand&post_type=rep_devices">'. esc_html__( 'Add some', 'computer-repair-shop' ) . ' ' . $wc_device_brand_label_plural .'</a>';
		}

		$output .= '</div></li>';
			
		return $output;
	}
	
	function return_device_accordion_price( $thePostID, $_partID ) {
		if ( empty( $thePostID ) ) {
			return '';
		}

        $_partID = ( empty( $_partID ) ) ? 'default' : $_partID;

		$wc_device_label_plural = ( empty( get_option( 'wc_device_label_plural' ) ) ) ? esc_html__( 'Device', 'computer-repair-shop' ) : get_option( 'wc_device_label_plural' );
		$thePrice             = get_post_meta( $thePostID, '_price', true );
        $theStockCode         = get_post_meta( $thePostID, '_stock_code', true );
		$theManufacturingCode = get_post_meta( $thePostID, '_manufacturing_code', true );
		
		$output = '<li class="accordion-item" data-accordion-item>';
		$output .= '<a href="#" class="accordion-title">';
		$output .= esc_html__( 'Set price by ', 'computer-repair-shop' ) . ' ' . $wc_device_label_plural;		
		$output .= '</a>';

		$output .= '<div class="accordion-content" data-tab-content>';
		
		$output .= esc_html__( 'You may update manufacutre or type prices to apply to related devices as well.', 'computer-repair-shop' );
		
		$output .= '<div class="button-right">
                    <button id="WCRB_submit_device_part_prices'. esc_html( $_partID ) .'" data-part-identifier="'. esc_html( $_partID ) .'" data-job-id="' . esc_html( $thePostID ) . '" 
		class="button button-primary button-large preview WCRB_submit_device_part_prices">' . esc_html__( 'Update Prices', 'computer-repair-shop' ) . '</button></div>';

		$table_head = '<table class="wp-list-table widefat fixed striped posts reloadthedevices '. esc_html( $_partID ) .'"><thead>';
		$table_head .= '<tr>';
		$table_head .= '<th class="id_head">' . esc_html__( 'ID', 'computer-repair-shop' ) . '</th>';
		$table_head .= '<th>' . esc_html__( 'Name', 'computer-repair-shop' ) . '</th>';
		$table_head .= '<th>' . esc_html__( 'Manufacturing Code', 'computer-repair-shop' ) . '</th>';
		$table_head .= '<th>' . esc_html__( 'Stock Code', 'computer-repair-shop' ) . '</th>';
		$table_head .= '<th class="price_head">' . esc_html__( 'Price', 'computer-repair-shop' ) . '</th>';
		$table_head .= '<th class="action_head">';
		$table_head .= '<input type="checkbox" id="select_all_devices_' . esc_attr( $_partID ) . '" class="select-all-checkbox" data-target="' . esc_attr( $_partID ) . '_device_status" /> ';
		$table_head .= esc_html__( 'Active', 'computer-repair-shop' );
		$table_head .= '</th>';
		$table_head .= '</thead>';

		$table_foot = '</table>';

		$wcrb_type = 'rep_devices';
		$wcrb_tax  = 'device_brand';

		$device_args = array(
			'post_type' 		=> $wcrb_type,
			'orderby'			=> 'title',
			'order' 			=> 'ASC',
			'posts_per_page' 	=> -1,
		);

		$device_query = new WP_Query( $device_args );

		if ( $device_query->have_posts() ) { 
			$table_body = '<tbody>';
			while( $device_query->have_posts() ) {
				$device_query->the_post();

				$device_id 	  = $device_query->post->ID;
				$device_title  = get_the_title();

				$returned = $this->get_price_by_device_for_part( $device_id, $thePostID, $_partID );
                
                $_theprice = $_thestockcode = $_themanufacturingcode = '';
                if ( is_array( $returned ) ) {
                    $_theprice            = $returned['price'];
                    $_thestockcode         = $returned['stock_code'];
                    $_themanufacturingcode = $returned['manufacturing_code'];
					$termPriceStatus 	   = $returned['status'];
                }
				$checked = ( $termPriceStatus == 'inactive' ) ? '' : 'checked';

				$_theprice = ( empty( $_theprice ) ) ? '0.00' : $_theprice;

				$table_body .= '<tr>';
				$table_body .= '<td><input type="hidden" name="'. esc_html( $_partID ) .'_device_id[]" value="' . esc_html( $device_id ) . '" /> ' . esc_html( $device_id ) . '</td>';
				$table_body .= '<td> ' . esc_html( $device_title ) . '</td>';
                $table_body .= '<td><input type="text" class="regular-text" name="'. esc_html( $_partID ) .'_device_manufacturing_code[]" step="any" value="'.esc_attr( $_themanufacturingcode ). '" /></td>';
                $table_body .= '<td><input type="text" class="regular-text" name="'. esc_html( $_partID ) .'_device_stock_code[]" step="any" value="'.esc_attr( $_thestockcode ). '" /></td>';
				$table_body .= '<td><input type="number" class="regular-text" name="'. esc_html( $_partID ) .'_device_price[]" step="any" value="'.esc_attr( $_theprice ). '" /></td>';
				$table_body .= '<td><input type="checkbox" name="'. esc_html( $_partID ) .'_device_status[]" value="' . esc_html( $device_id ) . '" ' . esc_attr( $checked ) . ' /></td>';
				$table_body .= '</tr>';
			}
			$table_body .= '<tbody>';
		}
		wp_reset_postdata();
		if ( isset( $table_body ) ) {
			$output .= $table_head;
			$output .= $table_body;
			$output .= $table_foot;
		} else {
			$output .= esc_html__( 'Add some', 'computer-repair-shop' ) .' ' . $wc_device_label_plural . esc_html__( 'to set prices', 'computer-repair-shop' )  . 
			'<a target="_blank" href="edit.php?post_type=rep_devices">'. esc_html__( 'Add some', 'computer-repair-shop' ) . ' ' . $wc_device_label_plural .'</a>';
		}
		$output .= '</div></li>';
			
		return $output;
	}

    //Function returns array with keys price, stock_code, manufacturing_code
    function get_price_by_device_for_part( $device_id, $part_id, $_partID ) {
		if ( empty( $part_id ) ) {
			return '';
		}
        $_partID = ( empty( $_partID ) ) ? 'default' : $_partID;

		$_default_price             = get_post_meta( $part_id, '_price', true );
        $_default_StockCode         = get_post_meta( $part_id, '_stock_code', true );
		$_default_ManufacturingCode = get_post_meta( $part_id, '_manufacturing_code', true );
		$_default_status 			= 'active';

		if ( $_partID != 'default' ) {
			$_default_price             = get_post_meta( $part_id, $_partID . '_price', true );
			$_default_StockCode         = get_post_meta( $part_id, $_partID . '_stock_code', true);
			$_default_ManufacturingCode = get_post_meta( $part_id, $_partID . '_manufacturing_code', true);
		}

		//Get Types if not manufactures
		if ( ! empty( $device_id ) ) :
			$type_id   	= ( ! empty( $device_id ) ) ? wcrb_return_device_terms( $device_id, 'device_type' ) : '';
			
			if ( ! empty( $type_id ) ) {
				$termPriceSet            = get_post_meta( $part_id, 'type_price_' . esc_html( $_partID ) . '_' . esc_html( $type_id ), true );
				$theStockCodeSet         = get_post_meta( $part_id, 'type_stock_code_' . esc_html( $_partID ) . '_' . esc_html( $type_id ), true );
				$theManufacturingCodeSet = get_post_meta( $part_id, 'type_manufacturing_code_' . esc_html( $_partID ) . '_' . esc_html( $type_id ), true );
				$theTypeStatusSet		= get_post_meta( $part_id, 'type_status_' . esc_html( $_partID ) . '_' . esc_html( $type_id ), true );
			}
			$_default_price             = ( empty( $termPriceSet ) ) ? $_default_price : $termPriceSet;
			$_default_StockCode         = ( empty( $theStockCodeSet ) ) ? $_default_StockCode : $theStockCodeSet;
			$_default_ManufacturingCode = ( empty( $theManufacturingCodeSet ) ) ? $_default_ManufacturingCode : $theManufacturingCodeSet;
			$_default_status 			= ( empty( $theTypeStatusSet ) ) ? $_default_status : $theTypeStatusSet;

			//Get Manufactures
			$manufacture_id  = ( ! empty( $device_id ) ) ? wcrb_return_device_terms( $device_id, 'device_brand' ) : '';

			if ( ! empty( $manufacture_id ) ) {
				$termPriceSet            = get_post_meta( $part_id, 'brand_price_' . esc_html( $_partID ) . '_' . esc_html( $manufacture_id ), true );
				$theStockCodeSet         = get_post_meta( $part_id, 'brand_stock_code_' . esc_html( $_partID ) . '_' . esc_html( $manufacture_id ), true );
				$theManufacturingCodeSet = get_post_meta( $part_id, 'brand_manufacturing_code_' . esc_html( $_partID ) . '_' . esc_html( $manufacture_id ), true );
				$theBrandStatus			= get_post_meta( $part_id, 'brand_status_' . esc_html( $_partID ) . '_' . esc_html( $manufacture_id ), true ); //CheckCheck
			}
			$_default_price             = ( empty( $termPriceSet ) ) ? $_default_price : $termPriceSet;
			$_default_StockCode         = ( empty( $theStockCodeSet ) ) ? $_default_StockCode : $theStockCodeSet;
			$_default_ManufacturingCode = ( empty( $theManufacturingCodeSet ) ) ? $_default_ManufacturingCode : $theManufacturingCodeSet;
			$_default_status 			= ( empty( $theBrandStatus ) ) ? $_default_status : $theBrandStatus;

			//Get Device price
			$termPriceSet               = get_post_meta( $part_id, 'device_price_' . esc_html( $_partID ) . '_'  . esc_html( $device_id ), true );
			$theStockCodeSet            = get_post_meta( $part_id, 'device_stock_code_' . esc_html( $_partID ) . '_'  . esc_html( $device_id ), true );
			$theManufacturingCodeSet    = get_post_meta( $part_id, 'device_manufacturing_code_' . esc_html( $_partID ) . '_'  . esc_html( $device_id ), true );
			$theDeviceStatus			 = get_post_meta( $part_id, 'device_status_' . esc_html( $_partID ) . '_'  . esc_html( $device_id ), true );//CheckCheck

			$_default_price             = ( empty( $termPriceSet ) ) ? $_default_price : $termPriceSet;
			$_default_StockCode         = ( empty( $theStockCodeSet ) ) ? $_default_StockCode : $theStockCodeSet;
			$_default_ManufacturingCode = ( empty( $theManufacturingCodeSet ) ) ? $_default_ManufacturingCode : $theManufacturingCodeSet;
			$_default_status 			= ( empty( $theDeviceStatus ) ) ? $_default_status : $theDeviceStatus;
		endif;

        $_return_array = array(
            'price' => $_default_price,
            'stock_code' => $_default_StockCode,
            'manufacturing_code' => $_default_ManufacturingCode,
			'status' => $_default_status
        );
		return $_return_array;
	}

	// Add this to your existing JavaScript or create a new method
	public function add_select_all_javascript() {
		// Only load on post editing screens for rep_products
		global $pagenow, $post_type;
		
		if (($pagenow === 'post.php' || $pagenow === 'post-new.php') && $post_type === 'rep_products') {
			?>
			<script type="text/javascript">
			jQuery(document).ready(function($) {
				// Select All functionality for device pricing tables
				function initSelectAllFunctionality() {
					$('.select-all-checkbox').off('change').on('change', function() {
						var target = $(this).data('target');
						var isChecked = $(this).is(':checked');
						
						// Find all checkboxes with name containing the target in the same table
						$(this).closest('table').find('input[name*="' + target + '"]').prop('checked', isChecked);
					});
					
					// Individual checkbox behavior - update Select All status
					$('input[type="checkbox"][name*="_status"]').off('change').on('change', function() {
						var nameAttr = $(this).attr('name');
						var baseName = nameAttr.split('[')[0]; // Get the base name without brackets
						
						// Find the corresponding Select All checkbox in the same table
						var selectAllCheckbox = $(this).closest('table').find('.select-all-checkbox[data-target="' + baseName + '"]');
						
						if (selectAllCheckbox.length) {
							// Check if all checkboxes are checked in this table
							var allChecked = true;
							$(this).closest('table').find('input[name*="' + baseName + '"]').each(function() {
								if (!$(this).is(':checked')) {
									allChecked = false;
									return false; // Break out of the loop
								}
							});
							
							// Update Select All checkbox
							selectAllCheckbox.prop('checked', allChecked);
						}
					});
				}
				
				// Initialize on page load
				initSelectAllFunctionality();
				
				// Re-initialize when accordions are opened (Foundation accordion)
				$(document).on('opened.zf.accordion', function() {
					setTimeout(function() {
						initSelectAllFunctionality();
					}, 100);
				});
			});
			</script>
			<?php
		}
	}
}