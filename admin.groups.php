<?php

class BU_Groups_Admin {

	static function load_manage_groups() {
		
		if( isset($_REQUEST['action']) ) {

			$groups = BU_Edit_Groups::get_instance();
			$group_id = isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : -1;
			$redirect_url = '';

			switch( $_REQUEST['action'] ) {

				case 'update':
					if( ! check_admin_referer( 'update_section_editing_group' ) )
						wp_die('Cheatin, uh?');

					$group_data = $_POST['group'];

					// if no users are set, array key for users won't exist
					if( ! isset($group_data['users']) ) $group_data['users'] = array();

					// @todo Improve error handling
					if(empty($group_data['name'])) {
						$redirect_url = add_query_arg( array('errors' => 1 ));
						wp_redirect($redirect_url);
						return;
					}

					if( $group_id > 0 ) {
						$groups->update_group($group_id, $group_data);
					} else {
						$test = $groups->add_group($group_data);
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

	static function manage_groups_screen() {

		$groups = BU_Edit_Groups::get_instance();

		$group_id = isset( $_REQUEST['id'] ) ? (int) $_REQUEST['id'] : -1;
		$action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';
		$tab = isset( $_REQUEST['tab'] ) ? $_REQUEST['tab'] : 'name';
		
		$template_path = 'interface/groups.php';

		switch( $action ) {

			case 'edit':
				$template_path = 'interface/edit-group.php';
				$group = $groups->get( $group_id );

				if( $group === false) 
					$group = new BU_Edit_Group();
				
				break;

			default:
				$template_path = 'interface/groups.php';
				$group_list = new BU_Groups_List();
				break;

		}

		// Render screen
		include $template_path;
	}

	static function group_edit_url( $id = -1, $tab = 'name' ) {
		$page = admin_url('users.php?page=manage_groups');

		$args = array( 'action' => 'edit', 'id' => $id, 'tab' => $tab );
		$url = add_query_arg($args, $page);

		return $url;
	}

	static function group_delete_url( $id ) {
		$page = admin_url('users.php?page=manage_groups');

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

	static function add_member() {

		$groups = BU_Edit_Groups::get_instance();

		$group_id = $_POST['group_id'];
		$user_input = $_POST['user'];
		$output = array();

		$user = get_user_by( 'login', $user_input );

		if( $user && is_user_member_of_blog( $user->ID ) ) {

			// Check that we're not adding someone who already exists
			if( $groups->has_user( $group_id, $user->ID ) ) {

				$output['status'] = false;
				$output['message'] = '<p>' . $user->user_login . ' is already a member of this group.</p>';

			} else { // Otherwise pass on the user ID and status of success

				$output['status'] = true;
				$output['message'] = '<p>' . $user->user_login . ' added to this group.</p>';
				$output['user_id'] = $user->ID;
			
			}

		} else { // User was not found

			$output['status'] = false;
			$output['message'] = '<p>No user found for: ' . $user_input . '</p>';

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

		$wp_user_search = new WP_User_Query( array( 'blog_id' => 0, 'search' => '*' . $user_input .'*', 'role' => 'section_editor' ) );
		$users = $wp_user_search->get_results();

		header("Content-type: application/json");
		echo json_encode( $users );
		die();
	}

}

?>