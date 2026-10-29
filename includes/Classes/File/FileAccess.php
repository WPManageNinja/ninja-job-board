<?php

namespace WPJobBoard\Classes\File;

use WPJobBoard\Classes\AccessControl;
use WPJobBoard\Classes\Models\Submission;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Serves uploaded application files (CVs etc.) only to users who can view the application.
 *
 * Files stay in uploads/wpjobboard/, but every link the plugin prints points to
 * admin-post.php?action=wpjb_download_file. The handler checks the login, the capability and
 * that the file belongs to the application, then streams it. The upload folder denies direct
 * access on Apache; for Nginx see the FAQ in readme.txt.
 *
 * @since 2.0.0
 */
class FileAccess
{
    const ACTION = 'wpjb_download_file';

    // Bump when the .htaccess stub changes, so existing sites get the new rules
    const PROTECTION_VERSION = '2';

    public function register()
    {
        add_action('admin_post_' . self::ACTION, array($this, 'serve'));
        add_action('admin_post_nopriv_' . self::ACTION, array($this, 'requireLogin'));
        add_action('admin_init', array($this, 'maybeProtectUploadDir'));
    }

    /**
     * The link to a file of an application
     * @param int    $submissionId
     * @param string $fileName Base name of the stored file
     * @param bool   $inline   Show in the browser (images, PDF) instead of downloading
     * @return string
     */
    public static function downloadUrl($submissionId, $fileName, $inline = false)
    {
        $args = array(
            'action'      => self::ACTION,
            'application' => absint($submissionId),
            'file'        => rawurlencode($fileName)
        );
        if ($inline) {
            $args['inline'] = 1;
        }
        return add_query_arg($args, admin_url('admin-post.php'));
    }

    /**
     * Base names of the files stored for an upload field value (array of file URLs)
     * @param mixed $values
     * @return array
     */
    public static function fileNames($values)
    {
        if (!is_array($values)) {
            return array();
        }
        $uploadUrl = FileHandler::getUploadDir()['url'];
        $names = array();
        foreach ($values as $file) {
            if (is_string($file) && FileHandler::isPluginFileUrl($file, $uploadUrl)) {
                $names[] = wp_basename(wp_parse_url($file, PHP_URL_PATH));
            }
        }
        return $names;
    }

    public function requireLogin()
    {
        $current = add_query_arg(wp_unslash($_GET), admin_url('admin-post.php')); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- only used to send the user back here after login; wp_login_url() encodes it
        wp_safe_redirect(wp_login_url(esc_url_raw($current)));
        exit;
    }

    /**
     * Stream a file after the access checks. Read-only, so a nonce is not needed:
     * the link is opened from emails and new tabs.
     */
    public function serve()
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only download, protected by capability and ownership checks
        $submissionId = isset($_GET['application']) ? absint($_GET['application']) : 0;
        $fileName = isset($_GET['file']) ? sanitize_file_name(rawurldecode(wp_unslash($_GET['file']))) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitize_file_name()
        $inline = !empty($_GET['inline']);
        // phpcs:enable

        if (!$submissionId || !$fileName || !FileHandler::isPluginFileName($fileName)) {
            $this->deny(400);
        }

        if (!AccessControl::canAccess('get_submission', 'submissions', 0, $submissionId)) {
            $this->deny(403);
        }

        $submission = (new Submission())->getSubmission($submissionId);
        if (!$submission || !$this->submissionHasFile($submission, $fileName)) {
            $this->deny(404);
        }

        $dir = FileHandler::getUploadDir()['path'];
        $path = $dir . '/' . $fileName;
        $realDir = realpath($dir);
        $realPath = realpath($path);

        if (!$realDir || !$realPath || strpos($realPath, $realDir . DIRECTORY_SEPARATOR) !== 0 || !is_file($realPath)) {
            $this->deny(404);
        }

        $type = wp_check_filetype($fileName);
        $mime = $type['type'] ? $type['type'] : 'application/octet-stream';
        // Only types that browsers show safely may open inline
        $inlineTypes = array('application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/bmp');
        $disposition = ($inline && in_array($mime, $inlineTypes, true)) ? 'inline' : 'attachment';
        $downloadName = preg_replace('/^wpf-[a-f0-9]{32}-wpf-/', '', $fileName);

        global $wp_filesystem;
        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        WP_Filesystem();
        $contents = $wp_filesystem ? $wp_filesystem->get_contents($realPath) : false;
        if ($contents === false) {
            $this->deny(500);
        }

        nocache_headers();
        header('Content-Type: ' . $mime);
        header('Content-Disposition: ' . $disposition . '; filename="' . str_replace('"', '', $downloadName) . '"');
        header('Content-Length: ' . strlen($contents));
        header('X-Content-Type-Options: nosniff');
        header("Content-Security-Policy: default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox");
        echo $contents; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- binary file download, not HTML
        exit;
    }

    /**
     * Write the .htaccess and index.php guards once per protection version (covers sites upgraded from 1.x)
     */
    public function maybeProtectUploadDir()
    {
        if (get_option('wpjobboard_upload_protection') === self::PROTECTION_VERSION) {
            return;
        }
        $dir = FileHandler::getUploadDir()['path'];
        if (is_dir($dir)) {
            FileHandler::ensureProtectedDir($dir, true);
        }
        update_option('wpjobboard_upload_protection', self::PROTECTION_VERSION, false);
    }

    private function submissionHasFile($submission, $fileName)
    {
        $values = is_array($submission->form_data_formatted) ? $submission->form_data_formatted : array();
        foreach ($values as $value) {
            if (in_array($fileName, self::fileNames($value), true)) {
                return true;
            }
        }
        return false;
    }

    private function deny($status)
    {
        wp_die(
            esc_html__('You can not open this file.', 'ninja-job-board'),
            esc_html__('File not available', 'ninja-job-board'),
            array('response' => absint($status))
        );
    }
}
