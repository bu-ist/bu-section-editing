<?php

/*
@todo Need to add an edit lock to editing groups (look at navman)
*/

class BU_Groups_Admin {

	const MANAGE_GROUPS_PAGE = 'users.php?page=manage_groups';

	public static $manage_groups_hook;

	private static $notices = array();

	/**
	 * Register for admin hooks
	 * 
	 * Called from main plugin class during init
	 */ 
	public static function register_hooks() {

		add_action('admin_menu', array( __CLASS__, 'admin_menus'));
		add_action('admin_enqueue_scripts', array( __CLASS__, 'admin_scripts' ) );

	}

	/**
	 * Add manage groups page
	 * 
	 * @hook admin_menus
	 */ 
	public static function admin_menus() {

		$hook = add_users_page('Section Groups', 'Section Groups', 'promote_users', 'manage_groups', array('BU_Groups_Admin', 'manage_groups_screen'));
		self::$manage_groups_hook = $hook;

		add_action('load-' . $hook, array( __CLASS__, 'load_manage_groups'), 1);

	}

	public static function admin_notices() {

		if( isset( self::$notices['error'] ) ) {
			foreach( self::$notices['error'] as $msg ) {
				printf( '<div id="message" class="error">%s</div>', $msg );
			}
		}

		if( isset( self::$notices['update'] ) ) {
			foreach( self::$notices['update'] as $msg ) {
				printf( '<div id="message" class="updated fade">%s</div>', $msg );
			}
		}

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
						$redirect_url = add_query_arg( array( 'status' => 1 ) );
						wp_redirect($redirect_url);
						return;
					}

					$status = 0;
					
					if( $group_id == -1 ) {
						$group = $groups->add_group($group_data);
						$group_id = $group->id;
						$status = 2;
					} else {
						$groups->update_group($group_id, $group_data);
						$status = 3;
					}

					$groups->save();

					$redirect_url = add_query_arg( array( 'id' => $group_id, 'action' => 'edit', 'status' => $status ) );
					break;

				case 'delete':
					if( ! check_admin_referer( 'delete_section_editing_group' ) )
						wp_die('Cheatin, uh?');

					// @todo check for valid delete
					$groups->delete_group( $group_id );

					$groups->save();

