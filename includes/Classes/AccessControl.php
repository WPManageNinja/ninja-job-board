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
     * Gate for every admin AJAX route: CSRF nonce, actor capability and form ownership.
     * Sends a JSON error and stops the request when any check fails.
     */
    public static function checkAndPresponseError($endpoint = false, $group = false, $message = '')
    {
        if (!check_ajax_referer('wpjobboard_admin_nonce', 'wpjb_nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security check failed. Please reload the page and try again.', 'ninja-job-board'),
                'action'  => $endpoint
            ), 403);
        }

        $permission = self::hasEndPointPermission($endpoint, $group);

        if ($permission && self::canAccessRequestedForm($permission, $endpoint)) {
            return true;
        }

        wp_send_json_error(array(
            'message' => ($message) ? $message : __('Sorry, You do not have permission to do this action: ', 'ninja-job-board') . $endpoint,
            'action'  => $endpoint
        ), 403);
    }

    /**
     * A user who has only an "own" permission can act only on forms they created.
     *
     * @param string $permission The capability that granted the access
     * @param string $endpoint
     * @return bool
     */
    private static function canAccessRequestedForm($permission, $endpoint)
    {
        if (!is_string($permission) || strpos($permission, '_own') === false) {
            return true;
        }

        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- nonce verified in checkAndPresponseError()
        $formId = isset($_REQUEST['form_id']) ? absint($_REQUEST['form_id']) : 0;
        $submissionId = isset($_REQUEST['submission_id']) ? absint($_REQUEST['submission_id']) : 0;
        // phpcs:enable

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
