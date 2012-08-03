<?php

/*
@todo
- Need to add an edit lock to editing groups (look at navman)
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
		global $wp_version;

		add_action('admin_menu', array( __CLASS__, 'admin_menus'));
		add_action('admin_enqueue_scripts', array( __CLASS__, 'admin_scripts' ) );

		add_action('transition_post_status', array( __CLASS__, 'transition_post_status' ), 10, 3 );

		// for filtering posts by editable status per user
		// parses query to add meta_query, which was a known
		// bug pre-3.2 -- a workaround may exist, but i
		// haven't dug into it yet.
		if( version_compare( $wp_version, '3.2', '>=' ) ) {
			add_action( 'init', array( __CLASS__, 'add_edit_views' ), 20 );
			add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
			add_action( 'parse_query', array( __CLASS__, 'parse_query' ) );
		}

	}

	/**
	 * Add custom edit post bucket for editable posts to views for each supported post type
	 *
	 */
	public static function add_edit_views() {

		if( BU_Section_Editing_Plugin::is_allowed_user() ) {

			$supported_post_types = BU_Group_Permissions::get_supported_post_types('names');

			foreach( $supported_post_types as $post_type ) {
				add_filter( 'views_edit-' . $post_type, array( __CLASS__, 'section_editing_views' ) );
			}

		}

	}

	/**
	 * Custom bucket for filter posts table to display only posts editable by current user
	 *
	 * @todo figure out "current" class
	 *
	 */
	public static function section_editing_views( $views ) {
		global $post_type_object;

		$groups = BU_Edit_Groups::get_instance();
		$post_type = $post_type_object->name;
		$user_id = get_current_user_id();

		$class = '';
		if( isset( $_REQUEST['editable_by'] ) )
			$class = ' class="current"';

		$edit_link = admin_url( "edit.php?post_type=$post_type&editable_by=" . $user_id );
		$args = array( 'user_id' => $user_id, 'post_type' => $post_type, 'include_unpublished' => true );
		$count = $groups->get_allowed_post_count( $args );

		$views['editable_by'] = "<a href=\"$edit_link\" $class>Editable <span class=\"count\">($count)</span></a>";

		return $views;

	}

	/**
	 * Add custom query var for filtering posts by editable status
	 */
	public static function query_vars( $query_vars ) {
		$query_vars[] = 'editable_by';
		return $query_vars;
	}

	/**
	 * Query logic for filtering posts by editable status for specific user
	 */
	public static function parse_query( $query ) {

		if( isset( $query->query_vars['editable_by'] ) ) {

			$user_id = $query->query_vars['editable_by'];
			$groups = BU_Edit_Groups::get_instance();
			$section_groups = $groups->find_groups_for_user($user_id);

			if( empty($section_groups) )
				return;

			$meta_query = array(
				'relation' => 'OR',
				);

			foreach( $section_groups as $group ) {
				$meta_query[] = array(
					'key' => BU_Group_Permissions::META_KEY,
					'value' => $group->id,
				    	'compare' => '='
					);
			}

			$query->set( 'meta_query', $meta_query );
			$query->set( 'post_status', 'publish' );

			// Include drafts and pending posts as well
			add_filter( 'posts_where', array( __CLASS__, 'editable_where_clause' ) );

		}

	}

	public static function editable_where_clause( $where ) {
		global $wpdb;

		$post_type = isset( $_GET['post_type'] ) ? $_GET['post_type'] : 'post';
		$where .= " OR ( {$wpdb->posts}.post_status IN ('draft','pending')";
		$where .= " AND {$wpdb->posts}.post_type = '$post_type')";

		return $where;
	}

	/**
	 * Runs when a post is updated and the status has changed
	 *
	 * Currently, we are looking for any transition to and from 'publish', and
	 * updating the groups post meta accordingly
	 *
	 * Once we decide how to handle drafts, we will want to switch this logic to
	 * add group post meta to any 'new' post if it is saved in an editable location
	 */
	public static function transition_post_status( $new_status, $old_status, $post ) {

		$pto = get_post_type_object( $post->post_type );

		// We only need special logic for hierarchical post types
		if( ! $pto->hierarchical ) {
			return;
		}

		$status_blacklist = array( 'publish', 'trash' );

		// From draft|pending|etc -> publish
		if( ! in_array( $old_status, $status_blacklist ) && $new_status == 'publish' ) {

			// This prevents section editors from publishing top-level posts
			if( empty( $post->post_parent ) )
				return;

			$parent = get_post( $post->post_parent );

			// Copy post permissions from parent on publish
			if( $parent && $parent->post_status == 'publish') {

				$group_controller = BU_Edit_Groups::get_instance();
				$groups = $group_controller->get_groups();

				$existing_groups = get_post_meta( $post->ID, BU_Group_Permissions::META_KEY );
				$parent_groups = get_post_meta( $parent->ID, BU_Group_Permissions::META_KEY );

				foreach( $groups as $group ) {

					// Add newly valid groups
					if( in_array( $group->id, $parent_groups ) && ! in_array( $group->id, $existing_groups ) ) {
						add_post_meta( $post->ID, BU_Group_Permissions::META_KEY, $group->id );
					}

				}

			}

		}

		// From publish -> draft|pending|etc
		if( $old_status == 'publish' && ! in_array( $new_status, $status_blacklist ) ) {

			$group_controller = BU_Edit_Groups::get_instance();
			$groups = $group_controller->get_groups();

			$existing_groups = get_post_meta( $post->ID, BU_Group_Permissions::META_KEY );

			foreach( $groups as $group ) {

				// Remove all group permissions for non-published posts
				delete_post_meta( $post->ID, BU_Group_Permissions::META_KEY, $group->id );

			}

		}

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

		$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';
	
		if( $hook == self::$manage_groups_hook ) {
			wp_enqueue_script('json2');
			//@todo do we need jquery-cookie?
			wp_enqueue_script( 'bu-jquery-tree', plugins_url( BUSE_PLUGIN_PATH . '/js/lib/jstree/jquery.jstree' . $suffix . '.js' ), array('jquery'), '1.0-rc3' );

			// Use newer version of jquery.ui.ppsition from github master, adds 'within' option
			// @see https://github.com/jquery/jquery-ui/pull/254
			// @see http://bugs.jqueryui.com/ticket/5645
			wp_enqueue_script( 'bu-jquery-ui-position', plugins_url( BUSE_PLUGIN_PATH . '/js/lib/jquery.ui.position' . $suffix . '.js' ), array('jquery') );

			wp_enqueue_script( 'group-editor', plugins_url( BUSE_PLUGIN_PATH . '/js/group-editor' . $suffix . '.js' ), array('jquery'), '0.3' );

			wp_enqueue_style( 'jstree-default', plugins_url( BUSE_PLUGIN_PATH . '/js/lib/jstree/themes/classic/style.css' ), '0.3' );
			wp_enqueue_style( 'group-editor', plugins_url( BUSE_PLUGIN_PATH . '/css/group-editor.css' ), '0.3' );

			$buse_config = array(
				'adminUrl' => admin_url( 'admin-ajax.php' ),
				'pluginUrl' => plugins_url( BUSE_PLUGIN_PATH )
				);

			wp_localize_script( 'group-editor', 'buse_config', $buse_config );

		}

		if( in_array($hook, array('post.php', 'post-new.php', 'edit.php') ) ) {
			wp_enqueue_script( 'bu-section-editor-post', plugins_url('/js/section-editor-post' . $suffix . '.js', __FILE__), array('jquery'), '1.0', true);
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
			$tab = isset( $_REQUEST['tab'] ) ? $_REQUEST['tab'] : 'properties';
			$perm_panel = isset( $_REQUEST['perm_panel'] ) ? $_REQUEST['perm_panel'] : 'page';
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

					// Process input
					$group_data = $_POST['group'];

					// if no users are set, array key for users won't exist
					if( ! isset($group_data['users']) ) $group_data['users'] = array();
					if( ! isset($group_data['perms'] ) ) $group_data['perms'] = array();

					if( ! isset($group_data['name']) || empty( $group_data['name'] ) ) {
						$redirect_url = add_query_arg( array( 'status' => 1 ) );
						wp_redirect($redirect_url);
						return;
					}

					$post_types = BU_Group_Permissions::get_supported_post_types( 'names' );

					foreach( $post_types as $post_type ) {

						// flat permission type use checkboxes, need to add empty array for post type
						if( ! isset( $group_data['perms'][$post_type] ) )
							$group_data['perms'][$post_type] = array();

						$data = $group_data['perms'][$post_type];

						// Convert JSON strings to arrays
						if( is_string( $data ) ) {
							$post_ids = json_decode( stripslashes( $data ), true );

							if( is_null( $post_ids ) )
								$post_ids = array();

							$group_data['perms'][$post_type] = $post_ids;

						}

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

					$redirect_url = add_query_arg( array( 'id' => $group_id, 'action' => 'edit', 'status' => $status, 'tab' => $tab, 'perm_panel' => $perm_panel ) );
					break;

				case 'delete':
					if( ! check_admin_referer( 'delete_section_editing_group' ) )
						wp_die('Cheatin, uh?');

					// @todo check for valid delete
					$groups->delete_group( $group_id );

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
		$tab = isset( $_REQUEST['tab'] ) ? $_REQUEST['tab'] : 'properties';
		$perm_panel = isset( $_REQUEST['perm_panel'] ) ? $_REQUEST['perm_panel'] : 'page';

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

			$groups_url = admin_url( self::MANAGE_GROUPS_PAGE );

			switch( $_GET['status'] ) {

				case 1:
					$notices['error'][] = '<p>There was an error saving the group.</p>';
					break;

				case 2:
					$notices['update'][] = sprintf( '<p>Group added. <a href="%s">View all groups</a></p></p>', $groups_url );
					break;

				case 3:
					$notices['update'][] = sprintf( '<p>Group updated. <a href="%s">View all groups</a></p>', $groups_url );
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
	 */
	static function group_permissions_string( $group, $args = array( ) ) {

		$defaults = array(
			'post_type' => null,
			'sep' => ', '
			);

		extract( wp_parse_args( $args, $defaults ) );
		
		if( ! is_null( $post_type ) && $pto = get_post_type_object( $post_type ) ) $content_types = array( $pto );
		else  $content_types =  BU_Group_Permissions::get_supported_post_types();

		$output = '';
		$counts = array();
		$groups = BU_Edit_Groups::get_instance();

		if( is_numeric( $group ) )
			$group = $groups->get( $group );

		if( ! is_object( $group ) )
			return false;

		foreach( $content_types as $pt ) {

			$count = 0;

			if( $group->id > 0 )
				$count = $groups->get_allowed_post_count( array( 'group' => $group->id, 'post_type' => $pt->name ) );

			$label = ( $count == 1 ) ? $pt->labels->singular_name : $pt->label;

			$counts[] = sprintf( "<span id=\"%s-stats\" class=\"perm-stats\"><span class=\"perm-stat-count\">%s</span> <span class=\"perm-label\">%s</span></span>",
				$pt->name,
				$count,
				$label
				);

		}

		if( ! empty( $counts ) ) {
			$output = implode( $sep, $counts );
		}

		return $output;

	}

}

/**
 * Centralized admin ajax routing
 *
 * @todo sanitize ALL input
 */
class BU_Groups_Admin_Ajax {

	static public function register_hooks() {

		add_action('wp_ajax_buse_add_member', array( __CLASS__, 'add_member' ) );
		add_action('wp_ajax_buse_find_user', array( __CLASS__, 'find_user' ) );
		add_action('wp_ajax_buse_search_posts', array( __CLASS__, 'search_posts' ) );
		add_action('wp_ajax_buse_render_post_list', array( __CLASS__, 'render_post_list' ) );
		add_action('wp_ajax_buse_can_edit', array( __CLASS__, 'can_edit'));
		add_action('wp_ajax_buse_can_move', array( __CLASS__, 'can_move'));
	}

	/**
	 * Add user to current edit group screen if they are valid
	 *
	 * @todo add nonce
	 */
	static public function add_member() {

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
	static public function find_user() {

		$groups = BU_Edit_Groups::get_instance();
		$user_input = $_POST['user'];

		// For now we are limiting group membership to section editors
		$users = BU_Section_Editing_Plugin::get_allowed_users( array( 'search' => '*' . $user_input .'*' ) );

		header("Content-type: application/json");
		echo json_encode( $users );
		die();
	}

	/**
	 * Renders an unordered list of posts for specified post type, optionally starting at a specifc post
	 *
	 * @uses BU_Hierarchical_Permissions_Editor or BU_Flat_Permissions_Editor depending on post_type
	 *
	 * @todo add nonce
	 */
	static public function render_post_list() {

		if( defined('DOING_AJAX') && DOING_AJAX ) {

			$group_id = intval(trim($_REQUEST['group_id']));
			$post_type = trim($_REQUEST['post_type']);
			$query_vars = isset($_REQUEST['query']) ? $_REQUEST['query'] : array();

			$post_type_obj = get_post_type_object( $post_type );

			if( is_null( $post_type_obj ) ) {
				error_log('Bad post type: ' . $post_type );
				die();
			}

			$perm_editor = null;

			if( $post_type_obj->hierarchical ) {

				$perm_editor = new BU_Hierarchical_Permissions_Editor( $group_id, $post_type_obj->name );
				$perm_editor->format = 'json';
				header("Content-type: application/json");

			} else {

				$perm_editor = new BU_Flat_Permissions_Editor( $group_id, $post_type_obj->name );

			}

			$perm_editor->query( $query_vars );


			$perm_editor->display();
			die();

		}

	}

	/**
	 * Not yet in use
	 *
	 * @todo implement
	 */
	static public function search_posts() {

		if( defined('DOING_AJAX') && DOING_AJAX ) {

			$group_id = intval(trim($_REQUEST['group_id']));
			$post_type = trim($_REQUEST['post_type']);
			$search_term = trim($_REQUEST['search']) ? $_REQUEST['search'] : '';

			$post_type_obj = get_post_type_object( $post_type );

			if( is_null( $post_type_obj ) ) {
				error_log('Bad post type: ' . $post_type );
				die();
			}

			die();

		}

	}


	static public function can_move() {
			$user_id = get_current_user_id();
			$post_id = (int) trim($_POST['post_id']);
			$parent_id = (int) trim($_POST['parent_id']);

			if(!isset($post_id) || !isset($parent_id)) {
				echo '-1';
				die();
			}

			$post = get_post($post_id);
			if($parent_id == 0 && $post->post_parent == 0) {
				$answer = BU_Section_Capabilities::can_edit($user_id, $post_id);
			} else {
				$answer = BU_Section_Capabilities::can_edit($user_id, $parent_id);
			}
			$response = new stdClass();

			$response->post_id = $post_id;
			$response->parent_id = $parent_id;
			$response->can_edit = $answer;
			$response->original_parent = $post->post_parent;
			$response->status = $post->post_status;

			header("Content-type: application/json");
			echo json_encode( $response );
			die();
	}

	static public function can_edit() {

			$user_id = get_current_user_id();
			$post_id = (int) trim($_POST['post_id']);


			if(!isset($post_id)) {
				echo '-1';
				die();
			}

			$post = get_post($post_id);
			if($post->post_status != 'publish')  {
				$answer = BU_Section_Capabilities::can_edit($user_id, $post->post_parent);
			} else {
				$answer = BU_Section_Capabilities::can_edit($user_id, $post_id);
			}

			$response = new stdClass();

			$response->post_id = $post_id;
			$response->parent_id = $post->post_parent;
			$response->can_edit = $answer;
			$response->status = $post->post_status;

			header("Content-type: application/json");
			echo json_encode( $response );
			die();
	}

}

?>
