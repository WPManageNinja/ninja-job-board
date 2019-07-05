<?php

namespace WPJobBoard\Classes\Entry;


use WPJobBoard\Classes\ArrayHelper;
use WPJobBoard\Classes\Models\Forms;
use WPJobBoard\Classes\Models\OrderItem;
use WPJobBoard\Classes\Models\Subscription;
use WPJobBoard\Classes\View;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Entry Methods
 * @since 1.0.0
 */
class Entry
{
    protected $formId;
    protected $submissionId;
    protected $submission;
    protected $formattedInput;
    protected $rawInput;
    protected $formattedFields;
    protected $instance;
    public $default = false;

    public function __construct($submission)
    {
        $this->formId = $submission->form_id;
        $this->submissionId = $submission->id;
        $this->submission = $submission;
        $this->formattedInput = $submission->form_data_formatted;
        $this->rawInput = $submission->form_data_raw;
        $this->instance = $this;
    }

    public function getRawInput($key, $default = false)
    {
        if (isset($this->rawInput[$key])) {
            return $this->rawInput[$key];
        }
        return $default;
    }

    public function getInput($key, $default = false)
    {
        $value = $default;
        if (isset($this->formattedInput[$key])) {
            $value = $this->formattedInput[$key];
        }
        if (is_array($value)) {
            $value = $this->maybeNeedToConverHtml($value, $key);
        }
        return $value;
    }

    public function getInputFieldsHtmlTable()
    {
        // We have to make the items as label and value pair first
        $inputItems = $this->formattedInput;
        $labels = (array)Forms::getFormInputLabels($this->formId);
        $items = array();
        foreach ($inputItems as $itemKey => $item) {
            $label = $itemKey;
            if (!empty($labels[$itemKey])) {
                $label = $labels[$itemKey];
            }

            if (is_array($item)) {
                $item = $this->maybeNeedToConverHtml($item, $itemKey);
                if (is_array($item)) {
                    $item = implode(', ', $item);
                }
            }

            $items[] = array(
                'label' => $label,
                'value' => $item
            );
        }

        return View::make('elements.input_fields_html', array(
            'items' => $items,
            'load_css' => true
        ));
    }

    public function __get($name)
    {
        if ($name == 'all_input_field_html') {
            return $this->getInputFieldsHtmlTable();
        }

        if ($name == 'product_items_table_html') {
            return $this->getOrderItemsHtml();
        }

        if ($name == 'subscription_details_table_html') {
            return $this->getSubscriptionsHtml();
        }

        if (property_exists($this->submission, $name)) {
            return $this->submission->{$name};
        }

        return $this->default;
    }

    public function getSubmission()
    {
        return $this->submission;
    }

    protected function maybeNeedToConverHtml($value, $key)
    {
        $formattedInputs = $this->getFormattedInputs();
        $element = ArrayHelper::get($formattedInputs, 'input.' . $key);
        if ($element) {
            $value = apply_filters('wpjobboard/maybe_conver_html_' . $element['type'], $value, $this->submission, $element);
        }
        return $value;
    }

    public function getFormattedInputs()
    {
        if (!$this->formattedFields) {
            $this->formattedFields = Forms::getFormattedElements($this->formId);
        }
        return $this->formattedFields;
    }
}