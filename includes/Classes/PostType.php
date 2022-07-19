<?php

namespace WPJobBoard\Classes;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register and initialize custom post type for Payment Forms
 * @since 1.0.0
 */
class PostType
{

    private $postTypeName = 'wp_job_board';

    public function __construct()
    {
        add_action('init', array($this, 'register'));

        // Disable Gutenber for this post type
        add_filter('use_block_editor_for_post_type', array($this, 'maybeDisableGutenberg'), 10, 2);

        add_filter('the_content', array($this, 'pushApplicationForm'), 10, 2);

        add_action('delete_post', array($this, 'maybeDeleteAssociateData'));

        add_action('add_meta_boxes', array($this, 'registerMetaBox'));
        add_action('save_post_' . $this->postTypeName, array($this, 'saveMetaData'));

    }

    public function maybeDisableGutenberg($status, $postType)
    {
        if ($postType == $this->postTypeName) {
            return false;
        }
        return $status;
    }

    public function register()
    {
        $labels = [
            'name'          => 'Jobs',
            'singular_name' => 'Job',
            'add_new'       => 'Add New Job',
            'edit_item'     => 'Edit Job',
            'view_item'     => 'View Job Post',
            'view_items'    => 'View Job Posts',
            'search_items'  => 'Search Job Posts',
            'not_found'     => 'No Job Found',
        ];

        $postSlug = apply_filters('wpjobboard/job_slug', 'wpjb-jobs');

        $args = array(
            'labels'             => $labels,
            'publicly_queryable' => true,
            'show_in_menu'       => false,
            'capability_type'    => 'post',
            'public'             => true,
            'show_in_admin_bar'  => false,
            'has_archive'        => true,
            'supports'           => [
                'title',
                'editor',
                'thumbnail'
            ],
            'show_in_rest'       => true,
            'rewrite'            => array('slug' => $postSlug),
        );
        register_post_type($this->postTypeName, $args);

        $this->registerJobCategories();
    }

    public function registerJobCategories()
    {
        // Add new taxonomy, make it hierarchical (like categories)
        $labels = array(
            'name'              => _x('Job Category', 'taxonomy general name', 'textdomain'),
            'singular_name'     => _x('Job Category', 'taxonomy singular name', 'textdomain'),
            'search_items'      => __('Search Job Categories', 'textdomain'),
            'all_items'         => __('All Job Categories', 'textdomain'),
            'parent_item'       => __('Parent Job Category', 'textdomain'),
            'parent_item_colon' => __('Parent Job Category:', 'textdomain'),
            'edit_item'         => __('Edit Job Category', 'textdomain'),
            'update_item'       => __('Update Job Category', 'textdomain'),
            'add_new_item'      => __('Add New Job Category', 'textdomain'),
            'new_item_name'     => __('New Job Category Name', 'textdomain'),
            'menu_name'         => __('Job Category', 'textdomain'),
        );

        $categorySlug = apply_filters('wpjobboard/job_category_slug', 'wpjb-job-categories');

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => false,
            'query_var'         => true,
            'rewrite'           => array('slug' => $categorySlug),
        );

