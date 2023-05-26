<?php

namespace WPJobBoard\Classes\Models;

use WPJobBoard\Classes\ArrayHelper;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manage Submission
 * @since 1.0.0
 */
class Submission
{
    public function create($submission)
    {
        return wpJobBoardDB()->table('wjb_applications')
            ->insert($submission);
    }

    public function getSubmissions($formId = false, $wheres = array(), $perPage = false, $skip = false, $orderBy = 'DESC', $searchString = false)
    {
        $resultQuery = wpJobBoardDB()->table('wjb_applications')
            ->select(array('wjb_applications.*', 'posts.post_title'))
            ->join('posts', 'posts.ID', '=', 'wjb_applications.form_id')
            ->orderBy('wjb_applications.id', $orderBy);

        if ($perPage) {
            $resultQuery->limit($perPage);
        }
        if ($skip) {
            $resultQuery->offset($skip);
        }

        if ($formId) {
            $resultQuery->where('wjb_applications.form_id', $formId);
        }

        foreach ($wheres as $whereKey => $where) {
            if($where) {
                if(is_array($where)) {
                    $resultQuery->whereIn('wjb_applications.' . $whereKey, $where);
                } else {
                    $resultQuery->where('wjb_applications.' . $whereKey, $where);
                }
            }
        }

        if ($searchString) {
            $resultQuery->where(function ($q) use ($searchString) {
                $q->where('wjb_applications.applicant_name', 'LIKE', "%{$searchString}%")
                    ->orWhere('wjb_applications.applicant_email', 'LIKE', "%{$searchString}%")
                    ->orWhere('wjb_applications.status', 'LIKE', "%{$searchString}%")
                    ->orWhere('wjb_applications.application_status', 'LIKE', "%{$searchString}%")
                    ->orWhere('wjb_applications.form_data_formatted', 'LIKE', "%{$searchString}%")
                    ->orWhere('wjb_applications.created_at', 'LIKE', "%{$searchString}%");
            });
        }


        $totalItems = $resultQuery->count();

        $results = $resultQuery->get();

        $formattedResults = array();

        foreach ($results as $result) {
            $result->form_data_raw = maybe_unserialize($result->form_data_raw);
            $result->form_data_formatted = maybe_unserialize($result->form_data_formatted);
            $formattedResults[] = $result;
        }

        return (object)array(
            'items' => $results,
            'total' => $totalItems
        );
    }

    public function getSubmission($submissionId, $with = array())
    {
        $result = wpJobBoardDB()->table('wjb_applications')
            ->select(array('wjb_applications.*', 'posts.post_title'))
            ->join('posts', 'posts.ID', '=', 'wjb_applications.form_id')
            ->where('wjb_applications.id', $submissionId)
            ->first();

        $result->form_data_raw = maybe_unserialize($result->form_data_raw);
        $result->form_data_formatted = maybe_unserialize($result->form_data_formatted);
        if ($result->user_id) {
            $result->user_profile_url = get_edit_user_link($result->user_id);
        }
        if (in_array('activities', $with)) {
            $result->activities = SubmissionActivity::getSubmissionActivity($submissionId);
        }

        return $result;
    }

    public function getTotalCount($formId = false, $applicationStatus = false)
    {
        $query = wpJobBoardDB()->table('wjb_applications');
        if ($formId) {
            $query = $query->where('form_id', $formId);
        }
        if ($applicationStatus) {
            $query = $query->where('application_status', $applicationStatus);
        }
        return $query->count();
    }

    public function update($submissionId, $data)
    {
        $data['updated_at'] = gmdate('Y-m-d H:i:s');
        return wpJobBoardDB()->table('wjb_applications')->where('id', $submissionId)->update($data);
    }

    public function getParsedSubmission($submission, $elements = [])
    {
        if (!$elements) {
            $elements = get_post_meta($submission->form_id, 'wpjobboard_application_builder_settings', true);
        }

        if (!$elements) {
            return array();
        }
        
        $parsedSubmission = array();

        $inputValues = $submission->form_data_formatted;

        foreach ($elements as $element) {
            if ($element['group'] == 'input') {
                $elementId = ArrayHelper::get($element, 'id');
                $elementValue = apply_filters(
                    'wpjobboard/rendering_entry_value_' . $element['type'],
                    ArrayHelper::get($inputValues, $elementId),
                    $submission,
                    $element
                );

                if (is_array($elementValue)) {
                    $elementValue = implode(', ', $elementValue);
                }
                $parsedSubmission[$elementId] = array(
                    'label' => $this->getLabel($element),
                    'value' => $elementValue,
                    'type'  => $element['type']
                );
            }
        }

        return apply_filters('wpjobboard/parsed_entry', $parsedSubmission, $submission);
    }

    public function getLabel($element)
    {
        $elementId = ArrayHelper::get($element, 'id');
        if (!$label = ArrayHelper::get($element, 'field_options.admin_label')) {
            $label = ArrayHelper::get($element, 'field_options.label');
        }
        if (!$label) {
            $label = $elementId;
        }
        return $label;
    }

    public function deleteSubmission($sumissionId)
    {
        wpJobBoardDB()->table('wjb_applications')
            ->where('id', $sumissionId)
            ->delete();

        wpJobBoardDB()->table('wjb_application_activities')
            ->where('submission_id', $sumissionId)
            ->delete();
    }

    public function getEntryCountByApplicationStatus($formId, $applicationStatuses = array(), $period = 'total')
    {
        $query = wpJobBoardDB()->table('wjb_applications')
            ->where('form_id', $formId);
        if ($applicationStatuses && count($applicationStatuses)) {
            $query->whereIn('application_status', $applicationStatuses);
        }

        if ($period && $period != 'total') {
            $col = 'created_at';
            if ($period == 'day') {
                $year = "YEAR(`{$col}`) = YEAR(NOW())";
                $month = "MONTH(`{$col}`) = MONTH(NOW())";
                $day = "DAY(`{$col}`) = DAY(NOW())";
                $query->where(wpJobBoardDB()->raw("{$year} AND {$month} AND {$day}"));
            } elseif ($period == 'week') {
                $query->where(
                    wpJobBoardDB()->raw("YEARWEEK(`{$col}`, 1) = YEARWEEK(CURDATE(), 1)")
                );
            } elseif ($period == 'month') {
                $year = "YEAR(`{$col}`) = YEAR(NOW())";
                $month = "MONTH(`{$col}`) = MONTH(NOW())";
                $query->where(wpJobBoardDB()->raw("{$year} AND {$month}"));
            } elseif ($period == 'year') {
                $query->where(wpJobBoardDB()->raw("YEAR(`{$col}`) = YEAR(NOW())"));
            }
        }

        return $query->count();
    }

    public function getOtherSubmission($submission)
    {
        $applicantEmail = $submission->applicant_email;


        if(!$applicantEmail) {
            return array();
        }

        $entries = wpJobBoardDB()->table('wjb_applications')
            ->where('wjb_applications.id', '!=', $submission->id)
            ->where('wjb_applications.applicant_email', $applicantEmail)
            ->select(array('wjb_applications.id', 'wjb_applications.created_at', 'wjb_applications.form_id', 'wjb_applications.status',  'posts.post_title'))
             ->join('posts', 'posts.ID', '=', 'wjb_applications.form_id')
            ->orderBy('wjb_applications.created_at', 'DESC')
            ->get();

        foreach ($entries as $entry) {
            $entry->permalink = admin_url('admin.php?page=wpjobboard.php&timestamp='.time().'#/edit-form/'.$entry->form_id.'/entries/'.$entry->id.'/view');
        }

        return $entries;

    }
}
