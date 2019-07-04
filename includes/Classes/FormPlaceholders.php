<?php

namespace WPJobBoard\Classes;

use WPJobBoard\Classes\Models\Forms;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Form Placeholders Definations here
 * @since 1.1.0
 */
class FormPlaceholders
{
    public static function getAllPlaceholders($formId = false)
    {
        $allFields = array(
            'submission' => array(
                'title'        => __('Submission Info', 'wpjobboard'),
                'placeholders' => self::getFormPlaceHolders($formId)
            ),
            'wp'         => array(
                'title'        => __('WordPress', 'wpjobboard'),
                'placeholders' => self::getWPPlaceHolders()
            ),
            'other'      => array(
                'title'        => __('Other', 'wpjobboard'),
                'placeholders' => self::getOtherPlaceholders()
            )
        );
        return apply_filters('wpjobboard/all_placeholders', $allFields, $formId);
    }

    public static function getWPPlaceHolders()
    {
        $mergeTags = array(
            'post_id'           => array(
                'id'       => 'id',
                'tag'      => '{wp:post_id}',
                'label'    => __('Post ID', 'wpjobboard'),
                'callback' => 'post_id'
            ),
            'post_title'        => array(
                'id'       => 'title',
                'tag'      => '{wp:post_title}',
                'label'    => __('Post Title', 'wpjobboard'),
                'callback' => 'post_title'
            ),
            'post_url'          => array(
                'id'       => 'url',
                'tag'      => '{wp:post_url}',
                'label'    => __('Post URL', 'wpjobboard'),
                'callback' => 'post_url'
            ),
            'post_author'       => array(
                'id'       => 'author',
                'tag'      => '{wp:post_author}',
                'label'    => __('Post Author', 'wpjobboard'),
                'callback' => 'post_author'
            ),
            'post_author_email' => array(
                'id'       => 'author_email',
                'tag'      => '{wp:post_author_email}',
                'label'    => __('Post Author Email', 'wpjobboard'),
                'callback' => 'post_author_email'
            ),
            'post_meta'         => array(
                'id'       => 'post_meta',
                'tag'      => '{post_meta:YOUR_META_KEY}',
                'label'    => __('Post Meta', 'wpjobboard'),
                'callback' => null
            ),
            'user_id'           => array(
                'id'       => 'user_id',
                'tag'      => '{wp:user_id}',
                'label'    => __('User ID', 'wpjobboard'),
                'callback' => 'user_id'
            ),
            'user_first_name'   => array(
                'id'       => 'first_name',
                'tag'      => '{wp:user_first_name}',
                'label'    => __('User First Name', 'wpjobboard'),
                'callback' => 'user_first_name'
            ),
            'user_last_name'    => array(
                'id'       => 'last_name',
                'tag'      => '{wp:user_last_name}',
                'label'    => __('User Last Name', 'wpjobboard'),
                'callback' => 'user_last_name'
            ),
            'user_display_name' => array(
                'id'       => 'display_name',
                'tag'      => '{wp:user_display_name}',
                'label'    => __('User Display Name', 'wpjobboard'),
                'callback' => 'user_display_name'
            ),
            'user_email'        => array(
                'id'       => 'user_email',
                'tag'      => '{wp:user_email}',
                'label'    => __('User Email', 'wpjobboard'),
                'callback' => 'user_email'
            ),
            'user_url'          => array(
                'id'       => 'user_url',
                'tag'      => '{wp:user_url}',
                'label'    => __('User URL', 'wpjobboard'),
                'callback' => 'user_url'
            ),
            'user_meta'         => array(
                'id'       => 'user_meta',
                'tag'      => '{user_meta:YOUR_META_KEY}',
                'label'    => __('User Meta', 'wpjobboard'),
                'callback' => null
            ),
            'site_title'        => array(
                'id'       => 'site_title',
                'tag'      => '{wp:site_title}',
                'label'    => __('Site Title', 'wpjobboard'),
                'callback' => 'site_title'
            ),
            'site_url'          => array(
                'id'       => 'site_url',
                'tag'      => '{wp:site_url}',
                'label'    => __('Site URL', 'wpjobboard'),
                'callback' => 'site_url'
            ),
            'admin_email'       => array(
                'id'       => 'admin_email',
                'tag'      => '{wp:admin_email}',
                'label'    => __('Admin Email', 'wpjobboard'),
                'callback' => 'admin_email'
            )
        );

        return apply_filters('wpjobboard/wp_merge_tags', $mergeTags);
    }

    public static function getUserPlaceholders()
    {
        $mergeTags = array(
            'user_id'      => array(
                'id'    => 'ID',
                'tag'   => '{user:ID}',
                'label' => __('User ID', 'wpjobboard')
            ),
            'first_name'   => array(
                'id'    => 'first_name',
                'tag'   => '{user:first_name}',
                'label' => __('First name', 'wpjobboard')
            ),
            'last_name'    => array(
                'id'    => 'last_name',
                'tag'   => '{user:last_name}',
                'label' => __('Last name', 'wpjobboard')
            ),
            'display_name' => array(
                'id'    => 'display_name',
                'tag'   => '{user:display_name}',
                'label' => __('Display name', 'wpjobboard')
            ),
            'user_email' => array(
                'id'    => 'user_email',
                'tag'   => '{user:user_email}',
                'label' => __('User Email', 'wpjobboard')
            ),
            'user_url' => array(
                'id'    => 'user_url',
                'tag'   => '{user:user_url}',
                'label' => __('User URL', 'wpjobboard')
            ),
            'description' => array(
                'id'    => 'description',
                'tag'   => '{user:description}',
                'label' => __('User Description', 'wpjobboard')
            ),
            'roles' => array(
                'id'    => 'roles',
                'tag'   => '{user:roles}',
                'label' => __('User Role', 'wpjobboard')
            )
        );
        return apply_filters('wpjobboard/user_merge_tags', $mergeTags);
    }

    public static function getOtherPlaceholders()
    {
        $mergeTags = array(
            'querystring' => array(
                'tag'      => '{querystring:YOUR_KEY}',
                'label'    => __('Query String', 'wpjobboard'),
                'callback' => null,
            ),
            'date'        => array(
                'id'       => 'date',
                'tag'      => '{other:date}',
                'label'    => __('Date', 'wpjobboard'),
                'callback' => 'system_date'
            ),
            'time'        => array(
                'id'       => 'time',
                'tag'      => '{other:time}',
                'label'    => __('Time', 'wpjobboard'),
                'callback' => 'system_time'
            ),
            'ip'          => array(
                'id'       => 'ip',
                'tag'      => '{other:user_ip}',
                'label'    => __('User IP Address', 'wpjobboard'),
                'callback' => 'user_ip'
            ),
        );

        return apply_filters('wpjobboard/other_merge_tags', $mergeTags);
    }

    public static function getFormPlaceHolders($formId = false, $html = true)
    {
        if (!$formId) {
            return array();
        }
        $shortcodes = Forms::getEditorShortCodes($formId, $html);

        $formattedItems = array();

        foreach ($shortcodes as $codeSection) {
            foreach ($codeSection['shortcodes'] as $codeIndex => $codeTitle) {
                $codeIndexOnly = str_replace(['{', '}'], ['', ''], $codeIndex);
                $formattedItems[$codeIndexOnly] = array(
                    'tag'      => $codeIndex,
                    'label'    => $codeTitle,
                    'callback' => null,
                );
            }
        }

        return apply_filters('wpjobboard/form_merge_tags', $formattedItems, $formId);
    }

}
