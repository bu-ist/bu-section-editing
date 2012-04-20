<?php

class BU_Groups_Admin {

	const MANAGE_GROUPS_PAGE = 'users.php?page=manage_groups';

	public static $manage_groups_hook;

	/**
	 * Register for admin hooks
	 * 
	 * Called from main plugin class during init
	 */ 
	public static function register_hooks() {

		add_action('admin_menu', array( 'BU_Groups_Admin','admin_menus'));
		add_action('admin_enqueue_scripts', array( 'BU_Groups_Admin', 'admin_scripts' ) );

	}

	/**
	 * Add manage groups page
	 * 
	 * @hook admin_menus
	 */ 
	public static function admin_menus() {

		$hook = add_users_page('Section Groups', 'Section Groups', 'promote_users', 'manage_groups', array('BU_Groups_Admin', 'manage_groups_screen'));
		self::$manage_groups_hook = $hook;

		add_action('load-' . $hook, array( 'BU_Groups_Admin', 'load_manage_groups'), 1);

	}

	/**
	 * Register manage group css/js
	 * 
	 * @hook admin_enqueue_scripts
	 */ 
	public static function admin_scripts( $hook ) {

		if( $hook == self::$manage_groups_hook ) {
			wp_enqueue_script( 'group-editor', plugins_url( BUSE_PLUGIN_PATH . '/js/group-editor.js' ), array('jquery') );
			wp_enqueue_style( 'group-editor', plugins_url( BUSE_PLUGIN_PATH . '/css/group-editor.css' ) );
		}

	}

	/**
	 * Handle page load for manage groups, pre-headers send
	 * 
	 * @hook load-users_page_manage_groups
	 * 
	 * This method handles $_REQUEST data and redirection before page is loaded
	 */ 
	static function load_manage_groups() {
		
		if( isset($_REQUEST['action']) ) {

			$groups = BU_Edit_Groups::get_instance();
			$group_id = isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : -1;
			$redirect_url = '';

			switch( $_REQUEST['action'] ) {

				case 'edit':
					$group = $groups->get( $group_id );

					if( $group === false )
						wp_die("The requested section editing group ($group_id) does not exists");					
					break;

				case 'update':
					if( ! check_admin_referer( 'update_section_editing_group' ) )
						wp_die('Cheatin, uh?');

					$group_data = $_POST['group'];

					// if no users are set, array key for users won't exist
					if( ! isset($group_data['users']) ) $group_data['users'] = array();

					// @todo Move validation to controller/model layer?
					if(empty($group_data['name'])) {
						$redirect_url = add_query_arg( array('errors' => 1 ));
						wp_redirect($redirect_url);
						return;
					}

					if( $group_id > 0 ) {
						$groups->update_group($group_id, $group_data);
					} else {
						$groups->add_group($group_data);
					}

					$groups->save();

					$redirect_url = remove_query_arg( array('action','id','tab'));
					break;

				case 'delete':
					if( ! check_admin_referer( 'delete_section_editing_group' ) )
						wp_die('Cheatin, uh?');

					$groups->delete_group( $group_id );

					$groups->save();

					$redirect_url = remove_query_arg( array('action','_wpnonce','id','tab'));
					break;

			}

			if( $redirect_url )
				wp_redirect($redirect_url);

		}

	}

	/**
	 * Display the manage groups admin screen
	 * 
	 * Attached on add_users_page, called during admin_menus
	 */  
	static function manage_groups_screen() {

		$groups = BU_Edit_Groups::get_instance();

		$group_id = isset( $_REQUEST['id'] ) ? (int) $_REQUEST['id'] : -1;
		$action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';
		$tab = isset( $_REQUEST['tab'] ) ? $_REQUEST['tab'] : 'name';
		
		$template_path = 'interface/groups.php';

		switch( $action ) {

			case 'add':
				$template_path = 'interface/edit-group.php';
				$group = new BU_Edit_Group();
				break;

			case 'edit':
				$template_path = 'interface/edit-group.php';
				$group = $groups->get( $group_id );
				break;

			default:
				$template_path = 'interface/groups.php';
				$group_list = new BU_Groups_List();
				break;

		}

		// Render screen
		include $template_path;
	}


