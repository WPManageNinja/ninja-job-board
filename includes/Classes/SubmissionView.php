<?php

namespace WPJobBoard\Classes;

use WPJobBoard\Classes\Models\Forms;
use WPJobBoard\Classes\Models\Submission;
use WPJobBoard\Classes\Models\SubmissionActivity;
use WPJobBoard\Classes\Models\Transaction;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Form Submission Handler
 * @since 1.0.0
 */
class SubmissionView
{
    public function registerEndpoints()
    {
        add_action('wp_ajax_wpjb_submission_endpoints', array($this, 'routeAjaxMaps'));
    }

    public function routeAjaxMaps()
    {
        $routes = array(
            'get_submissions'             => 'getSubmissions',
            'get_submission'              => 'getSubmission',
            'get_available_forms'         => 'getAvailableForms',
            'get_next_prev_submission'    => 'getNextPrevSubmission',
            'add_submission_note'         => 'addSubmissionNote',
            'change_application_status'   => 'changeApplicationStatus',
            'change_internal_status'      => 'changeInternalStatus',
            'delete_submission'           => 'deleteSubmission',
            'get_form_report'             => 'getFormReport',
            'update_application_statuses' => 'updateApplicationStatuses'
        );
        $route = sanitize_text_field($_REQUEST['route']);

        if (isset($routes[$route])) {
            AccessControl::checkAndPresponseError($route, 'submissions');
            do_action('wpjobboard/doing_ajax_submissions_' . $route);
            $this->{$routes[$route]}();
            return;
        }
    }

    public function getSubmissions()
    {
        $formId = false;
        if (isset($_REQUEST['form_id']) && $_REQUEST['form_id']) {
            $formId = absint($_REQUEST['form_id']);
        }
        $searchString = sanitize_text_field($_REQUEST['search_string']);

        $page = absint($_REQUEST['page_number']);
        $perPage = absint($_REQUEST['per_page']);
        $skip = ($page - 1) * $perPage;

        $wheres = array();

        if (isset($_REQUEST['application_status']) && $_REQUEST['application_status']) {
            $wheres['application_status'] = wpJobBoardSanitize($_REQUEST['application_status']);
        }

        if (isset($_REQUEST['status']) && $_REQUEST['status']) {
            $wheres['status'] = wpJobBoardSanitize($_REQUEST['status']);
        }

        $submissionModel = new Submission();
        $submissions = $submissionModel->getSubmissions($formId, $wheres, $perPage, $skip, 'DESC', $searchString);

        $submissionItems = apply_filters('wpjobboard/form_entries', $submissions->items, $formId);

        wp_send_json_success(array(
            'submissions' => $submissionItems,
            'total'       => (int)$submissions->total
        ), 200);

    }

    public function getSubmission($submissionId = false)
    {
        $formId = absint($_REQUEST['form_id']);
        if (!$submissionId) {
            $submissionId = absint($_REQUEST['submission_id']);
        }

        $submissionModel = new Submission();
        $submission = $submissionModel->getSubmission($submissionId, array('transactions', 'order_items', 'tax_items', 'activities'));

        if ($submission->user_id) {
            $user = get_user_by('ID', $submission->user_id);
            if ($user) {
                $submission->user = [
                    'display_name' => $user->display_name,
                    'profile_url'  => get_edit_user_link($user->ID)
                ];
            }
        }

        $submission = apply_filters('wpjobboard/form_entry', $submission);

        $parsedEntry = (object)$submissionModel->getParsedSubmission($submission);

        $otherSubmissions = $submissionModel->getOtherSubmission($submission);

        wp_send_json_success(array(
            'submission' => $submission,
            'entry'      => $parsedEntry,
            'other_entries' => $otherSubmissions
        ), 200);
    }

