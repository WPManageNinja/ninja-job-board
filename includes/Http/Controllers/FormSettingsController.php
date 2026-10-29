<?php

namespace WPJobBoard\Http\Controllers;

use WPJobBoard\Classes\ArrayHelper;
use WPJobBoard\Classes\FormPlaceholders;
use WPJobBoard\Classes\GeneralSettings;
use WPJobBoard\Classes\Models\Forms;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Per-form settings: builder, confirmation, design, scheduling and email notifications
 * @since 2.0.0
 */
class FormSettingsController extends Controller
{
    public function getBuilder(\WP_REST_Request $request)
    {
        $form = $this->requireForm($request);
        if (is_wp_error($form)) {
            return $form;
        }

        return array(
            'job_post'             => $form,
            'builder_settings'     => Forms::getBuilderSettings($form->ID),
            'components'           => GeneralSettings::getComponents(),
            'form_button_settings' => Forms::getButtonSettings($form->ID)
        );
    }

    public function saveBuilder(\WP_REST_Request $request)
    {
        $form = $this->requireForm($request);
        if (is_wp_error($form)) {
            return $form;
        }

        $builderSettings = $this->settings($request, 'builder_settings');

        $errors = array();
        foreach ($builderSettings as $index => $builderSetting) {
            if (!is_array($builderSetting) || empty($builderSetting['type']) || empty($builderSetting['id'])) {
                unset($builderSettings[$index]);
                continue;
            }
            $builderSettings[$index]['type'] = sanitize_key($builderSetting['type']);
            $builderSettings[$index]['id'] = sanitize_key($builderSetting['id']);
            $builderSetting = $builderSettings[$index];

            $error = apply_filters('wpjobboard/validate_component_on_save_' . $builderSetting['type'], false, $builderSetting, $form->ID);
            if ($error) {
                $errors[$builderSetting['id']] = $error;
            }
        }

        if (!$builderSettings) {
            return $this->error(__('Validation Error, Please try again', 'ninja-job-board'), 422, array(
                'errors' => array(
                    'general' => __('Please add at least one input element', 'ninja-job-board')
                )
            ));
        }

        if ($errors) {
            return $this->error(__('Validation failed when saving the form', 'ninja-job-board'), 422, array(
                'errors' => $errors
            ));
        }

        $buttonSettings = $this->settings($request, 'submit_button_settings');
        foreach (array('css_class', 'button_style') as $classKey) {
            if (isset($buttonSettings[$classKey]) && is_string($buttonSettings[$classKey])) {
                $buttonSettings[$classKey] = implode(' ', array_map('sanitize_html_class', explode(' ', $buttonSettings[$classKey])));
            }
        }

        update_post_meta($form->ID, 'wpjobboard_application_builder_settings', array_values($builderSettings));
        update_post_meta($form->ID, 'wpjobboard_submit_button_settings', $buttonSettings);

        return array(
            'message' => __('Settings successfully updated', 'ninja-job-board')
        );
    }

    public function getConfirmation(\WP_REST_Request $request)
    {
        $form = $this->requireForm($request);
        if (is_wp_error($form)) {
            return $form;
        }

        $pages = get_posts(array(
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC'
        ));

        return array(
            'confirmation_settings' => Forms::getConfirmationSettings($form->ID),
            'editor_shortcodes'     => FormPlaceholders::getAllPlaceholders($form->ID),
            'pages'                 => array_map(function ($page) {
                return array('ID' => $page->ID, 'post_title' => $page->post_title);
            }, $pages)
        );
    }

    public function saveConfirmation(\WP_REST_Request $request)
    {
        $form = $this->requireForm($request);
        if (is_wp_error($form)) {
            return $form;
        }

        $settings = $this->settings($request, 'confirmation_settings');
        if (isset($settings['customUrl'])) {
            $settings['customUrl'] = esc_url_raw($settings['customUrl']);
        }
        if (isset($settings['customPage'])) {
            $settings['customPage'] = absint($settings['customPage']);
        }

        update_post_meta($form->ID, 'jobboard_confirmation_settings', $settings);

        return array(
            'message' => __('Settings successfully updated', 'ninja-job-board')
        );
    }

