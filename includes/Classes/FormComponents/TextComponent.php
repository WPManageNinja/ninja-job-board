<?php
namespace WPJobBoard\Classes\FormComponents;

if (!defined('ABSPATH')) {
    exit;
}

class TextComponent extends BaseComponent
{
    public function __construct()
    {
        parent::__construct('text', 13);
    }

    public function component()
    {
        return array(
            'type'            => 'text',
            'editor_title'    => 'Single Line Text',
            'group'           => 'input',
            'postion_group'   => 'general',
            'editor_elements' => array(
                'label'         => array(
                    'label' => 'Field Label',
                    'type'  => 'text',
                    'group' => 'general'
                ),
                'placeholder'    => array(
                    'label' => 'Placeholder',
                    'type'  => 'text',
                    'group' => 'general'
                ),
                'required'      => array(
                    'label' => 'Required',
                    'type'  => 'switch',
                    'group' => 'general'
                ),
                'default_value' => array(
                    'label' => 'Default Value',
                    'type'  => 'text',
                    'group' => 'general'
                )
            ),
            'field_options'   => array(
                'label' => 'Single Line Text',
                'placeholder' => '',
                'required' => 'no'
            )
        );
    }

    public function render($element, $form, $elements)
    {
        $element['type'] = 'text';
        $this->renderNormalInput($element, $form);
    }
}