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
            'editor_title'    => __('Hidden input', 'ninja-job-board'),
            'group'           => 'input',
            'postion_group'   => 'general',
            'editor_elements' => array(
                'label'         => array(
                    'label' => __('Admin field label', 'ninja-job-board'),
                    'type'  => 'text',
                    'group' => 'general'
                ),
                'default_value' => array(
                    'label' => __('Input value', 'ninja-job-board'),
                    'type'  => 'text',
                    'group' => 'general'
                )
            ),
            'field_options'   => array(
                'label'         => __('Hidden value', 'ninja-job-board'),
                'required'      => 'no',
                'default_value' => ''
            )
        );
    }

    public function validateOnSave($error, $element, $formId)
    {
        if (!ArrayHelper::get($element, 'field_options.default_value')) {
            /* translators: %s: field label */
            $error = sprintf(__('%s needs a value.', 'ninja-job-board'), ArrayHelper::get($element, 'field_options.label'));
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
        <input <?php echo $this->builtAttributes($attributes); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in builtAttributes() ?> />
        <?php
    }
}
