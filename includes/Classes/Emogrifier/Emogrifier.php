<?php

namespace WPJobBoard\Classes\Emogrifier;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Inlines CSS into email HTML for email clients that ignore <style> tags.
 *
 * The same inliner FluentCRM uses (app/Services/Libs/Emogrifier): a php-scoped copy of
 * tijsverkoyen/css-to-inline-styles 2.4.0 and symfony/css-selector 5.4.45 (PHP 7.4+).
 * The scoped classes share the FluentEmogrifier\Vendor prefix with the other Fluent plugins,
 * so whichever plugin loads first provides them. Only the Composer autoloader class names
 * carry this plugin's "Njb" marker, so two copies never collide.
 *
 * To update, copy scoped-vendor/ from FluentCRM again, then run `npm run prepare:emogrifier`
 * (build/prepare-emogrifier.mjs): it sets the "Njb" marker and the WordPress.org file guards.
 */
class Emogrifier
{
    private $html = '';

    private $css = '';

    /**
     * @param string $html Email HTML. <style> tags in it are inlined too.
     * @param string $css  Extra CSS to inline
     */
    public function __construct($html, $css = '')
    {
        $this->html = (string)$html;
        $this->css = (string)$css;
    }

    /**
     * @return string The HTML with the CSS inlined; @media rules stay in a <style> tag
     */
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
