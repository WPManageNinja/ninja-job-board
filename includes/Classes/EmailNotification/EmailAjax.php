<?php

namespace WPJobBoard\Classes\EmailNotification;


use WPJobBoard\Classes\AccessControl;
use WPJobBoard\Classes\ArrayHelper;
use WPJobBoard\Classes\FormPlaceholders;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ajax Handler Class for Email Notification
 * @since 1.0.0
 */
class EmailAjax
{

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
        $formId = intval($_REQUEST['form_id']);
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
        $formId = intval($_REQUEST['form_id']);
        $notifications = wpJobBoardSanitize(wp_unslash(ArrayHelper::get($_REQUEST, 'notifications')));

        update_post_meta($formId, 'wpjb_email_notifications', $notifications);

        wp_send_json_success(array(
            'message' => __('Email Notifications has been updated', 'wphobboard')
        ), 200);
    }
}
