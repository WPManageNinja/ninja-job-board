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
    // phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing -- every route passes AccessControl::checkAndPresponseError() first, which verifies the nonce

    /**
     * Get the requested form ID. Stops with an error when the ID is not a job form.
     */
    private function requireFormId()
    {
        $formId = isset($_REQUEST['form_id']) ? absint($_REQUEST['form_id']) : 0;
        if (!$formId || !Forms::getForm($formId)) {
            wp_send_json_error(array(
                'message' => __('Job post not found', 'ninja-job-board')
            ), 404);
        }
        return $formId;
    }

    public function registerEndpoints()
    {
        add_action('wp_ajax_wpjobboard_forms_admin_ajax', array($this, 'handeEndPoint'));
    }

    public function handeEndPoint()
    {
        $route = isset($_REQUEST['route']) ? sanitize_text_field(wp_unslash($_REQUEST['route'])) : '';

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

        if (isset($validRoutes[$route])) {
            AccessControl::checkAndPresponseError($route, 'forms');
            do_action('wpjobboard/doing_ajax_forms_' . $route);
            return $this->{$validRoutes[$route]}();
        }
        do_action('wpjobboard/admin_ajax_handler_catch', $route);
    }

    protected function getForms()
    {
        $perPage = isset($_REQUEST['per_page']) ? min(100, max(1, absint($_REQUEST['per_page']))) : 20;
        $pageNumber = isset($_REQUEST['page_number']) ? max(1, absint($_REQUEST['page_number'])) : 1;
        $searchString = isset($_REQUEST['search_string']) ? sanitize_text_field(wp_unslash($_REQUEST['search_string'])) : '';
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
        $postTitle = isset($_REQUEST['post_title']) ? sanitize_text_field(wp_unslash($_REQUEST['post_title'])) : '';
        if (!$postTitle) {
            wp_send_json_error(array(
                'message' => __('Please Provide a job title', 'ninja-job-board')
            ), 423);
            return;
        }

        $data = array(
            'post_title'  => $postTitle,
            'post_status' => 'draft'
        );

        do_action('wpjobboard/before_create_form', $data);

        $formId = Forms::create($data);

        if (is_wp_error($formId) || !$formId) {
            wp_send_json_error(array(
                'message' => __('Something is wrong when createding the form. Please try again', 'ninja-job-board')
            ), 423);
            return;
        }

        do_action('wpjobboard/after_create_form', $formId, $data);

        $this->createDemoApplicationForm($formId);

        wp_send_json_success(array(
            'message'      => __('Please wait, You are redirecting to the next page', 'ninja-job-board'),
            'form_id'      => $formId,
            'redirect_url' => get_edit_post_link($formId)
        ), 200);
    }

    protected function updateForm()
    {
        // validate first
        $formId = $this->requireFormId();
        $title = isset($_REQUEST['post_title']) ? sanitize_text_field(wp_unslash($_REQUEST['post_title'])) : '';
        if (!$title) {
            wp_send_json_error(array(
                'message' => __('Please provide form title', 'ninja-job-board')
            ), 423);
        }

        $formData = array(
            'post_title'   => $title,
            'post_content' => isset($_REQUEST['post_content']) ? wp_kses_post(wp_unslash($_REQUEST['post_content'])) : ''

        );

        do_action('wpjobboard/before_update_form', $formId, $formData);
        Forms::update($formId, $formData);
        do_action('wpjobboard/after_update_form', $formId, $formData);

        $showTitle = isset($_REQUEST['show_title_description']) ? sanitize_text_field(wp_unslash($_REQUEST['show_title_description'])) : '';
        update_post_meta($formId, 'wpjobboard_show_title_description', $showTitle);
        wp_send_json_success(array(
            'message' => __('Form successfully updated', 'ninja-job-board')
        ), 200);
    }

    protected function getForm()
    {
        $formId = $this->requireFormId();
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

        $formId = $this->requireFormId();

        wp_send_json_success(array(
            'confirmation_settings' => Forms::getConfirmationSettings($formId),
            'editor_shortcodes'     => FormPlaceholders::getAllPlaceholders($formId),
            'pages'                 => $allPages
        ), 200);
    }

    protected function saveFormSettings()
    {
        $formId = $this->requireFormId();
        if (isset($_REQUEST['confirmation_settings'])) {

            $formattedSettings = wpJobBoardSanitize(wp_unslash(ArrayHelper::get($_REQUEST, 'confirmation_settings', []))); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized by wpJobBoardSanitize()
            if (isset($formattedSettings['customUrl'])) {
                $formattedSettings['customUrl'] = esc_url_raw($formattedSettings['customUrl']);
            }

            update_post_meta($formId, 'jobboard_confirmation_settings', $formattedSettings);
        }

        wp_send_json_success(array(
            'message' => __('Settings successfully updated', 'ninja-job-board')
        ), 200);
    }

    protected function saveFormBuilderSettings()
    {
        $formId = $this->requireFormId();
        $builderSettings = isset($_REQUEST['builder_settings']) ? wpJobBoardSanitize(wp_unslash($_REQUEST['builder_settings'])) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized by wpJobBoardSanitize()

        if (!$builderSettings || !is_array($builderSettings)) {
            wp_send_json_error(array(
                'message' => __('Validation Error, Please try again', 'ninja-job-board'),
                'errors'  => array(
                    'general' => __('Please add at least one input element', 'ninja-job-board')
                )
            ), 423);
        }
        $errors = array();

        foreach ($builderSettings as $index => $builderSetting) {
            if (!is_array($builderSetting) || empty($builderSetting['type']) || empty($builderSetting['id'])) {
                unset($builderSettings[$index]);
                continue;
            }
            $builderSettings[$index]['type'] = sanitize_key($builderSetting['type']);
            $builderSettings[$index]['id'] = sanitize_key($builderSetting['id']);
            $builderSetting = $builderSettings[$index];
            $error = apply_filters('wpjobboard/validate_component_on_save_' . $builderSetting['type'], false, $builderSetting, $formId);
            if ($error) {
                $errors[$builderSetting['id']] = $error;
            }
        }

        if ($errors) {
            wp_send_json_error(array(
                'message' => __('Validation failed when saving the form', 'ninja-job-board'),
                'errors'  => $errors
            ), 423);
        }

        $submit_button_settings = wpJobBoardSanitize(wp_unslash(ArrayHelper::get($_REQUEST, 'submit_button_settings', []))); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized by wpJobBoardSanitize()
        if (is_array($submit_button_settings)) {
            foreach (array('css_class', 'button_style') as $classKey) {
                if (isset($submit_button_settings[$classKey])) {
                    $submit_button_settings[$classKey] = implode(' ', array_map('sanitize_html_class', explode(' ', $submit_button_settings[$classKey])));
                }
            }
        }
        update_post_meta($formId, 'wpjobboard_application_builder_settings', array_values($builderSettings));
        update_post_meta($formId, 'wpjobboard_submit_button_settings', $submit_button_settings);

        wp_send_json_success(array(
            'message' => __('Settings successfully updated', 'ninja-job-board')
        ), 200);
    }

    protected function getFormBuilderSettings()
    {
        $formId = $this->requireFormId();
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
        $formId = $this->requireFormId();
        do_action('wpjobboard/before_form_delete', $formId);
        Forms::deleteForm($formId);
        do_action('wpjobboard/after_form_delete', $formId);
        wp_send_json_success(array(
            'message' => __('Selected form successfully deleted', 'ninja-job-board')
        ), 200);
    }

    protected function getDesignSettings()
    {
        $formId = $this->requireFormId();
        wp_send_json_success(array(
            'layout_settings' => Forms::getDesignSettings($formId)
        ), 200);
    }

    protected function updateDesignSettings()
    {
        $formId = $this->requireFormId();
        $layoutSettings = isset($_REQUEST['layout_settings']) ? wpJobBoardSanitize(wp_unslash($_REQUEST['layout_settings'])) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized by wpJobBoardSanitize()
        update_post_meta($formId, 'wpjobboard_form_design_settings', $layoutSettings);
        wp_send_json_success(array(
            'message' => __('Settings successfully updated', 'ninja-job-board')
        ), 200);
    }

    protected function duplicateForm()
    {
        $formId = $this->requireFormId();
        $globalTools = new GlobalTools();
        $oldForm = $globalTools->getForm($formId);
        if ($oldForm) {
            $oldForm['post_title'] = '(Duplicate) ' . $oldForm['post_title'];
        }
        $oldForm = apply_filters('wpjobboard/form_duplicate', $oldForm);

        if (!$oldForm) {
            wp_send_json_error(array(
                'message' => __('No form found when duplicating the form', 'ninja-job-board')
            ), 423);
        }
        $newForm = $globalTools->createFormFromData($oldForm);
        wp_send_json_success(array(
            'message' => __('Form successfully duplicated', 'ninja-job-board'),
            'form'    => $newForm
        ), 200);
    }

    private function restoreForm()
    {
        $formId = $this->requireFormId();
        wp_update_post([
            'ID' => $formId,
            'post_status' => 'publish'
        ]);
        wp_send_json_success(array(
            'message' => __('Job post successfully restored', 'ninja-job-board')
        ));
    }

    protected function createDemoApplicationForm($formId)
    {
        $default = '{"form_meta":{"wpjobboard_application_builder_settings":[{"type":"applicant_name","editor_title":"Applicant Name","group":"input","postion_group":"general","editor_elements":{"label":{"label":"Field Label","type":"text","group":"general"},"placeholder":{"label":"Placeholder","type":"text","group":"general"},"required":{"label":"Required","type":"switch","group":"general"},"default_value":{"label":"Default Value","type":"text","group":"general"}},"field_options":{"label":"Your Name","placeholder":"Name","required":"yes"},"id":"applicant_name"},{"type":"applicant_email","editor_title":"Applicant Email","group":"input","postion_group":"general","editor_elements":{"label":{"label":"Field Label","type":"text","group":"general"},"placeholder":{"label":"Placeholder","type":"text","group":"general"},"required":{"label":"Required","type":"switch","group":"general"},"confirm_email":{"label":"Enable Confirm Email Field","type":"confirm_email_switch","group":"general"},"default_value":{"label":"Default Value","type":"text","group":"general"}},"field_options":{"label":"Email Address","placeholder":"Email Address","required":"yes","confirm_email":"no","confirm_email_label":"Confirm Email","default_value":""},"id":"applicant_email"},{"type":"file_upload_input","editor_title":"File Upload","group":"input","postion_group":"general","editor_elements":{"label":{"label":"Upload Label","type":"text","group":"general"},"button_text":{"label":"Upload Button Text","type":"text","group":"general"},"required":{"label":"Required","type":"switch","group":"general"},"max_file_size":{"label":"Max File Size (in MegaByte)","type":"number","group":"general"},"max_allowed_files":{"label":"Max Upload Files","type":"number","group":"general"},"allowed_files":{"label":"Allowed file types","type":"checkbox","wrapper_class":"checkbox_new_lined","options":{"images":"Images (jpg, jpeg, gif, png, bmp)","audios":"Audio (mp3, wav, ogg, wma, mka, m4a, ra, mid, midi)","pdf":"pdf","docs":"Docs (doc, ppt, pps, xls, mdb, docx, xlsx, pptx, odt, odp, ods, odg, odc, odb, odf, rtf, txt)","zips":"Zip Archives (zip, gz, gzip, rar, 7z)","csv":"CSV (csv)"}}},"field_options":{"label":"Attach Your CV","button_text":"Drag & Drop your files or Browse","required":"yes","max_file_size":"2","max_allowed_files":"1","allowed_files":["pdf"]},"id":"file_upload_input"}],"wpjobboard_submit_button_settings":{"button_text":"Submit Application","processing_text":"Please Wait\u2026","button_style":"wpjb_default_btn","css_class":""},"jobboard_confirmation_settings":{"confirmation_type":"custom","redirectTo":"samePage","customUrl":"","messageToShow":"<p>Your application has been successfully submitted.<\/p>","samePageFormBehavior":"hide_form"},"wpjobboard_form_design_settings":{"labelPlacement":"top","asteriskPlacement":"left","submit_button_position":"right","extra_styles":{"wpjb_default_form_styles":"yes","wpjb_bold_labels":"no"}},"wpjb_email_notifications":[{"title":"Email Notification to Admin","email_to":"{wp:admin_email}","reply_to":"{submission.applicant_email}","email_subject":"New Job Application Submitted at {wp:post_title}","email_body":"<p><strong>Application Details:<\/strong><\/p>\n<p>{submission.all_input_field_html}<\/p>\n<p>Application has been submitted at: {wp:post_url}<\/p>","from_name":"","from_email":"","format":"html","email_template":"default","cc_to":"","bcc_to":"","conditions":"","sending_action":"wpjobboard\/after_form_submission_complete","status":"disabled"}]}}';
        $defaltForm = json_decode($default, true);

        $builderSettings  = ArrayHelper::get($defaltForm, 'form_meta.wpjobboard_application_builder_settings');
        if($builderSettings) {
            update_post_meta($formId, 'wpjobboard_application_builder_settings', array_values($builderSettings));
        }

        $submitButton = ArrayHelper::get($defaltForm, 'form_meta.wpjobboard_submit_button_settings');
        if($submitButton) {
            update_post_meta($formId, 'wpjobboard_submit_button_settings', $submitButton);
        }

        $confirmSettings = ArrayHelper::get($defaltForm, 'form_meta.jobboard_confirmation_settings');
        if($confirmSettings) {
            update_post_meta($formId, 'jobboard_confirmation_settings', $confirmSettings);
        }

        $designSettings = ArrayHelper::get($defaltForm, 'form_meta.wpjobboard_form_design_settings');
        if($designSettings) {
            update_post_meta($formId, 'wpjobboard_form_design_settings', $designSettings);
        }

        update_post_meta($formId, 'application_end_timestamp', 0);

        $emailSettings = ArrayHelper::get($defaltForm, 'form_meta.wpjb_email_notifications');
        if($emailSettings) {
            update_post_meta($formId, 'wpjb_email_notifications', $emailSettings);
        }
    }
}
