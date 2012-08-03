<?php
/*
 Plugin Name: BU Section Editing
 Description: Enhances WordPress content editing workflow by providing section editing groups and permissions
 Version: 0.4
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
// @todo only load admin code when is_admin()
require_once(dirname(__FILE__) . '/admin.groups.php');
require_once(dirname(__FILE__) . '/classes.groups.php');
require_once(dirname(__FILE__) . '/classes.permissions.php');
require_once(dirname(__FILE__) . '/classes.upgrade.php');

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

// @see apply_filters('wp_insert_post_parent') which could be used to check whether a user is permitted to move a post

// do_action("{$old_status}_to_{$new_status}", $post); internally WordPress uses 'new' status as the
// previous status when creating a new post
// the status could be used to propagate the ACL of the parent to the new draft if the user has placed
// the draft in an editable location


/**
 * Plugin entry point
 */
class BU_Section_Editing_Plugin {

	const BUSE_VERSION = '0.4';

	public static function register_hooks() {

		add_action( 'init', array( 'BU_Section_Editing_Plugin', 'init' ) );
		add_action( 'init', array( 'BU_Section_Editing_Plugin', 'add_post_type_support' ), 20 );

		add_action( 'init', array( 'BU_Edit_Groups', 'register_post_type' ) );

		// Run last so all post types are registered
		add_action( 'init', array( 'BU_Section_Editing_Upgrader', 'version_check' ), 99 );

	}

	public static function init() {

		// Roles and capabilities
		add_filter( 'map_meta_cap', array('BU_Section_Capabilities', 'map_meta_cap'), 10, 4);
		add_filter( 'bu_user_manager_allowed_roles', array( 'BU_Section_Editing_Roles', 'bu_allowed_roles' ) );
		BU_Section_Editing_Roles::maybe_create();

		// Admin
		if( is_admin() ) {
			BU_Groups_Admin::register_hooks();
			BU_Groups_Admin_Ajax::register_hooks();
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