    public function getNextPrevSubmission()
    {
        $formId = false;
        if (isset($_REQUEST['form_id'])) {
            $formId = absint($_REQUEST['form_id']);
        }

        $currentSubmissionId = absint($_REQUEST['current_submission_id']);
        $queryType = sanitize_text_field($_REQUEST['type']);

        $whereOperator = '<';
        $orderBy = 'DESC';
        // find the next / previous form id
        if ($queryType == 'prev') {
            $whereOperator = '>';
            $orderBy = 'ASC';
        }

        $submissionQuery = wpJobBoardDB()->table('wjb_applications')
            ->orderBy('id', $orderBy)
            ->where('id', $whereOperator, $currentSubmissionId);

        if ($formId) {
            $submissionQuery->where('form_id', $formId);
        }


        $submission = $submissionQuery->first();

        if (!$submission) {
            wp_send_json_error(array(
                'message' => __('Sorry, No Submission found', 'wpjobboard')
            ), 423);
        }

        $this->getSubmission($submission->id);
    }

    public function getAvailableForms()
    {
        wp_send_json_success(array(
            'available_forms' => Forms::getAllAvailableForms()
        ), 200);
    }

    public function addSubmissionNote()
    {
        $formId = intval($_REQUEST['form_id']);
        $submissionId = intval($_REQUEST['submission_id']);
        $content = sanitize_textarea_field($_REQUEST['note']);
        $userId = get_current_user_id();
        $user = get_user_by('ID', $userId);

        $note = array(
            'form_id'            => $formId,
            'submission_id'      => $submissionId,
            'type'               => 'custom_note',
            'content'            => $content,
            'created_by'         => $user->display_name,
            'created_by_user_id' => $userId
        );

        $note = apply_filters('wpjobboard/add_note_by_user', $note, $formId, $submissionId);
        do_action('wpjobboard/before_create_note_by_user', $note);
        SubmissionActivity::createActivity($note);
        do_action('wpjobboard/after_create_note_by_user', $note);

        wp_send_json_success(array(
            'message'    => __('Note successfully added', 'wpjobboard'),
            'activities' => SubmissionActivity::getSubmissionActivity($submissionId)
        ), 200);
    }

    public function changeApplicationStatus()
    {
        $submissionId = intval($_REQUEST['submission_id']);
        $newStatus = sanitize_text_field($_REQUEST['new_application_status']);
        $submissionModel = new Submission();
        $submission = $submissionModel->getSubmission($submissionId);
        if ($submission->application_status == $newStatus) {
            wp_send_json_error(array(
                'message' => __('The submission have the same status', 'wpjobboard')
            ), 423);
        }

        do_action('wpjobboard/before_application_status_change_manually', $submission, $newStatus, $submission->application_status);
        $submissionModel->update($submissionId, array(
            'application_status' => $newStatus
        ));
        do_action('wpjobboard/after_application_status_change_manually', $submissionId, $newStatus, $submission->application_status);

        $activityContent = 'Application status changed from <b>' . $submission->application_status . '</b> to <b>' . $newStatus . '</b>';

        if (isset($_REQUEST['status_change_note']) && $_REQUEST['status_change_note']) {
            $note = wp_kses_post($_REQUEST['status_change_note']);
            $activityContent .= '<br />Note: ' . $note;
        }

        $userId = get_current_user_id();
        $user = get_user_by('ID', $userId);
        SubmissionActivity::createActivity(array(
            'form_id'            => $submission->form_id,
            'submission_id'      => $submission->id,
            'type'               => 'info',
            'created_by'         => $user->display_name,
            'created_by_user_id' => $userId,
            'content'            => $activityContent
        ));

        wp_send_json_success(array(
            'message' => __('Application status successfully changed', 'wpjobboard')
        ), 200);
    }

