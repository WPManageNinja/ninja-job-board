<?php

namespace WPJobBoard\Classes\FormComponents;

use WPJobBoard\Classes\ArrayHelper;

if (!defined('ABSPATH')) {
    exit;
}

class HiddenInputComponent extends BaseComponent
{
    public function __construct()
    {
        parent::__construct('hidden_input', 19);
        add_filter('wpjobboard/validate_component_on_save_hidden_input', array($this, 'validateOnSave'), 1, 3);
    }

    public function component()
    {
        return array(
            'type'            => 'hidden_input',
            'editor_title'    => 'Hidden Input',
            'group'           => 'input',
            'postion_group'   => 'general',
            'editor_elements' => array(
                'label'         => array(
                    'label' => 'Admin Field Label',
                    'type'  => 'text',
                    'group' => 'general'
                ),
                'default_value' => array(
                    'label' => 'Input Value',
                    'type'  => 'text',
                    'group' => 'general'
                )
            ),
            'field_options'   => array(
                'label'         => 'Hidden Value',
                'required'      => 'no',
                'default_value' => ''
            )
        );
    }

    public function validateOnSave($error, $element, $formId)
    {
        if (!ArrayHelper::get($element, 'field_options.default_value')) {
            $error = __('Value is required for item:', 'wpjobboard') . ' ' . ArrayHelper::get($element, 'field_options.label');
        }
        return $error;
    }

    public function render($element, $form, $elements)
    {
        $fieldOptions = ArrayHelper::get($element, 'field_options', false);
        if (!$fieldOptions) {
            return;
        }
        $inputId = 'wpjb_input_' . $form->ID . '_' . $element['id'];
        $defaultValue = apply_filters('wpjobboard/input_default_value', ArrayHelper::get($fieldOptions, 'default_value'), $element, $form);

        $attributes = array(
            'name'  => $element['id'],
            'value' => $defaultValue,
            'type'  => 'hidden',
            'id'    => $inputId
        );
        ?>
        <input <?php echo $this->builtAttributes($attributes); ?> />
        <?php
    }
}