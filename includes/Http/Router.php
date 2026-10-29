<?php

namespace WPJobBoard\Http;

use WPJobBoard\Classes\AccessControl;
use WPJobBoard\Http\Controllers\ApplicationsController;
use WPJobBoard\Http\Controllers\CampaignsController;
use WPJobBoard\Http\Controllers\FormSettingsController;
use WPJobBoard\Http\Controllers\FormsController;
use WPJobBoard\Http\Controllers\SettingsController;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registers the admin REST API.
 *
 * Every route maps to a key in AccessControl's permission map. The permission callback checks
 * the actor capability and, for users with "own" permissions, the ownership of the form.
 * Cookie authentication (X-WP-Nonce or _wpnonce) protects the routes against CSRF.
 *
 * @since 2.0.0
 */
class Router
{
    const NAMESPACE_V1 = 'ninja-job-board/v1';

    public function register()
    {
        add_action('rest_api_init', array($this, 'registerRoutes'));
    }

    public function registerRoutes()
    {
        $forms = new FormsController();
        $formSettings = new FormSettingsController();
        $applications = new ApplicationsController();
        $campaigns = new CampaignsController();
        $settings = new SettingsController();

        // Forms (job posts)
        $this->route('/forms', 'GET', array($forms, 'index'), 'get_forms', 'forms');
        $this->route('/forms', 'POST', array($forms, 'store'), 'create_form', 'forms');
        $this->route('/forms/available', 'GET', array($forms, 'available'), 'get_available_forms', 'submissions');
        $this->route('/forms/import', 'POST', array($forms, 'import'), 'import_form', 'forms');
        $this->route('/forms/(?P<id>\d+)', 'GET', array($forms, 'show'), 'get_form', 'forms', 'form');
        $this->route('/forms/(?P<id>\d+)', 'POST', array($forms, 'update'), 'update_form', 'forms', 'form');
        $this->route('/forms/(?P<id>\d+)', 'DELETE', array($forms, 'destroy'), 'delete_form', 'forms', 'form');
        $this->route('/forms/(?P<id>\d+)/duplicate', 'POST', array($forms, 'duplicate'), 'duplicate_form', 'forms', 'form');
        $this->route('/forms/(?P<id>\d+)/restore', 'POST', array($forms, 'restore'), 'restore_form', 'forms', 'form');
        $this->route('/forms/(?P<id>\d+)/export', 'GET', array($forms, 'export'), 'export_form', 'forms', 'form');

        // Form settings
        $this->route('/forms/(?P<id>\d+)/builder', 'GET', array($formSettings, 'getBuilder'), 'get_custom_form_settings', 'forms', 'form');
        $this->route('/forms/(?P<id>\d+)/builder', 'POST', array($formSettings, 'saveBuilder'), 'save_form_builder_settings', 'forms', 'form');
        $this->route('/forms/(?P<id>\d+)/confirmation', 'GET', array($formSettings, 'getConfirmation'), 'get_form_settings', 'forms', 'form');
        $this->route('/forms/(?P<id>\d+)/confirmation', 'POST', array($formSettings, 'saveConfirmation'), 'save_form_settings', 'forms', 'form');
        $this->route('/forms/(?P<id>\d+)/design', 'GET', array($formSettings, 'getDesign'), 'get_design_settings', 'forms', 'form');
        $this->route('/forms/(?P<id>\d+)/design', 'POST', array($formSettings, 'saveDesign'), 'update_design_settings', 'forms', 'form');
        $this->route('/forms/(?P<id>\d+)/scheduling', 'GET', array($formSettings, 'getScheduling'), 'get_scheduling_settings', 'forms', 'form');
        $this->route('/forms/(?P<id>\d+)/scheduling', 'POST', array($formSettings, 'saveScheduling'), 'save_scheduling_settings', 'forms', 'form');
        $this->route('/forms/(?P<id>\d+)/notifications', 'GET', array($formSettings, 'getNotifications'), 'get_email_notifications', 'forms', 'form');
        $this->route('/forms/(?P<id>\d+)/notifications', 'POST', array($formSettings, 'saveNotifications'), 'save_email_notifications', 'forms', 'form');

        // Applications (submissions)
        $this->route('/applications', 'GET', array($applications, 'index'), 'get_submissions', 'submissions', 'form_param');
        $this->route('/applications/export', 'GET', array($applications, 'export'), 'export_submissions', 'submissions', 'form_param');
        $this->route('/applications/report', 'GET', array($applications, 'report'), 'get_form_report', 'submissions', 'form_param');
        $this->route('/applications/(?P<id>\d+)', 'GET', array($applications, 'show'), 'get_submission', 'submissions', 'application');
        $this->route('/applications/(?P<id>\d+)', 'DELETE', array($applications, 'destroy'), 'delete_submission', 'submissions', 'application');
        $this->route('/applications/(?P<id>\d+)/adjacent', 'GET', array($applications, 'adjacent'), 'get_next_prev_submission', 'submissions', 'application');
        $this->route('/applications/(?P<id>\d+)/notes', 'POST', array($applications, 'addNote'), 'add_submission_note', 'submissions', 'application');
        $this->route('/applications/(?P<id>\d+)/status', 'POST', array($applications, 'updateStatus'), 'change_application_status', 'submissions', 'application');

        // Email campaigns
        $this->route('/forms/(?P<id>\d+)/campaigns', 'GET', array($campaigns, 'index'), 'get_campaigns', 'email_campaigns', 'form');
        $this->route('/forms/(?P<id>\d+)/campaigns', 'POST', array($campaigns, 'store'), 'send_email_campaign', 'email_campaigns', 'form');
        $this->route('/forms/(?P<id>\d+)/campaigns/count', 'GET', array($campaigns, 'count'), 'get_email_counts', 'email_campaigns', 'form');
        $this->route('/campaigns/(?P<id>\d+)', 'GET', array($campaigns, 'show'), 'get_campaign', 'email_campaigns');
        $this->route('/campaigns/(?P<id>\d+)/send', 'POST', array($campaigns, 'send'), 'send_emails', 'email_campaigns');
        $this->route('/campaigns/(?P<id>\d+)/emails', 'GET', array($campaigns, 'emails'), 'get_campaign_emails', 'email_campaigns');

        // Global settings
        $this->route('/settings', 'GET', array($settings, 'show'), 'get_global_settings', 'global');
        $this->route('/settings', 'POST', array($settings, 'update'), 'update_global_settings', 'global');
        $this->route('/settings/image', 'POST', array($settings, 'uploadImage'), 'wpjb_upload_image', 'global');

        do_action('wpjobboard/rest_routes_registered', self::NAMESPACE_V1);
    }

