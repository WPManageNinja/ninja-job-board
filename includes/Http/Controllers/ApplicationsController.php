<?php

namespace WPJobBoard\Http\Controllers;

use WPJobBoard\Classes\AccessControl;
use WPJobBoard\Classes\ArrayHelper;
use WPJobBoard\Classes\GeneralSettings;
use WPJobBoard\Classes\Models\Forms;
use WPJobBoard\Classes\Models\Submission;
use WPJobBoard\Classes\Models\SubmissionActivity;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Job applications (submissions)
 * @since 2.0.0
 */
class ApplicationsController extends Controller
{
    public function index(\WP_REST_Request $request)
    {
        $formId = absint($request->get_param('form_id'));
        $page = max(1, absint($request->get_param('page') ?: 1));
        $perPage = min(100, max(1, absint($request->get_param('per_page') ?: 20)));
        $search = $this->text($request, 'search');

        $submissionModel = new Submission();
        $submissions = $submissionModel->getSubmissions(
            $formId,
            $this->statusFilters($request),
            $perPage,
            ($page - 1) * $perPage,
            'DESC',
            is_string($search) ? $search : ''
        );

        return array(
            'submissions' => apply_filters('wpjobboard/form_entries', $submissions->items, $formId),
            'total'       => (int)$submissions->total
        );
    }

    public function show(\WP_REST_Request $request)
    {
        return $this->entryResponse(absint($request->get_param('id')));
    }

    /**
     * The next (older) or previous (newer) application
     */
    public function adjacent(\WP_REST_Request $request)
    {
        $formId = absint($request->get_param('form_id'));
        $isPrev = $request->get_param('direction') === 'prev';

        $query = wpJobBoardDB()->table('wjb_applications')
            ->orderBy('id', $isPrev ? 'ASC' : 'DESC')
            ->where('id', $isPrev ? '>' : '<', absint($request->get_param('id')));

        if ($formId) {
            $query->where('form_id', $formId);
        }

        $submission = $query->first();

        // The found application can belong to another form, so check the access again
        if (!$submission || !AccessControl::canAccess('get_submission', 'submissions', 0, $submission->id)) {
            return $this->error(__('Sorry, No Submission found', 'ninja-job-board'), 404);
        }

        return $this->entryResponse($submission->id);
    }

    public function destroy(\WP_REST_Request $request)
    {
        $submission = $this->requireSubmission($request);
        if (is_wp_error($submission)) {
            return $submission;
        }

        do_action('wpjobboard/before_delete_submission', $submission->id, $submission->form_id);
        (new Submission())->deleteSubmission($submission->id);
        do_action('wpjobboard/after_delete_submission', $submission->id, $submission->form_id);

        return array(
            'message' => __('Selected submission successfully deleted', 'ninja-job-board')
        );
    }

    public function addNote(\WP_REST_Request $request)
    {
        $submission = $this->requireSubmission($request);
        if (is_wp_error($submission)) {
            return $submission;
        }

        $note = $request->get_param('note');
        $content = is_string($note) ? esc_html(sanitize_textarea_field($note)) : '';
        if (!$content) {
            return $this->error(__('Please provide note content', 'ninja-job-board'));
        }

        $user = wp_get_current_user();
        $note = apply_filters('wpjobboard/add_note_by_user', array(
            'form_id'            => $submission->form_id,
            'submission_id'      => $submission->id,
            'type'               => 'custom_note',
            'content'            => $content,
            'created_by'         => $user->display_name,
            'created_by_user_id' => $user->ID
        ), $submission->form_id, $submission->id);

        do_action('wpjobboard/before_create_note_by_user', $note);
        SubmissionActivity::createActivity($note);
        do_action('wpjobboard/after_create_note_by_user', $note);

        return array(
            'message'    => __('Note successfully added', 'ninja-job-board'),
            'activities' => SubmissionActivity::getSubmissionActivity($submission->id)
        );
    }

