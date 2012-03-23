<?php

class BU_Groups_Admin {

	static function load_manage_groups() {
		
		if( isset($_POST['action']) && $_POST['action'] == 'update') {

			$groups = BU_Edit_Groups::get_instance();

			// A lot more to update

			$args = array(
				'name' => strip_tags(trim($_POST['name'])),
				'description' => strip_tags(trim($_POST['description'])),
				'users' => $_POST['users']
			);

			if(empty($args['name']) || empty($args['users'])) {
				// redirect back to previous view with errors
				return;
			}

			// maybe use exceptions?
			$groups->update_group((int) $_GET['id'], $args);
			$groups->update();

			// redirect to previous view
		}
	}

	static function manage_groups_screen() {

		$groups = BU_Edit_Groups::get_instance();

		$id = isset( $_REQUEST['id'] ) ? (int) $_REQUEST['id'] : 0;
		$action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';
		$tab = isset( $_REQUEST['tab'] ) ? $_REQUEST['tab'] : 'name';
		
		$template_path = 'interface/groups.php';

		switch( $action ) {

			case 'edit':
				$template_path = 'interface/edit-group.php';
				if( $id ) $group = $groups->get_group( $id );
				else $group = new BU_Edit_Group();
				break;

			default:
				$template_path = 'interface/groups.php';
				$group_list = new BU_Groups_List();
				break;

		}

		// Render screen
		include $template_path;
	}

	static function group_edit_url( $action, $id = 0, $tab = 'name' ) {

		$args = array( 'action' => $action, 'tab' => $tab );
		if( $id ) $args['id'] = $id;

		$url = add_query_arg($args);

		return $url;
	}

	static function group_member_list( $id, $member_ids ) {
		if( empty( $member_ids ) )
			return;

		$html = '';
		$html .= "<ul>\n";

		$users = get_users( array( 'include' => $member_ids ) );

		foreach( $users as $user ) {
			$remove_url = self::group_edit_url( 'delete', $id, 'members' );
			$remove_url = add_query_arg( array('action' => 'delete', 'member_id' => $user->ID ), $remove_url );
			$remove_url = wp_nonce_url(  $remove_url, 'delete_group_member');

			$html .= sprintf('<li>%s <a href="%s" class="alignright">Remove</a></li>', $user->display_name, $remove_url );
		}

		$html .= "</ul>\n";
		echo $html;
	}
}

class BU_Groups_Admin_Ajax {


	static function add_member() {

		$groups = BU_Edit_Groups::get_instance();

		$group_id = $_POST['group_id'];
		$member_id = $_POST['user_login'];

	}

	static function find_member() {

	}

}

?>