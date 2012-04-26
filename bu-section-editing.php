<?php
/*
 Plugin Name: BU Section Editing
 Description: Enhances WordPress content editing workflow by providing section editing groups and permissions
 Version: 0.1
 Author: Boston University (IS&T)
*/

 require_once('bu-section-roles.php');
 
 require_once('admin.groups.php');
 require_once('classes.groups.php');
 require_once('classes.permissions.php');

 define( 'BUSE_PLUGIN_PATH', basename( dirname(__FILE__) ) );

/**
 * Plugin entry point
 */ 
class BU_Section_Editing_Plugin {

	const BUSE_VERSION = '0.1';
	const BUSE_VERSION_OPTION = '_buse_version';

	public static function register_hooks() {

		register_activation_hook( __FILE__, array('BU_Section_Editing_Plugin','on_activate' ));

		add_action( 'init', array('BU_Section_Editing_Plugin','init') );

	}

	public static function init() {

		// Roles and capabilities
		add_filter('map_meta_cap', array('BU_Section_Editor', 'map_meta_cap'), 10, 4);
		BU_Section_Editing_Roles::maybe_create();

		// Admin
		if( is_admin() ) {
			BU_Groups_Admin::register_hooks();
			BU_Groups_Admin_Ajax::register_hooks();
		}

		add_post_type_support( 'page', 'section-editing' );
		add_post_type_support( 'post', 'section-editing' );

		// Check plugin version
		self::version_check();

	}

	public static function on_activate() {

		self::version_check( true );

	}

	public static function version_check( $activating = false ) {

		$existing_version = get_option( self::BUSE_VERSION_OPTION );

		if( $existing_version === false || $existing_version < self::BUSE_VERSION ) {

			// @todo perform any sort of updates as needed

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

		return $wp_user_query->get_results();

	}

}

BU_Section_Editing_Plugin::register_hooks();

?>