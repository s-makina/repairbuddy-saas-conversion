<?php
ob_start();
/**
 * This file handles the functions related to Reviews
 *
 * Help setup pages to they can be used in notifications and other items
 *
 * @package computer-repair-shop
 * @version 3.7947
 */
defined( 'ABSPATH' ) || exit;

use Dompdf\Dompdf;
use Dompdf\Options;

class WCRB_PDF_MAKER {

	private static $instance = NULL;

	static public function getInstance() {
		if ( self::$instance === NULL )
			self::$instance = new WCRB_PDF_MAKER();
		return self::$instance;
	}

    function return_styles() {
        $the_styles = "<style>
            /* Import fonts for multi-language support */
            @import url('https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;700&display=swap');
            @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@400;700&display=swap');
            @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@400;700&display=swap');
            @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;700&display=swap');
            @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@400;700&display=swap');
            @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@400;700&display=swap');
            @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Hebrew:wght@400;700&display=swap');
            
            /* Base styles with better language support */
            * { 
                font-family: 'Noto Sans', 'Noto Sans Arabic', sans-serif; 
                box-sizing: border-box;
            }
            
            body, h1, h2, h3, h4, h5, h6, p, table, tr, td { 
                color: #2a2a2a; 
            }
            
            /* Invoice container */
            .invoice-box { 
                max-width: 800px; 
                margin: auto; 
                padding: 30px; 
                border: 1px solid #eee; 
                box-shadow: 0 0 10px rgba(0, 0, 0, .15); 
                font-size: 12px; 
                line-height: 1.1; 
                color: #555; 
                background-color: #FFF; 
            }
            
            /* Table styles */
            .invoice-box table { 
                width: 100%; 
                line-height: inherit; 
                text-align: left; 
            }
            
            .invoice-box table td { 
                padding: 5px; 
                vertical-align: top; 
            }
            
            .invoice-box table tr td:nth-child(2) { 
                text-align: right; 
            }
            
            .invoice-box table .special_head td:nth-child(2) { 
                text-align: left; 
            }
            
            /* Table header styles */
            .invoice-box table tr.heading td,
            .invoice-box table tr.heading th { 
                background: #eee; 
                border-bottom: 1px solid #ddd; 
                font-weight: bold; 
            }
            
            .invoice-box table tr.heading.estimate td,
            .invoice-box table tr.heading.estimate th { 
                background: #000; 
                border-bottom: 1px solid #ddd; 
                font-weight: bold; 
                padding-top: 7.5px; 
                padding-bottom: 7.5px; 
                text-align: center; 
                font-size: 18px; 
                color: #FFF; 
                letter-spacing: 3px; 
            }
            
            /* Item row styles */
            .invoice-box table tr.item-row td { 
                border-bottom: 1px solid #eee; 
            }
            
            .invoice-box table tr.item-row td:nth-child(2) { 
                text-align: left; 
            }
            
            .invoice-box table tr.item-row td:last-child { 
                text-align: right; 
            }
            
            .invoice-box table tr.item-row td.text-left:last-child { 
                text-align: left; 
            }
            
            /* Total row */
            .invoice-box table tr.total td:nth-child(2) { 
                border-top: 2px solid #eee; 
                font-weight: bold; 
            }
            
            /* Title and logo styles */
            div.title img, 
            td.title img { 
                height: 55px; 
                width: auto; 
            }
            
            .paymentReciept div.title img { 
                height: 35px; 
                width: auto; 
            }
            
            .invoice-box table tr.top table td.title, 
            .invoice-box table tr.top table div.title { 
                font-size: 45px; 
                line-height: 1; 
                color: #333; 
            }
            
            /* Payment receipt styles */
            .invoice-box table.paymentRecieptTable td p strong, 
            .invoice-box table.paymentRecieptTable td p span { 
                margin-right: 8px; 
            }
            
            .invoice-box table.paymentRecieptTable td p { 
                padding: 0px; 
                margin: 0px; 
                border-bottom: 1px solid #000; 
            }
            
            .invoice-box table.paymentRecieptTable tr td:nth-child(2) { 
                text-align: left; 
            }
            
            .invoice-box table.paymentRecieptTable td { 
                margin: 0px; 
                padding: 10px; 
            }
            
            /* Form elements */
            .invoice-box table tr.item-row td input { 
                background: transparent; 
                margin: 0px; 
                width: auto; 
                padding: 0px; 
                height: auto; 
                border: 0px; 
                max-width: 70px; 
                min-width: auto; 
            }
            
            /* Layout utilities */
            .invoice_totals table { 
                border: 1px solid #ededed; 
                width: 250px; 
                max-width: 250px; 
                text-align: right; 
                float: right; 
                margin-bottom: 15px; 
            }
            
            .invoice_totals:after,
            .invoice-box:after { 
                clear: both; 
                display: table; 
                content: ''; 
            }
            
            .invoice-items, 
            .invoice_totals, 
            .invoice_headers { 
                font-size: 12px; 
                line-height: 1.2; 
            }
            
            /* Text styles */
            .wcrb_invoice_label { 
                margin: 0px; 
                font-size: 19px; 
                font-weight: 700; 
                text-transform: uppercase; 
                margin-bottom: 7px; 
                letter-spacing: 1px; 
            }
            
            .invoice-box .address-side h2, 
            .invoice-box .address-side p, 
            .report-container .company_info .address-side h2, 
            .report-container .company_info .address-side p { 
                margin-top: 0px; 
                margin-bottom: 5px; 
            }
            
            /* Flexbox utilities */
            .company_logo_wrap, 
            .address-side { 
                flex: 1; 
                align-self: center; 
            }
            
            /* Spacing utilities */
            .mb-twenty { 
                margin-bottom: 20px; 
            }
            
            .text-center { 
                text-align: center; 
            }
            
            /* RTL (Right-to-Left) Support - Consolidated */
            .rtl,
            .lang-ar,
            [dir='rtl'] {
                direction: rtl;
                text-align: right;
                font-family: 'Noto Sans Arabic', 'Noto Sans', sans-serif !important;
            }
            
            .rtl table,
            .lang-ar table,
            [dir='rtl'] table { 
                text-align: right; 
            }
            
            .rtl .invoice-box table tr td:nth-child(2),
            .lang-ar .invoice-box table tr td:nth-child(2),
            [dir='rtl'] .invoice-box table tr td:nth-child(2) { 
                text-align: left; 
            }
            
            .rtl .invoice-box table tr.item-row td:last-child,
            .lang-ar .invoice-box table tr.item-row td:last-child,
            [dir='rtl'] .invoice-box table tr.item-row td:last-child { 
                text-align: left; 
            }
            
            .rtl .invoice_totals table,
            .lang-ar .invoice_totals table,
            [dir='rtl'] .invoice_totals table { 
                text-align: left; 
                float: left;
            }
            
            .rtl .invoice-box table.paymentRecieptTable tr td:nth-child(2),
            .lang-ar .invoice-box table.paymentRecieptTable tr td:nth-child(2),
            [dir='rtl'] .invoice-box table.paymentRecieptTable tr td:nth-child(2) { 
                text-align: right; 
            }
            
            .rtl .invoice-box table.paymentRecieptTable td p strong,
            .rtl .invoice-box table.paymentRecieptTable td p span,
            .lang-ar .invoice-box table.paymentRecieptTable td p strong,
            .lang-ar .invoice-box table.paymentRecieptTable td p span,
            [dir='rtl'] .invoice-box table.paymentRecieptTable td p strong,
            [dir='rtl'] .invoice-box table.paymentRecieptTable td p span { 
                margin-right: 0;
                margin-left: 8px; 
            }

            /* Language-specific font classes */
            .lang-ar {
                font-family: 'Noto Sans Arabic', 'Noto Sans', sans-serif !important;
            }
            
            .lang-zh {
                font-family: 'Noto Sans SC', 'Noto Sans', sans-serif;
            }
            
            .lang-ja {
                font-family: 'Noto Sans JP', 'Noto Sans', sans-serif;
            }
            
            .lang-ko {
                font-family: 'Noto Sans KR', 'Noto Sans', sans-serif;
            }
            
            .lang-hi {
                font-family: 'Noto Sans Devanagari', 'Noto Sans', sans-serif;
            }
            
            .lang-he {
                font-family: 'Noto Sans Hebrew', 'Noto Sans', sans-serif;
            }
            
            /* Print styles */
            @media print {
                .invoice-box {
                    box-shadow: none;
                    border: none;
                }
                
                .invoice-box table tr.heading.estimate td,
                .invoice-box table tr.heading.estimate th {
                    background: #000 !important;
                    -webkit-print-color-adjust: exact;
                    color-adjust: exact;
                }
            }
        </style>";

        return $the_styles;
    }

