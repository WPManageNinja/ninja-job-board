<?php

namespace WPJobBoard\Classes;

use WPJobBoard\Classes\Models\Forms;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin App Renderer and Handler
 * @since 1.0.0
 */
class AdminApp
{
    public function bootView()
    {
        echo "<div id='wpjobboardsapp'></div>";
    }
}