<?php

namespace WPJobBoard\Classes\FormComponents;

use WPJobBoard\Classes\ArrayHelper;

if (!defined('ABSPATH')) {
    exit;
}

class StepComponent extends BaseComponent
{
    public function __construct()
    {
        parent::__construct('step_component', 21);
        add_action('wpjobboard/form_step_header', array($this, 'pushStepHeader'), 10, 2);
        add_action('wpjobboard/form_step_footer', array($this, 'pushStepFooter'), 10, 2);
    }

    public function component()
    {
        return array(
            'type'            => 'step_component',
            'editor_title'    => 'Form Step',
            'group'           => 'html',
            'postion_group'   => 'general',
            'editor_elements' => array(
                'step_title'  => array(
                    'label' => 'Step Title',
                    'type'  => 'text',
                    'group' => 'general',
                    'info'  => 'Provide a step title'
                ),
                'next_button' => array(
                    'label' => 'Next Step Button Text',
                    'type'  => 'text',
                    'group' => 'general',
                    'info'  => ''
                ),
                'back_button' => array(
                    'label' => 'Back Button Text',
                    'type'  => 'text',
                    'group' => 'general',
                    'info'  => ''
                )
            ),
            'field_options'   => array(
                'step_title'  => '',
                'next_button' => 'Next',
                'back_button' => 'Back'
            )
        );
    }


    public function pushStepHeader($steps, $form)
    {
        if (!$steps) {
            return;
        }
        ?>
        <!--Step start -->
        <div data-target_step_number="0" id="wpjb_step_0" class="wpjb_step_start">
        <?php
    }

    public function pushStepFooter($steps, $form)
    {
        if (!$steps) {
            return;
        }
        $lastStep = end($steps);
        ?>
        <?php if ($lastStep): ?>
        <?php
        $backBtnText = ArrayHelper::get($lastStep, 'field_options.back_button', 'Back');
        ?>
        <div class="wpjb_step_button_wrapper wpjb_last_step">
            <button data-target_step_number="<?php echo $lastStep['step_counter'] - 1; ?>"
                    class="wpjb_step_button wpjb_step_back"><?php echo $backBtnText; ?></button>
        </div>
    <?php endif; ?>
        </div><!--Step end -->
        <?php
    }

    public function render($element, $form, $elements)
    {
        $fieldOptions = ArrayHelper::get($element, 'field_options', []);
        $stepTitle = ArrayHelper::get($fieldOptions, 'step_title');
        $nextBtnText = ArrayHelper::get($fieldOptions, 'next_button', 'Next');
        ?>
        <div class="wpjb_step_button_wrapper">
            <?php if ($element['previous_step']): ?>
                <?php
                $backBtnText = ArrayHelper::get($element['previous_step'], 'field_options.back_button', 'Back');
                ?>
                <button data-target_step_number="<?php echo $element['step_counter'] - 2; ?>"
                        class="wpjb_step_button wpjb_step_back"><?php echo $backBtnText; ?></button>
            <?php endif; ?>
            <button data-step_number="<?php echo $element['step_counter']; ?>"
                    class="wpjb_step_button wpjb_step_next"><?php echo $nextBtnText; ?></button>
        </div>
        </div>
    <div data-target_step_number="<?php echo $element['step_counter']; ?>" class="wpjb_step_start"
         id="wpjb_step_<?php echo $element['step_counter']; ?>">
        <h3 class="wpjb_step_title"><?php echo $stepTitle; ?></h3>
        <?php
    }
}