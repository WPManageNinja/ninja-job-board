<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<?php if(!$jobs) : ?>
    <div class="wpjb_job_list_not_found"><?php esc_html_e('No Job found', 'ninja-job-board'); ?></div>
<?php endif;?>
<div class="wpjb_job_list_wrapper">
    <div class="wpjb_job_list_header">
        <div class="wpjb_job_list_header-label"><?php echo wp_kses_post($settings['title']); ?></div>
        <?php if($show_filter && $categories): ?>
        <div class="wpjb_job_filter">
            <select id="wpjb_job_filter" aria-label="<?php esc_attr_e('Filter jobs by category', 'ninja-job-board'); ?>">
                <option value=""><?php esc_html_e('All categories', 'ninja-job-board');?></option>
                <?php foreach ($categories as $wpjobboard_category_slug => $wpjobboard_category): ?>
                <option value="<?php echo esc_attr($wpjobboard_category_slug); ?>"><?php echo esc_html($wpjobboard_category); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
    </div>
    <div class="wpjb_job_lists">
        <?php foreach ($jobs as $wpjobboard_job): ?>
        <div data-job_cat="<?php echo esc_attr(($wpjobboard_job->category) ? $wpjobboard_job->category->slug : ''); ?>" class="wpjb_each_job">
            <div class="wpjb_hover"></div>
            <a class="wpjb_job_url" href="<?php echo esc_url(get_the_permalink($wpjobboard_job)); ?>">
                <div class="wpjb_part wpjb_title">
                    <span class="wpjb_jb_title"><?php echo esc_html($wpjobboard_job->post_title); ?></span>
                    <span class="wpjb_cat"><?php echo ($wpjobboard_job->category) ? esc_html($wpjobboard_job->category->name) : ''; ?></span>
                </div>
                <div class="wpjb_part wpjb_details">
                    <?php if ($wpjobboard_job->location): ?>
                    <span class="wpjb_location"><?php echo esc_html($wpjobboard_job->location); ?></span>
                    <?php endif; ?>
                    <?php if ($wpjobboard_job->experince_text != '—'): ?>
                    <span title="<?php esc_attr_e('Experience', 'ninja-job-board'); ?>" class="wpjb_exp_text"><?php echo esc_html($wpjobboard_job->experince_text); ?></span>
                    <?php endif; ?>
                    <?php if ($wpjobboard_job->exp_date != '—'): ?>
                    <span class="wpjb_exp_date"><?php
                        /* translators: %s: the date applications close */
                        echo esc_html(sprintf(__('Closes %s', 'ninja-job-board'), $wpjobboard_job->exp_date)); ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($wpjobboard_job->job_type): ?>
                <div class="wpjb_part wpjb_type"><?php echo esc_html($wpjobboard_job->job_type); ?></div>
                <?php endif; ?>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>
