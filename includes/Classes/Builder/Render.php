<?php

namespace WPJobBoard\Classes\Builder;

use WPJobBoard\Classes\ArrayHelper;
use WPJobBoard\Classes\GeneralSettings;
use WPJobBoard\Classes\Models\Forms;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ajax Handler Class
 * @since 1.0.0
 */
class Render
{
    public function render($formId, $show_title = false)
    {
        $form = Forms::getForm($formId);
        if (!$form) {
            return;
        }
        if ($show_title) {
            $form->show_title = $show_title;
        }
        $form->show_description = false;
        $form->scheduleing_settings = Forms::getSchedulingSettings($formId);

        $elements = Forms::getBuilderSettings($formId);
        $form->designSettings = Forms::getDesignSettings($formId);

        $form->asteriskPosition = $form->designSettings['asteriskPlacement'];

        if (!$elements) {
            return '';
        }
        $this->addAssets($form);
        $steps = [];
        $step_counter = 0;
        $previousStep = false;
        ob_start();
        if ($elements):
            foreach ($elements as $element) {
                if ($element['type'] == 'step_component') {
                    $step_counter += 1;
                    $element['step_counter'] = $step_counter;
                    $element['previous_step'] = $previousStep;
                    $steps[] = $element;
                }
                do_action('wpjobboard/render_component_' . $element['type'], $element, $form, $elements);
                if ($element['type'] == 'step_component') {
                    $previousStep = $element;
                }
            }
            $form_body = ob_get_clean();
        endif;
        ob_start();
        $this->renderFormHeader($form, $steps);
        $header_html = ob_get_clean();
        ob_start();
        $this->renderFormFooter($form, $steps);
        $formFooter = ob_get_clean();
        $html = $header_html . $form_body . $formFooter;

        return apply_filters('wpjobboard/rendered_form_html', $html, $form);
    }

    public function renderFormHeader($form, $steps = [])
    {
        global $wp;
        $currentUrl = home_url(add_query_arg($_GET, $wp->request));;
        $labelPlacement = $form->designSettings['labelPlacement'];
        $btnPosition = ArrayHelper::get($form->designSettings, 'submit_button_position');

        $extraCssClasses = array_keys(array_filter($form->designSettings['extra_styles'], function ($value) {
            return $value == 'yes';
        }));

        $css_classes = array(
            'wpjb_form',
            'wpjb_strip_default_style',
            'wpjb_form_id_' . $form->ID,
            'wpjb_label_' . $labelPlacement,
            'wpjb_asterisk_' . $form->asteriskPosition,
            'wpjb_submit_button_pos_' . $btnPosition
        );

        if ($steps) {
            $css_classes[] = 'wpjb_has_steps';
        }
        $css_classes = array_merge($css_classes, $extraCssClasses);

        if ($labelPlacement != 'top') {
            $css_classes[] = 'wpjb_inline_labels';
        }

        $css_classes = apply_filters('wpjobboard/form_css_classes', $css_classes, $form);

        $formAttributes = array(
            'data-wpjb_form_id' => $form->ID,
            'class'             => implode(' ', $css_classes),
            'method'            => 'POST',
            'action'            => site_url(),
            'id'                => "wpjb_form_id_" . $form->ID
        );
        $formAttributes = apply_filters('wpjobboard/form_attributes', $formAttributes, $form);

        $formWrapperClasses = apply_filters('wpjobboard/form_wrapper_css_classes', array(
            'wpjb_form_wrapper',
            'wpjb_form_wrapper_' . $form->ID
        ), $form);

        $wrapperClassStr = implode(' ', $formWrapperClasses);

        ?>
        <div class="<?php echo esc_html($wrapperClassStr); ?>">
        <?php if ($form->show_title == 'yes'): ?>
        <h3 class="wp_form_title"><?php echo esc_html($form->post_title); ?></h3>
    <?php endif; ?>
        <?php do_action('wpjobboard/form_render_before', $form); ?>
        <form <?php wpJobBoardPrintInternal($this->builtAttributes($formAttributes)); ?>>
        <input type="hidden" name="__wpjb_form_id" value="<?php echo intval($form->ID); ?>"/>
        <input type="hidden" name="__wpjb_current_url" value="<?php echo esc_url($currentUrl); ?>">
        <input type="hidden" name="__wpjb_current_page_id" value="<?php echo get_the_ID(); ?>">
        <?php do_action('wpjobboard/form_render_start_form', $form); ?>
        <?php if ($steps): ?>
        <?php do_action('wpjobboard/form_step_header', $steps, $form); ?>
    <?php endif; ?>

        <?php
    }

