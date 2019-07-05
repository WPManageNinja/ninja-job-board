<?php

namespace WPJobBoard\Classes;

use WPJobBoard\Classes\Models\Forms;
use WPJobBoard\Classes\Tools\GlobalTools;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ajax Handler Class
 * @since 1.0.0
 */
class AdminAjaxHandler
{
    public function registerEndpoints()
    {
        add_action('wp_ajax_wpjobboard_forms_admin_ajax', array($this, 'handeEndPoint'));
    }

    public function handeEndPoint()
    {
        $route = sanitize_text_field($_REQUEST['route']);

        $validRoutes = array(
            'get_forms'                  => 'getForms',
            'create_form'                => 'createForm',
            'update_form'                => 'updateForm',
            'get_form'                   => 'getForm',
            'save_form_settings'         => 'saveFormSettings',
            'save_form_builder_settings' => 'saveFormBuilderSettings',
            'get_custom_form_settings'   => 'getFormBuilderSettings',
            'delete_form'                => 'deleteForm',
            'get_form_settings'          => 'getFormSettings',
            'get_design_settings'        => 'getDesignSettings',
            'update_design_settings'     => 'updateDesignSettings',
            'duplicate_form'             => 'duplicateForm',
            'restore_form'               => 'restoreForm'
        );

        if (WPJOBBOARD_DB_VERSION > intval(get_option('WPJB_DB_VERSION'))) {
            $activator = new Activator();
            $activator->maybeUpgradeDB();
        }

        if (isset($validRoutes[$route])) {
            AccessControl::checkAndPresponseError($route, 'forms');
            do_action('wpjobboard/doing_ajax_forms_' . $route);
            return $this->{$validRoutes[$route]}();
        }
        do_action('wpjobboard/admin_ajax_handler_catch', $route);
    }

    protected function getForms()
    {
        $perPage = absint($_REQUEST['per_page']);
        $pageNumber = absint($_REQUEST['page_number']);
        $searchString = sanitize_text_field($_REQUEST['search_string']);
        $args = array(
            'posts_per_page' => $perPage,
            'offset'         => $perPage * ($pageNumber - 1)
        );

        $args = apply_filters('wpjobboard/get_all_forms_args', $args);

        if ($searchString) {
            $args['s'] = $searchString;
        }
        $forms = Forms::getForms($args, $with = array('entries_count'));

        wp_send_json_success($forms);
    }

    protected function createForm()
    {
        $postTitle = sanitize_text_field($_REQUEST['post_title']);
        if (!$postTitle) {
            wp_send_json_error(array(
                'message' => __('Please Provide a job title', 'wpjobboard')
            ), 423);
            return;
        }

        $data = array(
            'post_title'  => $postTitle,
            'post_status' => 'draft'
        );

        do_action('wpjobboard/before_create_form', $data);

        $formId = Forms::create($data);

        wp_update_post([
            'ID'         => $formId,
            'post_title' => $data['post_title']
        ]);

        if (is_wp_error($formId)) {
            wp_send_json_error(array(
                'message' => __('Something is wrong when createding the form. Please try again', 'wpjobboard')
            ), 423);
            return;
        }

        do_action('wpjobboard/after_create_form', $formId, $data);

        $this->createDemoApplicationForm($formId);

        wp_send_json_success(array(
            'message'      => __('Please wait, You are redirecting to the next page', 'wpjobboard'),
            'form_id'      => $formId,
            'redirect_url' => get_edit_post_link($formId)
        ), 200);
    }

    protected function updateForm()
    {
        // validate first
        $formId = intval($_REQUEST['form_id']);
        $title = sanitize_text_field($_REQUEST['post_title']);
        if (!$formId || !$title) {
            wp_send_json_error(array(
                'message' => __('Please provide form title', 'wpjobboard')
            ), 423);
        }

        $formData = array(
            'post_title'   => $title,
            'post_content' => wp_kses_post($_REQUEST['post_content'])
        );

        do_action('wpjobboard/before_update_form', $formId, $formData);
        Forms::update($formId, $formData);
        do_action('wpjobboard/after_update_form', $formId, $formData);

        update_post_meta($formId, 'wpjobboard_show_title_description', sanitize_text_field($_REQUEST['show_title_description']));
        wp_send_json_success(array(
            'message' => __('Form successfully updated', 'wpjobboard')
        ), 200);
    }

    protected function getForm()
    {
        $formId = absint($_REQUEST['form_id']);
        $form = Forms::getForm($formId);
        $form->edit_url = admin_url('post.php?post=' . $form->ID . '&action=edit');
        wp_send_json_success(array(
            'form' => $form
        ), 200);
    }

