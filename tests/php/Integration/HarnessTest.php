<?php

namespace NinjaJobBoard\Tests\Integration;

use NinjaJobBoard\Tests\IntegrationTestCase;

class HarnessTest extends IntegrationTestCase
{
    private static $jobIdSeen;

    public function test_writes_are_visible_inside_the_test()
    {
        $formId = $this->makeJob('Harness job');
        $applicationId = $this->makeApplication($formId);

        self::$jobIdSeen = $formId;
        $this->assertSame('Harness job', get_post($formId)->post_title);
        $this->assertSame($formId, (int)$this->wpdb->get_var($this->wpdb->prepare('SELECT form_id FROM ' . $this->wpdb->prefix . 'wjb_applications WHERE id = %d', $applicationId)));
    }

    /**
     * @depends test_writes_are_visible_inside_the_test
     */
    public function test_writes_are_rolled_back_after_the_test()
    {
        $this->assertNull(get_post(self::$jobIdSeen), 'The job from the previous test survived the rollback');
    }

    public function test_outbound_mail_and_http_are_blocked()
    {
        wp_mail('someone@example.com', 'Subject', 'Body');
        $response = wp_remote_get('https://example.com/');

        $this->assertSame(1, $this->blockedMail);
        $this->assertSame(1, $this->blockedHttp);
        $this->assertWPError($response);
    }

    private function assertWPError($value)
    {
        $this->assertInstanceOf(\WP_Error::class, $value);
    }
}
