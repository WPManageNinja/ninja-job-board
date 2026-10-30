<?php

namespace WPJobBoard\Http\Controllers;

use WPJobBoard\Classes\ArrayHelper;
use WPJobBoard\Classes\Models\Forms;
use WPJobBoard\Classes\Tools\GlobalTools;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Job posts (forms)
 * @since 2.0.0
 */
class FormsController extends Controller
{
    public function index(\WP_REST_Request $request)
    {
        $perPage = min(100, max(1, absint($request->get_param('per_page') ?: 20)));
        $page = max(1, absint($request->get_param('page') ?: 1));
        $search = $this->text($request, 'search');

        $args = apply_filters('wpjobboard/get_all_forms_args', array(
            'posts_per_page' => $perPage,
            'offset'         => $perPage * ($page - 1)
        ));

        if ($search && is_string($search)) {
            $args['s'] = $search;
        }

        return Forms::getForms($args, array('entries_count'));
    }

    public function available(\WP_REST_Request $request)
    {
        return array(
            'forms' => Forms::getAllAvailableForms()
        );
    }

    public function store(\WP_REST_Request $request)
    {
        $title = $this->text($request, 'post_title');
        if (!$title || !is_string($title)) {
            return $this->error(__('Please provide a job title.', 'ninja-job-board'));
        }

        $data = array(
            'post_title'  => $title,
            'post_status' => 'draft'
        );

        do_action('wpjobboard/before_create_form', $data);

        $formId = Forms::create($data);

        if (is_wp_error($formId) || !$formId) {
            return $this->error(__('Something is wrong when creating the form. Please try again', 'ninja-job-board'), 500);
        }

        do_action('wpjobboard/after_create_form', $formId, $data);

        $this->createDemoApplicationForm($formId);

        return array(
            'message'      => __('Please wait. You are being redirected to the next page.', 'ninja-job-board'),
            'form_id'      => $formId,
            'redirect_url' => get_edit_post_link($formId, 'raw')
        );
    }

    public function show(\WP_REST_Request $request)
    {
        $form = $this->requireForm($request);
        if (is_wp_error($form)) {
            return $form;
        }

        $form->edit_url = get_edit_post_link($form->ID, 'raw');
        $form->show_title_description = get_post_meta($form->ID, 'wpjobboard_show_title_description', true);

        return array(
            'form' => $form
        );
    }

    public function update(\WP_REST_Request $request)
    {
        $form = $this->requireForm($request);
        if (is_wp_error($form)) {
            return $form;
        }

        $title = $this->text($request, 'post_title');
        if (!$title || !is_string($title)) {
            return $this->error(__('Enter a job title.', 'ninja-job-board'));
        }

        $content = $request->get_param('post_content');
        $formData = array(
            'post_title'   => $title,
            'post_content' => is_string($content) ? wp_kses_post($content) : $form->post_content
        );

        do_action('wpjobboard/before_update_form', $form->ID, $formData);
        Forms::update($form->ID, $formData);
        do_action('wpjobboard/after_update_form', $form->ID, $formData);

        $showTitle = $this->text($request, 'show_title_description');
        update_post_meta($form->ID, 'wpjobboard_show_title_description', is_string($showTitle) ? $showTitle : '');

        return array(
            'message' => __('Form successfully updated', 'ninja-job-board')
        );
    }

    public function destroy(\WP_REST_Request $request)
    {
        $form = $this->requireForm($request);
        if (is_wp_error($form)) {
            return $form;
        }

        do_action('wpjobboard/before_form_delete', $form->ID);
        Forms::deleteForm($form->ID);
        do_action('wpjobboard/after_form_delete', $form->ID);

        return array(
            'message' => __('Selected form successfully deleted', 'ninja-job-board')
        );
    }

    public function duplicate(\WP_REST_Request $request)
    {
        $form = $this->requireForm($request);
        if (is_wp_error($form)) {
            return $form;
        }

        $globalTools = new GlobalTools();
        $oldForm = $globalTools->getForm($form->ID);
        if ($oldForm) {
            $oldForm['post_title'] = '(Duplicate) ' . $oldForm['post_title'];
        }
        $oldForm = apply_filters('wpjobboard/form_duplicate', $oldForm);

        if (!$oldForm) {
            return $this->error(__('No form found when duplicating the form', 'ninja-job-board'), 404);
        }

        $newForm = $globalTools->createFormFromData($oldForm);
        if (!$newForm) {
            return $this->error(__('Something is wrong when duplicating the form. Please try again', 'ninja-job-board'), 500);
        }

        return array(
            'message' => __('Form successfully duplicated', 'ninja-job-board'),
            'form'    => $newForm
        );
    }

