<?php

namespace WPJobBoard\Classes\FormComponents;

if (!defined('ABSPATH')) {
    exit;
}

class SelectComponent extends BaseComponent
{
    public function __construct()
    {
        parent::__construct('select', 16);
    }

    public function component()
    {
        return array(
            'type'            => 'select',
            'editor_title'    => __('Dropdown', 'ninja-job-board'),
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
                'options'       => array(
                    'label' => __('Field choices', 'ninja-job-board'),
                    'type'  => 'key_pair',
                    'group' => 'general'
                )
            ),
            'field_options'   => array(
                'label'       => __('Dropdown', 'ninja-job-board'),
                'placeholder' => '',
                'required'    => 'no',
                'options'     => array(
                    array(
                        'label' => __('Select item 1', 'ninja-job-board'),
                        'value' => __('Select item 1', 'ninja-job-board')
                    ),
                    array(
                        'label' => __('Select item 2', 'ninja-job-board'),
                        'value' => __('Select item 2', 'ninja-job-board')
                    )
                )
            )
        );
    }

    public function render($element, $form, $elements)
    {
        $this->renderSelectInput($element, $form);
    }
}