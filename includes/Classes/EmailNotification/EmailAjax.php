<?php

namespace WPJobBoard\Classes\EmailNotification;


use WPJobBoard\Classes\AccessControl;
use WPJobBoard\Classes\ArrayHelper;
use WPJobBoard\Classes\FormPlaceholders;
use WPJobBoard\Classes\Models\Forms;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ajax Handler Class for Email Notification
 * @since 1.0.0
 */
class EmailAjax
{
    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- every route passes AccessControl::checkAndPresponseError() first, which verifies the nonce

    public function register()
    {
        add_action('wpjobboard/admin_ajax_handler_catch', array($this, 'handle'));
    }

    public function handle($route)
    {
        $validRoutes = array(
            'get_email_notifications'  => 'getNotifications',
            'save_email_notifications' => 'saveNotifications'
        );

        if (isset($validRoutes[$route])) {
            AccessControl::checkAndPresponseError($route, 'forms');
            do_action('wpjobboard/doing_ajax_forms_' . $route);
            return $this->{$validRoutes[$route]}();
        }
    }

    public function getNotifications()
    {
        $formId = isset($_REQUEST['form_id']) ? absint($_REQUEST['form_id']) : 0;
        $notifications = get_post_meta($formId, 'wpjb_email_notifications', true);
        if (!$notifications) {
            $notifications = array();
        }

        $notificationActions = array(
            'wpjobboard/after_form_submission_complete' => array(
                'hook_name'   => 'wpjobboard/after_form_submission_complete',
                'hook_title'  => 'After Form Submission',
                'description' => 'Send email when the form will be submitted.'
            )
        );

        $notificationActions = apply_filters('wpjobboard/email_notification_actions', $notificationActions, $formId);

        wp_send_json_success(array(
            'notifications'        => $notifications,
            'merge_tags'           => FormPlaceholders::getAllPlaceholders($formId),
            'notification_actions' => array_values($notificationActions)
        ), 200);
    }

    public function saveNotifications()
    {
        $formId = isset($_REQUEST['form_id']) ? absint($_REQUEST['form_id']) : 0;
        if (!Forms::getForm($formId)) {
            wp_send_json_error(array(
                'message' => __('Job post not found', 'ninja-job-board')
            ), 404);
        }
        $notifications = wpJobBoardSanitize(wp_unslash(ArrayHelper::get($_REQUEST, 'notifications', []))); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized by wpJobBoardSanitize()

        // Only the hooks that the UI offers can trigger a notification
        $allowedActions = array_keys(apply_filters('wpjobboard/email_notification_actions', array(
            'wpjobboard/after_form_submission_complete' => true
        ), $formId));
        foreach ((array)$notifications as $index => $notification) {
            if (!is_array($notification) || !in_array(ArrayHelper::get($notification, 'sending_action'), $allowedActions, true)) {
                unset($notifications[$index]);
            }
        }
        $notifications = array_values((array)$notifications);

        update_post_meta($formId, 'wpjb_email_notifications', $notifications);

        wp_send_json_success(array(
            'message' => __('Email Notifications has been updated', 'ninja-job-board')
        ), 200);
    }
}
