<?php
    defined( 'ABSPATH' ) || exit;

function wcrb_manage_hourly_rates() {
    if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'list_users' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'computer-repair-shop' ) );
    }

    if ( isset( $_POST['update_hourly_rate'] ) && isset( $_POST['user_id'] ) ) {
        $user_id = intval( $_POST['user_id'] );
        $technician_rate = isset( $_POST['technician_hourly_rate'] ) ? sanitize_text_field( $_POST['technician_hourly_rate'] ) : '';
        $client_rate = isset( $_POST['client_hourly_rate'] ) ? sanitize_text_field( $_POST['client_hourly_rate'] ) : '';
        
        // Verify nonce for security
        if ( wp_verify_nonce( $_POST['_wpnonce'], 'update_hourly_rate_' . $user_id ) ) {
            // Update technician rate
            if ( $technician_rate !== '' ) {
                update_user_meta( $user_id, 'technician_hourly_rate', $technician_rate );
            }
            
            // Update client charge rate
            if ( $client_rate !== '' ) {
                update_user_meta( $user_id, 'client_hourly_rate', $client_rate );
            }
            
            // Show success message
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Hourly rates updated successfully!', 'computer-repair-shop' ) . '</p></div>';
        }
    }

    global $wpdb;

    // Pagination vars
    $current_page     = isset( $_GET['paged'] ) ? intval( $_GET['paged'] ) : 1;
    $users_per_page   = 20;

    // Get users with multiple roles
    $args = array(
        'role__in'  => array( 'store_manager', 'administrator', 'technician' ),
        'orderby'   => 'ID',
        'order'     => 'DESC',
        'number'    => $users_per_page,
        'paged'     => $current_page
    );

    $users_obj    = new WP_User_Query( $args );
    $total_users  = $users_obj->get_total();
    $num_pages    = ceil( $total_users / $users_per_page );
