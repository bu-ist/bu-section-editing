<?php
/*
 Plugin Name: BU Section Editing
 Description: Enhances WordPress content editing workflow by providing section editing groups and permissions
 Version: 0.2
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

	const BUSE_VERSION = '0.2';
	const BUSE_VERSION_OPTION = '_buse_version';

	public static function register_hooks() {

		register_activation_hook( __FILE__, array('BU_Section_Editing_Plugin','on_activate' ));

		add_action( 'init', array('BU_Section_Editing_Plugin', 'init' ) );
		add_action( 'init', array('BU_Section_Editing_Plugin', 'add_post_type_support' ), 20 );

	}

	public static function init() {

		// Roles and capabilities
		add_filter( 'map_meta_cap', array('BU_Section_Editor', 'map_meta_cap'), 10, 4);
		add_filter( 'bu_user_manager_allowed_roles', array( 'BU_Section_Editing_Roles', 'bu_allowed_roles' ) );
		BU_Section_Editing_Roles::maybe_create();

		// Admin
		if( is_admin() ) {
			BU_Groups_Admin::register_hooks();
			BU_Groups_Admin_Ajax::register_hooks();
		}

		// Check plugin version
		self::version_check();

	}

	public static function add_post_type_support() {

		// Support posts and pages + all public custom post types by default
		$post_types = get_post_types( array( 'public' => true, '_builtin' => false ) );
		$post_types = array_merge( $post_types, array('post','page') );

		foreach( $post_types as $post_type ) {
			add_post_type_support( $post_type, 'section-editing' );
		}

	}

	public static function on_activate() {

		self::version_check();

	}

	public static function version_check() {

		$existing_version = get_option( self::BUSE_VERSION_OPTION );

		// Check if plugin has been updated (or just installed) and store current version
		if( $existing_version === false || $existing_version != self::BUSE_VERSION ) {

			if( $existing_version )
				self::upgrade( $existing_version );

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

	/**
	 * Perform any data modifications as needed based on version diff
	 */ 
	public static function upgrade( $old_version ) {
		global $wpdb;

		if( version_compare( $old_version, '0.2', '<' ) && version_compare( self::BUSE_VERSION, '0.2', '>=' ) ) {
			
			// Upgrade (1.0 -> 2.0)
			$patterns = array( '/^(\d+)$/', '/^(\d+)-denied$/');
			$replacements = array('${1}:allowed', '${1}:denied' );

			// Fetch existing values
			$query = sprintf( 'SELECT `post_id`, `meta_value` FROM %s WHERE `meta_key` = "%s"', $wpdb->postmeta, BU_Edit_Group::META_KEY );
			$posts = $wpdb->get_results( $query );

			// Loop through and update
			foreach( $posts as $post ) {
				$result = preg_replace( $patterns, $replacements, $post->meta_value );
				update_post_meta( $post->post_id, BU_Edit_Group::META_KEY, $result, $post->meta_value );
			}

		} else if( version_compare( $old_version, '0.2', '>=' ) && version_compare( self::BUSE_VERSION, '0.2', '<' )  ) {

			// Downgrade (2.0 -> 1.0)
			$patterns = array( '/^(\d+):allowed$/', '/^(\d+):denied$/');
			$replacements = array('$1', '${1}-denied' );

			// Fetch existing values
			$query = sprintf( 'SELECT `post_id`, `meta_value` FROM %s WHERE `meta_key` = "%s"', $wpdb->postmeta, BU_Edit_Group::META_KEY );
			$posts = $wpdb->get_results( $query );

			// Loop through and update
			foreach( $posts as $post ) {
				$result = preg_replace( $patterns, $replacements, $post->meta_value );
				update_post_meta( $post->post_id, BU_Edit_Group::META_KEY, $result, $post->meta_value );
			}

		}
		
	}

}

BU_Section_Editing_Plugin::register_hooks();

?>