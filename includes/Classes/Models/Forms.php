<?php

namespace WPJobBoard\Classes\Models;

use WPJobBoard\Classes\GeneralSettings;
use WPJobBoard\Classes\ArrayHelper;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ajax Handler Class
 * @since 1.0.0
 */
class Forms
{
    public static function getForms($args = array(), $with = array())
    {
        $whereArgs = array(
            'post_type' => 'wp_job_board'
        );

        $whereArgs = apply_filters('wpjobboard/all_forms_where_args', $whereArgs);

        $formsQuery = wpJobBoardDB()->table('posts')
            ->orderBy('ID', 'DESC')
            ->offset($args['offset'])
            ->whereNotIn('post_status', ['auto-draft'])
            ->limit($args['posts_per_page']);

        foreach ($whereArgs as $key => $where) {
            $formsQuery->where($key, $where);
        }

        if (!empty($args['s'])) {
            $formsQuery->where(function ($q) use ($args) {
                $q->where('post_title', 'LIKE', "%{$args['s']}%");
                $q->orWhere('ID', 'LIKE', "%{$args['s']}%");
                $q->orWhere('post_content', 'LIKE', "%{$args['s']}%");
            });
        }

        $formsQuery->whereNotIn('post_status', ['auto-draft']);

        $total = $formsQuery->count();

        $forms = $formsQuery->get();

        $submissionModel = new Submission();

        foreach ($forms as $form) {
            $form->edit_url = get_edit_post_link($form);
            if (in_array('entries_count', $with)) {
                $form->entries_count = $submissionModel->getEntryCountByApplicationStatus($form->ID);
            }
        }

        $forms = apply_filters('wpjobboard/get_all_forms', $forms);

        $lastPage = ceil($total / $args['posts_per_page']);

        return array(
            'forms'     => $forms,
            'total'     => $total,
            'last_page' => $lastPage
        );
    }

    public static function getTotalCount()
    {
        return wpJobBoardDB()->table('posts')
            ->where('post_type', 'wp_job_board')
            ->whereNotIn('post_status', ['auto-draft'])
            ->count();
    }

    public static function getAllAvailableForms()
    {
        return wpJobBoardDB()->table('posts')
            ->select(array('ID', 'post_title'))
            ->where('post_type', 'wp_job_board')
            ->whereNotIn('post_status', ['auto-draft'])
            ->orderBy('ID', 'DESC')
            ->get();
    }

    public static function create($data)
    {
        $data['post_type'] = 'wp_job_board';
        if (!isset($data['post_status'])) {
            $data['post_status'] = 'draft';
        }

        $id = wp_insert_post($data);
        return $id;
    }

    public static function update($formId, $data)
    {
        $data['ID'] = $formId;
        $data['post_type'] = 'wp_job_board';
        if (!isset($data['post_status'])) {
            $data['post_status'] = 'publish';
        }
        return wp_update_post($data);
    }

    public static function getButtonSettings($formId)
    {
        $settings = get_post_meta($formId, 'wpjobboard_submit_button_settings', true);
        if (!$settings) {
            $settings = array();
        }
        $buttonDefault = array(
            'button_text'     => __('Apply', 'ninja-job-board'),
            'processing_text' => __('Please Wait…', 'ninja-job-board'),
            'button_style'    => 'wpjb_default_btn',
            'css_class'       => ''
        );

        return wp_parse_args($settings, $buttonDefault);
    }

    public static function getForm($formId)
    {
        $form = get_post($formId, 'OBJECT');
        if (!$form || $form->post_type != 'wp_job_board') {
            return false;
        }
        return $form;
    }

    public static function getFormattedElements($formId)
    {
        $elements = Forms::getBuilderSettings($formId);
        $formattedElements = array(
            'input' => array()
        );
        foreach ($elements as $element) {
            $formattedElements[$element['group']][$element['id']] = array(
                'options' => $element['field_options'],
                'type'    => $element['type'],
                'id'      => $element['id'],
                'label'   => ArrayHelper::get($element['field_options'], 'label')
            );
        }

        return $formattedElements;
    }

    public static function getFormInputLabels($formId)
    {
        $elements = get_post_meta($formId, 'wpjobboard_application_builder_settings', true);
        if (!$elements) {
            return (object)array();
        }
        $formLabels = array();
        foreach ($elements as $element) {
            if ($element['group'] == 'input') {
                $elementId = ArrayHelper::get($element, 'id');
                if (!$label = ArrayHelper::get($element, 'field_options.admin_label')) {
                    $label = ArrayHelper::get($element, 'field_options.label');
                }
                if (!$label) {
                    $label = $elementId;
                }
                $formLabels[$elementId] = $label;
            }
        }
        return (object)$formLabels;
    }

    public static function getConfirmationSettings($formId)
    {
        $confirmationSettings = get_post_meta($formId, 'jobboard_confirmation_settings', true);
        if (!$confirmationSettings) {
            $confirmationSettings = array();
        }
        $defaultSettings = array(
            'confirmation_type'    => 'custom',
            'redirectTo'           => 'samePage',
            'customUrl'            => '',
            'messageToShow'        => __('Form has been successfully submitted', 'ninja-job-board'),
            'samePageFormBehavior' => 'hide_form',
        );
        return wp_parse_args($confirmationSettings, $defaultSettings);
    }

