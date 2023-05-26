<?php

namespace WPJobBoard\Classes\EmailCampaigns;

use WPJobBoard\Classes\AccessControl;
use WPJobBoard\Classes\ArrayHelper;
use WPJobBoard\Classes\Emogrifier\Emogrifier;
use WPJobBoard\Classes\Models\Submission;
use WPJobBoard\Classes\Models\SubmissionActivity;
use WPJobBoard\Classes\View;

class EmailCampaign
{
    public function boot()
    {
        add_action('wp_ajax_wpjobboard_email_campaigns', array($this, 'ajaxHandler'));
    }

    public function ajaxHandler()
    {
        $route = sanitize_text_field($_REQUEST['route']);

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
        if ($wpdb->get_var("SHOW TABLES LIKE '" . $wpdb->prefix . "wjb_email_campaigns'") != $table_name) {
            $activator = new \WPJobBoard\Classes\Activator();
            $activator->createCampaignDB();
        }

        $formId = intval($_REQUEST['form_id']);
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
        $formId = intval($_REQUEST['form_id']);
        $campaign = wp_unslash(ArrayHelper::get($_REQUEST, 'campaign')); // Will be sanitized when saving

        $settings = wpJobBoardSanitize(ArrayHelper::get($campaign, 'campaign_settings'));

        $count = $this->getCount($formId, $settings);

        if (!$count) {
            wp_send_json_error([
                'message' => __('No Applicant found based on your selection', 'wpjobboard')
            ], 423);
            die();
        }

        if (!ArrayHelper::get($campaign, 'body') || !ArrayHelper::get($campaign, 'subject') || !ArrayHelper::get($campaign, 'title')) {
            wp_send_json_error([
                'message' => __('Email subject, title, body required', 'wpjobboard')
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
            'created_at'         => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s')
        ];

        $campaignId = wpJobBoardDB()->table('wjb_email_campaigns')
            ->insert($campaignData);

        $wheres = [];

        if ($settings['internal_statuses']) {
            $wheres['status'] = $settings['internal_statuses'];
        }

        if ($settings['application_statuses']) {
            $wheres['application_status'] = $settings['application_statuses'];
        }

        $submissionModel = new Submission();
        $applications = $submissionModel->getSubmissions($formId, $wheres);

        foreach ($applications->items as $application) {
            $subject = $campaignData['subject'];
            $body = $campaignData['body'];

            $relaces = [
                '{applicant_name}'  => $application->applicant_name,
                '{applicant_email}' => $application->applicant_email
            ];

            $subject = str_replace(array_keys($relaces), array_values($relaces), $subject);
            $body = str_replace(array_keys($relaces), array_values($relaces), $body);

            $data = [
                'campaign_id'   => $campaignId,
                'submission_id' => $application->id,
                'status'        => 'pending',
                'email_to'      => $application->applicant_email,
                'subject'       => $subject,
                'body'          => $body,
                'email_type'    => 'html',
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s')
            ];

            wpJobBoardDB()->table('wjb_campaign_emails')
                ->insert($data);
        }

        wp_send_json_success([
            'campaign_id' => $campaignId,
            'message'     => __('Campaign created and email will be start sending now...', 'wpjobboard')
        ], 200);
    }

    public function getCampaign()
    {
        $campaignId = intval($_REQUEST['campaign_id']);
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
        $campaignId = intval($_REQUEST['campaign_id']);
        $formId = intval($_REQUEST['form_id']);
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
                    'sent_at' => date('Y-m-d H:i:s')
                ]);

            SubmissionActivity::createActivity(array(
                'form_id'       => $formId,
                'submission_id' => $pendingEmail->submission_id,
                'type'          => 'activity',
                'created_by'    => 'WPJobBoard BOT',
                'content'       => "Email Notification sent to {$email_to} and the subject: {$subject}."
            ));
        }

        $stats = $this->getCampaignStat($campaignId);

        wp_send_json_success([
            'stats' => $stats
        ]);
    }

    public function getCampaignEmails()
    {
        $campaignId = intval($_REQUEST['campaign_id']);
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
            $campaignId = intval($_REQUEST['campaign_id']);
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
        $formId = intval($_REQUEST['form_id']);
        $settings = wpJobBoardSanitize(ArrayHelper::get($_REQUEST, 'campaign_settings'));

        $count = $this->getCount($formId, $settings);

        wp_send_json_success([
            'count' => $count
        ], 200);

    }

    private function getCount($formId, $settings)
    {
        $internalStatuses = $settings['internal_statuses'];
        $applicationStatuses = $settings['application_statuses'];

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
        } catch (Exception $e) {

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
