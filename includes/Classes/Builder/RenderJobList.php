<?php

namespace WPJobBoard\Classes\Builder;

use WPJobBoard\Classes\ArrayHelper;
use WPJobBoard\Classes\GeneralSettings;
use WPJobBoard\Classes\Models\Forms;
use WPJobBoard\Classes\View;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render Job List Class
 * @since 1.0.0
 */
class RenderJobList
{
    public function render($args)
    {
        $categoryQuery = [];

        $cats = $args['categories'];

        if ($cats) {
            $cats = explode(',', $cats);
            $formattedCats = [];
            foreach ($cats as $cat) {
                $cat = trim($cat);
                if($cat) {
                    $formattedCats[] = $cat;
                }
            }
            $categoryQuery = [
                'taxonomy' => 'wpjb-job-categories',
                'field'    => 'slug',
                'terms'    => $cats
            ];
        }

        $metaQuery = [];

        if($args['current_only'] == 'yes') {
            $metaQuery = [
                'relation' => 'OR',
                array(
                    'key'     => 'application_end_timestamp',
                    'value'   => time(),
                    'compare' => '>',
                    'type' => 'NUMERIC'
                ),
                array(
                    'key'     => 'application_end_timestamp',
                    'value'   => 0,
                    'compare' => '=',
                    'type' => 'NUMERIC'
                ),
                array(
                    'key'     => 'application_end_timestamp',
                    'value'   => '',
                    'compare' => '=',
                    'type' => 'NUMERIC'
                )
            ];
        }

        $postsArg = [
            'post_type' => 'wp_job_board',
            'posts_per_page' => $args['posts_per_page'],
            'order' => $args['order'],
        ];

        if($categoryQuery) {
            $postsArg['tax_query'] = [
                $categoryQuery
            ];
        }

        if($metaQuery) {
            $postsArg['meta_query'] = $metaQuery;
        }

        $jobs = get_posts($postsArg);

        if($jobs) {
            $this->addAssets();
        } else {
            return 'No Current Job Found';
        }

        $categories = array();
        foreach ($jobs as $job) {
            $expDate = '—';
            $expDateTime = Forms::getExpirationDateTime($job->ID);
            if($expDateTime) {
                $expDate = date('d M, Y', strtotime($expDateTime));
            }
            $experinceText = get_post_meta($job->ID, 'job_experince_text', true);
            $category = $this->getFirstCategory($job->ID);
            $job->exp_date = $expDate;
            $job->job_type = GeneralSettings::getJobTypeName(get_post_meta($job->ID, 'job_type', true));
            $job->experince_text = ($experinceText) ? $experinceText : '—';
            $job->category = $category;
            if($category) {
                $categories[$category->slug] = $category->name;
            }
        }
        $showFilter = $args['show_filter'] == 'yes';

        if($showFilter && $categories) {
            $this->pushJS();
        }

        return View::make('templates.job_list', [
            'jobs' => $jobs,
            'settings' => $args,
            'categories' => $categories,
            'show_filter' => $showFilter
        ]);
    }

    private function getFirstCategory($postId)
    {
        $categoryies = wp_get_post_terms($postId, 'wpjb-job-categories');
        $category = false;
        if($categoryies) {
            $category = $categoryies[0];
        }

        return $category;
    }

    private function pushJS()
    {
        add_action('wp_footer', function () {
            ?>
            <script type="text/javascript">
                jQuery(document).ready(function ($) {
                    $('#wpjb_job_filter').on('change', function () {
                        var selected = $(this).val();
                        var $lists = $(this).closest('.wpjb_job_list_wrapper').find('.wpjb_each_job');
                        if(!selected) {
                            $lists.show();
                            return;
                        }
                        $lists.hide();
                        $.each($lists, function (index, list) {
                            var $list = $(list);
                            if($list.data('job_cat') == selected) {
                                $list.show();
                            }
                        });
                    });
                });
            </script>
            <?php
        }, 100);
    }

    private function addAssets()
    {
        wp_enqueue_style('wpjobboard_list', WPJOBBOARD_URL . 'assets/css/joblist.css', [], WPJOBBOARD_VERSION);
    }
}