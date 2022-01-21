<?php

namespace WPJobBoard\Classes;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Global Settings Handler
 * @since 1.0.0
 */
class GlobalSettingsHandler
{
    public function registerHooks()
    {
        add_action('wp_ajax_wpjb_global_settings_handler', array($this, 'handleEndpoints'));
    }

    public function handleEndpoints()
    {
        $routes = array(
            'get_global_settings'    => 'getGeneralSettings',
            'update_global_settings' => 'updateGenetalSettings',
            'wpjb_upload_image'       => 'handleFileUpload'
        );
        $route = sanitize_text_field($_REQUEST['route']);
        if (isset($routes[$route])) {
            AccessControl::checkAndPresponseError($route, 'global');
            do_action('wpjobboard/doing_ajax_global_' . $route);
            $this->{$routes[$route]}();
            return;
        }
    }

    protected function getGeneralSettings()
    {
        wp_send_json_success(array(
            'ip_logging_status' => GeneralSettings::ipLoggingStatus()
        ), 200);
    }

    protected function updateGenetalSettings()
    {
        update_option('wpjobboard_ip_logging_status', sanitize_text_field($_REQUEST['ip_logging_status']), false);
        wp_send_json_success(array(
            'message' => __('Settings successfully updated', 'ninja-job-board')
        ), 200);
    }

    protected function handleFileUpload()
    {
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        $uploadedfile = $_FILES['file'];

        $acceptedFilles = array(
            'image/png',
            'image/jpeg'
        );

        if (!in_array($uploadedfile['type'], $acceptedFilles)) {
            wp_send_json(__('Please upload only jpg/png format files', 'ninja-job-board'), 423);
        }

        $upload_overrides = array('test_form' => false);
        $movefile = wp_handle_upload($uploadedfile, $upload_overrides);
        if ($movefile && !isset($movefile['error'])) {
            wp_send_json_success(array(
                'file' => $movefile
            ), 200);
        } else {
            wp_send_json(__('Something is wrong when uploading the file', 'ninja-job-board'), 423);
        }
    }
}
