<?php

namespace NinjaJobBoard\Tests\Integration;

use NinjaJobBoard\Tests\IntegrationTestCase;
use WPJobBoard\Classes\SpamProtection;

/**
 * Honeypot (always on) and Cloudflare Turnstile (optional) on the public form.
 * Cloudflare's siteverify API is stubbed at the HTTP layer (outbound third party).
 */
class SpamProtectionTest extends IntegrationTestCase
{
    public function test_a_filled_honeypot_is_rejected_and_nothing_is_stored()
    {
        $formId = $this->makeJob();
        $before = $this->applicationCount($formId);

        $result = $this->submitApplication($formId, array(SpamProtection::HONEYPOT_FIELD => 'http://spam.example'));

        $this->assertSame(423, $result['status']);
        $this->assertSame($before, $this->applicationCount($formId));
    }

    public function test_an_empty_honeypot_passes_and_is_not_stored_with_the_application()
    {
        $formId = $this->makeJob();
        $result = $this->submitApplication($formId, array(SpamProtection::HONEYPOT_FIELD => ''));

        $this->assertSame(200, $result['status'], $result['raw']);
        $this->assertArrayNotHasKey(SpamProtection::HONEYPOT_FIELD, (array)$result['row']['form_data_raw']);
    }

    public function test_turnstile_rejects_a_submission_without_a_token()
    {
        $this->enableTurnstile();
        $formId = $this->makeJob();

        $result = $this->submitApplication($formId, array());

        $this->assertSame(423, $result['status']);
        $this->assertSame(0, $this->verifyCalls);
    }

    public function test_turnstile_rejects_a_token_that_cloudflare_does_not_confirm()
    {
        $this->enableTurnstile();
        $this->cloudflareAnswers(array('success' => false, 'error-codes' => array('invalid-input-response')));
        $formId = $this->makeJob();

        $result = $this->submitApplication($formId, array('cf-turnstile-response' => 'forged'));

        $this->assertSame(423, $result['status']);
        $this->assertSame(1, $this->verifyCalls);
    }

    public function test_turnstile_rejects_when_cloudflare_can_not_be_reached()
    {
        $this->enableTurnstile();
        $this->httpResponder = function () {
            $this->verifyCalls++;
            return new \WP_Error('http_request_failed', 'timeout');
        };
        $formId = $this->makeJob();

        $result = $this->submitApplication($formId, array('cf-turnstile-response' => 'token'));

        $this->assertSame(423, $result['status'], 'A network error must not let the submission through');
    }

    public function test_turnstile_accepts_a_confirmed_token_and_sends_the_secret_server_side()
    {
        $this->enableTurnstile();
        $this->cloudflareAnswers(array('success' => true));
        $formId = $this->makeJob();

        $result = $this->submitApplication($formId, array('cf-turnstile-response' => 'good-token'));

        $this->assertSame(200, $result['status'], $result['raw']);
        $this->assertSame('secret-key', $this->lastVerifyBody['secret']);
        $this->assertSame('good-token', $this->lastVerifyBody['response']);
        $this->assertArrayNotHasKey('cf-turnstile-response', (array)$result['row']['form_data_raw']);
    }

    public function test_settings_api_never_returns_the_secret_and_keeps_it_when_left_empty()
    {
        $this->actAsAdmin();

        $this->assertSame(422, $this->rest('POST', '/settings', array('turnstile_enabled' => 'yes'))->get_status(), 'Turning Turnstile on without keys must be refused');

        $this->assertSame(200, $this->rest('POST', '/settings', array(
            'turnstile_enabled'    => 'yes',
            'turnstile_site_key'   => 'site-key',
            'turnstile_secret_key' => 'secret-key'
        ))->get_status());
        $this->assertSame(200, $this->rest('POST', '/settings', array(
            'turnstile_enabled'    => 'yes',
            'turnstile_site_key'   => 'site-key',
            'turnstile_secret_key' => ''
        ))->get_status());

        $this->assertSame('secret-key', SpamProtection::getSettings()['turnstile_secret_key'], 'An empty field must keep the saved secret');

        $shown = $this->rest('GET', '/settings')->get_data();
        $this->assertStringNotContainsString('secret-key', wp_json_encode($shown));
        $this->assertTrue($shown['turnstile_secret_saved']);
    }

    // ─── Helpers ───────────────────────────────────────────────────────────

    private $verifyCalls = 0;
    private $lastVerifyBody = array();

    private function enableTurnstile()
    {
        update_option(SpamProtection::OPTION, array(
            'turnstile_enabled'    => 'yes',
            'turnstile_site_key'   => 'site-key',
            'turnstile_secret_key' => 'secret-key'
        ));
    }

    private function cloudflareAnswers(array $json)
    {
        $this->httpResponder = function ($args, $url) use ($json) {
            $this->assertSame(SpamProtection::SITEVERIFY_URL, $url);
            $this->verifyCalls++;
            $this->lastVerifyBody = (array)$args['body'];
            return array(
                'headers'  => array(),
                'body'     => wp_json_encode($json),
                'response' => array('code' => 200, 'message' => 'OK'),
                'cookies'  => array(),
                'filename' => null
            );
        };
    }

    private function applicationCount($formId)
    {
        return (int)$this->wpdb->get_var($this->wpdb->prepare('SELECT COUNT(*) FROM ' . $this->wpdb->prefix . 'wjb_applications WHERE form_id = %d', $formId));
    }
}