    // Update your PDF generation methods to use better font configuration:
    function generate_repair_estimate_invoice( $job_id, $case_number = '' ) {
        if ( empty( $job_id ) ) {
            wp_die( 'Job ID is required' );
        }
        
        // Check permissions
        if ( ! current_user_can( 'read' ) ) {
            if ( empty( $case_number ) || $case_number != get_the_title( $job_id ) ) {
                wp_die( 'You do not have permission to view this invoice' );
            }
        }

        try {
            // Get WordPress language attributes
            $language_attributes = get_language_attributes();
            
            // Get language classes
            $language_classes = $this->get_language_classes();
            
            $pdfHTML  = "<!DOCTYPE html><html $language_attributes><head><meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>";
            $pdfHTML .= $this->return_styles() . "</head>";
            $pdfHTML .= '<body class="' . esc_attr($language_classes) . '">' . wc_print_order_invoice( $job_id, 'pdf' ) . '</body></html>';

            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isPhpEnabled', false);
            $options->set('defaultFont', 'dejavusans'); // Keep this but ensure it supports Arabic
            $options->set('isFontSubsettingEnabled', true); // Enable font subsetting
            $options->set('chroot', get_template_directory()); // Set chroot for better resource loading

            $dompdf = new Dompdf( $options );
            $dompdf->loadHtml( $pdfHTML, 'UTF-8' );
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            while ( ob_get_level() ) {
                ob_end_clean();
            }

            $dompdf->stream('invoice-' . $job_id . '.pdf', array('Attachment' => false));
            exit;

        } catch ( Exception $e ) {
            wp_die( 'PDF generation failed: ' . $e->getMessage() );
        }
    }

