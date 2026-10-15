<?php
/**
 * PHPUnit Bootstrap
 *
 * To run these tests:
 * 	1. Install Composer (https://getcomposer.org/)
 *  2. Install plugin development dependencies (`composer install`)
 *  3. Install WordPress unit tests library (`bin/install-wp-tests.sh`)
 *  4. Run `./vendor/bin/phpunit` from the root directory of this plugin
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
