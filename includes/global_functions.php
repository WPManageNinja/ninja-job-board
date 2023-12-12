<?php

function wpJobBoardDB()
{
    if (!function_exists('wpFluentDb')) {
        include WPJOBBOARD_DIR . 'includes/libs/wp-fluent/wp-fluent.php';
    }
    return wpFluentDb();
}

function wpJobBoardPrintInternal( $string ) {
    /*
     * It was supposed to just echo the string
     * But WP Plugin review team asked to use the kses which is not ideal
     * Many plugins can print hard coded strings but we can
     * Not sure we why we can use the flag: phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
     */
    echo wp_kses_post($string);
}


function wpJobBoardSanitize($values) {
    if(is_array($values)) {
        $sanitizedValues = [];
        foreach ($values as $key => $value) {
            $sanitizedValues[$key] = wpJobBoardSanitize($value);
        }
        return $sanitizedValues;
    } else if(is_string($values)) {
        return wp_kses_post_deep($values);
    }

    return $values;
}