        register_taxonomy('wpjb-job-categories', array($this->postTypeName), $args);
    }

    public function pushApplicationForm($content)
    {
        if (!is_singular('wp_job_board')) {
            return $content;
        }
        $formRenderer = new \WPJobBoard\Classes\Builder\Render();
        global $post;
        $form = $formRenderer->render($post->ID, false);

        $jobMetaHtml = $this->getMetaData($post);

        if($jobMetaHtml) {
            $content = $jobMetaHtml.$content;
        }

        if (!$form) {
            return $content;
        }

        $applyText = __('Apply for this position', 'wpjobboard');
        $contentBtn = '<div class="wpjb_job_application_wrapper"><div class="wpjb_job_apply"><button class="btn wpjb_job_apply_btn">' . $applyText . '</button></div><div style="display: none;" class="wpjb_job_form_wrapper">' . $form . '</div></div>';

        return $content . $contentBtn;
    }

    private function getMetaData($post)
    {
        $postId  = $post->ID;
        $location = get_post_meta($postId, 'job_location', true);
        $vacancies = get_post_meta($postId, 'job_vacancies', true);
        $experinceText = get_post_meta($postId, 'job_experince_text', true);

        $jobType = GeneralSettings::getJobTypeName(get_post_meta($postId, 'job_type', true));


        $display_date = sprintf( esc_html__( '%s ago', 'wpjobboard' ), human_time_diff( get_post_time( 'U', false, $post ), current_time( 'timestamp' ) ) );

        $html = '<ul class="wpjb_job_meta">';
        if($jobType) {
            $html .= '<li class="wpjb_job_type">'.$jobType.'</li>';
        }
        if($location) {
            $html .= '<li class="wpjb_job_location"><img class="wpjb_meta_icon" src="'.WPJOBBOARD_URL.'assets/images/map.svg" />'.$location.'</li>';
        }
        if($vacancies) {
            $vacanciesText = __('No. of Vacancies:', 'wpjobboard');
            if(intval($vacancies) == 1) {
                $vacanciesText = __('No. of Vacancy:', 'wpjobboard');
            }
            $html .= '<li class="wpjb_job_vacancies"><img class="wpjb_meta_icon" src="'.WPJOBBOARD_URL.'assets/images/people.svg" />'.$vacanciesText.' '.$vacancies.'</li>';
        }
        if($experinceText) {
            $html .= '<li class="wpjb_job_exp_text"><img class="wpjb_meta_icon" src="'.WPJOBBOARD_URL.'assets/images/exp.svg" />'.$experinceText.'</li>';
        }

        $html .= '<li class="wpjb_job_time"><img class="wpjb_meta_icon" src="'.WPJOBBOARD_URL.'assets/images/time.svg" /><time datetime="' . esc_attr( get_post_time( 'Y-m-d', false, $post ) ) . '">' . wp_kses_post( $display_date ) . '</time></li>';

        $html .= '</ul>';

        $html .= $this->getMetaCSS();
        return $html;
    }

    private function getMetaCSS()
    {
        ob_start();
        ?>
            <style type="text/css">
                .wpjb_meta_icon {
                    display: inline-block;
                    width: 16px;
                    margin-right: 2px;
                }
                ul.wpjb_job_meta {
                    display: block;
                    overflow: hidden;
                    list-style: none;
                    margin: 10px 0px 20px !important;
                    padding: 0;
                    width: 100%;
                }
                ul.wpjb_job_meta li {
                    display: inline-block;
                    padding: 0px 8px;
                }
                ul.wpjb_job_meta li:last-child {
                    margin-right: 0px;
                }
                ul.wpjb_job_meta li.wpjb_job_type {
                    background-color: #f7e2c5;
                    color: #906b37;
                    border-radius: 3px;
                }
            </style>
        <?php
        return ob_get_clean();
    }

    public function maybeDeleteAssociateData($postId)
    {
        if ($this->postTypeName != get_post_type($postId)) {
            return;
        }

        wpJobBoardDB()->table('wjb_applications')
            ->where('form_id', $postId)
            ->delete();

        wpJobBoardDB()->table('wjb_application_activities')
            ->where('form_id', $postId)
            ->delete();
    }

    public function registerMetaBox()
    {
        add_meta_box(
            'wpjobboard_metabox_job_info',
            esc_html__('Job Info', 'wpjobboard'),
            array($this, 'renderJobMetaBox'),
            $this->postTypeName,
            'normal',
            'core'
        );
    }

    public function renderJobMetaBox($post)
    {
        $location = get_post_meta($post->ID, 'job_location', true);
        $vacancies = get_post_meta($post->ID, 'job_vacancies', true);
        $experinceText = get_post_meta($post->ID, 'job_experince_text', true);
        $jobType = get_post_meta($post->ID, 'job_type', true);
        $availableJobTypes = GeneralSettings::getJobTypes();
        ?>
        <input type="hidden" name="wpjobboard_job_meta" value="1"/>
        <div class="wpjb_meta_field">
            <label><?php _e('Location', 'wpjobboard');?></label>
            <div class="wpjb_input_container">
                <input placeholder="Location" type="text" value="<?php echo esc_html($location); ?>"
                       name="wpjobboard_job_meta_values[job_location]"/>
            </div>
        </div>
        <div class="wpjb_meta_field">
            <label><?php _e('Number of Vacancies', 'wpjobboard');?></label>
            <div class="wpjb_input_container">
                <input placeholder="Number of Vacancies" type="number" value="<?php echo esc_html($vacancies); ?>"
                       name="wpjobboard_job_meta_values[job_vacancies]"/>
            </div>
        </div>
        <div class="wpjb_meta_field">
            <label><?php _e('Experince Year Requirement', 'wpjobboard');?> </label>
            <div class="wpjb_input_container">
                <input placeholder="ex: 1-2 Years" type="text" value="<?php echo esc_html($experinceText); ?>"
                       name="wpjobboard_job_meta_values[job_experince_text]"/>
            </div>
        </div>
        <div class="wpjb_meta_field">
            <label><?php _e('Job Type', 'wpjobboard');?> </label>
            <div class="wpjb_input_container">
                <select name="wpjobboard_job_meta_values[job_type]">
                    <option value="">Select Job Type</option>
                    <?php foreach ($availableJobTypes as $type => $label): ?>
                    <option <?php selected($jobType, $type); ?> value="<?php echo esc_html($type); ?>"><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <?php
    }

    public function saveMetaData($postId)
    {
        if(isset($_REQUEST['wpjobboard_job_meta']) && $_REQUEST['wpjobboard_job_meta']) {
            $metaValues = $_REQUEST['wpjobboard_job_meta_values'];
            foreach ($metaValues as $metaKey => $metaValue) {
                update_post_meta($postId, $metaKey, sanitize_text_field($metaValue) );
            }
        }
    }
}
