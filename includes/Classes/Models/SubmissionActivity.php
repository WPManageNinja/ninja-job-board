<?php

namespace WPJobBoard\Classes\Models;

if (!defined('ABSPATH')) {
    exit;
}

class SubmissionActivity
{
    public static function getSubmissionActivity($submissionId)
    {
        $activities = wpJobBoardDB()->table('wjb_application_activities')
            ->where('submission_id', $submissionId)
            ->orderBy('id', 'DESC')
            ->get();
        foreach ($activities as $activitiy) {
            // The admin app renders the content as HTML (v-html)
            $activitiy->content = wp_kses_post($activitiy->content);
            $activitiy->created_by = sanitize_text_field($activitiy->created_by);
            if($activitiy->created_by_user_id) {
                $activitiy->user_profile_url = get_edit_user_link($activitiy->created_by_user_id);
            }
        }
        
        return apply_filters('wpjobboard/entry_activities', $activities, $submissionId);
    }

    public static function createActivity($data)
    {
        $data['created_at'] = gmdate('Y-m-d H:i:s');
        $data['updated_at'] = gmdate('Y-m-d H:i:s');

        return wpJobBoardDB()->table('wjb_application_activities')
            ->insert($data);
    }
}