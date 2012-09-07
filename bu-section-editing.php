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
	
	const BUSE_VERSION = '0.5';
	const TEXT_DOMAIN = 'bu_section_editing';

	public static function register_hooks() {

		add_action( 'init', array( __CLASS__, 'init' ) );
		add_action( 'init', array( __CLASS__, 'add_post_type_support' ), 20 );

		BU_Edit_Groups::register_hooks();

	}

	public static function init() {

		// Roles and capabilities
		add_filter( 'map_meta_cap', array('BU_Section_Capabilities', 'map_meta_cap'), 10, 4);
		add_filter( 'bu_user_manager_allowed_roles', array( 'BU_Section_Editing_Roles', 'bu_allowed_roles' ) );
		BU_Section_Editing_Roles::maybe_create();

		// Admin requests
		if( is_admin() ) {
			
			require_once(dirname(__FILE__) . '/classes.upgrade.php');
			require_once(dirname(__FILE__) . '/admin.groups.php');
			require_once(dirname(__FILE__) . '/admin-ajax.groups.php');
			
			BU_Groups_Admin::register_hooks();
			BU_Groups_Admin_Ajax::register_hooks();
			BU_Section_Editing_Upgrader::register_hooks();

			add_filter( 'plugin_action_links', array( __CLASS__, 'plugin_settings_link' ), 10, 2 );

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
		array_unshift($links, "<a href=\"$groups_url\" title=\"Manage Section Editing Groups\" class=\"edit\">Manage Groups</a>" );

		return $links;
	}

	/**
	 * Query for all users with the cability to be added to section groups
	 */
	public static function get_allowed_users( $query_args = array() ) {

		$defaults = array(
			'search_columns' => array( 'user_login', 'user_nicename', 'user_email' ),
			);

		$query_args = wp_parse_args( $query_args, $defaults );
		$wp_user_query = new WP_User_Query( $query_args );

		$allowed_users = array();

		// Filter blog users by section editing status
		foreach( $wp_user_query->get_results() as $user ) {

			if( self::is_allowed_user( $user->ID ) )
				$allowed_users[] = $user;

		}

		return $allowed_users;

	}

	/**
	 * Check if a user has the capability to be added to section groups
	 * 
	 * @todo switch cap check to edit_in_section after caps branch is merged
	 */
	public static function is_allowed_user( $user = null ) {

		if( is_null( $user ) ) {
			$user = wp_get_current_user();
		} else if( is_numeric( $user ) ) {
			$user = new WP_User( intval( $user ) );
		}

		// Iterate over ALL roles for this user
		if( isset( $user->roles ) && is_array( $user->roles ) ) {

			foreach( $user->roles as $role ) {
				$role = get_role( $role );

				// Return true if any role is section editing-enabled
				if( $role->has_cap( 'edit_published_in_section' ) )
					return true;
			
			}

		}

		return false;

	}

}

BU_Section_Editing_Plugin::register_hooks();

?>
