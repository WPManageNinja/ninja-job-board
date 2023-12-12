<?php
/**
 * Plugin Name: Ninja Job Board
 * Plugin URI:  https://github.com/WPManageNinja/wp-job-board
 * Description: Create Job Posting and Manage Jon Application In WordPress
 * Author: WPManageNinja LLC
 * Author URI:  https://wpmanageninja.com
 * Version: 1.4.0
 * Text Domain: ninja-job-board
 *
 */

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright 2019 WPManageNinja LLC. All rights reserved.
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WPJOBBOARD_VERSION_LITE', true);
define('WPJOBBOARD_VERSION', '1.3.3');
define('WPJOBBOARD_DB_VERSION', 100);
define('WPJOBBOARD_MAIN_FILE', __FILE__);
define('WPJOBBOARD_URL', plugin_dir_url(__FILE__));
define('WPJOBBOARD_DIR', plugin_dir_path(__FILE__));
define('WPJOBBOARD_UPLOAD_DIR', '/wpjobboard');

class WPJobBoard
{
    public function boot()
    {
        $this->textDomain();
        $this->loadDependecies();
        if (is_admin()) {
            $this->adminHooks();
        }
        $this->commonActions();
        $this->registerShortcodes();
        $this->loadComponents();
    }

    public function adminHooks()
    {
        // Init The Classes
        // Register Admin menu
        $menu = new \WPJobBoard\Classes\Menu();
        $menu->register();

        add_action('wpjobboard/render_admin_app', function () {
            $adminApp = new \WPJobBoard\Classes\AdminApp();
            $adminApp->bootView();
        });

        // Top Level Ajax Handlers
        $ajaxHandler = new \WPJobBoard\Classes\AdminAjaxHandler();
        $ajaxHandler->registerEndpoints();

        // Submission Ajax Handler
        $submissionHandler = new \WPJobBoard\Classes\SubmissionView();
        $submissionHandler->registerEndpoints();

        // Exporter Ajax Handler
        $exoprtHandler = new \WPJobBoard\Classes\Exporter();
        $exoprtHandler->registerEndpoints();

        // General Settings Handler
        $globalSettingHandler = new \WPJobBoard\Classes\GlobalSettingsHandler();
        $globalSettingHandler->registerHooks();

        // Handle Globla Tools
        $globalTools = new \WPJobBoard\Classes\Tools\GlobalTools();
        $globalTools->registerEndpoints();

        $scheduleSettings = new \WPJobBoard\Classes\Tools\SchedulingSettings();
        $scheduleSettings->register();

        // init tinymce
        $tinyMCE = new \WPJobBoard\Classes\Integrations\TinyMceBlock();
        $tinyMCE->register();

        // Dashboard Widget Here
        $dashboardWidget = new \WPJobBoard\Classes\DashboardWidgetModule();
        $dashboardWidget->register();

        // Email Notification Handler
        $emailAjaxEndpoints = new \WPJobBoard\Classes\EmailNotification\EmailAjax();
        $emailAjaxEndpoints->register();

        // Email Campaigns
        $emailCampaigns = new \WPJobBoard\Classes\EmailCampaigns\EmailCampaign();
        $emailCampaigns->boot();

    }

    public function registerShortcodes()
    {
        // Register the shortcode
        add_shortcode('wp_job_form', function ($args) {
            $args = shortcode_atts(array(
                'id'         => '',
                'show_title' => false
            ), $args);

            if (!$args['id']) {
                return;
            }

            $builder = new \WPJobBoard\Classes\Builder\Render();
            return $builder->render($args['id'], $args['show_title'], $args['show_description']);
        });
        add_shortcode('wp_job_list', function ($args) {
            $args = shortcode_atts(array(
                'categories'     => '',
                'order'          => 'DESC',
                'title'          => __('Job List', 'ninja-job-board'),
                'show_apply'     => 'yes',
                'current_only'   => 'yes',
                'show_cat'       => 'yes',
                'show_filter'    => 'yes',
                'posts_per_page' => 200
            ), $args);
            $builder = new \WPJobBoard\Classes\Builder\RenderJobList();
            return $builder->render($args);
        });
        add_shortcode('wpjobboard_confirmation', function () {
            if (isset($_REQUEST['wpjb_submission']) && $_REQUEST['wpjb_submission']) {
                $submissionHash = sanitize_text_field($_REQUEST['wpjb_submission']);
                $submission = wpJobBoardDB()->table('wjb_applications')
                    ->where('submission_hash', '=', $submissionHash)
                    ->first();
                if ($submission) {
                    $receiptHandler = new \WPJobBoard\Classes\Builder\ApplicationConfirmation();
                    return $receiptHandler->render($submission->id);
                } else {
                    return '<p class="wpjb_no_recipt_found">' . __('Sorry, no submission confirmation found, Please check your URL', 'ninja-job-board') . '</p>';
                }
            } else {
                return '<p class="wpjb_no_recipt_found">' . __('Sorry, no submission conirmation found, Please check your URL', 'ninja-job-board') . '</p>';
            }
        });
    }

    public function commonActions()
    {
        // Form Submission Handler
        $submissionHandler = new \WPJobBoard\Classes\SubmissionHandler();
        add_action('wp_ajax_wpjb_submit_form', array($submissionHandler, 'handeSubmission'));
        add_action('wp_ajax_nopriv_wpjb_submit_form', array($submissionHandler, 'handeSubmission'));

        $scheduleSettings = new \WPJobBoard\Classes\Tools\SchedulingSettings();
        $scheduleSettings->checkRestrictionHooks();

        // Register Post Type
        new \WPJobBoard\Classes\PostType();


        // Default value Parser
        $formDefaultValueRenderer = new \WPJobBoard\Classes\DefaultValueParser\FormDefaultValueRenderer();
        $formDefaultValueRenderer->register();


        $emailHandler = new \WPJobBoard\Classes\EmailNotification\EmailHandler();
        $emailHandler->register();
    }

    public function loadComponents()
    {
        require_once WPJOBBOARD_DIR . 'includes/Classes/FormComponents/init.php';
    }

    public function textDomain()
    {
        load_plugin_textdomain('ninja-job-board', false, basename(dirname(__FILE__)) . '/languages');
    }

    public function loadDependecies()
    {
        require_once(WPJOBBOARD_DIR . 'includes/autoload.php');
    }
}

add_action('plugins_loaded', function () {
    // Let's check again if Pro version is available or not
    (new WPJobBoard())->boot();
});
register_activation_hook(__FILE__, function ($newWorkWide) {
    require_once(WPJOBBOARD_DIR . 'includes/Classes/Activator.php');
    $activator = new \WPJobBoard\Classes\Activator();
    $activator->migrateDatabases($newWorkWide);
});

// Handle Newtwork new Site Activation
add_action('wpmu_new_blog', function ($blogId) {
    require_once(WPJOBBOARD_DIR . 'includes/Classes/Activator.php');
    switch_to_blog($blogId);
    (new \WPJobBoard\Classes\Activator)->migrate();
    restore_current_blog();
});
