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
            'editor_title'    => 'Applicant Email',
            'group'           => 'input',
            'postion_group'   => 'general',
            'editor_elements' => array(
                'label'         => array(
                    'label' => 'Field Label',
                    'type'  => 'text',
                    'group' => 'general'
                ),
                'placeholder'   => array(
                    'label' => 'Placeholder',
                    'type'  => 'text',
                    'group' => 'general'
                ),
                'required'      => array(
                    'label' => 'Required',
                    'type'  => 'switch',
                    'group' => 'general'
                ),
                'confirm_email'      => array(
                    'label' => 'Enable Confirm Email Field',
                    'type'  => 'confirm_email_switch',
                    'group' => 'general'
                ),
                'default_value' => array(
                    'label' => 'Default Value',
                    'type'  => 'text',
                    'group' => 'general'
                ),
            ),
            'field_options'   => array(
                'label' => 'Email Address',
                'placeholder' => 'Email Address',
                'required' => 'yes',
                'confirm_email' => 'no',
                'confirm_email_label' => 'Confirm Email',
                'default_value' => ''
            )
        );
    }

    public function validateEmailOnSubmission($error, $elementId, $element, $data)
    {
        // Validation Already failed so We are just returning it
        if($error) {
            return $error;
        }
        $value = ArrayHelper::get($data, $elementId);
        if($value) {
            // We have to check if it's a valid email address or not
            if(!is_email($value)) {
                return __('Valid email address is required for field:', 'ninja-job-board').' '.ArrayHelper::get($element, 'label');
            }
        }

        // check if confirm email exists and need to validate
        if(ArrayHelper::get($element, 'options.confirm_email') == 'yes') {
            $confirmEmailvalue = ArrayHelper::get($data, '__confirm_'.$elementId);
            if($confirmEmailvalue != $value) {
                return ArrayHelper::get($element, 'label') .' & '.ArrayHelper::get($element, 'options.confirm_email_label') .__(' does not match', 'ninja-job-board');
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
            $element['field_options']['placeholder'] = ArrayHelper::get($element, 'field_options.confirm_email_label', 'Confirm Email');
            $element['field_options']['label'] = ArrayHelper::get($element, 'field_options.confirm_email_label', 'Confirm Email');
            $this->renderNormalInput($element, $form);
        }
    }
}