    protected function getFormSettings()
    {
        $allPages = wpJobBoardDB()->table('posts')
            ->select(array('ID', 'post_title'))
            ->where('post_type', 'page')
            ->where('post_status', 'publish')
            ->get();

        $formId = absint($_REQUEST['form_id']);

        wp_send_json_success(array(
            'confirmation_settings' => Forms::getConfirmationSettings($formId),
            'editor_shortcodes'     => FormPlaceholders::getAllPlaceholders($formId),
            'pages'                 => $allPages
        ), 200);
    }

    protected function saveFormSettings()
    {
        $formId = absint($_REQUEST['form_id']);
        if (isset($_REQUEST['confirmation_settings'])) {
            $confirmationSettings = wp_unslash($_REQUEST['confirmation_settings']);
            update_post_meta($formId, 'jobboard_confirmation_settings', $confirmationSettings);
        }

        wp_send_json_success(array(
            'message' => __('Settings successfully updated', 'wpjobboard')
        ), 200);
    }

    protected function saveFormBuilderSettings()
    {
        $formId = absint($_REQUEST['form_id']);
        $builderSettings = wp_unslash($_REQUEST['builder_settings']);
        if (!$formId || !$builderSettings) {
            wp_send_json_error(array(
                'message' => __('Validation Error, Please try again', 'wpjobboard'),
                'errors'  => array(
                    'general' => __('Please add atleast one input element', 'wpjobboard')
                )
            ), 423);
        }
        $errors = array();

        foreach ($builderSettings as $builderSetting) {
            $error = apply_filters('wpjobboard/validate_component_on_save_' . $builderSetting['type'], false, $builderSetting, $formId);
            if ($error) {
                $errors[$builderSetting['id']] = $error;
            }
        }

        if ($errors) {
            wp_send_json_error(array(
                'message' => __('Validation failed when saving the form', 'wpjobboard'),
                'errors'  => $errors
            ), 423);
        }

        $submit_button_settings = wp_unslash($_REQUEST['submit_button_settings']);
        update_post_meta($formId, 'wpjobboard_application_builder_settings', $builderSettings);
        update_post_meta($formId, 'wpjobboard_submit_button_settings', $submit_button_settings);

        wp_send_json_success(array(
            'message' => __('Settings successfully updated', 'wpjobboard')
        ), 200);
    }

    protected function getFormBuilderSettings()
    {
        $formId = absint($_REQUEST['form_id']);
        $jobPost = Forms::getForm($formId);
        $builderSettings = Forms::getBuilderSettings($formId);

        wp_send_json_success(array(
            'job_post'             => $jobPost,
            'builder_settings'     => $builderSettings,
            'components'           => GeneralSettings::getComponents(),
            'form_button_settings' => Forms::getButtonSettings($formId)
        ), 200);
    }

    protected function deleteForm()
    {
        $formId = intval($_REQUEST['form_id']);
        do_action('wpjobboard/before_form_delete', $formId);
        Forms::deleteForm($formId);
        do_action('wpjobboard/after_form_delete', $formId);
        wp_send_json_success(array(
            'message' => __('Selected form successfully deleted', 'wpjobboard')
        ), 200);
    }

    protected function getDesignSettings()
    {
        $formId = intval($_REQUEST['form_id']);
        wp_send_json_success(array(
            'layout_settings' => Forms::getDesignSettings($formId)
        ), 200);
    }

    protected function updateDesignSettings()
    {
        $formId = intval($_REQUEST['form_id']);
        $layoutSettings = wp_unslash($_REQUEST['layout_settings']);
        update_post_meta($formId, 'wpjobboard_form_design_settings', $layoutSettings);
        wp_send_json_success(array(
            'message' => __('Settings successfully updated', 'wpjobboard')
        ), 200);
    }

    protected function duplicateForm()
    {
        $formId = absint($_POST['form_id']);
        $globalTools = new GlobalTools();
        $oldForm = $globalTools->getForm($formId);
        $oldForm['post_title'] = '(Duplicate) ' . $oldForm['post_title'];
        $oldForm = apply_filters('wpjobboard/form_duplicate', $oldForm);

        if (!$oldForm) {
            wp_send_json_error(array(
                'message' => __('No form found when duplicating the form', 'wpjobboard')
            ), 423);
        }
        $newForm = $globalTools->createFormFromData($oldForm);
        wp_send_json_success(array(
            'message' => __('Form successfully duplicated', 'wpjobboard'),
            'form'    => $newForm
        ), 200);
    }

    private function restoreForm()
    {
        $formId = intval($_REQUEST['form_id']);
        wp_update_post([
            'ID' => $formId,
            'post_status' => 'publish'
        ]);
        wp_send_json_success(array(
            'message' => 'Job post successfully restored'
        ));
    }

    protected function createDemoApplicationForm($formId)
    {

    }
}