    public function changeInternalStatus()
    {
        $submissionId = intval($_REQUEST['submission_id']);
        $newStatus = sanitize_text_field($_REQUEST['new_internal_status']);
        $submissionModel = new Submission();
        $submission = $submissionModel->getSubmission($submissionId);

        if (!$newStatus) {
            wp_send_json_error(array(
                'message' => __('Please Provide a status', 'wpjobboard')
            ), 423);
        }

        if ($submission->status == $newStatus) {
            wp_send_json_error(array(
                'message' => __('The submission have the same status', 'wpjobboard')
            ), 423);
        }

        do_action('wpjobboard/before_internal_status_change_manually', $submission, $newStatus, $submission->application_status);
        $submissionModel->update($submissionId, array(
            'status' => $newStatus
        ));
        do_action('wpjobboard/after_application_status_change_manually', $submissionId, $newStatus, $submission->application_status);

        $activityContent = 'Internal status changed from <b>' . $submission->status . '</b> to <b>' . $newStatus . '</b>';

        if (isset($_REQUEST['status_change_note']) && $_REQUEST['status_change_note']) {
            $note = wp_kses_post($_REQUEST['status_change_note']);
            $activityContent .= '<br />Note: ' . $note;
        }

        $userId = get_current_user_id();
        $user = get_user_by('ID', $userId);
        SubmissionActivity::createActivity(array(
            'form_id'            => $submission->form_id,
            'submission_id'      => $submission->id,
            'type'               => 'info',
            'created_by'         => $user->display_name,
            'created_by_user_id' => $userId,
            'content'            => $activityContent
        ));

        wp_send_json_success(array(
            'message' => __('Internal status successfully changed', 'wpjobboard')
        ), 200);
    }

    public function deleteSubmission()
    {
        $submissionId = intval($_REQUEST['submission_id']);
        $formId = intval($_REQUEST['form_id']);
        do_action('wpjobboard/before_delete_submission', $submissionId, $formId);
        $submissionModel = new Submission();
        $submissionModel->deleteSubmission($submissionId);
        do_action('wpjobboard/after_delete_submission', $submissionId, $formId);

        wp_send_json_success(array(
            'message' => __('Selected submission successfully deleted', 'wpjobboard')
        ), 200);
    }

    public function getFormReport()
    {
        $formId = absint($_REQUEST['form_id']);
        $applicationStatuses = GeneralSettings::getApplicationStatuses();

        $submissionModel = new Submission();
        $reports = [];
        $reports[''] = [
            'label'            => 'All',
            'submission_count' => $submissionModel->getTotalCount($formId)
        ];

        foreach ($applicationStatuses as $status => $statusName) {
            $reports[$status] = [
                'label'            => $statusName,
                'submission_count' => $submissionModel->getTotalCount($formId, $status)
            ];
        }

        wp_send_json_success([
            'reports' => $reports
        ], 200);
    }

    public function updateApplicationStatuses()
    {
        $submissionId = intval($_REQUEST['submission_id']);
        $submissionModel = new Submission();
        $submission = $submissionModel->getSubmission($submissionId);


        $userId = get_current_user_id();
        $user = get_user_by('ID', $userId);

        $newApplicationStatus = sanitize_text_field($_REQUEST['application_status']);
        if ($submission->application_status != $newApplicationStatus) {

            do_action('wpjobboard/before_application_status_change_manually', $submission, $newApplicationStatus, $submission->application_status);
            $submissionModel->update($submissionId, array(
                'application_status' => $newApplicationStatus
            ));
            do_action('wpjobboard/after_application_status_change_manually', $submissionId, $newApplicationStatus, $submission->application_status);

            SubmissionActivity::createActivity(array(
                'form_id'            => $submission->form_id,
                'submission_id'      => $submission->id,
                'type'               => 'info',
                'created_by'         => $user->display_name,
                'created_by_user_id' => $userId,
                'content'            => 'Application status changed from <b>'.$submission->application_status.'</b> to <b>'.$newApplicationStatus.'</b>'
            ));

        }

        $newStatus = sanitize_text_field($_REQUEST['status']);
        if ($submission->status != $newStatus) {
            do_action('wpjobboard/before_internal_status_change_manually', $submission, $newStatus, $submission->status);
            $submissionModel->update($submissionId, array(
                'status' => $newStatus
            ));
            do_action('wpjobboard/after_internal_status_change_manually', $submissionId, $newStatus, $submission->status);

            SubmissionActivity::createActivity(array(
                'form_id'            => $submission->form_id,
                'submission_id'      => $submission->id,
                'type'               => 'info',
                'created_by'         => $user->display_name,
                'created_by_user_id' => $userId,
                'content'            => 'Internal status changed from <b>'.$submission->status.'</b> to <b>'.$newStatus.'</b>'
            ));

        }

        wp_send_json_success([
           'message' => __('Status has been changed successfully', 'wpjobboard')
        ]);

    }
}
