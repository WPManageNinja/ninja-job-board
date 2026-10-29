<?php

namespace NinjaJobBoard\Tests\Integration;

use NinjaJobBoard\Tests\IntegrationTestCase;

/**
 * Gutenberg blocks (rendered in PHP), the job fields in the block editor, and the rule that a job
 * page does not show the form or the details twice.
 */
class BlocksTest extends IntegrationTestCase
{
    public function test_the_three_blocks_are_registered_with_a_php_render()
    {
        $registry = \WP_Block_Type_Registry::get_instance();
        foreach (array('ninja-job-board/job-list', 'ninja-job-board/application-form', 'ninja-job-board/job-details') as $name) {
            $this->assertTrue($registry->is_registered($name), $name . ' is not registered');
            $this->assertTrue($registry->get_registered($name)->is_dynamic(), $name . ' must render in PHP');
        }
    }

    public function test_job_list_shows_only_the_chosen_categories()
    {
        $engineering = $this->makeJob('Backend Engineer');
        $design = $this->makeJob('Product Designer');
        wp_set_object_terms($engineering, 'engineering', 'wpjb-job-categories');
        wp_set_object_terms($design, 'design', 'wpjb-job-categories');

        $html = $this->renderBlock('ninja-job-board/job-list', array('categories' => array('engineering'), 'showFilter' => false));

        $this->assertStringContainsString('Backend Engineer', $html);
        $this->assertStringNotContainsString('Product Designer', $html);
        $this->assertStringNotContainsString('wpjb_job_filter', $html, 'The filter was shown although it is turned off');
    }

    public function test_job_list_escapes_its_title()
    {
        $this->makeJob();
        $html = $this->renderBlock('ninja-job-board/job-list', array('title' => '<script>alert(1)</script>Careers'));

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('Careers', $html);
    }

    public function test_application_form_renders_the_chosen_published_job_only()
    {
        $published = $this->makeJob('Open role');
        $draft = $this->makeJob('Draft role');
        wp_update_post(array('ID' => $draft, 'post_status' => 'draft'));

        $this->assertStringContainsString('wpjb_form_id_' . $published, $this->renderBlock('ninja-job-board/application-form', array('jobId' => $published)));
        $this->assertSame('', $this->renderBlock('ninja-job-board/application-form', array('jobId' => $draft)), 'A draft job form was shown to visitors');
        $this->assertSame('', $this->renderBlock('ninja-job-board/application-form', array('jobId' => 0)), 'A form without a job was shown');
    }

    public function test_job_details_shows_only_the_chosen_fields()
    {
        $jobId = $this->makeJob();
        update_post_meta($jobId, 'job_location', 'Berlin');
        update_post_meta($jobId, 'job_experince_text', '2+ years');

        $html = $this->renderBlock('ninja-job-board/job-details', array('jobId' => $jobId, 'fields' => array('location')));

        $this->assertStringContainsString('Berlin', $html);
        $this->assertStringNotContainsString('2+ years', $html);
    }

    public function test_job_page_adds_the_form_and_details_only_when_the_post_does_not_place_them()
    {
        $plain = $this->makeJob('Plain job');
        update_post_meta($plain, 'job_location', 'Berlin');
        $this->assertSame(1, $this->countIn($this->jobPageContent($plain), 'id="wpjb_form_id_' . $plain . '"'), 'The automatic form is missing');

        $withBlocks = $this->makeJob('Block job');
        update_post_meta($withBlocks, 'job_location', 'Berlin');
        wp_update_post(array('ID' => $withBlocks, 'post_content' => '<!-- wp:ninja-job-board/job-details /--><!-- wp:ninja-job-board/application-form /-->'));

        $content = $this->jobPageContent($withBlocks);
        $this->assertSame(1, $this->countIn($content, 'id="wpjb_form_id_' . $withBlocks . '"'), 'The form is shown twice');
        $this->assertSame(1, $this->countIn($content, 'class="wpjb_job_meta"'), 'The job details are shown twice');
    }

    public function test_job_fields_are_sanitized_and_only_editors_of_the_job_can_change_them()
    {
        $jobId = $this->makeJob();

        $this->actAsAdmin();
        $response = $this->coreRest('POST', '/wp/v2/wp_job_board/' . $jobId, array('meta' => array(
            'job_type'      => 'astronaut',
            'job_vacancies' => '3 people',
            'job_location'  => '<b>Berlin</b>'
        )));
        $this->assertSame(200, $response->get_status());
        $this->assertSame('', get_post_meta($jobId, 'job_type', true), 'An unknown job type was stored');
        $this->assertSame('3', get_post_meta($jobId, 'job_vacancies', true));
        $this->assertSame('Berlin', get_post_meta($jobId, 'job_location', true));

        $this->actAs($this->makeUser('subscriber'));
        $response = $this->coreRest('POST', '/wp/v2/wp_job_board/' . $jobId, array('meta' => array('job_location' => 'Hacked')));
        $this->assertContains($response->get_status(), array(401, 403));
        $this->assertSame('Berlin', get_post_meta($jobId, 'job_location', true));
    }

    // ─── Helpers ───────────────────────────────────────────────────────────

    private function renderBlock($name, array $attributes)
    {
        return render_block(array('blockName' => $name, 'attrs' => $attributes, 'innerBlocks' => array(), 'innerHTML' => '', 'innerContent' => array()));
    }

    /**
     * The content of a job page as the theme would print it
     */
    private function jobPageContent($jobId)
    {
        global $post, $wp_query;
        $previousPost = $post;
        $previousQuery = $wp_query;

        $wp_query = new \WP_Query(array('p' => $jobId, 'post_type' => 'wp_job_board'));
        $wp_query->the_post();
        $content = apply_filters('the_content', get_post($jobId)->post_content);

        wp_reset_postdata();
        $wp_query = $previousQuery;
        $post = $previousPost;
        return $content;
    }

    private function coreRest($method, $route, array $params)
    {
        $request = new \WP_REST_Request($method, $route);
        foreach ($params as $key => $value) {
            $request->set_param($key, $value);
        }
        return rest_do_request($request);
    }

    private function countIn($haystack, $needle)
    {
        return substr_count($haystack, $needle);
    }
}
