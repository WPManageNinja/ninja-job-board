<?php

namespace WPJobBoard\Classes;

use WPJobBoard\Classes\Builder\Render;
use WPJobBoard\Classes\Builder\RenderJobList;
use WPJobBoard\Classes\Models\Forms;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gutenberg blocks: Job list, Application form and Job details.
 *
 * All three are dynamic blocks rendered in PHP by the same code as the shortcodes,
 * so the front end, the editor preview (ServerSideRender) and [wp_job_list] / [wp_job_form]
 * stay identical. block.json files live in assets/blocks/<name>/ (copied from src/blocks/).
 *
 * @since 2.0.0
 */
class Blocks
{
    const EDITOR_SCRIPT = 'wpjobboard-blocks';
    const JOB_EDITOR_SCRIPT = 'wpjobboard-job-editor';

    public function register()
    {
        add_action('init', array($this, 'registerBlocks'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueueJobEditor'));
    }

    public function registerBlocks()
    {
        $editorDeps = array('wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-i18n', 'wp-server-side-render', 'wp-data', 'wp-core-data');
        wp_register_script(self::EDITOR_SCRIPT, WPJOBBOARD_URL . 'assets/blocks/blocks.js', $editorDeps, WPJOBBOARD_VERSION, true);
        wp_set_script_translations(self::EDITOR_SCRIPT, 'ninja-job-board');

        // The same handles the shortcodes enqueue, so a page with both loads each file once
        wp_register_style('wpjobboard_public', WPJOBBOARD_URL . 'assets/css/wp_job_board-public.css', array(), WPJOBBOARD_VERSION);
        wp_register_style('wpjobboard_list', WPJOBBOARD_URL . 'assets/css/joblist.css', array(), WPJOBBOARD_VERSION);
        wp_register_style('wpjobboard-blocks-editor', WPJOBBOARD_URL . 'assets/blocks/editor.css', array(), WPJOBBOARD_VERSION);

        register_block_type(WPJOBBOARD_DIR . 'assets/blocks/job-list', array(
            'render_callback' => array($this, 'renderJobList')
        ));
        register_block_type(WPJOBBOARD_DIR . 'assets/blocks/application-form', array(
            'render_callback' => array($this, 'renderApplicationForm')
        ));
        register_block_type(WPJOBBOARD_DIR . 'assets/blocks/job-details', array(
            'render_callback' => array($this, 'renderJobDetails')
        ));
    }

    /**
     * Job details panel and job navigation in the block editor, only when editing a job
     */
    public function enqueueJobEditor()
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->post_type !== 'wp_job_board') {
            return;
        }

        wp_enqueue_script(
            self::JOB_EDITOR_SCRIPT,
            WPJOBBOARD_URL . 'assets/blocks/job-editor.js',
            array('wp-plugins', 'wp-editor', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-core-data', 'wp-i18n'),
            WPJOBBOARD_VERSION,
            true
        );
        wp_set_script_translations(self::JOB_EDITOR_SCRIPT, 'ninja-job-board');

        $jobTypes = array();
        foreach (GeneralSettings::getJobTypes() as $value => $label) {
            $jobTypes[] = array('value' => $value, 'label' => $label);
        }

        wp_localize_script(self::JOB_EDITOR_SCRIPT, 'wpJobBoardJobEditor', array(
            'jobTypes'     => $jobTypes,
            'appUrl'       => admin_url('admin.php?page=wpjobboard.php'),
            'canManageApp' => (bool)AccessControl::hasTopLevelMenuPermission()
        ));
    }

    public function renderJobList($attributes, $content = '', $block = null)
    {
        $attributes = wp_parse_args((array)$attributes, array(
            'title'        => __('Job List', 'ninja-job-board'),
            'categories'   => array(),
            'showFilter'   => true,
            'currentOnly'  => true,
            'postsPerPage' => 20,
            'order'        => 'DESC'
        ));

        $categories = array_filter(array_map('sanitize_title', (array)$attributes['categories']));

        $html = (new RenderJobList())->render(array(
            'categories'     => implode(',', $categories),
            'order'          => $attributes['order'] === 'ASC' ? 'ASC' : 'DESC',
            'title'          => sanitize_text_field($attributes['title']),
            'show_apply'     => 'yes',
            'current_only'   => $attributes['currentOnly'] ? 'yes' : 'no',
            'show_cat'       => 'yes',
            'show_filter'    => $attributes['showFilter'] ? 'yes' : 'no',
            'posts_per_page' => min(200, max(1, absint($attributes['postsPerPage'])))
        ));

        return $this->wrap($html);
    }

    public function renderApplicationForm($attributes, $content = '', $block = null)
    {
        $attributes = wp_parse_args((array)$attributes, array(
            'jobId'     => 0,
            'showTitle' => false
        ));

        $form = Forms::getForm($this->jobId($attributes['jobId'], $block));
        if (!$form || ($form->post_status !== 'publish' && !$this->isEditorPreview())) {
            return $this->editorNotice(__('Choose a published job to show its application form.', 'ninja-job-board'));
        }

        $html = (new Render())->render($form->ID, $attributes['showTitle'] ? 'yes' : false);
        if (!$html) {
            return $this->editorNotice(__('This job has no application form fields yet.', 'ninja-job-board'));
        }

        return $this->wrap($html);
    }

    public function renderJobDetails($attributes, $content = '', $block = null)
    {
        $attributes = wp_parse_args((array)$attributes, array(
            'jobId'  => 0,
            'fields' => array('type', 'location', 'vacancies', 'experience', 'posted')
        ));

        $form = Forms::getForm($this->jobId($attributes['jobId'], $block));
        if (!$form) {
            return $this->editorNotice(__('Choose a job to show its details.', 'ninja-job-board'));
        }

        $allowed = array('type', 'location', 'vacancies', 'experience', 'posted');
        $fields = array_values(array_intersect((array)$attributes['fields'], $allowed));

        return $this->wrap(PostType::renderJobMeta($form, $fields));
    }

    /**
     * The chosen job, or the current post when the block sits in a job post
     */
    private function jobId($jobId, $block)
    {
        $jobId = absint($jobId);
        if ($jobId) {
            return $jobId;
        }
        if ($block && !empty($block->context['postId']) && get_post_type($block->context['postId']) === 'wp_job_board') {
            return absint($block->context['postId']);
        }
        $current = get_the_ID();
        return ($current && get_post_type($current) === 'wp_job_board') ? $current : 0;
    }

    private function wrap($html)
    {
        return '<div ' . get_block_wrapper_attributes() . '>' . $html . '</div>';
    }

    /**
     * A hint shown only in the editor preview; visitors see nothing
     */
    private function editorNotice($message)
    {
        if (!$this->isEditorPreview()) {
            return '';
        }
        return '<div ' . get_block_wrapper_attributes(array('class' => 'wpjb_block_notice')) . '>' . esc_html($message) . '</div>';
    }

    private function isEditorPreview()
    {
        return defined('REST_REQUEST') && REST_REQUEST;
    }
}
