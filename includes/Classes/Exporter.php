<?php

namespace WPJobBoard\Classes;

use WPJobBoard\Classes\Models\Submission;

if (!defined('ABSPATH')) {
    exit;
}

class Exporter
{
    public function registerEndpoints()
    {
        add_action('wp_ajax_wpjb_export_endpoints', [$this, 'routeAjaxMaps']);
    }

    public function routeAjaxMaps()
    {
        $routes = [
            'export_data' => 'export',
        ];

        $route = sanitize_text_field($_REQUEST['route']);

        if (isset($routes[$route])) {
            AccessControl::checkAndPresponseError($route, 'submissions');

            do_action('wpjobboard/doing_ajax_submissions_' . $route);

            $this->{$routes[$route]}();

            return;
        }
    }

    public function export()
    {
        $formId = absint($_REQUEST['form_id']);

        $submissionModel = new Submission();

        $wheres = [];

        if (isset($_REQUEST['application_status']) && $_REQUEST['application_status']) {
            $wheres['application_status'] = sanitize_text_field($_REQUEST['application_status']);
        }

        if (isset($_REQUEST['status']) && $_REQUEST['status']) {
            $wheres['status'] = sanitize_text_field($_REQUEST['status']);
        }

        $submissions = $submissionModel->getSubmissions($formId, $wheres)->items;

        $formElements = get_post_meta($formId, 'wpjobboard_application_builder_settings', true);

        $entries = [];
        $headers = [];

        foreach ($submissions as $submission) {
            $inputValues = $submission->form_data_formatted;
            $parsedSubmission = [];

            foreach ($formElements as $element) {
                if ('input' == $element['group']) {
                    $elementId = ArrayHelper::get($element, 'id');

                    $headers[$elementId] = $submissionModel->getLabel($element);

                    $elementValue = apply_filters(
                        'wpjobboard/exporting_entry_value_' . $element['type'],
                        ArrayHelper::get($inputValues, $elementId),
                        $submission,
                        $element
                    );

                    if (is_array($elementValue)) {
                        $elementValue = implode(', ', $elementValue);
                    }

                    $parsedSubmission[$elementId] = $elementValue;
                }
            }

            $entries[] = apply_filters('wpjobboard/parsed_export_entry', $parsedSubmission, $submission);
        }

        // Merge headers with the entries to the top.
        array_unshift($entries, $headers);

        require_once WPJOBBOARD_DIR . 'includes/libs/Spout/Autoloader/autoload.php';

        $type = sanitize_text_field($_REQUEST['type']);
        $fileName = null;

        $writer = \Box\Spout\Writer\WriterFactory::create($type);

        $fileName = ($fileName) ? $fileName . '.' . $type : 'export-data-' . date('d-m-Y') . '.' . $type;
        $writer = \Box\Spout\Writer\WriterFactory::create($type);
        $writer->openToBrowser($fileName);
        $writer->addRows($entries);
        $writer->close();
        exit();
    }
}
