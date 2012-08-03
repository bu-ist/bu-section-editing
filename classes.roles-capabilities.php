<?php

class BU_Section_Editing_Roles {

	// need to figure out the *best* way to create roles
	static public function maybe_create() {
		/** Temporary **/
		$role = get_role('section_editor');

		if(empty($role)) {
			add_role('section_editor', 'Section Editor');
		}

		$role = get_role('section_editor');

		$role->add_cap('upload_files');

		$role->add_cap('read');
		$role->add_cap('edit_pages');
		$role->add_cap('edit_others_pages');

		$role->add_cap('delete_posts');
		$role->add_cap('delete_pages');


//		$role->remove_cap('edit_published_pages');
//		$role->remove_cap('publish_pages');
//		$role->remove_cap('delete_others_pages');
//		$role->remove_cap('delete_published_pages');

		$role->add_cap('delete_published_in_section');
		$role->add_cap('edit_published_in_section');
		$role->add_cap('publish_in_section');

		$role->add_cap('edit_posts');
		$role->add_cap('edit_others_posts');

//		$role->remove_cap('edit_published_posts');
//		$role->remove_cap('publish_posts');
//		$role->remove_cap('delete_others_posts');
//		$role->remove_cap('delete_published_posts');

		$role->add_cap('level_1');
		$role->add_cap('level_0');
		$role->add_cap('edit_private_posts');
		$role->add_cap('read_private_posts');
		$role->add_cap('edit_private_pages');
		$role->add_cap('read_private_pages');

		if(defined('BU_CMS') && BU_CMS == true) {
			$role->add_cap('unfiltered_html');
		}

	}

	/**
	 * This filter is only needed for BU installations where the bu_user_management plugin is active
	 *
	 * Hopefully this will not be required some day soon
	 */
	static function bu_allowed_roles( $roles ) {

		if( ! array_key_exists( 'lead_editor', $roles ) )
			$roles[] = 'lead_editor';

		if( ! array_key_exists( 'section_editor', $roles ) )
			$roles[] = 'section_editor';

		return $roles;

	}
}

class BU_Section_Capabilities {

	/**
	 * Checks whether or not a specific user can edit a post
	 *
	 * @param int $user_id
	 * @param int $post_id
	 */
	static function can_edit( $user_id, $post_id )  {

		if($user_id == 0) return false;

		// Extra checks for any "allowed" users
		if( BU_Section_Editing_Plugin::is_allowed_user( $user_id ) ) {

			if( $post_id == 0 ) return false;

			// Get all groups for this user
			$edit_groups_o = BU_Edit_Groups::get_instance();
			$groups = $edit_groups_o->find_groups_for_user( $user_id );

			if(empty($groups)) {
				return false;
			}

			foreach( $groups as $key => $group ) {

				// This group is good, bail here
				if( BU_Group_Permissions::group_can_edit( $group->id, $post_id ) ) {
					return true;
				}

			}

			// User is section editor, but not allowed for this section
			return false;
		}

		// User is not restricted by plugin, normal meta_caps apply
		return true;

	}

	/**
	 * Filter that modifies the caps based on the current state.
	 *
	 * @todo clean up all of this logic, figure out best approach to drafts
	 *
	 * @param array $caps
	 * @param string $cap
	 * @param int $user_id
	 * @param mixed $args
	 * @return array
	 */
	static function map_meta_cap($caps, $cap, $user_id, $args) {
		global $post_ID;

		// only add custom mapping to Section Editors
		if( ! BU_Section_Editing_Plugin::is_allowed_user( $user_id ) ) {
			return $caps;
		}
		// edit_page and delete_page get a post ID passed, but publish does not
		if( isset( $args[0] ) ) {
			$id = $args[0];
		}

		// get all post types because section editors start with no rights
		$post_types = get_post_types(null, 'objects');

		// Override normal edit post permissions
		if( in_array( $cap, self::get_caps_for_post_types( 'edit_post', $post_types ) ) ) {
			$parent_id = null;
			$post = get_post($id);
			$post_type = get_post_type($post);
			$post_type = get_post_type_object($post_type);

			if($post_type->hierarchical != true) {
				if(self::can_edit($user_id, $id)) {
					$caps = array('edit_published_in_section');
				}
			} else {
				// Post parent has switched
				if(isset($_POST['post_ID']) && $id == $_POST['post_ID'] && isset($_POST['parent_id']) &&  $post->post_parent != $_POST['parent_id']) {
					$parent_id = (int) $_POST['parent_id'];

					if( $post->post_status == 'publish' && self::can_edit($user_id, $parent_id)) {
						$caps = array('edit_published_in_section');
					}

				}

				if($id && $post->post_status == 'publish' && self::can_edit($user_id, $id)) {
					$caps = array('edit_published_in_section');
				}
			}


			return $caps;
		}

		if( in_array( $cap, self::get_caps_for_post_types( 'delete_post', $post_types ) ) ) {
			$post = get_post($id);
			$post_type = get_post_type($post);
			$post_type = get_post_type_object($post_type);

			if($post_type->hierarchical != true) {
				if(self::can_edit($user_id, $id)) {
					$caps = array('delete_published_in_section');
				}
			} else {
				if($id && $post->post_status == 'publish' && self::can_edit($user_id, $id)) {
					$caps = array('delete_published_in_section');
				}
			}
			return $caps;
		}

		// As publish_posts does not come tied to a post ID, relying on the global $post_ID is fragile
		// For instance, the "Quick Edit" interface of the edit posts page does not populate this
		// global, and therefore the "Published" status is unavailable with this meta_cap check in place
		if( in_array( $cap, self::get_caps_for_post_types( 'publish_posts', $post_types ) ) ) {
			$parent_id = null;
			if(isset($post_ID)) {
				$id = $post_ID;
			}
			if(!isset($id)) {
				return $caps;
			}
			$post = get_post($id);
			$post_type = get_post_type($post);
			$post_type = get_post_type_object($post_type);
			$is_alt = false;
			
			// BU Versions uses the post_parent to relate the alternate version 
			// to the original
			if(class_exists('BU_Version_Workflow')) {
				$is_alt = BU_Version_Workflow::$v_factory->is_alt(get_post_type($post));
			}

			if($post_type->hierarchical != true && $is_alt != true) {
				if(self::can_edit($user_id, $id)) {
					$caps = array('publish_in_section');
				}
			} else {
				// User is attempting to switch post parent while publishing
				if(isset($_POST['post_ID']) && $id == $_POST['post_ID'] && isset($_POST['parent_id']) && $post->post_parent != $_POST['parent_id']) {

					$parent_id = (int) $_POST['parent_id'];

					// Can't move published posts under sections they can't edit
					if( self::can_edit( $user_id, $parent_id ) ) {
						$caps = array('publish_in_section');
					}

				} else {
					if ( isset($id) && self::can_edit($user_id, $post->post_parent ) ) {
						$caps = array('publish_in_section');
					}
				}
			}
			return $caps;
		}
		return $caps;

	}

	public function get_caps_for_post_types( $cap, $post_types ) {

		$caps = array();

		foreach( $post_types as $post_type ) {
			if( isset( $post_type->cap->$cap ))
				$caps[] = $post_type->cap->$cap;
		}

		return $caps;

	}

}

?>
