<?php

namespace WPJobBoard\Http\Controllers;

use WPJobBoard\Classes\GeneralSettings;
use WPJobBoard\Classes\SpamProtection;

if (!defined('ABSPATH')) {
    exit;
}

class SettingsController extends Controller
{
    public function show(\WP_REST_Request $request)
    {
        $spam = SpamProtection::getSettings();

        return array(
            'ip_logging_status'          => GeneralSettings::ipLoggingStatus(),
            'delete_data_on_uninstall'   => get_option('wpjobboard_delete_data_on_uninstall') === 'yes' ? 'yes' : 'no',
            'turnstile_enabled'          => $spam['turnstile_enabled'],
            'turnstile_site_key'         => $spam['turnstile_site_key'],
            // The secret never goes back to the browser
            'turnstile_secret_saved'     => !empty($spam['turnstile_secret_key'])
        );
    }

    public function update(\WP_REST_Request $request)
    {
        $yesNo = function ($value) {
            return ($value === 'yes') ? 'yes' : 'no';
        };

        $spam = SpamProtection::getSettings();
        $spam['turnstile_enabled'] = $yesNo($request->get_param('turnstile_enabled'));
        $spam['turnstile_site_key'] = sanitize_text_field((string)$request->get_param('turnstile_site_key'));
        $secret = sanitize_text_field((string)$request->get_param('turnstile_secret_key'));
        if ($secret !== '') {
            // An empty field keeps the saved secret
            $spam['turnstile_secret_key'] = $secret;
        }

        if ($spam['turnstile_enabled'] === 'yes' && (!$spam['turnstile_site_key'] || !$spam['turnstile_secret_key'])) {
            return $this->error(__('Add the Turnstile site key and secret key, or turn Turnstile off.', 'ninja-job-board'));
        }

        update_option('wpjobboard_ip_logging_status', $yesNo($request->get_param('ip_logging_status')), false);
        update_option('wpjobboard_delete_data_on_uninstall', $yesNo($request->get_param('delete_data_on_uninstall')), false);
        update_option(SpamProtection::OPTION, $spam, false);

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
