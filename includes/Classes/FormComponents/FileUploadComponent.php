<?php

namespace WPJobBoard\Classes\FormComponents;

use WPJobBoard\Classes\ArrayHelper;
use WPJobBoard\Classes\File\FileAccess;
use WPJobBoard\Classes\File\FileHandler;
use WPJobBoard\Classes\Models\Forms;
use WPJobBoard\Classes\Tools\SchedulingSettings;

if (!defined('ABSPATH')) {
    exit;
}

class FileUploadComponent extends BaseComponent
{
    protected $componentName = 'file_upload_input';

    public function __construct()
    {
        parent::__construct($this->componentName, 12);
        add_action('wp_ajax_wpjb_file_upload_process', array($this, 'handleFileUpload'));
        add_action('wp_ajax_nopriv_wpjb_file_upload_process', array($this, 'handleFileUpload'));
        add_filter('wpjobboard/submitted_value_' . $this->componentName, array($this, 'formatUploadedValue'), 10, 3);
        add_filter('wpjobboard/validate_data_on_submission_' . $this->componentName, array($this, 'validateUploadedValue'), 10, 4);
        add_filter('wpjobboard/rendering_entry_value_' . $this->componentName, array($this, 'convertValueToHtml'), 10, 3);
        add_filter('wpjobboard/exporting_entry_value_' . $this->componentName, array($this, 'exportValue'), 10, 3);
        add_action('wpjobboard/require_entry_html', array($this, 'registerConvertHtml'));
        add_filter('wpjobboard/require_entry_html_done', array($this, 'deRegisterConvertHtml'));
    }

    public function component()
    {
        return array(
            'type'            => $this->componentName,
            'editor_title'    => __('File Upload', 'ninja-job-board'),
            'group'           => 'input',
            'postion_group'   => 'general',
            'editor_elements' => array(
                'label'             => array(
                    'label' => __('Upload label', 'ninja-job-board'),
                    'type'  => 'text',
                    'group' => 'general'
                ),
                'button_text'       => array(
                    'label' => __('Upload button text', 'ninja-job-board'),
                    'type'  => 'text',
                    'group' => 'general'
                ),
                'required'          => array(
                    'label' => __('Required', 'ninja-job-board'),
                    'type'  => 'switch',
                    'group' => 'general'
                ),
                'max_file_size'     => array(
                    'label' => __('Maximum file size (in MB)', 'ninja-job-board'),
                    'type'  => 'number',
                    'group' => 'general'
                ),
                'max_allowed_files' => array(
                    'label' => __('Maximum number of files', 'ninja-job-board'),
                    'type'  => 'number',
                    'group' => 'general'
                ),
                'allowed_files'     => array(
                    'label'         => __('Allowed file types', 'ninja-job-board'),
                    'type'          => 'checkbox',
                    'wrapper_class' => 'checkbox_new_lined',
                    'options'       => $this->getFileTypes('label')
                )
            ),
            'field_options'   => array(
                'label'             => __('Upload your file', 'ninja-job-board'),
                'button_text'       => __('Drag and drop your files or browse', 'ninja-job-board'),
                'required'          => 'yes',
                'max_file_size'     => 2,
                'max_allowed_files' => 1,
                'allowed_files'     => ['pdf'],
            )
        );
    }

