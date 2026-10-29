<?php

namespace WPJobBoard\Classes\Tools;

use WPJobBoard\Classes\ArrayHelper;
use WPJobBoard\Classes\Models\Forms;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Form export, import and duplication helpers
 * @since 1.1.0
 */
class GlobalTools
{
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

        if (is_wp_error($postId) || !$postId) {
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