<?php

namespace WPJobBoard\Classes\FormComponents;

use WPJobBoard\Classes\ArrayHelper;

if (!defined('ABSPATH')) {
    exit;
}

class DateComponent extends BaseComponent
{
    public function __construct()
    {
        parent::__construct('date', 19);
    }

    public function component()
    {
        $example = function ($format, $sample) {
            /* translators: 1: date format code, 2: sample date in that format */
            return sprintf(__('%1$s (example: %2$s)', 'ninja-job-board'), $format, $sample);
        };
        $timeExample = function ($format, $sample) {
            /* translators: 1: time format code, 2: sample time in that format */
            return sprintf(__('%1$s (time only, example: %2$s)', 'ninja-job-board'), $format, $sample);
        };

        $dateFormats = apply_filters('wpjobboard/available_date_formats', array(
            'm/d/Y'       => $example('m/d/Y', '04/28/2018'), // USA
            'd/m/Y'       => $example('d/m/Y', '28/04/2018'), // Canada, UK
            'd.m.Y'       => $example('d.m.Y', '28.04.2019'), // Germany
            'n/j/y'       => $example('n/j/y', '4/28/18'),
            'm/d/y'       => $example('m/d/y', '04/28/18'),
            'M/d/Y'       => $example('M/d/Y', 'Apr/28/2018'),
            'y/m/d'       => $example('y/m/d', '18/04/28'),
            'Y-m-d'       => $example('Y-m-d', '2018-04-28'),
            'd-M-y'      => $example('d-M-y', '28-Apr-18'),
            'm/d/Y h:i K' => $example('m/d/Y h:i K', '04/28/2018 08:55 PM'), // USA
            'm/d/Y H:i'   => $example('m/d/Y H:i', '04/28/2018 20:55'), // USA
            'd/m/Y h:i K' => $example('d/m/Y h:i K', '28/04/2018 08:55 PM'), // Canada, UK
            'd/m/Y H:i'   => $example('d/m/Y H:i', '28/04/2018 20:55'), // Canada, UK
            'd.m.Y h:i K' => $example('d.m.Y h:i K', '28.04.2019 08:55 PM'), // Germany
            'd.m.Y H:i'   => $example('d.m.Y H:i', '28.04.2019 20:55'), // Germany
            'h:i K'       => $timeExample('h:i K', '08:55 PM'),
            'H:i'         => $timeExample('H:i', '20:55'),
        ));

        return array(
            'type'            => 'date',
            'editor_title'    => __('Date', 'ninja-job-board'),
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
                'date_format'   => array(
                    'label'     => __('Date format', 'ninja-job-board'),
                    'type'      => 'select_option',
                    'options'   => $dateFormats,
                    'group'     => 'general',
                    'creatable' => 'yes'
                ),
                'default_value' => array(
                    'label' => __('Default value', 'ninja-job-board'),
                    'type'  => 'text',
                    'group' => 'general'
                )
            ),
            'field_options'   => array(
                'label'       => __('Date', 'ninja-job-board'),
                'placeholder' => '',
                'required'    => 'no',
                'date_format' => 'm/d/Y'
            )
        );
    }

    public function render($element, $form, $elements)
    {
        wp_enqueue_script('flatpickr');
        wp_enqueue_style('flatpickr');

        $fieldOptions = ArrayHelper::get($element, 'field_options', false);
        if (!$fieldOptions) {
            return;
        }
        $defaultValue = apply_filters('wpjobboard/input_default_value', ArrayHelper::get($fieldOptions, 'default_value'), $element, $form);

        $controlClass = $this->elementControlClass($element);
        $inputClass = $this->elementInputClass($element);
        $inputId = 'wpjb_input_' . $form->ID . '_' . $element['id'];
        $attributes = array(
            'data-required'    => ArrayHelper::get($fieldOptions, 'required'),
            'name'             => $element['id'],
            'placeholder'      => ArrayHelper::get($fieldOptions, 'placeholder'),
            'value'            => $defaultValue,
            'type'             => 'text',
            'id'               => $inputId,
            'class'            => $inputClass . ' wpjb_date_field',
            'data-date_config' => $this->getDateFormatConfigJSON($fieldOptions, $form),
        );


        if (ArrayHelper::get($fieldOptions, 'required') == 'yes') {
            $attributes['required'] = true;
        }
        ?>
        <div id="wpjb_<?php echo esc_attr($this->elementName); ?>" data-element_type="<?php echo esc_attr($this->elementName); ?>"
             class="<?php echo esc_attr($controlClass); ?>">
            <?php $this->buildLabel($fieldOptions, $form, array('for' => $inputId)); ?>
            <div class="wpjb_input_content">
                <input <?php echo $this->builtAttributes($attributes); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in builtAttributes() ?> />
            </div>
        </div>
        <?php
    }

    private function getDateFormatConfigJSON($fieldOptions, $form)
    {
        // We are converting moment.js format to flatpickr format
        $fieldOptions['date_format'] = $this->convertDateFormat($fieldOptions['date_format']);
        $dateFormat = ArrayHelper::get($fieldOptions, 'date_format');

        if(!$dateFormat) {
            $dateFormat = 'm/d/Y';
        }

        $config = apply_filters('wpjobboard/frontend_date_format', array(
            'dateFormat' => $dateFormat,
            'enableTime' => $this->hasTime($dateFormat),
            'noCalendar' => !$this->hasDate($dateFormat),
        ), $fieldOptions, $form);

        return json_encode($config);
    }

    private function hasTime($string)
    {
        $timeStrings = ['H', 'h', 'G', 'i', 'S', 's', 'K'];
        foreach ($timeStrings as $timeString) {
            if (strpos($string, $timeString) != false) {
                return true;
            }
        }
        return false;
    }

    private function hasDate($string)
    {
        $dateStrings = ['d', 'D', 'l', 'j', 'J', 'w', 'W', 'F', 'm', 'n', 'M', 'U', 'Y', 'y', 'Z'];
        foreach ($dateStrings as $dateString) {
            if (strpos($string, $dateString) != false) {
                return true;
            }
        }
        return false;
    }

    private function convertDateFormat($dateFormat)
    {
        $oldFormats = [
            'M/D/YYYY'    => 'n/j/Y',
            'M/D/YY'      => 'n/j/YY',
            'MM/DD/YY'    => 'm/d/y',
            'MM/DD/YYYY'  => 'MM/DD/Y',
            'MMM/DD/YYYY' => 'MMM/DD/Y',
            'YY/MM/DD'    => 'y/m/d',
            'YYYY-MM-DD'  => 'Y-m-d',
            'DD-MMM-YY'   => 'd-M-y',
        ];

        $dateFormat = str_replace(array_keys($oldFormats), array_values($oldFormats), $dateFormat);

        return $dateFormat;
    }
}