					$redirect_url = remove_query_arg( array('action','_wpnonce','id','tab'));
					$redirect_url = add_query_arg( array( 'status' => 4 ), $redirect_url );
					break;

			}

			if( $redirect_url )
				wp_redirect($redirect_url);

		}

		// Generate admin notices
		self::$notices = self::get_notices();

		if( ! empty( self::$notices ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
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
	 * Generate admin notice messages based on incoming status codes
	 */ 
	static function get_notices() {

		$notices = array();

		if( isset( $_GET['status'] ) ) {

			switch( $_GET['status'] ) {

				case 1:
					$notices['error'][] = '<p>There was an error saving the group.</p>';
					break;

				case 2:
					$notices['update'][] = '<p>Group added.</p>';
					break;

				case 3:
					$notices['update'][] = '<p>Group updated.</p>';
					break;

				case 4:
					$notices['update'][] = '<p>Group deleted.</p>';
					break;

				default:
					$notices = array();
					break;
			}

		}

		$count_user_args = array( 
			'count_total' => true,
			'fields' => 'ID',
			'number' => 1
		);

		$valid_user_count = BU_Section_Editing_Plugin::get_allowed_users( $count_user_args );

		if( $valid_user_count == 0 ) {

			$manage_users_url = admin_url('users.php');

			$msg  = <<< MSG
<p>There are currently no users on your site that are capable of being assigned to section editing groups.</p>
<p>To start using this plugin, visit the <a href="$manage_users_url">users page</a> and change the role for any users you would like to add to a section editing group to "Section Editor".</p>
MSG;

			$notices['error'][] = $msg;
		}

		return $notices;

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

	/**
	 * Render group permissions string
	 * 
	 * @todo should there be a BU_Group_Permissions object that handles all of this?
	 */ 
	static function group_permissions_string( $group, $post_type = null, $args = array(), $offset = 0 ) {

		if( ! is_null( $post_type ) && $pto = get_post_type_object( $post_type ) ) $content_types = array( $pto );
		else  $content_types =  BU_Permissions_Editor::get_supported_post_types();

		$output = '';
		$counts = array();

		foreach( $content_types as $pt ) {
			$defaults = array( 'post_type' => $pt->name );
			$query_args = wp_parse_args( $args, $defaults );

			$count = $group->get_posts_count( $query_args );
			$count = $count + $offset;

			if( (int) $count > 0 ) {
				$label = ( $count > 1 ) ? $pt->label : $pt->labels->singular_name;

				$counts[] = sprintf( "<span id=\"%s-stats\" class=\"perm-stats\"><span id=\"%s-stat-count\">%s</span> %s</span>\n", 
					$pt->name,
					$pt->name,
					$count,
					$label );
			}

		}

		if( ! empty( $counts ) ) { 
			$output = implode(', ', $counts );
		}

		return $output;

	}

}

/**
 * Centralized admin ajax routing
 */ 
class BU_Groups_Admin_Ajax {

	static function register_hooks() {

		add_action('wp_ajax_buse_add_member', array( __CLASS__, 'add_member' ) );
		add_action('wp_ajax_buse_find_user', array( __CLASS__, 'find_user' ) );
		add_action('wp_ajax_buse_fetch_children', array( __CLASS__, 'render_post_children' ) );
		add_action('wp_ajax_buse_update_permissions_count', array( __CLASS__, 'update_permissions_count' ) );

	}

	/**
	 * Add user to current edit group screen if they are valid
	 * 
	 * @todo add nonce
	 */ 
	static function add_member() {

		$groups = BU_Edit_Groups::get_instance();

		$group_id = $_POST['group_id'];
		$user_input = $_POST['user'];
		$output = array();

		// Should we only allow exact matches?
		$users = BU_Section_Editing_Plugin::get_allowed_users( array( 'search' => $user_input ) );

		if( is_array( $users ) && ! empty( $users ) ) {

			// Temporary ...
			if( count( $users ) > 1 ) {
				error_log('More than one users were returned for input: ' . $user_input );
				die();
			}

			$user = $users[0];

			$output['status'] = true;
			$output['user_id'] = $user->ID;

		} else { // User was not found
			
			$output['status'] = false;

			// Look for exact user match to tailor error message
			$user_id = username_exists($user_input);

			if( ! is_null( $user_id ) && is_user_member_of_blog( $user_id ) ) {
				// User has incorrect role
				$output['message'] = '<p><b>' . $user_input . '</b> is not a section editor.  Before you can assign them to a group, you must change their role to "Section Editor" on the <a href="'.admin_url('users.php?s=' . $user_input ).'">users page</a>.</p>';
				
			} else {
				// User does exist, but is not a member of this blog
				$output['message'] = '<p><b>' . $user_input . '</b> is not a member of this site.  Please <a href="'.admin_url('user-new.php').'">add them to your site</a> with the "Section Editor" role.';
				
			}
			
		}

		header("Content-type: application/json");
		echo json_encode( $output );
		die();

	}

	/**
	 * Find valid users based on input string
	 * 
	 * @todo add nonce
	 */ 
	static function find_user() {

		$groups = BU_Edit_Groups::get_instance();
		$user_input = $_POST['user'];

		// For now we are limiting group membership to section editors
		$users = BU_Section_Editing_Plugin::get_allowed_users( array( 'search' => '*' . $user_input .'*' ) );

		header("Content-type: application/json");
		echo json_encode( $users );
		die();
	}

	/**
	 * Displays post hierarchy starting at a specifc post ID
	 * 
	 * @todo add nonce
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