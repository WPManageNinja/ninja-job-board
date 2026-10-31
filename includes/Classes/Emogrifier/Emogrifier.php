<?php

namespace WPJobBoard\Classes\Emogrifier;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Inlines CSS into email HTML. A php-scoped copy of FluentCRM's inliner: the FluentEmogrifier\Vendor
 * classes are shared with the other Fluent plugins (first loaded wins); only the Composer autoloader
 * carries this plugin's "Njb" marker, so two copies never collide.
 * To update, copy scoped-vendor/ from FluentCRM again, then run `npm run prepare:emogrifier`.
 */
class Emogrifier
{
    private $html = '';

    private $css = '';

    public function __construct($html, $css = '')
    {
        $this->html = (string)$html;
        $this->css = (string)$css;
    }

    public function emogrify()
    {
        if (!class_exists('\FluentEmogrifier\Vendor\TijsVerkoyen\CssToInlineStyles\CssToInlineStyles')) {
            require_once __DIR__ . '/scoped-vendor/autoload.php';
        }

        $css = $this->css;
        $html = $this->html;

        if (preg_match_all('/<style[^>]*>(.*?)<\/style>/si', $html, $matches)) {
            $css .= "\n" . implode("\n", $matches[1]);
            $html = preg_replace('/<style[^>]*>.*?<\/style>/si', '', $html);
        }

        // The inliner drops @media rules, but Apple Mail and Gmail support them: keep them in a <style> tag
        $mediaBlocks = '';
        if (preg_match_all('/@media\s*[^{]*\{(?:[^{}]*\{[^{}]*\})*[^{}]*\}/s', $css, $mediaMatches)) {
            $mediaBlocks = implode("\n", $mediaMatches[0]);
        }

        $inliner = new \FluentEmogrifier\Vendor\TijsVerkoyen\CssToInlineStyles\CssToInlineStyles();
        $result = $inliner->convert($html, $css);

        if ($mediaBlocks) {
            $styleTag = '<style type="text/css">' . $mediaBlocks . '</style>';

            if (stripos($result, '</head>') !== false) {
                $result = str_ireplace('</head>', $styleTag . '</head>', $result);
            } elseif (stripos($result, '<body') !== false) {
                $result = preg_replace('/<body/i', $styleTag . '<body', $result, 1);
            } else {
                $result = $styleTag . $result;
            }
        }

        return $result;
    }
}
