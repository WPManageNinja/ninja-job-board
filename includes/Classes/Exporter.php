<?php

namespace WPJobBoard\Classes;

use WPJobBoard\Classes\Models\Submission;

if (!defined('ABSPATH')) {
    exit;
}

class Exporter
{
    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- every route passes AccessControl::checkAndPresponseError() first, which verifies the nonce

    public function registerEndpoints()
    {
        add_action('wp_ajax_wpjb_export_endpoints', [$this, 'routeAjaxMaps']);
    }

    public function routeAjaxMaps()
    {
        $routes = [
            'export_data' => 'export',
        ];

        $route = isset($_REQUEST['route']) ? sanitize_text_field(wp_unslash($_REQUEST['route'])) : '';

        if (isset($routes[$route])) {
            AccessControl::checkAndPresponseError($route, 'submissions');

            do_action('wpjobboard/doing_ajax_submissions_' . $route);

            $this->{$routes[$route]}();

            return;
        }
    }

    public function export()
    {
        $formId = isset($_REQUEST['form_id']) ? absint($_REQUEST['form_id']) : 0;

        if (!$formId || !Models\Forms::getForm($formId)) {
            wp_die(esc_html__('Job post not found', 'ninja-job-board'));
        }

        $type = isset($_REQUEST['type']) ? sanitize_key(wp_unslash($_REQUEST['type'])) : 'csv';
        if ($type !== 'csv') {
            wp_die(esc_html__('Invalid export type', 'ninja-job-board'));
        }

        $submissionModel = new Submission();

        $wheres = [];

        $applicationStatus = isset($_REQUEST['application_status']) ? sanitize_text_field(wp_unslash($_REQUEST['application_status'])) : '';
        if ($applicationStatus && isset(GeneralSettings::getApplicationStatuses()[$applicationStatus])) {
            $wheres['application_status'] = $applicationStatus;
        }

        $status = isset($_REQUEST['status']) ? sanitize_text_field(wp_unslash($_REQUEST['status'])) : '';
        if ($status && isset(GeneralSettings::getInternalStatuses()[$status])) {
            $wheres['status'] = $status;
        }

        $submissions = $submissionModel->getSubmissions($formId, $wheres)->items;

        $formElements = get_post_meta($formId, 'wpjobboard_application_builder_settings', true);
        if (!is_array($formElements)) {
            $formElements = [];
        }

        $entries = [];
        $headers = [];

        foreach ($submissions as $submission) {
            $inputValues = $submission->form_data_formatted;
            $parsedSubmission = [];

            foreach ($formElements as $element) {
                if ('input' == $element['group']) {
                    $elementId = ArrayHelper::get($element, 'id');

                    $headers[$elementId] = $this->safeCell($submissionModel->getLabel($element));

                    $elementValue = apply_filters(
                        'wpjobboard/exporting_entry_value_' . $element['type'],
                        ArrayHelper::get($inputValues, $elementId),
                        $submission,
                        $element
                    );

                    if (is_array($elementValue)) {
                        $elementValue = implode(', ', array_filter($elementValue, 'is_scalar'));
                    }

                    $parsedSubmission[$elementId] = $this->safeCell($elementValue);
                }
            }

            $entries[] = apply_filters('wpjobboard/parsed_export_entry', $parsedSubmission, $submission);
        }

        // Merge headers with the entries to the top.
        array_unshift($entries, $headers);

        $fileName = 'export-data-' . gmdate('d-m-Y') . '.csv';

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');

        echo $this->toCsv($entries); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV file download, not HTML; every cell is cleaned in safeCell() and quoted in toCsv()
        exit();
    }

    /**
     * Build RFC 4180 CSV text
     * @param array $rows
     * @return string
     */
    private function toCsv($rows)
    {
        $lines = array();
        foreach ($rows as $row) {
            $cells = array();
            foreach ((array)$row as $cell) {
                $cells[] = '"' . str_replace('"', '""', (string)$cell) . '"';
            }
            $lines[] = implode(',', $cells);
        }
        // UTF-8 BOM so spreadsheet apps detect the encoding
        return "\xEF\xBB\xBF" . implode("\r\n", $lines) . "\r\n";
    }

    /**
     * Plain-text cell value. A leading quote stops spreadsheet apps from running it as a formula.
     * @param mixed $value
     * @return string
     */
    private function safeCell($value)
    {
        $value = is_scalar($value) ? wp_strip_all_tags((string)$value) : '';
        if ($value !== '' && in_array($value[0], array('=', '+', '-', '@', "\t", "\r"), true)) {
            $value = "'" . $value;
        }
        return $value;
    }
}
