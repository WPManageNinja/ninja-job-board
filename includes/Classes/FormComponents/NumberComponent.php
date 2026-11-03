<?php

namespace WPJobBoard\Classes\FormComponents;

if (!defined('ABSPATH')) {
    exit;
}

class NumberComponent extends BaseComponent
{
    public function __construct()
    {
        parent::__construct('number', 15);
    }

    public function component()
    {
        return array(
            'type'            => 'number',
            'editor_title'    => __('Number', 'ninja-job-board'),
            'group'           => 'input',
            'is_markup'       => 'no',
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
                'min_value' => array(
                    'label' => __('Minimum value', 'ninja-job-board'),
                    'type'  => 'number',
                    'group' => 'general'
                ),
            ),
            'field_options'   => array(
                'label' => __('Numeric value', 'ninja-job-board'),
                'placeholder' => '',
                'required' => 'no'
            )
        );
    }

    public function render($element, $form, $elements)
    {
        $element['type'] = 'number';
        $this->renderNormalInput($element, $form);
    }
}