    public function renderFormFooter($form, $steps = [])
    {
        $submitButton = Forms::getButtonSettings($form->ID);
        $processingText = $submitButton['processing_text'];
        if (!$processingText) {
            $processingText = __('Please Wait…', 'ninja-job-board');
        }
        $button_text = $submitButton['button_text'];
        if (!$button_text) {
            $button_text = __('Apply', 'ninja-job-board');
        }
        $buttonClasses = array(
            'wpjb_submit_button',
            $submitButton['css_class'],
            $submitButton['button_style']
        );
        $buttonAttributes = apply_filters('wpjobboard/submit_button_attributes', array(
            'id'    => 'wpjb_form_submit_' . $form->ID,
            'class' => implode(' ', array_unique($buttonClasses))
        ), $form);
        ?>
        <?php do_action('wpjobboard/form_render_before_submit_button', $form); ?>
        <div class="wpjb_form_group wpjb_form_submissions">
            <button <?php echo wp_kses_post($this->builtAttributes($buttonAttributes)); ?>>
                <span class="wpjb_txt_normal"><?php wpJobBoardPrintInternal($this->parseText($button_text, $form->ID)); ?></span>
                <span style="display: none;" class="wpjb_txt_loading">
                    <?php  wpJobBoardPrintInternal($this->parseText($processingText, $form->ID)); ?>
                </span>
            </button>
            <div class="wpjb_loading_svg">
                <svg version="1.1" id="loader-1" xmlns="http://www.w3.org/2000/svg"
                     xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="30px" height="30px"
                     viewBox="0 0 40 40" enable-background="new 0 0 40 40" xml:space="preserve"><path opacity="0.2"
                                                                                                      fill="#000"
                                                                                                      d="M20.201,5.169c-8.254,0-14.946,6.692-14.946,14.946c0,8.255,6.692,14.946,14.946,14.946 s14.946-6.691,14.946-14.946C35.146,11.861,28.455,5.169,20.201,5.169z M20.201,31.749c-6.425,0-11.634-5.208-11.634-11.634 c0-6.425,5.209-11.634,11.634-11.634c6.425,0,11.633,5.209,11.633,11.634C31.834,26.541,26.626,31.749,20.201,31.749z"/>
                    <path fill="#000"
                          d="M26.013,10.047l1.654-2.866c-2.198-1.272-4.743-2.012-7.466-2.012h0v3.312h0 C22.32,8.481,24.301,9.057,26.013,10.047z">
                        <animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 20 20"
                                          to="360 20 20" dur="0.5s" repeatCount="indefinite"/>
                    </path></svg>
            </div>
        </div>
        <?php do_action('wpjobboard/form_render_after_submit_button', $form); ?>

        <?php if ($steps): ?>
        <?php do_action('wpjobboard/form_step_footer', $steps, $form); ?>
    <?php endif; ?>

        </form>
        <div style="display: none" class="wpjb_form_notices wpjb_form_errors"></div>
        <div style="display: none" class="wpjb_form_notices wpjb_form_success"></div>
        <?php do_action('wpjobboard/form_render_after', $form); ?>
        <?php do_action('wpjobboard/form_render_after_' . $form->ID, $form); ?>
        </div>
        <?php
    }