    /**
     * Change the application status and/or the internal status, with an optional note
     */
    public function updateStatus(\WP_REST_Request $request)
    {
        $submission = $this->requireSubmission($request);
        if (is_wp_error($submission)) {
            return $submission;
        }

        $applicationStatus = $this->knownStatus($request->get_param('application_status'), GeneralSettings::getApplicationStatuses());
        $internalStatus = $this->knownStatus($request->get_param('status'), GeneralSettings::getInternalStatuses());
        $note = $request->get_param('note');
        $note = is_string($note) ? wp_kses_post($note) : '';

        if (!$applicationStatus && !$internalStatus) {
            return $this->error(__('Please Provide a valid status', 'ninja-job-board'));
        }

        $submissionModel = new Submission();
        $changed = false;

        if ($applicationStatus && $applicationStatus !== $submission->application_status) {
            do_action('wpjobboard/before_application_status_change_manually', $submission, $applicationStatus, $submission->application_status);
            $submissionModel->update($submission->id, array('application_status' => $applicationStatus));
            do_action('wpjobboard/after_application_status_change_manually', $submission->id, $applicationStatus, $submission->application_status);
            $this->logStatusChange($submission, __('Application status changed:', 'ninja-job-board'), $submission->application_status, $applicationStatus, $note);
            $note = '';
            $changed = true;
        }

        if ($internalStatus && $internalStatus !== $submission->status) {
            do_action('wpjobboard/before_internal_status_change_manually', $submission, $internalStatus, $submission->status);
            $submissionModel->update($submission->id, array('status' => $internalStatus));
            do_action('wpjobboard/after_internal_status_change_manually', $submission->id, $internalStatus, $submission->status);
            $this->logStatusChange($submission, __('Internal status changed:', 'ninja-job-board'), $submission->status, $internalStatus, $note);
            $changed = true;
        }

        if (!$changed) {
            return $this->error(__('The submission have the same status', 'ninja-job-board'));
        }

        return array(
            'message' => __('Status has been changed successfully', 'ninja-job-board')
        );
    }

    /**
     * Application count by status for one form
     */
    public function report(\WP_REST_Request $request)
    {
        $formId = absint($request->get_param('form_id'));
        $submissionModel = new Submission();

        $reports = array(
            '' => array(
                'label'            => __('All', 'ninja-job-board'),
                'submission_count' => $submissionModel->getTotalCount($formId)
            )
        );

        foreach (GeneralSettings::getApplicationStatuses() as $status => $statusName) {
            $reports[$status] = array(
                'label'            => $statusName,
                'submission_count' => $submissionModel->getTotalCount($formId, $status)
            );
        }

        return array(
            'reports' => $reports
        );
    }

    /**
     * Download the applications of one form as CSV. The browser opens this URL directly, with _wpnonce.
     */
    public function export(\WP_REST_Request $request)
    {
        $formId = absint($request->get_param('form_id'));
        if (!$formId || !Forms::getForm($formId)) {
            return $this->error(__('Job post not found', 'ninja-job-board'), 404);
        }

        $submissionModel = new Submission();
        $submissions = $submissionModel->getSubmissions($formId, $this->statusFilters($request))->items;

        $formElements = get_post_meta($formId, 'wpjobboard_application_builder_settings', true);
        $formElements = is_array($formElements) ? $formElements : array();

        $headers = array();
        $rows = array();

        foreach ($formElements as $element) {
            if (ArrayHelper::get($element, 'group') == 'input') {
                $headers[ArrayHelper::get($element, 'id')] = $this->safeCell($submissionModel->getLabel($element));
            }
        }

        foreach ($submissions as $submission) {
            $inputValues = is_array($submission->form_data_formatted) ? $submission->form_data_formatted : array();
            $row = array();
            foreach ($formElements as $element) {
                if (ArrayHelper::get($element, 'group') != 'input') {
                    continue;
                }
                $elementId = ArrayHelper::get($element, 'id');
                $value = apply_filters('wpjobboard/exporting_entry_value_' . $element['type'], ArrayHelper::get($inputValues, $elementId), $submission, $element);
                if (is_array($value)) {
                    $value = implode(', ', array_filter($value, 'is_scalar'));
                }
                $row[$elementId] = $this->safeCell($value);
            }
            $rows[] = apply_filters('wpjobboard/parsed_export_entry', $row, $submission);
        }

        array_unshift($rows, $headers);

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="export-data-' . gmdate('d-m-Y') . '.csv"');
        echo $this->toCsv($rows); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV file download, not HTML; every cell is cleaned in safeCell() and quoted in toCsv()
        exit();
    }

