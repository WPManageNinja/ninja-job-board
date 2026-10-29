<?php

namespace WPJobBoard\Classes\File;

if (!defined('ABSPATH')) {
    exit;
}

class FileHandler
{
    const FILE_PREFIX_PATTERN = '/^wpf-[a-f0-9]{32}-wpf-[^\/\\\\]+$/';

    private $originalFile;
    private $validations;
    private $file;

    public function __construct($file, $validations = [])
    {
        $this->originalFile = $file;
        $this->validations = $validations;
        $this->file = new File($file['tmp_name'], $file['name']);
    }

    public function validate($rules)
    {
        $errors = [];
        foreach ($rules as $ruleName => $ruleValue) {
            if ($ruleName == 'extensions') {
                $fileExtension = $this->file->guessExtension();
                $nameExtension = strtolower(pathinfo($this->originalFile['name'], PATHINFO_EXTENSION));
                if (!in_array('.' . $fileExtension, $ruleValue, true) || !in_array('.' . $nameExtension, $ruleValue, true)) {
                    $errors[$ruleName] = __('Invalid File Extension', 'ninja-job-board');
                }
            } else if ($ruleName == 'max_file_size' && $ruleValue) {
                $valueInBytes = $ruleValue * 1024 * 1024;
                if ($this->file->getSize() > $valueInBytes) {
                    /* translators: %s: max file size in MB */
                    $errors[$ruleName] = sprintf(__('File size needs to be less than %sMB', 'ninja-job-board'), $ruleValue);
                }
            }
        }
        return $errors;
    }

    public function upload()
    {
        if (!function_exists('wp_handle_upload')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        // test_type stays on: WordPress checks the real file type against the allowed mime list.
        $uploadedFile = wp_handle_upload(
            $this->originalFile,
            ['test_form' => false, 'test_type' => true]
        );
        return $uploadedFile;
    }

    /**
     * Register filters for custom upload dir
     */
    public function overrideUploadDir()
    {
        add_filter('wp_handle_upload_prefilter', function ($file) {
            add_filter('upload_dir', [$this, 'setCustomUploadDir']);

            add_filter('wp_handle_upload', function ($fileinfo) {
                remove_filter('upload_dir', [$this, 'setCustomUploadDir']);
                $fileinfo['file'] = basename($fileinfo['file']);
                return $fileinfo;
            });

            return $this->renameFileName($file);
        });
    }

    /**
     * Set plugin's custom upload dir
     * @param  array $param
     * @return array $param
     */
    public function setCustomUploadDir($param)
    {
        $param['url'] = $param['baseurl'] . WPJOBBOARD_UPLOAD_DIR;
        $param['path'] = $param['basedir'] . WPJOBBOARD_UPLOAD_DIR;
        $param['subdir'] = WPJOBBOARD_UPLOAD_DIR;
        self::ensureProtectedDir($param['path']);

        return $param;
    }


    /**
     * Rename the uploaded file name before saving
     * @param  array $file
     * @return array $file
     */
    public function renameFileName($file)
    {
        $prefix = 'wpf-' . bin2hex(random_bytes(16)) . '-wpf-';
        $file['name'] = $prefix . sanitize_file_name($file['name']);
        return $file;
    }

    /**
     * The plugin's upload directory (path and URL)
     * @return array
     */
    public static function getUploadDir()
    {
        $uploadDir = wp_upload_dir(null, false);
        return [
            'path' => $uploadDir['basedir'] . WPJOBBOARD_UPLOAD_DIR,
            'url'  => $uploadDir['baseurl'] . WPJOBBOARD_UPLOAD_DIR
        ];
    }

    /**
     * Create the upload directory with the index.php and .htaccess guards
     * @param string $path
     * @param bool   $refresh Rewrite the guards even when they exist (after a guard update)
     */
    public static function ensureProtectedDir($path, $refresh = false)
    {
        if (!is_dir($path)) {
            wp_mkdir_p($path);
        }

        $stubs = [
            '.htaccess' => __DIR__ . '/Stubs/htaccess.stub',
            'index.php' => __DIR__ . '/Stubs/index.stub'
        ];

        foreach ($stubs as $fileName => $stub) {
            if ($refresh || !file_exists($path . '/' . $fileName)) {
                copy($stub, $path . '/' . $fileName);
            }
        }
    }

    /**
     * Check that a file name has the random prefix that renameFileName() adds
     * @param string $fileName
     * @return bool
     */
    public static function isPluginFileName($fileName)
    {
        return (bool)preg_match(self::FILE_PREFIX_PATTERN, $fileName);
    }

    /**
     * Check that a URL points to a file directly inside the plugin's upload directory
     * @param string $url
     * @param string $uploadUrl
     * @return bool
     */
    public static function isPluginFileUrl($url, $uploadUrl)
    {
        $stripScheme = function ($value) {
            return preg_replace('#^https?:#i', '', $value);
        };

        $url = $stripScheme($url);
        $base = trailingslashit($stripScheme($uploadUrl));

        if (strpos($url, $base) !== 0) {
            return false;
        }

        $fileName = substr($url, strlen($base));
        return $fileName === sanitize_file_name($fileName) && self::isPluginFileName($fileName);
    }

    /**
     * Transient key for the server-side record of an upload
     * @param string $fileName
     * @return string
     */
    public static function uploadRecordKey($fileName)
    {
        return 'wpjb_upl_' . md5($fileName);
    }

}