    public function getDesign(\WP_REST_Request $request)
    {
        $form = $this->requireForm($request);
        if (is_wp_error($form)) {
            return $form;
        }

        return array(
            'layout_settings' => Forms::getDesignSettings($form->ID)
        );
    }

    public function saveDesign(\WP_REST_Request $request)
    {
        $form = $this->requireForm($request);
        if (is_wp_error($form)) {
            return $form;
        }

        update_post_meta($form->ID, 'wpjobboard_form_design_settings', $this->settings($request, 'layout_settings'));

        return array(
            'message' => __('Settings successfully updated', 'ninja-job-board')
        );
    }

    public function getScheduling(\WP_REST_Request $request)
    {
        $form = $this->requireForm($request);
        if (is_wp_error($form)) {
            return $form;
        }

        return array(
            'scheduling_settings' => Forms::getSchedulingSettings($form->ID),
            'current_date_time'   => gmdate('d M Y H:i:s')
        );
    }

    public function saveScheduling(\WP_REST_Request $request)
    {
        $form = $this->requireForm($request);
        if (is_wp_error($form)) {
            return $form;
        }

        $settings = $this->settings($request, 'settings');

        if (
            ArrayHelper::get($settings, 'limitNumberOfEntries.status') == 'no' &&
            ArrayHelper::get($settings, 'scheduleForm.status') == 'no' &&
            ArrayHelper::get($settings, 'requireLogin.status') == 'no'
        ) {
            $settings = false;
        }

        if (ArrayHelper::get($settings, 'scheduleForm.status') != 'yes') {
            update_post_meta($form->ID, 'application_start_timestamp', 0);
            update_post_meta($form->ID, 'application_end_timestamp', 0);
        } else {
            update_post_meta($form->ID, 'application_start_timestamp', intval(strtotime(ArrayHelper::get($settings, 'scheduleForm.start_date'))));
            update_post_meta($form->ID, 'application_end_timestamp', intval(strtotime(ArrayHelper::get($settings, 'scheduleForm.end_date'))));
        }

        update_post_meta($form->ID, 'wpjb_form_scheduling_settings', $settings);

        return array(
            'message' => __('Settings successfully updated', 'ninja-job-board')
        );
    }

    public function getNotifications(\WP_REST_Request $request)
    {
        $form = $this->requireForm($request);
        if (is_wp_error($form)) {
            return $form;
        }

        $notifications = get_post_meta($form->ID, 'wpjb_email_notifications', true);

        return array(
            'notifications'        => $notifications ? $notifications : array(),
            'merge_tags'           => FormPlaceholders::getAllPlaceholders($form->ID),
            'notification_actions' => array_values($this->notificationActions($form->ID))
        );
    }

    public function saveNotifications(\WP_REST_Request $request)
    {
        $form = $this->requireForm($request);
        if (is_wp_error($form)) {
            return $form;
        }

        $notifications = $this->settings($request, 'notifications');

        // Only the hooks that the UI offers can trigger a notification
        $allowedActions = array_keys($this->notificationActions($form->ID));
        foreach ($notifications as $index => $notification) {
            if (!is_array($notification) || !in_array(ArrayHelper::get($notification, 'sending_action'), $allowedActions, true)) {
                unset($notifications[$index]);
            }
        }

        update_post_meta($form->ID, 'wpjb_email_notifications', array_values($notifications));

        return array(
            'message' => __('Email Notifications has been updated', 'ninja-job-board')
        );
    }

    private function notificationActions($formId)
    {
        return apply_filters('wpjobboard/email_notification_actions', array(
            'wpjobboard/after_form_submission_complete' => array(
                'hook_name'   => 'wpjobboard/after_form_submission_complete',
                'hook_title'  => __('After Form Submission', 'ninja-job-board'),
                'description' => __('Send email when the form will be submitted.', 'ninja-job-board')
            )
        ), $formId);
    }
}
