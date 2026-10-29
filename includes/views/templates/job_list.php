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
            <select id="wpjb_job_filter">
                <option value=""><?php esc_html_e('-- Show all --', 'ninja-job-board');?></option>
                <?php foreach ($categories as $category_slug => $category): ?>
                <option value="<?php echo esc_attr($category_slug); ?>"><?php echo esc_html($category); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
    </div>
    <div class="wpjb_job_lists">
        <?php foreach ($jobs as $job): ?>
        <div data-job_cat="<?php echo esc_attr(($job->category) ? $job->category->slug : ''); ?>" class="wpjb_each_job">
            <div class="wpjb_hover"></div>
            <a class="wpjb_job_url" href="<?php echo esc_url(get_the_permalink($job)); ?>">
                <div class="wpjb_part wpjb_title">
                    <span class="wpjb_jb_title"><?php echo esc_html($job->post_title); ?></span>
                    <span class="wpjb_cat"><?php echo ($job->category) ? esc_html($job->category->name) : ''; ?></span>
                </div>
                <div title="<?php esc_attr_e('Experience', 'ninja-job-board'); ?>" class="wpjb_part wpjb_years">
                    <span class="wpjb_exp_text"><?php echo esc_html($job->experince_text); ?></span>
                </div>
                <div class="wpjb_part wpjb_type"><?php echo esc_html($job->job_type); ?></div>
                <div title="<?php ($job->exp_date != '—') ? esc_attr_e('Job Expiration Date', 'ninja-job-board') : esc_attr_e('No Expiration Date', 'ninja-job-board');?>" class="wpjb_part wpjb_exp_date">
                    <span><?php echo esc_html($job->exp_date); ?></span>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>