?>      
    <div class="wrap" id="poststuff">
        <h1 class="wp-heading-inline">
            <?php echo esc_html__( "Manage Staff", "computer-repair-shop" ); ?>
        </h1>
        
        <p>
            <?php echo esc_html__( "Manage store managers, administrators, and technicians hourly rates.", "computer-repair-shop" ); ?>
        </p>

        <br class="clear" />
        <?php
            $display_from   = ( ( $current_page * $users_per_page ) - $users_per_page );
            $display_to     = ( $current_page * $users_per_page );
            $display_to     = ( $display_to >= $total_users ) ? $total_users : $display_to;
        ?>
        <p><?php echo esc_html__( "Display", "computer-repair-shop" ) . " " . esc_html( $display_from ) . " - " . esc_html( $display_to ) . " " . esc_html__( "From Total", "computer-repair-shop" ) . " " . esc_html( $total_users ) . " " . esc_html__( "Staff Members.", "computer-repair-shop" ); ?></p>
        
        <table class="wp-list-table widefat fixed striped users">
            <thead>
                <tr>
                    <th class="manage-column column-id">
                        <span><?php echo esc_html__( "ID", "computer-repair-shop" ); ?></span>
                    </th>
                    <th class="manage-column column-name">
                        <span><?php echo esc_html__( "Name", "computer-repair-shop" ); ?></span>
                    </th>
                    <th class="manage-column column-role">
                        <span><?php echo esc_html__( "Role", "computer-repair-shop" ); ?></span>
                    </th>
                    <th class="manage-column column-email">
                        <span><?php echo esc_html__( "Email", "computer-repair-shop" ); ?></span>
                    </th>
                    <th class="manage-column column-hourly-rate">
                        <?php echo esc_html__( "Hourly Rate", "computer-repair-shop" ); ?>
                    </th>
                </tr>
            </thead>

            <tbody data-wp-lists="list:user">
                <?php 
                    $content = '';

                    foreach( $users_obj->get_results() as $userdata ) {
                        $user           = get_user_by( 'id', $userdata->ID );
                        $hourly_rate    = get_user_meta( $userdata->ID, 'technician_hourly_rate', true );
                        $user_roles     = $user->roles;
                        $primary_role   = ! empty( $user_roles ) ? reset( $user_roles ) : '';
                        
                        // Format role for display
                        $role_display = '';
                        switch( $primary_role ) {
                            case 'administrator':
                                $role_display = esc_html__( 'Administrator', 'computer-repair-shop' );
                                break;
                            case 'store_manager':
                                $role_display = esc_html__( 'Store Manager', 'computer-repair-shop' );
                                break;
                            case 'technician':
                                $role_display = esc_html__( 'Technician', 'computer-repair-shop' );
                                break;
                            default:
                                $role_display = $primary_role;
                        }

                        $content .= '<tr>';
                        $content .= '<td class="id column-id num" data-colname="ID">';
                        $content .= esc_html( $userdata->ID );
                        $content .= '</td>';  

                        $content .= '<td class="username column-username has-row-actions column-primary" data-colname="Username">
                                        <strong>
                                            <a href="edit.php?post_type=rep_jobs&job_technician=' . esc_attr( $userdata->ID ) . '">
                                                ' . esc_html( $user->first_name . ' ' . $user->last_name ) . '
                                            </a>
                                        </strong><br>
                                        <div class="row-actions">
                                            <span class="edit">
                                                <a href="' . esc_url( add_query_arg( array( 'update_user' => $userdata->ID ) ) ) . '" class="update_user_form">
                                                    ' . esc_html__( "Edit", "computer-repair-shop" ) . '
                                                </a> | 
                                            </span>
                                            <span class="remove">
                                                <a href="edit.php?post_type=rep_jobs&job_technician=' . esc_attr( $userdata->ID ) . '">
                                                    ' . esc_html__( "View Jobs", "computer-repair-shop" ) . '
                                                </a> 
                                            </span>
                                        </div>
                                        <button type="button" class="toggle-row"><span class="screen-reader-text">' . esc_html__( "Show more details", "computer-repair-shop" ) . '</span></button>
                                    </td>';
                    
                        $content .= '<td class="role column-role" data-colname="Role">';
                        $content .= esc_html( $role_display );
                        $content .= '</td>';

                        $content .= '<td class="email column-email" data-colname="Email">
                                        <a href="mailto:' . esc_attr( $userdata->user_email ) . '">
                                        ' . esc_html( $userdata->user_email ) . '
                                        </a>
                                    </td>';

                        $content .= '<td class="hourly-rate column-hourly-rate" data-colname="Hourly Rate">';
                        $content .= '<form method="post" class="hourly-rate-form" style="display: inline-block;">';
                        $content .= wp_nonce_field( 'update_hourly_rate_' . $userdata->ID, '_wpnonce', true, false );
                        $content .= '<input type="hidden" name="user_id" value="' . esc_attr( $userdata->ID ) . '">';

                        // Technician Hourly Rate (what the technician earns)
                        $content .= '<table class="ratetable">';
                        $content .= '<tr>';
                        $content .= '<td style="padding-top:0px;padding-bottom:0px;"><label style="display: block; font-size: 11px; color: #666;">' . esc_html__( 'Tech Rate', 'computer-repair-shop' ) . '</label></td>';
                        $content .= '<td style="padding-top:0px;padding-bottom:0px;"><input type="number" name="technician_hourly_rate" value="' . esc_attr( $hourly_rate ) . '" class="small-text" style="width: 100px;" step="0.01" min="0" inputmode="decimal" placeholder="0.00"></td>';
                        $content .= '</tr>';

                        // Client Charge Rate (what the client is charged)
                        $client_charge_rate = get_user_meta( $userdata->ID, 'client_hourly_rate', true );
                        $content .= '<tr>';
                        $content .= '<td style="padding-top:0px;padding-bottom:0px;"><label style="display: block; font-size: 11px; color: #666;">' . esc_html__( 'Client Rate', 'computer-repair-shop' ) . '</label></td>';
                        $content .= '<td style="padding-top:0px;padding-bottom:0px;"><input type="number" name="client_hourly_rate" value="' . esc_attr( $client_charge_rate ) . '" class="small-text" style="width: 100px;" step="0.01" min="0" inputmode="decimal" placeholder="0.00"></td>';
                        $content .= '</tr>';

                        $content .= '<tr><td></td><td style="padding-top:0px;padding-bottom:0px;">&nbsp;<button type="submit" name="update_hourly_rate" class="button button-small" style="margin-top: 5px;">' . esc_html__( 'Update', 'computer-repair-shop' ) . '</button></td></tr>';
                        $content .= '</table>';
                        $content .= '</form>';
                        $content .= '</td>';

                        $content .= '</tr>';
                    }

                    $allowedHTML = wc_return_allowed_tags(); 
                    echo wp_kses( $content, $allowedHTML );
                ?>
            </tbody>

            <tfoot>
                <tr>
                    <th class="manage-column column-id">
                        <span><?php echo esc_html__( "ID", "computer-repair-shop" ); ?></span>
                    </th>
                    <th class="manage-column column-name">
                        <span><?php echo esc_html__( "Name", "computer-repair-shop" ); ?></span>
                    </th>
                    <th class="manage-column column-role">
                        <span><?php echo esc_html__( "Role", "computer-repair-shop" ); ?></span>
                    </th>
                    <th class="manage-column column-email">
                        <span><?php echo esc_html__( "Email", "computer-repair-shop" ); ?></span>
                    </th>
                    <th class="manage-column column-hourly-rate">
                        <?php echo esc_html__( "Hourly Rate", "computer-repair-shop" ); ?>
                    </th>
                </tr>
            </tfoot>
        </table>

        <div class="tablenav-pages" style="float:right;margin-top:20px;">
            <span class="displaying-num">
                <?php 
                    echo esc_html( $total_users ) . " " . esc_html__( "items", "computer-repair-shop" ); 
                ?>
            </span>

            <span class="pagination-links">
                <?php
                    // Previous page
                    if ( $current_page > 1 ) {
                    ?>
                        <a class="first-page button" href="<?php echo esc_url( add_query_arg( array( 'paged' => 1 ) ) ); ?>">
                            <span aria-hidden="true">«</span>
                        </a>
                        <a class="prev-page button" href="<?php echo esc_url( add_query_arg( array( 'paged' => $current_page - 1 ) ) ); ?>">
                            <span aria-hidden="true">‹</span>
                        </a>
                    <?php } ?>

                <span id="table-paging" class="paging-input">
                    <span class="tablenav-paging-text"><?php echo esc_html( $current_page ); ?> <?php echo esc_html__( "of", "computer-repair-shop" ); ?> <span class="total-pages"><?php echo esc_html( $num_pages ); ?></span></span></span>
            
                <?php
                // Next page
                if ( $current_page < $num_pages ) {
                ?>
                    <a class="next-page button" href="<?php echo esc_url( add_query_arg( array( 'paged' => $current_page + 1 ) ) ); ?>"><span aria-hidden="true">›</span></a>
                    <a class="last-page button" href="<?php echo esc_url( add_query_arg( array( 'paged' => $num_pages ) ) ); ?>"><span aria-hidden="true">»</span></a></span>
                <?php } ?>
        </div>
    </div> <!-- Wrap Ends /-->
    
    <?php
    // Add JavaScript for better UX
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Add confirmation before submitting hourly rate changes
        $('.hourly-rate-form').on('submit', function(e) {
            var form = $(this);
            var hourlyRate = form.find('input[name="hourly_rate"]').val();
            
            if (!confirm('<?php echo esc_js( __( 'Are you sure you want to update the hourly rate?', 'computer-repair-shop' ) ); ?>')) {
                e.preventDefault();
                return false;
            }
        });
    });
    </script>
    <?php
}