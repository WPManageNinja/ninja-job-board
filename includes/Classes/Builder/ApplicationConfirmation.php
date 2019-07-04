<?php
namespace WPJobBoard\Classes\Builder;
use WPJobBoard\Classes\Models\Submission;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Recept Shortcode Handler
 * @since 1.0.0
 */
class ApplicationConfirmation
{
    public function render($submissionId)
    {
        $submissionModel = new Submission();
        $submission = $submissionModel->getSubmission($submissionId, array());
        $submission->parsedData = $submissionModel->getParsedSubmission($submission);
        $html = $this->applicationDetails($submission);
        return $html;
    }


    private function applicationDetails($submission)
    {
        $preRender = apply_filters('wpjobboard/application_confirmation/pre_render_confirmation_details', '', $submission);
        if ($preRender) {
            return $preRender;
        }
        return $this->loadView('elements/input_fields_html', array(
            'submission' => $submission,
            'items' => $submission->parsedData,
            'load_css' => true
        ));
    }

    public function loadView($fileName, $data)
    {
        // normalize the filename
        $fileName = str_replace(array('../', './'), '', $fileName);
        $basePath = apply_filters('wpjobboard/template_base_path', WPJOBBOARD_DIR . 'includes/views/', $fileName, $data);
        $filePath = $basePath . $fileName . '.php';
        extract($data);
        ob_start();
        include $filePath;
        return ob_get_clean();
    }
}