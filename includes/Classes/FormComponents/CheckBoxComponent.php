<?php

namespace WPJobBoard\Classes\FormComponents;

if (!defined('ABSPATH')) {
    exit;
}

class CheckBoxComponent extends BaseComponent
{
    public function __construct()
    {
        parent::__construct('checkbox', 18);
    }

    public function component()
    {
        return array(
            'type'            => 'checkbox',
            'editor_title'    => __('Checkbox', 'ninja-job-board'),
            'group'           => 'input',
            'postion_group'   => 'general',
            'editor_elements' => array(
                'label'         => array(
                    'label' => __('Field label', 'ninja-job-board'),
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
                'options'       => array(
                    'label' => __('Field options', 'ninja-job-board'),
                    'type'  => 'key_pair',
                    'group' => 'general'
                )
            ),
            'field_options'   => array(
                'label' => __('Checkboxes', 'ninja-job-board'),
                'required' => 'no',
                'default_value' => '',
                'options'     => array(
                    array(
                        'label' => __('Checkbox item 1', 'ninja-job-board'),
                        'value' => __('Checkbox item 1', 'ninja-job-board')
                    ),
                    array(
                        'label' => __('Checkbox item 2', 'ninja-job-board'),
                        'value' => __('Checkbox item 2', 'ninja-job-board')
                    )
                )
            )
        );
    }

    public function render($element, $form, $elements)
    {
        $this->renderCheckBoxInput($element, $form);
    }

}