    public function render($element, $form, $elements)
    {
        wp_enqueue_script('wpjobboard_file_upload');

        add_filter('wpjobboard/form_css_classes', function ($classes, $reneringForm) use ($form) {
            if ($reneringForm->ID == $form->ID) {
                $classes[] = 'wpjb_form_has_file_upload';
            }
            return $classes;
        }, 10, 2);

        $fieldOptions = ArrayHelper::get($element, 'field_options');


        $controlClass = $this->elementControlClass($element);
        $inputId = 'wpjb_input_' . $form->ID . '_' . $element['id'];
        $element['extra_input_class'] = 'wpjb_file_upload_element';
        $inputClass = $this->elementInputClass($element);

        $maxFileSize = ArrayHelper::get($fieldOptions, 'max_file_size');

        $accepts = implode(',', $this->getFileAcceptExtensions($element));

        $maxFilesCount = ArrayHelper::get($fieldOptions, 'max_allowed_files');

        $btnText = ArrayHelper::get($fieldOptions, 'button_text');
        if (!$btnText) {
            $btnText = __('Drag and drop your files or browse', 'ninja-job-board');
        }

        $associateKey = '__' . $element['id'] . '_files';
        $attributes = array(
            'data-target_name'   => $element['id'],
            'value'              => '',
            'type'               => 'file',
            'accept'             => $accepts,
            'data-max_files'     => $maxFilesCount,
            'data-max_file_size' => $maxFileSize,
            'data-associate_key' => $associateKey,
            'data-btn_txt'       => $btnText,
            'class'              => $inputClass,
            'id'                 => $inputId,
            'multiple'           => 'true',
            'data-file_required' => ArrayHelper::get($fieldOptions, 'required')
        );
        if ($maxFilesCount > 1) {
            $attributes['multiple'] = 'true';
        }
        ?>

        <div data-element_type="<?php echo esc_attr($this->elementName); ?>"
             class="<?php echo esc_attr($controlClass); ?>">
            <?php $this->buildLabel($fieldOptions, $form, array('for' => $inputId)); ?>
            <div class="wpjb_input_content wpjb_file_upload_wrapper dropzone dropzone_parent">
                <input type="hidden" name="<?php echo esc_attr($element['id']); ?>" value="<?php echo esc_attr($associateKey); ?>"/>
                <input <?php echo $this->builtAttributes($attributes); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- every value escaped with esc_attr() in builtAttributes() ?> />
            </div>
            <div class="upload_error_message"></div>
        </div>
        <?php
    }

    public function validateUploadedValue($error, $elementId, $element, $form_data)
    {
        $dataName = ArrayHelper::get($form_data, $elementId);
        $dataValues = ($dataName) ? ArrayHelper::get($form_data, $dataName) : [];
        $dataValues = is_array($dataValues) ? array_filter($dataValues) : [];

        $maxFiles = absint(ArrayHelper::get($element, 'options.max_allowed_files'));
        if ($maxFiles && count($dataValues) > $maxFiles) {
            /* translators: %d: maximum number of files */
            return sprintf(_n('You can upload a maximum of %d file.', 'You can upload a maximum of %d files.', $maxFiles, 'ninja-job-board'), $maxFiles);
        }

        $isRequired = ArrayHelper::get($element, 'options.required') == 'yes';
        if (!$isRequired) {
            return false;
        }

        if (!$dataValues) {
            /* translators: %s: field label */
            $error = sprintf(__('%s is required. Please upload the required files.', 'ninja-job-board'), ArrayHelper::get($element, 'options.label'));
        }
        return $error;
    }

    /**
     * Only files this plugin uploaded for this form and field pass: the upload transient record is consumed here,
     * so the client can not submit arbitrary names, URLs, or another applicant's file.
     */
    public function formatUploadedValue($dataName, $element, $data)
    {
        if (!$dataName || !is_string($dataName)) {
            return array();
        }

        $files = ArrayHelper::get($data, $dataName);

        if (!$files || !is_array($files)) {
            return [];
        }

        $formId = absint(ArrayHelper::get($data, '__wpjb_form_id'));
        $uploadDir = FileHandler::getUploadDir();
        $maxFiles = absint(ArrayHelper::get($element, 'options.max_allowed_files'));

        $fullPathFiles = array();
        foreach ($files as $file) {
            if (!is_string($file)) {
                continue;
            }

            $fileName = sanitize_file_name(wp_basename($file));

            if (!$fileName || $fileName !== $file || !FileHandler::isPluginFileName($fileName)) {
                continue;
            }

            if (!file_exists($uploadDir['path'] . '/' . $fileName)) {
                continue;
            }

            $record = get_transient(FileHandler::uploadRecordKey($fileName));
            if (!$record || $record !== $formId . ':' . $element['id']) {
                continue;
            }
            delete_transient(FileHandler::uploadRecordKey($fileName));

            $fullPathFiles[] = $uploadDir['url'] . '/' . $fileName;

            if ($maxFiles && count($fullPathFiles) >= $maxFiles) {
                break;
            }
        }

        return $fullPathFiles;
    }

    public function registerConvertHtml()
    {
        add_filter('wpjobboard/maybe_conver_html_' . $this->componentName, array($this, 'convertValueToHtml'), 10, 3);
    }

    public function deRegisterConvertHtml()
    {
        remove_filter('wpjobboard/maybe_conver_html_' . $this->componentName, array($this, 'convertValueToHtml'), 10, 3);
    }

