<?php

namespace WPJobBoard\Classes\EmailCampaigns;

use WPJobBoard\Classes\AccessControl;
use WPJobBoard\Classes\ArrayHelper;
use WPJobBoard\Classes\Emogrifier\Emogrifier;
use WPJobBoard\Classes\Models\Submission;
use WPJobBoard\Classes\Models\SubmissionActivity;
use WPJobBoard\Classes\View;
use WPJobBoard\Classes\GeneralSettings;
use WPJobBoard\Classes\Models\Forms;

if (!defined('ABSPATH')) {
    exit;
}

class EmailCampaign
{
    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- every route passes AccessControl::checkAndPresponseError() first, which verifies the nonce

    /**
     * Campaign filter settings with only known status keys
     */
    private function sanitizeCampaignSettings($settings)
    {
        $settings = is_array($settings) ? wpJobBoardSanitizeText($settings) : array();
        $internal = (array)ArrayHelper::get($settings, 'internal_statuses', array());
        $application = (array)ArrayHelper::get($settings, 'application_statuses', array());
        $settings['internal_statuses'] = array_values(array_intersect($internal, array_keys(GeneralSettings::getInternalStatuses())));
        $settings['application_statuses'] = array_values(array_intersect($application, array_keys(GeneralSettings::getApplicationStatuses())));
        return $settings;
    }

    private function requestFormId()
    {
        $formId = isset($_REQUEST['form_id']) ? absint($_REQUEST['form_id']) : 0;
        if (!$formId || !Forms::getForm($formId)) {
            wp_send_json_error(array(
                'message' => __('Job post not found', 'ninja-job-board')
            ), 404);
        }
        return $formId;
    }

    public function boot()
    {
        add_action('wp_ajax_wpjobboard_email_campaigns', array($this, 'ajaxHandler'));
    }

    public function ajaxHandler()
    {
        $route = isset($_REQUEST['route']) ? sanitize_text_field(wp_unslash($_REQUEST['route'])) : '';

        $validEndpoints = array(
            'get_campaigns'       => 'getCampaigns',
            'get_campaign'        => 'getCampaign',
            'saveCampaign'        => 'saveCamapign',
            'send_email_campaign' => 'sendEmailCampaign',
            'get_email_counts'    => 'getEmailCounts',
            'send_emails'         => 'sendEmails',
            'get_campaign_emails' => 'getCampaignEmails'
        );

        if (isset($validEndpoints[$route])) {
            AccessControl::checkAndPresponseError($route, 'email_campaigns');
            $this->{$validEndpoints[$route]}();
        }
        die();
    }

