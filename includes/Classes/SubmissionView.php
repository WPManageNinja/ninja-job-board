<?php

namespace WPJobBoard\Classes;

use WPJobBoard\Classes\Models\Forms;
use WPJobBoard\Classes\Models\Submission;
use WPJobBoard\Classes\Models\SubmissionActivity;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Form Submission Handler
 * @since 1.0.0
 */
class SubmissionView
{
    // phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing -- every route passes AccessControl::checkAndPresponseError() first, which verifies the nonce

    /**
     * Read a request value as plain text
     */
    private function input($key, $default = '')
    {
        if (!isset($_REQUEST[$key])) {
            return $default;
        }
        return wpJobBoardSanitizeText(wp_unslash($_REQUEST[$key])); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized by wpJobBoardSanitizeText()
    }

    /**
     * Keep only known status keys
     */
    private function filterStatuses($value, $statuses)
    {
        $allowed = array_keys($statuses);
        if (is_array($value)) {
            return array_values(array_intersect($value, $allowed));
        }
        return in_array($value, $allowed, true) ? $value : '';
    }

    /**
     * Load a submission or stop with an error
     */
    private function getSubmissionOrFail($submissionId)
    {
        $submissionModel = new Submission();
        $submission = $submissionModel->getSubmission($submissionId);
        if (!$submission) {
            wp_send_json_error(array(
                'message' => __('Sorry, No Submission found', 'ninja-job-board')
            ), 404);
        }
        return $submission;
    }