    public function convertValueToHtml($values, $submission, $element)
    {
        if (empty($values) || !is_array($values)) {
            return '';
        }

        $submissionId = (is_object($submission) && !empty($submission->id)) ? absint($submission->id) : 0;

        $html = '<div class="wpjb_file_list">';
        foreach (FileAccess::fileNames($values) as $baseName) {
            // Show the original name: drop the random "wpf-<hash>-wpf-" prefix
            $displayName = preg_replace('/^wpf-[a-f0-9]{32}-wpf-/', '', $baseName);
            $ext = strtolower(pathinfo($baseName, PATHINFO_EXTENSION));

            // Without an application there is no permission to check, so show the name only
            if (!$submissionId) {
                $html .= '<span class="wpjb_file"><span class="wpjb_file_name">' . esc_html($displayName) . '</span></span>';
                continue;
            }

            $html .= '<a class="wpjb_file" href="' . esc_url(FileAccess::downloadUrl($submissionId, $baseName, true)) . '" target="_blank" rel="noopener" title="' . esc_attr__('Open file in a new tab', 'ninja-job-board') . '">';
            if ($this->isImage($baseName)) {
                $html .= '<img class="wpjb_file_thumb" src="' . esc_url(FileAccess::downloadUrl($submissionId, $baseName, true)) . '" alt="" width="32" height="32" />';
            } else {
                $html .= '<span class="wpjb_file_ext">' . esc_html($ext ? strtoupper(substr($ext, 0, 4)) : __('File', 'ninja-job-board')) . '</span>';
            }
            $html .= '<span class="wpjb_file_name">' . esc_html($displayName) . '</span>';
            $html .= '</a>';
        }
        $html .= '</div>';
        return wp_kses_post($html);
    }

    private function getFileAcceptExtensions($element)
    {
        $fieldOptions = ArrayHelper::get($element, 'field_options');
        $allowedFiles = (array)ArrayHelper::get($fieldOptions, 'allowed_files', []);
        $fileTypes = $this->getFileTypes('accepts');
        $accepts = [];
        foreach ($allowedFiles as $allowedFile) {
            $accepts[] = str_replace(' ', '', (string)ArrayHelper::get($fileTypes, $allowedFile));
        }
        $accepts = array_filter($accepts);
        $accepts = implode(',', $accepts);
        return explode(',', $accepts);
    }

    private function getFileTypes($pairType = false)
    {
        $types = array(
            'images' => array(
                'label'   => __('Images (jpg, jpeg, gif, png, bmp)', 'ninja-job-board'),
                'accepts' => '.jpg,.jpeg,.gif,.png,.bmp'
            ),
            'audios' => array(
                'label'   => __('Audio (mp3, wav, ogg, wma, mka, m4a, ra, mid, midi)', 'ninja-job-board'),
                'accepts' => '.mp3, .wav, .ogg, .wma, .mka, .m4a, .ra, .mid, .midi'
            ),
            'pdf'    => array(
                'label'   => __('PDF (pdf)', 'ninja-job-board'),
                'accepts' => '.pdf'
            ),
            'docs'   => array(
                'label'   => __('Docs (doc, ppt, pps, xls, mdb, docx, xlsx, pptx, odt, odp, ods, odg, odc, odb, odf, rtf, txt)', 'ninja-job-board'),
                'accepts' => '.doc,.ppt,.pps,.xls,.mdb,.docx,.xlsx,.pptx,.odt,.odp,.ods,.odg,.odc,.odb,.odf,.rtf,.txt'
            ),
            'zips'   => array(
                'label'   => __('Zip archives (zip, gz, gzip, rar, 7z)', 'ninja-job-board'),
                'accepts' => '.zip,.gz,.gzip,.rar,.7z'
            ),
            'csv'    => array(
                'label'   => __('CSV (csv)', 'ninja-job-board'),
                'accepts' => '.csv'
            )
        );

        $types = apply_filters('wpjobboard/upload_files_available', $types);

        if ($pairType) {
            $pairs = [];
            foreach ($types as $typeName => $type) {
                $pairs[$typeName] = ArrayHelper::get($type, $pairType);
            }
            return $pairs;
        }

        return $types;
    }

