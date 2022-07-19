<?php

namespace WPJobBoard\Classes;

use WPJobBoard\Classes\Models\Forms;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Menu and Admin Pages
 * @since 1.0.0
 */
class Menu
{
    public function register()
    {
        add_action('admin_menu', array($this, 'addMenus'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueAssets'));
        add_action('edit_form_top', array($this, 'pushJobNav'));
        add_action('admin_footer', array($this, 'maybeMenuExpand'));
    }

    public function addMenus()
    {
        $menuPermission = AccessControl::hasTopLevelMenuPermission();
        if (!$menuPermission) {
            return;
        }

        global $submenu;


        add_menu_page(
            __('WP Job Board', 'ninja-job-board'),
            __('WP Job Board', 'ninja-job-board'),
            $menuPermission,
            'wpjobboard.php',
            array($this, 'render'),
            $this->getIcon(),
            25
        );

        $submenu['wpjobboard.php']['all_forms'] = array(
            __('All Jobs', 'ninja-job-board'),
            $menuPermission,
            'admin.php?page=wpjobboard.php#/',
        );
        $submenu['wpjobboard.php']['entries'] = array(
            __('Applications', 'ninja-job-board'),
            $menuPermission,
            'admin.php?page=wpjobboard.php#/entries',
        );
        $submenu['wpjobboard.php']['settings'] = array(
            __('Settings', 'ninja-job-board'),
            $menuPermission,
            'admin.php?page=wpjobboard.php#/settings/general-settings',
        );

        $submenu['wpjobboard.php']['job_categories'] = array(
            __('Job Categories', 'ninja-job-board'),
            $menuPermission,
            'edit-tags.php?taxonomy=wpjb-job-categories&post_type=wp_job_board',
        );


//        $submenu['wpjobboard.php']['support'] = array(
//            __('Support', 'wpjobboard'),
//            $menuPermission,
//            'admin.php?page=wpjobboard.php#/support',
//        );
    }

    public function render()
    {
        do_action('wpjobboard/render_admin_app');
    }

    public function enqueueAssets()
    {
        if (isset($_GET['page']) && $_GET['page'] == 'wpjobboard.php') {

            if(is_dir(wp_upload_dir()['basedir'] . WPJOBBOARD_UPLOAD_DIR) && !file_exists(wp_upload_dir()['basedir'] . WPJOBBOARD_UPLOAD_DIR . '/index.php')) {
                file_put_contents(
                    wp_upload_dir()['basedir'] . WPJOBBOARD_UPLOAD_DIR . '/index.php',
                    file_get_contents(WPJOBBOARD_DIR.'includes/Classes/File/Stubs/index.stub')
                );
            }

            if (function_exists('wp_enqueue_editor')) {
                wp_enqueue_editor();
                wp_enqueue_script('thickbox');
            }
            if (function_exists('wp_enqueue_media')) {
                wp_enqueue_media();
            }

            if (function_exists('wp_enqueue_editor')) {
                wp_enqueue_editor();
            }
            wp_enqueue_script('wpjobboard_boot', WPJOBBOARD_URL . 'assets/js/jobboard-boot.js', array('jquery'), WPJOBBOARD_VERSION, true);
            // 3rd party developers can now add their scripts here
            do_action('wpjobboard/booting_admin_app');
            wp_enqueue_script('wpjobboard_admin_app', WPJOBBOARD_URL . 'assets/js/jobboard-admin.js', array('wpjobboard_boot'), WPJOBBOARD_VERSION, true);
            wp_enqueue_style('wpjobboard_admin_app', WPJOBBOARD_URL . 'assets/css/jobboard-admin.css', array(), WPJOBBOARD_VERSION);

            $payformAdminVars = apply_filters('wpjobboard/admin_app_vars', array(
                'i18n'                => array(
                    'All Job Posts' => __('All Job Posts', 'ninja-job-board')
                ),
                'applicationStatuses' => GeneralSettings::getApplicationStatuses(),
                'internalStatuses'    => GeneralSettings::getInternalStatuses(),
                'image_upload_url'    => admin_url('admin-ajax.php?action=wpjb_global_settings_handler&route=wpjb_upload_image'),
                'forms_count'         => Forms::getTotalCount(),
                'assets_url'          => WPJOBBOARD_URL . 'assets/',
                'ajaxurl'             => admin_url('admin-ajax.php'),
                'printStyles'         => apply_filters('wpjobboard/print_styles', [
                    WPJOBBOARD_URL . 'assets/css/jobboard-admin.css',
                    WPJOBBOARD_URL . 'assets/css/jobboard-print.css',
                ]),
                'ace_path_url'        => WPJOBBOARD_URL . 'assets/libs/ace'
            ));

            wp_localize_script('wpjobboard_boot', 'wpJobBoardsAdmin', $payformAdminVars);
        }

        global $post;
        if (!$post || $post->post_type != 'wp_job_board') {
            return;
        }

        wp_enqueue_style('wpjobboard_admin_edit_app', WPJOBBOARD_URL . 'assets/css/jobboard-edit.css', array(), WPJOBBOARD_VERSION);

    }

    public function getIcon()
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 220.17 200.16"><defs><style>.a{fill:#fff;}</style></defs><title>Asset 860</title><path class="a" d="M168.28,56.61H56.89A15.88,15.88,0,0,0,41,72.5V251.88a15.89,15.89,0,0,0,15.89,15.89H168.28a15.9,15.9,0,0,0,15.89-15.89V72.5a15.89,15.89,0,0,0-15.88-15.89Zm-23,14.88a14.05,14.05,0,1,1-14,14.06h0A14,14,0,0,1,145.25,71.49Zm-89.69,5.6a4.67,4.67,0,0,1,4.67-4.65h75a16.12,16.12,0,0,0-2.52,2.14,15.54,15.54,0,0,0-4.38,10.86A16.15,16.15,0,0,0,137.71,100a11,11,0,0,0,1.07.45H60.24a4.68,4.68,0,0,1-4.68-4.67h0ZM169.08,251a1.41,1.41,0,0,1-1.41,1.4H57a1.41,1.41,0,0,1-1.41-1.4h0v-4.45a1.4,1.4,0,0,1,1.41-1.4H167.67a1.4,1.4,0,0,1,1.41,1.4h0Zm0-27.93H55.57V207.29A1.41,1.41,0,0,1,57,205.88H167.67a1.41,1.41,0,0,1,1.41,1.41Zm0-41.61H55.57V165.56A1.41,1.41,0,0,1,57,164.15H167.67a1.41,1.41,0,0,1,1.41,1.41Zm0-41.6H55.57v-16a1.4,1.4,0,0,1,1.41-1.4H167.67a1.4,1.4,0,0,1,1.41,1.4Zm0-44.09a4.7,4.7,0,0,1-4.68,4.67h0l-5.06-5.18-.41.38,0,0-.35-.36A15.31,15.31,0,0,0,160.94,91a12.52,12.52,0,0,0,.53-1.78,16.08,16.08,0,0,0,.34-2,13.54,13.54,0,0,0,.06-1.8,14,14,0,0,0-.18-2.24,15.9,15.9,0,0,0-5-9.23,16.45,16.45,0,0,0-1.77-1.45h9.55a4.67,4.67,0,0,1,4.67,4.65Z" transform="translate(-41 -56.61)"/></svg>';
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    public function pushJobNav($post)
    {
        if (!$post || $post->post_type != 'wp_job_board') {
            return;
        }


        add_action('admin_footer', function () {
            echo '<script type="text/javascript">jQuery("#toplevel_page_wpjobboard").addClass("wp-has-current-submenu wp-menu-open");</script>';
        });

        wp_enqueue_style('wpjobboard_admin_edit_app', WPJOBBOARD_URL . 'assets/css/jobboard-edit.css', array(), WPJOBBOARD_VERSION);
        $urlBase = admin_url('admin.php?page=wpjobboard.php#/edit-form/' . $post->ID . '/');
        ?>
        <ul role="menubar" class="el-menu--horizontal el-menu">
            <li role="menuitem" class="el-menu-item  is-active" tabindex="0">
                <a href="#">
                    <i class="dashicons dashicons-edit"></i>
                    <span><?php _e('Post Details', 'wpjobboard'); ?></span>
                </a>
            </li>
            <li role="menuitem" tabindex="1" class="el-menu-item">
                <a href="<?php echo esc_url($urlBase); ?>form-builder">
                    <i class="dashicons dashicons-lightbulb"></i>
                    <span><?php _e('Application Form', 'wpjobboard');?></span>
                </a>
            </li>
            <li role="menuitem" tabindex="2" class="el-menu-item" style="border-bottom-color: transparent;">
                <a href="<?php echo esc_url($urlBase); ?>settings/confirmation_settings">
                    <i class="dashicons dashicons-admin-settings"></i>
                    <span><?php _e('Form Settings', 'wpjobboard');?></span>
                </a>
            </li>
            <li role="menuitem" tabindex="3" class="el-menu-item" style="border-bottom-color: transparent;">
                <a href="<?php echo esc_url($urlBase); ?>email_settings">
                    <i class="dashicons dashicons-email-alt"></i>
                    <span><?php _e('Email Notifications', 'wpjobboard');?></span>
                </a>
            </li>
            <li role="menuitem" tabindex="4" class="el-menu-item" style="border-bottom-color: transparent;">
                <a href="<?php echo esc_url($urlBase); ?>form_entries">
                    <i class="dashicons dashicons-text"></i>
                    <span><?php _e('Job Applications', 'wpjobboard');?></span>
                </a>
            </li>
        </ul>
        <?php
    }

    public function maybeMenuExpand()
    {
        if (isset($_GET['taxonomy']) && $_GET['taxonomy'] == 'wpjb-job-categories') {
            ?>
            <script type="text/javascript">
                jQuery(document).ready(function ($) {
                    jQuery("#toplevel_page_wpjobboard").addClass("wp-has-current-submenu wp-menu-open");
                    var $element = jQuery('a[href="edit-tags.php?taxonomy=wpjb-job-categories&post_type=wp_job_board"]');
                    $element.addClass('current');
                    $element.parent().addClass('current');
                });
            </script>';
<?php
        }
    }
}
