<?php

namespace WPJobBoard\Classes\FormComponents;

use WPJobBoard\Classes\ArrayHelper;

if (!defined('ABSPATH')) {
    exit;
}

class ApplicantNameComponent extends BaseComponent
{
    public function __construct()
    {
        parent::__construct('applicant_name', 10);
    }
    
    public function component()
    {
        return array(
            'type'            => 'applicant_name',
            'editor_title'    => __('Applicant name', 'ninja-job-board'),
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
                'default_value' => array(
                    'label' => __('Default value', 'ninja-job-board'),
                    'type'  => 'text',
                    'group' => 'general'
                ),
            ),
            'field_options'   => array(
                'label' => __('Your name', 'ninja-job-board'),
                'placeholder' => __('Name', 'ninja-job-board'),
                'required' => 'yes'
            )
        );
    }

    public function render($element, $form, $elements)
    {
        $element['type'] = 'text';
        $defaultValue = ArrayHelper::get($element, 'field_options.default_value');

        if($defaultValue && strpos($defaultValue, '{current_user.display_name}') !== false) {
            $currentUserId = get_current_user_id();
            $replaceValue = '';
            if($currentUserId) {
                $currentUser = get_user_by('ID', $currentUserId);
                $replaceValue = $currentUser->display_name;
            }
            $element['field_options']['default_value'] = str_replace('{current_user.display_name}', $replaceValue, $defaultValue);
        }

        $this->renderNormalInput($element, $form);
    }
}