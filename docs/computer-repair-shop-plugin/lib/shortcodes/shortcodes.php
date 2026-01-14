<?php
    //Include Shortcode Files.

    require_once WC_COMPUTER_REPAIR_SHOP_DIR . 'lib' . DS . 'shortcodes' . DS . 'list_products.php';
    require_once WC_COMPUTER_REPAIR_SHOP_DIR . 'lib' . DS . 'shortcodes' . DS . 'list_services.php';
    require_once WC_COMPUTER_REPAIR_SHOP_DIR . 'lib' . DS . 'shortcodes' . DS . 'order_status.php';
    require_once WC_COMPUTER_REPAIR_SHOP_DIR . 'lib' . DS . 'shortcodes' . DS . 'request_quote.php';
    require_once WC_COMPUTER_REPAIR_SHOP_DIR . 'lib' . DS . 'shortcodes' . DS . 'my_account.php';
    require_once WC_COMPUTER_REPAIR_SHOP_DIR . 'lib' . DS . 'shortcodes' . DS . 'book_my_service.php';
    require_once WC_COMPUTER_REPAIR_SHOP_DIR . 'lib' . DS . 'shortcodes' . DS . 'type_grouped_service.php';
    require_once WC_COMPUTER_REPAIR_SHOP_DIR . 'lib' . DS . 'shortcodes' . DS . 'book_device_and_services.php';
    require_once WC_COMPUTER_REPAIR_SHOP_DIR . 'lib' . DS . 'shortcodes' . DS . 'wc_book_my_warranty.php';

/**
 * Start Job
 * Front End
 *
 * Selecting Device
 * @popup
 * @Since 3.53
 */
require_once WC_COMPUTER_REPAIR_SHOP_DIR . 'lib' . DS . 'shortcodes' . DS . 'start_job_by_device.php';