    //Function $thetype can be 'invoice' or 'work_order' for now
    function return_repair_estimate_invoice( $job_id, $thetype = 'invoice' ) {
        if ( empty( $job_id ) ) {
            return false;
        }

        global $wp_filesystem;

        try {
            // Get WordPress language attributes
            $language_attributes = get_language_attributes();
            
            // Get language classes
            $language_classes = $this->get_language_classes();
            
            $pdfHTML  = "<!DOCTYPE html><html $language_attributes><head><meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>";
            $pdfHTML .= $this->return_styles() . "</head>";
            $pdfHTML .= '<body class="' . esc_attr( $language_classes ) . '">';

            $pdfHTML .= ( $thetype == 'work_order' ) ? wcrb_print_large_work_order( $job_id, 'pdf' ) : wc_print_order_invoice( $job_id, 'pdf' );  
            $pdfHTML .= '</body></html>';

            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isPhpEnabled', false);
            $options->set('defaultFont', 'dejavusans');

            $dompdf = new Dompdf( $options );
            $dompdf->loadHtml( $pdfHTML, 'UTF-8' );
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            // Initialize filesystem
            if ( empty( $wp_filesystem ) ) {
                require_once ( ABSPATH . '/wp-admin/includes/file.php' );
                WP_Filesystem();
            }

            $upload_dir = wp_upload_dir();
            $directory = $upload_dir['basedir'] . '/repairbuddy_uploads/attachments/';

            if ( ! $wp_filesystem->is_dir( $directory ) ) {
                if ( ! $wp_filesystem->mkdir( $directory, 0755 ) ) {
                    return false;
                }
            }

            $customer = get_post_meta( $job_id, "_customer", true );
            $customer = empty( $customer ) ? 'unknown' : sanitize_file_name( $customer );
            
            $fileName = $directory . 'attachment_' . $customer . '_' . $job_id . '.pdf';
            
            if ( $wp_filesystem->put_contents( $fileName, $dompdf->output(), 0644 ) ) {
                return $fileName;
            } else {
                return false;
            }
        } catch ( Exception $e ) {
            return false;
        }
    }

    /**
     * Get language classes for body tag
     */
    private function get_language_classes() {
        $classes = array();
        
        $locale = get_locale();
        $language = get_bloginfo('language');
        
        
        $lang_class = 'lang-' . strtok($language, '-');
        $classes[] = $lang_class;
        
        $locale_class = 'locale-' . strtolower(str_replace('_', '-', $locale));
        $classes[] = $locale_class;
        
        if (is_rtl()) {
            $classes[] = 'rtl';
        }
        
        $direction_class = is_rtl() ? 'dir-rtl' : 'dir-ltr';
        $classes[] = $direction_class;
        
        return implode(' ', $classes);
    }

    /**
     * Generate PDF from HTML content
     */
    public function generate_pdf_from_html($html_content, $filename = 'document') {
        try {
            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isPhpEnabled', false);
            $options->set('defaultFont', 'dejavusans');
            $options->set('isFontSubsettingEnabled', true);
            $options->set('chroot', get_template_directory());

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html_content, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            return $dompdf->output();
            
        } catch (Exception $e) {
            return false;
        }
    }
}