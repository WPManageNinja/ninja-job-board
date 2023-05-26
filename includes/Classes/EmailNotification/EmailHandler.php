<?php

namespace WPJobBoard\Classes\EmailNotification;

use WPJobBoard\Classes\ArrayHelper;
use WPJobBoard\Classes\Models\SubmissionActivity;
use WPJobBoard\Classes\PlaceholderParser;
use WPJobBoard\Classes\View;
use WPJobBoard\Classes\Emogrifier\Emogrifier;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Email Handler Class for Email Notification
 * @since 1.0.0
 */
class EmailHandler
{
    public function register()
    {
        add_action('wpjobboard/form_submission_activity_start', array($this, 'initEmailHooks'));
        add_action('wpjobboard/send_email_notification', array($this, 'proceesEmaillNotification'), 10, 2);
    }

    public function initEmailHooks($formId)
    {
        $notifications = get_post_meta($formId, 'wpjb_email_notifications', true);
        if (!$notifications) {
            return;
        }

        // Let's filter the notifications
        $validNotifiations = array();
        foreach ($notifications as $notification) {
            $status = ArrayHelper::get($notification, 'status');
            if ($status != 'active') {
                continue;
            }
            $action = ArrayHelper::get($notification, 'sending_action');
            if (!isset($validNotifiations[$action])) {
                $validNotifiations[$action] = array();
            }
            $validNotifiations[$action][] = $notification;
        }

        if (empty($validNotifiations)) {
            return;
        }

        foreach ($validNotifiations as $notifiationAction => $notifiationInfos) {
            add_action($notifiationAction, function ($submission) use ($notifiationInfos) {
                foreach ($notifiationInfos as $notifiationData) {
                    do_action('wpjobboard/send_email_notification', $notifiationData, $submission);
                }
            });
        }
    }

    public function proceesEmaillNotification($notifiation, $submission)
    {
        do_action('wpjobboard/require_entry_html');
        $notification['from_name'] = PlaceholderParser::parse(ArrayHelper::get($notifiation, 'from_name'), $submission);
        $notification['from_email'] = PlaceholderParser::parse(ArrayHelper::get($notifiation, 'from_email'), $submission);
        $notification['reply_to'] = PlaceholderParser::parse(ArrayHelper::get($notifiation, 'reply_to'), $submission);
        $notification['email_to'] = PlaceholderParser::parse(ArrayHelper::get($notifiation, 'email_to'), $submission);
        $notification['email_subject'] = PlaceholderParser::parse(ArrayHelper::get($notifiation, 'email_subject'), $submission);
        $notification['email_body'] = PlaceholderParser::parse(ArrayHelper::get($notifiation, 'email_body'), $submission);
        $notifiation = apply_filters('wpjobboard/email_notification_before_send', $notifiation, $submission);
        do_action('wpjobboard/require_entry_html_done');

        if(!$notification['email_to'] || !$notification['email_subject'] || !$notification['email_body']) {
            return;
        }

        $notification['email_body']  = $this->getEmailWithTemplate($notification['email_body'], $submission, $notifiation);
        $headers = $this->getEmailHeader($notifiation);

        $result = wp_mail(
            $notification['email_to'],
            $notification['email_subject'],
            $notification['email_body'],
            $headers
        );

        if($result) {
            SubmissionActivity::createActivity(array(
                'form_id'       => $submission->form_id,
                'submission_id' => $submission->id,
                'type'          => 'activity',
                'created_by'    => 'WPJobBoard BOT',
                'content'       => "Email Notification sent to {$notification['email_to']} and the subject: {$notification['email_subject']}."
            ));
        } else {
            SubmissionActivity::createActivity(array(
                'form_id'       => $submission->form_id,
                'submission_id' => $submission->id,
                'type'          => 'activity',
                'created_by'    => 'WPJobBoard BOT',
                'content'       => "Maybe email sending failed to {$notification['email_to']} and the subject: {$notification['email_subject']}, You may check wp_mail fuction and probably you should use smtp"
            ));
        }
    }

    public function getEmailHeader($notification) {
        $headers = [
            'Content-Type: text/html; charset=UTF-8'
        ];

        if ($notification['from_name'] && $notification['from_email']) {
            $headers[] = "From: {$notification['from_name']} <{$notification['from_email']}>";
        } elseif ($notification['from_name']) {
            $headers[] = "From: {$notification['from_name']}";
        } elseif ($notification['from_email']) {
            $headers[] = "From: <{$notification['from_email']}>";
        }

        if ($notification['reply_to']) {
            $headers[] = "Reply-To: <{$notification['reply_to']}>";
        }

        return $headers;
    }

    public function getEmailWithTemplate($emailBody, $submission, $notification)
    {
        $emailHeader = apply_filters('wpjobboard/email_header', '', $submission, $notification);
        $emailFooter = apply_filters('wpjobboard/email_footer', '', $submission, $notification);

        if (empty($emailHeader)) {
            $emailHeader = View::make('email.default.header', array(
                'submission'   => $submission,
                'notification' => $notification
            ));
        }

        if (empty($emailFooter)) {
            $emailFooter = View::make('email.default.footer', array(
                'submission'   => $submission,
                'notification' => $notification
            ));
        }

        $css = View::make('email.default.styles');
        $css = apply_filters('wpjobboard/email_styles', $css, $submission, $notification);
        $emailBody = $emailHeader . $emailBody . $emailFooter;

        try {
            // apply CSS styles inline for picky email clients
            $emogrifier = new Emogrifier($emailBody, $css);
            $emailBody = $emogrifier->emogrify();
        } catch (Exception $e) {

        }
        return $emailBody;
    }
}