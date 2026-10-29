<?php

namespace NinjaJobBoard\Tests\Integration;

use NinjaJobBoard\Tests\IntegrationTestCase;
use WPJobBoard\Classes\File\FileHandler;

/**
 * The reported vulnerability (WordPress.org, 2026): unauthenticated stored XSS through file upload
 * values, rendered as HTML in the admin application view. Plus the same class for text fields.
 */
class StoredXssTest extends IntegrationTestCase
{
    // The exact payload from the report
    private const REPORT_PAYLOAD = 'x" onerror="alert(document.domain)//.png';

    public function test_the_reported_payload_is_not_accepted_as_an_uploaded_file()
    {
        $formId = $this->makeJob();
        $stored = $this->submit($formId, array('__file_upload_input_files[]' => self::REPORT_PAYLOAD));

        $this->assertSame(array(), $stored['form_data_formatted']['file_upload_input']);
    }

    public function test_a_real_upload_is_accepted_once_and_only_for_its_own_form()
    {
        $formId = $this->makeJob();
        $otherFormId = $this->makeJob('Other job');

        $file = $this->makeUploadedFile();
        set_transient(FileHandler::uploadRecordKey($file['name']), $otherFormId . ':file_upload_input', 60);
        $stored = $this->submit($formId, array('__file_upload_input_files[]' => $file['name']));
        $this->assertSame(array(), $stored['form_data_formatted']['file_upload_input'], 'A file uploaded for another form was accepted');

        set_transient(FileHandler::uploadRecordKey($file['name']), $formId . ':file_upload_input', 60);
        $stored = $this->submit($formId, array('__file_upload_input_files[]' => $file['name']));
        $this->assertSame(array($file['url']), $stored['form_data_formatted']['file_upload_input']);

        // The upload record is single use: the same file can not be attached again
        $stored = $this->submit($formId, array('__file_upload_input_files[]' => $file['name']));
        $this->assertSame(array(), $stored['form_data_formatted']['file_upload_input']);
    }

    public function test_a_payload_already_stored_by_an_old_version_does_not_render()
    {
        $formId = $this->makeJob();
        $uploadUrl = FileHandler::getUploadDir()['url'];
        $applicationId = $this->makeApplication($formId, array(
            'file_upload_input' => array($uploadUrl . '/' . self::REPORT_PAYLOAD, 'javascript:alert(1)')
        ));

        $html = $this->renderedValue($applicationId, 'file_upload_input');

        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function test_a_valid_file_renders_as_a_protected_download_link()
    {
        $formId = $this->makeJob();
        $file = $this->makeUploadedFile('ada-cv.pdf');
        $applicationId = $this->makeApplication($formId, array('file_upload_input' => array($file['url'])));

        $html = $this->renderedValue($applicationId, 'file_upload_input');

        $this->assertStringContainsString('admin-post.php?action=wpjb_download_file', $html);
        $this->assertStringContainsString('ada-cv.pdf', $html);
        // The public upload URL must not be exposed
        $this->assertStringNotContainsString($file['url'], $html);
    }

    public function test_html_in_text_fields_is_stored_as_text_and_escaped_on_display()
    {
        $formId = $this->makeJob();
        $stored = $this->submit($formId, array('applicant_name' => 'Mallory <img src=x onerror=alert(1)>'));

        $this->assertStringNotContainsString('<img', $stored['applicant_name']);
        $this->assertStringNotContainsString('<img', $stored['form_data_formatted']['applicant_name']);

        // Rows written before 1.5.0 can still hold raw HTML: the view escapes it
        $legacyId = $this->makeApplication($formId, array('applicant_name' => '<img src=x onerror=alert(1)>'));
        $html = $this->renderedValue($legacyId, 'applicant_name');
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('&lt;img', $html);
    }

    private function submit($formId, array $fields)
    {
        $result = $this->submitApplication($formId, $fields);
        $this->assertSame(200, $result['status'], 'Submission failed: ' . $result['raw']);
        return $result['row'];
    }

    /**
     * The HTML the admin application view receives for one field (rendered with v-html)
     */
    private function renderedValue($applicationId, $fieldId)
    {
        $this->actAsAdmin();
        $response = $this->rest('GET', '/applications/' . $applicationId);
        $this->assertSame(200, $response->get_status());
        $entry = (array)$response->get_data()['entry'];
        $this->assertArrayHasKey($fieldId, $entry, 'The field is missing from the parsed entry');
        return $entry[$fieldId]['value'];
    }
}
