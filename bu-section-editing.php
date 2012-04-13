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

 define( 'BUSE_PLUGIN_PATH', basename( dirname(__FILE__) ) );
 define( 'BUSE_VERSION', '0.1' );

/**
 * Plugin entry point
 */ 
class BU_Section_Editing_Plugin {

	// Group editor admin page hook
	public static $group_admin_page;

	public static function init() {
		global $bu_edit_groups;

		// Admin
		add_action('admin_menu', array('BU_Section_Editing_Plugin','admin_menus'));
		add_action('admin_enqueue_scripts', array('BU_Section_Editing_Plugin', 'admin_scripts' ) );

		// Roles and capabilities
		add_filter('map_meta_cap', array('BU_Section_Editor', 'map_meta_cap'), 10, 4);
		BU_Section_Editing_Roles::maybe_create();

		// Admin AJAX registrations
		if( is_admin() ) {
			BU_Groups_Admin_Ajax::register_hooks();
		}

		// Check plugin version
		self::version_check();

	}

	public static function on_activate() {

		update_option( 'buse_version', BUSE_VERSION );

	}

	// Add administrative menu items
	public static function admin_menus() {

		$hook = add_users_page('Section Editor Permissions', 'Section Editor Permissions', 'promote_users', 'manage_groups', array('BU_Groups_Admin', 'manage_groups_screen'));
		self::$group_admin_page = $hook;

		add_action('load-' . $hook, array('BU_Groups_Admin', 'load_manage_groups'), 1);

	}

	// Add administrative scripts on appropriate pages
	public static function admin_scripts( $hook ) {

		if( $hook == self::$group_admin_page ) {
			wp_enqueue_script( 'group-editor', plugins_url( BUSE_PLUGIN_PATH . '/js/group-editor.js' ), array('jquery') );
			wp_enqueue_style( 'group-editor', plugins_url( BUSE_PLUGIN_PATH . '/css/group-editor.css' ) );
		}

	}

	public static function version_check() {

		$existing_version = get_option( 'buse_version' );

		if( $existing_version === false || $existing_version < BUSE_VERSION ) {

			// @todo perform any sort of updates as needed

			update_option( 'buse_version', BUSE_VERSION );

		}

	}

}

register_activation_hook( __FILE__, array('BU_Section_Editing_Plugin','on_activate' ));
add_action( 'init', array('BU_Section_Editing_Plugin','init') );

 ?>