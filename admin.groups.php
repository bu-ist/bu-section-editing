<?php

/*
@todo Need to add an edit lock to editing groups (look at navman)
*/

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

			wp_enqueue_script( 'jstree', plugins_url( BUSE_PLUGIN_PATH . '/js/lib/jstree/jquery.jstree.js' ), array('jquery') );
			wp_enqueue_script( 'group-editor', plugins_url( BUSE_PLUGIN_PATH . '/js/group-editor.js' ), array('jquery') );

			wp_enqueue_style( 'jstree-default', plugins_url( BUSE_PLUGIN_PATH . '/js/lib/jstree/themes/classic/style.css' ) );
			wp_enqueue_style( 'group-editor', plugins_url( BUSE_PLUGIN_PATH . '/css/group-editor.css' ) );

			$buse_config = array(
				'adminUrl' => admin_url( 'admin-ajax.php' ),
				'pluginUrl' => plugins_url( BUSE_PLUGIN_PATH )
				);

			wp_localize_script( 'group-editor', 'buse_config', $buse_config );

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


	/**
	 * Generates query string for manage groups page request
	 * 
	 * @param string $action manage groups action: add, edit or delete
	 * @param array $extra_args optiona list of extra query args to be added
	 * @return string $url
	 */ 
	static function manage_groups_url( $action, $extra_args = array() ) {
		$page = admin_url( self::MANAGE_GROUPS_PAGE );

		$args = array_merge( array( 'action' => $action ), $extra_args );

		$url = add_query_arg( $args, $page );

		if( $action == 'delete' )
			$url = wp_nonce_url( $url, 'delete_section_editing_group' );

		return $url;
	}

}

/**
 * Centralized admin ajax routing
 */ 
class BU_Groups_Admin_Ajax {

	static function register_hooks() {

		add_action('wp_ajax_buse_add_member', array( 'BU_Groups_Admin_Ajax', 'add_member' ) );
		add_action('wp_ajax_buse_find_user', array( 'BU_Groups_Admin_Ajax', 'find_user' ) );
		add_action('wp_ajax_buse_fetch_children', array( 'BU_Groups_Admin_Ajax', 'render_post_children' ) );

	}

	/**
	 * Add user to current edit group screen if they are valid
	 */ 
	static function add_member() {

		$groups = BU_Edit_Groups::get_instance();

		$group_id = $_POST['group_id'];
		$user_input = $_POST['user'];
		$output = array();

		$users = BU_Section_Editing_Plugin::get_allowed_users( array( 'search' => $user_input ) );

		if( is_array( $users ) && ! empty( $users ) ) {

			if( count( $users ) > 1 ) {
				error_log('More than one users were returned for input: ' . $user_input );
				die();
			}

			$user = $users[0];

			$output['status'] = true;
			$output['user_id'] = $user->ID;

		} else { // User was not found

			$output['status'] = false;
			$output['message'] = '<p>' . $user_input . ' is not a member of this site or does not have permission to edit sections.</p>';

		}

		header("Content-type: application/json");
		echo json_encode( $output );
		die();

	}

	/**
	 * Find valid users based on input string
	 */ 
	static function find_user() {

		$groups = BU_Edit_Groups::get_instance();
		$user_input = $_POST['user'];

		// For now we are limiting group membership to section editors
		// @todo move this check to an isolated class/method so that we can
		// easily switch this behavior later if needed
		$users = BU_Section_Editing_Plugin::get_allowed_users( array( 'search' => '*' . $user_input .'*' ) );

		header("Content-type: application/json");
		echo json_encode( $users );
		die();
	}

	/**
	 * Displays post hierarchy starting at a specifc post ID
	 * 
	 * @todo currently only supports HTML output, might decide to use json instead
	 */ 
	static function render_post_children() {

		if( defined('DOING_AJAX') && DOING_AJAX ) {

			$parent_id = trim($_REQUEST['parent_id'], 'p');
			$group_id = $_REQUEST['group_id'];
			$post_type = $_REQUEST['post_type'];

			$post_type_obj = get_post_type_object( $post_type );

			if( is_null( $post_type_obj ) ) {
				error_log('Bad post type: ' . $post_type );
				die();
			}

			$perm_editor = null;

			if( $post_type_obj->hierarchical ) {

				$perm_editor = new BU_Hierarchical_Permissions_Editor( $group_id, $post_type_obj->name );

			} else {

				$perm_editor = new BU_Flat_Permissions_Editor( $group_id, $post_type_obj->name );

			}

			$perm_editor->render( $parent_id );

			die();
		}
	
	}

}

?>