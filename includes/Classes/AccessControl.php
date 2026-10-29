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

    /**
     * REST permission check: actor capability and form ownership.
     *
     * @param string $endpoint     Key in the permission map
     * @param string $group        Group in the permission map
     * @param int    $formId       Form the request acts on (0 if none)
     * @param int    $submissionId Application the request acts on (0 if none)
     * @return bool
     */
    public static function canAccess($endpoint, $group, $formId = 0, $submissionId = 0)
    {
        $permission = self::hasEndPointPermission($endpoint, $group);
        if (!$permission) {
            return false;
        }
        return self::canAccessForm($permission, $endpoint, absint($formId), absint($submissionId));
    }

    /**
     * A user who has only an "own" permission can act only on forms they created.
     *
     * @param string $permission The capability that granted the access
     * @param string $endpoint
     * @param int    $formId
     * @param int    $submissionId
     * @return bool
     */
    private static function canAccessForm($permission, $endpoint, $formId, $submissionId)
    {
        // "Can add form" lets a user create forms and work on the forms they created
        $ownScoped = is_string($permission) && (strpos($permission, '_own') !== false || $permission === 'wpjb_can_add_form');
        if (!$ownScoped) {
            return true;
        }

        if ($submissionId) {
            $submission = wpJobBoardDB()->table('wjb_applications')
                ->select(array('form_id'))
                ->where('id', $submissionId)
                ->first();
            if (!$submission || ($formId && intval($submission->form_id) !== $formId)) {
                return false;
            }
            $formId = intval($submission->form_id);
        }

        if (!$formId) {
            // Routes that do not touch a single form
            return in_array($endpoint, array('get_forms', 'create_form', 'get_available_forms'), true);
        }

        $form = get_post($formId);

        return $form && $form->post_type === 'wp_job_board' && intval($form->post_author) === get_current_user_id();
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
                'get_email_notifications'    => array(
                    'wpjb_can_edit_all_forms',
                    'wpjb_can_edit_own_form',
                    'wpjb_can_add_form'
                ),
                'save_email_notifications'   => array(
                    'wpjb_can_edit_all_forms',
                    'wpjb_can_edit_own_form',
                    'wpjb_can_add_form'
                ),
                'get_scheduling_settings'    => array(
                    'wpjb_can_edit_all_forms',
                    'wpjb_can_edit_own_form',
                    'wpjb_can_add_form'
                ),
                'save_scheduling_settings'   => array(
                    'wpjb_can_edit_all_forms',
                    'wpjb_can_edit_own_form',
                    'wpjb_can_add_form'
                ),
                'duplicate_form'             => array(
                    'wpjb_can_add_form'
                ),
                'restore_form'               => array(
                    'wpjb_can_edit_all_forms',
                    'wpjb_can_edit_own_form'
                ),
                'export_form'                => array(
                    'wpjb_can_edit_all_forms',
                    'wpjb_can_edit_own_form'
                ),
                'import_form'                => array(
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
                'get_form_report'          => array(
                    'wpjb_can_view_all_entries',
                    'wpjb_can_view_entries_of_own_created_forms',
                ),
                'export_submissions'       => array(
                    'wpjb_can_view_all_entries',
                    'wpjb_can_view_entries_of_own_created_forms',
                ),
            ),
            // Email campaigns send email to many applicants: full access only
            'email_campaigns' => array(),
            'global'      => array(
                'get_global_settings'    => array(
                    'wpjb_can_change_global_settings'
                ),
                'update_global_settings' => array(
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