    public static function getEditorShortCodes($formId, $html = true)
    {
        $builderSettings = get_post_meta($formId, 'wpjobboard_application_builder_settings', true);
        if (!$builderSettings) {
            return array();
        }
        $formattedShortcodes = array(
            'input' => array(
                'title'      => 'Custom Input Items',
                'shortcodes' => array()
            )
        );


        foreach ($builderSettings as $element) {
            $elementId = ArrayHelper::get($element, 'id');
            if ($element['group'] == 'input') {
                $formattedShortcodes['input']['shortcodes']['{input.' . $elementId . '}'] = self::getLabel($element);
            }
        }

        $items = [$formattedShortcodes['input']];

        $submissionItem = array(
            'title'      => 'Submission Fields',
            'shortcodes' => array(
                '{submission.id}'              => __('Submission ID', 'ninja-job-board'),
                '{submission.submission_hash}' => __('Submission Hash ID', 'ninja-job-board'),
                '{submission.applicant_name}'  => __('Applicant Name', 'ninja-job-board'),
                '{submission.applicant_email}' => __('Applicant Email', 'ninja-job-board'),
            )
        );

        if ($html) {
            $submissionItem['shortcodes']['{submission.all_input_field_html}'] = __('All input field html', 'ninja-job-board');
        }


        $items[] = $submissionItem;
        return $items;
    }

    public static function getBuilderSettings($formId)
    {
        $builderSettings = get_post_meta($formId, 'wpjobboard_application_builder_settings', true);
        if (!$builderSettings) {
            $builderSettings = array();
        }
        $defaultSettings = array();
        $elements = wp_parse_args($builderSettings, $defaultSettings);
        $allElements = GeneralSettings::getComponents();
        $parsedElements = array();

        foreach ($elements as $elementIndex => $element) {
            if (!empty($allElements[$element['type']])) {
                $componentElement = $allElements[$element['type']];
                $fieldOption = ArrayHelper::get($element, 'field_options');
                if ($fieldOption) {
                    $componentElement['field_options'] = $fieldOption;
                }
                $componentElement['id'] = ArrayHelper::get($element, 'id');
                $element = $componentElement;
            }
            $parsedElements[$elementIndex] = $element;
        }
        return $parsedElements;
    }

    public static function deleteForm($formID)
    {
        wp_delete_post($formID, true);
        return true;
    }

    private static function getLabel($element)
    {
        $elementId = ArrayHelper::get($element, 'id');
        if (!$label = ArrayHelper::get($element, 'field_options.admin_label')) {
            $label = ArrayHelper::get($element, 'field_options.label');
        }
        if (!$label) {
            $label = $elementId;
        }
        return $label;
    }

    public static function getDesignSettings($formId)
    {
        $settings = get_post_meta($formId, 'wpjobboard_form_design_settings', true);
        if (!$settings) {
            $settings = array();
        }
        $defaults = array(
            'labelPlacement'         => 'top',
            'asteriskPlacement'      => 'none',
            'submit_button_position' => 'left',
            'extra_styles'           => array(
                'wpjb_default_form_styles' => 'yes',
                'wpjb_bold_labels'         => 'no'
            )
        );
        return wp_parse_args($settings, $defaults);
    }

    public static function getSchedulingSettings($formId)
    {
        $settings = get_post_meta($formId, 'wpjb_form_scheduling_settings', true);
        if (!$settings) {
            $settings = array();
        }
        $defaults = array(
            'limitNumberOfEntries'     => array(
                'status'                     => 'no',
                'limit_type'                 => 'total',
                'number_of_entries'          => 100,
                'limit_application_statuses' => array(),
                'limit_exceeds_message'      => __('Number of entry has been exceeds, Please check back later', 'ninja-job-board')
            ),
            'scheduleForm'             => array(
                'status'               => 'no',
                'start_date'           => gmdate('Y-m-d H:i:s'),
                'end_date'             => '',
                'before_start_message' => __('Form submission time schedule is not started yet. Please check back later', 'ninja-job-board'),
                'expire_message'       => __('Form submission time has been expired.')
            ),
            'requireLogin'             => array(
                'status'  => 'no',
                'message' => __('You need to login to submit this form', 'ninja-job-board')
            ),
            'restriction_applied_type' => 'hide_form'
        );
        return wp_parse_args($settings, $defaults);
    }

    public static function getExpirationDateTime($formId)
    {
        $settings = get_post_meta($formId, 'wpjb_form_scheduling_settings', true);
        if (!$settings) {
            return false;
        }

        if (ArrayHelper::get($settings, 'scheduleForm.status') == 'yes') {
            $timeSchedule = ArrayHelper::get($settings, 'scheduleForm');
            if ($timeSchedule['end_date']) {
                return $timeSchedule['end_date'];
            }
        }

        return false;
    }
}
