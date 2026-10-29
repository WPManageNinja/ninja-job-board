<?php

namespace WPJobBoard\Classes;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Spam protection for the public application form.
 *
 * - Honeypot: always on. A hidden field that people never see; bots fill it.
 * - Cloudflare Turnstile: optional. On when the site key and secret key are saved
 *   in Settings > General. The token is checked with Cloudflare's siteverify API.
 *
 * @since 2.0.0
 */
class SpamProtection
{
    const HONEYPOT_FIELD = '__wpjb_hp';
    const OPTION = 'wpjobboard_spam_settings';
    const SITEVERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function register()
    {
        add_action('wpjobboard/form_render_start_form', array($this, 'renderFields'));
        add_filter('wpjobboard/spam_check', array($this, 'check'), 10, 3);
    }

    /**
     * @return array{turnstile_enabled: string, turnstile_site_key: string, turnstile_secret_key: string}
     */
    public static function getSettings()
    {
        $settings = get_option(self::OPTION, array());
        return wp_parse_args(is_array($settings) ? $settings : array(), array(
            'turnstile_enabled'    => 'no',
            'turnstile_site_key'   => '',
            'turnstile_secret_key' => ''
        ));
    }

    public static function turnstileActive()
    {
        $settings = self::getSettings();
        return $settings['turnstile_enabled'] === 'yes' && $settings['turnstile_site_key'] && $settings['turnstile_secret_key'];
    }

    public function renderFields($form)
    {
        // Off screen, not display:none (some bots skip hidden fields); hidden from screen readers
        ?>
        <div class="wpjb_hp" aria-hidden="true" style="position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden;">
            <label>
                <?php esc_html_e('Leave this field empty', 'ninja-job-board'); ?>
                <input type="text" name="<?php echo esc_attr(self::HONEYPOT_FIELD); ?>" value="" tabindex="-1" autocomplete="off"/>
            </label>
        </div>
        <?php
        if (!self::turnstileActive()) {
            return;
        }

        $settings = self::getSettings();
        // Cloudflare requires loading Turnstile from its own URL (it can not be bundled). Opt-in only;
        // documented under "External services" in readme.txt.
        wp_enqueue_script('wpjobboard_turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', array(), null, array('strategy' => 'defer', 'in_footer' => true)); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion, PluginCheck.CodeAnalysis.EnqueuedResourceOffloading.OffloadedContent -- third-party service script, see above
        ?>
        <div class="wpjb_form_group wpjb_turnstile">
            <div class="cf-turnstile" data-sitekey="<?php echo esc_attr($settings['turnstile_site_key']); ?>"></div>
        </div>
        <?php
    }

    /**
     * @param string   $error     An error message from an earlier check, or ''
     * @param array    $form_data The submitted form fields
     * @param \WP_Post $form
     * @return string Error message, or '' when the submission looks human
     */
    public function check($error, $form_data, $form)
    {
        if ($error) {
            return $error;
        }

        if (!empty($form_data[self::HONEYPOT_FIELD])) {
            return __('Your application could not be sent. Please reload the page and try again.', 'ninja-job-board');
        }

        if (!self::turnstileActive()) {
            return '';
        }

        $token = isset($form_data['cf-turnstile-response']) && is_string($form_data['cf-turnstile-response']) ? sanitize_text_field($form_data['cf-turnstile-response']) : '';
        if (!$token) {
            return __('Please complete the security check.', 'ninja-job-board');
        }

        $settings = self::getSettings();
        $body = array(
            'secret'   => $settings['turnstile_secret_key'],
            'response' => $token
        );
        $ip = (new Browser())->getIp();
        if ($ip && $ip !== 'UNKNOWN') {
            $body['remoteip'] = $ip;
        }

        $response = wp_remote_post(self::SITEVERIFY_URL, array(
            'timeout' => 10,
            'body'    => $body
        ));

        if (is_wp_error($response)) {
            return __('The security check could not be verified. Please try again.', 'ninja-job-board');
        }

        $result = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($result['success'])) {
            return __('The security check failed. Please try again.', 'ninja-job-board');
        }

        return '';
    }
}
