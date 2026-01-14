<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! function_exists( "wc_rs_get_email_footer" ) ) :
    function wc_rs_get_email_footer() {
        $wc_rb_business_address = get_option( 'wc_rb_business_address' );

        $output = '</div>'; // #body_content_inner
        $output .= '</td></tr></table>'; // cellpadding="20" table
        $output .= '</td></tr></table>'; // #template_body
        $output .= '</td></tr>'; // main content row
        
        $output .= '</table>'; // #template_container
        
        $output .= '</td></tr></table>'; // outer tables from head function
        
        $output .= '</div></center>';
        
        // Footer section (outside the main email container)
        $output .= '<center style="width: 100%; background-color: #f1f1f1; padding: 20px 0;">
            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;">
                <tr>
                    <td align="center" style="padding: 20px; background-color: #f8f8f8; text-align: center; font-size: 12px; color: #666; line-height: 1.5;">
                        ' . esc_html( get_bloginfo( 'name' ) ) . '<br>
                        ' . esc_html( get_bloginfo( 'description' ) );
        
        if ( ! empty( $wc_rb_business_address ) ) {
            $output .= '<br>' . esc_html( $wc_rb_business_address );
        }
        
        $output .= '
                    </td>
                </tr>
            </table>
        </center>';
        
        $output .= '</body></html>';

        return $output;
    }
endif;