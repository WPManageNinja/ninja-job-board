<?php

namespace WPJobBoard\Classes;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * General Settings Definations here
 * @since 1.0.0
 */
class GeneralSettings
{

    public static function getComponents()
    {
        $components = array();
        return apply_filters('wpjobboard/form_components', $components);;
    }

    public static function ipLoggingStatus($bool = false)
    {
        $status = get_option('wpjobboard_ip_logging_status');
        if (!$status) {
            $status = 'yes';
        }
        if ($bool) {
            $status == 'yes';
        }
        return apply_filters('wpjobboard/ip_logging_status', $status);
    }

    public static function getConfirmationPageSettings()
    {
        return get_option('wpjobboard_confirmation_page_id');
    }

    public static function getApplicationStatuses()
    {
        return apply_filters('wpjobboard/available_application_statuses', array(
            'applied'    => __('Applied', 'wpjobboard'),
            'processing' => __('Processing', 'wpjobboard'),
            'pending'    => __('Pending', 'wpjobboard'),
            'completed'  => __('Completed', 'wpjobboard')
        ));
    }

    public static function getInternalStatuses()
    {
        return apply_filters('wpjobboard/available_internal_statuses', array(
            'new'              => __('New', 'wpjobboard'),
            'audited'          => __('Audited', 'wpjobboard'),
            'initial_rejected' => __('Inital Rejected', 'wpjobboard'),
            'interviewed'      => __('Interviewed', 'wpjobboard'),
            'potential'        => __('Potential', 'wpjobboard'),
            'short_listed'     => __('Short Listed', 'wpjobboard'),
            'hired'            => __('Hired', 'wpjobboard'),
        ));
    }

    public static function getJobTypes()
    {
        return apply_filters('wpjobboard/available_job_types', array(
            'full_time'   => __('Full Time', 'wpjobboard'),
            'part_time'   => __('Part Time', 'wpjobboard'),
            'remote'      => __('Remote', 'wpjobboard'),
            'contractual' => __('Contractual', 'wpjobboard')
        ));
    }

    public static function getJobTypeName($type)
    {
        if(!$type) {
            return $type;
        }
        $types = self::getJobTypes();
        return (isset($types[$type])) ? $types[$type] : $type;
    }

}