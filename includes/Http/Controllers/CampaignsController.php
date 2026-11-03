<?php

namespace WPJobBoard\Http\Controllers;

use WPJobBoard\Classes\Activator;
use WPJobBoard\Classes\ArrayHelper;
use WPJobBoard\Classes\Emogrifier\Emogrifier;
use WPJobBoard\Classes\GeneralSettings;
use WPJobBoard\Classes\Models\SubmissionActivity;
use WPJobBoard\Classes\View;

if (!defined('ABSPATH')) {
    exit;
}

class CampaignsController extends Controller
{
    const BATCH_SIZE = 12;

    public function index(\WP_REST_Request $request)
    {
        $form = $this->requireForm($request);
        if (is_wp_error($form)) {
            return $form;
        }

        $this->maybeCreateTables();

        $perPage = min(100, max(1, absint($request->get_param('per_page') ?: 15)));
        $page = max(1, absint($request->get_param('page') ?: 1));

        $query = wpJobBoardDB()->table('wjb_email_campaigns')->where('form_id', $form->ID);
        $total = $query->count();
        $campaigns = $query->orderBy('id', 'DESC')
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->get();

        return array(
            'campaigns' => array(
                'data'  => $campaigns,
                'total' => $total
            )
        );
    }

    public function count(\WP_REST_Request $request)
    {
        $form = $this->requireForm($request);
        if (is_wp_error($form)) {
            return $form;
        }

        return array(
            'count' => $this->applicantsQuery($form->ID, $this->campaignSettings($request->get_param('campaign_settings')))->count()
        );
    }

    public function store(\WP_REST_Request $request)
    {
        $form = $this->requireForm($request);
        if (is_wp_error($form)) {
            return $form;
        }

        $this->maybeCreateTables();

        $campaign = $request->get_param('campaign');
        $campaign = is_array($campaign) ? $campaign : array();
        $settings = $this->campaignSettings(ArrayHelper::get($campaign, 'campaign_settings'));

        $title = sanitize_text_field((string)ArrayHelper::get($campaign, 'title'));
        $subject = sanitize_text_field((string)ArrayHelper::get($campaign, 'subject'));
        $body = wp_kses_post((string)ArrayHelper::get($campaign, 'body'));

        if (!$title || !$subject || !$body) {
            return $this->error(__('Broadcast name, email subject and body are required.', 'ninja-job-board'));
        }

        $applications = $this->applicantsQuery($form->ID, $settings)->get();
        if (!$applications) {
            return $this->error(__('No applicant found for your selection.', 'ninja-job-board'));
        }

        $now = gmdate('Y-m-d H:i:s');
        $campaignId = wpJobBoardDB()->table('wjb_email_campaigns')->insert(array(
            'form_id'            => $form->ID,
            'status'             => 'ready',
            'title'              => $title,
            'subject'            => $subject,
            'body'               => $body,
            'campaign_settings'  => maybe_serialize($settings),
            'email_type'         => 'html',
            'created_by_user_id' => get_current_user_id(),
            'created_at'         => $now,
            'updated_at'         => $now
        ));

        foreach ($applications as $application) {
            $emailTo = sanitize_email($application->applicant_email);
            if (!is_email($emailTo)) {
                continue;
            }

            wpJobBoardDB()->table('wjb_campaign_emails')->insert(array(
                'campaign_id'   => $campaignId,
                'submission_id' => $application->id,
                'status'        => 'pending',
                'email_to'      => $emailTo,
                'subject'       => str_replace(
                    array('{applicant_name}', '{applicant_email}'),
                    array(sanitize_text_field($application->applicant_name), $emailTo),
                    $subject
                ),
                'body'          => str_replace(
                    array('{applicant_name}', '{applicant_email}'),
                    array(esc_html($application->applicant_name), esc_html($emailTo)),
                    $body
                ),
                'email_type'    => 'html',
                'created_at'    => $now,
                'updated_at'    => $now
            ));
        }

        return array(
            'campaign_id' => $campaignId,
            'message'     => __('Email broadcast created. Emails will start sending now.', 'ninja-job-board')
        );
    }

    public function show(\WP_REST_Request $request)
    {
        $campaign = $this->requireCampaign($request);
        if (is_wp_error($campaign)) {
            return $campaign;
        }

        return array(
            'campaign' => $campaign,
            'stats'    => $this->stats($campaign->id)
        );
    }

