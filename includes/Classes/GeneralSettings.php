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
            'applied'    => __('Applied', 'ninja-job-board'),
            'processing' => __('Processing', 'ninja-job-board'),
            'pending'    => __('Pending', 'ninja-job-board'),
            'completed'  => __('Completed', 'ninja-job-board')
        ));
    }

    public static function getInternalStatuses()
    {
        return apply_filters('wpjobboard/available_internal_statuses', array(
            'new'              => __('New', 'ninja-job-board'),
            'audited'          => __('Audited', 'ninja-job-board'),
            'initial_rejected' => __('Inital Rejected', 'ninja-job-board'),
            'interviewed'      => __('Interviewed', 'ninja-job-board'),
            'potential'        => __('Potential', 'ninja-job-board'),
            'short_listed'     => __('Short Listed', 'ninja-job-board'),
            'hired'            => __('Hired', 'ninja-job-board'),
        ));
    }

    public static function getJobTypes()
    {
        return apply_filters('wpjobboard/available_job_types', array(
            'full_time'   => __('Full Time', 'ninja-job-board'),
            'part_time'   => __('Part Time', 'ninja-job-board'),
            'remote'      => __('Remote', 'ninja-job-board'),
            'contractual' => __('Contractual', 'ninja-job-board')
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