    public function getCampaigns()
    {
        // Check if table created or not
        global $wpdb;
        $table_name = $wpdb->prefix . 'wjb_email_campaigns';
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name)) != $table_name) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- schema check
            $activator = new \WPJobBoard\Classes\Activator();
            $activator->createCampaignDB();
        }

        $formId = $this->requestFormId();
        $campaigns = wpJobBoardDB()->table('wjb_email_campaigns')
            ->orderBy('id', 'DESC')
            ->where('form_id', $formId)
            ->paginate();

        wp_send_json_success([
            'campaigns' => $campaigns
        ], 200);
    }

    public function sendEmailCampaign()
    {
        $formId = $this->requestFormId();
        $campaign = wp_unslash(ArrayHelper::get($_REQUEST, 'campaign', array())); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- each field is sanitized below
        $campaign = is_array($campaign) ? $campaign : array();

        $settings = $this->sanitizeCampaignSettings(ArrayHelper::get($campaign, 'campaign_settings'));

        $count = $this->getCount($formId, $settings);

        if (!$count) {
            wp_send_json_error([
                'message' => __('No Applicant found based on your selection', 'ninja-job-board')
            ], 423);
            die();
        }

        if (!ArrayHelper::get($campaign, 'body') || !ArrayHelper::get($campaign, 'subject') || !ArrayHelper::get($campaign, 'title')) {
            wp_send_json_error([
                'message' => __('Email subject, title, body required', 'ninja-job-board')
            ], 423);
            die();
        }

        $campaignData = [
            'form_id'            => $formId,
            'status'             => 'ready',
            'title'              => sanitize_text_field(ArrayHelper::get($campaign, 'title')),
            'subject'            => sanitize_text_field(ArrayHelper::get($campaign, 'subject')),
            'body'               => wp_kses_post(ArrayHelper::get($campaign, 'body')),
            'campaign_settings'  => maybe_serialize($settings),
            'email_type'         => 'html',
            'created_by_user_id' => get_current_user_id(),
            'created_at'         => gmdate('Y-m-d H:i:s'),
            'updated_at'         => gmdate('Y-m-d H:i:s')
        ];

        $campaignId = wpJobBoardDB()->table('wjb_email_campaigns')
            ->insert($campaignData);

        $wheres = [];

        if (!empty($settings['internal_statuses'])) {
            $wheres['status'] = $settings['internal_statuses'];
        }

        if (!empty($settings['application_statuses'])) {
            $wheres['application_status'] = $settings['application_statuses'];
        }

        $submissionModel = new Submission();
        $applications = $submissionModel->getSubmissions($formId, $wheres);

        foreach ($applications->items as $application) {
            $subject = $campaignData['subject'];
            $body = $campaignData['body'];

            $emailTo = sanitize_email($application->applicant_email);
            if (!is_email($emailTo)) {
                continue;
            }

            $subject = str_replace(
                array('{applicant_name}', '{applicant_email}'),
                array(sanitize_text_field($application->applicant_name), $emailTo),
                $subject
            );
            $body = str_replace(
                array('{applicant_name}', '{applicant_email}'),
                array(esc_html($application->applicant_name), esc_html($emailTo)),
                $body
            );

            $data = [
                'campaign_id'   => $campaignId,
                'submission_id' => $application->id,
                'status'        => 'pending',
                'email_to'      => $emailTo,
                'subject'       => $subject,
                'body'          => $body,
                'email_type'    => 'html',
                'created_at'    => gmdate('Y-m-d H:i:s'),
                'updated_at'    => gmdate('Y-m-d H:i:s')
            ];

            wpJobBoardDB()->table('wjb_campaign_emails')
                ->insert($data);
        }

        wp_send_json_success([
            'campaign_id' => $campaignId,
            'message'     => __('Campaign created and email will be start sending now...', 'ninja-job-board')
        ], 200);
    }

    public function getCampaign()
    {
        $campaignId = isset($_REQUEST['campaign_id']) ? absint($_REQUEST['campaign_id']) : 0;
        $campaign = wpJobBoardDB()->table('wjb_email_campaigns')
            ->where('id', $campaignId)
            ->first();

        wp_send_json_success([
            'campaign' => $campaign,
            'stats'    => $this->getCampaignStat($campaignId)
        ]);

    }

    public function sendEmails()
    {
        $campaignId = isset($_REQUEST['campaign_id']) ? absint($_REQUEST['campaign_id']) : 0;
        $formId = $this->requestFormId();
        $pendingEmails = wpJobBoardDB()->table('wjb_campaign_emails')
            ->where('campaign_id', $campaignId)
            ->where('status', 'pending')
            ->limit(12)
            ->get();

        if (!$pendingEmails) {
            $stats = $this->getCampaignStat($campaignId);
            wpJobBoardDB()->table('wjb_email_campaigns')
                ->where('id', $campaignId)
                ->update([
                    'status'     => 'completed',
                    'total_sent' => $stats['all']
                ]);

            wp_send_json_success([
                'stats' => $stats
            ]);
        }

        foreach ($pendingEmails as $pendingEmail) {
            $emailBody = $this->prepareEmailBody($pendingEmail->body);
            $subject = $pendingEmail->subject;
            $email_to = $pendingEmail->email_to;

            $this->broadCast($email_to, $subject, $emailBody);

            wpJobBoardDB()->table('wjb_campaign_emails')
                ->where('id', $pendingEmail->id)
                ->update([
                    'status'  => 'sent',
                    'sent_at' => gmdate('Y-m-d H:i:s')
                ]);

            SubmissionActivity::createActivity(array(
                'form_id'       => $formId,
                'submission_id' => $pendingEmail->submission_id,
                'type'          => 'activity',
                'created_by'    => 'WPJobBoard BOT',
                /* translators: 1: recipient email, 2: email subject */
                'content'       => sprintf(__('Email Notification sent to %1$s and the subject: %2$s.', 'ninja-job-board'), esc_html($email_to), esc_html($subject))
            ));
        }

        $stats = $this->getCampaignStat($campaignId);

        wp_send_json_success([
            'stats' => $stats
        ]);
    }

    public function getCampaignEmails()
    {
        $campaignId = isset($_REQUEST['campaign_id']) ? absint($_REQUEST['campaign_id']) : 0;
        $emails = wpJobBoardDB()->table('wjb_campaign_emails')
            ->where('campaign_id', $campaignId)
            ->get();

        wp_send_json_success(array(
            'emails' => $emails
        ));

    }

    public function getCampaignStat($campaignId)
    {
        if (!$campaignId) {
            $campaignId = isset($_REQUEST['campaign_id']) ? absint($_REQUEST['campaign_id']) : 0;
        }

        $sentEmails = wpJobBoardDB()->table('wjb_campaign_emails')
            ->where('campaign_id', $campaignId)
            ->where('status', 'sent')
            ->count();

        $allEmails = wpJobBoardDB()->table('wjb_campaign_emails')
            ->where('campaign_id', $campaignId)
            ->count();

        return [
            'remaining' => $allEmails - $sentEmails,
            'all'       => $allEmails,
            'sent'      => $sentEmails
        ];

    }

    public function getEmailCounts()
    {
        $formId = $this->requestFormId();
        $settings = $this->sanitizeCampaignSettings(wp_unslash(ArrayHelper::get($_REQUEST, 'campaign_settings', array()))); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized in sanitizeCampaignSettings()

        $count = $this->getCount($formId, $settings);

        wp_send_json_success([
            'count' => $count
        ], 200);

    }

    private function getCount($formId, $settings)
    {
        $internalStatuses = ArrayHelper::get($settings, 'internal_statuses', array());
        $applicationStatuses = ArrayHelper::get($settings, 'application_statuses', array());

        $count = wpJobBoardDB()->table('wjb_applications')
            ->where('form_id', $formId);
        if ($internalStatuses) {
            $count = $count->whereIn('status', $internalStatuses);
        }

        if ($applicationStatuses) {
            $count = $count->whereIn('application_status', $applicationStatuses);
        }
        return $count->count();
    }


    private function prepareEmailBody($emailBody)
    {
        $emailHeader = View::make('email.default.header', [
            'submission'   => false,
            'notification' => false
        ]);

        $emailFooter = View::make('email.default.footer', array(
            'submission'   => false,
            'notification' => false
        ));

        $css = View::make('email.default.styles');
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

    private function broadCast($to, $subject, $body)
    {
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            "Reply-To: <$to>"
        ];
        return wp_mail($to, $subject, $body, $headers);
    }

}
