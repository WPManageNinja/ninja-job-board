<?php

namespace WPJobBoard\Classes\Tools;

use WPJobBoard\Classes\AccessControl;
use WPJobBoard\Classes\ArrayHelper;
use WPJobBoard\Classes\Models\Forms;
use WPJobBoard\Classes\Models\Submission;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Form Scheduling and Restriction Class
 * @since 1.0.0
 */
class SchedulingSettings
{
    public function register()
    {
        add_action('wp_ajax_wpjb_scheduling_endpoints', array($this, 'schedulingAjaxHandler'));
    }

    public function schedulingAjaxHandler()
    {
        $route = sanitize_text_field($_REQUEST['route']);
        $validRoutes = array(
            'get_settings'    => 'getSettings',
            'update_settings' => 'updateSettings'
        );
        if (isset($validRoutes[$route])) {
            AccessControl::checkAndPresponseError('save_form_settings', 'forms');
            do_action('wpjobboard/doing_ajax_forms_' . $route);
            return $this->{$validRoutes[$route]}();
        }
    }

    public function getSettings()
    {
        $formId = intval($_REQUEST['form_id']);
        $settings = Forms::getSchedulingSettings($formId);

        wp_send_json_success(array(
            'scheduling_settings' => $settings,
            'current_date_time'   => gmdate('d M Y H:i:s')
        ), 200);
    }

    public function updateSettings()
    {
        $formId = intval($_REQUEST['form_id']);
        $settings = wpJobBoardSanitize(ArrayHelper::get($_REQUEST, 'settings', []));

        if (
            ArrayHelper::get($settings, 'limitNumberOfEntries.status') == 'no' &&
            ArrayHelper::get($settings, 'scheduleForm.status') == 'no' &&
            ArrayHelper::get($settings, 'requireLogin.status') == 'no'
        ) {
            $settings = false;
        }

        if (ArrayHelper::get($settings, 'scheduleForm.status') == 'no') {
            update_post_meta($formId, 'application_start_date', 0);
            update_post_meta($formId, 'application_end_date', 0);
        } else {
            update_post_meta($formId, 'application_start_timestamp', strtotime(ArrayHelper::get($settings, 'scheduleForm.start_date')));
            update_post_meta($formId, 'application_end_timestamp', strtotime(ArrayHelper::get($settings, 'scheduleForm.end_date')));
        }

        update_post_meta($formId, 'wpjb_form_scheduling_settings', $settings);
        wp_send_json_success(array(
            'message' => __('Settings successfully updated', 'ninja-job-board')
        ), 200);
    }

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
        // if now sheduleing settings found then just return
        if (!get_post_meta($form->ID, 'wpjb_form_scheduling_settings', true)) {
            return $wrapperCSSClasses;
        }

        $extra_css_class = '';
        // We have some schedule settings now so we have add some wrapper class
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
            $wrapperCSSClasses[] = 'wpjb_restriction_action_' . $sheduleSettings['restriction_applied_type'];
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
                    : __('Submission limit has been exceded.', 'ninja-job-board');
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
                    : __('Form submission is not started yet.', 'ninja-job-board');

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
