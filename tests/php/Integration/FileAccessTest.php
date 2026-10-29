<?php

namespace NinjaJobBoard\Tests\Integration;

use NinjaJobBoard\Tests\IntegrationTestCase;
use WPJobBoard\Classes\File\FileAccess;

/**
 * Uploaded files (CVs) are personal data: only users who can view the application get them.
 */
class FileAccessTest extends IntegrationTestCase
{
    private $formId;
    private $file;
    private $applicationId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formId = $this->makeJob();
        $this->file = $this->makeUploadedFile('ada-cv.pdf');
        $this->applicationId = $this->makeApplication($this->formId, array('file_upload_input' => array($this->file['url'])));
    }

    public function test_admin_gets_the_file_of_the_application()
    {
        $this->actAsAdmin();
        $result = FileAccess::resolveFile($this->applicationId, $this->file['name']);

        $this->assertSame(200, $result['status']);
        $this->assertSame(realpath($this->file['path']), $result['path']);
    }

    public function test_anonymous_and_users_without_permission_are_denied()
    {
        wp_set_current_user(0);
        $this->assertSame(403, FileAccess::resolveFile($this->applicationId, $this->file['name'])['status']);

        $this->actAs($this->makeUser('subscriber'));
        $this->assertSame(403, FileAccess::resolveFile($this->applicationId, $this->file['name'])['status']);
    }

    public function test_own_forms_user_can_not_open_files_of_other_forms()
    {
        $this->actAs($this->makeUser('subscriber', array('wpjb_can_view_entries_of_own_created_forms')));
        $this->assertSame(403, FileAccess::resolveFile($this->applicationId, $this->file['name'])['status']);
    }

    public function test_a_file_of_another_application_can_not_be_opened_through_this_one()
    {
        $otherFile = $this->makeUploadedFile('other-cv.pdf');
        $this->makeApplication($this->formId, array('file_upload_input' => array($otherFile['url'])));

        $this->actAsAdmin();
        $this->assertSame(404, FileAccess::resolveFile($this->applicationId, $otherFile['name'])['status']);
    }

    public function test_names_outside_the_plugin_file_pattern_are_rejected()
    {
        $this->actAsAdmin();
        foreach (array('../../wp-config.php', 'index.php', '.htaccess', '') as $name) {
            $this->assertSame(400, FileAccess::resolveFile($this->applicationId, $name)['status'], 'Accepted: ' . $name);
        }
    }

    public function test_unknown_application_is_not_found()
    {
        $this->actAsAdmin();
        $this->assertSame(404, FileAccess::resolveFile(999999, $this->file['name'])['status']);
    }

    public function test_the_upload_folder_guards_deny_direct_access_on_apache()
    {
        // Write the guards into a fresh folder, so the test sees the current stub and not an old copy
        $dir = get_temp_dir() . 'njb-guards-' . wp_generate_password(8, false);
        \WPJobBoard\Classes\File\FileHandler::ensureProtectedDir($dir);

        $htaccess = file_get_contents($dir . '/.htaccess'); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- test
        $hasIndex = file_exists($dir . '/index.php');
        array_map('wp_delete_file', glob($dir . '/{,.}*', GLOB_BRACE) ?: array());
        rmdir($dir); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- test cleanup

        $this->assertStringContainsString('Require all denied', $htaccess);
        $this->assertStringContainsString('Deny from all', $htaccess);
        $this->assertStringNotContainsString('Require all granted', $htaccess);
        $this->assertTrue($hasIndex, 'index.php guard is missing');
    }
}