	static function group_add_url( $tab = 'name' ) {
		$page = admin_url(self::MANAGE_GROUPS_PAGE);

		$args = array( 'action' => 'add', 'tab' => $tab );
		$url = add_query_arg($args, $page);

		return $url;
	}

	static function group_edit_url( $id = -1, $tab = 'name' ) {
		$page = admin_url(self::MANAGE_GROUPS_PAGE);

		$args = array( 'action' => 'edit', 'id' => $id, 'tab' => $tab );
		$url = add_query_arg($args, $page);

		return $url;
	}

	static function group_delete_url( $id ) {
		$page = admin_url(self::MANAGE_GROUPS_PAGE);

		$url = remove_query_arg( array('tab','errors'), $page );
		$url = add_query_arg( array('id' => $id, 'action' => 'delete' ), $url );
		$url = wp_nonce_url( $url, 'delete_section_editing_group' );

		return $url;
	}
}

class BU_Groups_Admin_Ajax {

	static function register_hooks() {

		add_action('wp_ajax_buse_add_member', array( 'BU_Groups_Admin_Ajax', 'add_member' ) );
		add_action('wp_ajax_buse_find_user', array( 'BU_Groups_Admin_Ajax', 'find_user' ) );

	}

	/**
	 * @todo Rename this method -- it doesn't actally add a member, just verifys
	 * that they are an existing member of the site and have the correct role/capabilities to
	 * be a member of a section editing group.
	 * 
	 */ 
	static function add_member() {

		$groups = BU_Edit_Groups::get_instance();

		$group_id = $_POST['group_id'];
		$user_input = $_POST['user'];
		$output = array();

		$user = get_user_by( 'login', $user_input );

		if( $user && is_user_member_of_blog( $user->ID ) ) {

			$roles = array();

			/* 
			 * WP 3.1 doesn't return $user->roles (fixed in ???)
			 */
			if( ! isset ( $user->roles ) ) {

				/* Option One */

				/*
				global $wpdb;

				$capabilities = $user->{$wpdb->prefix . 'capabilities'};

				if( !isset( $wp_roles ) )
					$wp_roles = new WP_Roles();

				foreach( $wp_roles->role_names as $role => $name ) {

					if ( array_key_exists( $role, $capabilities ) )
						$roles[] = $role;
				}
				*/

				/* Option Two */
				$temp_user = new WP_User( $user->ID );
				
				if( !empty( $temp_user->roles ) && is_array( $temp_user->roles ) ) {
					foreach( $temp_user->roles as $role ) {
						$roles[] = $role;
					}
				}

			} else {

				$roles = $user->roles;
			
			}

			// For now we are limiting group membership to section editors
			// @todo move this check to an isolated class/method so that we can
			// easily switch this behavior later if needed 
			if( in_array( 'section_editor', $roles ) ) {

				$output['status'] = true;
				$output['user_id'] = $user->ID;


			} else { // Otherwise pass on the user ID and status of success

				$output['status'] = false;
				$output['message'] = '<p>' . $user->user_login . ' is not a section editor.</p>';
				$output['user_id'] = $user->ID;
			
			}


		} else { // User was not found

			$output['status'] = false;
			$output['message'] = '<p>' . $user_input . ' is not a member of this site.</p>';

		}

		header("Content-type: application/json");
		echo json_encode( $output );
		die();

	}

	/**
	 * Find users for this site who we might want to add to this group
	 * 
	 * @todo need some logic related to whether or not a user needs permissions,
	 * based on their user role/capabilities
	 */ 
	static function find_user() {

		$groups = BU_Edit_Groups::get_instance();
		$user_input = $_POST['user'];

		// For now we are limiting group membership to section editors
		// @todo move this check to an isolated class/method so that we can
		// easily switch this behavior later if needed 
		$wp_user_search = new WP_User_Query( array( 'blog_id' => 0, 'search' => '*' . $user_input .'*', 'role' => 'section_editor' ) );
		$users = $wp_user_search->get_results();

		header("Content-type: application/json");
		echo json_encode( $users );
		die();
	}

}

?>