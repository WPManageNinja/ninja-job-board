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
            __('WP Job Board', 'wpjobboard'),
            __('WP Job Board', 'wpjobboard'),
            $menuPermission,
            'wpjobboard.php',
            array($this, 'render'),
            $this->getIcon(),
            25
        );

        $submenu['wpjobboard.php']['all_forms'] = array(
            __('All Jobs', 'wpjobboard'),
            $menuPermission,
            'admin.php?page=wpjobboard.php#/',
        );
        $submenu['wpjobboard.php']['entries'] = array(
            __('Applications', 'wpjobboard'),
            $menuPermission,
            'admin.php?page=wpjobboard.php#/entries',
        );
        $submenu['wpjobboard.php']['settings'] = array(
            __('Settings', 'wpjobboard'),
            $menuPermission,
            'admin.php?page=wpjobboard.php#/settings/general-settings',
        );

        $submenu['wpjobboard.php']['job_categories'] = array(
            __('Job Categories', 'wpjobboard'),
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
                    'All Job Posts' => __('All Job Posts', 'wpjobboard')
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
        $svg = '<?xml version="1.0" encoding="UTF-8"?><svg enable-background="new 0 0 512 512" version="1.1" viewBox="0 0 512 512" xml:space="preserve" xmlns="http://www.w3.org/2000/svg">
		<path d="m446 0h-380c-8.284 0-15 6.716-15 15v482c0 8.284 6.716 15 15 15h380c8.284 0 15-6.716 15-15v-482c0-8.284-6.716-15-15-15zm-15 482h-350v-452h350v452z" fill="#fff"/>
		<path d="m313 151h-2v-23c0-30.327-24.673-55-55-55s-55 24.673-55 55v23h-2c-8.284 0-15 6.716-15 15v78c0 8.284 6.716 15 15 15h114c8.284 0 15-6.716 15-15v-78c0-8.284-6.716-15-15-15zm-82-23c0-13.785 11.215-25 25-25s25 11.215 25 25v23h-50v-23zm67 101h-84v-48h84v48z" fill="#fff"/>
		<path d="m166.43 318h-22.857c-4.734 0-8.571 3.838-8.571 8.571v22.857c0 4.734 3.838 8.571 8.571 8.571h22.857c4.734 0 8.571-3.838 8.571-8.571v-22.857c0-4.733-3.838-8.571-8.571-8.571z" fill="#fff"/>
		<path d="m377 323h-142c-8.284 0-15 6.716-15 15s6.716 15 15 15h142c8.284 0 15-6.716 15-15s-6.716-15-15-15z" fill="#fff"/>
		<path d="m166.43 398h-22.857c-4.734 0-8.571 3.838-8.571 8.571v22.857c0 4.734 3.838 8.571 8.571 8.571h22.857c4.734 0 8.571-3.838 8.571-8.571v-22.857c0-4.733-3.838-8.571-8.571-8.571z" fill="#fff"/>
		<path d="m377 403h-142c-8.284 0-15 6.716-15 15s6.716 15 15 15h142c8.284 0 15-6.716 15-15s-6.716-15-15-15z" fill="#fff"/></svg>';
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
                <a href="<?php echo $urlBase; ?>form-builder">
                    <i class="dashicons dashicons-lightbulb"></i>
                    <span><?php _e('Application Form', 'wpjobboard');?></span>
                </a>
            </li>
            <li role="menuitem" tabindex="2" class="el-menu-item" style="border-bottom-color: transparent;">
                <a href="<?php echo $urlBase; ?>settings/confirmation_settings">
                    <i class="dashicons dashicons-admin-settings"></i>
                    <span><?php _e('Form Settings', 'wpjobboard');?></span>
                </a>
            </li>
            <li role="menuitem" tabindex="3" class="el-menu-item" style="border-bottom-color: transparent;">
                <a href="<?php echo $urlBase; ?>email_settings">
                    <i class="dashicons dashicons-email-alt"></i>
                    <span><?php _e('Email Notifications', 'wpjobboard');?></span>
                </a>
            </li>
            <li role="menuitem" tabindex="4" class="el-menu-item" style="border-bottom-color: transparent;">
                <a href="<?php echo $urlBase; ?>form_entries">
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