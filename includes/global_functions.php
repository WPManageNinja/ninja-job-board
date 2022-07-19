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