    /**
     * Activity text for a status change. All parts are escaped.
     */
    private function statusChangeText($label, $oldStatus, $newStatus, $note = '')
    {
        $content = esc_html($label) . ' <b>' . esc_html($oldStatus) . '</b> &rarr; <b>' . esc_html($newStatus) . '</b>';
        if ($note) {
            $content .= '<br />' . esc_html__('Note:', 'ninja-job-board') . ' ' . wp_kses_post($note);
        }
        return $content;
    }

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
        $route = isset($_REQUEST['route']) ? sanitize_text_field(wp_unslash($_REQUEST['route'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce verified in AccessControl::checkAndPresponseError()

        if (isset($routes[$route])) {
            AccessControl::checkAndPresponseError($route, 'submissions');
            do_action('wpjobboard/doing_ajax_submissions_' . $route);
            $this->{$routes[$route]}();
            return;
        }
    }

    public function getSubmissions()
    {
        $formId = absint($this->input('form_id', 0));
        $searchString = $this->input('search_string');
        $searchString = is_string($searchString) ? $searchString : '';

        $page = max(1, absint($this->input('page_number', 1)));
        $perPage = min(100, max(1, absint($this->input('per_page', 20))));
        $skip = ($page - 1) * $perPage;

        $wheres = array();

        if ($applicationStatus = $this->input('application_status')) {
            $wheres['application_status'] = $this->filterStatuses($applicationStatus, GeneralSettings::getApplicationStatuses());
        }

        if ($status = $this->input('status')) {
            $wheres['status'] = $this->filterStatuses($status, GeneralSettings::getInternalStatuses());
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
        if (!$submissionId) {
            $submissionId = absint($this->input('submission_id', 0));
        }

        $submissionModel = new Submission();
        $submission = $submissionModel->getSubmission($submissionId, array('activities'));

        if (!$submission) {
            wp_send_json_error(array(
                'message' => __('Sorry, No Submission found', 'ninja-job-board')
            ), 404);
        }

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
        $formId = absint($this->input('form_id', 0));

        $currentSubmissionId = absint($this->input('current_submission_id', 0));
        $queryType = $this->input('type');

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
                'message' => __('Sorry, No Submission found', 'ninja-job-board')
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
        $submission = $this->getSubmissionOrFail(absint($this->input('submission_id', 0)));
        $formId = intval($submission->form_id);
        $submissionId = intval($submission->id);
        $content = isset($_REQUEST['note']) ? esc_html(sanitize_textarea_field(wp_unslash($_REQUEST['note']))) : '';
        if (!$content) {
            wp_send_json_error(array(
                'message' => __('Please provide note content', 'ninja-job-board')
            ), 423);
        }
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
            'message'    => __('Note successfully added', 'ninja-job-board'),
            'activities' => SubmissionActivity::getSubmissionActivity($submissionId)
        ), 200);
    }

    public function changeApplicationStatus()
    {
        $submissionId = absint($this->input('submission_id', 0));
        $newStatus = $this->filterStatuses($this->input('new_application_status'), GeneralSettings::getApplicationStatuses());
        $submissionModel = new Submission();
        $submission = $this->getSubmissionOrFail($submissionId);
        if (!$newStatus) {
            wp_send_json_error(array(
                'message' => __('Please Provide a valid status', 'ninja-job-board')
            ), 423);
        }
        if ($submission->application_status == $newStatus) {
            wp_send_json_error(array(
                'message' => __('The submission have the same status', 'ninja-job-board')
            ), 423);
        }

        do_action('wpjobboard/before_application_status_change_manually', $submission, $newStatus, $submission->application_status);
        $submissionModel->update($submissionId, array(
            'application_status' => $newStatus
        ));
        do_action('wpjobboard/after_application_status_change_manually', $submissionId, $newStatus, $submission->application_status);

        $note = isset($_REQUEST['status_change_note']) ? wp_kses_post(wp_unslash($_REQUEST['status_change_note'])) : '';
        $activityContent = $this->statusChangeText(__('Application status changed:', 'ninja-job-board'), $submission->application_status, $newStatus, $note);

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
            'message' => __('Application status successfully changed', 'ninja-job-board')
        ), 200);
    }

    public function changeInternalStatus()
    {
        $submissionId = absint($this->input('submission_id', 0));
        $newStatus = $this->filterStatuses($this->input('new_internal_status'), GeneralSettings::getInternalStatuses());
        $submissionModel = new Submission();
        $submission = $this->getSubmissionOrFail($submissionId);

        if (!$newStatus) {
            wp_send_json_error(array(
                'message' => __('Please Provide a status', 'ninja-job-board')
            ), 423);
        }

        if ($submission->status == $newStatus) {
            wp_send_json_error(array(
                'message' => __('The submission have the same status', 'ninja-job-board')
            ), 423);
        }

        do_action('wpjobboard/before_internal_status_change_manually', $submission, $newStatus, $submission->application_status);
        $submissionModel->update($submissionId, array(
            'status' => $newStatus
        ));
        do_action('wpjobboard/after_application_status_change_manually', $submissionId, $newStatus, $submission->application_status);

        $note = isset($_REQUEST['status_change_note']) ? wp_kses_post(wp_unslash($_REQUEST['status_change_note'])) : '';
        $activityContent = $this->statusChangeText(__('Internal status changed:', 'ninja-job-board'), $submission->status, $newStatus, $note);

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
            'message' => __('Internal status successfully changed', 'ninja-job-board')
        ), 200);
    }

    public function deleteSubmission()
    {
        $submission = $this->getSubmissionOrFail(absint($this->input('submission_id', 0)));
        $submissionId = intval($submission->id);
        $formId = intval($submission->form_id);
        do_action('wpjobboard/before_delete_submission', $submissionId, $formId);
        $submissionModel = new Submission();
        $submissionModel->deleteSubmission($submissionId);
        do_action('wpjobboard/after_delete_submission', $submissionId, $formId);

        wp_send_json_success(array(
            'message' => __('Selected submission successfully deleted', 'ninja-job-board')
        ), 200);
    }

    public function getFormReport()
    {
        $formId = absint($this->input('form_id', 0));
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
        $submissionId = absint($this->input('submission_id', 0));
        $submissionModel = new Submission();
        $submission = $this->getSubmissionOrFail($submissionId);

        $userId = get_current_user_id();
        $user = get_user_by('ID', $userId);

        $newApplicationStatus = $this->filterStatuses($this->input('application_status'), GeneralSettings::getApplicationStatuses());
        if ($newApplicationStatus && $submission->application_status != $newApplicationStatus) {

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
                'content'            => $this->statusChangeText(__('Application status changed:', 'ninja-job-board'), $submission->application_status, $newApplicationStatus)
            ));

        }

        $newStatus = $this->filterStatuses($this->input('status'), GeneralSettings::getInternalStatuses());
        if ($newStatus && $submission->status != $newStatus) {
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
                'content'            => $this->statusChangeText(__('Internal status changed:', 'ninja-job-board'), $submission->status, $newStatus)
            ));

        }

        wp_send_json_success([
           'message' => __('Status has been changed successfully', 'ninja-job-board')
        ]);

    }
}
