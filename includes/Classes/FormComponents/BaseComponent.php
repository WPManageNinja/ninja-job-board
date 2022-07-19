<?php

namespace WPJobBoard\Classes\FormComponents;

use WPJobBoard\Classes\ArrayHelper;

if (!defined('ABSPATH')) {
    exit;
}

abstract class BaseComponent
{
    public $elementName = '';

    public function __construct($elementName, $priority = 10)
    {
        $this->elementName = $elementName;
        $this->registerHooks($elementName, $priority);
    }

    public function registerHooks($elementName, $priority = 10)
    {
        add_filter('wpjobboard/form_components', array($this, 'addComponent'), $priority);
        add_action('wpjobboard/render_component_' . $elementName, array($this, 'render'), 10, 3);
    }

    public function addComponent($components)
    {
        $component = $this->component();
        if ($component) {
            $components[$this->elementName] = $this->component();
        }
        return $components;
    }

    public function validateOnSave($error, $element, $formId)
    {
        return 'ok';
        return $error;
    }

    public function renderNormalInput($element, $form)
    {
        $fieldOptions = ArrayHelper::get($element, 'field_options', false);
        if (!$fieldOptions) {
            return;
        }
        $controlClass = $this->elementControlClass($element);
        $inputClass = $this->elementInputClass($element);
        $inputId = 'wpjb_input_' . $form->ID . '_' . $element['id'];

        $defaultValue = apply_filters('wpjobboard/input_default_value', ArrayHelper::get($fieldOptions, 'default_value'), $element, $form);

        $attributes = array(
            'data-required' => ArrayHelper::get($fieldOptions, 'required'),
            'name'          => $element['id'],
            'placeholder'   => ArrayHelper::get($fieldOptions, 'placeholder'),
            'value'         => $defaultValue,
            'type'          => ArrayHelper::get($element, 'type', 'text'),
            'class'         => $inputClass,
            'id'            => $inputId
        );

        if($attributes['type'] == 'number') {
            $attributes['step'] = 'any';
        }

        if (isset($fieldOptions['min_value'])) {
            $attributes['min'] = $fieldOptions['min_value'];
        }

        if (isset($fieldOptions['max_value'])) {
            $attributes['max'] = $fieldOptions['max_value'];
        }

        if (ArrayHelper::get($fieldOptions, 'required') == 'yes') {
            $attributes['required'] = true;
            $attributes['data-required'] = 'yes';
        }



        if($extraAtts = ArrayHelper::get($fieldOptions, 'extra_data_atts')) {
            if(is_array($extraAtts)) {
                $attributes = wp_parse_args($extraAtts, $attributes);
            }
        }

        ?>
        <div data-element_type="<?php echo esc_html($this->elementName); ?>"
             class="<?php echo esc_html($controlClass); ?>">
            <?php $this->buildLabel($fieldOptions, $form, array('for' => $inputId)); ?>
            <div class="wpjb_input_content">
                <input <?php echo wp_kses_post($this->builtAttributes($attributes)); ?> />
            </div>
        </div>
        <?php
    }

