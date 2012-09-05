<?php

/*
@todo
- Need to add an edit lock to editing groups (look at navman)
*/

class BU_Groups_Admin {

	const MANAGE_GROUPS_PAGE = 'users.php?page=manage_groups';
	const EDITABLE_POST_STATUS = 'section_editable';
	
	const MANAGE_USERS_COLUMN = 'section_groups';
	const MANAGE_USERS_MAX_NAME_LENGTH = 60;

	const POSTS_PER_PAGE_OPTION = 'buse_posts_per_page';

	public static $manage_groups_hook;

	private static $notices = array();

	/**
	 * Register for admin hooks
	 *
	 * Called from main plugin class during init
	 */
	public static function register_hooks() {
		global $wp_version;

		// Interface
		add_action( 'admin_menu', array( __CLASS__, 'admin_menus' ) );
		add_filter( 'set-screen-option', array( __CLASS__, 'manage_groups_set_screen_option' ), 10, 3);
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_scripts' ) );

		add_filter( 'manage_users_columns', array( __CLASS__, 'add_manage_users_column' ) );
		add_filter( 'manage_users_custom_column', array( __CLASS__, 'manage_users_group_column' ), 10, 3 );
		
		// WP hooks that trigger group related state changes
		add_action( 'transition_post_status', array( __CLASS__, 'transition_post_status' ), 10, 3 );
		add_action( 'set_user_role', array( __CLASS__, 'user_role_switched'), 10, 2 );

		// For filtering posts by editable status for current user
		// parses query to add meta_query, which was a known bug pre-3.2 -- a workaround may exist, but I
		// haven't dug into it yet.
		if( version_compare( $wp_version, '3.2', '>=' ) ) {
			add_action( 'admin_init', array( __CLASS__, 'add_edit_views' ), 20 );
			add_action( 'parse_query', array( __CLASS__, 'add_editable_query' ) );
		}

	}

	/**
	 * Register a custom "Section Groups" column for the manage users table
	 */ 
	public static function add_manage_users_column( $columns ) {

		$columns[self::MANAGE_USERS_COLUMN] = 'Section Groups';

		return $columns;

	}

