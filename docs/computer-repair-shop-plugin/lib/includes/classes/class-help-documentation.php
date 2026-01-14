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

class WCRB_SUPPORT_DOCS {

    private static $instance = NULL;

    //Usage >> $WCRB_SUPPORT_DOCS = WCRB_SUPPORT_DOCS::getInstance();
	static public function getInstance() {
		if ( self::$instance === NULL )
			self::$instance = new WCRB_SUPPORT_DOCS();
		return self::$instance;
	}

    function return_helpful_link_parts() {
        $content = $this->docs_styles();
        $content .= '<a href="https://youtu.be/VOXNv92H7NY" target="_blank" id="wcrbtop" class="wcrbtop">';
        $content .= '<span>' . esc_html__( 'Tutorial', 'computer-repair-shop' ) . '</span>';
        $content .= '<img src="'. esc_url( WC_COMPUTER_REPAIR_DIR_URL . '/assets/admin/images/icons/video-help.png' ) .'" />';
        $content .= '</a>';

        $allowedHTML = wc_return_allowed_tags(); 
        echo wp_kses( $content, $allowedHTML );
    }

    function return_helpful_link_signature_workflow() {
        $content = $this->docs_inline_styles();
        $content .= '<a href="https://youtu.be/Hh9cE9IGM_k" target="_blank" id="wcrbtop" class="wcrbtop">';
        $content .= '<span>' . esc_html__( 'Tutorial', 'computer-repair-shop' ) . '</span>';
        $content .= '<img src="'. esc_url( WC_COMPUTER_REPAIR_DIR_URL . '/assets/admin/images/icons/video-help.png' ) .'" />';
        $content .= '</a>';

        $allowedHTML = wc_return_allowed_tags(); 
        return wp_kses( $content, $allowedHTML );
    }

    function docs_styles() {
        $style = '<style type="text/css">';
        $style .= '#wcrbtop {
                        position: fixed;
                        right: 15px;
                        text-align: center;
                        bottom: 15px;
                        font-weight: bold;
                        text-decoration: none;
                        width: 65px;
                        height: 65px;
                        padding: 5px;
                        font-size: 22px;
                        z-index: 99;
                        background-color:#fd6742;
                        line-height: 30px;
                        border:1px solid #fd6742;
                        border-radius:100%;
                    }
                    #wcrbtop:hover {
                        background-color:#063e70; 
                        color:#FFF;
                    }
                    #wcrbtop span {
                        margin-top: -22px;
                        background-color: #fd6742;
                        position: absolute;
                        top: 5px;
                        font-size: 13px;
                        width: 65px;
                        right: 0px;
                        color: #FFF;
                        border-radius:15px;
                    }';
        $style .= '</style>';

        return $style;
    }

    function docs_inline_styles() {
        $style = '<style type="text/css">';
        $style .= '#wcrbtop {
                        position: absolute;
                        right: 15px;
                        text-align: center;
                        top: 20px;
                        font-weight: bold;
                        text-decoration: none;
                        width: 65px;
                        height: 65px;
                        padding: 5px;
                        font-size: 22px;
                        z-index: 99;
                        background-color:#fd6742;
                        line-height: 30px;
                        border:1px solid #fd6742;
                        border-radius:100%;
                    }
                    #wcrbtop:hover {
                        background-color:#063e70; 
                        color:#FFF;
                    }
                    .tabs-panel {
                        position:relative;
                    }
                    #wcrbtop span {
                        margin-top: -22px;
                        background-color: #fd6742;
                        position: absolute;
                        top: 5px;
                        font-size: 13px;
                        width: 65px;
                        right: 0px;
                        color: #FFF;
                        border-radius:15px;
                    }';
        $style .= '</style>';

        return $style;
    }
}