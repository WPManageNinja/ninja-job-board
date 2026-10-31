<?php
/**
 * Deletes data only when Settings > General > "Delete all data when the plugin is deleted" is on.
 *
 * @package NinjaJobBoard
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

if (!function_exists('wpjobboard_uninstall_site')) :
function wpjobboard_uninstall_site()
{
    global $wpdb;

    if (get_option('wpjobboard_delete_data_on_uninstall') !== 'yes') {
        return;
    }

    // Job posts (their post meta goes with them)
    $jobIds = get_posts(array(
        'post_type'      => 'wp_job_board',
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'fields'         => 'ids'
    ));
    foreach ($jobIds as $jobId) {
        wp_delete_post($jobId, true);
    }

    // Job categories: the taxonomy is not registered while uninstalling
    if (!taxonomy_exists('wpjb-job-categories')) {
        register_taxonomy('wpjb-job-categories', 'wp_job_board');
    }
    $terms = get_terms(array('taxonomy' => 'wpjb-job-categories', 'hide_empty' => false, 'fields' => 'ids'));
    if (!is_wp_error($terms)) {
        foreach ($terms as $termId) {
            wp_delete_term($termId, 'wpjb-job-categories');
        }
    }

    $pageId = absint(get_option('wpjobboard_confirmation_page_id'));
    if ($pageId && get_post_type($pageId) === 'page') {
        wp_delete_post($pageId, true);
    }

    foreach (array('wjb_applications', 'wjb_application_activities', 'wjb_email_campaigns', 'wjb_campaign_emails') as $table) {
        $wpdb->query('DROP TABLE IF EXISTS ' . esc_sql($wpdb->prefix . $table)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared -- fixed table names; uninstall only
    }

    // Options and the upload record transients
    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like('wpjobboard_') . '%', $wpdb->esc_like('_transient_wpjb_upl_') . '%', $wpdb->esc_like('_transient_timeout_wpjb_upl_') . '%')); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- uninstall only

    $uploads = wp_upload_dir(null, false);
    $dir = trailingslashit($uploads['basedir']) . 'wpjobboard';
    if (is_dir($dir)) {
        global $wp_filesystem;
        require_once ABSPATH . 'wp-admin/includes/file.php';
        if (WP_Filesystem()) {
            $wp_filesystem->rmdir($dir, true);
        }
    }

    wp_cache_flush();
}
endif;

if (!function_exists('wpjobboard_uninstall')) :
function wpjobboard_uninstall()
{
    if (!is_multisite()) {
        wpjobboard_uninstall_site();
        return;
    }
    foreach (get_sites(array('fields' => 'ids', 'number' => 0)) as $siteId) {
        switch_to_blog($siteId);
        wpjobboard_uninstall_site();
        restore_current_blog();
    }
}
endif;

wpjobboard_uninstall();