	/**
	 * Custom "Section Groups" column for the manage users table
	 */ 
	public static function manage_users_group_column( $content, $column, $user_id ) {

		if( $column == self::MANAGE_USERS_COLUMN ) {

			// Find groups for the current user row
			$gc = BU_Edit_Groups::get_instance();
			$groups = $gc->find_groups_for_user( $user_id );

			if( empty( $groups ) ) {
				
				$content = 'None';

			} else {

				$group_names = array();
				$current_length = $visible_count = $truncated_count = 0;

				foreach( $groups as $group ) {

					$toolong = self::MANAGE_USERS_MAX_NAME_LENGTH < ( $current_length + strlen( $group->name ) );

					// Allow at least one group
					if( 0 == $visible_count || ( 0 == $truncated_count && ! $toolong ) ) {

						$group_names[] = sprintf( '<a href="%s">%s</a>', self::manage_groups_url( 'edit', array( 'id' => $group->id ) ), $group->name );
						$current_length += strlen( $group->name );
						$visible_count++;

					} else {

						$truncated_count++;

					}

				}

				$content = implode( ', ', $group_names );

				if( $truncated_count > 0 ) {
					$content .= sprintf( ' and <a href="%s"> ' . _n( '%s other', '%s others', $truncated_count, BU_Section_Editing_Plugin::TEXT_DOMAIN ) . '</a>',
						admin_url(self::MANAGE_GROUPS_PAGE),
						$truncated_count );
				}
			
			}

		}

		return $content;

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
	 * Remove group members when user role has switched to role that cannot belong in section groups
	 * 
	 * @todo make sure this works in 3.1.4
	 */ 
	public static function user_role_switched( $user_id, $newrole ) {

		$role = get_role( $newrole );

		// @todo move this logic check to a method in classes.roles-capabilities once
		// Gregory figures out how to handle "Section Editing"-ness
		if( ! $role->has_cap('edit_published_in_section') ) {

			// Remove members from any groups
			$manager = BU_Edit_Groups::get_instance();

			$groups = $manager->find_groups_for_user( $user_id );
			foreach( $groups as $group ) {
				$group->remove_user( $user_id );
			}

			// commit state
			$manager->save();

		}

	}

	/**
	 * Add custom edit post bucket for editable posts to views for each supported post type
	 * 
	 * register_post_status API/admin UI functionality is limited as of 3.5
	 * @see http://core.trac.wordpress.org/ticket/12706
	 */
	public static function add_edit_views() {

		if( BU_Section_Editing_Plugin::is_allowed_user() ) {

			// Most of these options don't do anything at this time, but we should keep an eye
			// on the ticket mentioned above as this could change in future releases
			$args = array(
				'label' => 'Editable',
				'label_count' => true,
				'public' => true,
				'show_in_admin_all' => true,
				'publicly_queryable' => true,
				'show_in_admin_status_list' => false,
				'show_in_admin_all_list' => true,
			);

			// WP_Query will not recognize custom post status query vars without this
			register_post_status( self::EDITABLE_POST_STATUS, $args );

			$supported_post_types = BU_Group_Permissions::get_supported_post_types('names');

			foreach( $supported_post_types as $post_type ) {
				add_filter( 'views_edit-' . $post_type, array( __CLASS__, 'add_editable_view' ) );
			}

		}

	}

	/**
	 * Custom bucket for filter posts table to display only posts editable by current user
	 */
	public static function add_editable_view( $views ) {
		global $post_type_object;

		$groups = BU_Edit_Groups::get_instance();
		$post_type = $post_type_object->name;
		$user_id = get_current_user_id();

		$class = '';
		if( isset( $_REQUEST['post_status'] ) && $_REQUEST['post_status'] == self::EDITABLE_POST_STATUS )
			$class = ' class="current"';

		$edit_link = admin_url( "edit.php?post_type=$post_type&post_status=" . self::EDITABLE_POST_STATUS );

		$args = array( 'user_id' => $user_id, 'post_type' => $post_type );

		if( $post_type_object->hierarchical )
			$args['include_unpublished'] = true;

		$count = $groups->get_allowed_post_count( $args );

		$views[self::EDITABLE_POST_STATUS] = "<a href=\"$edit_link\" $class>Editable <span class=\"count\">($count)</span></a>";

		return $views;

	}

	/**
	 * Query logic for filtering posts by editable status for current user
	 */
	public static function add_editable_query( $query ) {

		if( isset( $query->query_vars['post_status'] ) && $query->query_vars['post_status'] == self::EDITABLE_POST_STATUS ) {

			$user_id = get_current_user_id();

			if( empty( $user_id ) )
				return;

			$groups = BU_Edit_Groups::get_instance();
			$section_groups = $groups->find_groups_for_user($user_id);

			if( empty( $section_groups ) )
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

	/**
	 * Modify the WHERE clause to include drafts and pending posts for editable queries
	 */ 
	public static function editable_where_clause( $where ) {
		global $wpdb;

		$post_type = isset( $_GET['post_type'] ) ? $_GET['post_type'] : 'post';
		$pto = get_post_type_object( $post_type );

		// Include drafts and pending posts for hierarchical post types
		if( $pto->hierarchical )
			$where .= " OR ( {$wpdb->posts}.post_status IN ('draft','pending')";

		$where .= " AND {$wpdb->posts}.post_type = '$post_type')";

		return $where;

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

			// Setup screen option
			add_screen_option( 'per_page', array( 
				'label' => 'Posts per page',
				'default' => 10,
				'option' => self::POSTS_PER_PAGE_OPTION 
				) 
			);

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

					if( strlen( $group_data['name'] ) >= BU_Edit_Group::MAX_NAME_LENGTH )
						$group_data['name'] = substr( $group_data['name'], 0, BU_Edit_Group::MAX_NAME_LENGTH - 1 );

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
	 * Store custom "Posts per page" screen option for manage groups page in user meta
	 */ 
	public function manage_groups_set_screen_option( $status, $option, $value ) {

		if ( self::POSTS_PER_PAGE_OPTION == $option ) return $value;

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

		$valid_user_count = count( BU_Section_Editing_Plugin::get_allowed_users() );

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

?>