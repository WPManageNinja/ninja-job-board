<?php

/**
 * Bootstrap for the integration tier: loads a REAL WordPress with this plugin active.
 *
 * SAFETY MODEL
 *
 * The tier runs against a dedicated test site (default ~/Sites/njb-tests, own database
 * `ninja_job_board_tests`, table prefix `njbt_`). Never point it at a site with real content.
 *
 * Tests in tests/php/Integration run inside a transaction that is rolled back in tearDown(), so
 * nothing they write survives. Rules that keep that guarantee:
 *   1. No DDL (CREATE/ALTER/DROP/TRUNCATE) in those tests: DDL commits the transaction.
 *   2. No COMMIT.
 * Tests that need DDL (uninstall) live in tests/php/Destructive and restore the schema themselves.
 *
 * Setup of the test site: see tests/README.md.
 */

if (getenv('NJB_INTEGRATION') !== '1') {
    fwrite(STDERR, "\n[ninja-job-board integration tests] Refusing to run.\n\n"
        . "This tier connects to a real WordPress database. Enable it deliberately:\n\n"
        . "    composer test:integration\n\n"
        . "See tests/README.md for the test site setup.\n\n");
    exit(1);
}

$candidates = array_filter(array(
    getenv('NJB_WP_PATH') ?: null,
    getenv('HOME') . '/Sites/njb-tests',
));

$wpPath = null;
foreach ($candidates as $candidate) {
    if (file_exists(rtrim($candidate, '/') . '/wp-load.php')) {
        $wpPath = realpath(rtrim($candidate, '/'));
        break;
    }
}

if (!$wpPath) {
    fwrite(STDERR, "\n[ninja-job-board integration tests] Could not find wp-load.php. Tried:\n  - "
        . implode("\n  - ", $candidates) . "\nSet NJB_WP_PATH to the test site root (see tests/README.md).\n\n");
    exit(1);
}

// Run as a front-end request; tests switch users and contexts themselves.
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'njb-tests.test';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';
$_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

require_once $wpPath . '/wp-load.php';

global $wpdb;

if ($wpdb->prefix === 'wp_') {
    fwrite(STDERR, "\n[ninja-job-board integration tests] Refusing to run on a site with the default wp_ table prefix.\n"
        . "A non-default prefix is what catches unprefixed SQL. Use the dedicated test site.\n\n");
    exit(1);
}

$pluginFile = 'ninja-job-board/wp-job-board.php';
if (!function_exists('is_plugin_active')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
if (!is_plugin_active($pluginFile) || !class_exists('WPJobBoard')) {
    fwrite(STDERR, "\n[ninja-job-board integration tests] The plugin is not active on {$wpPath}.\n"
        . "Run: wp plugin activate ninja-job-board --path={$wpPath}\n\n");
    exit(1);
}

// The tested code must be this repository, not another copy of the plugin
$loaded = realpath(WPJOBBOARD_DIR);
$repo = realpath(dirname(__DIR__, 2));
if ($loaded !== $repo) {
    fwrite(STDERR, "\n[ninja-job-board integration tests] The active plugin is {$loaded}, not this repository ({$repo}).\n"
        . "Symlink the repository into the test site's wp-content/plugins/ninja-job-board.\n\n");
    exit(1);
}

// Make sure the REST routes are registered once
rest_get_server();

fwrite(STDERR, sprintf(
    "[ninja-job-board integration] WordPress %s, PHP %s, MySQL %s, prefix %s, site %s\n",
    get_bloginfo('version'),
    PHP_VERSION,
    $wpdb->db_version(),
    $wpdb->prefix,
    $wpPath
));

require_once __DIR__ . '/../../vendor/autoload.php';