    public function restore(\WP_REST_Request $request)
    {
        $form = $this->requireForm($request);
        if (is_wp_error($form)) {
            return $form;
        }

        wp_update_post(array(
            'ID'          => $form->ID,
            'post_status' => 'publish'
        ));

        return array(
            'message' => __('Job post successfully restored', 'ninja-job-board')
        );
    }

    /**
     * Download the form as a JSON file. The browser opens this URL directly, with _wpnonce.
     */
    public function export(\WP_REST_Request $request)
    {
        $globalTools = new GlobalTools();
        $form = $globalTools->getForm(absint($request->get_param('id')));

        if (!$form) {
            return $this->error(__('No Form Found', 'ninja-job-board'), 404);
        }

        $fileName = sanitize_file_name(sanitize_title($form['post_title'], 'export-form') . '-export-form-' . gmdate('Y-m-d') . '.json');
        nocache_headers();
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Type: application/json; charset=utf-8');
        echo wp_json_encode($form); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON file download, not HTML
        exit();
    }

    public function import(\WP_REST_Request $request)
    {
        $files = $request->get_file_params();
        $file = isset($files['import_file']) ? $files['import_file'] : null;

        if (!$file || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return $this->error(__('Please upload a json file', 'ninja-job-board'));
        }

        if (filesize($file['tmp_name']) > 2 * MB_IN_BYTES) {
            return $this->error(__('The file is too large', 'ninja-job-board'));
        }

        $form = json_decode(file_get_contents($file['tmp_name']), true); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local uploaded temp file
        $form = apply_filters('wpjobboard/impor_form_json_data', $form);

        if (!is_array($form) || !isset($form['post_title']) || ArrayHelper::get($form, 'post_type') != 'wp_job_board') {
            return $this->error(__('Invalid file. Please upload a valid JSON file.', 'ninja-job-board'));
        }

        do_action('wpjobboard/before_form_json_import', $form);

        $newForm = (new GlobalTools())->createFormFromData($form);
        if (!$newForm) {
            return $this->error(__('Something is wrong when processing the file. Please try again', 'ninja-job-board'), 500);
        }

        do_action('wpjobboard/form_json_imported', $newForm);

        return array(
            'message' => __('Form successfully imported', 'ninja-job-board'),
            'form'    => $newForm
        );
    }

