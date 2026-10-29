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
    // phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing -- every route passes AccessControl::checkAndPresponseError() first, which verifies the nonce

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
        $route = isset($_REQUEST['route']) ? sanitize_text_field(wp_unslash($_REQUEST['route'])) : '';
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
        $status = isset($_REQUEST['ip_logging_status']) ? sanitize_text_field(wp_unslash($_REQUEST['ip_logging_status'])) : 'no';
        update_option('wpjobboard_ip_logging_status', ($status === 'yes') ? 'yes' : 'no', false);
        wp_send_json_success(array(
            'message' => __('Settings successfully updated', 'ninja-job-board')
        ), 200);
    }

    protected function handleFileUpload()
    {
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        if (empty($_FILES['file']) || !is_array($_FILES['file'])) {
            wp_send_json(__('Please upload only jpg/png format files', 'ninja-job-board'), 423);
        }

        $uploadedfile = $_FILES['file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- validated by wp_handle_upload()

        // WordPress checks the real file type against this list, not the client's Content-Type
        $upload_overrides = array(
            'test_form' => false,
            'test_type' => true,
            'mimes'     => array(
                'jpg|jpeg|jpe' => 'image/jpeg',
                'png'          => 'image/png'
            )
        );
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
