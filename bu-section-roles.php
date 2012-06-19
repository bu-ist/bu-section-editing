<?php

/**
 * Temporary class until a good solution for creating roles / assigning
 * capabilities is arrived upon
 */
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


		// the following roles are overriden by the section editor functionality
		$role->add_cap('edit_published_pages');
		$role->add_cap('publish_pages');
		$role->add_cap('delete_others_pages');
		$role->add_cap('delete_published_pages');
		$role->add_cap('delete_pages');


		$role->add_cap('edit_posts');
		$role->add_cap('edit_others_posts');

		// the following roles are overriden by the section editor functionality
		$role->add_cap('edit_published_posts');
		$role->add_cap('publish_posts');
		$role->add_cap('delete_posts');
		$role->add_cap('delete_others_posts');
		$role->add_cap('delete_published_posts');

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

/**
 * Section Editor
 */
class BU_Section_Editor {

	/**
	 * Checks whether or not a specific user can edit a post
	 *
	 * @param int $user_id
	 * @param int $post_id
	 * @param int $parent_id
	 */
	static function can_edit($user_id, $post_id, $parent_id = null)  {

		if($user_id == 0) return false;

		// Extra checks for any "allowed" users
		if( BU_Section_Editing_Plugin::is_allowed_user( $user_id ) ) {

			// Get groups associated with post
			$post_groups = get_post_meta( $post_id, BU_Edit_Group::META_KEY );

			// Get all groups for this user
			$edit_groups_o = BU_Edit_Groups::get_instance();
			$groups = $edit_groups_o->find_groups_for_user( $user_id );

			if(empty($groups)) {
				return false;
			}

			foreach( $groups as $key => $group ) {

				// This group is good, bail here
				if( in_array( (string) $group->id, $post_groups ) ) {
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
	 * @param type $caps
	 * @param type $cap
	 * @param type $user_id
	 * @param type $args
	 * @return string
	 */
	static function map_meta_cap($caps, $cap, $user_id, $args) {
		global $post_ID;

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

			if(isset($_POST['post_ID']) && $id == $_POST['post_ID'] && isset($_POST['parent_id']) &&  $post->post_parent != $_POST['parent_id']) {
				$parent_id = (int) $_POST['parent_id'];
			}
			if($id && $post->post_status == 'publish' && ! self::can_edit($user_id, $id, $parent_id)) {
				$caps = array('do_not_allow');
			}
			return $caps;
		}

		if( in_array( $cap, self::get_caps_for_post_types( 'delete_post', $post_types ) ) ) {
			if($id && ! self::can_edit($user_id, $id)) {
				$caps = array('do_not_allow');
			}
			return $caps;
		}

		if( in_array( $cap, self::get_caps_for_post_types( 'publish_posts', $post_types ) ) ) {
			$parent_id = null;
			$id = $post_ID;
			$post = get_post($id);
			if(isset($_POST['post_ID']) && $id == $_POST['post_ID'] && isset($_POST['parent_id']) && $post->post_parent != $_POST['parent_id']) {
				$parent_id = (int) $_POST['parent_id'];
			}
			if (!isset($id) || !self::can_edit($user_id, $id, $parent_id)) {
				$caps = array('do_not_allow');
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
