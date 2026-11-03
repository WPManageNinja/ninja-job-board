<?php

namespace WPJobBoard\Classes;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * The visitor's browser, platform and IP. The order of the checks matters: most browsers
 * also send "Chrome" and "Safari" in their user agent.
 */
class Browser
{
    const UNKNOWN = 'Unknown';

    private $userAgent = '';

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
