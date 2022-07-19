<?php

namespace WPJobBoard\Classes\FormComponents;

use WPJobBoard\Classes\ArrayHelper;
use WPJobBoard\Classes\File\FileHandler;
use WPJobBoard\Classes\Models\Forms;

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
                    'label' => 'Upload Label',
                    'type'  => 'text',
                    'group' => 'general'
                ),
                'button_text'       => array(
                    'label' => 'Upload Button Text',
                    'type'  => 'text',
                    'group' => 'general'
                ),
                'required'          => array(
                    'label' => 'Required',
                    'type'  => 'switch',
                    'group' => 'general'
                ),
                'max_file_size'     => array(
                    'label' => 'Max File Size (in MegaByte)',
                    'type'  => 'number',
                    'group' => 'general'
                ),
                'max_allowed_files' => array(
                    'label' => 'Max Upload Files',
                    'type'  => 'number',
                    'group' => 'general'
                ),
                'allowed_files'     => array(
                    'label'         => 'Allowed file types',
                    'type'          => 'checkbox',
                    'wrapper_class' => 'checkbox_new_lined',
                    'options'       => $this->getFileTypes('label')
                )
            ),
            'field_options'   => array(
                'label'             => 'Upload Your File',
                'button_text'       => 'Drag & Drop your files or Browse',
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
            $btnText = 'Drag & Drop your files or Browse';
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
            'data-btn_txt'       => htmlspecialchars($btnText),
            'class'              => $inputClass,
            'id'                 => $inputId,
            'multiple'           => 'true',
            'data-file_required' => $fieldOptions['required']
        );
        if ($maxFilesCount > 1) {
            $attributes['multiple'] = 'true';
        }
        ?>

        <div data-element_type="<?php echo esc_html($this->elementName); ?>"
             class="<?php echo esc_html($controlClass); ?>">
            <?php $this->buildLabel($fieldOptions, $form, array('for' => $inputId)); ?>
            <div class="wpjb_input_content wpjb_file_upload_wrapper dropzone dropzone_parent">
                <input type="hidden" name="<?php echo esc_html($element['id']); ?>" value="<?php echo esc_html($associateKey); ?>"/>
                <input <?php wpJobBoardPrintInternal($this->builtAttributes($attributes)); ?> />
            </div>
            <div class="upload_error_message"></div>
        </div>
        <?php
    }

    public function validateUploadedValue($error, $elementId, $element, $form_data)
    {
        // Check it's required
        $isRequired = ArrayHelper::get($element, 'options.required') == 'yes';

        if (!$isRequired) {
            return false;
        }

        $dataName = ArrayHelper::get($form_data, $elementId);
        $dataValues = ArrayHelper::get($form_data, $dataName);

        if (!$dataValues) {
            $error = ArrayHelper::get($element, 'options.label') . ' is required, Please upload required files';
        }
        return $error;
    }

    public function formatUploadedValue($dataName, $element, $data)
    {
        if (!$dataName) {
            return array();
        }

        $files = ArrayHelper::get($data, $dataName);

        if(!$files) {
            return [];
        }

        $fullPathFiles = array();
        $uploadDir = get_option('wpjobboard_upload_dir');
        foreach ($files as $file) {
            $fullPathFiles[] = $uploadDir . '/' . $file;
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
        if (empty($values)) {
            return '';
        }
        $html = '<div class="payform_file_lists">';
        foreach ($values as $file) {
            $previewUrl = $this->getPreviewUrl($file);
            $html .= '<div class="payform_each_file"><a title="Click to View/Download" href="' . $file . '" target="_blank" rel="noopener"><img src="' . $previewUrl . '" /></a></div>';
        }
        $html .= '</div>';
        return $html;
    }

    private function getFileAcceptExtensions($element)
    {
        $fieldOptions = ArrayHelper::get($element, 'field_options');
        $allowedFiles = ArrayHelper::get($fieldOptions, 'allowed_files');
        $fileTypes = $this->getFileTypes('accepts');
        $accepts = [];
        foreach ($allowedFiles as $allowedFile) {
            $accepts[] = ArrayHelper::get($fileTypes, $allowedFile);
        }
        $accepts = array_filter($accepts);
        $accepts = implode(',', $accepts);
        return explode(',', $accepts);
    }

    private function getFileTypes($pairType = false)
    {
        $types = array(
            'images' => array(
                'label'   => 'Images (jpg, jpeg, gif, png, bmp)',
                'accepts' => '.jpg,.jpeg,.gif,.png,.bmp'
            ),
            'audios' => array(
                'label'   => 'Audio (mp3, wav, ogg, wma, mka, m4a, ra, mid, midi)',
                'accepts' => '.mp3, .wav, .ogg, .wma, .mka, .m4a, .ra, .mid, .midi'
            ),
            'pdf'    => array(
                'label'   => 'pdf',
                'accepts' => '.pdf'
            ),
            'docs'   => array(
                'label'   => 'Docs (doc, ppt, pps, xls, mdb, docx, xlsx, pptx, odt, odp, ods, odg, odc, odb, odf, rtf, txt)',
                'accepts' => '.doc,.ppt,.pps,.xls,.mdb,.docx,.xlsx,.pptx,.odt,.odp,.ods,.odg,.odc,.odb,.odf,.rtf,.txt'
            ),
            'zips'   => array(
                'label'   => 'Zip Archives (zip, gz, gzip, rar, 7z)',
                'accepts' => '.zip,.gz,.gzip,.rar,.7z'
            ),
            'csv'    => array(
                'label'   => 'CSV (csv)',
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
        $formId = ArrayHelper::get($_REQUEST, 'form_id');
        $elementName = ArrayHelper::get($_REQUEST, 'element_name');

        if (!$formId || !$elementName) {
            $this->sendError('Wrong file upload instance', 'no element found');
        }

        $element = $this->getFileUploadElement($formId, $elementName);
        $fieldOptions = ArrayHelper::get($element, 'field_options');
        if (!$element || !$fieldOptions) {
            $this->sendError('Element not found', 'no element found');
        }

        $uploadFile = $_FILES['file'];
        // We have to validate the uploaded file now
        $file = $this->handleUploadFile($uploadFile, $element, $formId);

        if (!empty($file['error'])) {
            $this->sendError($file['error'], $file);
        }

        $file['original_name'] = sanitize_text_field($uploadFile['name']);
        update_option('wpjobboard_upload_dir', dirname($file['url']));

        wp_send_json($file, 200);
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
            $errorMessage = 'Validation Failed';
            if (is_array($errors)) {
                $errorMessage .= '<ul>';
                foreach ($errors as $error) {
                    $errorMessage .= '<li>' . $error . '</li>';
                }
                $errorMessage .= '</ul>';
            }
            $this->sendError($errorMessage, $errors);
        }
        return $fileHandler->upload();
    }

    private function sendError($message, $error = false, $code = 423)
    {
        wp_send_json_error(array(
            'message' => $message,
            'error'   => $error,
            'ok'      => 'ine'
        ), 423);
    }

    private function getPreviewUrl($file)
    {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $imageExtensions = array('jpg', 'jpeg', 'gif', 'png', 'bmp');
        if (in_array($ext, $imageExtensions)) {
            return $file;
        }

        if ($ext == 'pdf') {
            return WPJOBBOARD_URL . '/assets/images/pdf_icon.png';
        }

        // Return normal Document Extension
        return WPJOBBOARD_URL . '/assets/images/document.png';
    }
}
