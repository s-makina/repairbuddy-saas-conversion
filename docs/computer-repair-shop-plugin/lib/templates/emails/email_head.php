<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! function_exists( "wc_rs_get_email_head" ) ) :
function wc_rs_get_email_head() {
    $output = '<!DOCTYPE html>';
    $output .= '<html ' . get_language_attributes() . '>';
    $output .= '<head>';
    $output .= '<meta http-equiv="Content-Type" content="text/html; charset=' . esc_attr( get_bloginfo( 'charset' ) ) . '" />';
    $output .= '<title>' . esc_html( get_bloginfo( 'name', 'display' ) ) . '</title>';
    $output .= '<style type="text/css">
    /* Basic reset for Outlook */
    body, table, td, div, p { 
        font-family: Arial, Helvetica, sans-serif;
        line-height: 1.5;
    }
    /* Add to your email header CSS */
    .repair-box {
        max-width: 800px !important;
        margin: auto !important;
        padding: 30px !important;
        border: 1px solid #eee !important;
        box-shadow: 0 0 10px rgba(0, 0, 0, .15) !important;
        font-size: 12px !important;
        line-height: 1.2 !important;
        color: #555 !important;
        background-color: #FFF !important;
    }

    .invoice_headers {
        font-size: 12px !important;
        line-height: 1.4 !important;
    }

    .wcrb_invoice_label {
        margin: 0 0 10px 0 !important;
        font-size: 19px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 1px !important;
        color: #333 !important;
    }

    .text-center {
        text-align: center !important;
    }

    .signatureblock {
        display: block !important;
        width: 100% !important;
        text-align: center !important;
        clear: both !important;
        margin: 20px 0 !important;
        padding: 15px !important;
        background-color: #f9f9f9 !important;
        border-top: 2px solid #eeeeee !important;
        font-style: italic !important;
    }
    .invoice_totals:after { clear: both; display: table; content: ""; }
    .company_info.large_invoice .address-side p { font-size: 12px; margin-top: 4px; margin-bottom: 4px; }
    .company_info.large_invoice .address-side h2 { font-size: 14px; font-weight: bold; margin-top: 4px; margin-bottom: 4px; }
    p.signatureblock { display: block; width: 100%; text-align: center; clear: both; }
    tr.top td.title img.company_logo { max-height: 83px; max-width: 200px; width: auto; height: auto; }
    .repair_box .invoice_header { text-align: right; }
    .invoice_totals:after { clear: both; display: table; content: ""; width: 100%; }
    .repair_box table tr td, .repair_box table tr th { border: 1px solid #f7f7f7; padding: 8px; }
    .repair_box table tr.heading td { background: #eee; border-bottom: 1px solid #ddd; font-weight: bold; }
    .logomain { text-align: left; }
    td.textallright, td.textallright div, td.textallright p { text-align: right; }
    .repair_box .invoice_totals table { border: 1px solid #ededed; max-width: 350px; text-align: right; float: right; margin-bottom: 15px; }
    .repair_box p.aligncenter { width: 100%; display: block; clear: both; text-align: center; }
    .repair_box table tr th { font-weight: bold; }
    .repair_box table { margin-bottom: 15px; width: 100%; border-collapse: collapse; }
    #wrapper { max-width: 600px; margin: 0 auto; }
    body { margin: 0; padding: 0; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    
    /* Invoice Box Styles - Outlook Compatible */
    .paymentReciept div.title img { height:35px; width:auto; }
    .invoice-box table.paymentRecieptTable td  p strong, 
    .invoice-box table.paymentRecieptTable td  p span { margin-right:8px; }
    .invoice-box table.paymentRecieptTable td  p { padding:0px; margin:0px; border-bottom:1px solid #000; }
    .invoice-box table.paymentRecieptTable tr td:nth-child(2) { text-align:left; }
    .invoice-box table.paymentRecieptTable td { margin:0px; padding:10px 10px; }
    .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, .15); font-size: 12px; line-height: 1.2; color: #555; background-color:#FFF; }
    .invoice-box table { width: 100%; line-height: inherit; text-align: left; border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    .invoice-box table td { padding: 5px; vertical-align: top; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    .invoice-box table tr td:nth-child(2) { text-align: right; }
    .invoice-box table .special_head td:nth-child(2) { text-align:left; }
    .invoice-box table tr.top table td { padding-bottom: 5px; }
    .invoice-box table tr.top table td.title, 
    .invoice-box table tr.top table div.title { font-size: 45px; line-height: 1.2; color: #333; }
    .invoice-box table tr.information table td { padding-bottom: 15px; }
    
    /* For other email clients */
    .invoice-box table tr.heading td, 
    .invoice-box table tr.heading th { 
        background: #eee; 
        border-bottom: 1px solid #ddd; 
        font-weight: bold; 
        padding: 8px 5px;
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
    
    .invoice-box table tr.details td { padding-bottom: 20px; }
    .invoice-box table tr.item-row td{ border-bottom: 1px solid #eee; }
    .invoice-box table tr.total td:nth-child(2) { border-top: 2px solid #eee; font-weight: bold; }
    .invoice-box table tr.item-row .delme { display:none; }
    .invoice-box table tr.item-row td:nth-child(2) { text-align:left; }
    .invoice-box table tr.item-row td:last-child { text-align:right; }
    .invoice-box table tr.item-row td.text-left:last-child { text-align:left; }
    .invoice-box table tr.item-row td input { background:transparent; margin:0px; width:auto; padding:0px; height:auto; border:0px; max-width:70px; min-width:auto; }
    .invoice-box table tr.heading .emptyhead { background-color:transparent; }
    .invoice-box table tr.item-row:last-child > td { border-bottom:0px; }
    .invoice_totals table { border:1px solid #ededed; width:250px; max-width:250px; text-align:right; float:right; margin-bottom:15px; }
    .invoice_totals:after { clear:both; display:table; content:\'\'; }
    .invoice-box:after { clear:both; display:table; content:\'\'; }
    .invoice-items, .invoice_totals, .invoice_headers { font-size:12px; line-height:1.2; }
    div.wcrb_print_terms_conditions { margin-top:30px; }
    .wcrb_invoice_label { margin: 0px; font-size: 19px; font-weight: 700; text-transform: uppercase; margin-bottom: 7px; letter-spacing: 1px; }
    .invoice-box .address-side h2, 
    .invoice-box .address-side p, 
    .report-container .company_info .address-side h2, 
    .report-container .company_info .address-side p { margin-top:0px; margin-bottom:5px; }
    .company_logo_wrap, .address-side { flex:1; align-self: center; }
    .mb-twenty { margin-bottom:20px; } 
    .text-center{text-align:center;}

    /* Payment Links Styling */
    .wcrb_payment_links {
        margin: 15px 0;
        text-align: center;
        padding: 10px;
        background-color: #f9f9f9;
        border-radius: 5px;
        border: 1px solid #e0e0e0;
    }
    
    .wcrb_payment_links a.button {
        display: block;
        background-color: #0073aa !important;
        color: #ffffff !important;
        text-decoration: none !important;
        padding: 12px 20px !important;
        margin: 8px 0 !important;
        border-radius: 4px !important;
        font-weight: bold !important;
        text-align: center !important;
        border: none !important;
        font-size: 14px !important;
        line-height: 1.4 !important;
    }
    
    .wcrb_payment_links a.button:hover {
        background-color: #005a87 !important;
    }
    
    .wcrb_payment_links a.button.expanded {
        width: 100%;
        box-sizing: border-box;
    }
    
    .wcrb_payment_links a.button.wcrb {
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    /* Multiple links layout */
    .wcrb_payment_links .payment-link-group {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .wcrb_payment_links .payment-link-row {
        display: flex;
        gap: 10px;
        justify-content: center;
    }
    
    .wcrb_payment_links .payment-link-row a.button {
        flex: 1;
        min-width: 120px;
        margin: 0 !important;
    }
    </style>';
    $output .= '</head>';
    
    $rightmargin = is_rtl() ? 'rightmargin' : 'leftmargin';
    $direction = is_rtl() ? 'rtl' : 'ltr';
    
    $output .= '<body ' . $rightmargin . '="0" marginwidth="0" topmargin="0" marginheight="0" offset="0" style="margin: 0; padding: 0;">';
    
    // Outlook conditional comments - MUST be outside the style tag
    $output .= '<!--[if mso]>
    <style type="text/css">
    body, table, td {font-family: Arial, Helvetica, sans-serif !important;}
    table {border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;}
    .heading td {background-color: #eeeeee !important; border-bottom: 1px solid #dddddd !important; font-weight: bold !important;}
    .heading.estimate td {background-color: #000000 !important; color: #ffffff !important; text-align: center !important; font-size: 18px !important;}
    .invoice-box table tr.heading td, 
    .invoice-box table tr.heading th { 
        background-color: #eeeeee !important;
        border-bottom: 1px solid #dddddd !important;
        font-weight: bold !important;
        padding: 8px 5px !important;
    }
    .invoice-box table tr.heading.estimate td, 
    .invoice-box table tr.heading.estimate th { 
        background-color: #000000 !important;
        border-bottom: 1px solid #dddddd !important;
        font-weight: bold !important;
        padding: 7.5px 5px !important;
        text-align: center !important;
        font-size: 18px !important;
        color: #ffffff !important;
        letter-spacing: 3px !important;
    }
    .wcrb_payment_links a.button {
        background-color: #0073aa !important;
        color: #ffffff !important;
        text-decoration: none !important;
        padding: 12px 20px !important;
        margin: 8px 0 !important;
        font-weight: bold !important;
        text-align: center !important;
        border: none !important;
        font-size: 14px !important;
    }
    </style>
    <![endif]-->';
    
    $output .= '<center style="width: 100%; background-color: #f1f1f1;">';
    $output .= '<div id="wrapper" dir="' . esc_attr( $direction ) . '" style="max-width: 600px; margin: 0 auto;">';
    $output .= '<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" style="border-collapse: collapse;">';
    $output .= '<tr><td align="center" valign="top">';
    $output .= '<table border="0" style="background-color: #FFF; margin: 30px 0; max-width: 600px; width: 100%;" cellpadding="0" cellspacing="0" width="600" id="template_container">';

    // Header section
    $output .= '<tr><td>';
    $output .= '<table border="0" cellpadding="15" cellspacing="0" width="100%" style="background-color: #f8f8f8; border-collapse: collapse;">';
    $output .= '<tr class="top"><td align="left" valign="top" class="title logomain" width="50%">';
    $output .= wc_rb_return_logo_url_with_img( "company_logo" );
    $output .= '</td><td align="right" valign="top" class="textallright" width="50%">';

    $wc_rb_business_name    = get_option( 'wc_rb_business_name' );
    $wc_rb_business_phone   = get_option( 'wc_rb_business_phone' );
    $wc_rb_business_name    = ( empty( $wc_rb_business_name ) ) ? get_bloginfo( 'name' ) : $wc_rb_business_name;
    $computer_repair_email  = get_option( 'computer_repair_email' );

    if ( empty( $computer_repair_email ) ) {
        $computer_repair_email = get_option( "admin_email" );    
    }

    $output .= '<div class="company_info large_invoice">';
    $output .= '<div class="address-side">';
    $output .= '<h2 style="margin: 4px 0; font-size: 14px; font-weight: bold;">' . esc_html( wp_unslash( $wc_rb_business_name ) ) . '</h2>';
    $output .= '<p style="margin: 4px 0; font-size: 12px;">';
    
    if ( ! empty( $computer_repair_email ) ) {
        $output .= '<strong>' . esc_html__( "Email", "computer-repair-shop" ) . '</strong>: ' . esc_html( $computer_repair_email );
    }
    
    if ( ! empty( $wc_rb_business_phone ) ) {
        $output .= '<br><strong>' . esc_html__( "Phone", "computer-repair-shop" ) . '</strong>: ' . esc_html( $wc_rb_business_phone );
    }
    
    $output .= '</p></div></div>';
    $output .= '</td></tr></table>';
    $output .= '</td></tr>';

    // Body section
    $output .= '<tr><td align="center" valign="top">';
    $output .= '<table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;" id="template_body">';
    $output .= '<tr><td valign="top" id="body_content">';
    $output .= '<table border="0" cellpadding="20" cellspacing="0" width="100%">';
    $output .= '<tr><td valign="top">';
    $output .= '<div id="body_content_inner">';

    return $output;
}
endif;