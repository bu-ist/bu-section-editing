<?php
/*
 Plugin Name: BU Section Editing
 Description: Enhances WordPress content editing workflow by providing section editing groups and permissions
 Version: 0.8
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

require_once(dirname(__FILE__) . '/classes.capabilities.php');
require_once(dirname(__FILE__) . '/classes.groups.php');
require_once(dirname(__FILE__) . '/classes.permissions.php');

define( 'BUSE_PLUGIN_PATH', basename( dirname(__FILE__) ) );

define( 'BUSE_NAV_INSTALL_LINK', 'http://wordpress.org/extend/plugins/bu-navigation/' );

define( 'BUSE_NAV_UPGRADE_LINK', 'http://wordpress.org/extend/plugins/bu-navigation/' );

/**
 * Plugin entry point
 */
class BU_Section_Editing_Plugin {

	public static $caps;
	public static $upgrader;

	const BUSE_VERSION = '0.8';
	const BUSE_VERSION_OPTION = '_buse_version';

	const TEXT_DOMAIN = 'bu-section-editing';

	public static function register_hooks() {

		register_activation_hook( __FILE__, array( __CLASS__, 'on_activate' ) );

		add_action( 'init', array( __CLASS__, 'init' ) );
		add_action( 'init', array( __CLASS__, 'add_post_type_support' ), 20 );
		add_action( 'init', array( __CLASS__, 'version_check' ), 99 );

		BU_Edit_Groups::register_hooks();

	}

	public static function init() {

		self::$caps = new BU_Section_Capabilities();

		// Roles and capabilities
		add_filter( 'map_meta_cap', array( self::$caps, 'map_meta_cap' ), 10, 4 );

		// @todo move this to the bu_user_manager plugin
		add_filter( 'bu_user_manager_allowed_roles', array( __CLASS__, 'allowed_roles' ) );

		// Admin requests
		if( is_admin() ) {

			require_once(dirname(__FILE__) . '/admin.groups.php');
			require_once(dirname(__FILE__) . '/admin-ajax.groups.php');

			BU_Groups_Admin::register_hooks();
			BU_Groups_Admin_Ajax::register_hooks();

			add_action( 'load-plugins.php', array( __CLASS__, 'load_plugins_screen' ) );
			add_filter( 'plugin_action_links', array( __CLASS__, 'plugin_settings_link' ), 10, 2 );

			// Load support code for the BU Navigation plugin if it's active
			if( class_exists( 'BU_Navigation_Plugin' ) ) {
				require_once( dirname( __FILE__ ) . '/plugin-support/bu-navigation/section-editor-nav.php' );
			}

		}

	}

	/**
	 * Look for the BU Navigation plugin when this plugin activates
	 */
	public static function on_activate() {

		$msg = '';

		if ( ! class_exists( 'BU_Navigation_Plugin' ) ) {
			$link = 'http://wordpress.org/extend/plugins/bu-navigation'; // @todo replace with actual install link
			$msg = __('<p>The BU Section Editing plugin relies on the BU Navigation plugin for displaying hierarchical permission editors.</p>', self::TEXT_DOMAIN );
			$msg .= __(sprintf('<p>Please install and activate the <a href="%s">BU Navigation plugin</a> in order to set permissions for hierarchical post types.</p>', $link ), self::TEXT_DOMAIN );
		}

		else if ( version_compare( BU_Navigation_Plugin::VERSION, '1.1', '<' ) ) {
			$link = 'http://wordpress.org/extend/plugins/bu-navigation'; // @todo replace with actual upgrade link
			$msg = __('<p>The BU Section Editing plugin relies on the BU Navigation plugin for displaying hierarchical permission editors.</p>', self::TEXT_DOMAIN );
			$msg .= __('<p>This version of BU Section Editing requires at least version 1.1 of BU Navigation.</p>', self::TEXT_DOMAIN );
			$msg .= __(sprintf('<p>Please <a href="%s">upgrade your copy of BU Navigation</a> to enable permissions for hierarchical post types.</p>', $link ), self::TEXT_DOMAIN );
		}

		if ( $msg )
			set_transient( 'buse_nav_dep_nag', $msg );

	}

	/**
	 * Check for the BU Navigation plugin when the user vistis the "Plugins" page
	 */
	public static function load_plugins_screen() {

		add_action( 'admin_notices', array( __CLASS__, 'plugin_dependency_nag' ) );

	}

	/**
	 * Display a notice on the "Plugins" page if a sufficient version of the  BU Navigation plugin is not activated
	 */
	public static function plugin_dependency_nag() {

		$notice = get_transient( 'buse_nav_dep_nag' );

		if ( $notice ) {
			echo "<div class=\"error\">$notice</div>\n";
			delete_transient( 'buse_nav_dep_nag' );
		}

	}

	/**
	 * Callback that adds section editor role to BU list of allowed roles
	 */
	public function allowed_roles( $roles ) {

		if( ! array_key_exists( 'section_editor', $roles ) ) {
			$roles[] = 'section_editor';
		}

		return $roles;

	}

	public static function add_post_type_support() {

		// Support posts and pages + all custom post types with show_ui by default
		$post_types = get_post_types( array( 'show_ui' => true, '_builtin' => false ) );
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
	 * Checks currently installed plugin version against last version stored in DB,
	 * performing upgrades as needed.
	 */
	public static function version_check() {

		$version = get_option( self::BUSE_VERSION_OPTION );

		if( empty( $version ) ) $version = '0';

		// Check if plugin has been updated (or just installed) and store current version
		if( version_compare( $version, self::BUSE_VERSION, '<' ) ) {

			require_once( dirname(__FILE__) . '/classes.upgrade.php' );

			self::$upgrader = new BU_Section_Editing_Upgrader();
			self::$upgrader->upgrade( $version );

			// Store new version
			update_option( self::BUSE_VERSION_OPTION, self::BUSE_VERSION );

		}

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
	 */
	public static function is_allowed_user( $user = null ) {

		if( is_null( $user ) ) {
			$user = wp_get_current_user();
		} else if( is_numeric( $user ) ) {
			$user = new WP_User( intval( $user ) );
		}

		if( is_super_admin( $user->ID ) ) {
			return false;
		}

		if( $user->has_cap( 'edit_in_section' ) ) {
			return true;
		}

		return false;

	}

}

BU_Section_Editing_Plugin::register_hooks();

?>
