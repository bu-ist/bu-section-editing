<?php
/*
 Plugin Name: BU Section Editing
 Description: Enhances WordPress content editing workflow by providing section editing groups and permissions
 Version: 0.5
 Author: Boston University (IS&T)
*/

/**
Copyright 2012 by Boston University

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

**/

/*
@author Gregory Cornelius <gcorne@bu.edu>
@author Mike Burns <mgburns@bu.edu>
*/

require_once(dirname(__FILE__) . '/classes.roles-capabilities.php');
require_once(dirname(__FILE__) . '/classes.groups.php');
require_once(dirname(__FILE__) . '/classes.permissions.php');

if(!defined('BU_INCLUDES_PATH')) {
	// @todo We should try to come up with a way of supporting
	// bu-includes that makes use of submodules or some sort of simple build script
	if(!defined('BU_NAVIGATION_LIB_LOADED') || BU_NAVIGATION_LIB_LOADED != true ) {
		require_once(dirname(__FILE__) . '/lib/bu-navigation/bu-navigation.php');
	}
} else {
	require_once(BU_INCLUDES_PATH . '/bu-navigation/bu-navigation.php');
}


define( 'BUSE_PLUGIN_PATH', basename( dirname(__FILE__) ) );

/**
 * Plugin entry point
 */
class BU_Section_Editing_Plugin {
	
	public static $caps;
	public static $roles;
	public static $upgrader;

	const BUSE_VERSION = '0.5';
	const BUSE_VERSION_OPTION = '_buse_version';

	const TEXT_DOMAIN = 'bu_section_editing';

	public static function register_hooks() {

		add_action( 'init', array( __CLASS__, 'init' ) );
		add_action( 'init', array( __CLASS__, 'add_post_type_support' ), 20 );
		add_action( 'init', array( __CLASS__, 'version_check' ), 99 );

		BU_Edit_Groups::register_hooks();

	}

	public static function init() {
		self::$caps = new BU_Section_Capabilities();
		self::$roles = new BU_Section_Editing_Roles();

		// Roles and capabilities
		add_filter( 'map_meta_cap', array( self::$caps, 'map_meta_cap' ), 10, 4 );	
		add_filter( 'bu_user_manager_allowed_roles', array( self::$roles, 'allowed_roles' ) );
		self::$roles->maybe_create();

		// Admin requests
		if( is_admin() ) {
			
			// AJAX
			if( defined('DOING_AJAX') && DOING_AJAX ) {

				require_once(dirname(__FILE__) . '/admin-ajax.groups.php');
				
				BU_Groups_Admin_Ajax::register_hooks();

			} else {

				require_once(dirname(__FILE__) . '/classes.upgrade.php');
				require_once(dirname(__FILE__) . '/admin.groups.php');

				BU_Groups_Admin::register_hooks();

				add_filter( 'plugin_action_links', array( __CLASS__, 'plugin_settings_link' ), 10, 2 );

			}
		
			if( function_exists( 'bu_navigation_get_pages' ) ) {
				require_once( dirname(__FILE__) . '/plugin-support/bu-navigation.php' );
			}
			
		}

	}

	public static function add_post_type_support() {

		// Support posts and pages + all public custom post types by default
		$post_types = get_post_types( array( 'public' => true, '_builtin' => false ) );
		$post_types = array_merge( $post_types, array('post','page') );

		foreach( $post_types as $post_type ) {
			add_post_type_support( $post_type, 'section-editing' );
		}

	}

	public static function plugin_settings_link( $links, $file ) {
		if ( $file != plugin_basename( __FILE__ ))
			return $links;

		$groups_url = admin_url( BU_Groups_Admin::MANAGE_GROUPS_PAGE );
		array_unshift($links, "<a href=\"$groups_url\" title=\"Section Editing Settings\" class=\"edit\">Settings</a>" );

		return $links;
	}

	/**
	 * Checks currently installed plugin version against last version stored in DB,
	 * performing upgrades as needed.
	 */ 
	public static function version_check() {

		$existing_version = get_option( self::BUSE_VERSION_OPTION );

		// Check if plugin has been updated (or just installed) and store current version
		if( $existing_version === false || $existing_version != self::BUSE_VERSION ) {

			// Perform upgrade(s) based on previously installed version
			if( $existing_version ) {

				require_once( dirname(__FILE__) . '/classes.upgrade.php' );
				self::$upgrader = new BU_Section_Editing_Upgrader();

				self::$upgrader->upgrade( $existing_version );

			}

			// Store new version
			update_option( self::BUSE_VERSION_OPTION, self::BUSE_VERSION );

		}

	}

	/**
	 * Placeholder function until we determine the best method to determine how
	 * to grant users the ability to edit sections
	 */
	public static function get_allowed_users( $query_args = array() ) {

		// For now, allowed users are section editors that belong to the current blog
		$default_args = array(
			'role' => 'section_editor'
			);

		$query_args = wp_parse_args( $query_args, $default_args );

		$wp_user_query = new WP_User_Query( $query_args );

		if( isset( $query_args['count_total'] ) )
			return $wp_user_query->get_total();

		return $wp_user_query->get_results();

	}

	/**
	 * Another placeholder -- checks if the given user is allowed by the plugin
	 * to hold section editing priviliges
	 */
	public static function is_allowed_user( $user = null, $query_args = array() ) {

		if( is_null( $user ) ) {
			$user = wp_get_current_user();
		} else if( is_numeric( $user ) ) {
			$user = new WP_User( intval( $user ) );
		}

		if( isset( $user->roles ) && is_array( $user->roles ) ) {

			return( in_array( 'section_editor', $user->roles ) );

		} else {

			error_log( 'Error checking for allowed user: ' . print_r($user,true) );
			return false;
		}

	}

}

BU_Section_Editing_Plugin::register_hooks();

?>
