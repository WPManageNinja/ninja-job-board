<?php

namespace WPJobBoard\Classes;

if (!defined('ABSPATH')) {
    exit;
}

class View
{

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

    protected static function getFilePath($path)
    {
        $path = str_replace('.', DIRECTORY_SEPARATOR, $path);
        $viewName = WPJOBBOARD_DIR.DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.$path;
        $fullPath = $viewName.'.php';
        return apply_filters('wpjobboard/template_view_path', $fullPath, $path);
    }
}