<?php

namespace WPJobBoard\Classes;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * The visitor's browser, platform and IP address, stored with each application.
 *
 * A small user-agent parser for current browsers. The order of the checks matters:
 * most browsers also send "Chrome" and "Safari" in their user agent.
 *
 * @since 2.0.0 Replaces the 2010 Browser.php (Chris Schuld)
 */
class Browser
{
    const UNKNOWN = 'Unknown';

    private $userAgent = '';

    /**
     * @param string $userAgent Defaults to the current request's user agent
     */
    public function __construct($userAgent = '')
    {
        if ($userAgent === '' && isset($_SERVER['HTTP_USER_AGENT'])) {
            $userAgent = sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT']));
        }
        $this->userAgent = (string)$userAgent;
    }

    public function getUserAgent()
    {
        return $this->userAgent;
    }

    /**
     * @return string For example: Chrome, Safari, Firefox, Edge, Opera, Samsung Internet
     */
    public function getBrowser()
    {
        $browsers = array(
            'Edge'              => '/\bEdg(e|A|iOS)?\//',
            'Opera'             => '/\b(OPR|Opera)\//',
            'Samsung Internet'  => '/\bSamsungBrowser\//',
            'Firefox'           => '/\b(Firefox|FxiOS)\//',
            'Chrome'            => '/\b(Chrome|CriOS)\//',
            'Safari'            => '/\bVersion\/[\d.]+.*\bSafari\//',
            'Internet Explorer' => '/\b(MSIE |Trident\/)/'
        );

        foreach ($browsers as $name => $pattern) {
            if (preg_match($pattern, $this->userAgent)) {
                return $name;
            }
        }

        return self::UNKNOWN;
    }

    /**
     * @return string For example: Windows, macOS, iOS, iPadOS, Android, Linux, ChromeOS
     */
    public function getPlatform()
    {
        $platforms = array(
            'iPadOS'   => '/\biPad\b/',
            'iOS'      => '/\b(iPhone|iPod)\b/',
            'Android'  => '/\bAndroid\b/',
            'ChromeOS' => '/\bCrOS\b/',
            'Windows'  => '/\bWindows\b/',
            'macOS'    => '/\bMacintosh\b/',
            'Linux'    => '/\bLinux\b/'
        );

        foreach ($platforms as $name => $pattern) {
            if (preg_match($pattern, $this->userAgent)) {
                return $name;
            }
        }

        return self::UNKNOWN;
    }

    /**
     * The visitor IP. Proxy headers can be forged by the client, so only REMOTE_ADDR is used.
     * Sites behind a trusted proxy can change this with the `wpjobboard/client_ip` filter.
     */
    public function getIp()
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        $ip = apply_filters('wpjobboard/client_ip', $ip);
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'UNKNOWN';
    }
}
