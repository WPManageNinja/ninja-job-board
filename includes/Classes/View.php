<?php

namespace WPJobBoard\Classes;

if (!defined('ABSPATH')) {
    exit;
}

class View
{

    /**
     * Generate a view file
     * @param  string $path
     * @param  array  $data
     * @return string [generated html]
     */
    public static function make($path, $data = [])
    {
        if (file_exists($path = self::getFilePath($path))) {
            ob_start();
            extract($data);
            include $path;
            return ob_get_clean();
        }
        return '';
    }

    /**
     * Resolve the view file path
     * @param  string $path
     * @return string
     */
    protected static function getFilePath($path)
    {
        $path = str_replace('.', DIRECTORY_SEPARATOR, $path);
        $viewName = WPJOBBOARD_DIR.DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.$path;
        $fullPath = $viewName.'.php';
        return apply_filters('wpjobboard/template_view_path', $fullPath, $path);
    }
}