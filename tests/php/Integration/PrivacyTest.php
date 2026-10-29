<?php

namespace NinjaJobBoard\Tests\Integration;

use NinjaJobBoard\Tests\IntegrationTestCase;

/**
 * WordPress privacy tools (Tools > Export/Erase Personal Data) and IP logging.
 */
class PrivacyTest extends IntegrationTestCase
{
    public function test_exporter_returns_the_applications_of_that_email_only()
    {
        $formId = $this->makeJob('Data Analyst');
        $file = $this->makeUploadedFile('ada-cv.pdf');
        $this->makeApplication($formId, array('applicant_email' => 'ada@example.com', 'file_upload_input' => array($file['url'])), array('ip_address' => '203.0.113.7'));
        $this->makeApplication($formId, array('applicant_name' => 'Bob Other', 'applicant_email' => 'bob@example.com'));

        $result = $this->exporter()('ada@example.com', 1);

        $this->assertTrue($result['done']);
        $this->assertCount(1, $result['data']);
        $values = wp_list_pluck($result['data'][0]['data'], 'value', 'name');
        $this->assertSame('Data Analyst', $values['Job']);
        $this->assertSame('ada@example.com', $values['Email']);
        $this->assertSame('203.0.113.7', $values['IP address']);
        $this->assertSame('ada-cv.pdf', $values['Attach Your CV'], 'Files are listed by their original name');
        $this->assertStringNotContainsString('Bob Other', wp_json_encode($result));
    }

    public function test_eraser_deletes_the_applications_their_activity_and_their_files()
    {
        $formId = $this->makeJob();
        $file = $this->makeUploadedFile('ada-cv.pdf');
        $adaId = $this->makeApplication($formId, array('applicant_email' => 'ada@example.com', 'file_upload_input' => array($file['url'])));
        $bobId = $this->makeApplication($formId, array('applicant_email' => 'bob@example.com'));
        $this->wpdb->insert($this->wpdb->prefix . 'wjb_application_activities', array(
            'form_id'       => $formId,
            'submission_id' => $adaId,
            'type'          => 'custom_note',
            'content'       => 'Interviewed',
            'created_at'    => gmdate('Y-m-d H:i:s'),
            'updated_at'    => gmdate('Y-m-d H:i:s')
        ));

        $result = $this->eraser()('ada@example.com', 1);

        $this->assertTrue($result['items_removed']);
        $this->assertTrue($result['done']);
        $this->assertSame(0, $this->countWhere('wjb_applications', 'id', $adaId));
        $this->assertSame(0, $this->countWhere('wjb_application_activities', 'submission_id', $adaId));
        $this->assertFileDoesNotExist($file['path']);
        $this->assertSame(1, $this->countWhere('wjb_applications', 'id', $bobId), 'Another applicant was erased');
    }

    public function test_deleting_an_application_from_the_admin_deletes_its_file()
    {
        $formId = $this->makeJob();
        $file = $this->makeUploadedFile('ada-cv.pdf');
        $applicationId = $this->makeApplication($formId, array('file_upload_input' => array($file['url'])));

        $this->actAsAdmin();
        $this->assertSame(200, $this->rest('DELETE', '/applications/' . $applicationId)->get_status());

        $this->assertFileDoesNotExist($file['path']);
    }

    public function test_ip_address_is_not_stored_when_ip_logging_is_off()
    {
        $formId = $this->makeJob();

        update_option('wpjobboard_ip_logging_status', 'no');
        $off = $this->submitApplication($formId, array());
        $this->assertSame(200, $off['status'], $off['raw']);
        $this->assertEmpty($off['row']['ip_address']);

        update_option('wpjobboard_ip_logging_status', 'yes');
        $on = $this->submitApplication($formId, array());
        $this->assertSame('127.0.0.1', $on['row']['ip_address']);
    }

    private function exporter()
    {
        $exporters = apply_filters('wp_privacy_personal_data_exporters', array());
        $this->assertArrayHasKey('ninja-job-board', $exporters, 'The exporter is not registered');
        return $exporters['ninja-job-board']['callback'];
    }

    private function eraser()
    {
        $erasers = apply_filters('wp_privacy_personal_data_erasers', array());
        $this->assertArrayHasKey('ninja-job-board', $erasers, 'The eraser is not registered');
        return $erasers['ninja-job-board']['callback'];
    }

    private function countWhere($table, $column, $value)
    {
        return (int)$this->wpdb->get_var($this->wpdb->prepare('SELECT COUNT(*) FROM ' . $this->wpdb->prefix . $table . ' WHERE ' . $column . ' = %d', $value));
    }
}
