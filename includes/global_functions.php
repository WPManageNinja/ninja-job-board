<?php

if (!defined('ABSPATH')) {
    exit;
}

function wpJobBoardDB()
{
    if (!function_exists('wpFluentDb')) {
        include WPJOBBOARD_DIR . 'includes/libs/wp-fluent/wp-fluent.php';
    }
    return wpFluentDb();
}

function wpJobBoardPrintInternal( $string ) {
    echo wp_kses_post($string);
}

/**
 * Sites with full-page caching can serve an expired nonce; they can turn the check off with the
 * `wpjobboard/verify_public_nonce` filter. Submissions still pass all server-side validation.
 */
function wpJobBoardVerifyPublicNonce() {
    if (!apply_filters('wpjobboard/verify_public_nonce', true)) {
        return true;
    }
    $nonce = isset($_REQUEST['wpjb_nonce']) ? sanitize_text_field(wp_unslash($_REQUEST['wpjb_nonce'])) : '';
    return (bool) wp_verify_nonce($nonce, 'wpjobboard_public_nonce');
}

/**
 * wp_kses_post() that also keeps <style> blocks. The entry table template has its own CSS.
 */
function wpJobBoardKsesWithStyle($html) {
    $allowed = wp_kses_allowed_html('post');
    $allowed['style'] = array('type' => true);
    return wp_kses((string)$html, $allowed);
}

/**
 * Sanitize nested request data as plain text. Keeps the array structure.
 */
function wpJobBoardSanitizeText($values) {
    if (is_array($values)) {
        $sanitized = [];
        foreach ($values as $key => $value) {
            $sanitized[sanitize_text_field((string)$key)] = wpJobBoardSanitizeText($value);
        }
        return $sanitized;
    }
    if (is_scalar($values)) {
        return sanitize_text_field((string)$values);
    }
    return '';
}


function wpJobBoardSanitize($values) {
    if(is_array($values)) {
        $sanitizedValues = [];
        foreach ($values as $key => $value) {
            $sanitizedValues[$key] = wpJobBoardSanitize($value);
        }
        return $sanitizedValues;
    } else if(is_string($values)) {
        return wp_kses_post($values);
    } else if (is_bool($values) || is_int($values) || is_float($values) || is_null($values)) {
        return $values;
    }

    return '';
}
