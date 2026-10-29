<?php

namespace WPJobBoard\Classes\Tools;

use WPJobBoard\Classes\AccessControl;
use WPJobBoard\Classes\ArrayHelper;
use WPJobBoard\Classes\Models\Forms;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Managing Global Tools
 * @since 1.1.0
 */
class GlobalTools
{
    // phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing -- every route passes AccessControl::checkAndPresponseError() first, which verifies the nonce

    /**
     * Form meta keys that an export file can contain and an import can write
     */
    private $allowedMetaKeys = array(
        'wpjobboard_application_builder_settings',
        'wpjobboard_submit_button_settings',
        'jobboard_confirmation_settings',
        'wpjobboard_form_design_settings',
        'wpjb_email_notifications',
        'wpjb_form_scheduling_settings',
        'wpjobboard_show_title_description',
        'application_start_timestamp',
        'application_end_timestamp',
        'job_location',
        'job_vacancies',
        'job_experince_text',
        'job_type'
    );

    private function getAllowedMetaKeys()
    {
        return (array)apply_filters('wpjobboard/form_meta_keys_for_export', $this->allowedMetaKeys);
    }

    public function registerEndpoints()
    {
        add_action('wp_ajax_wpjobboard_global_tools', array($this, 'handeEndPoint'));
    }

    public function handeEndPoint()
    {
        $route = isset($_REQUEST['route']) ? sanitize_text_field(wp_unslash($_REQUEST['route'])) : '';

        $validRoutes = array(
            'get_forms'   => 'getForms',
            'export_form' => 'exportFormJson',
            'upload_form' => 'handleImportForm'
        );

        if (isset($validRoutes[$route])) {
            AccessControl::checkAndPresponseError($route, 'tools');
            do_action('wpjobboard/doing_ajax_forms_' . $route);
            return $this->{$validRoutes[$route]}();
        }
    }

    public function getForms()
    {
        $forms = wpJobBoardDB()->table('posts')
            ->select(['ID', 'post_title'])
            ->where('post_type', 'wp_job_board')
            ->where('post_status', 'publish')
            ->orderBy('ID', 'DESC')
            ->get();
        wp_send_json_success(array(
            'forms' => $forms
        ), 200);
    }

    public function exportFormJson()
    {
        $formId = isset($_REQUEST['form_id']) ? absint($_REQUEST['form_id']) : 0;
        $form = $this->getForm($formId);

        if (!$form) {
            wp_die(esc_html__('No Form Found', 'ninja-job-board'));
        }

        $fileName = sanitize_file_name(sanitize_title($form['post_title'], 'export-form') . '-export-form-' . gmdate('Y-m-d') . '.json');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Type: application/json; charset=utf-8');
        echo wp_json_encode($form); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON file download, not HTML
        exit();
    }

    public function getForm($formId)
    {
        $form = get_post($formId, 'ARRAY_A');
        if (!$form || $form['post_type'] != 'wp_job_board') {
            return false;
        }

        $metas = wpJobBoardDB()->table('postmeta')
            ->select(['meta_key', 'meta_value'])
            ->where('post_id', $formId)
            ->get();

        $formattedMeta = array();
        foreach ($metas as $meta) {
            if (in_array($meta->meta_key, $this->getAllowedMetaKeys(), true)) {
                $formattedMeta[$meta->meta_key] = maybe_unserialize($meta->meta_value);
            }
        }

        $form['form_meta'] = $formattedMeta;

        return $form;
    }

    public function handleImportForm()
    {
        if (empty($_FILES['import_file']['tmp_name']) || !is_uploaded_file($_FILES['import_file']['tmp_name'])) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- temp path from PHP, checked with is_uploaded_file()
            wp_send_json_error(array(
                'message' => __('Please upload a json file', 'ninja-job-board')
            ), 423);
        }

        $tmpName = $_FILES['import_file']['tmp_name']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- checked with is_uploaded_file()
        if (filesize($tmpName) > 2 * MB_IN_BYTES) {
            wp_send_json_error(array(
                'message' => __('The file is too large', 'ninja-job-board')
            ), 423);
        }

        $form = json_decode(file_get_contents($tmpName), true); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local uploaded temp file
        $form = apply_filters('wpjobboard/impor_form_json_data', $form);

        // validate the $form and it's content
        if (
            !is_array($form) ||
            !isset($form['post_title']) ||
            ArrayHelper::get($form, 'post_type') != 'wp_job_board'
        ) {
            wp_send_json_error(array(
                'message' => __('Invalid File, Please upload right json file', 'ninja-job-board')
            ), 423);
        }

        do_action('wpjobboard/before_form_json_import', $form);

        $newForm = $this->createFormFromData($form);

        do_action('wpjobboard/form_json_imported', $newForm);

        wp_send_json_success(array(
            'message' => __('Form successfully imported', 'ninja-job-board'),
            'form' => $newForm
        ), 200);
    }

    public function createFormFromData($form)
    {
        // Create the form post type
        $postId = wp_insert_post(array(
            'post_title'   => sanitize_text_field(ArrayHelper::get($form, 'post_title', 'imported form ' . gmdate('Y-m-d'))),
            'post_content' => wp_kses_post(ArrayHelper::get($form, 'post_content', '')),
            'post_type'    => 'wp_job_board',
            'post_status'  => 'publish',
            'post_author'  => get_current_user_id()
        ));

        if (is_wp_error($postId) && wp_doing_ajax()) {
            wp_send_json_error(array(
                'message' => __('Something is wrong when processing the file. Please try again', 'ninja-job-board')
            ), 423);
        } else if(is_wp_error($postId)) {
            return false;
        }

        $metas = ArrayHelper::get($form, 'form_meta', array());
        if (is_array($metas)) {
            foreach ($metas as $metaKey => $metaValue) {
                if (in_array($metaKey, $this->getAllowedMetaKeys(), true)) {
                    update_post_meta($postId, $metaKey, wpJobBoardSanitize($metaValue));
                }
            }
        }

        $newForm = Forms::getForm($postId);

        return $newForm;

    }
}