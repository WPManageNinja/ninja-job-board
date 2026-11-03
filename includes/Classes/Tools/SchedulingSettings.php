<?php

namespace WPJobBoard\Classes\Tools;

use WPJobBoard\Classes\ArrayHelper;
use WPJobBoard\Classes\Models\Forms;
use WPJobBoard\Classes\Models\Submission;

if (!defined('ABSPATH')) {
    exit;
}

class SchedulingSettings
{
    public function checkRestrictionHooks()
    {
        add_filter('wpjobboard/form_wrapper_css_classes', array($this, 'checkRestrictionOnRender'), 10, 2);
        add_filter('wpjobboard/form_submission_validation_errors', array($this, 'validateForm'), 100, 2);
    }

    public function validateForm($errors, $formId)
    {
        if ($errors) {
            return $errors;
        }
        if (!get_post_meta($formId, 'wpjb_form_scheduling_settings', true)) {
            return $errors;
        }
        $sheduleSettings = Forms::getSchedulingSettings($formId);
        $errorMessage = '';
        if ($message = $this->checkIfExceedsEntryLimit($formId, $sheduleSettings)) {
            $errorMessage = $message;
        } else if ($timeMessage = $this->checkTimeSchedulingValidityError($formId, $sheduleSettings)) {
            $errorMessage = $timeMessage;
        } else if ($message = $this->checkLoginValidityError($formId, $sheduleSettings)) {
            $errorMessage = $message;
        }
        if ($errorMessage) {
            $errors[] = $errorMessage;
        }
        return $errors;
    }

    public function checkRestrictionOnRender($wrapperCSSClasses, $form)
    {
        if (!get_post_meta($form->ID, 'wpjb_form_scheduling_settings', true)) {
            return $wrapperCSSClasses;
        }

        $extra_css_class = '';
        $sheduleSettings = $form->scheduleing_settings;
        if ($message = $this->checkIfExceedsEntryLimit($form->ID, $sheduleSettings)) {
            $extra_css_class = 'wpjb_exceeds_entry_limit';
            $this->addErrorMessage($form->ID, $message);
        } else if ($timeMessage = $this->checkTimeSchedulingValidityError($form->ID, $sheduleSettings)) {
            $extra_css_class = 'wpjb_time_schedule_fail';
            $this->addErrorMessage($form->ID, $timeMessage);
        } else if ($message = $this->checkLoginValidityError($form->ID, $sheduleSettings)) {
            $extra_css_class = 'wpjb_logged_in_required';
            $this->addErrorMessage($form->ID, $message);
        }

        if ($extra_css_class) {
            $wrapperCSSClasses[] = $extra_css_class;
            $wrapperCSSClasses[] = 'wpjb_restriction_action_' . sanitize_html_class(ArrayHelper::get($sheduleSettings, 'restriction_applied_type'));
        }
        return $wrapperCSSClasses;
    }

    private function checkIfExceedsEntryLimit($formId, $sheduleSettings)
    {
        if (ArrayHelper::get($sheduleSettings, 'limitNumberOfEntries.status') == 'yes') {
            $limitEntrySettings = ArrayHelper::get($sheduleSettings, 'limitNumberOfEntries');
            $limitPeriod = ArrayHelper::get($limitEntrySettings, 'limit_type');
            $numberOfEntries = ArrayHelper::get($limitEntrySettings, 'number_of_entries');
            $applicationStatuses = ArrayHelper::get($limitEntrySettings, 'limit_application_statuses');
            $submissionModel = new Submission();
            $totalEntryCount = $submissionModel->getEntryCountByApplicationStatus($formId, $applicationStatuses, $limitPeriod);
            if ($totalEntryCount >= intval($numberOfEntries)) {
                return $limitEntrySettings['limit_exceeds_message']
                    ? $limitEntrySettings['limit_exceeds_message']
                    : __('The application limit has been reached.', 'ninja-job-board');
            }
        }
        return false;
    }

    private function checkTimeSchedulingValidityError($formId, $sheduleSettings)
    {
        if (ArrayHelper::get($sheduleSettings, 'scheduleForm.status') == 'yes') {
            $timeSchedule = ArrayHelper::get($sheduleSettings, 'scheduleForm');
            $time = time();
            $start = strtotime($timeSchedule['start_date']);
            $end = strtotime($timeSchedule['end_date']);
            if ($time < $start) {
                return $timeSchedule['before_start_message']
                    ? $timeSchedule['before_start_message']
                    : __('The application period has not started yet.', 'ninja-job-board');

            }
            if ($time >= $end) {
                return $timeSchedule['expire_message']
                    ? $timeSchedule['expire_message']
                    : __('Form submission is now closed.', 'ninja-job-board');
            }
        }

        return false;
    }

    private function checkLoginValidityError($formId, $sheduleSettings)
    {
        if (ArrayHelper::get($sheduleSettings, 'requireLogin.status') == 'yes') {
            if (!is_user_logged_in()) {
                return !empty($sheduleSettings['message'])
                    ? $sheduleSettings['message']
                    : __('You must be logged in to submit the form.', 'ninja-job-board');
            }
        }

        return false;
    }

    private function addErrorMessage($formId, $message = '')
    {
        if ($message) {
            add_action('wpjobboard/form_render_after_' . $formId, function ($form) use ($message) {
                echo '<div class="wpjb_form_notices wpjb_form_restrictuon_errors wpjb_form_errors">' . wp_kses_post($message) . '</div>';
            });
        }
    }
}
