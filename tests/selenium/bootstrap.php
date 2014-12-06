<?php
/**
 * PHPUnit Bootstrap
 *
 * Note that certain test cases require the BU Navigation plugin:
 * http://wordpress.org/extend/plugins/bu-navigation
 *
 * To run these tests:
 * 	1. Install PHPUnit (http://www.phpunit.de)
 *  2. Install the WordPress Unit Testing Framework (http://unit-test.svn.wordpress.org/trunk)
 *  3. Configure wp-tests-config.php, install WordPress and create a clean DB
 *  4. Set the WP_TESTS_DIR environment variable to point at the WP Unit Testing Framework
 *
 * $ cd wp-content/plugins/bu-section-editing
 * $ phpunit
 */

$GLOBALS['wp_tests_options'] = array(
	'active_plugins' => array( 'bu-section-editing/bu-section-editing.php', /* 'bu-navigation/bu-navigation.php' */ ),
);

require getenv( 'WP_TESTS_DIR' ) . '/includes/bootstrap.php';
