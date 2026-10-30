<?php

namespace NinjaJobBoard\Tests\Integration;

use NinjaJobBoard\Tests\IntegrationTestCase;
use WPJobBoard\Classes\Browser;
use WPJobBoard\Classes\Emogrifier\Emogrifier;
use WPJobBoard\Classes\View;

/**
 * The code that replaced or updated third-party libraries in 2.0.0: the browser and platform
 * stored with an application (Browser), the CSS inliner for emails, and the date picker locale.
 */
class LibrariesTest extends IntegrationTestCase
{
    /**
     * @dataProvider userAgents
     */
    public function test_browser_and_platform_are_read_from_current_user_agents($userAgent, $browser, $platform)
    {
        $client = new Browser($userAgent);

        $this->assertSame($browser, $client->getBrowser());
        $this->assertSame($platform, $client->getPlatform());
    }

    public function userAgents()
    {
        return array(
            'Chrome on Windows'  => array('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Chrome', 'Windows'),
            'Edge on Windows'    => array('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'Edge', 'Windows'),
            'Opera on Windows'   => array('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', 'Opera', 'Windows'),
            'Safari on macOS'    => array('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0 Safari/605.1.15', 'Safari', 'macOS'),
            'Firefox on Linux'   => array('Mozilla/5.0 (X11; Linux x86_64; rv:144.0) Gecko/20100101 Firefox/144.0', 'Firefox', 'Linux'),
            'Safari on iPhone'   => array('Mozilla/5.0 (iPhone; CPU iPhone OS 18_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Mobile/15E148 Safari/604.1', 'Safari', 'iOS'),
            'Chrome on iPad'     => array('Mozilla/5.0 (iPad; CPU OS 18_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/141.0.0.0 Mobile/15E148 Safari/604.1', 'Chrome', 'iPadOS'),
            'Samsung on Android' => array('Mozilla/5.0 (Linux; Android 15; SM-S928B) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Mobile Safari/537.36', 'Samsung Internet', 'Android'),
            'Chrome on ChromeOS' => array('Mozilla/5.0 (X11; CrOS x86_64 16328.65.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Chrome', 'ChromeOS'),
            'Not a browser'      => array('curl/8.7.1', 'Unknown', 'Unknown'),
            'Empty'              => array('', 'Unknown', 'Unknown')
        );
    }

    public function test_an_application_stores_the_browser_and_platform()
    {
        $formId = $this->makeJob();
        $previous = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0 Safari/605.1.15';

        try {
            $result = $this->submitApplication($formId, array());
        } finally {
            if ($previous === null) {
                unset($_SERVER['HTTP_USER_AGENT']);
            } else {
                $_SERVER['HTTP_USER_AGENT'] = $previous;
            }
        }

        $this->assertSame(200, $result['status'], $result['raw']);
        $this->assertSame('Safari', $result['row']['browser']);
        $this->assertSame('macOS', $result['row']['device']);
    }

    public function test_email_css_is_inlined_and_media_queries_are_kept()
    {
        $css = View::make('email.default.styles') . "\n@media only screen and (max-width: 620px) { p { font-size: 16px !important; } }";
        $html = '<html><head></head><body><p class="intro">Hello Zoë, 你好 &amp; welcome</p></body></html>';

        $result = (new Emogrifier($html, $css))->emogrify();

        $this->assertMatchesRegularExpression('/<body style="[^"]*background-color: #f6f6f6/', $result, 'The default email CSS was not inlined');
        $this->assertStringContainsString('@media only screen and (max-width: 620px)', $result, 'The @media rule was dropped');
        $this->assertStringContainsString('Zoë', $result);
        $this->assertStringContainsString('你好', $result);
        $this->assertStringContainsString('&amp; welcome', $result);
    }

    public function test_style_tags_in_the_email_body_are_inlined_too()
    {
        $html = '<html><head><style>.cta { color: #335CFF; }</style></head><body><a class="cta" href="#">Apply</a></body></html>';

        $result = (new Emogrifier($html))->emogrify();

        $this->assertMatchesRegularExpression('/<a class="cta" href="#" style="color: #335CFF;"/', $result);
    }

    public function test_the_date_picker_locale_has_every_list_flatpickr_reads()
    {
        $formId = $this->makeJob();
        (new \WPJobBoard\Classes\Builder\Render())->render($formId, false);

        $data = wp_scripts()->get_data('wpjobboard_public', 'data');
        $this->assertNotEmpty($data, 'The public form settings were not printed');
        $this->assertMatchesRegularExpression('/var wp_job_board_general = (\{.*?\});\n?/s', $data);
        preg_match('/var wp_job_board_general = (\{.*?\});(\n|$)/s', $data, $match);
        $locale = json_decode($match[1], true)['date_i18n'];

        // flatpickr 4.6 calls .join() on these lists; a missing one stops the date picker
        $this->assertCount(12, $locale['months']['shorthand']);
        $this->assertCount(12, $locale['months']['longhand']);
        $this->assertCount(7, $locale['weekdays']['shorthand']);
        $this->assertCount(7, $locale['weekdays']['longhand']);
        $this->assertSame((int)get_option('start_of_week', 0), $locale['firstDayOfWeek']);
    }
}
