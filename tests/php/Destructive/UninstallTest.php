<?php

namespace NinjaJobBoard\Tests\Destructive;

use PHPUnit\Framework\TestCase;
use WPJobBoard\Classes\Activator;
use WPJobBoard\Classes\File\FileHandler;

/**
 * uninstall.php drops tables (DDL), which commits any transaction, so this test runs outside one,
 * in its own suite after the integration suite. It rebuilds the plugin schema in tearDown().
 */
class UninstallTest extends TestCase
{
    private $jobId;
    private $file;

    protected function setUp(): void
    {
        parent::setUp();
        global $wpdb;

        $this->jobId = wp_insert_post(array('post_type' => 'wp_job_board', 'post_status' => 'publish', 'post_title' => 'Uninstall job'));
        $wpdb->insert($wpdb->prefix . 'wjb_applications', array(
            'form_id' => $this->jobId, 'applicant_name' => 'Ada', 'applicant_email' => 'ada@example.com',
            'form_data_raw' => 'a:0:{}', 'form_data_formatted' => 'a:0:{}', 'application_status' => 'applied', 'status' => 'new',
            'submission_hash' => 'wpjb_' . bin2hex(random_bytes(16)), 'created_at' => gmdate('Y-m-d H:i:s'), 'updated_at' => gmdate('Y-m-d H:i:s')
        ));
        update_option('wpjobboard_ip_logging_status', 'yes');

        $dir = FileHandler::getUploadDir();
        FileHandler::ensureProtectedDir($dir['path']);
        $this->file = $dir['path'] . '/wpf-' . bin2hex(random_bytes(16)) . '-wpf-cv.pdf';
        file_put_contents($this->file, '%PDF-1.4'); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- test fixture
    }

    protected function tearDown(): void
    {
        global $wpdb;
        // Rebuild what uninstall removed, so the next run starts from an installed plugin
        (new Activator())->migrateDatabases(false);
        delete_option('wpjobboard_delete_data_on_uninstall');
        if ($this->jobId && get_post($this->jobId)) {
            wp_delete_post($this->jobId, true);
        }
        $wpdb->query('DELETE FROM ' . $wpdb->prefix . "wjb_applications WHERE applicant_email = 'ada@example.com'");
        if (is_file($this->file)) {
            wp_delete_file($this->file);
        }
        wp_cache_flush();
        parent::tearDown();
    }

    public function test_uninstall_keeps_everything_without_the_opt_in_and_removes_everything_with_it()
    {
        delete_option('wpjobboard_delete_data_on_uninstall');
        $this->runUninstall();

        $this->assertCount(4, $this->pluginTables(), 'Tables were removed without the opt-in');
        $this->assertNotNull(get_post($this->jobId));
        $this->assertFileExists($this->file);

        update_option('wpjobboard_delete_data_on_uninstall', 'yes');
        $this->runUninstall();

        $this->assertSame(array(), $this->pluginTables());
        $this->assertNull(get_post($this->jobId));
        $this->assertFalse(get_option('wpjobboard_ip_logging_status'));
        $this->assertFalse(get_option('wpjobboard_delete_data_on_uninstall'));
        $this->assertDirectoryDoesNotExist(dirname($this->file));
    }

    private function runUninstall()
    {
        if (!defined('WP_UNINSTALL_PLUGIN')) {
            define('WP_UNINSTALL_PLUGIN', 'ninja-job-board/wp-job-board.php');
        }
        wp_cache_flush();
        include WPJOBBOARD_DIR . 'uninstall.php';
        wp_cache_flush();
    }

    private function pluginTables()
    {
        global $wpdb;
        return $wpdb->get_col($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($wpdb->prefix . 'wjb_') . '%'));
    }
}
