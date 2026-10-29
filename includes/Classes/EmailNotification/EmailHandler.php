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
        $notifiation = apply_filters('wpjobboard/email_notification_before_send', $notifiation, $submission);

        do_action('wpjobboard/require_entry_html');
        $notification = array(
            'from_name'     => $this->sanitizeHeaderText(PlaceholderParser::parse(ArrayHelper::get($notifiation, 'from_name'), $submission, 'text')),
            'from_email'    => sanitize_email(PlaceholderParser::parse(ArrayHelper::get($notifiation, 'from_email'), $submission, 'text')),
            'reply_to'      => sanitize_email(PlaceholderParser::parse(ArrayHelper::get($notifiation, 'reply_to'), $submission, 'text')),
            'email_to'      => $this->sanitizeEmailList(PlaceholderParser::parse(ArrayHelper::get($notifiation, 'email_to'), $submission, 'text')),
            'email_subject' => $this->sanitizeHeaderText(PlaceholderParser::parse(ArrayHelper::get($notifiation, 'email_subject'), $submission, 'text')),
            'email_body'    => wpJobBoardKsesWithStyle(PlaceholderParser::parse(ArrayHelper::get($notifiation, 'email_body'), $submission, 'html'))
        );
        do_action('wpjobboard/require_entry_html_done');

        if (!$notification['email_to'] || !$notification['email_subject'] || !$notification['email_body']) {
            return;
        }

        $notification['email_body'] = $this->getEmailWithTemplate($notification['email_body'], $submission, $notifiation);
        $headers = $this->getEmailHeader($notification);

        $result = wp_mail(
            $notification['email_to'],
            $notification['email_subject'],
            $notification['email_body'],
            $headers
        );

        $emailTo = esc_html(implode(', ', $notification['email_to']));
        $subject = esc_html($notification['email_subject']);

        if ($result) {
            /* translators: 1: recipient email addresses, 2: email subject */
            $content = sprintf(__('Email Notification sent to %1$s and the subject: %2$s.', 'ninja-job-board'), $emailTo, $subject);
        } else {
            /* translators: 1: recipient email addresses, 2: email subject */
            $content = sprintf(__('Maybe email sending failed to %1$s and the subject: %2$s, You may check wp_mail fuction and probably you should use smtp', 'ninja-job-board'), $emailTo, $subject);
        }

        SubmissionActivity::createActivity(array(
            'form_id'       => $submission->form_id,
            'submission_id' => $submission->id,
            'type'          => 'activity',
            'created_by'    => 'WPJobBoard BOT',
            'content'       => $content
        ));
    }

    public function getEmailHeader($notification)
    {
        $headers = [
            'Content-Type: text/html; charset=UTF-8'
        ];

        if ($notification['from_name'] && $notification['from_email']) {
            $headers[] = "From: {$notification['from_name']} <{$notification['from_email']}>";
        } elseif ($notification['from_email']) {
            $headers[] = "From: <{$notification['from_email']}>";
        }

        if ($notification['reply_to']) {
            $headers[] = "Reply-To: <{$notification['reply_to']}>";
        }

        return $headers;
    }

    /**
     * Header-safe single-line text
     * @param string $text
     * @return string
     */
    private function sanitizeHeaderText($text)
    {
        return trim(str_replace(array("\r", "\n", '<', '>'), '', sanitize_text_field($text)));
    }

    /**
     * Comma separated addresses to an array of valid emails
     * @param string $emails
     * @return array
     */
    private function sanitizeEmailList($emails)
    {
        $valid = array();
        foreach (explode(',', (string)$emails) as $email) {
            $email = sanitize_email(trim($email));
            if ($email && is_email($email)) {
                $valid[] = $email;
            }
        }
        return array_values(array_unique($valid));
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
        } catch (\Exception $e) {
            // Send the email without inlined CSS
        }
        return $emailBody;
    }
}