if ( ! function_exists( 'wc_comp_rep_register_foundation' ) ) :
    /**
     * Register Scripts
     * Register Styles
     * 
     * To Enque within Shortcodes 
     */
    function wc_comp_rep_register_foundation() {
        
        // enqueue foundation.min.
		wp_register_style( 'foundation-css', WC_COMPUTER_REPAIR_DIR_URL . '/assets/css/foundation.min.css', array(), '6.5.3', 'all' );
		wp_register_style( 'plugin-styles-wc', WC_COMPUTER_REPAIR_DIR_URL . '/assets/css/style.css', array(), WC_CR_SHOP_VERSION, 'all' );

        wp_register_script( 'foundation-js', WC_COMPUTER_REPAIR_DIR_URL . '/assets/admin/js/foundation.min.js', array( 'jquery' ), '6.5.3', true );
        wp_register_script( 'wc-cr-js', WC_COMPUTER_REPAIR_DIR_URL . '/assets/js/wc_cr_scripts.js', array( 'jquery' ), WC_CR_SHOP_VERSION, true );

        wp_register_style( 'select2', WC_COMPUTER_REPAIR_DIR_URL . '/assets/admin/css/select2.min.css', array(),'4.0.13','all' );
        wp_register_script( 'select2', WC_COMPUTER_REPAIR_DIR_URL . '/assets/admin/js/select2.min.js', array( 'jquery' ),  '4.0.13', true );

        //intl-tel-input
        wp_register_script( 'intl-tel-input', WC_COMPUTER_REPAIR_DIR_URL . '/assets/vendors/intl-tel-input/js/intlTelInputWithUtils.min.js', array( 'jquery' ), '23.1.0', true );
        wp_register_style( 'intl-tel-input', WC_COMPUTER_REPAIR_DIR_URL . '/assets/vendors/intl-tel-input/css/intlTelInput.min.css', array(),'23.1.0','all' );

        //Ajax Scripts
        wp_enqueue_script( 'ajax_script', WC_COMPUTER_REPAIR_DIR_URL . '/assets/js/ajax_scripts.js', array( 'jquery' ), WC_CR_SHOP_VERSION, true );
		wp_localize_script( 'ajax_script', 'ajax_obj', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

        $post_types = array( 'rep_devices', 'rep_devices_other', 'rep_jobs', 'rep_products', 'rep_services', 'rep_reviews' );
        if ( in_array( get_post_type(), $post_types ) || isset( $_GET['review_id'] ) || isset( $_GET['case_number'] ) ) {
            wp_enqueue_style( 'foundation-css');
            wp_enqueue_style( 'plugin-styles-wc' );
			wp_enqueue_script("foundation-js");
			wp_enqueue_script("wc-cr-js");
			wp_enqueue_script("select2");
		}
    }// adding styles and scripts for wordpress admin.
    add_action( 'wp_enqueue_scripts', 'wc_comp_rep_register_foundation' );
endif;

if ( ! function_exists( 'wcrb_intl_tel_input_script' ) ) : 
    function wcrb_intl_tel_input_script() { ?>
        <script defer type="text/javascript">
			jQuery(document).ready(function(){
                const input = document.querySelector('input[name="phoneNumber_ol"]');
                
                if (input) {
                    // Store original value for backup
                    const originalValue = input.value;
                    
                    const iti = window.intlTelInput(input, {
                        <?php 
                            $_country = ( ! empty( get_option( 'wc_primary_country' ) ) ) ? strtolower( get_option( 'wc_primary_country' ) ) : "us";
                            $_lang = explode( '-', get_bloginfo( 'language' ) );
                            $_lang = $_lang[0];
                        ?>
                        i18n: "<?php echo esc_attr( $_lang ); ?>",
                        initialCountry: "<?php echo esc_attr( $_country ); ?>",
                        // Use your existing field name
                        hiddenInput: () => ({
                            phone: "phoneNumber",  // This creates hidden field named "phoneNumber"
                            country: "country_code"
                        }),
                        separateDialCode: true,
                        utilsScript: "<?php echo esc_url( WC_COMPUTER_REPAIR_DIR_URL ); ?>/assets/vendors/intl-tel-input/js/utils.js?1716383386062"
                    });

                    // Initialize with existing value
                    if (originalValue) {
                        iti.setNumber(originalValue);
                    }

                    // Find the form
                    const form = input.closest('form');
                    
                    // Create hidden field if it doesn't exist
                    if (form) {
                        let hiddenPhoneInput = form.querySelector('input[name="phoneNumber"]');
                        if (!hiddenPhoneInput) {
                            hiddenPhoneInput = document.createElement('input');
                            hiddenPhoneInput.type = 'hidden';
                            hiddenPhoneInput.name = 'phoneNumber';
                            form.appendChild(hiddenPhoneInput);
                        }
                        
                        // Also create country code field if needed
                        let countryInput = form.querySelector('input[name="country_code"]');
                        if (!countryInput) {
                            countryInput = document.createElement('input');
                            countryInput.type = 'hidden';
                            countryInput.name = 'country_code';
                            form.appendChild(countryInput);
                        }
                        
                        // Initialize hidden field with current value
                        if (iti.isValidNumber()) {
                            hiddenPhoneInput.value = iti.getNumber();
                            countryInput.value = iti.getSelectedCountryData().iso2;
                        } else if (originalValue) {
                            // If not valid but has original value, use that
                            hiddenPhoneInput.value = originalValue;
                        }
                    }

                    // Update hidden field on input change
                    input.addEventListener('change', function() {
                        if (iti.isValidNumber()) {
                            const phoneNumber = iti.getNumber();
                            const countryData = iti.getSelectedCountryData();
                            
                            // Update the visible input with national format
                            input.value = iti.getNumber(intlTelInputUtils.numberFormat.NATIONAL);
                            
                            // Update hidden fields
                            if (form) {
                                const hiddenPhoneInput = form.querySelector('input[name="phoneNumber"]');
                                const countryInput = form.querySelector('input[name="country_code"]');
                                
                                if (hiddenPhoneInput) {
                                    hiddenPhoneInput.value = phoneNumber;
                                }
                                if (countryInput) {
                                    countryInput.value = countryData.iso2;
                                }
                            }
                        }
                    });

                    // Also update on blur (when user leaves the field)
                    input.addEventListener('blur', function() {
                        if (iti.isValidNumber()) {
                            const phoneNumber = iti.getNumber();
                            const countryData = iti.getSelectedCountryData();
                            
                            if (form) {
                                const hiddenPhoneInput = form.querySelector('input[name="phoneNumber"]');
                                const countryInput = form.querySelector('input[name="country_code"]');
                                
                                if (hiddenPhoneInput) {
                                    hiddenPhoneInput.value = phoneNumber;
                                }
                                if (countryInput) {
                                    countryInput.value = countryData.iso2;
                                }
                            }
                        }
                    });

                    // Update before form submission
                    const formElement = input.closest('form');
                    if (formElement) {
                        formElement.addEventListener('submit', function(e) {
                            if (iti.isValidNumber()) {
                                const phoneNumber = iti.getNumber();
                                const countryData = iti.getSelectedCountryData();
                                
                                // Update hidden fields before submission
                                const hiddenPhoneInput = formElement.querySelector('input[name="phoneNumber"]');
                                const countryInput = formElement.querySelector('input[name="country_code"]');
                                
                                if (hiddenPhoneInput) {
                                    hiddenPhoneInput.value = phoneNumber;
                                }
                                if (countryInput) {
                                    countryInput.value = countryData.iso2;
                                }
                            } else {
                                // If invalid, use the visible input value
                                const hiddenPhoneInput = formElement.querySelector('input[name="phoneNumber"]');
                                if (hiddenPhoneInput) {
                                    hiddenPhoneInput.value = input.value;
                                }
                            }
                        });
                    }
                }
			});
        </script>
    <?php }
endif;

if ( ! function_exists( 'wcrb_intl_tel_input_script_admin' ) ) : 
    function wcrb_intl_tel_input_script_admin() { ?>
        <script type="text/javascript">
            jQuery(document).ready(function(){
                const phoneInputs   = document.querySelectorAll('input.customer_phone_ol');
                const output        = document.querySelector('input[name="customer_phone"]');
                
                phoneInputs.forEach(function(input) {
                    const iti = window.intlTelInput(input, {
                        <?php 
                            $_country = ( ! empty( get_option( 'wc_primary_country' ) ) ) ? strtolower( get_option( 'wc_primary_country' ) ) : "us";

                            $_lang = explode( '-', get_bloginfo( 'language' ) );
                            $_lang = $_lang[0];
                        ?>
                        i18n: "<?php echo esc_attr( $_lang ); ?>",
                        initialCountry: "<?php echo esc_attr( $_country ); ?>",
                        hiddenInput: () => ({ phone: "customer_phone", country: "country_code" }),
                        separateDialCode: true,
                        utilsScript: "<?php echo esc_url( WC_COMPUTER_REPAIR_DIR_URL ); ?>/assets/vendors/intl-tel-input/js/utils.js?1716383386062"
                    });
                    input.addEventListener('blur', function() {
                        jQuery('input[name="customer_phone"]').val(iti.getNumber());
                    });

                    input.onchange = () => {
                        if (!iti.isValidNumber()) {
                            alert( '<?php echo esc_html__( 'Invalid phone number', 'computer-repair-shop' ); ?>' );
                            return false;
                        }
                    };
                });
            });
        </script>
    <?php }
endif;