    private function entryResponse($submissionId)
    {
        $submissionModel = new Submission();
        $submission = $submissionModel->getSubmission($submissionId, array('activities'));

        if (!$submission) {
            return $this->error(__('Sorry, No Submission found', 'ninja-job-board'), 404);
        }

        if ($submission->user_id) {
            $user = get_user_by('ID', $submission->user_id);
            if ($user) {
                $submission->user = array(
                    'display_name' => $user->display_name,
                    'profile_url'  => get_edit_user_link($user->ID)
                );
            }
        }

        $submission = apply_filters('wpjobboard/form_entry', $submission);

        return array(
            'submission'    => $submission,
            'entry'         => (object)$submissionModel->getParsedSubmission($submission),
            'other_entries' => $submissionModel->getOtherSubmission($submission)
        );
    }

    private function requireSubmission(\WP_REST_Request $request)
    {
        $submission = (new Submission())->getSubmission(absint($request->get_param('id')));
        if (!$submission) {
            return $this->error(__('Sorry, No Submission found', 'ninja-job-board'), 404);
        }
        return $submission;
    }

    private function statusFilters(\WP_REST_Request $request)
    {
        $wheres = array();

        $applicationStatus = $this->knownStatus($request->get_param('application_status'), GeneralSettings::getApplicationStatuses());
        if ($applicationStatus) {
            $wheres['application_status'] = $applicationStatus;
        }

        $status = $this->knownStatus($request->get_param('status'), GeneralSettings::getInternalStatuses());
        if ($status) {
            $wheres['status'] = $status;
        }

        return $wheres;
    }

    /**
     * The status key when it is one of the known statuses, otherwise ''
     */
    private function knownStatus($value, $statuses)
    {
        return (is_string($value) && isset($statuses[$value])) ? $value : '';
    }

    private function logStatusChange($submission, $label, $oldStatus, $newStatus, $note = '')
    {
        $content = esc_html($label) . ' <b>' . esc_html($oldStatus) . '</b> &rarr; <b>' . esc_html($newStatus) . '</b>';
        if ($note) {
            $content .= '<br />' . esc_html__('Note:', 'ninja-job-board') . ' ' . $note;
        }

        $user = wp_get_current_user();
        SubmissionActivity::createActivity(array(
            'form_id'            => $submission->form_id,
            'submission_id'      => $submission->id,
            'type'               => 'info',
            'created_by'         => $user->display_name,
            'created_by_user_id' => $user->ID,
            'content'            => $content
        ));
    }

    /**
     * Build RFC 4180 CSV text
     */
    private function toCsv($rows)
    {
        $lines = array();
        foreach ($rows as $row) {
            $cells = array();
            foreach ((array)$row as $cell) {
                $cells[] = '"' . str_replace('"', '""', (string)$cell) . '"';
            }
            $lines[] = implode(',', $cells);
        }
        // UTF-8 BOM so spreadsheet apps detect the encoding
        return "\xEF\xBB\xBF" . implode("\r\n", $lines) . "\r\n";
    }

    /**
     * Plain-text cell value. A leading quote stops spreadsheet apps from running it as a formula.
     */
    private function safeCell($value)
    {
        $value = is_scalar($value) ? wp_strip_all_tags((string)$value) : '';
        if ($value !== '' && in_array($value[0], array('=', '+', '-', '@', "\t", "\r"), true)) {
            $value = "'" . $value;
        }
        return $value;
    }
}
