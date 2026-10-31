<?php

namespace WPJobBoard\Http\Controllers;

use WPJobBoard\Classes\Models\Forms;

if (!defined('ABSPATH')) {
    exit;
}

abstract class Controller
{
    /**
     * @return \WP_Post|\WP_Error The form from the {id} route parameter
     */
    protected function requireForm(\WP_REST_Request $request)
    {
        $form = Forms::getForm(absint($request->get_param('id')));
        if (!$form) {
            return $this->error(__('Job post not found', 'ninja-job-board'), 404);
        }
        return $form;
    }

    /**
     * A request parameter as plain text. Arrays keep their shape.
     */
    protected function text(\WP_REST_Request $request, $key, $default = '')
    {
        $value = $request->get_param($key);
        if ($value === null) {
            return $default;
        }
        return wpJobBoardSanitizeText($value);
    }

    /**
     * A request parameter as nested settings. Strings pass through wp_kses_post().
     */
    protected function settings(\WP_REST_Request $request, $key)
    {
        $value = $request->get_param($key);
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : array();
        }
        return is_array($value) ? wpJobBoardSanitize($value) : array();
    }

    protected function error($message, $status = 422, $data = array())
    {
        return new \WP_Error('wpjb_error', $message, array_merge($data, array('status' => $status)));
    }
}
