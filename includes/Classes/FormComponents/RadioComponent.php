<?php

namespace WPJobBoard\Classes\FormComponents;

if (!defined('ABSPATH')) {
    exit;
}

class RadioComponent extends BaseComponent
{
    public function __construct()
    {
        parent::__construct('radio', 17);
    }

    public function component()
    {
        return array(
            'type'            => 'radio',
            'editor_title'    => __('Radio', 'ninja-job-board'),
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
                    'label' => __('Field choices', 'ninja-job-board'),
                    'type' => 'key_pair',
                    'group' => 'general'
                )
            ),
            'field_options'   => array(
                'label' => __('Radio', 'ninja-job-board'),
                'placeholder' => '',
                'required' => 'no',
                'options' => array(
                    array(
                        'label' => __('Radio item 1', 'ninja-job-board'),
                        'value' => __('Radio item 1', 'ninja-job-board')
                    ),
                    array(
                        'label' => __('Radio item 2', 'ninja-job-board'),
                        'value' => __('Radio item 2', 'ninja-job-board')
                    )
                )
            )
        );
    }

    public function render($element, $form, $elements)
    {
        $this->renderRadioInput($element, $form);
    }
}