    public function renderSelectInput($element, $form)
    {
        $fieldOptions = ArrayHelper::get($element, 'field_options', false);
        if (!$fieldOptions) {
            return;
        }
        $controlClass = $this->elementControlClass($element);
        $inputClass = $this->elementInputClass($element);
        $inputId = 'wpjb_input_' . $form->ID . '_' . $element['id'];

        $defaultValue = apply_filters('wpjobboard/input_default_value', ArrayHelper::get($fieldOptions, 'default_value'), $element, $form);

        $options = ArrayHelper::get($fieldOptions, 'options', array());
        $placeholder = ArrayHelper::get($fieldOptions, 'placeholder');
        $inputAttributes = array(
            'data-required' => ArrayHelper::get($fieldOptions, 'required'),
            'name'          => $element['id'],
            'class'         => $inputClass,
            'id'            => $inputId
        );
        if (ArrayHelper::get($fieldOptions, 'required') == 'yes') {
            $inputAttributes['required'] = true;
        }
        $controlAttributes = array(
            'id'                => 'wpjb_' . $this->elementName,
            'data-element_type' => $this->elementName,
            'class'             => $controlClass
        );
        ?>
        <div <?php echo wp_kses_post($this->builtAttributes($controlAttributes)); ?>>
            <?php $this->buildLabel($fieldOptions, $form, array('for' => $inputId)); ?>
            <div class="wpjb_input_content">
                <select <?php echo wp_kses_post($this->builtAttributes($inputAttributes)); ?>>
                    <?php if ($placeholder): ?>
                        <option data-type="placeholder" value=""><?php echo esc_html($placeholder); ?></option>
                    <?php endif; ?>
                    <?php foreach ($options as $option): ?>
                        <?php
                        $optionAttributes = array(
                            'value' => $option['value']
                        );
                        if ($defaultValue == $option['value']) {
                            $optionAttributes['selected'] = 'true';
                        }
                        ?>
                        <option <?php echo wp_kses_post($this->builtAttributes($optionAttributes)); ?>><?php echo esc_html($option['label']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <?php
    }

    public function renderRadioInput($element, $form)
    {
        $fieldOptions = ArrayHelper::get($element, 'field_options', false);
        if (!$fieldOptions) {
            return;
        }

        $controlClass = $this->elementControlClass($element);
        $inputClass = $this->elementInputClass($element);
        $inputId = 'wpjb_input_' . $form->ID . '_' . $element['id'];

        $defaultValue = apply_filters('wpjobboard/input_default_value', ArrayHelper::get($fieldOptions, 'default_value'), $element, $form);

        $options = ArrayHelper::get($fieldOptions, 'options', array());

        $controlAttributes = array(
            'data-element_type' => $this->elementName,
            'class'             => $controlClass
        );
        ?>
        <div <?php wpJobBoardPrintInternal($this->builtAttributes($controlAttributes)); ?>>
            <?php $this->buildLabel($fieldOptions, $form, array('for' => $inputId)); ?>
            <div class="wpjb_multi_form_controls wpjb_input_content">
                <?php foreach ($options as $index => $option): ?>
                    <?php
                    $optionId = $element['id'] . '_' . $index . '_' . $form->ID;
                    $attributes = array(
                        'class' => 'form-check-input',
                        'type'  => 'radio',
                        'name'  => $element['id'],
                        'id'    => $optionId,
                        'value' => $option['value']
                    );
                    if ($option['value'] == $defaultValue) {
                        $attributes['checked'] = 'true';
                    }
                    if (ArrayHelper::get($fieldOptions, 'required') == 'yes') {
                        $attributes['required'] = true;
                    }
                    ?>
                    <div class="form-check">
                        <input <?php wpJobBoardPrintInternal($this->builtAttributes($attributes)); ?>>
                        <label class="form-check-label" for="<?php echo esc_html($optionId); ?>">
                            <?php echo esc_html($option['label']); ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    public function renderCheckBoxInput($element, $form)
    {
        $fieldOptions = ArrayHelper::get($element, 'field_options', false);
        if (!$fieldOptions) {
            return;
        }
        $controlClass = $this->elementControlClass($element);
        $inputClass = $this->elementInputClass($element);
        $inputId = 'wpjb_input_' . $form->ID . '_' . $element['id'];
        $defaultValue = ArrayHelper::get($fieldOptions, 'default_value');
        $defaultValues = explode(',', $defaultValue);

        $defaultValues = apply_filters('wpjobboard/input_default_value', $defaultValues, $element, $form);

        $options = ArrayHelper::get($fieldOptions, 'options', array());

        $controlAttributes = array(
            'data-element_type' => $this->elementName,
            'class'             => $controlClass
        );
        if (ArrayHelper::get($fieldOptions, 'required') == 'yes') {
            $controlAttributes['data-checkbox_required'] = 'yes';
        }
        ?>
        <div <?php wpJobBoardPrintInternal($this->builtAttributes($controlAttributes)); ?>>
            <?php $this->buildLabel($fieldOptions, $form, array('for' => $inputId)); ?>
            <div class="wpjb_multi_form_controls wpjb_input_content">
                <?php foreach ($options as $index => $option): ?>
                    <?php
                    $optionId = $element['id'] . '_' . $index . '_' . $form->ID;
                    $attributes = array(
                        'class' => 'form-check-input',
                        'type'  => 'checkbox',
                        'name'  => $element['id'] . '[]',
                        'id'    => $optionId,
                        'value' => ArrayHelper::get($option, 'value')
                    );
                    if (in_array(ArrayHelper::get($option, 'value'), $defaultValues)) {
                        $attributes['checked'] = 'true';
                    }
                    ?>
                    <div class="form-check">
                        <input <?php wpJobBoardPrintInternal($this->builtAttributes($attributes)); ?>>
                        <label class="form-check-label" for="<?php echo esc_html($optionId); ?>">
                            <?php echo esc_html($option['label']); ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    public function renderHtmlContent($element, $form)
    {
        ?>
        <div class="wpjb_html_content_wrapper">
            <?php
            $text = ArrayHelper::get($element, 'field_options.custom_html');
            wpJobBoardPrintInternal($this->parseText($text, $form->ID));
            ?>
        </div>
        <?php
    }

    public function builtAttributes($attributes)
    {
        $atts = ' ';
        foreach ($attributes as $attributeKey => $attribute) {
            if (is_array($attribute)) {
                $attribute = json_encode($attribute);
            }
            $atts .= $attributeKey . "='" . htmlspecialchars($attribute, ENT_QUOTES) . "' ";
        }
        return $atts;
    }

    public function elementControlClass($element)
    {
        return apply_filters('wppayfrom/element_control_class', 'wpjb_form_group wpjb_item_' . $element['type'], $element);
    }

    public function elementInputClass($element)
    {
        $extraClasses = '';
        if (isset($element['extra_input_class'])) {
            $extraClasses = ' ' . $element['extra_input_class'];
        }
        return apply_filters('wppayfrom/element_input_class', 'wpjb_form_control' . $extraClasses, $element);
    }

    public function parseText($text, $formId)
    {
        return $text;
    }

    public function buildLabel($fieldOptions, $form, $attributes = array())
    {
        $label = ArrayHelper::get($fieldOptions, 'label');
        $required = ArrayHelper::get($fieldOptions, 'required') == 'yes';
        $xtra_left = '';
        $xtra_right = '';
        $astPosition = $form->asteriskPosition;
        if($required) {
            if ($astPosition == 'left') {
                $xtra_left = '<span class="wpjb_required_sign wpjb_required_sign_left">*</span> ';
            } else if ($astPosition == 'right') {
                $xtra_right = ' <span class="wpjb_required_sign wpjb_required_sign_left">*</span>';
            }
        }
        if ($label): ?>
            <div class="wpjb_input_label">
                <label <?php wpJobBoardPrintInternal($this->builtAttributes($attributes)); ?>><?php wpJobBoardPrintInternal($xtra_left . $label . $xtra_right); ?></label>
            </div>
        <?php endif;
    }

    abstract function component();

    abstract function render($element, $form, $elements);

}
