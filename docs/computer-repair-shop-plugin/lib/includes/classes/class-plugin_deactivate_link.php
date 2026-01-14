<?php
/**
 * Plugin Deactivation Functionality
 *
 * Help setup pages to they can be used in notifications and other items
 *
 * @package computer-repair-shop
 * @version 3.7947
 */

defined( 'ABSPATH' ) || exit;

class PLUGIN_DEACTIVATE_LINK {

	private static $instance = NULL;

	static public function getInstance() {
		if ( self::$instance === NULL )
			self::$instance = new PLUGIN_DEACTIVATE_LINK();
		return self::$instance;
	}

    function __construct() {
        global $pagenow;
        if ( $pagenow == 'plugins.php' ) {
            add_filter( 'plugin_action_links', array( $this, 'wcrb_pro_plugin_links' ), 10, 2 );
            add_action( 'admin_footer', array( $this, 'repairbuddy_deactivate_feedback_popup' ) );
		}
        add_action('wp_ajax_repairbuddy_deactivate_plugin_process', array( $this, 'repairbuddy_deactivate_plugin_process' ));
    }

    function wcrb_pro_plugin_links( $links, $file ) {
		if ( ! wc_rs_license_state() && $file == WCRB_DEFINE_PLUGIN_BASE_FILE && ! defined( 'REPAIRBUDDY_WHITELABEL' ) ) {
			if( isset( $links['deactivate'] ) ) {
				$deactivation_link = $links['deactivate'];
				// Insert an onClick action to allow form before deactivating
				$deactivation_link = str_replace( '<a ',
					'<div class="wcrbfree-deactivate-form-wrapper">
						<span class="wcrbfree-deactivate-form" id="wcrb-deactivate-form-' . esc_attr('RepairBuddyFree') . '"></span>
					</div><a id="wcrb-deactivate-link-' . esc_attr('RepairBuddyFree') . '"', $deactivation_link );
				$links['deactivate'] = $deactivation_link;
			}
			$link = '<a target="_blank" title="' . esc_html__( 'Upgrade To Premium', 'computer-repair-shop' ) . '" href="https://www.webfulcreations.com/products/crm-wordpress-plugin-repairbuddy/?utm_source=freeversion&utm_medium=plugin&utm_campaign=Upgrade+to+Premium&utm_id=repairbuddy_plugin_page" style="font-weight:bold;">' . esc_html__( 'Upgrade To Premium', 'computer-repair-shop' ) . '</a>';
			array_unshift( $links, $link );
		}
		return $links;
	}

    function repairbuddy_deactivate_feedback_popup() { ?>
        <style type="text/css">
            .wcrb-deactivate-btn { display: inline-block; font-weight: 400; text-align: center; white-space; vertical-align: nowrap; user-select: none; border: 1px solid transparent; padding: .375rem .75rem; font-size:1rem; line-height:1.5; border-radius:0.25rem; transition:color .15s }
            .wcrb-deactivate-btn-primary { color: #fff; background-color: #2c304d; border-color:none !important; }
            .wcrb-deactivate-btn:hover { color: #FFF; }                    
            .wcrbfree-deactivate-form-active .wcrbfree-deactivate-form-bg {background: rgba( 0, 0, 0, .5 );position: fixed;top: 0;left: 0;width: 100%;height: 100%; z-index: 99;}
            .wcrbfree-deactivate-form-wrapper {position: relative;z-index: 999;display: none; }
            .wcrbfree-deactivate-form-active .wcrbfree-deactivate-form-wrapper {display: inline-block;}
            .wcrbfree-deactivate-form { display: none; }
            .wcrbfree-deactivate-form-active .wcrbfree-deactivate-form {position: absolute;bottom: 30px;left: 0;max-width: 500px;min-width: 360px;background: #fff;white-space: normal;}
            .wcrbfree-deactivate-form-active .wcrbfree-deactivate-form.wcrb_rtl_enabled {position: absolute;bottom: 30px;left: unset;max-width: 500px;min-width: 360px;background: #fff;white-space: normal;}
            .wcrbfree-deactivate-form-head {background: #2c304d;color: #fff;padding: 8px 18px;}
            .wcrbfree-deactivate-confirm-head p{color: #fff; padding-left:10px}
            .wcrbfree-deactivate-confirm-head{padding: 4px 18px; background:orange; }
            .wcrbfree-deactivate-form-body {padding: 8px 18px 0;color: #444;}
            .wcrbfree-deactivate-form-body label[for="wcrbfree-remove-settings"] {font-weight: bold;}
            .deactivating-spinner {display: none;}
            .deactivating-spinner .spinner {float: none;margin: 4px 4px 0 18px;vertical-align: bottom;visibility: visible;}
            .wcrbfree-deactivate-form-footer {padding: 0 18px 8px;}
            .wcrbfree-deactivate-form-footer label[for="wcrbfree_anonymous"] {visibility: hidden;}
            .wcrbfree-deactivate-form-footer p {display: flex;align-items: center;justify-content: space-between;margin: 0;}
            #wcrbfree-deactivate-submit-form span {display: none;}
            .wcrbfree-deactivate-form.process-response .wcrbfree-deactivate-form-body,.wcrbfree-deactivate-form.process-response .wcrbfree-deactivate-form-footer {position: relative;}
            .wcrbfree-deactivate-form.process-response .wcrbfree-deactivate-form-body:after,.wcrbfree-deactivate-form.process-response .wcrbfree-deactivate-form-footer:after {content: "";display: block;position: absolute;top: 0;left: 0;width: 100%;height: 100%;background-color: rgba( 255, 255, 255, .5 );}
            button#wcrbfree-deactivate-submit-btn{cursor:pointer;}
            button#wcrbfree-deactivate-submit-btn[disabled=disabled] { cursor:not-allowed; opacity: 0.5; }         
            .wcrbfree-confirm-deactivate-wrapper { width:550px; max-width:600px !important; }
            .wcrbfree-confirm-deactivate-wrapper .wcrbfree-deactivate-confirm-head strong { margin-bottom:unset; }
            .wcrbfree-confirm-deactivate-wrapper .wcrbfree-deactivate-confirm-head { display: flex; align-items: center; }
            body.rtl .wcrbfree-deactivate-form-footer p{ justify-content: space-between;}
        </style>

        <?php
        $question_options = array();
        $question_options['list_data_options'] = array(
                                                    'setup-difficult'  => esc_html__( 'Set up is too difficult', 'computer-repair-shop' ),
                                                    'docs-improvement' => esc_html__( 'Lack of documentation', 'computer-repair-shop' ),
                                                    'features'         => esc_html__( 'Not the features I wanted', 'computer-repair-shop' ),
                                                    'better-plugin'    => esc_html__( 'Found a better plugin', 'computer-repair-shop' ),
                                                    'incompatibility'  => esc_html__( 'Incompatible with theme or plugin', 'computer-repair-shop' ),
                                                    'maintenance'      => esc_html__( 'Other', 'computer-repair-shop' ),
                                                );

        $html = '<div class="wcrbfree-deactivate-form-head"><strong>' . esc_html__( 'RepairBuddy Sorry to see you go', 'computer-repair-shop') . '</strong></div>';
        $html .= '<div class="wcrbfree-deactivate-form-body">';

        if ( is_array( $question_options['list_data_options'] ) ) {
            $html .= '<div class="wcrbfree-deactivate-options">';
            $html .= '<p><strong>' . esc_html__( 'Before you deactivate the RepairBuddy plugin, would you quickly give us your reason for doing so?', 'computer-repair-shop' ) . '</strong></p><p>';

            foreach ( $question_options['list_data_options'] as $key => $option ) {
                $html .= '<input type="radio" name="wcrbfree-deactivate-reason" id="' . esc_attr( $key ) . '" value="' . esc_attr( $key ) . '"><label for="' . esc_attr( $key ) . '">' . esc_attr( $option ) . '</label><br>';
            }
            $html .= '</p><label id="wcrbfree-deactivate-details-label" for="wcrbfree-deactivate-details"><strong>' . esc_html__( 'How could we improve?', 'computer-repair-shop' ) . '</strong></label><textarea name="wcrbfree-deactivate-details" id="wcrbfree-deactivate-details" rows="2" style="width:100%"></textarea>';
            $html .= '</div>';
        }
        $html .= '<hr/></div>';
        
        $html .= '<p class="deactivating-spinner"><span class="spinner"></span> ' . esc_html__( 'Submitting form', 'computer-repair-shop' ) . '</p>';
        $html .= '<div class="wcrbfree-deactivate-form-footer"><p>';
        
        $html .= '<label for="wcrbfree_anonymous" title="'
                . esc_html__('If you uncheck this option, then your email address will be sent along with your feedback. This can be used by RepairBuddy to get back to you for more information or a solution.', 'computer-repair-shop')
                . '"><input type="checkbox" name="wcrbfree-deactivate-tracking" id="wcrbfree_anonymous"> ' . esc_html__('Send anonymous', 'computer-repair-shop') . '</label><br>';
        $html .= '<a id="wcrbfree-deactivate-submit-form"  class="wcrb-deactivate-btn wcrb-deactivate-btn-primary" href="#"><span>'
        . esc_html__('Submit and', 'computer-repair-shop').'</span> '.esc_html__( 'Deactivate', 'computer-repair-shop' ) . '</a>';
        $html .= '</p></div>'; 
        ?>

        <div class="wcrbfree-deactivate-form-bg"></div>
           
            <script type="text/javascript">
                jQuery(document).ready(function($){
                    var wcrbfree_deactivateURL = $("#wcrb-deactivate-link-<?php echo esc_attr('RepairBuddyFree'); ?>")
                        wcrbfree_formContainer = $('#wcrb-deactivate-form-<?php echo esc_attr('RepairBuddyFree'); ?>'),
                        wcrbfree_deactivated = true,
                        wcrbfree_detailsStrings = {
                            'setup-difficult' : '<?php echo esc_html__('What was the dificult part?', 'computer-repair-shop'); ?>',
                            'docs-improvement' : '<?php echo esc_html__('What can we describe more?', 'computer-repair-shop'); ?>',
                            'features' : '<?php echo esc_html__('How could we improve?', 'computer-repair-shop'); ?>',
                            'better-plugin' : '<?php echo esc_html__('Can you mention it?', 'computer-repair-shop'); ?>',
                            'incompatibility' : '<?php echo esc_html__('With what plugin or theme is incompatible?', 'computer-repair-shop'); ?>',
                            'maintenance' : '<?php echo esc_html__('Please specify', 'computer-repair-shop'); ?>',
                        };

                    jQuery( wcrbfree_deactivateURL).attr('onclick', "javascript:event.preventDefault();");
                    jQuery( wcrbfree_deactivateURL ).on("click", function() {
                        function wcrbfreeSubmitData(wcrbfree_data, wcrbfree_formContainer) {
                            wcrbfree_data['action']          = 'repairbuddy_deactivate_plugin_process';
                            wcrbfree_data['security']        = '<?php echo esc_html(wp_create_nonce('repairbuddy_deactivate_plugin_process')); ?>';
                            wcrbfree_data['dataType']        = 'json';
                            wcrbfree_formContainer.addClass( 'process-response' );
                            wcrbfree_formContainer.find(".deactivating-spinner").show();
                            jQuery.post(ajaxurl,wcrbfree_data,function(response) {   
                               window.location.href = wcrbfree_url;
                            });
                        }
                        var wcrbfree_url = wcrbfree_deactivateURL.attr( 'href' );
                        jQuery('body').toggleClass('wcrbfree-deactivate-form-active');

                        wcrbfree_formContainer.show({complete: function(){
                            var offset = wcrbfree_formContainer.offset();
                            if( offset.top < 50) {
                                $(this).parent().css('top', (50 - offset.top) + 'px')
                            }
                            jQuery('html,body').animate({ scrollTop: Math.max(0, offset.top - 50) });
                        }});
                        <?php  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Reason: output is properly escaped or hardcoded ?>
                        wcrbfree_formContainer.html( '<?php echo $html; //phpcs:ignore ?>');

                        wcrbfree_formContainer.on( 'change', 'input[type=radio]', function() {
                            var wcrbfree_detailsLabel = wcrbfree_formContainer.find( '#wcrbfree-deactivate-details-label strong' );
                            var wcrbfree_anonymousLabel = wcrbfree_formContainer.find( 'label[for="wcrbfree_anonymous"]' )[0];
                            var wcrbfree_submitSpan = wcrbfree_formContainer.find( '#wcrbfree-deactivate-submit-form span' )[0];
                            var wcrbfree_value = wcrbfree_formContainer.find( 'input[name="wcrbfree-deactivate-reason"]:checked' ).val();

                            wcrbfree_detailsLabel.text( wcrbfree_detailsStrings[ wcrbfree_value ] );
                            wcrbfree_anonymousLabel.style.visibility = "visible";
                            wcrbfree_submitSpan.style.display = "inline-block";
                            if(wcrbfree_deactivated) {
                                wcrbfree_deactivated = false;
                                jQuery('#wcrbfree-deactivate-submit-form').removeAttr("disabled");
                                wcrbfree_formContainer.off('click', '#wcrbfree-deactivate-submit-form');
                                wcrbfree_formContainer.on('click', '#wcrbfree-deactivate-submit-form', function(e) {
                                    e.preventDefault();
                                    var data = {
                                        wcrbfree_reason: wcrbfree_formContainer.find('input[name="wcrbfree-deactivate-reason"]:checked').val(),
                                        wcrbfree_details: wcrbfree_formContainer.find('#wcrbfree-deactivate-details').val(),
                                        wcrbfree_anonymous: wcrbfree_formContainer.find('#wcrbfree_anonymous:checked').length,
                                    };
                                    wcrbfreeSubmitData(data, wcrbfree_formContainer);
                                });
                            }
                        });

                        wcrbfree_formContainer.on('click', '#wcrbfree-deactivate-submit-form', function(e) {
                            e.preventDefault();
                            wcrbfreeSubmitData({}, wcrbfree_formContainer);
                        });
                        $('.wcrbfree-deactivate-form-bg').on('click',function() {
                            wcrbfree_formContainer.fadeOut(); 
                            $('body').removeClass('wcrbfree-deactivate-form-active');
                        });

                        wcrbfree_formContainer.on( 'change', '#wcrbfree-risk-confirm', function() {
                            if(jQuery(this).is(":checked")) {
                                $('#wcrbfree-deactivate-submit-btn').removeAttr("disabled");
                            } else {
                                $('#wcrbfree-deactivate-submit-btn').attr('disabled','disabled');
                            }
                        });                            
                        wcrbfree_formContainer.on( 'click', '#wcrbfree-deactivate-cancel-btn', function(e) {
                            e.preventDefault();
                            wcrbfree_formContainer.fadeOut(); 
                            $('body').removeClass('wcrbfree-deactivate-form-active');
                            return false;
                        });
                        wcrbfree_formContainer.on( 'click', '#wcrbfree-deactivate-submit-btn', function() {
                            window.location.href = wcrbfree_url;
                            return false;
                        });                            
                    });
                });
            </script>
        <?php
    }

    function repairbuddy_deactivate_plugin_process() {

        check_ajax_referer('repairbuddy_deactivate_plugin_process', 'security');
        if (! empty($_POST['wcrbfree_reason']) && isset($_POST['wcrbfree_details']) ) {
            $wcrbfree_anonymous = isset($_POST['wcrbfree_anonymous']) && sanitize_text_field($_POST['wcrbfree_anonymous']); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
            $args  = $_POST;

            $args['plugin_name']        = 'RepairBuddy WordPress Plugin';
            $args['wcrbfree_site_url']        = REPAIRBUDDY_HOME_URL;
            $args['wcrbfree_site_ip_address'] = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
            if (! $wcrbfree_anonymous ) {
                $args['wcrb_lite_site_email'] = get_option( 'admin_email' );
            }

            $url = 'https://www.webfulcreations.com/members/plugin_deactivation.php';

            $response = wp_remote_post(
                $url,
                array(
                'timeout' => 500,
                'body'    => $args,
                )
            );
        }
        echo wp_json_encode(
            array(
            'status' => 'OK',
            )
        );
        die();
    }
}