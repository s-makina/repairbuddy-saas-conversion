<?php
    defined( 'ABSPATH' ) || exit;
?>
<!-- Main Content -->
<div class="main-content" id="main-content">
    <!-- Top Bar -->
    <header class="top-bar bg-white shadow-sm border-bottom">
        <div class="container-fluid">
            <div class="row align-items-center py-2">
                <div class="col-md-6">
                    <button class="btn btn-outline-secondary btn-sm me-2 d-md-none" id="sidebarToggle">
                        <i class="bi bi-list"></i>
                    </button>
                    <h4 class="mb-0 text-dark"><?php echo esc_html( $page_title ); ?></h4>
                </div>
                <div class="col-md-6 text-end">
                    <div class="d-flex align-items-center justify-content-end gap-2">
                        <!-- Fullscreen Toggle -->
                        <button class="btn btn-outline-secondary btn-sm" id="fullscreenToggle" title="Toggle Fullscreen">
                            <i class="bi bi-arrows-fullscreen"></i>
                        </button>
                        
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" title="<?php echo esc_html__( 'Theme Settings', 'computer-repair-shop' ); ?>">
                                <i class="bi bi-palette"></i>
                            </button>
                            <ul class="dropdown-menu rounded-md rounded-3 p-0">
                                <li>
                                    <div class="btn-group w-100" role="group">
                                        <button type="button" class="btn theme-option" data-theme="light" title="<?php echo esc_html__( 'Light Mode', 'computer-repair-shop' ); ?>">
                                            <i class="bi bi-sun"></i>
                                        </button>
                                        <button type="button" class="btn border-start border-end theme-option" data-theme="dark" title="<?php echo esc_html__( 'Dark Mode', 'computer-repair-shop' ); ?>">
                                            <i class="bi bi-moon"></i>
                                        </button>
                                        <button type="button" class="btn theme-option" data-theme="auto" title="<?php echo esc_html__( 'Auto Mode', 'computer-repair-shop' ); ?>">
                                            <i class="bi bi-circle-half"></i>
                                        </button>
                                    </div>
                                </li>
                            </ul>
                        </div>

                        <!-- New Job Button -->
                         <?php
                            $_jobURL = ( $role == 'administrator' || $role == 'store_manager' || $role == 'technician' ) ? esc_url( admin_url('post-new.php?post_type=rep_jobs') ) : add_query_arg( array( 'screen' => 'book-my-device' ), get_the_permalink( $_mainpage ) );
                        ?>
                        <a class="btn btn-primary btn-sm" href="<?php echo esc_url( $_jobURL ); ?>" target="_blank">
                            <i class="bi bi-plus-circle me-1"></i><?php echo esc_html__( 'New Job', 'computer-repair-shop' ); ?>
                        </a>

                        <?php
                            $current_user = wp_get_current_user();
                            $user_id = $current_user->ID;
                            $first_name = $current_user->first_name;
                            $display_name = $current_user->display_name;

                            // Check for custom avatar
                            $custom_avatar_id = get_user_meta($user_id, 'custom_avatar', true);
                            $custom_avatar_url = get_user_meta($user_id, 'custom_avatar_url', true);

                            // Get user initials for fallback
                            $initials = '';
                            if (!empty($first_name)) {
                                $initials = strtoupper(substr($first_name, 0, 1));
                            } elseif (!empty($display_name)) {
                                $initials = strtoupper(substr($display_name, 0, 1));
                            } else {
                                $initials = 'U';
                            }
                            ?>

                            <!-- User Menu with Custom Avatar Support -->
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown">
                                    <?php if ($custom_avatar_url): ?>
                                        <img src="<?php echo esc_url($custom_avatar_url); ?>" class="rounded-circle me-2" width="32" height="32" alt="<?php echo esc_attr($display_name); ?>">
                                    <?php elseif ($custom_avatar_id): ?>
                                        <?php echo wp_get_attachment_image($custom_avatar_id, array(32, 32), true, array('class' => 'rounded-circle me-2')); ?>
                                    <?php else: ?>
                                        <?php 
                                        $default_avatar = get_avatar($user_id, 32, '', '', array('class' => 'rounded-circle me-2'));
                                        if ($default_avatar && !str_contains($default_avatar, 'avatar-default')) {
                                            echo wp_kses($default_avatar, array(
                                                'img' => array(
                                                    'src' => array(),
                                                    'class' => array(),
                                                    'alt' => array(),
                                                    'width' => array(),
                                                    'height' => array()
                                                )
                                            ));
                                        } else {
                                        ?>
                                            <div class="user-avatar bg-primary rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                <small class="text-white fw-bold"><?php echo esc_html($initials); ?></small>
                                            </div>
                                        <?php } ?>
                                    <?php endif; ?>
                                    
                                    <span class="user-name">
                                        <?php 
                                        if (!empty($first_name)) {
                                            echo esc_html($first_name);
                                        } else {
                                            echo esc_html($display_name);
                                        }
                                        ?>
                                    </span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <!-- Rest of the menu remains the same -->
                                    <li>
                                        <div class="dropdown-header text-muted small">
                                            <?php esc_html_e("Signed in as", "computer-repair-shop"); ?>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="dropdown-header fw-bold">
                                            <?php echo esc_html($display_name); ?>
                                        </div>
                                    </li>
                                    <li><hr class="dropdown-divider m-1"></li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo esc_url(add_query_arg('screen', 'profile', get_permalink())); ?>">
                                            <i class="bi bi-person me-2"></i><?php esc_html_e("Profile", "computer-repair-shop"); ?>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo esc_url(add_query_arg('screen', 'jobs', get_permalink())); ?>">
                                            <i class="bi bi-briefcase me-2"></i><?php esc_html_e("My Jobs", "computer-repair-shop"); ?>
                                        </a>
                                    </li>
                                    <?php if (current_user_can('manage_options')): ?>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo esc_url(admin_url('admin.php?page=wc-computer-rep-shop-handle')); ?>">
                                            <i class="bi bi-gear me-2"></i><?php esc_html_e("Settings", "computer-repair-shop"); ?>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider m-1"></li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="<?php echo esc_url(wp_logout_url(get_the_permalink())); ?>">
                                            <i class="bi bi-box-arrow-right me-2"></i><?php echo esc_html__('Logout', 'computer-repair-shop'); ?>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                    </div>
                </div>
            </div>
        </div>
    </header>