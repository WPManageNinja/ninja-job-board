<?php

namespace NinjaJobBoard\Tests\Integration;

use NinjaJobBoard\Tests\IntegrationTestCase;
use WPJobBoard\Classes\Entry\MetaData;

class PublicInputTest extends IntegrationTestCase
{
    public function test_an_upload_is_rejected_after_the_application_period_ends()
    {
        $formId = $this->makeJob();
        update_post_meta($formId, 'wpjb_form_scheduling_settings', array(
            'scheduleForm' => array('status' => 'yes', 'start_date' => '2000-01-01 00:00:00', 'end_date' => '2001-01-01 00:00:00', 'expire_message' => '')
        ));

        $result = $this->upload($formId);

        $this->assertSame(403, $result['status'], $result['raw']);
        $this->assertSame('restricted', $result['body']['data']['error']);
    }

    public function test_an_upload_needs_a_login_when_the_job_requires_one()
    {
        $formId = $this->makeJob();
        update_post_meta($formId, 'wpjb_form_scheduling_settings', array(
            'requireLogin' => array('status' => 'yes', 'message' => '')
        ));

        $this->assertSame('restricted', $this->upload($formId)['body']['data']['error']);

        wp_set_current_user($this->makeUser('subscriber'));
        $this->assertSame('no file', $this->upload($formId)['body']['data']['error']);
    }

    public function test_an_open_job_passes_the_restriction_check()
    {
        $formId = $this->makeJob();

        $this->assertSame('no file', $this->upload($formId)['body']['data']['error']);
    }

    public function test_the_page_id_is_used_only_for_a_public_page_that_embeds_this_job()
    {
        $formId = $this->makeJob();

        $shortcodePage = $this->makePage('[wp_job_form id="' . $formId . '"]');
        $blockPage = $this->makePage('<!-- wp:ninja-job-board/application-form {"jobId":' . $formId . '} /-->');

        $this->assertSame($shortcodePage, MetaData::resolvePostId($shortcodePage, $formId));
        $this->assertSame($blockPage, MetaData::resolvePostId((string)$blockPage, $formId));
        $this->assertSame($formId, MetaData::resolvePostId(0, $formId));
    }

    public function test_any_other_page_id_falls_back_to_the_job()
    {
        $formId = $this->makeJob();
        $otherJob = $this->makeJob('Other job');

        $cases = array(
            'private page'       => $this->makePage('[wp_job_form id="' . $formId . '"]', 'private'),
            'draft page'         => $this->makePage('[wp_job_form id="' . $formId . '"]', 'draft'),
            'page without form'  => $this->makePage('Hello'),
            'another job\'s form' => $this->makePage('[wp_job_form id="' . $otherJob . '"]'),
            'missing post'       => 999999999
        );

        foreach ($cases as $label => $pageId) {
            $this->assertSame($formId, MetaData::resolvePostId($pageId, $formId), $label);
        }
    }

    private function makePage($content, $status = 'publish')
    {
        return wp_insert_post(array(
            'post_type'    => 'page',
            'post_status'  => $status,
            'post_title'   => 'Careers',
            'post_content' => $content
        ));
    }

    private function upload($formId)
    {
        $_REQUEST = array(
            'action'       => 'wpjb_file_upload_process',
            'form_id'      => $formId,
            'element_name' => 'file_upload_input',
            'wpjb_nonce'   => wp_create_nonce('wpjobboard_public_nonce')
        );
        $_FILES = array();

        $result = $this->captureAjax(function () {
            do_action(is_user_logged_in() ? 'wp_ajax_wpjb_file_upload_process' : 'wp_ajax_nopriv_wpjb_file_upload_process');
        });
        $_REQUEST = array();

        return $result;
    }
}