    private function createDemoApplicationForm($formId)
    {
        $default = '{"form_meta":{"wpjobboard_application_builder_settings":[{"type":"applicant_name","editor_title":"Applicant Name","group":"input","postion_group":"general","editor_elements":{"label":{"label":"Field Label","type":"text","group":"general"},"placeholder":{"label":"Placeholder","type":"text","group":"general"},"required":{"label":"Required","type":"switch","group":"general"},"default_value":{"label":"Default Value","type":"text","group":"general"}},"field_options":{"label":"Your Name","placeholder":"Name","required":"yes"},"id":"applicant_name"},{"type":"applicant_email","editor_title":"Applicant Email","group":"input","postion_group":"general","editor_elements":{"label":{"label":"Field Label","type":"text","group":"general"},"placeholder":{"label":"Placeholder","type":"text","group":"general"},"required":{"label":"Required","type":"switch","group":"general"},"confirm_email":{"label":"Enable Confirm Email Field","type":"confirm_email_switch","group":"general"},"default_value":{"label":"Default Value","type":"text","group":"general"}},"field_options":{"label":"Email Address","placeholder":"Email Address","required":"yes","confirm_email":"no","confirm_email_label":"Confirm Email","default_value":""},"id":"applicant_email"},{"type":"file_upload_input","editor_title":"File Upload","group":"input","postion_group":"general","editor_elements":{"label":{"label":"Upload Label","type":"text","group":"general"},"button_text":{"label":"Upload Button Text","type":"text","group":"general"},"required":{"label":"Required","type":"switch","group":"general"},"max_file_size":{"label":"Max File Size (in MegaByte)","type":"number","group":"general"},"max_allowed_files":{"label":"Max Upload Files","type":"number","group":"general"},"allowed_files":{"label":"Allowed file types","type":"checkbox","wrapper_class":"checkbox_new_lined","options":{"images":"Images (jpg, jpeg, gif, png, bmp)","audios":"Audio (mp3, wav, ogg, wma, mka, m4a, ra, mid, midi)","pdf":"pdf","docs":"Docs (doc, ppt, pps, xls, mdb, docx, xlsx, pptx, odt, odp, ods, odg, odc, odb, odf, rtf, txt)","zips":"Zip Archives (zip, gz, gzip, rar, 7z)","csv":"CSV (csv)"}}},"field_options":{"label":"Attach Your CV","button_text":"Drag & Drop your files or Browse","required":"yes","max_file_size":"2","max_allowed_files":"1","allowed_files":["pdf"]},"id":"file_upload_input"}],"wpjobboard_submit_button_settings":{"button_text":"Submit Application","processing_text":"Please Wait\u2026","button_style":"wpjb_default_btn","css_class":""},"jobboard_confirmation_settings":{"confirmation_type":"custom","redirectTo":"samePage","customUrl":"","messageToShow":"<p>Your application has been successfully submitted.<\/p>","samePageFormBehavior":"hide_form"},"wpjobboard_form_design_settings":{"labelPlacement":"top","asteriskPlacement":"left","submit_button_position":"right","extra_styles":{"wpjb_default_form_styles":"yes","wpjb_bold_labels":"no"}},"wpjb_email_notifications":[{"title":"Email Notification to Admin","email_to":"{wp:admin_email}","reply_to":"{submission.applicant_email}","email_subject":"New Job Application Submitted at {wp:post_title}","email_body":"<p><strong>Application Details:<\/strong><\/p>\n<p>{submission.all_input_field_html}<\/p>\n<p>Application has been submitted at: {wp:post_url}<\/p>","from_name":"","from_email":"","format":"html","email_template":"default","cc_to":"","bcc_to":"","conditions":"","sending_action":"wpjobboard\/after_form_submission_complete","status":"disabled"}]}}';
        $defaltForm = json_decode($default, true);
        $defaltForm = $this->localizeDemoForm($defaltForm);

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

    /**
     * Replace the English defaults of the demo form with translated text.
     */
    private function localizeDemoForm($form)
    {
        if (!is_array($form)) {
            return $form;
        }

        $fieldTexts = array(
            'applicant_name'    => array(
                'label'       => __('Your name', 'ninja-job-board'),
                'placeholder' => __('Name', 'ninja-job-board')
            ),
            'applicant_email'   => array(
                'label'               => __('Email address', 'ninja-job-board'),
                'placeholder'         => __('Email address', 'ninja-job-board'),
                'confirm_email_label' => __('Confirm email', 'ninja-job-board')
            ),
            'file_upload_input' => array(
                'label'       => __('Attach your CV', 'ninja-job-board'),
                'button_text' => __('Drag and drop your files or browse', 'ninja-job-board')
            )
        );

        $fields = ArrayHelper::get($form, 'form_meta.wpjobboard_application_builder_settings', array());
        foreach ($fields as $index => $field) {
            $fieldId = ArrayHelper::get($field, 'id');
            if (isset($fieldTexts[$fieldId])) {
                $form['form_meta']['wpjobboard_application_builder_settings'][$index]['field_options'] = array_merge(
                    (array) ArrayHelper::get($field, 'field_options', array()),
                    $fieldTexts[$fieldId]
                );
            }
        }

        if (isset($form['form_meta']['wpjobboard_submit_button_settings'])) {
            $form['form_meta']['wpjobboard_submit_button_settings']['button_text'] = __('Submit application', 'ninja-job-board');
            $form['form_meta']['wpjobboard_submit_button_settings']['processing_text'] = __('Please wait…', 'ninja-job-board');
        }

        if (isset($form['form_meta']['jobboard_confirmation_settings'])) {
            $form['form_meta']['jobboard_confirmation_settings']['messageToShow'] = '<p>' . esc_html__('Your application has been successfully submitted.', 'ninja-job-board') . '</p>';
        }

        if (isset($form['form_meta']['wpjb_email_notifications'][0])) {
            $form['form_meta']['wpjb_email_notifications'][0]['title'] = __('Email notification to admin', 'ninja-job-board');
            /* translators: %s: job title placeholder, for example {wp:post_title} */
            $form['form_meta']['wpjb_email_notifications'][0]['email_subject'] = sprintf(__('New job application for %s', 'ninja-job-board'), '{wp:post_title}');
            $form['form_meta']['wpjb_email_notifications'][0]['email_body'] = '<p><strong>' . esc_html__('Application details:', 'ninja-job-board') . '</strong></p>' . "\n"
                . '<p>{submission.all_input_field_html}</p>' . "\n"
                /* translators: %s: job page URL placeholder, for example {wp:post_url} */
                . '<p>' . sprintf(esc_html__('Job page: %s', 'ninja-job-board'), '{wp:post_url}') . '</p>';
        }

        return $form;
    }
}
