<?php
/**
 * PHPUnit Bootstrap
 *
 * To run these tests:
 * 	1. Install Composer (https://getcomposer.org/)
 *  2. Install the development dependencies for the selected test lane
 *  3. Install a WordPress 5.8.17-6.9 unit test library (`bin/install-wp-tests.sh` defaults to 6.9)
 *  4. Run PHPUnit 7.5 for WordPress 5.8 or PHPUnit 8 for WordPress 5.9-6.9
 *
 * See CONTRIBUTING.md for the modern and minimum-version test lanes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	if ( 'cli' !== PHP_SAPI && 'phpdbg' !== PHP_SAPI ) {
		exit;
	}
}

$buse_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $buse_tests_dir ) {
	$buse_tests_dir = '/tmp/wordpress-tests-lib';
}

if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__, 2 ) . '/vendor/yoast/phpunit-polyfills' );
}

// Load plugin files from this directory manually (as mu-plugin)
require_once $buse_tests_dir . '/includes/functions.php';

function buse_manually_load_plugin() {
	require dirname( __FILE__ ) . '/../../bu-section-editing.php';
}

tests_add_filter( 'muplugins_loaded', 'buse_manually_load_plugin' );

// Import WordPress unit test bootstrap
require $buse_tests_dir . '/includes/bootstrap.php';

// Custom section group factory
require dirname( __FILE__ ) . '/includes/class-group-factory.php';
