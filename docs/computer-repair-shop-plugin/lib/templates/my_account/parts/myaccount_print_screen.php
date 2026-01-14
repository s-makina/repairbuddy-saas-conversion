<?php
    defined( 'ABSPATH' ) || exit;

    if ( isset( $_GET['template'] ) && $_GET['template'] == 'print_label' ) {
        require_once MYACCOUNT_TEMP_DIR . 'parts/label_generator.php';
        exit;
    }

    // Get current user data
    $user_id = $current_user->ID;

    if ( ! isset( $user_id ) || empty( $user_id ) || ! isset( $_GET['data-security'] ) || ! wp_verify_nonce( $_GET['data-security'], 'wcrb_nonce_printscreen' ) ) {
        echo esc_html__( "Something is wrong with your submission!", "computer-repair-shop" );
        exit();
    }
    //$dasboard     = WCRB_MYACCOUNT_DASHBOARD::getInstance();
    if ( ! isset( $_GET['job_id'] ) || ! $dasboard->have_job_access( sanitize_text_field( $_GET['job_id'] ) ) ) {
        echo esc_html__( "Something is wrong with your submission!", "computer-repair-shop" );
        exit();
    }
?>
<style type="text/css">
/* Timeline Styling */
.mb-twenty { margin-bottom: 20px !important; } 
.invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, .15); font-size: 12px; line-height: 1.4; color: #555; background-color:#FFF; } 
.invoice-box table { width: 100%; line-height: inherit; text-align: left; } 
.invoice-box table td { padding: 5px; vertical-align: top; } 
.invoice-box table tr td:nth-child(2) { text-align: right; } 
.invoice-box table .special_head td:nth-child(2) { text-align:left; } 
.invoice-box table tr.top table td { padding-bottom: 5px; } 
.invoice-box table tr.top table td.title, .invoice-box table tr.top table div.title { font-size: 45px; line-height: 45px; color: #333; } 
.invoice-box table tr.information table td { padding-bottom: 15px; } 
.invoice-box table tr.heading td, .invoice-box table tr.heading th { background: #eee; border-bottom: 1px solid #ddd; font-weight: bold; } 
.invoice-box table tr.heading.estimate td, .invoice-box table tr.heading.estimate th { background: #000; border-bottom: 1px solid #ddd; font-weight: bold; padding-top: 7.5px; padding-bottom: 7.5px; text-align: center; font-size: 18px; color: #FFF; letter-spacing: 3px; } 
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
.invoice_totals table { border:1px solid #ededed; max-width:250px; text-align:right; float:right; margin-bottom:15px; } 
.invoice_totals:after { clear:both; display:table; content:""; } 
.invoice-box:after { clear:both; display:table; content:""; } 
.invoice-items, .invoice_totals, .invoice_headers { font-size:12px; line-height:1.4; } 
.button.hidden-print { margin-top:35px; float:right; } 
p.label_box { font-size:12px; } 
.wcrb_invoice_label { margin: 0px; font-size: 19px; font-weight: 700; text-transform: uppercase; margin-bottom: 7px; letter-spacing: 1px; } 
.invoice-box.ticket-box div.titlelogo img.company_logo, div.titlelogo img.company_logo { max-height:55px; width:auto; max-width:auto; margin-bottom:7px; } 
div.title img, td.title img { height:55px; width:auto; } 
.paymentReciept div.title img { height:35px; width:auto; } 
.invoice-box table.paymentRecieptTable td p strong, .invoice-box table.paymentRecieptTable td p span { margin-right:8px; } 
.invoice-box table.paymentRecieptTable td p { padding:0px; margin:0px; border-bottom:1px solid #000; } 
.invoice-box table.paymentRecieptTable tr td:nth-child(2) { text-align:left; } 
.invoice-box table.paymentRecieptTable td { margin:0px; padding:10px 10px; } 
@media print { @page { margin: 2cm; } body { padding: 20px; } .invoice-box { box-shadow: 0px 0px !important; border:0px; padding:0px !important; } .wcrb_print_terms_conditions { padding:20px; } .dashboard-content.wcrb_print_template  { padding:0px !important; } div.wcrb_print_terms_conditions { page-break-before: always; } #screen-meta { display:none !important; } #wpcontent { padding:0px !important; margin-left:0px !important; } .invoice-box.ticket-box { width:100% !important; max-width:100% !important; /*margin: auto;*/ padding: 0px; border: 0px !important; box-shadow: 0px 0px 0px 0px !important; background-color:transparent; float:left; } #wpbody-content { padding-bottom:0px; } body { min-width:auto; max-width:100%; min-height:unset; height:auto; } #wpwrap { min-height:unset; } html.wp-toolbar { padding-top:0px; } #wpfooter, #adminmenuwrap, #adminmenuwrap *, #adminmenuback, #adminmenuback *{ display: none !important; width:0px; } .hidden-print, .hidden-print * { display: none !important; } .invoice-box table tr.heading.estimate td, .invoice-box table tr.heading.estimate th { background: #000; border-bottom: 1px solid #ddd; font-weight: bold; padding-top: 7.5px; padding-bottom: 7.5px; text-align: center; font-size: 18px; color: #FFF; letter-spacing: 3px; } }
</style>
<!-- Dashboard Content -->
<main class="dashboard-content container-fluid py-4 wcrb_print_template">
<?php
    $the_order_id = sanitize_text_field( $_GET['job_id'] );

    if ( isset( $_GET["email_customer"] ) && $_GET["email_customer"] == 'yes' ) {
        wc_cr_send_customer_update_email( $the_order_id );
        echo '<h2>' . esc_html__( 'Email have been sent to the customer.', 'computer-repair-shop' ) . '</h2>';
    }
    if ( isset( $_GET['template'] ) && $_GET['template'] == 'print-invoice' ) {
        $generatedHTML 	= wc_print_order_invoice( $the_order_id, 'print' );

        if ( isset( $_GET['dl_pdf'] ) && $_GET['dl_pdf'] == 'yes' ) {
            $WCRB_PDF_MAKER = WCRB_PDF_MAKER::getInstance();
            $WCRB_PDF_MAKER->generate_repair_estimate_invoice( $the_order_id, '' );
        }
        echo wp_kses( $generatedHTML, $allowedHTML );
    }

    if ( isset( $_GET['template'] ) && $_GET['template'] == 'customer_job' ) {
        $the_order_id     = sanitize_text_field( $_GET["order_id"] );
		$case_number      = get_post_meta( $the_order_id, '_case_number', true );
		$curr_case_number = ( isset( $_GET["wc_case_number"] ) ) ? sanitize_text_field( $_GET["wc_case_number"] ) : "";

        if($case_number != $curr_case_number) {
            $generatedHTML = esc_html__("You do not have permission to view this record.", "computer-repair-shop");
        } else {
            $order_id = $the_order_id;
            $_GET['my_account'] = 'yes';
            $generatedHTML = "<div class='callout success'><div class='orderstatusholder'>";
            $generatedHTML .= wcrb_return_job_history_bootstrap( $order_id, 'active' );
            $generatedHTML .= '</div></div>';
            $generatedHTML .= wc_computer_repair_print_functionality(TRUE);
        }
        $permalink = get_permalink(); // Gets current post's URL
        $url_with_args = add_query_arg( array( 'screen' => 'jobs' ), $permalink );
        $print_url     = add_query_arg( array(
                                            'wc_case_number' => $case_number,
                                            'screen'         => 'print-screen',
                                            'job_id'         => $the_order_id,
                                            'data-security'  => wp_create_nonce( 'wcrb_nonce_printscreen' ),
                                            'template'       => 'print-invoice'
                                        ), $permalink );
        
        $generatedHTML .= '<div class="aligncenter mt-25">
                            <a class="hidden-print btn btn-primary" href="' . esc_url( $print_url ) . '">' . esc_html__( 'Print Job Invoice', 'computer-repair-shop' ) . '</a>
                            <a class="hidden-print btn btn-primary" href="' . esc_url( $url_with_args ) . '">' . esc_html__( 'Go Back', 'computer-repair-shop' ) . '</a>
                          </div>';

        echo wp_kses( $generatedHTML, $allowedHTML );
    } //End template customer job add msg and timeline
?>
</main>