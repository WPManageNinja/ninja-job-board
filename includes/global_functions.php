<?php

function wpJobBoardDB()
{
    if (!function_exists('wpFluent')) {
        include WPJOBBOARD_DIR . 'includes/libs/wp-fluent/wp-fluent.php';
    }
    return wpFluent();
}

function wpJobBoardPrintInternal( $string ) {
    echo $string; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