    public function handleFileUpload()
    {
        if (!wpJobBoardVerifyPublicNonce()) {
            $this->sendError(__('Security check failed. Please reload the page and try again.', 'ninja-job-board'), 'nonce', 403);
        }

        $formId = isset($_REQUEST['form_id']) ? absint($_REQUEST['form_id']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- verified in wpJobBoardVerifyPublicNonce()
        $elementName = isset($_REQUEST['element_name']) ? sanitize_key(wp_unslash($_REQUEST['element_name'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        if (!$formId || !$elementName) {
            $this->sendError(__('Wrong file upload instance', 'ninja-job-board'), 'no element found');
        }

        $form = Forms::getForm($formId);
        if (!$form || $form->post_status !== 'publish') {
            $this->sendError(__('Form not found', 'ninja-job-board'), 'no form found');
        }

        // The same schedule, entry limit and login rules as the submission
        $restrictions = (new SchedulingSettings())->validateForm(array(), $formId);
        if ($restrictions) {
            $this->sendError(wp_strip_all_tags(reset($restrictions)), 'restricted', 403);
        }

        $element = $this->getFileUploadElement($formId, $elementName);
        $fieldOptions = ArrayHelper::get($element, 'field_options');
        if (!$element || !$fieldOptions) {
            $this->sendError(__('Element not found', 'ninja-job-board'), 'no element found');
        }

        if (empty($_FILES['file']) || !is_array($_FILES['file']) || empty($_FILES['file']['tmp_name'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $this->sendError(__('No file was uploaded', 'ninja-job-board'), 'no file');
        }

        $uploadFile = $_FILES['file']; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- validated by FileHandler and wp_handle_upload()

        if (!is_uploaded_file($uploadFile['tmp_name'])) {
            $this->sendError(__('Invalid file upload', 'ninja-job-board'), 'invalid upload');
        }

        $file = $this->handleUploadFile($uploadFile, $element, $formId);

        if (!empty($file['error'])) {
            $this->sendError(esc_html($file['error']), 'upload_error');
        }

        $fileName = wp_basename($file['file']);

        // Server-side record: this file belongs to this form field. It is consumed on submission.
        set_transient(FileHandler::uploadRecordKey($fileName), $formId . ':' . $element['id'], DAY_IN_SECONDS);

        wp_send_json(array(
            'file'          => $fileName,
            'url'           => esc_url_raw($file['url']),
            'type'          => sanitize_mime_type($file['type']),
            'original_name' => sanitize_file_name($uploadFile['name'])
        ), 200);
    }

    public function getFileUploadElement($formId, $elementName)
    {
        $allEmenets = Forms::getBuilderSettings($formId);
        foreach ($allEmenets as $element) {
            if ($element['id'] == $elementName && $element['type'] == $this->componentName) {
                return $element;
            }
        }

        return array();
    }

    private function handleUploadFile($file, $element, $formId)
    {
        $fileHandler = new FileHandler($file);
        $fileHandler->overrideUploadDir();

        $errors = $fileHandler->validate([
            'extensions'    => $this->getFileAcceptExtensions($element),
            'max_file_size' => ArrayHelper::get($element, 'field_options.max_file_size')
        ]);

        $errors = apply_filters('wpjobboard/upload_validation_errors', $errors, $file, $element, $formId);

        if ($errors) {
            $errorMessage = esc_html__('Validation Failed', 'ninja-job-board');
            if (is_array($errors)) {
                $errorMessage .= '<ul>';
                foreach ($errors as $error) {
                    $errorMessage .= '<li>' . esc_html($error) . '</li>';
                }
                $errorMessage .= '</ul>';
            }
            $this->sendError($errorMessage, array_map('esc_html', (array)$errors));
        }
        return $fileHandler->upload();
    }

    private function sendError($message, $error = false, $code = 423)
    {
        wp_send_json_error(array(
            'message' => $message,
            'error'   => $error
        ), $code);
    }

    /**
     * CSV export: one protected download link per file
     */
    public function exportValue($values, $submission, $element)
    {
        $links = array();
        foreach (FileAccess::fileNames($values) as $baseName) {
            $links[] = FileAccess::downloadUrl($submission->id, $baseName);
        }
        return implode(' ', $links);
    }

    private function isImage($fileName)
    {
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        return in_array($ext, array('jpg', 'jpeg', 'gif', 'png', 'bmp'), true);
    }
}
