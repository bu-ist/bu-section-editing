<?php

/**
 * Centralized admin ajax routing
 *
 * @todo sanitize ALL input
 * @todo AJAX nonces
 */
class BU_Groups_Admin_Ajax {

	const NONCE_ACTION = 'buse_admin_ajax';

	static public function register_hooks() {

		add_action( 'wp_ajax_buse_site_users_script', array( __CLASS__, 'site_users_script' ) );
		add_action( 'wp_ajax_buse_search_posts', array( __CLASS__, 'search_posts' ) );
		add_action( 'wp_ajax_buse_render_post_list', array( __CLASS__, 'render_post_list' ) );
		add_action( 'wp_ajax_buse_can_edit', array( __CLASS__, 'can_edit' ) );
		add_action( 'wp_ajax_buse_can_move', array( __CLASS__, 'can_move' ) );

	}

	/**
	 * Generates a Javscript file that contains a variable with all site users and relevant meta
	 *
	 * The variable is used for autocompletion (find user tool) and while adding members
	 */
	static public function site_users_script() {

		$return = array();

		// Get all users of the current site
		$users = get_users();

		// Format output
		foreach ( $users as $user ) {

			$email = ! empty( $user->user_email ) ? " ({$user->user_email})" : '';

			$return[] = array(
				'autocomplete' => array(
					'label' => sprintf( '%1$s%2$s', $user->display_name, $email ),
					'value' => $user->user_login,
				),
				'user' => array(
					'id' => (int) $user->ID,
					'login' => $user->user_login,
					'nicename' => $user->user_nicename,
					'display_name' => $user->display_name,
					'email' => $user->user_email,
					'is_section_editor' => (bool) BU_Section_Editing_Plugin::is_allowed_user( $user->ID ),
				),
			);

		}

		header( 'Content-type: application/x-javascript' );
		echo 'var buse_site_users = ' . wp_json_encode( $return );
		wp_die();

	}

	/**
	 * Renders an unordered list of posts for specified post type, optionally starting at a specifc post
	 *
	 * @uses BU_Hierarchical_Permissions_Editor or BU_Flat_Permissions_Editor depending on post_type
	 *
	 * @todo add nonce
	 */
	static public function render_post_list() {

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

			check_ajax_referer( self::NONCE_ACTION );

			$group_id = isset( $_REQUEST['group_id'] ) ? absint( wp_unslash( $_REQUEST['group_id'] ) ) : 0;
			$post_type = isset( $_REQUEST['post_type'] ) ? sanitize_key( wp_unslash( $_REQUEST['post_type'] ) ) : '';
			$query_vars = isset( $_REQUEST['query'] ) && is_array( $_REQUEST['query'] ) ? map_deep( wp_unslash( $_REQUEST['query'] ), 'sanitize_text_field' ) : array();
			$post_type_obj = get_post_type_object( $post_type );

			if ( is_null( $post_type_obj ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid post type.', 'bu-section-editing' ) ), 400 );
			}

			$perm_editor = null;

			if ( $post_type_obj->hierarchical ) {

				$perm_editor = new BU_Hierarchical_Permissions_Editor( $group_id, $post_type_obj->name );
				$perm_editor->format = 'json';

			} else {

				$perm_editor = new BU_Flat_Permissions_Editor( $group_id, $post_type_obj->name );

			}

			$perm_editor->query( $query_vars );

			$response = new stdClass();
			$child_of = isset( $query_vars['child_of'] ) ? absint( $query_vars['child_of'] ) : 0;

			$response->posts = $perm_editor->get_posts( $child_of );
			$response->page = $perm_editor->page;
			$response->found_posts = $perm_editor->found_posts;
			$response->post_count = $perm_editor->post_count;
			$response->max_num_pages = $perm_editor->max_num_pages;

			wp_send_json( $response );

		}

	}

	/**
	 * Not yet in use
	 *
	 * @todo implement
	 */
	static public function search_posts() {

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

			check_ajax_referer( self::NONCE_ACTION );

			$group_id = isset( $_REQUEST['group_id'] ) ? absint( wp_unslash( $_REQUEST['group_id'] ) ) : 0;
			$post_type = isset( $_REQUEST['post_type'] ) ? sanitize_key( wp_unslash( $_REQUEST['post_type'] ) ) : '';
			$search_term = isset( $_REQUEST['search'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['search'] ) ) : '';

			$post_type_obj = get_post_type_object( $post_type );

			if ( is_null( $post_type_obj ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid post type.', 'bu-section-editing' ) ), 400 );
			}

			wp_die();

		}

	}

	static public function can_move() {
		check_ajax_referer( self::NONCE_ACTION );

		$post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;
		$parent_id = isset( $_POST['parent_id'] ) ? intval( wp_unslash( $_POST['parent_id'] ) ) : 0;

		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Missing post ID.', 'bu-section-editing' ) ), 400 );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post ID.', 'bu-section-editing' ) ), 404 );
		}

		$post_type_obj = get_post_type_object( $post->post_type );
		if ( ! $post_type_obj ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post type.', 'bu-section-editing' ) ), 400 );
		}

		if ( $parent_id == 0 && $post->post_parent == 0 ) {
			$answer = current_user_can( $post_type_obj->cap->edit_post, $post_id );
		} else {
			$answer = current_user_can( $post_type_obj->cap->edit_post, $parent_id );
		}

		$response = new stdClass();

		$response->post_id = $post_id;
		$response->parent_id = $parent_id;
		$response->can_edit = $answer;
		$response->original_parent = $post->post_parent;
		$response->status = $post->post_status;

		wp_send_json( $response );
	}

	static public function can_edit() {

		check_ajax_referer( self::NONCE_ACTION );

		$post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;

		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Missing post ID.', 'bu-section-editing' ) ), 400 );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post ID.', 'bu-section-editing' ) ), 404 );
		}

		$post_type_obj = get_post_type_object( $post->post_type );
		if ( ! $post_type_obj ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post type.', 'bu-section-editing' ) ), 400 );
		}

		if ( $post->post_status != 'publish' ) {
			$answer = current_user_can( $post_type_obj->cap->edit_post, $post->post_parent );
		} else {
			$answer = current_user_can( $post_type_obj->cap->edit_post, $post_id );
		}

		$response = new stdClass();

		$response->post_id = $post_id;
		$response->parent_id = $post->post_parent;
		$response->can_edit = $answer;
		$response->status = $post->post_status;

		wp_send_json( $response );
	}
}
