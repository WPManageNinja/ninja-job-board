<?php

namespace WPJobBoard\Classes;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ajax Handler Class
 * @since 1.0.0
 */
class Activator
{
    public $wpfDbVersion = WPJOBBOARD_DB_VERSION;

    public function migrateDatabases($network_wide = false)
    {
        global $wpdb;
        if ($network_wide) {
            // Retrieve all site IDs from this network (WordPress >= 4.6 provides easy to use functions for that).
            if (function_exists('get_sites') && function_exists('get_current_network_id')) {
                $site_ids = get_sites(array('fields' => 'ids', 'network_id' => get_current_network_id()));
            } else {
                $site_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs WHERE site_id = $wpdb->siteid;");
            }
            // Install the plugin for all these sites.
            foreach ($site_ids as $site_id) {
                switch_to_blog($site_id);
                $this->migrate();
                restore_current_blog();
            }
        } else {
            $this->migrate();
        }
    }

    public function migrate()
    {
        $this->createSubmissionsTable();
        $this->createSubmissionActivitiesTable();

        $this->createCampaignDB();

        $this->createPages();

        include 'PostType.php';
        $postTypeClass = new PostType();
        $postTypeClass->register();
        flush_rewrite_rules(true);
    }

    public function createCampaignDB() {
        $this->createCampaignsTable();
        $this->createCampaignEmails();
    }

    public function createSubmissionsTable()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'wjb_applications';

        $sql = "CREATE TABLE $table_name (
				id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				form_id int(11) NOT NULL,
				user_id int(11) DEFAULT NULL,
				applicant_name varchar(255),
				applicant_email varchar(255),
				form_data_raw longtext,
				form_data_formatted longtext,
				application_status varchar(255),
				submission_hash varchar (255),
				status varchar(255),
				overall_score int(11) DEFAULT NULL,
				ip_address varchar (45),
				browser varchar(45),
				device varchar(45),
				city varchar(45),
				country varchar(45),
				created_at timestamp NULL,
				updated_at timestamp NULL
			) $charset_collate;";

        return $this->runSQL($sql, $table_name);
    }

    public function createSubmissionActivitiesTable($forced = false)
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'wjb_application_activities';

        $sql = "CREATE TABLE $table_name (
				id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				form_id int(11) NOT NULL,
				submission_id int(11) NOT NULL,
				type varchar(255),
				created_by varchar(255),
				created_by_user_id int(11),
				title varchar(255),
				content text,
				created_at timestamp NULL,
				updated_at timestamp NULL
			) $charset_collate;";
        return $this->runSQL($sql, $table_name);
    }

    public function createCampaignsTable()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'wjb_email_campaigns';

        $sql = "CREATE TABLE $table_name (
				id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				form_id int(11) NOT NULL,
				status varchar(255) default 'draft',
				title varchar(255),
				subject varchar(255),
				body longtext,
				campaign_settings text,
				email_type varchar (255) DEFAULT 'html',
				total_sent int(11) default 0,
				created_by_user_id int(11),
				created_at timestamp NULL,
				updated_at timestamp NULL
			) $charset_collate;";
        return $this->runSQL($sql, $table_name);
    }


    public function createCampaignEmails()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'wjb_campaign_emails';

        $sql = "CREATE TABLE $table_name (
				id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				campaign_id int(11) NOT NULL,
				submission_id int(11) NOT NULL,
				status varchar(255) default 'draft',
				email_to varchar(255),
				subject varchar(255),
				body longtext,
				email_type varchar (255) DEFAULT 'html',
				sent_at timestamp NULL,
				created_at timestamp NULL,
				updated_at timestamp NULL
			) $charset_collate;";
        return $this->runSQL($sql, $table_name);
    }

    private function runSQL($sql, $tableName)
    {
        global $wpdb;
        if ($wpdb->get_var("SHOW TABLES LIKE '$tableName'") != $tableName) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            return true;
        }
        return false;
    }

    /**
     * Create the pages for success and failure redirects
     */
    public function createPages()
    {
        $options = get_option('wpjobboard_confirmation_page_id');
        if (false === $options) {
            $charge_confirmation = wp_insert_post(array(
                'post_title'     => __('Job Application Confirmation', 'ninja-job-board'),
                'post_content'   => '[wpjobboard_confirmation]',
                'post_status'    => 'publish',
                'post_author'    => 1,
                'post_type'      => 'page',
                'comment_status' => 'closed',
            ));
            update_option('wpjobboard_confirmation_page_id', $charge_confirmation);
        }
    }
}
