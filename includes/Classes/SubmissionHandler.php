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
        if (!wpJobBoardVerifyPublicNonce()) {
            wp_send_json_error(array(
                'message' => __('Security check failed. Please reload the page and try again.', 'ninja-job-board')
            ), 403);
        }

        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- verified in wpJobBoardVerifyPublicNonce()
        $formId = isset($_REQUEST['form_id']) ? absint($_REQUEST['form_id']) : 0;
        $rawFormData = isset($_REQUEST['form_data']) && is_string($_REQUEST['form_data']) ? wp_unslash($_REQUEST['form_data']) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- parsed below, then every value is sanitized by field type
        // phpcs:enable

        $form_data = array();
        parse_str($rawFormData, $form_data);

        $form = Forms::getForm($formId);

        if (!$form || $form->post_status !== 'publish') {
            wp_send_json_error(array(
                'message' => __('Invalid request. Please try again', 'ninja-job-board')
            ), 423);
        }

        // Honeypot and the optional Turnstile check (see SpamProtection)
        $spamError = apply_filters('wpjobboard/spam_check', '', $form_data, $form);
        if ($spamError) {
            wp_send_json_error(array(
                'message' => $spamError,
                'errors'  => array('spam' => $spamError)
            ), 423);
        }
        unset($form_data[SpamProtection::HONEYPOT_FIELD], $form_data['cf-turnstile-response']);

        do_action('wpjobboard/form_submission_activity_start', $formId);

        $formattedElements = Forms::getFormattedElements($formId);
        $this->validate($form_data, $formattedElements, $form);

        // Extract Input Items Here
        $inputItems = array();
        foreach ($formattedElements['input'] as $inputName => $inputElement) {
            $value = $this->sanitizeValue(ArrayHelper::get($form_data, $inputName), $inputElement['type']);
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
            'applicant_name'      => sanitize_text_field($this->applicantName),
            'applicant_email'     => sanitize_email($this->applicantEmail),
            'form_data_raw'       => maybe_serialize(wpJobBoardSanitizeText($form_data)),
            'form_data_formatted' => maybe_serialize($inputItems),
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

        $this->applicantName = is_string($applicantName) ? $applicantName : '';
        $this->applicantEmail = is_string($applicantEmail) ? $applicantEmail : '';

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
        /* translators: %s: field label */
        $label = sprintf(__('%s is required.', 'ninja-job-board'), $label);
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
            $confirmation['customUrl'] = esc_url_raw(PlaceholderParser::parse($url, $submission, 'url'));
        } else if ($confirmation['redirectTo'] == 'samePage') {
            do_action('wpjobboard/require_entry_html');
            $confirmation['messageToShow'] = wpJobBoardKsesWithStyle(PlaceholderParser::parse($confirmation['messageToShow'], $submission));
            do_action('wpjobboard/require_entry_html_done');
        }
        return $confirmation;
    }

    /**
     * Sanitize a submitted value by the field type
     * @param mixed  $value
     * @param string $type
     * @return mixed
     */
    private function sanitizeValue($value, $type)
    {
        if (is_array($value)) {
            return array_map(function ($item) use ($type) {
                return $this->sanitizeValue($item, $type);
            }, $value);
        }

        if (!is_scalar($value)) {
            return '';
        }

        $value = (string)$value;

        switch ($type) {
            case 'textarea':
                return sanitize_textarea_field($value);
            case 'applicant_email':
                return sanitize_email($value);
            default:
                return sanitize_text_field($value);
        }
    }

    /**
     * Unguessable hash. It is the only key that opens the public confirmation page.
     * @return string
     */
    private function getHash()
    {
        return 'wpjb_' . bin2hex(random_bytes(16));
    }
}
