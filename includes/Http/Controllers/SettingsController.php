<?php

namespace WPJobBoard\Http\Controllers;

use WPJobBoard\Classes\GeneralSettings;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Global plugin settings
 * @since 2.0.0
 */
class SettingsController extends Controller
{
    public function show(\WP_REST_Request $request)
    {
        return array(
            'ip_logging_status' => GeneralSettings::ipLoggingStatus()
        );
    }

    public function update(\WP_REST_Request $request)
    {
        $status = $request->get_param('ip_logging_status');
        update_option('wpjobboard_ip_logging_status', ($status === 'yes') ? 'yes' : 'no', false);

        return array(
            'message' => __('Settings successfully updated', 'ninja-job-board')
        );
    }

    public function uploadImage(\WP_REST_Request $request)
    {
        $files = $request->get_file_params();
        if (empty($files['file']) || !is_array($files['file'])) {
            return $this->error(__('Please upload only jpg/png format files', 'ninja-job-board'));
        }

        if (!function_exists('wp_handle_upload')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        // WordPress checks the real file type against this list, not the client's Content-Type
        $result = wp_handle_upload($files['file'], array(
            'test_form' => false,
            'test_type' => true,
            'mimes'     => array(
                'jpg|jpeg|jpe' => 'image/jpeg',
                'png'          => 'image/png'
            )
        ));

        if (!$result || isset($result['error'])) {
            return $this->error(isset($result['error']) ? $result['error'] : __('Something is wrong when uploading the file', 'ninja-job-board'));
        }

        return array(
            'file' => array(
                'url'  => esc_url_raw($result['url']),
                'type' => $result['type']
            )
        );
    }
}
