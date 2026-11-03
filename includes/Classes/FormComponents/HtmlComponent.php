<?php

namespace WPJobBoard\Classes\FormComponents;

if (!defined('ABSPATH')) {
    exit;
}

class HtmlComponent extends BaseComponent
{
    public function __construct()
    {
        parent::__construct('custom_html', 20);
    }

    public function component()
    {
        return array(
            'type'            => 'custom_html',
            'editor_title'    => __('HTML markup', 'ninja-job-board'),
            'group'           => 'html',
            'postion_group'   => 'general',
            'editor_elements' => array(
                'custom_html' => array(
                    'label' => __('Custom HTML', 'ninja-job-board'),
                    'type' => 'html',
                    'group' => 'general',
                    'info' => __('You can use any valid HTML here.', 'ninja-job-board')
                )
            )
        );
    }

    public function render($element, $form, $elements)
    {
        $this->renderHtmlContent($element, $form);
    }
}