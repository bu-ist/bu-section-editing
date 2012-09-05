<?php 

/**
 * Centralized admin ajax routing
 *
 * @todo sanitize ALL input
 * @todo AJAX nonces
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
	 * Find capable section editors for this blog based on input string
	 *
	 * @todo add nonce
	 */
	static public function find_user() {

		$return = array();
		$term = trim( $_REQUEST['term'] );

		if( empty( $term ) )
			wp_die(-1);

		// Get users capable of section editing for this blog
		$users = BU_Section_Editing_Plugin::get_allowed_users( array( 'search' => '*' . $term . '*' ) );

		// Format output
		foreach ( $users as $user ) {

			$email = ! empty( $user->user_email ) ? " ({$user->user_email})" : '';

			$return[] = array(
				'label' => sprintf( __( '%1$s%2$s' ), $user->display_name, $email ),
				'value' => $user->user_login,
			);
		}

		header("Content-type: application/json");
		echo json_encode( $return );
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

			} else {

				$perm_editor = new BU_Flat_Permissions_Editor( $group_id, $post_type_obj->name );

			}

			$perm_editor->query( $query_vars );
			
			$response = new stdClass();
			$child_of = isset( $query_vars['child_of'] ) ? $query_vars['child_of'] : 0;

			$response->posts = $perm_editor->get_posts( $child_of );
			$response->page = $perm_editor->page;
			$response->found_posts = $perm_editor->found_posts;
			$response->post_count = $perm_editor->post_count;
			$response->max_num_pages = $perm_editor->max_num_pages;

			header("Content-type: application/json");
			echo json_encode($response);
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