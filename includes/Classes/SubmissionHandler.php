<?php

namespace WPJobBoard\Classes;

use WPJobBoard\Classes\Models\Forms;
use WPJobBoard\Classes\Models\Submission;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Form Submission Handler
 * @since 1.0.0
 */
class SubmissionHandler
{
    private $applicantName = '';
    private $applicantEmail = '';

    public function handeSubmission()
    {
        parse_str($_REQUEST['form_data'], $form_data);
        // Now Validate the form please
        $formId = absint($_REQUEST['form_id']);
        // Get Original Form Elements Now

        do_action('wpjobboard/form_submission_activity_start', $formId);

        $form = Forms::getForm($formId);

        if (!$form) {
            wp_send_json_error(array(
                'message' => __('Invalid request. Please try again', 'ninja-job-board')
            ), 423);
        }

        $formattedElements = Forms::getFormattedElements($formId);
        $this->validate($form_data, $formattedElements, $form);

        // Extract Input Items Here
        $inputItems = array();
        foreach ($formattedElements['input'] as $inputName => $inputElement) {
            $value = ArrayHelper::get($form_data, $inputName);
            $inputItems[$inputName] = apply_filters('wpjobboard/submitted_value_' . $inputElement['type'], $value, $inputElement, $form_data);
        }


        $currentUserId = get_current_user_id();
        if (!$this->applicantName && $currentUserId) {
            $currentUser = get_user_by('ID', $currentUserId);
            $this->applicantName = $currentUser->display_name;
        }

        if (!$this->applicantEmail && $currentUserId) {
            $currentUser = get_user_by('ID', $currentUserId);
            $this->applicantEmail = $currentUser->user_email;
        }

        $inputItems = apply_filters('wpjobboard/submission_data_formatted', $inputItems, $form_data, $formId);


        $submission = array(
            'form_id'             => $formId,
            'user_id'             => $currentUserId,
            'applicant_name'       => $this->customerName,
            'applicant_email'      => $this->customerEmail,
            'form_data_raw'       => maybe_serialize($form_data),
            'form_data_formatted' => maybe_serialize(wp_unslash($inputItems)),
            'application_status'      => 'applied',
            'submission_hash'     => $this->getHash(),
            'status'              => 'new',
            'created_at'          => gmdate('Y-m-d H:i:s'),
            'updated_at'          => gmdate('Y-m-d H:i:s')
        );

        $ipLoggingStatus = GeneralSettings::ipLoggingStatus(true);

        if (apply_filters('wpjobboard/record_client_info', $ipLoggingStatus, $form)) {
            $browser = new Browser();
            $submission['ip_address'] = $browser->getIp();
            $submission['browser'] = $browser->getBrowser();
            $submission['device'] = $browser->getPlatform();
        }

        $submission = apply_filters('wpjobboard/create_submission_data', $submission, $formId, $form_data);

        do_action('wpjobboard/before_submission_data_insert', $submission, $form_data);

        // Insert Submission
        $submissionModel = new Submission();
        $submissionId = $submissionModel->create($submission);
        do_action('wpjobboard/after_submission_data_insert', $submissionId, $formId);
        $submission = $submissionModel->getSubmission($submissionId);
        do_action('wpjobboard/after_form_submission_complete', $submission, $formId);
        $confirmation = Forms::getConfirmationSettings($formId);
        $confirmation = $this->parseConfirmation($confirmation, $submission);
        $confirmation = apply_filters('wpjobboard/form_confirmation', $confirmation, $submissionId, $formId);
        wp_send_json_success(array(
            'message'       => __('Form is successfully submitted', 'ninja-job-board'),
            'submission_id' => $submissionId,
            'confirmation'  => $confirmation
        ), 200);
    }

    private function validate($form_data, $formattedElements, $form)
    {
        $errors = array();
        $formId = $form->ID;
        $applicantName = '';
        $applicantEmail = '';

        // Validate Normal Inputs
        foreach ($formattedElements['input'] as $elementId => $element) {
            $error = false;
            if (ArrayHelper::get($element, 'options.required') == 'yes' && empty($form_data[$elementId])) {
                $error = $this->getErrorLabel($element, $formId);
            }
            $error = apply_filters('wpjobboard/validate_data_on_submission_' . $element['type'], $error, $elementId, $element, $form_data);
            if ($error) {
                $errors[$elementId] = $error;
            }

            if ($element['type'] == 'applicant_name' && !$applicantName && isset($form_data[$elementId])) {
                $applicantName = $form_data[$elementId];
            } else if ($element['type'] == 'applicant_email' && !$applicantEmail && isset($form_data[$elementId])) {
                $applicantEmail = $form_data[$elementId];
            }
        }

        $errors = apply_filters('wpjobboard/form_submission_validation_errors', $errors, $formId, $formattedElements);

        if ($errors) {
            wp_send_json_error(array(
                'message' => __('Form Validation failed', 'ninja-job-board'),
                'errors'  => $errors
            ), 423);
        }

        $this->customerName = $applicantName;
        $this->customerEmail = $applicantEmail;

        return;
    }

    private function getErrorLabel($element, $formId)
    {
        $label = ArrayHelper::get($element, 'options.label');
        if (!$label) {
            $label = ArrayHelper::get($element, 'options.placeholder');
            if (!$label) {
                $label = $element['id'];
            }
        }
        $label = $label . __(' is required', 'ninja-job-board');
        return apply_filters('wpjobboard/error_label_text', $label, $element, $formId);
    }

    public function parseConfirmation($confirmation, $submission)
    {
        // add payment hash to the url
        if (
            ($confirmation['redirectTo'] == 'customUrl' && $confirmation['customUrl']) ||
            ($confirmation['redirectTo'] == 'customPage' && $confirmation['customPage'])
        ) {
            if ($confirmation['redirectTo'] == 'customUrl') {
                $url = $confirmation['customUrl'];
            } else {
                $url = get_permalink(intval($confirmation['customPage']));
            }
            $confirmation['redirectTo'] = 'customUrl';
            $url = add_query_arg('wpjb_submission', $submission->submission_hash, $url);
            $confirmation['customUrl'] = PlaceholderParser::parse($url, $submission);
        } else if ($confirmation['redirectTo'] == 'samePage') {
            do_action('wpjobboard/require_entry_html');
            $confirmation['messageToShow'] = PlaceholderParser::parse($confirmation['messageToShow'], $submission);
            do_action('wpjobboard/require_entry_html_done');
        }
        return $confirmation;
    }

    private function getHash()
    {
        $prefix = 'wpjb_' . time();
        $uid = uniqid($prefix);
        // now let's make a unique number from 1 to 999
        $uid .= mt_rand(1, 999);
        $uid = str_replace(array("'", '/', '?', '#', "\\"), '', $uid);
        return $uid;
    }
}
