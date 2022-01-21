<?php
namespace WPJobBoard\Classes;
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Managing Access Control
 * This is not complete on version 1.0.0 but we will definately add this feature.
 * @since 1.0.0
 */
class AccessControl
{
    public static function hasGrandAccess()
    {
        $grandPermissions = array(
            'manage_options',
            'wpjb_full_access'
        );

        foreach ($grandPermissions as $grandPermission) {
            if (current_user_can($grandPermission)) {
                return $grandPermission;
            }
        }
        return false;
    }

    public static function hasTopLevelMenuPermission()
    {
        $menuPermissions = array(
            'manage_options',
            'wpjb_full_access',
            'wpjb_can_view_menus'
        );
        foreach ($menuPermissions as $menuPermission) {
            if (current_user_can($menuPermission)) {
                return $menuPermission;
            }
        }
        return false;
    }

    public static function checkAndPresponseError($endpoint = false, $group = false, $message = '')
    {
        if(self::hasEndPointPermission($endpoint, $group)) {
            return true;
        }
        wp_send_json_error(array(
            'message' => ($message) ? $message : __('Sorry, You do not have permission to do this action: ', 'ninja-job-board').$endpoint,
            'action' => $endpoint
        ), 423);
    }

    public static function hasEndPointPermission($endpoint = false, $group = false)
    {
        if($grandAccess = self::hasGrandAccess()) {
            return apply_filters('wpjobboard/has_endpoint_access', $grandAccess, $endpoint, $group);
        }

        $permissions = self::getEndpointPermissionMaps($group);
        if(isset($permissions[$endpoint])) {
            $relatedPermission = $permissions[$endpoint];
            foreach ($relatedPermission as $permission) {
                if(current_user_can($permission)) {
                    return apply_filters('wpjobboard/has_endpoint_access', $permission, $endpoint, $group);
                }
            }
        }
        return apply_filters('wpjobboard/has_endpoint_access', false, $endpoint, $group);
    }

    public static function getEndpointPermissionMaps($group = false)
    {
        $permissionroups = array(
            'forms'       => array(
                'get_forms'                  => array(
                    'wpjb_can_edit_all_forms',
                    'wpjb_can_add_form',
                    'wpjb_can_edit_own_form',
                    'wpjb_can_delete_all_forms',
                    'wpjb_can_delete_own_created_forms',
                ),
                'create_form'                => array(
                    'wpjb_can_add_form'
                ),
                'update_form'                => array(
                    'wpjb_can_edit_all_forms',
                    'wpjb_can_edit_own_form',
                    'wpjb_can_add_form'
                ),
                'get_form'                   => array(
                    'wpjb_can_edit_all_forms',
                    'wpjb_can_edit_own_form',
                    'wpjb_can_add_form'
                ),
                'save_form_settings'         => array(
                    'wpjb_can_edit_all_forms',
                    'wpjb_can_edit_own_form',
                    'wpjb_can_add_form'
                ),
                'save_form_builder_settings' => array(
                    'wpjb_can_edit_all_forms',
                    'wpjb_can_edit_own_form',
                    'wpjb_can_add_form'
                ),
                'get_custom_form_settings'   => array(
                    'wpjb_can_edit_all_forms',
                    'wpjb_can_edit_own_form',
                    'wpjb_can_add_form'
                ),
                'delete_form'                => array(
                    'wpjb_can_delete_all_forms',
                    'wpjb_can_delete_own_created_forms'
                ),
                'get_form_settings'          => array(
                    'wpjb_can_edit_all_forms',
                    'wpjb_can_edit_own_form',
                    'wpjb_can_add_form'
                ),
                'get_design_settings'        => array(
                    'wpjb_can_edit_all_forms',
                    'wpjb_can_edit_own_form',
                    'wpjb_can_add_form'
                ),
                'update_design_settings'     => array(
                    'wpjb_can_edit_all_forms',
                    'wpjb_can_edit_own_form',
                    'wpjb_can_add_form'
                ),
            ),
            'submissions' => array(
                'get_submissions'          => array(
                    'wpjb_can_view_all_entries',
                    'wpjb_can_view_entries_of_own_created_forms',
                    'wpjb_can_edit_all_form_entries',
                    'wpjb_can_edit_entries_of_own_created_forms',
                    'wpjb_can_delete_all_entries',
                    'wpjb_can_delete_entries_of_own_created_forms',
                ),
                'get_submission'           => array(
                    'wpjb_can_view_all_entries',
                    'wpjb_can_view_entries_of_own_created_forms',
                ),
                'get_available_forms'      => array(
                    'wpjb_can_view_all_entries',
                    'wpjb_can_view_entries_of_own_created_forms',
                    'wpjb_can_edit_all_form_entries',
                    'wpjb_can_edit_entries_of_own_created_forms',
                    'wpjb_can_delete_all_entries',
                    'wpjb_can_delete_entries_of_own_created_forms',
                ),
                'get_next_prev_submission' => array(
                    'wpjb_can_view_all_entries',
                    'wpjb_can_view_entries_of_own_created_forms',
                ),
                'add_submission_note'      => array(
                    'wpjb_can_edit_all_form_entries',
                    'wpjb_can_edit_entries_of_own_created_forms',
                ),
                'change_application_status'    => array(
                    'wpjb_can_edit_all_form_entries',
                    'wpjb_can_edit_entries_of_own_created_forms',
                ),
                'delete_submission'        => array(
                    'wpjb_can_delete_all_entries',
                    'wpjb_can_delete_entries_of_own_created_forms',
                ),
            ),
            'global'      => array(
                'update_global_currency_settings' => array(
                    'wpjb_can_change_global_settings'
                ),
                'wpjb_upload_image'                => array(
                    'wpjb_can_change_global_settings',
                    'wpjb_can_view_menus'
                )
            )
        );

        if (!$group || !isset($permissionroups[$group])) {
            return array_merge(
                $permissionroups['forms'],
                $permissionroups['submissions'],
                $permissionroups['global']
            );
        }

        return $permissionroups[$group];
    }

    public static function getPermissionLists()
    {
        return array(
            'wpjb_full_access',
            'wpjb_can_view_menus',
            // FOrm Related Actions
            'wpjb_can_edit_all_forms',
            'wpjb_can_add_form',
            'wpjb_can_edit_own_form',
            'wpjb_can_delete_all_forms',
            'wpjb_can_delete_own_created_forms',
            // Submission Related Actions
            'wpjb_can_view_all_entries',
            'wpjb_can_view_entries_of_own_created_forms',
            'wpjb_can_delete_all_entries',
            'wpjb_can_delete_entries_of_own_created_forms',
            'wpjb_can_edit_all_form_entries',
            'wpjb_can_edit_entries_of_own_created_forms',
            // Global Settings Related
            'wpjb_can_change_global_settings',
        );
    }
}