    /**
     * @param string   $path
     * @param string   $method
     * @param callable $callback
     * @param string   $endpoint Key in AccessControl's permission map
     * @param string   $group    Group in AccessControl's permission map
     * @param string   $scope    What identifies the form for the ownership check:
     *                           'form' ({id} is a form), 'application' ({id} is an application),
     *                           'form_param' (form_id parameter) or '' (no single form)
     */
    private function route($path, $method, $callback, $endpoint, $group, $scope = '')
    {
        $args = array();
        if (strpos($path, '(?P<id>') !== false) {
            $args['id'] = array(
                'type'              => 'integer',
                'required'          => true,
                'sanitize_callback' => 'absint'
            );
        }

        register_rest_route(self::NAMESPACE_V1, $path, array(
            'methods'             => $method,
            'callback'            => $callback,
            'args'                => $args,
            'permission_callback' => function (\WP_REST_Request $request) use ($endpoint, $group, $scope) {
                $formId = 0;
                $applicationId = 0;
                if ($scope === 'form') {
                    $formId = absint($request->get_param('id'));
                } else if ($scope === 'application') {
                    $applicationId = absint($request->get_param('id'));
                } else if ($scope === 'form_param') {
                    $formId = absint($request->get_param('form_id'));
                }
                return AccessControl::canAccess($endpoint, $group, $formId, $applicationId);
            }
        ));
    }
}
