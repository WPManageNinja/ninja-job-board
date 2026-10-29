<?php

namespace WPJobBoard\Classes;

use WPJobBoard\Classes\File\FileAccess;
use WPJobBoard\Classes\File\FileHandler;
use WPJobBoard\Classes\Models\Submission;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Connects job applications to the WordPress privacy tools
 * (Tools > Export Personal Data / Erase Personal Data).
 *
 * Applications are matched by the applicant email address.
 *
 * @since 2.0.0
 */
class Privacy
{
    const PER_PAGE = 20;

    public function register()
    {
        add_filter('wp_privacy_personal_data_exporters', array($this, 'addExporter'));
        add_filter('wp_privacy_personal_data_erasers', array($this, 'addEraser'));
        add_action('admin_init', array($this, 'addPolicyText'));
        // Every way of deleting an application (admin, eraser, job deletion) removes its files
        add_action('wpjobboard/before_delete_submission', array($this, 'deleteApplicationFiles'));
    }

    public function addExporter($exporters)
    {
        $exporters['ninja-job-board'] = array(
            'exporter_friendly_name' => __('Job applications', 'ninja-job-board'),
            'callback'               => array($this, 'export')
        );
        return $exporters;
    }

    public function addEraser($erasers)
    {
        $erasers['ninja-job-board'] = array(
            'eraser_friendly_name' => __('Job applications', 'ninja-job-board'),
            'callback'             => array($this, 'erase')
        );
        return $erasers;
    }

    public function export($email, $page = 1)
    {
        $page = max(1, absint($page));
        $applications = $this->applicationsFor($email, $page);
        $submissionModel = new Submission();
        $items = array();

        foreach ($applications as $application) {
            $submission = $submissionModel->getSubmission($application->id);
            if (!$submission) {
                continue;
            }

            $data = array(
                array('name' => __('Job', 'ninja-job-board'), 'value' => $submission->post_title),
                array('name' => __('Submitted at', 'ninja-job-board'), 'value' => $submission->created_at),
                array('name' => __('Name', 'ninja-job-board'), 'value' => $submission->applicant_name),
                array('name' => __('Email', 'ninja-job-board'), 'value' => $submission->applicant_email),
                array('name' => __('Application status', 'ninja-job-board'), 'value' => $submission->application_status),
                array('name' => __('IP address', 'ninja-job-board'), 'value' => $submission->ip_address),
                array('name' => __('Browser', 'ninja-job-board'), 'value' => $submission->browser),
                array('name' => __('Device', 'ninja-job-board'), 'value' => $submission->device)
            );

            foreach ($submissionModel->getParsedSubmission($submission) as $elementId => $field) {
                $value = wp_strip_all_tags((string)$field['value']);
                if ($field['type'] === 'file_upload_input') {
                    // List the file names; the files themselves stay behind the permission check
                    $names = FileAccess::fileNames(ArrayHelper::get($submission->form_data_formatted, $elementId));
                    $value = implode(', ', array_map(function ($name) {
                        return preg_replace('/^wpf-[a-f0-9]{32}-wpf-/', '', $name);
                    }, $names));
                }
                if ($value !== '') {
                    $data[] = array('name' => wp_strip_all_tags((string)$field['label']), 'value' => $value);
                }
            }

            $items[] = array(
                'group_id'          => 'ninja-job-board-applications',
                'group_label'       => __('Job applications', 'ninja-job-board'),
                'group_description' => __('Applications submitted through Ninja Job Board.', 'ninja-job-board'),
                'item_id'           => 'wpjb-application-' . $submission->id,
                'data'              => array_values(array_filter($data, function ($row) {
                    return $row['value'] !== null && $row['value'] !== '';
                }))
            );
        }

        return array(
            'data' => $items,
            'done' => count($applications) < self::PER_PAGE
        );
    }

    /**
     * Delete the applications, their activity and their uploaded files.
     * Always reads page 1: the rows of the previous page are already gone.
     */
    public function erase($email, $page = 1)
    {
        $applications = $this->applicationsFor($email, 1);
        $submissionModel = new Submission();
        $removed = false;

        foreach ($applications as $application) {
            do_action('wpjobboard/before_delete_submission', $application->id, $application->form_id);
            $submissionModel->deleteSubmission($application->id);
            do_action('wpjobboard/after_delete_submission', $application->id, $application->form_id);
            $removed = true;
        }

        return array(
            'items_removed'  => $removed,
            'items_retained' => false,
            'messages'       => array(),
            'done'           => count($applications) < self::PER_PAGE
        );
    }

    public function addPolicyText()
    {
        if (!function_exists('wp_add_privacy_policy_content')) {
            return;
        }

        $content = '<p>' . esc_html__('When you apply for a job, we store the information you enter in the application form, the files you upload (for example your CV), the date, and, if enabled, your IP address and browser. The hiring team uses this data to review your application.', 'ninja-job-board') . '</p>'
            . '<p>' . esc_html__('Uploaded files are only available to logged-in users who are allowed to review applications. You can ask us to export or erase your application data.', 'ninja-job-board') . '</p>';

        wp_add_privacy_policy_content(__('Ninja Job Board', 'ninja-job-board'), wp_kses_post($content));
    }

    private function applicationsFor($email, $page)
    {
        $email = sanitize_email($email);
        if (!$email) {
            return array();
        }

        return wpJobBoardDB()->table('wjb_applications')
            ->select(array('id', 'form_id'))
            ->where('applicant_email', $email)
            ->orderBy('id', 'ASC')
            ->limit(self::PER_PAGE)
            ->offset(($page - 1) * self::PER_PAGE)
            ->get();
    }

    /**
     * Delete the uploaded files of one application
     * @param int $submissionId
     */
    public function deleteApplicationFiles($submissionId)
    {
        $submission = (new Submission())->getSubmission(absint($submissionId));
        if (!$submission) {
            return;
        }

        $dir = FileHandler::getUploadDir()['path'];
        $values = is_array($submission->form_data_formatted) ? $submission->form_data_formatted : array();
        foreach ($values as $value) {
            foreach (FileAccess::fileNames($value) as $name) {
                $path = $dir . '/' . $name;
                if (FileHandler::isPluginFileName($name) && is_file($path)) {
                    wp_delete_file($path);
                }
            }
        }
    }
}