    private function addAssets($form)
    {
        do_action('wpjobboard/wpjobboard_adding_assets', $form);

        wp_register_script('flatpickr', WPJOBBOARD_URL . 'assets/libs/flatpickr/flatpickr.min.js', array(), '4.5.7', true);
        wp_register_style('flatpickr', WPJOBBOARD_URL . 'assets/libs/flatpickr/flatpickr.min.css', array(), '4.5.7', 'all');

        wp_enqueue_script('wpjobboard_public', WPJOBBOARD_URL . 'assets/js/jobboard-public.js', array('jquery'), WPJOBBOARD_VERSION, true);
        wp_enqueue_style('wpjobboard_public', WPJOBBOARD_URL . 'assets/css/wp_job_board-public.css', array(), WPJOBBOARD_VERSION);

        wp_localize_script('wpjobboard_public', 'wp_job_board_' . $form->ID, apply_filters('wpjobboard/checkout_vars', array(
            'form_id' => $form->ID
        ), $form));

        wp_register_script('dropzone', WPJOBBOARD_URL . 'assets/libs/dropzone/dropzone.min.js', array('jquery'), '5.5.0', true);
        wp_register_script('wpjobboard_file_upload', WPJOBBOARD_URL . 'assets/js/fileupload.js', array('jquery', 'wpjobboard_public', 'dropzone'), WPJOBBOARD_VERSION, true);

        wp_localize_script('wpjobboard_public', 'wp_job_board_general', array(
            'ajax_url'  => admin_url('admin-ajax.php'),
            'date_i18n' => array(
                'previousMonth'    => __('Previous Month', 'ninja-job-board'),
                'nextMonth'        => __('Next Month', 'ninja-job-board'),
                'months'           => [
                    'sorthand' => [
                        __('Jan', 'ninja-job-board'),
                        __('Feb', 'ninja-job-board'),
                        __('Mar', 'ninja-job-board'),
                        __('Apr', 'ninja-job-board'),
                        __('May', 'ninja-job-board'),
                        __('Jun', 'ninja-job-board'),
                        __('Jul', 'ninja-job-board'),
                        __('Aug', 'ninja-job-board'),
                        __('Sep', 'ninja-job-board'),
                        __('Oct', 'ninja-job-board'),
                        __('Nov', 'ninja-job-board'),
                        __('Dec', 'ninja-job-board')
                    ],
                    'longhand' => [
                        __('January', 'ninja-job-board'),
                        __('February', 'ninja-job-board'),
                        __('March', 'ninja-job-board'),
                        __('April', 'ninja-job-board'),
                        __('May', 'ninja-job-board'),
                        __('June', 'ninja-job-board'),
                        __('July', 'ninja-job-board'),
                        __('August', 'ninja-job-board'),
                        __('September', 'ninja-job-board'),
                        __('October', 'ninja-job-board'),
                        __('November', 'ninja-job-board'),
                        __('December', 'ninja-job-board')
                    ]
                ],
                'weekdays'         => [
                    'longhand'  => array(
                        __('Sunday', 'ninja-job-board'),
                        __('Monday', 'ninja-job-board'),
                        __('Tuesday', 'ninja-job-board'),
                        __('Wednesday', 'ninja-job-board'),
                        __('Thursday', 'ninja-job-board'),
                        __('Friday', 'ninja-job-board'),
                        __('Saturday', 'ninja-job-board')
                    ),
                    'shorthand' => array(
                        __('Sun', 'ninja-job-board'),
                        __('Mon', 'ninja-job-board'),
                        __('Tue', 'ninja-job-board'),
                        __('Wed', 'ninja-job-board'),
                        __('Thu', 'ninja-job-board'),
                        __('Fri', 'ninja-job-board'),
                        __('Sat', 'ninja-job-board')
                    )
                ],
                'daysInMonth'      => [
                    31,
                    28,
                    31,
                    30,
                    31,
                    30,
                    31,
                    31,
                    30,
                    31,
                    30,
                    31
                ],
                'rangeSeparator'   => __(' to ', 'ninja-job-board'),
                'weekAbbreviation' => __('Wk', 'ninja-job-board'),
                'scrollTitle'      => __('Scroll to increment', 'ninja-job-board'),
                'toggleTitle'      => __('Click to toggle', 'ninja-job-board'),
                'amPM'             => [
                    __('AM', 'ninja-job-board'),
                    __('PM', 'ninja-job-board')
                ],
                'yearAriaLabel'    => __('Year', 'ninja-job-board')
            )
        ));
    }

    private function parseText($text, $formId)
    {
        return $text;
    }

    private function builtAttributes($attributes)
    {
        $atts = ' ';
        foreach ($attributes as $attributeKey => $attribute) {
            $atts .= $attributeKey . "='" . $attribute . "' ";
        }
        return $atts;
    }
}