    /**
     * The admin app calls this until nothing is left.
     */
    public function send(\WP_REST_Request $request)
    {
        $campaign = $this->requireCampaign($request);
        if (is_wp_error($campaign)) {
            return $campaign;
        }

        $pendingEmails = wpJobBoardDB()->table('wjb_campaign_emails')
            ->where('campaign_id', $campaign->id)
            ->where('status', 'pending')
            ->limit(self::BATCH_SIZE)
            ->get();

        if (!$pendingEmails) {
            $stats = $this->stats($campaign->id);
            wpJobBoardDB()->table('wjb_email_campaigns')
                ->where('id', $campaign->id)
                ->update(array(
                    'status'     => 'completed',
                    'total_sent' => $stats['all']
                ));

            return array(
                'stats' => $stats
            );
        }

        foreach ($pendingEmails as $pendingEmail) {
            // Mark first, so a parallel request can not send the same email again.
            // The query builder runs on $wpdb->query(), so rows_affected tells if this request won.
            global $wpdb;
            wpJobBoardDB()->table('wjb_campaign_emails')
                ->where('id', $pendingEmail->id)
                ->where('status', 'pending')
                ->update(array(
                    'status'  => 'sent',
                    'sent_at' => gmdate('Y-m-d H:i:s')
                ));

            if ((int)$wpdb->rows_affected !== 1) {
                continue;
            }

            wp_mail($pendingEmail->email_to, $pendingEmail->subject, $this->prepareEmailBody($pendingEmail->body), array(
                'Content-Type: text/html; charset=UTF-8'
            ));

            SubmissionActivity::createActivity(array(
                'form_id'       => $campaign->form_id,
                'submission_id' => $pendingEmail->submission_id,
                'type'          => 'activity',
                'created_by'    => 'WPJobBoard BOT',
                /* translators: 1: recipient email address, 2: email subject */
                'content'       => sprintf(__('Email sent to %1$s. Subject: %2$s.', 'ninja-job-board'), esc_html($pendingEmail->email_to), esc_html($pendingEmail->subject))
            ));
        }

        return array(
            'stats' => $this->stats($campaign->id)
        );
    }

    public function emails(\WP_REST_Request $request)
    {
        $campaign = $this->requireCampaign($request);
        if (is_wp_error($campaign)) {
            return $campaign;
        }

        return array(
            'emails' => wpJobBoardDB()->table('wjb_campaign_emails')
                ->select(array('id', 'submission_id', 'status', 'email_to', 'subject', 'sent_at', 'created_at'))
                ->where('campaign_id', $campaign->id)
                ->get()
        );
    }

    private function requireCampaign(\WP_REST_Request $request)
    {
        $campaign = wpJobBoardDB()->table('wjb_email_campaigns')
            ->where('id', absint($request->get_param('id')))
            ->first();

        if (!$campaign) {
            return $this->error(__('Campaign not found', 'ninja-job-board'), 404);
        }

        return $campaign;
    }

    private function campaignSettings($settings)
    {
        $settings = is_array($settings) ? wpJobBoardSanitizeText($settings) : array();
        $internal = (array)ArrayHelper::get($settings, 'internal_statuses', array());
        $application = (array)ArrayHelper::get($settings, 'application_statuses', array());
        $settings['internal_statuses'] = array_values(array_intersect($internal, array_keys(GeneralSettings::getInternalStatuses())));
        $settings['application_statuses'] = array_values(array_intersect($application, array_keys(GeneralSettings::getApplicationStatuses())));
        return $settings;
    }

    private function applicantsQuery($formId, $settings)
    {
        $query = wpJobBoardDB()->table('wjb_applications')->where('form_id', $formId);
        if (!empty($settings['internal_statuses'])) {
            $query->whereIn('status', $settings['internal_statuses']);
        }
        if (!empty($settings['application_statuses'])) {
            $query->whereIn('application_status', $settings['application_statuses']);
        }
        return $query;
    }

    private function stats($campaignId)
    {
        $sent = wpJobBoardDB()->table('wjb_campaign_emails')
            ->where('campaign_id', $campaignId)
            ->where('status', 'sent')
            ->count();

        $all = wpJobBoardDB()->table('wjb_campaign_emails')
            ->where('campaign_id', $campaignId)
            ->count();

        return array(
            'remaining' => $all - $sent,
            'all'       => $all,
            'sent'      => $sent
        );
    }

    private function prepareEmailBody($emailBody)
    {
        $data = array(
            'submission'   => false,
            'notification' => false
        );

        $emailBody = View::make('email.default.header', $data) . $emailBody . View::make('email.default.footer', $data);

        try {
            // Inline the CSS for email clients that ignore <style>
            $emogrifier = new Emogrifier($emailBody, View::make('email.default.styles'));
            $emailBody = $emogrifier->emogrify();
        } catch (\Exception $e) {
            // Send the email without inlined CSS
        }

        return $emailBody;
    }

    private function maybeCreateTables()
    {
        global $wpdb;
        $tableName = $wpdb->prefix . 'wjb_email_campaigns';
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tableName)) != $tableName) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- schema check
            (new Activator())->createCampaignDB();
        }
    }
}
