<?php

namespace WPJobBoard\Classes\FormComponents;

use WPJobBoard\Classes\ArrayHelper;

if (!defined('ABSPATH')) {
    exit;
}

class ApplicantEmailComponent extends BaseComponent
{
    public function __construct()
    {
        parent::__construct('applicant_email', 11);
        add_filter('wpjobboard/validate_data_on_submission_applicant_email', array($this, 'validateEmailOnSubmission'), 10, 4);
    }

    public function component()
    {
        return array(
            'type'            => 'applicant_email',
            'editor_title'    => __('Applicant email', 'ninja-job-board'),
            'group'           => 'input',
            'postion_group'   => 'general',
            'editor_elements' => array(
                'label'         => array(
                    'label' => __('Field label', 'ninja-job-board'),
                    'type'  => 'text',
                    'group' => 'general'
                ),
                'placeholder'   => array(
                    'label' => __('Placeholder', 'ninja-job-board'),
                    'type'  => 'text',
                    'group' => 'general'
                ),
                'required'      => array(
                    'label' => __('Required', 'ninja-job-board'),
                    'type'  => 'switch',
                    'group' => 'general'
                ),
                'confirm_email'      => array(
                    'label' => __('Enable confirm email field', 'ninja-job-board'),
                    'type'  => 'confirm_email_switch',
                    'group' => 'general'
                ),
                'default_value' => array(
                    'label' => __('Default value', 'ninja-job-board'),
                    'type'  => 'text',
                    'group' => 'general'
                ),
            ),
            'field_options'   => array(
                'label' => __('Email address', 'ninja-job-board'),
                'placeholder' => __('Email address', 'ninja-job-board'),
                'required' => 'yes',
                'confirm_email' => 'no',
                'confirm_email_label' => __('Confirm email', 'ninja-job-board'),
                'default_value' => ''
            )
        );
    }

    public function validateEmailOnSubmission($error, $elementId, $element, $data)
    {
        if($error) {
            return $error;
        }
        $value = ArrayHelper::get($data, $elementId);
        if($value) {
            if(!is_email($value)) {
                /* translators: %s: field label */
                return sprintf(__('Enter a valid email address in %s.', 'ninja-job-board'), ArrayHelper::get($element, 'label'));
            }
        }

        if(ArrayHelper::get($element, 'options.confirm_email') == 'yes') {
            $confirmEmailvalue = ArrayHelper::get($data, '__confirm_'.$elementId);
            if($confirmEmailvalue != $value) {
                /* translators: 1: email field label, 2: confirm email field label */
                return sprintf(__('%1$s and %2$s do not match.', 'ninja-job-board'), ArrayHelper::get($element, 'label'), ArrayHelper::get($element, 'options.confirm_email_label'));
            }
        }

        return $error;
    }

    public function render($element, $form, $elements)
    {
        $element['type'] = 'email';
        $element['extra_input_class'] = 'wpjb_applicant_email';
        $defaultValue = apply_filters('wpjobboard/input_default_value', ArrayHelper::get($element['field_options'], 'default_value'), $element, $form);
        $element['field_options']['default_value'] = $defaultValue;
        $this->renderNormalInput($element, $form);
        if(ArrayHelper::get($element, 'field_options.confirm_email') == 'yes') {
            $element['field_options']['extra_data_atts'] = array(
                'data-parent_confirm_name' =>  $element['id']
            );
            $element['extra_input_class'] = 'wpjb_confirm_email';
            $element['id'] = '__confirm_'.$element['id'];
            $element['field_options']['placeholder'] = ArrayHelper::get($element, 'field_options.confirm_email_label', __('Confirm email', 'ninja-job-board'));
            $element['field_options']['label'] = ArrayHelper::get($element, 'field_options.confirm_email_label', __('Confirm email', 'ninja-job-board'));
            $this->renderNormalInput($element, $form);
        }
    }
}
